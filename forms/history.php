<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$filterYear = $_GET['year'] ?? '';

// Requête SQL avec filtre
$sql = "SELECT d.*, p.file_path FROM declarations d
        LEFT JOIN pdf_documents p ON d.id = p.declaration_id
        WHERE d.user_id = ?";
$params = [$user_id];

if ($filterYear !== '') {
    $sql .= " AND d.year = ?";
    $params[] = $filterYear;
}

$sql .= " ORDER BY d.declaration_date DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$declarations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupération des années disponibles
$years = $pdo->prepare("SELECT DISTINCT year FROM declarations WHERE user_id = ? ORDER BY year DESC");
$years->execute([$user_id]);
$yearOptions = $years->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Historique des déclarations</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    body {
      font-family: 'Inter', sans-serif;
      background: #f0f2f5;
      margin: 0;
      padding: 20px;
      color: #333;
    }

    h2, h3 {
      text-align: center;
      margin-bottom: 20px;
      font-weight: 600;
    }

    .filter-form {
      display: flex;
      justify-content: center;
      gap: 12px;
      margin-bottom: 30px;
      flex-wrap: wrap;
    }

    select, button {
      padding: 10px 14px;
      font-size: 15px;
      border-radius: 8px;
      border: none;
      box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }

    button {
      background-color: #4CAF50;
      color: white;
      cursor: pointer;
      transition: background 0.3s ease;
    }

    button:hover {
      background-color: #388e3c;
    }

    .declaration-card {
      background: white;
      border-radius: 10px;
      padding: 20px;
      margin-bottom: 20px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.05);
      transition: transform 0.2s ease;
    }

    .declaration-card:hover {
      transform: scale(1.02);
    }

    .card-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 20px;
    }

    .charts {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 30px;
      margin-top: 40px;
    }

    canvas {
      background: white;
      border-radius: 10px;
      padding: 10px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }

    .btn-back {
      display: inline-block;
      margin-bottom: 20px;
      padding: 10px 16px;
      background-color: #607d8b;
      color: white;
      text-decoration: none;
      border-radius: 8px;
    }

    .download-btn {
  display: inline-block;
  margin-top: 12px;
  padding: 10px 14px;
  background-color: #0078D7;
  color: white;
  text-decoration: none;
  border-radius: 6px;
  font-weight: bold;
  transition: background 0.3s ease;
}

.download-btn:hover {
  background-color: #005fa3;
}

.chart-controls button {
  margin: 0 6px;
  padding: 10px 14px;
  font-size: 14px;
  border: none;
  border-radius: 6px;
  background-color: #0078D7;
  color: white;
  cursor: pointer;
  transition: background 0.3s ease;
}

.chart-controls button:hover {
  background-color: #005fa3;
}

canvas {
  background: white;
  border-radius: 10px;
  padding: 10px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.05);
  opacity: 0;
  transition: opacity 0.5s ease;
  position: absolute;
  left: 0;
  right: 0;
  margin: auto;
  max-width: 100%;
}

canvas.active {
  opacity: 1;
  position: relative;
}

.charts {
  position: relative;
  min-height: 400px;
}


  </style>
</head>
<body>

<a href="../dashboard.php" class="btn-back">← Retour au dashboard</a>
<h2>Historique de vos déclarations fiscales</h2>

<form method="GET" action="history.php" class="filter-form">
  <select name="year">
    <option value="">Toutes les années</option>
    <?php foreach ($yearOptions as $y): 
      $selected = ($filterYear == $y['year']) ? 'selected' : '';
      echo "<option value='{$y['year']}' $selected>{$y['year']}</option>";
    endforeach; ?>
  </select>
  <button type="submit">Filtrer</button>
</form>

