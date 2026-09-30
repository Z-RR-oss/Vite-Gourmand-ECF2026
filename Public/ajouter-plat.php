<?php

require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/../Services/CatalogueValidation.php';

requireAdminOrEmployee();

$sqlAllergenes = '
    SELECT id, nom
    FROM allergenes
    ORDER BY nom ASC
';

$stmtAllergenes = $pdo->prepare(
    $sqlAllergenes
);

$stmtAllergenes->execute();

$allergenes = $stmtAllergenes->fetchAll(
    PDO::FETCH_ASSOC
);

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim(
        $_POST['nom'] ?? ''
    );

    $description = trim(
        $_POST['description'] ?? ''
    );

    $typePlat = trim(
        $_POST['type_plat'] ?? ''
    );

    $allergenesSelectionnes =
        $_POST['allergenes'] ?? [];

    $nouveauxAllergenes = trim(
        $_POST['nouveaux_allergenes'] ?? ''
    );

    $typesAutorises = [
        'entree',
        'plat',
        'dessert'
    ];

    if ($nom === '') {
        $erreur =
            'Le nom du plat est obligatoire.';
    } elseif (
        !in_array(
            $typePlat,
            $typesAutorises,
            true
        )
    ) {
        $erreur =
            'Le type de plat est invalide.';
    }

    $erreur = dishValidationError($_POST, $allergenes) ?? $erreur;
    $allergenesSelectionnes = is_array($allergenesSelectionnes) ? array_unique($allergenesSelectionnes) : [];

    if ($erreur === '') {
        try {
            $pdo->beginTransaction();

            // Ajouter le plat
            $sqlInsertPlat = '
                INSERT INTO plats (
                    nom,
                    description,
                    type_plat
                )

                VALUES (
                    :nom,
                    :description,
                    :type_plat
                )
            ';

            $stmtInsertPlat = $pdo->prepare(
                $sqlInsertPlat
            );

            $stmtInsertPlat->execute([
                ':nom' => $nom,
                ':description' => $description,
                ':type_plat' => $typePlat
            ]);

            $platId = (int)
                $pdo->lastInsertId();

            foreach (
                $allergenesSelectionnes as $allergeneId
            ) {
                if (!is_numeric($allergeneId)) {
                    continue;
                }

                $sqlLien = '
                    INSERT INTO plat_allergene (
                        plat_id,
                        allergene_id
                    )

                    VALUES (
                        :plat_id,
                        :allergene_id
                    )
                ';

                $stmtLien = $pdo->prepare(
                    $sqlLien
                );

                $stmtLien->execute([
                    ':plat_id' => $platId,
                    ':allergene_id' =>
                        (int) $allergeneId
                ]);
            }

            // de nouveaux allergènes
            if ($nouveauxAllergenes !== '') {
                $listeNouveaux =
                    explode(
                        ',',
                        $nouveauxAllergenes
                    );

                foreach ($listeNouveaux as $nomAllergene) {
                    $nomAllergene = trim(
                        $nomAllergene
                    );

                    if ($nomAllergene === '') {
                        continue;
                    }

                    // Créer l'allergène s'il n'existe pas
                    $sqlInsertAllergene = '
                        INSERT IGNORE INTO allergenes (
                            nom
                        )

                        VALUES (
                            :nom
                        )
                    ';

                    $stmtInsertAllergene =
                        $pdo->prepare(
                            $sqlInsertAllergene
                        );

                    $stmtInsertAllergene->execute([
                        ':nom' => $nomAllergene
                    ]);

                    // Récupérer son ID
                    $sqlGetAllergene = '
                        SELECT id
                        FROM allergenes
                        WHERE nom = :nom
                    ';

                    $stmtGetAllergene =
                        $pdo->prepare(
                            $sqlGetAllergene
                        );

                    $stmtGetAllergene->execute([
                        ':nom' => $nomAllergene
                    ]);

                    $allergeneId =
                        $stmtGetAllergene->fetchColumn();

                    if ($allergeneId) {
                        $sqlLienNouveau = '
                            INSERT IGNORE INTO plat_allergene (
                                plat_id,
                                allergene_id
                            )

                            VALUES (
                                :plat_id,
                                :allergene_id
                            )
                        ';

                        $stmtLienNouveau =
                            $pdo->prepare(
                                $sqlLienNouveau
                            );

                        $stmtLienNouveau->execute([
                            ':plat_id' => $platId,
                            ':allergene_id' =>
                                (int) $allergeneId
                        ]);
                    }
                }
            }

            $pdo->commit();

            header(
                'Location: admin-plats.php'
            );

            exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log(
                'Erreur ajout plat : '
                . $e->getMessage()
            );

            $erreur =
                "Une erreur est survenue lors de l'ajout du plat.";
        }
    }
}

