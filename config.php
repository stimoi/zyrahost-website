<?php
// config.php
// Inclure ce fichier partout (require_once 'config.php')

session_start();

// --- CONFIG DB ---
$db_host = 'localhost:3306';
$db_name = 'mttljx_zyrahostf_db';
$db_user = 'Sti_moi';
$db_pass = '680ve7Qp$';

try {
    $pdo = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
        $db_user,
        $db_pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    // En prod, logger et afficher un message générique
    die("Erreur de connexion à la base de données.");
}

// --- CSRF ---
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function csrf_get_token(): string {
    return $_SESSION['csrf_token'];
}

function csrf_validate(string $token): bool {
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

// --- Helpers ---
function require_login() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

function json_response($data, int $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
