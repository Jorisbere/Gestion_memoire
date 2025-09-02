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
      background-image: url('assets/images/image.png');
      background-size: cover;
      background-position: center;
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
      margin-top: 140px;
    }

    .logo {
      width: 120px;
      margin-bottom: 20px;
    }

    h1 {
      font-size: 2.4em;
      color: #ffffffff;
      margin-bottom: 10px;
    }

    p {
      font-size: 1.1em;
      color: #ecf1f5ff;
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
      color: #f9f6f6ff;
      border-top: 1px solid #1a9ed7ff;
      margin-top: auto;
    }

    footer .links {
      margin-top: 8px;
    }

    footer .links a {
      color: #021322ff;
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
<<<<<<< HEAD
  <img src="assets/images/télécharger.png" alt="Logo Fiscal" class="logo">
  <h1>Bienvenue sur votre votre application de gestion des mémoires académiques de U-AUBEN </h1>
  <p></p>
=======
  <!-- <img src="assets/images/télécharger (1).png" alt="Logo Fiscal" class="logo"> -->
  <h1>Bienvenue sur votre espace de gestion académique</h1>
<p>Organisez vos mémoires, suivez les demandes de soutenance, planifiez les sessions et collaborez efficacement avec les encadrants et les étudiants grâce à une interface intuitive et sécurisée.</p>
>>>>>>> 284ed42c7761f536e73b861b5ec106dca0eb8b13

  <div class="buttons">
    <a href="register.php" class="button"><i class="fa-solid fa-user-plus"></i> S’inscrire</a>
    <a href="login.php" class="button"><i class="fa-solid fa-user"></i> Se connecter</a>
  </div>
</main>

<<<<<<< HEAD
<footer class="academic-footer">
  <div class="footer-content">
    <div class="footer-branding">
      <strong>Gestion de Mémoire</strong><br>
      <small>Plateforme académique pour le suivi des mémoires, protocoles et soutenances</small>
    </div>

    <div class="footer-links">
      <a href="help.php">📘 Aide</a>
      <a href="contact.php">📨 Contact</a>
      <a href="terms.php">📄 Conditions</a>
    </div>
  </div>

  <div class="footer-bottom">
    &copy; <?= date('Y') ?> Gestion de Mémoire. Tous droits réservés.
=======
<footer>
  &copy; <?= date('Y') ?> Gestion Memoire. Tous droits réservés.
  <div class="links">
    <a href="help.php"><i class="fa-solid fa-circle-question"></i> Aide</a>
    <a href="contact.php"><i class="fa-solid fa-envelope"></i> Contact</a>
    <a href="terms.php"><i class="fa-solid fa-file-contract"></i> Conditions</a>
>>>>>>> 284ed42c7761f536e73b861b5ec106dca0eb8b13
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
      <p2><strong>Comment accéder au site ?</strong><br> Cliquez sur “Se connecter” et identifiez-vous avec vos identifiants de connection.</p2>
      <p2><strong>Comment modifier un mémoire ou une demande de soutenance ?</strong><br> Une fois connecté, rendez-vous dans “Mes projets” pour éditer ou mettre à jour les informations.</p2>
      <p2><strong>Mes données sont-elles sécurisées ?</strong><br> Oui, elles sont hébergées sur un serveur sécurisé et protégées par des protocoles de chiffrement.</p2>

    </div>

    <div id="contact" class="tab-content">
      <form>
        <label for="email"><i class="fa-solid fa-envelope"></i> Votre email :</label>
        <input type="email" id="email" placeholder="exemple@domaine.com" required>

        <label for="message"><i class="fa-solid fa-comment"></i> Message :</label>
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
