<?php
session_start();

// Vérifie si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

// Vérifier si on force la récompense (pour les tests ou en cas d'échec)
$forceReward = isset($_GET['force']) && $_GET['force'] === '1';

// Génère un token unique pour sécuriser la récompense
$token = bin2hex(random_bytes(32));
$_SESSION['ad_token'] = $token;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Regarder une pub - ZyraHost</title>
<style>
body {
    margin: 0;
    background: #0f0f0f;
    color: white;
    font-family: Arial, sans-serif;
    text-align: center;
}

.card {
    background: #1a1a1a;
    border: 1px solid #333;
    padding: 25px;
    width: 400px;
    margin: 70px auto;
    border-radius: 15px;
}

.loader {
    margin-top: 20px;
    font-size: 16px;
    color: #aaa;
}
</style>

<!-- Script GNI Ads -->
<script>
// Initialiser le suivi des publicités
window.adDisplayed = false;
let adCheckInterval;

// Fonction pour vérifier la présence d'une publicité GNI
function checkGniAdVisible() {
    // Sélecteurs pour les éléments GNI Ads
    const selectors = [
        '.gni-vignette-container',
        '.gni-ad',
        '[id^="gni-"]',
        'iframe[src*="groleegni.net"]'
    ];

    // Vérifier chaque sélecteur
    for (const selector of selectors) {
        const elements = document.querySelectorAll(selector);
        for (const el of elements) {
            if (el.offsetParent !== null && 
                el.offsetWidth > 0 && 
                el.offsetHeight > 0 && 
                window.getComputedStyle(el).display !== 'none') {
                return el;
            }
        }
    }
    return null;
}

// Fonction pour charger le script GNI Ads
function loadGniAds() {
    console.log('[GNI Ads] Initialisation...');
    
    // Ajouter la marque de performance
    if (window.performance?.mark) {
        performance.mark('gni_ads:load_start');
    }
    
    return new Promise((resolve) => {
        // Vérifier si une publicité est déjà visible
        if (checkGniAdVisible()) {
            console.log('[GNI Ads] Publicité déjà présente');
            window.adDisplayed = true;
            resolve(true);
            return;
        }

        // Vérifier si le script est déjà chargé
        if (window.gniVignette) {
            console.log('[GNI Ads] Script déjà chargé, attente de l\'affichage...');
            waitForAdDisplay().then(resolve);
            return;
        }
        
        try {
            // Fonction utilitaire pour gérer les marques de performance
            function ensurePerformanceMarks() {
                if (!window.performance || !performance.mark) return;
                
                // Définir les marques nécessaires pour GNI Ads
                const marks = ['hints:start', 'hints:end', 'hidden_iframe:start', 'hidden_iframe:end'];
                
                marks.forEach(mark => {
                    if (!performance.getEntriesByName(mark).length) {
                        performance.mark(mark);
                    }
                });
                
                // Créer les mesures nécessaires
                const measures = [
                    {name: 'hints', start: 'hints:start', end: 'hints:end'},
                    {name: 'hidden_iframe', start: 'hidden_iframe:start', end: 'hidden_iframe:end'}
                ];
                
                measures.forEach(({name, start, end}) => {
                    try {
                        if (performance.getEntriesByName(start).length && performance.getEntriesByName(end).length) {
                            performance.measure(name, start, end);
                        }
                    } catch (e) {
                        console.warn(`Erreur lors de la création de la mesure '${name}':`, e);
                    }
                });
            }
            
            // Créer et configurer le script
            const script = document.createElement('script');
            
            // Initialiser les marques de performance avant le chargement
            ensurePerformanceMarks();
            script.dataset.zone = '10263677';
            script.src = 'https://groleegni.net/vignette.min.js';
            script.async = true;
            
            // Gestionnaire de succès
            script.onload = () => {
                console.log('[GNI Ads] Script chargé, attente de l\'affichage...');
                waitForAdDisplay().then(resolve);
            };
            
            // Gestionnaire d'erreur
            script.onerror = (error) => {
                console.error('[GNI Ads] ⚠️ Erreur de chargement du script:', error);
                resolve(false);
            };
            
            // Ajouter le script à la page
            console.log('[GNI Ads] Ajout du script...');
            (document.head || document.documentElement).appendChild(script);
            
        } catch (e) {
            console.error('[GNI Ads] ⚠️ Erreur lors du chargement:', e);
            resolve(false);
        }
    });
}

