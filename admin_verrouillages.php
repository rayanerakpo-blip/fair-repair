<?php
session_start();
require_once 'db.php';

// Protection d'accès : Administrateurs uniquement
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$page_title = 'Verrouillages de connexion';
$msg_success = '';
$msg_error = '';

// Déverrouillage d'un compte
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deverrouiller_id'])) {
    $id = intval($_POST['deverrouiller_id']);
    $stmt = $pdo ->prepare("DELETE FROM tentatives_connexion WHERE id = ?");
    $stmt->execute([$id]);
    $msg_success = 'Le compte a été déverrouillé.';
}

// Liste des comptes actuellement verrouillés
$stmt = $pdo->query("
    SELECT t.*,
           COALESCE(u.prenom, a.prenom) AS prenom,
           COALESCE(u.nom, a.nom) AS nom,
           COALESCE(u.role, 'admin') AS role
    FROM tentatives_connexion t
    LEFT JOIN users u ON u.email = t.email
    LEFT JOIN admins a ON a.email = t.email
    ORDER BY t.verrouille_jusqua DESC, t.updated_at DESC
");
$comptes = $stmt->fetchAll();

$now = time();

require_once 'header.php';
?>
<div class="top-bar">
    <h1>Verrouillages de connexion</h1>
    <a href="admin_users.php" class="btn btn-secondary btn-sm">← Gestion des utilisateurs</a>
</div>

<?php if (empty($comptes)): ?>
    <div class="empty-message">Aucune tentative échouée enregistrée actuellement.</div>
<?php else: ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Compte</th>
                    <th>Email</th>
                    <th>Rôle</th>
                    <th>Adresse IP</th>
                    <th>Tentatives</th>
                    <th>Verrouillé jusqu'à</th>
                    <th>Statut</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($comptes as $c): ?>
                    <?php $encoreBloque = $c['verrouille_jusqua'] !== null && strtotime($c['verrouille_jusqua']) > $now; ?>
                    <tr>
                        <td><strong><?= htmlspecialchars(($c['prenom'] ?? '') . ' ' . ($c['nom'] ?? '')) ?></strong></td>
                        <td><?= htmlspecialchars($c['email']) ?></td>
                        <td><span class="badge badge-<?= htmlspecialchars($c['role'] ?? 'client') ?>"><?= htmlspecialchars($c['role'] ?? 'client') ?></span></td>
                        <td><?= htmlspecialchars($c['adresse_ip']) ?></td>
                        <td><?= (int) $c['nb_tentatives'] ?> (dont <?= (int) $c['meme_mdp'] ?>× même mdp)</td>
                        <td><?= $c['verrouille_jusqua'] ? date('d/m/Y H:i', strtotime($c['verrouille_jusqua'])) : '—' ?></td>
                        <td>
                            <?php if ($encoreBloque): ?>
                                <span class="badge st-en_attente">Verrouillé</span>
                            <?php else: ?>
                                <span class="badge st-terminee">Expiré</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="POST" class="form-inline" onsubmit="return confirm('Déverrouiller ce compte ?');">
                                <input type="hidden" name="deverrouiller_id" value="<?= (int) $c['id'] ?>">
                                <button type="submit" class="btn btn-accept btn-action">Déverrouiller</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
<?php require_once 'footer.php'; ?>