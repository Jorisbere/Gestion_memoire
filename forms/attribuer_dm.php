<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'administrateur') {
    header("Location: ../login.php");
    exit();
}

$notification = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    $selected_id = intval($_POST['user_id']);
    $stmt = $conn->prepare("UPDATE users SET role = 'DM' WHERE id = ?");
    $stmt->bind_param("i", $selected_id);
    if ($stmt->execute()) {
        $notification = "✅ L'utilisateur a été nommé DM avec succès.";
    } else {
        $notification = "❌ Une erreur est survenue.";
    }
    $stmt->close();
}

// Récupération des utilisateurs
$result = $conn->query("SELECT id, username, role FROM users ORDER BY username ASC");
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Attribuer un DM</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background: #f4f6f8;
      padding: 40px;
      color: #333;
    }

    .container {
      max-width: 600px;
      margin: auto;
      background: #fff;
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
    <h2>👤 Attribuer le rôle DM</h2>

    <form method="POST">
      <label for="user_id">Sélectionner un utilisateur :</label>
      <select name="user_id" id="user_id" required>
        <option value="">-- Choisir --</option>
        <?php while ($user = $result->fetch_assoc()): ?>
          <option value="<?= $user['id'] ?>">
            <?= htmlspecialchars($user['username']) ?> (<?= $user['role'] ?>)
          </option>
        <?php endwhile; ?>
      </select>

      <button type="submit">Attribuer le rôle DM</button>
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
