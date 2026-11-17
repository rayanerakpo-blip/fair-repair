<?php
/**
 * ============================================================
 *  Ajout d'activité au compte repateur1@gmail.com
 *  (interventions terminées + réservations client)
 * ============================================================
 *
 *  Usage :
 *      php seed_rep1.php          → ajoute les données
 *      php seed_rep1.php --clean  → retire uniquement ce qui a été ajouté
 *
 *  Les identifiants créés sont consignés dans rep1_manifest.json
 *  afin que le nettoyage ne touche jamais au reste de la base.
 */

require_once __DIR__ . '/db.php';

const EMAIL_CIBLE = 'repateur1@gmail.com';
const MANIFESTE   = __DIR__ . '/rep1_manifest.json';

// ---------------------------------------------------------------
// Manifeste
// ---------------------------------------------------------------
function lire_manifeste(): array
{
    if (!is_file(MANIFESTE)) {
        return [];
    }
    $contenu = json_decode((string) file_get_contents(MANIFESTE), true);
    return is_array($contenu) ? $contenu : [];
}

function ecrire_manifeste(array $m): void
{
    file_put_contents(MANIFESTE, json_encode($m, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function liste_sql(array $ids): string
{
    return $ids ? implode(',', array_map('intval', $ids)) : '0';
}

function choisir(array $t): mixed
{
    return $t[array_rand($t)];
}

function choisir_pondere(array $propositions): mixed
{
    $total = array_sum(array_column($propositions, 1));
    $tirage = random_int(1, $total);
    foreach ($propositions as [$valeur, $poids]) {
        $tirage -= $poids;
        if ($tirage <= 0) {
            return $valeur;
        }
    }
    return $propositions[count($propositions) - 1][0];
}

// ---------------------------------------------------------------
// Nettoyage
// ---------------------------------------------------------------
function nettoyer(PDO $pdo): array
{
    $m = lire_manifeste();
    if (!$m) {
        return ['message' => 'Aucun manifeste rep1_manifest.json : rien à retirer.'];
    }

    $pdo->beginTransaction();
    $rapport = [];

    foreach (['signalements' => 'signalements', 'avis' => 'avis', 'paiements' => 'paiements'] as $cle => $table) {
        if (!empty($m[$cle])) {
            $rapport[$cle] = $pdo->exec("DELETE FROM `$table` WHERE id IN (" . liste_sql($m[$cle]) . ")");
        }
    }
    if (!empty($m['reservations'])) {
        $rapport['reservations'] = $pdo->exec("DELETE FROM reservations WHERE id IN (" . liste_sql($m['reservations']) . ")");
    }
    if (!empty($m['services'])) {
        $rapport['services'] = $pdo->exec("DELETE FROM services WHERE id IN (" . liste_sql($m['services']) . ")");
    }
    if (!empty($m['reparateur'])) {
        $stmt = $pdo->prepare("DELETE FROM reparateurs WHERE id_user = ?");
        $stmt->execute([(int) $m['reparateur']['id_user']]);
        $rapport['fiche_reparateur'] = $stmt->rowCount();
    }
    if (!empty($m['role_initial'])) {
        $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->execute([$m['role_initial'], (int) ($m['utilisateur'] ?? 0)]);
        $rapport['role_restaure'] = $m['role_initial'];
    }

    $pdo->commit();
    unlink(MANIFESTE);
    return $rapport;
}

// ---------------------------------------------------------------
// Création
// ---------------------------------------------------------------
function creer(PDO $pdo): array
{
    $utilisateur = $pdo->query("SELECT * FROM users WHERE email = " . $pdo->quote(EMAIL_CIBLE))->fetch();
    if (!$utilisateur) {
        throw new RuntimeException('Compte introuvable : ' . EMAIL_CIBLE);
    }
    $userId = (int) $utilisateur['id'];

    $manifeste = [
        'utilisateur'      => $userId,
        'role_initial'     => $utilisateur['role'],
        'services'         => [],
        'reservations'     => [],
        'paiements'        => [],
        'avis'             => [],
        'reparateur'       => null,
        'date_creation'    => date('c'),
    ];

    $commentaires = [
        'Travail rapide et soigné, vélo comme neuf. Je recommande vivement.',
        'Très bon contact, prix annoncé respecté. Merci !',
        'Réparation impeccable, le vélo roule parfaitement.',
        'Ponctuel et professionnel, de bons conseils en plus.',
        'Excellent rapport qualité/prix, je reviendrai.',
        'Intervention propre et efficace, rien à redire.',
        'Réglage parfait, je sens tout de suite la différence.',
        'Bon diagnostic, réparation solide. Merci beaucoup.',
        'Service sympathique et rapide, je recommande.',
        'Travail de qualité, le vélo est reparti pour des années.',
    ];

    $aujourdhui = new DateTimeImmutable('today');

    $pdo->beginTransaction();

    // 1) Passage en réparateur -------------------------------------
    $pdo->prepare("UPDATE users SET role = 'reparateur' WHERE id = ?")->execute([$userId]);

    $aFiche = $pdo->prepare("SELECT COUNT(*) FROM reparateurs WHERE id_user = ?");
    $aFiche->execute([$userId]);
    if (!(int) $aFiche->fetchColumn()) {
        $siret = str_pad((string) random_int(0, 99999999999999), 14, '0', STR_PAD_LEFT);
        $pdo->prepare("INSERT INTO reparateurs (id_user, siret, specialite) VALUES (?, ?, ?)")
            ->execute([$userId, $siret, 'Réparation et entretien de vélos']);
        $manifeste['reparateur'] = ['id_user' => $userId];
    }

    // 2) Nouveaux services -----------------------------------------
    $modeles = [
        ['Révision complète', 'Révision complète du vélo : freins, transmission, pressions et graissage. Compte rendu remis en fin d\'intervention.', 65, 15, 'demo_service_1.svg'],
        ['Réparation de crevaison', 'Réparation de chambre à air ou remplacement du pneu, à l\'atelier ou à domicile.', 22, 10, 'demo_service_2.svg'],
        ['Réglage des freins', 'Réglage des freins et remplacement des patins ou plaquettes si nécessaire.', 30, 12, 'demo_service_3.svg'],
    ];
    $stmtService = $pdo->prepare("INSERT INTO services (user_id, titre, description, tarif, rayon, image, created_at) VALUES (?,?,?,?,?,?,?)");
    foreach ($modeles as [$titre, $desc, $tarif, $rayon, $image]) {
        $cree = $aujourdhui->modify('-' . random_int(150, 200) . ' days')->format('Y-m-d H:i:s');
        $stmtService->execute([$userId, $titre, $desc, $tarif, $rayon, $image, $cree]);
        $manifeste['services'][] = (int) $pdo->lastInsertId();
    }

    // Tous les services du réparateur (existants + nouveaux)
    $stmt = $pdo->prepare("SELECT id, titre, tarif FROM services WHERE user_id = ?");
    $stmt->execute([$userId]);
    $mesServices = $stmt->fetchAll();

    // 3) Interventions terminées sur ses services -------------------
    $stmtClients = $pdo->query("SELECT id, adresse, code_postal, ville FROM users
                                WHERE email LIKE '%@demo.fairrepair.fr' AND role = 'client'");
    $clients = $stmtClients->fetchAll();

    $stmtRes = $pdo->prepare("INSERT INTO reservations
        (client_id, service_id, adresse, code_postal, ville, date_intervention, heure_intervention,
         type, statut, montant, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
    $stmtPay = $pdo->prepare("INSERT INTO paiements (reservation_id, montant, date_paiement, statut, mode_reglement) VALUES (?,?,?,?,?)");
    $stmtAvis = $pdo->prepare("INSERT INTO avis (reservation_id, note, commentaire, created_at) VALUES (?,?,?,?)");
    $modes = ['carte', 'especes', 'virement'];

    if (!$clients || !$mesServices) {
        throw new RuntimeException('Comptes clients de démo ou services manquants.');
    }

    for ($i = 0; $i < 12; $i++) {
        $client  = choisir($clients);
        $service = choisir($mesServices);
        $decalage = -random_int(3, 178);                       // réparti sur ~6 mois
        $date = $aujourdhui->modify($decalage . ' days');
        $heure = sprintf('%02d:%02d:00', random_int(8, 18), choisir([0, 10, 20, 30, 40, 50]));
        $cree = $date->modify('-' . random_int(2, 12) . ' days')->format('Y-m-d H:i:s');

        $stmtRes->execute([
            $client['id'], $service['id'], $client['adresse'], $client['code_postal'], $client['ville'],
            $date->format('Y-m-d'), $heure, choix_aleatoire(['planifiee', 'planifiee', 'immediate']),
            'terminee', $service['tarif'], $cree,
        ]);
        $reservationId = (int) $pdo->lastInsertId();
        $manifeste['reservations'][] = $reservationId;

        $fin = $date->format('Y-m-d') . ' ' . sprintf('%02d:%02d:00', random_int(9, 19), random_int(0, 59));
        $stmtPay->execute([$reservationId, $service['tarif'], $fin, 'paye', choisir($modes)]);
        $manifeste['paiements'][] = (int) $pdo->lastInsertId();

        if (random_int(1, 100) <= 65) {
            $note = choisir_pondere([[5, 45], [4, 35], [3, 15], [2, 5]]);
            $creeAvis = $date->modify('+1 day')->format('Y-m-d H:i:s');
            if (strtotime($creeAvis) > time()) {
                $creeAvis = date('Y-m-d H:i:s');
            }
            $stmtAvis->execute([$reservationId, $note, choisir($commentaires), $creeAvis]);
            $manifeste['avis'][] = (int) $pdo->lastInsertId();
        }
    }

    // 4) Réservations terminées en tant que client ------------------
    $stmtCatalogue = $pdo->query("SELECT s.id, s.tarif FROM services s JOIN users u ON u.id = s.user_id
                                  WHERE s.user_id <> $userId AND u.email LIKE '%@demo.fairrepair.fr'");
    $catalogue = $stmtCatalogue->fetchAll();

    for ($i = 0; $i < 5 && $catalogue; $i++) {
        $service = choisir($catalogue);
        $decalage = -random_int(5, 170);
        $date = $aujourdhui->modify($decalage . ' days');
        $heure = sprintf('%02d:%02d:00', random_int(8, 18), choisir([0, 15, 30, 45]));
        $cree = $date->modify('-' . random_int(2, 10) . ' days')->format('Y-m-d H:i:s');

        $stmtRes->execute([
            $userId, $service['id'], $utilisateur['adresse'], $utilisateur['code_postal'], $utilisateur['ville'],
            $date->format('Y-m-d'), $heure, 'planifiee', 'terminee', $service['tarif'], $cree,
        ]);
        $reservationId = (int) $pdo->lastInsertId();
        $manifeste['reservations'][] = $reservationId;

        $fin = $date->format('Y-m-d') . ' ' . sprintf('%02d:%02d:00', random_int(9, 19), random_int(0, 59));
        $stmtPay->execute([$reservationId, $service['tarif'], $fin, 'paye', choisir($modes)]);
        $manifeste['paiements'][] = (int) $pdo->lastInsertId();

        if (random_int(1, 100) <= 50) {
            $stmtAvis->execute([$reservationId, choisir_pondere([[5, 40], [4, 40], [3, 20]]), choisir($commentaires), date('Y-m-d H:i:s', strtotime($fin))]);
            $manifeste['avis'][] = (int) $pdo->lastInsertId();
        }
    }

    $pdo->commit();
    ecrire_manifeste($manifeste);

    return [
        'role'          => 'reparateur',
        'services'      => count($manifeste['services']),
        'interventions' => 12,
        'client'        => 5,
        'reservations'  => count($manifeste['reservations']),
        'paiements'     => count($manifeste['paiements']),
        'avis'          => count($manifeste['avis']),
    ];
}

function choix_aleatoire(array $t): string
{
    return $t[array_rand($t)];
}

// ---------------------------------------------------------------
// Exécution
// ---------------------------------------------------------------
$action = PHP_SAPI === 'cli' ? ($argv[1] ?? 'seed') : ($_GET['action'] ?? 'seed');
header('Content-Type: text/plain; charset=utf-8');

try {
    if ($action === '--clean' || $action === 'clean') {
        echo "Nettoyage des données ajoutées à " . EMAIL_CIBLE . "..." . PHP_EOL;
        $rapport = nettoyer($pdo);
        foreach ($rapport as $cle => $valeur) {
            echo sprintf("  %-18s : %s%s", $cle, is_scalar($valeur) ? $valeur : json_encode($valeur), PHP_EOL);
        }
    } else {
        echo "Ajout d'activité au compte " . EMAIL_CIBLE . "..." . PHP_EOL;
        $stats = creer($pdo);
        foreach ($stats as $cle => $valeur) {
            echo sprintf("  %-15s : %s%s", $cle, $valeur, PHP_EOL);
        }
        echo PHP_EOL . "Manifeste : rep1_manifest.json (permet le nettoyage ciblé)" . PHP_EOL;
    }
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, 'ERREUR : ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
