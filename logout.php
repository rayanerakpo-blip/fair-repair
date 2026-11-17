<?php
session_start();
require_once __DIR__ . '/db.php';

// On révoque le jeton « Se souvenir de moi » de ce navigateur
if (!empty($_COOKIE['remember_me'])) {
    try {
        $stmt = $pdo->prepare("UPDATE users SET remember_token = NULL WHERE remember_token = ?");
        $stmt->execute([hash('sha256', $_COOKIE['remember_me'])]);
    } catch (PDOException $e) {
        // Base indisponible : on supprime au minimum le cookie
        error_log('logout.php : révocation du jeton impossible — ' . $e->getMessage());
    }
    setcookie('remember_me', '', time() - 3600, '/');
}

session_unset();
session_destroy();
header('Location: login.php');
exit;
