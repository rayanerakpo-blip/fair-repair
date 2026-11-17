-- ============================================================
--  Fair'repair — script de réinitialisation complète
--  Recrée la base `fair-repair` avec un jeu de données de démo :
--    * 1 administrateur
--    * 3 utilisateurs (1 réparateur, 2 clients) — mot de passe : password123
--    * 3 prestations, 3 réservations (dates relatives à aujourd'hui),
--      1 avis, 3 paiements, 1 signalement
--    * Questions secrètes (3) + réponses de démo (« Monsieur Martin »)
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
CREATE DATABASE IF NOT EXISTS `fair-repair` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `fair-repair`;

DROP TABLE IF EXISTS tentatives_connexion;
DROP TABLE IF EXISTS signalements;
DROP TABLE IF EXISTS avis;
DROP TABLE IF EXISTS paiements;
DROP TABLE IF EXISTS service_liaisons;
DROP TABLE IF EXISTS reponses_secretes;
DROP TABLE IF EXISTS questions_secretes;
DROP TABLE IF EXISTS reservations;
DROP TABLE IF EXISTS reparateurs;
DROP TABLE IF EXISTS services;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS admins;

-- -----------------------------------------------------------
-- Structure conforme au schéma de la base en production (users.id,
-- questions_secretes.id_questions, reponses_secretes.id_reponses)
-- -----------------------------------------------------------

CREATE TABLE IF NOT EXISTS admins (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    prenom VARCHAR(100) NOT NULL,
    nom VARCHAR(100) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    prenom VARCHAR(100) NOT NULL,
    nom VARCHAR(100) NOT NULL,
    adresse VARCHAR(255) NOT NULL,
    code_postal VARCHAR(10) NOT NULL,
    ville VARCHAR(100) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    telephone VARCHAR(20) NOT NULL,
    mot_de_passe VARCHAR(255) NOT NULL,
    reset_token VARCHAR(255) NULL,
    reset_expires DATETIME NULL,
    role ENUM('client', 'reparateur') NOT NULL DEFAULT 'client',
    latitude DECIMAL(10, 7) NULL,
    longitude DECIMAL(10, 7) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_role (role),
    INDEX idx_ville (ville)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS reparateurs (
    id_user INT UNSIGNED PRIMARY KEY,
    siret VARCHAR(14) NULL,
    specialite VARCHAR(100) NULL,
    CONSTRAINT fk_reparateur_user FOREIGN KEY (id_user) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS services (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    titre VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    tarif DECIMAL(10, 2) NOT NULL,
    rayon INT UNSIGNED NOT NULL DEFAULT 10,
    image VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_services_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS reservations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id INT UNSIGNED NOT NULL,
    service_id INT UNSIGNED NOT NULL,
    adresse VARCHAR(255) NOT NULL,
    code_postal VARCHAR(10) NOT NULL,
    ville VARCHAR(100) NOT NULL,
    date_intervention DATE NOT NULL,
    heure_intervention TIME NOT NULL,
    type ENUM('immediate', 'planifiee') NOT NULL DEFAULT 'planifiee',
    statut ENUM('en_attente', 'confirmee', 'annulee', 'terminee') NOT NULL DEFAULT 'en_attente',
    montant DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_reserv_client FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_reserv_service FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
    INDEX idx_client (client_id),
    INDEX idx_service (service_id),
    INDEX idx_statut (statut)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS paiements (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reservation_id INT UNSIGNED NOT NULL,
    montant DECIMAL(10, 2) NOT NULL,
    date_paiement DATETIME NULL,
    statut ENUM('en_attente', 'paye', 'rembourse') NOT NULL DEFAULT 'en_attente',
    mode_reglement ENUM('carte', 'especes', 'virement') NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_paiement_reservation UNIQUE (reservation_id),
    CONSTRAINT fk_paiement_reservation FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE,
    INDEX idx_statut (statut)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS service_liaisons (
    service_parent_id INT UNSIGNED NOT NULL,
    service_enfant_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (service_parent_id, service_enfant_id),
    CONSTRAINT fk_liaison_parent FOREIGN KEY (service_parent_id) REFERENCES services(id) ON DELETE CASCADE,
    CONSTRAINT fk_liaison_enfant FOREIGN KEY (service_enfant_id) REFERENCES services(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS avis (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reservation_id INT UNSIGNED NOT NULL,
    note TINYINT UNSIGNED NOT NULL,
    commentaire TEXT NOT NULL,
    modere_par_id INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uq_avis_reservation UNIQUE (reservation_id),
    CONSTRAINT fk_avis_reservation FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE,
    CONSTRAINT fk_avis_moderateur FOREIGN KEY (modere_par_id) REFERENCES admins(id) ON DELETE SET NULL,
    INDEX idx_note (note)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS signalements (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    signalant_id INT UNSIGNED NOT NULL,
    type ENUM('avis', 'service') NOT NULL DEFAULT 'avis',
    avis_id INT UNSIGNED NULL,
    service_id INT UNSIGNED NULL,
    motif ENUM('contenu_inapproprie', 'harcelement', 'arnaque', 'faux_avis', 'autre') NOT NULL,
    description TEXT NULL,
    statut ENUM('en_attente', 'refuse', 'traite') NOT NULL DEFAULT 'en_attente',
    modere_par_id INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_signalement_signalant FOREIGN KEY (signalant_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_signalement_avis FOREIGN KEY (avis_id) REFERENCES avis(id) ON DELETE CASCADE,
    CONSTRAINT fk_signalement_service FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
    CONSTRAINT fk_signalement_moderateur FOREIGN KEY (modere_par_id) REFERENCES admins(id) ON DELETE SET NULL,
    INDEX idx_statut (statut),
    INDEX idx_type (type)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tentatives_connexion (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190) NOT NULL,
    adresse_ip VARCHAR(45) NOT NULL,
    nb_tentatives INT UNSIGNED NOT NULL DEFAULT 0,
    meme_mdp INT UNSIGNED NOT NULL DEFAULT 0,
    dernier_mdp_hash VARCHAR(255) NULL,
    verrouille_jusqua DATETIME NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_email_ip (email, adresse_ip)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS questions_secretes (
    id_questions INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    questions VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS reponses_secretes (
    id_reponses INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    id_questions INT NULL,
    id_users INT UNSIGNED NULL,
    reponses VARCHAR(255) NOT NULL,
    CONSTRAINT fk_reponses_question FOREIGN KEY (id_questions)
        REFERENCES questions_secretes(id_questions) ON DELETE CASCADE,
    CONSTRAINT fk_reponses_user FOREIGN KEY (id_users)
        REFERENCES users(id)
) ENGINE=InnoDB;

-- -----------------------------------------------------------
-- Jeu de données de démo
-- Mot de passe commun des comptes démo : password123
-- Réponse secrète de démo : « Monsieur Martin »
-- -----------------------------------------------------------

INSERT INTO admins (prenom, nom, email, mot_de_passe) VALUES
('Admin', 'Système', 'admin@fairrepair.fr', '$2y$10$jQYSaOq2fmCytu.eeNoEme17Juo0imiin.QnvtL/NE150qmBOttDa');

INSERT INTO users (prenom, nom, adresse, code_postal, ville, email, telephone, mot_de_passe, role, latitude, longitude) VALUES
('Jean', 'Dupont', '12 Rue de la Paix', '75002', 'Paris', 'jean.dupont@repair.fr', '0612345678', '$2y$10$jQYSaOq2fmCytu.eeNoEme17Juo0imiin.QnvtL/NE150qmBOttDa', 'reparateur', 48.868639, 2.331889),
('Marie', 'Martin', '5 Avenue des Champs-Élysées', '75008', 'Paris', 'marie.martin@client.fr', '0698765432', '$2y$10$jQYSaOq2fmCytu.eeNoEme17Juo0imiin.QnvtL/NE150qmBOttDa', 'client', 48.869867, 2.307946),
('Lucas', 'Bernard', '22 Rue de la Roquette', '75011', 'Paris', 'lucas.bernard@client.fr', '0611223344', '$2y$10$jQYSaOq2fmCytu.eeNoEme17Juo0imiin.QnvtL/NE150qmBOttDa', 'client', 48.856600, 2.370800);

INSERT INTO reparateurs (id_user, siret, specialite) VALUES
(1, '53264327800015', 'Vélos de ville, VAE et réparations à domicile');

INSERT INTO questions_secretes (id_questions, questions) VALUES
(1, 'Quel est le nom de ton premier professeur de mathématiques ?'),
(2, 'Quel est le nom de ton jouet préféré quand tu étais enfant ?'),
(3, 'Quel est le nom de la rue où vivait ton meilleur ami d''enfance ?');

-- Réponses secrètes de démo (hachées, réponse : « Monsieur Martin ») pour les 3 comptes
INSERT INTO reponses_secretes (id_questions, id_users, reponses) VALUES
(1, 1, '$2y$10$8ZMaFNi9Ow8ebBKl32aZGuC3kJ6bxE3NqbJcOtjB0Nw7tyZwhdC5O'),
(1, 2, '$2y$10$8ZMaFNi9Ow8ebBKl32aZGuC3kJ6bxE3NqbJcOtjB0Nw7tyZwhdC5O'),
(1, 3, '$2y$10$8ZMaFNi9Ow8ebBKl32aZGuC3kJ6bxE3NqbJcOtjB0Nw7tyZwhdC5O');

INSERT INTO services (user_id, titre, description, tarif, rayon) VALUES
(1, 'Réparation crevaison', 'Réparation de chambre à air ou crevaison rapide sur place.', 25.00, 10),
(1, 'Révision complète', 'Révision complète du vélo : freins, transmission, pressions.', 60.00, 15),
(1, 'Réglage freins', 'Réglage et remplacement des plaquettes de freins si nécessaire.', 20.00, 10);

INSERT INTO reservations (client_id, service_id, adresse, code_postal, ville, date_intervention, heure_intervention, type, statut, montant) VALUES
(2, 1, '5 Avenue des Champs-Élysées', '75008', 'Paris', DATE_ADD(CURDATE(), INTERVAL 3 DAY), '14:00:00', 'planifiee', 'confirmee', 25.00),
(2, 2, '5 Avenue des Champs-Élysées', '75008', 'Paris', DATE_SUB(CURDATE(), INTERVAL 7 DAY), '10:00:00', 'planifiee', 'terminee', 60.00),
(2, 3, '10 Rue de Rivoli', '75004', 'Paris', DATE_ADD(CURDATE(), INTERVAL 5 DAY), '16:30:00', 'planifiee', 'en_attente', 20.00);

INSERT INTO paiements (reservation_id, montant, date_paiement, statut, mode_reglement) VALUES
(1, 25.00, NULL, 'en_attente', NULL),
(2, 60.00, DATE_SUB(CURDATE(), INTERVAL 7 DAY), 'paye', 'carte'),
(3, 20.00, NULL, 'en_attente', NULL);

INSERT INTO avis (reservation_id, note, commentaire) VALUES
(2, 5, 'Révision très complète, vélo comme neuf. Réparateur sérieux, ponctuel et de bon conseil !');

INSERT INTO service_liaisons (service_parent_id, service_enfant_id) VALUES
(2, 3);

INSERT INTO signalements (signalant_id, type, avis_id, service_id, motif, description) VALUES
(3, 'avis', 1, NULL, 'faux_avis', 'Avis potentiellement posté sans intervention réelle.');

SET FOREIGN_KEY_CHECKS = 1;