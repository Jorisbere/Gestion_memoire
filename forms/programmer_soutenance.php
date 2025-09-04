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

$search = $_GET['search'] ?? '';
$programmation = $_GET['programmation'] ?? '';
$annee = $_GET['annee'] ?? '';

$query = "
  SELECT ds.*, u.username AS auteur 
  FROM demandes_soutenance ds 
  JOIN users u ON ds.user_id = u.id 
  WHERE ds.etat_validation = 'valide'
";

$params = [];
$types = "";

// 🔍 Filtre texte
if (!empty($search)) {
  $query .= " AND (ds.titre LIKE ? OR ds.encadrant LIKE ? OR u.username LIKE ?)";
  $likeSearch = "%$search%";
  $params[] = $likeSearch;
  $params[] = $likeSearch;
  $params[] = $likeSearch;
  $types .= "sss";
}

// 📌 Filtre programmation
if ($programmation === 'programmee') {
  $query .= " AND ds.date_soutenance IS NOT NULL";
} elseif ($programmation === 'non_programmee') {
  $query .= " AND ds.date_soutenance IS NULL";
}

// 📅 Filtre année
if (!empty($annee)) {
  $query .= " AND YEAR(ds.date_soutenance) = ?";
  $params[] = $annee;
  $types .= "i";
}

