# Plan de recette et résultats

Recette locale du 28 septembre 2026, PHP 8.2.4/MariaDB XAMPP. Les données de tests sont fictives. Les tests modifient une base dédiée contenant `_test_`, jamais la base personnelle.

## Reproduction automatisée

Créer une base jetable et importer le dump. Les commandes ci-dessous sont adaptées au Mac XAMPP (ajuster MYSQL_BIN/PHP_BIN et les identifiants sur une autre machine).

```sh
mysql -u root -e "CREATE DATABASE vite_gourmand_test_20260928 CHARACTER SET utf8mb4"
mysql -u root vite_gourmand_test_20260928 < vite_gourmand.sql.sql
DB_NAME=vite_gourmand_test_20260928 php tests/orders.php
php tests/statistics.php
```

Dans un terminal distinct, lancer la capture locale avec Python 3.9 à 3.11 (smtpd est retiré en 3.12) :

```sh
/usr/bin/python3 tests/smtp_capture.py /tmp/vg-test-mails.jsonl
```

Dans un second terminal :

```sh
DB_NAME=vite_gourmand_test_20260928 \
SMTP_HOST=127.0.0.1 SMTP_PORT=1025 SMTP_AUTH=false SMTP_ENCRYPTION=none \
SMTP_FROM_EMAIL=robot@example.com CONTACT_EMAIL=contact@example.com \
APP_URL=http://127.0.0.1:8091 \
php -d error_log=/tmp/vg-test-php.log -S 127.0.0.1:8091 -t Public
```

Puis exécuter dans l'ordre, après import propre :

```sh
DB_NAME=vite_gourmand_test_20260928 /usr/bin/python3 tests/integration_http.py
DB_NAME=vite_gourmand_test_20260928 php tests/images.php
php tests/statistics-live.php
```

Le dernier test doit sortir 77 / NON EXÉCUTÉ sans activation explicite. La configuration SMTP loopback est autorisée uniquement pour cette recette; aucun message ne quitte la machine. Réimporter le dump avant une nouvelle suite HTTP : elle conserve ses fixtures dans la base jetable.

## Résultats obtenus

| Suite | Résultat | Portée |
| --- | --- | --- |
| Import dump final | Réussi | 12 tables, contraintes et données de démonstration |
| orders.php | 25 assertions réussies | Prix, limites, délais, stock, concurrence réelle à deux processus, workflow et matériel |
| statistics.php | 41 vérifications réussies | SQL SQLite en mémoire, agrégats, filtres, erreurs REST, OAuth signé; transport simulé |
| integration_http.py | 126 assertions réussies | HTTP réel + MariaDB + capture SMTP, trois rôles, parcours et attaques basiques |
| images.php | 4 contrôles réussis | SVG refusé, PNG réencodé avec nom sûr, alt modifié, retrait galerie |
| statistics-live.php | Non exécuté | Firebase absent : blocage externe assumé |

La première recette upload a révélé l'absence de imagewebp dans GD XAMPP. Le réencodage a été changé en PNG et les quatre contrôles ont ensuite réussi. Les anciennes erreurs du journal restent des traces de diagnostic, pas des erreurs persistantes.

## Scénarios fonctionnels et attendus

