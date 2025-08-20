<?php
session_start();
require_once 'includes/db.php'; // doit définir $conn (mysqli)

// 1) Vérifier la session
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SESSION['role'] !== 'administrateur') {
    header("Location: accueil.php");
    exit();
}


$user_id = (int) $_SESSION['user_id'];


// 🔎 Récupération des infos utilisateur
$stmt = $conn->prepare("SELECT username, avatar, role FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$role = $user['role'] ?? 'etudiant'; // valeur par défaut si non défini
$stmt->close();

$loginHistory = [];
$loginStmt = $conn->prepare("SELECT ip_address, user_agent, login_time FROM user_logins WHERE user_id = ? ORDER BY login_time DESC LIMIT 10");
$loginStmt->bind_param("i", $user_id);
$loginStmt->execute();
$loginRes = $loginStmt->get_result();
while ($row = $loginRes->fetch_assoc()) {
    $row['device'] = parseUserAgent($row['user_agent']);
    $loginHistory[] = $row;
}
$loginStmt->close();

function parseUserAgent($ua) {
    if (empty($ua)) return 'Inconnu';

    if (stripos($ua, 'Windows')) return 'Windows';
    if (stripos($ua, 'Mac')) return 'Mac';
    if (stripos($ua, 'Linux')) return 'Linux';
    if (stripos($ua, 'Android')) return 'Android';
    if (stripos($ua, 'iPhone')) return 'iPhone';
    return 'Autre';
}


if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit();
}

$username = htmlspecialchars($user['username']);
$avatar = !empty($user['avatar']) ? htmlspecialchars($user['avatar']) : 'assets/images/utilisateur.png';

// 📅 Filtre par année
$filterYear = $_GET['year'] ?? '';
$hasYearFilter = !empty($filterYear);

// 📊 Statistiques globales
$stats = [
    'total' => 0,
    'total_income' => 0,
    'total_tax' => 0,
    'last_year' => null
];

$sqlStats = "SELECT COUNT(*) AS total, COALESCE(SUM(income), 0) AS total_income, COALESCE(SUM(tax_due), 0) AS total_tax, MAX(year) AS last_year FROM declarations WHERE user_id = ?";
if ($hasYearFilter) {
    $sqlStats .= " AND year = ?";
}
$statsStmt = $conn->prepare($sqlStats);
if ($hasYearFilter) {
    $statsStmt->bind_param("ii", $user_id, $filterYear);
} else {
    $statsStmt->bind_param("i", $user_id);
}
$statsStmt->execute();
$statsRes = $statsStmt->get_result();
if ($row = $statsRes->fetch_assoc()) {
    $stats = $row;
}
$statsStmt->close();

// 📈 Répartition des profits par profil
$profitData = [];
$sqlProfit = "SELECT profile, COALESCE(SUM(income), 0) AS total_income, COALESCE(SUM(expenses), 0) AS total_expenses FROM declarations WHERE user_id = ?";
if ($hasYearFilter) {
    $sqlProfit .= " AND year = ?";
}
$sqlProfit .= " GROUP BY profile";
$profitStmt = $conn->prepare($sqlProfit);
if ($hasYearFilter) {
    $profitStmt->bind_param("ii", $user_id, $filterYear);
} else {
    $profitStmt->bind_param("i", $user_id);
}
$profitStmt->execute();
$profitRes = $profitStmt->get_result();
while ($row = $profitRes->fetch_assoc()) {
    $profitData[] = [
        'source' => $row['profile'],
        'amount' => (float)$row['total_income'] - (float)$row['total_expenses'],
        'total_expenses' => (float)$row['total_expenses']
    ];
}
$stats['total_profit'] = array_sum(array_column($profitData, 'amount'));
$profitStmt->close();

// 📊 Évolution annuelle (non filtrée pour garder la vue globale)
$yearData = [];
$sqlYear = "SELECT year, SUM(income) AS income, SUM(tax_due) AS tax FROM declarations WHERE user_id = ? GROUP BY year ORDER BY year ASC";
$yearStmt = $conn->prepare($sqlYear);
$yearStmt->bind_param("i", $user_id);
$yearStmt->execute();
$yearRes = $yearStmt->get_result();
while ($r = $yearRes->fetch_assoc()) {
    $yearData[] = $r;
}
$yearStmt->close();

// 📊 Préparation des données pour le graphique
$labels = [];
$revenus = [];
$depenses = [];
$profits = [];

