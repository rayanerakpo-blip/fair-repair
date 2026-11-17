<?php
session_start();
require_once __DIR__ . '/db.php';

// Administrateurs uniquement
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$page_title = 'Logs d\'authentification';

// Récupération des logs de connexion.
// user_id référence admins.id pour un administrateur et users.id sinon,
// le rôle enregistré à la connexion permet donc d'orienter la jointure.
$stmt = $pdo->query("
    SELECT h.*,
           COALESCE(CONCAT(u.prenom, ' ', u.nom), CONCAT(a.prenom, ' ', a.nom), h.email, 'Compte supprimé') AS identite,
           COALESCE(u.email, a.email, h.email) AS email,
           COALESCE(h.role, u.role, 'admin') AS role
    FROM login_history h
    LEFT JOIN users u ON u.id = h.user_id AND COALESCE(h.role, 'client') <> 'admin'
    LEFT JOIN admins a ON a.id = h.user_id AND COALESCE(h.role, '') = 'admin'
    ORDER BY h.login_time DESC
    LIMIT 200
");
$logs = $stmt->fetchAll();

require_once __DIR__ . '/header.php';
?>
<div class="top-bar">
    <h1>Logs d'authentification</h1>
    <a href="dashboard.php" class="btn btn-secondary btn-sm">← Tableau de bord</a>
</div>

<?php if (empty($logs)): ?>
    <div class="empty-message">Aucune connexion enregistrée pour l'instant.</div>
<?php else: ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Date &amp; heure</th>
                    <th>Utilisateur</th>
                    <th>Email</th>
                    <th>Rôle</th>
                    <th>Adresse IP</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?= date('d/m/Y H:i:s', strtotime($log['login_time'])) ?></td>
                        <td><strong><?= htmlspecialchars($log['identite']) ?></strong></td>
                        <td><?= htmlspecialchars($log['email'] ?? '—') ?></td>
                        <td><span class="badge badge-<?= htmlspecialchars($log['role']) ?>"><?= htmlspecialchars($log['role']) ?></span></td>
                        <td><?= htmlspecialchars($log['ip_address']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <p class="muted">
        200 dernières connexions enregistrées. Une ligne est écrite à chaque authentification réussie,
        puis supprimée avec le compte correspondant.
    </p>
<?php endif; ?>
<?php require_once 'footer.php'; ?>
