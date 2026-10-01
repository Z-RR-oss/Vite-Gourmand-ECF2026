# Rapport final de réalisation et de recette locale

Édition du 30 septembre 2026. **Le site est publié : https://vite-gourmandecf2026.alwaysdata.net.** MySQL et Firebase réels sont vérifiés sur alwaysdata, avec 29 contrôles en ligne. Les tâches sont installées et l’email de test est indiqué « Envoyé ». La réception est confirmée et les deux scripts réussissent dans le planificateur. L’intégration finale feature → develop → main est réalisée et publiée.

## 1. Résumé

L'existant a été conservé et consolidé : parcours métier, accès, données, présentation et livrables. Audit du dépôt et du schéma réel, lecture intégrale du transfert et du sujet Studi, migration additive de la base personnelle après sauvegarde privée. Recette sur base isolée et SMTP local, sans envoi externe. Le document de transfert décrit un état historique plus ancien que le dépôt; les exigences du sujet ont été confrontées au code réellement présent.

## 2. Fonctionnalités ajoutées

Contact avec PHPMailer, pages légales de démonstration, footer SQL partagé, catalogue public autonome, gestion des images de menus, architecture Firebase REST, synchronisation CLI et dashboard administrateur avec filtres, graphique natif et tableau alternatif. Les fonctions de compte, commandes, matériel, avis et CRUD existantes ont été conservées puis sécurisées.

## 3. Bugs corrigés

Mutations via GET, permissions dispersées, sessions employé encore actives après désactivation, réutilisation de tokens/devis, transitions arbitraires, double restitution de stock, validation de dates/nombres/notes, risque de suppression d'un menu devenu référencé. Ajout des champs email/GSM préremplis, lien d'inscription, espaces manquants sur mobile et suppression d'un footer doublé. Téléversement corrigé après erreur GD réelle : réencodage PNG au lieu de WebP indisponible. Fermeture Échap du menu corrigée lorsque le focus est sur son bouton.

## 4. Sécurité

Bootstrap/CSRF centralisés, contrôles serveur rôle/propriétaire/compte actif, PDO natif préparé, validation partagée et sorties échappées. Sessions renouvelées au login et invalidées après désactivation/reset. Tokens reset hachés, expirables et à usage unique. Uploads raster contrôlés/réencodés, noms aléatoires et exécutables interdits. Secrets locaux ignorés, exemples fictifs, erreurs techniques journalisées. Les fichiers privés testés retournent 403/404 via le virtual host. Aucun secret détecté dans le contenu destiné à Git; ce contrôle ne constitue pas un audit de tout l'historique distant.

## 5. Front-end

Identité bordeaux/sauge/crème, typographies système, sceau et illustrations SVG originales, titres éditoriaux et composants CSS communs. Navigation par rôle, formulaires, badges, filtres et panneaux de gestion harmonisés. Revue de 22 pages à trois largeurs; tests clavier et repli complémentaire à 320 px. Contrastes principaux vérifiés; aucune conformité RGAA exhaustive revendiquée.

## 6. Architecture finale

Public = contrôleurs/vues et assets; Templates = présentation commune; Config = connexions, sessions, sécurité et rôles; Services = règles, transactions, notifications et statistiques; Repositories = transport Firebase; Scripts = maintenance et tâches CLI. Quelques services POO concentrent les règles critiques; les CRUD simples restent procéduraux. Le MCD et les diagrammes UML correspondent à cette architecture réelle.

## 7. Fonctionnement du NoSQL

MySQL conserve le métier. La synchronisation agrège quotidiennement par menu puis publie un instantané par PUT HTTPS dans Firebase Realtime Database. Le dashboard effectue un GET de cet instantané; SQL n'y fournit que l'authentification et les horaires. OAuth RS256 avec compte de service, TLS vérifié et erreurs neutralisées. Aucun fichier JSON local ni repli SQL ne simule NoSQL. L'intégration distante a réussi le 29 septembre : PUT puis GET authentifiés, 5 menus et 2 jours de données fictives; lecture anonyme refusée (HTTP401).

