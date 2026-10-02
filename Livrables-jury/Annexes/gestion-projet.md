# Gestion du projet

Application réalisée dans le cadre de l'ECF DWWM. Le travail est organisé par parcours et risques, avec une priorité aux règles métier, à la sécurité et à la possibilité de recréer l'application.

Tableau existant : https://trello.com/b/mHfEGdUL/vite-gourmand-projet-ecf . Relecture du 1er octobre 2026 : aucune carte en « A faire » ou « En cours » et 23 en « Terminé ». La carte des maquettes a été renommée et complétée avec les exports réellement produits et leur méthode, sans prétendre à une utilisation de Figma. Les 19 cartes livrées ont été déplacées vers « Terminé » après accord explicite de la propriétaire. Les 6 cartes du guide Trello sont conservées séparément. Preuve : [capture du tableau](captures/trello-cartes-terminees.png). Ce suivi est une mise à jour de l'état réel, pas une reconstitution de sprints historiques. Le tableau demande une connexion dans une session non authentifiée : prévoir son accès pour le jury.

## Backlog et critères d'acceptation

| Priorité | User story | Critère de recette |
|---|---|---|
| P0 | Visiteur : comparer les menus adaptés à mon événement | filtres sans rechargement, détails/conditions/allergènes |
| P0 | Client : créer mon compte et récupérer mon accès | rôle forcé, politique mot de passe, emails, token unique expirant |
| P0 | Client : commander au prix annoncé | minimum, remise, livraison, stock verrouillé, récapitulatif |
| P0 | Client : modifier ou annuler avant acceptation | contrôle propriétaire, historique, stock restauré une fois |
| P0 | Équipe : suivre la préparation et le matériel | transitions légales, contact annulation, notifications |
| P0 | Admin : lire les statistiques NoSQL | synchro MySQL -> Firebase -> graphique et tableau |
| P1 | Équipe : gérer le catalogue | menus/plats/images/allergènes/horaires |
| P1 | Admin : créer et désactiver un employé | email sans mot de passe, session révoquée |
| P1 | Client : publier mon retour après prestation | avis unique, validation avant affichage public |
| P1 | Visiteur : contacter l'entreprise | validation et réception email, retour erreur honnête |
| P1 | Jury : reproduire et comprendre le projet | SQL, README, documentation, maquettes et preuves |
| P0 livraison | Jury : consulter l'application déployée | HTTPS, comptes adaptés, Firebase réel, SMTP et cron vérifiés |

## Lots de réalisation

1. Audit du sujet original, du dépôt et du schéma actif.
2. Correctifs sécurité et règles de commandes; tests métier.
3. Statistiques Firebase, pages publiques et présentation commune.
4. SQL reproductible, documentation, maquettes et diagrammes.
5. Recette sur base isolée, revue visuelle et correction des anomalies.
6. Configuration des services réels et mise en ligne; recette externe finale.

Ces lots décrivent le travail et les points de contrôle; ils ne prétendent pas à des sprints historiques fictifs ni à des temps réellement chronométrés.

## Git

Conserver `main` stable et `develop` comme branche d'intégration. La branche de reprise est `feature/statistiques-admin`, déjà issue de develop. Les changements sont répartis en commits thématiques. Après la recette, `feature/statistiques-admin` a été fusionnée dans `develop` (`3397f6e`), puis `develop` dans `main` (`3d23419`). Les ancêtres et l’identité des arbres ont été vérifiés : les historiques sont conservés, sans écrasement. Ces deux fusions ont été réalisées directement avec Git à la demande de l’utilisatrice ; aucune nouvelle PR GitHub n’a été créée pour elles.

## Difficultés et décisions

- Architecture PHP XAMPP x86_64 / Mac arm64 : choix de Firebase REST plutôt qu'une nouvelle compilation MongoDB.
- Schéma modifié manuellement : comparaison information_schema puis import sur base vide.
- Scripts PHP historiques longs : extraction ciblée de services métier, conservation des contrôleurs compréhensibles.
- Services tiers non disponibles : configuration externalisée, tests locaux identifiés séparément de la preuve d'intégration réelle.
- Présentation : composants communs et CSS sans framework lourd, pour limiter dépendances et faciliter l'explication devant le jury.

## À publier avant la remise

Liens GitHub public, application HTTPS, Trello; checklist finale et preuves de test. Compléter la copie officielle ECF au nom de l'étudiant et y expliquer les choix techniques avec ses propres mots. La copie de l'examen et son dépôt ne sont pas automatisés.
