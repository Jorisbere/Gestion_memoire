<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SESSION['role'] !== 'administrateur') {
    header("Location: accueil.php");
    exit();
}

$user_id = (int) $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT username, avatar FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit();
}

$username = htmlspecialchars($user['username']);
$avatar = !empty($user['avatar']) ? htmlspecialchars($user['avatar']) : '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Tableau de bord</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      display: flex;
      min-height: 100vh;
      background: linear-gradient(90deg, #00c6ff 0%, #0072ff 100%);
      color: #fff;
      transition: background 0.3s, color 0.3s;
    }
    .dark-mode {
      background: #0f1115;
      color: #e7e7e7;
    }
    .sidebar {
      width: 260px;
      background: rgba(0,0,0,0.15);
      backdrop-filter: blur(6px);
      padding: 20px;
      display: flex;
      flex-direction: column;
      gap: 14px;
    }
    .brand {
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    .user-menu {
      position: relative;
      display: flex;
      align-items: center;
      gap: 8px;
      cursor: pointer;
      user-select: none;
    }
    .avatar {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      object-fit: cover;
      border: 2px solid rgba(255,255,255,0.3);
    }
    .avatar-initiales {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      background: linear-gradient(135deg, #00c6ff, #151617ff);
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      font-size: 14px;
      color: #fff;
    }
    .username {
      font-weight: 600;
      max-width: 140px;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }
    .dropdown {
      position: absolute;
      top: 46px;
      left: 0;
      background: rgba(0,0,0,0.85);
      backdrop-filter: blur(4px);
      border-radius: 10px;
      padding: 8px 0;
      display: none;
      flex-direction: column;
      min-width: 180px;
      z-index: 20;
      box-shadow: 0 8px 20px rgba(0,0,0,0.25);
      opacity: 0;
      transform: translateY(-6px);
      transition: opacity 0.18s ease, transform 0.18s ease;
    }
    .dropdown.open {
      display: flex;
      opacity: 1;
      transform: translateY(0);
    }
    .dropdown a {
      padding: 10px 14px;
      color: #fff;
      text-decoration: none;
      display: flex;
      align-items: center;
      gap: 10px;
      transition: background 0.25s;
    }
    .dropdown a:hover {
      background: rgba(255,255,255,0.1);
    }
    #darkToggle {
      cursor: pointer;
      font-size: 15px;
      background: rgba(255,255,255,0.18);
      padding: 8px;
      border-radius: 50%;
    }
    .nav {
      display: flex;
      flex-direction: column;
      gap: 8px;
      margin-top: 8px;
    }
    .nav a {
      text-decoration: none;
      color: inherit;
      padding: 10px 12px;
      display: block;
      border-radius: 10px;
      background: rgba(255,255,255,0.08);
    }
    .nav a:hover {
      background: rgba(255,255,255,0.18);
    }
    .main {
      flex: 1;
      padding: 24px;
      max-width: 1200px;
      margin: 0 auto;
    }
    header h1 {
      font-size: 1.6rem;
      font-weight: 700;
      letter-spacing: 0.2px;
    }
  </style>
</head>
<body>
  <aside class="sidebar">
    <div class="brand">
      <div class="user-menu" tabindex="0" aria-haspopup="true" aria-expanded="false">
        <?php
          if (empty($avatar)) {
            $initiales = '';
            $mots = explode(' ', trim($username));
            foreach ($mots as $m) {
              $initiales .= strtoupper(mb_substr($m, 0, 1));
            }
            echo '<div class="avatar-initiales">'.htmlspecialchars($initiales).'</div>';
          } else {
            echo '<img src="'.htmlspecialchars($avatar).'" alt="Avatar" class="avatar">';
          }
        ?>
        <span class="username"><?= $username ?></span>
        <div class="dropdown" role="menu">
          <a href="profile.php">👤 Mon profil</a>
          <a href="settings.php">⚙️ Paramètres</a>
          <a href="logout.php">🚪 Déconnexion</a>
        </div>
      </div>
      <div id="darkToggle" title="Mode sombre" aria-label="Basculer le mode sombre">🌙</div>
    </div>
    <nav class="nav">
      <a href="dashboard.php">🏠 Tableau de bord</a>
      <a href="forms/protocoles_consultation.php">📄 Consultation Protocole</a>
      <a href="forms/programmer_soutenance.php">🏢 Programmer Soutenance</a>
      <a href="forms/memoires_archive.php">📊 Memoires Archiver</a>
      <a href="forms/attribuer_dm.php">📊 Ajouter DM</a>
      <a href="logout.php">🚪 Déconnexion</a>
    </nav>
  </aside>
  <main class="main">
    <header>
      <h1>Résumé fiscal</h1>
    </header>
    <!-- Contenu central retiré -->
  </main>
  <script>
    // 🌙 Mode sombre avec persistance
    (function initTheme() {
      if (localStorage.getItem("darkMode") === "true") {
        document.body.classList.add("dark-mode");
      }
    })();
    const darkToggleBtn = document.getElementById('darkToggle');
    darkToggleBtn.addEventListener('click', function() {
      document.body.classList.toggle('dark-mode');
      localStorage.setItem('darkMode', document.body.classList.contains('dark-mode'));
    });

    // 👤 Menu profil
    const userMenu = document.querySelector('.user-menu');
    const dropdown = document.querySelector('.dropdown');
    function closeDropdown() {
      dropdown.classList.remove('open');
      userMenu.setAttribute('aria-expanded', 'false');
    }
    function toggleDropdown() {
      const isOpen = dropdown.classList.contains('open');
      if (isOpen) {
        closeDropdown();
      } else {
        dropdown.classList.add('open');
        userMenu.setAttribute('aria-expanded', 'true');
      }
    }
    userMenu.addEventListener('click', (e) => {
      toggleDropdown();
      e.stopPropagation();
    });
    userMenu.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        toggleDropdown();
      }
      if (e.key === 'Escape') {
        closeDropdown();
      }
    });
    document.addEventListener('click', () => closeDropdown());
  </script>
</body>
</html>
