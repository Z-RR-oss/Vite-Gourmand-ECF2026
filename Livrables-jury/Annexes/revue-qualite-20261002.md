# Revue de qualité du 2 octobre 2026

Cette revue complète les recettes historiques. Elle porte sur le code et les preuves techniques ; elle ne garantit aucune note ni validation du jury.

## Défauts corrigés

| Problème observé | Correction et preuve |
| --- | --- |
| Création et modification du catalogue dupliquées | Quatre routes minces, deux contrôleurs et deux vues partagés ; `CatalogueService` sépare la persistance de l’affichage. |
| Validations inégales et conversion silencieuse des prix | Prix décimaux bornés, deux décimales maximum, entiers stricts, longueurs et types contrôlés, allergènes vérifiés dans SQL. |
| Risque de plat partiellement enregistré | Plat et associations dans une transaction ; test d’échec SQL injecté vérifiant le rollback intégral. |
| Erreurs difficiles à corriger | Saisies conservées, résumé avec liens vers les champs, `aria-invalid` et description associée ; focus du résumé puis navigation clavier vérifiés. |
| Réponses HTTP et pages d’erreur inégales | 400 pour les identifiants mal formés, 404 pour les ressources absentes, 405 pour les méthodes interdites, 422 pour les saisies rejetées ; document HTML complet pour les erreurs. |
| Formulaire de commande répété | Champs communs à création/modification, coordonnées en texte, récapitulatif focalisable ; confirmation fondée sur le devis serveur. |
| Livraison gratuite sur simple distance zéro | Bordeaux impose zéro côté serveur ; autre ville avec distance nulle refusée. La distance reste déclarative et doit être confirmée par l’équipe. |
| Jours ouvrés sans calendrier | Week-ends et jours fériés nationaux exclus ; dates mobiles calculées, fixtures officielles 2026 et 2027 comparées. |
| Bandeau hors des repères de navigation | Bandeau et navigation regroupés dans l’en-tête, après détection par axe. |
| État de chargement des filtres parfois persistant | Remise à zéro de l’état accessible quand les nouveaux filtres sont invalides. |

Le CSS reste organisé dans les douze modules existants. Les ajouts liés aux erreurs et aux cases à cocher se trouvent dans `02-formulaires.css` ; `style.css` est reconstruit, sans édition manuelle. Les commentaires décrivent les invariants utiles : persistance transactionnelle, saisies originales, calendrier, devis serveur et limites de l’audit.

## Recette reproductible

Environnement : PHP 8.2.4, MariaDB XAMPP, base jetable `vite_gourmand_test_quality_20261002`, HTTP loopback et capture SMTP locale. Aucun email de cette recette n’est transmis à un destinataire externe.

| Vérification | Résultat |
| --- | --- |
| Syntaxe PHP | 83 fichiers valides |
| Métier des commandes | 25 assertions |
| Statistiques, transport simulé | 41 vérifications |
| Catalogue, rollback, livraison et calendrier | 34 contrôles |
| HTTP/SQL/SMTP, parcours et rôles | 126 assertions |
| Erreurs HTTP, champs invalides et méthodes | 48 contrôles |
| Images | 4 contrôles |
| Structure HTML/accessibilité ciblée | 470 contrôles |
| Axe-core 4.13.0 | 24 états : 18 à 1280 × 900 et 6 à 320 × 844, aucune violation détectée |
| Entretien | CSS synchronisé, syntaxe JS/Python, PHP-CS-Fixer et diff sans erreur |

Les nombres de tests ne sont pas des taux de couverture ou de conformité. Les résultats axe détaillés sont dans [le rapport JSON](recette-axe-20261002.json). La [capture du formulaire](captures/qualite-formulaire-mobile.png) montre le résumé d’erreurs et les valeurs conservées à 390 px. Les six vues contrôlées à 320 px n’ont pas de débordement horizontal.

Lancer `python3 Scripts/verifier-qualite.py` pour les contrôles sans mutation SQL. Pour l’intégration, préparer les services locaux et une base jetable fraîche selon le [plan de tests](plan-tests.md), puis lancer `DB_NAME=nom_test_jetable python3 Scripts/verifier-qualite.py --integration`. `PHP_BIN` et `NODE_BIN` permettent de choisir les exécutables. La capture SMTP utilise Python 3.9 à 3.11.

## Limites à défendre honnêtement à l’oral

- Le lecteur d’écran VoiceOver reste non testé : les permissions du Mac bloquent l’accès. Le vrai zoom navigateur n’a pas pu être vérifié ; les essais de police agrandie ne le remplacent pas. Aucun audit RGAA exhaustif ni taux de conformité n’est revendiqué.
- Axe demande une vérification manuelle des étoiles de notation et de l’icône du bouton Menu. Des alternatives textuelles sont présentes ; cela ne clôt pas à lui seul tous les contrôles de contraste.
- La distance n’est pas calculée par géocodage. Les règles des fériés correspondent au calendrier national pour Bordeaux, pas à toutes les conventions de travail locales.
- Les notifications courantes n’ont pas de file de relance durable ; une outbox reste une amélioration d’exploitation.
- La réorganisation est progressive : le projet n’est pas un MVC intégral. Le responsive existant est conservé ; aucun historique mobile-first n’est inventé.
- Les maquettes reconstituées et la consolidation Trello restent datées honnêtement. La copie officielle, l’accès du jury aux liens et la maîtrise personnelle du projet restent des vérifications de remise et de préparation orale.

Voir les [décisions techniques](decisions-techniques.md) et le [rapport d’accessibilité](accessibilite.md).
