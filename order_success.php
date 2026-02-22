<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/order_functions.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Vérifier si un ID de commande est spécifié
if (!isset($_GET['order_id'])) {
    header('Location: dashboard.php');
    exit;
}

$orderId = $_GET['order_id'];
$userId = $_SESSION['user_id'];

// Récupérer les détails de la commande
$stmt = $pdo->prepare("
    SELECT o.*, p.name as product_name, p.price, p.billing_cycle 
    FROM orders o
    LEFT JOIN products p ON o.product_id = p.id
    WHERE o.id = ? AND o.user_id = ?
");

$stmt->execute([$orderId, $userId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commande confirmée - ZyraHost</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        :root {
            --neon-cyan: #00f3ff;
            --neon-purple: #bf00ff;
            --neon-pink: #ff0080;
            --neon-green: #39ff14;
            --dark-bg: #0a0a0f;
            --darker-bg: #050508;
            --card-bg: #13131a;
            --text-primary: #e8e8f0;
            --text-secondary: #a0a0b8;
        }

        body {
            background: linear-gradient(135deg, var(--darker-bg) 0%, var(--dark-bg) 100%);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            flex: 1;
        }

        /* Header */
        header {
            background-color: var(--dark-bg);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
            padding: 15px 0;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 1.8rem;
            color: var(--neon-cyan);
            text-shadow: 0 0 5px rgba(0, 243, 255, 0.4);
            text-decoration: none;
            font-weight: 700;
        }

        .nav-links {
            display: flex;
            gap: 25px;
            list-style: none;
        }

        .nav-links a {
            color: var(--text-secondary);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s, text-shadow 0.3s;
        }

        .nav-links a:hover {
            color: var(--neon-cyan);
            text-shadow: 0 0 8px rgba(0, 243, 255, 0.6);
        }

        .nav-links a.active {
            color: var(--neon-cyan);
            position: relative;
        }

        .nav-links a.active::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 100%;
            height: 2px;
            background: linear-gradient(90deg, var(--neon-cyan), var(--neon-purple));
        }

        /* Success Section */
        .success-container {
            text-align: center;
            max-width: 800px;
            margin: 5rem auto;
            padding: 3rem;
            background: var(--card-bg);
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(0, 243, 255, 0.1);
            position: relative;
            overflow: hidden;
        }

        .success-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--neon-green), var(--neon-cyan));
        }

        .success-icon {
            font-size: 5rem;
            color: var(--neon-green);
            margin-bottom: 1.5rem;
            text-shadow: 0 0 20px rgba(57, 255, 20, 0.5);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: var(--neon-cyan);
            background: linear-gradient(90deg, var(--neon-cyan), var(--neon-purple));
            -webkit-background-clip: text;
            -moz-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .order-details {
            background: rgba(0, 243, 255, 0.05);
            border-radius: 8px;
            padding: 1.5rem;
            margin: 2rem 0;
            text-align: left;
            border: 1px solid rgba(0, 243, 255, 0.1);
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid rgba(191, 0, 255, 0.1);
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            color: var(--text-secondary);
        }

        .detail-value {
            font-weight: 500;
        }

        .btn {
            display: inline-block;
            background: linear-gradient(45deg, var(--neon-cyan), var(--neon-purple));
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 243, 255, 0.3);
            position: relative;
            overflow: hidden;
            z-index: 1;
            margin: 0.5rem;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, var(--neon-purple), var(--neon-pink));
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: -1;
        }

        .btn:hover::before {
            opacity: 1;
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 7px 20px rgba(191, 0, 255, 0.4);
        }

        .btn-outline {
            background: transparent;
            border: 2px solid var(--neon-cyan);
            color: var(--neon-cyan);
            box-shadow: none;
        }

        .btn-outline:hover {
            background: rgba(0, 243, 255, 0.1);
            transform: translateY(-3px);
            box-shadow: 0 0 20px rgba(0, 243, 255, 0.3);
        }

        .btn-group {
            margin-top: 2rem;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 1rem;
        }

        /* Footer */
        footer {
            background: var(--darker-bg);
            color: var(--text-secondary);
            text-align: center;
            padding: 2rem 0;
            margin-top: auto;
            border-top: 1px solid rgba(191, 0, 255, 0.1);
        }

        .social-links {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin: 1.5rem 0;
        }

        .social-links a {
            color: var(--text-secondary);
            font-size: 1.5rem;
            transition: all 0.3s ease;
        }

        .social-links a:hover {
            color: var(--neon-cyan);
            transform: translateY(-3px);
            text-shadow: 0 0 15px rgba(0, 243, 255, 0.5);
        }

        .copyright {
            font-size: 0.9rem;
            opacity: 0.7;
            margin-top: 1rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .success-container {
                margin: 2rem auto;
                padding: 2rem 1.5rem;
            }

            h1 {
                font-size: 2rem;
            }

            .btn-group {
                flex-direction: column;
                align-items: center;
            }

            .btn {
                width: 100%;
                max-width: 300px;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <nav class="navbar">
                <a href="index.php" class="logo">Zyrahost</a>
                <ul class="nav-links">
                    <li><a href="index.php">Accueil</a></li>
                    <li><a href="offres.php">Nos Offres</a></li>
                    <li><a href="#services">Services</a></li>
                    <li><a href="#contact">Contact</a></li>
                    <li><a href="dashboard.php" class="btn btn-outline"><i class="fas fa-tachometer-alt"></i> Tableau de bord</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container">
        <div class="success-container">
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h1>Commande confirmée !</h1>
            <p style="font-size: 1.2rem; color: var(--text-secondary); margin-bottom: 2rem;">
                Merci pour votre commande. Votre serveur est en cours de déploiement.
            </p>
            
            <div class="order-details">
                <div class="detail-row">
                    <span class="detail-label">Numéro de commande :</span>
                    <span class="detail-value">#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Produit :</span>
                    <span class="detail-value"><?php echo htmlspecialchars($order['product_name']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Prix :</span>
                    <span class="detail-value"><?php echo number_format($order['price'], 2, ',', ' '); ?>€ / <?php echo $order['billing_cycle'] === 'yearly' ? 'an' : 'mois'; ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Statut :</span>
                    <span class="detail-value" style="color: var(--neon-green);">
                        <i class="fas fa-check-circle"></i> Payé
                    </span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Date :</span>
                    <span class="detail-value"><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></span>
                </div>
            </div>

            <p style="margin: 2rem 0; color: var(--text-secondary);">
                Un email de confirmation a été envoyé à <strong><?php echo htmlspecialchars($_SESSION['user_email'] ?? ''); ?></strong> 
                avec les détails de votre commande et les instructions de connexion.
            </p>

            <div class="btn-group">
                <a href="dashboard.php" class="btn">
                    <i class="fas fa-tachometer-alt"></i> Aller au tableau de bord
                </a>
                <a href="offres.php" class="btn btn-outline">
                    <i class="fas fa-arrow-left"></i> Retour aux offres
                </a>
            </div>

            <div style="margin-top: 3rem; padding-top: 1.5rem; border-top: 1px solid rgba(191, 0, 255, 0.1);">
                <h3 style="font-size: 1.2rem; margin-bottom: 1rem;">Besoin d'aide ?</h3>
                <p style="color: var(--text-secondary); margin-bottom: 1rem;">
                    Notre équipe est là pour vous aider à configurer votre serveur ou répondre à vos questions.
                </p>
                <a href="support.php" class="btn btn-outline" style="margin-top: 0.5rem;">
                    <i class="fas fa-headset"></i> Contacter le support
                </a>
            </div>
        </div>
    </div>

    <footer>
        <div class="container">
            <div class="social-links">
                <a href="#" title="GitHub"><i class="fab fa-github"></i></a>
                <a href="#" title="Twitter"><i class="fab fa-twitter"></i></a>
                <a href="#" title="Discord"><i class="fab fa-discord"></i></a>
            </div>
            <p class="copyright">
                &copy; 2025 Zyrahost - Tous droits réservés
            </p>
        </div>
    </footer>
</body>
</html>
