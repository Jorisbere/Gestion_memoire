<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $location = $_POST['location'] ?? '';
    $bio = $_POST['bio'] ?? '';
    $birthdate = $_POST['birthdate'] ?? null;

    // Gestion de l'image
    if (!empty($_FILES['avatar']['name'])) {
    $target_dir = "uploads/";
    $filename = basename($_FILES["avatar"]["name"]);
    $target_file = $target_dir . time() . "_" . $filename;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
    if (in_array($imageFileType, $allowed_types)) {
        if (move_uploaded_file($_FILES["avatar"]["tmp_name"], $target_file)) {
            $avatar_path = $target_file;
        }
    }
}


    // Mise à jour SQL
    $sql = "UPDATE users SET username = ?, email = ?, location = ?, bio = ?, birthdate = ?" . (isset($avatar_path) ? ", avatar = ?" : "") . " WHERE id = ?";
    $stmt = $conn->prepare($sql);

   if (isset($avatar_path)) {
    $sql = "UPDATE users SET username = ?, email = ?, location = ?, bio = ?, birthdate = ?, avatar = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssi", $username, $email, $location, $bio, $birthdate, $avatar_path, $user_id);
} else {
    $sql = "UPDATE users SET username = ?, email = ?, location = ?, bio = ?, birthdate = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssi", $username, $email, $location, $bio, $birthdate, $user_id);
}

    $stmt->execute();
    $stmt->close();

    header("Location: profile.php");
    exit();
}

// Récupération des données actuelles
$stmt = $conn->prepare("SELECT username, email, location, bio, birthdate, avatar FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Modifier le Profil</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="sidebar">
  <h2>Mon Compte</h2>
  <a href="profile.php">Profil</a>
  <a href="settings.php">Paramètres</a>
  <a href="logout.php">Déconnexion</a>
</div>

<header>
  <h1>Modifier mon profil</h1>
</header>

<main class="settings-container">
  <h1>Modifier mon profil</h1>
  <form method="POST" enctype="multipart/form-data" class="form-profile">
    <div class="form-group">
      <label for="username">👤 Nom d'utilisateur :</label>
      <input type="text" name="username" id="username" value="<?= htmlspecialchars($user['username']) ?>" required />
    </div>

    <div class="form-group">
      <label for="email">📧 Email :</label>
      <input type="email" name="email" id="email" value="<?= htmlspecialchars($user['email']) ?>" required />
    </div>

    <div class="form-group">
      <label for="location">📍 Localisation :</label>
      <input type="text" name="location" id="location" value="<?= htmlspecialchars($user['location'] ?? '') ?>" />
    </div>

    <div class="form-group">
      <label for="birthdate">🎂 Date de naissance :</label>
      <input type="date" name="birthdate" id="birthdate" value="<?= htmlspecialchars($user['birthdate'] ?? '') ?>" />
    </div>

    <div class="form-group">
      <label for="bio">📝 Biographie :</label>
      <textarea name="bio" id="bio"><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
    </div>

    <div class="form-group">
      <label for="avatar">🖼️ Photo de profil (format jpg, jpeg, png, gif):</label>
      <input type="file" name="avatar" id="avatar" accept="image/*" />
      <?php if (!empty($user['avatar'])): ?>
        <div class="avatar-preview">
          <img src="<?= htmlspecialchars($user['avatar']) ?>" alt="Avatar actuel" style="max-width:100px; border-radius:50%; margin-top:10px;" />
        </div>
      <?php endif; ?>
    </div>
    
    <button type="submit" class="btn-save">💾 Enregistrer les modifications</button>
    <a href="javascript:history.back()" class="btn-back">← Retour</a>
  </form>
</main>


</body>
</html>
