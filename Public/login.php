<?php
session_start();
require_once '../Config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $sql =" SELECT * FROM users WHERE email = :email";
    $stmt = $pdo->prepare($sql);


    $stmt->execute([
        ':email' => $email,

        
    ]);

  
  
    



$user = $stmt->fetch(PDO::FETCH_ASSOC);

    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];

        echo "Connexion réussie !";
    } else {
        echo "Email ou mot de passe incorrect.";
    }
}

?>









<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion</title>
</head>
<body>
    <main>
    <div class="container">
    <form action="" method="post">
    <input type="email" name="email" placeholder="Adresse mail"><br>
    <input type="password" name="password" placeholder="Mot de passe"><br><br>


         <p>Mot de passe oublié</p><br>

         <button>Connexion</button>



    </form>

    </div>


    </main>
    
</body>
</html>