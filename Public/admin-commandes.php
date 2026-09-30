<?php

require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/../Services/OrderRules.php';

requireAdminOrEmployee();

$client = trim($_GET['client'] ?? '');
$statut = trim($_GET['statut'] ?? '');

$sql = '
    SELECT
        commandes.*,
        menus.titre,
        users.email,
        users.nom,
        users.prenom
    FROM commandes

    INNER JOIN menus
        ON commandes.menu_id = menus.id

    INNER JOIN users
        ON commandes.user_id = users.id
';

$conditions = [];
$params = [];

// Filtre client
if ($client !== '') {
    $conditions[] = '
        (
            users.nom LIKE :client
            OR users.prenom LIKE :client_prenom
            OR users.email LIKE :client_email
        )
    ';

    $params[':client'] = '%' . $client . '%';
    $params[':client_prenom'] = $params[':client'];
    $params[':client_email'] = $params[':client'];
}

// Filtre statut
if ($statut !== '') {
    $conditions[] = '
        commandes.statut = :statut
    ';

    $params[':statut'] = $statut;
}

// Ajouter WHERE si nécessaire
if (!empty($conditions)) {
    $sql .= '
        WHERE '
        . implode(
            ' AND ',
            $conditions
        );
}

$sql .= '
    ORDER BY commandes.created_at DESC
';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$commandes = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<?php require_once __DIR__ . '/../Templates/layout.php';
renderHeader('Gestion des commandes'); ?>
<section class="content-panel">
    <h1>
        Gestion des commandes
    </h1>
    <!-- Filtres -->
    <section class="filtres">
        <h2>
            Rechercher une commande
        </h2>
        <form
            method="GET"
            action="admin-commandes.php"
        >
            <div class="champ">
                <label for="client">
                    Client
                </label>
                <input
                    type="text"
                    id="client"
                    name="client"
                    placeholder="Nom, prénom ou email"
                    value="<?= htmlspecialchars($client) ?>"
                >
            </div>
            <div class="champ">
                <label for="statut">
                    Statut
                </label>
                <select
                    id="statut"
                    name="statut"
                >
                    <option value="">
                        Tous les statuts
                    </option>
                    <option
                        value="en attente"
                        <?php
    if ($statut === 'en attente') {
        echo 'selected';
    }
?>
                    >
                        En attente
                    </option>
                    <option
                        value="accepté"
                        <?php
if ($statut === 'accepté') {
    echo 'selected';
}
?>
                    >
                        Accepté
                    </option>
                    <option
                        value="en préparation"
                        <?php
if ($statut === 'en préparation') {
    echo 'selected';
}
?>
                    >
                        En préparation
                    </option>
                    <option
                        value="en cours de livraison"
                        <?php
if (
    $statut
    === 'en cours de livraison'
) {
    echo 'selected';
}
?>
                    >
                        En cours de livraison
                    </option>
                    <option
                        value="livré"
                        <?php
if ($statut === 'livré') {
    echo 'selected';
}
?>
                    >
                        Livré
                    </option>
                    <option
                        value="en attente du retour de matériel"
                        <?php
if (
    $statut
    === 'en attente du retour de matériel'
) {
    echo 'selected';
}
?>
                    >
                        En attente du retour de matériel
                    </option>
                    <option
                        value="terminée"
                        <?php
if ($statut === 'terminée') {
    echo 'selected';
}
?>
                    >
                        Terminée
                    </option>
                    <option
                        value="annulée"
                        <?php
