<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$id = $_GET['id'] ?? null;

if ($id) {
    $stmt = $pdo->prepare("DELETE FROM declarant WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $user_id]);
    $_SESSION['success'] = "🗑️ Déclarant supprimé avec succès.";
}

header("Location: liste_declarants.php");
exit();
