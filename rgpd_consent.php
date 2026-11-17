<?php
session_start();

// Enregistrement du consentement cookies (RGPD) côté session.
// Aucune donnée personnelle supplémentaire n'est collectée : le site n'utilise
// que le cookie de session PHPSESSID, strictement nécessaire.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['consent'] ?? '') === 'accepted') {
    $_SESSION['cookies_acceptes'] = true;
    http_response_code(204);
    exit;
}
// Requête non conforme : accès refusé
http_response_code(400);
echo 'Consentement invalide.';