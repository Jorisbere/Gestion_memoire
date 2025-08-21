$username = $_SESSION['username'] ?? 'Utilisateur';

<p class="welcome">Bienvenue <?= htmlspecialchars($username) ?> 👋</p>