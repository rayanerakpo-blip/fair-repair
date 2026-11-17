<?php
session_start();
require_once 'db.php';
require_once 'helpers.php';

$page_title = 'Réinitialiser le mot de passe';
$message = '';
$msg_type = 'success';
$token = $_GET['token'] ?? $_POST['token'] ?? '';

if (empty($token)) {
    header('Location: login.php');
    exit;
}

$stmt = $pdo->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_expires > NOW()");
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user) {
    $message = 'Ce lien de réinitialisation est invalide ou a expiré.';
    $msg_type = 'error';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
    $new_password = $_POST['mot_de_passe'] ?? '';
    $confirm = $_POST['mot_de_passe_confirm'] ?? '';

    if (($erreurMdp = valider_mot_de_passe($new_password)) !== '') {
        $message = $erreurMdp;
        $msg_type = 'error';
    } elseif ($new_password !== $confirm) {
        $message = 'Les mots de passe ne correspondent pas.';
        $msg_type = 'error';
    } else {
        $hash = password_hash($new_password, PASSWORD_BCRYPT);
        $update = $pdo->prepare("UPDATE users SET mot_de_passe = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
        $update->execute([$hash, $user['id']]);
        $message = 'Votre mot de passe a été réinitialisé avec succès.';
        $token = '';
    }
}

require_once 'header.php';
?>
<div class="card auth-card">
    <h2>Réinitialiser le mot de passe</h2>

    <?php if ($message): ?>
        <div class="msg-<?= $msg_type ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if ($user && $token): ?>
        <form method="POST">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
            <div class="field">
                <label for="mp">Nouveau mot de passe</label>
                <input type="password" id="mp" name="mot_de_passe" required minlength="12"
                       pattern="<?= htmlspecialchars(pattern_mot_de_passe()) ?>"
                       placeholder="12 caractères minimum, majuscule, minuscule, chiffre, caractère spécial"
                       title="<?= htmlspecialchars(aide_mot_de_passe()) ?>">
                <p class="muted" style="margin-top: 0.3rem; font-size: 0.8rem;"><?= htmlspecialchars(aide_mot_de_passe()) ?></p>
            </div>
            <div class="field">
                <label for="mp2">Confirmer le mot de passe</label>
                <input type="password" id="mp2" name="mot_de_passe_confirm" required minlength="12"
                       pattern="<?= htmlspecialchars(pattern_mot_de_passe()) ?>">
            </div>
            <button type="submit" class="btn btn-block">Réinitialiser</button>
        </form>
    <?php endif; ?>

    <p style="margin-top: 1rem; text-align: center;" class="muted">
        <a href="login.php">← Retour à la connexion</a>
    </p>
</div>
<?php require_once 'footer.php'; ?>