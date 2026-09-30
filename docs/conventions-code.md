# Entretien du code

## Responsabilités

- `Public/` traite les requêtes HTTP et affiche les pages. Les gardes d’accès précèdent toute mutation.
- `Templates/` contient la présentation partagée et le catalogue.
- `Config/` initialise les connexions, sessions, CSRF et contrôles d’accès.
- `Services/` porte les validations, transactions, prix et notifications.
- `Repositories/` isole le transport Firebase.
- `Scripts/` regroupe les opérations CLI ; les migrations proposent un plan avant application.

Ne pas déplacer une règle métier dans JavaScript : le serveur reste responsable du prix, du stock et des autorisations. Les commentaires expliquent les invariants (centimes, concurrence, révocation de session), plutôt que de répéter chaque instruction. Échapper les valeurs lors de leur affichage, lier les paramètres PDO et ne jamais versionner les secrets locaux.

## PHP

`.editorconfig` impose UTF-8, LF et quatre espaces. `.php-cs-fixer.dist.php` applique PSR-12 et des règles de lisibilité sans transformation risquée. Les fichiers `*.local.php`, les dépendances et les données d’exploitation restent hors du périmètre.

Outil de développement utilisé : PHP-CS-Fixer 3.95.0, PHAR officiel, installé dans `var/tools/` et exclu de Git. Installation reproductible :

```sh
mkdir -p var/tools
curl --fail --location https://github.com/PHP-CS-Fixer/PHP-CS-Fixer/releases/download/v3.95.0/php-cs-fixer.phar -o var/tools/php-cs-fixer.phar
php var/tools/php-cs-fixer.phar fix --sequential
php var/tools/php-cs-fixer.phar fix --dry-run --diff --sequential
```

Les vues mixtes HTML/PHP sont gardées lisibles avec les blocs d’affichage `<?= ... ?>`. La mise en forme n’exonère pas de relire le diff et d’exécuter la recette.

## CSS et JavaScript

Les sources CSS sont dans `Public/assets/css/`, avec une déclaration par ligne :

| Ordre | Contenu |
| --- | --- |
| 01–03 | Variables, base, boutons/champs et navigation |
| 04–06 | Accueil, catalogue et contenus éditoriaux |
| 07–09 | Pied de page, fiche menu, contact et pages légales |
| 10 | Gestion, tableaux et statistiques |
| 11 | Responsive, réduction des animations et impression |
| 12 | Panneaux métier et leurs adaptations finales |

L’ordre explicite dans `Scripts/construire-css.py` conserve la cascade. Le module 12 suit volontairement les adaptations générales. Modifier les sources, puis reconstruire :

```sh
python3 Scripts/construire-css.py
python3 Scripts/construire-css.py --check
```

`Public/style.css` est généré et versionné : un seul chargement CSS, sans dépendance Node/Python sur l’hébergement. Sa version dans l’URL dépend de sa date de modification pour renouveler le cache après déploiement.

Prettier 3.6.2 et `.prettierrc.json` harmonisent uniquement le CSS source et les deux scripts JavaScript :

```sh
npx --yes prettier@3.6.2 --write 'Public/assets/css/*.css' Public/scripts.js Public/assets/js/statistics.js
python3 Scripts/construire-css.py
node --check Public/scripts.js
node --check Public/assets/js/statistics.js
```

Ne pas formater séparément le CSS généré. Les noms de classe et l’ordre des règles sont conservés. Pour les filtres, garder l’annulation des anciennes requêtes et le numéro de version ; pour les cartes, utiliser `textContent` pour les données issues de la base.

## Vérification avant livraison

Relire `git diff --check`, contrôler la syntaxe PHP, vérifier le CSS généré et exécuter les [suites de recette](plan-tests.md) sur une base jetable. Tester les pages concernées sur ordinateur et mobile. Les images sont décrites dans [visuels-menus.md](visuels-menus.md).
