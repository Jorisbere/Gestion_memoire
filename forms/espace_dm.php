<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$username = $_SESSION['username'] ?? 'Utilisateur';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Espace DM</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background: linear-gradient(to right, #eef2f3, #dfe9f3);
      padding: 40px;
      color: #333;
    }

    .container {
      max-width: 800px;
      margin: auto;
      background: #fff;
      padding: 30px;
      border-radius: 16px;
      box-shadow: 0 8px 24px rgba(0,0,0,0.1);
      text-align: center;
    }

    h1 {
      font-size: 28px;
      color: #0078D7;
      margin-bottom: 10px;
    }

    p.welcome {
      font-size: 18px;
      margin-bottom: 30px;
    }

    .grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }

    .card {
      background: #f9f9f9;
      padding: 20px;
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.05);
      transition: transform 0.2s ease;
    }

    .card:hover {
      transform: translateY(-4px);
    }

    .card a {
      text-decoration: none;
      color: #0078D7;
      font-weight: bold;
      font-size: 16px;
      display: block;
    }



    .back-button {
      display: flex;
      justify-content: center;
      margin-top: 12px;
      padding: 12px;
    }

    

    @media (max-width: 600px) {
      h1 {
        font-size: 22px;
      }

      .card a {
        font-size: 14px;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <h1>📁 Espace DM</h1>
    <p class="welcome">Bienvenue <?= htmlspecialchars($username) ?> 👋</p>

    <div class="grid">
      <div class="card">
        <a href="depot_protocole.php">📄 Dépôt de protocole</a>
      </div>
      <div class="card">
        <a href="demande_soutenance.php">🧑‍💼 Demande de soutenance</a>
      </div>
      <div class="card">
        <a href="memoire.php">🏢 Déposer mémoire</a>
      </div>


    <div class="back-button">
    <a href="../dashboard.php">← Retour au tableau de bord</a>
  </div>
  </div>
</body>
</html>
