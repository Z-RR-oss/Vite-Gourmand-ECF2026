# Script oral — Vite & Gourmand

Durée indicative : environ 15 à 17 minutes, dont 2 minutes de démonstration. Les 3 annexes MCD servent aux questions. Adapte les phrases à ta façon de parler et aux consignes de ton examen. Ce script décrit les faits vérifiés du projet.

## 1. Vite & Gourmand

Durée indicative : 30 secondes.

Bonjour. Je vous présente Vite & Gourmand, une application web de traiteur réalisée dans le cadre de l’ECF. Elle permet de découvrir les menus, de préparer une commande et de suivre sa prise en charge. Je vais présenter le besoin, l’interface, les choix techniques et les vérifications réalisées. Le site présenté est une démonstration pédagogique avec des données fictives.
Source : sujet Studi et README.md.

## 2. Le besoin de Julie et José

Durée indicative : 50 secondes.

Julie et José dirigent un traiteur bordelais depuis vingt-cinq ans. Le besoin du sujet est de rendre les menus plus visibles et de permettre la commande depuis le site. Le visiteur consulte sans compte. Le client s’identifie pour commander et retrouver son historique. L’employé gère les opérations et l’administrateur dispose en plus de la gestion des employés et des statistiques. Cette séparation détermine les écrans visibles, mais surtout les autorisations contrôlées par PHP.
Sources : Sujet Studi.pdf, Config/auth.php, Templates/layout.php.

## 3. Le parcours client

Durée indicative : 55 secondes.

Le parcours commence par le catalogue. Les filtres permettent de sélectionner un thème, un régime alimentaire, un budget ou un nombre de convives. La fiche détaille le prix du forfait, le minimum de personnes, la composition, les allergènes et les conditions. Après connexion, le client prépare sa commande puis vérifie le récapitulatif. Le serveur recalcule toujours le prix. Le suivi et l’avis restent associés au bon compte. Le paiement en ligne ne fait pas partie du périmètre livré.
Sources : Templates/menu-list.php, Public/menu.php, Public/commander.php, docs/parcours-jury.md. Capture : docs/captures/catalogue-nouveaux-visuels-en-ligne.png.

## 4. Wireframes et mockups

Durée indicative : 45 secondes.

Les wireframes décrivent la structure des pages. Les mockups montrent leur présentation visuelle avec la palette bordeaux, sauge et crème. Trois écrans sont documentés en ordinateur et en mobile : accueil, détail d’un menu et contact. Les exports d’origine documentent l’interface après son développement. Les versions Figma ont été ajoutées ensuite pour disposer de calques modifiables. Je présente donc ces livrables comme une documentation de la solution finale, sans inventer une phase Figma antérieure au code.
Sources : Livrables-jury/Wireframes, Livrables-jury/Mockups, docs/charte-graphique.md.

## 5. Responsive et accessibilité

Durée indicative : 60 secondes.

Le CSS reprend une base pensée d’abord pour ordinateur, puis adaptée aux petits écrans. Je parle donc de responsive et non d’une démarche mobile first que le projet n’a pas suivie. Les colonnes se replient et le menu change de présentation. Les tests couvrent notamment 320, 390, 768 et 1440 pixels. Les dernières corrections renforcent les bordures des champs, signalent les champs obligatoires et déplacent le focus sur les erreurs. Les parcours au clavier ont été contrôlés. En revanche, le test VoiceOver et l’audit complet RGAA ne sont pas validés. Je n’annonce donc aucun taux de conformité.
Sources : docs/accessibilite.md, docs/recette-accessibilite-responsive.json, Public/assets/css.

## 6. Une architecture PHP progressive

Durée indicative : 60 secondes.

Le navigateur reçoit du HTML, du CSS et du JavaScript. Les pages Public traitent les requêtes et utilisent des gabarits partagés. Les règles les plus sensibles sont regroupées dans des services, par exemple les commandes, leurs prix et les notifications. PDO dialogue avec MySQL. Un repository séparé prend en charge les appels REST vers Firebase. Le projet conserve des pages procédurales pour les CRUD simples. Il ne s’agit donc pas d’un framework MVC complet. Ce choix permet de centraliser les règles critiques sans reconstruire toute la base existante.
Sources : docs/documentation-technique.md, Config, Templates, Services, Repositories.

## 7. Le modèle de données

Durée indicative : 70 secondes.

