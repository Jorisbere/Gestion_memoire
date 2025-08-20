<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'administrateur') {
    header("Location: ../login.php");
    exit();
}

$id = intval($_POST['id'] ?? 0);
$date = $_POST['date'] ?? '';
$heure = $_POST['heure'] ?? '';
$salle = trim($_POST['salle'] ?? '');

if ($id && $date && $heure && $salle) {
    $datetime = $date . ' ' . $heure;
    $stmt = $conn->prepare("UPDATE demandes_soutenance SET date_soutenance = ?, salle = ? WHERE id = ?");
    $stmt->bind_param("ssi", $datetime, $salle, $id);
    $stmt->execute();
    $_SESSION['notification'] = "Soutenance programmée avec succès.";
}

header("Location: programmer_soutenance.php");
exit();
