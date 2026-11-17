<?php
/**
 * ============================================================
 *  Générateur de données de démonstration — Fair'repair
 *  Outil de développement (à supprimer en production)
 * ============================================================
 *
 *  Usage en ligne de commande :
 *      php seed_demo.php              → génère les données
 *      php seed_demo.php --clean      → supprime UNIQUEMENT la démo
 *
 *  Comptes créés : prenom.nom@demo.fairrepair.fr
 *  Mot de passe commun de tous les comptes de démo : Demo@Fair2026
 *  Administrateur de démo : admin@demo.fairrepair.fr
 */

require_once __DIR__ . '/db.php';

const DOMAINE_DEMO = 'demo.fairrepair.fr';
const MDP_DEMO     = 'Demo@Fair2026';

// ---------------------------------------------------------------
// Petits utilitaires
// ---------------------------------------------------------------
function choisir(array $tableau)
{
    return $tableau[array_rand($tableau)];
}

/** Tirage pondéré : [['valeur', poids], ...] */
function choisir_pondere(array $propositions)
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

/** @return string[] les ids (chaînes) correspondant aux données de démo */
function ids_demo(PDO $pdo, string $sql): array
{
    return $pdo->query($sql)->fetchAll(PDO::FETCH_COLUMN);
}

// ---------------------------------------------------------------
// Nettoyage : ne touche qu'aux données portant le domaine de démo
// ---------------------------------------------------------------
function nettoyer(PDO $pdo): array
{
    $domaine = '%' . DOMAINE_DEMO;
    $compteAvant = [
        'users'       => (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
        'reservations'=> (int) $pdo->query("SELECT COUNT(*) FROM reservations")->fetchColumn(),
        'avis'        => (int) $pdo->query("SELECT COUNT(*) FROM avis")->fetchColumn(),
    ];

    $idsUsers      = ids_demo($pdo, "SELECT id FROM users WHERE email LIKE '$domaine'");
    $idsServices   = ids_demo($pdo, "SELECT s.id FROM services s JOIN users u ON u.id = s.user_id WHERE u.email LIKE '$domaine'");
    $idsReserv     = [];
    if ($idsUsers || $idsServices) {
        $conditions = [];
        if ($idsUsers) {
            $conditions[] = 'client_id IN (' . implode(',', array_map('intval', $idsUsers)) . ')';
        }
        if ($idsServices) {
            $conditions[] = 'service_id IN (' . implode(',', array_map('intval', $idsServices)) . ')';
        }
        $idsReserv = ids_demo($pdo, 'SELECT id FROM reservations WHERE ' . implode(' OR ', $conditions));
    }
    $idsReservSql = $idsReserv ? implode(',', array_map('intval', $idsReserv)) : '0';

    // Ordre respectueux des clés étrangères
    $pdo->exec("DELETE FROM signalements WHERE signalant_id IN (" . ($idsUsers ? implode(',', array_map('intval', $idsUsers)) : '0') . ")");
    $pdo->exec("DELETE FROM signalements WHERE avis_id IN (SELECT id FROM avis WHERE reservation_id IN ($idsReservSql))");
    $pdo->exec("DELETE FROM signalements WHERE service_id IN (" . ($idsServices ? implode(',', array_map('intval', $idsServices)) : '0') . ")");
    $pdo->exec("DELETE FROM avis WHERE reservation_id IN ($idsReservSql)");
    $pdo->exec("DELETE FROM login_history WHERE email LIKE '$domaine'");
    $pdo->exec("DELETE FROM tentatives_connexion WHERE email LIKE '$domaine'");
    $pdo->exec("DELETE FROM reponses_secretes WHERE id_users IN (" . ($idsUsers ? implode(',', array_map('intval', $idsUsers)) : '0') . ")");
    $pdo->exec("DELETE FROM service_liaisons WHERE service_parent_id IN (" . ($idsServices ? implode(',', array_map('intval', $idsServices)) : '0') . ")");
    $pdo->exec("DELETE FROM paiements WHERE reservation_id IN ($idsReservSql)");
    $pdo->exec("DELETE FROM reservations WHERE id IN ($idsReservSql)");
    $pdo->exec("DELETE FROM services WHERE id IN (" . ($idsServices ? implode(',', array_map('intval', $idsServices)) : '0') . ")");
    $pdo->exec("DELETE FROM reparateurs WHERE id_user IN (" . ($idsUsers ? implode(',', array_map('intval', $idsUsers)) : '0') . ")");
    $pdo->exec("DELETE FROM users WHERE id IN (" . ($idsUsers ? implode(',', array_map('intval', $idsUsers)) : '0') . ")");
    $pdo->exec("DELETE FROM admins WHERE email LIKE '$domaine'");

    // Vignettes générées par le script
    foreach (glob(__DIR__ . '/uploads/demo_service_*.svg') ?: [] as $fichier) {
        @unlink($fichier);
    }

    return [
        'users'        => $compteAvant['users'] - (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
        'reservations' => $compteAvant['reservations'] - (int) $pdo->query("SELECT COUNT(*) FROM reservations")->fetchColumn(),
        'avis'         => $compteAvant['avis'] - (int) $pdo->query("SELECT COUNT(*) FROM avis")->fetchColumn(),
    ];
}

// ---------------------------------------------------------------
// Vignettes d'illustration (SVG) pour les services de démo
// ---------------------------------------------------------------
function creer_vignettes(PDO $pdo): array
{
    $palettes = [
        ['#16283a', '#ff6b35', 'Révision'],
        ['#1f3b54', '#ffd23f', 'Freins'],
        ['#243244', '#2f9fd0', 'Transmission'],
        ['#16283a', '#2f9fd0', 'Vélo de ville'],
        ['#1f3b54', '#ff6b35', 'VAE'],
        ['#243244', '#ffd23f', 'VTT'],
    ];

    $fichiers = [];
    foreach ($palettes as $i => [$fond, $accent, $libelle]) {
        $nom = 'demo_service_' . ($i + 1) . '.svg';
        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 400" width="800" height="400">
  <rect width="800" height="400" fill="$fond"/>
  <g fill="none" stroke="$accent" stroke-width="14" stroke-linecap="round" stroke-linejoin="round" opacity="0.95">
    <circle cx="250" cy="255" r="85"/>
    <circle cx="550" cy="255" r="85"/>
    <path d="M250 255 L330 130 L470 130 L550 255"/>
    <path d="M330 130 L400 255"/>
    <path d="M300 130 h70"/>
  </g>
  <circle cx="550" cy="110" r="26" fill="#ffffff" opacity="0.9"/>
  <text x="400" y="70" text-anchor="middle" font-family="sans-serif" font-size="34" font-weight="bold" fill="#ffffff">$libelle</text>
</svg>
SVG;
        $fichiers[] = $nom;
        file_put_contents(__DIR__ . '/uploads/' . $nom, $svg);
    }
    return $fichiers;
}

// ---------------------------------------------------------------
// Génération
// ---------------------------------------------------------------
function generer(PDO $pdo): array
{
    $mdpHash = password_hash(MDP_DEMO, PASSWORD_BCRYPT);
    $aujourdhui = new DateTimeImmutable('today');

    // --- Référentiels -------------------------------------------------
    $prenoms  = ['Camille','Louis','Léa','Hugo','Chloé','Nathan','Manon','Lucas','Jade','Tom','Emma','Noah','Inès','Adam','Lou','Ethan','Zoé','Sacha','Mila','Léo','Anna','Rayan','Luna','Noé','Ambre','Maël','Yasmine','Théo','Sarah','Malik','Clara','Enzo','Julia','Mehdi','Alice','Valentin','Nora','Baptiste','Émilie','Ilan'];
    $noms     = ['Martin','Bernard','Dubois','Thomas','Robert','Richard','Petit','Durand','Leroy','Moreau','Simon','Laurent','Lefebvre','Michel','Garcia','David','Bertrand','Roux','Vincent','Fournier','Morel','Girard','Andre','Mercier','Blanc','Guerin','Boyer','Chevalier','Da Silva','Lemoine','Perrin','Clement','Morin','Nguyen','Henry','Roussel','Marchand','Duval','Lambert','Bonnet'];
    $villes   = [
        ['Paris', '75001', 48.8566, 2.3522],
        ['Lyon', '69001', 45.7640, 4.8357],
        ['Marseille', '13001', 43.2965, 5.3698],
        ['Toulouse', '31000', 43.6047, 1.4442],
        ['Nice', '06000', 43.7102, 7.2620],
        ['Nantes', '44000', 47.2184, -1.5536],
        ['Montpellier', '34000', 43.6108, 3.8767],
        ['Strasbourg', '67000', 48.5734, 7.7521],
        ['Bordeaux', '33000', 44.8378, -0.5792],
        ['Lille', '59000', 50.6292, 3.0573],
        ['Rennes', '35000', 48.1173, -1.6778],
        ['Reims', '51100', 49.2583, 4.0317],
        ['Tours', '37000', 47.3941, 0.6848],
        ['Grenoble', '38000', 45.1885, 5.7245],
        ['Dijon', '21000', 47.3220, 5.0415],
        ['Angers', '49000', 47.4784, -0.5632],
        ['Nîmes', '30000', 43.8367, 4.3601],
        ['Clermont-Ferrand', '63000', 45.7772, 3.0870],
        ['Brest', '29200', 48.3904, -4.4861],
        ['Ajaccio', '20000', 41.9192, 8.7386],
    ];
    $rues      = ['rue de la République', 'avenue Jean Jaurès', 'rue des Écoles', 'boulevard Victor Hugo', 'rue Pasteur', 'allée des Tilleuls', 'rue Amédée Girod', 'chemin du Moulin', 'place du Marché', 'rue des Lilas', 'impasse des Peupliers', 'rue François Mitterrand'];
    $specialites = ['Vélos de ville et VAE', 'VTT et vélos de route', 'Réparations à domicile', 'Révision et entretien courant', 'Vélos électriques', 'Vélos enfants et familiaux', 'Montage et réglages', 'Vélos de course'];

    $servicesModeles = [
        ['Révision complète', 'Révision complète du vélo : freins, transmission, pressions et graissage. Compte rendu détaillé remis en fin d\'intervention.', 60, 90],
        ['Réparation de crevaison', 'Réparation de chambre à air ou remplacement du pneu, intervention rapide sur place ou à l\'atelier.', 15, 30],
        ['Réglage des freins', 'Réglage et remplacement des plaquettes ou patins de freins si nécessaire.', 15, 35],
        ['Changement de transmission', 'Remplacement de la chaîne, du cassette et des plateaux usés, réglage des vitesses.', 45, 95],
        ['Réglage du dérailleur', 'Réglage précis du dérailleur avant/arrière, contrôle de la câroterie.', 15, 30],
        ['Montage de vélo neuf', 'Montage et contrôle d\'un vélo livré en carton : serrages, hauteur de selle, essai routier.', 35, 60],
        ['Réparation VAE', 'Diagnostic de la motorisation, contrôle de la batterie et de l\'écran, réparation mécanique associée.', 50, 110],
        ['Entretien vélo de ville', 'Entretien complet : freins, éclairage, garde-boue, sonnette, pression des pneus.', 30, 55],
        ['Changement de pneus', 'Dépose et repose de pneus, contrôle de la jante et de la roue.', 20, 45],
        ['Révision freins à disque', 'Purge du circuit hydraulique, remplacement des plaquettes et contrôle du disque.', 40, 75],
        ['Pose de accessoires', 'Pose de panier, de soutache, de garde-boue, de compteur ou de béquille.', 15, 40],
        ['Contrôle avant route', 'Bilan complet de 20 points de contrôle avant une longue sortie ou un voyage.', 25, 45],
        ['Réparation de cadre', 'Réparation d\'un cadre ou d\'un fourche (soudure aluminium/acier selon cas) et remise en peinture partielle.', 70, 160],
        ['Entretien vélo enfant', 'Révision du vélo de votre enfant : freins, roues, hauteur de selle et sécurité.', 20, 40],
        ['Réparation éclairage', 'Remplacement de l\'éclairage dynamo ou à batterie, contrôle de l\'installation électrique.', 15, 35],
    ];

    $commentaires = [
        'Intervention rapide et professionnelle, mon vélo est comme neuf !',
        'Très satisfait, réparateur ponctuel et pédagogue. Je recommande.',
        'Très bon contact, prix respecté, travail de qualité.',
        'Réparation faite dans les temps, le vélo roule à nouveau parfaitement.',
        'Service agréable et efficace, conseils précieux pour l\'entretien.',
        'Très bon rapport qualité/prix, je repasserai par lui.',
        'Arrivé à l\'heure, explique bien ce qu\'il fait. Rien à redire.',
        'Un vrai professionnel, réparation solide et durable.',
        'Bon service, un peu de retard mais prévenu à l\'avance.',
        'Excellent travail sur ma transmission, merci !',
        'Accueil sympathique, vélo réparé en une journée.',
        'Réglage des freins parfait, maintenant le vélo s\'arrête net.',
        'Très bon service à domicile, pratique quand on n\'a pas de véhicule.',
        'Bon diagnostic sur mon VAE, problème de batterie identifié vite.',
        'Rapide, propre et honnête sur le devis.',
    ];

    $motifs  = ['contenu_inapproprie', 'harcelement', 'arnaque', 'faux_avis', 'autre'];
    $modes   = ['carte', 'especes', 'virement'];

    $vignettes = creer_vignettes($pdo);

    $stats = ['users' => 0, 'reparateurs' => 0, 'services' => 0, 'reservations' => 0,
              'paiements' => 0, 'avis' => 0, 'signalements' => 0, 'login_history' => 0,
              'tentatives' => 0, 'liaisons' => 0, 'reponses' => 0, 'admins' => 0];

    // --- Requêtes préparées ------------------------------------------
    $stmtUser = $pdo->prepare("INSERT INTO users (prenom, nom, adresse, code_postal, ville, email, telephone, mot_de_passe, role, latitude, longitude, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
    $stmtRep  = $pdo->prepare("INSERT INTO reparateurs (id_user, siret, specialite) VALUES (?,?,?)");
    $stmtServ = $pdo->prepare("INSERT INTO services (user_id, titre, description, tarif, rayon, image, created_at) VALUES (?,?,?,?,?,?,?)");
    $stmtRes  = $pdo->prepare("INSERT INTO reservations (client_id, service_id, adresse, code_postal, ville, date_intervention, heure_intervention, type, statut, montant, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
    $stmtPay  = $pdo->prepare("INSERT INTO paiements (reservation_id, montant, date_paiement, statut, mode_reglement) VALUES (?,?,?,?,?)");
    $stmtAvis = $pdo->prepare("INSERT INTO avis (reservation_id, note, commentaire, created_at) VALUES (?,?,?,?)");
    $stmtSig  = $pdo->prepare("INSERT INTO signalements (signalant_id, type, avis_id, service_id, motif, description, statut, created_at) VALUES (?,?,?,?,?,?,?,?)");
    $stmtHist = $pdo->prepare("INSERT INTO login_history (user_id, email, role, ip_address, login_time) VALUES (?,?,?,?,?)");
    $stmtTent = $pdo->prepare("INSERT INTO tentatives_connexion (email, adresse_ip, nb_tentatives, meme_mdp, dernier_mdp_hash, verrouille_jusqua) VALUES (?,?,?,?,?,?)");
    $stmtRepQ = $pdo->prepare("INSERT INTO reponses_secretes (id_questions, id_users, reponses) VALUES (?,?,?)");
    $stmtLia  = $pdo->prepare("INSERT INTO service_liaisons (service_parent_id, service_enfant_id) VALUES (?,?)");
    $stmtAdm  = $pdo->prepare("INSERT INTO admins (prenom, nom, email, mot_de_passe) VALUES (?,?,?,?)");

    $pdo->beginTransaction();

    // --- Administrateur de démo --------------------------------------
    $stmtAdm->execute(['Admin', 'Démo', 'admin@' . DOMAINE_DEMO, $mdpHash]);
    $stats['admins']++;

    // --- Utilisateurs -------------------------------------------------
    $clients = [];
    $reparateurs = [];

    for ($i = 0; $i < 55; $i++) {
        $estReparateur = $i < 15;
        $prenom = choisir($prenoms);
        $nom    = choisir($noms);
        $villeData = choisir($villes);
        [$ville, $cp, $lat, $lng] = $villeData;

        $prenomSlug = mb_strtolower(preg_replace('/[^a-zA-Z]/', '', $prenom));
        $nomSlug    = mb_strtolower(preg_replace('/[^a-zA-Z]/', '', $nom));
        $email      = $prenomSlug . '.' . $nomSlug . '.' . $i . '@' . DOMAINE_DEMO;

        $adresse  = random_int(1, 180) . ' ' . choisir($rues);
        $telephone = '0' . random_int(6, 7) . random_int(10000000, 99999999);
        $role = $estReparateur ? 'reparateur' : 'client';

        // Éparpillement autour du centre de la ville pour la carte
        $lat2 = round($lat + (random_int(-400, 400) / 10000), 7);
        $lng2 = round($lng + (random_int(-400, 400) / 10000), 7);

        $cree = $aujourdhui->modify('-' . random_int(30, 300) . ' days')->format('Y-m-d H:i:s');
        $stmtUser->execute([$prenom, $nom, $adresse, $cp, $ville, $email, $telephone, $mdpHash, $role, $lat2, $lng2, $cree]);
        $userId = (int) $pdo->lastInsertId();
        $stats['users']++;

        $utilisateur = [
            'id' => $userId, 'prenom' => $prenom, 'nom' => $nom, 'email' => $email,
            'adresse' => $adresse, 'code_postal' => $cp, 'ville' => $ville, 'role' => $role,
        ];

        if ($estReparateur) {
            $siret = str_pad((string) random_int(0, 99999999999999), 14, '0', STR_PAD_LEFT);
            $stmtRep->execute([$userId, $siret, choisir($specialites)]);
            $reparateurs[] = $utilisateur;
            $stats['reparateurs']++;
        } else {
            $clients[] = $utilisateur;
        }
    }

    // --- Services (1 à 4 par réparateur) -----------------------------
    $services = [];
    foreach ($reparateurs as $rep) {
        $nbServices = random_int(1, 4);
        $choisis = [];
        while (count($choisis) < $nbServices) {
            $modele = choisir($servicesModeles);
            if (!in_array($modele[0], $choisis, true)) {
                $choisis[] = $modele[0];
            }
        }
        foreach ($choisis as $titre) {
            $modele = null;
            foreach ($servicesModeles as $m) {
                if ($m[0] === $titre) { $modele = $m; break; }
            }
            $tarif   = random_int($modele[2] * 100, $modele[3] * 100) / 100;
            $image   = random_int(0, 100) < 55 ? choisir($vignettes) : null;
            $cree    = $aujourdhui->modify('-' . random_int(1, 160) . ' days')->format('Y-m-d H:i:s');

            $stmtServ->execute([$rep['id'], $titre, $modele[1], $tarif, random_int(5, 25), $image, $cree]);
            $serviceId = (int) $pdo->lastInsertId();
            $services[] = ['id' => $serviceId, 'tarif' => $tarif, 'reparateur' => $rep];
            $stats['services']++;
        }
    }

    // --- Liaisons entre services d'un même réparateur ----------------
    $parReparateur = [];
    foreach ($services as $srv) {
        $parReparateur[$srv['reparateur']['id']][] = $srv['id'];
    }
    foreach ($parReparateur as $liste) {
        if (count($liste) >= 2) {
            $stmtLia->execute([$liste[0], $liste[1]]);
            $stats['liaisons']++;
        }
    }

    // --- Réservations sur 7 mois (dont le mois en cours) -------------
    $reservations = [];
    foreach ($clients as $client) {
        $nbReservations = random_int(3, 9);
        for ($r = 0; $r < $nbReservations; $r++) {
            $service = choisir($services);
            $futur   = random_int(1, 100) <= 22;              // 22 % à venir
            $decalage = $futur ? random_int(1, 30) : -random_int(1, 195);
            $date = $aujourdhui->modify(($decalage > 0 ? '+' : '') . $decalage . ' days');

            if ($futur) {
                $statut = choisir_pondere([['en_attente', 55], ['confirmee', 40], ['annulee', 5]]);
            } else {
                $statut = choisir_pondere([['terminee', 55], ['annulee', 18], ['confirmee', 15], ['en_attente', 12]]);
            }

            $heure = sprintf('%02d:%02d:00', random_int(8, 18), random_int(0, 1) ? random_int(0, 5) * 10 : random_int(0, 5) * 10);
            $cree  = $date->modify('-' . random_int(2, 20) . ' days')->format('Y-m-d H:i:s');
            if (strtotime($cree) > time()) {
                $cree = $aujourdhui->format('Y-m-d H:i:s');
            }

            $stmtRes->execute([
                $client['id'], $service['id'], $client['adresse'], $client['code_postal'], $client['ville'],
                $date->format('Y-m-d'), $heure, random_int(0, 100) < 85 ? 'planifiee' : 'immediate',
                $statut, $service['tarif'], $cree,
            ]);
            $reservationId = (int) $pdo->lastInsertId();
            $stats['reservations']++;

            // --- Paiement ------------------------------------------------
            if ($statut === 'terminee') {
                $statutPaiement = choisir_pondere([['paye', 85], ['rembourse', 10], ['en_attente', 5]]);
            } elseif ($statut === 'annulee') {
                $statutPaiement = choisir_pondere([['rembourse', 55], ['en_attente', 45]]);
            } else {
                $statutPaiement = choisir_pondere([['en_attente', 78], ['paye', 22]]);
            }
            $datePaiement = ($statutPaiement === 'en_attente')
                ? null
                : $date->format('Y-m-d') . ' ' . sprintf('%02d:%02d:00', random_int(9, 19), random_int(0, 59));
            $stmtPay->execute([$reservationId, $service['tarif'], $datePaiement, $statutPaiement,
                ($statutPaiement === 'en_attente') ? null : choisir($modes)]);
            $stats['paiements']++;

            $reservations[] = ['id' => $reservationId, 'statut' => $statut, 'date' => $date, 'client' => $client];
        }
    }

    // --- Avis sur les réservations terminées -------------------------
    $idsAvis = [];
    foreach ($reservations as $res) {
        if ($res['statut'] !== 'terminee' || random_int(1, 100) > 75) {
            continue;
        }
        $note = choisir_pondere([[5, 40], [4, 32], [3, 16], [2, 7], [1, 5]]);
        $dateAvis = $res['date']->modify('+1 day');
        $cree = $dateAvis->format('Y-m-d H:i:s');
        if (strtotime($cree) > time()) {
            $cree = $aujourdhui->format('Y-m-d H:i:s');
        }
        $stmtAvis->execute([$res['id'], $note, choisir($commentaires), $cree]);
        $avisId = (int) $pdo->lastInsertId();
        $idsAvis[] = ['id' => $avisId, 'client' => $res['client']];
        $stats['avis']++;
    }

    // --- Signalements (avis ou services) -----------------------------
    for ($i = 0; $i < 14 && $clients; $i++) {
        $signalement = choisir($clients);
        $dateSig = $aujourdhui->modify('-' . random_int(0, 45) . ' days')->format('Y-m-d H:i:s');
        $statutSig = choisir_pondere([['en_attente', 45], ['traite', 35], ['refuse', 20]]);

        if (!empty($idsAvis) && random_int(0, 1) === 0) {
            $avis = choisir($idsAvis);
            if ($avis['client']['id'] === $signalement['id']) {
                continue;
            }
            $stmtSig->execute([$signalement['id'], 'avis', $avis['id'], null, choisir($motifs),
                'Contenu signalé lors de la simulation de données.', $statutSig, $dateSig]);
        } elseif (!empty($services)) {
            $service = choisir($services);
            $stmtSig->execute([$signalement['id'], 'service', null, $service['id'], choisir($motifs),
                'Service signalé lors de la simulation de données.', $statutSig, $dateSig]);
        }
        $stats['signalements']++;
    }

    // --- Historique de connexions ------------------------------------
    foreach (array_merge($clients, $reparateurs) as $user) {
        $nbConnexions = random_int(1, 5);
        for ($c = 0; $c < $nbConnexions; $c++) {
            $quand = $aujourdhui->modify('-' . random_int(0, 40) . ' days')
                ->modify('+' . random_int(0, 23) . ' hours')
                ->modify('+' . random_int(0, 59) . ' minutes');
            $stmtHist->execute([$user['id'], $user['email'], $user['role'],
                random_int(0, 1) ? '127.0.0.1' : '::1', $quand->format('Y-m-d H:i:s')]);
            $stats['login_history']++;
        }
    }

    // --- Tentatives de connexion (dont comptes verrouillés) ----------
    foreach (array_slice($clients, 0, 8) as $i => $user) {
        if ($i < 3) {
            $verrou = date('Y-m-d H:i:s', time() + random_int(5, 14) * 60);
            $stmtTent->execute([$user['email'], '127.0.0.1', random_int(5, 9), random_int(0, 3),
                password_hash(MDP_DEMO, PASSWORD_BCRYPT), $verrou]);
        } else {
            $stmtTent->execute([$user['email'], '127.0.0.1', random_int(1, 4), 0,
                password_hash(MDP_DEMO, PASSWORD_BCRYPT), null]);
        }
        $stats['tentatives']++;
    }

    // --- Questions secrètes ------------------------------------------
    $nbReponses = 0;
    foreach (array_slice($clients, 0, 25) as $user) {
        $stmtRepQ->execute([random_int(1, 3), $user['id'], password_hash('Réponse' . random_int(100, 999), PASSWORD_BCRYPT)]);
        $nbReponses++;
    }
    $stats['reponses'] = $nbReponses;

    $pdo->commit();
    return $stats;
}

// ---------------------------------------------------------------
// Exécution
// ---------------------------------------------------------------
$action = PHP_SAPI === 'cli'
    ? ($argv[1] ?? 'seed')
    : ($_GET['action'] ?? 'seed');

header('Content-Type: text/plain; charset=utf-8');

try {
    if ($action === '--clean' || $action === 'clean') {
        echo "Suppression des données de démonstration...\n";
        $avant = nettoyer($pdo);
        echo "Terminé. Supprimé : {$avant['users']} utilisateur(s), {$avant['reservations']} réservation(s), {$avant['avis']} avis.\n";
        foreach (['users', 'reservations', 'avis', 'services', 'paiements'] as $table) {
            $restant = (int) $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
            echo "  - $table : $restant ligne(s) restante(s)\n";
        }
    } else {
        echo "Suppression d'une éventuelle précédente simulation...\n";
        nettoyer($pdo);

        echo "Génération des données de démonstration...\n";
        $stats = generer($pdo);

        echo "\n--- Données créées ---\n";
        foreach ($stats as $cle => $valeur) {
            echo sprintf("  %-15s : %d\n", $cle, $valeur);
        }
        echo "\nComptes de démo : prenom.nom@." . DOMAINE_DEMO . " (ex. admin@." . DOMAINE_DEMO . ")\n";
        echo "Mot de passe commun : " . MDP_DEMO . "\n";
        echo "\nTotal en base :\n";
        foreach (['users', 'services', 'reservations', 'paiements', 'avis', 'signalements', 'login_history'] as $table) {
            echo sprintf("  %-16s : %d\n", $table, (int) $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn());
        }
    }
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, "ERREUR : " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n");
    exit(1);
}