$stmt = $conn->prepare($query);
if (!empty($params)) {
  $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Programmer une soutenance</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background: linear-gradient(90deg, #49c4e6ff 0%, #e5edf7ff 100%);
      padding: 40px;
      color: #333;
    }
    /* Réservation d'espace pour le sidebar type dashboard */
    @media (min-width: 901px) { .main { padding-left: 280px; } }
    @media (max-width: 900px) { .main { padding-top: 80px; } }

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

  .dark-mode { background: #0f1115 !important; color: #e7e7e7 !important; }
  .dark-mode .sidebar { background: rgba(255,255,255,0.04); }
  .dark-mode .nav a { background: rgba(255,255,255,0.06); }
  .dark-mode .nav a:hover { background: rgba(255,255,255,0.12); }
  .dark-mode .dropdown { background: rgba(30,32,38,0.98); }

    h2 {
      text-align: center;
      color: #0078D7;
      margin-bottom: 30px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      background:rgb(229, 232, 232);
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }

    th, td {
      padding: 12px 16px;
      border-bottom: 1px solid #eee;
      text-align: left;
    }

    th {
      background: #0078D7;
      color: #333;
    }

    form {
      display: flex;
      flex-direction: column;
      gap: 6px;
    }

    input, select {
      padding: 8px;
      border-radius: 6px;
      border: 1px solid #ccc;
      font-size: 14px;
    }

    .btn {
      padding: 8px 16px;
      background-color: #28a745;
      color: #333;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-weight: bold;
      text-align: center;
      margin: 0 5px;
      margin-top: 5px;
    }

    .btn:hover {
      opacity: 0.8;
    }

    .btn-warning {
      background-color: #ffc107;
      color: #212529;
    }

    /* Alignement des boutons succès/danger si utilisés */
    .btn-success { background-color: #28a745; color: #fff; margin: 0 5px; margin-top: 5px; }
    .btn-danger { background-color: #dc3545; color: #fff; margin: 0 5px; margin-top: 5px; }
    .btn-success:hover { background-color: #218838; }
    .btn-danger:hover { background-color: #c82333; }

    .notification {
      background-color:rgb(229, 232, 232);
      color: #155724;
      padding: 12px;
      border-radius: 8px;
      margin-bottom: 20px;
      text-align: center;
      font-weight: bold;
      display: none;
    }

    .notification.success {
      background-color:rgb(229, 232, 232);
      border-color: #28a745;
    }

    .notification.error {
      background-color:rgb(229, 232, 232);
      border-color: #dc3545;
      color: #721c24;
    }

    .notification button {
      background: none;
      border: none;
      font-size: 16px;
      position: absolute;
      top: 5px;
      right: 10px;
      cursor: pointer;
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

    input[type="text"]::placeholder {
      color: #888;
    }

    /* ✅ Overlay de chargement */
    .loading-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(255, 255, 255, 0.8);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 9999;
    }
    .loading-box {
      background:rgb(229, 232, 232);
      border: 1px solid #ddd;
      border-radius: 10px;
      padding: 20px 30px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.15);
      display: flex;
      align-items: center;
      gap: 12px;
      color: #333;
      font-weight: 600;
    }
    .spinner {
      width: 24px;
      height: 24px;
      border: 3px solid #e3e3e3;
      border-top-color: #0078D7;
      border-radius: 50%;
      animation: spin 0.8s linear infinite;
    }
    @keyframes spin {
      to { transform: rotate(360deg); }
    }

    /* ✅ Styles pour la barre de recherche et filtres */
    .filter-form {
      text-align: center;
      margin-bottom: 30px;
      display: flex;
      gap: 20px;
      flex-wrap: wrap;
      justify-content: center;
      align-items: center;
      padding: 25px;
      background:rgb(229, 232, 232);
      border-radius: 12px;
      box-shadow: 0 4px 16px rgba(0,0,0,0.1);
      border: 1px solid #e1e5e9;
    }

    .filter-form > div {
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .filter-form label {
      font-weight: 600;
      color: #555;
      font-size: 16px;
      min-width: 24px;
      text-align: center;
    }

    .filter-form input[type="text"] {
      min-width: 320px;
      padding: 14px 18px;
      border-radius: 10px;
      border: 2px solid #e1e5e9;
      font-size: 14px;
      transition: all 0.3s ease;
      background-color: #f8f9fa;
    }

    .filter-form input[type="text"]:focus {
      outline: none;
      border-color: #0078D7;
      box-shadow: 0 0 0 4px rgba(0, 120, 215, 0.15);
      background-color:rgb(229, 232, 232);
    }

    .filter-form input[type="text"]::placeholder {
      color: #888;
      font-style: italic;
    }

    .filter-form select {
      min-width: 200px;
      padding: 14px 18px;
      border-radius: 10px;
      border: 2px solid #e1e5e9;
      font-size: 14px;
      background-color: #f8f9fa;
      cursor: pointer;
      transition: all 0.3s ease;
      font-weight: 500;
    }

    .filter-form select:focus {
      outline: none;
      border-color: #0078D7;
      box-shadow: 0 0 0 4px rgba(0, 120, 215, 0.15);
      background-color:rgb(229, 232, 232);
    }

    .filter-form button {
      padding: 14px 28px;
      background: linear-gradient(135deg, #0078D7, #106ebe);
      color: #333;
      border: none;
      border-radius: 10px;
      font-size: 15px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      min-width: 140px;
      box-shadow: 0 2px 8px rgba(0, 120, 215, 0.3);
    }

    .filter-form button:hover {
      background: linear-gradient(135deg, #106ebe, #005a9e);
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(0, 120, 215, 0.4);
    }

    .filter-form button:active {
      transform: translateY(0);
    }

    /* Responsive design */
    @media (max-width: 1200px) {
      .filter-form {
        gap: 15px;
        padding: 20px;
      }
      
      .filter-form input[type="text"] {
        min-width: 280px;
      }
      
      .filter-form select {
        min-width: 180px;
      }
    }

    @media (max-width: 900px) {
      .filter-form {
        flex-direction: column;
        gap: 15px;
        align-items: stretch;
      }
      
      .filter-form > div {
        justify-content: center;
      }
      
      .filter-form input[type="text"],
      .filter-form select {
        min-width: 100%;
        width: 100%;
        max-width: 400px;
      }
      
      .filter-form button {
        width: 100%;
        max-width: 200px;
        margin: 0 auto;
      }
    }

    @media (max-width: 600px) {
      .filter-form {
        padding: 20px 15px;
        margin: 20px 10px;
      }
      
      .filter-form input[type="text"],
      .filter-form select {
        max-width: 100%;
        padding: 12px 16px;
      }
      
      .filter-form button {
        max-width: 100%;
        padding: 12px 20px;
      }
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
  <main class="main">
    <div class="back-button">
    <a href="../dashboard.php">← Retour au tableau de bord</a>
  </div>

  <!-- ✅ Overlay de chargement -->
  <div id="loading-overlay" class="loading-overlay" aria-hidden="true">
    <div class="loading-box">
      <div class="spinner" aria-label="Chargement"></div>
      <span id="loading-text">Traitement en cours…</span>
    </div>
  </div>

  <h2><i class="fa-solid fa-calendar"></i> Programmer une soutenance</h2>

  <div id="notification" class="notification">
    <span id="notification-message"></span>
    <button onclick="document.getElementById('notification').style.display='none'">✖</button>
  </div>

  <form method="get" class="filter-form">
    <div style="display: flex; align-items: center; gap: 8px;">
      <label for="search">🔍</label>
      <input type="text" name="search" id="search" placeholder="Rechercher par titre, auteur ou encadrant" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
    </div>

    <div style="display: flex; align-items: center; gap: 8px;">
      <label for="programmation">📌</label>
      <select name="programmation" id="programmation">
        <option value="">Tous</option>
        <option value="programmee" <?= ($_GET['programmation'] ?? '') === 'programmee' ? 'selected' : '' ?>>✅ Programmées</option>
        <option value="non_programmee" <?= ($_GET['programmation'] ?? '') === 'non_programmee' ? 'selected' : '' ?>>⏳ Non programmées</option>
      </select>
    </div>

    <div style="display: flex; align-items: center; gap: 8px;">
      <label for="annee">📅</label>
      <select name="annee" id="annee">
        <option value="">Toutes les années</option>
        <?php
          $currentYear = date('Y');
          for ($y = $currentYear; $y >= $currentYear - 5; $y--) {
            $selected = ($_GET['annee'] ?? '') == $y ? 'selected' : '';
            echo "<option value=\"$y\" $selected>$y</option>";
          }
        ?>
      </select>
    </div>

    <button type="submit">Filtrer</button>
  </form>

  <table>
    <thead>
      <tr>
  <th>Titre</th>
  <th>Auteur</th>
  <th>Encadrant</th>
  <th>Session souhaitée</th>
  <th>Programmation</th>
  <th>Actions</th>
</tr>

    </thead>
    <tbody>
      <?php while ($row = $result->fetch_assoc()): ?>
  <tr id="demande-row-<?= $row['id'] ?>">
    <!-- Titre -->
    <td><?= htmlspecialchars($row['titre'] ?? '') ?></td>

    <!-- Auteur -->
    <td><?= htmlspecialchars($row['auteur'] ?? '') ?></td>

    <!-- Encadrant -->
    <td><?= htmlspecialchars($row['encadrant'] ?? '') ?></td>
    <!-- Session souhaitée -->
    <td><?= htmlspecialchars($row['session_souhaitee'] ?? '') ?></td>

    <!-- Programmation -->
    <td id="programmation-<?= $row['id'] ?>">
      <?php if (!empty($row['date_soutenance']) && !empty($row['salle'])): ?>
        <div style="line-height: 1.5;">
          <i class="fa-solid fa-calendar"></i> <strong><?= date('Y-m-d', strtotime($row['date_soutenance'])) ?></strong><br>
          <i class="fa-solid fa-clock"></i> <?= date('H:i', strtotime($row['date_soutenance'])) ?><br>
          <i class="fa-solid fa-school"></i> Salle <?= htmlspecialchars($row['salle']) ?>
        </div>
      <?php else: ?>
        <span style="color:#888;"><i class="fa-solid fa-hourglass-half"></i> Non programmée</span>
      <?php endif; ?>
    </td>

    <!-- Actions -->
    <td id="actions-<?= $row['id'] ?>">
      <?php if (!empty($row['date_soutenance'])): ?>
        <div style="display: flex; flex-direction: column; gap: 6px;">
          <a href="modifier_soutenance.php?id=<?= $row['id'] ?>" class="btn btn-warning"><i class="fa-solid fa-pencil"></i> Modifier</a>
        </div>
      <?php else: ?>
        <form class="programmation-form" data-id="<?= $row['id'] ?>" style="display: flex; flex-direction: column; gap: 6px;">
          <input type="hidden" name="id" value="<?= $row['id'] ?>">
          <input type="date" name="date" required>
          <input type="time" name="heure" required>
          <input type="text" name="salle" placeholder="Salle" required>
          <button type="submit" class="btn"><i class="fa-solid fa-check"></i> Enregistrer</button>
        </form>
      <?php endif; ?>
    </td>
  </tr>
<?php endwhile; ?>

    </tbody>
  </table>

  <script>
    // ✅ Gestion du loader
    const loadingOverlay = document.getElementById('loading-overlay');
    const loadingText = document.getElementById('loading-text');
    function showLoading(text = 'Traitement en cours…') {
      if (loadingText) loadingText.textContent = text;
      if (loadingOverlay) loadingOverlay.style.display = 'flex';
    }
    function hideLoading() {
      if (loadingOverlay) loadingOverlay.style.display = 'none';
    }

    // Affichage notification dynamique
    function showNotification(message, type = "success") {
      const notif = document.getElementById('notification');
      const msg = document.getElementById('notification-message');
      notif.className = 'notification ' + type;
      msg.textContent = message;
      notif.style.display = 'block';
    }

    // Programmation AJAX
    document.querySelectorAll('.programmation-form').forEach(form => {
      form.addEventListener('submit', function(e) {
        e.preventDefault();
        const id_demande = this.querySelector('input[name="id"]').value;
        const date = this.querySelector('input[name="date"]').value;
        const heure = this.querySelector('input[name="heure"]').value;
        const salle = this.querySelector('input[name="salle"]').value;

        if (!date || !heure || !salle) {
          showNotification("Veuillez remplir tous les champs.", "error");
          return;
        }

        showLoading('Programmation de la soutenance en cours et envoi de l\'email…');
        
        const formData = new FormData();
        formData.append('id', id_demande);
        formData.append('date', date);
        formData.append('heure', heure);
        formData.append('salle', salle);
        
        console.log('Envoi de la programmation pour ID:', id_demande);
        
        fetch('enregistrer_soutenance.php', {
          method: 'POST',
          body: formData
        })
        .then(response => {
          console.log('Réponse reçue:', response);
          if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
          }
          return response.json();
        })
        .then(data => {
          console.log('Données reçues:', data);
          if (data.success) {
            showNotification(data.message, "success");
            
            // Mise à jour de la ligne du tableau
            const row = document.getElementById('demande-row-' + id_demande);
            const programmationCell = document.getElementById('programmation-' + id_demande);
            const actionsCell = document.getElementById('actions-' + id_demande);
            
            // Mettre à jour la cellule de programmation
            const datetime = new Date(data.date_soutenance);
            const dateStr = datetime.toISOString().split('T')[0];
            const timeStr = datetime.toTimeString().substring(0, 5);
            
            programmationCell.innerHTML = `
              <div style="line-height: 1.5;">
                <i class="fa-solid fa-calendar"></i> <strong>${dateStr}</strong><br>
                <i class="fa-solid fa-clock"></i> ${timeStr}<br>
                <i class="fa-solid fa-school"></i> Salle ${data.salle}
              </div>
            `;
            
            // Remplacer le formulaire par le bouton de modification
            actionsCell.innerHTML = `
              <div style="display: flex; flex-direction: column; gap: 6px;">
                <a href="modifier_soutenance.php?id=${id_demande}" class="btn btn-warning">
                  <i class="fa-solid fa-pencil"></i> Modifier
                </a>
              </div>
            `;
            
          } else {
            showNotification(data.message, "error");
          }
        })
        .catch(error => {
          console.error('Erreur lors de la programmation:', error);
          showNotification("Erreur lors de la programmation: " + error.message, "error");
        })
        .finally(() => hideLoading());
      });
    });
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
