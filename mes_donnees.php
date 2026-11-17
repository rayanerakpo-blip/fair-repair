<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$role = $_SESSION['role'] ?? 'client';
$error = '';
$success = '';

if ($role === 'admin') {
    $stmt = $pdo->prepare("SELECT id, prenom, nom, email, created_at FROM admins WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    $profil_scope = 'admin';
} else {
    $stmt = $pdo->prepare("SELECT id, prenom, nom, email, telephone, adresse, code_postal, ville, role, created_at FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    $profil_scope = 'user';
}

if (!$user) {
    header('Location: logout.php');
    exit;
}

/**
 * Regroupe toutes les données personnelles de l'utilisateur (hors colonnes sensibles)
 * pour l'exercice du droit à la portabilité (export JSON).
 */
function collecter_donnees(PDO $pdo, int $user_id, string $scope): array {
    $data = [];
    if ($scope === 'admin') {
        $stmt = $pdo->prepare("SELECT id, prenom, nom, email, created_at FROM admins WHERE id = ?");
        $stmt->execute([$user_id]);
        $data['profil'] = $stmt->fetch();
        return $data;
    }
    // Profil utilisateur (sans mot_de_passe ni jeton de réinitialisation)
    $stmt = $pdo->prepare("SELECT id, prenom, nom, email, telephone, adresse, code_postal, ville, role, created_at FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $data['profil'] = $stmt->fetch();

    // Informations professionnelles (réparateur)
    $stmt = $pdo->prepare("SELECT * FROM reparateurs WHERE id_user = ?");
    $stmt->execute([$user_id]);
    $data['professionnel'] = $stmt->fetch() ?: null;

    // Services publiés
    $stmt = $pdo->prepare("SELECT id, titre, description, tarif, rayon, image, created_at FROM services WHERE user_id = ? ORDER BY id");
    $stmt->execute([$user_id]);
    $data['services'] = $stmt->fetchAll();

    // Réservations en tant que client
    $stmt = $pdo->prepare("
        SELECT r.id, r.service_id, r.adresse, r.code_postal, r.ville, r.date_intervention,
               r.heure_intervention, r.type, r.statut, r.montant, r.created_at, s.titre AS service_titre
        FROM reservations r
        LEFT JOIN services s ON s.id = r.service_id
        WHERE r.client_id = ?
        ORDER BY r.id
    ");
    $stmt->execute([$user_id]);
    $reservations_client = $stmt->fetchAll();

    // Réservations en tant que réparateur (via ses services)
    $stmt = $pdo->prepare("
        SELECT r.id, r.client_id, r.adresse, r.code_postal, r.ville, r.date_intervention,
               r.heure_intervention, r.type, r.statut, r.montant, r.created_at
        FROM reservations r
        JOIN services s ON s.id = r.service_id
        WHERE s.user_id = ?
        ORDER BY r.id
    ");
    $stmt->execute([$user_id]);
    $reservations_reparateur = $stmt->fetchAll();
    $data['reservations_client'] = $reservations_client;
    $data['reservations_reparateur'] = $reservations_reparateur;

    // Avis déposés et reçus via les réservations
    $data['avis_deposes'] = [];
    $data['avis_recus'] = [];
    $ids_client = array_column($reservations_client, 'id');
    if ($ids_client) {
        $in = implode(',', array_map('intval', $ids_client));
        $stmt = $pdo->query("SELECT reservation_id, note, commentaire, created_at FROM avis WHERE reservation_id IN ($in)");
        $data['avis_deposes'] = $stmt->fetchAll();
    }
    $ids_rep = array_column($reservations_reparateur, 'id');
    if ($ids_rep) {
        $in = implode(',', array_map('intval', $ids_rep));
        $stmt = $pdo->query("SELECT reservation_id, note, commentaire, created_at FROM avis WHERE reservation_id IN ($in)");
        $data['avis_recus'] = $stmt->fetchAll();
    }
    // Paiements liés aux réservations client
    $data['paiements'] = [];
    if ($ids_client) {
        $in = implode(',', array_map('intval', $ids_client));
        $stmt = $pdo->query("SELECT reservation_id, montant, statut, mode_reglement, date_paiement FROM paiements WHERE reservation_id IN ($in)");
        $data['paiements'] = $stmt->fetchAll();
    }
    return $data;
}

// ---- Export JSON (droit à la portabilité) ----
if (isset($_GET['export']) && $_GET['export'] === 'json') {
    $data = collecter_donnees($pdo, $user_id, $profil_scope);
    $data['editeur'] = 'Fair\'repair';
    $data['date_export'] = date('c');
    $filename = 'mes_donnees_' . ($user['email'] ?? $user_id) . '_' . date('Ymd') . '.json';
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// ---- Suppression du compte (droit à l'effacement) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['supprimer_compte'])) {
    if ($role === 'admin') {
        $error = 'Les comptes administrateurs ne peuvent pas être supprimés depuis cet espace. Contactez un autre administrateur.';
    } else {
        $password = $_POST['mot_de_passe'] ?? '';
        $confirm = isset($_POST['confirmation']);

        // Vérification du mot de passe actuel
        $stmt = $pdo->prepare("SELECT mot_de_passe FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $hash = $stmt->fetchColumn();

        if (!$confirm) {
            $error = 'Veuillez cocher la case de confirmation.';
        } elseif (!$hash || !password_verify($password, $hash)) {
            $error = 'Mot de passe incorrect. La suppression est annulée.';
        } else {
            try {
                $email_utilisateur = $user['email'];
                $pdo->beginTransaction();
                // Réponse secrète (FK sans cascade)
                $stmt = $pdo->prepare("DELETE FROM reponses_secretes WHERE id_users = ?");
                $stmt->execute([$user_id]);
                // Tentatives de connexion liées à ce compte
                $stmt = $pdo->prepare("DELETE FROM tentatives_connexion WHERE email = ?");
                $stmt->execute([$email_utilisateur]);
                // L'utilisateur : services, réservations, avis, paiements, signalements suivent par cascade
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $pdo->commit();

                // Fermeture de session et redirection (pas de retour arrière possible)
                $_SESSION = [];
                if (ini_get('session.use_cookies')) {
                    $params = session_get_cookie_params();
                    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
                }
                session_destroy();
                header('Location: index.php?compte_supprime=1');
                exit;
            } catch (PDOException $e) {
                $pdo->rollBack();
                $error = 'Suppression impossible : ' . htmlspecialchars($e->getMessage());
            }
        }
    }
}

$page_title = 'Mes données personnelles';
require_once 'header.php';
?>
<div class="card" style="max-width: 750px; margin: 2rem auto;">
    <h2>Mes données personnelles</h2>
    <p class="muted" style="margin-bottom: 1.5rem;">
        Conformément au RGPD, vous pouvez consulter, récupérer ou supprimer vos données personnelles depuis cet espace.
    </p>

    <?php if ($error): ?>
        <div class="msg-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="msg-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <h3 style="margin-bottom: 0.8rem;">1. Droit d'accès — données conservées</h3>
    <?php $data = collecter_donnees($pdo, $user_id, $profil_scope); ?>
    <table>
        <tbody>
            <tr><th>Nom</th><td><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></td></tr>
            <tr><th>Email</th><td><?= htmlspecialchars($user['email']) ?></td></tr>
            <?php if ($role !== 'admin'): ?>
                <tr><th>Rôle</th><td><?= htmlspecialchars($user['role']) ?></td></tr>
                <tr><th>Services publiés</th><td><?= count($data['services']) ?></td></tr>
                <tr><th>Réservations (client)</th><td><?= count($data['reservations_client']) ?></td></tr>
                <tr><th>Réservations (réparateur)</th><td><?= count($data['reservations_reparateur']) ?></td></tr>
                <tr><th>Avis déposés / reçus</th><td><?= count($data['avis_deposes']) ?> / <?= count($data['avis_recus']) ?></td></tr>
                <tr><th>Paiements</th><td><?= count($data['paiements']) ?></td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <hr style="margin: 1.5rem 0; border: 0; border-top: 1px solid var(--border-color);">

    <h3 style="margin-bottom: 0.8rem;">2. Droit à la portabilité — exporter mes données</h3>
    <p class="muted" style="margin-bottom: 1rem;">
        Téléchargez l'intégralité de vos données personnelles dans un fichier JSON lisible : votre profil, vos services,
        réservations, avis et paiements (hors mots de passe, jamais exportés).
    </p>
    <a href="mes_donnees.php?export=json" class="btn btn-secondary">📥 Exporter mes données (JSON)</a>

    <?php if ($role !== 'admin'): ?>
        <hr style="margin: 1.5rem 0; border: 0; border-top: 1px solid var(--border-color);">

        <h3 style="margin-bottom: 0.8rem;">3. Droit à l'effacement — supprimer mon compte</h3>
        <p class="msg-error" style="margin-bottom: 1rem;">
            ⚠️ La suppression est <strong>définitive et irréversible</strong> : vos services, réservations, avis, paiements
            et votre question secrète seront supprimés. Saisissez votre mot de passe pour confirmer.
        </p>
        <form method="POST">
            <div class="field">
                <label for="mot_de_passe">Mot de passe actuel</label>
                <input type="password" id="mot_de_passe" name="mot_de_passe" required>
            </div>
            <div class="field-consent" style="margin-bottom: 1rem;">
                <label class="checkbox-label">
                    <input type="checkbox" name="confirmation" value="1" required>
                    Je comprends que toutes mes données seront supprimées définitivement et j'accepte la suppression de mon compte.
                </label>
            </div>
            <button type="submit" name="supprimer_compte" value="1" class="btn btn-danger btn-block"
                    onclick="return confirm('Supprimer définitivement votre compte et toutes vos données ? Cette action est irréversible.');">
                🗑️ Supprimer définitivement mon compte
            </button>
        </form>
    <?php endif; ?>
</div>
<?php require_once 'footer.php'; ?>