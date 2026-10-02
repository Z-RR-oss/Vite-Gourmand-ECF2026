<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Erreur <?= (int) $status ?> — Vite &amp; Gourmand</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <main class="page-shell content-panel">
        <h1>Cette demande ne peut pas aboutir</h1>
        <p><?= e($message) ?></p>
        <p><a href="index.php">Retour à l’accueil</a></p>
    </main>
</body>
</html>
