# Statistiques : MySQL → Firebase → administration

## État de la configuration distante — 29 septembre 2026

La console confirme la base `https://vite-gourmand-ecf2026-default-rtdb.europe-west1.firebasedatabase.app`, située en Belgique (`europe-west1`), avec les règles `.read: false` et `.write: false`. La clé téléchargée par le propriétaire est conservée hors du dépôt et de Public, dans un fichier privé de mode 600. La synchronisation réelle et `RUN_FIREBASE_TEST=1 php tests/statistics-live.php` ont réussi : 5 menus, 2 jours, instantané du 29 septembre à 13:46:43 UTC. Le dashboard de recette affiche 3 commandes, 19 convives et 120 € de CA livré; filtres menu/dates et mesure du graphique vérifiés. Une lecture REST sans authentification reçoit HTTP 401. Ces vérifications utilisent uniquement le dump de démonstration dans la base SQL jetable.

## Circuit réellement implémenté

1. `Scripts/synchroniser-statistiques.php`, accessible uniquement en CLI, ouvre une transaction SQL en lecture.
2. `StatisticsService::synchronize()` agrège les commandes par date de création et menu, puis termine la transaction SQL.
3. `StatisticsRepository::publish()` remplace en une requête HTTPS `PUT` le nœud Firebase `vite_gourmand/statistics_v1`. Une nouvelle exécution remplace le même instantané : elle n’ajoute pas deux fois les commandes. Les annulations/modifications sont répercutées à la synchronisation suivante.
4. `admin-statistiques.php` utilise `StatisticsRepository::read()` (`GET` Firebase). Les noms de menus, chiffres, dates de mise à jour et comparaisons viennent de cet instantané distant. PHP additionne les agrégats journaliers déjà stockés dans Firebase pour appliquer les filtres.

Il n’existe aucun cache JSON local et aucun repli statistique vers MySQL. La connexion SQL de cette page ne sert qu’à vérifier l’identité active de l’administrateur et à afficher les horaires du pied de page commun. En cas d’indisponibilité Firebase, un message explicite et une réponse HTTP 503 remplacent les chiffres. Le navigateur ne reçoit jamais de clé Firebase.

## Définition des indicateurs

- **Commandes** : toutes les commandes, sauf celles au statut `annulée`. Le nombre d’annulations apparaît séparément.
- **Convives** : somme de `nb_personnes` des commandes non annulées, tous statuts restants confondus.
- **CA livré** : somme de `prix_total` des commandes `livré`, `en attente du retour de matériel` et `terminée`. Le libellé historique `livrée` est également reconnu pour la compatibilité avec un ancien dump. Les autres statuts ne contribuent pas au CA livré.
- **Montant** : prix final après remise et livraison comprise ; les pénalités de matériel sont exclues. Le total est calculé et stocké en centimes entiers, puis formaté en euros.
- **Période** : date de création de la commande, bornes incluses. Il s’agit donc d’une cohorte de commandes créées pendant la période, dont on observe l’état à la dernière synchronisation, et non d’un journal comptable des livraisons du jour.
- **Fuseau** : application et session MySQL doivent utiliser Europe/Paris. Une base configurée sur une autre zone doit être corrigée à l’installation. Un offset fixe ne remplace pas les règles historiques d’heure d’été/hiver.

Filtres : un menu ou tous, 7/30/90/365 derniers jours (aujourd’hui inclus), tout l’historique, ou deux dates personnalisées. Les dates impossibles/inversées et les menus invalides sont refusés côté serveur. Les menus sans commande restent visibles avec zéro.

## Configuration minimale

Prérequis : PHP 8.1+, PDO MySQL, JSON, cURL et OpenSSL ; certificats racines à jour et accès HTTPS sortant. Aucune extension MongoDB, aucun SDK JavaScript Firebase ni nouvelle dépendance Composer ne sont requis.

1. Dans un projet Firebase, créer une **Realtime Database** (et non Firestore), en mode verrouillé. Conserver ses règles publiques fermées :

   ```json
   { "rules": { ".read": false, ".write": false } }
   ```

2. Créer un compte de service disposant des droits IAM nécessaires sur cette base et télécharger sa clé JSON. La ranger **hors de `Public/` et hors du dépôt**, lisible seulement par le compte système PHP/cron. Ne jamais copier sa valeur dans les tickets, captures, logs ou arguments de commande.
3. Copier `Config/nosql.example.php` vers `Config/nosql.local.php` et renseigner l’URL exacte de la base ainsi que le chemin absolu de la clé. Ce fichier est ignoré par Git.
4. Exécuter la synchronisation, puis ouvrir `admin-statistiques.php` connecté comme administrateur.

```sh
php Scripts/synchroniser-statistiques.php
RUN_FIREBASE_TEST=1 php tests/statistics-live.php
```

Alternativement, définir dans l’environnement du serveur/cron les noms suivants, prioritaires sur le fichier local :