// Fonction d'attente de l'affichage de la publicité
function waitForAdDisplay() {
    return new Promise((resolve) => {
        let checkCount = 0;
        const maxChecks = 15; // 15 secondes max
        const checkIntervalMs = 1000;
        
        console.log(`[GNI Ads] Vérification de l'affichage (max ${maxChecks}s)...`);
        
        const checkInterval = setInterval(() => {
            checkCount++;
            
            // Vérifier la présence de la publicité
            const adElement = checkGniAdVisible();
            
            if (adElement) {
                console.log('[GNI Ads] ✅ Publicité détectée !');
                window.adDisplayed = true;
                setupAdObservers(adElement);
                clearInterval(checkInterval);
                resolve(true);
                return;
            }
            
            // Vérifier le délai maximum
            if (checkCount >= maxChecks) {
                console.warn(`[GNI Ads] ⚠️ Délai d'attente dépassé (${maxChecks}s)`);
                clearInterval(checkInterval);
                resolve(false);
            } else if (checkCount % 5 === 0) {
                console.log(`[GNI Ads] En attente... (${checkCount}/${maxChecks}s)`);
            }
        }, checkIntervalMs);
    });
}

// Configurer les observateurs pour la publicité
function setupAdObservers(adElement) {
    // Observer les changements de style
    const styleObserver = new MutationObserver((mutations) => {
        for (const mutation of mutations) {
            if (mutation.type === 'attributes' && mutation.attributeName === 'style') {
                const isVisible = adElement.offsetParent !== null && 
                               adElement.offsetWidth > 0 && 
                               adElement.offsetHeight > 0;
                window.adDisplayed = isVisible;
                console.log(`[GNI Ads] Visibilité de la pub: ${isVisible ? 'visible' : 'cachée'}`);
            }
        }
    });
    
    // Démarrer l'observation
    styleObserver.observe(adElement, {
        attributes: true,
        attributeFilter: ['style']
    });
    
    // Nettoyer l'observateur quand la page se ferme
    window.addEventListener('beforeunload', () => {
        styleObserver.disconnect();
    });
    
    // Détection des clics sur la publicité
    adElement.addEventListener('click', () => {
        console.log('[GNI Ads] ✅ Clic sur la publicité');
        window.adDisplayed = true;
    }, { once: true });
}

// Fonction pour détecter la présence de la publicité
function checkForAd() {
    const adElement = document.querySelector('#gni-ads, .gni-ad');
    if (adElement && window.getComputedStyle(adElement).display !== 'none') {
        return adElement;
    }
    return null;
}
</script>

</head>
<body>

<div class="card">
    <h2>Chargement de la publicité…</h2>
    <p>Merci de patienter quelques secondes.</p>
    <div class="loader">
        <div id="adStatus">⏳ Recherche de publicités disponibles…</div>
        <div id="adFallback" style="display: none; margin-top: 20px; padding: 15px; background: rgba(255,255,255,0.05); border-radius: 8px;">
            <p>Nous n'avons pas pu charger de publicité.</p>
            <button id="continueBtn" style="
                background: #5865F2;
                color: white;
                border: none;
                padding: 10px 20px;
                border-radius: 5px;
                cursor: pointer;
                margin-top: 10px;
            ">
                Continuer sans publicité
            </button>
        </div>
    </div>
</div>

<script>
// Configuration
let attempts = 0;

