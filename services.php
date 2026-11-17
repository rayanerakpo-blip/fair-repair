<?php
session_start();
require_once 'db.php';
require_once 'helpers.php';

$page_title = 'Prestations disponibles';

$search = trim($_GET['q'] ?? '');
$reparateur_id = intval($_GET['reparateur_id'] ?? 0);

// Options : filtrer par réparateur (lien depuis la carte) et/ou par recherche
$services = get_services_liste($pdo, $reparateur_id, $search);

// Infos du réparateur si le filtre est actif (pour l'intitulé de la page)
$reparateur = null;
if ($reparateur_id > 0) {
    $stmt = $pdo->prepare("SELECT prenom, nom, ville FROM users WHERE id = ? AND role = 'reparateur'");
    $stmt->execute([$reparateur_id]);
    $reparateur = $stmt->fetch();
    if (!$reparateur) {
        $reparateur_id = 0;
    }
}

require_once 'header.php';
?>
<div class="top-bar">
    <h1>
        <?= $reparateur
            ? 'Services de ' . htmlspecialchars($reparateur['prenom'] . ' ' . $reparateur['nom'])
            : 'Prestations disponibles' ?>
    </h1>
    <?php if ($reparateur): ?>
        <a href="services.php" class="btn btn-secondary btn-sm">Toutes les prestations</a>
    <?php endif; ?>
</div>

<div class="search-box" style="margin-bottom: 2rem; text-align: center;">
    <form method="GET" style="display: flex; gap: 0.5rem; justify-content: center; flex-wrap: wrap;">
        <?php if ($reparateur_id > 0): ?>
            <input type="hidden" name="reparateur_id" value="<?= (int) $reparateur_id ?>">
        <?php endif; ?>
        <input type="text" name="q" placeholder="Rechercher un service, une ville..." value="<?= htmlspecialchars($search) ?>" style="width: 300px; max-width: 100%;">
        <button type="submit" class="btn">Rechercher</button>
    </form>
</div>

<?php if (empty($services)): ?>
    <div class="empty-message">Aucun service trouvé.</div>
<?php else: ?>
    <div class="grid">
        <?php foreach ($services as $svc): ?>
            <?php require '_service_card.php'; ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?php require_once 'footer.php'; ?>