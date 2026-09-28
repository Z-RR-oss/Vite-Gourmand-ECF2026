<?php

require_once __DIR__ . '/../Config/database.php';

// Vérifier la connexion
requireLogin();

// Autoriser uniquement admin / employé
requireAdminOrEmployee();

// Les 7 jours de la semaine
$jours = [
    'Lundi',
    'Mardi',
    'Mercredi',
    'Jeudi',
    'Vendredi',
    'Samedi',
    'Dimanche'
];

// Récupérer les horaires existants
$sql = "
    SELECT *
    FROM horaires
";

$stmt = $pdo->prepare($sql);
$stmt->execute();

$horairesBdd = $stmt->fetchAll(
    PDO::FETCH_ASSOC
);

// Regrouper les horaires par jour
$horaires = [];

foreach ($horairesBdd as $horaire) {

    $horaires[
        $horaire['jour']
    ] = $horaire;
}

?>

<?php require_once __DIR__ . '/../Templates/layout.php'; renderHeader('Gestion des horaires'); ?>

<section class="content-panel">

    <h1>
        Gestion des horaires
    </h1>

    <p>
        Définissez les horaires d'ouverture
        pour chaque jour de la semaine.
    </p>

    <?php foreach ($jours as $jour): ?>

        <?php
        $horaire = $horaires[$jour] ?? null;
        ?>

        <section class="horaire">

            <div class="infos">

                <h2>

                    <?php
                    echo htmlspecialchars(
                        $jour
                    );
                    ?>

                </h2>

                <?php if (!$horaire): ?>

                    <p>
                        Aucun horaire renseigné.
                    </p>

                <?php elseif ((int) $horaire['ferme'] === 1): ?>

                    <span class="ferme">
                        Fermé
                    </span>

                <?php else: ?>

                    <span class="ouvert">
                        Ouvert
                    </span>

                    <p>

                        <?php
                        echo htmlspecialchars(
                            substr(
                                $horaire['heure_ouverture'],
                                0,
                                5
                            )
                        );
                        ?>

                        -

                        <?php
                        echo htmlspecialchars(
                            substr(
                                $horaire['heure_fermeture'],
                                0,
                                5
                            )
                        );
                        ?>

                    </p>

                <?php endif; ?>

            </div>

            <div>

                <a
                    class="action"
                    href="modifier-horaire.php?jour=<?php
                    echo urlencode($jour);
                    ?>"
                >

                    <?php
                    echo $horaire
                        ? 'Modifier'
                        : 'Ajouter';
                    ?>

                </a>

            </div>

        </section>

    <?php endforeach; ?>

</section>

<?php renderFooter($pdo); ?>