## 8. Statistiques administrateur

Nombre de commandes non annulées, annulations séparées, convives et CA livré. CA après remise, livraison incluse, pénalités exclues; états livré/attente matériel/terminée. Période par date de création des commandes, pas date de facturation. Filtres menu et 7/30/90/365 jours, tout ou dates libres. Graphique responsive et tableau. Le dashboard de recette lit réellement Firebase : 3 commandes, 19 convives, 120 € de CA livré. Filtres menu/dates et changement de mesure du graphique vérifiés dans le navigateur.

## 9. Emails

Huit familles vérifiées par capture SMTP : bienvenue, reset, confirmation, attente matériel, retard, avis, création employé et contact. L'email employé ne contient pas le mot de passe. La configuration SMTP personnelle existante est conservée et ignorée de Git. La capture locale ne prouve pas la réception Internet. Les emails courants après commit n'ont pas de file de relance durable; le cron retard marque ses succès et réessaie les échecs, avec une fenêtre de doublon documentée en cas de crash SMTP/SQL.

## 10. Déploiement

Site publié sur alwaysdata Free (0 €), PHP web 8.2, MySQL, racine app/Public et HTTPS obligatoire. Import fictif sur base vide : 12 tables, 5 menus et 3 commandes. Clé Firebase privée transférée après accord; PUT/GET réels réussis avec le réglage IPv4 propre à l’hébergement. Les trois mots de passe de démonstration ont été remplacés et remis dans un fichier privé. SMTP STARTTLS opérationnel; test autorisé indiqué Envoyé sans rebond dans le journal #499997354. Deux tâches actives #33622/#33623; leurs scripts réussissent en CLI. Réception confirmée par la propriétaire; sorties du planificateur vérifiées avec code 0.

## 11. Livrables créés

README complet; SQL complet et données fictives; manuel PDF de 4 pages; charte PDF de 9 pages; six wireframes SVG/PNG et six mockups PNG; documentation technique, déploiement, NoSQL, gestion de projet, audit initial; [MCD conceptuel en 3 vues PDF/SVG](mcd.md), schéma relationnel/classes/cas d'utilisation/séquences Mermaid; plan de tests, captures, mesures responsive et checklist de 73 exigences. Manuel et charte générables via Scripts/generer-documents.py avec ReportLab/Pillow ; MCD via Scripts/generer-mcd.py avec ReportLab. Les wireframes documentent l'état final et ne prétendent pas avoir précédé le code.

## 12. Tests réalisés

196 contrôles automatisés réussis : 25 métier/concurrence sur MariaDB, 41 statistiques locales (transport simulé et SQLite), 126 HTTP/SQL/SMTP et 4 uploads. Deux processus réels ont été mis en concurrence sur la dernière disponibilité. Inscription, trois rôles, réinitialisation, commande/devis, workflow, annulations, retour, avis, employés, CRUD, contact et protections testés. Capture/revue visuelle des écrans; navigation mobile/filtres dynamiques/clavier; 72 mesures responsive. Sources et reproduction : [plan-tests.md](plan-tests.md).

## 13. Résultats des contrôles obligatoires

| Contrôle | Résultat |
| --- | --- |
| PHP lint | 68 fichiers hors vendor, aucune erreur; relancé après nettoyage des commentaires |
| JavaScript | Deux fichiers passent node --check; console de recette sans erreur observée |
| git diff --check | Propre avant commits |
| Import SQL | Réel et réussi dans une base jetable; douze tables; toute la suite HTTP sur ce dump |
| Fonctionnel | 196 contrôles locaux réussis, complétés par synchronisation/lecture Firebase réelles et recette navigateur |
| Liens publics | 22 liens/ressources, aucune erreur HTTP |
| Secrets HTTP | .git/HEAD403; Config/mail.local.php, dump SQL et vendor404 |
| Logs | Dernière recette sans500;503 Firebase attendu; ancienne erreur GD corrigée et retestée |
| Composer | Validation réussie, avertissement licence absente; audit sans vulnérabilité signalée |
| PDF | Toutes les pages rendues et inspectées; sources et exports présents |
| Firebase distant | PUT réussi, test de lecture code0, dashboard/filtres/graphique vérifiés, accès anonyme HTTP401 |

