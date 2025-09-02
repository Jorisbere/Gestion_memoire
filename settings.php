<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int) $_SESSION['user_id'];

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $location = $_POST['location'] ?? '';
    $bio = $_POST['bio'] ?? '';
    $birthdate = $_POST['birthdate'] ?? null;
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $password_hash = null;

    // Vérification du mot de passe
    if (!empty($new_password)) {
        if ($new_password === $confirm_password) {
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        } else {
            die("⚠️ Les mots de passe ne correspondent pas.");
        }
    }

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

    // Construction dynamique de la requête SQL
    $fields = "username = ?, email = ?, location = ?, bio = ?, birthdate = ?";
    $params = [$username, $email, $location, $bio, $birthdate];
    $types = "sssss";

    if (isset($avatar_path)) {
        $fields .= ", avatar = ?";
        $params[] = $avatar_path;
        $types .= "s";
    }

    if ($password_hash) {
        $fields .= ", password = ?";
        $params[] = $password_hash;
        $types .= "s";
    }

    $fields .= " WHERE id = ?";
    $params[] = $user_id;
    $types .= "i";

    $sql = "UPDATE users SET $fields";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
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
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<div class="sidebar">
  <h2>Mon Compte</h2>
  <a href="profile.php"><i class="fa-solid fa-user"></i> Profil</a>
  <a href="settings.php"><i class="fa-solid fa-cog"></i> Paramètres</a>
  <a href="logout.php"><i class="fa-solid fa-door-open"></i> Déconnexion</a>
</div>

<header>
  <h1>Modifier mon profil</h1>
</header>

<main class="settings-container">
  <form method="POST" enctype="multipart/form-data" class="form-profile">
    <div class="form-group">
      <label for="username"><i class="fa-solid fa-user"></i> Nom d'utilisateur :</label>
      <input type="text" name="username" id="username" value="<?= htmlspecialchars($user['username'] ?? '') ?>" required />
    </div>

    <div class="form-group">
      <label for="email"><i class="fa-solid fa-envelope"></i> Email :</label>
      <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required />
    </div>

    <div class="form-group">
      <label for="location"><i class="fa-solid fa-map-marker-alt"></i> Localisation :</label>
      <input type="text" name="location" id="location" value="<?= htmlspecialchars($user['location'] ?? '') ?>" />
    </div>

    <div class="form-group">
      <label for="birthdate"><i class="fa-solid fa-calendar-alt"></i> Date de naissance :</label>
      <input type="date" name="birthdate" id="birthdate" value="<?= htmlspecialchars($user['birthdate'] ?? '') ?>" />
    </div>

    <div class="form-group">
      <label for="bio"><i class="fa-solid fa-user-edit"></i> Biographie :</label>
      <textarea name="bio" id="bio"><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
    </div>

    <div class="form-group">
      <label for="avatar"><i class="fa-solid fa-image"></i> Photo de profil :</label>
      <input type="file" name="avatar" id="avatar" accept="image/*" />
      <?php if (!empty($user['avatar'])): ?>
        <div class="avatar-preview">
          <img src="<?= htmlspecialchars($user['avatar']) ?>" alt="Avatar actuel" style="max-width:100px; border-radius:50%; margin-top:10px;" />
        </div>
      <?php endif; ?>
    </div>

    <div class="form-group">
      <label for="new_password"><i class="fa-solid fa-lock"></i> Nouveau mot de passe :</label>
      <input type="password" name="new_password" id="new_password" placeholder="Laisser vide pour ne pas changer" />
    </div>

    <div class="form-group">
      <label for="confirm_password"><i class="fa-solid fa-lock"></i> Confirmer le mot de passe :</label>
      <input type="password" name="confirm_password" id="confirm_password" />
    </div>

    <button type="submit" class="btn-save"><i class="fa-solid fa-floppy-disk"></i> Enregistrer les modifications</button>
    <a href="javascript:history.back()" class="btn-back">← Retour</a>
  </form>
</main>

</body>
</html>
