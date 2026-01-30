-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: jem_database
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `min_birth_year` int(11) DEFAULT NULL,
  `max_birth_year` int(11) DEFAULT NULL,
  `max_age` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `idx_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (5,'Fraldinha',2017,2018,0,'2025-11-27 18:25:34','2025-11-27 18:25:34'),(6,'Pre-Mirin',2014,2016,0,'2025-11-27 18:25:34','2025-11-27 18:40:17'),(7,'Mirin',2012,2014,0,'2025-11-27 18:25:34','2025-11-28 19:52:58'),(8,'Mirin 2',2010,2011,0,'2025-11-27 18:25:34','2025-11-27 18:25:34');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `enrollments`
--

DROP TABLE IF EXISTS `enrollments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `enrollments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `registration_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_enrollment` (`student_id`,`registration_id`),
  KEY `idx_student` (`student_id`),
  KEY `idx_registration` (`registration_id`),
  CONSTRAINT `enrollments_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `enrollments_ibfk_2` FOREIGN KEY (`registration_id`) REFERENCES `registrations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `enrollments`
--

LOCK TABLES `enrollments` WRITE;
/*!40000 ALTER TABLE `enrollments` DISABLE KEYS */;
INSERT INTO `enrollments` VALUES (7,5,7,'2025-11-28 19:53:33'),(8,5,8,'2025-11-28 20:00:42'),(9,7,8,'2025-11-28 20:00:46'),(10,6,8,'2025-11-28 20:00:50');
/*!40000 ALTER TABLE `enrollments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `modalities`
--

DROP TABLE IF EXISTS `modalities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `modalities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `allows_mixed` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `idx_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `modalities`
--

LOCK TABLES `modalities` WRITE;
/*!40000 ALTER TABLE `modalities` DISABLE KEYS */;
INSERT INTO `modalities` VALUES (1,'Futsal',0,'2025-11-26 13:21:37','2025-11-26 13:21:37'),(2,'Atletismo 50m',0,'2025-11-26 13:21:37','2025-11-26 15:13:55'),(5,'Atletismo 100m',0,'2025-11-26 13:21:37','2025-11-27 19:27:18'),(7,'Cabo de guerra',1,'2025-11-26 13:21:37','2025-11-26 15:13:20'),(8,'Queimada',1,'2025-11-26 13:21:37','2025-11-26 15:13:11'),(9,'Atletismo 1000m',0,'2025-11-26 15:14:21','2025-11-26 15:14:21'),(10,'Atletismo 4x100m',0,'2025-11-26 15:14:36','2025-11-26 15:14:36'),(11,'Atletismo 4x50m',0,'2025-11-26 15:14:48','2025-11-26 15:14:48');
/*!40000 ALTER TABLE `modalities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `registration_requests`
--

DROP TABLE IF EXISTS `registration_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `registration_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `cpf` varchar(14) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `school_name` varchar(255) NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `registration_requests`
--

