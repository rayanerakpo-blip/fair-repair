<?php
session_start();

require_once 'db.php';

$page_title = 'Conditions Générales d\'Utilisation';
require_once 'header.php';
?>
<div class="card legal-page">
    <h1>Conditions Générales d'Utilisation</h1>
    <p class="muted">Dernière mise à jour : 23 septembre 2026</p>

    <h2>Préambule</h2>
    <p>
        Les présentes Conditions Générales d'Utilisation (ci-après les « CGU ») régissent l'utilisation de la plateforme
        Fair'repair, service de mise en relation entre cyclistes et réparateurs de vélos de proximité, accessible depuis le site
        <strong>fair-repair</strong> (ci-après la « Plateforme »).
    </p>
    <p>
        L'inscription et l'utilisation de la Plateforme impliquent l'acceptation pleine et entière des présentes CGU.
        Si vous n'acceptez pas ces conditions, vous n'êtes pas autorisé à utiliser la Plateforme.
    </p>

    <h2>Article 1 — Objet</h2>
    <p>
        La Plateforme a pour objet de permettre :
    </p>
    <ul>
        <li>aux <strong>cyclistes</strong> de rechercher des réparateurs de vélos à proximité et de réserver une intervention à domicile ;</li>
        <li>aux <strong>réparateurs</strong> de publier leurs prestations, de gérer leurs demandes et de percevoir le paiement de leurs interventions.</li>
    </ul>

    <h2>Article 2 — Définitions</h2>
    <ul>
        <li><strong>« Utilisateur »</strong> : toute personne physique créant un compte sur la Plateforme, en qualité de client ou de réparateur ;</li>
        <li><strong>« Client »</strong> : utilisateur qui sollicite les services d'un réparateur ;</li>
        <li><strong>« Réparateur »</strong> : professionnel inscrit qui propose des prestations ;</li>
        <li><strong>« Réservation »</strong> : demande d'intervention effectuée par un client auprès d'un réparateur.</li>
    </ul>

    <h2>Article 3 — Accès à la Plateforme</h2>
    <p>
        La consultation des services et de la carte des prestations est libre et ne nécessite pas de compte.
        L'inscription est obligatoire pour réserver une intervention, publier une prestation ou gérer des demandes.
        L'accès à la Plateforme requiert une connexion Internet. La Plateforme ne saurait être tenue responsable
        des dysfonctionnements d'accès imputables à l'opérateur de l'utilisateur.
    </p>

    <h2>Article 4 — Création et sécurité du compte</h2>
    <ul>
        <li>Vous devez fournir des informations exactes, complètes et à jour lors de l'inscription ;</li>
        <li>Vous êtes responsable de la confidentialité de votre mot de passe et de toute activité effectuée via votre compte ;</li>
        <li>Vous devez informer immédiatement la Plateforme de toute utilisation non autorisée de votre compte ;</li>
        <li>La Plateforme met en place un verrouillage temporaire du compte après plusieurs tentatives de connexion échouées.</li>
    </ul>

    <h2>Article 5 — Services proposés</h2>
    <p>
        Les réparateurs publient des prestations (révision, réparation, entretien, etc.) avec un tarif et un rayon d'intervention.
        Les clients choisissent une date et une heure d'intervention. La réalisation des prestations relève de la seule responsabilité
        du réparateur, la Plateforme jouant uniquement un rôle d'intermédiaire technique de mise en relation.
    </p>

    <h2>Article 6 — Réservations et paiements</h2>
    <ul>
        <li>Une réservation peut être <strong>confirmée, annulée, terminée ou refusée</strong> selon la décision du réparateur ou du client, dans les conditions prévues par la Plateforme ;</li>
        <li>Le paiement est effectué au travers de la Plateforme après réalisation de l'intervention ;</li>
        <li>En cas de litige relatif à une prestation, les utilisateurs peuvent signaler un problème via le formulaire de signalement dédié.</li>
    </ul>

    <h2>Article 7 — Obligations des utilisateurs</h2>
    <p>Chaque utilisateur s'engage à :</p>
    <ul>
        <li>utiliser la Plateforme conformément aux lois en vigueur et aux présentes CGU ;</li>
        <li>ne pas publier de contenus illicites, diffamatoires, frauduleux ou portant atteinte aux droits des tiers ;</li>
        <li>ne pas tenter de perturber le fonctionnement de la Plateforme ou d'accéder aux comptes d'autres utilisateurs ;</li>
        <li>fournir des informations exactes, notamment le numéro SIRET pour les réparateurs.</li>
    </ul>

    <h2>Article 8 — Responsabilité</h2>
    <p>
        La Plateforme est un simple intermédiaire de mise en relation. Elle ne saurait être tenue responsable des
        prestations réalisées, des éventuels dommages causés lors d'une intervention, ni des relations contractuelles
        établies directement entre clients et réparateurs. La Plateforme s'efforce d'assurer la disponibilité du service
        mais ne garantit pas une disponibilité ininterrompue.
    </p>

    <h2>Article 9 — Données personnelles</h2>
    <p>
        Le traitement des données personnelles est décrit dans la
        <a href="confidentialite.php">Politique de confidentialité</a>.
        Vous disposez de droits d'accès, de rectification, d'effacement, de limitation, de portabilité et d'opposition,
        exerçables comme indiqué dans cette politique, notamment depuis votre espace personnel
        (<a href="mes_donnees.php">Mes données personnelles</a>).
    </p>

    <h2>Article 10 — Propriété intellectuelle</h2>
    <p>
        L'ensemble des éléments de la Plateforme (textes, graphismes, logo, code, base de données) est protégé par le droit
        de la propriété intellectuelle. Toute reproduction ou représentation sans autorisation est interdite.
    </p>

    <h2>Article 11 — Durée, suspension et résiliation</h2>
    <p>
        Les présentes CGU s'appliquent pendant toute la durée d'utilisation de la Plateforme. La Plateforme peut suspendre
        ou résilier un compte en cas de manquement aux présentes CGU, notamment en cas de contenu frauduleux ou de violation
        des règles de sécurité. L'utilisateur peut fermer son compte à tout moment depuis
        <a href="mes_donnees.php">Mes données personnelles</a>, entraînant la suppression de ses données.
    </p>

    <h2>Article 12 — Modification des CGU</h2>
    <p>
        Les présentes CGU peuvent être modifiées à tout moment. Les CGU applicables sont celles en vigueur à la date
        d'utilisation de la Plateforme. En cas de modification substantielle, les utilisateurs en seront informés.
    </p>

    <h2>Article 13 — Droit applicable et litiges</h2>
    <p>
        Les présentes CGU sont soumises à la loi française. En cas de litige, une solution amiable sera recherchée en priorité.
        À défaut, les tribunaux compétents seront saisis, conformément aux règles de droit commun.
    </p>

    <h2>Contact</h2>
    <p>
        Pour toute question relative aux présentes CGU, vous pouvez nous contacter à l'adresse suivante :
        <strong>contact@fair-repair.fr</strong>.
    </p>
</div>
<?php require_once 'footer.php'; ?>