<?php
session_start();
require_once 'db.php';

// --- Connexion automatique grâce au cookie « Se souvenir de moi » ---
if (!isset($_SESSION['user_id']) && !empty($_COOKIE['remember_me'])) {
    $stmt = $pdo->prepare("SELECT id, prenom, nom, email, role FROM users WHERE remember_token = ?");
    $stmt->execute([hash('sha256', $_COOKIE['remember_me'])]);
    $userRemember = $stmt->fetch();

    if ($userRemember) {
        $_SESSION['user_id'] = $userRemember['id'];
        $_SESSION['nom']     = $userRemember['prenom'] . ' ' . $userRemember['nom'];
        $_SESSION['role']    = $userRemember['role'];
        $_SESSION['email']   = $userRemember['email'];
    } else {
        // Jeton inconnu ou révoqué : on nettoie le cookie du navigateur
        setcookie('remember_me', '', time() - 3600, '/');
    }
}

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}



// Paramètres de sécurité (anti force brute)
define('MAX_TENTATIVES', 5);                // échecs avant verrouillage
define('DUREE_VERROUILLAGE', 15);           // minutes de verrouillage classique
define('SEUIL_MEME_MDP', 3);                // répétitions du même mauvais mot de passe
define('DUREE_MEME_MDP', 30);               // minutes de verrouillage renforcé

