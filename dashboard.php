<?php
session_start();
require_once 'db.php';

// Redirection si non connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$page_title = 'Tableau de bord';
$user_id = (int) $_SESSION['user_id'];
$role = $_SESSION['role'] ?? '';
$nom = $_SESSION['nom'] ?? '';

// ---------------------------------------------------------------
// Statistiques selon le rôle
// ---------------------------------------------------------------
$stats = [];

if ($role === 'admin') {
    $stats['users']         = (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $stats['services']      = (int) $pdo->query("SELECT COUNT(*) FROM services")->fetchColumn();
    $stats['reservations']  = (int) $pdo->query("SELECT COUNT(*) FROM reservations")->fetchColumn();
    $stats['signalements']  = (int) $pdo->query("SELECT COUNT(*) FROM signalements WHERE statut = 'en_attente'")->fetchColumn();
    $stats['verrouillages'] = (int) $pdo->query("SELECT COUNT(*) FROM tentatives_connexion WHERE verrouille_jusqua IS NOT NULL AND verrouille_jusqua > NOW()")->fetchColumn();
    $stats['avis']          = round((float) $pdo->query("SELECT IFNULL(AVG(note), 0) FROM avis")->fetchColumn(), 1);
    $stats['ca']            = (float) $pdo->query("SELECT IFNULL(SUM(montant), 0) FROM paiements WHERE statut = 'paye'")->fetchColumn();

} elseif ($role === 'reparateur') {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM services WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $stats['mes_services'] = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM reservations r JOIN services s ON r.service_id = s.id WHERE s.user_id = ? AND r.statut = 'en_attente'");
    $stmt->execute([$user_id]);
    $stats['demandes_attente'] = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT IFNULL(AVG(a.note), 0) FROM avis a JOIN reservations r ON a.reservation_id = r.id JOIN services s ON r.service_id = s.id WHERE s.user_id = ?");
    $stmt->execute([$user_id]);
    $stats['avis'] = round((float) $stmt->fetchColumn(), 1);

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM reservations r JOIN services s ON r.service_id = s.id WHERE s.user_id = ? AND r.statut = 'terminee'");
    $stmt->execute([$user_id]);
    $stats['reservations'] = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT IFNULL(SUM(p.montant), 0) FROM paiements p JOIN reservations r ON p.reservation_id = r.id JOIN services s ON r.service_id = s.id WHERE s.user_id = ? AND p.statut = 'paye'");
    $stmt->execute([$user_id]);
    $stats['ca'] = (float) $stmt->fetchColumn();

} elseif ($role === 'client') {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE client_id = ?");
    $stmt->execute([$user_id]);
    $stats['mes_reservations'] = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE client_id = ? AND statut = 'en_attente'");
    $stmt->execute([$user_id]);
    $stats['en_attente'] = (int) $stmt->fetchColumn();
}

// ---------------------------------------------------------------
// Historique des 6 derniers mois (alimente le graphique Chart.js)
// ---------------------------------------------------------------
$debutPeriode = date('Y-m-01', strtotime('-5 months'));

$sqlGraphe = "SELECT DATE_FORMAT(r.date_intervention, '%Y-%m') AS mois,
                     COUNT(*) AS total,
                     SUM(CASE WHEN r.statut = 'terminee' THEN 1 ELSE 0 END) AS terminees
              FROM reservations r";
$paramsGraphe = [];

if ($role === 'client') {
    $sqlGraphe .= " WHERE r.client_id = ? AND r.date_intervention >= ?";
    $paramsGraphe = [$user_id, $debutPeriode];
} elseif ($role === 'reparateur') {
    $sqlGraphe .= " JOIN services s ON r.service_id = s.id
                    WHERE s.user_id = ? AND r.date_intervention >= ?";
    $paramsGraphe = [$user_id, $debutPeriode];
} else {
    $sqlGraphe .= " WHERE r.date_intervention >= ?";
    $paramsGraphe = [$debutPeriode];
}
$sqlGraphe .= " GROUP BY mois ORDER BY mois";

$stmt = $pdo->prepare($sqlGraphe);
$stmt->execute($paramsGraphe);
$parMois = [];
foreach ($stmt->fetchAll() as $ligne) {
    $parMois[$ligne['mois']] = $ligne;
}

$moisFrancais = [1 => 'janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin',
                 'juill.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'];

$labelsGraphique = [];
$serieTotal = [];
$serieTerminees = [];

for ($i = 5; $i >= 0; $i--) {
    $timestamp = strtotime(date('Y-m-01') . " -$i months");
    $cle = date('Y-m', $timestamp);
    $labelsGraphique[] = $moisFrancais[(int) date('n', $timestamp)] . ' ' . date('y', $timestamp);
    $serieTotal[]      = (int) ($parMois[$cle]['total'] ?? 0);
    $serieTerminees[]  = (int) ($parMois[$cle]['terminees'] ?? 0);
}

require_once 'header.php';
?>
<div class="welcome-card">
    <h1>Bienvenue, <?= htmlspecialchars($nom) ?> !</h1>
    <p>
        Voici un aperçu de votre activité.
        Rôle : <span class="badge role-badge badge-<?= htmlspecialchars($role) ?>"><?= htmlspecialchars($role) ?></span>
    </p>
</div>

<!-- VUE ADMINISTRATEUR -->
<?php if ($role === 'admin'): ?>
    <div class="grid">
        <div class="card stat"><h3><?= (int) $stats['users'] ?></h3><p>Utilisateurs inscrits</p></div>
        <div class="card stat"><h3><?= (int) $stats['services'] ?></h3><p>Services proposés</p></div>
        <div class="card stat"><h3><?= (int) $stats['reservations'] ?></h3><p>Réservations globales</p></div>
        <div class="card stat stat-danger"><h3><?= (int) $stats['signalements'] ?></h3><p>Signalements en attente</p></div>
        <div class="card stat stat-warning"><h3><?= (int) $stats['verrouillages'] ?></h3><p>Comptes verrouillés</p></div>
        <div class="card stat stat-primary"><h3><?= htmlspecialchars(number_format($stats['ca'], 2, ',', ' ')) ?> €</h3><p>Encaissements</p></div>
    </div>
    <div class="actions-row">
        <a href="admin_users.php" class="btn btn-secondary">Gérer les utilisateurs</a>
        <a href="admin_signalements.php" class="btn btn-secondary">Modération des signalements</a>
        <a href="admin_verrouillages.php" class="btn btn-secondary">Gestion des verrouillages</a>
        <a href="admin_logs.php" class="btn btn-secondary">Logs de connexion</a>
        <a href="services.php" class="btn">Voir les services</a>
    </div>

<!-- VUE RÉPARATEUR -->
<?php elseif ($role === 'reparateur'): ?>
    <div class="grid">
        <div class="card stat"><h3><?= (int) $stats['demandes_attente'] ?></h3><p>Demandes en attente</p></div>
        <div class="card stat"><h3><?= (int) $stats['mes_services'] ?></h3><p>Mes prestations en cours</p></div>
        <div class="card stat"><h3><?= (int) $stats['reservations'] ?></h3><p>Prestations terminées</p></div>
        <div class="card stat stat-primary"><h3><?= htmlspecialchars(number_format($stats['ca'], 2, ',', ' ')) ?> €</h3><p>Chiffre d'affaires</p></div>
        <div class="card stat"><h3><?= htmlspecialchars(number_format($stats['avis'], 1, ',', ' ')) ?></h3><p>Note moyenne</p></div>
    </div>
    <div class="actions-row">
        <a href="ajouter_service.php" class="btn">Ajouter un service</a>
        <a href="mes_demandes.php" class="btn btn-secondary">Gérer mes demandes</a>
        <a href="mes_services.php" class="btn btn-secondary">Mes services</a>
        <a href="mes_gains.php" class="btn btn-secondary">Mes gains</a>
        <a href="services.php" class="btn btn-secondary">Voir les services</a>
    </div>

<!-- VUE CLIENT -->
<?php elseif ($role === 'client'): ?>
    <div class="grid">
        <div class="card stat"><h3><?= (int) $stats['mes_reservations'] ?></h3><p>Réservations effectuées</p></div>
        <div class="card stat stat-warning"><h3><?= (int) $stats['en_attente'] ?></h3><p>En attente de confirmation</p></div>
    </div>
    <div class="actions-row">
        <a href="services.php" class="btn">Trouver un réparateur</a>
        <a href="mes_reservations.php" class="btn btn-secondary">Voir mes réservations</a>
    </div>
<?php endif; ?>

<!-- GRAPHIQUE : évolution des réservations sur 6 mois -->
<div class="card chart-card">
    <h2>📈 Évolution des réservations <span class="muted" style="font-weight:400; font-size:.85rem;">— 6 derniers mois</span></h2>
    <div class="chart-wrap">
        <canvas id="graphiqueReservations"></canvas>
    </div>
    <p class="muted mb-0" id="msgGraphique" style="display:none;">Aucune réservation enregistrée sur les 6 derniers mois.</p>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    (function () {
        const donneesTotales = <?= json_encode($serieTotal, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        const donneesFinies  = <?= json_encode($serieTerminees, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        const labels         = <?= json_encode($labelsGraphique, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        const canvas         = document.getElementById('graphiqueReservations');

        if (!canvas || typeof Chart === 'undefined') {
            if (canvas) {
                canvas.style.display = 'none';
                const msg = document.getElementById('msgGraphique');
                if (msg) msg.style.display = 'block';
            }
            return;
        }

        if (donneesTotales.every(v => v === 0)) {
            canvas.style.display = 'none';
            const msg = document.getElementById('msgGraphique');
            if (msg) msg.style.display = 'block';
            return;
        }

        new Chart(canvas, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Réservations',
                        data: donneesTotales,
                        borderColor: '#ff6b35',
                        backgroundColor: 'rgba(255, 107, 53, .14)',
                        borderWidth: 3,
                        tension: .35,
                        fill: true,
                        pointBackgroundColor: '#ff6b35',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 7
                    },
                    {
                        label: 'Terminées',
                        data: donneesFinies,
                        borderColor: '#1f9d63',
                        backgroundColor: 'rgba(31, 157, 99, .10)',
                        borderWidth: 2,
                        tension: .35,
                        fill: false,
                        pointBackgroundColor: '#1f9d63',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            padding: 18,
                            font: { family: "'Poppins', sans-serif", size: 13 }
                        }
                    },
                    tooltip: {
                        backgroundColor: '#16283a',
                        titleFont: { family: "'Poppins', sans-serif" },
                        bodyFont: { family: "'Poppins', sans-serif" },
                        padding: 10,
                        cornerRadius: 8
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1, font: { family: "'Poppins', sans-serif" } },
                        grid: { color: 'rgba(22, 40, 58, .07)' }
                    },
                    x: {
                        ticks: { font: { family: "'Poppins', sans-serif" } },
                        grid: { display: false }
                    }
                }
            }
        });
    })();
</script>
<?php require_once 'footer.php'; ?>
