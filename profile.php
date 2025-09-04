<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$stmt = $conn->prepare("SELECT username, email, location, avatar, bio, birthdate FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
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
$email = htmlspecialchars($user['email'] ?? '');
$location = htmlspecialchars($user['location'] ?? 'Non renseignée');
$bio = htmlspecialchars($user['bio'] ?? 'Aucune biographie');
$birthdate = $user['birthdate'] ? date('d/m/Y', strtotime($user['birthdate'])) : 'Non renseignée';
$avatar_raw = $user['avatar'] ?? '';
$avatar = '';
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
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Mon Profil</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" type="image/x-icon" href="assets/favicon.ico">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background: linear-gradient(90deg, #49c4e6ff 0%, #e5edf7ff 100%);
      padding: 40px;
      color: #333;
      margin: 0;
      min-height: 100vh;
      transition: background 0.3s, color 0.3s;
    }
    .main {
      display: flex;
      align-items: center;
      justify-content: center;
      transition: background 0.3s, color 0.3s;
    }
    @media (min-width: 901px) {
      .main {
        padding-left: 280px;
      }
    }
    @media (max-width: 900px) {
      .main {
        padding-top: 80px;
        padding-left: 0;
      }
    }
    .container {
      position: relative;
      max-width: 500px;
      width: 100%;
      margin: 0 auto;
      background: rgb(229, 232, 232);
      padding: 30px;
      border-radius: 30px;
      box-shadow: 0 6px 18px rgba(0,0,0,0.1);
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
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
      position: fixed;
      top: 0;
      left: 0;
      bottom: 0;
      z-index: 900;
      border-radius: 12px;
    }
    .user-menu {
      position: relative;
      display: flex;
      align-items: center;
      gap: 8px;
      cursor: pointer;
      user-select: none;
      margin-bottom: 20px;
    }
    .avatar {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      object-fit: cover;
      border: 3px solid #fff;
      box-shadow: 0 2px 8px rgba(0,0,0,0.08);
      margin-bottom: 10px;
      background: #e5edf7;
    }
    .avatar-initiales {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      background: linear-gradient(135deg, #00c6ff, #151617ff);
      display:flex;
      align-items:center;
      justify-content:center;
      font-weight:700;
      font-size:32px;
      color:#fff;
      margin-bottom: 10px;
    }
    .username {
      font-weight: 600;
      max-width: 140px;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
      font-size: 18px;
      margin-bottom: 10px;
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
      z-index: 920;
      box-shadow: 0 8px 20px rgba(0,0,0,0.25);
      opacity: 0;
      transform: translateY(-6px);
      transition: opacity .18s ease, transform .18s ease;
    }
    .dropdown.open { display: flex; opacity: 1; transform: translateY(0); }
    .dropdown a { padding: 10px 14px; color: #fff; text-decoration: none; display: flex; align-items: center; gap: 10px; transition: background .25s; }
    .dropdown a:hover { background: rgba(255,255,255,0.1); }
    .nav { display: flex; flex-direction: column; gap: 8px; margin-top: 8px; }
    .nav a { text-decoration: none; color: inherit; padding: 10px 12px; display: block; border-radius: 10px; background: rgba(255,255,255,0.08); }
    .nav a:hover { background: rgba(255,255,255,0.18); }
    .profile-info {
      width: 100%;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 10px;
      margin-bottom: 20px;
    }
    .profile-info h2 {
      color: #0078D7;
      margin-bottom: 8px;
      font-size: 26px;
      text-align: center;
    }
    .profile-info p {
      margin: 0;
      font-size: 16px;
      color: #333;
      text-align: center;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .profile-actions {
      display: flex;
      gap: 16px;
      margin-top: 18px;
      justify-content: center;
      width: 100%;
    }
    .btn, .btn-back {
      padding: 12px 24px;
      background: #0078D7;
      color: #fff;
      border: none;
      border-radius: 8px;
      font-size: 16px;
      cursor: pointer;
      text-decoration: none;
      font-weight: bold;
      transition: background 0.3s, color 0.3s;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .btn-back {
      background: #e5edf7;
      color: #0078D7;
      border: 1px solid #0078D7;
    }
    .btn:hover {
      background: #0056a3;
      color: #fff;
    }
    .btn-back:hover {
      background: #0078D7;
      color: #fff;
    }
    @media (max-width: 900px) {
      .sidebar {
        position: static;
        width: 100%;
        border-radius: 0;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        padding: 10px 8px;
        margin-bottom: 20px;
      }
      .main {
        padding-left: 0;
        padding-top: 0;
      }
      .container {
        max-width: 98vw;
        padding: 10px;
        border-radius: 16px;
      }
    }
  </style>
</head>
<body>
  <aside class="sidebar">
    <div class="user-menu" tabindex="0" aria-expanded="false">
      <?php
        $initiales = '';
        $mots = explode(' ', trim($username));
        foreach ($mots as $m) { $initiales .= strtoupper(mb_substr($m, 0, 1)); }
      ?>
      <?php if (empty($user['avatar'])): ?>
        <div class="avatar-initiales"><?= htmlspecialchars($initiales) ?></div>
      <?php else: ?>
        <img src="<?= $avatar ?>" alt="Avatar" class="avatar">
      <?php endif; ?>
      <span class="username"><?= $username ?></span>
      <div class="dropdown" role="menu">
        <a href="profile.php"><i class="fa-solid fa-user"></i> Mon profil</a>
        <a href="settings.php"><i class="fa-solid fa-cog"></i> Paramètres</a>
        <a href="logout.php"><i class="fa-solid fa-arrow-right-from-bracket"></i> Déconnexion</a>
      </div>
    </div>
    <nav class="nav">
      <a href="dashboard.php"><i class="fa-solid fa-house"></i> Tableau de bord</a>
      <a href="forms/protocoles_consultation.php"><i class="fa-solid fa-eye"></i> Consultation Protocole</a>
      <a href="forms/programmer_soutenance.php"><i class="fa-solid fa-book"></i> Programmer Soutenance</a>
      <a href="forms/memoires_archive.php"><i class="fa-solid fa-box-archive"></i> Mémoires Archivés</a>
      <?php if ($_SESSION['role'] === 'administrateur'): ?>
        <a href="forms/attribuer_dm.php"><i class="fa-solid fa-user"></i> Ajouter DM</a>
      <?php endif; ?>
      <a href="logout.php"><i class="fa-solid fa-arrow-right-from-bracket"></i> Déconnexion</a>
    </nav>
  </aside>
  <div class="main">
    <div class="container">
      <div class="profile-info">
        <?php if (empty($user['avatar'])): ?>
          <div class="avatar-initiales"><?= htmlspecialchars($initiales) ?></div>
        <?php else: ?>
          <img src="<?= $avatar ?>" alt="Photo de profil" class="avatar" />
        <?php endif; ?>
        <h2><?= $username ?></h2>
        <p><i class="fa-solid fa-envelope"></i> <?= $email ?></p>
        <p><i class="fa-solid fa-map-marker-alt"></i> <?= $location ?></p>
        <p><i class="fa-solid fa-calendar-alt"></i> <?= $birthdate ?></p>
        <p><i class="fa-solid fa-user-edit"></i> <?= $bio ?></p>
      </div>
      <div class="profile-actions">
        <a href="javascript:history.back()" class="btn-back"><i class="fa-solid fa-arrow-left"></i> Retour</a>
        <a href="settings.php" class="btn"><i class="fa-solid fa-user-edit"></i> Modifier le profil</a>
      </div>
    </div>
  </div>
  <script>
    // Dropdown menu pour le menu utilisateur
    document.querySelectorAll('.user-menu').forEach(function(menu) {
      menu.addEventListener('click', function() {
        var dropdown = this.querySelector('.dropdown');
        dropdown.classList.toggle('open');
      });
      menu.addEventListener('blur', function() {
        var dropdown = this.querySelector('.dropdown');
        dropdown.classList.remove('open');
      });
    });
  </script>
</body>
</html>
