<?php
// Ce script ne doit faire aucune redirection ni stockage de notification en session.
// Il doit simplement retourner un JSON pour que la page protocoles_consultation.php affiche la notification dynamiquement via JS
// et permettre d’actualiser automatiquement la page via JS après validation ou rejet.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../includes/db.php';
require_once '../utils/email.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => "⛔ Session expirée. Veuillez vous reconnecter."
    ]);
    exit;
}

$id_protocole = intval($_POST['id_protocole'] ?? 0); 
$action = $_POST['action'] ?? ''; 
$dm_id = intval($_POST['dm_id'] ?? 0);

$etat_validation = '';
if ($action === 'valider') {
    $etat_validation = 'valide';
} elseif ($action === 'rejeter') {
    $etat_validation = 'rejete';
}

if ($id_protocole && in_array($action, ['valider', 'rejeter'])) {
    // Mise à jour du protocole
    if ($action === 'valider' && $dm_id > 0) {
        $stmt = $conn->prepare("UPDATE protocoles SET etat_validation = ?, dm_id = ? WHERE id = ?");
        $stmt->bind_param("sii", $etat_validation, $dm_id, $id_protocole);
    } else {
        $stmt = $conn->prepare("UPDATE protocoles SET etat_validation = ? WHERE id = ?");
        $stmt->bind_param("si", $etat_validation, $id_protocole);
    }
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        // Réponse AJAX en cas d'échec
        echo json_encode([
            'success' => false,
            'message' => "❌ Aucun protocole mis à jour. Vérifiez l’ID ou l’état."
        ]);
        exit;
    }
    
    // Récupération des infos de l'étudiant + DM
    $sql = "SELECT u.email, u.username AS etudiant, p.titre, p.date_depot, dm.username AS nom_dm
            FROM protocoles p
            JOIN users u ON p.user_id = u.id
            LEFT JOIN users dm ON p.dm_id = dm.id
            WHERE p.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_protocole);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user || !filter_var($user['email'], FILTER_VALIDATE_EMAIL)) {
        echo json_encode([
            'success' => false,
            'message' => "⚠️ Protocole mis à jour mais email introuvable."
        ]);
        exit;
    }

    $email = $user['email'];
    $nom = $user['etudiant'] ?? 'Étudiant';
    $titre = $user['titre'] ?? 'Mémoire';
    $date_depot = date("d/m/Y H:i", strtotime($user['date_depot']));
    $nom_dm = $user['nom_dm'] ?? null;

    // Préparation du mail
    $etat_label = ($action === 'valider') ? 'validé' : 'rejeté';
    $sujet = ($action === 'valider') ? '📄 Votre protocole a été validé' : '❌ Votre protocole a été rejeté';

    $corps = "<p>Bonjour <strong>{$nom}</strong>,</p>
    <p>Votre protocole intitulé <em>\"{$titre}\"</em> déposé le <strong>{$date_depot}</strong> a été <strong>{$etat_label}</strong>";

    if ($action === 'valider' && $nom_dm) {
        $corps .= " par <strong>{$nom_dm}</strong>";
    }

    $corps .= ".</p>
    <p>Merci de consulter votre espace étudiant pour la suite.</p>
    <p>Cordialement,<br>L'équipe académique</p>";

    // Envoi de l'email
    $emailEnvoye = envoyerEmail($email, $sujet, $corps, true, $id_protocole, $conn);

    // On retourne un JSON pour que le JS affiche la notification sur la page protocoles_consultation.php
    // et que le JS puisse rafraîchir dynamiquement la ligne du protocole ou la page entière si besoin
    if ($emailEnvoye) {
        echo json_encode([
            'success' => true,
            'message' => "✅ Protocole mis à jour et email envoyé à $email",
            'etat_validation' => $etat_validation,
            'id_protocole' => $id_protocole,
            'dm_id' => $dm_id,
            'nom_dm' => $nom_dm,
            'action' => $action,
            'refresh' => true // Indique au JS qu'il peut rafraîchir la page ou la ligne
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => "⚠️ Protocole mis à jour mais échec d’envoi à $email",
            'etat_validation' => $etat_validation,
            'id_protocole' => $id_protocole,
            'dm_id' => $dm_id,
            'nom_dm' => $nom_dm,
            'action' => $action,
            'refresh' => true // On autorise aussi le rafraîchissement même si l'email a échoué
        ]);
    }
    exit;
} else {
    echo json_encode([
        'success' => false,
        'message' => "❌ Requête invalide."
    ]);
    exit;
}
?>
