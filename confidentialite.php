<?php
session_start();

require_once 'db.php';

$page_title = 'Politique de confidentialité — RGPD';
require_once 'header.php';
?>
<div class="card legal-page">
    <h1>Politique de confidentialité &amp; protection des données personnelles</h1>
    <p class="muted">Dernière mise à jour : 23 septembre 2026 — Conforme au Règlement (UE) 2016/679 (« RGPD »)</p>

    <h2>1. Responsable du traitement</h2>
    <p>
        Le responsable du traitement des données personnelles collectées sur la Plateforme
        <strong>Fair'repair</strong> est l'éditeur du service, joignable à l'adresse : <strong>contact@fair-repair.fr</strong>.
    </p>

    <h2>2. Données collectées</h2>
    <p>Nous collectons uniquement les données nécessaires au fonctionnement de la Plateforme :</p>
    <ul>
        <li><strong>Données d'identification</strong> : prénom, nom, adresse email ;</li>
        <li><strong>Données de contact et de livraison</strong> : téléphone, adresse, code postal, ville (nécessaires à la réalisation des interventions à domicile) ;</li>
        <li><strong>Données professionnelles (réparateurs)</strong> : numéro SIRET, spécialité ;</li>
        <li><strong>Données de fonctionnement</strong> : services publiés, réservations, avis, paiements, signalements ;</li>
        <li><strong>Données de sécurité</strong> : mot de passe (haché), question et réponse secrètes (hachées), journal des tentatives de connexion (email, adresse IP) ;</li>
        <li><strong>Cookie de session</strong> : identifiant de session (PHPSESSID), strictement nécessaire au fonctionnement du site.</li>
    </ul>

    <h2>3. Finalités et bases légales du traitement</h2>
    <table>
        <thead>
            <tr><th>Finalité</th><th>Base légale (art. 6 RGPD)</th></tr>
        </thead>
        <tbody>
            <tr><td>Création et gestion des comptes utilisateurs</td><td>Exécution du contrat (6.1.b)</td></tr>
            <tr><td>Mise en relation, réservations et paiements</td><td>Exécution du contrat (6.1.b)</td></tr>
            <tr><td>Récupération de mot de passe (question secrète / lien de réinitialisation)</td><td>Exécution du contrat (6.1.b)</td></tr>
            <tr><td>Sécurité des comptes et anti-fraude (verrouillage après échecs)</td><td>Intérêt légitime (6.1.f)</td></tr>
            <tr><td>Envoi d'e-mails de bienvenue et de suivi</td><td>Exécution du contrat (6.1.b)</td></tr>
            <tr><td>Modération des avis et gestion des signalements</td><td>Obligation légale et intérêt légitime (6.1.c / 6.1.f)</td></tr>
        </tbody>
    </table>

    <h2>4. Durées de conservation</h2>
    <table>
        <thead>
            <tr><th>Donnée</th><th>Durée de conservation</th></tr>
        </thead>
        <tbody>
            <tr><td>Compte utilisateur et données de profil</td><td>Durée de vie du compte, puis suppression sur demande</td></tr>
            <tr><td>Réservations, paiements, avis</td><td>Durée de vie du compte (historique), conformément aux obligations comptables pour les paiements</td></tr>
            <tr><td>Journal des tentatives de connexion</td><td>45 jours maximum après la dernière tentative enregistrée</td></tr>
            <tr><td>Lien de réinitialisation de mot de passe</td><td>1 heure après émission du jeton</td></tr>
            <tr><td>Cookie de session (PHPSESSID)</td><td>Durée de la session de navigation</td></tr>
        </tbody>
    </table>

    <h2>5. Destinataires des données</h2>
    <p>
        Les données sont destinées aux seuls services internes de la Plateforme et, en tant que de besoin,
        aux utilisateurs dans le strict cadre de leur mise en relation :
    </p>
    <ul>
        <li>le <strong>réparateur</strong> reçoit les informations de contact du client nécessaires à la réalisation de l'intervention (nom, adresse, téléphone) ;</li>
        <li>le <strong>client</strong> voit les informations professionnelles du réparateur (nom, spécialité, ville).</li>
    </ul>
    <p>
        Les données ne sont <strong>jamais vendues ni louées</strong> à des tiers. Aucun transfert hors de l'Union européenne n'est effectué.
    </p>

    <h2>6. Sécurité des données</h2>
    <p>
        Nous mettons en œuvre des mesures techniques et organisationnelles adaptées : chiffrement des mots de passe et des
        réponses secrètes (bcrypt), requêtes SQL préparées contre les injections, validation et nettoyage des entrées,
        protection des sessions, et verrouillage automatique en cas de tentative de connexion répétée.
    </p>

    <h2>7. Vos droits (RGPD)</h2>
    <p>Conformément aux articles 15 à 22 du RGPD, vous disposez des droits suivants :</p>
    <ul>
        <li><strong>Droit d'accès</strong> : consulter les données vous concernant, directement depuis la page <a href="mes_donnees.php">Mes données personnelles</a> ;</li>
        <li><strong>Droit de rectification</strong> : modifier vos informations depuis votre <a href="profil.php">profil</a> ;</li>
        <li><strong>Droit à l'effacement</strong> (« droit à l'oubli ») : demander la suppression de votre compte et de vos données depuis <a href="mes_donnees.php">Mes données personnelles</a> ;</li>
        <li><strong>Droit à la limitation</strong> du traitement ;</li>
        <li><strong>Droit à la portabilité</strong> : récupérer vos données dans un format lisible (export JSON) depuis <a href="mes_donnees.php">Mes données personnelles</a> ;</li>
        <li><strong>Droit d'opposition</strong> et <strong>droit de retirer votre consentement</strong> à tout moment sans affecter la licéité du traitement antérieur.</li>
    </ul>
    <p>
        Pour exercer ces droits, connectez-vous à votre espace personnel ou écrivez-nous à
        <strong>contact@fair-repair.fr</strong>. Vous pouvez également introduire une réclamation auprès de la
        <strong>CNIL</strong> (cnil.fr).
    </p>

    <h2>8. Cookies</h2>
    <p>
        La Plateforme utilise un <strong>unique cookie de session</strong> (PHPSESSID), strictement nécessaire à votre
        authentification et au bon fonctionnement du service. Ce cookie est déposé automatiquement lors de votre visite :
        il ne nécessite pas de consentement préalable au sens de la directive 2002/58/CE (article 82 de la loi Informatique et Libertés).
    </p>
    <p>
        Aucun cookie publicitaire, de mesure d'audience ou de suivi n'est déposé. Vous pouvez à tout moment supprimer
        ce cookie depuis les paramètres de votre navigateur : vous serez alors simplement déconnecté du service.
    </p>

    <h2>9. Contact et délégué à la protection des données</h2>
    <p>
        Pour toute question relative à la présente politique ou à l'exercice de vos droits, contactez-nous à
        <strong>contact@fair-repair.fr</strong>.
    </p>
</div>
<?php require_once 'footer.php'; ?>