foreach ($profitData as $row) {
    $labels[] = $row['source'];
    $revenus[] = $row['amount'] + $row['total_expenses'];
    $depenses[] = $row['total_expenses'];
    $profits[] = $row['amount'];
}

$declCountStmt = $conn->prepare("SELECT COUNT(*) AS total FROM declarant WHERE user_id = ?");
$declCountStmt->bind_param("i", $user_id);
$declCountStmt->execute();
$declCountResult = $declCountStmt->get_result()->fetch_assoc();
$declarantCount = $declCountResult['total'] ?? 0;
$declCountStmt->close();



?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Tableau de bord</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
:root {
  --radius: 12px;
  --gap: 16px;
  --card-bg: rgba(255,255,255,0.12);
  --card-bg-dark: rgba(255,255,255,0.06);
  --grid-light: rgba(0,0,0,0.08);
  --grid-dark: rgba(255,255,255,0.1);
}
* { margin: 0; padding: 0; box-sizing: border-box; }
body {
  font-family: 'Segoe UI', sans-serif;
  display: flex;
  min-height: 100vh;
  background: linear-gradient(90deg, #00c6ff 0%, #0072ff 100%);
  color: #fff;
  transition: background 0.3s, color 0.3s;
}

/* Sidebar */
.sidebar {
  width: 260px;
  background: rgba(0,0,0,0.15);
  backdrop-filter: blur(6px);
  padding: 20px;
  display: flex;
  flex-direction: column;
  gap: 14px;
}
.brand {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

/* Profil menu */
.user-menu {
  position: relative;
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  user-select: none;
}
.user-menu:focus { outline: none; }
.avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid rgba(255,255,255,0.3);
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

/* Nav */
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
  transition: background 0.25s, transform 0.15s;
  background: rgba(255,255,255,0.08);
}
.nav a:hover {
  background: rgba(255,255,255,0.18);
  transform: translateY(-1px);
}

/* Main content */
.main {
  flex: 1;
  padding: 24px;
  max-width: 1200px;
  margin: 0 auto;
}
header { margin-bottom: 18px; }
header h1 {
  font-size: 1.6rem;
  font-weight: 700;
  letter-spacing: 0.2px;
  animation: fadeIn 0.6s ease-out;
}

/* Stats grid */
.stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: var(--gap);
  margin-bottom: 24px;
}
.stat-box {
  background: var(--card-bg);
  padding: 14px;
  border-radius: var(--radius);
}
.stat-box h3 {
  font-size: 0.9rem;
  font-weight: 600;
  opacity: 0.85;
  margin-bottom: 6px;
}
.stat-box p {
  font-size: 1.4rem;
  font-weight: 700;
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
  border: 0px solid rgba(255,255,255,0.3);
  flex-shrink: 0;
  text-transform: uppercase;
}

/* 🌟 Conteneur du formulaire */
.filter-form {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    align-items: center;
    margin-bottom: 24px;
}

/* 🏷️ Label */
.filter-form label {
    font-weight: 600;
    color: #ffffffff;
    font-size: 14px;
}

/* 📅 Select */
.filter-form select {
    padding: 8px 12px;
    border: 1px solid #ccc;
    border-radius: 6px;
    background-color: #d4d1d1ff;
    font-size: 14px;
    color: #333;
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
    cursor: pointer;
}

.filter-form select:focus {
    border-color: #0078D7;
    box-shadow: 0 0 0 2px rgba(0, 120, 215, 0.2);
    outline: none;
}

