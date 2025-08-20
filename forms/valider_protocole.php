<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$id = intval($_POST['id_protocole'] ?? 0);
$action = $_POST['action'] ?? '';
$dm_id = intval($_POST['dm_id'] ?? 0);

if ($id && in_array($action, ['valider', 'rejeter'])) {
    if ($action === 'valider' && $dm_id) {
        $stmt = $conn->prepare("UPDATE protocoles SET etat_validation = 'valide', dm_id = ? WHERE id = ?");
        $stmt->bind_param("ii", $dm_id, $id);
    } else {
        $stmt = $conn->prepare("UPDATE protocoles SET etat_validation = 'rejete' WHERE id = ?");
        $stmt->bind_param("i", $id);
    }

    $stmt->execute();
    $_SESSION['notification'] = "Protocole mis à jour avec succès.";
}

header("Location: protocoles_consultation.php");
exit();
