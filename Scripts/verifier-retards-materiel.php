<?php

require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/../Config/mail.php';


// --------------------------------------------------
// CALCUL DES JOURS OUVRÉS
// --------------------------------------------------

function calculerJoursOuvres(
    string $dateDebut,
    string $dateFin
): int {

    $debut = new DateTime($dateDebut);
    $fin = new DateTime($dateFin);

    // On commence à compter le lendemain.
    $debut->modify('+1 day');

    $joursOuvres = 0;

    while (
        $debut->setTime(0, 0)
        <= $fin->setTime(0, 0)
    ) {

        $jourSemaine =
            (int) $debut->format('N');

        // 1 = lundi
        // 5 = vendredi
        if ($jourSemaine <= 5) {
            $joursOuvres++;
        }

        $debut->modify('+1 day');
    }

    return $joursOuvres;
}


// --------------------------------------------------
// COMMANDES EN ATTENTE DU MATÉRIEL
// --------------------------------------------------

$sql = "
    SELECT
        commandes.id,
        commandes.date_debut_attente_retour,
        commandes.notification_retard_envoyee,

        users.nom,
        users.prenom,
        users.email,

        menus.titre

    FROM commandes

    INNER JOIN users
        ON users.id = commandes.user_id

    INNER JOIN menus
        ON menus.id = commandes.menu_id

    WHERE commandes.statut =
        'en attente du retour de matériel'

    AND commandes.materiel_retourne = 0

    AND commandes.date_debut_attente_retour
        IS NOT NULL

    AND commandes.notification_retard_envoyee = 0
";


$stmt = $pdo->query($sql);

$commandes =
    $stmt->fetchAll(
        PDO::FETCH_ASSOC
    );


$maintenant =
    new DateTime();


// --------------------------------------------------
// ANALYSE DES RETARDS
// --------------------------------------------------

foreach ($commandes as $commande) {

    $joursOuvres =
        calculerJoursOuvres(
            $commande[
                'date_debut_attente_retour'
            ],
            $maintenant->format(
                'Y-m-d H:i:s'
            )
        );


    /*
     * Le délai autorisé est de
     * 10 jours ouvrés.
     *
     * À partir du moment où ce délai
     * est dépassé, les frais sont de 600 €.
     */
    if ($joursOuvres <= 10) {

        continue;
    }


    // --------------------------------------------------
    // ENREGISTRER LES FRAIS
    // --------------------------------------------------

    $sqlFrais = "
        UPDATE commandes

        SET frais_retard_materiel = 600

        WHERE id = :id
        AND materiel_retourne = 0
    ";


    $stmtFrais =
        $pdo->prepare(
            $sqlFrais
        );


    $stmtFrais->execute([
        ':id' => $commande['id']
    ]);


    // --------------------------------------------------
    // EMAIL CLIENT
    // --------------------------------------------------

    $prenom =
        htmlspecialchars(
            $commande['prenom']
        );


    $titreMenu =
        htmlspecialchars(
            $commande['titre']
        );


    $contenuEmail = "
        <h2>Retard de retour du matériel</h2>

        <p>
            Bonjour {$prenom},
        </p>

        <p>
            Le délai de 10 jours ouvrés
            pour le retour du matériel lié
            à votre commande
            <strong>{$titreMenu}</strong>
            est maintenant dépassé.
        </p>

        <p>
            Conformément aux conditions
            de la prestation,
            des frais de
            <strong>600 €</strong>
            sont désormais appliqués.
        </p>

        <p>
            Merci de contacter
            Vite & Gourmand afin
            d'organiser le retour
            du matériel dans les meilleurs délais.
        </p>

        <p>
            L'équipe Vite & Gourmand
        </p>
    ";


    $emailEnvoye =
        envoyerEmail(
            $commande['email'],
            $commande['prenom']
                . ' '
                . $commande['nom'],
            'Retard de retour du matériel - 600 €',
            $contenuEmail
        );


    // --------------------------------------------------
    // MARQUER LA NOTIFICATION COMME ENVOYÉE
    // --------------------------------------------------

    if ($emailEnvoye) {

        $sqlNotification = "
            UPDATE commandes

            SET
                notification_retard_envoyee = 1,
                date_notification_retard = NOW()

            WHERE id = :id
        ";


        $stmtNotification =
            $pdo->prepare(
                $sqlNotification
            );


        $stmtNotification->execute([
            ':id' => $commande['id']
        ]);


        echo
            "Commande n°"
            . $commande['id']
            . " : notification envoyée."
            . PHP_EOL;

    } else {

        echo
            "Commande n°"
            . $commande['id']
            . " : échec de l'envoi de l'email."
            . PHP_EOL;
    }
}


echo
    "Vérification des retards terminée."
    . PHP_EOL;