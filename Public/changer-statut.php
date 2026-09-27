<?php

session_start();

require_once '../Config/database.php';
require_once '../Config/mail.php';


// 1. Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'], $_SESSION['role'])) {

    header("Location: login.php");
    exit;
}


// 2. Seuls les employés et administrateurs
// peuvent modifier le statut d'une commande
if (
    $_SESSION['role'] !== 'admin'
    && $_SESSION['role'] !== 'employe'
) {

    exit("Accès refusé.");
}


// 3. Récupérer les paramètres
$id = $_GET['id'] ?? null;
$statut = $_GET['statut'] ?? null;


// 4. Vérifier l'identifiant
if (!$id || !is_numeric($id)) {

    exit("ID de commande invalide.");
}

$id = (int) $id;


// 5. Vérifier le statut
if (!$statut) {

    exit("Statut manquant.");
}


$statut = strtolower(
    trim($statut)
);


$statutsAutorises = [
    "accepté",
    "en préparation",
    "en cours de livraison",
    "livré",
    "en attente du retour de matériel",
    "terminée"
];


if (
    !in_array(
        $statut,
        $statutsAutorises,
        true
    )
) {

    exit("Statut invalide.");
}


// --------------------------------------------------
// 6. RÉCUPÉRER LA COMMANDE + CLIENT + MENU
// --------------------------------------------------

$sqlCommande = "
    SELECT
        commandes.id,
        commandes.statut,
        commandes.date_debut_attente_retour,
        commandes.materiel_retourne,

        users.nom,
        users.prenom,
        users.email,

        menus.titre AS menu_titre

    FROM commandes

    INNER JOIN users
        ON users.id = commandes.user_id

    INNER JOIN menus
        ON menus.id = commandes.menu_id

    WHERE commandes.id = :id
";


$stmtCommande =
    $pdo->prepare(
        $sqlCommande
    );


$stmtCommande->execute([
    ':id' => $id
]);


$commande =
    $stmtCommande->fetch(
        PDO::FETCH_ASSOC
    );


if (!$commande) {

    exit("Commande introuvable.");
}


// Éviter de créer deux fois
// le même historique
if (
    $commande['statut']
    === $statut
) {

    header(
        "Location: admin-commandes.php"
    );

    exit;
}


// --------------------------------------------------
// MODIFICATION DU STATUT
// --------------------------------------------------

try {

    $pdo->beginTransaction();


    /*
     * Si la commande passe en attente
     * du retour de matériel,
     * on enregistre la date de début.
     */
    if (
        $statut ===
        'en attente du retour de matériel'
    ) {

        $sqlUpdate = "
            UPDATE commandes

            SET
                statut = :statut,

                date_debut_attente_retour =
                    COALESCE(
                        date_debut_attente_retour,
                        NOW()
                    ),

                materiel_retourne = 0,

                date_retour_materiel = NULL,

                frais_retard_materiel = 0

            WHERE id = :id
        ";

    } else {

        $sqlUpdate = "
            UPDATE commandes

            SET statut = :statut

            WHERE id = :id
        ";
    }


    $stmtUpdate =
        $pdo->prepare(
            $sqlUpdate
        );


    $stmtUpdate->execute([
        ':statut' => $statut,
        ':id' => $id
    ]);


    // Ajouter le changement à l'historique
    $sqlHistorique = "
        INSERT INTO historique_statuts (
            commande_id,
            statut,
            modifie_par
        )

        VALUES (
            :commande_id,
            :statut,
            :modifie_par
        )
    ";


    $stmtHistorique =
        $pdo->prepare(
            $sqlHistorique
        );


    $stmtHistorique->execute([

        ':commande_id' =>
            $id,

        ':statut' =>
            $statut,

        ':modifie_par' =>
            $_SESSION['user_id']
    ]);


    $pdo->commit();


} catch (PDOException $e) {


    if (
        $pdo->inTransaction()
    ) {

        $pdo->rollBack();
    }


    error_log(
        "Erreur changement statut commande : "
        . $e->getMessage()
    );


    exit(
        "Une erreur est survenue lors du changement de statut."
    );
}


// --------------------------------------------------
// EMAIL POUR LAISSER UN AVIS
// --------------------------------------------------


if ($statut === 'terminée') {


    $baseUrl =
        defined('APP_URL')
        ? APP_URL
        : 'http://vite-gourmand.local';


    $lienAvis =
        rtrim(
            $baseUrl,
            '/'
        )
        . '/laisser-avis.php?id='
        . $id;


    $prenom =
        htmlspecialchars(
            $commande['prenom']
        );


    $menuTitre =
        htmlspecialchars(
            $commande['menu_titre']
        );


    $lienAvisHtml =
        htmlspecialchars(
            $lienAvis,
            ENT_QUOTES
        );


    $contenuEmail = "
        <h2>Votre avis compte pour nous</h2>

        <p>
            Bonjour {$prenom},
        </p>

        <p>
            Votre commande pour le menu
            <strong>{$menuTitre}</strong>
            est maintenant terminée.
        </p>

        <p>
            Nous espérons que votre expérience
            avec Vite & Gourmand vous a plu.
        </p>

        <p>
            Vous pouvez maintenant laisser
            une note et un commentaire.
        </p>

        <p>
            <a href=\"{$lienAvisHtml}\">
                Laisser mon avis
            </a>
        </p>

        <p>
            Merci pour votre confiance.
        </p>

        <p>
            À bientôt,<br>
            L'équipe Vite & Gourmand
        </p>
    ";


    envoyerEmail(

        $commande['email'],

        $commande['prenom']
            . ' '
            . $commande['nom'],

        'Donnez votre avis sur votre commande',

        $contenuEmail
    );
}


// Redirection finale
header(
    "Location: admin-commandes.php"
);

exit;