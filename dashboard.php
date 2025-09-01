<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$role = $_SESSION['role'];

if ($role === 'administrateur') {
    // Vue globale
    $protocoles_valides   = $conn->query("SELECT COUNT(*) FROM protocoles WHERE etat_validation = 'valide'")->fetch_row()[0] ?? 0;
    $protocoles_rejetes   = $conn->query("SELECT COUNT(*) FROM protocoles WHERE etat_validation = 'rejete'")->fetch_row()[0] ?? 0;
    $protocoles_attente   = $conn->query("SELECT COUNT(*) FROM protocoles WHERE etat_validation = 'en_attente'")->fetch_row()[0] ?? 0;

    $soutenances_valides  = $conn->query("SELECT COUNT(*) FROM demandes_soutenance WHERE etat_validation = 'valide'")->fetch_row()[0] ?? 0;
    $soutenances_rejetes  = $conn->query("SELECT COUNT(*) FROM demandes_soutenance WHERE etat_validation = 'rejete'")->fetch_row()[0] ?? 0;
    $soutenances_attente  = $conn->query("SELECT COUNT(*) FROM demandes_soutenance WHERE etat_validation = 'en_attente'")->fetch_row()[0] ?? 0;

    $memoires_valides     = $conn->query("SELECT COUNT(*) FROM memoires WHERE etat_validation = 'valide'")->fetch_row()[0] ?? 0;
    $memoires_rejetes     = $conn->query("SELECT COUNT(*) FROM memoires WHERE etat_validation = 'rejete'")->fetch_row()[0] ?? 0;
    $memoires_attente     = $conn->query("SELECT COUNT(*) FROM memoires WHERE etat_validation = 'en_attente'")->fetch_row()[0] ?? 0;

    $dm_attribues         = $conn->query("SELECT COUNT(DISTINCT dm_id) FROM protocoles WHERE dm_id IS NOT NULL")->fetch_row()[0] ?? 0;
} else {
    // Vue filtrée pour DM ou encadrant
    $protocoles_valides   = $conn->query("SELECT COUNT(*) FROM protocoles WHERE etat_validation = 'valide' AND dm_id = $user_id")->fetch_row()[0] ?? 0;
    $protocoles_rejetes   = $conn->query("SELECT COUNT(*) FROM protocoles WHERE etat_validation = 'rejete' AND dm_id = $user_id")->fetch_row()[0] ?? 0;
    $protocoles_attente   = $conn->query("SELECT COUNT(*) FROM protocoles WHERE etat_validation = 'en_attente' AND dm_id = $user_id")->fetch_row()[0] ?? 0;

    $soutenances_valides  = $conn->query("SELECT COUNT(*) FROM demandes_soutenance WHERE etat_validation = 'valide' AND encadrant = '$username'")->fetch_row()[0] ?? 0;
    $soutenances_rejetes  = $conn->query("SELECT COUNT(*) FROM demandes_soutenance WHERE etat_validation = 'rejete' AND encadrant = '$username'")->fetch_row()[0] ?? 0;
    $soutenances_attente  = $conn->query("SELECT COUNT(*) FROM demandes_soutenance WHERE etat_validation = 'en_attente' AND encadrant = '$username'")->fetch_row()[0] ?? 0;

    $memoires_valides     = $conn->query("SELECT COUNT(*) FROM memoires WHERE etat_validation = 'valide' AND encadrant = '$username'")->fetch_row()[0] ?? 0;
    $memoires_rejetes     = $conn->query("SELECT COUNT(*) FROM memoires WHERE etat_validation = 'rejete' AND encadrant = '$username'")->fetch_row()[0] ?? 0;
    $memoires_attente     = $conn->query("SELECT COUNT(*) FROM memoires WHERE etat_validation = 'en_attente' AND encadrant = '$username'")->fetch_row()[0] ?? 0;

    $dm_attribues         = $conn->query("SELECT COUNT(DISTINCT user_id) FROM protocoles WHERE dm_id = $user_id")->fetch_row()[0] ?? 0;
}

// Requête pour compter les étudiants
$etudiants_total = 0;
$result = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'etudiant'");
if ($result) {
  $row = $result->fetch_row();
  $etudiants_total = $row[0] ?? 0;
}

