<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

require_once 'db.php';
require_once 'helpers.php';

$page_title = 'Accueil';

// La page d'accueil affiche tous les services disponibles
$services = get_services_liste($pdo);

require_once 'header.php';
?>
<div class="jumbotron">
    <h1>Bienvenue sur Fair'repair</h1>
    <?php if (isset($_GET['compte_supprime'])): ?>
        <p class="msg-success" style="background: transparent; border-color: var(--accent); color: var(--accent);">
            Votre compte et toutes vos données personnelles ont été définitivement supprimés conformément au RGPD.
        </p>
    <?php endif; ?>
    <p>Mettez en relation les cyclistes et des réparateurs de vélos de proximité.
       Trouvez un professionnel près de chez vous et réservez votre intervention en quelques clics.</p>
    <a href="services.php" class="btn btn-lg">Trouver un réparateur</a>
    <a href="register.php" class="btn btn-lg btn-outline">Créer un compte</a>
</div>

<div class="grid">
    <div class="card">
        <h3>🔧 Des réparateurs de proximité</h3>
        <p class="muted">Prestataires vérifiés, interventions à domicile avec un rayon d'action défini.</p>
    </div>
    <div class="card">
        <h3>📅 Réservation en ligne</h3>
        <p class="muted">Choix de la date et de l'heure, suivi du statut de votre demande en temps réel.</p>
    </div>
    <div class="card">
        <h3>💳 Paiement simplifié</h3>
        <p class="muted">Payez votre intervention directement depuis votre espace client.</p>
    </div>
</div>

<h2 class="section-title">Nos prestations</h2>
<p class="muted" style="margin-bottom: 1.5rem;">
    Tous les services proposés par nos réparateurs partenaires.
    <?php if (empty($services)): ?>
        Aucun service n'est encore disponible.
    <?php else: ?>
        Connectez-vous pour réserver.
    <?php endif; ?>
</p>

<?php if (!empty($services)): ?>
    <div class="grid">
        <?php foreach ($services as $svc): ?>
            <?php require '_service_card.php'; ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?php require_once 'footer.php'; ?>