<?php
require_once '../Config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $sql = "INSERT INTO users (email, password) VALUES (:email, :password)";
    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':email' => $email,
        ':password' => $hashedPassword
    ]);

    echo "Utilisateur créé avec succès !";
}
?>
<!DOCTYPE html>
<html lang="en">
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
             <input type="email" name="email" placeholder="Adresse Mail"/><br>
             <input type="password" name="password" placeholder="Mot de passe"/><br>
             
             <p>Mot de passe oublié</p>

             <button>Inscription</button>






     </form>

    </div>
   </main>

    
</body>
</html>

