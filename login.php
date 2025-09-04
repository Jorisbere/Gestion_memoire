<?php
session_start();
require_once 'includes/db.php';

// Gestion de la connexion
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    $stmt = $conn->prepare("SELECT id, password, role, avatar FROM users WHERE username = ?");
    if ($stmt) {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($user_id, $hashed_password, $role, $avatar_raw);
            $stmt->fetch();

            if (password_verify($password, $hashed_password)) {
                // Stockage des infos dans la session
                $_SESSION['user_id'] = $user_id;
                $_SESSION['username'] = $username;
                $_SESSION['role'] = $role;

                // Avatar pour affichage dans la sidebar
                if (!empty($avatar_raw)) {
                    $trim = ltrim($avatar_raw);
                    if (preg_match('#^https?://#i', $trim)) {
                        $avatar = $trim;
                    } elseif (substr($trim, 0, 1) === '/') {
                        $avatar = $trim;
                    } else {
                        $avatar = '/Gestion_Memoire/' . ltrim($trim, '/');
                    }
                    $avatar = htmlspecialchars($avatar);
                } else {
                    $avatar = 'assets/images/utilisateur.png';
                }
                $_SESSION['avatar'] = $avatar;

                // Enregistrement de la connexion
                $ip = $_SERVER['REMOTE_ADDR'];
                $agent = substr($_SERVER['HTTP_USER_AGENT'], 0, 255);

                $stmtLog = $conn->prepare("INSERT INTO user_logins (user_id, ip_address, user_agent) VALUES (?, ?, ?)");
                $stmtLog->bind_param("iss", $user_id, $ip, $agent);
                $stmtLog->execute();
                $stmtLog->close();

                if ($_SESSION['role'] === 'administrateur') {
                    header("Location: dashboard.php");
                } else {
                    header("Location: accueil.php");
                }
                exit();
            } else {
                $error = "Mot de passe incorrect.";
            }
        } else {
            $error = "Nom d'utilisateur introuvable.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="assets/favicon.ico">
    <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background: linear-gradient(90deg, #49c4e6ff 0%, #e5edf7ff 100%);
      color: #333;
      margin: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: background 0.3s, color 0.3s;
    }
    .container {
      background: rgba(255,255,255,0.15);
      backdrop-filter: blur(12px);
      border-radius: 18px;
      box-shadow: 0 8px 24px rgba(0,0,0,0.13);
      padding: 0;
      width: 440px;
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
      gap: 8px;
    }
    button:hover {
      background: #0056a3;
      color: #fff;
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
      opacity: 1;
    }
    .success {
      background: #d4edda;
      color: #155724;
      opacity: 1;
    }
    a {
      color: #0078D7;
      text-decoration: underline;
    }
    .dark-mode {
      background: #121212;
      color: #f0f0f0;
    }
    .dark-mode .container {
      background: rgba(255,255,255,0.05);
    }
    .dark-mode .main {
      background: transparent;
    }
    .dark-mode .form-group input {
      background: #333;
      color: #fff;
      border: 1px solid #444;
    }
    .dark-mode button {
      background: #444;
      color: #fff;
    }
    .dark-mode a {
      color: #ccc;
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
      font-size: 18px;
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
    @media (max-width: 600px) {
      .container {
        width: 98vw;
        border-radius: 12px;
      }
      .main {
        padding: 18px 8px 12px 8px;
      }
      button, input {
        font-size: 14px;
      }
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
    <div class="container">
      <div class="main">
        <h2>Connexion</h2>
        <form method="POST" action="login.php" id="loginForm" autocomplete="on" onsubmit="return animateLogin()">
          <div class="form-group">
            <label for="username">Nom d'utilisateur</label>
            <input type="text" name="username" id="username" placeholder="Nom d'utilisateur" required value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>">
          </div>
          <div class="form-group">
            <label for="password">Mot de passe</label>
            <input type="password" name="password" id="password" placeholder="Mot de passe" required autocomplete="current-password">
          </div>
          <div class="button-wrapper">
            <button type="submit" id="loginBtn">Se connecter</button>
          </div>
        </form>
        <p style="text-align:center; margin-top: 15px;">Pas encore inscrit ? <a href="register.php">Créer un compte</a></p>
        <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
      </div>
    </div>
    <script>
        window.addEventListener("load", function () {
            document.getElementById("loader").style.display = "none";
        });

        function toggleDarkMode() {
            document.body.classList.toggle("dark-mode");
        }

        function animateLogin() {
            const btn = document.getElementById("loginBtn");
            btn.classList.add("loading");
            btn.innerHTML = 'Connexion...';

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
