# Visuels des menus

Créés le 30 septembre 2026 avec l’outil intégré de génération d’images, mode génération (deux appels indépendants).
Les images sont des visuels de présentation générés, pas des photographies de prestations réelles.

| Menu | Fichier livré | Dimensions | Poids |
| --- | --- | --- | --- |
| Vegan | `Public/assets/images/menu-vegan-genere.jpg` | 1536 × 1024 | 399 824 octets |
| Noël | `Public/assets/images/menu-noel-genere.jpg` | 1536 × 1024 | 361 855 octets |

Les PNG originaux sont conservés localement dans `var/design/` (hors Git). Les JPEG de qualité 85 conservent les dimensions et réduisent le transfert. Les cartes utilisent le chargement différé ; les fiches affichent un texte alternatif descriptif.

Le dump neuf inclut les deux chemins. Sur une base existante, `php Scripts/installer-images-menus.php` affiche le plan ; `--apply` ajoute les visuels manquants ou remplace uniquement les anciennes illustrations de démonstration. Les photos téléversées par l’équipe sont conservées. La migration peut être relancée sans créer de doublon.

## Prompt Vegan

Photographie culinaire réaliste haut de gamme, format paysage 3:2, pour la carte Menu Vegan d’un traiteur français Vite & Gourmand. Assiette artisanale ivoire avec lentilles parfumées, carottes et légumes de saison rôtis, herbes fraîches, belle présentation généreuse et appétissante entièrement végétale. Petite assiette de fruits rôtis discrète à l’arrière-plan. Table en bois clair, serviette sauge, lumière naturelle douce de fenêtre, palette crème et vert avec une touche bordeaux, texture des aliments nette, angle trois quarts légèrement plongeant, composition centrale adaptée au recadrage de cartes web. Sans personne, sans texte, sans logo, sans filigrane.

## Prompt Noël

Photographie culinaire réaliste haut de gamme, format paysage 3:2, pour la carte Menu Noël d’un traiteur français Vite & Gourmand. Belle assiette en céramique ivoire d’un repas de fête français avec volaille rôtie dorée tranchée, pommes de terre fondantes et légumes d’hiver rôtis, sauce brune délicate, romarin. Fond de table crème élégant avec une serviette bordeaux, quelques branches de sapin et lumières chaleureuses discrètes floues, ambiance Noël raffinée, lumière naturelle douce, nourriture généreuse et appétissante, angle trois quarts légèrement plongeant, composition centrale adaptée au recadrage de cartes web. Sans personne, sans texte, sans logo, sans filigrane.
