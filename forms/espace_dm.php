<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'DM') {
    header("Location: ../login.php");
    exit();
}

$dm_id = $_SESSION['user_id'];

// Récupérer le nom du DM
$stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
$stmt->bind_param("i", $dm_id);
$stmt->execute();
$result = $stmt->get_result();
$dm = $result->fetch_assoc();
$dm_nom = $dm['username'] ?? '';

// Nombre d'étudiants encadrés
$stmt = $conn->prepare("SELECT COUNT(DISTINCT user_id) AS total FROM protocoles WHERE dm_id = ?");
$stmt->bind_param("i", $dm_id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();
$nb_etudiants = $data['total'] ?? 0;

// // Protocoles en attente
// $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM protocoles WHERE dm_id = ? AND etat_validation = 'en_attente'");
// $stmt->bind_param("i", $dm_id);
// $stmt->execute();
// $result = $stmt->get_result();
// $data = $result->fetch_assoc();
// $nb_protocoles = $data['total'] ?? 0;

// Soutenances en attente
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM demandes_soutenance WHERE encadrant = ? AND etat_validation = 'en_attente'");
$stmt->bind_param("s", $dm_nom);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();
$nb_soutenances = $data['total'] ?? 0;

// Mémoires en attente
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM memoires WHERE encadrant = ? AND etat_validation = 'en_attente'");
$stmt->bind_param("s", $dm_nom);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();
$nb_memoires = $data['total'] ?? 0;

// 👉 Ajoute ce bloc ici :
$nb_soutenances_validées = getValidatedCount($conn, 'demandes_soutenance', $dm_nom);
$nb_soutenances_rejetees = getRejectedCount($conn, 'demandes_soutenance', $dm_nom);
$nb_memoires_validés = getValidatedCount($conn, 'memoires', $dm_nom);
$nb_memoires_rejetés = getRejectedCount($conn, 'memoires', $dm_nom);

// Fonction pour les validations
function getValidatedCount($conn, $table, $encadrant) {
    $allowed_tables = ['demandes_soutenance', 'memoires'];
    if (!in_array($table, $allowed_tables)) return 0;

    $query = "SELECT COUNT(*) AS total FROM `$table` WHERE encadrant = ? AND etat_validation = 'valide'";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $encadrant);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    return $data['total'] ?? 0;
}

function getRejectedCount($conn, $table, $encadrant) {
    $allowed_tables = ['demandes_soutenance', 'memoires'];
    if (!in_array($table, $allowed_tables)) return 0;

    $query = "SELECT COUNT(*) AS total FROM `$table` WHERE encadrant = ? AND etat_validation = 'rejete'";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $encadrant);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    return $data['total'] ?? 0;
}

?>


<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Espace DM</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
   j;;;;_ body {
  font-family: 'Segoe UI', sans-serif;
  background: #f0f4f8;
  margin: 0;
  padding: 0;
}

    h2 {
      text-align: center;
      color: #1e2a38;
      margin-bottom: 30px;
    }

    .dashboard {
       max-width: 1200px;
      margin: auto;
      background: #fff;
      padding: 40px 20px;
      border-radius: 16px;
      box-shadow: 0 8px 24px rgba(0,0,0,0.1);
    }

    .stat {
      display: flex;
      justify-content: space-between;
      margin-bottom: 20px;
      font-size: 18px;
    }

    .stat span {
      font-weight: bold;
    }

    .stat-box {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  margin-bottom: 20px;
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.05);
  color: #fff;
  font-size: 18px;
  font-weight: 500;
}

