<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'administrateur') {
    header("Location: ../login.php");
    exit();
}

// Récupération utilisateur pour sidebar type dashboard
$user_id = (int) $_SESSION['user_id'];
$__stmt = $conn->prepare("SELECT username, avatar FROM users WHERE id = ?");
$__stmt->bind_param("i", $user_id);
$__stmt->execute();
$__res = $__stmt->get_result();
$__user = $__res->fetch_assoc();
$__stmt->close();
$__username = htmlspecialchars($__user['username'] ?? 'Utilisateur');
$__avatar_raw = $__user['avatar'] ?? '';
$__avatar = '';
if (!empty($__avatar_raw)) {
  $__trim = ltrim($__avatar_raw);
  if (preg_match('#^https?://#i', $__trim)) {
    $__avatar = $__trim;
  } elseif (substr($__trim, 0, 1) === '/') {
    $__avatar = $__trim;
  } else {
    $__avatar = '/Gestion_Memoire/' . ltrim($__trim, '/');
  }
  $__avatar = htmlspecialchars($__avatar);
}

$notification = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'], $_POST['action'])) {
    $selected_id = intval($_POST['user_id']);
    $action = $_POST['action'];

    if ($action === 'attribuer') {
        $stmt = $conn->prepare("UPDATE users SET role = 'DM' WHERE id = ?");
        $stmt->bind_param("i", $selected_id);
        if ($stmt->execute()) {
            $notification = "✅ L'utilisateur a été nommé DM avec succès.";
        } else {
            $notification = "❌ Une erreur est survenue lors de l'attribution.";
        }
        $stmt->close();
    } elseif ($action === 'revoquer') {
        $stmt = $conn->prepare("UPDATE users SET role = 'etudiant' WHERE id = ?");
        $stmt->bind_param("i", $selected_id);
        if ($stmt->execute()) {
            $notification = "✅ Le rôle DM a été révoqué. L'utilisateur est redevenu étudiant.";
        } else {
            $notification = "❌ Une erreur est survenue lors de la révocation.";
        }
        $stmt->close();
    }
}


// Récupération des utilisateurs
$result = $conn->query("SELECT id, username, role FROM users WHERE role IN ('etudiant', 'DM') ORDER BY username ASC");

$etudiants = [];
$dms = [];

