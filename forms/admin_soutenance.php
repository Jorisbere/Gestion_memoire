<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'DM') {
    header("Location: ../login.php");
    exit();
}

$dm_id = $_SESSION['user_id'];
$dm_query = $conn->prepare("SELECT username FROM users WHERE id = ?");
$dm_query->bind_param("i", $dm_id);
$dm_query->execute();
$dm_result = $dm_query->get_result();
$dm_data = $dm_result->fetch_assoc();
$dm_nom = $dm_data['username'];

$filtre = $_GET['filtre'] ?? '';
$sql = "SELECT ds.*, u.username AS auteur FROM demandes_soutenance ds 
        JOIN users u ON ds.user_id = u.id 
        WHERE ds.encadrant = ?";

if (in_array($filtre, ['valide', 'rejete', 'en_attente'])) {
    $sql .= " AND ds.etat_validation = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $dm_nom, $filtre);
} else {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $dm_nom);
}

$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Gestion des demandes de soutenance</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background: #f0f4f8;
      padding: 40px;
      color: #333;
    }

    h2 {
      text-align: center;
      color: #0078D7;
      margin-bottom: 30px;
    }

    .notification {
      background: #d4edda;
      color: #155724;
      padding: 12px;
      border-radius: 8px;
      margin-bottom: 20px;
      text-align: center;
      font-weight: bold;
      display: none;
    }

    .notification.success {
      background-color: #d4edda;
      border-color: #28a745;
    }

    .notification.error {
      background-color: #f8d7da;
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
      background: #fff;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }

    th, td {
      padding: 12px 16px;
      border-bottom: 1px solid #eee;
      text-align: left;
    }

    th {
      background: #0078D7;
      color: #fff;
    }

    .btn {
      padding: 6px 12px;
      border: none;
      border-radius: 4px;
      font-weight: bold;
      cursor: pointer;
      margin-right: 5px;
    }

    .btn-success {
      background-color: #28a745;
      color: white;
    }

    .btn-danger {
      background-color: #dc3545;
      color: white;
    }

    .btn:hover {
      opacity: 0.8;
    }

    .badge {
      padding: 5px 10px;
      border-radius: 12px;
      font-size: 0.9em;
      color: white;
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

    .download-link {
      color: #0078D7;
      text-decoration: none;
      font-weight: bold;
    }
    .download-link:hover {
      text-decoration: underline;
    }

    .bg-success { background-color: #28a745; }
    .bg-danger { background-color: #dc3545; }
    .bg-warning { background-color: #ffc107; color: #212529; }

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
      background: #ffffff;
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
  </style>
</head>
<body>
    <div class="back-button">
    <a href="../accueil.php">← Retour au tableau de bord</a>
  </div>

  <!-- ✅ Overlay de chargement -->
  <div id="loading-overlay" class="loading-overlay" aria-hidden="true">
    <div class="loading-box">
      <div class="spinner" aria-label="Chargement"></div>
      <span id="loading-text">Traitement en cours…</span>
    </div>
  </div>

  <h2>🧑‍💼 Gestion des demandes de soutenance</h2>

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
        <th>Encadrant</th>
        <th>Fichier</th>
        <th>Date de demande</th>
        <th>État</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($row = $result->fetch_assoc()): ?>
        <tr id="demande-row-<?= $row['id'] ?>">
          <td><?= htmlspecialchars($row['titre']) ?></td>
          <td><?= htmlspecialchars($row['auteur']) ?></td>
          <td><?= htmlspecialchars($row['encadrant']) ?></td>
          <td>
            <?php if (!empty($row['fichier_path'])): ?>
            <a class="download-link" href="<?= htmlspecialchars($row['fichier_path']) ?>" target="_blank"><i class="fa-solid fa-download"></i> Télécharger</a>
            <?php else: ?>
            <em>Non transmis</em>
            <?php endif; ?>
        </td>

          <td><?= date('d/m/Y', strtotime($row['date_demande'])) ?></td>
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
                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                <button type="submit" name="action" value="valider" class="btn btn-success"><i class="fa-solid fa-check"></i> Valider</button>
              </form>
              <form class="rejet-form" data-id="<?= $row['id'] ?>" style="display:inline;">
                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                <button type="submit" name="action" value="rejeter" class="btn btn-danger"><i class="fa-solid fa-times"></i> Rejeter</button>
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

    // Validation AJAX
    document.querySelectorAll('.validation-form').forEach(form => {
      form.addEventListener('submit', function(e) {
        e.preventDefault();
        const id_demande = this.querySelector('input[name="id"]').value;
        showLoading('Validation en cours et envoi de l\'email…');
        
        const formData = new FormData();
        formData.append('id', id_demande);
        formData.append('action', 'valider');
        
        console.log('Envoi de la validation pour ID:', id_demande);
        
        fetch('valider_soutenance.php', {
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
            document.getElementById('etat-badge-' + id_demande).textContent = "Valide";
            document.getElementById('etat-badge-' + id_demande).className = "badge bg-success";
            // Suppression des boutons d'action
            document.querySelectorAll('tr#demande-row-' + id_demande + ' form').forEach(f => f.remove());
          } else {
            showNotification(data.message, "error");
          }
        })
        .catch(error => {
          console.error('Erreur lors de la validation:', error);
          showNotification("Erreur lors de la validation: " + error.message, "error");
        })
        .finally(() => hideLoading());
      });
    });

    // Rejet AJAX
    document.querySelectorAll('.rejet-form').forEach(form => {
      form.addEventListener('submit', function(e) {
        e.preventDefault();
        const id_demande = this.querySelector('input[name="id"]').value;
        showLoading('Rejet en cours et envoi de l\'email…');
        
        const formData = new FormData();
        formData.append('id', id_demande);
        formData.append('action', 'rejeter');
        
        console.log('Envoi du rejet pour ID:', id_demande);
        
        fetch('valider_soutenance.php', {
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
            document.getElementById('etat-badge-' + id_demande).textContent = "Rejete";
            document.getElementById('etat-badge-' + id_demande).className = "badge bg-danger";
            // Suppression des boutons d'action
            document.querySelectorAll('tr#demande-row-' + id_demande + ' form').forEach(f => f.remove());
          } else {
            showNotification(data.message, "error");
          }
        })
        .catch(error => {
          console.error('Erreur lors du rejet:', error);
          showNotification("Erreur lors du rejet: " + error.message, "error");
        })
        .finally(() => hideLoading());
      });
    });
  </script>
</body>
</html>