## 14. Fichiers ajoutés depuis la reprise

- `.gitattributes`
- `Config/auth.php`
- `Config/bootstrap.php`
- `Config/database.example.php`
- `Config/mail.example.php`
- `Config/nosql.example.php`
- `Config/nosql.php`
- `Config/security.php`
- `Public/.htaccess`
- `Public/admin-statistiques.php`
- `Public/assets/images/embleme.svg`
- `Public/assets/images/menu-classique.svg`
- `Public/assets/images/menu-fete.svg`
- `Public/assets/images/menu-vegan.svg`
- `Public/assets/images/table-partage.svg`
- `Public/assets/js/statistics.js`
- `Public/assets/uploads/.gitkeep`
- `Public/assets/uploads/.htaccess`
- `Public/cgv.php`
- `Public/contact.php`
- `Public/gerer-menu-images.php`
- `Public/mentions-legales.php`
- `Public/menus.php`
- `Repositories/StatisticsRepository.php`
- `Scripts/generer-documents.py`
- `Scripts/migrer-schema.php`
- `Scripts/preparer-livraison.py`
- `Scripts/synchroniser-statistiques.php`
- `Services/CatalogueValidation.php`
- `Services/MenuImages.php`
- `Services/OrderNotifications.php`
- `Services/OrderRules.php`
- `Services/OrderService.php`
- `Services/StatisticsService.php`
- `Templates/catalogue.php`
- `Templates/layout.php`
- `Templates/menu-list.php`
- `docs/audit-initial.md`
- `docs/captures/accueil-alwaysdata.png`
- `docs/captures/accueil-desktop.png`
- `docs/captures/admin-avis.png`
- `docs/captures/admin-commandes.png`
- `docs/captures/admin-employes.png`
- `docs/captures/admin-horaires.png`
- `docs/captures/admin-menus.png`
- `docs/captures/admin-plats.png`
- `docs/captures/admin-statistiques.png`
- `docs/captures/cgv.png`
- `docs/captures/commander.png`
- `docs/captures/contact.png`
- `docs/captures/gerer-menu-images.png`
- `docs/captures/index.png`
- `docs/captures/laisser-avis.png`
- `docs/captures/login.png`
- `docs/captures/mentions-legales.png`
- `docs/captures/menu.png`
- `docs/captures/menus.png`
- `docs/captures/mes-commandes.png`
- `docs/captures/modifier-commande.png`
- `docs/captures/mon-profil.png`
- `docs/captures/mot-de-passe-oublie.png`
- `docs/captures/register.png`
- `docs/captures/statistiques-alwaysdata.png`
- `docs/captures/statistiques-firebase-mobile.png`
- `docs/captures/statistiques-firebase-reel.png`
- `docs/charte-graphique.md`
- `docs/charte-graphique.pdf`
- `docs/checklist-finale.md`
- `docs/deploiement.md`
- `docs/diagrammes.md`
- `docs/documentation-technique.md`
- `docs/gestion-projet.md`
- `docs/manuel-utilisateur.md`
- `docs/manuel-utilisateur.pdf`
- `docs/maquettes/accueil-desktop.png`
- `docs/maquettes/accueil-mobile.png`
- `docs/maquettes/contact-desktop.png`
- `docs/maquettes/contact-mobile.png`
- `docs/maquettes/detail-menu-desktop.png`
- `docs/maquettes/detail-menu-mobile.png`
- `docs/nosql.md`
- `docs/plan-tests.md`
- `docs/rapport-final.md`
- `docs/recette-alwaysdata.json`
- `docs/recette-finale-hebergement.json`
- `docs/recette-responsive.json`
- `docs/remise-jury.md`
- `docs/wireframes/accueil-desktop.png`
- `docs/wireframes/accueil-desktop.svg`
- `docs/wireframes/accueil-mobile.png`
- `docs/wireframes/accueil-mobile.svg`
- `docs/wireframes/contact-desktop.png`
- `docs/wireframes/contact-desktop.svg`
- `docs/wireframes/contact-mobile.png`
- `docs/wireframes/contact-mobile.svg`
- `docs/wireframes/detail-menu-desktop.png`
- `docs/wireframes/detail-menu-desktop.svg`
- `docs/wireframes/detail-menu-mobile.png`
- `docs/wireframes/detail-menu-mobile.svg`
- `tests/images.php`
- `tests/integration_http.py`
- `tests/orders.php`
- `tests/smtp_capture.py`
- `tests/statistics-live.php`
- `tests/statistics.php`

