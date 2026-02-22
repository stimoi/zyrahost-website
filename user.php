<?php
// users.php - SIMULATION D'UNE BASE DE DONNÉES UTILISATEURS
// L'authentification est maintenant GÉRÉE PAR HACHAGE.

// Structure : [email => ['password_hash' => 'haché', 'hosted_bots' => 0, 'last_login' => timestamp]]

// Un hash simulé pour 'test@zyrahost.com' avec le mot de passe 'testpass'
// Ce hash a été généré via password_hash('testpass', PASSWORD_DEFAULT)
$test_pass_hash = '$2y$10$tM78wA5pY0j5JgR9V1s/6.2D2F0H9Fp5.q8T8b5R3Q9dG4p7c2D7k';

$users = [
    // Exemple d'utilisateur pré-existant (mot de passe initial: testpass)
    'test@zyrahost.com' => [
        'password_hash' => $test_pass_hash, 
        'hosted_bots' => 3,
        'last_login' => time() - (3600 * 24 * 7), // Il y a 7 jours
    ]
];

// Fonction pour sauvegarder le tableau $users dans ce fichier
function save_users($new_users_array) {
    // Nettoie le hash d'exemple pour qu'il soit généré dynamiquement lors du rechargement
    $code = "<?php\n// Attention: La variable \$test_pass_hash doit être générée une fois pour simuler l'entrée dans la BD.\n// Nous la recréons ici pour que le fichier soit auto-maintenu.\n\n\$test_pass_hash = '$2y$10$tM78wA5pY0j5JgR9V1s/6.2D2F0H9Fp5.q8T8b5R3Q9dG4p7c2D7k';\n\n\$users = " . var_export($new_users_array, true) . ";\n\n// Fin du fichier de données\n";
    
    // Écrit la chaîne dans le fichier users.php
    file_put_contents(__FILE__, $code);
}

// Fonction pour créer un profil par défaut (nécessaire pour l'inscription)
function create_default_profile($email, $password) {
    global $users;
    
    // TRÈS IMPORTANT : HACHAGE SÉCURISÉ DU MOT DE PASSE
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    $users[$email] = [
        'password_hash' => $hashed_password,
        'hosted_bots' => 0,
        'last_login' => time(),
    ];
    // Sauvegarde la nouvelle liste
    save_users($users);
    return true;
}

// Fonction pour mettre à jour la dernière connexion
function update_last_login($email) {
    global $users;
    if (isset($users[$email])) {
        $users[$email]['last_login'] = time();
        save_users($users);
    }
}

// Fonction pour récupérer l'utilisateur
function get_user($email) {
    global $users;
    return $users[$email] ?? null;
}

?>
// Fin du fichier de données
