<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'administrateur') {
    header("Location: ../login.php");
    exit();
}

$notification = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'], $_POST['action'])) {
    $selected_id = intval($_POST['user_id']);
    $action = $_POST['action'];

    if ($action === 'attribuer') {
        $stmt = $conn->prepare("UPDATE users SET role = 'DM' WHERE id = ?");
        $stmt->bind_param("i", $selected_id);
        if ($stmt->execute()) {
            $notification = "✅ L'utilisateur a été nommé DM avec succès.";
        } else {
            $notification = "❌ Une erreur est survenue lors de l'attribution.";
        }
        $stmt->close();
    } elseif ($action === 'revoquer') {
        $stmt = $conn->prepare("UPDATE users SET role = 'etudiant' WHERE id = ?");
        $stmt->bind_param("i", $selected_id);
        if ($stmt->execute()) {
            $notification = "✅ Le rôle DM a été révoqué. L'utilisateur est redevenu étudiant.";
        } else {
            $notification = "❌ Une erreur est survenue lors de la révocation.";
        }
        $stmt->close();
    }
}


// Récupération des utilisateurs
$result = $conn->query("SELECT id, username, role FROM users WHERE role IN ('etudiant', 'DM') ORDER BY username ASC");

$etudiants = [];
$dms = [];

while ($user = $result->fetch_assoc()) {
    if ($user['role'] === 'etudiant') {
        $etudiants[] = $user;
    } elseif ($user['role'] === 'DM') {
        $dms[] = $user;
    }
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Attribuer un DM</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" type="image/x-icon" href="../assets/favicon.ico">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background: #f0f4f8;
      padding: 40px;
      color: #333;
    }

    .container {
      max-width: 600px;
      margin: auto;
      background: #dfe7f0ff;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 6px 18px rgba(0,0,0,0.1);
    }

    h2 {
      text-align: center;
      margin-bottom: 30px;
      color: #0078D7;
    }

    form {
      display: flex;
      flex-direction: column;
      gap: 16px;
    }

    select {
      padding: 10px;
      border-radius: 8px;
      border: 1px solid #ccc;
      font-size: 16px;
    }

    button {
      padding: 12px;
      background: #0078D7;
      color: white;
      border: none;
      border-radius: 8px;
      font-size: 16px;
      cursor: pointer;
    }

    button:hover {
      background: #0056a3;
    }

    .notification {
      margin-top: 20px;
      padding: 12px;
      border-radius: 8px;
      font-weight: bold;
      text-align: center;
    }

    .success {
      background: #d4edda;
      color: #155724;
    }

    .error {
      background: #f8d7da;
      color: #721c24;
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
  <div class="container">
    <h2><i class="fa-solid fa-user"></i> Attribuer le rôle DM</h2>

    <form method="POST">
  <label for="user_id">Sélectionner un utilisateur :</label>
  <select name="user_id" id="user_id" required>
  <option value="">-- Choisir --</option>

  <optgroup label="Étudiants">
    <?php foreach ($etudiants as $user): ?>
      <option value="<?= $user['id'] ?>">
        <?= htmlspecialchars($user['username']) ?> (étudiant)
      </option>
    <?php endforeach; ?>
  </optgroup>

  <optgroup label="DM actuels">
    <?php foreach ($dms as $user): ?>
      <option value="<?= $user['id'] ?>">
        <?= htmlspecialchars($user['username']) ?> (DM)
      </option>
    <?php endforeach; ?>
  </optgroup>
</select>


  <button type="submit" name="action" value="attribuer">Attribuer le rôle DM</button>
  <button type="submit" name="action" value="revoquer" style="background:#d9534f;">Révoquer le rôle DM</button>

  <div class="back-button">
    <a href="../dashboard.php">← Retour au tableau de bord</a>
  </div>
</form>


    <?php if ($notification): ?>
      <div class="notification <?= str_starts_with($notification, '✅') ? 'success' : 'error' ?>">
        <?= $notification ?>
      </div>
    <?php endif; ?>
  </div>
</body>
</html>
