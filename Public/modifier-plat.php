<?php

require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/../Services/CatalogueValidation.php';

// Vérifier la connexion
requireLogin();

// Autoriser admin / employé
requireAdminOrEmployee();

// Vérifier l'identifiant
$id = $_GET['id'] ?? null;

if (!$id || !is_numeric($id)) {
    exit("ID de plat invalide.");
}

$id = (int) $id;

// Récupérer le plat
$sqlPlat = "
    SELECT *
    FROM plats
    WHERE id = :id
";

$stmtPlat = $pdo->prepare($sqlPlat);

$stmtPlat->execute([
    ':id' => $id
]);

$plat = $stmtPlat->fetch(PDO::FETCH_ASSOC);

if (!$plat) {
    exit("Plat introuvable.");
}

// Récupérer tous les allergènes
$sqlAllergenes = "
    SELECT id, nom
    FROM allergenes
    ORDER BY nom ASC
";

$stmtAllergenes = $pdo->prepare(
    $sqlAllergenes
);

$stmtAllergenes->execute();

$allergenes = $stmtAllergenes->fetchAll(
    PDO::FETCH_ASSOC
);

// Allergènes actuellement associés au plat
$sqlSelection = "
    SELECT allergene_id
    FROM plat_allergene
    WHERE plat_id = :plat_id
";

$stmtSelection = $pdo->prepare(
    $sqlSelection
);

$stmtSelection->execute([
    ':plat_id' => $id
]);

$allergenesSelectionnes =
    $stmtSelection->fetchAll(
        PDO::FETCH_COLUMN
    );

// Transformer les IDs en chaînes
$allergenesSelectionnes = array_map(
    'strval',
    $allergenesSelectionnes
);

$erreur = '';

// Traitement du formulaire
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
            "Le nom du plat est obligatoire.";

    } elseif (
        !in_array(
            $typePlat,
            $typesAutorises,
            true
        )
    ) {

        $erreur =
            "Le type de plat est invalide.";
    }

    $erreur = dishValidationError($_POST, $allergenes) ?? $erreur;
    $allergenesSelectionnes = is_array($allergenesSelectionnes) ? array_unique($allergenesSelectionnes) : [];

    if ($erreur === '') {

        try {

            $pdo->beginTransaction();

            // Modifier les informations du plat
            $sqlUpdate = "
                UPDATE plats

                SET
                    nom = :nom,
                    description = :description,
                    type_plat = :type_plat

                WHERE id = :id
            ";

            $stmtUpdate = $pdo->prepare(
                $sqlUpdate
            );

            $stmtUpdate->execute([
                ':nom' => $nom,
                ':description' => $description,
                ':type_plat' => $typePlat,
                ':id' => $id
            ]);

            // Supprimer les anciennes associations
            $sqlDeleteLiens = "
                DELETE FROM plat_allergene
                WHERE plat_id = :plat_id
            ";

            $stmtDeleteLiens = $pdo->prepare(
                $sqlDeleteLiens
            );

            $stmtDeleteLiens->execute([
                ':plat_id' => $id
            ]);

            // Réenregistrer les allergènes cochés
            foreach (
                $allergenesSelectionnes
                as $allergeneId
            ) {

                if (!is_numeric($allergeneId)) {
                    continue;
                }

                $sqlLien = "
                    INSERT INTO plat_allergene (
                        plat_id,
                        allergene_id
                    )

                    VALUES (
                        :plat_id,
                        :allergene_id
                    )
                ";

                $stmtLien = $pdo->prepare(
                    $sqlLien
                );

                $stmtLien->execute([
                    ':plat_id' => $id,
                    ':allergene_id' =>
                        (int) $allergeneId
                ]);
            }

            // Ajouter les nouveaux allergènes
            if ($nouveauxAllergenes !== '') {

                $listeNouveaux = explode(
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

                    // Créer s'il n'existe pas
                    $sqlInsertAllergene = "
                        INSERT IGNORE INTO allergenes (
                            nom
                        )

                        VALUES (
                            :nom
                        )
                    ";

                    $stmtInsertAllergene =
                        $pdo->prepare(
                            $sqlInsertAllergene
                        );

                    $stmtInsertAllergene->execute([
                        ':nom' => $nomAllergene
                    ]);

                    // Récupérer son ID
                    $sqlGetAllergene = "
                        SELECT id
                        FROM allergenes
                        WHERE nom = :nom
                    ";

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

                        $sqlLienNouveau = "
                            INSERT IGNORE INTO plat_allergene (
                                plat_id,
                                allergene_id
                            )

                            VALUES (
                                :plat_id,
                                :allergene_id
                            )
                        ";

                        $stmtLienNouveau =
                            $pdo->prepare(
                                $sqlLienNouveau
                            );

                        $stmtLienNouveau->execute([
                            ':plat_id' => $id,
                            ':allergene_id' =>
                                (int) $allergeneId
                        ]);
                    }
                }
            }

            $pdo->commit();

            header(
                "Location: admin-plats.php"
            );

            exit;

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log(
                "Erreur modification plat : "
                . $e->getMessage()
            );

            $erreur =
                "Une erreur est survenue lors de la modification.";
        }
    }

    // Réafficher les nouvelles valeurs
    $plat['nom'] = $nom;
    $plat['description'] = $description;
    $plat['type_plat'] = $typePlat;
}

