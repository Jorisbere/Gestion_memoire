<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$annee = isset($_GET['annee']) ? trim($_GET['annee']) : '';

$query = "SELECT m.*, u.username AS auteur FROM memoires m JOIN users u ON m.user_id = u.id WHERE 1";
$query = "SELECT m.*, u.username AS auteur 
          FROM memoires m 
          JOIN users u ON m.user_id = u.id 
          WHERE m.etat_validation = 'valide'";


$params = [];
$types = '';

if ($search !== '') {
    $query .= " AND m.titre LIKE ?";
    $params[] = "%$search%";
    $types .= 's';
}

if ($annee !== '') {
    $query .= " AND m.annee = ?";
    $params[] = $annee;
    $types .= 's';
}

$query .= " ORDER BY m.date_depot DESC";

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
  <title>Mémoires Archivés</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background: #f0f4f8;
      padding: 40px;
      color: #333;
    }

    .back-button {
      text-align: center;
      margin-bottom: 20px;
    }

    .back-button a {
      text-decoration: none;
      color: #0078D7;
      font-weight: bold;
      font-size: 16px;
    }

    h2 {
      text-align: center;
      margin-bottom: 30px;
      font-size: 26px;
      color: #0078D7;
    }

    .search-bar {
      max-width: 700px;
      margin: auto;
      display: flex;
      gap: 10px;
      margin-bottom: 30px;
    }

    .search-bar input {
      flex: 1;
      padding: 10px;
      border-radius: 8px;
      border: 1px solid #ccc;
    }

    .search-bar button {
      padding: 10px 20px;
      background: #0078D7;
      color: #fff;
      border: none;
      border-radius: 8px;
      cursor: pointer;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
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

    tr:hover {
      background: #f1f9ff;
    }

    .download-link {
      color: #0078D7;
      text-decoration: none;
      font-weight: bold;
    }

    @media (max-width: 600px) {
      .search-bar {
        flex-direction: column;
      }

      th, td {
        font-size: 14px;
      }
    }
  </style>
</head>
<body>
  <div class="back-button">
    <a href="../dashboard.php">← Retour au tableau de bord</a>
  </div>

  <h2>📊 Mémoires Archivés</h2>

  <form method="GET" class="search-bar">
    <input type="text" name="search" placeholder="Rechercher par titre..." value="<?= htmlspecialchars($search) ?>">
    <input type="text" name="annee" placeholder="Année académique (ex: 2024-2025)" value="<?= htmlspecialchars($annee) ?>">
    <button type="submit">Filtrer</button>
  </form>

  <table>
    <thead>
      <tr>
        <th>Titre</th>
        <th>Auteur</th>
        <th>Année</th>
        <th>Date de dépôt</th>
        <th>Fichier</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars($row['titre']) ?></td>
            <td><?= htmlspecialchars($row['auteur']) ?></td>
            <td><?= htmlspecialchars($row['annee']) ?></td>
            <td><?= date('d/m/Y', strtotime($row['date_depot'])) ?></td>
            <td><a class="download-link" href="<?= htmlspecialchars($row['fichier_path']) ?>" target="_blank">📥 Télécharger</a></td>
          </tr>
        <?php endwhile; ?>
      <?php else: ?>
        <tr><td colspan="5">Aucun mémoire trouvé.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</body>
</html>
