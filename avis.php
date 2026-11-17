<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header('Location: login.php');
    exit;
}

$reservation_id = intval($_GET['reservation_id'] ?? 0);

// Vérifier que la réservation appartient au client et est terminée
$stmt = $pdo        ->prepare("
    SELECT r.id, s.titre, u.prenom, u.nom
    FROM reservations r
    JOIN services s ON r.service_id = s.id
    JOIN users u ON s.user_id = u.id
    WHERE r.id = ? AND r.client_id = ? AND r.statut = 'terminee'
");
$stmt->execute([$reservation_id, $_SESSION['user_id']]);
$reservation = $stmt->fetch();

if (!$reservation) {
    require_once 'header.php';
    echo '<div class="empty-message">Réservation introuvable ou non éligible à un avis.</div>';
    require_once 'footer.php';
    exit;
}

// Empêcher un double avis
$stmt = $pdo->prepare("SELECT id FROM avis WHERE reservation_id = ?");
$stmt->execute([$reservation_id]);
if ($stmt->fetch()) {
    header('Location: mes_reservations.php');
    exit;
}

$page_title = 'Laisser un avis';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $note = (int) ($_POST['note'] ?? 0);
    $commentaire = trim($_POST['commentaire'] ?? '');

    if ($note < 1 || $note > 5) {
        $error = 'Veuillez choisir une note entre 1 et 5 étoiles.';
    } elseif (mb_strlen($commentaire) < 10) {
        $error = 'Votre commentaire doit contenir au moins 10 caractères.';
    } else {
        $stmtIns = $pdo->prepare("INSERT INTO avis (reservation_id, note, commentaire) VALUES (?, ?, ?)");
        $stmtIns->execute([$reservation_id, $note, $commentaire]);
        header('Location: mes_reservations.php?avis_ajoute=1');
        exit;
    }
}

require_once 'header.php';
?>
<div class="card" style="max-width: 520px; margin: 2rem auto;">
    <h1 style="font-size: 1.4rem; margin-bottom: 1rem;">Laisser un avis</h1>
    <p class="muted" style="margin-bottom: 1rem;">
        Prestation : <strong><?= htmlspecialchars($reservation['titre']) ?></strong>
        — <?= htmlspecialchars($reservation['prenom'] . ' ' . $reservation['nom']) ?>
    </p>

    <?php if ($error): ?><div class="msg-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <form method="POST">
        <div class="field">
            <label>Votre note</label>
            <select name="note" required>
                <option value="">Choisir une note</option>
                <?php for ($i = 5; $i >= 1; $i--): ?>
                    <option value="<?= $i ?>"><?= $i ?> étoile<?= $i > 1 ? 's' : '' ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="field">
            <label>Votre commentaire</label>
            <textarea name="commentaire" placeholder="Décrivez la qualité de l'intervention..." required></textarea>
        </div>
        <button type="submit" class="btn btn-block">Publier mon avis</button>
    </form>
    <p style="margin-top: 1rem; text-align: center;">
        <a href="mes_reservations.php" class="muted">← Retour à mes réservations</a>
    </p>
</div>
<?php require_once 'footer.php'; ?>