Le MCD décrit les objets métier et leurs associations avant de parler de tables. Une commande appartient à un utilisateur et porte sur un menu. Une commande peut recevoir au maximum un avis. Le catalogue réutilise les plats et leurs allergènes. Le modèle comprend dix entités. Les deux associations plusieurs-à-plusieurs deviennent des tables de liaison, ce qui conduit à douze tables SQL. Les clés étrangères protègent la cohérence des liens. L’unicité de l’avis par commande est aussi garantie par la base. Les trois vues complètes du MCD sont placées en annexe pour répondre aux questions.
Sources : Livrables-jury/MCD/mcd-vite-gourmand.pdf, docs/mcd.md, vite_gourmand.sql.sql.

## 8. Un prix recalculé côté serveur

Durée indicative : 65 secondes.

Voici un exemple calculé avec la règle réellement implémentée. Le menu coûte 120 euros pour quatre personnes. Pour neuf convives, le montant des repas est de 270 euros. Le nombre de personnes atteint le minimum plus cinq : la remise de dix pour cent s’applique donc aux repas, soit 27 euros. Pour vingt kilomètres hors Bordeaux, la livraison vaut cinq euros plus cinquante-neuf centimes par kilomètre, soit 16 euros 80. Le total est de 259 euros 80. Cette valeur provient du serveur. Modifier le récapitulatif dans le navigateur ne modifie pas la règle utilisée pour enregistrer la commande.
Source : Services/OrderRules.php, fonction calculerPrixCommande. Exemple pédagogique calculé : 120 × 9 / 4 − 27 + 5 + 0,59 × 20.

## 9. La réservation du dernier stock

Durée indicative : 70 secondes.

Le cas sensible est celui de deux clients qui confirment en même temps la dernière disponibilité. Le service ouvre une transaction et verrouille la ligne du menu. Il revérifie le stock, le devis et les conditions, puis crée la commande, met à jour le stock et inscrit le premier événement dans l’historique. Si une étape échoue, la transaction revient en arrière. Le deuxième client lit alors le nouvel état et reçoit un refus si le stock est épuisé. Le test a lancé deux processus concurrents pour vérifier qu’une seule réservation réussit. Les emails partent après la validation de la transaction.
Sources : Services/OrderService.php, tests/orders.php, docs/plan-tests.md.

## 10. Les contrôles de sécurité

Durée indicative : 60 secondes.

Les contrôles de sécurité se font côté serveur, même si un bouton est caché dans l’interface. Le rôle et le propriétaire d’une commande sont vérifiés avant l’action. Les mutations utilisent POST avec un jeton CSRF. PDO prépare les requêtes et les sorties HTML sont échappées pour éviter qu’un contenu saisi devienne du code. Les mots de passe sont hachés. Les liens de réinitialisation utilisent des jetons à usage unique et expirables. Les secrets de configuration et la clé Firebase restent hors de la racine publique et du dépôt. La recette vérifie également les accès interdits et les anciens mots de passe de démonstration.
Sources : Config/security.php, Config/auth.php, Config/bootstrap.php, tests/integration_http.py.

## 11. MySQL et Firebase

Durée indicative : 65 secondes.

MySQL conserve les commandes et les autres données métier. Une tâche planifiée construit des agrégats par jour et par menu, puis les publie dans Firebase Realtime Database. Le tableau de bord administrateur lit cet instantané par l’API REST. Les chiffres ne proviennent donc pas d’un fichier JSON local. Le navigateur ne reçoit pas la clé du compte de service. La capture illustre les données fictives de recette : trois commandes, dix-neuf convives et 120 euros de chiffre d’affaires livré. La date de synchronisation permet de connaître la fraîcheur des chiffres. Une panne Firebase produit une erreur explicite, sans inventer de statistiques de remplacement.
Sources : docs/nosql.md, Services/StatisticsService.php, Repositories/StatisticsRepository.php. Capture : docs/captures/statistiques-firebase-reel.png, données de recette de septembre 2026.

## 12. Les preuves de fonctionnement

Durée indicative : 55 secondes.

La recette utilise une base MariaDB isolée et une capture SMTP locale, afin de ne pas modifier les données personnelles ni envoyer des messages à des tiers. Les 196 contrôles couvrent les règles métier, les statistiques, les parcours HTTP et les images. Une suite complémentaire réalise 473 vérifications ciblées du HTML et des erreurs de formulaire. Après le déploiement, 38 contrôles vérifient les pages HTTPS, les trois rôles, Firebase et les chemins privés. Ces nombres correspondent à des assertions, pas à un pourcentage de couverture. Ils ne remplacent pas un audit complet de sécurité ou d’accessibilité.
Sources : docs/plan-tests.md, docs/recette-accessibilite-en-ligne.json, tests/. Résultats de la recette du 1er octobre 2026.

