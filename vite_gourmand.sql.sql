SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
-- Le serveur SQL doit utiliser Europe/Paris, comme PHP.
SET time_zone = 'SYSTEM';

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `password_reset_tokens`;
DROP TABLE IF EXISTS `avis`;
DROP TABLE IF EXISTS `historique_statuts`;
DROP TABLE IF EXISTS `menu_images`;
DROP TABLE IF EXISTS `plat_allergene`;
DROP TABLE IF EXISTS `menu_plat`;
DROP TABLE IF EXISTS `horaires`;
DROP TABLE IF EXISTS `commandes`;
DROP TABLE IF EXISTS `allergenes`;
DROP TABLE IF EXISTS `plats`;
DROP TABLE IF EXISTS `menus`;
DROP TABLE IF EXISTS `users`;

SET FOREIGN_KEY_CHECKS = 1;


-- =========================================================
-- UTILISATEURS
-- =========================================================

CREATE TABLE `users` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `nom` VARCHAR(50) NOT NULL,
    `prenom` VARCHAR(50) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `role` VARCHAR(25) NOT NULL DEFAULT 'utilisateur',
    `gsm` VARCHAR(20) NOT NULL,
    `adresse` VARCHAR(255) NOT NULL,
    `actif` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- =========================================================
-- MENUS
-- =========================================================

CREATE TABLE `menus` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `titre` VARCHAR(255) NOT NULL,
    `description` TEXT NOT NULL,
    `prix` DECIMAL(10,2) NOT NULL,
    `nb_personnes_min` INT NOT NULL,

    `theme` VARCHAR(100) DEFAULT NULL,
    `regime` VARCHAR(100) DEFAULT NULL,

    `stock_disponible` INT NOT NULL DEFAULT 0,

    `conditions_menu` TEXT DEFAULT NULL,
    `delai_commande_heures` INT DEFAULT NULL,

    `actif` TINYINT(1) NOT NULL DEFAULT 1,

    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- =========================================================
-- PLATS
-- =========================================================

CREATE TABLE `plats` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `nom` VARCHAR(255) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `type_plat` VARCHAR(30) NOT NULL,

    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- =========================================================
-- RELATION MENUS / PLATS
-- Un plat peut appartenir à plusieurs menus
-- =========================================================