$page_title = 'Connexion';
$error = '';
$msg_success = isset($_GET['inscrit']) ? 'Votre compte a bien été créé. Vous pouvez vous connecter.' : '';
$retour = '';
if (isset($_GET['retour']) && $_GET['retour'] !== '' && $_GET['retour'][0] === '/' && strpos($_GET['retour'], '//') === false) {
    $retour = $_GET['retour'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = mb_strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['mot_de_passe'] ?? '';
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    // Le champ caché 'retour' du formulaire
    if (isset($_POST['retour']) && $_POST['retour'] !== '' && $_POST['retour'][0] === '/' && strpos($_POST['retour'], '//') === false) {
        $retour = $_POST['retour'];
    }

    if (!empty($email) && !empty($password)) {

        // --- Suivi des tentatives de connexion (anti force brute) ---
        $stmt = $pdo->prepare("SELECT * FROM tentatives_connexion WHERE email = ? AND adresse_ip = ?");
        $stmt->execute([$email, $ip]);
        $tentative = $stmt->fetch();

        $verrouille = false;
        if ($tentative && $tentative['verrouille_jusqua'] !== null) {
            $finVerrouillage = strtotime($tentative['verrouille_jusqua']);
            if ($finVerrouillage > time()) {
                $verrouille = true;
                $resteMin = (int) ceil(($finVerrouillage - time()) / 60);
                if ($tentative['meme_mdp'] >= SEUIL_MEME_MDP) {
                    $error = 'Compte temporairement verrouillé : le même mot de passe erroné a été saisi '
                           . $tentative['meme_mdp'] . ' fois. Réessayez dans environ ' . $resteMin . ' min.';
                } else {
                    $error = 'Trop de tentatives de connexion échouées. Compte verrouillé et protégé pendant environ '
                           . $resteMin . ' minute(s).';
                }
            }
        }

        if (!$verrouille) {
            // Un verrouillage précédent est expiré → on repart de zéro
            if ($tentative && $tentative['verrouille_jusqua'] !== null) {
                $stmt = $pdo->prepare("UPDATE tentatives_connexion SET nb_tentatives = 0, meme_mdp = 0, dernier_mdp_hash = NULL, verrouille_jusqua = NULL WHERE email = ? AND adresse_ip = ?");
                $stmt->execute([$email, $ip]);
                $tentative = false;
            }

            // 1. Vérification dans la table admins
            $stmt = $pdo->prepare("SELECT id, prenom, nom, email, mot_de_passe, 'admin' AS role FROM admins WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            // 2. Si non trouvé dans admins, vérification dans la table users
            if (!$user) {
                $stmt = $pdo->prepare("SELECT id, prenom, nom, email, mot_de_passe, role FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch();
            }

            // 3. Vérification du mot de passe
            if ($user && password_verify($password, $user['mot_de_passe'])) {
                // Connexion réussie → on réinitialise le compteur d'échecs
                $stmt = $pdo->prepare("DELETE FROM tentatives_connexion WHERE email = ? AND adresse_ip = ?");
                $stmt->execute([$email, $ip]);

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['nom'] = $user['prenom'] . ' ' . $user['nom'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['email'] = $user['email'];

                // Historique des connexions (consultable via admin_logs.php)
                $stmt = $pdo->prepare("INSERT INTO login_history (user_id, ip_address, email, role) VALUES (?, ?, ?, ?)");
                $stmt->execute([$user['id'], $ip, $user['email'], $user['role']]);

                // --- « Se souvenir de moi » ---
                // Le jeton brut part dans le cookie, seul son empreinte SHA-256 est stockée en base.
                if (!empty($_POST['remember_me']) && $user['role'] !== 'admin') {
                    $token = bin2hex(random_bytes(32));
                    $stmt = $pdo->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
                    $stmt->execute([hash('sha256', $token), $user['id']]);
                    setcookie('remember_me', $token, [
                        'expires'  => time() + 86400 * 30,
                        'path'     => '/',
                        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
                        'httponly' => true,
                        'samesite' => 'Lax',
                    ]);
                } else {
                    // Case non cochée : on révoque d'éventuels jetons précédents
                    $stmt = $pdo->prepare("UPDATE users SET remember_token = NULL WHERE id = ?");
                    $stmt->execute([$user['id']]);
                }

                // Redirection vers la page d'origine (si paramètre retour présent et sûr)
                if (!empty($retour)) {
                    header('Location: ' . $retour);
                } else {
                    header('Location: dashboard.php');
                }
                exit;
            }

            // --- Mot de passe incorrect : on enregistre l'échec ---
            $nbTentatives = $tentative ? (int) $tentative['nb_tentatives'] + 1 : 1;
            $memeMdp = 0;

            // Détection : l'utilisateur ressaisit-il exactement le même mot de passe erroné ?
            if ($tentative && $tentative['dernier_mdp_hash'] !== null
                && password_verify($password, $tentative['dernier_mdp_hash'])) {
                $memeMdp = (int) $tentative['meme_mdp'] + 1;
            }

            $verrouilleJusqua = null;
            if ($nbTentatives >= MAX_TENTATIVES) {
                $verrouilleJusqua = date('Y-m-d H:i:s', time() + DUREE_VERROUILLAGE * 60);
            } elseif ($memeMdp >= SEUIL_MEME_MDP) {
                $verrouilleJusqua = date('Y-m-d H:i:s', time() + DUREE_MEME_MDP * 60);
            }

            $stmt = $pdo->prepare("
                INSERT INTO tentatives_connexion (email, adresse_ip, nb_tentatives, meme_mdp, dernier_mdp_hash, verrouille_jusqua)
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    nb_tentatives = VALUES(nb_tentatives),
                    meme_mdp = VALUES(meme_mdp),
                    dernier_mdp_hash = VALUES(dernier_mdp_hash),
                    verrouille_jusqua = VALUES(verrouille_jusqua)
            ");
            $stmt->execute([$email, $ip, $nbTentatives, $memeMdp, password_hash($password, PASSWORD_BCRYPT), $verrouilleJusqua]);

            // Message d'erreur informatif
            if ($verrouilleJusqua !== null) {
                if ($memeMdp >= SEUIL_MEME_MDP) {
                    $error = 'Compte verrouillé pour cause de répétition du même mot de passe erroné ('
                           . $memeMdp . ' fois). Réessayez dans ' . DUREE_MEME_MDP . ' min.';
                } else {
                    $error = 'Trop de tentatives (5 maximum). Compte verrouillé pendant ' . DUREE_VERROUILLAGE . ' minutes.';
                }
            } else {
                $reste = MAX_TENTATIVES - $nbTentatives;
                $error = 'Email ou mot de passe incorrect. Tentative ' . $nbTentatives . '/' . MAX_TENTATIVES
                       . ($reste > 0 ? ' - ' . $reste . ' tentative(s) restante(s).' : '');
            }
        }
    } else {
        $error = 'Veuillez remplir tous les champs.';
    }
}

require_once 'header.php';
?>
<div class="card auth-card">
    <h1>Connexion</h1>
    <?php if ($error): ?>
        <div class="msg-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST">
        <?php if (!empty($retour)): ?>
            <input type="hidden" name="retour" value="<?= htmlspecialchars($retour) ?>">
        <?php endif; ?>
        <div class="field">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autofocus>
        </div>
        <div class="field">
            <label for="mot_de_passe">Mot de passe</label>
            <input type="password" id="mot_de_passe" name="mot_de_passe" required>
        </div>
        <div class="field" style="display:flex;align-items:center;gap:0.5rem;">
            <input type="checkbox" name="remember_me" id="remember_me">
            <label for="remember_me" style="margin:0;">Se souvenir de moi</label>
        </div>
        <button type="submit" class="btn btn-block">Se connecter</button>
    </form>
    <p style="margin-top: 0.5rem; text-align: center;" class="muted">
        <a href="demande_password.php">Mot de passe oublié ?</a>
    </p>
    <p style="margin-top: 1rem; text-align: center;" class="muted">
        Pas encore de compte ? <a href="register.php">S'inscrire</a>
    </p>
</div>
<?php require_once 'footer.php'; ?>