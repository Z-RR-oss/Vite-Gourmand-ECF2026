# Remise du projet Vite & Gourmand

## Liens à reporter dans la copie officielle

- Application : https://vite-gourmandecf2026.alwaysdata.net/
- Code testé : https://github.com/Z-RR-oss/Vite-Gourmand-ECF2026/tree/main
- Gestion de projet : https://trello.com/b/mHfEGdUL/vite-gourmand-projet-ecf

La branche `main` contient la version finale testée, après fusion de la branche de fonctionnalité dans `develop`, puis de `develop` dans `main`. C’est la branche à présenter au jury.

## Documents à joindre

- `manuel-utilisateur.pdf` : parcours et comptes du dump local.
- `charte-graphique.pdf` : identité, 6 wireframes et 6 mockups (pour chacun : 3 ordinateur et 3 mobile).
- `../output/pdf/mcd-vite-gourmand.pdf` : MCD conceptuel en trois vues, avec associations et cardinalités ; sources et règles dans [mcd.md](mcd.md).
- `documentation-technique.md`, `diagrammes.md`, `deploiement.md`, `nosql.md`.
- `gestion-projet.md`, `plan-tests.md`, `checklist-finale.md`, `rapport-final.md`.
- `accessibilite.md`, `recette-accessibilite-responsive.json` : corrections, mesures et limites de l'évaluation.
- `preparation-copie-ecf.md`, `parcours-jury.md` : aide à la remise et à la présentation, sans remplacer le modèle officiel Studi.
- `recette-alwaysdata.json`, `recette-finale-hebergement.json` et `recette-nettoyage-visuels.json` : contrôles distants.
- `vite_gourmand.sql.sql` : schéma complet et données fictives; import uniquement sur une base vide.

Les identifiants du site public sont dans le fichier privé local `var/deployment/acces-site-alwaysdata.md`. Le transmettre séparément au jury par un canal privé. Il est volontairement exclu du dépôt public et de l'archive publique. Les clés Firebase/SSH et les configurations privées ne doivent jamais accompagner un dépôt public.

## Démonstration de cinq minutes

1. Accueil, menus, filtres et détail avec conditions/allergènes.
2. Client : connexion, préparation d'une commande, prix et historique.
3. Employé : catalogue, filtres des commandes, transitions et avis.
4. Administrateur : équipe, puis statistiques avec source Firebase et date de synchronisation.
5. Montrer le schéma SQL, le flux MySQL → agrégation serveur → Firebase → dashboard, et les preuves de tests.

Le site est pédagogique : aucun paiement réel et aucune prestation commerciale. Les huit familles d'emails sont testées localement; un message autorisé a été réellement envoyé puis reçu dans la boîte de recette. Les deux tâches serveur ont réussi dans le planificateur.

## Dernières actions de remise

La copie officielle est à compléter et déposer par l'étudiante avec ses propres explications et les liens ci-dessus. Reporter les preuves dans le tableau de gestion de projet si nécessaire. Ces démarches de dépôt de l'examen ne sont pas effectuées automatiquement.

Le lien Trello nécessite actuellement une connexion dans une session non authentifiée. Vérifier l'accès du jury avant remise. L'évaluation RGAA exhaustive n'est pas achevée : voir les contrôles effectués et les limites dans `accessibilite.md`.
