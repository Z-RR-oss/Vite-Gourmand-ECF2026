SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

SET FOREIGN_KEY_CHECKS = 0;

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

    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),

    KEY `idx_commandes_user` (`user_id`),
    KEY `idx_commandes_menu` (`menu_id`),
    KEY `idx_commandes_statut` (`statut`),

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


-- =========================================================
-- DONNEES DE DEMONSTRATION
-- =========================================================

INSERT INTO `users`
(`id`, `nom`, `prenom`, `email`, `password`, `role`, `gsm`, `adresse`)
VALUES
(
    4,
    'Demo',
    'Quentin',
    'quentin@example.com',
    '$2y$10$JAAryi6qxR/MzigVi8kAq.m2YpLJU0nVeUsRl2wtURGhFHecvqOF6',
    'utilisateur',
    '0600000001',
    'Adresse de démonstration'
),
(
    5,
    'Demo',
    'Charlie',
    'charlie@example.com',
    '$2y$10$3PlGEsQur3Rb5EqMS6lKcOAU5pfYcg/eD/2ojsVVeBYQcsfECgUhO',
    'employe',
    '0600000002',
    'Adresse de démonstration'
),
(
    6,
    'Demo',
    'Corentin',
    'corentin@example.com',
    '$2y$10$hyo3uwyoww4zGg.QKjYg4OB8SAFyHIBS1dAawNhFCY4VrbiKCCswa',
    'admin',
    '0600000003',
    'Adresse de démonstration'
);


INSERT INTO `menus`
(`id`, `titre`, `description`, `prix`, `nb_personnes_min`,
 `theme`, `regime`, `stock_disponible`,
 `conditions_menu`, `delai_commande_heures`)
VALUES
(
    1,
    'Menu Classique',
    'Entrée, plat, dessert',
    120.00,
    4,
    'Classique',
    'Classique',
    20,
    'À conserver au frais.',
    48
),
(
    3,
    'Menu Vegan',
    'Menu végétal complet',
    150.00,
    4,
    'Classique',
    'Vegan',
    20,
    'À conserver au frais.',
    48
),
(
    5,
    'Menu Noel',
    'Spécial fêtes',
    250.00,
    6,
    'Noël',
    'Classique',
    20,
    'Commande anticipée recommandée.',
    72
);


INSERT INTO `commandes`
(`id`, `user_id`, `menu_id`, `nb_personnes`, `prix_total`, `statut`)
VALUES
(2, 5, 1, 6, 120.00, 'livrée'),
(3, 5, 1, 1, 120.00, 'en attente');


COMMIT;