$total_utilisateurs = 0;
$result = $conn->query("SELECT COUNT(*) FROM users");
if ($result) {
  $row = $result->fetch_row();
  $total_utilisateurs = $row[0] ?? 0;
}


$user_id = (int) $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT username, avatar FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();


if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit();
}

$username = htmlspecialchars($user['username']);
$avatar = !empty($user['avatar']) ? htmlspecialchars($user['avatar']) : '';

?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Tableau de bord</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      display: flex;
      min-height: 100vh;
      background: linear-gradient(90deg, #49c4e6ff 0%, #e5edf7ff 100%);
      color: #1b1919ff;
      transition: background 0.3s, color 0.3s;
    }
    .dark-mode {
      background: #0f1115;
      color: #e7e7e7;
    }
    .sidebar {
      width: 260px;
      background: rgba(0,0,0,0.15);
      backdrop-filter: blur(6px);
      padding: 20px;
      display: flex;
      flex-direction: column;
      gap: 14px;
      border-radius: 12px;
    }
    .brand {
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    .user-menu {
      position: relative;
      display: flex;
      align-items: center;
      gap: 8px;
      cursor: pointer;
      user-select: none;
    }
    .avatar {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      object-fit: cover;
      border: 2px solid rgba(255,255,255,0.3);
    }
    .avatar-initiales {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      background: linear-gradient(135deg, #00c6ff, #151617ff);
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      font-size: 14px;
      color: #fff;
    }
    .username {
      font-weight: 600;
      max-width: 140px;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }
    .dropdown {
      position: absolute;
      top: 46px;
      left: 0;
      background: rgba(0,0,0,0.85);
      backdrop-filter: blur(4px);
      border-radius: 10px;
      padding: 8px 0;
      display: none;
      flex-direction: column;
      min-width: 180px;
      z-index: 20;
      box-shadow: 0 8px 20px rgba(0,0,0,0.25);
      opacity: 0;
      transform: translateY(-6px);
      transition: opacity 0.18s ease, transform 0.18s ease;
    }
    .dropdown.open {
      display: flex;
      opacity: 1;
      transform: translateY(0);
    }
    .dropdown a {
      padding: 10px 14px;
      color: #fff;
      text-decoration: none;
      display: flex;
      align-items: center;
      gap: 10px;
      transition: background 0.25s;
    }
    .dropdown a:hover {
      background: rgba(255,255,255,0.1);
    }
    #darkToggle {
      cursor: pointer;
      font-size: 15px;
      background: rgba(255,255,255,0.18);
      padding: 8px;
      border-radius: 50%;
    }
    .nav {
      display: flex;
      flex-direction: column;
      gap: 8px;
      margin-top: 8px;
    }
    .nav a {
      text-decoration: none;
      color: inherit;
      padding: 10px 12px;
      display: block;
      border-radius: 10px;
      background: rgba(255,255,255,0.08);
    }
    .nav a:hover {
      background: rgba(255,255,255,0.18);
    }
    .main {
      flex: 1;
      padding: 24px;
      max-width: 1200px;
      margin: 0 auto;
    }
    header h1 {
      font-size: 1.6rem;
      font-weight: 700;
      letter-spacing: 0.2px;
    }

    .stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 20px;
  margin-top: 30px;
}

.stat-box {
  display: flex;
  align-items: center;
  background-color: #1ca6e7ff;
  border-radius: 10px;
  padding: 15px;
  box-shadow: 0 2px 6px rgba(173, 35, 35, 0.46);
  transition: transform 0.2s ease;
}

.stat-box:hover {
  transform: scale(1.02);
}

.stat-box .icon {
  font-size: 28px;
  margin-right: 15px;
}

.stat-content h3 {
  margin: 0;
  font-size: 16px;
  color: #333;
}

.stat-content p {
  margin: 4px 0 0;
  font-size: 20px;
  font-weight: bold;
  color: #111;
}

