<?php
/*
 coins.php - Page principale des méthodes de gain de coins
 - Affiche toutes les méthodes disponibles
 - Permet de choisir comment gagner des coins
 - Affiche le solde actuel de l'utilisateur
*/

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

// Paramètres DB
$db_host = 'localhost:3306';
$db_name = 'mttljx_zyrahostf_db';
$db_user = 'sti_moi';
$db_pass = 'pj32~lH36';

try {
    $pdo = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8",
        $db_user,
        $db_pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (Exception $e) {
    die("Erreur de connexion à la base de données.");
}

$user_id = (int)$_SESSION['user_id'];

// Récupérer infos utilisateur
$stmt = $pdo->prepare("SELECT username, coins FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header("Location: /login.php");
    exit;
}

$username = htmlspecialchars($user['username']);
$coins = (int)$user['coins'];

// Vérifier les récompenses disponibles aujourd'hui
$stmt = $pdo->prepare("SELECT COUNT(*) FROM ad_logs WHERE user_id = ? AND DATE(created_at) = CURDATE()");
$stmt->execute([$user_id]);
$ads_today = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM discord_status_rewards WHERE user_id = ? AND DATE(created_at) = CURDATE()");
$stmt->execute([$user_id]);
$discord_today = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM daily_rewards WHERE user_id = ? AND DATE(created_at) = CURDATE()");
$stmt->execute([$user_id]);
$daily_today = (int)$stmt->fetchColumn();

$max_ads_per_day = 10;
$remaining_ads = max(0, $max_ads_per_day - $ads_today);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Coins - ZyraHost</title>
<style>
:root{
  --bg:#0f0f12;
  --card:#121216;
  --muted:#9aa0a6;
  --accent:#5865F2;
  --accent-2:#6d7cff;
  --gold:#ffd700;
  --success:#10b981;
  --discord:#5865F2;
  --daily:#059669;
  --premium:#8b5cf6;
  --referral:#06b6d4;
}
*{box-sizing:border-box}
body{
  margin:0;
  font-family:Inter, "Segoe UI", Roboto, Arial, sans-serif;
  background: linear-gradient(180deg, var(--bg) 0%, #070709 100%);
  color:#e8eef6;
  -webkit-font-smoothing:antialiased;
}
.container{
  max-width:1200px;
  margin:36px auto;
  padding:20px;
}
.header{
  text-align:center;
  margin-bottom:40px;
}
.logo{
  width:80px;
  height:80px;
  border-radius:16px;
  background:linear-gradient(135deg,var(--accent),var(--accent-2));
  display:flex;
  align-items:center;
  justify-content:center;
  font-weight:700;
  color:white;
  font-size:32px;
  margin:0 auto 20px;
  box-shadow:0 8px 24px rgba(88,101,242,0.16);
}
h1{
  font-size:32px;
  margin:0;
  color:#fff;
}
.subtitle{
  color:var(--muted);
  font-size:16px;
  margin-top:12px;
}
.balance-card{
  background: linear-gradient(135deg, rgba(255,215,0,0.1), rgba(255,215,0,0.05));
  border:1px solid rgba(255,215,0,0.2);
  padding:24px;
  border-radius:16px;
  text-align:center;
  margin-bottom:40px;
  box-shadow: 0 8px 24px rgba(255,215,0,0.08);
}
.balance-amount{
  font-size:48px;
  font-weight:700;
  color:var(--gold);
  margin:0;
}
.balance-label{
  color:var(--muted);
  font-size:16px;
  margin-top:8px;
}
.methods-grid{
  display:grid;
  grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
  gap:24px;
  margin-bottom:40px;
}
.method-card{
  background: linear-gradient(180deg, rgba(255,255,255,0.02), rgba(255,255,255,0.01));
  border:1px solid rgba(255,255,255,0.03);
  padding:24px;
  border-radius:16px;
  transition:transform 0.2s ease, box-shadow 0.2s ease;
  cursor:pointer;
  position:relative;
  overflow:hidden;
}
.method-card:hover{
  transform:translateY(-4px);
  box-shadow: 0 12px 28px rgba(2,6,23,0.6);
}
.method-card::before{
  content:'';
  position:absolute;
  top:0;
  left:0;
  right:0;
  height:4px;
  background:linear-gradient(90deg,var(--accent),var(--accent-2));
}
.method-card.discord::before{
  background:linear-gradient(90deg,var(--discord),#7289DA);
}
.method-card.daily::before{
  background:linear-gradient(90deg,var(--success),var(--daily));
}
.method-card.premium::before{
  background:linear-gradient(90deg,var(--premium),#7c3aed);
}
.method-card.shop::before{
  background:linear-gradient(90deg,var(--gold),#f59e0b);
}
.method-card.referral::before{
  background:linear-gradient(90deg,var(--referral),#0891b2);
}
.method-icon{
  width:48px;
  height:48px;
  border-radius:12px;
  display:flex;
  align-items:center;
  justify-content:center;
  font-size:24px;
  margin-bottom:16px;
}
.method-icon.pub{
  background:linear-gradient(135deg,var(--accent),var(--accent-2));
}
.method-icon.discord{
  background:linear-gradient(135deg,var(--discord),#7289DA);
}
.method-icon.daily{
  background:linear-gradient(135deg,var(--success),var(--daily));
}
.method-icon.premium{
  background:linear-gradient(135deg,var(--premium),#7c3aed);
}
.method-icon.shop{
  background:linear-gradient(135deg,var(--gold),#f59e0b);
}
.method-icon.referral{
  background:linear-gradient(135deg,var(--referral),#0891b2);
}
.method-title{
  font-size:20px;
  font-weight:700;
  margin:0 0 8px 0;
}
.method-description{
  color:var(--muted);
  font-size:14px;
  line-height:1.5;
  margin-bottom:16px;
}
.method-reward{
  display:inline-block;
  background:rgba(255,215,0,0.1);
  color:var(--gold);
  padding:6px 12px;
  border-radius:8px;
  font-weight:600;
  font-size:14px;
  margin-bottom:16px;
}
.method-status{
  font-size:13px;
  color:var(--muted);
  margin-bottom:16px;
}
.method-status.available{
  color:var(--success);
}
.method-status.limited{
  color:#f59e0b;
}
.method-status.unavailable{
  color:#ef4444;
}
.method-btn{
  width:100%;
  background:var(--accent);
  border:none;
  color:white;
  padding:12px 20px;
  border-radius:10px;
  font-weight:700;
  font-size:14px;
  cursor:pointer;
  transition:all 0.2s ease;
  text-decoration:none;
  display:block;
  text-align:center;
}
.method-btn:hover{
  background:var(--accent-2);
  transform:translateY(-1px);
}
.method-btn:disabled{
  opacity:.5;
  cursor:not-allowed;
  transform:none;
}
.method-btn.discord{
  background:var(--discord);
}
.method-btn.discord:hover{
  background:#7289DA;
}
.method-btn.daily{
  background:var(--success);
}
.method-btn.daily:hover{
  background:var(--daily);
}
.method-btn.premium{
  background:var(--premium);
}
.method-btn.premium:hover{
  background:#7c3aed;
}
.method-btn.shop{
  background:var(--gold);
  color:var(--bg);
}
.method-btn.shop:hover{
  background:#f59e0b;
}
.method-btn.referral{
  background:var(--referral);
}
.method-btn.referral:hover{
  background:#0891b2;
}
.stats-section{
  background:rgba(255,255,255,0.02);
  border:1px solid rgba(255,255,255,0.03);
  padding:24px;
  border-radius:16px;
  margin-bottom:40px;
}
.stats-title{
  font-size:18px;
  font-weight:700;
  margin:0 0 16px 0;
}
.stats-grid{
  display:grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap:16px;
}
.stat-item{
  text-align:center;
  padding:16px;
  background:rgba(255,255,255,0.02);
  border-radius:12px;
}
.stat-value{
  font-size:24px;
  font-weight:700;
  color:var(--accent);
  margin:0;
}
.stat-label{
  color:var(--muted);
  font-size:13px;
  margin-top:4px;
}
.footer{
  text-align:center;
  color:var(--muted);
  font-size:14px;
  margin-top:40px;
}
.footer a{
  color:var(--accent);
  text-decoration:none;
}
.footer a:hover{
  text-decoration:underline;
}
@media(max-width:768px){
  .container{padding:16px}
  .methods-grid{grid-template-columns:1fr; gap:16px}
  .balance-amount{font-size:36px}
  h1{font-size:24px}
}
</style>
</head>
<body>
<div class="container">
  <div class="header">
    <div class="logo">💰</div>
    <h1>Centre des Coins</h1>
    <div class="subtitle">Choisis ta méthode pour gagner des coins et débloquer des fonctionnalités premium</div>
  </div>

  <div class="balance-card">
    <div class="balance-amount"><?php echo number_format($coins); ?></div>
    <div class="balance-label">coins disponibles</div>
  </div>

  <div class="methods-grid">
    <!-- Méthode 1: Publicités -->
    <div class="method-card">
      <div class="method-icon pub">🎥</div>
      <div class="method-title">Publicités</div>
      <div class="method-description">
        Regarde des publicités pour gagner des coins rapidement. Simple et efficace !
      </div>
      <div class="method-reward">+10 coins par pub</div>
      <div class="method-status <?php echo $remaining_ads > 0 ? 'available' : 'unavailable'; ?>">
        <?php echo $remaining_ads > 0 ? "✅ $remaining_ads pubs disponibles" : "❌ Limite atteinte aujourd'hui"; ?>
      </div>
      <a href="/coins/coins--pub.php" class="method-btn">
        <?php echo $remaining_ads > 0 ? 'Regarder une pub' : 'Revenir demain'; ?>
      </a>
    </div>

    <!-- Méthode 2: Statut Discord -->
    <div class="method-card discord">
      <div class="method-icon discord">💬</div>
      <div class="method-title">Statut Discord</div>
      <div class="method-description">
        Ajoute "zyrahost.fr | host bots gratuit" dans ton statut Discord pendant 1h.
      </div>
      <div class="method-reward">+5 coins par jour</div>
      <div class="method-status <?php echo $discord_today == 0 ? 'available' : 'unavailable'; ?>">
        <?php echo $discord_today == 0 ? "✅ Disponible maintenant" : "❌ Déjà utilisé aujourd'hui"; ?>
      </div>
      <a href="/coins/coins--discord.php" class="method-btn discord">
        <?php echo $discord_today == 0 ? 'Vérifier mon statut' : 'Revenir demain'; ?>
      </a>
    </div>

    <!-- Méthode 3: Connexion quotidienne -->
    <div class="method-card daily">
      <div class="method-icon daily">📅</div>
      <div class="method-title">Connexion Quotidienne</div>
      <div class="method-description">
        Connecte-toi chaque jour pour gagner des coins automatiquement.
      </div>
      <div class="method-reward">+3 coins par jour</div>
      <div class="method-status <?php echo $daily_today == 0 ? 'available' : 'unavailable'; ?>">
        <?php echo $daily_today == 0 ? "✅ Disponible maintenant" : "❌ Déjà claim aujourd'hui"; ?>
      </div>
      <a href="/coins/coins--daily.php" class="method-btn daily">
        <?php echo $daily_today == 0 ? 'Claim ma récompense' : 'Revenir demain'; ?>
      </a>
    </div>

    <!-- Méthode 4: Avantages Premium -->
    <div class="method-card premium">
      <div class="method-icon premium">⭐</div>
      <div class="method-title">Avantages Premium</div>
      <div class="method-description">
        Débloque des avantages de confort : pas de pubs, démarrage rapide, backups fréquents.
      </div>
      <div class="method-reward">À partir de 500 coins</div>
      <div class="method-status available">
        ✅ Disponible maintenant
      </div>
      <a href="/coins/coins--premium.php" class="method-btn premium">
        Voir les avantages
      </a>
    </div>

    <!-- Méthode 5: Boutique Coins -->
    <div class="method-card shop">
      <div class="method-icon shop">💎</div>
      <div class="method-title">Boutique Coins</div>
      <div class="method-description">
        Achète des coins directement avec des bundles intelligents et offres limitées.
      </div>
      <div class="method-reward">À partir de 2.99€</div>
      <div class="method-status available">
        ✅ Disponible maintenant
      </div>
      <a href="/coins/coins--shop.php" class="method-btn shop">
        Acheter des coins
      </a>
    </div>

    <!-- Méthode 6: Parrainage -->
    <div class="method-card referral">
      <div class="method-icon referral">👥</div>
      <div class="method-title">Parrainage</div>
      <div class="method-description">
        Invite tes amis et gagne des coins quand ils s'inscrivent et deviennent actifs.
      </div>
      <div class="method-reward">+50 à +200 coins par ami</div>
      <div class="method-status available">
        ✅ Disponible maintenant
      </div>
      <a href="/coins/coins--referral.php" class="method-btn referral">
        Inviter des amis
      </a>
    </div>
  </div>

  <div class="stats-section">
    <div class="stats-title">📊 Tes statistiques aujourd'hui</div>
    <div class="stats-grid">
      <div class="stat-item">
        <div class="stat-value"><?php echo $ads_today; ?>/<?php echo $max_ads_per_day; ?></div>
        <div class="stat-label">Publicités vues</div>
      </div>
      <div class="stat-item">
        <div class="stat-value"><?php echo $discord_today == 0 ? 'Non' : 'Oui'; ?></div>
        <div class="stat-label">Statut Discord vérifié</div>
      </div>
      <div class="stat-item">
        <div class="stat-value"><?php echo $daily_today == 0 ? 'Non' : 'Oui'; ?></div>
        <div class="stat-label">Récompense quotidienne</div>
      </div>
      <div class="stat-item">
        <div class="stat-value"><?php echo ($ads_today * 10) + ($discord_today * 5) + ($daily_today * 3); ?></div>
        <div class="stat-label">Coins gagnés aujourd'hui</div>
      </div>
    </div>
  </div>

  <div class="footer">
    <p>Besoin d'aide ? Contacte le support via le panel.</p>
    <p><a href="/client.php">← Retour au dashboard</a></p>
  </div>
</div>
</body>
</html>
