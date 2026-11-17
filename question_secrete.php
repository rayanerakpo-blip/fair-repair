<?php
session_start();
require_once 'db.php';

$page_title = 'Question secrète';
$message = '';
$msg_type = 'success';
$email = trim($_GET['email'] ?? $_POST['email'] ?? '');
$user = null;
$question = null;
$avancement = null; // 'email' | 'question'

if (!empty($email)) {
    // On cherche l'utilisateur et sa question secrète enregistrée
    $stmt = $pdo->prepare("SELECT id, prenom, nom FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        $stmtQ = $pdo->prepare("
            SELECT q.questions, r.reponses
            FROM reponses_secretes r
            JOIN questions_secretes q ON r.id_questions = q.id_questions
            WHERE r.id_users = ?
            LIMIT 1
        ");
        $stmtQ->execute([$user['id']]);
        $question = $stmtQ->fetch();
    }

    if (!$user || !$question) {
        // Message volontairement identique pour ne pas révéler l'existence d'un compte
        $message = 'Aucune question secrète n\'est enregistrée pour cette adresse. '
                 . 'Utilisez la réinitialisation par email.';
        $msg_type = 'error';
    } else {
        $avancement = 'question';
    }
}

// Vérification de la réponse
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user && $question && isset($_POST['reponse'])) {
    $reponse = trim($_POST['reponse'] ?? '');

    if ($reponse === '') {
        $message = 'Veuillez saisir votre réponse.';
        $msg_type = 'error';
    } elseif (password_verify(mb_strtolower($reponse), $question['reponses'])) {
        // Bonne réponse : on génère un jeton de réinitialisation réutilisé par reset_password.php
        $token = bin2hex(random_bytes(32));
        $stmtUp = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE id = ?");
        $stmtUp->execute([$token, $user['id']]);
        header('Location: reset_password.php?token=' . $token);
        exit;
    } else {
        $message = 'Réponse incorrecte. Veuillez réessayer ou utiliser la réinitialisation par email.';
        $msg_type = 'error';
    }
}

require_once 'header.php';
?>
<div class="card auth-card">
    <h2>Réinitialiser via ma question secrète</h2>

    <?php if ($message): ?>
        <div class="msg-<?= $msg_type ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if ($avancement === 'question'): ?>
        <p class="muted" style="margin: 1rem 0;">
            <?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?>, répondez à votre question secrète pour
            pouvoir choisir un nouveau mot de passe.
        </p>
        <form method="POST">
            <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
            <div class="field">
                <label>Question secrète</label>
                <p style="background: #fdf8f2; padding: 0.7rem; border-radius: 8px; border: 1px solid var(--border-color);">
                    <?= htmlspecialchars($question['questions']) ?>
                </p>
            </div>
            <div class="field">
                <label for="reponse">Votre réponse</label>
                <input type="text" id="reponse" name="reponse" required autofocus>
            </div>
            <button type="submit" class="btn btn-block">Valider et choisir un nouveau mot de passe</button>
        </form>
    <?php else: ?>
        <form method="POST">
            <div class="field">
                <label for="email">Votre adresse Email</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($email) ?>" required>
            </div>
            <button type="submit" class="btn btn-block">Continuer</button>
        </form>
    <?php endif; ?>

    <p style="margin-top: 1rem; text-align: center;" class="muted">
        <a href="demande_password.php">← Réinitialisation par email</a>
    </p>
</div>
<?php require_once 'footer.php'; ?>