## 15. Fichiers modifiés depuis la reprise

- `.gitignore`
- `Config/database.php`
- `Config/mail.php`
- `Public/admin-avis.php`
- `Public/admin-commandes.php`
- `Public/admin-employes.php`
- `Public/admin-horaires.php`
- `Public/admin-menus.php`
- `Public/admin-plats.php`
- `Public/ajouter-employe.php`
- `Public/ajouter-menu.php`
- `Public/ajouter-plat.php`
- `Public/annuler-commande-employe.php`
- `Public/api-menu.php`
- `Public/changer-avis.php`
- `Public/changer-statut.php`
- `Public/commander.php`
- `Public/desactiver-employe.php`
- `Public/gerer-menu-plats.php`
- `Public/index.php`
- `Public/laisser-avis.php`
- `Public/login.php`
- `Public/logout.php`
- `Public/menu.php`
- `Public/mes-commandes.php`
- `Public/modifier-commande.php`
- `Public/modifier-horaire.php`
- `Public/modifier-menu.php`
- `Public/modifier-plat.php`
- `Public/mon-profil.php`
- `Public/mot-de-passe-oublie.php`
- `Public/navbar.html`
- `Public/register.php`
- `Public/reinitialiser-mot-de-passe.php`
- `Public/retour-materiel.php`
- `Public/scripts.js`
- `Public/style.css`
- `Public/supprimer-commande.php`
- `Public/supprimer-menu.php`
- `Public/supprimer-plat.php`
- `Public/valider-commande.php`
- `README.md`
- `Scripts/verifier-retards-materiel.php`
- `composer.json`
- `composer.lock`
- `vite_gourmand.sql.sql`

## 16. Vérifications et remise restantes

- SMTP : réception du message autorisé confirmée par la propriétaire. Le test ne garantit pas le classement chez tous les fournisseurs.
- Cron : les deux scripts ont réussi depuis le planificateur. La tâche quotidienne #33623 a réussi à 09:00:23 le 30 septembre; la fréquence normale des statistiques est rétablie à 15 minutes.
- Remise : reporter l'URL publique et fournir les identifiants privés au jury; actualiser le Trello existant. La copie d'examen et son dépôt restent à l'étudiante.
- Avant une exploitation commerciale : remplacer les mentions pédagogiques par les données juridiques validées de l'exploitant, et limiter davantage les droits du compte SQL. Le site livré est une démonstration ECF.

Les hypothèses métier sont explicites : distance déclarative, zéro à Bordeaux; jours ouvrés lundi-vendredi sans calendrier des fériés; CA de gestion par cohorte de création. Elles sont documentées dans l'interface, le manuel et la documentation.

## 17. Lancer rapidement

Démarrer Apache/MySQL XAMPP puis ouvrir http://vite-gourmand.local. Pour une installation neuve : composer install, créer une base vide, importer vite_gourmand.sql.sql, renseigner Config/*.local.php depuis les exemples, puis php -S 127.0.0.1:8080 -t Public. Ne jamais réimporter le dump destructif sur les données personnelles. Les comptes de démonstration sont fournis dans le manuel/README avec Demo-Vg2026! et concernent le dump de démonstration.

