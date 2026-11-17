-- MySQL dump 10.13  Distrib 8.0.46, for Win64 (x86_64)
--
-- Host: localhost    Database: fair-repair
-- ------------------------------------------------------
-- Server version	8.0.46

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admins` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `prenom` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `nom` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(190) COLLATE utf8mb4_general_ci NOT NULL,
  `mot_de_passe` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admins`
--

LOCK TABLES `admins` WRITE;
/*!40000 ALTER TABLE `admins` DISABLE KEYS */;
INSERT INTO `admins` VALUES (1,'Admin','Système','admin@fairrepair.fr','$2y$10$jQYSaOq2fmCytu.eeNoEme17Juo0imiin.QnvtL/NE150qmBOttDa','2026-09-21 20:49:10','2026-09-21 20:49:10');
/*!40000 ALTER TABLE `admins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `avis`
--

DROP TABLE IF EXISTS `avis`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `avis` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `reservation_id` int unsigned NOT NULL,
  `note` tinyint unsigned NOT NULL,
  `commentaire` text COLLATE utf8mb4_general_ci NOT NULL,
  `modere_par_id` int unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_avis_reservation` (`reservation_id`),
  KEY `fk_avis_moderateur` (`modere_par_id`),
  KEY `idx_note` (`note`),
  CONSTRAINT `fk_avis_moderateur` FOREIGN KEY (`modere_par_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_avis_reservation` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `avis`
--

LOCK TABLES `avis` WRITE;
/*!40000 ALTER TABLE `avis` DISABLE KEYS */;
INSERT INTO `avis` VALUES (1,2,5,'Révision très complète, vélo comme neuf. Réparateur sérieux, ponctuel et de bon conseil !',NULL,'2026-09-21 20:49:10','2026-09-21 20:49:10');
/*!40000 ALTER TABLE `avis` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `paiements`
--

DROP TABLE IF EXISTS `paiements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `paiements` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `reservation_id` int unsigned NOT NULL,
  `montant` decimal(10,2) NOT NULL,
  `date_paiement` datetime DEFAULT NULL,
  `statut` enum('en_attente','paye','rembourse') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'en_attente',
  `mode_reglement` enum('carte','especes','virement') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_paiement_reservation` (`reservation_id`),
  KEY `idx_statut` (`statut`),
  CONSTRAINT `fk_paiement_reservation` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `paiements`
--

LOCK TABLES `paiements` WRITE;
/*!40000 ALTER TABLE `paiements` DISABLE KEYS */;
INSERT INTO `paiements` VALUES (1,1,25.00,NULL,'en_attente',NULL,'2026-09-21 20:49:10'),(2,2,60.00,'2026-09-14 00:00:00','paye','carte','2026-09-21 20:49:10'),(3,3,20.00,NULL,'en_attente',NULL,'2026-09-21 20:49:10');
/*!40000 ALTER TABLE `paiements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `questions_secretes`
--

DROP TABLE IF EXISTS `questions_secretes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `questions_secretes` (
  `id_questions` int NOT NULL AUTO_INCREMENT,
  `questions` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id_questions`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `questions_secretes`
--

LOCK TABLES `questions_secretes` WRITE;
/*!40000 ALTER TABLE `questions_secretes` DISABLE KEYS */;
INSERT INTO `questions_secretes` VALUES (1,'Quel est le nom de ton premier professeur de mathématiques ?'),(2,'Quel est le nom de ton jouet préféré quand tu étais enfant ?'),(3,'Quel est le nom de la rue où vivait ton meilleur ami d’enfance ?');
/*!40000 ALTER TABLE `questions_secretes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reparateurs`
--

DROP TABLE IF EXISTS `reparateurs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reparateurs` (
  `id_user` int unsigned NOT NULL,
  `siret` varchar(14) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `specialite` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id_user`),
  CONSTRAINT `fk_reparateur_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reparateurs`
--

LOCK TABLES `reparateurs` WRITE;
/*!40000 ALTER TABLE `reparateurs` DISABLE KEYS */;
INSERT INTO `reparateurs` VALUES (1,'53264327800015','Vélos de ville, VAE et réparations à domicile');
/*!40000 ALTER TABLE `reparateurs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reponses_secretes`
--

DROP TABLE IF EXISTS `reponses_secretes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reponses_secretes` (
  `id_reponses` int NOT NULL AUTO_INCREMENT,
  `id_questions` int DEFAULT NULL,
  `id_users` int unsigned DEFAULT NULL,
  `reponses` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id_reponses`),
  KEY `id_questions` (`id_questions`),
  KEY `id_users` (`id_users`),
  CONSTRAINT `reponses_secretes_ibfk_1` FOREIGN KEY (`id_questions`) REFERENCES `questions_secretes` (`id_questions`) ON DELETE CASCADE,
  CONSTRAINT `reponses_secretes_ibfk_2` FOREIGN KEY (`id_users`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reponses_secretes`
--

LOCK TABLES `reponses_secretes` WRITE;
/*!40000 ALTER TABLE `reponses_secretes` DISABLE KEYS */;
INSERT INTO `reponses_secretes` VALUES (2,1,10,'$2y$10$TPrx8gGgkiEQndtUjETAhuPBiFjG/tfanMN.h0Mb7ng5ROE02.XTO');
/*!40000 ALTER TABLE `reponses_secretes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reservations`
--

DROP TABLE IF EXISTS `reservations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reservations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `client_id` int unsigned NOT NULL,
  `service_id` int unsigned NOT NULL,
  `adresse` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `code_postal` varchar(10) COLLATE utf8mb4_general_ci NOT NULL,
  `ville` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `date_intervention` date NOT NULL,
  `heure_intervention` time NOT NULL,
  `type` enum('immediate','planifiee') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'planifiee',
  `statut` enum('en_attente','confirmee','annulee','terminee') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'en_attente',
  `montant` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_client` (`client_id`),
  KEY `idx_service` (`service_id`),
  KEY `idx_statut` (`statut`),
  CONSTRAINT `fk_reserv_client` FOREIGN KEY (`client_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_reserv_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reservations`
--

LOCK TABLES `reservations` WRITE;
/*!40000 ALTER TABLE `reservations` DISABLE KEYS */;
INSERT INTO `reservations` VALUES (1,2,1,'5 Avenue des Champs-Élysées','75008','Paris','2026-09-24','14:00:00','planifiee','confirmee',25.00,'2026-09-21 20:49:10','2026-09-21 20:49:10'),(2,2,2,'5 Avenue des Champs-Élysées','75008','Paris','2026-09-14','10:00:00','planifiee','terminee',60.00,'2026-09-21 20:49:10','2026-09-21 20:49:10'),(3,2,3,'10 Rue de Rivoli','75004','Paris','2026-09-26','16:30:00','planifiee','en_attente',20.00,'2026-09-21 20:49:10','2026-09-21 20:49:10');
/*!40000 ALTER TABLE `reservations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `service_liaisons`
--

DROP TABLE IF EXISTS `service_liaisons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `service_liaisons` (
  `service_parent_id` int unsigned NOT NULL,
  `service_enfant_id` int unsigned NOT NULL,
  PRIMARY KEY (`service_parent_id`,`service_enfant_id`),
  KEY `fk_liaison_enfant` (`service_enfant_id`),
  CONSTRAINT `fk_liaison_enfant` FOREIGN KEY (`service_enfant_id`) REFERENCES `services` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_liaison_parent` FOREIGN KEY (`service_parent_id`) REFERENCES `services` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `service_liaisons`
--

LOCK TABLES `service_liaisons` WRITE;
/*!40000 ALTER TABLE `service_liaisons` DISABLE KEYS */;
INSERT INTO `service_liaisons` VALUES (2,3);
/*!40000 ALTER TABLE `service_liaisons` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `services`
--

DROP TABLE IF EXISTS `services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `services` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `titre` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci NOT NULL,
  `tarif` decimal(10,2) NOT NULL,
  `rayon` int unsigned NOT NULL DEFAULT '10',
  `image` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  CONSTRAINT `fk_services_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `services`
--

LOCK TABLES `services` WRITE;
/*!40000 ALTER TABLE `services` DISABLE KEYS */;
INSERT INTO `services` VALUES (1,1,'Réparation crevaison','Réparation de chambre à air ou crevaison rapide sur place.',25.00,10,NULL,'2026-09-21 20:49:10','2026-09-21 20:49:10'),(2,1,'Révision complète','Révision complète du vélo : freins, transmission, pressions.',60.00,15,NULL,'2026-09-21 20:49:10','2026-09-21 20:49:10'),(3,1,'Réglage freins','Réglage et remplacement des plaquettes de freins si nécessaire.',20.00,10,NULL,'2026-09-21 20:49:10','2026-09-21 20:49:10');
/*!40000 ALTER TABLE `services` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `signalements`
--

DROP TABLE IF EXISTS `signalements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `signalements` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `signalant_id` int unsigned NOT NULL,
  `type` enum('avis','service') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'avis',
  `avis_id` int unsigned DEFAULT NULL,
  `service_id` int unsigned DEFAULT NULL,
  `motif` enum('contenu_inapproprie','harcelement','arnaque','faux_avis','autre') COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `statut` enum('en_attente','refuse','traite') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'en_attente',
  `modere_par_id` int unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_signalement_signalant` (`signalant_id`),
  KEY `fk_signalement_avis` (`avis_id`),
  KEY `fk_signalement_service` (`service_id`),
  KEY `fk_signalement_moderateur` (`modere_par_id`),
  KEY `idx_statut` (`statut`),
  KEY `idx_type` (`type`),
  CONSTRAINT `fk_signalement_avis` FOREIGN KEY (`avis_id`) REFERENCES `avis` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_signalement_moderateur` FOREIGN KEY (`modere_par_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_signalement_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_signalement_signalant` FOREIGN KEY (`signalant_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `signalements`
--

LOCK TABLES `signalements` WRITE;
/*!40000 ALTER TABLE `signalements` DISABLE KEYS */;
INSERT INTO `signalements` VALUES (1,3,'avis',1,NULL,'faux_avis','Avis potentiellement posté sans intervention réelle.','en_attente',NULL,'2026-09-21 20:49:10');
/*!40000 ALTER TABLE `signalements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tentatives_connexion`
--

DROP TABLE IF EXISTS `tentatives_connexion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tentatives_connexion` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(190) COLLATE utf8mb4_general_ci NOT NULL,
  `adresse_ip` varchar(45) COLLATE utf8mb4_general_ci NOT NULL,
  `nb_tentatives` int unsigned NOT NULL DEFAULT '0',
  `meme_mdp` int unsigned NOT NULL DEFAULT '0',
  `dernier_mdp_hash` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `verrouille_jusqua` datetime DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_email_ip` (`email`,`adresse_ip`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tentatives_connexion`
--

LOCK TABLES `tentatives_connexion` WRITE;
/*!40000 ALTER TABLE `tentatives_connexion` DISABLE KEYS */;
INSERT INTO `tentatives_connexion` VALUES (7,'rayaneakpo@gmail.com','::1',1,0,'$2y$10$jSxu96QxC56Mh0oBYvAnvuiCBO3HYzccHow/tpsPgxmJiu8d61cG2',NULL,'2026-09-22 12:37:47');
/*!40000 ALTER TABLE `tentatives_connexion` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `prenom` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `nom` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `adresse` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `code_postal` varchar(10) COLLATE utf8mb4_general_ci NOT NULL,
  `ville` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(190) COLLATE utf8mb4_general_ci NOT NULL,
  `telephone` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `mot_de_passe` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `reset_token` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL,
  `role` enum('client','reparateur') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'client',
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_role` (`role`),
  KEY `idx_ville` (`ville`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Jean','Dupont','12 Rue de la Paix','75002','Paris','jean.dupont@repair.fr','0612345678','$2y$10$jQYSaOq2fmCytu.eeNoEme17Juo0imiin.QnvtL/NE150qmBOttDa',NULL,NULL,'reparateur',48.8686390,2.3318890,'2026-09-21 20:49:10','2026-09-21 20:49:10'),(2,'Marie','Martin','5 Avenue des Champs-Élysées','75008','Paris','marie.martin@client.fr','0698765432','$2y$10$jQYSaOq2fmCytu.eeNoEme17Juo0imiin.QnvtL/NE150qmBOttDa',NULL,NULL,'client',48.8698670,2.3079460,'2026-09-21 20:49:10','2026-09-21 20:49:10'),(3,'Lucas','Bernard','22 Rue de la Roquette','75011','Paris','lucas.bernard@client.fr','0611223344','$2y$10$jQYSaOq2fmCytu.eeNoEme17Juo0imiin.QnvtL/NE150qmBOttDa',NULL,NULL,'client',48.8566000,2.3708000,'2026-09-21 20:49:10','2026-09-21 20:49:10'),(4,'Rayane','AKPO','1 Rue de Paradis','95100','argenteuil','rayaneakpo@gmail.com','0759574907','$2y$10$s1.MmXZPeadA6U6H1vQiv.FpzEki6fYvU41PG39Y4RRsBYtheGGP6',NULL,NULL,'client',NULL,NULL,'2026-09-22 08:00:42','2026-09-22 08:00:42'),(7,'toi','moi','1 Rue de Paradis','95100','argenteuil','toimoi@gmail.com','0759574907','$2y$10$s/XjfH0dPrpkdtcg.RJUwOHSPn.K1MQRUvbUwTTvwui6sPmiiDvFu',NULL,NULL,'client',NULL,NULL,'2026-09-22 12:38:57','2026-09-22 12:38:57'),(10,'toi','toi','1 Rue de Paradis','95100','argenteuil','toitoi@gmail.com','0759574907','$2y$10$GhwU5wxe7TZQCwodFk2H7.UkcU7uF7fmxhUe.WN7x3mcSlgjLAWCu',NULL,NULL,'client',NULL,NULL,'2026-09-22 13:37:45','2026-09-22 13:37:45');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-09-22 15:53:42
