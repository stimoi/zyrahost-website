<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/offers.php';
require_once __DIR__ . '/includes/order_functions.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    $_SESSION['login_redirect'] = $_SERVER['REQUEST_URI'];
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$error = '';
$success = '';

// Vérifier si une offre est spécifiée
if (!isset($_GET['offer']) || !isset($offers[$_GET['offer']])) {
    header('Location: offres.php');
    exit;
}

$offerId = $_GET['offer'];
$offer = $offers[$offerId];
$billingCycle = isset($_GET['cycle']) && $_GET['cycle'] === 'yearly' ? 'yearly' : 'monthly';

// Traitement du formulaire de commande
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérifier le jeton CSRF (à implémenter)
    
    // Créer la commande
    $orderResult = createOrder($userId, $offerId, $billingCycle);
    
    if ($orderResult['success']) {
        // Traiter le paiement (simulé pour l'instant)
        $paymentResult = processPayment($orderResult['order_id']);
        
        if ($paymentResult['success']) {
            // Provisionner le VPS
            $provisionResult = provisionVPS($orderResult['order_id']);
            
            if ($provisionResult['success']) {
                // Rediriger vers la page de confirmation
                header('Location: order_success.php?order_id=' . $orderResult['order_id']);
                exit;
            } else {
                $error = 'Erreur lors du provisionnement du VPS : ' . $provisionResult['message'];
            }
        } else {
            $error = 'Erreur de paiement : ' . $paymentResult['message'];
        }
    } else {
        $error = 'Erreur lors de la création de la commande : ' . $orderResult['message'];
    }
}

