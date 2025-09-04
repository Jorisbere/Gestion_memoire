<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    return;
}

$sbUserId = (int) $_SESSION['user_id'];

$sbStmt = $conn->prepare("SELECT username, avatar, role FROM users WHERE id = ?");
$sbStmt->bind_param("i", $sbUserId);
$sbStmt->execute();
$sbResult = $sbStmt->get_result();
$sbUser = $sbResult->fetch_assoc();
$sbStmt->close();

if (!$sbUser) {
    return;
}

$sbUsername = htmlspecialchars($sbUser['username'] ?? '');
$sbAvatarRaw = $sbUser['avatar'] ?? '';
$sbRole = $sbUser['role'] ?? 'etudiant';

// Normaliser l'URL de l'avatar
$sbAvatar = '';
if (!empty($sbAvatarRaw)) {
    $sbTrimmed = ltrim($sbAvatarRaw);
    if (preg_match('#^https?://#i', $sbTrimmed)) {
        $sbAvatar = $sbTrimmed;
    } elseif (substr($sbTrimmed, 0, 1) === '/') {
        $sbAvatar = $sbTrimmed;
    } else {
        $sbAvatar = '/Gestion_Memoire/' . ltrim($sbTrimmed, '/');
    }
    $sbAvatar = htmlspecialchars($sbAvatar);
}

function _gm_sidebar_getValidationStatus($table, $user_id, $conn) {
    $q = "SELECT etat_validation FROM $table WHERE user_id = ? ORDER BY id DESC LIMIT 1";
    $s = $conn->prepare($q);
    $s->bind_param("i", $user_id);
    $s->execute();
    $r = $s->get_result();
    $row = $r->fetch_assoc();
    return $row['etat_validation'] ?? null;
}

