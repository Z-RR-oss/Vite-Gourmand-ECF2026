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
FIREBASE_DATABASE_URL='' \
php -d error_log=/tmp/vg-test-php.log -S 127.0.0.1:8091 -t Public
```

Puis exécuter dans l'ordre, après import propre :

```sh
DB_NAME=vite_gourmand_test_20260928 /usr/bin/python3 tests/integration_http.py
DB_NAME=vite_gourmand_test_20260928 php tests/images.php
php tests/statistics-live.php
```

Le dernier test doit sortir 77 / NON EXÉCUTÉ sans activation explicite. L'URL Firebase vide du serveur de cette suite force volontairement le scénario d'indisponibilité, même si une configuration locale réelle existe. Pour la recette Firebase réelle, relancer le serveur sans cette surcharge. La configuration SMTP loopback est autorisée uniquement pour cette recette; aucun message ne quitte la machine. Réimporter le dump avant une nouvelle suite HTTP : elle conserve ses fixtures dans la base jetable.

## Résultats obtenus

| Suite | Résultat | Portée |
| --- | --- | --- |
| Import dump final | Réussi | 12 tables, contraintes et données de démonstration |
| orders.php | 25 assertions réussies | Prix, limites, délais, stock, concurrence réelle à deux processus, workflow et matériel |
| statistics.php | 41 vérifications réussies | SQL SQLite en mémoire, agrégats, filtres, erreurs REST, OAuth signé; transport simulé |
| integration_http.py | 126 assertions réussies | HTTP réel + MariaDB + capture SMTP, trois rôles, parcours et attaques basiques |
| images.php | 4 contrôles réussis | SVG refusé, PNG réencodé avec nom sûr, alt modifié, retrait galerie |
| statistics-live.php | Réussi le 29 septembre 2026 | Lecture réelle Firebase après PUT de 5 menus/2 jours, code0 |
| Apache XAMPP et Firebase | Réussi le 29 septembre 2026 | Lecture sous le compte daemon, HTTP200 et 5 menus; sonde locale supprimée |
| Archive de livraison | Réussie le 29 septembre 2026 | 164 fichiers + manifeste; dépendances, permissions 644, empreintes et exclusions contrôlées; deux générations de même SHA-256 |

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

Complément du 29 septembre : graphique Firebase réel contrôlé en desktop et à 390 px. Les barres natives utilisent désormais les couleurs sauge/bordeaux et affichent les valeurs nulles sur un fond neutre. Le graphique du CA et les valeurs textuelles restent cohérents; largeur document 390 px pour un viewport de 390 px. Captures `statistiques-firebase-reel.png` et `statistiques-firebase-mobile.png`.

Les six mockups (accueil, détail menu, contact en desktop et mobile) sont des captures de l'interface implémentée. Les wireframes sont reconstruits pour documenter l'organisation finale, pas présentés comme une preuve de conception antérieure au code.

## À recetter après configuration externe

Firebase réel est configuré et le PUT puis RUN_FIREBASE_TEST=1 ont réussi le 29 septembre. Le dashboard affiche les valeurs du dump de recette : 3 commandes, 19 convives, 120 €; menu Vegan : 1/9/0 €; journée du 29 : 2/15/0 €. Graphique commandes/CA et filtres vérifiés dans le navigateur; accès REST anonyme HTTP401. Capture : captures/statistiques-firebase-reel.png. Le scénario d'indisponibilité 503 a été validé avant configuration; le rétablissement affiche maintenant les données réelles. Vérifier la délivrabilité réelle des huit emails et l'exécution des deux cron. En production HTTPS, vérifier cookies Secure, fichiers privés inaccessibles, mentions légales complétées et comptes démo supprimés.

Une revue RGAA exhaustive avec lecteur d'écran, zoom navigateur, toutes les erreurs et tous les états n'a pas été réalisée. Ne pas transformer les tests pragmatiques en certificat de conformité.

## Contrôles de clôture

68 fichiers PHP hors vendor : php -l réussi. Les deux JavaScript passent node --check. git diff --check propre. Composer validate réussi (avertissement licence non spécifiée), audit sans vulnérabilité signalée. Crawl HTTP public : 22 liens/ressources, aucune erreur. Sur le virtual host réel : /.git/HEAD = 403, /Config/mail.local.php, /vite_gourmand.sql.sql et /vendor/ = 404.

Clavier à 320 px sur Contact : premier Tab sur Aller au contenu, outline visible; menu activable avec Entrée et refermable par Échap, aria-expanded revient à false. Correction d'Échap étendue au bouton du menu puis retestée. Largeur document = 320 px. Console navigateur : aucun avertissement/erreur observé. Cette mesure de repli ne remplace pas un test exhaustif au zoom.

Contrastes calculés sur les tokens sRGB : encre/crème 12,81:1; texte secondaire/crème 5,31:1; blanc/bordeaux 10,23:1; sauge/crème 5,27:1; focus/crème 4,45:1. Le blé est décoratif. Après la correction du réencodage PNG, le journal PHP ne contient que les erreurs Firebase attendues pendant la dernière suite; aucune erreur HTTP500 dans la recette finale.

## Mise en ligne du 30 septembre 2026

29 contrôles réussis sur alwaysdata : huit pages publiques, cookie Secure/HttpOnly/SameSite, huit chemins privés interdits, connexion et espace des trois rôles, refus des anciens mots de passe, dashboard Firebase réservé à l’administrateur (3 commandes, 19 convives, 120 €). Rapport `recette-alwaysdata.json`, captures `accueil-alwaysdata.png` et `statistiques-alwaysdata.png`. Les 41 contrôles statistiques ont été relancés après l’ajout du réglage IPv4; PUT puis GET Firebase réels réussis depuis alwaysdata. Le test SMTP explicitement autorisé est indiqué Envoyé sans rebond dans le journal hébergeur; réception en boîte confirmée par la propriétaire. Les deux tâches sont installées et leurs scripts réussissent en CLI; exécutions automatiques observées avec code 0, dont la tâche quotidienne à 09:00:23.

Contrôle final du 30 septembre : 67 fichiers PHP suivis par Git passent php -l, les deux JavaScript passent node --check. Les anciens comptes démo sont refusés en ligne. Journaux hébergeur : 55 réponses HTTP, aucune 5xx; aucune erreur Apache et aucune nouvelle erreur PHP depuis la correction Firebase à 02:43. Les trois anciennes entrées PHP correspondent aux erreurs de configuration corrigées avant ouverture.

Les journaux alwaysdata confirment les deux scripts exécutés par le planificateur, code de sortie 0 : synchronisation à 02:54:22 et tâche quotidienne matériel à 09:00:23 le 30 septembre 2026 (Europe/Paris). Fréquence finale : statistiques toutes les 15 minutes, matériel chaque jour à 09:00.

## Nettoyage du code et nouveaux visuels — 30 septembre 2026

Recette rejouée sur `vite_gourmand_test_cleanup_20260930` : 25 assertions métier/concurrence, 41 statistiques, 126 HTTP/SQL/SMTP et 4 uploads réussis (196). Après simplification finale des vues, les suites HTTP et images ont été rejouées avec succès. Les messages restent capturés localement.

Les 68 fichiers PHP applicatifs/scripts/tests passent la vérification syntaxique ; PHP-CS-Fixer ne propose plus de correction. Les deux scripts JavaScript passent la vérification syntaxique et Prettier valide le CSS source/JavaScript. Le CSS assemblé est synchronisé ; comparaison de la feuille précédente et de la nouvelle après normalisation : mêmes règles, valeurs et ordre. Les espaces et commentaires seuls diffèrent.

La migration des deux images a été exécutée puis relancée sans doublon dans la base locale. Les couvertures Vegan et Noël sont chargées et leurs alternatives textuelles sont présentes. Contrôles navigateur à 1440 px sur le catalogue et 390 px sur les deux fiches : largeur document égale au viewport. Captures : `captures/menu-vegan-photo-mobile.png` et `captures/menu-noel-photo-mobile.png`.

Après déploiement du commit `2e80ccb`, 34 contrôles HTTPS/authentification/Firebase réussissent, dont les chemins API des deux nouvelles images et la comparaison des empreintes des JPEG/CSS publiés avec les fichiers locaux. Rapport : `recette-nettoyage-visuels.json`. Le filtre AJAX Vegan affiche une seule carte avec sa photo chargée ; Réinitialiser restitue les quatre menus actifs de la démonstration. Capture publique : `captures/catalogue-nouveaux-visuels-en-ligne.png`.

## Commentaires et maintenance — 1er octobre 2026

La connexion est simplifiée avec une destination interne partagée, des redirections 303 et un message d'erreur annoncé aux technologies d'assistance. La validation du texte alternatif est commune aux deux actions de galerie. La normalisation du catalogue n'utilise plus de référence persistante de boucle. Des commentaires et contrats PHPDoc expliquent les limites et invariants des sessions, commandes, emails, uploads, agrégats Firebase et tâches planifiées.

Sur `vite_gourmand_test_comments_20261001`, les 25 assertions métier, 41 statistiques, 126 HTTP/SQL/SMTP et 4 images réussissent. Les 68 fichiers PHP passent le contrôle syntaxique ; JavaScript, formatage et assemblage CSS sont vérifiés. La connexion est inspectée sur ordinateur et à 390 px (largeur du document : 390 px). Aucun email de recette n'est envoyé hors de la capture locale.

## Modélisation et livrables graphiques - 1er octobre 2026

Ajout d'un MCD conceptuel distinct du schéma SQL : dix entités, dix associations, trois vues PDF/SVG. Les cardinalités sont confrontées aux clés étrangères, à l'unicité de l'avis par commande et aux règles applicatives. Le PDF de trois pages est rendu en PNG et chaque page inspectée ; les liens locaux des documents modifiés sont vérifiés. Les six wireframes et six mockups existants ont les dimensions attendues (1440 × 1000 ou 390 × 844), et la charte conserve ses neuf pages. Ce changement documentaire ne modifie ni le code PHP ni la base de données.


## Finalisation accessibilité et préparation du jury — 1er octobre 2026

Sur `vite_gourmand_test_final_20261001`, la suite métier donne 25 assertions, les statistiques 41 et les images 4. Après les derniers changements PHP, la recette HTTP/SQL/SMTP a été rejouée : 126 assertions réussies. La nouvelle suite `tests/accessibility_http.py`, exécutée avant les mutations de fixtures HTTP, vérifie 32 pages/états et 473 assertions HTML/validation. Les emails de ces tests restent capturés localement.

71 fichiers PHP hors dépendances/configurations privées passent le contrôle syntaxique. Les deux JavaScript, le formatage PHP/CSS/JS, les 12 modules CSS assemblés et `git diff --check` sont vérifiés. Le routeur `tests/viewport-router.php` refuse les accès non locaux et les bases qui ne contiennent pas `_test_` ; il est exclu de l'archive applicative de production.

48 mesures de présentation sont conservées dans `recette-accessibilite-responsive.json` : repli à 320 px, tailles 390/768/1440, texte doublé et espacement augmenté sur un échantillon représentatif. Les mesures d'espacement ont été reprises après attente explicite de l'application des styles ; le débordement ainsi détecté sur Contact est corrigé en autorisant les colonnes à rétrécir et les longs mots à se replier. Les 48 mesures finales ne présentent pas de débordement horizontal du document. Les tableaux peuvent défiler dans leur propre région.

VoiceOver a été autorisé par la propriétaire mais l'accès à l'utilitaire a été refusé par les permissions Mac : ce test n'a pas pu être exécuté. Une revue complète des critères RGAA et du zoom navigateur reste à effectuer ; aucun taux de conformité n'est calculé. Voir `accessibilite.md` pour la portée exacte.