LOCK TABLES `registration_requests` WRITE;
/*!40000 ALTER TABLE `registration_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `registration_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `registrations`
--

DROP TABLE IF EXISTS `registrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `registrations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `school_id` int(11) NOT NULL,
  `modality_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `gender` enum('M','F','mixed') NOT NULL,
  `created_by_user_id` int(11) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `rejection_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_registration` (`school_id`,`modality_id`,`category_id`,`gender`),
  KEY `idx_school` (`school_id`),
  KEY `idx_modality` (`modality_id`),
  KEY `idx_category` (`category_id`),
  KEY `idx_status` (`status`),
  KEY `idx_created_by` (`created_by_user_id`),
  CONSTRAINT `registrations_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `registrations_ibfk_2` FOREIGN KEY (`modality_id`) REFERENCES `modalities` (`id`) ON DELETE CASCADE,
  CONSTRAINT `registrations_ibfk_3` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `registrations_ibfk_4` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `registrations`
--

LOCK TABLES `registrations` WRITE;
/*!40000 ALTER TABLE `registrations` DISABLE KEYS */;
INSERT INTO `registrations` VALUES (7,2,5,7,'M',3,'pending',NULL,'2025-11-28 19:53:28','2025-11-28 19:53:28'),(8,2,8,7,'mixed',2,'pending',NULL,'2025-11-28 20:00:34','2025-11-28 20:00:34');
/*!40000 ALTER TABLE `registrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `schools`
--

DROP TABLE IF EXISTS `schools`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `schools` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `municipality` varchar(100) NOT NULL,
  `address` text DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `director` varchar(255) DEFAULT NULL,
  `coordinator` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_name` (`name`),
  KEY `idx_municipality` (`municipality`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `schools`
--

LOCK TABLES `schools` WRITE;
/*!40000 ALTER TABLE `schools` DISABLE KEYS */;
INSERT INTO `schools` VALUES (2,'ESCOLA HILDEMAR PEREIRA DE FIGUEREDO','RORAINÓPOLIS','CENTRO','(95) 98425-8130','','RICARDO AROUCHA','MILZA SEIXAS','2025-11-28 16:18:35','2025-11-28 16:18:35'),(3,'ESCOLA JOSELMA LIMA DE SOUZA','RORAINÓPOLIS','SUELANDIA','(95) 99166-3891','','DAVID FARIAS','ANA CLEIDE','2025-11-28 16:20:19','2025-11-28 16:20:35'),(4,'ESCOLA MUNICIPAL ORDALHA ARAÚJO DE LIMA','RORAINOPOLIS','Avenida Tancredo Neves, SN - Novo Horizonte','','','','','2025-11-28 16:23:20','2025-11-28 16:23:20'),(5,'ESCOLA MUNICIPAL VÓ HILDA KLENNIVING DA SILVA','RORAINÓPOLIS','Rua Daniel Silva Costa, SN - Gentil Carneiro Brito','','','','','2025-11-28 16:24:10','2025-11-28 16:24:10'),(6,'ESCOLA MUNICIPAL JEAN DE SOUSA OLIVEIRA','RORAINÓPOLIS','Rua F, SN - Parque das Orquídeas','','','','','2025-11-28 16:25:06','2025-11-28 16:25:06'),(7,'ESCOLA MUNICIPAL PROFESSORA TEREZINHA DE JESUS','RORAINOPOLIS','DISTRITO DE MARTINS PEREIRA','(95) 98421-2103','','','','2025-11-28 16:26:00','2025-11-28 16:30:27'),(8,'ESCOLA MUNICIPAL JOÃO MAIA DA SILVA','RORAINÓPOLIS','Vicinal 16 - Distrito Nova Colina - Zona Rural','','','','','2025-11-28 16:26:43','2025-11-28 16:26:43'),(9,'ESCOLA MUNICIPAL JOSEFA DA SILVA GOMES','RORAINÓPOLIS','Rodovia BR 174, N° 172 - Distrito de Nova Colina, Centro','','','','','2025-11-28 16:27:22','2025-11-28 16:27:22'),(10,'ESCOLA MUNICIPAL ZILDETH PUGA ROCHA','RORAINÓPOLIS','Rodovia BR 174, 330 - Distrito de Jundiá','','','','','2025-11-28 16:27:59','2025-11-28 16:28:28'),(11,'ESCOLA MUNICIPAL PEDRO MOLETA','RORAINÓPOLIS','Rua Luzinete Cantão - Distrito Equador, Centro','','','','','2025-11-28 16:29:05','2025-11-28 16:29:05');
/*!40000 ALTER TABLE `schools` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `students`
--

DROP TABLE IF EXISTS `students`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `students` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `document_number` varchar(30) NOT NULL,
  `birth_date` date NOT NULL,
  `gender` enum('M','F') NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `rg` varchar(20) DEFAULT NULL,
  `guardian_name` varchar(255) DEFAULT NULL,
  `guardian_phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `document_path` varchar(255) DEFAULT NULL,
  `school_id` int(11) NOT NULL,
  `created_by_user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `cpf` (`document_number`),
  UNIQUE KEY `document_number` (`document_number`),
  KEY `idx_name` (`name`),
  KEY `idx_cpf` (`document_number`),
  KEY `idx_school` (`school_id`),
  KEY `idx_birth_date` (`birth_date`),
  KEY `idx_student_created_by` (`created_by_user_id`),
  CONSTRAINT `students_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `students_ibfk_2` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `students`
--

LOCK TABLES `students` WRITE;
/*!40000 ALTER TABLE `students` DISABLE KEYS */;
INSERT INTO `students` VALUES (5,'HEITOR RODRIGUES NASCIMENTO','78946','2014-02-19','M','95991427180',11,NULL,NULL,NULL,NULL,'uploads/photos/6929fd158730a.jpg','uploads/documents/6929fd1587b1b.jpeg',2,3,'2025-11-28 19:50:45','2025-11-28 19:50:45'),(6,'THAYLA SOPHIA SOUZA DA SILVA','78945555','2013-06-13','F','95984147716',12,NULL,NULL,NULL,NULL,'uploads/photos/6929fefde1bf8.jpg','uploads/documents/6929fefde236b.jpeg',2,2,'2025-11-28 19:58:53','2025-11-28 19:58:53'),(7,'PALOMA DA CONCEIÇÃO ALMEIDA','021135454','2013-08-10','F','95991197558',12,NULL,NULL,NULL,NULL,'uploads/photos/6929ffd24115b.png','uploads/documents/6929ffd241e0f.png',2,2,'2025-11-28 20:00:08','2025-11-28 20:02:26');
/*!40000 ALTER TABLE `students` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `cpf` varchar(14) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('admin','professor') NOT NULL,
  `school_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `cpf` (`cpf`),
  KEY `idx_email` (`email`),
  KEY `idx_role` (`role`),
  KEY `idx_school` (`school_id`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Administrador','admin@jem.com','$2y$10$DMDYkx7FRu8Co.iImtjwi.LGGjkH/3iuIkEMvNVHxsL6xljp3Qg9a',NULL,NULL,'admin',NULL,1,'2025-11-26 13:21:37','2025-11-26 13:21:37'),(2,'ITAMAR VIEIRA NUNES','itamar@jem.com','$2y$10$Uw2L0NC8uCOTW7F3fX.NOufu4bPKa2G6Rl2SS9JOgXqLU1o1jrCOa','044.615.235-86','(95) 99124-8941','professor',2,1,'2025-11-26 14:06:07','2025-11-28 19:57:09'),(3,'Cristiano da Silva do Equador','cristiano@jem.com','$2y$10$jL2hWTAHe25SVGoqNfPED.nAcQJbsykIkUNdVIfw7SSrFz8rbYx7S','662.044.532-20','(95) 99155-5555','professor',2,1,'2025-11-28 15:21:34','2025-11-28 19:49:53');
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

-- Dump completed on 2025-11-30 20:21:19
