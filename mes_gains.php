<?php
session_start();
require_once 'db.php';
require_once 'helpers.php';

// Protection d'accès : Réparateurs uniquement
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'reparateur') {
    header('Location: login.php');
    exit;
}

$page_title = 'Mes gains';
$reparateur_id = (int) $_SESSION['user_id'];

// Synthèse des gains
function somme_statut(PDO $pdo, int $reparateur_id, string $statut): float
{
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(p.montant), 0)
        FROM paiements p
        JOIN reservations r ON p.reservation_id = r.id
        JOIN services s ON r.service_id = s.id
        WHERE s.user_id = ? AND p.statut = ?
    ");
    $stmt->execute([$reparateur_id, $statut]);
    return (float) $stmt->fetchColumn();
}

$gains_payes       = somme_statut($pdo, $reparateur_id, 'paye');
$gains_attente     = somme_statut($pdo, $reparateur_id, 'en_attente');
$gains_rembourses  = somme_statut($pdo, $reparateur_id, 'rembourse');

// Nombre d'interventions terminées
$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM reservations r
    JOIN services s ON r.service_id = s.id
    WHERE s.user_id = ? AND r.statut = 'terminee'
");
$stmt->execute([$reparateur_id]);
$nb_terminees = (int) $stmt->fetchColumn();

// Détail des paiements des clients (pour les services de ce réparateur)
$stmt = $pdo->prepare("
    SELECT p.id, p.montant, p.date_paiement, p.statut AS paiement_statut,
           p.mode_reglement, s.titre, r.date_intervention,
           u.prenom AS client_prenom, u.nom AS client_nom
    FROM paiements p
    JOIN reservations r ON p.reservation_id = r.id
    JOIN services s ON r.service_id = s.id
    JOIN users u ON r.client_id = u.id
    WHERE s.user_id = ?
    ORDER BY p.date_paiement DESC, p.id DESC
");
$stmt->execute([$reparateur_id]);
$paiements = $stmt->fetchAll();

require_once 'header.php';
?>
<div class="top-bar">
    <h1>Mes gains</h1>
    <a href="dashboard.php" class="btn btn-secondary btn-sm">← Tableau de bord</a>
</div>

<div class="grid" style="margin-bottom: 1.5rem;">
    <div class="card">
        <h3 style="font-size: 2rem; color: var(--primary);"><?= number_format($gains_payes, 2, ',', ' ') ?> €</h3>
        <p class="muted">Déjà encaissé</p>
    </div>
    <div class="card">
        <h3 style="font-size: 2rem; color: var(--warning);"><?= number_format($gains_attente, 2, ',', ' ') ?> €</h3>
        <p class="muted">En attente de paiement</p>
    </div>
    <div class="card">
        <h3 style="font-size: 2rem; color: var(--gray);"><?= number_format($gains_rembourses, 2, ',', ' ') ?> €</h3>
        <p class="muted">Remboursé</p>
    </div>
    <div class="card">
        <h3 style="font-size: 2rem;"><?= $nb_terminees ?></h3>
        <p class="muted">Interventions terminées</p>
    </div>
</div>

<?php if (empty($paiements)): ?>
    <div class="empty-message">Aucun paiement enregistré pour le moment.</div>
<?php else: ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Client</th>
                    <th>Prestation</th>
                    <th>Intervention</th>
                    <th>Payé le</th>
                    <th>Mode</th>
                    <th>Montant</th>
                    <th>Statut</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($paiements as $p): ?>
                    <tr>
                        <td><?= htmlspecialchars($p['client_prenom'] . ' ' . $p['client_nom']) ?></td>
                        <td><?= htmlspecialchars($p['titre']) ?></td>
                        <td><?= date('d/m/Y', strtotime($p['date_intervention'])) ?></td>
                        <td><?= $p['date_paiement'] ? date('d/m/Y', strtotime($p['date_paiement'])) : '—' ?></td>
                        <td>
                            <?= $p['mode_reglement']
                                ? htmlspecialchars(ucfirst(htmlspecialchars_decode($p['mode_reglement'])))
                                : '<span class="muted">—</span>' ?>
                        </td>
                        <td><strong><?= number_format((float)$p['montant'], 2, ',', ' ') ?> €</strong></td>
                        <td>
                            <span class="badge st-<?= htmlspecialchars($p['paiement_statut']) ?>">
                                <?= $p['paiement_statut'] === 'paye' ? 'Payé'
                                   : ($p['paiement_statut'] === 'rembourse' ? 'Remboursé' : 'En attente') ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
<?php require_once 'footer.php'; ?>