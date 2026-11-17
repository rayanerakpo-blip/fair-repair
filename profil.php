<?php
session_start();
require_once 'db.php';
require_once 'helpers.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$role = $_SESSION['role'] ?? 'client';
$error = '';
$success = '';

// Messages de la bascule de rôle, transmis via la session après redirection
if (!empty($_SESSION['flash_success'])) {
    $success = $_SESSION['flash_success'];
}
if (!empty($_SESSION['flash_error'])) {
    $error = $_SESSION['flash_error'];
}
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// Chargement des données : table users pour clients/réparateurs, table admins pour les administrateurs
if ($role === 'admin') {
    $stmt = $pdo->prepare("SELECT prenom, nom, email FROM admins WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
} else {
    $stmt = $pdo->prepare("SELECT nom, prenom, email, telephone, adresse, code_postal, ville FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
}

if (!$user) {
    header('Location: logout.php');
    exit;
}

// Questions secrètes (modification pour les non-admins)
$questions = $pdo->query("SELECT id_questions, questions FROM questions_secretes ORDER BY id_questions")->fetchAll();
$question_actuelle = null;
if ($role !== 'admin') {
    $stmtQ = $pdo->prepare("
        SELECT r.id_questions, q.questions
        FROM reponses_secretes r
        JOIN questions_secretes q ON r.id_questions = q.id_questions
        WHERE r.id_users = ?
        LIMIT 1
    ");
    $stmtQ->execute([$user_id]);
    $question_actuelle = $stmtQ->fetch();
}

// --- Bascule de rôle client <-> réparateur ---
// La bascule est réservée aux comptes utilisateurs (ni admin, ni rôle inconnu).
$basculeAutorisee = in_array($role, ['client', 'reparateur'], true);
$roleActuel = $basculeAutorisee ? $role : 'client';
$nouveauRole = ($roleActuel === 'reparateur') ? 'client' : 'reparateur';

// Ligne `reparateurs` éventuelle : on récupère le SIRET déjà connu pour ne
// le redemander que lorsqu'il manque (même logique que register.php).
$ligneReparateur = false;
if ($basculeAutorisee) {
    $stmtRep = $pdo->prepare("SELECT siret, specialite FROM reparateurs WHERE id_user = ?");
    $stmtRep->execute([$user_id]);
    $ligneReparateur = $stmtRep->fetch();
}

$siretEnregistre  = $ligneReparateur ? trim((string)($ligneReparateur['siret'] ?? '')) : '';
$siretDejaValide  = (bool)preg_match('/^\d{14}$/', $siretEnregistre);
$besoinDuSiret    = ($nouveauRole === 'reparateur' && !$siretDejaValide);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'basculer_role') {

    if (!$basculeAutorisee) {
        $_SESSION['flash_error'] = 'Ce compte ne peut pas changer de rôle.';
        header('Location: profil.php');
        exit;
    }

    // --- Passage en réparateur : le SIRET est obligatoire (14 chiffres) ---
    if ($nouveauRole === 'reparateur') {
        $siret = preg_replace('/\D+/', '', (string)($_POST['siret'] ?? ''));
        $specialite = trim((string)($_POST['specialite'] ?? ''));

        if ($besoinDuSiret && !preg_match('/^\d{14}$/', $siret)) {
            $_SESSION['flash_error'] = 'Le numéro SIRET doit contenir exactement 14 chiffres pour devenir réparateur.';
            header('Location: profil.php');
            exit;
        }

        // Si un SIRET valide existe déjà, on le conserve (et on n'écrase pas la spécialité existante).
        if (!$besoinDuSiret) {
            $siret = $siretEnregistre;
            if ($specialite === '') {
                $specialite = (string)($ligneReparateur['specialite'] ?? '');
            }
        }

        $valeurSpecialite = ($specialite !== '') ? $specialite : null;

        try {
            $pdo->beginTransaction();

            $stmtRole = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
            $stmtRole->execute([$nouveauRole, $user_id]);

            // La ligne `reparateurs` est créée si absente, complétée sinon.
            if ($ligneReparateur) {
                $stmtRep = $pdo->prepare("UPDATE reparateurs SET siret = ?, specialite = ? WHERE id_user = ?");
                $stmtRep->execute([$siret, $valeurSpecialite, $user_id]);
            } else {
                $stmtRep = $pdo->prepare("INSERT INTO reparateurs (id_user, siret, specialite) VALUES (?, ?, ?)");
                $stmtRep->execute([$user_id, $siret, $valeurSpecialite]);
            }

            $pdo->commit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['flash_error'] = 'Changement de rôle impossible. Veuillez réessayer.';
            header('Location: profil.php');
            exit;
        }

        $_SESSION['role'] = 'reparateur';
        $_SESSION['flash_success'] = 'Vous êtes désormais réparateur : vous pouvez publier vos prestations et recevoir des demandes.';

    // --- Passage en client ---
    } else {
        $stmtRole = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmtRole->execute([$nouveauRole, $user_id]);

        $_SESSION['role'] = 'client';
        $_SESSION['flash_success'] = 'Vous êtes désormais client : vous pouvez réserver les prestations des réparateurs.';
        // La ligne `reparateurs` (SIRET, spécialité) est conservée pour faciliter
        // un futur retour en mode réparateur ; vos services et réservations restent intacts.
    }

    header('Location: profil.php');
    exit;

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $prenom = trim($_POST['prenom'] ?? '');
    $nom = trim($_POST['nom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $new_password = $_POST['new_password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Adresse email invalide.';
    } elseif (empty($nom) || empty($prenom)) {
        $error = 'Les champs Nom et Prénom sont obligatoires.';
    } elseif (!empty($new_password) && ($erreurMdp = valider_mot_de_passe($new_password)) !== '') {
        $error = $erreurMdp;
    } else {
        $nouveauHash = !empty($new_password) ? password_hash($new_password, PASSWORD_BCRYPT) : null;

        try {
            $pdo->beginTransaction();

            if ($role === 'admin') {
                $sql = "UPDATE admins SET prenom = ?, nom = ?, email = ?";
                $params = [$prenom, $nom, $email];
                if ($nouveauHash) {
                    $sql .= ", mot_de_passe = ?";
                    $params[] = $nouveauHash;
                }
                $sql .= " WHERE id = ?";
                $params[] = $user_id;
            } else {
                $telephone = trim($_POST['telephone'] ?? '');
                $adresse = trim($_POST['adresse'] ?? '');
                $code_postal = trim($_POST['code_postal'] ?? '');
                $ville = trim($_POST['ville'] ?? '');

                $sql = "UPDATE users SET nom = ?, prenom = ?, email = ?, telephone = ?, adresse = ?, code_postal = ?, ville = ?";
                $params = [$nom, $prenom, $email, $telephone, $adresse, $code_postal, $ville];
                if ($nouveauHash) {
                    $sql .= ", mot_de_passe = ?";
                    $params[] = $nouveauHash;
                }
                $sql .= " WHERE id = ?";
                $params[] = $user_id;
            }

            $stmtUpdate = $pdo->prepare($sql);
            $stmtUpdate->execute($params);

            // Mise à jour de la question secrète (clients / réparateurs uniquement)
            if ($role !== 'admin') {
                $question_id = intval($_POST['question_id'] ?? 0);
                $reponse = trim($_POST['reponse'] ?? '');

                if ($question_id > 0 && mb_strlen($reponse) >= 2) {
                    $stmtDel = $pdo->prepare("DELETE FROM reponses_secretes WHERE id_users = ?");
                    $stmtDel->execute([$user_id]);

                    $stmtSecret = $pdo->prepare("INSERT INTO reponses_secretes (id_questions, id_users, reponses) VALUES (?, ?, ?)");
                    $stmtSecret->execute([$question_id, $user_id, password_hash(mb_strtolower($reponse), PASSWORD_BCRYPT)]);
                } elseif ($question_id > 0 || !empty($reponse)) {
                    // Sélection incomplète : on ignore silencieusement la question (pas de casse du profil)
                    $error = 'Pour modifier votre question secrète, choisissez une question ET saisissez une réponse.';
                }
            }

            $pdo->commit();

            $_SESSION['nom'] = $prenom . ' ' . $nom;
            $success = 'Votre profil a été mis à jour avec succès.';

            if (empty($error)) {
                $user = array_merge($user, ['prenom' => $prenom, 'nom' => $nom, 'email' => $email] + ($role !== 'admin' ? ['telephone' => $telephone ?? $user['telephone'] ?? '', 'adresse' => $adresse ?? $user['adresse'] ?? '', 'code_postal' => $code_postal ?? $user['code_postal'] ?? '', 'ville' => $ville ?? $user['ville'] ?? ''] : []));
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            if ($e->getCode() == 23000) {
                $error = 'Cette adresse email est déjà utilisée.';
            } else {
                $error = 'Erreur lors de la mise à jour.';
            }
        }
    }
}

$page_title = 'Mon profil';
require_once 'header.php';
?>
<div class="card" style="max-width: 650px; margin: 2rem auto;">
    <h2>Mon Profil</h2>
    <p class="muted" style="margin-bottom: 1rem;">Rôle : <span class="badge role-badge badge-<?= htmlspecialchars($role) ?>"><?= htmlspecialchars($role) ?></span></p>

    <?php if ($error): ?><div class="msg-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="msg-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

    <?php if ($basculeAutorisee): ?>
        <hr style="margin: 1.5rem 0; border: 0; border-top: 1px solid var(--border-color);">
        <h3 style="font-size: 1rem; margin-bottom: 0.5rem;">🔄 Changer de rôle</h3>
        <p class="muted" style="margin-bottom: 0.8rem;">
            Vous êtes actuellement <strong><?= $roleActuel === 'reparateur' ? 'réparateur' : 'client' ?></strong>.
            <?php if ($roleActuel === 'reparateur'): ?>
                En passant en mode <strong>client</strong>, le menu et les pages disponibles changent.
                Vos prestations restent enregistrées, mais vous ne pourrez plus les gérer
                tant que vous n'aurez pas rebasculé en réparateur.
            <?php else: ?>
                En passant en mode <strong>réparateur</strong>, le menu et les pages disponibles changent.
                Vos réservations restent enregistrées.
            <?php endif; ?>
        </p>

        <form method="POST" action="profil.php">
            <input type="hidden" name="action" value="basculer_role">

            <?php if ($besoinDuSiret): ?>
                <div class="reparateur-fields">
                    <div class="field">
                        <label for="siret">Numéro SIRET (obligatoire pour devenir réparateur)</label>
                        <input type="text" id="siret" name="siret" inputmode="numeric" maxlength="14"
                               pattern="\d{14}" placeholder="Ex. 53264327800015" required>
                    </div>
                    <div class="field" style="margin-bottom: 0;">
                        <label for="specialite">Spécialité (facultatif)</label>
                        <input type="text" id="specialite" name="specialite" maxlength="100"
                               placeholder="Ex. Vélos de ville, VAE et réparations à domicile">
                    </div>
                </div>
            <?php elseif ($ligneReparateur && !empty($ligneReparateur['specialite'])): ?>
                <p class="muted" style="margin-bottom: 0.8rem;">
                    Réparateur — SIRET <strong><?= htmlspecialchars($siretEnregistre) ?></strong>,
                    spécialité : <?= htmlspecialchars((string)$ligneReparateur['specialite']) ?>.
                </p>
            <?php endif; ?>

            <button type="submit" class="btn btn-secondary btn-block">
                <?= $roleActuel === 'reparateur' ? 'Basculer en client' : 'Basculer en réparateur' ?>
            </button>
        </form>
        <hr style="margin: 1.5rem 0; border: 0; border-top: 1px solid var(--border-color);">
    <?php endif; ?>

    <form method="POST">
        <div style="display: flex; gap: 1rem;">
            <div class="field" style="flex: 1;">
                <label for="nom">Nom</label>
                <input type="text" id="nom" name="nom" value="<?= htmlspecialchars($user['nom']) ?>" required>
            </div>
            <div class="field" style="flex: 1;">
                <label for="prenom">Prénom</label>
                <input type="text" id="prenom" name="prenom" value="<?= htmlspecialchars($user['prenom']) ?>" required>
            </div>
        </div>

        <div class="field">
            <label for="email">Adresse Email</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
        </div>

        <?php if ($role !== 'admin'): ?>
            <div class="field">
                <label for="telephone">Téléphone</label>
                <input type="text" id="telephone" name="telephone" value="<?= htmlspecialchars($user['telephone'] ?? '') ?>">
            </div>

            <div class="field">
                <label for="adresse">Adresse (Rue, Numéro)</label>
                <input type="text" id="adresse" name="adresse" value="<?= htmlspecialchars($user['adresse'] ?? '') ?>">
            </div>

            <div style="display: flex; gap: 1rem;">
                <div class="field" style="flex: 1;">
                    <label for="code_postal">Code Postal</label>
                    <input type="text" id="code_postal" name="code_postal" value="<?= htmlspecialchars($user['code_postal'] ?? '') ?>">
                </div>
                <div class="field" style="flex: 2;">
                    <label for="ville">Ville</label>
                    <input type="text" id="ville" name="ville" value="<?= htmlspecialchars($user['ville'] ?? '') ?>">
                </div>
            </div>

            <hr style="margin: 1.5rem 0; border: 0; border-top: 1px solid var(--border-color);">

            <div class="field">
                <label for="question_id">Question secrète (récupération de mot de passe)</label>
                <select name="question_id" id="question_id">
                    <option value="">Ne pas modifier</option>
                    <?php foreach ($questions as $q): ?>
                        <option value="<?= (int)$q['id_questions'] ?>" <?= $question_actuelle && (int)$question_actuelle['id_questions'] === (int)$q['id_questions'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($q['questions']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="reponse">Réponse (indispensable si vous changez la question)</label>
                <input type="text" id="reponse" name="reponse" maxlength="255" placeholder="Nouvelle réponse (2 caractères minimum)">
            </div>
        <?php endif; ?>

        <hr style="margin: 1.5rem 0; border: 0; border-top: 1px solid var(--border-color);">

        <div class="field">
            <label for="new_password">Changer de mot de passe (laisser vide pour ne pas modifier)</label>
            <input type="password" id="new_password" name="new_password" minlength="12"
                   pattern="<?= htmlspecialchars(pattern_mot_de_passe()) ?>"
                   placeholder="12 caractères minimum, majuscule, minuscule, chiffre, caractère spécial"
                   title="<?= htmlspecialchars(aide_mot_de_passe()) ?>">
            <p class="muted" style="margin-top: 0.3rem; font-size: 0.8rem;"><?= htmlspecialchars(aide_mot_de_passe()) ?></p>
        </div>

        <button type="submit" class="btn btn-block">Enregistrer les modifications</button>
    </form>

    <hr style="margin: 1.5rem 0; border: 0; border-top: 1px solid var(--border-color);">
    <h3 style="font-size: 1rem; margin-bottom: 0.5rem;">🔒 Données personnelles (RGPD)</h3>
    <p class="muted" style="margin-bottom: 0.8rem;">
        Consultez, exportez ou supprimez vos données personnelles conformément au RGPD.
    </p>
    <a href="mes_donnees.php" class="btn btn-secondary btn-sm">Mes données personnelles</a>
</div>
<?php require_once 'footer.php'; ?>