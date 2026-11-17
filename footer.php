<footer class="site-footer">
    <div class="container">
        <p>&copy; <?= date('Y') ?> Fair-repair - Mise en relation de cyclistes et réparateurs de vélos</p>
        <nav class="footer-links" aria-label="Liens légaux">
            <a href="cgu.php">Conditions Générales d'Utilisation</a>
            <a href="confidentialite.php">Politique de confidentialité (RGPD)</a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="mes_donnees.php">Mes données personnelles</a>
            <?php endif; ?>
            <a href="mailto:contact@fair-repair.fr">Contact</a>
        </nav>
    </div>
</footer>

<?php if (empty($_SESSION['cookies_acceptes'])): ?>
<div id="cookie-banner" role="dialog" aria-label="Information cookies">
    <div class="cookie-banner-text">
        <strong>🍪 Cookies</strong>
        <p>
            Ce site utilise un cookie de session strictement nécessaire à votre connexion (RGPD : aucun cookie publicitaire
            ni de suivi). En continuant à naviguer, vous acceptez notre
            <a href="confidentialite.php">Politique de confidentialité</a> et
            <a href="cgu.php">Conditions Générales d'Utilisation</a>.
        </p>
    </div>
    <div class="cookie-banner-actions">
        <button type="button" class="btn btn-sm" onclick="accepterCookies()">J'accepte</button>
        <a href="confidentialite.php" class="btn btn-secondary btn-sm">En savoir plus</a>
    </div>
</div>
<script>
    function accepterCookies() {
        document.getElementById('cookie-banner').style.display = 'none';
        // Enregistrement du consentement côté serveur (session) puis côté navigateur
        fetch('rgpd_consent.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: 'consent=accepted' })
            .catch(function () {});
        try { localStorage.setItem('rgpd_consent', 'accepted'); } catch (e) {}
    }
</script>
<?php endif; ?>
</body>
</html>