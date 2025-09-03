<?php
require_once('includes/mailer.php');

$email = "jorisbere@gmail.com";
$sujet = "Test direct";
$corps = "<p>Ceci est un test direct depuis le script.</p>";

envoyerEmail($email, $sujet, $corps, true);
echo "<div style='color:green;'>✅ Script d'envoi direct exécuté.</div>";