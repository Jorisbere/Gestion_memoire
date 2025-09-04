<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int) $_SESSION['user_id'];

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $birthdate = $_POST['birthdate'] ?? null;
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $password_hash = null;

    // Vérification du mot de passe
    if (!empty($new_password)) {
        if ($new_password === $confirm_password) {
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        } else {
            die("⚠️ Les mots de passe ne correspondent pas.");
        }
    }

    // Gestion de l'image de profil
    if (!empty($_FILES['avatar']['name'])) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0755, true);
        }
        $filename = basename($_FILES["avatar"]["name"]);
        $target_file = $target_dir . time() . "_" . $filename;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($imageFileType, $allowed_types)) {
            if (move_uploaded_file($_FILES["avatar"]["tmp_name"], $target_file)) {
                $avatar_path = $target_file;
            }
        }
    }

    // Construction dynamique de la requête SQL
    $fields = "username = ?, email = ?, location = ?, bio = ?, birthdate = ?";
    $params = [$username, $email, $location, $bio, $birthdate];
    $types = "sssss";

    if (isset($avatar_path)) {
        $fields .= ", avatar = ?";
        $params[] = $avatar_path;
        $types .= "s";
    }

    if ($password_hash) {
        $fields .= ", password = ?";
        $params[] = $password_hash;
        $types .= "s";
    }

    $fields .= " WHERE id = ?";
    $params[] = $user_id;
    $types .= "i";

    $sql = "UPDATE users SET $fields";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $stmt->close();

    header("Location: profile.php");
    exit();
}

// Récupération des données actuelles
$stmt = $conn->prepare("SELECT username, email, location, bio, birthdate, avatar FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Préparation des variables pour l'affichage (mêmes améliorations que dans profile.php)
$username = htmlspecialchars($user['username']);
$email = htmlspecialchars($user['email'] ?? '');
$location = htmlspecialchars($user['location'] ?? 'Non renseignée');
$bio = htmlspecialchars($user['bio'] ?? 'Aucune biographie');
$birthdate = $user['birthdate'] ? date('Y-m-d', strtotime($user['birthdate'])) : '';
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

// Initiales pour l'avatar si pas d'image
$initiales = '';
$mots = explode(' ', trim($username));
foreach ($mots as $m) { $initiales .= strtoupper(mb_substr($m, 0, 1)); }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Modifier le Profil</title>
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
      max-width: 700px;
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
      align-items: center;
      gap: 6px;
    }
    .form-group input,
    .form-group textarea {
      width: 100%;
      padding: 10px 12px;
      border-radius: 8px;
      border: 1px solid #b0c4de;
      font-size: 16px;
      background: #f7fafc;
      margin-bottom: 2px;
      transition: border 0.2s;
    }
    .form-group input:focus,
    .form-group textarea:focus {
      border: 1.5px solid #0078D7;
      outline: none;
    }
    .avatar-preview img {
      max-width: 100px;
      border-radius: 50%;
      margin-top: 10px;
      border: 2px solid #0078D7;
      background: #e5edf7;
    }
    .btn-save {
      background: #0078D7;
      color: #fff;
      border: none;
      border-radius: 8px;
      font-size: 16px;
      cursor: pointer;
      text-decoration: none;
      font-weight: bold;
      padding: 12px 24px;
      margin-right: 10px;
      transition: background 0.3s, color 0.3s;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .btn-save:hover {
      background: #0056a3;
      color: #fff;
    }
  </style>
</head>
<body>
  <aside class="sidebar">
    <div class="user-menu" tabindex="0" aria-expanded="false">
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
        <p><i class="fa-solid fa-calendar-alt"></i> <?= $birthdate ? date('d/m/Y', strtotime($birthdate)) : 'Non renseignée' ?></p>
        <p><i class="fa-solid fa-user-edit"></i> <?= $bio ?></p>
      </div>
      <form method="POST" enctype="multipart/form-data" class="form-profile" style="width:100%;margin-top:20px;">
        <div class="form-group">
          <label for="username"><i class="fa-solid fa-user"></i> Nom d'utilisateur :</label>
          <input type="text" name="username" id="username" value="<?= $username ?>" required />
        </div>

        <div class="form-group">
          <label for="email"><i class="fa-solid fa-envelope"></i> Email :</label>
          <input type="email" name="email" id="email" value="<?= $email ?>" required />
        </div>

        <div class="form-group">
          <label for="location"><i class="fa-solid fa-map-marker-alt"></i> Localisation :</label>
          <input type="text" name="location" id="location" value="<?= $location !== 'Non renseignée' ? $location : '' ?>" />
        </div>

        <div class="form-group">
          <label for="birthdate"><i class="fa-solid fa-calendar-alt"></i> Date de naissance :</label>
          <input type="date" name="birthdate" id="birthdate" value="<?= $birthdate ?>" />
        </div>

        <div class="form-group">
          <label for="bio"><i class="fa-solid fa-user-edit"></i> Biographie :</label>
          <textarea name="bio" id="bio"><?= $bio !== 'Aucune biographie' ? $bio : '' ?></textarea>
        </div>

        <div class="form-group">
          <label for="avatar"><i class="fa-solid fa-image"></i> Photo de profil :</label>
          <input type="file" name="avatar" id="avatar" accept="image/*" />
          <?php if (!empty($user['avatar'])): ?>
            <div class="avatar-preview">
              <img src="<?= $avatar ?>" alt="Avatar actuel" />
            </div>
          <?php endif; ?>
        </div>

        <div class="form-group">
          <label for="new_password"><i class="fa-solid fa-lock"></i> Nouveau mot de passe :</label>
          <input type="password" name="new_password" id="new_password" placeholder="Laisser vide pour ne pas changer" />
        </div>

        <div class="form-group">
          <label for="confirm_password"><i class="fa-solid fa-lock"></i> Confirmer le mot de passe :</label>
          <input type="password" name="confirm_password" id="confirm_password" />
        </div>

        <div class="profile-actions">
          <button type="submit" class="btn-save"><i class="fa-solid fa-floppy-disk"></i> Enregistrer les modifications</button>
          <a href="javascript:history.back()" class="btn-back"><i class="fa-solid fa-arrow-left"></i> Retour</a>
        </div>
      </form>
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