CREATE TABLE `menu_plat` (
    `menu_id` INT NOT NULL,
    `plat_id` INT NOT NULL,
    `ordre_affichage` INT DEFAULT NULL,

    PRIMARY KEY (`menu_id`, `plat_id`),

    CONSTRAINT `fk_menu_plat_menu`
        FOREIGN KEY (`menu_id`)
        REFERENCES `menus` (`id`)
        ON DELETE CASCADE,

    CONSTRAINT `fk_menu_plat_plat`
        FOREIGN KEY (`plat_id`)
        REFERENCES `plats` (`id`)
        ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- =========================================================
-- ALLERGENES
-- =========================================================

CREATE TABLE `allergenes` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `nom` VARCHAR(100) NOT NULL,

    PRIMARY KEY (`id`),
    UNIQUE KEY `nom` (`nom`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- =========================================================
-- RELATION PLATS / ALLERGENES
-- =========================================================

CREATE TABLE `plat_allergene` (
    `plat_id` INT NOT NULL,
    `allergene_id` INT NOT NULL,

    PRIMARY KEY (`plat_id`, `allergene_id`),

    CONSTRAINT `fk_plat_allergene_plat`
        FOREIGN KEY (`plat_id`)
        REFERENCES `plats` (`id`)
        ON DELETE CASCADE,

    CONSTRAINT `fk_plat_allergene_allergene`
        FOREIGN KEY (`allergene_id`)
        REFERENCES `allergenes` (`id`)
        ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- =========================================================
-- GALERIE D'IMAGES DES MENUS
-- =========================================================

CREATE TABLE `menu_images` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `menu_id` INT NOT NULL,
    `chemin_image` VARCHAR(255) NOT NULL,
    `texte_alternatif` VARCHAR(255) DEFAULT NULL,

    PRIMARY KEY (`id`),

    CONSTRAINT `fk_menu_images_menu`
        FOREIGN KEY (`menu_id`)
        REFERENCES `menus` (`id`)
        ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- =========================================================
-- COMMANDES
-- =========================================================

CREATE TABLE `commandes` (
    `id` INT NOT NULL AUTO_INCREMENT,

    `user_id` INT NOT NULL,
    `menu_id` INT NOT NULL,

    `nb_personnes` INT NOT NULL,
    `prix_total` DECIMAL(10,2) NOT NULL,

    `date_prestation` DATE DEFAULT NULL,
    `heure_prestation` TIME DEFAULT NULL,
    `lieu_prestation` VARCHAR(255) DEFAULT NULL,
    `adresse_prestation` VARCHAR(255) DEFAULT NULL,

    `distance_km` DECIMAL(10,2) NOT NULL DEFAULT 0,
    `frais_livraison` DECIMAL(10,2) NOT NULL DEFAULT 0,
    `remise_pourcentage` DECIMAL(5,2) NOT NULL DEFAULT 0,

    `statut` VARCHAR(50) NOT NULL DEFAULT 'en attente',

    `mode_contact_annulation` VARCHAR(100) DEFAULT NULL,
    `motif_annulation` TEXT DEFAULT NULL,
    `date_annulation` DATETIME DEFAULT NULL,

    `date_debut_attente_retour` DATETIME DEFAULT NULL,
    `date_retour_materiel` DATETIME DEFAULT NULL,
    `materiel_retourne` TINYINT(1) NOT NULL DEFAULT 0,
    `frais_retard_materiel` DECIMAL(10,2) NOT NULL DEFAULT 0,

    `notification_retard_envoyee` TINYINT(1) NOT NULL DEFAULT 0,
    `date_notification_retard` DATETIME DEFAULT NULL,

    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),

    KEY `idx_commandes_user` (`user_id`),
    KEY `idx_commandes_menu` (`menu_id`),
    KEY `idx_commandes_statut` (`statut`),
    KEY `idx_commandes_created_menu` (`created_at`, `menu_id`),

    CONSTRAINT `fk_commandes_user`
        FOREIGN KEY (`user_id`)
        REFERENCES `users` (`id`),

    CONSTRAINT `fk_commandes_menu`
        FOREIGN KEY (`menu_id`)
        REFERENCES `menus` (`id`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- =========================================================
-- HISTORIQUE DES STATUTS
-- =========================================================

CREATE TABLE `historique_statuts` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `commande_id` INT NOT NULL,
    `statut` VARCHAR(50) NOT NULL,
    `modifie_par` INT DEFAULT NULL,
    `date_modification` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),

    CONSTRAINT `fk_historique_commande`
        FOREIGN KEY (`commande_id`)
        REFERENCES `commandes` (`id`)
        ON DELETE CASCADE,

    CONSTRAINT `fk_historique_user`
        FOREIGN KEY (`modifie_par`)
        REFERENCES `users` (`id`)
        ON DELETE SET NULL

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- =========================================================
-- AVIS CLIENTS
-- =========================================================

CREATE TABLE `avis` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `commande_id` INT NOT NULL,
    `user_id` INT NOT NULL,

    `note` TINYINT NOT NULL,
    `commentaire` TEXT NOT NULL,

    `statut_validation` VARCHAR(20) NOT NULL DEFAULT 'en attente',

    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),

    UNIQUE KEY `avis_commande_unique` (`commande_id`),

    CONSTRAINT `fk_avis_commande`
        FOREIGN KEY (`commande_id`)
        REFERENCES `commandes` (`id`)
        ON DELETE CASCADE,

    CONSTRAINT `fk_avis_user`
        FOREIGN KEY (`user_id`)
        REFERENCES `users` (`id`)
        ON DELETE CASCADE,

    CONSTRAINT `chk_avis_note`
        CHECK (`note` >= 1 AND `note` <= 5)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- =========================================================
-- HORAIRES
-- =========================================================

CREATE TABLE `horaires` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `jour` VARCHAR(20) NOT NULL,
    `heure_ouverture` TIME DEFAULT NULL,
    `heure_fermeture` TIME DEFAULT NULL,
    `ferme` TINYINT(1) NOT NULL DEFAULT 0,

    PRIMARY KEY (`id`),
    UNIQUE KEY `jour` (`jour`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- Jetons de réinitialisation : seul le SHA-256 est conservé.
CREATE TABLE `password_reset_tokens` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `user_id` INT NOT NULL,
    `token_hash` VARCHAR(255) NOT NULL,
    `expires_at` DATETIME NOT NULL,
    `used` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_reset_hash` (`token_hash`),
    KEY `idx_reset_expiration` (`expires_at`),
    CONSTRAINT `fk_password_reset_user` FOREIGN KEY (`user_id`)
        REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Données fictives uniquement. Mot de passe partagé de démonstration : Demo-Vg2026!
-- Retirer ces comptes ou changer leurs mots de passe AVANT exposition publique.
INSERT INTO users (id, nom, prenom, email, password, role, gsm, adresse) VALUES
(4, 'Demo', 'Quentin', 'quentin@example.com', '$2y$10$VA8CGaTV5Tl.VhdjJcpWFOVugUOL.z9lzZXCbe2KcjUNAL4/c6hWi', 'utilisateur', '0600000001', 'Adresse de démonstration, Bordeaux'),
(5, 'Demo', 'Charlie', 'charlie@example.com', '$2y$10$VA8CGaTV5Tl.VhdjJcpWFOVugUOL.z9lzZXCbe2KcjUNAL4/c6hWi', 'employe', '0600000002', 'Adresse de démonstration, Bordeaux'),
(6, 'Demo', 'Corentin', 'corentin@example.com', '$2y$10$VA8CGaTV5Tl.VhdjJcpWFOVugUOL.z9lzZXCbe2KcjUNAL4/c6hWi', 'admin', '0600000003', 'Adresse de démonstration, Bordeaux');

INSERT INTO menus (id, titre, description, prix, nb_personnes_min, theme, regime, stock_disponible, conditions_menu, delai_commande_heures, actif) VALUES
(1, 'Menu Classique', 'Une table généreuse : salade de saison, volaille rôtie et douceur au chocolat.', 120, 4, 'Classique', 'Classique', 20, 'Commander 48 h avant. Conserver les préparations au frais entre 0 et 4 °C.', 48, 1),
(3, 'Menu Vegan', 'Le végétal à l’honneur : légumes de saison, lentilles parfumées et fruits rôtis.', 150, 4, 'Classique', 'Vegan', 20, 'Commander 48 h avant. Conserver au frais. Signaler toute allergie lors du contact.', 48, 1),
(5, 'Menu Noël', 'Un repas de fête autour de saveurs chaleureuses, à partager en famille.', 250, 6, 'Noël', 'Classique', 12, 'Commande au moins 72 h avant la prestation. Conservation au frais.', 72, 1),
(7, 'Menu Printanier', 'Une proposition végétarienne pour célébrer les beaux jours.', 180, 6, 'Pâques', 'Végétarien', 0, 'Ce menu est momentanément indisponible. Commande 48 h à l’avance.', 48, 1),
(8, 'Menu Archives', 'Ancienne proposition désactivée, non visible au catalogue.', 100, 4, 'Classique', 'Classique', 3, 'Menu archivé.', 24, 0);

INSERT INTO plats (id, nom, description, type_plat) VALUES
(1, 'Salade gourmande', 'Salade fraîche et croûtons dorés', 'entree'),
(2, 'Volaille rôtie', 'Volaille, pommes de terre et jus réduit', 'plat'),
(3, 'Fondant au chocolat', 'Chocolat noir et crème légère', 'dessert'),
(4, 'Légumes croquants', 'Légumes de saison et vinaigrette citronnée', 'entree'),
(5, 'Lentilles et légumes rôtis', 'Lentilles parfumées et légumes au four', 'plat'),
(6, 'Fruits rôtis', 'Fruits de saison et sirop vanillé', 'dessert'),
(7, 'Risotto printanier', 'Riz crémeux et légumes verts', 'plat');
INSERT INTO menu_plat (menu_id, plat_id, ordre_affichage) VALUES
(1,1,1),(1,2,2),(1,3,3),(3,4,1),(3,5,2),(3,6,3),(5,1,1),(5,2,2),(5,3,3),(7,4,1),(7,7,2),(7,6,3);
INSERT INTO allergenes (id, nom) VALUES (1,'Gluten'),(2,'Lait'),(3,'Œufs'),(4,'Moutarde');
INSERT INTO plat_allergene (plat_id, allergene_id) VALUES (1,1),(1,4),(3,2),(3,3),(7,2);
INSERT INTO menu_images (menu_id, chemin_image, texte_alternatif) VALUES
(1,'assets/images/menu-classique.svg','Illustration du menu Classique'),
(3,'assets/images/menu-vegan.svg','Illustration du menu Vegan'),
(5,'assets/images/menu-fete.svg','Illustration du menu de fête');

INSERT INTO horaires (jour, heure_ouverture, heure_fermeture, ferme) VALUES
('Lundi','09:00','18:00',0),('Mardi','09:00','18:00',0),('Mercredi','09:00','18:00',0),
('Jeudi','09:00','18:00',0),('Vendredi','09:00','18:00',0),('Samedi','09:00','14:00',0),('Dimanche',NULL,NULL,1);

-- Prix cohérents : base minimale 120/4, remise 10 % dès 9 personnes ; Bordeaux sans frais.
INSERT INTO commandes (id, user_id, menu_id, nb_personnes, prix_total, date_prestation, heure_prestation, lieu_prestation, adresse_prestation, distance_km, frais_livraison, remise_pourcentage, statut, created_at) VALUES
(1,4,1,4,120,DATE_SUB(CURDATE(), INTERVAL 5 DAY),'12:00','Bordeaux','Adresse de démonstration, Bordeaux',0,0,0,'terminée',DATE_SUB(NOW(), INTERVAL 10 DAY)),
(2,4,3,9,303.75,DATE_ADD(CURDATE(), INTERVAL 10 DAY),'12:30','Bordeaux','Adresse de démonstration, Bordeaux',0,0,10,'en attente',NOW()),
(3,4,5,6,266.80,DATE_ADD(CURDATE(), INTERVAL 15 DAY),'19:00','Mérignac','Adresse de démonstration, Mérignac',20,16.80,0,'accepté',NOW());
INSERT INTO historique_statuts (commande_id, statut, modifie_par, date_modification) VALUES
(1,'en attente',4,DATE_SUB(NOW(), INTERVAL 10 DAY)),
(1,'accepté',5,DATE_SUB(NOW(), INTERVAL 9 DAY)),
(1,'en préparation',5,DATE_SUB(NOW(), INTERVAL 5 DAY)),
(1,'en cours de livraison',5,DATE_SUB(NOW(), INTERVAL 5 DAY)),
(1,'livré',5,DATE_SUB(NOW(), INTERVAL 5 DAY)),
(1,'terminée',5,DATE_SUB(NOW(), INTERVAL 5 DAY)),
(2,'en attente',4,NOW()),(3,'en attente',4,NOW()),(3,'accepté',5,NOW());
INSERT INTO avis (commande_id, user_id, note, commentaire, statut_validation) VALUES
(1,4,5,'Une belle table, un repas généreux et une équipe attentionnée. Avis de démonstration.', 'validé');
COMMIT;
