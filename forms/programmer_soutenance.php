<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'administrateur') {
    header("Location: ../login.php");
    exit();
}

$stmt = $conn->prepare("SELECT ds.*, u.username AS auteur FROM demandes_soutenance ds JOIN users u ON ds.user_id = u.id WHERE ds.etat_validation = 'valide' AND ds.date_soutenance IS NULL");
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Programmer une soutenance</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background: #f4f6f9;
      padding: 40px;
      color: #333;
    }

    h2 {
      text-align: center;
      color: #0078D7;
      margin-bottom: 30px;
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

    form {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      align-items: center;
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
      color: white;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-weight: bold;
    }

    .notification {
      background-color: #d4edda;
      color: #155724;
      padding: 12px;
      border-radius: 8px;
      margin-bottom: 20px;
      text-align: center;
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
    <a href="../dashboard.php">← Retour au tableau de bord</a>
  </div>

  <h2>📅 Programmer une soutenance</h2>

  <?php if (isset($_SESSION['notification'])): ?>
    <div class="notification"><?= $_SESSION['notification'] ?></div>
    <?php unset($_SESSION['notification']); ?>
  <?php endif; ?>

  <table>
    <thead>
      <tr>
        <th>Titre</th>
        <th>Auteur</th>
        <th>Encadrant</th>
        <th>Date souhaitée</th>
        <th>Programmer</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
          <td><?= htmlspecialchars($row['titre']) ?></td>
          <td><?= htmlspecialchars($row['auteur']) ?></td>
          <td><?= htmlspecialchars($row['encadrant']) ?></td>
          <td><?= htmlspecialchars($row['date_souhaitee']) ?></td>
          <td>
            <form method="post" action="enregistrer_soutenance.php">
              <input type="hidden" name="id" value="<?= $row['id'] ?>">
              <input type="date" name="date" required>
              <input type="time" name="heure" required>
              <input type="text" name="salle" placeholder="Salle" required>
              <button type="submit" class="btn">✅ Enregistrer</button>
            </form>
          </td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</body>
</html>
