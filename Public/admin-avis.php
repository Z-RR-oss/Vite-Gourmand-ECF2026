<?php

require_once __DIR__ . '/../Config/database.php';

requireAdminOrEmployee();

$sql = '
    SELECT
        avis.id,
        avis.note,
        avis.commentaire,
        avis.statut_validation,
        avis.created_at,
        users.nom,
        users.prenom,
        users.email,
        menus.titre
    FROM avis

    INNER JOIN users
        ON avis.user_id = users.id

    INNER JOIN commandes
        ON avis.commande_id = commandes.id

    INNER JOIN menus
        ON commandes.menu_id = menus.id

    ORDER BY avis.created_at DESC
';

$stmt = $pdo->prepare($sql);
$stmt->execute();

$avis = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<?php require_once __DIR__ . '/../Templates/layout.php';
renderHeader('Gestion des avis'); ?>
    <section class="content-panel">
        <h1>
            Gestion des avis clients
        </h1>
        <?php if (empty($avis)): ?>
            <p>
                Aucun avis pour le moment.
            </p>
        <?php else: ?>
            <?php foreach ($avis as $unAvis): ?>
                <div class="avis">
                    <h2>
                        <?= htmlspecialchars($unAvis['titre']) ?>
                    </h2>
                    <p>
                        Client :
                        <strong>
                            <?= htmlspecialchars($unAvis['prenom']) ?>
                            <?= htmlspecialchars($unAvis['nom']) ?>
                        </strong>
                    </p>
                    <p>
                        Email :
                        <?= htmlspecialchars($unAvis['email']) ?>
                    </p>
                    <p class="note">

                        Note :

                        <?= (int) $unAvis['note'] ?>

                        / 5 ⭐

                    </p>
                    <p>
                        Commentaire :
                    </p>
                    <p>
                        <?= nl2br(htmlspecialchars($unAvis['commentaire'])) ?>
                    </p>
                    <p>
                        Avis envoyé le :
                        <?= htmlspecialchars($unAvis['created_at']) ?>
                    </p>
                    <p class="statut">

                        Statut :

                        <?= htmlspecialchars($unAvis['statut_validation']) ?>
                    </p>
                    <?php
                    if (
                        $unAvis['statut_validation']
                        === 'en attente'
                    ):
                        ?>
                        <div class="actions">
                            <form method="post" action="changer-avis.php" class="inline-form">
                                <?= csrfInput() ?>
                                <input type="hidden" name="id" value="<?= (int) $unAvis['id'] ?>">
                                <input type="hidden" name="statut" value="validé">
                                <button type="submit">Valider</button>
                            </form>
                            <form method="post" action="changer-avis.php" class="inline-form">
                                <?= csrfInput() ?>
                                <input type="hidden" name="id" value="<?= (int) $unAvis['id'] ?>">
                                <input type="hidden" name="statut" value="refusé">
                                <button type="submit">Refuser</button>
                            </form>
                        </div>
                    <?php else: ?>
                        <p>
                            Cet avis a déjà été traité.
                        </p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>
<?php renderFooter($pdo); ?>