// Fonction pour afficher une publicité
async function showAd() {
    console.log("[Ad System] Début de la séquence publicitaire...");
    updateStatus("Chargement de la publicité...");
    
    // Réinitialiser l'état
    window.adDisplayed = false;
    let adShown = false;
    
    try {
        // Essayer GNI Ads en premier
        console.log("[Ad System] Tentative avec GNI Ads...");
        adShown = await loadGniAds();
        
        if (!adShown) {
            console.warn("[Ad System] ⚠️ Échec de GNI Ads, vérification des alternatives...");
            // Ici, vous pouvez ajouter d'autres réseaux publicitaires en cas d'échec
        }
        
        return adShown;
        
    } catch (error) {
        console.error("[Ad System] Erreur lors de l'affichage de la publicité:", error);
        return false;
    }
    
    // Ajouter la marque de performance manquante
    if (window.performance && performance.mark) {
        performance.mark('hidden_iframe:start');
    }

    // Essayer de charger GNI Ads
    const gniLoaded = await loadGniAds();
    
    if (gniLoaded) {
        console.log("[Ad System] GNI Ads chargé, attente de l'affichage...");
        
        // Vérifier périodiquement si la pub est affichée
        return new Promise((resolve) => {
            let checkCount = 0;
            const maxChecks = 20; // 10 secondes max (500ms * 20)
            
            adCheckInterval = setInterval(() => {
                checkCount++;
                const adElement = checkForAd();
                
                if (adElement) {
                    console.log("[Ad System] Publicité GNI détectée");
                    clearInterval(adCheckInterval);
                    window.adDisplayed = true;
                    updateStatus("Publicité chargée. Merci de patienter...");
                    
                    // Surveiller la fermeture de la pub
                    const observer = new MutationObserver(() => {
                        if (!checkForAd()) {
                            observer.disconnect();
                            window.adDisplayed = false;
                            console.log("[Ad System] Publicité terminée");
                            resolve(true);
                        }
                    });
                    
                    observer.observe(document.body, { 
                        childList: true, 
                        subtree: true 
                    });
                    
                    // Sécurité : résoudre après 2 minutes max
                    setTimeout(() => {
                        observer.disconnect();
                        resolve(true);
                    }, 120000);
                } 
                else if (checkCount >= maxChecks) {
                    console.log("[Ad System] Timeout d'attente de la publicité");
                    clearInterval(adCheckInterval);
                    resolve(false);
                }
            }, 500);
        });
    }
    
    return false;
}

// Fonction pour mettre à jour le statut
function updateStatus(message, isError = false) {
    const statusEl = document.getElementById('adStatus');
    if (statusEl) {
        statusEl.textContent = message;
        if (isError) {
            statusEl.style.color = '#ff6b6b';
        }
    }
}

// Fonction pour terminer avec succès (avec ou sans pub)
function completeSuccess() {
    updateStatus("✅ Terminé ! Redirection en cours...");
    // Vérifier si une publicité est en cours
    if (window.adDisplayed) {
        // Si une pub est affichée, on attend qu'elle se termine
        return;
    }
    window.location.href = "/reward.php?token=<?php echo $token; ?>";
}

// Variable pour suivre si une pub est affichée
window.adDisplayed = false;

// Initialisation au chargement du DOM
document.addEventListener('DOMContentLoaded', () => {
    // Nettoyage des ressources en cas de déchargement de la page
    window.addEventListener('beforeunload', () => {
        if (adCheckInterval) clearInterval(adCheckInterval);
    });
    
    // Démarrer le processus de chargement des publicités
    startAdProcess();
});

// Fonction pour démarrer le processus de publicité
function startAdProcess() {
    updateStatus("Préparation de la publicité...");
    
    // Démarrer la vérification périodique
    const checkInterval = setInterval(async () => {
        attempts++;
        console.log(`[Ad System] Tentative ${attempts}/${MAX_ATTEMPTS}...`);
        
        const adShown = await showAd();
        
        if (adShown) {
            console.log("[Ad System] Publicité affichée avec succès");
            clearInterval(checkInterval);
        } 
        else if (attempts >= MAX_ATTEMPTS) {
            console.log("[Ad System] Nombre maximum de tentatives atteint");
            clearInterval(checkInterval);
            showFallback();
        }
    }, 2000); // Vérifier toutes les 2 secondes
}

