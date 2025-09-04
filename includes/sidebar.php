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

  /* === DARK MODE POUR LE MAIN ET SES ELEMENTS === */
  .dark-mode .main {
    background: #181b22 !important;
    color: #e7e7e7 !important;
  }
  .dark-mode h2 {
    color: #4fc3f7 !important;
  }
  .dark-mode .search-form input,
  .dark-mode .search-form button,
  .dark-mode .search-form select {
    background: #232733 !important;
    color: #e7e7e7 !important;
    border: 1px solid #444 !important;
  }
  .dark-mode .search-form button {
    background: #1565c0 !important;
    color: #fff !important;
  }
  .dark-mode table {
    background: #232733 !important;
    color: #e7e7e7 !important;
    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
  }
  .dark-mode th, .dark-mode td {
    border-bottom: 1px solid #333 !important;
    color: #e7e7e7 !important;
  }
  .dark-mode th {
    background: #1565c0 !important;
    color: #fff !important;
  }
  .dark-mode tr:hover {
    background: #1a222d !important;
  }
  .dark-mode .download-link {
    color: #4fc3f7 !important;
  }
  .dark-mode .back-button a {
    color: #4fc3f7 !important;
  }
  .dark-mode select {
    background: #232733 !important;
    color: #e7e7e7 !important;
    border: 1px solid #444 !important;
  }
  .dark-mode .btn-success {
    background-color: #388e3c !important;
    color: #fff !important;
  }
  .dark-mode .btn-danger {
    background-color: #b71c1c !important;
    color: #fff !important;
  }
  .dark-mode .btn-success:hover {
    background-color: #2e7031 !important;
  }
  .dark-mode .btn-danger:hover {
    background-color: #7f1313 !important;
  }
  .dark-mode .notification {
    background-color: #1b2a1b !important;
    border-color: #388e3c !important;
    color: #e7e7e7 !important;
  }
  .dark-mode .notification.success {
    background-color: #1e2e1e !important;
    border-color: #388e3c !important;
  }
  .dark-mode .notification.error {
    background-color: #2d1b1b !important;
    border-color: #b71c1c !important;
    color: #ffbdbd !important;
  }
  .dark-mode .loading-overlay {
    background: rgba(15, 17, 21, 0.92) !important;
  }
  .dark-mode .loading-box {
    background: #232733 !important;
    color: #e7e7e7 !important;
    border: 1px solid #333 !important;
  }
  .dark-mode .spinner {
    border: 3px solid #232733 !important;
    border-top-color: #4fc3f7 !important;
  }
  /* Badges */
  .badge.bg-success { background: #28a745; color: #fff; border-radius: 4px; padding: 3px 8px; }
  .badge.bg-danger { background: #dc3545; color: #fff; border-radius: 4px; padding: 3px 8px; }
  .badge.bg-warning { background: #ffc107; color: #333; border-radius: 4px; padding: 3px 8px; }
  .dark-mode .badge.bg-success { background: #388e3c !important; color: #fff !important; }
  .dark-mode .badge.bg-danger { background: #b71c1c !important; color: #fff !important; }
  .dark-mode .badge.bg-warning { background: #ffb300 !important; color: #232733 !important; }
  /* Fin badges */

  /* === DARK MODE POUR LES FORMULAIRES DE REMPLISSAGE === */
  .dark-mode form,
  .dark-mode .form-container,
  .dark-mode .form-box,
  .dark-mode .form-section,
  .dark-mode .container,
  .dark-mode .container-fluid,
  .dark-mode .content-container {
    background: #232733 !important;
    color: #e7e7e7 !important;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.18);
    border: 1px solid #333 !important;
  }
  .dark-mode form label,
  .dark-mode .form-label {
    color: #b3e5fc !important;
  }
  .dark-mode form input,
  .dark-mode form textarea,
  .dark-mode form select,
  .dark-mode .form-control,
  .dark-mode .form-select,
  .dark-mode .form-input,
  .dark-mode .form-textarea {
    background: #181b22 !important;
    color: #e7e7e7 !important;
    border: 1px solid #444 !important;
    border-radius: 6px;
    transition: background 0.2s, color 0.2s;
  }
  .dark-mode form input:focus,
  .dark-mode form textarea:focus,
  .dark-mode form select:focus,
  .dark-mode .form-control:focus,
  .dark-mode .form-select:focus,
  .dark-mode .form-input:focus,
  .dark-mode .form-textarea:focus {
    background: #232733 !important;
    color: #fff !important;
    border-color: #4fc3f7 !important;
    outline: none;
  }
  .dark-mode form button,
  .dark-mode .form-button,
  .dark-mode input[type="submit"],
  .dark-mode input[type="button"] {
    background: #1565c0 !important;
    color: #fff !important;
    border: none;
    border-radius: 6px;
    transition: background 0.2s;
  }
  .dark-mode form button:hover,
  .dark-mode .form-button:hover,
  .dark-mode input[type="submit"]:hover,
  .dark-mode input[type="button"]:hover {
    background: #1976d2 !important;
  }
  .dark-mode .form-hint,
  .dark-mode .form-help {
    color: #b3e5fc !important;
  }
  .dark-mode .form-error,
  .dark-mode .form-error-message {
    color: #ffbdbd !important;
    background: #2d1b1b !important;
    border: 1px solid #b71c1c !important;
    border-radius: 6px;
    padding: 6px 10px;
  }
  .dark-mode .form-success,
  .dark-mode .form-success-message {
    color: #b9f6ca !important;
    background: #1e2e1e !important;
    border: 1px solid #388e3c !important;
    border-radius: 6px;
    padding: 6px 10px;
  }
  /* Pour les champs désactivés */
  .dark-mode form input:disabled,
  .dark-mode form textarea:disabled,
  .dark-mode form select:disabled {
    background: #232733 !important;
    color: #888 !important;
    opacity: 0.7;
  }
  /* Pour les champs readonly */
  .dark-mode form input[readonly],
  .dark-mode form textarea[readonly] {
    background: #232733 !important;
    color: #b3e5fc !important;
    opacity: 0.9;
  }
  /* Pour les fieldset et legend */
  .dark-mode form fieldset {
    border: 1px solid #444 !important;
    background: #181b22 !important;
  }
  .dark-mode form legend {
    color: #4fc3f7 !important;
    background: transparent !important;
  }
  /* Pour les input file */
  .dark-mode input[type="file"] {
    color: #e7e7e7 !important;
    background: #232733 !important;
    border: 1px solid #444 !important;
  }
  /* Pour les checkboxes et radios */
  .dark-mode input[type="checkbox"],
  .dark-mode input[type="radio"] {
    accent-color: #4fc3f7 !important;
    background: #232733 !important;
    border: 1px solid #444 !important;
  }
  /* Pour les select option */
  .dark-mode select option {
    background: #232733 !important;
    color: #e7e7e7 !important;
  }
  /* Pour les placeholders */
  .dark-mode ::placeholder {
    color: #b3e5fc !important;
    opacity: 1;
  }
  /* Pour les form-group ou row */
  .dark-mode .form-group,
  .dark-mode .row {
    background: transparent !important;
  }
  /* Pour les input-group */
  .dark-mode .input-group-text {
    background: #232733 !important;
    color: #b3e5fc !important;
    border: 1px solid #444 !important;
  }
  /* Pour les progress bar dans les formulaires */
  .dark-mode .progress-bar {
    background: #4fc3f7 !important;
    color: #232733 !important;
  }
  /* Pour les labels obligatoires */
  .dark-mode label.required:after {
    color: #ffbdbd !important;
  }
  /* Fin dark mode formulaires */
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
    // Appliquer le dark mode si déjà activé
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

    // Pour forcer le dark mode sur les formulaires et containers dynamiquement ajoutés (ex: AJAX)
    const observer = new MutationObserver(() => {
      if (document.body.classList.contains('dark-mode')) {
        document.querySelectorAll('form, .form-container, .form-box, .form-section, .container, .container-fluid, .content-container').forEach(el => {
          el.classList.add('dark-mode');
        });
      } else {
        document.querySelectorAll('form, .form-container, .form-box, .form-section, .container, .container-fluid, .content-container').forEach(el => {
          el.classList.remove('dark-mode');
        });
      }
    });
    observer.observe(document.body, { childList: true, subtree: true });

    // Appliquer la classe dark-mode sur les formulaires et containers déjà présents au chargement
    window.addEventListener('DOMContentLoaded', function() {
      if (document.body.classList.contains('dark-mode')) {
        document.querySelectorAll('form, .form-container, .form-box, .form-section, .container, .container-fluid, .content-container').forEach(el => {
          el.classList.add('dark-mode');
        });
      }
    });
  })();
</script>
