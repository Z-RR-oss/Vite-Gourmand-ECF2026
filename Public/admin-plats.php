<?php

require_once __DIR__ . '/../Config/database.php';

// Vérifier la connexion
requireLogin();

// Autoriser uniquement admin et employé
requireAdminOrEmployee();

// Récupérer les plats + allergènes associés
$sql = "
    SELECT
        plats.id,
        plats.nom,
        plats.description,
        plats.type_plat,

        GROUP_CONCAT(
            DISTINCT allergenes.nom
            ORDER BY allergenes.nom
            SEPARATOR ', '
        ) AS allergenes

    FROM plats

    LEFT JOIN plat_allergene
        ON plats.id = plat_allergene.plat_id

    LEFT JOIN allergenes
        ON plat_allergene.allergene_id = allergenes.id

    GROUP BY
        plats.id,
        plats.nom,
        plats.description,
        plats.type_plat

    ORDER BY plats.id DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute();

$plats = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<?php require_once __DIR__ . '/../Templates/layout.php'; renderHeader('Gestion des plats'); ?>

<section class="content-panel">

    <h1>
        Gestion des plats
    </h1>

    <div class="header-actions">

        <a
            class="bouton"
            href="ajouter-plat.php"
        >
            + Ajouter un plat
        </a>

    </div>

    <?php if (empty($plats)): ?>

        <p>
            Aucun plat enregistré.
        </p>

    <?php else: ?>

        <?php foreach ($plats as $plat): ?>

            <article class="plat">

                <h2>

                    <?php
                    echo htmlspecialchars(
                        $plat['nom']
                    );
                    ?>

                </h2>

                <p class="type">

                    <?php
                    echo htmlspecialchars(
                        $plat['type_plat']
                    );
                    ?>

                </p>

                <p>

                    <?php
                    echo nl2br(
                        htmlspecialchars(
                            $plat['description'] ?? ''
                        )
                    );
                    ?>

                </p>

                <p>

                    <strong>
                        Allergènes :
                    </strong>

                    <?php
                    echo !empty($plat['allergenes'])
                        ? htmlspecialchars(
                            $plat['allergenes']
                        )
                        : 'Aucun allergène renseigné';
                    ?>

                </p>

                <div class="actions">

                    <a
                        href="modifier-plat.php?id=<?php
                        echo (int) $plat['id'];
                        ?>"
                    >
                        Modifier
                    </a>

                    <a
                        class="supprimer"
                        href="supprimer-plat.php?id=<?php
                        echo (int) $plat['id'];
                        ?>"
                    >
                        Supprimer
                    </a>

                </div>

            </article>

        <?php endforeach; ?>

    <?php endif; ?>

</section>

<?php renderFooter($pdo); ?>
