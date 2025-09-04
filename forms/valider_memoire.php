<?php
// Retour JSON uniquement, aucune redirection ni notification en session
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once '../includes/db.php';
require_once '../includes/mailer.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'DM') {
    echo json_encode([
        'success' => false,
        'message' => "⛔ Accès non autorisé. Seuls les DM peuvent valider les mémoires."
    ]);
    exit;
}

$id = intval($_POST['id_memoire'] ?? 0);
$action = $_POST['action'] ?? '';

if (!$id || !in_array($action, ['valider', 'rejeter'])) {
    echo json_encode([
        'success' => false,
        'message' => "❌ Requête invalide."
    ]);
    exit;
}

$etat = $action === 'valider' ? 'valide' : 'rejete';

// Mise à jour du mémoire
$stmt = $conn->prepare("UPDATE memoires SET etat_validation = ? WHERE id = ?");
$stmt->bind_param("si", $etat, $id);
$stmt->execute();

if ($stmt->affected_rows === 0) {
    echo json_encode([
        'success' => false,
        'message' => "❌ Aucun mémoire mis à jour. Vérifiez l'ID ou l'état."
    ]);
    exit;
}

// Récupération des infos pour l'email
$sql = "SELECT m.titre, m.date_depot, u.email, u.username AS etudiant
        FROM memoires m
        JOIN users u ON m.user_id = u.id
        WHERE m.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$memoire = $result->fetch_assoc();

if (!$memoire || !filter_var($memoire['email'], FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false,
        'message' => "⚠️ Mémoire mis à jour mais email introuvable."
    ]);
    exit;
}

$email = $memoire['email'];
$nom = $memoire['etudiant'] ?? 'Étudiant';
$titre = $memoire['titre'] ?? 'Mémoire';
$date_depot = isset($memoire['date_depot']) ? date("d/m/Y H:i", strtotime($memoire['date_depot'])) : '';
$etat_label = ($action === 'valider') ? 'validé' : 'rejeté';

$sujet = ($action === 'valider') ? '📄 Votre mémoire final a été validé' : '❌ Votre mémoire final a été rejeté';

$corps = "<p>Bonjour <strong>{$nom}</strong>,</p>
<p>Votre mémoire intitulé <em>\"{$titre}\"</em> déposé le <strong>{$date_depot}</strong> a été <strong>{$etat_label}</strong>.</p>
<p>Merci de consulter votre espace étudiant pour la suite.</p>
<p>Cordialement,<br>L'équipe académique</p>";

$emailEnvoye = envoyerEmail($email, $sujet, $corps, true);

if ($emailEnvoye) {
    echo json_encode([
        'success' => true,
        'message' => "✅ Mémoire mis à jour et email envoyé à $email",
        'etat_validation' => $etat,
        'id_memoire' => $id,
        'action' => $action,
        'refresh' => true
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => "⚠️ Mémoire mis à jour mais échec d'envoi de l'email à $email",
        'etat_validation' => $etat,
        'id_memoire' => $id,
        'action' => $action,
        'refresh' => true
    ]);
}
exit;
