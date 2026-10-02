# Accessibilité : contrôles et limites

Revue du 1er octobre 2026. Référence : [critères et tests officiels RGAA 4.1.2](https://accessibilite.numerique.gouv.fr/methode/criteres-et-tests/). Ce rapport documente les contrôles effectués ; il ne constitue ni un audit exhaustif des 106 critères, ni un taux de conformité.

## Périmètre et méthode

- 32 pages et états HTML du site public, du client et de l'administration : `tests/accessibility_http.py`, exécuté sur une base MariaDB jetable. 473 vérifications, incluant les erreurs de saisie et la conservation des valeurs.
- Contrôles de mise en page dans le navigateur Chromium intégré, avec un cadre local de largeur contrôlée. Les mesures réelles (`clientWidth`, largeur défilante) figurent dans [recette-accessibilite-responsive.json](recette-accessibilite-responsive.json). La barre verticale peut soustraire 15 px à la largeur du cadre.
- Tailles de référence du cadre : 320, 390, 768 et 1440 px. Texte à 200 % par agrandissement de la police racine et essai d'espacement sur des pages représentatives. Il s'agit de tests de présentation, pas d'un essai complet du zoom navigateur ni de tests sur téléphones physiques.
- Inspection de l'arbre d'accessibilité, du focus après erreur et du menu au clavier. La restitution avec un lecteur d'écran reste à vérifier ; un arbre d'accessibilité correct ne suffit pas à la prouver.
- Le banc de test autorise uniquement son propre cadre en local. Aucun outil de recette ni assouplissement de l'en-tête `X-Frame-Options: DENY` n'est livré sur l'hébergement.

## Corrections réalisées

| Point | Correction et preuve |
| --- | --- |
| Champs obligatoires | Indication textuelle dans les libellés, présente même sans JavaScript ; dates personnalisées du dashboard signalées au changement de période. |
| Erreurs de formulaire | Rôle d'alerte homogène ; focus placé sur le message après réponse serveur. Les erreurs d'identité précisent le champ et sa limite. |
| Dépôt d'avis | L'erreur serveur est enfin affichée ; note et commentaire conservés lors d'un rejet. Les états impossibles renvoient une page structurée et un statut HTTP adapté. |
| Modification de profil | Conservation des coordonnées saisies après erreur ; longueurs maximales et finalité des champs renseignées. |
| Réinitialisation du mot de passe | Consignes visibles reliées au champ et bornes de longueur cohérentes avec le serveur. |
| Contraste des champs | Bordure renforcée : 4,00:1 sur le fond du champ et 3,68:1 sur le fond de page. |
| Navigation et agrandissement | Retour à la ligne de l'en-tête pour éviter le débordement des liens administrateur avec une police doublée. |
| Espacement du texte | Colonnes Contact rétractables et mots longs repliables ; contrôle effectué après application des styles de recette. |
| Navigation au clavier | Cible du lien d'évitement focusable ; Échap ferme le menu et restitue le focus ; la sortie du menu au clavier le referme. |
| Catalogue et avis | Le thème est exposé aux lecteurs d'écran même si l'image liée est décorative ; les étoiles d'avis ont un rôle d'image et une note textuelle. |
| Repérage | Zone de recherche nommée, plan du site et page d'aide accessibles dans le pied de page. |

## Couverture par thème

| Thème RGAA | Éléments contrôlés | Portée / limite |
| --- | --- | --- |
| 1. Images | Présence des alternatives, images décoratives, notes et thème des menus | Pertinence contrôlée sur les visuels de démonstration ; les futurs uploads restent à décrire par l'équipe. |
| 2. Cadres | Aucun cadre utilisé dans l'application livrée | Le cadre de recette est un outil local, exclu du livrable. |
| 3. Couleurs | Textes et contrôles principaux ; informations de statut également textuelles | Mesures détaillées dans le JSON ; pas de validation exhaustive de tous les états de toutes les couleurs. |
| 4. Multimédia | Aucun audio ou vidéo sur les pages applicatives | Pas de média temporel à transcrire dans ce périmètre. |
| 5. Tableaux | En-têtes avec scope, légende, valeurs du graphique disponibles dans le tableau | Défilement horizontal du tableau prévu avec région focusable. |
| 6. Liens | Noms explicites des accès principaux et liens de cartes | Revue des gabarits et des parcours principaux. |
| 7. Scripts | État du menu, clavier, filtres dynamiques, messages de statut, rendu initial serveur | Restitution avec lecteur d'écran non validée à ce stade. |
| 8. Éléments obligatoires | Langue française, titre de page, identifiants uniques, références ARIA existantes | Contrôles HTML ciblés, sans revendication d'un audit de chaque règle de parsing HTML. |
| 9. Structuration | Titre principal, sections et titres, listes et zones de page | Structure inspectée sur les parcours et pages contrôlés. |
| 10. Présentation | Repli mobile, agrandissement du texte, espacement, focus visible | Les tests de police agrandie ne remplacent pas tous les essais de zoom à 200 % / 400 %. |
| 11. Formulaires | Libellés, champs obligatoires, erreurs, consignes, autocomplete et maintien des valeurs | États d'erreur ciblés ; pas de certification de l'ensemble des combinaisons navigateur/assistance. |
| 12. Navigation | Menu, plan du site, lien d'évitement, ordre de tabulation et fermeture Échap | Parcours principaux vérifiés ; pas de raccourci clavier à une seule lettre. |
| 13. Consultation | Pas de défilement automatique ni de média clignotant ; réduction des animations prévue | Documents de remise hors interface : les PDF ne sont pas déclarés conformes PDF/UA. |

## Reproduire et interpréter les résultats

Suivre [plan-tests.md](plan-tests.md) pour démarrer le serveur et la capture SMTP sur une base contenant `_test_`. Avant les tests HTTP qui modifient les fixtures :

```sh
DB_NAME=vite_gourmand_test_final_20261001 python3 tests/accessibility_http.py
```

Le script crée puis supprime une commande fictive pour tester le rejet d'un avis. Il ne doit jamais cibler une base personnelle ou l'hébergement public.

Pour reproduire les mesures de présentation, ajouter `tests/viewport-router.php` à la fin de la commande de démarrage PHP du plan de tests. Ce routeur refuse les accès non locaux et les environnements sans nom de base `_test_`. Ouvrir par exemple `http://127.0.0.1:8091/__recette/viewport?width=320&page=contact.php`. Ajouter `&mode=text200` pour doubler la police racine ou `&mode=spacing` pour augmenter les espacements. Le code livré dans `Public/` conserve son refus de mise en cadre ; le routeur de test ne doit pas être utilisé pour héberger le site public.

Une validation finale RGAA nécessite encore la restitution avec technologies d'assistance, les essais de zoom navigateur et l'évaluation critère par critère de tous les états retenus dans l'échantillon. Les défauts constatés par cette revue ont été corrigés ; cela ne permet pas d'annoncer « 100 % conforme ».

## Essai VoiceOver

Le 1er octobre 2026, la propriétaire a autorisé un essai temporaire avec VoiceOver. L’accès à l’utilitaire a été refusé par le contrôle du Mac (« Computer Use permissions are not granted »). Aucune activation ni restitution vocale n’a pu être confirmée ; le test reste non exécuté. Les contrôles de structure et de clavier ci-dessus ne sont pas présentés comme un essai de lecteur d’écran.
