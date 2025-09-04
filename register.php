<?php
session_start();
require_once __DIR__ . '/config/db.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm  = trim($_POST['confirm'] ?? '');

    if ($password !== $confirm) {
        $error = "Les mots de passe ne correspondent pas.";
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        try {
            // Vérifier si l'utilisateur existe déjà
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                $error = "Ce nom d'utilisateur ou cet email existe déjà.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
                $stmt->execute([$username, $email, $hashed]);
                // Connexion automatique après inscription
                $_SESSION['user_id'] = $pdo->lastInsertId();
                $_SESSION['username'] = $username;
                $_SESSION['role'] = 'utilisateur';
                $_SESSION['avatar'] = 'assets/images/utilisateur.png';
                $success = "🎉 Bienvenue <strong>" . htmlspecialchars($username) . "</strong> ! Votre inscription est réussie.";
                // Redirection après un court délai
                echo "<script>
                    setTimeout(function() {
                        window.location.href = 'accueil.php';
                    }, 1800);
                </script>";
            }
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
  <link rel="icon" type="image/x-icon" href="assets/favicon.ico">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
      --primary: #0078D7;
      --gradient: linear-gradient(90deg, #49c4e6ff 0%, #e5edf7ff 100%);
      --glass: rgba(255, 255, 255, 0.15);
    }

    body {
      font-family: 'Segoe UI', sans-serif;
      background: var(--gradient);
      color: #333;
      margin: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: background 0.3s, color 0.3s;
      min-height: 100vh;
    }

    .form-box {
      background: var(--glass);
      backdrop-filter: blur(12px);
      border-radius: 18px;
      box-shadow: 0 8px 24px rgba(0,0,0,0.13);
      padding: 0;
      width: 460px;
      max-width: 98vw;
      overflow: hidden;
      margin: 30px 0;
    }

    .main {
      padding: 32px 32px 24px 32px;
      background: transparent;
      display: flex;
      flex-direction: column;
      align-items: stretch;
    }

    h2 {
      text-align: center;
      margin-bottom: 20px;
      color: #0078D7;
      font-weight: 600;
    }

    .form-group {
      margin-bottom: 18px;
      width: 100%;
      display: flex;
      flex-direction: column;
      align-items: flex-start;
    }
    .form-group label {
      font-weight: 500;
      margin-bottom: 6px;
      color: #0078D7;
      display: flex;
      align-items: flex-start;
      gap: 0;
    }
    .form-group input {
      width: 100%;
      padding: 10px 12px;
      border-radius: 8px;
      border: 1px solid #b0c4de;
      font-size: 16px;
      background: #f7fafc;
      margin-bottom: 2px;
      transition: border 0.2s;
    }
    .form-group input:focus {
      border: 1.5px solid #0078D7;
      outline: none;
    }
    .button-wrapper {
      display: flex;
      justify-content: center;
      margin-top: 20px;
    }
    button {
      padding: 12px 24px;
      font-size: 16px;
      background: #0078D7;
      color: #fff;
      font-weight: bold;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      transition: background 0.3s, color 0.3s;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    button.loading {
      background: #005fa3;
      cursor: wait;
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
      color: #0078D7;
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
        padding: 0;
      }
      .main {
        padding: 18px 8px 12px 8px;
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
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }
  </style>
</head>
<body>
  <div id="loader">Chargement...</div>
  <div id="darkToggle" onclick="toggleDarkMode()" title="Activer le mode sombre">🌙</div>
  <div class="form-box">
    <div class="main">
      <h2>Inscription</h2>
      <form method="POST" action="register.php" id="registerForm" autocomplete="on" onsubmit="return animateRegister()">
        <div class="form-group">
          <label for="username">Nom d'utilisateur</label>
          <input type="text" name="username" id="username" placeholder="Nom d'utilisateur" required value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>">
        </div>
        <div class="form-group">
          <label for="email">Adresse email</label>
          <input type="email" name="email" id="email" placeholder="Email" required value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
        </div>
        <div class="form-group">
          <label for="password">Mot de passe</label>
          <input type="password" name="password" id="password" placeholder="Mot de passe" required autocomplete="new-password">
        </div>
        <div class="form-group">
          <label for="confirm">Confirmer le mot de passe</label>
          <input type="password" name="confirm" id="confirm" placeholder="Confirmer le mot de passe" required autocomplete="new-password">
        </div>
        <div class="button-wrapper">
          <button type="submit" id="registerBtn">S'inscrire</button>
        </div>
      </form>
      <p style="text-align:center; margin-top: 15px;">Déjà inscrit ? <a href="login.php">Se connecter</a></p>
      <?php if ($error) echo "<div class='error'>$error</div>"; ?>
      <?php if ($success) echo "<div class='success'>$success</div>"; ?>
    </div>
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

    function animateRegister() {
      const btn = document.getElementById("registerBtn");
      btn.classList.add("loading");
      btn.innerHTML = "Inscription...";
      setTimeout(() => {
        showWelcome();
      }, 800);
      return true;
    }

    function showWelcome() {
      const msg = document.createElement("div");
      msg.className = "success";
      msg.innerHTML = "<strong>Bienvenue !</strong><br>Redirection en cours...";
      document.body.appendChild(msg);
      msg.style.display = "block";
      setTimeout(() => {
        msg.remove();
      }, 1500);
    }
  </script>
</body>
</html>
