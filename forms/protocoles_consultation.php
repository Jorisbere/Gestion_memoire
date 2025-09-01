<?php
session_start();
require_once '../includes/db.php';

// 🔐 Vérification de session
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// 🔍 Récupération des filtres GET
$search = $_GET['search'] ?? '';
$annee = $_GET['annee'] ?? '';
$filtre = $_GET['filtre_validation'] ?? '';

// 📦 Construction de la requête principale
$sql = "
  SELECT p.*, u.username AS auteur, dm.username AS dm_nom
  FROM protocoles p
  JOIN users u ON p.user_id = u.id
  LEFT JOIN users dm ON p.dm_id = dm.id
  WHERE 1=1
";
$params = [];
$types = "";

// 🔎 Filtrage par titre
if (!empty($search)) {
    $sql .= " AND p.titre LIKE ?";
    $params[] = "%$search%";
    $types .= "s";
}

// 📅 Filtrage par année
if (!empty($annee)) {
    $sql .= " AND YEAR(p.date_depot) = ?";
    $params[] = $annee;
    $types .= "i";
}

// 🧮 Filtrage par état de validation
if (in_array($filtre, ['valide', 'rejete', 'en_attente'])) {
    $sql .= " AND p.etat_validation = ?";
    $params[] = $filtre;
    $types .= "s";
}

// 🔧 Préparation et exécution
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// 🧠 Récupération des DM disponibles (moins de 5 protocoles validés cette année)
$annee_actuelle = date('Y');
$dm_query = "
  SELECT u.id, u.username
  FROM users u
  WHERE u.role = 'DM'
  AND (
    SELECT COUNT(*) FROM protocoles p
    WHERE p.dm_id = u.id AND YEAR(p.date_depot) = ?
    AND p.etat_validation = 'valide'
  ) < 5
";
$dm_stmt = $conn->prepare($dm_query);
$dm_stmt->bind_param("s", $annee_actuelle);
$dm_stmt->execute();
$dm_result = $dm_stmt->get_result();

