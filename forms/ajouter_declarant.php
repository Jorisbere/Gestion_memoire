<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname   = trim($_POST['fullname']);
    $email      = trim($_POST['email']);
    $fiscal_id  = trim($_POST['fiscal_id']);

    if (!$fullname || !$email || !$fiscal_id) {
        $error = "Tous les champs sont obligatoires.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO declarant (user_id, fullname, email, fiscal_id) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user_id, $fullname, $email, $fiscal_id]);
            $_SESSION['success'] = "✅ Déclarant ajouté avec succès !";
            header("Location: liste_declarants.php");
            exit();
        } catch (PDOException $e) {
            $error = "Erreur : " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Ajouter un déclarant</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background-color: #eef2f7;
      margin: 0;
      padding: 0;
    }

    .container {
      max-width: 600px;
      margin: 50px auto;
      background: #fff;
      padding: 40px;
      border-radius: 12px;
      box-shadow: 0 8px 20px rgba(0,0,0,0.1);
    }

    h2 {
      text-align: center;
      color: #0078D7;
      margin-bottom: 30px;
    }

    label {
      font-weight: bold;
      display: block;
      margin-bottom: 6px;
      color: #333;
    }

    input {
      width: 100%;
      padding: 12px;
      margin-bottom: 20px;
      border: 1px solid #ccc;
      border-radius: 8px;
      font-size: 15px;
      background-color: #fdfdfd;
    }

    input:focus {
      border-color: #0078D7;
      outline: none;
      background-color: #f0f8ff;
    }

    button {
      width: 100%;
      background-color: #0078D7;
      color: white;
      padding: 12px;
      border: none;
      border-radius: 8px;
      font-size: 16px;
      cursor: pointer;
      transition: background 0.3s ease;
    }

    button:hover {
      background-color: #005fa3;
    }

    .error {
      color: red;
      text-align: center;
      margin-bottom: 20px;
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

    @media (max-width: 600px) {
      .container {
        margin: 20px;
        padding: 25px;
      }
    }
  </style>
</head>
<body>

<div class="container">
  <h2>👤 Ajouter un déclarant</h2>

  <?php if ($error): ?>
    <div class="error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST">
    <label for="fullname">Nom complet :</label>
    <input type="text" name="fullname" id="fullname" required placeholder="Ex: Jean Dupont">

    <label for="email">Email :</label>
    <input type="email" name="email" id="email" required placeholder="Ex: jean.dupont@email.com">

    <label for="fiscal_id">Numéro fiscal :</label>
    <input type="text" name="fiscal_id" id="fiscal_id" required placeholder="Ex: 1234567890">

    <button type="submit">✅ Ajouter</button>
  </form>

  <a href="liste_declarants.php" class="back-link">← Retour à la liste</a>
</div>

</body>
</html>
