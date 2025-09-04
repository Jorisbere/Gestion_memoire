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

// Vérification de l'autorisation de dépôt
$check_stmt = $conn->prepare("SELECT etat_validation FROM protocoles WHERE user_id = ? ORDER BY id DESC LIMIT 1");
$check_stmt->bind_param("i", $user_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();
$existing = $check_result->fetch_assoc();
$check_stmt->close();

$depot_autorise = true;
if ($existing && $existing['etat_validation'] === 'en_attente') {
    $depot_autorise = false;
    $error = "Vous avez déjà un protocole en attente. Veuillez attendre sa validation ou son rejet avant d’en soumettre un nouveau.";
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $depot_autorise) {
    $titre = trim($_POST['titre']);
    $description = trim($_POST['description']);
    $fichier = $_FILES['fichier'];

    if (empty($titre) || empty($description) || empty($fichier['name'])) {
        $error = "Tous les champs sont obligatoires.";
    } else {
        $uploadDir = '../uploads/protocoles/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileName = basename($fichier['name']);
        $targetPath = $uploadDir . time() . '_' . $fileName;

        if (move_uploaded_file($fichier['tmp_name'], $targetPath)) {
            $stmt = $conn->prepare("INSERT INTO protocoles (user_id, titre, description, fichier_path, date_depot, etat_validation) VALUES (?, ?, ?, ?, NOW(), 'en_attente')");
            $stmt->bind_param("isss", $user_id, $titre, $description, $targetPath);
            if ($stmt->execute()) {
                $success = "Protocole déposé avec succès et en attente de validation.";
            } else {
                $error = "Erreur lors de l'enregistrement en base.";
            }
            $stmt->close();
        } else {
            $error = "Échec du téléchargement du fichier.";
        }
    }
}
// Récupérer le dernier protocole déposé
$last_stmt = $conn->prepare("SELECT titre, etat_validation, fichier_path, date_depot FROM protocoles WHERE user_id = ? ORDER BY id DESC LIMIT 1");
$last_stmt->bind_param("i", $user_id);
$last_stmt->execute();
$last_result = $last_stmt->get_result();
$last_protocole = $last_result->fetch_assoc();
$last_stmt->close();

?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Dépot de protocole</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <script>
function confirmerDepot() {
  return confirm("Êtes-vous sûr de vouloir déposer ce protocole ?");
}
</script>
  <style>
  body {
    font-family: 'Segoe UI', sans-serif;
    background: linear-gradient(90deg, #49c4e6ff 0%, #e5edf7ff 100%); 
    padding: 40px;
    color: #333;
  }
  @media (min-width: 901px) { .gm-main { padding-left: 280px; } }
  @media (max-width: 900px) { .gm-main { padding-top: 80px; } }

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
    background:rgb(229, 232, 232);
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
    color: #333;
  }

  input[type="text"],
  textarea,
  input[type="file"] {
    width: 100%;
    padding: 12px;
    margin-top: 8px;
    border-radius: 10px;
    border: 1px solid #ccc;
    font-size: 15px;
    background:rgb(229, 232, 232);
    transition: border-color 0.3s ease;
  }

  input:focus,
  textarea:focus {
    border-color: #0078D7;
    outline: none;
  }

  button {
    margin-top: 25px;
    padding: 14px 24px;
    background: #0078D7;
    color: #333;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    font-size: 16px;
    transition: background 0.3s ease;
  }

  button:hover {
    background: #0056a3;
  }

  .button-container {
  display: flex;
  justify-content: center;
  /* margin-top: 35px; */
  padding: 12px;
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

  .error { background:rgb(151, 206, 248); color: rgb(200, 47, 62); }

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
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <main class="main">
    <div class="gm-main">
      <form method="POST" enctype="multipart/form-data" onsubmit="return confirmerDepot();">
        <?php if ($last_protocole): ?>
        <?php
          // Définir le badge selon le statut
        $etat = strtolower($last_protocole['etat_validation']);
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
      <!-- <div class="message" style="background:#f0f4ff; border:1px solid #cce; margin-bottom:20px;">
        <strong>📌 Dernier protocole déposé :</strong><br>
        <span><strong>Titre :</strong> <?= htmlspecialchars($last_protocole['titre']) ?></span><br>
        <span><strong>Statut :</strong> <?= $badge ?></span><br>
        <span><strong>Date :</strong> <?= date('d/m/Y à H:i', strtotime($last_protocole['date_depot'])) ?></span><br>
        <?php if (!empty($last_protocole['fichier_path']) && file_exists($last_protocole['fichier_path'])): ?>
          <a href="<?= $last_protocole['fichier_path'] ?>" target="_blank" style="color:#0078D7; font-weight:bold;"><i class="fa-solid fa-download"></i> Télécharger le fichier</a>
        <?php endif; ?>
      </div>
    <?php endif; ?> -->

      <h2><i class="fa-solid fa-file"></i> Dépot de protocole</h2>
      <label for="titre"><i class="fa-solid fa-heading"></i> Titre du protocole</label>
      <input type="text" name="titre" id="titre" required>

      <label for="description"><i class="fa-solid fa-file-alt"></i> Description</label>
      <textarea name="description" id="description" rows="5" required></textarea>

      <label for="fichier"><i class="fa-solid fa-file-pdf"></i> Fichier PDF</label>
      <small style="color:#666;"><i class="fa-solid fa-info-circle"></i> Taille maximale autorisée : 20 Mo</small>
      <input type="file" name="fichier" id="fichier" accept=".pdf" required>

      <div class="button-container">
        <button type="submit"><i class="fa-solid fa-upload"></i> Déposer</button>
      </div>
      <!-- <div class="back-button">
        <a href="../accueil.php">← Retour au tableau de bord</a>
      </div> -->

      <?php if ($success): ?>
        <div class="message success"><?= htmlspecialchars($success) ?></div>
      <?php elseif ($error): ?>
        <div class="message error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
    </form>
  </div>
</main>
</body>
</html>
