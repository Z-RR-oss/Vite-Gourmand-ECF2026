<?php
require_once '../Config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = $_POST['email'];
    $password = $_POST['password'];
    $gsm = $_POST['gsm'];
    $adresse = $_POST['adresse'];
    $nom = $_POST['nom'];
    $prenom = $_POST['prenom'];

    // Vérification de la longueur
    if (strlen($password) < 10) {
        echo "Le mot de passe doit contenir au moins 10 caractères.";
        exit;
    }

    // Vérification d'une majuscule
    $hasUpper = false;
    $hasLower = false;
    $hasNumber = false;
    $hasSpecial = false;

for ($i = 0; $i < strlen($password); $i++) {

    $caractere = $password[$i];

    if (ctype_upper($caractere)) {
        $hasUpper = true;
    } elseif (ctype_lower($caractere)) {
        $hasLower = true;
    } elseif (ctype_digit($caractere)) {
        $hasNumber = true;
    } else {
        $hasSpecial = true;
    }
}

    if ($hasUpper === false) {
        echo "Le mot de passe doit contenir au moins une majuscule.";
        exit;
    }
    if ($hasLower === false) {
    echo "Le mot de passe doit contenir au moins une minuscule.";
    exit;
}

if ($hasNumber === false) {
    echo "Le mot de passe doit contenir au moins un chiffre.";
    exit;
}

if ($hasSpecial === false) {
    echo "Le mot de passe doit contenir au moins un caractère spécial.";
    exit;
}

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $sql = "INSERT INTO users (email, password, gsm, adresse, nom, prenom)
            VALUES (:email, :password, :gsm, :adresse, :nom, :prenom)";

$stmt = $pdo->prepare($sql);




try {
    $stmt->execute([
        ':email' => $email,
        ':password' => $hashedPassword,
        ':gsm' => $gsm,
        ':adresse' => $adresse,
        ':nom' => $nom,
        ':prenom' => $prenom
    ]);

    echo "Utilisateur créé avec succès !";

} catch (PDOException $e) {

    // Code MySQL 1062 = valeur dupliquée sur une colonne UNIQUE
    if (isset($e->errorInfo[1]) && $e->errorInfo[1] === 1062) {
        echo "Cette adresse email est déjà utilisée.";
    } else {
        // On garde le détail technique dans les logs
        error_log($e->getMessage());

        // Mais on ne l'affiche pas à l'utilisateur
        echo "Une erreur est survenue lors de l'inscription.";
    }
}
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>inscription</title>

</head>
<body>
   <main>
    <div class="container">
     <form action="register.php"  method="post">
             <h2>Inscription</h2>
             <input type="email" name="email" placeholder="Adresse Mail" required><br>
             <input type="password" name="password" placeholder="Mot de passe" required><br>
             <input type="tel" name="gsm" placeholder="Numéro de téléphone" required><br>
             <input type="text" name="adresse" placeholder="Adresse" required><br>
             <input type="text" name="nom" placeholder="Nom" required><br>
             <input type="text" name="prenom" placeholder="Prénom" required><br>






             <button type="submit">Inscription</button>






     </form>

    </div>
   </main>


</body>
</html>

