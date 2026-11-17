<?php
require_once __DIR__ . '/db.php';

// Table des connexions (journal consulté dans admin_logs.php)
$sql = "CREATE TABLE IF NOT EXISTS login_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    email VARCHAR(255) NULL,
    role VARCHAR(20) NULL,
    ip_address VARCHAR(45) NOT NULL,
    login_time DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;";

$pdo->exec($sql);

// Colonnes éventuellement absentes si la table existait déjà (script idempotent)
$colonnes = $pdo->query("SHOW COLUMNS FROM login_history")->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('email', $colonnes, true)) {
    $pdo->exec("ALTER TABLE login_history ADD COLUMN email VARCHAR(255) NULL AFTER user_id");
}
if (!in_array('role', $colonnes, true)) {
    $pdo->exec("ALTER TABLE login_history ADD COLUMN role VARCHAR(20) NULL AFTER email");
}

echo "Table login_history créée ou mise à jour.";
