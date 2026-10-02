# Préparer la copie officielle ECF

Ce document est une aide à la rédaction, pas la copie officielle Studi. Le modèle Word/Excel officiel n'a pas été trouvé parmi les fichiers accessibles. Télécharger ce modèle depuis l'espace de formation, compléter son identité et ses réponses, puis relire les explications ci-dessous avec ses propres mots.

Nom de fichier demandé dans le sujet : `ECF_TPDeveloppeurWebEtWebMobile_copiearendre_NOM_Prenom` (conserver l'extension du modèle).

## Liens à reporter

- Application : https://vite-gourmandecf2026.alwaysdata.net/
- Dépôt public, branche stable : https://github.com/Z-RR-oss/Vite-Gourmand-ECF2026/tree/main
- Gestion de projet : https://trello.com/b/mHfEGdUL/vite-gourmand-projet-ecf

Le lien Trello demande actuellement un compte pour être consulté dans une session non connectée. Prévoir un accès au tableau pour le jury ou fournir en complément la documentation de gestion exportée. Ne pas considérer la présence d'un lien comme une preuve d'accès.

## Éléments techniques à expliquer

| Sujet à expliquer | Faits du projet et documents |
| --- | --- |
| Besoin | Catalogue de menus, commande et suivi de prestations pour Julie et José ; rôles visiteur, client, employé et administrateur. |
| Choix techniques | PHP/PDO/MariaDB conserve l'environnement existant. JavaScript natif gère les filtres. Firebase REST apporte le stockage NoSQL des agrégats sans extension serveur supplémentaire. Voir `documentation-technique.md`. |
| Données | Dix entités conceptuelles, douze tables SQL avec deux tables de liaison ; MCD distinct du schéma relationnel. Voir `mcd.md` et `diagrammes.md`. |
| Sécurité | Requêtes préparées, échappement des sorties, CSRF, autorisations côté serveur, mots de passe hachés, jetons de reset expirables, secrets hors dépôt et hors racine HTTP. |
| Règles métier | Minimum de convives, remise dès minimum + 5, livraison hors Bordeaux, stock transactionnel, transitions de statut, matériel et avis après prestation. Voir `plan-tests.md`. |
| Interface | Maquettes ordinateur/mobile ; approche responsive issue d'une base desktop, sans prétendre à une conception mobile first initiale. Voir `charte-graphique.md` et `accessibilite.md`. |
| Déploiement | Alwaysdata, racine `Public`, configuration privée, MySQL et Firebase réels, tâches planifiées et SMTP. Voir `deploiement.md`. |
| Gestion | Branches de travail, intégration dans develop puis main après tests ; suivi Trello et explication honnête des mises à jour rétrospectives. Voir `gestion-projet.md`. |
| Validation | Suites métier, statistiques, HTTP/SQL/SMTP, images, HTML et recette navigateur ; distinguer les transports simulés des intégrations réelles. |

## Pièces et accès

Utiliser la liste de [remise-jury.md](remise-jury.md). Le manuel et la charte sont en PDF ; le MCD dispose également d'un PDF et de sources SVG. La documentation technique contient aussi les diagrammes de classes, cas d'utilisation et séquences.

Le manuel public documente les comptes du dump local. Les mots de passe du site hébergé sont différents : transmettre séparément le fichier privé local `var/deployment/acces-site-alwaysdata.md`. Ne jamais joindre les clés SSH/Firebase ni les fichiers de configuration privée.

## Avant de déposer

1. Compléter le modèle officiel et vérifier le nom du fichier.
2. Vérifier les trois liens depuis un navigateur sans les sessions du propriétaire.
3. Joindre les documents demandés et fournir les accès de démonstration au jury par un canal privé.
4. Relire ses explications et vérifier qu'on peut montrer le code correspondant.
5. Déposer dans l'espace Studi indiqué et conserver la confirmation de dépôt.

Le dépôt officiel et la préparation de l'examen final du titre relèvent des consignes de l'organisme de formation ; ce projet ne permet pas de présumer que toutes les pièces administratives de l'examen sont réunies.
