# Modèle des données et diagrammes

Le [MCD conceptuel](mcd.md) dispose d'un [export PDF](../output/pdf/mcd-vite-gourmand.pdf) et de trois vues SVG éditables : entités, associations et cardinalités, sans clés étrangères ni types SQL. Le présent fichier rassemble le schéma relationnel et les diagrammes UML en sources Mermaid éditables.

## Modèle relationnel / schéma SQL

Ce schéma représente les tables, clés et types réellement implémentés. Les associations plusieurs-à-plusieurs du MCD deviennent les tables `menu_plat` et `plat_allergene`. Les règles applicatives ajoutent notamment l'égalité entre propriétaire de l'avis et propriétaire de sa commande.

```mermaid
erDiagram
    users ||--o{ commandes : passe
    menus ||--o{ commandes : concerne
    menus ||--o{ menu_images : illustre
    menus ||--o{ menu_plat : compose
    plats ||--o{ menu_plat : appartient
    plats ||--o{ plat_allergene : contient
    allergenes ||--o{ plat_allergene : signale
    commandes ||--o{ historique_statuts : trace
    users o|--o{ historique_statuts : modifie
    commandes ||--o| avis : recoit
    users ||--o{ avis : redige
    users ||--o{ password_reset_tokens : reinitialise
    users {
        int id PK
        varchar email UK
        varchar nom
        varchar prenom
        varchar password
        varchar role
        varchar gsm
        varchar adresse
        boolean actif
        timestamp created_at
    }
    menus {
        int id PK
        varchar titre
        text description
        decimal prix
        int nb_personnes_min
        varchar theme
        varchar regime
        int stock_disponible
        text conditions_menu
        int delai_commande_heures
        boolean actif
    }
    plats {
        int id PK
        varchar nom
        text description
        varchar type_plat
    }
    menu_plat {
        int menu_id PK,FK
        int plat_id PK,FK
        int ordre_affichage
    }
    allergenes {
        int id PK
        varchar nom UK
    }
    plat_allergene {
        int plat_id PK,FK
        int allergene_id PK,FK
    }
    menu_images {
        int id PK
        int menu_id FK
        varchar chemin_image
        varchar texte_alternatif
    }
    commandes {
        int id PK
        int user_id FK
        int menu_id FK
        int nb_personnes
        decimal prix_total
        date date_prestation
        time heure_prestation
        varchar lieu_prestation
        varchar adresse_prestation
        decimal distance_km
        decimal frais_livraison
        decimal remise_pourcentage
        varchar statut
        varchar mode_contact_annulation
        text motif_annulation
        datetime date_annulation
        datetime date_debut_attente_retour
        datetime date_retour_materiel
        boolean materiel_retourne
        decimal frais_retard_materiel
        boolean notification_retard_envoyee
        datetime date_notification_retard
        timestamp created_at
        timestamp updated_at
    }
    historique_statuts {
        int id PK
        int commande_id FK
        varchar statut
        int modifie_par FK
        datetime date_modification
    }
    avis {
        int id PK
        int commande_id FK,UK
        int user_id FK
        int note
        text commentaire
        varchar statut_validation
        timestamp created_at
    }
    horaires {
        int id PK
        varchar jour UK
        time heure_ouverture
        time heure_fermeture
        boolean ferme
    }
    password_reset_tokens {
        int id PK
        int user_id FK
        varchar token_hash UK
        datetime expires_at
        boolean used
        timestamp created_at
    }
```

`horaires` est un référentiel indépendant. PK = clé primaire, FK = clé étrangère, UK = unicité. Sur avis, commande_id est unique indépendamment de id. L'auteur d'un historique est nullable et devient NULL si le compte est supprimé. Menus/users référencés par commandes ne sont pas supprimables en cascade. Les comptes se désactivent.

## Classes réelles