| Domaine | Manipulation | Résultat attendu et vérification |
| --- | --- | --- |
| Inscription | Champs absents, faible mot de passe, email dupliqué, rôle falsifié, puis données valides | Erreurs sans mutation, email unique, utilisateur imposé, bienvenue capturée |
| Connexion | Faux mot de passe, trois rôles, employé désactivé | Refus ou bon espace; session désactivée révoquée |
| Reset | Demande neutre, lien valide, utilisé, expiré | Nouveau hash, usage unique, expiration, révocation des anciennes sessions |
| Catalogue | Chaque filtre, fourchette inversée, actif=0, stock=0 | Résultats cohérents; inactif absent; épuisé non commandable |
| Commande | Invité, minimum, délai, remise, livraison, confirmation répétée | Connexion requise, calcul serveur exact, une seule réservation |
| Concurrence | Deux processus sur dernier stock | Une commande, un refus, jamais stock négatif |
| Modification | Propriétaire et intrus, avant/après acceptation | Seul propriétaire en attente; menu inchangé |
| Annulation | Client, staff sans/avec motif, répétition | Contact obligatoire staff, historique conservé, +1 stock une fois |
| Suivi | Saut d'étape puis chaîne autorisée | Saut rejeté, historique horodaté complet |
| Matériel | Frontière 10 jours, week-end, retard, double cron et retour | 600 € après dépassement, un email accepté, retour unique et clôture |
| Avis | Avant fin, doublon, XSS, modération | Refus prématuré/doublon; HTML échappé; seul validé public |
| CRUD | Menus, plats/allergènes, associations, horaires, images | Persistance, validation, footer actualisé; menu référencé désactivé |
| Équipe | Créer, injecter rôle, désactiver session ouverte | Employé imposé; mail sans mot de passe; accès retiré |
| Contact | Invalide puis valide | Validation, Reply-To, SMTP accepté, message fidèle au résultat |
| Sécurité | POST sans CSRF, GET mutation, rôle/ID usurpé, SQLi, tableaux | 403/405/validation, absence de mutation non autorisée |
| NoSQL | Configuration manquante | HTTP 503 explicite, aucun faux chiffre |

## Recette navigateur

Les 22 pages principales publiques/client/admin ont été contrôlées à 390, 768 et 1 440 px, avec captures dans `captures/` et mesures dans `recette-responsive.json`. Largeur du document contrôlée, h1 unique, labels et alternatives images. Navigation mobile et filtres fetch vérifiés. Lecture visuelle des captures : identité cohérente, champs utilisables et absence de chevauchement sur les vues inspectées.

Les six mockups (accueil, détail menu, contact en desktop et mobile) sont des captures de l'interface implémentée. Les wireframes sont reconstruits pour documenter l'organisation finale, pas présentés comme une preuve de conception antérieure au code.

## À recetter après configuration externe

Configurer Firebase réel, synchroniser, vérifier le nœud dans la console, lancer RUN_FIREBASE_TEST=1, comparer commandes/CA à SQL de contrôle, filtrer menu/dates, inspecter graphique, tester panne/reprise. Vérifier la délivrabilité réelle des huit emails et l'exécution des deux cron. En production HTTPS, vérifier cookies Secure, fichiers privés inaccessibles, mentions légales complétées et comptes démo supprimés.

Une revue RGAA exhaustive avec lecteur d'écran, zoom navigateur, toutes les erreurs et tous les états n'a pas été réalisée. Ne pas transformer les tests pragmatiques en certificat de conformité.

## Contrôles de clôture

68 fichiers PHP hors vendor : php -l réussi. Les deux JavaScript passent node --check. git diff --check propre. Composer validate réussi (avertissement licence non spécifiée), audit sans vulnérabilité signalée. Crawl HTTP public : 22 liens/ressources, aucune erreur. Sur le virtual host réel : /.git/HEAD = 403, /Config/mail.local.php, /vite_gourmand.sql.sql et /vendor/ = 404.

Clavier à 320 px sur Contact : premier Tab sur Aller au contenu, outline visible; menu activable avec Entrée et refermable par Échap, aria-expanded revient à false. Correction d'Échap étendue au bouton du menu puis retestée. Largeur document = 320 px. Console navigateur : aucun avertissement/erreur observé. Cette mesure de repli ne remplace pas un test exhaustif au zoom.

Contrastes calculés sur les tokens sRGB : encre/crème 12,81:1; texte secondaire/crème 5,31:1; blanc/bordeaux 10,23:1; sauge/crème 5,27:1; focus/crème 4,45:1. Le blé est décoratif. Après la correction du réencodage PNG, le journal PHP ne contient que les erreurs Firebase attendues pendant la dernière suite; aucune erreur HTTP500 dans la recette finale.
