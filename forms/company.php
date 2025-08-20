<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Récupérer les déclarants liés à l'utilisateur
$stmt = $pdo->prepare("SELECT id, fullname, email, fiscal_id FROM declarant WHERE user_id = ?");
$stmt->execute([$user_id]);
$declarants = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Si un déclarant est sélectionné
$selected = $_GET['declarant_id'] ?? '';
$selectedDeclarant = null;

if ($selected) {
    foreach ($declarants as $d) {
        if ($d['id'] == $selected) {
            $selectedDeclarant = $d;
            break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Déclaration Entreprise</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #eef2f7;
            margin: 0;
            padding: 0;
            animation: fadeIn 0.6s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .container {
            max-width: 650px;
            margin: 50px auto;
            background: #fff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }
        h2 {
            text-align: center;
            color: #0078D7;
            margin-bottom: 30px;
        }
        label {
            font-weight: bold;
            display: block;
            margin-bottom: 6px;
            color: #333;
        }
        input, select {
            width: 100%;
            padding: 12px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 15px;
            background-color: #fdfdfd;
        }
        input:focus, select:focus {
            border-color: #0078D7;
            outline: none;
            background-color: #f0f8ff;
        }
        .btn-group {
            display: flex;
            justify-content: space-between;
            gap: 10px;
        }
        button {
            flex: 1;
            background-color: #0078D7;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        button:hover {
            background-color: #005fa3;
        }
        .back-btn {
            background-color: #6c757d;
        }
        .back-btn:hover {
            background-color: #5a6268;
        }
        @media (max-width: 600px) {
            .container {
                margin: 20px;
                padding: 25px;
            }
            .btn-group {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <h2>🏢 Formulaire de déclaration – Entreprise</h2>

    <!-- Sélection du déclarant -->
    <form method="GET" action="company.php" style="margin-bottom: 20px;">
        <label for="declarant_id">👤 Sélectionner un déclarant :</label>
        <select name="declarant_id" id="declarant_id" onchange="this.form.submit()">
            <option value="">-- Choisir --</option>
            <?php foreach ($declarants as $d): ?>
                <option value="<?= $d['id'] ?>" <?= ($selected == $d['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($d['fullname']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>

    <!-- Formulaire fiscal -->
    <form method="POST" action="submit_company.php">
        <input type="hidden" name="declarant_id" value="<?= $selectedDeclarant['id'] ?? '' ?>">

        <label for="fullname">Nom complet :</label>
        <input type="text" name="fullname" id="fullname" required value="<?= htmlspecialchars($selectedDeclarant['fullname'] ?? '') ?>">

        <label for="email">Email :</label>
        <input type="email" name="email" id="email" required value="<?= htmlspecialchars($selectedDeclarant['email'] ?? '') ?>">

        <label for="fiscal_id">Numéro fiscal :</label>
        <input type="text" name="fiscal_id" id="fiscal_id" required value="<?= htmlspecialchars($selectedDeclarant['fiscal_id'] ?? '') ?>">

        <label for="year">📅 Année fiscale :</label>
        <input type="number" name="year" id="year" required min="2000" max="2099" value="<?= date('Y') ?>">

        <label for="income">💼 Revenu total :</label>
        <input type="number" step="0.01" name="income" id="income" required min="0" placeholder="Ex: 500000.00">

        <label for="expenses">📉 Dépenses opérationnelles :</label>
        <input type="number" step="0.01" name="expenses" id="expenses" min="0" placeholder="Ex: 120000.00">

        <label for="employees">👥 Nombre d'employés :</label>
        <input type="number" name="employees" id="employees" min="0" max="10000" placeholder="Ex: 25">

        <div class="btn-group">
            <button type="button" class="back-btn" onclick="window.location.href='../dashboard.php'">🔙 Retour</button>
            <button type="submit">📤 Soumettre</button>
        </div>
    </form>
</div>

</body>
</html>