if ($statut === 'annulée') {
    echo 'selected';
}
?>
                    >
                        Annulée
                    </option>
                </select>
            </div>
            <button type="submit">
                Filtrer
            </button>
            <a
                class="reset"
                href="admin-commandes.php"
            >
                Réinitialiser
            </a>
        </form>
    </section>
    <!-- Commandes -->
    <?php if (empty($commandes)): ?>
        <p>
            Aucune commande ne correspond à votre recherche.
        </p>
    <?php else: ?>
        <p>
            <?= count($commandes) ?>

            commande(s) trouvée(s).

        </p>
        <?php foreach ($commandes as $commande): ?>
            <div class="commande">
                <h2>

                    Commande n°

                    <?= (int) $commande['id'] ?>
                </h2>
                <h3>

                    Menu :

                    <?= htmlspecialchars($commande['titre']) ?>
                </h3>
                <p>

                    Client :

                    <?= htmlspecialchars($commande['prenom'] . ' ' . $commande['nom']) ?>
                </p>
                <p>

                    Email :

                    <?= htmlspecialchars($commande['email']) ?>
                </p>
                <p>

                    Nombre de personnes :

                    <?= (int) $commande['nb_personnes'] ?>
                </p>
                <p>

                    Date :

                    <?= !empty($commande['date_prestation']) ? htmlspecialchars($commande['date_prestation']) : 'Non renseignée' ?>
                </p>
                <p>

                    Heure :

                    <?= !empty($commande['heure_prestation']) ? htmlspecialchars($commande['heure_prestation']) : 'Non renseignée' ?>
                </p>
                <p>

                    Lieu :

                    <?= !empty($commande['lieu_prestation']) ? htmlspecialchars($commande['lieu_prestation']) : 'Non renseigné' ?>
                </p>
                <p>

                    Adresse :

                    <?= !empty($commande['adresse_prestation']) ? htmlspecialchars($commande['adresse_prestation']) : 'Non renseignée' ?>
                </p>
                <p>

                    Prix total :

                    <?= number_format($commande['prix_total'], 2, ',', ' ') ?>

                    €

                </p>
                <p class="statut">

                    Statut :

                    <?= htmlspecialchars($commande['statut']) ?>
                </p>
                <?php
                if (
                    !empty(
                        $commande['date_debut_attente_retour']
                    )
                ):
                    ?>
                    <p>

                        Début attente retour matériel :

                        <?= htmlspecialchars($commande[ 'date_debut_attente_retour' ]) ?>
                    </p>
                <?php endif; ?>
                <?php
                if (
                    (int) $commande['materiel_retourne']
                    === 1
                ):
                    ?>
                    <p>
                        <strong>
                            Matériel retourné :
                        </strong>

                        Oui

                    </p>
                    <p>

                        Date du retour :

                        <?= htmlspecialchars($commande[ 'date_retour_materiel' ] ?? '') ?>
                    </p>
                    <?php
                    if (
                        (float)
                        $commande[
                            'frais_retard_materiel'
                        ] > 0
                    ):
                        ?>
                        <p>
                            <strong>
                                Frais de retard matériel :
                            </strong>
                            <?= number_format($commande[ 'frais_retard_materiel' ], 2, ',', ' ') ?>

                            €

                        </p>
                    <?php endif; ?>
                <?php endif; ?>
                <!-- Actions -->
                <?php
                if (
                    $commande['statut'] !== 'terminée'
                    && $commande['statut'] !== 'annulée'
                ):
                    ?>
                    <div class="actions">
                        <?php foreach (allowedOrderTransitions($commande['statut']) as $nextStatus): ?>
                        <form method="post" action="changer-statut.php" class="inline-form">
                            <?= csrfInput() ?>
                            <input type="hidden" name="id" value="<?= (int) $commande['id'] ?>">
                            <input type="hidden" name="statut" value="<?= e($nextStatus) ?>">
                            <button type="submit"><?= $nextStatus === 'terminée' ? 'Terminer sans prêt de matériel' : e(ucfirst($nextStatus)) ?></button>
                        </form>
                        <?php endforeach; ?>
                        <?php
                            if (
                                $commande['statut']
                                ===
                                'en attente du retour de matériel'
                            ):
                                ?>
                            <a
                                class="retour-materiel"
                                href="retour-materiel.php?id=<?= (int) $commande['id'] ?>"
                            >
                                Matériel retourné
                            </a>
                        <?php endif; ?>
                        <a
                            class="annuler"
                            href="annuler-commande-employe.php?id=<?= (int) $commande['id'] ?>"
                        >
                            Annuler la commande
                        </a>
                    </div>
                <?php endif; ?>
                <!-- Informations d'annulation -->
                <?php
                if ($commande['statut'] === 'annulée'):
                    ?>
                    <div class="info-annulation">
                        <p>
                            <strong>
                                Commande annulée.
                            </strong>
                        </p>
                        <?php
                            if (
                                !empty(
                                    $commande[
                                        'mode_contact_annulation'
                                    ]
                                )
                            ):
                                ?>
                            <p>

                                Contact client :

                                <?= htmlspecialchars($commande[ 'mode_contact_annulation' ]) ?>
                            </p>
                        <?php endif; ?>
                        <?php
                        if (
                            !empty(
                                $commande[
                                    'motif_annulation'
                                ]
                            )
                        ):
                            ?>
                            <p>

                                Motif :

                                <?= htmlspecialchars($commande[ 'motif_annulation' ]) ?>
                            </p>
                        <?php endif; ?>
                        <?php
                        if (
                            !empty(
                                $commande[
                                    'date_annulation'
                                ]
                            )
                        ):
                            ?>
                            <p>

                                Date d'annulation :

                                <?= htmlspecialchars($commande[ 'date_annulation' ]) ?>
                            </p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</section>
<?php renderFooter($pdo); ?>
