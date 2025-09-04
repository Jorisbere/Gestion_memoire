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

$dm_stmt = $conn->prepare("SELECT id, username FROM users WHERE role = 'DM'");
$dm_stmt->execute();
$dm_result = $dm_stmt->get_result();

$dms = [];
while ($dm = $dm_result->fetch_assoc()) { $dms[] = $dm; }

$check_stmt = $conn->prepare("SELECT etat_validation FROM demandes_soutenance WHERE user_id = ? ORDER BY id DESC LIMIT 1");
$check_stmt->bind_param("i", $user_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();
$existing = $check_result->fetch_assoc();
$check_stmt->close();

$depot_autorise = true;
if ($existing && $existing['etat_validation'] === 'en_attente') {
    $depot_autorise = false;
    $error = "Vous avez déjà une demande de soutenance en attente. Veuillez attendre sa validation ou son rejet avant d’en soumettre une nouvelle.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $depot_autorise) {
    $titre = trim($_POST['titre']);
    $encadrant = trim($_POST['encadrant']);
    $session = $_POST['session'] ?? '';
    $fichier = $_FILES['fichier'];

    if (empty($titre) || empty($session) || empty($fichier['name'])) {
        $error = "Tous les champs sont obligatoires.";
    } else {
        $uploadDir = '../uploads/soutenances/';
        if (!is_dir($uploadDir)) { mkdir($uploadDir, 0777, true); }
        $fileName = basename($fichier['name']);
        $targetPath = $uploadDir . time() . '_' . $fileName;
        if (move_uploaded_file($fichier['tmp_name'], $targetPath)) {
            $stmt = $conn->prepare("INSERT INTO demandes_soutenance (user_id, titre, encadrant, session_souhaitee, fichier_path, etat_validation, date_demande) VALUES (?, ?, ?, ?, ?, 'en_attente', NOW())");
            $stmt->bind_param("issss", $user_id, $titre, $encadrant, $session, $targetPath);
            if ($stmt->execute()) { $success = "Demande de soutenance envoyée avec succès."; } else { $error = "Erreur lors de l'enregistrement."; }
            $stmt->close();
        } else { $error = "Échec du téléchargement du fichier."; }
    }
}

$last_stmt = $conn->prepare("SELECT titre, etat_validation, fichier_path, date_demande FROM demandes_soutenance WHERE user_id = ? ORDER BY id DESC LIMIT 1");
$last_stmt->bind_param("i", $user_id);
$last_stmt->execute();
$last_result = $last_stmt->get_result();
$last_demande = $last_result->fetch_assoc();
$last_stmt->close();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Demande de Soutenance</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <script>
function confirmerDepot() { return confirm("Êtes-vous sûr de vouloir envoyer cette demande de soutenance ?"); }
</script>
  <style>
    body { font-family: 'Segoe UI', sans-serif; background: linear-gradient(90deg, #49c4e6ff 0%, #e5edf7ff 100%); padding: 40px; color: #333; }
    /* élargir la plage où le padding gauche est appliqué pour éviter le chevauchement */
    @media (min-width: 768px) { .gm-main { padding-left: 280px; } }
    @media (max-width: 767px) { .gm-main { padding-top: 80px; } }

    form { background:rgb(229, 232, 232); padding: 30px; border-radius: 16px; box-shadow: 0 8px 24px rgba(0,0,0,0.1); max-width: 700px; margin: auto; }
    h2 { text-align: center; margin-bottom: 25px; font-size: 26px; color: #0078D7; }
    label { display: block; margin-top: 20px; font-weight: 600; color: #333; font-size: 15px; }
    input[type="text"], select, input[type="file"] { width: 100%; padding: 12px; margin-top: 8px; border-radius: 10px; border: 1px solid #ccc; font-size: 15px; background:rgb(229, 232, 232); transition: border-color 0.3s ease; }
    input:focus, select:focus { border-color: #0078D7; outline: none; }
    input[type="file"] { background-color:rgb(229, 232, 232); cursor: pointer; }
    .button-container { display: flex; justify-content: center; margin-top: 30px; padding: 12px; }
    button { padding: 14px 28px; background: #0078D7; color: #fff; border: none; border-radius: 10px; cursor: pointer; font-size: 16px; transition: background 0.3s ease; }
    button:hover { background: #0056a3; }
    .message { margin-top: 20px; padding: 14px; border-radius: 10px; font-weight: 500; text-align: center; }
    .success { background: #d4edda; color: #155724; }
    .error { background:rgb(151, 206, 248); color: rgb(200, 47, 62); }
    .back-button { text-align: center; margin-bottom: 20px; }
    .back-button a { text-decoration: none; color: #0078D7; font-weight: bold; }
  </style>
</head>
<body>
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="gm-main">
    <form method="POST" enctype="multipart/form-data" onsubmit="return confirmerDepot();">
      <?php if ($last_demande): ?>
      <?php
        $etat = strtolower($last_demande['etat_validation']);
        $badge = '';
        switch ($etat) {
          case 'valide': $badge = '<span style="color:green; font-weight:bold;">🟢 Validé</span>'; break;
          case 'rejete': $badge = '<span style="color:red; font-weight:bold;">🔴 Rejeté</span>'; break;
          default: $badge = '<span style="color:orange; font-weight:bold;">🟡 En attente</span>'; break;
        }
      ?>
      <?php endif; ?>

      <h2><i class="fa-solid fa-file"></i> Demande de Soutenance</h2>

      <label for="titre">Titre du mémoire</label>
      <input type="text" name="titre" id="titre" required>

      <label for="encadrant">Encadrant (DM)</label>
      <select name="encadrant" id="encadrant" required>
        <option value="">-- Sélectionner un DM --</option>
        <?php foreach ($dms as $dm): ?>
          <option value="<?= htmlspecialchars($dm['username']) ?>"><?= htmlspecialchars($dm['username']) ?></option>
        <?php endforeach; ?>
      </select>

      <label for="session">Session souhaitée</label>
      <select name="session" id="session" required>
        <option value="">-- Choisir une session --</option>
        <option value="normale">Session normale</option>
        <option value="anticipe">Session anticipée</option>
      </select>

      <label for="fichier">Fichier de soutenance (PDF)</label>
      <small style="color:#666;">Taille maximale autorisée : 20 Mo</small>
      <input type="file" name="fichier" id="fichier" accept=".pdf" required>

      <div class="button-container">
        <button type="submit">Envoyer la demande</button>
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
</body>
</html>
