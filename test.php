<?php
session_start();
require_once 'includes/db.php'; // doit définir $conn (mysqli)

// 1) Vérifier la session
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int) $_SESSION['user_id'];

// 🔎 Récupération des infos utilisateur
$stmt = $conn->prepare("SELECT username, avatar, role FROM users WHERE id = ?");
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
$avatar = !empty($user['avatar']) ? htmlspecialchars($user['avatar']) : 'assets/images/utilisateur.png';
$role = $user['role'] ?? 'etudiant';

// 🚫 Si l'utilisateur est administrateur, rediriger vers dashboard
if ($role === 'administrateur') {
    header("Location: dashboard.php");
    exit();
}

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

/* 🧩 Contenu principal */
    main {
      flex: 1;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      text-align: center;
      padding: 40px 20px;
    }

    
header { margin-bottom: 18px; }
header h1 {
  font-size: 1.6rem;
  font-weight: 700;
  letter-spacing: 0.2px;
  animation: fadeIn 0.6s ease-out;
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
$username = $_SESSION['username'] ?? 'Utilisateur';
$role = $_SESSION['role'] ?? 'Invité';

$initiales = '';
if (empty($avatar)) {
    // Générer les initiales : première lettre de chaque mot du nom
    $mots = explode(' ', trim($username));
    foreach ($mots as $mot) {
        $initiales .= strtoupper(mb_substr($mot, 0, 1));
    }
    echo '<div class="avatar-initiales" aria-label="Avatar avec initiales" title="Avatar">'.htmlspecialchars($initiales).'</div>';
} else {
    echo '<img src="'.htmlspecialchars($avatar).'" alt="Avatar de '.htmlspecialchars($username).'" class="avatar" loading="lazy">';
}
  ?>
  <span class="username"><?= htmlspecialchars($username) ?></span>
  <p style="text-align:right; font-style:italic;">Rôle : <?= htmlspecialchars($role) ?></p>
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
  <?php if ($role === 'etudiant'): ?>
    <a href="forms/salaried.php">📄 Dépot de protocole</a>
    <a href="forms/independent.php">🧑‍💼 Demande de Soutenance</a>
    <a href="forms/company.php">🏢 Déposer mémoire</a>
  <?php endif; ?>

  <?php if ($role === 'DM'): ?>
    <a href="dm_section.php">📁 Espace DM</a>
  <?php endif; ?>

  <a href="forms/history.php">📊 Memoires Archiver</a>
  <a href="logout.php">🚪 Déconnexion</a>
</nav>

  </aside>

  <main>
  <img src="assets/images/bJxrtjp71wqT9CVJ.webp" alt="Logo Fiscal" class="logo">
  <h1>Bienvenue sur votre tableau fiscal</h1>
  <p>Gérez vos déclarations, visualisez vos revenus, suivez vos performances et accédez à des outils fiscaux intelligents.</p>

</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
  // --- Mode sombre avec persistance ---
  const darkToggleBtn = document.getElementById('darkToggle');

  if (localStorage.getItem("darkMode") === "true") {
    document.body.classList.add("dark-mode");
  }

  darkToggleBtn.addEventListener('click', function () {
    document.body.classList.toggle('dark-mode');
    localStorage.setItem('darkMode', document.body.classList.contains('dark-mode'));
    updateChartTheme();
  });

  // --- Références Chart.js pour mise à jour du thème ---
  let profitChart, yearChart;

  function currentTextColor() {
    return getComputedStyle(document.body).color;
  }

  function gridColor() {
    return document.body.classList.contains('dark-mode')
      ? getComputedStyle(document.documentElement).getPropertyValue('--grid-dark')
      : getComputedStyle(document.documentElement).getPropertyValue('--grid-light');
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
  const barLabels = <?= json_encode(array_column($profitData, 'source'), JSON_UNESCAPED_UNICODE) ?>;
  const barIncome = <?= json_encode(array_map(fn($r) => $r['amount'] + $r['total_expenses'], $profitData)) ?>;
  const barExpenses = <?= json_encode(array_column($profitData, 'total_expenses')) ?>;

  // --- Options communes Chart.js ---
  function baseLegend() {
    return {
      position: 'bottom',
      labels: {
        boxWidth: 10,
        boxHeight: 10,
        font: { size: 12 },
        color: currentTextColor()
      }
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
});
</script>


</body>
</html>