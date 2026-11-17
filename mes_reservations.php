<?php
session_start();
require_once 'db.php';
require_once 'helpers.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header('Location: login.php');
    exit;
}

$page_title = 'Mes Réservations';
$client_id = $_SESSION['user_id'];
$msg_success = '';
$msg_error = '';

// Messages de confirmation transmis par d'autres pages
if (isset($_GET['reservee'])) {
    $msg_success = 'Votre réservation a bien été enregistrée. Vous recevrez confirmation par email.';
} elseif (isset($_GET['avis_ajoute'])) {
    $msg_success = 'Merci ! Votre avis a bien été publié.';
} elseif (isset($_GET['modifiee'])) {
    $msg_success = 'Votre réservation a été modifiée avec succès.';
}

// Annulation d'une réservation en attente
if (isset($_POST['annuler_id'])) {
    $annuler_id = intval($_POST['annuler_id']);
    $stmtCancel = $pdo->prepare("UPDATE reservations SET statut = 'annulee' WHERE id = ? AND client_id = ? AND statut = 'en_attente'");
    $stmtCancel->execute([$annuler_id, $client_id]);
    $msg_success = 'Votre réservation a été annulée.';
}

// Paiement d'une réservation
if (isset($_POST['payer_id'])) {
    $payer_id = intval($_POST['payer_id']);

    // Vérifie que la réservation appartient bien au client et que le paiement est en attente
    $stmtVerif = $pdo->prepare("SELECT p.id FROM paiements p JOIN reservations r ON p.reservation_id = r.id
                                WHERE p.id = ? AND r.client_id = ? AND p.statut = 'en_attente'");
    $stmtVerif->execute([$payer_id, $client_id]);
    if ($stmtVerif->fetch()) {
        $stmtPay = $pdo->prepare("UPDATE paiements SET statut = 'paye', date_paiement = NOW(), mode_reglement = 'carte' WHERE id = ?");
        $stmtPay->execute([$payer_id]);
        $msg_success = 'Paiement effectué avec succès.';

        $stmtRes = $pdo->prepare("SELECT r.*, s.titre, u.prenom AS rep_prenom, u.nom AS rep_nom
            FROM reservations r JOIN services s ON r.service_id = s.id JOIN users u ON s.user_id = u.id
            JOIN paiements p ON p.reservation_id = r.id
            WHERE p.id = ?");
        $stmtRes->execute([$payer_id]);
        $resInfo = $stmtRes->fetch();
        if ($resInfo) {
            require_once __DIR__ . '/send_mail.php';
            $dateFormatee = date('d/m/Y', strtotime($resInfo['date_intervention']));
            $html = mailTemplate (
                'Paiement confirmé',
                '<p>Bonjour <strong>' . htmlspecialchars($_SESSION['nom']) . '</strong>,</p>'
                . '<p>Votre paiement pour la prestation <strong>' . htmlspecialchars($resInfo['titre']) . '</strong> a bien été enregistré.</p>'
                . '<table style="width:100%;border-collapse:collapse;margin:16px 0;">'
                . '<tr><td style="padding:8px;color:#666;">Service</td><td style="padding:8px;font-weight:bold;">' . htmlspecialchars($resInfo['titre']) . '</td></tr>'
                . '<tr><td style="padding:8px;color:#666;">Réparateur</td><td style="padding:8px;font-weight:bold;">' . htmlspecialchars($resInfo['rep_prenom'] . ' ' . $resInfo['rep_nom']) . '</td></tr>'
                . '<tr><td style="padding:8px;color:#666;">Date</td><td style="padding:8px;font-weight:bold;">' . $dateFormatee . '</td></tr>'
                . '<tr><td style="padding:8px;color:#666;">Montant payé</td><td style="padding:8px;font-weight:bold;color:#0077b6;">' . number_format($resInfo['montant'], 2, ',', ' ') . ' €</td></tr>'
                . '</table>'
                . '<p>Merci pour votre confiance !</p>',
                'Voir mes réservations',
                url_absolue('mes_reservations.php')
            );
            envoyerMail($_SESSION['email'] ?? '', "Reçu de paiement — Fair'repair", $html, $_SESSION['nom'] ?? '');
        }
    } else {
        $msg_error = 'Paiement impossible.';
    }
}

// Récupération des réservations du client (avec statut du paiement)
$stmt = $pdo->prepare("
    SELECT r.*, s.titre, u.prenom AS rep_prenom, u.nom AS rep_nom, u.telephone AS rep_tel,
           p.id AS paiement_id, p.statut AS paiement_statut,
           a.id AS avis_id, a.note AS avis_note
    FROM reservations r
    JOIN services s ON r.service_id = s.id
    JOIN users u ON s.user_id = u.id
    LEFT JOIN paiements p ON p.reservation_id = r.id
    LEFT JOIN avis a ON a.reservation_id = r.id
    WHERE r.client_id = ?
    ORDER BY r.created_at DESC
");
$stmt->execute([$client_id]);
$reservations = $stmt->fetchAll();

require_once 'header.php';
?>
<div class="top-bar">
    <h1>Mes Réservations</h1>
    <a href="services.php" class="btn btn-secondary btn-sm">Trouver un réparateur</a>
</div>

<?php if (empty($reservations)): ?>
    <div class="empty-message">Vous n'avez effectué aucune réservation pour le moment.</div>
<?php else: ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Prestation</th>
                    <th>Réparateur</th>
                    <th>Date & Heure</th>
                    <th>Montant</th>
                    <th>Statut</th>
                    <th>Paiement</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reservations as $res): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($res['titre']) ?></strong></td>
                        <td>
                            <?= htmlspecialchars($res['rep_prenom'] . ' ' . $res['rep_nom']) ?><br>
                            <small class="muted"><?= htmlspecialchars($res['rep_tel']) ?></small>
                        </td>
                        <td><?= date('d/m/Y', strtotime($res['date_intervention'])) ?> à <?= substr($res['heure_intervention'], 0, 5) ?></td>
                        <td><?= number_format($res['montant'], 2, ',', ' ') ?> €</td>
                        <td><span class="badge st-<?= htmlspecialchars($res['statut']) ?>"><?= str_replace('_', ' ', htmlspecialchars($res['statut'])) ?></span></td>
                        <td>
                            <?php if ($res['paiement_statut']): ?>
                                <span class="badge st-<?= htmlspecialchars($res['paiement_statut']) ?>">
                                    <?= $res['paiement_statut'] === 'paye' ? 'Payé' : ($res['paiement_statut'] === 'rembourse' ? 'Remboursé' : 'En attente') ?>
                                </span>
                            <?php else: ?>
                                <span class="muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($res['statut'] === 'en_attente'): ?>
                                <a href="modifier_reservation.php?id=<?= (int) $res['id'] ?>" class="btn btn-secondary btn-sm">Modifier</a>
                                <form method="POST" class="form-inline" onsubmit="return confirm('Annuler cette réservation ?');">
                                    <input type="hidden" name="annuler_id" value="<?= (int) $res['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">Annuler</button>
                                </form>
                            <?php elseif ($res['statut'] === 'confirmee' || $res['statut'] === 'terminee'): ?>
                                <?php if ($res['paiement_statut'] === 'en_attente'): ?>
                                    <form method="POST" class="form-inline">
                                        <input type="hidden" name="payer_id" value="<?= (int) $res['paiement_id'] ?>">
                                        <button type="submit" class="btn btn-sm">Payer <?= number_format($res['montant'], 2, ',', ' ') ?> €</button>
                                    </form>
                                <?php endif; ?>
                                <?php if (!$res['avis_id']): ?>
                                    <a href="avis.php?reservation_id=<?= (int) $res['id'] ?>" class="btn btn-secondary btn-sm">Laisser un avis</a>
                                <?php else: ?>
                                    <span class="stars" title="Note : <?= (int) $res['avis_note'] ?>/5">
                                        <?php for ($i = 1; $i <= 5; $i++) echo $i <= (int) $res['avis_note'] ? '★' : '<span class="stars-empty">★</span>'; ?>
                                    </span>
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