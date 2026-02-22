<?php
// Démarre la session PHP
session_start();

// Vérifie si l'utilisateur est connecté
$is_logged_in = isset($_SESSION['user_id']);

// Définit les liens et le texte basés sur l'état de connexion
if ($is_logged_in) {
    $auth_link = 'client.php';
    $auth_text = '<i class="fas fa-user-circle"></i> Mon Compte';
    $logout_button = '<a href="logout.php" class="btn btn-accent" style="margin-left: 10px;"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>';
} else {
    $auth_link = 'login.php';
    $auth_text = '<i class="fas fa-sign-in-alt"></i> Connexion / Inscription';
    $logout_button = ''; // Pas de bouton déconnexion si non connecté
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
	<script>(function(s){s.dataset.zone='10263677',s.src='https://groleegni.net/vignette.min.js'})([document.documentElement, document.body].filter(Boolean).pop().appendChild(document.createElement('script')))</script>
	<meta name="monetag" content="e9d9608e41c58cf6ae4f95a2b78518f1">
	<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-6889654580446209"
     crossorigin="anonymous"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ZyraHost - Hébergement Gaming & Bots Discord</title>
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
            padding: 15px 0;
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

        .auth-buttons {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(90deg, var(--neon-purple), var(--neon-cyan));
            color: var(--darker-bg);
            box-shadow: 0 4px 15px rgba(0, 243, 255, 0.2);
        }

        .btn-primary:hover {
            opacity: 0.9;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 243, 255, 0.4);
        }
        
        .btn-accent {
            background-color: var(--accent-red);
            color: var(--text-primary);
            box-shadow: 0 4px 15px rgba(237, 66, 69, 0.2);
        }

        .btn-accent:hover {
             opacity: 0.9;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(237, 66, 69, 0.4);
        }

        /* Mobile Menu */
        .menu-toggle {
            display: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--neon-cyan);
        }

        .mobile-menu {
            display: none;
            flex-direction: column;
            background-color: var(--dark-bg);
            position: absolute;
            top: 70px;
            left: 0;
            width: 100%;
            padding: 20px 0;
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.5);
        }

        .mobile-menu a {
            padding: 12px 20px;
            text-align: center;
            color: var(--text-primary);
            text-decoration: none;
            transition: background-color 0.3s;
        }

        .mobile-menu a:hover {
            background-color: #1a1a24;
        }

        .mobile-auth {
            padding: 15px 20px;
            border-top: 1px solid #2e2e3a;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        /* SECTIONS GENERALES */
        section {
            padding: 100px 0;
            text-align: center;
        }

        h2 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            background: linear-gradient(90deg, var(--neon-cyan), var(--neon-purple));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0 0 10px rgba(0, 243, 255, 0.2);
        }

        h3 {
            font-size: 1.8rem;
            color: var(--neon-cyan);
            margin-bottom: 15px;
        }
        
        /* HERO SECTION */
        #hero {
            padding: 150px 0;
            background: url('https://placehold.co/1200x800/0a0a0f/13131a?text=CYBERSPACE_BG') no-repeat center center/cover;
            position: relative;
            text-align: center;
        }

        #hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            z-index: 1;
        }

        #hero .container {
            position: relative;
            z-index: 2;
        }

        #hero h1 {
            font-size: 4rem;
            margin-bottom: 20px;
            background: linear-gradient(90deg, var(--neon-cyan), var(--neon-pink));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0 0 15px rgba(0, 243, 255, 0.8);
            font-weight: 800;
        }

        #hero p {
            font-size: 1.5rem;
            color: var(--text-secondary);
            margin-bottom: 40px;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }
        
        /* SERVICES */
        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 50px;
        }

        .service-card {
            background-color: var(--card-bg);
            border-radius: 10px;
            padding: 30px;
            text-align: left;
            box-shadow: 0 0 20px rgba(0, 243, 255, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            border: 1px solid #2e2e3a;
        }

        .service-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 243, 255, 0.3);
        }

        .service-card i {
            font-size: 2.5rem;
            color: var(--neon-purple);
            margin-bottom: 15px;
            text-shadow: 0 0 10px rgba(191, 0, 255, 0.5);
        }

        .service-card p {
            color: var(--text-secondary);
        }
        
        /* FOOTER */
        footer {
            background-color: var(--dark-bg);
            padding: 20px 0;
            border-top: 1px solid #2e2e3a;
        }

        footer p {
            font-size: 0.9rem;
            color: var(--text-secondary);
        }

        footer a {
            color: var(--neon-cyan);
            text-decoration: none;
            transition: color 0.3s;
        }

        footer a:hover {
            color: var(--neon-purple);
        }

        /* RESPONSIVENESS */
        @media (max-width: 900px) {
            .nav-links, .auth-buttons {
                display: none;
            }

            .menu-toggle {
                display: block;
            }

            .navbar {
                padding: 15px 20px;
            }

            #hero h1 {
                font-size: 3rem;
            }

            #hero p {
                font-size: 1.2rem;
            }
            
            .services-grid {
                grid-template-columns: 1fr;
            }
        }
        /* Bannière Cookies */
        .cookie-banner {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            max-width: 90%;
            width: 800px;
            background: rgba(19, 19, 26, 0.95);
            color: var(--text-primary);
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(0, 243, 255, 0.3);
            border: 1px solid var(--neon-cyan);
            z-index: 1000;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: none;
            backdrop-filter: blur(10px);
            animation: neonPulse 1.5s infinite alternate;
        }
        .cookie-content {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .cookie-text {
            line-height: 1.6;
            margin-bottom: 10px;
            text-shadow: 0 0 5px rgba(0, 243, 255, 0.5);
        }
        .cookie-buttons {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        .cookie-btn {
            padding: 10px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
            overflow: hidden;
        }
        .accept-btn {
            background: linear-gradient(45deg, var(--neon-cyan), var(--neon-green));
            color: #0a0a0a;
            font-weight: bold;
        }
        .accept-btn:hover {
            box-shadow: 0 0 15px rgba(0, 243, 255, 0.8);
            transform: translateY(-2px);
        }
        .more-info-btn {
            background: transparent;
            color: var(--neon-cyan);
            border: 1px solid var(--neon-cyan);
        }
        .more-info-btn:hover {
            background: rgba(0, 243, 255, 0.1);
            box-shadow: 0 0 10px rgba(0, 243, 255, 0.5);
            transform: translateY(-2px);
        }
        @media (max-width: 600px) {
            .cookie-banner {
                width: 95%;
                bottom: 10px;
                padding: 15px;
            }
            .cookie-buttons {
                flex-direction: column;
            }
            .cookie-btn {
                width: 100%;
                padding: 12px;
            }
        }
        @keyframes neonPulse {
            from {
                box-shadow: 0 0 5px rgba(0, 243, 255, 0.3);
            }
            to {
                box-shadow: 0 0 20px rgba(0, 243, 255, 0.6);
            }
        }
    </style>
</head>
<body>

    <header>
        <div class="container">
            <nav class="navbar">
                <a href="index.php" class="logo">ZyraHost</a>
                
                <ul class="nav-links">
                    <li><a href="index.php" class="active">Accueil</a></li>
                    <li><a href="offres.php">Nos Offres</a></li>
                    <li><a href="#services">Services</a></li>
                    <li><a href="#a-propos">À Propos</a></li>
                    <li><a href="#contact">Contact</a></li>
                    <?php if ($is_logged_in): ?>
                        <li><a href="dashboard.php" class="btn btn-outline"><i class="fas fa-tachometer-alt"></i> Tableau de bord</a></li>
                        <li><a href="logout.php" class="btn"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
                    <?php else: ?>
                        <li><a href="login.php" class="btn btn-outline"><i class="fas fa-sign-in-alt"></i> Connexion</a></li>
                        <li><a href="register.php" class="btn"><i class="fas fa-user-plus"></i> Inscription</a></li>
                    <?php endif; ?>
                </ul>

                <!-- Toggle Menu Mobile -->
                <div class="menu-toggle" id="menuToggle">
                    <i class="fas fa-bars"></i>
                </div>
            </nav>
        </div>

        <!-- Menu Mobile -->
        <div class="mobile-menu" id="mobileMenu">
            <a href="index.php" class="active">Accueil</a>
            <a href="#hebergement-gaming">Hébergement Gaming</a>
            <a href="#bots-discord">Bots Discord</a>
            <a href="#caracteristiques">Fonctionnalités</a>
            <a href="#contact">Contact</a>
            
            <!-- Boutons d'Authentification (Mobile) -->
            <div class="mobile-auth">
                <a href="<?php echo $auth_link; ?>" class="btn btn-primary" style="width: 100%;">
                    <?php echo $auth_text; ?>
                </a>
                <?php if ($is_logged_in): ?>
                    <a href="logout.php" class="btn btn-accent" style="width: 100%;">
                        <i class="fas fa-sign-out-alt"></i> Déconnexion
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- SECTION HERO -->
    <section id="hero">
        <div class="container">
            <h1>Propulsez vos Projets Gaming.</h1>
            <p>Hébergement ultra-rapide et fiable pour vos serveurs de jeux et vos bots Discord. Zéro lag, 100% performance.</p>
            <a href="login.php" class="btn btn-primary" style="font-size: 1.2rem; padding: 1rem 2.5rem; text-transform: uppercase;">
                <i class="fas fa-rocket"></i> Commencer
            </a>
        </div>
    </section>

    <!-- SECTION HÉBERGEMENT GAMING -->
    <section id="hebergement-gaming">
        <div class="container">
            <h2><i class="fas fa-gamepad"></i> Hébergement Gaming</h2>
            <p style="color: var(--text-secondary);">Des performances brutes pour tous vos jeux préférés. Gagnez la bataille du Ping.</p>
            <div class="services-grid">
                <div class="service-card">
                    <i class="fas fa-server"></i>
                    <h3>Serveurs Minecraft</h3>
                    <p>Java et Bedrock. Installation instantanée, accès FTP complet et gestion facile.</p>
                </div>
                <div class="service-card">
                    <i class="fas fa-headset"></i>
                    <h3>Serveurs Discord</h3>
                    <p>Hébergez vos bots 24/7 avec une latence minimale. Support Python, Node.js, et plus.</p>
                </div>
                <div class="service-card">
                    <i class="fas fa-sitemap"></i>
                    <h3>Autres Jeux</h3>
                    <p>Rust, Ark, Valheim... notre infrastructure est prête pour tout. Performance garantie.</p>
                </div>
            </div>
            <a href="#" class="btn btn-primary" style="margin-top: 50px;">
                <i class="fas fa-arrow-right"></i> Voir nos offres
            </a>
        </div>
    </section>

    <!-- SECTION BOTS DISCORD -->
    <section id="bots-discord" style="background-color: var(--dark-bg);">
        <div class="container">
            <h2><i class="fab fa-discord"></i> Bots Discord</h2>
            <p style="color: var(--text-secondary);">Une plateforme dédiée pour le développement et le déploiement de vos bots.</p>
            <div class="services-grid">
                <div class="service-card">
                    <i class="fas fa-terminal"></i>
                    <h3>Environnements Flexibles</h3>
                    <p>Choisissez votre langage : Node.js, Python, Java. Déploiement en un clic.</p>
                </div>
                <div class="service-card">
                    <i class="fas fa-shield-alt"></i>
                    <h3>Sécurité Maximale</h3>
                    <p>Protection DDoS avancée pour garantir la disponibilité de votre bot, même sous attaque.</p>
                </div>
                <div class="service-card">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <h3>Mise à l'Échelle</h3>
                    <p>Des ressources évolutives pour suivre la croissance de votre communauté.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION CARACTÉRISTIQUES -->
    <section id="caracteristiques">
        <div class="container">
            <h2><i class="fas fa-cogs"></i> Pourquoi nous choisir ?</h2>
            <div class="services-grid">
                <div class="service-card">
                    <i class="fas fa-tachometer-alt"></i>
                    <h3>SSD NVMe</h3>
                    <p>Vitesse de lecture/écriture jusqu'à 10x plus rapide que les SSD classiques.</p>
                </div>
                <div class="service-card">
                    <i class="fas fa-life-ring"></i>
                    <h3>Support 24/7</h3>
                    <p>Notre équipe de support technique est là jour et nuit pour vous aider.</p>
                </div>
                <div class="service-card">
                    <i class="fas fa-dollar-sign"></i>
                    <h3>Meilleur Prix</h3>
                    <p>Qualité haut de gamme sans le coût premium. Tarifs compétitifs garantis.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION CONTACT -->
    <section id="contact" style="background-color: var(--dark-bg);">
        <div class="container">
            <h2 style="font-size: 2.5rem; margin-bottom: 1rem; background: linear-gradient(90deg, var(--neon-cyan), var(--neon-purple)); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Prêt à Dominer ? 🎯</h2>
            <p style="margin-bottom: 2rem; color: var(--text-secondary); font-size: 1.2rem;">Rejoins des milliers de gamers et devs qui font confiance à ZyraHost.</p>
            <!-- Lien de contact temporaire - peut être remplacé par un formulaire -->
            <a href="mailto:contact@zyrahost.fr" class="btn btn-primary" style="font-size: 1.2rem; padding: 1rem 2.5rem;">⚡ Nous Contacter</a>
        </div>
    </section>

    <footer>
        <div class="container">
            <p>&copy; 2025 ZyraHost. Tous droits réservés. | <a href="#">Mentions Légales</a></p>
        </div>
    </footer>

    <script>
        // Gestion du défilement fluide pour les liens d'ancrage (#)
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });

        // Mobile Menu Toggle
        document.getElementById('menuToggle').addEventListener('click', function() {
            const mobileMenu = document.getElementById('mobileMenu');
            // Utilise l'attribut CSS `display` pour gérer l'ouverture/fermeture du menu
            mobileMenu.style.display = mobileMenu.style.display === 'flex' ? 'none' : 'flex';
        });

        // Fermer le menu mobile lors du clic sur un lien (pour un meilleur UX)
        document.querySelectorAll('.mobile-menu a').forEach(link => {
            link.addEventListener('click', function() {
                // S'assure de ne pas fermer le menu si l'on clique sur un bouton d'authentification
                if (this.closest('.mobile-auth') === null) {
                    document.getElementById('mobileMenu').style.display = 'none';
                }
            });
        });
        
        // La simulation de connexion par localStorage a été retirée.
        // La gestion des boutons Connexion/Mon Compte est maintenant faite par PHP.
    </script>
    <!-- Bannière Cookies -->
    <div class="cookie-banner" id="cookieBanner">
        <div class="cookie-content">
            <div class="cookie-text">
                🔒 <strong>Respect de votre vie privée</strong><br>
                Nous utilisons un seul cookie strictement nécessaire pour gérer votre session de connexion. 
                Aucune donnée personnelle n'est collectée, analysée ou partagée.
            </div>
            <div class="cookie-buttons">
                <button class="cookie-btn accept-btn" id="acceptCookies">J'ai compris</button>
                <a href="#" class="cookie-btn more-info-btn">En savoir plus</a>
            </div>
        </div>
    </div>

    <script>
        // Gestion de la bannière de cookies
        document.addEventListener('DOMContentLoaded', function() {
            // Vérifier si le cookie d'acceptation existe
            if (!document.cookie.split('; ').find(row => row.startsWith('cookieConsent='))) {
                // Afficher la bannière si aucun consentement n'a encore été donné
                setTimeout(() => {
                    const banner = document.getElementById('cookieBanner');
                    banner.style.display = 'block';
                    setTimeout(() => {
                        banner.style.opacity = '1';
                    }, 100);
                }, 2000);
                
                // Gérer le clic sur le bouton d'acceptation
                document.getElementById('acceptCookies').addEventListener('click', function() {
                    // Définir un cookie valide 1 an
                    const date = new Date();
                    date.setFullYear(date.getFullYear() + 1);
                    document.cookie = `cookieConsent=true; expires=${date.toUTCString()}; path=/; SameSite=Lax`;
                    
                    // Masquer la bannière avec une animation
                    const banner = document.getElementById('cookieBanner');
                    banner.style.opacity = '1';
                    let opacity = 1;
                    const fadeOut = setInterval(() => {
                        if (opacity <= 0.1) {
                            clearInterval(fadeOut);
                            banner.style.display = 'none';
                        }
                        banner.style.opacity = opacity;
                        banner.style.transform = `translateX(-50%) translateY(${(1 - opacity) * 10}px)`;
                        opacity -= opacity * 0.2;
                    }, 50);
                });
            }
        });
    </script>
</body>
</html>