/* Couleurs par type */
.success { border-left: 6px solid #2ecc71; }
.danger  { border-left: 6px solid #e74c3c; }
.warning { border-left: 6px solid #f39c12; }
.info    { border-left: 6px solid #15547eff; }

.summary-cards {
  display: flex;
  gap: 20px;
  margin-top: 20px;
}
.summary-cards .card {
  background: #ffffff22;
  padding: 12px 20px;
  border-radius: 10px;
  font-weight: bold;
  color: #2b2828ff;
  box-shadow: 0 2px 6px rgba(0,0,0,0.1);
}

.charts-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 24px;
  margin-top: 30px;
}

.chart-card {
  background: var(--card-bg, rgba(255,255,255,0.12));
  border-radius: 12px;
  padding: 16px;
  box-shadow: 0 6px 18px rgba(0,0,0,0.06);
  text-align: center;
}

.chart-card h4 {
  margin-bottom: 12px;
  font-size: 16px;
  color: #1d1b1bff;
}

.chart-box.square {
  position: relative;
  width: 100%;
  aspect-ratio: 1 / 1;
}

.chart-box canvas {
  position: absolute;
  inset: 0;
  width: 100% !important;
  height: 100% !important;
  border-radius: 8px;
}
.single-chart {
  margin-top: 30px;
  background: var(--card-bg, rgba(255,255,255,0.12));
  border-radius: 12px;
  padding: 16px;
  box-shadow: 0 6px 18px rgba(0,0,0,0.06);
  text-align: center;
}

.single-chart .chart-box {
  position: relative;
  width: 100%;
  max-width: 600px;
  margin: 0 auto;
  aspect-ratio: 2 / 1;
}

.single-chart canvas {
  position: absolute;
  inset: 0;
  width: 100% !important;
  height: 100% !important;
  border-radius: 8px;
}

  </style>
</head>
<body>
  <aside class="sidebar">
    <div class="brand">
      <div class="user-menu" tabindex="0" aria-haspopup="true" aria-expanded="false">
        <?php
          if (empty($avatar)) {
            $initiales = '';
            $mots = explode(' ', trim($username));
            foreach ($mots as $m) {
              $initiales .= strtoupper(mb_substr($m, 0, 1));
            }
            echo '<div class="avatar-initiales">'.htmlspecialchars($initiales).'</div>';
          } else {
            echo '<img src="'.htmlspecialchars($avatar).'" alt="Avatar" class="avatar">';
          }
        ?>
        <span class="username"><?= $username ?></span>
        <div class="dropdown" role="menu">
          <a href="profile.php"><i class="fa-solid fa-user"></i> Mon profil</a>
          <a href="settings.php"><i class="fa-solid fa-cog"></i> Paramètres</a>
          <a href="logout.php"><i class="fa-solid fa-arrow-right-from-bracket"></i> Déconnexion</a>
        </div>
      </div>
      <div id="darkToggle" title="Mode sombre" aria-label="Basculer le mode sombre">🌙</div>
    </div>
    <nav class="nav">
      <a href="dashboard.php"><i class="fa-solid fa-house"></i> Tableau de bord</a>
      <a href="forms/protocoles_consultation.php"><i class="fa-solid fa-eye"></i> Consultation Protocole</a>
      <a href="forms/programmer_soutenance.php"><i class="fa-solid fa-book"></i> Programmer Soutenance</a>
      <a href="forms/memoires_archive.php"><i class="fa-solid fa-box-archive"></i> Memoires Archiver</a>
      <a href="forms/attribuer_dm.php"><i class="fa-solid fa-user"></i> Ajouter DM</a>
      <a href="logout.php"><i class="fa-solid fa-arrow-right-from-bracket"></i> Déconnexion</a>
    </nav>
  </aside>
  <main class="main">
    <header>
      <h1>Résumé</h1>
    </header>
          <section class="stats-grid">
  <div class="stat-box success">
    <span class="icon"><i class="fa-solid fa-check"></i></span>
    <div class="stat-content">
      <h3>Protocoles validés</h3>
      <p><?= $protocoles_valides ?></p>
    </div>
  </div>
  <div class="stat-box danger">
    <span class="icon"><i class="fa-solid fa-times"></i></span>
    <div class="stat-content">
      <h3>Protocoles rejetés</h3>
      <p><?= $protocoles_rejetes ?></p>
    </div>
  </div>
  <div class="stat-box warning">
    <span class="icon"><i class="fa-solid fa-hourglass-half"></i></span>
    <div class="stat-content">
      <h3>Protocoles en attente</h3>
      <p><?= $protocoles_attente ?></p>
    </div>
  </div>

  <div class="stat-box success">
    <span class="icon"><i class="fa-solid fa-check"></i></span>
    <div class="stat-content">
      <h3>Soutenances validées</h3>
      <p><?= $soutenances_valides ?></p>
    </div>
  </div>
  <div class="stat-box danger">
    <span class="icon"><i class="fa-solid fa-times"></i></span>
    <div class="stat-content">
      <h3>Soutenances rejetées</h3>
      <p><?= $soutenances_rejetes ?></p>
    </div>
  </div>
  <div class="stat-box warning">
    <span class="icon"><i class="fa-solid fa-hourglass-half"></i></span>
    <div class="stat-content">
      <h3>Soutenances en attente</h3>
      <p><?= $soutenances_attente ?></p>
    </div>
  </div>

  <div class="stat-box success">
    <span class="icon"><i class="fa-solid fa-check"></i></span>
    <div class="stat-content">
      <h3>Mémoires validés</h3>
      <p><?= $memoires_valides ?></p>
    </div>
  </div>
  <div class="stat-box danger">
    <span class="icon"><i class="fa-solid fa-times"></i></span>
    <div class="stat-content">
      <h3>Mémoires rejetés</h3>
      <p><?= $memoires_rejetes ?></p>
    </div>
  </div>
  <div class="stat-box warning">
    <span class="icon"><i class="fa-solid fa-hourglass-half"></i></span>
    <div class="stat-content">
      <h3>Mémoires en attente</h3>
      <p><?= $memoires_attente ?></p>
    </div>
  </div>

  <div class="stat-box info">
    <span class="icon"><i class="fa-solid fa-user"></i></span>
    <div class="stat-content">
      <h3>DM attribués</h3>
      <p><?= $dm_attribues ?></p>
    </div>
  </div>

  <div class="stat-box info">
  <span class="icon"><i class="fa-solid fa-graduation-cap"></i></span>
  <div class="stat-content">
    <h3>Étudiants enregistrés</h3>
    <p><?= $etudiants_total ?></p>
  </div>
</div>

<div class="stat-box info">
  <span class="icon"><i class="fa-solid fa-chart-line"></i></span>
  <div class="stat-content">
    <h3>Taux de validation</h3>
    <p>
      <?php
        $total_valides = $protocoles_valides + $soutenances_valides + $memoires_valides;
        $total_dossiers = $protocoles_valides + $protocoles_rejetes + $protocoles_attente +
                          $soutenances_valides + $soutenances_rejetes + $soutenances_attente +
                          $memoires_valides + $memoires_rejetes + $memoires_attente;
        $taux = $total_dossiers > 0 ? round(($total_valides / $total_dossiers) * 100, 2) : 0;
        echo $taux . '%';
      ?>
    </p>
  </div>
</div>

</section>



<section class="single-chart" style="margin-top: 30px;">
  <!-- <h4>📊 Activité par module</h4> -->
  <div class="chart-box">
    <canvas id="moduleChart"></canvas>
  </div>
</section>
<div class="summary-cards">
  <div class="card"><i class="fa-solid fa-file"></i> Total protocoles : <?= $protocoles_valides + $protocoles_rejetes + $protocoles_attente ?></div>
  <div class="card"><i class="fa-solid fa-building"></i> Total soutenances : <?= $soutenances_valides + $soutenances_rejetes + $soutenances_attente ?></div>
  <div class="card"><i class="fa-solid fa-book"></i> Total mémoires : <?= $memoires_valides + $memoires_rejetes + $memoires_attente ?></div>
    <div class="card"><i class="fa-solid fa-users"></i> Total utilisateurs : <?= $total_utilisateurs ?></div>
</div>

<section class="charts-grid">
  <div class="chart-card">
    <h4><i class="fa-solid fa-file"></i> Protocoles</h4>
    <div class="chart-box square">
      <canvas id="protocolChart"></canvas>
    </div>
  </div>
  <div class="chart-card">
    <h4><i class="fa-solid fa-building"></i> Soutenances</h4>
    <div class="chart-box square">
      <canvas id="soutenanceChart"></canvas>
    </div>
  </div>
  <div class="chart-card">
    <h4><i class="fa-solid fa-book"></i> Mémoires</h4>
    <div class="chart-box square">
      <canvas id="memoireChart"></canvas>
    </div>
  </div>
</section>



  </main>
  <script>
    // 🌙 Mode sombre avec persistance
    (function initTheme() {
      if (localStorage.getItem("darkMode") === "true") {
        document.body.classList.add("dark-mode");
      }
    })();
    const darkToggleBtn = document.getElementById('darkToggle');
    darkToggleBtn.addEventListener('click', function() {
      document.body.classList.toggle('dark-mode');
      localStorage.setItem('darkMode', document.body.classList.contains('dark-mode'));
    });

    // 👤 Menu profil
    const userMenu = document.querySelector('.user-menu');
    const dropdown = document.querySelector('.dropdown');
    function closeDropdown() {
      dropdown.classList.remove('open');
      userMenu.setAttribute('aria-expanded', 'false');
    }
    function toggleDropdown() {
      const isOpen = dropdown.classList.contains('open');
      if (isOpen) {
        closeDropdown();
      } else {
        dropdown.classList.add('open');
        userMenu.setAttribute('aria-expanded', 'true');
      }
    }
    userMenu.addEventListener('click', (e) => {
      toggleDropdown();
      e.stopPropagation();
    });
    userMenu.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        toggleDropdown();
      }
      if (e.key === 'Escape') {
        closeDropdown();
      }
    });
    document.addEventListener('click', () => closeDropdown());

    const moduleLabels = ['Protocoles', 'Soutenances', 'Mémoires', 'DM'];
