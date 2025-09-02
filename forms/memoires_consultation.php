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
      background: #f4f6f8;
      padding: 40px;
      color: #333;
    }

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

    .badge {
      padding: 5px 10px;
      border-radius: 12px;
      font-size: 0.9em;
      color: white;
    }

    .bg-success { background-color: #28a745; }
    .bg-danger { background-color: #dc3545; }
    .bg-warning { background-color: #ffc107; color: #212529; }

    .download-link {
      color: #0078D7;
      text-decoration: none;
      font-weight: bold;
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
  <div class="back-button">
    <a href="../accueil.php">← Retour au tableau de bord</a>
  </div>

  <h2>📊 Consultation des mémoires finaux</h2>

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
        <tr>
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
                 ($row['etat_validation'] === 'rejete' ? 'bg-danger' : 'bg-warning') ?>">
              <?= ucfirst($row['etat_validation']) ?>
            </span>
          </td>
          <td>
            <?php if ($row['etat_validation'] === 'en_attente'): ?>
              <form method="post" action="valider_memoire.php" style="display:inline;">
                <input type="hidden" name="id_memoire" value="<?= $row['id'] ?>">
                <button type="submit" name="action" value="valider" class="btn btn-success"><i class="fa-solid fa-check"></i> Valider</button>
              </form>
              <form method="post" action="valider_memoire.php" style="display:inline;">
                <input type="hidden" name="id_memoire" value="<?= $row['id'] ?>">
                <button type="submit" name="action" value="rejeter" class="btn btn-danger"><i class="fa-solid fa-times"></i> Rejeter</button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</body>
</html>
