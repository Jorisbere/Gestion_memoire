<?php
session_start();
require_once '../includes/db.php'; // Connexion à la base de données

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
echo "<p>Votre ID utilisateur : " . htmlspecialchars($user_id) . "</p>";

// Récupération du protocole avec nom du DM
function getProtocoleInfo($user_id, $conn) {
    $query = "
        SELECT p.etat_validation, u.username AS dm_nom
        FROM protocoles p
        LEFT JOIN users u ON p.dm_id = u.id
        WHERE p.user_id = ?
        ORDER BY p.id DESC
        LIMIT 1
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc() ?? ['etat_validation' => 'non déposé', 'dm_nom' => null];
}

// Récupération de la soutenance avec date et salle
function getSoutenanceInfo($user_id, $conn) {
    $query = "
        SELECT etat_validation, date_soutenance, salle
        FROM demandes_soutenance
        WHERE user_id = ?
        ORDER BY id DESC
        LIMIT 1
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc() ?? ['etat_validation' => 'non déposé', 'date_soutenance' => null, 'salle' => null];
}

// Récupération du statut mémoire
function getStatus($table, $user_id, $conn) {
    $query = "SELECT etat_validation FROM $table WHERE user_id = ? ORDER BY id DESC LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row ? $row['etat_validation'] : 'non déposé';
}

$protocole_info = getProtocoleInfo($user_id, $conn);
$statut_protocole = $protocole_info['etat_validation'];
$dm_nom = $protocole_info['dm_nom'];

$soutenance_info = getSoutenanceInfo($user_id, $conn);
$statut_soutenance = $soutenance_info['etat_validation'];
$date_soutenance = $soutenance_info['date_soutenance'];
$salle_soutenance = $soutenance_info['salle'];

$statut_memoire = getStatus('memoires', $user_id, $conn);

// Fonction pour afficher le badge
function renderBadge($status) {
    switch ($status) {
        case 'valide':
            return "<span class='badge success'>Validé</span>";
        case 'rejete':
            return "<span class='badge error'>Rejeté</span>";
        case 'en_attente':
            return "<span class='badge pending'>En attente</span>";
        default:
            return "<span class='badge neutral'>Non déposé</span>";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Statut des documents</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f4f6f8;
            padding: 40px;
        }

        .container {
            max-width: 600px;
            margin: auto;
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 6px 18px rgba(0,0,0,0.1);
        }

        h2 {
            text-align: center;
            margin-bottom: 30px;
            color: #0078D7;
        }

        .doc-status {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            font-size: 16px;
        }

        .badge {
            padding: 6px 12px;
            border-radius: 8px;
            font-weight: 600;
            text-transform: capitalize;
        }*

        .back-button {
      text-align: center;
      margin-bottom: 20px;
    }

    .back-button a {
      text-decoration: none;
      color: #0078D7;
      font-weight: bold;
    }

        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .pending { background: #fff3cd; color: #856404; }
        .neutral { background: #e2e3e5; color: #6c757d; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Infos</h2>

        <div class="doc-status">
            <span>Protocole :</span>
            <?= renderBadge($statut_protocole) ?>
        </div>

        <?php if ($statut_protocole === 'valide' && !empty($dm_nom)): ?>
        <div class="doc-status">
            <span>DM attribué :</span>
            <span class="badge success"><?= htmlspecialchars($dm_nom) ?></span>
        </div>
        <?php endif; ?>

        <div class="doc-status">
            <span>Demande de soutenance :</span>
            <?= renderBadge($statut_soutenance) ?>
        </div>

        <?php if ($statut_soutenance === 'valide' && !empty($date_soutenance) && !empty($salle_soutenance)): ?>
        <div class="doc-status">
            <span>Date de soutenance :</span>
            <span class="badge pending"><?= htmlspecialchars(date("d/m/Y H:i", strtotime($date_soutenance))) ?></span>
        </div>
        <div class="doc-status">
            <span>Salle :</span>
            <span class="badge pending"><?= htmlspecialchars($salle_soutenance) ?></span>
        </div>
        <?php endif; ?>

        <div class="doc-status">
            <span>Mémoire final :</span>
            <?= renderBadge($statut_memoire) ?>
        </div>

        <div class="back-button">
            <a href="../dashboard.php">← Retour au tableau de bord</a>
        </div>
    </div>
</body>
</html>
