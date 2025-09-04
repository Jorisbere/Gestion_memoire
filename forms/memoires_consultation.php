<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'DM') {
    header("Location: ../login.php");
    exit();
}

// Récupérer le nom du DM connecté
$dm_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
$stmt->bind_param("i", $dm_id);
$stmt->execute();
$result = $stmt->get_result();
$dm = $result->fetch_assoc();
$dm_nom = $dm['username'] ?? '';

$filtre = $_GET['filtre'] ?? '';

$sql = "SELECT m.*, u.username AS auteur 
        FROM memoires m 
        JOIN users u ON m.user_id = u.id 
        WHERE m.encadrant = ?";

$params = [$dm_nom];
$types = "s";

if (in_array($filtre, ['valide', 'rejete', 'en_attente'])) {
    $sql .= " AND m.etat_validation = ?";
    $params[] = $filtre;
    $types .= "s";
}

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Consultation des mémoires finaux</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background: linear-gradient(90deg, #49c4e6ff 0%, #e5edf7ff 100%);
      padding: 40px;
      color: #333;
    }
    @media (min-width: 901px) { .gm-main { padding-left: 280px; } }
    @media (max-width: 900px) { .gm-main { padding-top: 80px; } }

    h2 {
      text-align: center;
      color: #0078D7;
      margin-bottom: 30px;
    }

    .filter-form {
      text-align: center;
      margin-bottom: 20px;
    }

    select {
      padding: 8px;
      border-radius: 6px;
      border: 1px solid #ccc;
      font-size: 15px;
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

    .btn {
      padding: 6px 12px;
      border: none;
      border-radius: 4px;
      font-weight: bold;
      cursor: pointer;
      margin-right: 5px;
    }

    .btn-success { background-color: #28a745; color: #333; }
    .btn-danger { background-color: #dc3545; color: #333; }

    .badge { padding: 5px 10px; border-radius: 12px; font-size: 0.9em; color: #333; }
    .bg-success { background-color: #28a745; }
    .bg-danger { background-color: #dc3545; }
    .bg-warning { background-color: #ffc107; color: #333; }

    .download-link { color: #0078D7; text-decoration: none; font-weight: bold; }

    .back-button { text-align: center; margin-bottom: 20px; }
    .back-button a { text-decoration: none; color: #0078D7; font-weight: bold; }

    /* ✅ Notification */
    .notification { background-color: #e0f7e9; border: 1px solid #2ecc71; color: #333; padding: 10px 15px; margin: 15px 0; border-radius: 5px; position: relative; font-weight: bold; display: none; }
    .notification.success { background-color: #d4edda; border-color: #28a745; }
    .notification.error { background-color: #f8d7da; border-color: #dc3545; color: #721c24; }
    .notification button { background: none; border: none; font-size: 16px; position: absolute; top: 5px; right: 10px; cursor: pointer; }

    /* ✅ Overlay de chargement */
    .loading-overlay { position: fixed; top:0; left:0; width:100%; height:100%; background: rgba(255,255,255,0.8); display:none; align-items:center; justify-content:center; z-index:9999; }
    .loading-box { background:rgb(229, 232, 232); border:1px solid #ddd; border-radius:10px; padding:20px 30px; box-shadow:0 10px 30px rgba(0,0,0,0.15); display:flex; align-items:center; gap:12px; color:#333; font-weight:600; }
    .spinner { width:24px; height:24px; border:3px solid #e3e3e3; border-top-color:#0078D7; border-radius:50%; animation:spin .8s linear infinite; }
    @keyframes spin { to { transform: rotate(360deg); } }
  </style>
</head>
<body>
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="gm-main">
    <!-- <div class="back-button">
      <a href="../accueil.php">← Retour au tableau de bord</a>
    </div> -->

    <!-- ✅ Overlay de chargement -->
    <div id="loading-overlay" class="loading-overlay" aria-hidden="true">
      <div class="loading-box">
        <div class="spinner" aria-label="Chargement"></div>
        <span id="loading-text">Traitement en cours…</span>
      </div>
    </div>

    <h2>📊 Consultation des mémoires finaux</h2>

    <div id="notification" class="notification">
      <span id="notification-message"></span>
      <button onclick="document.getElementById('notification').style.display='none'">✖</button>
    </div>

    <form method="GET" class="filter-form">
      <label for="filtre">Filtrer par état :</label>
      <select name="filtre" id="filtre" onchange="this.form.submit()">
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
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
          <tr id="memoire-row-<?= $row['id'] ?>">
            <td><?= htmlspecialchars($row['titre']) ?></td>
            <td><?= htmlspecialchars($row['auteur']) ?></td>
            <td><?= date('d/m/Y', strtotime($row['date_depot'])) ?></td>
            <td>
              <?php if (!empty($row['fichier_path'])): ?>
                <a class="download-link" href="<?= htmlspecialchars($row['fichier_path']) ?>" target="_blank"><i class="fa-solid fa-download"></i> Télécharger</a>
              <?php else: ?>
                <em>Non transmis</em>
              <?php endif; ?>
            </td>
            <td>
              <span class="badge 
                <?= $row['etat_validation'] === 'valide' ? 'bg-success' : 
                   ($row['etat_validation'] === 'rejete' ? 'bg-danger' : 'bg-warning') ?>"
                id="etat-badge-<?= $row['id'] ?>">
                <?= ucfirst($row['etat_validation']) ?>
              </span>
            </td>
            <td>
              <?php if ($row['etat_validation'] === 'en_attente'): ?>
                <form class="validation-form" data-id="<?= $row['id'] ?>" style="display:inline;">
                  <input type="hidden" name="id_memoire" value="<?= $row['id'] ?>">
                  <button type="submit" name="action" value="valider" class="btn btn-success"><i class="fa-solid fa-check"></i> Valider</button>
                </form>
                <form class="rejet-form" data-id="<?= $row['id'] ?>" style="display:inline;">
                  <input type="hidden" name="id_memoire" value="<?= $row['id'] ?>">
                  <button type="submit" name="action" value="rejeter" class="btn btn-danger"><i class="fa-solid fa-times"></i> Rejeter</button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>

    <script>
      // ✅ Loader
      const loadingOverlay = document.getElementById('loading-overlay');
      const loadingText = document.getElementById('loading-text');
      function showLoading(text = 'Traitement en cours…') { if (loadingText) loadingText.textContent = text; if (loadingOverlay) loadingOverlay.style.display = 'flex'; }
      function hideLoading() { if (loadingOverlay) loadingOverlay.style.display = 'none'; }

      // ✅ Notification
      function showNotification(message, type = 'success') {
        const notif = document.getElementById('notification');
        const msg = document.getElementById('notification-message');
        notif.className = 'notification ' + type;
        msg.textContent = message;
        notif.style.display = 'block';
      }

      // Validation AJAX
      document.querySelectorAll('.validation-form').forEach(form => {
        form.addEventListener('submit', function(e) {
          e.preventDefault();
          const id_memoire = this.querySelector('input[name="id_memoire"]').value;
          showLoading("Validation du mémoire et envoi de l'email…");
          const formData = new FormData();
          formData.append('id_memoire', id_memoire);
          formData.append('action', 'valider');
          fetch('valider_memoire.php', { method: 'POST', body: formData })
          .then(r => r.json())
          .then(data => {
            if (data.success) {
              showNotification(data.message, 'success');
              document.getElementById('etat-badge-' + id_memoire).textContent = 'Valide';
              document.getElementById('etat-badge-' + id_memoire).className = 'badge bg-success';
              document.querySelectorAll('tr#memoire-row-' + id_memoire + ' form').forEach(f => f.remove());
            } else { showNotification(data.message, 'error'); }
          })
          .catch(() => showNotification('Erreur lors de la validation.', 'error'))
          .finally(() => hideLoading());
        });
      });

      // Rejet AJAX
      document.querySelectorAll('.rejet-form').forEach(form => {
        form.addEventListener('submit', function(e) {
          e.preventDefault();
          const id_memoire = this.querySelector('input[name="id_memoire"]').value;
          showLoading("Rejet du mémoire et envoi de l'email…");
          const formData = new FormData();
          formData.append('id_memoire', id_memoire);
          formData.append('action', 'rejeter');
          fetch('valider_memoire.php', { method: 'POST', body: formData })
          .then(r => r.json())
          .then(data => {
            if (data.success) {
              showNotification(data.message, 'success');
              document.getElementById('etat-badge-' + id_memoire).textContent = 'Rejete';
              document.getElementById('etat-badge-' + id_memoire).className = 'badge bg-danger';
              document.querySelectorAll('tr#memoire-row-' + id_memoire + ' form').forEach(f => f.remove());
            } else { showNotification(data.message, 'error'); }
          })
          .catch(() => showNotification('Erreur lors du rejet.', 'error'))
          .finally(() => hideLoading());
        });
      });
    </script>
  </div>
</body>
</html>
