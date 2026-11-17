<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?retour=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$page_title = 'Signaler un abus';

$type = $_GET['type'] ?? 'service';
if (!in_array($type, ['service', 'avis'], true)) {
    $type = 'service';
}
$cible_id = intval($_GET['id'] ?? 0);

$motifs = [
    'contenu_inapproprie' => 'Contenu inapproprié',
    'harcelement' => 'Harcèlement',
    'arnaque' => 'Arnaque',
    'faux_avis' => 'Faux avis',
    'autre' => 'Autre motif',
];

if ($type === 'service') {
    $stmt = $pdo->prepare("SELECT s.id, s.titre, u.prenom, u.nom, u.id AS proprietaire_id FROM services s JOIN users u ON s.user_id = u.id WHERE s.id = ?");
    $stmt->execute([$cible_id]);
    $cible = $stmt->fetch();
} else {
    $stmt = $pdo->prepare("SELECT a.id, a.commentaire, r.id AS reservation_id, r.client_id AS proprietaire_id FROM avis a JOIN reservations r ON a.reservation_id = r.id WHERE a.id = ?");
    $stmt->execute([$cible_id]);
    $cible = $stmt->fetch();
}

if (!$cible) {
    require_once 'header.php';
    echo '<div class="empty-message">Élément introuvable.</div>';
    require_once 'footer.php';
    exit;
}

if ((int) $cible['proprietaire_id'] === (int) $_SESSION['user_id']) {
    require_once 'header.php';
    echo '<div class="empty-message">Vous ne pouvez pas signaler votre propre contenu.</div>';
    require_once 'footer.php';
    exit;
}

$error = '';
$msg_success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $motif = $_POST['motif'] ?? '';
    $description = trim($_POST['description'] ?? '');

    if (!isset($motifs[$motif])) {
        $error = 'Veuillez choisir un motif valide.';
    } elseif (mb_strlen($description) > 1000) {
        $error = 'La description est trop longue (1000 caractères maximum).';
    } else {
        $stmtIns = $pdo->prepare("INSERT INTO signalements (signalant_id, type, avis_id, service_id, motif, description) VALUES (?, ?, ?, ?, ?, ?)");
        $stmtIns->execute([
            $_SESSION['user_id'],
            $type,
            $type === 'avis' ? $cible_id : null,
            $type === 'service' ? $cible_id : null,
            $motif,
            $description !== '' ? $description : null,
        ]);
        header('Location: signalement.php?type=' . $type . '&id=' . $cible_id . '&envoye=1');
        exit;
    }
}

$envoye = isset($_GET['envoye']);

require_once 'header.php';
?>
<div class="card" style="max-width: 560px; margin: 2rem auto;">
    <h1 style="font-size: 1.4rem; margin-bottom: 1rem;">Signaler</h1>

    <?php if ($envoye): ?>
        <div class="msg-success">Votre signalement a bien été envoyé. L'équipe de modération va l'examiner.</div>
        <p style="text-align: center; margin-top: 1rem;"><a href="services.php" class="btn">Retour à la liste des services</a></p>
    <?php else: ?>
        <p class="muted" style="margin-bottom: 1rem;">
            <?php if ($type === 'service'): ?>
                Service : <strong><?= htmlspecialchars($cible['titre']) ?></strong> (<?= htmlspecialchars($cible['prenom'] . ' ' . $cible['nom']) ?>)
            <?php else: ?>
                Avis : <em><?= htmlspecialchars(mb_strimwidth($cible['commentaire'], 0, 120, '...')) ?></em>
            <?php endif; ?>
        </p>

        <?php if ($error): ?><div class="msg-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <form method="POST" class="motif-list">
            <div class="field">
                <label>Motif du signalement</label>
                <select name="motif" required>
                    <option value="">Choisir un motif</option>
                    <?php foreach ($motifs as $cle => $libelle): ?>
                        <option value="<?= htmlspecialchars($cle) ?>"><?= htmlspecialchars($libelle) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label>Description (facultatif)</label>
                <textarea name="description" placeholder="Précisez le problème constaté..." maxlength="1000"></textarea>
            </div>
            <button type="submit" class="btn btn-block">Envoyer le signalement</button>
        </form>
    <?php endif; ?>
</div>
<?php require_once 'footer.php'; ?>