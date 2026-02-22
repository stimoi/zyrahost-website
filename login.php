<?php
// Tente de forcer le chemin du cookie de session à la racine du site
ini_set('session.cookie_path', '/');
session_start();

// Si l'utilisateur est déjà connecté, REDIRECTION IMMÉDIATE VERS LE DASHBOARD OU NEXT
if (isset($_SESSION['user_id'])) {
    $redirect_url = isset($_GET['next']) ? $_GET['next'] : 'client.php';
    // Validation de l'URL pour éviter les redirections malveillantes
    if (filter_var($redirect_url, FILTER_VALIDATE_URL) && parse_url($redirect_url, PHP_URL_HOST) === 'zyrahost.fr') {
        header('Location: ' . $redirect_url);
    } else {
        header('Location: client.php');
    }
    exit;
}

// ============== CONFIGURATION BASE DE DONNÉES ==============\\
// VEUILLEZ VÉRIFIER CES IDENTIFIANTS DANS VOTRE PANNEAU DE CONTRÔLE.
// L'hôte est souvent 'localhost', mais certains hébergeurs utilisent une adresse IP ou un nom spécifique.
// Si vous rencontrez l'erreur "Access denied" (1044), c'est que l'utilisateur ci-dessous
// n'a pas tous les privilèges sur la base de données correspondante.
$db_host = 'localhost:3306'; 
$db_name = 'mttljx_zyrahostf_db'; // LE NOM DE VOTRE BASE DE DONNÉES
$db_user = 'sti_moi'; // VOTRE NOM D'UTILISATEUR BDD
$db_pass = 'pj32~lH36'; // VOTRE MOT DE PASSE BDD

// ============== CONFIGURATION OAUTH ==============\\

$error = '';
$success = '';

// ============== TENTATIVE DE CONNEXION PDO ==============\\
$pdo = null;
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $error = ''; 

} catch (PDOException $e) {
    // AFFICHE LE MESSAGE D'ERREUR SPÉCIFIQUE
    $error = "Erreur de connexion à la base de données. Code: " . $e->getMessage() . ". Veuillez vérifier les identifiants et l'hôte de votre utilisateur BDD.";
    $pdo = null; 
}