/* ✅ Bouton */
.filter-form button {
    padding: 8px 16px;
    background-color: #0078D7;
    color: white;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.filter-form button:hover {
    background-color: #005fa3;
}

/* Charts grid */
.charts {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: var(--gap);
}

/* Chart cards */
.chart-card {
  background: var(--card-bg);
  border-radius: var(--radius);
  padding: 14px;
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.chart-card h4 {
  font-size: 0.95rem;
  font-weight: 600;
  opacity: 0.9;
}

/* Canvas wrapper with aspect-ratio */
.chart-box {
  position: relative;
  width: 100%;
  height: auto;
  aspect-ratio: 4 / 3; /* line chart */
}
.chart-box.square { aspect-ratio: 1 / 1; } /* donut */

canvas {
  position: absolute;
  inset: 0;
  width: 100% !important;
  height: 100% !important;
  background: transparent;
  border-radius: 8px;
}

/* Dark mode */
.dark-mode { background: #0f1115; color: #e7e7e7; }
.dark-mode .sidebar { background: rgba(255,255,255,0.04); }
.dark-mode .nav a { background: rgba(255,255,255,0.06); }
.dark-mode .nav a:hover { background: rgba(255,255,255,0.12); }
.dark-mode .stat-box,
.dark-mode .chart-card { background: var(--card-bg-dark); }
.dark-mode .dropdown { background: rgba(30,32,38,0.98); }

/* Responsive: sidebar to top on mobile */
@media (max-width: 900px) {
  body { flex-direction: column; }
  .sidebar {
    width: 100%;
    flex-direction: row;
    align-items: center;
    gap: 10px;
    overflow-x: auto;
  }
  .brand { gap: 10px; }
  .nav { flex-direction: row; gap: 10px; }
  .nav a { white-space: nowrap; }
}

/* Animations */
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(-6px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
</head>
<body>
  <aside class="sidebar">
    <div class="brand">
      <!-- Menu profil moderne -->
      <div class="user-menu" tabindex="0" aria-haspopup="true" aria-expanded="false">
  <?php
    $avatar = $_SESSION['avatar'] ?? '';
    $initiales = '';
    if (empty($avatar)) {
        // Générer initiales : première lettre de chaque mot du nom
        $mots = explode(' ', trim($username));
        foreach ($mots as $m) {
            $initiales .= strtoupper(mb_substr($m, 0, 1));
        }
        echo '<div class="avatar-initiales">'.htmlspecialchars($initiales).'</div>';
    } else {
        echo '<img src="'.htmlspecialchars($avatar).'" alt="Avatar de '.htmlspecialchars($username).'" class="avatar">';
    }
  ?>
  <span class="username"><?= htmlspecialchars($username) ?></span>
  <div class="dropdown" role="menu">
    <a href="profile.php">👤 Mon profil</a>
    <a href="settings.php">⚙️ Paramètres</a>
    <a href="logout.php">🚪 Déconnexion</a>
  </div>
</div>


      <!-- Bouton mode sombre -->
      <div id="darkToggle" title="Mode sombre" aria-label="Basculer le mode sombre">🌙</div>
    </div>

    <nav class="nav">
      <a href="dashboard.php">🏠 Tableau de bord</a>
      <a href="forms/protocoles_consultation.php">📄 Consultation Protocole</a>
      <a href="forms/programmer_soutenance.php">🏢 Programmer Soutenance</a>
      <a href="forms/memoires_archive.php">📊 Memoires Archiver</a>
      <a href="forms/attribuer_dm.php">📊 Ajouter DM</a>
      <a href="logout.php">🚪 Déconnexion</a>
    </nav>
  </aside>

  <main class="main">
    <header>
      <h1>Résumé fiscal</h1>

    </header>
    <form method="GET" action="dashboard.php" class="filter-form">
  <label for="year">📅 Année :</label>
  <select name="year" id="year">
    <option value="">Toutes</option>
    <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
      <option value="<?= $y ?>" <?= ($filterYear == $y) ? 'selected' : '' ?>><?= $y ?></option>
    <?php endfor; ?>
  </select>
  <button type="submit">Filtrer</button>
</form>


    <section class="stats">
      <div class="stat-box">
        <h3>📄 Déclarations</h3>
        <p><?= (int)($stats['total'] ?? 0) ?></p>
      </div>
      <div class="stat-box">
        <h3>💰 Revenu total</h3>
        <p><?= number_format((float)($stats['total_income'] ?? 0), 0, ',', ' ') ?> FCFA</p>
      </div>
      <div class="stat-box">
        <h3>🧾 Impôt estimé</h3>
        <p><?= number_format((float)($stats['total_tax'] ?? 0), 0, ',', ' ') ?> FCFA</p>
      </div>
      <div class="stat-box">
        <h3>📅 Dernière année</h3>
        <p><?= htmlspecialchars($stats['last_year'] ?? '—') ?></p>
      </div>

      <div class="stat-box">
        <h3>👥 Déclarants enregistrés</h3>
        <p><?= $declarantCount ?></p>
      </div>

      <!-- <div class="stat-box">
  <h3>📈 Profit net total</h3>
  <p><?= number_format((float)($stats['total_profit'] ?? 0), 0, ',', ' ') ?> FCFA</p>
</div> -->

    </section>


    <section class="charts">
  <div class="chart-card">
    <h4>Répartition des profits</h4>
    <div class="chart-box square">
      <canvas id="profitChart"></canvas>
    </div>
    <div class="stat-box">
  <h3>📈 Profit net total</h3>
  <p><?= number_format((float)($stats['total_profit'] ?? 0), 0, ',', ' ') ?> FCFA</p>
</div>

  </div>

      <div class="chart-card">
        <h4>Évolution annuelle</h4>
        <div class="chart-box">
          <canvas id="yearChart"></canvas>
        </div>
      </div>

      <div class="chart-card">
  <h4>Comparatif par profil</h4>
  <div class="chart-box">
    <canvas id="barChart"></canvas>
  </div>
</div>

<div class="chart-card" style="margin-top: 30px;">
  <h4>📊 Connexions par jour</h4>
  <div class="chart-box">
    <canvas id="loginChart"></canvas>
  </div>
</div>


    </section>

    <section class="chart-card" style="margin-top: 30px;">
  <h4>🔐 Historique des connexions</h4>
  <div style="overflow-x:auto;">
    <table style="width:100%; border-collapse: collapse; background: rgba(255,255,255,0.08); border-radius: 8px; overflow: hidden;">
      <thead style="background: rgba(255,255,255,0.1);">
        <tr>
          <th style="padding: 10px;">📅 Date</th>
          <th style="padding: 10px;">🌍 IP</th>
          <th style="padding: 10px;">💻 Appareil</th>
          <th style="padding: 10px;">🔔</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $lastIP = null;
        $lastDevice = null;
        foreach ($loginHistory as $log):
          $isNew = ($lastIP && $log['ip_address'] !== $lastIP) || ($lastDevice && $log['device'] !== $lastDevice);
          $alert = $isNew ? "<span style='color:#ff4d4d;'>⚠️ Nouvelle connexion</span>" : "<span style='color:#4caf50;'>✔️</span>";
        ?>
        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
          <td style="padding: 10px;"><?= date('d/m/Y à H:i', strtotime($log['login_time'])) ?></td>
          <td style="padding: 10px;"><?= htmlspecialchars($log['ip_address'] ?? '') ?></td>
          <td style="padding: 10px;"><?= htmlspecialchars($log['device'] ?? '') ?></td>
          <td style="padding: 10px;"><?= $alert ?></td>
        </tr>
        <?php
          $lastIP = $log['ip_address'];
          $lastDevice = $log['device'];
        endforeach;
        ?>
      </tbody>
    </table>
  </div>
</section>

  </main>

<script>
// --- Mode sombre avec persistance ---
(function initTheme() {
  if (localStorage.getItem("darkMode") === "true") {
    document.body.classList.add("dark-mode");
  }
})();

const darkToggleBtn = document.getElementById('darkToggle');

// Références de chart pour mise à jour du thème
let profitChart, yearChart;
function currentTextColor() {
  return getComputedStyle(document.body).color;
}
function gridColor() {
  return document.body.classList.contains('dark-mode') ? getComputedStyle(document.documentElement).getPropertyValue('--grid-dark') : getComputedStyle(document.documentElement).getPropertyValue('--grid-light');
}
function updateChartTheme() {
  if (profitChart) {
    profitChart.options.plugins.legend.labels.color = currentTextColor();
    profitChart.update();
  }
  if (yearChart) {
    const c = currentTextColor();
    const g = gridColor();
    yearChart.options.plugins.legend.labels.color = c;
    yearChart.options.scales.x.ticks.color = c;
    yearChart.options.scales.y.ticks.color = c;
    yearChart.options.scales.y.grid.color = g;
    yearChart.update();
  }
}

darkToggleBtn.addEventListener('click', function() {
  document.body.classList.toggle('dark-mode');
  localStorage.setItem('darkMode', document.body.classList.contains('dark-mode'));
  updateChartTheme();
});

// --- Menu profil (ouverture/fermeture + accessibilité simple) ---
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

// --- Données PHP -> JS ---
const profitLabels = <?= json_encode(array_column($profitData, 'source') ?: [], JSON_UNESCAPED_UNICODE) ?>;
const profitValues = <?= json_encode(array_map('floatval', array_column($profitData, 'amount') ?: [])) ?>;
const yearLabels    = <?= json_encode(array_column($yearData, 'year') ?: []) ?>;
const yearIncome    = <?= json_encode(array_map('floatval', array_column($yearData, 'income') ?: [])) ?>;
const yearTax       = <?= json_encode(array_map('floatval', array_column($yearData, 'tax') ?: [])) ?>;
const barLabels = <?= json_encode(array_column($profitData, 'source'), JSON_UNESCAPED_UNICODE) ?>;
const barIncome = <?= json_encode(array_map(fn($r) => $r['amount'] + $r['total_expenses'], $profitData)) ?>;
const barExpenses = <?= json_encode(array_column($profitData, 'total_expenses')) ?>;
const barProfit = <?= json_encode(array_column($profitData, 'amount')) ?>;


// --- Options communes Chart.js ---
function baseLegend() {
  return {
    position: 'bottom',
    labels: { boxWidth: 10, boxHeight: 10, font: { size: 12 }, color: currentTextColor() }
  };
}
const commonOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: baseLegend(),
    title: { display: false },
    tooltip: { mode: 'index', intersect: false }
  },
  layout: { padding: 0 }
};

// --- Création graphiques ---
(function createCharts() {
  // Donut
  const profitCtx = document.getElementById('profitChart');
profitChart = new Chart(profitCtx, {
  type: 'doughnut',
  data: {
    labels: profitLabels,
    datasets: [{
      data: profitValues,
      backgroundColor: ['#2ecc71', '#1693e6ff', '#e67e22', '#9b59b6', '#f1c40f'],
      borderWidth: 0
    }]
  },
  options: {
    ...commonOptions,
    cutout: '60%',
    plugins: {
      legend: baseLegend(),
      tooltip: {
        callbacks: {
          label: function(context) {
            const value = context.raw.toLocaleString('fr-FR', { minimumFractionDigits: 0 });
            return `${context.label}: ${value} FCFA`;
          }
        }
      }
    }
  }
});


  // Line
  const yearCtx = document.getElementById('yearChart');
  yearChart = new Chart(yearCtx, {
    type: 'line',
    data: {
      labels: yearLabels,
      datasets: [
        {
          label: 'Revenu',
          data: yearIncome,
          borderColor: '#0008ffff',
          backgroundColor: 'rgba(0,198,255,0.15)',
          tension: 0.35,
          fill: true,
          pointRadius: 2,
          borderWidth: 2
        },
        {
          label: 'Impôt',
          data: yearTax,
          borderColor: '#f31344ff',
          backgroundColor: 'rgba(255,99,132,0.12)',
          tension: 0.35,
          fill: true,
          pointRadius: 2,
          borderWidth: 2
        }
      ]
    },
    options: {
      ...commonOptions,
      scales: {
        x: {
          grid: { display: false },
          ticks: { font: { size: 11 }, color: currentTextColor() }
        },
        y: {
          beginAtZero: true,
          grid: { color: gridColor() },
          ticks: { font: { size: 11 }, color: currentTextColor() }
        }
      }
    }
  });

  const barCtx = document.getElementById('barChart');
new Chart(barCtx, {
  type: 'bar',
  data: {
    labels: barLabels,
    datasets: [
      {
        label: 'Revenu',
        data: barIncome,
        backgroundColor: 'rgba(39, 66, 84, 0.6)'
      },
      {
        label: 'Dépenses',
        data: barExpenses,
        backgroundColor: 'rgba(243, 71, 108, 0.6)'
      },
      {
        label: 'Profit net',
        data: barProfit,
        backgroundColor: 'rgba(29, 220, 109, 0.6)'
      }
    ]
  },
  options: {
    ...commonOptions,
    scales: {
      y: {
        beginAtZero: true,
        grid: { color: gridColor() },
        ticks: { color: currentTextColor() }
      },
      x: {
        ticks: { color: currentTextColor() }
      }
    }
  }
});


})();
</script>

<script defer>
  const loginLabels = [<?php foreach ($loginHistory as $log) echo "'" . date('d/m', strtotime($log['login_time'])) . "',"; ?>];
const loginCounts = Array(loginLabels.length).fill(1); // simplifié

new Chart(document.getElementById('loginChart').getContext('2d'), {
  type: 'bar',
  data: {
    labels: loginLabels,
    datasets: [{
      label: 'Connexions',
      data: loginCounts,
      backgroundColor: 'rgba(0, 123, 255, 0.6)',
      borderRadius: 6
    }]
  },
  options: {
    responsive: true,
    plugins: {
      title: {
        display: true,
        text: 'Activité récente',
        font: { size: 16 }
      }
    },
    scales: {
      y: { beginAtZero: true }
    }
  }
});
</script>

</body>
</html>
