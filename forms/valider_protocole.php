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

// 🔄 Enregistrement des actions dans historique_actions
if (isset($_POST['valider']) || isset($_POST['rejeter'])) {
    $id_demande = $_POST['id_demande'] ?? null;
    $etat_validation = isset($_POST['valider']) ? 'valide' : 'rejete';

    // Récupérer les infos de la demande
    $sql = "SELECT d.type_demande, u.nom AS etudiant, e.nom AS encadrant
            FROM demandes_soutenance d
            JOIN users u ON d.etudiant_id = u.id
            JOIN encadrements e ON d.encadrant_id = e.id
            WHERE d.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_demande);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if ($row && !empty($row['etudiant']) && !empty($row['type_demande'])) {
        $insert = $conn->prepare("INSERT INTO historique_actions (encadrant, etudiant, type_demande, etat_validation, date_action) VALUES (?, ?, ?, ?, NOW())");
        $insert->bind_param("ssss", $row['encadrant'], $row['etudiant'], $row['type_demande'], $etat_validation);
        $insert->execute();
    }
}

header("Location: protocoles_consultation.php");
exit();
