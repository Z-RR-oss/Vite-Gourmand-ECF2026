# Vite & Gourmand - ECF DWWM

Application de traiteur bordelais : catalogue filtrable, commandes, suivi, avis modérés et gestion par rôle. PHP 8.2, PDO/MariaDB, HTML/CSS/JavaScript et PHPMailer. Les statistiques sont synchronisées vers Firebase Realtime Database puis lues depuis Firebase.

**Site de démonstration publié le 30 septembre 2026 : [https://vite-gourmandecf2026.alwaysdata.net](https://vite-gourmandecf2026.alwaysdata.net).** Hébergement alwaysdata Free (0 €), PHP 8.2, MySQL et Firebase opérationnels; 29 contrôles HTTPS/authentification réussis. Les deux tâches sont installées et le test SMTP autorisé est indiqué « Envoyé » par alwaysdata. La réception en boîte et la première exécution automatique restent à observer. Les mots de passe publics ont été remplacés; ils sont remis séparément dans un fichier privé. Voir la [checklist](docs/checklist-finale.md) et le [rapport](docs/rapport-final.md).

## Installation locale

Prérequis : PHP >=8.2, pdo_mysql, mbstring, curl, openssl, gd; MariaDB/MySQL et Composer 2. PHP et SQL doivent utiliser Europe/Paris. Environnement de recette : XAMPP/macOS.

```sh
git clone https://github.com/Z-RR-oss/Vite-Gourmand-ECF2026.git
cd Vite-Gourmand-ECF2026
composer install
mysql -u root -p -e "CREATE DATABASE vite_gourmand CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci"
mysql -u root -p vite_gourmand < vite_gourmand.sql.sql
cp Config/database.example.php Config/database.local.php
cp Config/mail.example.php Config/mail.local.php
cp Config/nosql.example.php Config/nosql.local.php
```

Renseigner les fichiers locaux, sans écraser une configuration existante et sans les versionner. Le dump **supprime les tables homonymes** : import uniquement sur base vide/jetable. Pour une base existante : sauvegarder, examiner `php Scripts/migrer-schema.php`, puis appliquer avec `--apply` après vérification du plan.

Sur le Mac actuel, `php composer.phar install` remplace `composer install`. Le virtual host [vite-gourmand.local](http://vite-gourmand.local) pointe sur `Public/`. À défaut :

```sh
php -S 127.0.0.1:8080 -t Public
```

Ouvrir [le site local](http://127.0.0.1:8080). Définir APP_URL avec cette URL pour les liens email. Ce serveur PHP est destiné au développement.

## Comptes de démonstration

Comptes fournis par le dump, pas nécessairement par une base personnelle préexistante. Mot de passe commun : **Demo-Vg2026!**. À retirer/changer avant exposition publique.

| Rôle | Email | Parcours |
| --- | --- | --- |
| Client | quentin@example.com | Commandes, profil, avis |
| Employé | charlie@example.com | Commandes, menus/images, plats/allergènes, horaires, avis |
| Administrateur | corentin@example.com | Fonctions employé, comptes employés, statistiques |

L'inscription publique impose utilisateur. Aucun administrateur ne se crée depuis l'interface.

## Configuration et exploitation

Variables DB_*, SMTP_*, APP_URL et FIREBASE_* prioritaires sur les fichiers locaux. Seuls les exemples fictifs sont versionnés. Configurer un SMTP autorisé et CONTACT_EMAIL. Le contact utilise Reply-To. La capture SMTP locale prouve la génération des messages, pas la délivrabilité Internet.

Créer une vraie Firebase Realtime Database et un compte de service; règles publiques fermées et clé hors Public. Suivre [NoSQL](docs/nosql.md), puis :

```sh
php Scripts/synchroniser-statistiques.php
RUN_FIREBASE_TEST=1 php tests/statistics-live.php
```

Sans configuration, les statistiques répondent 503 avec explication. Aucun JSON local ne remplace Firebase. Prévoir un cron toutes les 15 minutes pour la synchronisation et quotidien pour `Scripts/verifier-retards-materiel.php`. Voir [déploiement](docs/deploiement.md) pour chemins, droits, HTTPS et sauvegardes.

## Métier, architecture et sécurité

Prix du menu = forfait du minimum de convives. Prix proportionnel, remise 10 % dès minimum + 5, livraison hors Bordeaux 5 € + 0,59 €/km. Distance déclarative (0 à Bordeaux), contrôlée par l'équipe. Jours ouvrés : lundi-vendredi, hors jour de départ, sans jours fériés. Stock = nombre de commandes disponibles.

`Public/` : pages/contrôleurs; `Templates/` : présentation commune; `Config/` : connexions, sessions, CSRF, rôles; `Services/` : métier et transactions; `Repositories/` : accès Firebase; `Scripts/` : tâches CLI. SQL garde le métier; Firebase reçoit des agrégats sans données clients.

PDO préparé, sorties échappées, CSRF, rôles serveur, sessions renouvelées/révoquées après désactivation ou reset, tokens hachés/expirables, images réencodées, secrets hors Git. Transactions/verrous pour le stock. Audit pragmatique, sans certification RGAA complète.

## Tests et livrables

[Plan de tests reproductible](docs/plan-tests.md) : 25 assertions métier/concurrence, 41 statistiques locales, 126 HTTP/SQL/SMTP, 4 uploads. Import SQL réel en base jetable.

- [Manuel PDF](docs/manuel-utilisateur.pdf) et [source](docs/manuel-utilisateur.md).
- [Charte PDF](docs/charte-graphique.pdf), [source](docs/charte-graphique.md), [exports](docs/maquettes/).
- [Documentation technique](docs/documentation-technique.md), [MCD et UML](docs/diagrammes.md).
- [Gestion de projet](docs/gestion-projet.md), [audit initial](docs/audit-initial.md).
- [Dépôt GitHub](https://github.com/Z-RR-oss/Vite-Gourmand-ECF2026), [Trello communiqué](https://trello.com/b/mHfEGdUL/vite-gourmand-projet-ecf).

Workflow : feature -> PR develop -> recette -> main. Vérifier l'ascendance avant fusion. La documentation locale ne vaut pas publication GitHub/Trello. **URL publique à renseigner après déploiement.**
