<?php
/**
 * Connexion à la base de données.
 *
 * Les valeurs par défaut correspondent à une installation AMPPS locale.
 * En production, définir les variables d'environnement DB_HOST, DB_NAME,
 * DB_USER et DB_PASS (ou surcharger ce fichier, ignoré par Git).
 */
$host   = getenv('DB_HOST') ?: 'localhost';
$dbname = getenv('DB_NAME') ?: 'fair-repair';
$user   = getenv('DB_USER') ?: 'moi';
$pass   = getenv('DB_PASS') ?: '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