## 18. Checklist finale et Git

La [checklist complète](checklist-finale.md) contient 73 lignes avec exigence, statut OK/BLOQUÉE, fichiers, test et commentaire. Les 73 exigences sont validées dans le périmètre de leurs contrôles; aucune donnée Firebase simulée n'est présentée comme une réussite d'intégration.

Commits applicatifs sur feature/statistiques-admin : 6310334 (métier/sécurité/gestion), 42ccd6e (interface/pages publiques), d8d9015 (Firebase/statistiques), suivis du commit de documentation et preuves. Le commit 3db42ed raccorde l’historique main, initialement séparé, à la branche de fonctionnalité. L’arbre de fichiers est strictement identique avant/après ce raccordement; les deux historiques sont préservés. La fusion de `feature/statistiques-admin` dans `develop` est publiée sous `3397f6e`, puis celle de `develop` dans `main` sous `3d23419`. Les arbres de fichiers de ces deux fusions sont identiques à celui de `6f49c18`, version ayant passé les 196 tests locaux et les 34 contrôles publics. Le sujet Studi demande ces fusions, sans exiger explicitement de pull request ; les PR figurent dans le prompt de travail initial.

## Amélioration du code et des visuels — 30 septembre 2026

Ajout de deux visuels culinaires générés pour les menus Vegan et Noël, optimisés en JPEG et associés aux menus par une migration ciblée relançable. Le dump neuf contient également les nouveaux chemins. Les originaux restent conservés hors Git.

Le PHP applicatif est harmonisé, les blocs d’affichage historiques sont simplifiés et les contrôles d’authentification redondants sont retirés lorsque la garde de rôle les réalise déjà. Des commentaires expliquent les règles de prix, verrous de stock, sessions et requêtes asynchrones. Le CSS est réparti en 12 modules puis assemblé en une ressource ; les règles de formatage et les commandes d’entretien sont documentées dans `conventions-code.md`. Les 196 contrôles automatisés sont repassés avec succès ; les nouveaux visuels sont vérifiés sur ordinateur et mobile.

Ces améliorations sont publiées sur alwaysdata et dans `main` après intégration de `feature/statistiques-admin` (commit applicatif `2e80ccb`). Les 34 contrôles en ligne supplémentaires sont tous réussis ; les deux JPEG et le CSS servis correspondent aux fichiers testés.


## Complément du 1er octobre : finalisation et limites de remise

Les formulaires affichent leurs champs obligatoires et conservent les coordonnées/avis après erreur ; le message serveur d'un avis rejeté est désormais visible. Le focus rejoint les erreurs, les champs ont des bordures plus contrastées et l'en-tête accepte le texte agrandi. Ajout du plan du site et de l'aide à l'accessibilité. Dernière recette : 473 contrôles HTML/validation, 126 HTTP/SQL/SMTP, 71 fichiers PHP valides et 48 mesures de présentation sans débordement du document. Les suites métier/statistiques/images ont également réussi sur la base isolée de cette finalisation.

Les guides `preparation-copie-ecf.md` et `parcours-jury.md` préparent la remise et la démonstration. Le reclassement des cartes Trello attend une autorisation explicite après refus du contrôle automatique ; l'accès du jury au tableau reste à vérifier. L'essai VoiceOver, autorisé, est bloqué par les permissions Mac. Le contrôle complet RGAA n'est pas terminé. La copie officielle Studi et son dépôt restent à effectuer par l'étudiante. Ces éléments empêchent de présenter la préparation à l'examen comme intégralement terminée.

Publication de ces ajustements confirmée sur alwaysdata (version applicative `3e02e2c`) : 38 contrôles HTTPS/authentification/Firebase réussis. Preuve dans `recette-accessibilite-en-ligne.json`.