// Récupérer les informations de l'utilisateur
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Calculer le prix en fonction de la période de facturation
$price = $billingCycle === 'yearly' ? $offer['price'] * 10 : $offer['price'];
$billingText = $billingCycle === 'yearly' ? 'an' : 'mois';
$savings = $billingCycle === 'yearly' ? number_format($offer['price'] * 2, 2, ',', ' ') . '€' : '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commander - <?php echo htmlspecialchars($offer['name']); ?> - ZyraHost</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        :root {
            --neon-yan: #00f3ff;
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
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
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

        /* Main Content */
        .main-content {
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
            padding: 40px 0;
        }

        .order-summary, .order-form {
            background: var(--card-bg);
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(191, 0, 255, 0.1);
        }

        .order-summary {
            flex: 1;
            min-width: 300px;
        }

        .order-form {
            flex: 2;
            min-width: 300px;
        }

        h1 {
            font-size: 2rem;
            margin-bottom: 1.5rem;
            color: var(--neon-cyan);
        }

        h2 {
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            color: var(--text-primary);
            border-bottom: 1px solid rgba(191, 0, 255, 0.2);
            padding-bottom: 10px;
        }

        .offer-details {
            margin-bottom: 2rem;
        }

        .offer-name {
            font-size: 1.5rem;
            color: var(--neon-purple);
            margin-bottom: 0.5rem;
        }

        .offer-description {
            color: var(--text-secondary);
            margin-bottom: 1.5rem;
        }

        .price {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--neon-cyan);
            margin: 1.5rem 0;
        }

        .price small {
            font-size: 1rem;
            color: var(--text-secondary);
        }

        .savings {
            color: var(--neon-green);
            font-weight: 500;
            margin-bottom: 1.5rem;
        }

        .feature-list {
            list-style: none;
            margin: 1.5rem 0;
        }

        .feature-list li {
            display: flex;
            align-items: center;
            margin-bottom: 0.8rem;
            color: var(--text-secondary);
        }

        .feature-list li::before {
            content: '✓';
            color: var(--neon-green);
            margin-right: 10px;
            font-weight: bold;
        }

        /* Formulaire */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(191, 0, 255, 0.2);
            border-radius: 5px;
            color: var(--text-primary);
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--neon-cyan);
            box-shadow: 0 0 0 2px rgba(0, 243, 255, 0.2);
        }

        .form-row {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .form-row .form-group {
            flex: 1;
            min-width: 200px;
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
            width: 100%;
            text-align: center;
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

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .back-link {
            display: inline-block;
            margin-top: 1.5rem;
            color: var(--neon-cyan);
            text-decoration: none;
            transition: color 0.3s;
        }

        .back-link:hover {
            color: var(--neon-purple);
        }

        .alert {
            padding: 15px;
            margin-bottom: 1.5rem;
            border-radius: 5px;
            font-weight: 500;
        }

        .alert-danger {
            background-color: rgba(255, 0, 0, 0.1);
            border: 1px solid rgba(255, 0, 0, 0.2);
            color: #ff6b6b;
        }

        .alert-success {
            background-color: rgba(0, 255, 0, 0.1);
            border: 1px solid rgba(0, 255, 0, 0.2);
            color: #6bff6b;
        }

        /* Footer */
        footer {
            background: var(--darker-bg);
            color: var(--text-secondary);
            text-align: center;
            padding: 2rem 0;
            margin-top: 3rem;
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
            .main-content {
                flex-direction: column;
            }

            .order-summary, .order-form {
                width: 100%;
            }

            .form-row .form-group {
                min-width: 100%;
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
                    <li><a href="offres.php" class="active">Nos Offres</a></li>
                    <li><a href="#services">Services</a></li>
                    <li><a href="#contact">Contact</a></li>
                    <li><a href="dashboard.php" class="btn btn-outline"><i class="fas fa-tachometer-alt"></i> Tableau de bord</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container">
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="main-content">
            <div class="order-summary">
                <h2>Récapitulatif de la commande</h2>
                <div class="offer-details">
                    <div class="offer-name"><?php echo htmlspecialchars($offer['name']); ?></div>
                    <div class="offer-description"><?php echo htmlspecialchars($offer['subtitle']); ?></div>
                    <div class="price">
                        <?php echo number_format($price, 2, ',', ' '); ?>€
                        <small>/<?php echo $billingText; ?></small>
                    </div>
                    <?php if ($savings): ?>
                        <div class="savings">
                            <i class="fas fa-piggy-bank"></i> Économisez <?php echo $savings; ?> avec un engagement annuel
                        </div>
                    <?php endif; ?>
                    
                    <ul class="feature-list">
                        <?php foreach ($offer['features'] as $feature): ?>
                            <li><?php echo htmlspecialchars($feature); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="included-features">
                    <h3>Caractéristiques incluses :</h3>
                    <ul class="feature-list">
                        <?php foreach ($included_features as $feature): ?>
                            <li><?php echo htmlspecialchars($feature); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <a href="offres.php" class="back-link">
                    <i class="fas fa-arrow-left"></i> Retour aux offres
                </a>
            </div>

            <div class="order-form">
                <h2>Informations de facturation</h2>
                <p style="margin-bottom: 1.5rem; color: var(--text-secondary);">
                    Veuillez vérifier vos informations de facturation avant de procéder au paiement.
                </p>

                <form method="POST" id="orderForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name">Prénom</label>
                            <input type="text" id="first_name" name="first_name" class="form-control" 
                                   value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="last_name">Nom</label>
                            <input type="text" id="last_name" name="last_name" class="form-control" 
                                   value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="email">Adresse e-mail</label>
                        <input type="email" id="email" name="email" class="form-control" 
                               value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="address">Adresse</label>
                        <input type="text" id="address" name="address" class="form-control" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="postal_code">Code postal</label>
                            <input type="text" id="postal_code" name="postal_code" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="city">Ville</label>
                            <input type="text" id="city" name="city" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="country">Pays</label>
                            <select id="country" name="country" class="form-control" required>
                                <option value="FR">France</option>
                                <option value="BE">Belgique</option>
                                <option value="CH">Suisse</option>
                                <option value="LU">Luxembourg</option>
                                <option value="CA">Canada</option>
                                <option value="">Autre pays...</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group" style="margin-top: 2rem;">
                        <h3>Méthode de paiement</h3>
                        <div style="background: rgba(0, 243, 255, 0.1); padding: 15px; border-radius: 5px; margin-top: 1rem;">
                            <div class="form-group" style="margin-bottom: 0;">
                                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                                    <input type="radio" id="paypal" name="payment_method" value="paypal" checked>
                                    <label for="paypal" style="margin: 0; font-weight: 500;">
                                        <i class="fab fa-cc-paypal"></i> Payer avec PayPal
                                    </label>
                                </div>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <input type="radio" id="card" name="payment_method" value="card">
                                    <label for="card" style="margin: 0; font-weight: 500;">
                                        <i class="far fa-credit-card"></i> Carte de crédit
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group" style="margin-top: 2rem;">
                        <div class="form-check">
                            <input type="checkbox" id="terms" name="terms" class="form-check-input" required>
                            <label for="terms" class="form-check-label">
                                J'accepte les <a href="terms.php" target="_blank" style="color: var(--neon-cyan);">conditions générales de vente</a> et la 
                                <a href="privacy.php" target="_blank" style="color: var(--neon-cyan);">politique de confidentialité</a>.
                            </label>
                        </div>
                    </div>

                    <div class="form-group" style="margin-top: 2rem;">
                        <button type="submit" class="btn" id="submitBtn">
                            <i class="fas fa-shopping-cart"></i> Payer maintenant <?php echo number_format($price, 2, ',', ' '); ?>€
                        </button>
                        <p style="margin-top: 1rem; font-size: 0.9rem; color: var(--text-secondary);">
                            <i class="fas fa-lock"></i> Paiement sécurisé via Stripe. Vos données sont cryptées.
                        </p>
                    </div>
                </form>
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

    <script>
        // Désactiver le double-clic sur le formulaire
        document.getElementById('orderForm').addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('submitBtn');
            if (submitBtn.disabled) {
                e.preventDefault();
                return false;
            }
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Traitement en cours...';
            return true;
        });

        // Animation de chargement pour les boutons
        document.querySelectorAll('button[type="submit"]').forEach(button => {
            button.addEventListener('click', function() {
                if (this.form.checkValidity()) {
                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Traitement...';
                }
            });
        });
    </script>
</body>
</html>
