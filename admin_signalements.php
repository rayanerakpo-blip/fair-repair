<?php
session_start();
require_once 'db.php';

// Protection d'accès : Administrateurs uniquement
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$page_title = 'Modération des signalements';
$admin_id = (int) $_SESSION['user_id'];
$msg_success = '';
$msg_error = '';

$motifs = [
    'contenu_inapproprie' => 'Contenu inapproprié',
    'harcelement' => 'Harcèlement',
    'arnaque' => 'Arnaque',
    'faux_avis' => 'Faux avis',
    'autre' => 'Autre motif',
];

// Traitement des actions de modération
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $signalement_id = intval($_POST['signalement_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    try {
        $pdo ->beginTransaction();

        if ($action === 'traiter') {
            $stmt = $pdo ->prepare("UPDATE signalements SET statut = 'traite', modere_par_id = ? WHERE id = ?");
            $stmt->execute([$admin_id, $signalement_id]);
            $msg_success = 'Signalement marqué comme traité.';
        } elseif ($action === 'refuser') {
            $stmt = $pdo ->prepare("UPDATE signalements SET statut = 'refuse', modere_par_id = ? WHERE id = ?");
            $stmt->execute([$admin_id, $signalement_id]);
            $msg_success = 'Signalement refusé (non retenu).';
        } elseif ($action === 'supprimer_avis') {
            $cible_id = intval($_POST['cible_id']);
            $stmt = $pdo ->prepare("DELETE FROM avis WHERE id = ?");
            $stmt->execute([$cible_id]);
            $msg_success = 'L\'avis signalé a été supprimé.';
        } elseif ($action === 'supprimer_service') {
            $cible_id = intval($_POST['cible_id']);
            $stmt = $pdo        ->prepare("DELETE FROM services WHERE id = ?");
            $stmt->execute([$cible_id]);
            $msg_success = 'Le service signalé a été supprimé (et toutes ses données associées).';
        } else {
            $msg_error = 'Action inconnue.';
        }

        $pdo ->commit();
    } catch (PDOException $e) {
        $pdo ->rollBack();
        $msg_error = 'Erreur : ' . $e->getMessage();
    }
}

// Listes des signalements
$stmt = $pdo ->query("
    SELECT s.*, u.prenom, u.nom,
           a.commentaire AS avis_commentaire, a.note AS avis_note, a.id AS avis_vrai_id,
           sv.titre AS service_titre, sv.id AS service_vrai_id
    FROM signalements s
    JOIN users u ON s.signalant_id = u.id
    LEFT JOIN avis a ON a.id = s.avis_id
    LEFT JOIN services sv ON sv.id = s.service_id
    ORDER BY FIELD(s.statut, 'en_attente', 'traite', 'refuse'), s.created_at DESC
");
$signalements = $stmt->fetchAll();

require_once 'header.php';
?>
<div class="top-bar">
    <h1>Modération des signalements</h1>
    <a href="dashboard.php" class="btn btn-secondary btn-sm">← Tableau de bord</a>
</div>

<?php if (empty($signalements)): ?>
    <div class="empty-message">Aucun signalement à modérer.</div>
<?php else: ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Signalant</th>
                    <th>Cible</th>
                    <th>Motif</th>
                    <th>Description</th>
                    <th>Statut</th>
                    <th>Reçu le</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($signalements as $s): ?>
                    <tr>
                        <td><?= htmlspecialchars($s['prenom'] . ' ' . $s['nom']) ?></td>
                        <td>
                            <?php if ($s['type'] === 'avis'): ?>
                                <strong>Avis</strong> <?= $s['avis_note'] ?>/5<br>
                                <small class="muted"><?= htmlspecialchars(mb_strimwidth($s['avis_commentaire'] ?? '', 0, 100, '...')) ?></small>
                            <?php else: ?>
                                <strong>Service</strong><br>
                                <small class="muted"><?= htmlspecialchars(mb_strimwidth($s['service_titre'] ?? '', 0, 100, '...')) ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($motifs[$s['motif']] ?? $s['motif']) ?></td>
                        <td><?= htmlspecialchars(mb_strimwidth($s['description'] ?? '—', 0, 120, '...')) ?></td>
                        <td><span class="badge st-<?= htmlspecialchars($s['statut']) ?>"><?= htmlspecialchars(str_replace('_', ' ', $s['statut'])) ?></span></td>
                        <td><?= date('d/m/Y', strtotime($s['created_at'])) ?></td>
                        <td>
                            <?php if ($s['statut'] === 'en_attente'): ?>
                                <form method="POST" class="form-inline">
                                    <input type="hidden" name="signalement_id" value="<?= (int) $s['id'] ?>">
                                    <button type="submit" name="action" value="traiter" class="btn btn-action btn-accept">Traiter</button>
                                    <button type="submit" name="action" value="refuser" class="btn btn-action btn-reject">Refuser</button>
                                </form>
                                <?php if ($s['type'] === 'avis' && $s['avis_vrai_id']): ?>
                                    <form method="POST" class="form-inline" onsubmit="return confirm('Supprimer définitivement cet avis ?');">
                                        <input type="hidden" name="signalement_id" value="<?= (int) $s['id'] ?>">
                                        <input type="hidden" name="cible_id" value="<?= (int) $s['avis_vrai_id'] ?>">
                                        <button type="submit" name="action" value="supprimer_avis" class="btn btn-danger btn-sm">Supprimer l'avis</button>
                                    </form>
                                <?php elseif ($s['type'] === 'service' && $s['service_vrai_id']): ?>
                                    <form method="POST" class="form-inline" onsubmit="return confirm('Supprimer définitivement ce service ? Les réservations associées seront aussi supprimées.');">
                                        <input type="hidden" name="signalement_id" value="<?= (int) $s['id'] ?>">
                                        <input type="hidden" name="cible_id" value="<?= (int) $s['service_vrai_id'] ?>">
                                        <button type="submit" name="action" value="supprimer_service" class="btn btn-danger btn-sm">Supprimer le service</button>
                                    </form>
                                <?php endif; ?>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
<?php require_once 'footer.php'; ?>