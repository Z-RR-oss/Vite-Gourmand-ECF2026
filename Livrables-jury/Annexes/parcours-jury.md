# Parcours de démonstration et préparation orale

Ce parcours court est un entraînement, pas une durée imposée par le sujet. Utiliser exclusivement les comptes et données fictifs. Préférer la base locale de recette pour les mutations afin de ne pas envoyer d'emails à des tiers pendant une répétition.

## Démonstration guidée

1. **Visiteur** : montrer l'accueil, filtrer les menus sans recharger la page, puis expliquer minimum, prix forfaitaire, conditions et allergènes d'un menu.
2. **Client** : se connecter, préparer une commande et montrer son récapitulatif. Expliquer que le serveur recalcule le prix et réserve le stock dans une transaction lors de la confirmation.
3. **Employé** : retrouver une commande, montrer les transitions autorisées, le contact obligatoire pour l'annulation et l'historique.
4. **Administrateur** : montrer les restrictions d'accès, la gestion des employés puis le graphique et le tableau des statistiques Firebase. Indiquer la date de synchronisation.
5. **Qualité** : parcourir le site à petite largeur et au clavier ; montrer les tests, le MCD et la séparation entre données SQL et agrégats NoSQL.

## Questions à savoir expliquer

| Question | Point de départ dans le code |
| --- | --- |
| Pourquoi SQL et NoSQL ? | `Services/StatisticsService.php` produit des agrégats ; `Repositories/StatisticsRepository.php` les lit/écrit dans Firebase. Les commandes restent dans MySQL. |
| Comment empêcher deux réservations du dernier stock ? | `Services/OrderService.php` : transaction et verrou sur le menu, validation puis décrément. |
| Que se passe-t-il si l'email échoue ? | La commande validée reste enregistrée ; les notifications arrivent après commit. Une file d'envoi durable n'est pas implémentée pour tous les messages. |
| Comment empêcher l'accès à une commande d'un autre client ? | Garde de connexion, requête limitée au propriétaire et contrôle du rôle côté serveur. |
| Pourquoi le MCD n'affiche-t-il pas les clés étrangères ? | Le MCD exprime les objets et relations métier ; le modèle relationnel traduit ensuite ces relations en tables et clés. |
| Pourquoi desktop first ? | Évolution d'une base existante, avec adaptations et tests multi-largeurs ; reconnaître les surcharges CSS et l'intérêt d'une approche mobile first pour un nouveau projet. |
| Le site est-il entièrement conforme RGAA ? | Présenter les contrôles et correctifs réels dans `accessibilite.md`, puis les limites restantes. Ne pas annoncer un taux non mesuré. |
| Les maquettes ont-elles précédé le code ? | Les exports actuels documentent l'interface finale ; ne pas inventer un historique de conception ou une utilisation de Figma. |

## Répétition finale

Faire une répétition sans assistance : retrouver un fichier à partir d'une question, expliquer la règle avec un exemple, puis montrer le test ou le comportement correspondant. Préparer également le projet local et les captures en cas d'indisponibilité du réseau le jour de la présentation.
