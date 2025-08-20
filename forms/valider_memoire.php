<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'DM') {
    header("Location: ../login.php");
    exit();
}

$id = intval($_POST['id_memoire'] ?? 0);
$action = $_POST['action'] ?? '';

if ($id && in_array($action, ['valider', 'rejeter'])) {
    $etat = $action === 'valider' ? 'valide' : 'rejete';
    $stmt = $conn->prepare("UPDATE memoires SET etat_validation = ? WHERE id = ?");
    $stmt->bind_param("si", $etat, $id);
    $stmt->execute();
    $_SESSION['notification'] = "Mémoire mis à jour : $etat.";
}

header("Location: memoires_consultation.php");
exit();
