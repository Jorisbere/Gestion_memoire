<?php
session_start();
require_once 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT id, password, role FROM users WHERE username = ?");
    if ($stmt) {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($user_id, $hashed_password, $role);
            $stmt->fetch();

            if (password_verify($password, $hashed_password)) {
                // ✅ Stockage des infos dans la session
                $_SESSION['user_id'] = $user_id;
                $_SESSION['username'] = $username;
                $_SESSION['role'] = $role;

                // 🔽 AJOUTE ICI L'ENREGISTREMENT DE LA CONNEXION
                $ip = $_SERVER['REMOTE_ADDR'];
                $agent = substr($_SERVER['HTTP_USER_AGENT'], 0, 255);

                $stmtLog = $conn->prepare("INSERT INTO user_logins (user_id, ip_address, user_agent) VALUES (?, ?, ?)");
                $stmtLog->bind_param("iss", $user_id, $ip, $agent);
                $stmtLog->execute();
                $stmtLog->close();

                // 🔼 FIN DE L'AJOUT

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
    <title>Connexion</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Icônes Font Awesome -->
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
    <h2>Connexion</h2>
    <form method="POST" action="login.php" id="loginForm" onsubmit="return animateLogin()">
        <div class="input-group">
            <i class="fa fa-user"></i>
            <input type="text" name="username" placeholder="Nom d'utilisateur" required>
        </div>
        <div class="input-group">
            <i class="fa fa-lock"></i>
            <input type="password" name="password" placeholder="Mot de passe" required>
        </div>
        <div class="button-wrapper">
            <button type="submit" id="loginBtn">Se connecter</button>
        </div>
    </form>
    <p style="text-align:center; margin-top: 15px;">Pas encore inscrit ? <a href="register.php">Créer un compte</a></p>
    <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
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
    btn.innerText = "Connexion...";

    setTimeout(() => {
        showWelcome();
    }, 800); // délai avant le message

    return true; // permet au formulaire de se soumettre
}

function showWelcome() {
    const msg = document.createElement("div");
    msg.className = "welcome-message";
    msg.innerHTML = "<strong>Bienvenue !</strong><br>Redirection en cours...";
    document.body.appendChild(msg);
    msg.style.display = "block";

    setTimeout(() => {
        
    }, 1500);
}

    </script>
</body>
</html>
