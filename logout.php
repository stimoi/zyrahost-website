<?php
session_start();

// Détruire toutes les variables de session
$_SESSION = array();

// Détruire le cookie de session si utilisé
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// Détruire le cookie "Se souvenir de moi"
if (isset($_COOKIE['zyrahost_remember'])) {
    setcookie('zyrahost_remember', '', time()-3600, '/');
}

// Détruire la session
session_destroy();

// Rediriger vers la page de connexion
header('Location: login.php');
exit;
?>