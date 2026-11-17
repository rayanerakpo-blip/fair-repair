<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? htmlspecialchars($page_title) . ' - ' : '' ?>Fair'repair</title>
    <meta name="description" content="Fair'repair met en relation les cyclistes et les réparateurs de vélos de proximité : trouvez un réparateur, réservez une intervention et payez en ligne.">
    <meta name="theme-color" content="#1a2a3a">
    <link rel="icon" type="image/svg+xml" href="favicon.svg">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="style.css?v=<?= filemtime(__DIR__ . '/style.css') ?>">
</head>
<body>
<?php if (isset($_SESSION['user_id'])): ?>
    <header class="site-header">
        <div class="header-inner container">
            <a href="dashboard.php" class="brand">🚴 Fair'repair</a>
            <nav class="nav-links">
                <?php if (($_SESSION['role'] ?? '') === 'reparateur'): ?>
                    <a href="mes_demandes.php">Mes demandes</a>
                    <a href="mes_services.php">Mes services</a>
                    <a href="ajouter_service.php">Ajouter un service</a>
                    <a href="mes_gains.php">Mes gains</a>
                    <a href="carte.php">Carte des prestations</a>
                <?php elseif (($_SESSION['role'] ?? '') === 'client'): ?>
                    <a href="services.php">Trouver un réparateur</a>
                    <a href="mes_reservations.php">Mes réservations</a>
                    <a href="carte.php">Carte des prestations</a>
                <?php elseif (($_SESSION['role'] ?? '') === 'admin'): ?>
                    <a href="admin_users.php">Utilisateurs</a>
                    <a href="admin_signalements.php">Signalements</a>
                    <a href="admin_verrouillages.php">Verrouillages</a>
                    <a href="admin_logs.php">Logs connexion</a>
                    <a href="carte.php">Carte des prestations</a>
                <?php else: ?>
                    <a href="services.php">Trouver un réparateur</a>
                    <a href="mes_reservations.php">Mes réservations</a>
                    <a href="carte.php">Carte des prestations</a>
                <?php endif; ?>
                <a href="profil.php">Mon profil</a>
                <a href="logout.php" class="logout-btn">Se déconnecter</a>
            </nav>
        </div>
    </header>
<?php else: ?>
    <header class="site-header">
        <div class="header-inner container">
            <a href="index.php" class="brand">Fair'repair  </a>
            <nav class="nav-links">
                <a href="services.php">Trouver un réparateur</a>
                <a href="carte.php">Carte des prestations</a>
                <a href="login.php">Se connecter</a>
                <a href="register.php" class="btn btn-secondary">S'inscrire</a>
            </nav>
        </div>
    </header>
<?php endif; ?>
<div class="container">
    <?php if (!empty($msg_success)): ?>
        <div class="msg-success"><?= htmlspecialchars($msg_success) ?></div>
    <?php endif; ?>
    <?php if (!empty($msg_error)): ?>
        <div class="msg-error"><?= htmlspecialchars($msg_error) ?></div>
    <?php endif; ?>