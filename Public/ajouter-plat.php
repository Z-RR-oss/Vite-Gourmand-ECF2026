<?php

session_start();
require_once '../Config/database.php';


// 1. Vérifier la connexion
if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header("Location: login.php");
    exit;
}


// 2. Autoriser uniquement admin / employé
if (
    $_SESSION['role'] !== 'admin'
    && $_SESSION['role'] !== 'employe'
) {
    exit("Accès refusé.");
}


// 3. Récupérer les allergènes déjà existants
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


$erreur = '';


// 4. Traitement du formulaire
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


    // 5. Vérifier le type
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


    // 6. Enregistrer le plat
    if ($erreur === '') {

        try {

            $pdo->beginTransaction();


            // Ajouter le plat
            $sqlInsertPlat = "
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
            ";

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


            // 7. Associer les allergènes existants
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
                    ':plat_id' => $platId,
                    ':allergene_id' =>
                        (int) $allergeneId
                ]);
            }


            // 8. Ajouter éventuellement
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
                            ':plat_id' => $platId,
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
                "Erreur ajout plat : "
                . $e->getMessage()
            );


            $erreur =
                "Une erreur est survenue lors de l'ajout du plat.";
        }
    }
}

?>

<!DOCTYPE html>

<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Ajouter un plat - Vite & Gourmand
    </title>


    <style>

        body {
            font-family: Arial, sans-serif;

            background-color: #f4f4f4;

            padding: 20px;
        }

        main {
            max-width: 700px;

            margin: auto;

            background: white;

            padding: 25px;

            border-radius: 10px;

            box-shadow:
                0 2px 8px rgba(0, 0, 0, 0.1);
        }

        label {
            display: block;

            margin-top: 15px;
            margin-bottom: 5px;

            font-weight: bold;
        }

        input[type="text"],
        textarea,
        select {
            width: 100%;

            padding: 10px;

            box-sizing: border-box;

            border: 1px solid #ccc;

            border-radius: 5px;
        }

        textarea {
            min-height: 100px;

            resize: vertical;
        }

        .allergenes {
            margin-top: 10px;

            padding: 15px;

            background: #f5f5f5;

            border-radius: 8px;
        }

        .allergene {
            margin-bottom: 8px;
        }

        .allergene label {
            display: inline;

            margin: 0;

            font-weight: normal;
        }

        button {
            margin-top: 20px;

            padding: 10px 18px;

            border: none;

            border-radius: 5px;

            background: black;

            color: white;

            cursor: pointer;
        }

        button:hover {
            background: #444;
        }

        .erreur {
            background: #ffdede;

            color: #8b0000;

            padding: 10px;

            border-radius: 5px;
        }

        .retour {
            display: inline-block;

            margin-top: 20px;
        }

    </style>

</head>


<body>

<main>

    <h1>
        Ajouter un plat
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


        <label for="nom">
            Nom du plat *
        </label>

        <input
            type="text"
            id="nom"
            name="nom"
            value="<?php
            echo htmlspecialchars(
                $_POST['nom'] ?? ''
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
            $_POST['description'] ?? ''
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
                                    $_POST['allergenes'] ?? [],
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
            value="<?php
            echo htmlspecialchars(
                $_POST['nouveaux_allergenes'] ?? ''
            );
            ?>"
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

</main>
                    
</body>

</html>