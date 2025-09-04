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

// Gestion des filtres et recherche
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_etat = isset($_GET['etat']) ? $_GET['etat'] : '';

// Construction dynamique de la requête SQL avec filtres
$sql = "
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
";
$params = [$dm_id];

if ($search !== '') {
    $sql .= " AND (u.username LIKE ? OR ds.titre LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($filter_etat !== '' && in_array($filter_etat, ['valide', 'rejete', 'en_attente'])) {
    $sql .= " AND ds.etat_validation = ?";
    $params[] = $filter_etat;
}

$sql .= " ORDER BY ds.date_soutenance DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
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
      color: #0078D7;
    }
    .search-filter-bar {
      display: flex;
      flex-wrap: wrap;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 25px;
      gap: 10px;
    }
    .search-filter-bar form {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
      width: 100%;
      justify-content: flex-end;
    }
    .search-filter-bar input[type="text"] {
      padding: 8px 12px;
      border: 1px solid #b3c6d7;
      border-radius: 4px;
      font-size: 1em;
      min-width: 180px;
    }
    .search-filter-bar select {
      padding: 8px 12px;
      border: 1px solid #b3c6d7;
      border-radius: 4px;
      font-size: 1em;
    }
    .search-filter-bar button {
      padding: 8px 16px;
      background: #0078D7;
      color: #fff;
      border: none;
      border-radius: 4px;
      font-weight: bold;
      cursor: pointer;
      transition: background 0.2s;
    }
    .search-filter-bar button:hover {
      background: #005fa3;
    }

    .search-bar {
      max-width: 700px;
      margin: auto;
      display: flex;
      gap: 10px;
      margin-bottom: 30px;
    }

    .search-bar select {
      flex: 1;
      padding: 10px;
      border-radius: 8px;
      border: 1px solid #ccc;
    }

    .search-bar input {
      flex: 1;
      padding: 10px;
      border-radius: 8px;
      border: 1px solid #ccc;
    }

    .search-bar button {
      padding: 10px 20px;
      background: #0078D7;
      color: #fff;
      border: none;
      border-radius: 8px;
      cursor: pointer;
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
      color: #121212ff;
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

<div class="search-bar">
  <form method="get" action="">
    <input type="text" name="search" placeholder="Rechercher un étudiant ou un titre..." value="<?= htmlspecialchars($search) ?>" />
    <select name="etat">
      <option value="">Tous les états</option>
      <option value="en_attente" <?= $filter_etat === 'en_attente' ? 'selected' : '' ?>>En attente</option>
      <option value="valide" <?= $filter_etat === 'valide' ? 'selected' : '' ?>>Validé</option>
      <option value="rejete" <?= $filter_etat === 'rejete' ? 'selected' : '' ?>>Rejeté</option>
    </select>
    <button type="submit"><i class="fa fa-search"></i> Rechercher</button>
    <?php if ($search !== '' || $filter_etat !== ''): ?>
      <a href="liste_etudiants.php" style="margin-left:10px; color:#0078D7; text-decoration:underline;">Réinitialiser</a>
    <?php endif; ?>
  </form>
</div>

<table>
  <thead>
    <tr>
      <th><i class="fa-solid fa-user"></i> Étudiant</th>
      <th><i class="fa-solid fa-file-alt"></i> Titre du mémoire</th>
      <th><i class="fa-solid fa-check-circle"></i> État</th>
      <th><i class="fa-solid fa-calendar-alt"></i> Date soutenance</th>
      <th><i class="fa-solid fa-door-open"></i> Salle</th>
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