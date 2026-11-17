<?php
session_start();
require_once 'db.php';
require_once 'helpers.php';

$page_title = 'Mot de passe oublié';
$message = '';
$link = '';
$msg_type = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['email'])) {
    $email = trim($_POST['email']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Adresse email invalide.';
        $msg_type = 'error';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $token = bin2hex(random_bytes(32));
            $update = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE id = ?");
            $update->execute([$token, $user['id']]);

            $lienAbsolu = url_absolue('reset_password.php') . '?token=' . $token;

            require_once __DIR__ . '/send_mail.php';
            $html = mailTemplate (
                'Réinitialisation de votre mot de passe',
                '<p>Bonjour,</p>'
                . '<p>Vous avez demandé la réinitialisation de votre mot de passe Fair\'repair.</p>'
                . '<p>Ce lien est valable <strong>1 heure</strong>.</p>',
                'Réinitialiser mon mot de passe',
                $lienAbsolu
            );
            $mailEnvoye = envoyerMail($email, "Réinitialisation du mot de passe — Fair'repair", $html);

            $message = $mailEnvoye
                ? 'Un email contenant un lien de réinitialisation vient d\'être envoyé à votre adresse.'
                : 'Le lien a été généré (envoi impossible). En mode démo, cliquez sur le bouton ci-dessous.';
            $link = $mailEnvoye ? '' : "reset_password.php?token=" . $token;
        } else {
            $message = 'Si cette adresse existe dans notre base, un lien de réinitialisation a été généré.';
        }
    }
}

require_once 'header.php';
?>
<div class="card auth-card">
    <h2>Mot de passe oublié</h2>

    <?php if ($message): ?>
        <div class="msg-<?= $msg_type ?>"><?= htmlspecialchars($message) ?></div>
        <?php if (!empty($link)): ?>
            <a href="<?= htmlspecialchars($link) ?>" class="btn btn-block" style="margin-top: 1rem;">Réinitialiser mon mot de passe</a>
        <?php endif; ?>
    <?php endif; ?>

    <form method="POST">
        <div class="field">
            <label for="email">Votre adresse Email :</label>
            <input type="email" id="email" name="email" required>
        </div>
        <button type="submit" class="btn btn-block">Générer le lien</button>
    </form>
    <p style="margin-top: 1rem; text-align: center;" class="muted">
        <a href="question_secrete.php">J'ai enregistré une question secrète</a>
    </p>
    <p style="margin-top: 0.5rem; text-align: center;" class="muted">
        <a href="login.php">← Retour à la connexion</a>
    </p>
</div>
<?php require_once 'footer.php'; ?>