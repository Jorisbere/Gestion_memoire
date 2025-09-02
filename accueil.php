<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int) $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT username, avatar, role FROM users WHERE id = ?");
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
$role = $user['role'] ?? 'etudiant';

if ($role === 'administrateur') {
    header("Location: dashboard.php");
    exit();
}

function getValidationStatus($table, $user_id, $conn) {
    $query = "SELECT etat_validation FROM $table WHERE user_id = ? ORDER BY id DESC LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['etat_validation'] ?? null;
}

$protocole_status = getValidationStatus('protocoles', $user_id, $conn);
$soutenance_status = getValidationStatus('demandes_soutenance', $user_id, $conn);

?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Accueil</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      margin: 0;
      padding: 0;
      display: flex;
      min-height: 100vh;
      background: linear-gradient(90deg, #00c6ff 0%, #0072ff 100%);
      color: #fff;
      transition: background 0.3s, color 0.3s;
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
      text-transform: uppercase;
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
    #darkToggle,
.dashboard-btn {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 15px;
  background: rgba(255,255,255,0.18);
  border-radius: 50%;
  cursor: pointer;
  transition: background 0.3s, transform 0.2s;
  box-shadow: 0 2px 6px rgba(0,0,0,0.2);
  position: fixed;
  top: 20px;
  right: 20px;
  z-index: 1000;
}

#darkToggle:hover,
.dashboard-btn:hover {
  background: rgba(255,255,255,0.3);
  transform: scale(1.05);
}

    .nav {
      display: flex;
      flex-direction: column;
      gap: 8px;
      margin-top: 20px;
    }
    .nav a {
      text-decoration: none;
      color: inherit;
      padding: 10px 12px;
      display: block;
      border-radius: 10px;
      transition: background 0.25s, transform 0.15s;
      background: rgba(255,255,255,0.08);
    }
    .nav a:hover {
      background: rgba(255,255,255,0.18);
      transform: translateY(-1px);
    }
    main {
      flex: 1;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      text-align: center;
      padding: 40px 20px;
    }
    .dark-mode {
      background: #0f1115;
      color: #e7e7e7;
    }
    .dark-mode .sidebar {
      background: rgba(255,255,255,0.04);
    }
    .dark-mode .nav a {
      background: rgba(255,255,255,0.06);
    }
    .dark-mode .nav a:hover {
      background: rgba(255,255,255,0.12);
    }
    .dark-mode .dropdown {
      background: rgba(30,32,38,0.98);
    }

    /* Responsive: sidebar to top on mobile */
@media (max-width: 900px) {
  body { flex-direction: column; }
  .sidebar {
    width: 100%;
    flex-direction: row;
    align-items: center;
    gap: 10px;
    overflow-x: auto;
  }
  .brand { gap: 10px; }
  .nav { flex-direction: row; gap: 10px; }
  .nav a { white-space: nowrap; }
}

/* Animations */
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(-6px); }
  to { opacity: 1; transform: translateY(0); }
}
  </style>
</head>
<body>
  <aside class="sidebar">
    <div class="user-menu" tabindex="0" aria-expanded="false">
      <?php
      $avatar = $_SESSION['avatar'] ?? '';
        $initiales = '';
        if (empty($avatar)) {
            $mots = explode(' ', trim($username));
            foreach ($mots as $mot) {
                $initiales .= strtoupper(mb_substr($mot, 0, 1));
            }
            echo '<div class="avatar-initiales">'.htmlspecialchars($initiales).'</div>';
        } else {
            echo '<img src="'.htmlspecialchars($avatar).'" alt="Avatar de '.htmlspecialchars($username).'" class="avatar">';
        }
      ?>
      
      <span class="username"><?= $username ?></span>
      <div class="dropdown" role="menu">
        <a>--- <strong><?= htmlspecialchars($role) ?></strong></a>
        <a href="profile.php">👤 Mon profil</a>
        <a href="settings.php">⚙️ Paramètres</a>
        <a href="logout.php">🚪 Déconnexion</a>
      </div>
    </div>
    <div id="darkToggle" tabindex="0" role="button" aria-label="Basculer le mode sombre">🌙</div>

    <nav class="nav">
      <?php if ($role === 'etudiant'): ?>
  <a href="forms/protocol.php">📄 Dépot de protocole</a>

  <?php if ($protocole_status === 'valide'): ?>
    <a href="forms/soutenance.php">🎓 Demande de Soutenance</a>
  <?php else: ?>
    <span style="opacity: 0.5; cursor: not-allowed;" title="Protocole non validé">🧑‍💼 Demande de Soutenance (bloqué)</span>
  <?php endif; ?>

  <?php if ($protocole_status === 'valide' && $soutenance_status === 'valide'): ?>
    <a href="forms/memoire.php">🏢 Dépot mémoire Final</a>
  <?php else: ?>
    <span style="opacity: 0.5; cursor: not-allowed;" title="Soutenance non validée">🏢 Dépot mémoire Final (bloqué)</span>
  <?php endif; ?>

  <a href="forms/memoires_archive.php">📊 Memoires Archiver</a>

  <a href="forms/statut.php">🛈 Infos</a>
<?php endif; ?>


      <?php if ($role === 'DM'): ?>
        <a href="forms/espace_dm.php">📁 Espace DM</a>
        <a href="forms/admin_soutenance.php">🗒 Consultation des Soutenances</a>
        <a href="forms/memoires_consultation.php">📕 Consultation des mémoires finaux</a>
        <a href="forms/memoires_archive.php">📊 Memoires Archiver</a>
      <?php endif; ?>
      <a href="logout.php">🚪 Déconnexion</a>
    </nav>
  </aside>

  <main>
    <img src="assets/images/télécharger.jpg" alt="Logo Fiscal" class="logo">
    <h1>Bienvenue <?= $username ?> 👋</h1>
    <p>Gérez vos déclarations, visualisez vos revenus, suivez vos performances et accédez à des outils fiscaux intelligents.</p>
  </main>

  <script>
  document.addEventListener('DOMContentLoaded', () => {
    const darkToggleBtn = document.getElementById('darkToggle');
    const userMenu = document.querySelector('.user-menu');
    const dropdown = document.querySelector('.dropdown');

    if (localStorage.getItem("darkMode") === "true") {
      document.body.classList.add("dark-mode");
    }

    darkToggleBtn.addEventListener('click', () => {
      document.body.classList.toggle("dark-mode");
      localStorage.setItem("darkMode", document.body.classList.contains("dark-mode"));
    });

    darkToggleBtn.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        darkToggleBtn.click();
      }
    });

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

      userMenu.addEventListener('click', toggleDropdown);
      userMenu.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          toggleDropdown();
        }
      });

      document.addEventListener('click', (e) => {
        if (!userMenu.contains(e.target)) {
          closeDropdown();
        }
      });
    });
  </script>
</body>
<html>