<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header('Location: login.php');
    exit;
}

$page_title = 'Modifier ma réservation';
$client_id = (int) $_SESSION['user_id'];
$reservation_id = intval($_GET['id'] ?? $_POST['id'] ?? 0);

// Récupération de la réservation (seulement si elle appartient au client et est en attente)
$stmt = $pdo->prepare("
    SELECT r.*, s.titre, u.prenom, u.nom
    FROM reservations r
    JOIN services s ON r.service_id = s.id
    JOIN users u ON s.user_id = u.id
    WHERE r.id = ? AND r.client_id = ? AND r.statut = 'en_attente'
");
$stmt->execute([$reservation_id, $client_id]);
$reservation = $stmt->fetch();

if (!$reservation) {
    require_once 'header.php';
    echo '<div class="empty-message">Réservation introuvable ou non modifiable.</div>';
    require_once 'footer.php';
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $adresse = trim($_POST['adresse'] ?? '');
    $code_postal = trim($_POST['code_postal'] ?? '');
    $ville = trim($_POST['ville'] ?? '');
    $date = $_POST['date_intervention'] ?? '';
    $heure = $_POST['heure_intervention'] ?? '';

    if (empty($adresse) || empty($code_postal) || empty($ville) || empty($date) || empty($heure)) {
        $error = 'Tous les champs sont obligatoires.';
    } elseif (strtotime($date) < strtotime(date('Y-m-d'))) {
        $error = 'La date d\'intervention ne peut pas être dans le passé.';
    } else {
        $stmtUp = $pdo->prepare("
            UPDATE reservations
            SET adresse = ?, code_postal = ?, ville = ?, date_intervention = ?, heure_intervention = ?
            WHERE id = ? AND client_id = ? AND statut = 'en_attente'
        ");
        $stmtUp->execute([$adresse, $code_postal, $ville, $date, $heure, $reservation_id, $client_id]);
        header('Location: mes_reservations.php?modifiee=1');
        exit;
    }
}

require_once 'header.php';
?>
<div class="card" style="max-width: 520px; margin: 2rem auto;">
    <h1 style="font-size: 1.4rem; margin-bottom: 1rem;">Modifier ma réservation</h1>

    <p class="muted" style="margin-bottom: 1rem;">
        Prestation : <strong><?= htmlspecialchars($reservation['titre']) ?></strong>
        — <?= htmlspecialchars($reservation['prenom'] . ' ' . $reservation['nom']) ?>
    </p>

    <?php if ($error): ?><div class="msg-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <form method="POST">
        <input type="hidden" name="id" value="<?= (int) $reservation_id ?>">
        <div class="field"><label>Adresse du rendez-vous</label><input type="text" name="adresse" value="<?= htmlspecialchars($reservation['adresse']) ?>" required></div>
        <div class="field"><label>Code Postal</label><input type="text" name="code_postal" value="<?= htmlspecialchars($reservation['code_postal']) ?>" required></div>
        <div class="field"><label>Ville</label><input type="text" name="ville" value="<?= htmlspecialchars($reservation['ville']) ?>" required></div>
        <div class="field"><label>Date d'intervention</label><input type="date" name="date_intervention" min="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($reservation['date_intervention']) ?>" required></div>
        <div class="field"><label>Heure d'intervention</label><input type="time" name="heure_intervention" value="<?= htmlspecialchars(substr($reservation['heure_intervention'], 0, 5)) ?>" required></div>
        <button type="submit" class="btn btn-block">Enregistrer les modifications</button>
    </form>
    <p style="margin-top: 1rem; text-align: center;">
        <a href="mes_reservations.php" class="muted">← Retour à mes réservations</a>
    </p>
</div>
<?php require_once 'footer.php'; ?>