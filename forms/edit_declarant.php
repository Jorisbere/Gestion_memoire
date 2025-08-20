<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$id = $_GET['id'] ?? null;

$stmt = $pdo->prepare("SELECT * FROM declarant WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $user_id]);
$declarant = $stmt->fetch();

if (!$declarant) {
    echo "Déclarant introuvable.";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname   = trim($_POST['fullname']);
    $email      = trim($_POST['email']);
    $fiscal_id  = trim($_POST['fiscal_id']);

    $update = $pdo->prepare("UPDATE declarant SET fullname = ?, email = ?, fiscal_id = ? WHERE id = ? AND user_id = ?");
    $update->execute([$fullname, $email, $fiscal_id, $id, $user_id]);

    $_SESSION['success'] = "✅ Déclarant mis à jour avec succès.";
    header("Location: liste_declarants.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Modifier le déclarant</title>
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
  <h2>✏️ Modifier le déclarant</h2>
  <form method="POST">
    <label for="fullname">Nom complet :</label>
    <input type="text" name="fullname" id="fullname" required value="<?= htmlspecialchars($declarant['fullname']) ?>">

    <label for="email">Email :</label>
    <input type="email" name="email" id="email" required value="<?= htmlspecialchars($declarant['email']) ?>">

    <label for="fiscal_id">Numéro fiscal :</label>
    <input type="text" name="fiscal_id" id="fiscal_id" required value="<?= htmlspecialchars($declarant['fiscal_id']) ?>">

    <button type="submit">💾 Enregistrer</button>
  </form>

  <a href="liste_declarants.php" class="back-link">← Retour à la liste</a>
</div>

</body>
</html>
