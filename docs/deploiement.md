# Installation et déploiement

## État de livraison

Le code est préparé pour un hébergement PHP/MariaDB. Au 29 septembre 2026, le projet Firebase `vite-gourmand-ecf2026` et sa Realtime Database en Belgique sont créés; les règles publiques de lecture et d'écriture sont fermées. La clé serveur est configurée et la synchronisation/lecture réelle a réussi sur les données de démonstration. Le compte alwaysdata `vite-gourmandecf2026` existe à Paris, mais affiche Medium (240 € HT/an). Le propriétaire a demandé son retour à Free : la tentative de validation a redirigé vers la connexion, donc le changement n'est pas encore confirmé. Reconnexion nécessaire avant de reprendre le déploiement. **Aucune URL publique de production n'est disponible et aucun déploiement réel n'est revendiqué.** Le lien local est `http://vite-gourmand.local`.

## Prérequis

PHP 8.2 ou supérieur avec PDO/MySQL, mbstring, cURL, OpenSSL, GD, sessions et JSON; MySQL 8 ou MariaDB 10.4+; Composer 2; serveur Apache 2.4 ou Nginx/PHP-FPM; certificat HTTPS; sorties réseau HTTPS/443 et SMTP autorisées. Les tests ont utilisé PHP 8.2.4 et MariaDB XAMPP.

Le serveur SQL doit utiliser Europe/Paris, comme PHP. `DB_TIMEZONE=Europe/Paris` peut fixer la session lorsque les tables de fuseaux MySQL sont disponibles. Sinon configurer le fuseau système du serveur SQL; ne pas utiliser un simple décalage fixe qui serait faux lors des changements d'heure. Vérifier les dates d'un historique et `SELECT @@session.time_zone, NOW()` après installation.

## Installation sur base vide

Depuis la racine du dépôt :

```sh
composer install --no-dev --prefer-dist --optimize-autoloader
mysql -u administrateur -p -e "CREATE DATABASE vite_gourmand CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci"
mysql -u administrateur -p vite_gourmand < vite_gourmand.sql.sql
cp Config/database.example.php Config/database.local.php
cp Config/mail.example.php Config/mail.local.php
cp Config/nosql.example.php Config/nosql.local.php
```

Sur le Mac existant, `php composer.phar install` remplace `composer install`. Le dump contient des DROP TABLE : l'import est réservé à une base vide ou jetable; ne pas le réimporter dans une base métier à conserver. Les mises à niveau non destructives utilisent `php Scripts/migrer-schema.php` après sauvegarde.

Configurer localement les trois fichiers; ils sont ignorés par Git. Les variables d'environnement DB_*, SMTP_*, APP_URL et FIREBASE_* sont prioritaires. Ne jamais publier la clé Firebase dans Public ni la commiter. Le compte SQL de l'application n'a besoin que des droits SELECT/INSERT/UPDATE/DELETE sur sa propre base; l'import/migration est effectué par un compte distinct.

## Racine HTTP

