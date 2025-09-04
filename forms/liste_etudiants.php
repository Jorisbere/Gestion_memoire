<?php
session_start();
require_once '../config/db.php'; // adapte le chemin si nécessaire

// Vérification de session
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'DM') {
    header('Location: login.php');
    exit;
}

// Récupération de l'ID du DM connecté
$dm_id = $_SESSION['user_id'];

// Requête : récupérer les étudiants liés au DM via la table protocoles
$stmt = $pdo->prepare("
    SELECT 
        u.username AS etudiant,
        ds.titre,
        ds.etat_validation,
        ds.date_soutenance,
        ds.salle,
        ds.id
    FROM protocoles p
    JOIN users u ON p.user_id = u.id
    JOIN demandes_soutenance ds ON ds.user_id = u.id
    WHERE p.dm_id = ?
    ORDER BY ds.date_soutenance DESC
");
$stmt->execute([$dm_id]);
$etudiants = $stmt->fetchAll();
// Récupérer le nom du DM pour l'affichage
$stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
$stmt->execute([$dm_id]);
$dm_name = $stmt->fetchColumn();

?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Liste des étudiants</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background: linear-gradient(90deg, #49c4e6ff 0%, #e5edf7ff 100%);
      padding: 40px;
      color: #333;
    }
    h2 {
      text-align: center;
      margin-bottom: 30px;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      background:rgb(229, 232, 232);
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    th, td {
      padding: 12px 16px;
      border-bottom: 1px solid #eee;
      text-align: left;
    }
    th {
      background-color: #0078D7;
      color: #fff;
    }
    tr:hover {
      background-color: #f1f9ff;
    }
    .status {
      font-weight: bold;
      color: #0078D7;
    }
    .actions a {
      margin-right: 10px;
      text-decoration: none;
      color: #0078D7;
    }
    .actions a:hover {
      text-decoration: underline;
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
    @media (min-width: 901px) { .gm-main { padding-left: 280px; } }
    @media (max-width: 900px) { .gm-main { padding-top: 80px; } }
  </style>
</head>
<body>
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="gm-main">
    <!-- <div class="back-button">
    <a href="../accueil.php">← Retour au tableau de bord</a>
  </div> -->

<h2><i class="fas fa-users"></i> Étudiants encadrés par <?= htmlspecialchars($dm_name) ?></h2>

<table>
  <thead>
    <tr>
      <th>Étudiant</th>
      <th>Titre du mémoire</th>
      <th>État</th>
      <th>Date soutenance</th>
      <th>Salle</th>
    </tr>
  </thead>
  <tbody>
    <?php if (count($etudiants) > 0): ?>
      <?php foreach ($etudiants as $row): ?>
        <tr>
          <td><?= htmlspecialchars($row['etudiant']) ?></td>
          <td><?= htmlspecialchars($row['titre']) ?></td>
          <td class="status"><?= ucfirst($row['etat_validation']) ?></td>
          <td><?= $row['date_soutenance'] ? date('Y-m-d H:i', strtotime($row['date_soutenance'])) : '—' ?></td>
          <td><?= htmlspecialchars($row['salle'] ?? '—') ?></td>
        </tr>
      <?php endforeach; ?>
    <?php else: ?>
      <tr><td colspan="6" style="text-align:center;">Aucun étudiant encadré pour le moment.</td></tr>
    <?php endif; ?>
  </tbody>
</table>
  </div>
</body>
</html>