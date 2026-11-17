<?php
/**
 * Helpers communs — Fair'repair
 * Fonctions réutilisables : upload d'image, suppression d'image, rendu d'étoiles.
 */

/**
 * Traite le téléversement d'une image de service.
 * @param array|null $fichier $_FILES['image'] ou null
 * @return array{ok:bool, fichier:?string, erreur:string} nom du fichier enregistré (sans le dossier)
 */
function traiter_image_upload(?array $fichier): array
{
    if (empty($fichier) || ($fichier['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['ok' => true, 'fichier' => null, 'erreur' => ''];
    }

    if ((int)$fichier['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'fichier' => null, 'erreur' => 'Erreur lors du téléversement du fichier.'];
    }

    $tailleMax = 2 * 1024 * 1024; // 2 Mo
    if ((int)$fichier['size'] > $tailleMax) {
        return ['ok' => false, 'fichier' => null, 'erreur' => 'Image trop lourde (2 Mo maximum).'];
    }

    $info = @getimagesize($fichier['tmp_name']);
    if ($info === false) {
        return ['ok' => false, 'fichier' => null, 'erreur' => 'Le fichier doit être une image valide.'];
    }

    $extensions = [
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG  => 'png',
        IMAGETYPE_GIF  => 'gif',
        IMAGETYPE_WEBP => 'webp',
    ];
    $ext = $extensions[$info[2]] ?? null;
    if (!$ext) {
        return ['ok' => false, 'fichier' => null, 'erreur' => 'Format d\'image non supporté (JPG, PNG, GIF, WEBP).'];
    }

    $dossier = __DIR__ . '/uploads';
    if (!is_dir($dossier)) {
        @mkdir($dossier, 0775, true);
    }

    $nom = 'service_' . bin2hex(random_bytes(8)) . '.' . $ext;
    if (!move_uploaded_file($fichier['tmp_name'], $dossier . '/' . $nom)) {
        return ['ok' => false, 'fichier' => null, 'erreur' => 'Impossible d\'enregistrer l\'image sur le serveur.'];
    }

    return ['ok' => true, 'fichier' => $nom, 'erreur' => ''];
}

/**
 * Supprime une image du dossier uploads (ne fait rien si le nom est vide).
 */
function supprimer_image(?string $nom): void
{
    if (empty($nom)) return;
    $chemin = __DIR__ . '/uploads/' . basename($nom);
    if (is_file($chemin)) {
        @unlink($chemin);
    }
}

/**
 * Construit une URL absolue vers une page de l'application.
 * Le dossier courant est détecté automatiquement (évite les liens cassés
 * dans les emails quand le site n'est pas racine de domaine).
 * Ex. : url_absolue('login.php') → http://localhost/www/Fair_repair/login.php
 */
function url_absolue(string $page = ''): string
{
    $https  = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $hote   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $dossier = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    return ($https ? 'https' : 'http') . '://' . $hote . $dossier . '/' . ltrim($page, '/');
}

/**
 * Rendu HTML d'une note en étoiles (1-5).
 */
function etoiles(?float $note): string
{
    $note = (float)$note;
    $html = '';
    for ($i = 1; $i <= 5; $i++) {
        $html .= $i <= round($note) ? '★' : '<span class="stars-empty">★</span>';
    }
    return $html;
}

/**
 * Politique de mot de passe fort (recommandations ANSSI / bonnes pratiques RGPD).
 * @return string message d'erreur détaillé, ou chaîne vide si le mot de passe est valide.
 */
function valider_mot_de_passe(string $mdp): string
{
    if (mb_strlen($mdp) < 12) {
        return 'Le mot de passe doit contenir au moins 12 caractères.';
    }
    if (!preg_match('/[a-z]/', $mdp)) {
        return 'Le mot de passe doit contenir au moins une lettre minuscule.';
    }
    if (!preg_match('/[A-Z]/', $mdp)) {
        return 'Le mot de passe doit contenir au moins une lettre majuscule.';
    }
    if (!preg_match('/[0-9]/', $mdp)) {
        return 'Le mot de passe doit contenir au moins un chiffre.';
    }
    if (!preg_match('/[^A-Za-z0-9]/', $mdp)) {
        return 'Le mot de passe doit contenir au moins un caractère spécial (ex. ! @ # ?).';
    }
    return '';
}

/**
 * Message d'aide affiché sous les champs de mot de passe.
 */
function aide_mot_de_passe(): string
{
    return '12 caractères minimum, avec au moins une majuscule, une minuscule, un chiffre et un caractère spécial.';
}

/**
 * Motif HTML (attribut pattern) pour la validation côté navigateur.
 */
function pattern_mot_de_passe(): string
{
    return '(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[^A-Za-z0-9]).{12,}';
}

/**
 * Liste des services (optionnellement filtrée par réparateur et/ou recherche),
 * avec note moyenne, nombre d'avis et les 3 avis les plus récents attachés à chaque service.
 */
function get_services_liste(PDO $pdo, int $reparateur_id = 0, string $search = ''): array
{
    $sql = "SELECT s.*, u.prenom, u.nom, u.ville,
                   ROUND(AVG(a.note), 1) AS note_moyenne,
                   COUNT(a.id) AS nb_avis
            FROM services s
            JOIN users u ON s.user_id = u.id
            LEFT JOIN reservations r ON r.service_id = s.id AND r.statut = 'terminee'
            LEFT JOIN avis a ON a.reservation_id = r.id
            WHERE 1=1";

    $params = [];
    if ($reparateur_id > 0) {
        $sql .= " AND s.user_id = ?";
        $params[] = $reparateur_id;
    }

    $search = trim($search);
    if ($search !== '') {
        $sql .= " AND (s.titre LIKE ? OR s.description LIKE ? OR u.ville LIKE ?)";
        $terme = '%' . $search . '%';
        $params = array_merge($params, [$terme, $terme, $terme]);
    }

    $sql .= " GROUP BY s.id, u.id ORDER BY s.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $services = $stmt->fetchAll();

    foreach ($services as $i => $svc) {
        $stmtAvis = $pdo->prepare("
            SELECT a.id, a.note, a.commentaire, r.client_id, DATE(a.created_at) AS date_avis
            FROM avis a
            JOIN reservations r ON a.reservation_id = r.id
            WHERE r.service_id = ?
            ORDER BY a.created_at DESC
            LIMIT 3
        ");
        $stmtAvis->execute([(int)$svc['id']]);
        $services[$i]['avis'] = $stmtAvis->fetchAll();
    }

    return $services;
}