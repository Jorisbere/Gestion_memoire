<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$stmt = $conn->prepare("SELECT username, email, location, avatar, bio, birthdate FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit();
}

$username = htmlspecialchars($user['username']);
$email = htmlspecialchars($user['email']);
$location = htmlspecialchars($user['location'] ?? 'Non renseignée');
$bio = htmlspecialchars($user['bio'] ?? 'Aucune biographie');
$birthdate = $user['birthdate'] ? date('d/m/Y', strtotime($user['birthdate'])) : 'Non renseignée';
$avatar = !empty($user['avatar']) ? htmlspecialchars($user['avatar']) : 'assets/images/utilisateur.png';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Mon Profil</title>
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
  <h1>Mon Profil</h1>
</header>

<main class="profile-container">
  <section class="profile-card">
    <img src="<?= $avatar ?>" alt="Photo de profil" class="avatar" />
    <h2><?= $username ?></h2>
    <p>Email : <?= $email ?></p>
    <p>Localisation : <?= $location ?></p>
    <p>Date de naissance : <?= $birthdate ?></p>
    <p>Biographie : <?= $bio ?></p>
    <a href="javascript:history.back()" class="btn-back">← Retour</a>
    <a href="settings.php" class="btn">Modifier le profil</a>
  </section>
</main>

</body>
</html>
