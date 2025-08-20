<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nom = $_POST['username'];
  $email = $_POST['email'];
  $localisation = $_POST['location'];

  // Ici tu pourrais enregistrer dans une base de données
  echo "<h2>Profil mis à jour !</h2>";
  echo "<p>Nom : $nom</p>";
  echo "<p>Email : $email</p>";
  echo "<p>Localisation : $localisation</p>";
}
?>
