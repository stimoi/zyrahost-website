<?php
/**
 * Redirection Permanente pour l'URL /mardi
 * Ce fichier doit être placé à la racine du site (public_html) pour fonctionner
 * sous le chemin zyrahost.fr/mardi.
 */

// NOUVELLE URL DE DESTINATION : Atelier création de jeux vidéos
$destination_url = "https://stimoi.github.io/atelier-cr-ation_de_jeux_vid-os/";

// Définir un code de statut 301 (Redirection Permanente)
header("HTTP/1.1 301 Moved Permanently");

// Définir la nouvelle URL de destination
header("Location: " . $destination_url);

// Assurez-vous d'arrêter l'exécution du script après l'envoi des headers
exit();
?>
