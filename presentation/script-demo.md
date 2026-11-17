# Script de démonstration — Fair'repair

**Format : 5 à 10 minutes · jury scolaire**

Ce document accompagne le support `diapositives.html`.

---

## 1. Avant de commencer (à préparer 5 min avant)

- [ ] Lancer **AMPPS** (Apache + MySQL) et vérifier que le site s'ouvre : `http://localhost/www/Fair_repair/`
- [ ] Vérifier que les **données de démo** sont en place (sinon : `php seed_demo.php`)
- [ ] Ouvrir **3 onglets** déjà connectés, pour ne pas perdre de temps :
  1. **Client** — `camille.morel.26@demo.fairrepair.fr` / `Demo@Fair2026`
  2. **Réparateur** — `lo.leroy.8@demo.fairrepair.fr` / `Demo@Fair2026`
  3. **Administrateur** — `admin@demo.fairrepair.fr` / `Demo@Fair2026`
- [ ] Fermer les notifications / autres applications, couper le son du téléphone
- [ ] Mettre le navigateur en plein écran (**F11**) et zoomer un peu (**Ctrl + +**) pour la salle
- [ ] Vérifier une dernière fois la connexion Internet (la carte utilise OpenStreetMap)

> Astuce : gardez `diapositives.html` ouvert dans un onglet séparé pour pouvoir le réafficher.

---

## 2. Déroulé minuté (≈ 7 minutes)

### 0:00 → 0:45 · Introduction (slide 1-2)
> « Bonjour. Je m'appelle Rayane, je suis en SLAM 2.1. J'ai créé **Fair'repair**, une plateforme qui met en relation les cyclistes et les réparateurs de vélos. Le constat est simple : avec le retour du vélo en ville, trouver un réparateur de confiance, comparer les prix et réserver reste compliqué — et les réparateurs indépendants manquent de visibilité. »

### 0:45 → 1:30 · La solution (slide 3)
> « Fair'repair répond à ce problème avec **une plateforme et trois espaces** : un espace client, un espace réparateur et un espace administrateur. Elle est construite avec PHP, MySQL et une base de 13 tables. »

### 1:30 → 3:30 · Démonstration — parcours client (slide 4)
**À montrer :**
1. Page d'accueil → bouton « Trouver un réparateur »
2. **Recherche** : taper par exemple `frein` dans la barre de recherche
3. **Carte des réparateurs** : montrer les marqueurs, cliquer sur un marqueur, ouvrir une popup
4. Se connecter au **compte client** → **« Mes réservations »** (liste avec les différents statuts)
5. **Laisser un avis** sur une réservation terminée
6. Montrer l'onglet **« Mes données »** : l'export JSON et la suppression du compte (RGPD)

> À dire : « Le client peut donc chercher, localiser, réserver, suivre et évaluer — tout au même endroit. »

### 3:30 → 5:00 · Démonstration — parcours réparateur (slide 5)
**À montrer :**
1. Se connecter au **compte réparateur** → **Tableau de bord**
2. Montrer les **statistiques** et le **graphique sur 6 mois** (réservations / terminées)
3. **« Mes services »** : la liste des prestations publiées
4. **« Demandes reçues »** : accepter une demande sous les yeux du jury
5. **« Mes gains »** : les paiements encaissés

> À dire : « Le réparateur gère toute son activité en ligne : ses prestations, ses demandes, ses revenus. »

### 5:00 → 6:15 · Démonstration — parcours administrateur (slide 6)
**À montrer :**
1. Se connecter au **compte administrateur**
2. **« Signalements »** : traiter ou refuser un signalement
3. **« Verrouillages »** : montrer un compte bloqué après des tentatives échouées
4. **« Journal d'authentification »** : l'historique des connexions (e-mail, rôle, IP, date)

> À dire : « L'administrateur garantit la modération et la sécurité de la plateforme. »

### 6:15 → 7:00 · Conclusion (slides 7-10)
> « Techniquement, tout est développé sans framework, avec PHP 8.2 et MySQL : 38 pages, environ 4 500 lignes, une carte interactive, des graphiques. La sécurité a été prise au sérieux : mots de passe hachés, requêtes préparées, verrouillage après 5 échecs et conformité RGPD. Demain, j'aimerais ajouter le paiement en ligne réel et une messagerie interne. Merci de votre attention. »

---

## 3. Chiffres à citer (ils impressionnent)

| Chiffre | Signification |
|---|---|
| **3** | rôles (client, réparateur, admin) |
| **13** | tables dans la base MySQL |
| **38** | fichiers / pages PHP |
| **4 500** | lignes de code |
| **105** | requêtes SQL préparées (sécurisées) |
| **5** | échecs de connexion avant verrouillage |
| **6** | mois d'historique dans le graphique |

Données de démonstration visibles : **66 comptes**, **47 prestations**, **252 réservations**, **96 avis**.

---

## 4. Réponses aux questions probables

**« Pourquoi ne pas avoir utilisé un framework (Symfony, Laravel) ? »**
> Pour maîtriser chaque brique et comprendre le fonctionnement réel d'une application web. Le projet étant de taille modeste, un framework aurait ajouté de la complexité sans bénéfice immédiat — mais je sais que pour un passage à grande échelle, il deviendrait pertinent.

**« Comment protégez-vous les mots de passe ? »**
> Ils ne sont jamais stockés en clair : j'utilise `password_hash` (bcrypt), avec vérification via `password_verify`.

**« Et les injections SQL ? »**
> Toutes les requêtes qui contiennent une donnée utilisateur passent par des **requêtes préparées PDO** : 105 au total. Les affichages sont échappés avec `htmlspecialchars` contre le XSS.

**« Comment gérez-vous le RGPD ? »**
> Consentement explicite à l'inscription, page dédiée aux données personnelles avec **export en JSON** (droit à la portabilité) et **suppression définitive du compte** (droit à l'effacement).

**« Qu'est-ce qui se passe si quelqu'un essaie de deviner un mot de passe ? »**
> Après 5 échecs, le compte est temporairement verrouillé. L'administrateur peut le voir dans la page « Verrouillages » et toutes les tentatives sont journalisées.

**« Que feriez-vous si vous aviez plus de temps ? »**
> Le paiement en ligne réel, une messagerie interne, des notifications par e-mail à chaque changement de statut, et une mise en production avec HTTPS.

---

## 5. Plan B (si problème pendant la démo)

- **La carte ne s'affiche pas** : passer à la **vue liste** (« Voir la liste ») et enchaîner — ne pas s'acharner.
- **Une page ne répond pas** : recharger (**F5**), ou revenir à un onglet déjà ouvert.
- **Site totalement indisponible** : s'appuyer sur le support de diapositives, qui contient déjà tous les chiffres et fonctionnalités.
- **En cas de doute** : toujours raconter le **parcours utilisateur** plutôt que de cliquer au hasard.

---

## 6. Raccourcis utiles

| Action | Raccourci |
|---|---|
| Plein écran navigateur | `F11` |
| Zoom avant / arrière | `Ctrl` + `+` / `Ctrl` + `-` |
| Recharger la page | `F5` |
| Diapositive suivante / précédente | `→` / `←` (dans `diapositives.html`) |
| Exporter les diapositives en PDF | bouton « Exporter en PDF » |

> À l'impression, choisissez **Paysage**, **sans marges**, et cochez **Graphiques d'arrière-plan** pour garder les couleurs.
