<?php
session_start();
require_once 'db.php';

// Protection d'accès : Administrateurs uniquement
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$page_title = 'Gestion des utilisateurs';
$msg_success = '';
$msg_error = '';

// Suppression d'un utilisateur
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['supprimer_id'])) {
    $supprimer_id = intval($_POST['supprimer_id']);
    if ($supprimer_id > 0) {
        try {
            $pdo->beginTransaction();
            // Réponse secrète éventuelle (FK sans cascade)
            $stmt = $pdo->prepare("DELETE FROM reponses_secretes WHERE id_users = ?");
            $stmt->execute([$supprimer_id]);
            // Tentatives de connexion liées
            $stmt = $pdo->prepare("DELETE FROM tentatives_connexion WHERE email IN (SELECT email FROM users WHERE id = ?)");
            $stmt->execute([$supprimer_id]);
            // L'utilisateur (services, réservations, avis, signalements suivent par cascade)
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$supprimer_id]);
            $pdo->commit();
            $msg_success = "L'utilisateur (son service, ses réservations, ses avis et sa question secrète) a été supprimé.";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $msg_error = "Suppression impossible : " . htmlspecialchars($e->getMessage());
        }
    }
}

// Changement de rôle (client <-> réparateur) décidé par un administrateur
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['changer_role_id'])) {
    $cibleId = intval($_POST['changer_role_id']);
    $nouveauRole = (string)($_POST['nouveau_role'] ?? '');

    if ($cibleId <= 0 || !in_array($nouveauRole, ['client', 'reparateur'], true)) {
        $msg_error = 'Demande de changement de rôle invalide.';
    } else {
        $stmtVerif = $pdo->prepare("
            SELECT u.prenom, u.nom, u.role, r.siret
            FROM users u
            LEFT JOIN reparateurs r ON r.id_user = u.id
            WHERE u.id = ?
        ");
        $stmtVerif->execute([$cibleId]);
        $cible = $stmtVerif->fetch();
        $libelleRole = ($nouveauRole === 'reparateur') ? 'réparateur' : 'client';

        if (!$cible) {
            $msg_error = 'Utilisateur introuvable.';
        } elseif ($cible['role'] === $nouveauRole) {
            $msg_error = 'Cet utilisateur est déjà « ' . $libelleRole . ' ».';
        } elseif ($nouveauRole === 'reparateur') {
            // Le SIRET est obligatoire pour un réparateur : on ne l'exige que s'il manque.
            $siretExistant = trim((string)($cible['siret'] ?? ''));
            $siretDejaValide = (bool)preg_match('/^\d{14}$/', $siretExistant);
            $siret = preg_replace('/\D+/', '', (string)($_POST['siret'] ?? ''));

            if (!$siretDejaValide && !preg_match('/^\d{14}$/', $siret)) {
                $msg_error = 'Le numéro SIRET doit contenir exactement 14 chiffres pour passer cet utilisateur en réparateur.';
            } else {
                try {
                    $pdo->beginTransaction();

                    $stmtRole = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
                    $stmtRole->execute([$nouveauRole, $cibleId]);

                    if (!$siretDejaValide) {
                        $stmtRep = $pdo->prepare("INSERT INTO reparateurs (id_user, siret, specialite) VALUES (?, ?, ?)");
                        $stmtRep->execute([$cibleId, $siret, null]);
                    }

                    $pdo->commit();
                    $msg_success = $cible['prenom'] . ' ' . $cible['nom'] . ' est désormais ' . $libelleRole . '.';
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    $msg_error = 'Changement de rôle impossible : ' . htmlspecialchars($e->getMessage());
                }
            }
        } else {
            $stmtRole = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
            $stmtRole->execute([$nouveauRole, $cibleId]);
            // La ligne `reparateurs` est conservée pour autoriser un retour ultérieur en réparateur.
            $msg_success = $cible['prenom'] . ' ' . $cible['nom'] . ' est désormais ' . $libelleRole . '.';
        }
    }
}

$stmt = $pdo->query("
    SELECT u.*, r.siret, r.specialite
    FROM users u
    LEFT JOIN reparateurs r ON r.id_user = u.id
    ORDER BY u.created_at DESC
");
$users = $stmt->fetchAll();

require_once 'header.php';
?>
<div class="top-bar">
    <h1>Gestion des utilisateurs</h1>
    <a href="dashboard.php" class="btn btn-secondary btn-sm">← Tableau de bord</a>
</div>

<?php if ($msg_error): ?>
    <div class="msg-error"><?= $msg_error ?></div>
<?php endif; ?>
<?php if ($msg_success): ?>
    <div class="msg-success"><?= $msg_success ?></div>
<?php endif; ?>

<?php if (empty($users)): ?>
    <div class="empty-message">Aucun utilisateur inscrit.</div>
<?php else: ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Email</th>
                    <th>Rôle</th>
                    <th>Ville</th>
                    <th>SIRET</th>
                    <th>Inscrit le</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <?php
                    // Un SIRET valide est déjà associé : le champ n'est demandé que pour les clients sans SIRET.
                    $sDejaValide = (bool)preg_match('/^\d{14}$/', trim((string)($u['siret'] ?? '')));
                    $besoinSiret = ($u['role'] === 'client' && !$sDejaValide);
                    $estReparateur = ($u['role'] === 'reparateur');
                    ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($u['prenom'] . ' ' . $u['nom']) ?></strong></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td><span class="badge badge-<?= htmlspecialchars($u['role']) ?>"><?= htmlspecialchars($u['role']) ?></span></td>
                        <td><?= htmlspecialchars($u['ville']) ?></td>
                        <td><?= htmlspecialchars($u['siret'] ?? '—') ?></td>
                        <td><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                        <td>
                            <form method="POST" class="form-inline"
                                  onsubmit="return confirm('Passer <?= $estReparateur ? 'en client' : 'en réparateur' ?> ?');">
                                <input type="hidden" name="changer_role_id" value="<?= (int) $u['id'] ?>">
                                <input type="hidden" name="nouveau_role" value="<?= $estReparateur ? 'client' : 'reparateur' ?>">
                                <?php if ($besoinSiret): ?>
                                    <input type="text" name="siret" placeholder="SIRET (14 chiffres)" maxlength="14"
                                           pattern="\d{14}" inputmode="numeric" required
                                           style="width: 160px; display: inline-block; padding: 0.3rem 0.5rem; font-size: 0.85rem; margin-right: 0.3rem;">
                                <?php endif; ?>
                                <button type="submit" class="btn btn-action <?= $estReparateur ? 'btn-secondary' : 'btn-accept' ?>">
                                    <?= $estReparateur ? '→ Client' : '→ Réparateur' ?>
                                </button>
                            </form>
                            <form method="POST" class="form-inline" onsubmit="return confirm('Supprimer définitivement cet utilisateur et toutes ses données ?');">
                                <input type="hidden" name="supprimer_id" value="<?= (int) $u['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <p class="muted">
        Le bouton <strong>→ Réparateur</strong> / <strong>→ Client</strong> change le rôle du compte
        (et le menu qui lui est proposé). Un champ SIRET est demandé uniquement lorsqu'un client
        sans SIRET valide est promu réparateur. Passer un réparateur en client ne supprime ni son SIRET,
        ni ses prestations, ni ses réservations.
    </p>
<?php endif; ?>
<?php require_once 'footer.php'; ?>