$sbProtocoleStatus = null;
$sbSoutenanceStatus = null;
if ($sbRole === 'etudiant') {
    $sbProtocoleStatus = _gm_sidebar_getValidationStatus('protocoles', $sbUserId, $conn);
    $sbSoutenanceStatus = _gm_sidebar_getValidationStatus('demandes_soutenance', $sbUserId, $conn);
}
?>
<style>
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
  .user-menu { position: relative; display: flex; align-items: center; gap: 8px; cursor: pointer; user-select: none; }
  .avatar { width: 36px; height: 36px; border-radius: 50%; object-fit: cover; border: 2px solid rgba(255,255,255,0.3); }
  .avatar-initiales { width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg, #00c6ff, #151617ff); display:flex; align-items:center; justify-content:center; font-weight:700; font-size:14px; color:#fff; text-transform:uppercase; }
  .username { font-weight: 600; max-width: 140px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
  .dropdown { position: absolute; top: 46px; left: 0; background: rgba(0,0,0,0.85); backdrop-filter: blur(4px); border-radius: 10px; padding: 8px 0; display: none; flex-direction: column; min-width: 180px; z-index: 920; box-shadow: 0 8px 20px rgba(0,0,0,0.25); opacity: 0; transform: translateY(-6px); transition: opacity 0.18s ease, transform 0.18s ease; }
  .dropdown.open { display: flex; opacity: 1; transform: translateY(0); }
  .dropdown a { padding: 10px 14px; color: #fff; text-decoration: none; display: flex; align-items: center; gap: 10px; transition: background 0.25s; }
  .dropdown a:hover { background: rgba(255,255,255,0.1); }
  .nav { display: flex; flex-direction: column; gap: 8px; margin-top: 20px; }
  .nav a { text-decoration: none; color: inherit; padding: 10px 12px; display: block; border-radius: 10px; transition: background 0.25s, transform 0.15s; background: rgba(255,255,255,0.08); }
  .nav a:hover { background: rgba(255,255,255,0.18); transform: translateY(-1px); }

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

  .dark-mode { background: #0f1115 !important; color: #e7e7e7 !important; }
  .dark-mode .sidebar { background: rgba(255,255,255,0.04); }
  .dark-mode .nav a { background: rgba(255,255,255,0.06); }
  .dark-mode .nav a:hover { background: rgba(255,255,255,0.12); }
  .dark-mode .dropdown { background: rgba(30,32,38,0.98); }
</style>

<aside class="sidebar">
  <div class="user-menu" tabindex="0" aria-expanded="false">
    <?php if (empty($sbAvatar)): ?>
      <?php
        $sbInitiales = '';
        $sbMots = explode(' ', trim($sbUsername));
        foreach ($sbMots as $m) { $sbInitiales .= strtoupper(mb_substr($m, 0, 1)); }
      ?>
      <div class="avatar-initiales"><?= htmlspecialchars($sbInitiales) ?></div>
    <?php else: ?>
      <img src="<?= $sbAvatar ?>" alt="Avatar" class="avatar">
    <?php endif; ?>

    <span class="username"><?= $sbUsername ?></span>
    <div class="dropdown" role="menu">
      <a>--- <strong><?= htmlspecialchars($sbRole) ?></strong></a>
      <a href="/Gestion_Memoire/profile.php"><i class="fa-solid fa-user"></i> Mon profil</a>
      <a href="/Gestion_Memoire/settings.php"><i class="fa-solid fa-cog"></i> Paramètres</a>
      <a href="/Gestion_Memoire/logout.php"><i class="fa-solid fa-door-open"></i> Déconnexion</a>
    </div>
  </div>

  <div id="darkToggle" tabindex="0" role="button" aria-label="Basculer le mode sombre"><i class="fa-solid fa-moon"></i></div>

  <nav class="nav">
    <?php if ($sbRole === 'etudiant'): ?>
      <a href="/Gestion_Memoire/forms/protocol.php"><i class="fa-solid fa-file"></i> Dépot de protocole</a>
      <?php if ($sbProtocoleStatus === 'valide'): ?>
        <a href="/Gestion_Memoire/forms/soutenance.php"><i class="fa-solid fa-graduation-cap"></i> Demande de Soutenance</a>
      <?php else: ?>
        <span style="opacity: 0.5; cursor: not-allowed;" title="Protocole non validé"><i class="fa-solid fa-user-tie"></i> Demande de Soutenance (bloqué)</span>
      <?php endif; ?>
      <?php if ($sbProtocoleStatus === 'valide' && $sbSoutenanceStatus === 'valide'): ?>
        <a href="/Gestion_Memoire/forms/memoire.php"><i class="fa-solid fa-building"></i> Dépot mémoire Final</a>
      <?php else: ?>
        <span style="opacity: 0.5; cursor: not-allowed;" title="Soutenance non validée"><i class="fa-solid fa-building"></i> Dépot mémoire Final (bloqué)</span>
      <?php endif; ?>
      <a href="/Gestion_Memoire/forms/memoires_archive.php"><i class="fa-solid fa-folder-open"></i> Memoires Archiver</a>
      <a href="/Gestion_Memoire/forms/statut.php"><i class="fa-solid fa-info-circle"></i> Infos</a>
    <?php elseif ($sbRole === 'DM'): ?>
      <a href="/Gestion_Memoire/forms/espace_dm.php"><i class="fa-solid fa-folder"></i> Espace DM</a>
      <a href="/Gestion_Memoire/forms/admin_soutenance.php"><i class="fa-solid fa-file"></i> Consultation des Soutenances</a>
      <a href="/Gestion_Memoire/forms/memoires_consultation.php"><i class="fa-solid fa-book"></i> Consultation des mémoires finaux</a>
      <a href="/Gestion_Memoire/forms/memoires_archive.php"><i class="fa-solid fa-chart-bar"></i> Memoires Archiver</a>
      <a href="/Gestion_Memoire/forms/liste_etudiants.php"><i class="fa-solid fa-user-graduate"></i> Liste Etudiants Suivis</a>
    <?php else: ?>
      <a href="/Gestion_Memoire/dashboard.php"><i class="fa-solid fa-gauge"></i> Tableau de bord</a>
    <?php endif; ?>
    <a href="/Gestion_Memoire/logout.php"><i class="fa-solid fa-door-open"></i> Déconnexion</a>
  </nav>
</aside>

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
