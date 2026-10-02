# Documentation technique

## Choix et environnement

Le projet conserve PHP 8.2/PDO/MariaDB et les pages existantes pour rester compréhensible en DWWM. Les transactions InnoDB répondent aux besoins de stock, d'historique et de cohérence. JavaScript natif suffit pour fetch et la navigation; aucun framework ni CDN n'est imposé. PHPMailer conserve l'envoi SMTP existant. Composer verrouille les dépendances.

Firebase Realtime Database via REST HTTPS satisfait le besoin NoSQL sans extension native. L'ancienne piste PECL MongoDB n'est pas reprise, compte tenu du mélange PHP XAMPP x86_64 et Mac arm64. Le code utilise cURL/OpenSSL déjà disponibles. Les détails et références officielles sont dans [nosql.md](nosql.md).

Environnement vérifié : macOS, XAMPP, PHP 8.2.4, MariaDB, virtual host Public/. Base personnelle conservée; migration additive après sauvegarde privée. Les mutations de recette utilisent des bases jetables nommées `_test_`, dont `vite_gourmand_test_quality_20261002` pour la revue du 2 octobre, avec SMTP loopback. Les captures sont pédagogiques, pas des données de production.

## Couches et responsabilités

| Dossier | Responsabilité |
| --- | --- |
| Config | Bootstrap, validation globale, sessions, authentification, PDO, SMTP et Firebase |
| Public | Contrôleurs HTTP et vues, CSS/JS et images servies |
| Controllers | Contrôleurs communs de création/modification des menus et plats |
| Templates | Header/footer, catalogue, formulaires partagés et page d’erreur |
| Services/OrderRules.php | Prix, dates, jours ouvrés, transitions |
| Services/OrderService.php | Transactions création/modification/annulation/suivi/retour |
| Services/OrderNotifications.php | Emails après mutation validée |
| Services/CatalogueValidation.php | Validation des types, bornes, champs et allergènes des menus/plats |
| Services/CatalogueService.php | Persistance du catalogue, transaction des plats et associations |
| Services/BusinessCalendar.php | Calendrier des jours fériés nationaux pour Bordeaux |
| Services/MenuImages.php | Contrôle et réencodage des uploads |
| Services/StatisticsService.php | Agrégation SQL, instantané et filtres des agrégats |
| Repositories/StatisticsRepository.php | Transport REST Firebase et authentification OAuth |
| Scripts | Migration additive et tâches CLI |

Il s'agit d'une évolution progressive, pas d'un MVC intégral. Les formulaires menus/plats ont des contrôleurs et vues communs ; certaines autres pages restent procédurales. Les règles risquées sont centralisées. Voir le [MCD conceptuel](mcd.md), son [PDF](../MCD/mcd-vite-gourmand.pdf) et les [diagrammes relationnel et UML](diagrammes.md).

## Modèle SQL

Douze tables InnoDB utf8mb4, identifiants entiers, prix DECIMAL, clés étrangères et pivots composites. Email unique, nom d'allergène unique, jour unique, avis unique par commande et token reset haché unique. Index sur statut, client, menu et date de création pour les lectures. Le dump contient CREATE/INSERT complets et données fictives. Le diagramme reprend les associations réelles.

Les données d'une commande conservent le prix, frais/remise et adresse/prestation au moment de la commande. Le menu référencé est conservé ou désactivé. Les libellés de menu/profil restent liés aux données actuelles : cette application pédagogique n'est pas un système d'archivage de factures.

## Transactions et concurrence

La création verrouille le menu avec SELECT FOR UPDATE, valide actif/stock/délai/prix, insère commande et historique, décrémente le stock puis commit. Le devis est conservé en session avec token aléatoire et expiration; la confirmation revalide le prix. Deux processus concurrents sur le dernier stock produisent une réussite et un rejet.

Modification, annulation et retour verrouillent la commande. L'annulation n'efface rien; une deuxième annulation ne restaure jamais deux fois le stock. Annulée et terminée sont finales. Le passage livré -> terminée est permis sans prêt; après attente matériel, seul le retour clôture. Historique écrit dans la même transaction.

Les emails surviennent après commit : une erreur SMTP ne doit pas annuler une commande déjà enregistrée. Les notifications courantes n'ont pas de file de relance durable. Le rappel retard possède un marquage et une reprise; un crash entre acceptation SMTP et commit reste susceptible de doublon. Pour industrialiser : outbox transactionnelle et worker idempotent.

## Sécurité

PDO sans émulation et requêtes préparées pour les valeurs; identifiants structurels contrôlés. e()/htmlspecialchars pour HTML; textContent côté JavaScript. Validation métier serveur en plus des attributs HTML. Contrôle des IDs/propriétaires et rôles à chaque action, sans confiance dans les champs cachés.

Tous les POST web passent par vérification CSRF centralisée; les mutations historiques via GET deviennent POST. La connexion renouvelle l'identifiant de session et le jeton CSRF. Cookies HttpOnly/SameSite=Lax et Secure sous HTTPS. L'authentification relit le compte actif et son empreinte de mot de passe : désactivation/reset révoquent les anciennes sessions.

Mot de passe hashé; reset aléatoire, seul SHA-256 conservé, une heure et usage unique sous verrou. Réponse neutre sur demande de reset. Secrets dans fichiers ignorés/variables; pas dans Public, JS, captures ou logs. display_errors désactivé, message technique générique et logs privés. En-têtes anti-sniff et anti-frame.

Images : contrôle upload réel, taille 5 Mo, dimensions 16 MP, MIME et décodage GD, réencodage PNG et nom aléatoire. Pas de SVG uploadé ni de nom utilisateur exécuté. Fichiers orphelins nettoyables après sauvegarde. Limites de tentatives d'authentification à déployer au proxy/serveur, absence de MFA et d'audit de pénétration exhaustif documentées.

## Emails et automatisation

PHPMailer : bienvenue, reset, confirmation, début de prêt, retard, invitation avis, création employé sans mot de passe, contact avec Reply-To. Configuration SMTP TLS, timeout et erreurs journalisées sans secrets. Le script matériel utilise un verrou MySQL, relit la commande verrouillée et marque uniquement une notification acceptée.

La synchronisation NoSQL remplace atomiquement un instantané agrégé; ni nom, ni email, ni adresse client n'y figurent. Le dashboard ne calcule pas ses statistiques depuis MySQL : SQL n'y sert qu'à l'identité et au footer. Firebase absent produit HTTP 503. [Configuration et cron](deploiement.md).

## Interface, accessibilité et limites

Tokens CSS partagés, typographies système sans chargement tiers, illustrations SVG originales, mise en page mobile/tablette/desktop. Labels, textes alternatifs, lang fr, h1 unique, focus visible, lien d'évitement, tableaux avec entêtes, graphique avec chiffres/tableau. Les erreurs et statuts sont nommés en texte.

Recette pragmatique documentée dans [plan-tests.md](plan-tests.md), sans revendication de conformité RGAA totale. Distance déclarative contrôlée avec la ville, calendrier national des fériés pour les jours ouvrés, agrégation par création de commande et absence de comptabilité fiscale sont des choix explicites. La mise en ligne Alwaysdata et la recette Firebase réelle sont documentées dans le [rapport final](rapport-final.md) et les fichiers de recette d'hébergement.
