<?php
session_start();
require_once 'config/db.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm  = trim($_POST['confirm']);

    if ($password !== $confirm) {
        $error = "Les mots de passe ne correspondent pas.";
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        try {
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $stmt->execute([$username, $email, $hashed]);
            $success = "🎉 Bienvenue <strong>$username</strong> ! Votre inscription est réussie.";
        } catch (PDOException $e) {
            $error = "Erreur : " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Inscription</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
      --primary: #0078D7;
      --gradient: linear-gradient(to right, #00c6ff, #0072ff);
      --glass: rgba(255, 255, 255, 0.15);
    }

    body {
      font-family: 'Segoe UI', sans-serif;
      background: var(--gradient);
      color: #fff;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      margin: 0;
      transition: background 0.3s, color 0.3s;
    }

    .form-box {
      background: var(--glass);
      backdrop-filter: blur(12px);
      padding: 40px;
      border-radius: 16px;
      box-shadow: 0 8px 24px rgba(0,0,0,0.2);
      width: 400px;
      animation: fadeIn 0.6s ease;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }

    h2 {
      text-align: center;
      margin-bottom: 20px;
      color: #fff;
    }

    .input-group {
      position: relative;
      margin-top: 12px;
    }

    .input-group i {
      position: absolute;
      top: 50%;
      right: 6px;
      transform: translateY(-50%);
      color: #888;
    }

    .input-group input {
      width: 100%;
      padding: 10px;
      /* border: none; */
      border-radius: 8px;
      font-size: 15px;
      background: #fff;
      color: #000;
    }

    .button-wrapper {
      display: flex;
      justify-content: center;
      margin-top: 20px;
    }

    button {
      padding: 12px 24px;
      font-size: 16px;
      background: var(--primary);
      color: #fff;
      font-weight: bold;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      transition: transform 0.3s ease, background 0.3s ease;
    }

    button:hover {
      background: #005fa3;
    }

    .error, .success {
      margin-top: 15px;
      padding: 12px;
      border-radius: 8px;
      animation: fadeIn 0.6s ease-out forwards;
      opacity: 0;
    }

    .error {
      background: #f8d7da;
      color: #721c24;
    }

    .success {
      background: #d4edda;
      color: #155724;
    }

    a {
      color: #fff;
      text-decoration: underline;
    }

    .dark-mode {
      background: #121212;
      color: #f0f0f0;
    }

    .dark-mode .form-box {
      background: rgba(255,255,255,0.05);
    }

    .dark-mode input {
      background: #333;
      color: #fff;
    }

    .dark-mode button {
      background: #444;
      color: #fff;
    }

    .dark-mode a {
      color: #ccc;
    }

    @media screen and (max-width: 600px) {
      .form-box {
        width: 70%;
        padding: 30px;
      }
      input, button {
        font-size: 12px;
      }
    }

    #loader {
      position: fixed;
      top: 0; left: 0;
      width: 100%; height: 100%;
      background: #000;
      display: flex;
      justify-content: center;
      align-items: center;
      z-index: 9999;
      color: white;
      font-size: 24px;
    }

    #darkToggle {
            position: fixed;
            top: 15px;
            right: 15px;
            font-size: 14px;
            cursor: pointer;
            z-index: 1000;
            background: rgba(255,255,255,0.2);
            padding: 10px;
            border-radius: 50%;
            transition: background 0.3s;
        }

        #darkToggle:hover {
            background: rgba(255,255,255,0.4);
        }

        .dark-mode #darkToggle {
            background: rgba(255,255,255,0.1);
            color: #f0f0f0;
        }
  </style>
</head>
<body>
  <div id="loader">Chargement...</div>
  <div id="darkToggle" onclick="toggleDarkMode()" title="Activer le mode sombre">🌙</div>

  <div class="form-box">
    <h2>Inscription</h2>
    <form method="POST" action="register.php" id="registerForm">
      <div class="input-group">
        <i class="fa fa-user"></i>
        <input type="text" name="username" placeholder="Nom d'utilisateur" required>
      </div>
      <div class="input-group">
        <i class="fa fa-envelope"></i>
        <input type="email" name="email" placeholder="Email" required>
      </div>
      <div class="input-group">
        <i class="fa fa-lock"></i>
        <input type="password" name="password" placeholder="Mot de passe" required>
      </div>
      <div class="input-group">
        <i class="fa fa-lock"></i>
        <input type="password" name="confirm" placeholder="Confirmer le mot de passe" required>
      </div>
      <div class="button-wrapper">
        <button type="submit">S'inscrire</button>
      </div>
    </form>
    <p style="text-align:center; margin-top: 15px;">Déjà inscrit ? <a href="login.php">Se connecter</a></p>

    <?php if ($error) echo "<div class='error'>$error</div>"; ?>
    <?php if ($success) echo "<div class='success'>$success</div>"; ?>
  </div>

  <script>
    window.addEventListener("load", function () {
      document.getElementById("loader").style.display = "none";
      if (localStorage.getItem("darkMode") === "true") {
        document.body.classList.add("dark-mode");
      }
    });

    function toggleDarkMode() {
      document.body.classList.toggle("dark-mode");
      localStorage.setItem("darkMode", document.body.classList.contains("dark-mode"));
    }
  </script>
</body>
</html>
