<?php
session_start();
require_once 'db.php';
require_once 'helpers.php';

// Protection d'accès : Réparateurs uniquement
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'reparateur') {
    header('Location: login.php');
    exit;
}

$page_title = 'Modifier un service';
$reparateur_id = (int) $_SESSION['user_id'];
$service_id = intval($_GET['id'] ?? $_POST['id'] ?? 0);

// Récupération du service (vérification d'appartenance)
$stmt = $pdo->prepare("SELECT * FROM services WHERE id = ? AND user_id = ?");
$stmt->execute([$service_id, $reparateur_id]);
$service = $stmt->fetch();

if (!$service) {
    require_once 'header.php';
    echo '<div class="empty-message">Service introuvable ou accès non autorisé.</div>';
    require_once 'footer.php';
    exit;
}

$error = '';
$msg_success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = trim($_POST['titre'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $tarif = floatval($_POST['tarif'] ?? 0);
    $rayon = intval($_POST['rayon'] ?? 10);
    $supprimer_image = isset($_POST['supprimer_image']);

    if (empty($titre) || empty($description) || $tarif <= 0) {
        $error = 'Veuillez remplir correctement tous les champs obligatoires (titre, description, tarif).';
    } else {
        // Nouvelle image éventuelle
        $upload = traiter_image_upload($_FILES['image'] ?? null);
        if (!$upload['ok']) {
            $error = $upload['erreur'];
        } else {
            $nouvelleImage = $service['image'];
            if ($upload['fichier']) {
                $nouvelleImage = $upload['fichier'];
            } elseif ($supprimer_image) {
                $nouvelleImage = null;
            }

            $stmtUp = $pdo->prepare("UPDATE services SET titre = ?, description = ?, tarif = ?, rayon = ?, image = ? WHERE id = ? AND user_id = ?");
            if ($stmtUp->execute([$titre, $description, $tarif, $rayon, $nouvelleImage, $service_id, $reparateur_id])) {
                // Nettoyage de l'ancienne image si elle a été remplacée ou supprimée
                if ($nouvelleImage !== $service['image']) {
                    supprimer_image($service['image']);
                }
                header('Location: mes_services.php?modifie=1');
                exit;
            }
            $error = "Une erreur s'est produite lors de la mise à jour.";
        }
    }
}

require_once 'header.php';
?>
<div class="card" style="max-width: 560px; margin: 2rem auto;">
    <h1 style="font-size: 1.4rem; margin-bottom: 1rem;">Modifier la prestation</h1>

    <?php if ($error): ?><div class="msg-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?= (int) $service_id ?>">

        <div class="field">
            <label for="titre">Titre du service</label>
            <input type="text" id="titre" name="titre" value="<?= htmlspecialchars($service['titre']) ?>" required>
        </div>
        <div class="field">
            <label for="description">Description détaillée</label>
            <textarea id="description" name="description" required><?= htmlspecialchars($service['description']) ?></textarea>
        </div>
        <div class="field">
            <label for="tarif">Tarif (€)</label>
            <input type="number" step="0.01" min="0" id="tarif" name="tarif" value="<?= htmlspecialchars($service['tarif']) ?>" required>
        </div>
        <div class="field">
            <label for="rayon">Rayon de déplacement (km)</label>
            <input type="number" id="rayon" name="rayon" value="<?= (int) $service['rayon'] ?>" min="1" required>
        </div>

        <div class="field">
            <label>Image actuelle</label>
            <?php if (!empty($service['image'])): ?>
                <img class="thumb thumb-lg" src="uploads/<?= htmlspecialchars(basename($service['image'])) ?>" alt="Image actuelle">
                <label style="margin-top: 0.5rem; font-weight: normal;">
                    <input type="checkbox" name="supprimer_image" value="1"> Supprimer l'image actuelle
                </label>
            <?php else: ?>
                <p class="muted">Aucune image.</p>
            <?php endif; ?>
        </div>
        <div class="field">
            <label for="image">Remplacer la photo (optionnel, 2 Mo max)</label>
            <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/gif,image/webp">
        </div>

        <button type="submit" class="btn btn-block">Enregistrer les modifications</button>
    </form>
    <p style="margin-top: 1rem; text-align: center;">
        <a href="mes_services.php" class="muted">← Retour à mes services</a>
    </p>
</div>
<?php require_once 'footer.php'; ?>