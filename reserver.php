<?php
session_start();
require_once 'db.php';
require_once 'helpers.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header('Location: login.php');
    exit;
}

$page_title = 'Réserver un service';

$service_id = intval($_GET['service_id'] ?? $_POST['service_id'] ?? 0);
$stmt = $pdo->prepare("SELECT s.*, u.prenom, u.nom FROM services s JOIN users u ON s.user_id = u.id WHERE s.id = ?");
$stmt->execute([$service_id]);
$service = $stmt->fetch();

if (!$service) {
    require_once 'header.php';
    echo '<div class="empty-message">Service introuvable.</div>';
    require_once 'footer.php';
    exit;
}

$error = '';
$msg_success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $adresse = trim($_POST['adresse'] ?? '');
    $code_postal = trim($_POST['code_postal'] ?? '');
    $ville = trim($_POST['ville'] ?? '');
    $date = $_POST['date_intervention'] ?? '';
    $heure = $_POST['heure_intervention'] ?? '';

    if (!empty($adresse) && !empty($code_postal) && !empty($ville) && !empty($date) && !empty($heure)) {
        try {
            $pdo->beginTransaction();

            $stmtIns = $pdo->prepare("INSERT INTO reservations (client_id, service_id, adresse, code_postal, ville, date_intervention, heure_intervention, montant, statut) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'en_attente')");
            $stmtIns->execute([$_SESSION['user_id'], $service_id, $adresse, $code_postal, $ville, $date, $heure, $service['tarif']]);
            $reservation_id = $pdo->lastInsertId();

            // Un paiement "en attente" est créé automatiquement à la réservation
            $stmtPay = $pdo->prepare("INSERT INTO paiements (reservation_id, montant, statut) VALUES (?, ?, 'en_attente')");
            $stmtPay->execute([$reservation_id, $service['tarif']]);

            $pdo->commit();

            require_once __DIR__ . '/send_mail.php';
            $dateFormatee = date('d/m/Y', strtotime($date));
            $html = mailTemplate (
                'Réservation confirmée',
                '<p>Bonjour <strong>' . htmlspecialchars($_SESSION['nom']) . '</strong>,</p>'
                . '<p>Votre réservation pour le service <strong>' . htmlspecialchars($service['titre']) . '</strong> a bien été enregistrée.</p>'
                . '<table style="width:100%;border-collapse:collapse;margin:16px 0;">'
                . '<tr><td style="padding:8px;color:#666;">Réparateur</td><td style="padding:8px;font-weight:bold;">' . htmlspecialchars($service['prenom'] . ' ' . $service['nom']) . '</td></tr>'
                . '<tr><td style="padding:8px;color:#666;">Date</td><td style="padding:8px;font-weight:bold;">' . $dateFormatee . ' à ' . htmlspecialchars($heure) . '</td></tr>'
                . '<tr><td style="padding:8px;color:#666;">Lieu</td><td style="padding:8px;font-weight:bold;">' . htmlspecialchars($adresse . ', ' . $code_postal . ' ' . $ville) . '</td></tr>'
                . '<tr><td style="padding:8px;color:#666;">Montant</td><td style="padding:8px;font-weight:bold;color:#0077b6;">' . number_format($service['tarif'], 2, ',', ' ') . ' €</td></tr>'
                . '</table>'
                . '<p>Le paiement est en attente. Vous pourrez le régler depuis votre espace.</p>',
                'Voir mes réservations',
                url_absolue('mes_reservations.php')
            );
            envoyerMail ($_SESSION['email'] ?? '', "Confirmation réservation — Fair'repair", $html, $_SESSION['nom'] ?? '');

            header('Location: mes_reservations.php?reservee=1');
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Erreur lors de la réservation : " . $e->getMessage();
        }
    } else {
        $error = "Tous les champs sont obligatoires.";
    }
}

require_once 'header.php';
?>
<div class="card" style="max-width: 520px; margin: 2rem auto;">
    <h1 style="font-size: 1.4rem; margin-bottom: 1rem;">Confirmer la réservation</h1>

    <div class="summary" style="background: #eef2f7; padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem;">
        <h3><?= htmlspecialchars($service['titre']) ?></h3>
        <p class="muted">Réparateur : <?= htmlspecialchars($service['prenom'] . ' ' . $service['nom']) ?></p>
        <p>Prix : <strong style="color: var(--primary);"><?= number_format($service['tarif'], 2, ',', ' ') ?> €</strong></p>
    </div>

    <?php if ($error): ?><div class="msg-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <form method="POST">
        <div class="field"><label>Adresse du rendez-vous</label><input type="text" name="adresse" required></div>
        <div class="field"><label>Code Postal</label><input type="text" name="code_postal" required></div>
        <div class="field"><label>Ville</label><input type="text" name="ville" required></div>
        <div class="field"><label>Date d'intervention</label><input type="date" name="date_intervention" min="<?= date('Y-m-d') ?>" required></div>
        <div class="field"><label>Heure d'intervention</label><input type="time" name="heure_intervention" required></div>
        <button type="submit" class="btn btn-block">Valider la réservation</button>
    </form>
    <p style="margin-top: 1rem; text-align: center;">
        <a href="services.php" class="muted">← Retour aux services</a>
    </p>
</div>
<?php require_once 'footer.php'; ?>