```mermaid
classDiagram
    class OrderService {
        -PDO pdo
        +create(userId, menuId, input) int
        +update(id, userId, input) void
        +cancel(id, actor, staff, contact, reason) void
        +transition(id, actor, status) void
        +returnEquipment(id, actor) void
    }
    class StatisticsService {
        -StatisticsRepository repository
        +synchronize(pdo) array
        +buildSnapshot(menus, rows, syncedAt) array
        +dashboard(filters) array
        +parseFilters(query, today) array
        +summarize(snapshot, filters) array
    }
    class StatisticsRepository {
        -array config
        +read() array
        +publish(snapshot) void
    }
    class PDO
    OrderService --> PDO : transactions SQL
    StatisticsService --> PDO : agrégation
    StatisticsService --> StatisticsRepository : lit et publie
    StatisticsRepository --> FirebaseREST : HTTPS OAuth
```

OrderRules, OrderNotifications, sécurité et templates sont des modules de fonctions, pas des classes artificiellement ajoutées au diagramme.

## Cas d'utilisation

```mermaid
flowchart LR
    V[Visiteur] --> C(Consulter menus et filtres)
    V --> I(Créer un compte / se connecter / reset)
    V --> F(Contacter la maison)
    U[Utilisateur] --> C
    U --> O(Commander et lire le récapitulatif)
    U --> S(Suivre / modifier / annuler sa commande)
    U --> P(Modifier son profil)
    U --> A(Déposer un avis après clôture)
    E[Employé] --> G(Gérer menus, images, plats et horaires)
    E --> W(Suivre les statuts et retours matériel)
    E --> X(Annuler après contact et motif)
    E --> M(Modérer les avis)
    AD[Administrateur] --> G
    AD --> W
    AD --> X
    AD --> M
    AD --> EQ(Créer / désactiver employés)
    AD --> ST(Comparer menus et CA depuis Firebase)
```

## Séquence : passage d'une commande

```mermaid
sequenceDiagram
    actor Client
    participant Page as commander.php
    participant Session
    participant Service as OrderService
    participant DB as MariaDB
    participant SMTP as PHPMailer / SMTP
    Client->>Page: GET menu choisi
    Page->>Session: vérifier compte actif
    Page-->>Client: formulaire prérempli et CSRF
    Client->>Page: POST prestation et convives
    Page->>Page: vérifier CSRF, délai, prix et minimum
    Page->>Session: conserver devis + token + expiration
    Page-->>Client: récapitulatif détaillé
    Client->>Page: POST confirmation + token + CSRF
    Page->>Service: create(userId, menuId, devis)
    Service->>DB: BEGIN / SELECT menu FOR UPDATE
    Service->>Service: revalider actif, stock, prix et délai
    alt valide
        Service->>DB: INSERT commande et historique / stock - 1
        Service->>DB: COMMIT
        Service-->>Page: identifiant commande
        Page->>Session: consommer devis
        Page->>SMTP: email de confirmation
        SMTP-->>Page: accepté ou échec journalisé
        Page-->>Client: commande enregistrée
    else invalide ou indisponible
        Service->>DB: ROLLBACK
        Service-->>Page: erreur métier
        Page-->>Client: corriger / refaire récapitulatif
    end
```

## Séquence : statistiques

```mermaid
sequenceDiagram
    participant Cron
    participant Service as StatisticsService
    participant SQL as MySQL
    participant Repo as StatisticsRepository
    participant Firebase
    actor Admin
    Cron->>Service: synchronize(PDO)
    Service->>SQL: lire menus + agrégats dans transaction
    SQL-->>Service: jours, menus, volumes, montants
    Service->>Repo: publish(snapshot)
    Repo->>Firebase: HTTPS PUT authentifié
    Firebase-->>Repo: succès ou échec
    Admin->>Service: dashboard(filtres)
    Service->>Repo: read()
    Repo->>Firebase: HTTPS GET authentifié
    Firebase-->>Repo: instantané
    Repo-->>Service: données validées
    Service-->>Admin: indicateurs, graphique et tableau
```

Si Firebase est indisponible, le dernier parcours produit une erreur explicite; aucune lecture SQL de secours n'alimente les statistiques.
