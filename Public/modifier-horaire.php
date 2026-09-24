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


// 3. Jours autorisés
$joursAutorises = [
    'Lundi',
    'Mardi',
    'Mercredi',
    'Jeudi',
    'Vendredi',
    'Samedi',
    'Dimanche'
];


// 4. Récupérer le jour
$jour = trim(
    $_GET['jour'] ?? ''
);


if (
    !in_array(
        $jour,
        $joursAutorises,
        true
    )
) {
    exit("Jour invalide.");
}


// 5. Récupérer l'horaire existant
$sql = "
    SELECT *
    FROM horaires
    WHERE jour = :jour
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ':jour' => $jour
]);

$horaire = $stmt->fetch(
    PDO::FETCH_ASSOC
);


$erreur = '';


// 6. Valeurs par défaut
$heureOuverture =
    $horaire['heure_ouverture']
    ?? '09:00';

$heureFermeture =
    $horaire['heure_fermeture']
    ?? '18:00';

$ferme = $horaire
    ? (int) $horaire['ferme']
    : 0;


// 7. Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action =
        $_POST['action'] ?? 'enregistrer';


    /*
     * SUPPRESSION
     */
    if ($action === 'supprimer') {

        if ($horaire) {

            $sqlDelete = "
                DELETE FROM horaires
                WHERE jour = :jour
            ";

            $stmtDelete = $pdo->prepare(
                $sqlDelete
            );

            $stmtDelete->execute([
                ':jour' => $jour
            ]);
        }


        header(
            "Location: admin-horaires.php"
        );

        exit;
    }


    /*
     * CRÉATION / MODIFICATION
     */

    $ferme = isset($_POST['ferme'])
        ? 1
        : 0;


    $heureOuverture = trim(
        $_POST['heure_ouverture'] ?? ''
    );


    $heureFermeture = trim(
        $_POST['heure_fermeture'] ?? ''
    );


    // Si le restaurant est ouvert,
    // les heures doivent être renseignées
    if ($ferme === 0) {

        if (
            $heureOuverture === ''
            || $heureFermeture === ''
        ) {

            $erreur =
                "Les heures d'ouverture et de fermeture sont obligatoires.";

        } elseif (
            $heureFermeture
            <= $heureOuverture
        ) {

            $erreur =
                "L'heure de fermeture doit être après l'heure d'ouverture.";
        }
    }


    // 8. Enregistrement
    if ($erreur === '') {

        $sqlSave = "
            INSERT INTO horaires (
                jour,
                heure_ouverture,
                heure_fermeture,
                ferme
            )

            VALUES (
                :jour,
                :heure_ouverture,
                :heure_fermeture,
                :ferme
            )

            ON DUPLICATE KEY UPDATE

                heure_ouverture =
                    VALUES(heure_ouverture),

                heure_fermeture =
                    VALUES(heure_fermeture),

                ferme =
                    VALUES(ferme)
        ";

        $stmtSave = $pdo->prepare(
            $sqlSave
        );


        $stmtSave->execute([
            ':jour' => $jour,

            ':heure_ouverture' =>
                $heureOuverture !== ''
                    ? $heureOuverture
                    : '00:00',

            ':heure_fermeture' =>
                $heureFermeture !== ''
                    ? $heureFermeture
                    : '00:00',

            ':ferme' => $ferme
        ]);


        header(
            "Location: admin-horaires.php"
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
        Modifier un horaire - Vite & Gourmand
    </title>


    <style>

        body {
            font-family: Arial, sans-serif;

            background-color: #f4f4f4;

            margin: 0;

            padding: 30px 20px;
        }


        main {
            max-width: 600px;

            margin: 40px auto;

            background-color: white;

            padding: 30px;

            border-radius: 12px;

            box-shadow:
                0 2px 10px rgba(0, 0, 0, 0.1);
        }


        h1 {
            margin-top: 0;

            color: #222;
        }


        label {
            display: block;

            margin-top: 20px;
            margin-bottom: 8px;

            font-weight: bold;

            color: #222;
        }


        input[type="time"] {
            display: block;

            width: 100%;

            padding: 12px;

            box-sizing: border-box;

            border: 1px solid #bbb;

            border-radius: 6px;

            font-size: 16px;

            background-color: white;
        }


        .checkbox {
            display: flex;

            align-items: center;

            gap: 10px;

            margin-top: 25px;

            padding: 15px;

            background-color: #f5f5f5;

            border-radius: 6px;
        }


        .checkbox input {
            width: 18px;
            height: 18px;
        }


        .checkbox label {
            margin: 0;

            cursor: pointer;
        }


        .enregistrer {
            display: block;

            width: 100%;

            margin-top: 25px;

            padding: 15px;

            background-color: #000;
            color: #fff;

            border: none;

            border-radius: 7px;

            font-size: 16px;
            font-weight: bold;

            cursor: pointer;
        }


        .enregistrer:hover {
            background-color: #333;
        }


        .supprimer {
            display: block;

            width: 100%;

            margin-top: 12px;

            padding: 13px;

            background-color: #b00020;
            color: white;

            border: none;

            border-radius: 7px;

            font-size: 15px;
            font-weight: bold;

            cursor: pointer;
        }


        .supprimer:hover {
            background-color: #800018;
        }


        .erreur {
            background-color: #ffdede;

            color: #8b0000;

            padding: 12px;

            border-radius: 6px;

            margin-bottom: 20px;
        }


        .retour {
            display: block;

            margin-top: 25px;

            text-align: center;

            color: #222;

            text-decoration: underline;
        }


        .info {
            margin-bottom: 25px;

            color: #555;
        }


    </style>

</head>


<body>


<main>


    <h1>

        Horaires du

        <?php
        echo htmlspecialchars(
            $jour
        );
        ?>

    </h1>


    <p class="info">

        Modifiez les horaires de ce jour
        puis cliquez sur
        <strong>
            Enregistrer les horaires
        </strong>.

    </p>


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


        <label for="heure_ouverture">
            Heure d'ouverture
        </label>


        <input
            type="time"
            id="heure_ouverture"
            name="heure_ouverture"
            value="<?php
            echo htmlspecialchars(
                substr(
                    $heureOuverture,
                    0,
                    5
                )
            );
            ?>"
        >


        <label for="heure_fermeture">
            Heure de fermeture
        </label>


        <input
            type="time"
            id="heure_fermeture"
            name="heure_fermeture"
            value="<?php
            echo htmlspecialchars(
                substr(
                    $heureFermeture,
                    0,
                    5
                )
            );
            ?>"
        >


        <div class="checkbox">

            <input
                type="checkbox"
                id="ferme"
                name="ferme"
                <?php
                if ($ferme === 1) {
                    echo 'checked';
                }
                ?>
            >


            <label for="ferme">
                Fermé ce jour
            </label>

        </div>


        <button
            class="enregistrer"
            type="submit"
            name="action"
            value="enregistrer"
        >
            Enregistrer les horaires
        </button>


        <?php if ($horaire): ?>

            <button
                class="supprimer"
                type="submit"
                name="action"
                value="supprimer"
                onclick="   
                    return confirm(
                        'Voulez-vous vraiment supprimer cet horaire ?'
                    );
                "
            >
                Supprimer cet horaire
            </button>

        <?php endif; ?>


    </form>


    <a
        class="retour"
        href="admin-horaires.php"
    >
        ← Retour à la gestion des horaires
    </a>


</main>


</body>

</html>