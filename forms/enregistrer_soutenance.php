<?php
// Ce script ne doit faire aucune redirection ni stockage de notification en session.
// Il doit simplement retourner un JSON pour que la page programmer_soutenance.php affiche la notification dynamiquement via JS
// et permettre d'actualiser automatiquement la page via JS après programmation.

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
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'administrateur') {
        returnJsonResponse(false, "⛔ Accès non autorisé. Seuls les administrateurs peuvent programmer les soutenances.");
    }

    // Inclure les fichiers nécessaires
    require_once '../includes/db.php';
    require_once '../includes/mailer.php';

    // Récupérer et valider les paramètres
    $id = intval($_POST['id'] ?? 0);
    $date = $_POST['date'] ?? '';
    $heure = $_POST['heure'] ?? '';
    $salle = trim($_POST['salle'] ?? '');

    if (!$id || !$date || !$heure || !$salle) {
        returnJsonResponse(false, "❌ Tous les champs sont obligatoires.");
    }

    // Valider le format de la date et de l'heure
    $datetime = $date . ' ' . $heure;
    if (!strtotime($datetime)) {
        returnJsonResponse(false, "❌ Format de date ou d'heure invalide.");
    }

    // Vérifier que la date n'est pas dans le passé
    if (strtotime($datetime) < time()) {
        returnJsonResponse(false, "❌ La date de soutenance ne peut pas être dans le passé.");
    }

    // Mettre à jour la demande de soutenance
    $stmt = $conn->prepare("UPDATE demandes_soutenance SET date_soutenance = ?, salle = ? WHERE id = ?");
    $stmt->bind_param("ssi", $datetime, $salle, $id);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        returnJsonResponse(false, "❌ Aucune soutenance mise à jour. Vérifiez l'ID ou l'état.");
    }

    // Récupérer les informations de la demande pour l'email et confirmation
    $sql = "SELECT ds.*, u.email, u.username AS etudiant, ds.titre, ds.encadrant
            FROM demandes_soutenance ds
            JOIN users u ON ds.user_id = u.id
            WHERE ds.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $demande = $result->fetch_assoc();

    if (!$demande || !filter_var($demande['email'], FILTER_VALIDATE_EMAIL)) {
        returnJsonResponse(false, "⚠️ Soutenance programmée mais email introuvable.");
    }

    // Préparation et envoi de l'email de notification
    $email = $demande['email'];
    $nom = $demande['etudiant'] ?? 'Étudiant';
    $titre = $demande['titre'] ?? 'Mémoire';
    $encadrant = $demande['encadrant'] ?? 'l\'encadrant';
    $date_formatted = date("d/m/Y", strtotime($date));
    $heure_formatted = date("H:i", strtotime($heure));

    $sujet = '📅 Votre soutenance a été programmée';
    $corps = "<p>Bonjour <strong>{$nom}</strong>,</p>
    <p>Votre soutenance de mémoire intitulée <em>\"{$titre}\"</em> a été programmée avec succès.</p>
    
    <div style='background-color: #f8f9fa; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #0078D7;'>
        <h3 style='color: #0078D7; margin-top: 0;'>📋 Détails de la programmation</h3>
        <p><strong>📅 Date :</strong> {$date_formatted}</p>
        <p><strong>🕐 Heure :</strong> {$heure_formatted}</p>
        <p><strong>🏫 Salle :</strong> {$salle}</p>
        <p><strong>👨‍🏫 Encadrant :</strong> {$encadrant}</p>
    </div>
    
    <p><strong>⚠️ Important :</strong></p>
    <ul>
        <li>Présentez-vous 15 minutes avant l'heure prévue</li>
        <li>Apportez votre support de présentation (clé USB, ordinateur portable)</li>
        <li>Préparez votre défense orale</li>
    </ul>
    
    <p>Merci de consulter votre espace étudiant pour plus d'informations.</p>
    <p>Cordialement,<br>L'équipe académique</p>";

    // Envoi de l'email
    $emailEnvoye = envoyerEmail($email, $sujet, $corps, true);

    // Retourner la réponse
    if ($emailEnvoye) {
        returnJsonResponse(true, "✅ Soutenance programmée avec succès et email envoyé à $email", [
            'id_demande' => $id,
            'date_soutenance' => $datetime,
            'salle' => $salle,
            'etudiant' => $demande['etudiant'],
            'titre' => $demande['titre'],
            'encadrant' => $demande['encadrant'],
            'email_envoye' => true,
            'refresh' => true
        ]);
    } else {
        returnJsonResponse(false, "⚠️ Soutenance programmée mais échec d'envoi de l'email à $email", [
            'id_demande' => $id,
            'date_soutenance' => $datetime,
            'salle' => $salle,
            'etudiant' => $demande['etudiant'],
            'titre' => $demande['titre'],
            'encadrant' => $demande['encadrant'],
            'email_envoye' => false,
            'refresh' => true
        ]);
    }

} catch (Exception $e) {
    error_log("Erreur dans enregistrer_soutenance.php: " . $e->getMessage());
    returnJsonResponse(false, "❌ Erreur serveur: " . $e->getMessage());
} catch (Error $e) {
    error_log("Erreur fatale dans enregistrer_soutenance.php: " . $e->getMessage());
    returnJsonResponse(false, "❌ Erreur fatale: " . $e->getMessage());
}
?>
