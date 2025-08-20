<?php
session_start();
require_once '../config/db.php';
require_once '../includes/calculate_tax.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$id = $_GET['id'] ?? null;

$stmt = $pdo->prepare("SELECT * FROM declarations WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $user_id]);
$declaration = $stmt->fetch();

if (!$declaration) {
    echo "Déclaration introuvable.";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $year       = (int) $_POST['year'];
    $income     = (float) $_POST['income'];
    $expenses   = (float) $_POST['expenses'];
    $dependents = (int) $_POST['dependents'];

    $tax_due = calculateTax($declaration['profile'], $income, $expenses, $dependents);

    $update = $pdo->prepare("
        UPDATE declarations SET year = ?, income = ?, expenses = ?, dependents = ?, tax_due = ?
        WHERE id = ? AND user_id = ?
    ");
    $update->execute([$year, $income, $expenses, $dependents, $tax_due, $id, $user_id]);

    $_SESSION['success'] = "✅ Déclaration mise à jour avec succès.";
    header("Location: history.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Modifier la déclaration</title>
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background: #f4f4f4;
      padding: 30px;
    }

    .container {
      max-width: 600px;
      margin: auto;
      background: white;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }

    h2 {
      text-align: center;
      color: #0078D7;
      margin-bottom: 20px;
    }

    label {
      font-weight: bold;
      display: block;
      margin-bottom: 6px;
    }

    input {
      width: 100%;
      padding: 10px;
      margin-bottom: 20px;
      border-radius: 6px;
      border: 1px solid #ccc;
    }

    button {
      background-color: #0078D7;
      color: white;
      padding: 12px;
      border: none;
      border-radius: 8px;
      font-size: 16px;
      cursor: pointer;
      width: 100%;
    }

    button:hover {
      background-color: #005fa3;
    }

    .back-link {
      display: block;
      text-align: center;
      margin-top: 20px;
      color: #0078D7;
      text-decoration: none;
      font-weight: bold;
    }

    .back-link:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>

<div class="container">
  <h2>✏️ Modifier la déclaration</h2>
  <form method="POST">
    <label for="year">Année fiscale :</label>
    <input type="number" name="year" id="year" required value="<?= $declaration['year'] ?>">

    <label for="income">Revenu :</label>
    <input type="number" step="0.01" name="income" id="income" required value="<?= $declaration['income'] ?>">

    <label for="expenses">Dépenses :</label>
    <input type="number" step="0.01" name="expenses" id="expenses" required value="<?= $declaration['expenses'] ?>">

    <label for="dependents">Personnes à charge :</label>
    <input type="number" name="dependents" id="dependents" required value="<?= $declaration['dependents'] ?>">

    <button type="submit">💾 Enregistrer</button>
  </form>

  <a href="history.php" class="back-link">← Retour à l'historique</a>
</div>

</body>
</html>