?>
<?php require_once __DIR__ . '/../Templates/layout.php';
renderHeader('Ajouter un plat'); ?>
<section class="content-panel">
    <h1>
        Ajouter un plat
    </h1>
    <?php if ($erreur !== ''): ?>
        <p class="erreur">
            <?= htmlspecialchars($erreur) ?>
        </p>
    <?php endif; ?>
    <form method="POST">
        <?= csrfInput() ?>
        <label for="nom">
            Nom du plat *
        </label>
        <input
            type="text"
            id="nom"
            name="nom"
            value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>"
            required
        >
        <label for="description">
            Description
        </label>
        <textarea
            id="description"
            name="description"
        ><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
        <label for="type_plat">
            Type de plat *
        </label>
        <select
            id="type_plat"
            name="type_plat"
            required
        >
            <option value="">
                Choisir un type
            </option>
            <option
                value="entree"
                <?php
        if (
            ($_POST['type_plat'] ?? '')
            === 'entree'
        ) {
            echo 'selected';
        }
?>
            >
                Entrée
            </option>
            <option
                value="plat"
                <?php
if (
    ($_POST['type_plat'] ?? '')
    === 'plat'
) {
    echo 'selected';
}
?>
            >
                Plat
            </option>
            <option
                value="dessert"
                <?php
if (
    ($_POST['type_plat'] ?? '')
    === 'dessert'
) {
    echo 'selected';
}
?>
            >
                Dessert
            </option>
        </select>
        <label>
            Allergènes existants
        </label>
        <div class="allergenes">
            <?php if (empty($allergenes)): ?>
                <p>
                    Aucun allergène enregistré.
                </p>
            <?php else: ?>
                <?php foreach ($allergenes as $allergene): ?>
                    <div class="allergene">
                        <input
                            type="checkbox"
                            id="allergene-<?= (int) $allergene['id'] ?>"
                            name="allergenes[]"
                            value="<?= (int) $allergene['id'] ?>"
                            <?php
                    if (
                        in_array(
                            (string) $allergene['id'],
                            $_POST['allergenes'] ?? [],
                            true
                        )
                    ) {
                        echo 'checked';
                    }
                    ?>
                        >
                        <label
                            for="allergene-<?= (int) $allergene['id'] ?>"
                        >
                            <?= htmlspecialchars($allergene['nom']) ?>
                        </label>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <label for="nouveaux_allergenes">
            Ajouter de nouveaux allergènes
        </label>
        <input
            type="text"
            id="nouveaux_allergenes"
            name="nouveaux_allergenes"
            placeholder="Exemple : Lait, Oeufs, Arachides"
            value="<?= htmlspecialchars($_POST['nouveaux_allergenes'] ?? '') ?>"
        >
        <p>
            Sépare les allergènes par une virgule.
        </p>
        <button type="submit">
            Ajouter le plat
        </button>
    </form>
    <a
        class="retour"
        href="admin-plats.php"
    >
        Retour à la gestion des plats
    </a>
</section>
<?php renderFooter($pdo); ?>
