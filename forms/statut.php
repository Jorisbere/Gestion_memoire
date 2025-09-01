<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Récupération du protocole avec nom du DM, titre et date
function getProtocoleInfo($user_id, $conn) {
    $query = "
        SELECT p.etat_validation, p.titre, p.date_depot, u.username AS dm_nom
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
    return $result->fetch_assoc() ?? [
        'etat_validation' => 'non déposé',
        'dm_nom' => null,
        'titre' => null,
        'date_depot' => null
    ];
}

// Récupération de la soutenance avec titre, date et salle
function getSoutenanceInfo($user_id, $conn) {
    $query = "
        SELECT etat_validation, titre, date_soutenance, salle
        FROM demandes_soutenance
        WHERE user_id = ?
        ORDER BY id DESC
        LIMIT 1
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc() ?? [
        'etat_validation' => 'non déposé',
        'titre' => null,
        'date_soutenance' => null,
        'salle' => null
    ];
}

// Récupération du mémoire avec titre et date
function getMemoireInfo($user_id, $conn) {
    $query = "
        SELECT etat_validation, titre, date_depot
        FROM memoires
        WHERE user_id = ?
        ORDER BY id DESC
        LIMIT 1
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc() ?? [
        'etat_validation' => 'non déposé',
        'titre' => null,
        'date_depot' => null
    ];
}

$protocole_info = getProtocoleInfo($user_id, $conn);
$statut_protocole = $protocole_info['etat_validation'];

$soutenance_info = getSoutenanceInfo($user_id, $conn);
$statut_soutenance = $soutenance_info['etat_validation'];

$memoire_info = getMemoireInfo($user_id, $conn);
$statut_memoire = $memoire_info['etat_validation'];

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
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f4f6f8;
            padding: 40px;
            color: #333;
        }

        .container {
            max-width: 800px;
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

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: #fff;
        }

        th, td {
            padding: 12px 16px;
            border-bottom: 1px solid #eee;
            text-align: left;
            vertical-align: top;
        }

        th {
            background: #0078D7;
            color: #fff;
        }

        .badge {
            padding: 6px 12px;
            border-radius: 8px;
            font-weight: 600;
            text-transform: capitalize;
            display: inline-block;
        }

        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .pending { background: #fff3cd; color: #856404; }
        .neutral { background: #e2e3e5; color: #6c757d; }

        .back-button {
            text-align: center;
            margin-top: 30px;
        }

        .back-button a {
            text-decoration: none;
            color: #0078D7;
            font-weight: bold;
        }

        td strong {
            color: #0078D7;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>📋 Statut de vos documents</h2>

        <table>
            <thead>
                <tr>
                    <th>Document</th>
                    <th>Statut</th>
                    <th>Informations</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Protocole</td>
                    <td><?= renderBadge($statut_protocole) ?></td>
                    <td>
                        <?php if ($statut_protocole === 'valide'): ?>
                            Titre : <strong><?= !empty($protocole_info['titre']) ? htmlspecialchars($protocole_info['titre']) : '—' ?></strong><br>
                            <?php if (!empty($protocole_info['date_depot'])): ?>
                                Date : <strong><?= date("d/m/Y H:i", strtotime($protocole_info['date_depot'])) ?></strong><br>
                            <?php endif; ?>
                            <?php if (!empty($protocole_info['dm_nom'])): ?>
                                DM : <strong><?= htmlspecialchars($protocole_info['dm_nom']) ?></strong>
                            <?php endif; ?>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                </tr>

                <tr>
                    <td>Demande de soutenance</td>
                    <td><?= renderBadge($statut_soutenance) ?></td>
                    <td>
                        <?php if ($statut_soutenance === 'valide'): ?>
                            Titre : <strong><?= !empty($soutenance_info['titre']) ? htmlspecialchars($soutenance_info['titre']) : '—' ?></strong><br>
                            <?php if (!empty($soutenance_info['date_soutenance'])): ?>
                                Date : <strong><?= date("d/m/Y H:i", strtotime($soutenance_info['date_soutenance'])) ?></strong><br>
                            <?php endif; ?>
                            <?php if (!empty($soutenance_info['salle'])): ?>
                                Salle : <strong><?= htmlspecialchars($soutenance_info['salle']) ?></strong>
                            <?php endif; ?>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                </tr>

                <tr>
                    <td>Mémoire final</td>
                    <td><?= renderBadge($statut_memoire) ?></td>
                    <td>
                        <?php if ($statut_memoire === 'valide'): ?>
                            Titre : <strong><?= !empty($memoire_info['titre']) ? htmlspecialchars($memoire_info['titre']) : '—' ?></strong><br>
                            <?php if (!empty($memoire_info['date_depot'])): ?>
                                Date : <strong><?= date("d/m/Y H:i", strtotime($memoire_info['date_depot'])) ?></strong>
                            <?php endif; ?>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                </tr>
            </tbody>
        </table>

        <div class="back-button">
            <a href="../accueil.php">← Retour au tableau de bord</a>
        </div>
    </div>
</body>
</html>
