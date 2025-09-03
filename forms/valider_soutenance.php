<?php
// Ce script ne doit faire aucune redirection ni stockage de notification en session.
// Il doit simplement retourner un JSON pour que la page admin_soutenance.php affiche la notification dynamiquement via JS
// et permettre d'actualiser automatiquement la page via JS après validation ou rejet.

// Désactiver complètement l'affichage des erreurs
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Démarrer la session si nécessaire
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Définir le header JSON immédiatement
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

// Fonction pour retourner une réponse JSON et arrêter l'exécution
function returnJsonResponse($success, $message, $data = []) {
    $response = array_merge([
        'success' => $success,
        'message' => $message
    ], $data);
    
    echo json_encode($response);
    exit;
}

try {
    // Vérifier la session
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'DM') {
        returnJsonResponse(false, "⛔ Accès non autorisé. Seuls les DM peuvent valider les soutenances.");
    }

    // Inclure les fichiers nécessaires
    require_once '../includes/db.php';
    require_once '../includes/mailer.php';

    // Récupérer et valider les paramètres
    $id = intval($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if (!$id || !in_array($action, ['valider', 'rejeter'])) {
        returnJsonResponse(false, "❌ Paramètres invalides.");
    }

    $etat = $action === 'valider' ? 'valide' : 'rejete';
    
    // Mettre à jour la demande de soutenance
    $stmt = $conn->prepare("UPDATE demandes_soutenance SET etat_validation = ? WHERE id = ?");
    $stmt->bind_param("si", $etat, $id);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        returnJsonResponse(false, "❌ Aucune demande mise à jour. Vérifiez l'ID ou l'état.");
    }

    // Récupérer les informations de la demande
    $sql = "SELECT ds.*, u.email, u.username AS etudiant, ds.titre, ds.date_demande
            FROM demandes_soutenance ds
            JOIN users u ON ds.user_id = u.id
            WHERE ds.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $demande = $result->fetch_assoc();

    if (!$demande || !filter_var($demande['email'], FILTER_VALIDATE_EMAIL)) {
        returnJsonResponse(false, "⚠️ Demande mise à jour mais email introuvable.");
    }

    // Préparer l'email
    $email = $demande['email'];
    $nom = $demande['etudiant'] ?? 'Étudiant';
    $titre = $demande['titre'] ?? 'Demande de soutenance';
    $date_demande = date("d/m/Y H:i", strtotime($demande['date_demande']));
    $encadrant = $demande['encadrant'] ?? 'l\'encadrant';

    $etat_label = ($action === 'valider') ? 'validée' : 'rejetée';
    $sujet = ($action === 'valider') ? '📄 Votre demande de soutenance a été validée' : '❌ Votre demande de soutenance a été rejetée';

    $corps = "<p>Bonjour <strong>{$nom}</strong>,</p>
    <p>Votre demande de soutenance intitulée <em>\"{$titre}\"</em> déposée le <strong>{$date_demande}</strong> a été <strong>{$etat_label}</strong> par <strong>{$encadrant}</strong>.</p>";

    if ($action === 'valider') {
        $corps .= "<p>Vous pouvez maintenant procéder à la planification de votre soutenance.</p>";
    } else {
        $corps .= "<p>Veuillez contacter votre encadrant pour plus de détails.</p>";
    }

    $corps .= "<p>Merci de consulter votre espace étudiant pour la suite.</p>
    <p>Cordialement,<br>L'équipe académique</p>";

    // Envoyer l'email
    $emailEnvoye = envoyerEmail($email, $sujet, $corps, true);

    // Retourner la réponse
    if ($emailEnvoye) {
        returnJsonResponse(true, "✅ Demande mise à jour et email envoyé à $email", [
            'etat_validation' => $etat,
            'id_demande' => $id,
            'action' => $action,
            'refresh' => true
        ]);
    } else {
        returnJsonResponse(false, "⚠️ Demande mise à jour mais échec d'envoi de l'email à $email", [
            'etat_validation' => $etat,
            'id_demande' => $id,
            'action' => $action,
            'refresh' => true
        ]);
    }

} catch (Exception $e) {
    error_log("Erreur dans valider_soutenance.php: " . $e->getMessage());
    returnJsonResponse(false, "❌ Erreur serveur: " . $e->getMessage());
} catch (Error $e) {
    error_log("Erreur fatale dans valider_soutenance.php: " . $e->getMessage());
    returnJsonResponse(false, "❌ Erreur fatale: " . $e->getMessage());
}
?>
