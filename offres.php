<?php
session_start();
require_once __DIR__ . '/config/offers.php';

// Vérifier si l'utilisateur est connecté
$is_logged_in = isset($_SESSION['user_id']);

// Définit les liens et le texte basés sur l'état de connexion
if ($is_logged_in) {
    $auth_link = 'client.php';
    $auth_text = '<i class="fas fa-user-circle"></i> Mon Compte';
    $logout_button = '<a href="logout.php" class="btn btn-accent" style="margin-left: 10px;"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>';
} else {
    $auth_link = 'login.php';
    $auth_text = '<i class="fas fa-sign-in-alt"></i> Connexion / Inscription';
    $logout_button = '';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <title>Nos Offres - ZyraHost</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
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
            line-height: 1.6;
            overflow-x: hidden;
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
            position: relative;
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

        /* Hero Section */
        .hero {
            padding: 80px 0 40px;
            text-align: center;
            position: relative;
        }

        .hero h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
            background: linear-gradient(90deg, var(--neon-cyan), var(--neon-purple));
            -webkit-background-clip: text;
            -moz-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0 0 20px rgba(0, 243, 255, 0.3);
        }

        .hero p {
            font-size: 1.2rem;
            max-width: 800px;
            margin: 0 auto 2rem;
            color: var(--text-secondary);
        }

        /* Pricing Section */
        .pricing {
            padding: 40px 0 80px;
        }

        .pricing-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }

        .pricing-card {
            background: var(--card-bg);
            border-radius: 10px;
            padding: 2.5rem 2rem;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(191, 0, 255, 0.1);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .pricing-card.popular {
            transform: scale(1.05);
            border: 1px solid var(--neon-purple);
            box-shadow: 0 0 20px rgba(191, 0, 255, 0.3);
        }

        .pricing-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
        }

        .pricing-card.popular:hover {
            transform: scale(1.05) translateY(-5px);
        }

        .pricing-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--neon-cyan), var(--neon-purple));
        }

        .popular-badge {
            position: absolute;
            top: 15px;
            right: -30px;
            background: var(--neon-purple);
            color: white;
            padding: 5px 40px;
            transform: rotate(45deg);
            font-size: 0.8rem;
            font-weight: 600;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        .pricing-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .pricing-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, var(--neon-cyan), var(--neon-purple));
            -webkit-background-clip: text;
            -moz-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .pricing-title {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }

        .pricing-subtitle {
            color: var(--text-secondary);
            font-style: italic;
            margin-bottom: 1.5rem;
            min-height: 40px;
        }

        .pricing-features {
            margin: 2rem 0;
        }

        .pricing-features li {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            color: var(--text-secondary);
        }

        .pricing-features li::before {
            content: '✓';
            color: var(--neon-green);
            margin-right: 10px;
            font-weight: bold;
        }

        .pricing-price {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 1.5rem 0;
            color: var(--neon-cyan);
            text-align: center;
        }

        .billing-toggle {
            display: flex;
            justify-content: center;
            margin: 1rem 0 2rem;
            background: rgba(0, 243, 255, 0.1);
            padding: 8px;
            border-radius: 30px;
            max-width: 300px;
            margin-left: auto;
            margin-right: auto;
        }

        .billing-toggle button {
            background: none;
            border: none;
            color: var(--text-secondary);
            padding: 8px 20px;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .billing-toggle button.active {
            background: linear-gradient(45deg, var(--neon-cyan), var(--neon-purple));
            color: white;
            box-shadow: 0 4px 15px rgba(0, 243, 255, 0.3);
        }

        .pricing-cta {
            text-align: center;
            margin-top: 2rem;
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

        .features-section {
            background: rgba(19, 19, 26, 0.5);
            padding: 4rem 0;
            margin-top: 4rem;
            border-top: 1px solid rgba(191, 0, 255, 0.1);
            border-bottom: 1px solid rgba(191, 0, 255, 0.1);
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .feature-item {
            text-align: center;
            padding: 1.5rem;
            background: rgba(255, 255, 255, 0.02);
            border-radius: 8px;
            transition: transform 0.3s ease, background 0.3s ease;
        }

        .feature-item:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.05);
        }

        .feature-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: var(--neon-cyan);
        }

        /* Footer */
        footer {
            background: var(--darker-bg);
            color: var(--text-secondary);
            text-align: center;
            padding: 3rem 0 2rem;
            margin-top: 5rem;
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
            margin-top: 2rem;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                gap: 15px;
            }

            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
            }

            .hero h1 {
                font-size: 2.2rem;
            }

            .pricing-grid {
                grid-template-columns: 1fr;
                max-width: 500px;
                margin-left: auto;
                margin-right: auto;
            }

            .pricing-card.popular {
                transform: scale(1);
            }

            .pricing-card.popular:hover {
                transform: translateY(-5px);
            }
        }

        @media (max-width: 480px) {
            .hero h1 {
                font-size: 1.8rem;
            }

            .pricing-card {
                padding: 2rem 1.5rem;
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
                    <?php if ($is_logged_in): ?>
                        <li><a href="dashboard.php" class="btn btn-outline"><i class="fas fa-tachometer-alt"></i> Tableau de bord</a></li>
                        <li><a href="logout.php" class="btn"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
                    <?php else: ?>
                        <li><a href="login.php" class="btn btn-outline"><i class="fas fa-sign-in-alt"></i> Connexion</a></li>
                        <li><a href="register.php" class="btn"><i class="fas fa-user-plus"></i> Inscription</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <section class="hero">
        <div class="container">
            <h1>Nos Offres VPS</h1>
            <p>Propulsez vos mondes avec des ressources dédiées. Prix plancher, performance maximale.</p>
            
            <div class="billing-toggle">
                <button class="active" data-billing="monthly">Facturation Mensuelle</button>
                <button data-billing="yearly">Facturation Annuelle (2 mois offerts)</button>
            </div>
        </div>
    </section>

    <section class="pricing">
        <div class="container">
            <div class="pricing-grid">
                <?php foreach ($offers as $id => $offer): ?>
                    <div class="pricing-card <?php echo $offer['popular'] ? 'popular' : ''; ?>">
                        <?php if ($offer['popular']): ?>
                            <div class="popular-badge">Populaire</div>
                        <?php endif; ?>
                        
                        <div class="pricing-header">
                            <div class="pricing-icon"><?php echo htmlspecialchars($offer['icon']); ?></div>
                            <h3 class="pricing-title"><?php echo htmlspecialchars($offer['name']); ?></h3>
                            <p class="pricing-subtitle"><?php echo htmlspecialchars($offer['subtitle']); ?></p>
                            <p class="pricing-subtitle" style="font-size: 0.9rem;"><?php echo htmlspecialchars($offer['description']); ?></p>
                        </div>
                        
                        <ul class="pricing-features">
                            <?php foreach ($offer['features'] as $feature): ?>
                                <li><?php echo htmlspecialchars($feature); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        
                        <div class="pricing-price monthly-price">
                            <?php echo number_format($offer['price'], 2, ',', ' '); ?>€<span>/mois</span>
                        </div>
                        <div class="pricing-price yearly-price" style="display: none;">
                            <?php echo number_format($offer['price'] * 10, 2, ',', ' '); ?>€<span>/an</span>
                            <div style="font-size: 0.8rem; color: var(--neon-green);">Économisez 2 mois</div>
                        </div>
                        
                        <div class="pricing-cta">
                            <?php if ($is_logged_in): ?>
                                <a href="order.php?offer=<?php echo urlencode($id); ?>" class="btn">
                                    <i class="fas fa-shopping-cart"></i> Commander maintenant
                                </a>
                            <?php else: ?>
                                <a href="login.php?redirect=order.php?offer=<?php echo urlencode($id); ?>" class="btn">
                                    <i class="fas fa-sign-in-alt"></i> Se connecter pour commander
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="features-section">
        <div class="container">
            <h2 style="text-align: center; margin-bottom: 2rem;">Caractéristiques incluses</h2>
            <div class="features-grid">
                <?php foreach ($included_features as $feature): ?>
                    <div class="feature-item">
                        <div class="feature-icon">
                            <?php 
                                $icons = ['🔒', '⚙️', '🚀', '💾', '⚡'];
                                echo $icons[array_rand($icons)];
                            ?>
                        </div>
                        <h3><?php echo htmlspecialchars($feature); ?></h3>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <footer id="contact">
        <div class="container">
            <h3>Restons en contact</h3>
            <p>
                <i class="fas fa-envelope" style="margin-right: 10px;"></i>
                contact@zyrahost.fr
            </p>
            
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
        // Bascule entre mensuel/annuel
        document.addEventListener('DOMContentLoaded', function() {
            const monthlyBtn = document.querySelector('[data-billing="monthly"]');
            const yearlyBtn = document.querySelector('[data-billing="yearly"]');
            const monthlyPrices = document.querySelectorAll('.monthly-price');
            const yearlyPrices = document.querySelectorAll('.yearly-price');

            function toggleBilling(showYearly) {
                if (showYearly) {
                    monthlyBtn.classList.remove('active');
                    yearlyBtn.classList.add('active');
                    monthlyPrices.forEach(el => el.style.display = 'none');
                    yearlyPrices.forEach(el => el.style.display = 'block');
                } else {
                    monthlyBtn.classList.add('active');
                    yearlyBtn.classList.remove('active');
                    monthlyPrices.forEach(el => el.style.display = 'block');
                    yearlyPrices.forEach(el => el.style.display = 'none');
                }
            }

            monthlyBtn.addEventListener('click', () => toggleBilling(false));
            yearlyBtn.addEventListener('click', () => toggleBilling(true));

            // Animation des cartes au chargement
            const animateOnScroll = () => {
                const elements = document.querySelectorAll('.pricing-card, .feature-item');
                elements.forEach(element => {
                    const elementTop = element.getBoundingClientRect().top;
                    const windowHeight = window.innerHeight;
                    
                    if (elementTop < windowHeight - 100) {
                        element.style.opacity = '1';
                        element.style.transform = element.classList.contains('popular') ? 'scale(1.05) translateY(0)' : 'translateY(0)';
                    }
                });
            };

            // Initialiser l'état des éléments
            const initElements = () => {
                const elements = document.querySelectorAll('.pricing-card, .feature-item');
                elements.forEach((element, index) => {
                    element.style.opacity = '0';
                    element.style.transform = element.classList.contains('popular') ? 'scale(1.05) translateY(20px)' : 'translateY(20px)';
                    element.style.transition = 'opacity 0.6s ease-out, transform 0.6s ease-out';
                });
                
                // Démarrer l'animation après un court délai
                setTimeout(animateOnScroll, 100);
            };

            // Initialiser les éléments et ajouter l'écouteur de défilement
            initElements();
            window.addEventListener('scroll', animateOnScroll);

            // Animation de défilement fluide pour les ancres
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    const targetId = this.getAttribute('href');
                    if (targetId === '#' || targetId.startsWith('#')) {
                        e.preventDefault();
                        const target = document.querySelector(targetId);
                        if (target) {
                            const headerOffset = 80;
                            const elementPosition = target.getBoundingClientRect().top;
                            const offsetPosition = elementPosition + window.pageYOffset - headerOffset;

                            window.scrollTo({
                                top: offsetPosition,
                                behavior: 'smooth'
                            });
                        }
                    }
                });
            });
        });
    </script>
</body>
</html>
