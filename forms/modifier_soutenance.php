<?php
// Ce script ne doit faire aucune redirection ni stockage de notification en session.
// Il doit simplement retourner un JSON pour que la page affiche la notification dynamiquement via JS
// et permettre d'actualiser automatiquement la page via JS après modification.

// Désactiver complètement l'affichage des erreurs
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Démarrer la session si nécessaire
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier la session
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'administrateur') {
    header("Location: ../login.php");
    exit();
}

require_once '../includes/db.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    die("ID invalide.");
}

// Récupération des données existantes
$stmt = $conn->prepare("SELECT ds.*, u.username AS etudiant, u.email, ds.titre, ds.encadrant 
                        FROM demandes_soutenance ds 
                        JOIN users u ON ds.user_id = u.id 
                        WHERE ds.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$demande = $result->fetch_assoc();

if (!$demande) {
    die("Demande introuvable.");
}

// Traitement AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    
    try {
        $date_soutenance = $_POST['date_soutenance'] . ' ' . $_POST['heure_soutenance'];
        $salle = trim($_POST['salle']);
        $session = trim($_POST['session_souhaitee']);

        // Validation des données
        if (empty($date_soutenance) || empty($salle)) {
            echo json_encode([
                'success' => false,
                'message' => "❌ Tous les champs sont obligatoires."
            ]);
            exit;
        }

        // Vérifier que la date n'est pas dans le passé
        if (strtotime($date_soutenance) < time()) {
            echo json_encode([
                'success' => false,
                'message' => "❌ La date de soutenance ne peut pas être dans le passé."
            ]);
            exit;
        }

        // Mise à jour de la demande
        $stmt = $conn->prepare("UPDATE demandes_soutenance SET date_soutenance = ?, salle = ?, session_souhaitee = ? WHERE id = ?");
        $stmt->bind_param("sssi", $date_soutenance, $salle, $session, $id);
        $stmt->execute();

        if ($stmt->affected_rows === 0) {
            echo json_encode([
                'success' => false,
                'message' => "❌ Aucune modification effectuée."
            ]);
            exit;
        }

        // Envoi d'email de notification de modification
        require_once '../includes/mailer.php';
        
        $email = $demande['email'];
        $nom = $demande['etudiant'] ?? 'Étudiant';
        $titre = $demande['titre'] ?? 'Mémoire';
        $encadrant = $demande['encadrant'] ?? 'l\'encadrant';
        $date_formatted = date("d/m/Y", strtotime($_POST['date_soutenance']));
        $heure_formatted = date("H:i", strtotime($_POST['heure_soutenance']));

        $sujet = '📝 Votre soutenance a été modifiée';
        $corps = "<p>Bonjour <strong>{$nom}</strong>,</p>
        <p>La programmation de votre soutenance de mémoire intitulée <em>\"{$titre}\"</em> a été modifiée.</p>
        
        <div style='background-color: #fff3cd; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #ffc107;'>
            <h3 style='color: #856404; margin-top: 0;'>📋 Nouveaux détails de la programmation</h3>
            <p><strong>📅 Date :</strong> {$date_formatted}</p>
            <p><strong>🕐 Heure :</strong> {$heure_formatted}</p>
            <p><strong>🏫 Salle :</strong> {$salle}</p>
            <p><strong>👨‍🏫 Encadrant :</strong> {$encadrant}</p>
            <p><strong>📚 Session :</strong> " . ucfirst($session) . "</p>
        </div>
        
        <p><strong>⚠️ Important :</strong></p>
        <ul>
            <li>Présentez-vous 15 minutes avant l'heure prévue</li>
            <li>Apportez votre support de présentation (clé USB, ordinateur portable)</li>
            <li>Préparez votre défense orale</li>
        </ul>
        
        <p>Merci de consulter votre espace étudiant pour plus d'informations.</p>
        <p>Cordialement,<br>L'équipe académique</p>";

        // Envoi de l'email
        $emailEnvoye = envoyerEmail($email, $sujet, $corps, true);

        // Retour JSON
        if ($emailEnvoye) {
            echo json_encode([
                'success' => true,
                'message' => "✅ Soutenance modifiée avec succès et email envoyé à $email",
                'id_demande' => $id,
                'date_soutenance' => $date_soutenance,
                'salle' => $salle,
                'session_souhaitee' => $session,
                'email_envoye' => true,
                'refresh' => true
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => "⚠️ Soutenance modifiée mais échec d'envoi de l'email à $email",
                'id_demande' => $id,
                'date_soutenance' => $date_soutenance,
                'salle' => $salle,
                'session_souhaitee' => $session,
                'email_envoye' => false,
                'refresh' => true
            ]);
        }

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => "❌ Erreur serveur: " . $e->getMessage()
        ]);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Modifier la programmation</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background: linear-gradient(90deg, #49c4e6ff 0%, #e5edf7ff 100%);
      padding: 40px;
      color: #333;
    }

    h2 {
      text-align: center;
      color: #0078D7;
      margin-bottom: 30px;
    }

    .form-container {
      max-width: 600px;
      margin: auto;
      background: #fff;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 4px 16px rgba(0,0,0,0.1);
      border: 1px solid #e1e5e9;
    }

    .form-group {
      margin-bottom: 20px;
    }

    label {
      display: block;
      margin-bottom: 8px;
      font-weight: 600;
      color: #555;
      font-size: 14px;
    }

    input, select {
      width: 100%;
      padding: 12px 16px;
      border: 2px solid #e1e5e9;
      border-radius: 8px;
      font-size: 14px;
      transition: all 0.3s ease;
      background-color: #f8f9fa;
      box-sizing: border-box;
    }

    input:focus, select:focus {
      outline: none;
      border-color: #0078D7;
      box-shadow: 0 0 0 3px rgba(0, 120, 215, 0.15);
      background-color: #fff;
    }

    .btn {
      padding: 14px 28px;
      background: linear-gradient(135deg, #28a745, #20c997);
      color: white;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-weight: 600;
      font-size: 15px;
      text-align: center;
      width: 100%;
      transition: all 0.3s ease;
      box-shadow: 0 2px 8px rgba(40, 167, 69, 0.3);
    }

    .btn:hover {
      background: linear-gradient(135deg, #20c997, #17a2b8);
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(40, 167, 69, 0.4);
    }

    .btn:active {
      transform: translateY(0);
    }

    .btn-retour {
      display: inline-block;
      padding: 10px 20px;
      background: linear-gradient(135deg, #6c757d, #5a6268);
      color: white;
      text-decoration: none;
      border-radius: 8px;
      font-weight: 600;
      margin-top: 15px;
      transition: all 0.3s ease;
      text-align: center;
    }

    .btn-retour:hover {
      background: linear-gradient(135deg, #5a6268, #495057);
      transform: translateY(-2px);
      text-decoration: none;
      color: white;
    }

    .back-button {
      text-align: center;
      margin-bottom: 30px;
    }

    .back-button a {
      text-decoration: none;
      color: #0078D7;
      font-weight: bold;
      font-size: 16px;
    }

    .back-button a:hover {
      text-decoration: underline;
    }

    /* ✅ Overlay de chargement */
    .loading-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(255, 255, 255, 0.8);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 9999;
    }
    .loading-box {
      background: #ffffff;
      border: 1px solid #ddd;
      border-radius: 10px;
      padding: 20px 30px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.15);
      display: flex;
      align-items: center;
      gap: 12px;
      color: #333;
      font-weight: 600;
    }
    .spinner {
      width: 24px;
      height: 24px;
      border: 3px solid #e3e3e3;
      border-top-color: #0078D7;
      border-radius: 50%;
      animation: spin 0.8s linear infinite;
    }
    @keyframes spin {
      to { transform: rotate(360deg); }
    }

    /* ✅ Notification */
    .notification {
      background-color: #d4edda;
      color: #155724;
      padding: 15px;
      border-radius: 8px;
      margin-bottom: 20px;
      text-align: center;
      font-weight: bold;
      display: none;
      position: relative;
      border: 1px solid #c3e6cb;
    }
    .notification.success {
      background-color: #d4edda;
      border-color: #28a745;
    }
    .notification.error {
      background-color: #f8d7da;
      border-color: #dc3545;
      color: #721c24;
    }
    .notification button {
      background: none;
      border: none;
      font-size: 16px;
      position: absolute;
      top: 10px;
      right: 15px;
      cursor: pointer;
      color: inherit;
    }

    .info-box {
      background: #e3f2fd;
      border: 1px solid #bbdefb;
      border-radius: 8px;
      padding: 15px;
      margin-bottom: 20px;
      color: #1565c0;
    }

    .info-box h4 {
      margin: 0 0 10px 0;
      color: #0d47a1;
    }

    .info-box p {
      margin: 5px 0;
      font-size: 14px;
    }

    @media (max-width: 768px) {
      body {
        padding: 20px;
      }
      
      .form-container {
        padding: 20px;
        margin: 0 10px;
      }
    }
  </style>
</head>
<body>
  <!-- <div class="back-button">
    <a href="programmer_soutenance.php">← Retour à la liste des soutenances</a>
  </div> -->

  <!-- ✅ Overlay de chargement -->
  <div id="loading-overlay" class="loading-overlay" aria-hidden="true">
    <div class="loading-box">
      <div class="spinner" aria-label="Chargement"></div>
      <span id="loading-text">Traitement en cours…</span>
    </div>
  </div>

  <h2><i class="fa-solid fa-pencil"></i> Modifier la programmation</h2>

  <div class="form-container">
    <!-- ✅ Notification -->
    <div id="notification" class="notification">
      <span id="notification-message"></span>
      <button onclick="document.getElementById('notification').style.display='none'">✖</button>
    </div>

    <!-- Informations de la soutenance -->
    <div class="info-box">
      <h4><i class="fa-solid fa-info-circle"></i> Informations de la soutenance</h4>
      <p><strong>Étudiant :</strong> <?= htmlspecialchars($demande['etudiant'] ?? '') ?></p>
      <p><strong>Titre :</strong> <?= htmlspecialchars($demande['titre'] ?? '') ?></p>
      <p><strong>Encadrant :</strong> <?= htmlspecialchars($demande['encadrant'] ?? '') ?></p>
    </div>

    <form id="modification-form" method="POST">
      <div class="form-group">
        <label for="date_soutenance">📅 Date :</label>
        <input type="date" name="date_soutenance" id="date_soutenance" 
               value="<?= htmlspecialchars(substr($demande['date_soutenance'] ?? '', 0, 10)) ?>" required>
      </div>

      <div class="form-group">
        <label for="heure_soutenance">🕐 Heure :</label>
        <input type="time" name="heure_soutenance" id="heure_soutenance" 
               value="<?= htmlspecialchars(substr($demande['date_soutenance'] ?? '', 11, 5)) ?>" required>
      </div>

      <div class="form-group">
        <label for="salle">🏫 Salle :</label>
        <input type="text" name="salle" id="salle" 
               value="<?= htmlspecialchars($demande['salle'] ?? '') ?>" required>
      </div>

      <div class="form-group">
        <label for="session_souhaitee">📚 Session :</label>
        <select name="session_souhaitee" id="session_souhaitee">
          <option value="">-- Choisir --</option>
          <option value="normale" <?= ($demande['session_souhaitee'] ?? '') === 'normale' ? 'selected' : '' ?>>Normale</option>
          <option value="anticipe" <?= ($demande['session_souhaitee'] ?? '') === 'anticipe' ? 'selected' : '' ?>>Anticipée</option>
        </select>
      </div>

      <button type="submit" class="btn">
        <i class="fa-solid fa-check"></i> Enregistrer les modifications
      </button>
    </form>

    <div style="text-align: center;">
      <a href="programmer_soutenance.php" class="btn-retour">
        <i class="fa-solid fa-arrow-left"></i> Retour à la liste
      </a>
    </div>
  </div>

  <script>
    // ✅ Gestion du loader
    const loadingOverlay = document.getElementById('loading-overlay');
    const loadingText = document.getElementById('loading-text');
    function showLoading(text = 'Traitement en cours…') {
      if (loadingText) loadingText.textContent = text;
      if (loadingOverlay) loadingOverlay.style.display = 'flex';
    }
    function hideLoading() {
      if (loadingOverlay) loadingOverlay.style.display = 'none';
    }

    // Affichage notification dynamique
    function showNotification(message, type = "success") {
      const notif = document.getElementById('notification');
      const msg = document.getElementById('notification-message');
      notif.className = 'notification ' + type;
      msg.textContent = message;
      notif.style.display = 'block';
    }

    // Gestion du formulaire de modification
    document.getElementById('modification-form').addEventListener('submit', function(e) {
      e.preventDefault();
      
      const date = document.getElementById('date_soutenance').value;
      const heure = document.getElementById('heure_soutenance').value;
      const salle = document.getElementById('salle').value;
      const session = document.getElementById('session_souhaitee').value;

      if (!date || !heure || !salle) {
        showNotification("Veuillez remplir tous les champs obligatoires.", "error");
        return;
      }

      showLoading('Modification de la soutenance en cours et envoi de l\'email…');
      
      const formData = new FormData();
      formData.append('date_soutenance', date);
      formData.append('heure_soutenance', heure);
      formData.append('salle', salle);
      formData.append('session_souhaitee', session);
      
      console.log('Envoi de la modification pour ID:', <?= $id ?>);
      
      fetch('modifier_soutenance.php?id=<?= $id ?>', {
        method: 'POST',
        body: formData
      })
      .then(response => {
        console.log('Réponse reçue:', response);
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
      })
      .then(data => {
        console.log('Données reçues:', data);
        if (data.success) {
          showNotification(data.message, "success");
          
          // Redirection après 2 secondes
          setTimeout(() => {
            window.location.href = 'programmer_soutenance.php';
          }, 2000);
          
        } else {
          showNotification(data.message, "error");
        }
      })
      .catch(error => {
        console.error('Erreur lors de la modification:', error);
        showNotification("Erreur lors de la modification: " + error.message, "error");
      })
      .finally(() => hideLoading());
    });
  </script>
</body>
</html>