while ($user = $result->fetch_assoc()) {
    if ($user['role'] === 'etudiant') {
        $etudiants[] = $user;
    } elseif ($user['role'] === 'DM') {
        $dms[] = $user;
    }
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Attribuer un DM</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" type="image/x-icon" href="../assets/favicon.ico">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background: linear-gradient(90deg, #49c4e6ff 0%, #e5edf7ff 100%);
      padding: 40px;
      color: #333;
    }

    /* Main wrapper to handle sidebar space and centering */
    .main {
      display: flex;
      align-items: center;
      justify-content: center;
      transition: background 0.3s, color 0.3s;
    }

    /* Centrage horizontal en tenant compte du sidebar */
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
      max-width: 600px;
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

    /* Sidebar dashboard-like */
    .sidebar { width: 260px; background: rgba(0,0,0,0.15); backdrop-filter: blur(6px); padding: 20px; display: flex; flex-direction: column; gap: 14px; position: fixed; top: 0; left: 0; bottom: 0; z-index: 900; border-radius: 12px;}
    .user-menu { position: relative; display: flex; align-items: center; gap: 8px; cursor: pointer; user-select: none; }
    .avatar { width: 36px; height: 36px; border-radius: 50%; object-fit: cover; border: 2px solid rgba(255,255,255,0.3); }
    .avatar-initiales { width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg, #00c6ff, #151617ff); display:flex; align-items:center; justify-content:center; font-weight:700; font-size:14px; color:#fff; }
    .username { font-weight: 600; max-width: 140px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .dropdown { position: absolute; top: 46px; left: 0; background: rgba(0,0,0,0.85); backdrop-filter: blur(4px); border-radius: 10px; padding: 8px 0; display: none; flex-direction: column; min-width: 180px; z-index: 920; box-shadow: 0 8px 20px rgba(0,0,0,0.25); opacity: 0; transform: translateY(-6px); transition: opacity .18s ease, transform .18s ease; }
    .dropdown.open { display: flex; opacity: 1; transform: translateY(0); }
    .dropdown a { padding: 10px 14px; color: #fff; text-decoration: none; display: flex; align-items: center; gap: 10px; transition: background .25s; }
    .dropdown a:hover { background: rgba(255,255,255,0.1); }
    .nav { display: flex; flex-direction: column; gap: 8px; margin-top: 8px; }
    .nav a { text-decoration: none; color: inherit; padding: 10px 12px; display: block; border-radius: 10px; background: rgba(255,255,255,0.08); }
    .nav a:hover { background: rgba(255,255,255,0.18); }
    #darkToggle {
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
    #darkToggle:hover { background: rgba(255,255,255,0.3); transform: scale(1.05); }

    /* Dark mode styles */
    .dark-mode {
      background: #0f1115 !important;
      color: #e7e7e7 !important;
    }
    .dark-mode .sidebar { background: rgba(255,255,255,0.04); }
    .dark-mode .nav a { background: rgba(255,255,255,0.06); color: #e7e7e7; }
    .dark-mode .nav a:hover { background: rgba(255,255,255,0.12); }
    .dark-mode .dropdown { background: rgba(30,32,38,0.98); }
    .dark-mode .container {
      background: #181b22 !important;
      color: #e7e7e7 !important;
      box-shadow: 0 6px 18px rgba(0,0,0,0.4);
    }
    .dark-mode h2 {
      color: #4fc3f7 !important;
    }
    .dark-mode select,
    .dark-mode input,
    .dark-mode textarea {
      background: #23262b !important;
      color: #e7e7e7 !important;
      border: 1px solid #444 !important;
    }
    .dark-mode button {
      background: #4fc3f7 !important;
      color: #181b22 !important;
    }
    .dark-mode button:hover {
      background: #1976d2 !important;
      color: #fff !important;
    }
    .dark-mode .success,
    .dark-mode .error,
    .dark-mode .notification {
      background: #23262b !important;
      color: #e7e7e7 !important;
    }

    h2 {
      text-align: center;
      margin-bottom: 30px;
      color: #0078D7;
      transition: color 0.3s;
    }

    form {
      display: flex;
      flex-direction: column;
      gap: 16px;
      width: 100%;
    }

    select {
      padding: 10px;
      border-radius: 8px;
      border: 1px solid #ccc;
      font-size: 16px;
      transition: background 0.3s, color 0.3s, border 0.3s;
    }

    button {
      padding: 12px;
      background: #0078D7;
      color: #333;
      border: none;
      border-radius: 8px;
      font-size: 16px;
      cursor: pointer;
      transition: background 0.3s, color 0.3s;
    }

    button:hover {
      background: #0056a3;
      color: #fff;
    }

    .notification {
      margin-top: 20px;
      padding: 12px;
      border-radius: 8px;
      font-weight: bold;
      text-align: center;
      transition: background 0.3s, color 0.3s;
    }

    .success {
      background:rgb(229, 232, 232);
      color: #155724;
    }

    .error {
      background:rgb(229, 232, 232);
      color: #721c24;
    }

    .back-button {
      text-align: center;
      margin-bottom: 20px;
    }

    .back-button a {
      text-decoration: none;
      color: #0078D7;
      font-weight: bold;
    }

  </style>
</head>
<body>
  <!-- Sidebar de type dashboard -->
  <aside class="sidebar">
    <div class="user-menu" tabindex="0" aria-expanded="false">
      <?php if (empty($__avatar)): ?>
        <?php $__initiales = ''; $__mots = explode(' ', trim($__username)); foreach ($__mots as $__m) { $__initiales .= strtoupper(mb_substr($__m, 0, 1)); } ?>
        <div class="avatar-initiales"><?= htmlspecialchars($__initiales) ?></div>
      <?php else: ?>
        <img src="<?= $__avatar ?>" alt="Avatar" class="avatar">
      <?php endif; ?>
      <span class="username"><?= $__username ?></span>
      <div class="dropdown" role="menu">
        <a href="../profile.php"><i class="fa-solid fa-user"></i> Mon profil</a>
        <a href="../settings.php"><i class="fa-solid fa-cog"></i> Paramètres</a>
        <a href="../logout.php"><i class="fa-solid fa-arrow-right-from-bracket"></i> Déconnexion</a>
      </div>
    </div>
    <div id="darkToggle" tabindex="0" role="button" aria-label="Basculer le mode sombre"><i class="fa-solid fa-moon"></i></div>
    <nav class="nav">
      <a href="../dashboard.php"><i class="fa-solid fa-house"></i> Tableau de bord</a>
      <a href="./protocoles_consultation.php"><i class="fa-solid fa-eye"></i> Consultation Protocole</a>
      <a href="./programmer_soutenance.php"><i class="fa-solid fa-book"></i> Programmer Soutenance</a>
      <a href="./memoires_archive.php"><i class="fa-solid fa-box-archive"></i> Memoires Archiver</a>
      <a href="./attribuer_dm.php"><i class="fa-solid fa-user"></i> Ajouter DM</a>
      <a href="../logout.php"><i class="fa-solid fa-arrow-right-from-bracket"></i> Déconnexion</a>
    </nav>
  </aside>
  <div class="main">
    <div class="container">
      <h2><i class="fa-solid fa-user"></i> Attribuer le rôle DM</h2>

      <form method="POST">
        <label for="user_id">Sélectionner un utilisateur :</label>
        <select name="user_id" id="user_id" required>
          <option value="">-- Choisir --</option>
          <optgroup label="Étudiants">
            <?php foreach ($etudiants as $user): ?>
              <option value="<?= $user['id'] ?>">
                <?= htmlspecialchars($user['username']) ?> (étudiant)
              </option>
            <?php endforeach; ?>
          </optgroup>
          <optgroup label="DM actuels">
            <?php foreach ($dms as $user): ?>
              <option value="<?= $user['id'] ?>">
                <?= htmlspecialchars($user['username']) ?> (DM)
              </option>
            <?php endforeach; ?>
          </optgroup>
        </select>

        <button type="submit" name="action" value="attribuer">Attribuer le rôle DM</button>
        <button type="submit" name="action" value="revoquer" style="background:#d9534f;">Révoquer le rôle DM</button>

        <!-- <div class="back-button">
          <a href="../dashboard.php">← Retour au tableau de bord</a>
        </div> -->
      </form>

      <?php if ($notification): ?>
        <div class="notification <?= str_starts_with($notification, '✅') ? 'success' : 'error' ?>">
          <?= $notification ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
  <script>
  (function(){
    const userMenu = document.querySelector('.user-menu');
    const dropdown = document.querySelector('.dropdown');
    function closeDropdown(){ if(!dropdown) return; dropdown.classList.remove('open'); userMenu && userMenu.setAttribute('aria-expanded','false'); }
    function toggleDropdown(){ if(!dropdown) return; const isOpen = dropdown.classList.contains('open'); if (isOpen) closeDropdown(); else { dropdown.classList.add('open'); userMenu && userMenu.setAttribute('aria-expanded','true'); } }
    if (userMenu) {
      userMenu.addEventListener('click', toggleDropdown);
      userMenu.addEventListener('keydown', (e)=>{ if(e.key==='Enter'||e.key===' '){ e.preventDefault(); toggleDropdown(); }});
      document.addEventListener('click', (e)=>{ if (!userMenu.contains(e.target)) closeDropdown(); });
    }

    // Dark mode (identique à accueil.php)
    const darkToggleBtn = document.getElementById('darkToggle');
    if (localStorage.getItem('darkMode') === 'true') {
      document.body.classList.add('dark-mode');
    }
    if (darkToggleBtn) {
      darkToggleBtn.addEventListener('click', () => {
        document.body.classList.toggle('dark-mode');
        localStorage.setItem('darkMode', document.body.classList.contains('dark-mode'));
      });
      darkToggleBtn.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          darkToggleBtn.click();
        }
      });
    }
  })();
  </script>
</body>
</html>
