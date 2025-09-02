<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'administrateur') {
    header("Location: ../login.php");
    exit();
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
      text-align: center;
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

    input[type="text"]::placeholder {
  color: #888;
}

    
  </style>
</head>
<body>
    <div class="back-button">
    <a href="../dashboard.php">← Retour au tableau de bord</a>
  </div>

  <h2><i class="fa-solid fa-calendar"></i> Programmer une soutenance</h2>

  <form method="get" style="margin-bottom: 20px; display: flex; gap: 10px; flex-wrap: wrap; justify-content: center;">
  <input type="text" name="search" placeholder="🔍 Rechercher par titre, auteur ou encadrant" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" style="padding: 8px; width: 250px; border-radius: 6px; border: 1px solid #ccc;">

  <select name="programmation" style="padding: 8px; border-radius: 6px; border: 1px solid #ccc;">
    <option value="">📌 Tous</option>
    <option value="programmee" <?= ($_GET['programmation'] ?? '') === 'programmee' ? 'selected' : '' ?>>✅ Programmées</option>
    <option value="non_programmee" <?= ($_GET['programmation'] ?? '') === 'non_programmee' ? 'selected' : '' ?>>⏳ Non programmées</option>
  </select>

  <select name="annee" style="padding: 8px; border-radius: 6px; border: 1px solid #ccc;">
    <option value=""><i class="fa-solid fa-calendar"></i> Toutes les années</option>
    <?php
      $currentYear = date('Y');
      for ($y = $currentYear; $y >= $currentYear - 5; $y--) {
        $selected = ($_GET['annee'] ?? '') == $y ? 'selected' : '';
        echo "<option value=\"$y\" $selected>$y</option>";
      }
    ?>
  </select>

  <button type="submit" class="btn" style="background-color:#0078D7;">Filtrer</button>
</form>


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
  <th>Session souhaitée</th>
  <th>Programmation</th>
  <th>Actions</th>
</tr>

    </thead>
    <tbody>
      <?php while ($row = $result->fetch_assoc()): ?>
  <tr>
    <!-- Titre -->
    <td><?= htmlspecialchars($row['titre'] ?? '') ?></td>

    <!-- Auteur -->
    <td><?= htmlspecialchars($row['auteur'] ?? '') ?></td>

    <!-- Encadrant -->
    <td><?= htmlspecialchars($row['encadrant'] ?? '') ?></td>
    <!-- Session souhaitée -->
    <td><?= htmlspecialchars($row['session_souhaitee'] ?? '') ?></td>

    <!-- Programmation -->
    <td>
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
    <td>
      <?php if (!empty($row['date_soutenance'])): ?>
        <div style="display: flex; flex-direction: column; gap: 6px;">
          <a href="modifier_soutenance.php?id=<?= $row['id'] ?>" class="btn" style="background-color:#ffc107;"><i class="fa-solid fa-pencil"></i> Modifier</a>
        </div>
      <?php else: ?>
        <form method="post" action="enregistrer_soutenance.php" style="display: flex; flex-direction: column; gap: 6px;">
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
</body>
</html>