const moduleData = [
  <?= $protocoles_valides + $protocoles_rejetes + $protocoles_attente ?>,
  <?= $soutenances_valides + $soutenances_rejetes + $soutenances_attente ?>,
  <?= $memoires_valides + $memoires_rejetes + $memoires_attente ?>,
  <?= $dm_attribues ?>
];

new Chart(document.getElementById('moduleChart'), {
  type: 'bar',
  data: {
    labels: moduleLabels,
    datasets: [{
      label: 'Volume',
      data: moduleData,
      backgroundColor: ['#3498db', '#e67e22', '#9b59b6', '#2ecc71'],
      borderRadius: 6
    }]
  },
  options: {
    responsive: true,
    plugins: {
      legend: { display: false },
      title: {
        display: true,
        text: 'Activité par module',
        font: { size: 18 }
      }
    },
    scales: {
      y: { beginAtZero: true }
    }
  }
});


const doughnutOptions = {
  responsive: true,
  plugins: {
    legend: { position: 'bottom' },
    title: { display: false }
  }
};

new Chart(document.getElementById('protocolChart'), {
  type: 'doughnut',
  data: {
    labels: ['Validés', 'Rejetés', 'En attente'],
    datasets: [{
      data: [<?= $protocoles_valides ?>, <?= $protocoles_rejetes ?>, <?= $protocoles_attente ?>],
      backgroundColor: ['#4CAF50', '#F44336', '#FFC107']
    }]
  },
  options: doughnutOptions
});

new Chart(document.getElementById('soutenanceChart'), {
  type: 'doughnut',
  data: {
    labels: ['Validées', 'Rejetées', 'En attente'],
    datasets: [{
      data: [<?= $soutenances_valides ?>, <?= $soutenances_rejetes ?>, <?= $soutenances_attente ?>],
      backgroundColor: ['#4CAF50', '#F44336', '#FFC107']
    }]
  },
  options: doughnutOptions
});

new Chart(document.getElementById('memoireChart'), {
  type: 'doughnut',
  data: {
    labels: ['Validés', 'Rejetés', 'En attente'],
    datasets: [{
      data: [<?= $memoires_valides ?>, <?= $memoires_rejetes ?>, <?= $memoires_attente ?>],
      backgroundColor: ['#4CAF50', '#F44336', '#FFC107']
    }]
  },
  options: doughnutOptions
});


  </script>
</body>
</html>
