<?php
// index.php - Page d'accueil non connectée
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Tableau Fiscal - Accueil</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      font-family: 'Segoe UI', sans-serif;
      background: linear-gradient(to right, #f0f4f8, #d9e2ec);
      display: flex;
      flex-direction: column;
      min-height: 100vh;
    }

    /* 🔝 Barre du haut */
    header {
      position: relative;
      width: 100%;
      height: 60px;
      background-color: transparent;
    }

    .help-icon {
      position: absolute;
      top: 20px;
      right: 30px;
      font-size: 20px;
      color: #555;
      cursor: pointer;
      transition: color 0.3s ease;
      z-index: 10;
    }

    .help-icon:hover {
      color: #0078D7;
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

    .logo {
      width: 120px;
      margin-bottom: 20px;
    }

    h1 {
      font-size: 2.4em;
      color: #2c3e50;
      margin-bottom: 10px;
    }

    p {
      font-size: 1.1em;
      color: #34495e;
      margin-bottom: 30px;
      max-width: 600px;
    }

    .buttons {
      display: flex;
      gap: 20px;
      flex-wrap: wrap;
      justify-content: center;
    }

    a.button {
      padding: 12px 24px;
      background-color: #0078D7;
      color: white;
      text-decoration: none;
      border-radius: 8px;
      font-weight: bold;
      font-size: 16px;
      transition: background-color 0.3s ease, transform 0.2s ease;
    }

    a.button:hover {
      background-color: #005fa3;
      transform: translateY(-2px);
    }

    /* 🔚 Bas de page */
    footer {
      background-color: transparent;
      padding: 15px 30px;
      text-align: center;
      font-size: 13px;
      color: #777;
      border-top: 1px solid #e0e0e0;
    }

    footer .links {
      margin-top: 8px;
    }

    footer .links a {
      color: #0078D7;
      text-decoration: none;
      margin: 0 10px;
      font-weight: 500;
    }

    footer .links a:hover {
      text-decoration: underline;
    }

    .popup-overlay {
  position: fixed;
  top: 0; left: 0;
  width: 100%; height: 100%;
  background: rgba(0,0,0,0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
}

.popup-content {
  background: #fff;
  padding: 30px;
  border-radius: 12px;
  width: 90%;
  max-width: 500px;
  position: relative;
  box-shadow: 0 8px 20px rgba(0,0,0,0.2);
}

.close-btn {
  position: absolute;
  top: 12px;
  right: 16px;
  font-size: 24px;
  cursor: pointer;
  color: #555;
}

.tabs {
  display: flex;
  gap: 10px;
  margin-bottom: 20px;
}

.tab {
  flex: 1;
  padding: 10px;
  background-color: #f0f4f8;
  border: none;
  cursor: pointer;
  font-weight: bold;
  border-radius: 6px;
}

.tab.active {
  background-color: #0078D7;
  color: white;
}

.tab-content {
  display: none;
}

.tab-content.active {
  display: block;
}

form input, form textarea {
  width: 100%;
  padding: 10px;
  margin-bottom: 15px;
  border-radius: 6px;
  border: 1px solid #ccc;
  font-size: 14px;
}

form button {
  background-color: #0078D7;
  color: white;
  padding: 10px 16px;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-weight: bold;
}

form button:hover {
  background-color: #005fa3;
}


    @media (max-width: 600px) {
      h1 { font-size: 1.8em; }
      p { font-size: 1em; }
      .buttons { flex-direction: column; }
    }
  </style>
</head>
<body>

<header>
  <div class="help-icon" title="Besoin d'aide ?" onclick="openHelpPopup()">
    <i class="fas fa-circle-question"></i>
  </div>
</header>


<main>
  <img src="assets/images/bJxrtjp71wqT9CVJ.webp" alt="Logo Fiscal" class="logo">
  <h1>Bienvenue sur votre tableau fiscal</h1>
  <p>Gérez vos déclarations, visualisez vos revenus, suivez vos performances et accédez à des outils fiscaux intelligents.</p>

  <div class="buttons">
    <a href="register.php" class="button">📝 S’inscrire</a>
    <a href="login.php" class="button">📊 Découvrir le dashboard</a>
  </div>
</main>

<footer>
  &copy; <?= date('Y') ?> Tableau Fiscal. Tous droits réservés.
  <div class="links">
    <a href="help.php">Aide</a>
    <a href="contact.php">Contact</a>
    <a href="terms.php">Conditions</a>
  </div>
</footer>
<!-- 🧠 Popup Aide -->
<div id="helpPopup" class="popup-overlay" style="display: none;">
  <div class="popup-content">
    <span class="close-btn" onclick="closeHelpPopup()">&times;</span>
    <h2>Centre d'aide</h2>

    <div class="tabs">
      <button onclick="showTab('faq')" class="tab active">FAQ</button>
      <button onclick="showTab('contact')" class="tab">Contact</button>
    </div>

    <div id="faq" class="tab-content active">
      <p><strong>Comment accéder au dashboard ?</strong><br> Cliquez sur “Découvrir le dashboard” et connectez-vous.</p>
      <p><strong>Comment modifier mes déclarations ?</strong><br> Une fois connecté, allez dans “Historique”.</p>
      <p><strong>Mes données sont-elles sécurisées ?</strong><br> Oui, elles sont stockées localement et protégées.</p>
    </div>

    <div id="contact" class="tab-content">
      <form>
        <label for="email">📧 Votre email :</label>
        <input type="email" id="email" placeholder="exemple@domaine.com" required>

        <label for="message">✉️ Message :</label>
        <textarea id="message" rows="4" placeholder="Votre question ou remarque..." required></textarea>

        <button type="submit">Envoyer</button>
      </form>
    </div>
  </div>
</div>

<script>
function openHelpPopup() {
  document.getElementById('helpPopup').style.display = 'flex';
}

function closeHelpPopup() {
  document.getElementById('helpPopup').style.display = 'none';
}

function showTab(tabId) {
  document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
  document.querySelectorAll('.tab').forEach(el => el.classList.remove('active'));
  document.getElementById(tabId).classList.add('active');
  document.querySelector(`.tab[onclick="showTab('${tabId}')"]`).classList.add('active');
}
</script>

</body>
</html>
