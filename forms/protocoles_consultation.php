<?php
// Ceci est le code protocoles_consultation modifié pour validation/rejet AJAX sans redirection ni notification session

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Récupérer infos utilisateur pour le sidebar (même logique que dashboard)
$user_id = (int) $_SESSION['user_id'];
$__stmt = $conn->prepare("SELECT username, avatar FROM users WHERE id = ?");
$__stmt->bind_param("i", $user_id);
$__stmt->execute();
$__res = $__stmt->get_result();
$__user = $__res->fetch_assoc();
$__stmt->close();
$__username = htmlspecialchars($__user['username'] ?? 'Utilisateur');
$__avatar_raw = $__user['avatar'] ?? '';
// Normaliser l'URL de l'avatar
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

$search = $_GET['search'] ?? '';
$annee = $_GET['annee'] ?? '';
$filtre = $_GET['filtre_validation'] ?? '';

$sql = "
  SELECT p.*, u.username AS auteur, dm.username AS dm_nom
  FROM protocoles p
  JOIN users u ON p.user_id = u.id
  LEFT JOIN users dm ON p.dm_id = dm.id
  WHERE 1=1
";
$params = [];
$types = "";

if (!empty($search)) {
    $sql .= " AND p.titre LIKE ?";
    $params[] = "%$search%";
    $types .= "s";
}

if (!empty($annee)) {
    $sql .= " AND YEAR(p.date_depot) = ?";
    $params[] = $annee;
    $types .= "i";
}

if (in_array($filtre, ['valide', 'rejete', 'en_attente'])) {
    $sql .= " AND p.etat_validation = ?";
    $params[] = $filtre;
    $types .= "s";
}

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$annee_actuelle = date('Y');
$dm_query = "
  SELECT u.id, u.username
  FROM users u
  WHERE u.role = 'DM'
  AND (
    SELECT COUNT(*) FROM protocoles p
    WHERE p.dm_id = u.id AND YEAR(p.date_depot) = ?
    AND p.etat_validation = 'valide'
  ) < 5
";
$dm_stmt = $conn->prepare($dm_query);
$dm_stmt->bind_param("s", $annee_actuelle);
$dm_stmt->execute();
$dm_result = $dm_stmt->get_result();