// Fonction pour afficher l'option de secours
function showFallback() {
    const fallbackEl = document.getElementById('adFallback');
    if (fallbackEl) {
        fallbackEl.style.display = 'block';
    }
    // Rediriger automatiquement après 10 secondes
    setTimeout(completeSuccess, 10000);
}

// Gestionnaire pour le bouton de secours
document.getElementById('continueBtn')?.addEventListener('click', completeSuccess);

// Si on force la récompense (pour les tests ou en cas d'échec)
<?php if ($forceReward): ?>
    setTimeout(completeSuccess, 1500);
<?php else: ?>
    // Configuration de la vérification périodique
const MAX_ATTEMPTS = 3;
const CHECK_INTERVAL = 3000; // 3 secondes entre chaque vérification

async function startAdCheck() {
    let attempts = 0;
    let adDisplayed = false;
    
    const checkInterval = setInterval(async () => {
        attempts++;
        console.log(`[Ad System] Tentative ${attempts}/${MAX_ATTEMPTS}...`);
        updateStatus(`Recherche de publicité (${attempts}/${MAX_ATTEMPTS})...`);

        try {
            // Essayer d'afficher une pub
            adDisplayed = await showAd();
            
            if (adDisplayed) {
                console.log("[Ad System] ✅ Publicité détectée avec succès");
                updateStatus("Publicité chargée. Merci de patienter...");
                clearInterval(checkInterval);
                
                // Configurer le suivi de la publicité
                setupAdTracking();
                
                // Rediriger automatiquement après 2 minutes max
                const timeoutId = setTimeout(completeSuccess, 120000);
                
                // Nettoyer le timeout si la page se ferme
                window.addEventListener('beforeunload', () => clearTimeout(timeoutId));
                
                return;
            }
            
            // Vérifier le nombre maximum de tentatives
            if (attempts >= MAX_ATTEMPTS) {
                clearInterval(checkInterval);
                handleAdFailure();
            }
            
        } catch (error) {
            console.error("[Ad System] ⚠️ Erreur lors de la vérification:", error);
            if (attempts >= MAX_ATTEMPTS) {
                clearInterval(checkInterval);
                handleAdFailure();
            }
        }
    }, CHECK_INTERVAL);
    
    // Nettoyer l'intervalle si la page se ferme
    window.addEventListener('beforeunload', () => clearInterval(checkInterval));
}

function setupAdTracking() {
    // Détection quand l'utilisateur revient sur la page
    const handleVisibilityChange = () => {
        if (!document.hidden) {
            console.log("[Ad System] Utilisateur de retour, vérification de la publicité...");
            
            // Vérifier si la publicité est toujours visible
            const adElement = checkGniAdVisible();
            if (!adElement) {
                console.log("[Ad System] Publicité terminée, redirection...");
                completeSuccess();
            }
        }
    };
    
    document.addEventListener("visibilitychange", handleVisibilityChange);
    
    // Nettoyer l'événement quand la page se ferme
    window.addEventListener('beforeunload', () => {
        document.removeEventListener("visibilitychange", handleVisibilityChange);
    });
}

function handleAdFailure() {
    console.warn("[Ad System] ⚠️ Échec du chargement des publicités après plusieurs tentatives");
    updateStatus("Impossible de charger une publicité. Redirection...");
    
    // Afficher l'option de secours
    const fallbackEl = document.getElementById('adFallback');
    if (fallbackEl) {
        fallbackEl.style.display = 'block';
    }
    
    // Rediriger automatiquement après 5 secondes
    setTimeout(completeSuccess, 5000);
}

// Démarrer la vérification
startAdCheck();
<?php endif; ?>
</script>

</body>
</html>
