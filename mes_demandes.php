<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'reparateur') {
    header('Location: login.php');
    exit;
}

$page_title = 'Demandes reçues';
$reparateur_id = $_SESSION['user_id'];
$msg_success = '';
$msg_error = '';

// Changement d'état d'une réservation (confirmer / terminer / annuler)
if (isset($_POST['action']) && isset($_POST['reservation_id'])) {
    $res_id = intval($_POST['reservation_id']);
    $action = $_POST['action'];

    $statuts_valides = [
        'confirmer' => 'confirmee',
        'terminer'  => 'terminee',
        'annuler'   => 'annulee'
    ];

    if (array_key_exists($action, $statuts_valides)) {
        $nouveau_statut = $statuts_valides[$action];

        // Vérification que la demande appartient bien aux services du réparateur
        $stmtUp = $pdo->prepare("
            UPDATE reservations r
            JOIN services s ON r.service_id = s.id
            SET r.statut = ?
            WHERE r.id = ? AND s.user_id = ?
        ");
        $stmtUp->execute([$nouveau_statut, $res_id, $reparateur_id]);

        $labels = ['confirmer' => 'confirmée', 'terminer' => 'terminée', 'annuler' => 'annulée'];
        $msg_success = "La demande a été " . $labels[$action] . ".";
    } else {
        $msg_error = 'Action invalide.';
    }
}

// Récupération de l'ensemble des demandes destinées à ce réparateur
$stmt = $pdo->prepare("
    SELECT r.*, s.titre, u.prenom AS client_prenom, u.nom AS client_nom, u.telephone AS client_tel,
           p.id AS paiement_id, p.statut AS paiement_statut
    FROM reservations r
    JOIN services s ON r.service_id = s.id
    JOIN users u ON r.client_id = u.id
    LEFT JOIN paiements p ON p.reservation_id = r.id
    WHERE s.user_id = ?
    ORDER BY r.created_at DESC
");
$stmt->execute([$reparateur_id]);
$demandes = $stmt->fetchAll();

require_once 'header.php';
?>
<div class="top-bar">
    <h1>Demandes reçues</h1>
</div>

<?php if (empty($demandes)): ?>
    <div class="empty-message">Aucune demande reçue pour le moment.</div>
<?php else: ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Client</th>
                    <th>Prestation</th>
                    <th>Lieu d'intervention</th>
                    <th>Date & Heure</th>
                    <th>Statut</th>
                    <th>Paiement</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($demandes as $d): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($d['client_prenom'] . ' ' . $d['client_nom']) ?></strong><br>
                            <small class="muted">📞 <?= htmlspecialchars($d['client_tel']) ?></small>
                        </td>
                        <td><?= htmlspecialchars($d['titre']) ?> (<?= number_format($d['montant'], 2, ',', ' ') ?> €)</td>
                        <td><?= htmlspecialchars($d['adresse'] . ', ' . $d['code_postal'] . ' ' . $d['ville']) ?></td>
                        <td><?= date('d/m/Y', strtotime($d['date_intervention'])) ?> à <?= substr($d['heure_intervention'], 0, 5) ?></td>
                        <td><span class="badge st-<?= htmlspecialchars($d['statut']) ?>"><?= str_replace('_', ' ', htmlspecialchars($d['statut'])) ?></span></td>
                        <td>
                            <?php if ($d['paiement_statut']): ?>
                                <span class="badge st-<?= htmlspecialchars($d['paiement_statut']) ?>">
                                    <?= $d['paiement_statut'] === 'paye' ? 'Payé' : ($d['paiement_statut'] === 'rembourse' ? 'Remboursé' : 'En attente') ?>
                                </span>
                            <?php else: ?>
                                <span class="muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="POST" class="form-inline">
                                <input type="hidden" name="reservation_id" value="<?= (int) $d['id'] ?>">
                                <?php if ($d['statut'] === 'en_attente'): ?>
                                    <button type="submit" name="action" value="confirmer" class="btn btn-action btn-accept">Accepter</button>
                                    <button type="submit" name="action" value="annuler" class="btn btn-action btn-reject" onclick="return confirm('Refuser cette demande ?');">Refuser</button>
                                <?php elseif ($d['statut'] === 'confirmee'): ?>
                                    <button type="submit" name="action" value="terminer" class="btn btn-action btn-finish">Marquer terminée</button>
                                    <button type="submit" name="action" value="annuler" class="btn btn-action btn-reject" onclick="return confirm('Annuler cette demande ?');">Annuler</button>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
<?php require_once 'footer.php'; ?>