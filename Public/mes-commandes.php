<?php

require_once __DIR__ . '/../Config/database.php';

requireLogin();

$user_id = $_SESSION['user_id'];

$sql = "
    SELECT
        menus.titre,
        commandes.id,
        commandes.nb_personnes,
        commandes.prix_total,
        commandes.statut,
        commandes.date_prestation,
        commandes.heure_prestation,
        commandes.lieu_prestation,
        commandes.adresse_prestation,
        commandes.frais_livraison,
        commandes.remise_pourcentage,
        commandes.created_at,
        (SELECT COUNT(*) FROM avis WHERE avis.commande_id = commandes.id) AS avis_depose
    FROM commandes

    INNER JOIN menus
        ON commandes.menu_id = menus.id

    WHERE commandes.user_id = :user_id

    ORDER BY commandes.created_at DESC
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ':user_id' => $user_id
]);

$commandes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$sqlHistorique = "
    SELECT
        historique_statuts.commande_id,
        historique_statuts.statut,
        historique_statuts.date_modification
    FROM historique_statuts

    INNER JOIN commandes
        ON historique_statuts.commande_id = commandes.id

    WHERE commandes.user_id = :user_id

    ORDER BY historique_statuts.date_modification ASC
";

$stmtHistorique = $pdo->prepare($sqlHistorique);

$stmtHistorique->execute([
    ':user_id' => $user_id
]);

$historiques = $stmtHistorique->fetchAll(PDO::FETCH_ASSOC);

$historiquesParCommande = [];

foreach ($historiques as $historique) {

    $commandeId = $historique['commande_id'];

    $historiquesParCommande[$commandeId][] = $historique;
}

?>

<?php require_once __DIR__ . '/../Templates/layout.php'; renderHeader('Mes commandes'); ?>

    <section class="content-panel">

        <h1>Mes commandes</h1>

        <?php if (empty($commandes)): ?>

            <p>
                Aucune commande pour le moment.
            </p>

        <?php else: ?>

            <?php foreach ($commandes as $commande): ?>

                <div class="commande">

                    <h2>
                        <?php
                        echo htmlspecialchars(
                            $commande['titre']
                        );
                        ?>
                    </h2>

                    <p>
                        Numéro de commande :
                        <?php echo (int) $commande['id']; ?>
                    </p>

                    <p>
                        Nombre de personnes :
                        <?php echo (int) $commande['nb_personnes']; ?>
                    </p>

                    <p>
                        Prix total :
                        <?php
                        echo number_format(
                            $commande['prix_total'],
                            2,
                            ',',
                            ' '
                        );
                        ?>
                        €
                    </p>

                    <p>
                        Date de prestation :
                        <?php
                        echo !empty($commande['date_prestation'])
                            ? htmlspecialchars(
                                $commande['date_prestation']
                            )
                            : 'Non renseignée';
                        ?>
                    </p>

                    <p>
                        Heure :
                        <?php
                        echo !empty($commande['heure_prestation'])
                            ? htmlspecialchars(
                                $commande['heure_prestation']
                            )
                            : 'Non renseignée';
                        ?>
                    </p>

                    <p>
                        Lieu :
                        <?php
                        echo !empty($commande['lieu_prestation'])
                            ? htmlspecialchars(
                                $commande['lieu_prestation']
                            )
                            : 'Non renseigné';
                        ?>
                    </p>

                    <p>
                        Adresse :
                        <?php
                        echo !empty($commande['adresse_prestation'])
                            ? htmlspecialchars(
                                $commande['adresse_prestation']
                            )
                            : 'Non renseignée';
                        ?>
                    </p>

                    <p>
                        Frais de livraison :
                        <?php
                        echo number_format(
                            $commande['frais_livraison'],
                            2,
                            ',',
                            ' '
                        );
                        ?>
                        €
                    </p>

                    <p>
                        Remise :
                        <?php
                        echo number_format(
                            $commande['remise_pourcentage'],
                            0
                        );
                        ?>
                        %
                    </p>

                    <p>
                        Créée le :
                        <?php
                        echo htmlspecialchars(
                            $commande['created_at']
                        );
                        ?>
                    </p>

                    <p class="statut">

                        Statut actuel :

                        <?php
                        echo htmlspecialchars(
                            $commande['statut']
                        );
                        ?>

                    </p>

                    <!-- Historique des statuts -->

                    <div class="historique">

                        <h3>Suivi de la commande</h3>

                        <?php
                        $commandeId = $commande['id'];
                        ?>

                        <?php
                        if (
                            !empty(
                                $historiquesParCommande[$commandeId]
                            )
                        ):
                        ?>

                            <ul>

                                <?php
                                foreach (
                                    $historiquesParCommande[$commandeId]
                                    as $historique
                                ):
                                ?>

                                    <li>

                                        <strong>
                                            <?php
                                            echo htmlspecialchars(
                                                $historique['statut']
                                            );
                                            ?>
                                        </strong>

                                        —

                                        <?php
                                        echo htmlspecialchars(
                                            $historique['date_modification']
                                        );
                                        ?>

                                    </li>

                                <?php endforeach; ?>

                            </ul>

                        <?php else: ?>

                            <p>
                                Aucun changement de statut
                                pour le moment.
                            </p>

                        <?php endif; ?>

                    </div>

                    <!--
                    Modifier / annuler uniquement
                    tant que la commande est en attente
                    -->

                    <?php if ($commande['statut'] === 'en attente'): ?>

                        <div class="actions">

                            <a
                                href="modifier-commande.php?id=<?php
                                echo (int) $commande['id'];
                                ?>"
                            >
                                Modifier la commande
                            </a>

                            <form method="post" action="supprimer-commande.php" class="inline-form">
                                <?= csrfInput() ?>
                                <input type="hidden" name="id" value="<?= (int) $commande['id'] ?>">
                                <button type="submit" class="danger">Annuler la commande</button>
                            </form>

                        </div>

                    <?php else: ?>

                        <p>
                            Cette commande ne peut plus
                            être modifiée ou annulée en ligne.
                        </p>

                    <?php endif; ?>

                    <!--
                    Avis possible uniquement
                    lorsque la commande est terminée
                    -->

                    <?php if ($commande['statut'] === 'terminée' && !(int) $commande['avis_depose']): ?>

                        <div class="actions">

                            <a
                                href="laisser-avis.php?id=<?php
                                echo (int) $commande['id'];
                                ?>"
                            >
                                Laisser un avis
                            </a>

                        </div>

                    <?php endif; ?>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>

    </section>

<?php renderFooter($pdo); ?>