<div class="card-grid">
  <?php foreach ($declarations as $row): ?>
    <div class="declaration-card">
      <h3>📅 <span style="color:#0078D7"><?= $row['year'] ?></span> – <?= ucfirst($row['profile']) ?></h3>
      <p>👤 <strong>Déclarant :</strong> <?= htmlspecialchars($row['fullname']) ?></p>
      <p>📧 <strong>Email :</strong> <?= htmlspecialchars($row['email']) ?></p>
      <p>🆔 <strong>Numéro fiscal :</strong> <?= htmlspecialchars($row['fiscal_id']) ?></p>
      <hr>
      <p>💰 <strong>Revenu :</strong> <span style="color:green"><?= number_format($row['income'], 0, ',', ' ') ?> FCFA</span></p>
      <p>📉 <strong>Dépenses :</strong> <span style="color:#e67e22"><?= number_format($row['expenses'], 0, ',', ' ') ?> FCFA</span></p>
      <p>👨‍👩‍👧 <strong>Personnes à charge :</strong> <?= $row['dependents'] ?></p>
      <p>🧾 <strong>Impôt dû :</strong> <span style="color:red"><?= number_format($row['tax_due'], 0, ',', ' ') ?> FCFA</span></p>
      <p>🗓️ <strong>Date :</strong> <?= date('d/m/Y à H:i', strtotime($row['declaration_date'])) ?></p>

      <div style="margin-top: 12px; display: flex; gap: 10px; flex-wrap: wrap;">
        <a href="../generate_pdf.php?id=<?= $row['id'] ?>" class="download-btn" target="_blank">📄 Télécharger PDF</a>
        <a href="edit_declaration.php?id=<?= $row['id'] ?>" class="download-btn" style="background-color:#f39c12;">✏️ Modifier</a>
      </div>
    </div>
  <?php endforeach; ?>
</div>




<h3>📊 Visualisation fiscale</h3>

<div class="chart-controls" style="text-align:center; margin-bottom: 20px;">
  <button onclick="showChart('taxChart')">💸 Impôt dû</button>
  <button onclick="showChart('incomeChart')">📈 Revenu</button>
  <button onclick="showChart('expenseChart')">📉 Dépenses</button>
</div>

<div class="charts">
  <canvas id="taxChart" class="active"></canvas>
  <canvas id="incomeChart" class="active"></canvas>
  <canvas id="expenseChart" class="active"></canvas>

</div>


<script>
const labels = [<?php foreach ($declarations as $d) echo "'" . $d['year'] . "',"; ?>];
const taxData = [<?php foreach ($declarations as $d) echo $d['tax_due'] . ","; ?>];
const incomeData = [<?php foreach ($declarations as $d) echo $d['income'] . ","; ?>];
const expenseData = [<?php foreach ($declarations as $d) echo $d['expenses'] . ","; ?>];

function createGradient(ctx, color) {
  const gradient = ctx.createLinearGradient(0, 0, 0, 400);
  gradient.addColorStop(0, color);
  gradient.addColorStop(1, 'rgba(255,255,255,0)');
  return gradient;
}

function renderChart(id, label, data, color, type = 'bar') {
  const ctx = document.getElementById(id).getContext('2d');
  new Chart(ctx, {
    type: type,
    data: {
      labels: labels,
      datasets: [{
        label: label,
        data: data,
        backgroundColor: createGradient(ctx, color),
        borderColor: color,
        borderWidth: 2,
        fill: true,
        tension: 0.4
      }]
    },
    options: {
      responsive: true,
      plugins: {
        title: {
          display: true,
          text: label,
          font: { size: 16 }
        },
        tooltip: {
          mode: 'index',
          intersect: false
        }
      },
      interaction: {
        mode: 'nearest',
        axis: 'x',
        intersect: false
      },
      scales: {
        y: {
          beginAtZero: true
        }
      }
    }
  });
}

// ✅ Fonction de basculement des graphiques
function showChart(chartId) {
  const charts = ['taxChart', 'incomeChart', 'expenseChart'];
  charts.forEach(id => {
    const canvas = document.getElementById(id);
    if (canvas) {
      canvas.classList.remove('active');
      canvas.style.zIndex = (id === chartId) ? 1 : 0;
    }
  });

  // Légère pause pour déclencher l’animation
  setTimeout(() => {
    const target = document.getElementById(chartId);
    if (target) {
      target.classList.add('active');
    }
  }, 50);
}


// ✅ Initialisation des graphiques
renderChart('taxChart', 'Impôt dû (FCFA)', taxData, 'rgba(255,99,132,0.8)', 'line');
renderChart('incomeChart', 'Revenu (FCFA)', incomeData, 'rgba(54,162,235,0.8)');
renderChart('expenseChart', 'Dépenses (FCFA)', expenseData, 'rgba(255,206,86,0.8)');
</script>


</body>
</html>