| Variable | Usage |
| --- | --- |
| `FIREBASE_DATABASE_URL` | URL HTTPS `*.firebaseio.com` ou `*.firebasedatabase.app`, sans chemin ni secret |
| `FIREBASE_SERVICE_ACCOUNT_FILE` | Chemin absolu de la clé de compte de service |
| `FIREBASE_STATISTICS_PATH` | Nœud dédié, par défaut `vite_gourmand/statistics_v1` |
| `FIREBASE_TIMEOUT` | Délai total de requête en secondes, borné de 1 à 30, défaut 10 |
| `FIREBASE_DATABASE_SECRET` | Option de compatibilité ancienne, seulement si aucun fichier de compte de service n’est configuré |

L’authentification recommandée crée une assertion JWT RS256 avec OpenSSL, l’échange auprès du point OAuth Google, puis transmet le jeton dans l’en-tête `Authorization: Bearer`. Le jeton est conservé seulement en mémoire pour la durée du processus. Les deux portées demandées sont `firebase.database` et `userinfo.email`. Le chemin OAuth est fixe ; l’URL définie dans une clé JSON n’est jamais utilisée comme destination réseau.

La variante `database_secret` existe pour les bases possédant déjà un secret historique. Elle emploie le paramètre REST `auth`, comme les anciennes intégrations Firebase. Préférer le compte de service ; ne pas enregistrer les URL sortantes contenant ces secrets dans les outils de diagnostic/proxy.

## Structure du nœud

Exemple de **structure**, pas une donnée affichée à la place de Firebase :

```json
{
  "schema_version": 1,
  "synced_at": "2026-09-28T12:00:00+00:00",
  "date_basis": "created_at",
  "currency": "EUR",
  "menus": { "menu_1": { "id": 1, "titre": "Menu Classique" } },
  "days": {
    "2026-09-28": {
      "menu_1": { "commandes": 2, "annulees": 1, "personnes": 10, "ca_centimes": 12059 }
    }
  }
}
```

Les identifiants préfixés empêchent Firebase d’interpréter les menus comme un tableau à indices numériques. Les agrégats ne contiennent ni nom de client, ni email, ni adresse, ni identifiant de commande. Firebase supprime les collections vides ; la lecture les normalise en tableaux vides uniquement après vérification de l’enveloppe versionnée.

## Exploitation et limites

Exemple de cron toutes les quinze minutes, à adapter aux vrais chemins de l’hébergement :

```cron
*/15 * * * * /usr/bin/php /srv/vite-gourmand/Scripts/synchroniser-statistiques.php >> /var/log/vite-gourmand-statistiques.log 2>&1
```

La sortie indique une réussite seulement après un HTTP réussi du `PUT`. Codes de sortie : 0 succès, 1 échec, 2 autre synchronisation en cours. Le verrou local protège un serveur unique ; plusieurs machines doivent utiliser un ordonnanceur unique. Une panne de synchronisation préserve l’ancien instantané Firebase : vérifier l’horodatage affiché dans le dashboard et surveiller le code de sortie du cron.

TLS vérifie le certificat et le nom d’hôte. Les redirections sont refusées. Les erreurs HTTP, JSON, authentification et réseau ne révèlent pas le corps fournisseur ni les clés. Les filtres et le tableau fonctionnent sans JavaScript ; le JS enrichit seulement le choix de mesure du graphique et les champs de dates. Le graphique natif HTML est accompagné des valeurs et d’un tableau avec en-têtes/caption.

Pour le volume ECF, un instantané complet permet un code simple à défendre. Un volume de millions de lignes nécessiterait un historique partitionné et des agrégations incrémentales. Le chiffre d’affaires livré est un indicateur de gestion pédagogique, pas une comptabilité de factures/TVA.

## Vérifications et état réel

- `php tests/statistics.php` : tests locaux déterministes avec transport injecté, OAuth RS256 vérifié cryptographiquement et agrégation SQL sur SQLite en mémoire. Ils ne contactent pas Firebase.
- `php tests/statistics-live.php` : retourne **77 / NON EXÉCUTÉ** tant que l’activation explicite `RUN_FIREBASE_TEST=1` manque. Une fois activé, effectue une vraie lecture HTTPS et échoue si la configuration, l’accès ou l’instantané sont absents. Aucun faux succès, aucune donnée de remplacement.
- Parcours d’intégration : configurer une vraie base → synchroniser → confirmer le nœud en console Firebase → exécuter le test distant → comparer totaux MySQL de contrôle et dashboard → filtrer menu/dates → rendre temporairement l’accès indisponible et vérifier le message d’erreur → rétablir l’accès.
- **Validation distante réussie le 29 septembre 2026** depuis PHP local : publication réelle, lecture HTTPS et tableau de bord alimenté par Firebase. Capture : `captures/statistiques-firebase-reel.png`. Le déploiement et l'exécution automatique sur l'hébergement restent à vérifier.

## Références officielles consultées

Les méthodes d’authentification, portées et jetons historiques sont décrits dans [Firebase : authentifier les requêtes REST](https://firebase.google.com/docs/database/rest/auth). La sémantique de remplacement et la réponse silencieuse HTTP 204 sont documentées dans [Firebase : enregistrer des données](https://firebase.google.com/docs/database/rest/save-data). L’échange d’une assertion signée est décrit par [Google : OAuth 2.0 pour les applications serveur](https://developers.google.com/identity/protocols/oauth2/service-account).
