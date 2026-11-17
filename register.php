<?php
session_start();
require_once 'db.php';
require_once 'helpers.php';

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$page_title = 'Inscription';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $prenom = trim($_POST['prenom'] ?? '');
    $nom = trim($_POST['nom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $adresse = trim($_POST['adresse'] ?? '');
    $code_postal = trim($_POST['code_postal'] ?? '');
    $ville = trim($_POST['ville'] ?? '');
    $role = $_POST['role'] ?? 'client';
    $password = $_POST['mot_de_passe'] ?? '';
    $siret = trim($_POST['siret'] ?? '');
    $specialite = trim($_POST['specialite'] ?? '');

    if (!in_array($role, ['client', 'reparateur'], true)) {
        $role = 'client';
    }

    if ($role === 'reparateur' && empty($siret)) {
        $error = 'Le numéro SIRET est obligatoire pour les réparateurs.';
    } elseif ($role === 'reparateur' && !preg_match('/^\d{14}$/', $siret)) {
        $error = 'Le SIRET doit contenir exactement 14 chiffres.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Adresse email invalide.';
    } elseif (mb_strlen($password) < 12) {
        $error = 'Le mot de passe doit contenir au moins 12 caractères.';
    } elseif (!empty($prenom) && !empty($nom) && !empty($telephone) && !empty($adresse) && !empty($code_postal) && !empty($ville)) {
        try {
            $pdo->beginTransaction();

            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO users (prenom, nom, adresse, code_postal, ville, email, telephone, mot_de_passe, role) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$prenom, $nom, $adresse, $code_postal, $ville, $email, $telephone, $hash, $role]);

            $userId = $pdo->lastInsertId();

            if ($role === 'reparateur') {
                $stmtRep = $pdo->prepare("INSERT INTO reparateurs (id_user, siret, specialite) VALUES (?, ?, ?)");
                $stmtRep->execute([$userId, $siret, $specialite]);
            }

            $pdo->commit();

            require_once __DIR__ . '/send_mail.php';
            $typeCompte = $role === 'reparateur' ? 'réparateur' : 'client';
            $html = mailTemplate (
                'Bienvenue ' . htmlspecialchars($prenom) . ' !',
                '<p>Votre compte <strong>' . $typeCompte . '</strong> a bien été créé sur Fair\'repair.</p>'
                . '<p>Vous pouvez dès à présent vous connecter pour accéder à votre espace.</p>',
                'Se connecter',
                url_absolue('login.php')
            );
            envoyerMail ($email, "Bienvenue sur Fair'repair !", $html, $prenom . ' ' . $nom);

            header('Location: login.php?inscrit=1');
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            if ($e->getCode() == 23000) {
                $error = 'Cette adresse email est déjà utilisée.';
            } else {
                $error = 'Erreur lors de l\'inscription : ' . $e->getMessage();
            }
        }
    } else {
        $error = 'Veuillez remplir tous les champs obligatoires.';
    }
}

require_once 'header.php';
?>
<div class="card auth-card">
    <h1>Créer un compte</h1>
    <?php if ($error): ?>
        <div class="msg-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST">
        <div class="field">
            <label for="roleSelect">Je suis</label>
            <select name="role" id="roleSelect" onchange="toggleReparateurFields()">
                <option value="client" <?= ($role ?? 'client') === 'client' ? 'selected' : '' ?>>Un cycliste à la recherche d'un réparateur</option>
                <option value="reparateur" <?= ($role ?? '') === 'reparateur' ? 'selected' : '' ?>>Un réparateur de vélos</option>
            </select>
        </div>
        <div class="field"><label>Prénom</label><input type="text" name="prenom" required></div>
        <div class="field"><label>Nom</label><input type="text" name="nom" required></div>
        <div class="field"><label>Email</label><input type="email" name="email" required></div>
        <div class="field"><label>Téléphone</label><input type="text" name="telephone" required></div>
        <div class="field"><label>Adresse</label><input type="text" name="adresse" required></div>
        <div class="field"><label>Code Postal</label><input type="text" name="code_postal" required></div>
        <div class="field"><label>Ville</label><input type="text" name="ville" required></div>

        <div id="reparateurFields" class="field reparateur-fields" style="display:none;">
            <div class="field"><label>SIRET</label><input type="text" name="siret" placeholder="14 chiffres" maxlength="14" pattern="\d{14}"></div>
            <div class="field"><label>Spécialité</label><input type="text" name="specialite" placeholder="ex : Vélos de ville, VAE..."></div>
        </div>

        <div class="field"><label>Mot de passe</label><input type="password" name="mot_de_passe" required minlength="12" placeholder="12 caractères minimum"></div>
        <button type="submit" class="btn btn-block">S'inscrire</button>
    </form>
    <p style="margin-top: 1rem; text-align: center;" class="muted">
        Déjà inscrit ? <a href="login.php">Se connecter</a>
    </p>


    
</div>

<script>
    function toggleReparateurFields() {
        const role = document.getElementById('roleSelect').value;
        document.getElementById('reparateurFields').style.display = (role === 'reparateur') ? 'block' : 'none';
    }
</script>
<?php require_once 'footer.php'; ?>