// ============== GESTION CONNEXION GOOGLE ==============\\
if (isset($_GET['code']) && isset($_GET['state']) && $_GET['state'] === 'google') {
    $code = $_GET['code'];
    
    // Échanger le code contre un token
    $token_url = 'https://oauth2.googleapis.com/token';
    $token_data = [
        'code' => $code,
        'client_id' => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri' => GOOGLE_REDIRECT_URI,
        'grant_type' => 'authorization_code'
    ];
    
    $ch = curl_init($token_url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($token_data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    
    $token_result = json_decode($response, true);
    
    if (isset($token_result['access_token'])) {
        $access_token = $token_result['access_token'];
        
        // Récupérer les informations de l'utilisateur
        $user_info_url = 'https://www.googleapis.com/oauth2/v1/userinfo?access_token=' . $access_token;
        $user_info = json_decode(file_get_contents($user_info_url), true);
        
        if ($user_info && $pdo !== null) {
            $google_id = $user_info['id'];
            $email = $user_info['email'];
            $username = str_replace([' ', '@', '.'], '_', $user_info['name'] . '_' . substr($google_id, 0, 4));

            try {
                $stmt = $pdo->prepare("SELECT id, username, email FROM users WHERE google_id = ? OR email = ?");
                $stmt->execute([$google_id, $email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $user['email'];
                } else {
                    $stmt = $pdo->prepare("INSERT INTO users (username, email, google_id) VALUES (?, ?, ?)");
                    $stmt->execute([$username, $email, $google_id]);
                    
                    $user_id = $pdo->lastInsertId();
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['username'] = $username;
                    $_SESSION['email'] = $email;
                }
                // Rediriger vers le paramètre next ou client.php par défaut
                $redirect_url = isset($_GET['next']) ? $_GET['next'] : 'client.php';
                if (filter_var($redirect_url, FILTER_VALIDATE_URL) && parse_url($redirect_url, PHP_URL_HOST) === 'zyrahost.fr') {
                    header('Location: ' . $redirect_url);
                } else {
                    header('Location: client.php');
                }
                exit;

            } catch (PDOException $e) {
                $error = "Erreur BDD Google: " . $e->getMessage();
            }
        } else {
             $error = "Erreur de récupération des infos Google ou BDD indisponible.";
        }
    } else {
        $error = "Échec de l'échange de jeton Google.";
    }
}

// ============== GESTION CONNEXION DISCORD ==============\\
if (isset($_GET['code']) && isset($_GET['state']) && $_GET['state'] === 'discord') {
    $code = $_GET['code'];
    
    // Échanger le code contre un token
    $token_url = 'https://discord.com/api/oauth2/token';
    $token_data = [
        'client_id' => DISCORD_CLIENT_ID,
        'client_secret' => DISCORD_CLIENT_SECRET,
        'grant_type' => 'authorization_code',
        'code' => $code,
        'redirect_uri' => DISCORD_REDIRECT_URI,
        'scope' => 'identify email'
    ];
    
    $ch = curl_init($token_url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($token_data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    
    $token_result = json_decode($response, true);

    if (isset($token_result['access_token'])) {
        $access_token = $token_result['access_token'];
        
        // Récupérer les informations de l'utilisateur
        $user_info_url = 'https://discord.com/api/users/@me';
        $ch = curl_init($user_info_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $access_token]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $user_info = json_decode(curl_exec($ch), true);
        curl_close($ch);
        
        if ($user_info && $pdo !== null) {
            $discord_id = $user_info['id'];
            $email = $user_info['email'];
            $username = $user_info['username'] . '_' . substr($discord_id, 0, 4);

            try {
                $stmt = $pdo->prepare("SELECT id, username, email FROM users WHERE discord_id = ? OR email = ?");
                $stmt->execute([$discord_id, $email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $user['email'];
                } else {
                    $stmt = $pdo->prepare("INSERT INTO users (username, email, discord_id) VALUES (?, ?, ?)");
                    $stmt->execute([$username, $email, $discord_id]);
                    
                    $user_id = $pdo->lastInsertId();
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['username'] = $username;
                    $_SESSION['email'] = $email;
                }
                // Rediriger vers le paramètre next ou client.php par défaut
                $redirect_url = isset($_GET['next']) ? $_GET['next'] : 'client.php';
                if (filter_var($redirect_url, FILTER_VALIDATE_URL) && parse_url($redirect_url, PHP_URL_HOST) === 'zyrahost.fr') {
                    header('Location: ' . $redirect_url);
                } else {
                    header('Location: client.php');
                }
                exit;

            } catch (PDOException $e) {
                $error = "Erreur BDD Discord: " . $e->getMessage();
            }
        } else {
             $error = "Erreur de récupération des infos Discord ou BDD indisponible.";
        }
    } else {
        $error = "Échec de l'échange de jeton Discord.";
    }
}


// ============== GESTION ENREGISTREMENT (REGISTER) ==============\\
if (isset($_POST['register']) && $pdo !== null) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $error = "Les mots de passe ne correspondent pas.";
    } elseif (strlen($password) < 8) {
        $error = "Le mot de passe doit contenir au moins 8 caractères.";
    } else {
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);

            if ($stmt->fetch()) {
                $error = "Ce nom d'utilisateur ou cet e-mail est déjà utilisé.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
                $stmt->execute([$username, $email, $hashed_password]);

                $success = "Inscription réussie ! Vous pouvez maintenant vous connecter.";
            }
        } catch (PDOException $e) {
            $error = "Erreur lors de l'enregistrement: " . $e->getMessage();
        }
    }
}

// ============== GESTION CONNEXION (LOGIN) ==============\\
if (isset($_POST['login']) && $pdo !== null) {
    $identifier = trim($_POST['identifier']);
    $password = $_POST['password'];

    try {
        $stmt = $pdo->prepare("SELECT id, username, email, password FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$identifier, $identifier]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];

            $success = "Connexion réussie ! Redirection...";
            // Rediriger vers le paramètre next ou client.php par défaut
            $redirect_url = isset($_GET['next']) ? $_GET['next'] : 'client.php';
            if (filter_var($redirect_url, FILTER_VALIDATE_URL) && parse_url($redirect_url, PHP_URL_HOST) === 'zyrahost.fr') {
                header('Location: ' . $redirect_url);
            } else {
                header('Location: client.php');
            }
            exit;
        } else {
            $error = "Identifiants ou mot de passe incorrects.";
        }
    } catch (PDOException $e) {
        $error = "Erreur de connexion: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion / Inscription - ZyraHost</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #5865F2; /* Bleu Discord */
            --secondary: #00FFC3; /* Accent Cyan/Vert */
            --accent: #ED4245;   /* Rouge Alerte */
            --dark: #1e1f29;     /* Gris foncé */
            --darker: #15161d;   /* Très foncé */
            --light: #f8f9fa;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, var(--darker) 0%, var(--dark) 100%);
            color: var(--light);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
        }

        .auth-container {
            background-color: var(--dark);
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            width: 90%;
            max-width: 450px;
            padding: 30px;
            border: 1px solid #333;
        }

        h1 {
            text-align: center;
            color: var(--secondary);
            margin-bottom: 20px;
            font-size: 1.8rem;
        }

        .tab-menu {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
        }

        .tab {
            flex-grow: 1;
            text-align: center;
            padding: 15px 0;
            cursor: pointer;
            font-weight: bold;
            color: #aaa;
            transition: color 0.3s, background-color 0.3s;
            border-radius: 8px 8px 0 0;
        }

        .tab.active {
            color: var(--light);
            border-bottom: 2px solid var(--primary);
            margin-bottom: -2px; 
        }

        .form-content {
            display: none;
            padding-top: 20px;
        }

        .form-content.active {
            display: block;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #ccc;
        }

        .input-wrapper {
            display: flex;
            align-items: center;
            background-color: #2a2a35;
            border-radius: 8px;
            padding: 0 15px;
            border: 1px solid #333;
            transition: border-color 0.3s;
        }

        .input-wrapper:focus-within {
            border-color: var(--primary);
        }

        .input-wrapper i {
            color: #777;
            margin-right: 10px;
            font-size: 1rem;
        }

        .form-control {
            width: 100%;
            padding: 12px 0;
            border: none;
            background: none;
            color: var(--light);
            outline: none;
        }

        .btn {
            width: 100%;
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            transition: background-color 0.3s, transform 0.2s, box-shadow 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-primary {
            background-color: var(--primary);
            color: var(--light);
        }

        .btn-primary:hover {
            background-color: #4a54e6;
            transform: translateY(-1px);
            box-shadow: 0 4px 10px rgba(88, 101, 242, 0.4);
        }

        .social-buttons {
            display: flex;
            gap: 15px;
            margin-top: 15px;
        }

        .btn-social {
            flex-grow: 1;
            padding: 12px;
            border-radius: 8px;
            font-size: 1rem;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-google {
            background-color: #DB4437; 
            color: var(--light);
        }
        .btn-discord {
            background-color: #5865F2; 
            color: var(--light);
        }
        
        .btn-social:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        .back-home {
            margin-top: 20px;
            text-align: center;
        }

        .back-home a {
            color: var(--secondary);
            text-decoration: none;
            transition: color 0.3s;
        }
        .back-home a:hover {
            color: var(--primary);
        }

        .alert {
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 15px;
            text-align: center;
            font-weight: 500;
        }

        .alert-error {
            background-color: rgba(237, 66, 69, 0.2);
            color: var(--accent);
            border: 1px solid var(--accent);
        }

        .alert-success {
            background-color: rgba(0, 255, 195, 0.2);
            color: var(--secondary);
            border: 1px solid var(--secondary);
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <h1>ZyraHost - Authentification</h1>

        <?php if ($error): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
        <?php endif; ?>

        <div class="tab-menu">
            <div class="tab active" data-tab="login-form">Connexion</div>
            <div class="tab" data-tab="register-form">Inscription</div>
        </div>

        <div class="form-wrapper">
            <!-- Formulaire de Connexion -->
            <div class="form-content active" id="login-form">
                <form method="POST">
                    <div class="form-group">
                        <label for="login-identifier">Nom d'utilisateur ou Email</label>
                        <div class="input-wrapper">
                            <i class="fas fa-user"></i>
                            <input type="text" id="login-identifier" name="identifier" class="form-control" placeholder="Entrez votre identifiant" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="login-password">Mot de passe</label>
                        <div class="input-wrapper">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="login-password" name="password" class="form-control" placeholder="Entrez votre mot de passe" required>
                        </div>
                    </div>

                    <button type="submit" name="login" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt"></i> Se connecter
                    </button>
                    
                    <div class="social-buttons">
                        <!-- Connexion Google -->
                        <a href="https://accounts.google.com/o/oauth2/v2/auth?scope=email%20profile&response_type=code&client_id=<?php echo GOOGLE_CLIENT_ID; ?>&redirect_uri=<?php echo GOOGLE_REDIRECT_URI; ?>&state=google" class="btn-social btn-google">
                            <i class="fab fa-google"></i> Google
                        </a>
                        <!-- Connexion Discord -->
                        <a href="https://discord.com/oauth2/authorize?client_id=<?php echo DISCORD_CLIENT_ID; ?>&response_type=code&redirect_uri=<?php echo DISCORD_REDIRECT_URI; ?>&scope=identify+email&state=discord" class="btn-social btn-discord">
                            <i class="fab fa-discord"></i> Discord
                        </a>
                    </div>
                </form>
            </div>

            <!-- Formulaire d'Inscription -->
            <div class="form-content" id="register-form">
                <form method="POST">
                    <div class="form-group">
                        <label for="register-username">Nom d'utilisateur</label>
                        <div class="input-wrapper">
                            <i class="fas fa-user-plus"></i>
                            <input type="text" id="register-username" name="username" class="form-control" placeholder="Choisissez un nom d'utilisateur" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="register-email">Adresse Email</label>
                        <div class="input-wrapper">
                            <i class="fas fa-envelope"></i>
                            <input type="email" id="register-email" name="email" class="form-control" placeholder="Entrez votre email" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="register-password">Mot de passe</label>
                        <div class="input-wrapper">
                            <i class="fas fa-key"></i>
                            <input type="password" id="register-password" name="password" class="form-control" placeholder="Mot de passe (8 caractères min.)" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="register-confirm">Confirmer le mot de passe</label>
                        <div class="input-wrapper">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="register-confirm" name="confirm_password" class="form-control" placeholder="Répétez le mot de passe" required>
                        </div>
                    </div>

                    <button type="submit" name="register" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> Créer mon compte
                    </button>
                </form>
            </div>
        </div>

        <div class="back-home">
            <a href="index.php">
                <i class="fas fa-arrow-left"></i> Retour à l'accueil
            </a>
        </div>
    </div>

    <script>
        const tabs = document.querySelectorAll('.tab');
        const forms = document.querySelectorAll('.form-content');

        // Afficher le formulaire de connexion par défaut
        document.getElementById('login-form').classList.add('active');

        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                const target = tab.getAttribute('data-tab');

                tabs.forEach(t => t.classList.remove('active'));
                forms.forEach(f => f.classList.remove('active'));

                tab.classList.add('active');
                document.getElementById(target).classList.add('active');
            });
        });
    </script>
</body>
</html>