.stat-box.validée {
  background: linear-gradient(135deg, #4CAF50, #81C784);
}

.stat-box.rejetée {
  background: linear-gradient(135deg, #F44336, #E57373);
}

.stat-box.attente {
  background: linear-gradient(135deg, #FFC107, #FFD54F);
}

.stat-box.memoire {
  background: linear-gradient(135deg, #2196F3, #64B5F6);
}

.stat-box.soutenance {
  background: linear-gradient(135deg, #9C27B0, #BA68C8);
}

.stat-box .label {
  display: flex;
  align-items: center;
  gap: 10px;
}

.stat-box .label i {
  font-size: 22px;
}

.stat-box .value {
  font-size: 26px;
  font-weight: bold;
}

.stat-box.encadres {
  background: linear-gradient(135deg, #00BCD4, #4DD0E1);
}


.chart-card {
  background: #ffffff;
  border-radius: 16px;
  padding: 30px;
  box-shadow: 0 8px 24px rgba(0,0,0,0.1);
  margin-top: 30px;
  text-align: center;
}

.recent-actions {
  background: #f4f3f1ff;
  border-left: 6px solid #f1edd4ff;
  padding: 20px;
  border-radius: 10px;
  margin-top: 30px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}

.recent-actions h3 {
  margin-bottom: 15px;
  color: #e65100;
}

.recent-actions ul {
  list-style: none;
  padding: 0;
}

.recent-actions li {
  margin-bottom: 10px;
  font-size: 16px;
  color: #4e342e;
}


    .links {
      margin-top: 30px;
      text-align: center;
    }

    .links a {
      display: inline-block;
      margin: 10px;
      padding: 12px 20px;
      background: #0078D7;
      color: #fff;
      text-decoration: none;
      border-radius: 8px;
      font-weight: bold;
      transition: background 0.3s ease;
    }

    .links a:hover {
      background: #0056a3;
    }

    .back-button {
      text-align: center;
      margin-top: 30px;
    }

    .back-button a {
      text-decoration: none;
      color: #0078D7;
      font-weight: bold;
    }

    .stat-container {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  justify-content: center;
  margin-top: 20px;
}

.stat-box {
  flex: 1 1 160px;
  max-width: 180px;
  padding: 10px 14px;
  border-radius: 10px;
  color: white;
  font-weight: bold;
  box-shadow: 0 2px 6px rgba(0,0,0,0.1);
  transition: transform 0.2s ease;
}

.stat-box:hover {
  transform: scale(1.03);
}

.stat-box .label {
  font-size: 0.85em;
  margin-bottom: 4px;
  display: flex;
  align-items: center;
  gap: 6px;
}

.stat-box .value {
  font-size: 1.4em;
  text-align: right;
}

/* Couleurs personnalisées */
.encadres { background: linear-gradient(135deg, #527479ff, #527479ff); }
.soutenance.attente { background: linear-gradient(135deg, #FF9800, #FFB74D); }
.soutenance.validée { background: linear-gradient(135deg, #4CAF50, #81C784); }
.soutenance.rejetée { background: linear-gradient(135deg, #F44336, #E57373); }
.memoire.attente { background: linear-gradient(135deg, #464649ff, #323337ff); }
.memoire.validée { background: linear-gradient(135deg, #009688, #4DB6AC); }
.memoire.rejetée { background: linear-gradient(135deg, #D32F2F, #EF5350); }

.chart-container {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 30px;
  margin-top: 30px;
}

.chart-box {
  background: #fff;
  padding: 20px;
  border-radius: 16px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.05);
  max-width: 360px;
  width: 100%;
  text-align: center;
}

.chart-box h4 {
  margin-bottom: 10px;
  font-size: 18px;
  color: #333;
}

.chart-section {
  margin-top: 40px;
}

.chart-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 30px;
  justify-content: center;
}

.chart-card {
  background: #ffffff;
  border-radius: 16px;
  padding: 20px;
  box-shadow: 0 6px 18px rgba(0,0,0,0.06);
  text-align: center;
}

.chart-card h4 {
  margin-bottom: 12px;
  font-size: 18px;
  color: #1e2a38;
}

.chart-card canvas {
  max-width: 100%;
  height: auto;
}



  </style>
</head>
<body>
  <div class="dashboard">
  <h2>📁 Espace DM — Bienvenue <?= htmlspecialchars($dm_nom) ?> 👋</h2>

  <div class="stat-container">
  <div class="stat-box encadres">
    <div class="label"><i>👥</i> Étudiants encadrés</div>
    <div class="value"><?= $nb_etudiants ?></div>
  </div>

  <div class="stat-box soutenance attente">
    <div class="label"><i>🕐</i> Soutenances en attente</div>
    <div class="value"><?= $nb_soutenances ?></div>
  </div>

  <div class="stat-box soutenance validée">
    <div class="label"><i>✅</i> Soutenances validées</div>
    <div class="value"><?= $nb_soutenances_validées ?></div>
  </div>

  <div class="stat-box soutenance rejetée">
    <div class="label"><i>❌</i> Soutenances rejetées</div>
    <div class="value"><?= $nb_soutenances_rejetees ?></div>
  </div>

  <div class="stat-box memoire attente">
    <div class="label"><i>📄</i> Mémoires en attente</div>
    <div class="value"><?= $nb_memoires ?></div>
  </div>

  <div class="stat-box memoire validée">
    <div class="label"><i>✅</i> Mémoires validés</div>
    <div class="value"><?= $nb_memoires_validés ?></div>
  </div>

  <div class="stat-box memoire rejetée">
    <div class="label"><i>❌</i> Mémoires rejetés</div>
    <div class="value"><?= $nb_memoires_rejetés ?></div>
  </div>
</div>



  <div class="recent-actions">
  <h3>🕒 Dernières actions</h3>
  <div style="max-height:300px; overflow-y:auto; padding-right:5px;">
    <ul style="list-style:none; padding-left:0; margin:0;">
      <?php
      $historique = [];

      $sql = "
        SELECT 
          u.username AS etudiant,
          m.encadrant,
          'Mémoire' AS type_demande,
          m.etat_validation,
          m.date_depot AS date_action
        FROM memoires m
        JOIN users u ON m.user_id = u.id
        WHERE m.encadrant = ?

        UNION

        SELECT 
          u.username AS etudiant,
          d.encadrant,
          'Soutenance' AS type_demande,
          d.etat_validation,
          d.date_demande AS date_action
        FROM demandes_soutenance d
        JOIN users u ON d.user_id = u.id
        WHERE d.encadrant = ?

        ORDER BY date_action DESC
        LIMIT 5
      ";

      $stmt = $conn->prepare($sql);
      $stmt->bind_param("ss", $dm_nom, $dm_nom);
      $stmt->execute();
      $result = $stmt->get_result();
      while ($row = $result->fetch_assoc()) {
        $type = strtolower($row['type_demande'] ?? '');
        $etat = strtolower($row['etat_validation'] ?? '');
        $icon = $type === 'mémoire' ? '📘' : '📄';

        switch ($etat) {
          case 'valide':
            $color = 'green';
            $etat_label = '✅ validée';
            break;
          case 'rejete':
            $color = 'red';
            $etat_label = '❌ rejetée';
            break;
          default:
            $color = 'orange';
            $etat_label = '🕐 en attente';
            break;
        }

        $etudiant = !empty($row['etudiant']) ? htmlspecialchars($row['etudiant']) : 'Nom inconnu';
        $date = !empty($row['date_action']) ? date("d/m/Y à H:i", strtotime($row['date_action'])) : 'Date inconnue';

        echo "<li style='margin-bottom:12px; padding:10px; background:#f9f9f9; border-left:6px solid $color; border-radius:8px;'>
                $icon <strong>" . ucfirst($type) . "</strong> de <strong>$etudiant</strong> 
                <span style='color:$color; font-weight:bold;'>$etat_label</span> 
                le $date
              </li>";
      }
      ?>
    </ul>
  </div>
</div>


  <div class="chart-section">
  <h2>📊 Vue d’ensemble des validations</h2>

  <div class="chart-grid">
    <div class="chart-card">
      <h4>Global</h4>
      <canvas id="validationChart"></canvas>
    </div>

    <div class="chart-card">
      <h4>Mémoires</h4>
      <canvas id="memoireChart"></canvas>
    </div>

    <div class="chart-card">
      <h4>Soutenances</h4>
      <canvas id="soutenanceChart"></canvas>
    </div>
  </div>
</div>


<div class="back-button">
  <a href="../accueil.php">← Retour au tableau de bord</a>
</div>



 <script>
const validationChart = new Chart(document.getElementById('validationChart'), {
  type: 'doughnut',
  data: {
    labels: [
      'Soutenances en attente',
      'Soutenances validées',
      'Soutenances rejetées',
      'Mémoires en attente',
      'Mémoires validés',
      'Mémoires rejetés'
    ],
    datasets: [{
      data: [
        <?= $nb_soutenances ?>,
        <?= $nb_soutenances_validées ?>,
        <?= $nb_soutenances_rejetees ?>,
        <?= $nb_memoires ?>,
        <?= $nb_memoires_validés ?>,
        <?= $nb_memoires_rejetés ?>
      ],
      backgroundColor: [
        '#FFB74D', '#4CAF50', '#F44336',
        '#7986CB', '#009688', '#D32F2F'
      ],
      borderWidth: 1
    }]
  },
  options: {
    responsive: true,
    plugins: {
      legend: { position: 'bottom' },
      title: {
        display: true,
        text: 'État global des validations'
      }
    }
  }
});

const memoireChart = new Chart(document.getElementById('memoireChart'), {
  type: 'doughnut',
  data: {
    labels: ['En attente', 'Validés', 'Rejetés'],
    datasets: [{
      data: [<?= $nb_memoires ?>, <?= $nb_memoires_validés ?>, <?= $nb_memoires_rejetés ?>],
      backgroundColor: ['#7986CB', '#009688', '#D32F2F'],
      borderWidth: 1
    }]
  },
  options: {
    plugins: {
      title: {
        display: true,
        text: 'Mémoires'
      },
      legend: { position: 'bottom' }
    }
  }
});

const soutenanceChart = new Chart(document.getElementById('soutenanceChart'), {
  type: 'doughnut',
  data: {
    labels: ['En attente', 'Validées', 'Rejetées'],
    datasets: [{
      data: [<?= $nb_soutenances ?>, <?= $nb_soutenances_validées ?>, <?= $nb_soutenances_rejetees ?>],
      backgroundColor: ['#FFB74D', '#4CAF50', '#F44336'],
      borderWidth: 1
    }]
  },
  options: {
    plugins: {
      title: {
        display: true,
        text: 'Soutenances'
      },
      legend: { position: 'bottom' }
    }
  }
});
</script>



</body>

</html>
