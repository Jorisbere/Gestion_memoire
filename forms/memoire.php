<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = (int) $_SESSION['user_id'];
$success = '';
$error = '';

// Récupérer tous les utilisateurs avec le rôle DM
$encadrants = [];
$encadrant_stmt = $conn->prepare("SELECT username FROM users WHERE role = 'DM'");
$encadrant_stmt->execute();
$encadrant_result = $encadrant_stmt->get_result();
while ($row = $encadrant_result->fetch_assoc()) {
    $encadrants[] = $row['username'];
}
$encadrant_stmt->close();

$check_stmt = $conn->prepare("SELECT etat_validation FROM memoires WHERE user_id = ? ORDER BY id DESC LIMIT 1");
$check_stmt->bind_param("i", $user_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();
$existing = $check_result->fetch_assoc();
$check_stmt->close();

$depot_autorise = true;
if ($existing && $existing['etat_validation'] === 'en_attente') {
    $depot_autorise = false;
    $error = "Vous avez déjà un mémoire en attente. Veuillez attendre sa validation ou son rejet avant d’en soumettre un nouveau.";
}



if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = trim($_POST['titre']);
    $encadrant = trim($_POST['encadrant'] ?? '');
    $annee = trim($_POST['annee']);
    $fichier = $_FILES['fichier'];

    if (empty($titre) || empty($annee) || empty($fichier['name'])) {
        $error = "Tous les champs sont obligatoires.";
    } else {
        $uploadDir = '../uploads/memoires/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileName = basename($fichier['name']);
        $targetPath = $uploadDir . time() . '_' . $fileName;

        if (move_uploaded_file($fichier['tmp_name'], $targetPath)) {
           $stmt = $conn->prepare("INSERT INTO memoires (user_id, titre, annee, fichier_path, date_depot, encadrant) 
                        VALUES (?, ?, ?, ?, NOW(), ?)");
                        $stmt->bind_param("issss", $user_id, $titre, $annee, $targetPath, $encadrant);
            if ($stmt->execute()) {
                $success = "Mémoire déposé avec succès.";
            } else {
                $error = "Erreur lors de l'enregistrement.";
            }
            $stmt->close();
        } else {
            $error = "Échec du téléchargement du fichier.";
        }
    }
}

// Récupérer le dernier mémoire déposé
$last_stmt = $conn->prepare("SELECT titre, etat_validation, fichier_path, date_depot FROM memoires WHERE user_id = ? ORDER BY id DESC LIMIT 1");
$last_stmt->bind_param("i", $user_id);
$last_stmt->execute();
$last_result = $last_stmt->get_result();
$last_memoire = $last_result->fetch_assoc();
$last_stmt->close();

?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Déposer un Mémoire</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script>
function confirmerDepot() {
  return confirm("Êtes-vous sûr de vouloir déposer ce mémoire ?");
}
</script>

  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background: linear-gradient(to right, #f0f4f8, #d9e2ec);
      padding: 40px;
      color: #333;
    }

    .back-button {
      text-align: center;
      margin-bottom: 20px;
    }

    .back-button a {
      text-decoration: none;
      color: #0078D7;
      font-weight: bold;
      font-size: 16px;
      transition: color 0.3s ease;
    }

    .back-button a:hover {
      color: #0056a3;
    }

    form {
      background: #fff;
      padding: 30px;
      border-radius: 16px;
      box-shadow: 0 8px 24px rgba(0,0,0,0.1);
      max-width: 700px;
      margin: auto;
    }

    h2 {
      text-align: center;
      margin-bottom: 25px;
      font-size: 24px;
      color: #0078D7;
    }

    label {
      display: block;
      margin-top: 15px;
      font-weight: 600;
      color: #444;
    }

    input[type="text"],
    input[type="file"] {
      width: 100%;
      padding: 12px;
      margin-top: 8px;
      border-radius: 10px;
      border: 1px solid #ccc;
      font-size: 15px;
      background: #f9f9f9;
      transition: border-color 0.3s ease;
    }

    input:focus {
      border-color: #0078D7;
      outline: none;
    }

    .button-container {
      display: flex;
      justify-content: center;
      margin-top: 12px;
      padding: 12px;
    }

    button {
      padding: 14px 24px;
      background: #0078D7;
      color: #fff;
      border: none;
      border-radius: 10px;
      cursor: pointer;
      font-size: 16px;
      transition: background 0.3s ease;
    }

    button:hover {
      background: #0056a3;
    }

    .message {
      margin-top: 20px;
      padding: 14px;
      border-radius: 10px;
      font-weight: 500;
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

    select {
  width: 100%;
  padding: 12px;
  margin-top: 8px;
  border-radius: 10px;
  border: 1px solid #ccc;
  font-size: 15px;
  background: #f9f9f9;
}


    @media (max-width: 600px) {
      form {
        padding: 20px;
      }

      h2 {
        font-size: 20px;
      }

      button {
        width: 100%;
      }
    }
  </style>
</head>
<body>
  

  <form method="POST" enctype="multipart/form-data" onsubmit="return confirmerDepot();">
    <?php if ($last_memoire): ?>
  <?php
    $etat = strtolower($last_memoire['etat_validation']);
    $badge = '';
    switch ($etat) {
      case 'valide':
        $badge = '<span style="color:green; font-weight:bold;">🟢 Validé</span>';
        break;
      case 'rejete':
        $badge = '<span style="color:red; font-weight:bold;">🔴 Rejeté</span>';
        break;
      default:
        $badge = '<span style="color:orange; font-weight:bold;">🟡 En attente</span>';
        break;
    }
  ?>
  <div class="message" style="background:#f0f4ff; border:1px solid #cce; margin-bottom:20px;">
    <strong>📌 Dernier mémoire déposé :</strong><br>
    <span><strong>Titre :</strong> <?= htmlspecialchars($last_memoire['titre']) ?></span><br>
    <span><strong>Statut :</strong> <?= $badge ?></span><br>
    <span><strong>Date :</strong> <?= date('d/m/Y à H:i', strtotime($last_memoire['date_depot'])) ?></span><br>
    <?php if (!empty($last_memoire['fichier_path']) && file_exists($last_memoire['fichier_path'])): ?>
      <a href="<?= $last_memoire['fichier_path'] ?>" target="_blank" style="color:#0078D7; font-weight:bold;">📥 Télécharger le fichier</a>
    <?php endif; ?>
  </div>
<?php endif; ?>




    <h2>🏢 Déposer un Mémoire</h2>

    <label for="titre">Titre du mémoire</label>
    <input type="text" name="titre" id="titre" required>

    <label for="encadrant">Encadrant</label>
<select name="encadrant" id="encadrant" required>
  <option value="">-- Sélectionner --</option>
  <?php foreach ($encadrants as $nom): ?>
    <option value="<?= htmlspecialchars($nom) ?>"><?= htmlspecialchars($nom) ?></option>
  <?php endforeach; ?>
</select>


    <label for="annee">Année académique</label>
    <input type="text" name="annee" id="annee" placeholder="ex: 2024-2025" required>

    <label for="fichier">Fichier PDF</label>
    <small style="color:#666;">Taille maximale autorisée : 20 Mo</small>
    <input type="file" name="fichier" id="fichier" accept=".pdf" required>

    <div class="button-container">
      <button type="submit">Déposer</button>
    </div>
    <div class="back-button">
    <a href="../dashboard.php">← Retour au tableau de bord</a>
  </div>

    <?php if ($success): ?>
      <div class="message success"><?= htmlspecialchars($success) ?></div>
    <?php elseif ($error): ?>
      <div class="message error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
  </form>
</body>
</html>
