<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success = $_SESSION['success'] ?? '';
unset($_SESSION['success']);

$stmt = $pdo->prepare("SELECT * FROM declarant WHERE user_id = ?");
$stmt->execute([$user_id]);
$declarants = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Liste des déclarants</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background: #f4f4f4;
      padding: 30px;
      margin: 0;
    }

    .top-bar {
      margin-bottom: 20px;
      display: flex;
      flex-wrap: wrap;
      justify-content: space-between;
      align-items: center;
      gap: 10px;
    }

    h2 {
      color: #0078D7;
      margin: 0;
    }

    .add-btn {
      background-color: #0078D7;
      color: white;
      padding: 10px 16px;
      border-radius: 6px;
      text-decoration: none;
      font-weight: bold;
    }

    .add-btn:hover {
      background-color: #005fa3;
    }

    #searchInput {
      padding: 8px;
      border-radius: 6px;
      border: 1px solid #ccc;
      font-size: 14px;
      width: 220px;
    }

    #searchInput:focus {
      outline: none;
      box-shadow: 0 0 0 2px rgba(0,120,215,0.3);
    }

    .success-message {
      background-color: #d4edda;
      color: #155724;
      padding: 12px;
      border-radius: 6px;
      margin-bottom: 20px;
      box-shadow: 0 0 5px rgba(0,0,0,0.1);
    }

    table {
      width: 100%;
      border-collapse: collapse;
      background: white;
      box-shadow: 0 0 5px #ccc;
    }

    th, td {
      padding: 12px;
      border-bottom: 1px solid #ddd;
      text-align: left;
    }

    th {
      background-color: #0078D7;
      color: white;
    }

    a.action {
      text-decoration: none;
      color: #0078D7;
      font-weight: bold;
      margin-right: 10px;
    }

    a.action:hover {
      text-decoration: underline;
    }

    .back-btn {
  display: inline-block;
  /* margin-bottom: 20px; */
  background-color: #6c757d;
  color: white;
  padding: 10px 16px;
  border-radius: 6px;
  text-decoration: none;
  font-weight: bold;
}

.back-btn:hover {
  background-color: #5a6268;
}


    @media (max-width: 600px) {
      table, thead, tbody, th, td, tr {
        display: block;
      }

      th {
        display: none;
      }

      td {
        padding: 10px;
        border: none;
        position: relative;
      }

      td::before {
        content: attr(data-label);
        font-weight: bold;
        display: block;
        margin-bottom: 5px;
        color: #555;
      }
    }
  </style>
</head>
<body>

<div class="top-bar">
  <h2>👥 Déclarants enregistrés</h2>
  <div style="display: flex; gap: 10px; align-items: center;">
    <input type="text" id="searchInput" placeholder="🔍 Rechercher par nom...">
    <a href="../dashboard.php" class="back-btn">← Retour au dashboard</a>
    <a href="ajouter_declarant.php" class="add-btn">➕ Ajouter un déclarant</a>
  </div>
</div>

<?php if ($success): ?>
  <div class="success-message"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<table>
  <thead>
    <tr>
      <th>Nom</th>
      <th>Email</th>
      <th>Numéro fiscal</th>
      <th>Actions</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($declarants as $d): ?>
      <tr>
        <td data-label="Nom"><?= htmlspecialchars($d['fullname']) ?></td>
        <td data-label="Email"><?= htmlspecialchars($d['email']) ?></td>
        <td data-label="Numéro fiscal"><?= htmlspecialchars($d['fiscal_id']) ?></td>
        <td data-label="Actions">
          <a href="edit_declarant.php?id=<?= $d['id'] ?>" class="action">✏️ Modifier</a>
          <a href="delete_declarant.php?id=<?= $d['id'] ?>" class="action" onclick="return confirm('Supprimer ce déclarant ?')">🗑️ Supprimer</a>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<script>
document.getElementById('searchInput').addEventListener('input', function () {
  const filter = this.value.toLowerCase();
  const rows = document.querySelectorAll('table tbody tr');

  rows.forEach(row => {
    const nameCell = row.querySelector('td');
    const name = nameCell.textContent.toLowerCase();
    row.style.display = name.includes(filter) ? '' : 'none';
  });
});
</script>

</body>
</html>
