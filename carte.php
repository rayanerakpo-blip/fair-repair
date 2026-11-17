<?php
session_start();
require_once 'db.php';

$stmt = $pdo ->query("
    SELECT u.id, u.prenom, u.nom, u.adresse, u.code_postal, u.ville,
           u.latitude AS lat, u.longitude AS lng,
           COUNT(s.id) AS total_services
    FROM users u
    JOIN services s ON s.user_id = u.id
    WHERE u.adresse IS NOT NULL AND u.adresse != ''
    GROUP BY u.id, u.prenom, u.nom, u.adresse, u.code_postal, u.ville, u.latitude, u.longitude
");
$reparateurs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Carte des réparateurs';
require_once 'header.php';
?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
    #map { height: 600px; width: 100%; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
</style>

<div class="top-bar">
    <h1>Carte des réparateurs</h1>
    <a href="services.php" class="btn btn-secondary">Vue liste</a>
</div>

<div id="map"></div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    const reparateurs = <?= json_encode($reparateurs, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

    const map = L.map('map').setView([48.8566, 2.3522], 11);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    async function chargerMarqueurs() {
        for (const r of reparateurs) {
            // Coordonnées déjà enregistrées (géocodées à l'inscription) sinon géocodage Nominatim
            let lat, lon;

            if (r.lat != null && r.lng != null && r.lat !== '0.0000000' && r.lng !== '0.0000000') {
                lat = r.lat;
                lon = r.lng;
            } else {
                const adresseTexte = `${r.adresse}, ${r.code_postal} ${r.ville}, France`;
                const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(adresseTexte)}`;

                try {
                    const response = await fetch(url);
                    const data = await response.json();

                    if (data && data.length > 0) {
                        lat = data[0].lat;
                        lon = data[0].lon;
                    }
                } catch (err) {
                    console.error("Erreur de géocodage pour :", adresseTexte, err);
                }
            }

            if (lat != null && lon != null) {
                const marker = L.marker([lat, lon]).addTo(map);
                marker.bindPopup(`
                    <div style="min-width: 160px; text-align: center;">
                        <h3 style="margin: 0 0 5px 0;">${r.prenom} ${r.nom}</h3>
                        <p style="margin: 0 0 5px 0; font-size: 0.85rem; color: #555;">📍 ${r.ville} (${r.code_postal})</p>
                        <p style="margin: 0 0 10px 0; font-size: 0.85rem;"><b>${r.total_services}</b> service(s) proposé(s)</p>
                        <a href="services.php?reparateur_id=${r.id}" class="btn" style="display: block; padding: 0.4rem; font-size: 0.8rem;">Voir ses services</a>
                    </div>
                `);
            }

            await new Promise(res => setTimeout(res, 200));
        }
    }

    chargerMarqueurs();
</script>
<?php require_once 'footer.php'; ?>