<?php
session_start();
require_once 'db.php';
require_once 'helpers.php';

// Protection d'accès : Réparateurs uniquement
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'reparateur') {
    header('Location: login.php');
    exit;
}

$page_title = 'Mes services';
$reparateur_id = (int) $_SESSION['user_id'];

// Suppression d'un service (vérification d'appartenance)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['supprimer_id'])) {
    $supprimer_id = intval($_POST['supprimer_id']);
    $stmt = $pdo->prepare("SELECT image FROM services WHERE id = ? AND user_id = ?");
    $stmt->execute([$supprimer_id, $reparateur_id]);
    $svc = $stmt->fetch();

    if ($svc) {
        $stmtDel = $pdo->prepare("DELETE FROM services WHERE id = ? AND user_id = ?");
        $stmtDel->execute([$supprimer_id, $reparateur_id]);
        supprimer_image($svc['image']);
        header('Location: mes_services.php?supprime=1');
        exit;
    }
}

// Liste des services du réparateur connecté
$stmt = $pdo->prepare("
    SELECT s.*,
           COUNT(DISTINCT r.id) AS nb_demandes,
           ROUND(AVG(a.note), 1) AS note_moyenne
    FROM services s
    LEFT JOIN reservations r ON r.service_id = s.id
    LEFT JOIN avis a ON a.reservation_id = r.id
    WHERE s.user_id = ?
    GROUP BY s.id
    ORDER BY s.created_at DESC
");
$stmt->execute([$reparateur_id]);
$services = $stmt->fetchAll();

require_once 'header.php';
?>
<div class="top-bar">
    <h1>Mes services</h1>
    <a href="ajouter_service.php" class="btn">+ Ajouter un service</a>
</div>

<?php if (isset($_GET['supprime'])): ?>
    <div class="msg-success">Le service et toutes ses données associées ont été supprimés.</div>
<?php elseif (isset($_GET['modifie'])): ?>
    <div class="msg-success">Le service a été mis à jour avec succès.</div>
<?php endif; ?>

<?php if (empty($services)): ?>
    <div class="empty-message">
        Vous n'avez encore aucun service.
        <br><a href="ajouter_service.php" class="btn" style="margin-top: 1rem;">Créer ma première prestation</a>
    </div>
<?php else: ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Prestation</th>
                    <th>Tarif</th>
                    <th>Rayon</th>
                    <th>Demandes</th>
                    <th>Note</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($services as $s): ?>
                    <tr>
                        <td>
                            <?php if (!empty($s['image'])): ?>
                                <img class="thumb" src="uploads/<?= htmlspecialchars(basename($s['image'])) ?>" alt="">
                            <?php endif; ?>
                            <strong><?= htmlspecialchars($s['titre']) ?></strong><br>
                            <small class="muted"><?= htmlspecialchars(mb_strimwidth($s['description'], 0, 80, '...')) ?></small>
                        </td>
                        <td><?= number_format((float)$s['tarif'], 2, ',', ' ') ?> €</td>
                        <td><?= (int)$s['rayon'] ?> km</td>
                        <td><a href="mes_demandes.php"><?= (int)$s['nb_demandes'] ?></a></td>
                        <td>
                            <?php if ($s['note_moyenne'] !== null): ?>
                                <span class="stars"><?= etoiles((float)$s['note_moyenne']) ?></span>
                            <?php else: ?>
                                <span class="muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="modifier_service.php?id=<?= (int)$s['id'] ?>" class="btn btn-action btn-accept">Modifier</a>
                            <form method="POST" class="form-inline" onsubmit="return confirm('Supprimer définitivement ce service ? Les réservations et avis associés seront supprimés.');">
                                <input type="hidden" name="supprimer_id" value="<?= (int)$s['id'] ?>">
                                <button type="submit" class="btn btn-action btn-reject">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
<?php require_once 'footer.php'; ?>