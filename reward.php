<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Vérifier token
if (!isset($_GET['token']) || !isset($_SESSION['ad_token'])) {
    header("Location: /coins.php?error=invalid_token");
    exit;
}

$token = $_GET['token'];

if ($token !== $_SESSION['ad_token']) {
    header("Location: /coins.php?error=invalid_token");
    exit;
}

// Supprime le token pour éviter toute réutilisation
unset($_SESSION['ad_token']);

// ========= CONNEXION SQL =========
$db_host = 'localhost:3306';
$db_name = 'mttljx_zyrahostf_db';
$db_user = 'sti_moi';
$db_pass = 's3Fo6^36p';

try {
    $pdo = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8",
        $db_user,
        $db_pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (Exception $e) {
    header("Location: /coins.php?error=db_error");
    exit;
}


// ========= VÉRIFIER LIMITE QUOTIDIENNE =========
$max_ads_per_day = 10;

$stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM ad_logs 
    WHERE user_id = ? 
    AND DATE(created_at) = CURDATE()
");
$stmt->execute([$user_id]);
$ads_today = $stmt->fetchColumn();

if ($ads_today >= $max_ads_per_day) {
    header("Location: /coins.php?error=limit_reached");
    exit;
}


// ========= AJOUTER 1 COIN =========
$stmt = $pdo->prepare("UPDATE users SET coins = coins + 1 WHERE id = ?");
$stmt->execute([$user_id]);

// Mettre à jour la session
$_SESSION['coins'] = ($_SESSION['coins'] ?? 0) + 1;


// ========= LOG PUB =========
$stmt = $pdo->prepare("INSERT INTO ad_logs (user_id, created_at) VALUES (?, NOW())");
$stmt->execute([$user_id]);


// ========= REDIRECTION =========
header("Location: /coins.php?success=1");
exit;

?>
