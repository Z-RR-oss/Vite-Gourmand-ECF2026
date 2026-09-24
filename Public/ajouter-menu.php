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


$erreur = '';


// 3. Traiter le formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $titre = trim($_POST['titre'] ?? '');
    $description = trim($_POST['description'] ?? '');

    $prix = (float) ($_POST['prix'] ?? 0);

    $nbPersonnesMin = (int) (
        $_POST['nb_personnes_min'] ?? 0
    );

    $theme = trim($_POST['theme'] ?? '');
    $regime = trim($_POST['regime'] ?? '');

    $stockDisponible = (int) (
        $_POST['stock_disponible'] ?? 0
    );

    $conditionsMenu = trim(
        $_POST['conditions_menu'] ?? ''
    );

    $delaiCommandeHeures = (int) (
        $_POST['delai_commande_heures'] ?? 0
    );

    $actif = isset($_POST['actif'])
        ? 1
        : 0;


    // 4. Validation
    if (
        $titre === ''
        || $description === ''
        || $theme === ''
        || $regime === ''
    ) {
        $erreur =
            "Tous les champs obligatoires doivent être renseignés.";
    } elseif ($prix <= 0) {

        $erreur =
            "Le prix doit être supérieur à 0.";

    } elseif ($nbPersonnesMin < 1) {

        $erreur =
            "Le nombre minimum de personnes doit être au moins égal à 1.";

    } elseif ($stockDisponible < 0) {

        $erreur =
            "Le stock ne peut pas être négatif.";

    } elseif ($delaiCommandeHeures < 0) {

        $erreur =
            "Le délai de commande ne peut pas être négatif.";
    }


    // 5. Insérer le menu
    if ($erreur === '') {

        $sql = "
            INSERT INTO menus (
                titre,
                description,
                prix,
                nb_personnes_min,
                theme,
                regime,
                stock_disponible,
                conditions_menu,
                delai_commande_heures,
                actif
            )

            VALUES (
                :titre,
                :description,
                :prix,
                :nb_personnes_min,
                :theme,
                :regime,
                :stock_disponible,
                :conditions_menu,
                :delai_commande_heures,
                :actif
            )
        ";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ':titre' => $titre,
            ':description' => $description,
            ':prix' => $prix,
            ':nb_personnes_min' => $nbPersonnesMin,
            ':theme' => $theme,
            ':regime' => $regime,
            ':stock_disponible' => $stockDisponible,
            ':conditions_menu' => $conditionsMenu,
            ':delai_commande_heures' =>
                $delaiCommandeHeures,
            ':actif' => $actif
        ]);


        header(
            "Location: admin-menus.php"
        );

        exit;
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
        Ajouter un menu - Vite & Gourmand
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

        input,
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

        .checkbox {
            display: flex;

            align-items: center;

            gap: 10px;

            margin-top: 20px;
        }

        .checkbox input {
            width: auto;
        }

        .checkbox label {
            margin: 0;
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
        Ajouter un menu
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


        <label for="titre">
            Titre *
        </label>

        <input
            type="text"
            id="titre"
            name="titre"
            value="<?php
            echo htmlspecialchars(
                $_POST['titre'] ?? ''
            );
            ?>"
            required
        >


        <label for="description">
            Description *
        </label>

        <textarea
            id="description"
            name="description"
            required
        ><?php
        echo htmlspecialchars(
            $_POST['description'] ?? ''
        );
        ?></textarea>


        <label for="prix">
            Prix correspondant au minimum de personnes (€) *
        </label>

        <input
            type="number"
            id="prix"
            name="prix"
            step="0.01"
            min="0.01"
            value="<?php
            echo htmlspecialchars(
                $_POST['prix'] ?? ''
            );
            ?>"
            required
        >


        <label for="nb_personnes_min">
            Nombre minimum de personnes *
        </label>

        <input
            type="number"
            id="nb_personnes_min"
            name="nb_personnes_min"
            min="1"
            value="<?php
            echo htmlspecialchars(
                $_POST['nb_personnes_min'] ?? ''
            );
            ?>"
            required
        >


        <label for="theme">
            Thème *
        </label>

        <input
            type="text"
            id="theme"
            name="theme"
            placeholder="Exemple : Classique, Noël..."
            value="<?php
            echo htmlspecialchars(
                $_POST['theme'] ?? ''
            );
            ?>"
            required
        >


        <label for="regime">
            Régime alimentaire *
        </label>

        <input
            type="text"
            id="regime"
            name="regime"
            placeholder="Exemple : Classique, Vegan..."
            value="<?php
            echo htmlspecialchars(
                $_POST['regime'] ?? ''
            );
            ?>"
            required
        >


        <label for="stock_disponible">
            Stock disponible
        </label>

        <input
            type="number"
            id="stock_disponible"
            name="stock_disponible"
            min="0"
            value="<?php
            echo htmlspecialchars(
                $_POST['stock_disponible'] ?? '0'
            );
            ?>"
            required
        >


        <label for="conditions_menu">
            Conditions particulières
        </label>

        <textarea
            id="conditions_menu"
            name="conditions_menu"
            placeholder="Exemple : commander 48 h à l'avance..."
        ><?php
        echo htmlspecialchars(
            $_POST['conditions_menu'] ?? ''
        );
        ?></textarea>


        <label for="delai_commande_heures">
            Délai minimum de commande en heures
        </label>

        <input
            type="number"
            id="delai_commande_heures"
            name="delai_commande_heures"
            min="0"
            value="<?php
            echo htmlspecialchars(
                $_POST['delai_commande_heures'] ?? '0'
            );
            ?>"
            required
        >


        <div class="checkbox">

            <input
                type="checkbox"
                id="actif"
                name="actif"
                <?php
                if (
                    !isset($_POST['actif'])
                    && $_SERVER['REQUEST_METHOD'] !== 'POST'
                ) {
                    echo 'checked';
                } elseif (isset($_POST['actif'])) {
                    echo 'checked';
                }
                ?>
            >

            <label for="actif">
                Menu actif
            </label>

        </div>


        <button type="submit">
            Ajouter le menu
        </button>


    </form>


    <a
        class="retour"
        href="admin-menus.php"
    >
        Retour à la gestion des menus
    </a>

</main>

</body>

</html>