$dms_disponibles = [];
while ($dm = $dm_result->fetch_assoc()) {
    $dms_disponibles[] = $dm;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Protocoles déposés</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background: linear-gradient(90deg, #49c4e6ff 0%, #e5edf7ff 100%);
      padding: 40px;
      color: #333;
    }

    /* Sidebar du dashboard (adapté) */
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
    .avatar-initiales { width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg, #00c6ff, #151617ff); display:flex; align-items:center; justify-content:center; font-weight:700; font-size:14px; color:#fff; }
    .username { font-weight: 600; max-width: 140px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .dropdown { position: absolute; top: 46px; left: 0; background: rgba(0,0,0,0.85); backdrop-filter: blur(4px); border-radius: 10px; padding: 8px 0; display: none; flex-direction: column; min-width: 180px; z-index: 920; box-shadow: 0 8px 20px rgba(0,0,0,0.25); opacity: 0; transform: translateY(-6px); transition: opacity .18s ease, transform .18s ease; }
    .dropdown.open { display: flex; opacity: 1; transform: translateY(0); }
    .dropdown a { padding: 10px 14px; color: #fff; text-decoration: none; display: flex; align-items: center; gap: 10px; transition: background .25s; }
    .dropdown a:hover { background: rgba(255,255,255,0.1); }
    .nav { display: flex; flex-direction: column; gap: 8px; margin-top: 8px; }
    .nav a { text-decoration: none; color: inherit; padding: 10px 12px; display: block; border-radius: 10px; background: rgba(255,255,255,0.08); }
    .nav a:hover { background: rgba(255,255,255,0.18); }

    /* Bouton dark mode */
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

    /* Styles des boutons d'action */
    .btn { padding: 6px 12px; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; margin-right: 5px; }
    .btn-success { background-color: #28a745; color: #fff; margin: 0 5px;margin-top: 5px;}
    .btn-danger { background-color: #dc3545; color: #fff; margin: 0 5px;margin-top: 5px;}
    .btn-success:hover { background-color: #218838; }
    .btn-danger:hover { background-color: #c82333; }

    /* Espace contenu pour éviter le chevauchement */
    @media (min-width: 901px) { .main { padding-left: 280px; } }
    @media (max-width: 900px) { .main { padding-top: 90px; } }

    h2 { text-align: center; color: #0078D7; margin-bottom: 30px; }

    .search-form { max-width: 700px; margin: auto; display: flex; gap: 10px; margin-bottom: 30px; }

    .search-form input { flex: 1; padding: 10px; border-radius: 8px; border: 1px solid #ccc; }
    .search-form button { padding: 10px 20px; background: #0078D7; color: #fff; border: none; border-radius: 8px; cursor: pointer; }

    table { width: 100%; border-collapse: collapse; background:rgb(229, 232, 232); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
    th, td { padding: 12px 16px; border-bottom: 1px solid #eee; text-align: left; }
    th { background: #0078D7; color: #fff; }
    tr:hover { background: #f1f9ff; }


    .download-link { color: #0078D7; text-decoration: none; font-weight: bold; }
    .back-button { text-align: center; margin-bottom: 20px; }
    .back-button a { text-decoration: none; color: #0078D7; font-weight: bold; }

    select { padding: 6px; border-radius: 6px; border: 1px solid #ccc; font-size: 14px; margin-right: 8px; }

    /* ✅ Notification */
    .notification { background-color: #e0f7e9; border: 1px solid #2ecc71; color: #2c3e50; padding: 10px 15px; margin: 15px 0; border-radius: 5px; position: relative; font-weight: bold; display: none; }
    .notification.success { background-color: #d4edda; border-color: #28a745; }
    .notification.error { background-color: #f8d7da; border-color: #dc3545; color: #721c24; }
    .notification button { background: none; border: none; font-size: 16px; position: absolute; top: 5px; right: 10px; cursor: pointer; }

    /* ✅ Overlay de chargement */
    .loading-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255, 255, 255, 0.8); display: none; align-items: center; justify-content: center; z-index: 9999; }
    .loading-box { background: #ffffff; border: 1px solid #ddd; border-radius: 10px; padding: 20px 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.15); display: flex; align-items: center; gap: 12px; color: #333; font-weight: 600; }
    .spinner { width: 24px; height: 24px; border: 3px solid #e3e3e3; border-top-color: #0078D7; border-radius: 50%; animation: spin 0.8s linear infinite; }
    @keyframes spin { to { transform: rotate(360deg); } }

  </style>
</head>
<body>
  <!-- Sidebar type dashboard -->
  <aside class="sidebar">
    <div class="user-menu" tabindex="0" aria-expanded="false">
      <?php if (empty($__avatar)): ?>
        <?php
          $__initiales = '';
          $__mots = explode(' ', trim($__username));
          foreach ($__mots as $__m) { $__initiales .= strtoupper(mb_substr($__m, 0, 1)); }
        ?>
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

  <main class="main">
    <!-- <div class="back-button">
      <a href="../dashboard.php">← Retour au tableau de bord</a>
    </div> -->

    <!-- ✅ Overlay de chargement -->
    <div id="loading-overlay" class="loading-overlay" aria-hidden="true">
      <div class="loading-box">
        <div class="spinner" aria-label="Chargement"></div>
        <span id="loading-text">Traitement en cours…</span>
      </div>
    </div>

    <div id="notification" class="notification">
      <span id="notification-message"></span>
      <button onclick="document.getElementById('notification').style.display='none'">✖</button>
    </div>

    <h2><i class="fa-solid fa-file"></i> Protocoles déposés</h2>

    <form method="GET" class="search-form" onsubmit="return validateSearchForm()">
      <input type="text" name="search" id="search" placeholder="Rechercher par titre..." value="<?= htmlspecialchars($search) ?>">
      <input type="text" name="annee" id="annee" placeholder="Année (ex: 2025)" value="<?= htmlspecialchars($annee) ?>">
      <button type="submit">Filtrer</button>
      <select name="filtre_validation">
        <option value="">Tous</option>
        <option value="en_attente" <?= $filtre === 'en_attente' ? 'selected' : '' ?>>En attente</option>
        <option value="valide" <?= $filtre === 'valide' ? 'selected' : '' ?>>Validés</option>
        <option value="rejete" <?= $filtre === 'rejete' ? 'selected' : '' ?>>Rejetés</option>
      </select>
    </form>

    <table>
      <thead>
        <tr>
          <th>Titre</th>
          <th>Auteur</th>
          <th>Date de dépôt</th>
          <th>Fichier</th>
          <th>État</th>
          <th>DM attribué</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($result->num_rows > 0): ?>
          <?php while ($row = $result->fetch_assoc()): ?>
            <tr id="protocole-row-<?= $row['id'] ?>">
              <td><?= htmlspecialchars($row['titre']) ?></td>
              <td><?= htmlspecialchars($row['auteur'] ?? 'Inconnu') ?></td>
              <td><?= date('d/m/Y', strtotime($row['date_depot'])) ?></td>
              <td><a class="download-link" href="<?= htmlspecialchars($row['fichier_path']) ?>" target="_blank"><i class="fa-solid fa-download"></i> Télécharger</a></td>
              <td>
                <span class="badge 
                  <?= $row['etat_validation'] === 'valide' ? 'bg-success' : 
                     ($row['etat_validation'] === 'rejete' ? 'bg-danger' : 'bg-warning') ?>"
                  id="etat-badge-<?= $row['id'] ?>">
                  <?= ucfirst($row['etat_validation']) ?>
                </span>
              </td>
              <td id="dm-nom-<?= $row['id'] ?>">
                <?= !empty($row['dm_nom']) ? htmlspecialchars($row['dm_nom']) : '<em>Non attribué</em>' ?>
              </td>
              <td>
                <?php if ($row['etat_validation'] === 'en_attente'): ?>
                  <form class="validation-form" data-id="<?= $row['id'] ?>" style="display:inline;">
                    <input type="hidden" name="id_protocole" value="<?= $row['id'] ?>">
                    <select name="dm_id" required>
                      <option value="">Attribuer un DM</option>
                      <?php foreach ($dms_disponibles as $dm): ?>
                        <option value="<?= $dm['id'] ?>"><?= htmlspecialchars($dm['username']) ?></option>
                      <?php endforeach; ?>
                    </select>
                    <button type="submit" name="action" value="valider" class="btn btn-success"><i class="fa-solid fa-check"></i> Valider</button>
                  </form>
                  <form class="rejet-form" data-id="<?= $row['id'] ?>" style="display:inline;">
                    <input type="hidden" name="id_protocole" value="<?= $row['id'] ?>">
                    <button type="submit" name="action" value="rejeter" class="btn btn-danger"><i class="fa-solid fa-xmark"></i> Rejeter</button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="7">Aucun protocole trouvé.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>

    <script>

      function validateSearchForm() {
        const annee = document.getElementById('annee').value.trim();
        if (annee && !/^[0-9]{4}$/.test(annee)) { alert("L'année doit être un nombre à 4 chiffres (ex: 2025)."); return false; }
        return true;
      }

      // ✅ Gestion du loader
      const loadingOverlay = document.getElementById('loading-overlay');
      const loadingText = document.getElementById('loading-text');
      function showLoading(text = 'Traitement en cours…') { if (loadingText) loadingText.textContent = text; if (loadingOverlay) loadingOverlay.style.display = 'flex'; }
      function hideLoading() { if (loadingOverlay) loadingOverlay.style.display = 'none'; }

      // Validation AJAX
      document.querySelectorAll('.validation-form').forEach(form => {
        form.addEventListener('submit', function(e) {
          e.preventDefault();
          const id_protocole = this.querySelector('input[name="id_protocole"]').value;
          const dm_id = this.querySelector('select[name="dm_id"]').value;
          if (!dm_id) { showNotification("Veuillez sélectionner un DM.", "error"); return; }
          showLoading('Validation en cours et envoi de l\'email…');
          const formData = new FormData();
          formData.append('id_protocole', id_protocole);
          formData.append('dm_id', dm_id);
          formData.append('action', 'valider');
          fetch('valider_protocole.php', { method: 'POST', body: formData })
          .then(r => r.json())
          .then(data => {
            if (data.success) {
              showNotification(data.message, "success");
              document.getElementById('etat-badge-' + id_protocole).textContent = "Valide";
              document.getElementById('etat-badge-' + id_protocole).className = "badge bg-success";
              document.getElementById('dm-nom-' + id_protocole).textContent = data.nom_dm || "";
              document.querySelectorAll('tr#protocole-row-' + id_protocole + ' form').forEach(f => f.remove());
            } else { showNotification(data.message, "error"); }
          })
          .catch(() => showNotification("Erreur lors de la validation.", "error"))
          .finally(() => hideLoading());
        });
      });

      // Rejet AJAX
      document.querySelectorAll('.rejet-form').forEach(form => {
        form.addEventListener('submit', function(e) {
          e.preventDefault();
          const id_protocole = this.querySelector('input[name="id_protocole"]').value;
          showLoading('Rejet en cours et envoi de l\'email…');
          const formData = new FormData();
          formData.append('id_protocole', id_protocole);
          formData.append('action', 'rejeter');
          fetch('valider_protocole.php', { method: 'POST', body: formData })
          .then(r => r.json())
          .then(data => {
            if (data.success) {
              showNotification(data.message, "success");
              document.getElementById('etat-badge-' + id_protocole).textContent = "Rejete";
              document.getElementById('etat-badge-' + id_protocole).className = "badge bg-danger";
              document.querySelectorAll('tr#protocole-row-' + id_protocole + ' form').forEach(f => f.remove());
            } else { showNotification(data.message, "error"); }
          })
          .catch(() => showNotification("Erreur lors du rejet.", "error"))
          .finally(() => hideLoading());
        });
      });

      // Notification
      function showNotification(message, type = "success") {
        const notif = document.getElementById('notification');
        const msg = document.getElementById('notification-message');
        notif.className = 'notification ' + type;
        msg.textContent = message;
        notif.style.display = 'block';
      }
    </script>
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
  </main>
</body>
</html>
