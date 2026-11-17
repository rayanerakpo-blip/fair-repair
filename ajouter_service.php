<?php
session_start();
require_once 'db.php';
require_once 'helpers.php';

// Protection d'accès : Réparateurs uniquement
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'reparateur') {
    header('Location: login.php');
    exit;
}

$page_title = 'Ajouter un service';
$error = '';
$msg_success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = trim($_POST['titre'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $tarif = floatval($_POST['tarif'] ?? 0);
    $rayon = intval($_POST['rayon'] ?? 10);

    if (empty($titre) || empty($description) || $tarif <= 0) {
        $error = 'Veuillez remplir correctement tous les champs obligatoires (titre, description, tarif).';
    } else {
        // Upload de l'image (facultatif)
        $upload = traiter_image_upload($_FILES['image'] ?? null);
        if (!$upload['ok']) {
            $error = $upload['erreur'];
        } else {
            $stmt = $pdo->prepare("INSERT INTO services (user_id, titre, description, tarif, rayon, image) VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$_SESSION['user_id'], $titre, $description, $tarif, $rayon, $upload['fichier']])) {
                $msg_success = 'La prestation a été publiée avec succès !';
            } else {
                supprimer_image($upload['fichier']); // nettoyage si l'insertion échoue
                $error = "Une erreur s'est produite lors de l'ajout.";
            }
        }
    }
}

require_once 'header.php';
?>
<div class="card" style="max-width: 560px; margin: 2rem auto;">
    <h1 style="font-size: 1.4rem; margin-bottom: 1rem;">Ajouter une prestation</h1>

    <?php if ($error): ?><div class="msg-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($msg_success): ?><div class="msg-success"><?= htmlspecialchars($msg_success) ?></div><?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="field">
            <label for="titre">Titre du service</label>
            <input type="text" id="titre" name="titre" placeholder="ex: Changement de dérailleur" required>
        </div>
        <div class="field">
            <label for="description">Description détaillée</label>
            <textarea id="description" name="description" placeholder="Détaillez le déroulement de votre intervention..." required></textarea>
        </div>
        <div class="field">
            <label for="tarif">Tarif (€)</label>
            <input type="number" step="0.01" min="0" id="tarif" name="tarif" placeholder="35.00" required>
        </div>
        <div class="field">
            <label for="rayon">Rayon de déplacement (km)</label>
            <input type="number" id="rayon" name="rayon" value="10" min="1" required>
        </div>
        <div class="field">
            <label for="image">Photo de la prestation (optionnel, JPG/PNG/GIF/WEBP, 2 Mo max)</label>
            <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/gif,image/webp">
        </div>
        <button type="submit" class="btn btn-block">Publier la prestation</button>
    </form>
    <p style="margin-top: 1rem; text-align: center;">
        <a href="mes_services.php" class="muted">← Gérer mes services</a>
    </p>
</div>
<?php require_once 'footer.php'; ?>