?>

<?php require_once __DIR__ . '/../Templates/layout.php'; renderHeader('Modifier un plat'); ?>

<section class="content-panel">

    <h1>
        Modifier le plat
    </h1>

    <?php if ($erreur !== ''): ?>

        <p class="erreur">

            <?php
            echo htmlspecialchars(
                $erreur
            );
            ?>

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
            value="<?php
            echo htmlspecialchars(
                $plat['nom']
            );
            ?>"
            required
        >

        <label for="description">
            Description
        </label>

        <textarea
            id="description"
            name="description"
        ><?php
        echo htmlspecialchars(
            $plat['description'] ?? ''
        );
        ?></textarea>

        <label for="type_plat">
            Type de plat *
        </label>

        <select
            id="type_plat"
            name="type_plat"
            required
        >

            <option
                value="entree"
                <?php
                if ($plat['type_plat'] === 'entree') {
                    echo 'selected';
                }
                ?>
            >
                Entrée
            </option>

            <option
                value="plat"
                <?php
                if ($plat['type_plat'] === 'plat') {
                    echo 'selected';
                }
                ?>
            >
                Plat
            </option>

            <option
                value="dessert"
                <?php
                if ($plat['type_plat'] === 'dessert') {
                    echo 'selected';
                }
                ?>
            >
                Dessert
            </option>

        </select>

        <label>
            Allergènes
        </label>

        <div class="allergenes">

            <?php foreach ($allergenes as $allergene): ?>

                <div class="allergene">

                    <input
                        type="checkbox"
                        id="allergene-<?php
                        echo (int) $allergene['id'];
                        ?>"
                        name="allergenes[]"
                        value="<?php
                        echo (int) $allergene['id'];
                        ?>"
                        <?php
                        if (
                            in_array(
                                (string) $allergene['id'],
                                $allergenesSelectionnes,
                                true
                            )
                        ) {
                            echo 'checked';
                        }
                        ?>
                    >

                    <label
                        for="allergene-<?php
                        echo (int) $allergene['id'];
                        ?>"
                    >

                        <?php
                        echo htmlspecialchars(
                            $allergene['nom']
                        );
                        ?>

                    </label>

                </div>

            <?php endforeach; ?>

        </div>

        <label for="nouveaux_allergenes">
            Ajouter de nouveaux allergènes
        </label>

        <input
            type="text"
            id="nouveaux_allergenes"
            name="nouveaux_allergenes"
            placeholder="Exemple : Soja, Fruits à coque"
        >

        <button type="submit">
            Enregistrer les modifications
        </button>

    </form>

    <a
        class="retour"
        href="admin-plats.php"
    >
        Retour aux plats
    </a>

</section>

<?php renderFooter($pdo); ?>