$dms_disponibles = [];
while ($dm = $dm_result->fetch_assoc()) {
    $dms_disponibles[] = $dm;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Protocoles déposés</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background: #f0f4f8;
      padding: 40px;
      color: #333;
    }

    h2 {
      text-align: center;
      color: #0078D7;
      margin-bottom: 30px;
    }

    .search-form {
      max-width: 700px;
      margin: auto;
      display: flex;
      gap: 10px;
      margin-bottom: 30px;
    }

    .search-form input {
      flex: 1;
      padding: 10px;
      border-radius: 8px;
      border: 1px solid #ccc;
    }

    .search-form button {
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

    tr:hover {
      background: #f1f9ff;
    }

    .download-link {
      color: #0078D7;
      text-decoration: none;
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

    select {
  padding: 6px;
  border-radius: 6px;
  border: 1px solid #ccc;
  font-size: 14px;
  margin-right: 8px;
}


    /* ✅ Notification */
.notification {
  background-color: #e0f7e9;
  border: 1px solid #2ecc71;
  color: #2c3e50;
  padding: 10px 15px;
  margin: 15px 0;
  border-radius: 5px;
  position: relative;
  font-weight: bold;
}
.notification.success {
  background-color: #d4edda;
  border-color: #28a745;
}
.notification button {
  background: none;
  border: none;
  font-size: 16px;
  position: absolute;
  top: 5px;
  right: 10px;
  cursor: pointer;
}

/* ✅ Boutons de validation */
.btn {
  padding: 6px 12px;
  border: none;
  border-radius: 4px;
  font-weight: bold;
  cursor: pointer;
  margin-right: 5px;
}
.btn-success {
  background-color: #28a745;
  color: white;
}
.btn-danger {
  background-color: #dc3545;
  color: white;
}
.btn-success:hover {
  background-color: #218838;
}
.btn-danger:hover {
  background-color: #c82333;
}

/* ✅ Badge d’état */
.badge {
  padding: 5px 10px;
  border-radius: 12px;
  font-size: 0.9em;
  color: white;
}
.bg-success {
  background-color: #28a745;
}
.bg-danger {
  background-color: #dc3545;
}
.bg-warning {
  background-color: #ffc107;
  color: #212529;
}

    @media (max-width: 600px) {
      .search-form {
        flex-direction: column;
      }

      th, td {
        font-size: 14px;
      }
    }
  </style>
</head>
<body>
  <div class="back-button">
    <a href="../dashboard.php">← Retour au tableau de bord</a>
  </div>

  <h2><i class="fa-solid fa-file"></i> Protocoles déposés</h2>
  <?php if (isset($_SESSION['notification'])): ?>
  <div class="notification success">
    <?= $_SESSION['notification'] ?>
    <button onclick="this.parentElement.style.display='none'">✖</button>
  </div>
  <?php unset($_SESSION['notification']); ?>
<?php endif; ?>

  <form method="GET" class="search-form" onsubmit="return validateSearchForm()">
    <input type="text" name="search" id="search" placeholder="Rechercher par titre..." value="<?= htmlspecialchars($search) ?>">
    <input type="text" name="annee" id="annee" placeholder="Année (ex: 2025)" value="<?= htmlspecialchars($annee) ?>">
    <button type="submit">Filtrer</button>
    <select name="filtre_validation">
  <option value="">Tous</option>
  <option value="en_attente" <?= $filtre === 'en_attente' ? 'selected' : '' ?>>En attente</option>
  <option value="valide" <?= $filtre === 'valide' ? 'selected' : '' ?>>Validés</option>
  <option value="rejete" <?= $filtre === 'rejete' ? 'selected' : '' ?>>Rejetés</option>
    </select>



  </form>

  <table>
  <thead>
    <tr>
      <th>Titre</th>
      <th>Auteur</th>
      <th>Date de dépôt</th>
      <th>Fichier</th>
      <th>État</th>
      <th>DM attribué</th>
      <th>Action</th>
    </tr>
  </thead>
  <tbody>
  <?php if ($result->num_rows > 0): ?>
    <?php while ($row = $result->fetch_assoc()): ?>
      <tr>
  <td><?= htmlspecialchars($row['titre']) ?></td>
  <td><?= htmlspecialchars($row['auteur'] ?? 'Inconnu') ?></td>
  <td><?= date('d/m/Y', strtotime($row['date_depot'])) ?></td>
  <td><a class="download-link" href="<?= htmlspecialchars($row['fichier_path']) ?>" target="_blank"><i class="fa-solid fa-download"></i> Télécharger</a></td>
  <td>
    <span class="badge 
      <?= $row['etat_validation'] === 'valide' ? 'bg-success' : 
         ($row['etat_validation'] === 'rejete' ? 'bg-danger' : 'bg-warning') ?>">
      <?= ucfirst($row['etat_validation']) ?>
    </span>
  </td>
  <td>
    <?php if (!empty($row['dm_nom'])): ?>
      <?= htmlspecialchars($row['dm_nom']) ?>
    <?php else: ?>
      <em>Non attribué</em>
    <?php endif; ?>
  </td>
  <td>
    <?php if ($row['etat_validation'] === 'en_attente'): ?>
      <form method="post" action="valider_protocole.php" style="display:inline;">
        <input type="hidden" name="id_protocole" value="<?= $row['id'] ?>">
        <select name="dm_id" required>
          <option value="">Attribuer un DM</option>
          <?php foreach ($dms_disponibles as $dm): ?>
            <option value="<?= $dm['id'] ?>"><?= htmlspecialchars($dm['username']) ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit" name="action" value="valider" class="btn btn-success"><i class="fa-solid fa-check"></i> Valider</button>
      </form>
      <form method="post" action="valider_protocole.php" style="display:inline;">
        <input type="hidden" name="id_protocole" value="<?= $row['id'] ?>">
        <button type="submit" name="action" value="rejeter" class="btn btn-danger"><i class="fa-solid fa-xmark"></i> Rejeter</button>
      </form>
    <?php endif; ?>
  </td>
</tr>

    <?php endwhile; ?>
  <?php else: ?>
    <tr><td colspan="6">Aucun protocole trouvé.</td></tr>
  <?php endif; ?>
</tbody>

</table>


  <script>
    function validateSearchForm() {
      const annee = document.getElementById('annee').value.trim();
      if (annee && !/^[0-9]{4}$/.test(annee)) {
        alert("L'année doit être un nombre à 4 chiffres (ex: 2025).");
        return false;
      }
      return true;
    }
  </script>
</body>
</html>