## 13. Déploiement et suivi du projet

Durée indicative : 55 secondes.

L’application est publiée sur alwaysdata, avec la racine web limitée au dossier Public. Les configurations privées restent séparées. Firebase contient les agrégats et ses accès publics sont fermés. Les tâches planifiées synchronisent les statistiques toutes les quinze minutes et vérifient les retards de matériel chaque jour. Le message de recette SMTP a été reçu. Le code est conservé sur GitHub avec des branches de travail intégrées dans develop puis main. Trello présente désormais les vingt-trois cartes livrées dans Terminé. Cette mise à jour du tableau est rétrospective et ne reconstitue pas artificiellement des sprints.
Sources : docs/deploiement.md, docs/gestion-projet.md, docs/plan-tests.md.

## 14. Démonstration de l’application

Durée indicative : 120 secondes.

Je passe maintenant à la démonstration. Je commence par filtrer le catalogue et ouvrir un menu. Je montre les informations nécessaires pour décider de commander. Ensuite, avec le compte client de démonstration, je prépare une commande et j’explique son récapitulatif sans déclencher un email réel pendant l’oral. Enfin, je montre le suivi côté employé et les statistiques réservées à l’administrateur. Si le réseau est indisponible, les captures et les annexes permettent d’expliquer les mêmes parcours. Les mots de passe ne figurent pas sur les diapositives : les identifiants du site sont remis séparément.
Source : docs/parcours-jury.md. Site : https://vite-gourmandecf2026.alwaysdata.net/.

## 15. Bilan et pistes d’amélioration

Durée indicative : 65 secondes.

Le projet couvre le parcours de commande et la gestion attendus, avec une application déployée, un stockage SQL, des statistiques NoSQL et des preuves de recette. Les difficultés les plus intéressantes ont été la cohérence du stock, les transitions de commande et l’intégration Firebase. Plusieurs limites restent identifiées : terminer l’évaluation d’accessibilité, reprendre progressivement le CSS dans une démarche mobile first et renforcer la fiabilité des emails avec une file d’envoi durable. Pour la remise, il reste également à finaliser la copie officielle et à vérifier l’accès du jury aux liens. Je distingue ces actions de remise des fonctionnalités déjà livrées.
Sources : docs/rapport-final.md, docs/accessibilite.md, docs/remise-jury.md.

## 16. Questions et ressources

Durée indicative : 25 secondes.

Je vous remercie pour votre attention. Je peux maintenant détailler un choix technique, une cardinalité du modèle de données ou un contrôle de sécurité. Les trois diapositives suivantes contiennent les vues complètes du MCD. Le dossier remis regroupe également les wireframes, les mockups, la charte, le manuel, les documents techniques et les résultats de tests.
Ressources : https://github.com/Z-RR-oss/Vite-Gourmand-ECF2026 ; https://vite-gourmandecf2026.alwaysdata.net/ ; Livrables-jury/.

## 17. Annexe MCD : catalogue

Annexe, hors minutage.

Annexe à afficher si le jury souhaite examiner cette partie du modèle. Les cardinalités se lisent du côté de l’entité. Le PDF vectoriel reste disponible pour zoomer. Source : Livrables-jury/MCD/mcd-vite-gourmand.pdf et docs/mcd.md.

## 18. Annexe MCD : commandes

Annexe, hors minutage.

Annexe à afficher si le jury souhaite examiner cette partie du modèle. Les cardinalités se lisent du côté de l’entité. Le PDF vectoriel reste disponible pour zoomer. Source : Livrables-jury/MCD/mcd-vite-gourmand.pdf et docs/mcd.md.

## 19. Annexe MCD : accès et horaires

Annexe, hors minutage.

Annexe à afficher si le jury souhaite examiner cette partie du modèle. Les cardinalités se lisent du côté de l’entité. Le PDF vectoriel reste disponible pour zoomer. Source : Livrables-jury/MCD/mcd-vite-gourmand.pdf et docs/mcd.md.

## Préparation avant l’oral

Ouvrir le diaporama en mode Présentateur et répéter à voix haute. Préparer les comptes de démonstration sans afficher leurs mots de passe. Ouvrir le site et le dépôt avant la présentation. Conserver le MCD PDF et les captures pour une démonstration sans réseau. Vérifier les liens destinés au jury et personnaliser la copie officielle. Ne pas annoncer une conformité RGAA exhaustive ni une phase de conception Figma antérieure au développement.
