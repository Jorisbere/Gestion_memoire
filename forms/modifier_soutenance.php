<?php
require_once '../config/db.php'; // adapte le chemin si nécessaire

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    die("ID invalide.");
}

// Récupération des données existantes
$stmt = $pdo->prepare("SELECT * FROM demandes_soutenance WHERE id = ?");
$stmt->execute([$id]);
$demande = $stmt->fetch();

if (!$demande) {
    die("Demande introuvable.");
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $date_soutenance = $_POST['date_soutenance'] . ' ' . $_POST['heure_soutenance'];
    $salle = trim($_POST['salle']);
    $session = trim($_POST['session_souhaitee']);

    $stmt = $pdo->prepare("UPDATE demandes_soutenance SET date_soutenance = ?, salle = ?, session_souhaitee = ? WHERE id = ?");
    $success = $stmt->execute([$date_soutenance, $salle, $session, $id]);

    if ($success) {
        echo "<p style='color:green;'>✅ Programmation mise à jour avec succès.</p>";
        echo "<a href='programmer_soutenance.php'>← Retour à la liste</a>";
        exit;
    } else {
        echo "<p style='color:red;'>❌ Échec de la mise à jour.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Modifier la programmation</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f2f2f2;
      padding: 20px;
    }
    form {
      max-width: 500px;
      margin: auto;
      background: #fff;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    label {
      display: block;
      margin-top: 15px;
      font-weight: bold;
    }
    input, select {
      width: 100%;
      padding: 8px;
      margin-top: 5px;
      box-sizing: border-box;
      border: 1px solid #ccc;
      border-radius: 4px;
    }
    .btn {
      margin-top: 20px;
      padding: 10px 16px;
      background-color: #28a745;
      color: white;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-weight: bold;
      text-align: center;
      width: 100%;
    }
    .btn:hover {
      background-color: #218838;
    }

    .back-button {
  text-align: center;
  margin-bottom: 30px;
}

.btn-retour {
    margin-top: 12px;
  display: inline-block;
  padding: 8px 14px;
  background-color: #6c757d;
  color: white;
  text-decoration: none;
  border-radius: 6px;
  font-weight: bold;
}
.btn-retour:hover {
  background-color: #5a6268;
}


  </style>
</head>
<body>

<h2 style="text-align:center;">🛠️ Modifier la programmation</h2>

<form method="POST">
  <label for="date_soutenance">Date :</label>
  <input type="date" name="date_soutenance" id="date_soutenance" value="<?= htmlspecialchars(substr($demande['date_soutenance'] ?? '', 0, 10)) ?>" required>

  <label for="heure_soutenance">Heure :</label>
  <input type="time" name="heure_soutenance" id="heure_soutenance" value="<?= htmlspecialchars(substr($demande['date_soutenance'] ?? '', 11, 5)) ?>" required>

  <label for="salle">Salle :</label>
  <input type="text" name="salle" id="salle" value="<?= htmlspecialchars($demande['salle'] ?? '') ?>" required>

  <label for="session_souhaitee">Session :</label>
  <select name="session_souhaitee" id="session_souhaitee">
    <option value="">-- Choisir --</option>
    <option value="normale" <?= ($demande['session_souhaitee'] ?? '') === 'normale' ? 'selected' : '' ?>>Normale</option>
    <option value="anticipe" <?= ($demande['session_souhaitee'] ?? '') === 'anticipe' ? 'selected' : '' ?>>Anticipée</option>
  </select>

  <button type="submit" name="update" class="btn"><i class="fa-solid fa-check"></i> Enregistrer les modifications</button>
  <div class="back-button">
  <a href="programmer_soutenance.php" class="btn-retour">← Retour à la liste</a>
</div>

</form>

</body>
</html>