Le DocumentRoot doit être **Public/**, jamais la racine du dépôt. Exemple Apache à adapter aux chemins de l'hébergement :

```apache
<VirtualHost *:80>
    ServerName vite-gourmand.local
    DocumentRoot /chemin/Vite-Gourmand-ECF2026/Public
    <Directory /chemin/Vite-Gourmand-ECF2026/Public>
        Require all granted
        AllowOverride All
        Options -Indexes
        DirectoryIndex index.php
    </Directory>
</VirtualHost>
```

En production, activer le virtual host HTTPS avec le certificat fourni par l'hébergeur et rediriger HTTP vers HTTPS. Le cookie de session reçoit Secure lorsque PHP détecte HTTPS; si TLS se termine sur un proxy, configurer ce signal côté serveur de confiance. Ne pas faire confiance arbitrairement à un en-tête X-Forwarded-Proto envoyé par le client.

En local sans virtual host : `php -S 127.0.0.1:8080 -t Public`, puis ouvrir `http://127.0.0.1:8080`. Ce serveur PHP de développement ne sert pas une production publique.

## Permissions et fichiers

Le compte du serveur web doit lire le code, vendor et la configuration; seuls `Public/assets/uploads/` et le répertoire de sessions nécessitent des écritures web. Les uploads sont des images raster réencodées avec un nom aléatoire. Les fichiers supprimés d'une galerie sont conservés sur disque pour ne pas effacer une image partagée; prévoir un nettoyage des fichiers orphelins après sauvegarde. Le répertoire uploads et les secrets ne doivent pas être accessibles en écriture à tous (pas de chmod 777).

Les scripts de statistiques utilisent le répertoire temporaire du système pour leur verrou. Les journaux et sauvegardes restent hors Public; protéger leurs accès et appliquer une rotation.

## SMTP

Configurer un compte d'envoi autorisé, son mot de passe d'application et le port 587/STARTTLS ou 465/SMTPS. `SMTP_FROM_EMAIL` est une adresse autorisée par ce compte; `CONTACT_EMAIL` est l'adresse de réception de l'entreprise. APP_URL est l'URL HTTPS canonique utilisée dans les emails, sans slash final. Le formulaire contact emploie Reply-To avec l'adresse de l'expéditeur sans usurper le From.

Vérifier la réception réelle avec une adresse de recette contrôlée : bienvenue, reset, confirmation de commande, début d'attente matériel, rappel du retard, invitation à donner un avis, création employé et contact. La capture SMTP locale valide la génération et l'acceptation des messages; elle ne prouve ni délivrabilité iCloud ni placement en boîte de réception. La configuration SMTP personnelle déjà présente a été conservée.

## Firebase

Suivre `nosql.md` : créer une Realtime Database, règles fermées au public, compte de service autorisé, clé privée hors DocumentRoot, URL HTTPS dans la configuration serveur. Exécuter la synchronisation avant de visiter le dashboard. Un service indisponible produit une erreur 503 explicite, jamais des chiffres SQL présentés comme des chiffres NoSQL.

## Tâches planifiées

Adapter le chemin de PHP et le chemin du dépôt; fournir les mêmes variables de configuration au cron ou utiliser les fichiers locaux protégés.

```cron
0 9 * * * /usr/bin/php /chemin/projet/Scripts/verifier-retards-materiel.php >> /chemin/prive/materiel.log 2>&1
*/15 * * * * /usr/bin/php /chemin/projet/Scripts/synchroniser-statistiques.php >> /chemin/prive/statistiques.log 2>&1
```

Ces lignes sont des exemples à installer dans le scheduler de l'hébergement; aucune tâche n'a été installée sur le Mac. Le rappel utilise un verrou MySQL et marque les notifications acceptées; un échec SMTP est réessayé. Un crash entre acceptation SMTP et commit SQL peut encore provoquer un doublon : SQL et SMTP ne partagent pas une transaction distribuée.

## Avant exposition publique

1. Sauvegarder la base et les images; tester la restauration.
2. Remplacer les comptes de démonstration ou leurs mots de passe. Le compte administrateur initial se crée par SQL/CLI, jamais par inscription publique.
3. Compléter les mentions légales/CGV avec les vraies données validées de l'exploitant; les pages actuelles sont explicitement pédagogiques.
4. Vérifier que `/.git/HEAD`, `/Config/mail.local.php`, `/vite_gourmand.sql.sql` et `/vendor/` répondent 404/403.
5. Désactiver display_errors, activer logs privés et rotation, quotas, supervision HTTP et SMTP; limiter les tentatives d'authentification au niveau serveur/proxy.
6. Exécuter la recette complète sur une base dédiée, puis les tests de fumée en production sans données artificielles non autorisées.
7. Reporter l'URL publique et les preuves de Firebase/SMTP/cron dans `checklist-finale.md` et dans la copie ECF.

## Retour arrière

Conserver un tag/commit de la version précédente et une sauvegarde chiffrée de la base avant migration. En cas d'échec, couper les nouvelles écritures, restaurer code/base/images cohérents et vérifier les commandes avant réouverture. Ne pas remplacer le dump de démonstration par un export de production versionné.
