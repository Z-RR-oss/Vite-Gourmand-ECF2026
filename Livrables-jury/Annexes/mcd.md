# Modèle conceptuel de données

Édition du 1er octobre 2026, confrontée au dump `vite_gourmand.sql.sql` et aux règles applicatives. Ce modèle décrit le périmètre implémenté ; il ne prétend pas avoir été produit avant le développement.

Le sujet demande un **MCD ou un diagramme de classes** dans la documentation technique. Le diagramme de classes était déjà présent. Ce document ajoute un MCD explicite, distinct du schéma relationnel SQL.

## Exports et sources

- [PDF vectoriel, trois pages A3 paysage](../MCD/mcd-vite-gourmand.pdf).
- [Vue 1 : catalogue](../MCD/01-catalogue.svg).
- [Vue 2 : commandes](../MCD/02-commandes.svg).
- [Vue 3 : accès et horaires](../MCD/03-acces-horaires.svg).
- [Générateur Python](../../Scripts/generer-mcd.py), avec ReportLab : `python3 Scripts/generer-mcd.py`.
- [Schéma relationnel et diagrammes UML](diagrammes.md).

Les rectangles représentent les entités, les formes arrondies les associations. Les identifiants sont soulignés. Les cardinalités sont placées du côté de l'entité concernée. Une entité répétée entre deux vues est une référence au même objet. Le modèle ne contient ni clés étrangères ni types SQL.

## Associations et cardinalités

| Association | Première entité | Seconde entité | Interprétation |
| --- | --- | --- | --- |
| COMPOSER | MENU (0,N) | PLAT (0,N) | Un plat peut être réutilisé ; l'ordre d'affichage dépend du couple menu/plat. |
| ILLUSTRER | MENU (0,N) | IMAGE (1,1) | Une image appartient exactement à un menu. |
| CONTENIR | PLAT (0,N) | ALLERGÈNE (0,N) | Un plat peut déclarer plusieurs allergènes. |
| PASSER | UTILISATEUR (0,N) | COMMANDE (1,1) | Une commande appartient exactement à un client. |
| CONCERNER | COMMANDE (1,1) | MENU (0,N) | Une commande porte sur un seul menu pour un nombre de personnes donné. |
| RÉDIGER | UTILISATEUR (0,N) | AVIS (1,1) | Un avis possède un auteur. |
| ÉVALUER | COMMANDE (0,1) | AVIS (1,1) | Un seul avis au maximum pour une commande. |
| CONSERVER | COMMANDE (0,N) | ÉVÉNEMENT DE STATUT (1,1) | Chaque événement concerne une commande. |
| MODIFIER | UTILISATEUR (0,N) | ÉVÉNEMENT DE STATUT (0,1) | L'auteur d'un événement peut être absent. |
| DÉTENIR | UTILISATEUR (0,N) | JETON DE RÉINITIALISATION (1,1) | Chaque jeton appartient à un compte. |

## Règles complémentaires

- Le minimum zéro de COMPOSER autorise les menus en préparation et les plats non affectés, comme le permet le CRUD actuel. La présentation d'un menu complet constitue une règle métier supplémentaire ; les clés étrangères ne garantissent pas à elles seules une entrée, un plat et un dessert.
- Les rôles client, employé et administrateur sont des valeurs de l'attribut rôle de UTILISATEUR. Ils ne nécessitent pas trois entités séparées.
- L'auteur d'un avis doit être le propriétaire de sa commande ; celle-ci doit être terminée. Ces deux contraintes sont contrôlées par l'application. L'unicité de l'avis par commande est aussi garantie par SQL.
- L'historique accepte structurellement zéro événement, mais le parcours normal de création écrit un premier événement dans la même transaction que la commande. Chaque transition suivante est tracée.
- L'auteur d'un événement est facultatif dans le stockage ; une suppression de compte peut effacer cette référence sans effacer la trace.
- Les montants, remise, frais et coordonnées de prestation sont conservés dans COMMANDE. Le titre du menu et les données du profil restent liés à leur valeur actuelle ; ce modèle n'est pas un archivage comptable.
- HORAIRE utilise le jour comme identifiant conceptuel unique. La table SQL ajoute un identifiant technique entier et conserve l'unicité du jour. Les horaires décrivent une seule entreprise et ne sont pas artificiellement reliés aux commandes.
- Les empreintes de mot de passe et de jeton sont conservées, jamais les secrets en clair. Firebase contient un instantané statistique dérivé, sans identité des clients ; voir [NoSQL](nosql.md).

## Passage au modèle relationnel

| Élément conceptuel | Implémentation SQL |
| --- | --- |
| UTILISATEUR, MENU, PLAT, ALLERGÈNE | `users`, `menus`, `plats`, `allergenes` |
| IMAGE, COMMANDE, AVIS | `menu_images`, `commandes`, `avis` |
| ÉVÉNEMENT DE STATUT, JETON DE RÉINITIALISATION, HORAIRE | `historique_statuts`, `password_reset_tokens`, `horaires` |
| COMPOSER, avec ordre d'affichage | Table associative `menu_plat`, identifiée par le couple menu/plat |
| CONTENIR | Table associative `plat_allergene`, identifiée par le couple plat/allergène |
| Autres associations | Clés étrangères du côté de l'entité ayant la cardinalité maximale 1 |
| ÉVALUER | Clé étrangère `avis.commande_id` avec contrainte UNIQUE |

Les dix entités conceptuelles et les deux associations plusieurs-à-plusieurs conduisent aux douze tables du schéma actuel. Les types, index, contraintes et noms physiques figurent dans le dump et le schéma relationnel, pas dans le MCD.
