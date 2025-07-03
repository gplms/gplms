-- MySQL dump 10.13  Distrib 8.0.36, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: gplms_general
-- ------------------------------------------------------
-- Server version	8.0.36

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `activity_logs`
--

DROP TABLE IF EXISTS `activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_logs` (
  `log_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `username` varchar(255) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `target_object` varchar(255) DEFAULT NULL,
  `details` text,
  `ip_address` varchar(45) DEFAULT NULL,
  `timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=350 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_logs`
--

LOCK TABLES `activity_logs` WRITE;
/*!40000 ALTER TABLE `activity_logs` DISABLE KEYS */;
INSERT INTO `activity_logs` VALUES (1,1,NULL,'INSERT','users','Added new user: nikolitsa','127.0.0.1','2025-06-18 09:24:11'),(2,1,NULL,'REQUEST_EDIT','library_items','Requested to edit item: nikolitsa (ID: 4)','127.0.0.1','2025-06-18 09:39:44'),(3,1,NULL,'REQUEST_DELETE','library_items','Requested to delete item: nikolitsa (ID: 4)','127.0.0.1','2025-06-18 09:39:51'),(4,1,NULL,'DELETE','library_items','Deleted item: nikolitsalr (ID: 4)','127.0.0.1','2025-06-18 10:00:18'),(5,1,NULL,'DELETE','library_items','Deleted item: nikh (ID: 5)','127.0.0.1','2025-06-18 20:29:14'),(6,1,NULL,'DELETE','library_items','Deleted item: niggaer (ID: 2)','127.0.0.1','2025-06-18 20:47:04'),(7,1,NULL,'DELETE','library_items','Deleted item: nikolitsa2 (ID: 6)','127.0.0.1','2025-06-18 20:55:44'),(8,1,'admin','INSERT','roles','Added new role: testrole','127.0.0.1','2025-06-19 17:52:07'),(9,1,'admin','UPDATE','system_settings','Updated system configuration','127.0.0.1','2025-06-22 11:27:27'),(10,1,'admin','UPDATE','system_settings','Updated system configuration','127.0.0.1','2025-06-22 11:28:08'),(11,1,'admin','UPDATE','system_settings','Updated system configuration','127.0.0.1','2025-06-22 11:28:22'),(12,1,'admin','UPDATE','system_settings','Updated system configuration','127.0.0.1','2025-06-22 11:28:49'),(13,1,'admin','UPDATE','system_settings','Updated system configuration','127.0.0.1','2025-06-22 11:29:40'),(14,1,NULL,'UPDATE','users','Changed status for user ID: 1','127.0.0.1','2025-06-22 11:34:11'),(15,1,NULL,'UPDATE','users','Changed status for user ID: 1','127.0.0.1','2025-06-22 11:39:34'),(16,1,NULL,'UPDATE','users','Updated user: admin','127.0.0.1','2025-06-22 11:39:44'),(17,1,'admin','INSERT','users','Added new user: kiki','127.0.0.1','2025-06-22 11:47:56'),(18,1,'admin','UPDATE','users','Updated user: kikikikiki','127.0.0.1','2025-06-22 11:48:22'),(19,1,'admin','UPDATE','users','Changed status for user ID: 4','127.0.0.1','2025-06-22 11:48:27'),(20,1,'admin','UPDATE','users','Changed status for user ID: 4','127.0.0.1','2025-06-22 11:48:56'),(21,1,'admin','UPDATE','users','Changed status for user ID: 4','127.0.0.1','2025-06-22 11:50:40'),(22,1,'admin','UPDATE','users','Changed status for user ID: 4','127.0.0.1','2025-06-22 11:51:51'),(23,1,'admin','UPDATE','users','Changed status for user ID: 4','127.0.0.1','2025-06-22 11:51:53'),(24,1,'admin','UPDATE','users','Changed status for user ID: 4','127.0.0.1','2025-06-22 11:53:12'),(25,1,'admin','UPDATE','users','Changed status for user ID: 4','127.0.0.1','2025-06-22 11:53:13'),(26,1,'admin','UPDATE','users','Changed status for user ID: 4','127.0.0.1','2025-06-22 11:53:13'),(27,1,'admin','UPDATE','users','Changed status for user ID: 4','127.0.0.1','2025-06-22 11:53:13'),(28,1,'admin','UPDATE','users','Changed status for user ID: 4','127.0.0.1','2025-06-22 11:53:13'),(29,1,'admin','UPDATE','users','Changed status for user ID: 4','127.0.0.1','2025-06-22 11:53:13'),(30,1,'admin','UPDATE','users','Changed status for user ID: 4','127.0.0.1','2025-06-22 11:53:13'),(31,1,'admin','UPDATE','users','Changed status for user ID: 4','127.0.0.1','2025-06-22 11:53:14'),(32,1,'admin','INSERT','users','Added new user: testo','127.0.0.1','2025-06-22 11:58:47'),(33,1,'admin','UPDATE','users','Changed status for user ID: 6','127.0.0.1','2025-06-22 11:58:57'),(34,1,'admin','UPDATE','users','Changed status for user ID: 6','127.0.0.1','2025-06-22 11:59:58'),(35,1,'admin','UPDATE','users','Changed status for user ID: 6','127.0.0.1','2025-06-22 11:59:59'),(36,1,'admin','UPDATE','users','Changed status for user ID: 6','127.0.0.1','2025-06-22 11:59:59'),(37,1,'admin','UPDATE','users','Changed status for user ID: 6','127.0.0.1','2025-06-22 12:00:00'),(38,1,'admin','UPDATE','users','Changed status for user ID: 6','127.0.0.1','2025-06-22 12:00:00'),(39,1,'admin','UPDATE','users','Changed status for user ID: 6','127.0.0.1','2025-06-22 12:00:00'),(40,1,'admin','UPDATE','users','Changed status for user ID: 6','127.0.0.1','2025-06-22 12:00:00'),(41,1,'admin','UPDATE','users','Changed status for user ID: 6','127.0.0.1','2025-06-22 12:00:00'),(42,1,'admin','UPDATE','users','Changed status for user ID: 6','127.0.0.1','2025-06-22 12:00:01'),(43,1,'admin','UPDATE','users','Changed status for user ID: 6','127.0.0.1','2025-06-22 12:00:01'),(44,1,'admin','UPDATE','users','Changed status for user ID: 6','127.0.0.1','2025-06-22 12:00:01'),(45,1,'admin','UPDATE','users','Changed status for user ID: 6','127.0.0.1','2025-06-22 12:00:05'),(46,1,'admin','UPDATE','library_items','Changed status to archived for: Ο Αλέξης Ζορμπάς','127.0.0.1','2025-06-22 15:04:00'),(47,1,'admin','UPDATE','library_items','Changed status to available for: Ο Αλέξης Ζορμπάς','127.0.0.1','2025-06-22 15:04:04'),(48,1,'admin','UPDATE','library_items','Changed status to available for: Ο Αλέξης Ζορμπάς','127.0.0.1','2025-06-22 15:16:59'),(49,1,'admin','UPDATE','library_items','Updated material: Ο Αλέξης Ζορμπάς2','127.0.0.1','2025-06-22 15:17:20'),(50,1,'admin','UPDATE','library_items','Changed status to archived for: Ο Αλέξης Ζορμπάς2','127.0.0.1','2025-06-22 15:17:38'),(51,1,'admin','UPDATE','library_items','Changed status to available for: Ο Αλέξης Ζορμπάς2','127.0.0.1','2025-06-22 15:17:40'),(52,1,'admin','DELETE','library_items','Deleted material: gg','127.0.0.1','2025-06-22 15:17:42'),(53,1,'admin','INSERT','material_types','Added type: menu','127.0.0.1','2025-06-22 15:18:08'),(54,1,'admin','INSERT','users','Added new user: panos','127.0.0.1','2025-06-24 12:28:09'),(55,1,NULL,'DELETE','library_items','Deleted item: Advanced Mathematics (ID: 10)','127.0.0.1','2025-06-25 17:39:30'),(56,1,NULL,'DELETE','library_items','Deleted item: fotis123 (ID: 44)','127.0.0.1','2025-06-25 19:35:55'),(57,1,'admin','UPDATE','users','Updated user: nikolitsa2','127.0.0.1','2025-06-25 19:39:04'),(58,1,'admin','DELETE','users','Deleted user ID: 6','127.0.0.1','2025-06-25 19:39:15'),(59,1,'admin','DELETE','users','Deleted user ID: 6','127.0.0.1','2025-06-25 19:39:17'),(60,1,'admin','INSERT','users','Added new user: fotis','127.0.0.1','2025-06-25 19:40:32'),(61,1,'admin','DELETE','users','Deleted user ID: 6','127.0.0.1','2025-06-25 19:40:32'),(62,1,'admin','UPDATE','users','Changed status for user ID: 8','127.0.0.1','2025-06-25 19:40:37'),(63,1,'admin','UPDATE','users','Changed status for user ID: 8','127.0.0.1','2025-06-25 19:40:41'),(64,1,'admin','UPDATE','users','Changed status for user ID: 8','127.0.0.1','2025-06-25 19:40:50'),(65,1,'admin','INSERT','material_types','Added type: αιστηυτικη','127.0.0.1','2025-06-25 19:43:44'),(66,1,'admin','UPDATE','system_settings','Updated system configuration','127.0.0.1','2025-06-25 19:52:13'),(67,1,NULL,'DELETE','library_items','Deleted item: Data Structures Simplified (ID: 35)','127.0.0.1','2025-06-26 19:44:54'),(68,1,NULL,'DELETE','library_items','Deleted item: Digital Marketing Insights (ID: 39)','127.0.0.1','2025-06-26 19:45:01'),(69,1,NULL,'DELETE','library_items','Deleted item: nikolitsa (ID: 41)','127.0.0.1','2025-06-26 19:46:27'),(70,1,'admin','UPDATE','authors','Updated author: Ada Lovelace','127.0.0.1','2025-06-27 12:13:33'),(71,1,'admin','UPDATE','authors','Updated author: author ner 1','127.0.0.1','2025-06-27 12:24:01'),(72,1,'admin','UPDATE','authors','Updated author: Carl Sagan','127.0.0.1','2025-06-27 12:27:38'),(73,1,'admin','UPDATE','authors','Updated author: Ada Lovelace','127.0.0.1','2025-06-27 12:27:46'),(74,1,'admin','UPDATE','authors','Updated author: Brené Brown','127.0.0.1','2025-06-27 12:28:16'),(75,1,'admin','INSERT','categories','Added category: panos','127.0.0.1','2025-06-27 16:04:40'),(76,1,'admin','UPDATE','categories','Updated category: panos2','127.0.0.1','2025-06-27 16:04:47'),(77,1,'admin','DELETE','categories','Deleted category ID: 24','127.0.0.1','2025-06-27 16:04:54'),(78,1,'admin','DELETE','categories','Deleted category ID: 24','127.0.0.1','2025-06-27 16:06:29'),(79,1,'admin','UPDATE','categories','Updated category: Scientific','127.0.0.1','2025-06-27 16:13:22'),(80,1,'admin','UPDATE','categories','Updated category: Computer Science','127.0.0.1','2025-06-27 16:16:29'),(81,1,'admin','UPDATE','categories','Updated category: Mathematics','127.0.0.1','2025-06-27 16:16:33'),(82,1,'admin','UPDATE','categories','Updated category: Cooking','127.0.0.1','2025-06-27 16:16:41'),(83,1,'admin','UPDATE','categories','Updated category: World History','127.0.0.1','2025-06-27 16:16:47'),(84,1,'admin','INSERT','library_items','Added material: gg','127.0.0.1','2025-06-28 15:43:57'),(85,1,'admin','UPDATE','library_items','Changed status to archived for: gg','127.0.0.1','2025-06-28 16:25:22'),(86,1,'admin','UPDATE','library_items','Changed status to available for: gg','127.0.0.1','2025-06-28 16:25:25'),(87,1,'admin','DELETE','library_items','Deleted material: gg','127.0.0.1','2025-06-28 16:25:28'),(88,1,'admin','INSERT','material_types','Added type: gg','127.0.0.1','2025-06-28 16:26:24'),(89,1,'admin','UPDATE','library_items','Updated material: Introduction to Programming2','127.0.0.1','2025-06-28 16:26:34'),(90,1,'admin','UPDATE','roles','Changed status for role ID: 3','127.0.0.1','2025-06-28 17:08:41'),(91,1,'admin','UPDATE','roles','Changed status for role ID: 3','127.0.0.1','2025-06-28 17:08:45'),(92,1,'admin','INSERT','roles','Added new role: paos','127.0.0.1','2025-06-28 17:08:59'),(93,1,'admin','UPDATE','roles','Changed status for role ID: 3','127.0.0.1','2025-06-28 17:08:59'),(94,1,'admin','INSERT','users','Added new user: normal_user','127.0.0.1','2025-06-28 21:00:03'),(95,1,'admin','UPDATE','system_settings','Updated system configuration','127.0.0.1','2025-06-29 10:44:56'),(96,1,'admin','UPDATE','system_settings','Updated system configuration','127.0.0.1','2025-06-29 10:46:08'),(97,1,'admin','UPDATE','system_settings','Updated system configuration','127.0.0.1','2025-06-29 10:49:34'),(98,1,'admin','UPDATE','system_settings','Updated system configuration','127.0.0.1','2025-06-29 10:49:48'),(99,1,'admin','INSERT','users','Added new user: Panagiotis','127.0.0.1','2025-06-29 20:29:18'),(100,1,'admin','INSERT','users','Added new user: haris','127.0.0.1','2025-06-29 20:30:11'),(101,1,'admin','UPDATE','users','Updated user: haris23','127.0.0.1','2025-06-29 20:30:21'),(102,1,'admin','INSERT','users','Added new user: sss','127.0.0.1','2025-06-29 20:33:57'),(103,1,'admin','UPDATE','users','Updated user: kikikikikid','127.0.0.1','2025-06-29 20:41:27'),(104,1,'admin','INSERT','users','Added new user: asasasa','127.0.0.1','2025-06-29 20:41:44'),(105,1,'admin','UPDATE','users','Updated user: asasasaddd','127.0.0.1','2025-06-29 20:41:50'),(106,1,'admin','UPDATE','users','Updated user: kikikikikid','127.0.0.1','2025-06-29 20:42:02'),(107,1,'admin','UPDATE','roles','Updated role: testrole2','127.0.0.1','2025-06-29 21:17:38'),(108,1,'admin','UPDATE','roles','Updated role: testrole23','127.0.0.1','2025-06-29 21:22:25'),(109,1,'admin','INSERT','roles','Added new role: gg','127.0.0.1','2025-06-29 21:22:30'),(110,1,'admin','UPDATE','publishers','Updated publisher: hello publisher2','127.0.0.1','2025-06-30 07:03:59'),(111,1,'admin','UPDATE','publishers','Updated publisher: hello publisher23','127.0.0.1','2025-06-30 07:07:28'),(112,1,'admin','INSERT','publishers','Added publisher: dd','127.0.0.1','2025-06-30 07:07:35'),(113,1,'admin','UPDATE','publishers','Updated publisher: dddd','127.0.0.1','2025-06-30 07:07:42'),(114,1,'admin','UPDATE','publishers','Updated publisher: Science Pressx','127.0.0.1','2025-06-30 07:10:36'),(115,1,'admin','UPDATE','authors','Updated author: Ada Lovelace','127.0.0.1','2025-06-30 07:15:00'),(116,1,'admin','UPDATE','authors','Updated author: Ada Lovelace2','127.0.0.1','2025-06-30 07:26:54'),(117,1,'admin','INSERT','authors','Added author: fgfg','127.0.0.1','2025-06-30 07:27:02'),(118,1,'admin','UPDATE','authors','Updated author: Alan Turing','127.0.0.1','2025-06-30 07:27:14'),(119,1,'admin','UPDATE','system_settings','Updated system configuration','127.0.0.1','2025-06-30 08:01:05'),(120,1,'admin','UPDATE','system_settings','Updated system configuration','127.0.0.1','2025-06-30 08:11:44'),(121,1,'admin','UPDATE','system_settings','Updated system configuration','127.0.0.1','2025-06-30 08:20:48'),(122,1,'admin','UPDATE','system_settings','Updated system configuration','127.0.0.1','2025-06-30 08:22:10'),(123,1,'admin','UPDATE','system_settings','Updated system configuration','127.0.0.1','2025-06-30 08:44:35'),(124,1,'admin','UPDATE','system_settings','Updated system configuration','127.0.0.1','2025-06-30 08:56:06'),(125,1,'admin','UPDATE','system_settings','Updated system configuration','127.0.0.1','2025-06-30 09:00:38'),(126,1,'admin','UPDATE','system_settings','Updated system configuration','127.0.0.1','2025-06-30 09:00:46'),(127,1,'admin','UPDATE','system_settings','Updated system configuration','127.0.0.1','2025-06-30 09:08:54'),(128,1,'admin','UPDATE','system_settings','Updated system configuration','127.0.0.1','2025-06-30 09:09:05'),(129,1,'admin','UPDATE','system_settings','Updated system configuration','127.0.0.1','2025-06-30 09:09:15'),(130,1,'admin','UPDATE','system_settings','Updated system configuration','127.0.0.1','2025-06-30 09:15:23'),(131,1,'admin','UPDATE','system_settings','Updated system configuration','127.0.0.1','2025-06-30 09:26:12'),(132,1,'admin','UPDATE','system_settings','Updated system configuration','127.0.0.1','2025-06-30 09:32:03'),(133,1,'admin','UPDATE','system_settings','Updated system configuration','127.0.0.1','2025-06-30 09:33:27'),(134,1,'admin','UPDATE','system_settings','Updated system configuration','127.0.0.1','2025-06-30 09:55:07'),(135,1,'admin','UPDATE','system_settings','Updated system configuration','127.0.0.1','2025-06-30 10:09:15'),(136,1,'admin','UPDATE','system_settings','Updated system configuration','127.0.0.1','2025-06-30 12:12:18'),(137,1,'admin','UPDATE','system_settings','Updated system configuration','127.0.0.1','2025-06-30 12:12:30'),(138,1,'admin','UPDATE','system_settings','Updated system configuration','127.0.0.1','2025-06-30 12:12:52'),(139,1,'admin','UPDATE','roles','Changed status for role ID: 5','127.0.0.1','2025-06-30 12:19:32'),(140,1,'admin','UPDATE','system_settings','Updated system configuration','127.0.0.1','2025-06-30 12:33:08'),(141,1,'admin','UPDATE','system_settings','Updated system configuration','127.0.0.1','2025-06-30 12:37:39'),(142,1,'admin','UPDATE','roles','Changed status for role ID: 3','127.0.0.1','2025-06-30 12:41:42'),(143,1,'admin','UPDATE','roles','Changed status for role ID: 5','127.0.0.1','2025-06-30 12:41:43'),(144,1,'admin','UPDATE','system_settings','Updated system configuration','127.0.0.1','2025-06-30 12:42:02'),(145,1,'admin','DELETE','roles','Deleted role ID: 6','127.0.0.1','2025-06-30 12:45:13'),(146,1,'admin','UPDATE','roles','Updated role: panos','127.0.0.1','2025-06-30 12:45:20'),(147,1,'admin','INSERT','roles','Added new role: nikos','127.0.0.1','2025-06-30 12:45:29'),(148,1,'admin','UPDATE','system_settings','Updated system configuration','127.0.0.1','2025-06-30 12:47:04'),(149,1,'admin','UPDATE','system_settings','Updated system configuration','127.0.0.1','2025-06-30 12:54:36'),(150,1,'admin','UPDATE','system_settings','Updated system configuration','127.0.0.1','2025-06-30 13:02:27'),(151,1,'admin','UPDATE','system_settings','Updated system configuration','127.0.0.1','2025-06-30 13:06:05'),(152,1,'admin','UPDATE','system_settings','Updated system configuration','127.0.0.1','2025-06-30 13:06:13'),(153,1,'admin','UPDATE','system_settings','Updated system configuration','127.0.0.1','2025-06-30 13:12:48'),(154,1,'admin','UPDATE','system_settings','Updated system configuration','127.0.0.1','2025-06-30 13:21:49'),(155,1,'admin','UPDATE','system_settings','Updated system configuration','127.0.0.1','2025-06-30 13:22:07'),(156,1,'admin','UPDATE','system_settings','Updated system configuration','127.0.0.1','2025-06-30 13:26:07'),(157,1,'admin','UPDATE','system_settings','Updated system configuration','127.0.0.1','2025-06-30 13:40:36'),(158,1,'admin','INSERT','authors','Προστέθηκε συγγραφέας: kkk','127.0.0.1','2025-06-30 13:41:28'),(159,1,'admin','UPDATE','authors','Ενημερώθηκε συγγραφέας: Ada Lovelace23','127.0.0.1','2025-06-30 13:41:35'),(160,1,'admin','UPDATE','authors','Ενημερώθηκε συγγραφέας: Alan Turing2','127.0.0.1','2025-06-30 13:41:44'),(161,1,'admin','UPDATE','system_settings','Updated system configuration','127.0.0.1','2025-06-30 14:04:42'),(162,1,'admin','UPDATE','system_settings','Updated system configuration','127.0.0.1','2025-06-30 14:04:50'),(163,1,'admin','UPDATE','system_settings','Updated system configuration','127.0.0.1','2025-06-30 14:49:29'),(164,1,'admin','UPDATE','system_settings','Updated system configuration','127.0.0.1','2025-06-30 14:59:19'),(165,1,'admin','UPDATE','system_settings','Updated system configuration','127.0.0.1','2025-06-30 14:59:39'),(166,1,'admin','UPDATE','system_settings','Updated system configuration','127.0.0.1','2025-06-30 14:59:49'),(167,1,'admin','UPDATE','system_settings','Updated system configuration','127.0.0.1','2025-06-30 20:11:47'),(168,1,'admin','UPDATE','system_settings','Updated system configuration','127.0.0.1','2025-06-30 20:11:56'),(169,1,'admin','UPDATE','system_settings','Updated system configuration','127.0.0.1','2025-06-30 20:38:04'),(170,1,'admin','UPDATE','system_settings','Updated system configuration','127.0.0.1','2025-06-30 20:38:11'),(171,1,'admin','UPDATE','system_settings','Updated system configuration','127.0.0.1','2025-06-30 20:56:34'),(172,1,'admin','UPDATE','system_settings','Updated system configuration','127.0.0.1','2025-06-30 20:57:11'),(173,1,'admin','UPDATE','system_settings','Updated system configuration','127.0.0.1','2025-06-30 21:05:57'),(174,1,'admin','UPDATE','system_settings','Updated system configuration','127.0.0.1','2025-06-30 21:06:08'),(175,1,'admin','UPDATE','system_settings','Updated system configuration','127.0.0.1','2025-06-30 21:21:40'),(176,1,'admin','UPDATE','system_settings','Updated system configuration','127.0.0.1','2025-06-30 21:22:46'),(177,1,'admin','UPDATE','system_settings','Updated system configuration','127.0.0.1','2025-06-30 21:29:25'),(178,1,'admin','UPDATE','system_settings','Updated system configuration','127.0.0.1','2025-06-30 21:29:56'),(179,1,'admin','UPDATE','system_settings','Updated system configuration','127.0.0.1','2025-06-30 21:34:14'),(180,1,'admin','UPDATE','system_settings','Updated system configuration','127.0.0.1','2025-06-30 21:39:14'),(181,1,'admin','UPDATE','system_settings','Updated system configuration','127.0.0.1','2025-06-30 22:11:07'),(182,1,'admin','UPDATE','system_settings','Updated system configuration','127.0.0.1','2025-07-01 15:36:06'),(183,1,'admin','UPDATE','system_settings','Updated system configuration','127.0.0.1','2025-07-01 15:36:24'),(184,1,'admin','UPDATE','system_settings','Updated system configuration','127.0.0.1','2025-07-01 16:51:36'),(185,1,'admin','UPDATE','system_settings','Updated system configuration','127.0.0.1','2025-07-01 16:51:40'),(186,1,'admin','UPDATE','system_settings','Updated system configuration','127.0.0.1','2025-07-01 17:00:07'),(187,1,'admin','UPDATE','system_settings','Ενημέρωση διαμόρφωσης συστήματος','127.0.0.1','2025-07-01 17:00:36'),(188,1,'admin','UPDATE','system_settings','Ενημέρωση διαμόρφωσης συστήματος','127.0.0.1','2025-07-01 17:55:19'),(189,1,'admin','UPDATE','system_settings','Ενημέρωση διαμόρφωσης συστήματος','127.0.0.1','2025-07-02 08:56:19'),(190,1,'admin','INSERT','users','Προστέθηκε νέος χρήστης: kots','127.0.0.1','2025-07-02 08:58:49'),(191,1,NULL,'DELETE','library_items','Deleted item: KOTSORGIOS (ID: 67)','127.0.0.1','2025-07-02 10:59:04'),(192,1,NULL,'DELETE','library_items','Deleted item: kots23 (ID: 70)','127.0.0.1','2025-07-02 10:59:22'),(193,15,NULL,'REQUEST_DELETE','library_items','Requested to delete item: Economics Quarterly (ID: 26)','127.0.0.1','2025-07-02 11:14:44'),(194,15,NULL,'REQUEST_EDIT','library_items','Requested to edit item: Economics Quarterly (ID: 26)','127.0.0.1','2025-07-02 11:18:34'),(195,1,'admin','UPDATE','system_settings','Ενημέρωση διαμόρφωσης συστήματος','127.0.0.1','2025-07-02 13:31:14'),(196,1,'admin','UPDATE','system_settings','Ενημέρωση διαμόρφωσης συστήματος','127.0.0.1','2025-07-02 13:37:24'),(197,1,'admin','UPDATE','system_settings','Ενημέρωση διαμόρφωσης συστήματος','127.0.0.1','2025-07-02 13:37:57'),(198,1,'admin','UPDATE','system_settings','Ενημέρωση διαμόρφωσης συστήματος','127.0.0.1','2025-07-02 13:39:20'),(199,1,'admin','INSERT','users','Προστέθηκε νέος χρήστης: hhh','127.0.0.1','2025-07-02 13:39:45'),(200,1,'admin','UPDATE','system_settings','Ενημέρωση διαμόρφωσης συστήματος','127.0.0.1','2025-07-02 13:40:12'),(201,1,'admin','UPDATE','system_settings','Ενημέρωση διαμόρφωσης συστήματος','127.0.0.1','2025-07-02 13:44:09'),(202,1,'admin','UPDATE','system_settings','Ενημέρωση διαμόρφωσης συστήματος','127.0.0.1','2025-07-02 14:24:54'),(208,1,'admin','DELETE','users','Διαγράφηκε ο χρήστης ID: 16','127.0.0.1','2025-07-03 11:55:32'),(209,1,'admin','DELETE','users','Διαγράφηκε ο χρήστης ID: 11','127.0.0.1','2025-07-03 11:56:29'),(210,1,'admin','DELETE','users','Διαγράφηκε ο χρήστης ID: 14','127.0.0.1','2025-07-03 11:56:36'),(211,1,'admin','UPDATE','users','Ενημερώθηκε ο χρήστης: kots','127.0.0.1','2025-07-03 11:56:56'),(212,1,'admin','UPDATE','users','Άλλαξε κατάσταση για χρήστη ID: 15','127.0.0.1','2025-07-03 11:57:59'),(213,1,'admin','UPDATE','users','Άλλαξε κατάσταση για χρήστη ID: 7','127.0.0.1','2025-07-03 11:58:11'),(214,1,'admin','DELETE','users','Διαγράφηκε ο χρήστης ID: 4','127.0.0.1','2025-07-03 11:58:45'),(215,1,'admin','DELETE','users','Διαγράφηκε ο χρήστης ID: 8','127.0.0.1','2025-07-03 11:58:52'),(216,1,'admin','DELETE','users','Διαγράφηκε ο χρήστης ID: 12','127.0.0.1','2025-07-03 11:59:08'),(217,1,'admin','DELETE','users','Διαγράφηκε ο χρήστης ID: 13','127.0.0.1','2025-07-03 11:59:12'),(218,1,'admin','DELETE','roles','Deleted role ID: 5','127.0.0.1','2025-07-03 11:59:38'),(219,1,'admin','DELETE','roles','Deleted role ID: 7','127.0.0.1','2025-07-03 11:59:41'),(220,1,'admin','DELETE','roles','Deleted role ID: 3','127.0.0.1','2025-07-03 11:59:43'),(221,1,'admin','DELETE','users','Διαγράφηκε ο χρήστης ID: 3','127.0.0.1','2025-07-03 12:00:06'),(222,1,NULL,'DELETE','library_items','Deleted item: normal_user (ID: 46)','127.0.0.1','2025-07-03 12:00:47'),(223,1,'admin','UPDATE','users','Άλλαξε κατάσταση για χρήστη ID: 9','127.0.0.1','2025-07-03 12:00:56'),(224,1,'admin','DELETE','users','Διαγράφηκε ο χρήστης ID: 9','127.0.0.1','2025-07-03 12:00:58'),(225,1,NULL,'DELETE','library_items','Deleted item: Πανοι; (ID: 74)','127.0.0.1','2025-07-03 12:01:27'),(226,1,'admin','UPDATE','users','Άλλαξε κατάσταση για χρήστη ID: 7','127.0.0.1','2025-07-03 12:03:02'),(227,1,'admin','UPDATE','users','Άλλαξε κατάσταση για χρήστη ID: 15','127.0.0.1','2025-07-03 12:03:03'),(228,1,'admin','UPDATE','users','Ενημερώθηκε ο χρήστης: kotsorgios','127.0.0.1','2025-07-03 12:04:18'),(229,1,'admin','UPDATE','users','Ενημερώθηκε ο χρήστης: user','127.0.0.1','2025-07-03 12:04:52'),(230,1,'admin','UPDATE','users','Ενημερώθηκε ο χρήστης: Panagiotis','127.0.0.1','2025-07-03 12:05:04'),(231,1,'admin','UPDATE','users','Ενημερώθηκε ο χρήστης: Panagiotis','127.0.0.1','2025-07-03 12:09:55'),(232,1,'admin','DELETE','users','Διαγράφηκε ο χρήστης ID: 7','127.0.0.1','2025-07-03 12:13:30'),(233,1,'admin','UPDATE','users','Ενημερώθηκε ο χρήστης: user','127.0.0.1','2025-07-03 12:14:04'),(234,1,NULL,'DELETE','library_items','Deleted item: ww (ID: 72)','127.0.0.1','2025-07-03 12:19:25'),(235,1,NULL,'DELETE','library_items','Deleted item: Ο Αλέξης Ζορμπάς2 (ID: 43)','127.0.0.1','2025-07-03 12:19:58'),(236,1,'admin','DELETE','categories','Διαγράφηκε κατηγορία ID: 4','127.0.0.1','2025-07-03 12:22:37'),(237,1,'admin','DELETE','categories','Διαγράφηκε κατηγορία ID: 26','127.0.0.1','2025-07-03 12:22:45'),(238,1,'admin','DELETE','categories','Διαγράφηκε κατηγορία ID: 25','127.0.0.1','2025-07-03 12:22:47'),(239,1,'admin','DELETE','categories','Διαγράφηκε κατηγορία ID: 22','127.0.0.1','2025-07-03 12:22:51'),(240,1,'admin','DELETE','categories','Διαγράφηκε κατηγορία ID: 23','127.0.0.1','2025-07-03 12:22:53'),(241,1,'admin','DELETE','categories','Διαγράφηκε κατηγορία ID: 21','127.0.0.1','2025-07-03 12:22:55'),(242,1,'admin','DELETE','categories','Διαγράφηκε κατηγορία ID: 19','127.0.0.1','2025-07-03 12:22:57'),(243,1,'admin','DELETE','categories','Διαγράφηκε κατηγορία ID: 18','127.0.0.1','2025-07-03 12:23:00'),(244,1,'admin','DELETE','categories','Διαγράφηκε κατηγορία ID: 15','127.0.0.1','2025-07-03 12:23:03'),(245,1,'admin','DELETE','categories','Διαγράφηκε κατηγορία ID: 13','127.0.0.1','2025-07-03 12:23:05'),(246,1,'admin','DELETE','categories','Διαγράφηκε κατηγορία ID: 9','127.0.0.1','2025-07-03 12:23:12'),(247,1,'admin','DELETE','categories','Διαγράφηκε κατηγορία ID: 10','127.0.0.1','2025-07-03 12:23:17'),(248,1,NULL,'DELETE','library_items','Deleted item: Test Book (ID: 3)','127.0.0.1','2025-07-03 12:23:48'),(249,1,'admin','DELETE','categories','Διαγράφηκε κατηγορία ID: 1','127.0.0.1','2025-07-03 12:23:57'),(250,1,'admin','DELETE','categories','Διαγράφηκε κατηγορία ID: 16','127.0.0.1','2025-07-03 12:24:04'),(251,1,'admin','DELETE','categories','Διαγράφηκε κατηγορία ID: 20','127.0.0.1','2025-07-03 12:24:08'),(252,1,'admin','DELETE','categories','Διαγράφηκε κατηγορία ID: 20','127.0.0.1','2025-07-03 12:24:15'),(253,1,'admin','DELETE','categories','Διαγράφηκε κατηγορία ID: 20','127.0.0.1','2025-07-03 12:24:16'),(254,1,'admin','DELETE','categories','Διαγράφηκε κατηγορία ID: 20','127.0.0.1','2025-07-03 12:24:16'),(255,1,'admin','DELETE','categories','Διαγράφηκε κατηγορία ID: 20','127.0.0.1','2025-07-03 12:24:16'),(256,1,'admin','DELETE','categories','Διαγράφηκε κατηγορία ID: 20','127.0.0.1','2025-07-03 12:24:16'),(257,1,'admin','DELETE','categories','Διαγράφηκε κατηγορία ID: 20','127.0.0.1','2025-07-03 12:24:16'),(258,1,NULL,'DELETE','library_items','Deleted item: Introduction to Programming (ID: 75)','127.0.0.1','2025-07-03 12:24:26'),(259,1,NULL,'DELETE','library_items','Deleted item: Science Monthly (ID: 76)','127.0.0.1','2025-07-03 12:24:30'),(260,1,'admin','DELETE','categories','Διαγράφηκε κατηγορία ID: 3','127.0.0.1','2025-07-03 12:24:41'),(261,1,'admin','DELETE','categories','Διαγράφηκε κατηγορία ID: 7','127.0.0.1','2025-07-03 12:24:43'),(262,1,'admin','DELETE','categories','Διαγράφηκε κατηγορία ID: 11','127.0.0.1','2025-07-03 12:24:44'),(263,1,'admin','DELETE','categories','Διαγράφηκε κατηγορία ID: 12','127.0.0.1','2025-07-03 12:24:44'),(264,1,'admin','DELETE','categories','Διαγράφηκε κατηγορία ID: 14','127.0.0.1','2025-07-03 12:24:45'),(265,1,'admin','DELETE','categories','Διαγράφηκε κατηγορία ID: 17','127.0.0.1','2025-07-03 12:24:45'),(266,1,'admin','DELETE','categories','Διαγράφηκε κατηγορία ID: 8','127.0.0.1','2025-07-03 12:24:46'),(267,1,'admin','UPDATE','categories','Ενημερώθηκε κατηγορία: Computer Science','127.0.0.1','2025-07-03 12:24:57'),(268,1,'admin','DELETE','publishers','Deleted publisher ID: 2','127.0.0.1','2025-07-03 12:25:17'),(269,1,'admin','DELETE','publishers','Deleted publisher ID: 3','127.0.0.1','2025-07-03 12:25:18'),(270,1,'admin','DELETE','publishers','Deleted publisher ID: 4','127.0.0.1','2025-07-03 12:25:20'),(271,1,'admin','DELETE','publishers','Deleted publisher ID: 8','127.0.0.1','2025-07-03 12:25:22'),(272,1,'admin','DELETE','publishers','Deleted publisher ID: 9','127.0.0.1','2025-07-03 12:25:23'),(273,1,'admin','DELETE','publishers','Deleted publisher ID: 10','127.0.0.1','2025-07-03 12:25:23'),(274,1,'admin','DELETE','publishers','Deleted publisher ID: 11','127.0.0.1','2025-07-03 12:25:24'),(275,1,'admin','DELETE','publishers','Deleted publisher ID: 12','127.0.0.1','2025-07-03 12:25:25'),(276,1,'admin','DELETE','publishers','Deleted publisher ID: 13','127.0.0.1','2025-07-03 12:25:25'),(277,1,'admin','DELETE','publishers','Deleted publisher ID: 14','127.0.0.1','2025-07-03 12:25:26'),(278,1,'admin','DELETE','publishers','Deleted publisher ID: 15','127.0.0.1','2025-07-03 12:25:27'),(279,1,'admin','DELETE','publishers','Deleted publisher ID: 16','127.0.0.1','2025-07-03 12:25:28'),(280,1,'admin','DELETE','publishers','Deleted publisher ID: 17','127.0.0.1','2025-07-03 12:25:28'),(281,1,'admin','DELETE','publishers','Deleted publisher ID: 18','127.0.0.1','2025-07-03 12:25:29'),(282,1,'admin','DELETE','publishers','Deleted publisher ID: 19','127.0.0.1','2025-07-03 12:25:30'),(283,1,'admin','DELETE','publishers','Deleted publisher ID: 20','127.0.0.1','2025-07-03 12:25:30'),(284,1,'admin','DELETE','publishers','Deleted publisher ID: 21','127.0.0.1','2025-07-03 12:25:31'),(285,1,'admin','DELETE','publishers','Deleted publisher ID: 22','127.0.0.1','2025-07-03 12:25:32'),(286,1,'admin','DELETE','publishers','Deleted publisher ID: 23','127.0.0.1','2025-07-03 12:25:33'),(287,1,'admin','DELETE','publishers','Deleted publisher ID: 24','127.0.0.1','2025-07-03 12:25:33'),(288,1,'admin','DELETE','publishers','Deleted publisher ID: 25','127.0.0.1','2025-07-03 12:25:34'),(289,1,'admin','DELETE','publishers','Deleted publisher ID: 26','127.0.0.1','2025-07-03 12:25:35'),(290,1,'admin','DELETE','publishers','Deleted publisher ID: 27','127.0.0.1','2025-07-03 12:25:35'),(291,1,'admin','DELETE','publishers','Deleted publisher ID: 28','127.0.0.1','2025-07-03 12:25:36'),(292,1,'admin','DELETE','publishers','Deleted publisher ID: 29','127.0.0.1','2025-07-03 12:25:37'),(293,1,'admin','DELETE','publishers','Deleted publisher ID: 30','127.0.0.1','2025-07-03 12:25:38'),(294,1,'admin','DELETE','publishers','Deleted publisher ID: 31','127.0.0.1','2025-07-03 12:25:38'),(295,1,'admin','DELETE','publishers','Deleted publisher ID: 32','127.0.0.1','2025-07-03 12:25:39'),(296,1,'admin','DELETE','publishers','Deleted publisher ID: 33','127.0.0.1','2025-07-03 12:25:40'),(297,1,'admin','DELETE','publishers','Deleted publisher ID: 34','127.0.0.1','2025-07-03 12:25:41'),(298,1,'admin','DELETE','publishers','Deleted publisher ID: 35','127.0.0.1','2025-07-03 12:25:42'),(299,1,'admin','UPDATE','publishers','Updated publisher: Academic Press','127.0.0.1','2025-07-03 12:26:05'),(300,1,'admin','DELETE','authors','Διαγράφηκε συγγραφέας ID: 31','127.0.0.1','2025-07-03 12:26:47'),(301,1,'admin','DELETE','authors','Διαγράφηκε συγγραφέας ID: 10','127.0.0.1','2025-07-03 12:26:50'),(302,1,'admin','DELETE','authors','Διαγράφηκε συγγραφέας ID: 45','127.0.0.1','2025-07-03 12:26:51'),(303,1,'admin','DELETE','authors','Διαγράφηκε συγγραφέας ID: 15','127.0.0.1','2025-07-03 12:26:52'),(304,1,'admin','DELETE','authors','Διαγράφηκε συγγραφέας ID: 38','127.0.0.1','2025-07-03 12:26:53'),(305,1,'admin','DELETE','authors','Διαγράφηκε συγγραφέας ID: 2','127.0.0.1','2025-07-03 12:26:54'),(306,1,'admin','DELETE','authors','Διαγράφηκε συγγραφέας ID: 35','127.0.0.1','2025-07-03 12:26:54'),(307,1,'admin','DELETE','authors','Διαγράφηκε συγγραφέας ID: 33','127.0.0.1','2025-07-03 12:26:55'),(308,1,'admin','DELETE','authors','Διαγράφηκε συγγραφέας ID: 17','127.0.0.1','2025-07-03 12:26:56'),(309,1,'admin','DELETE','authors','Διαγράφηκε συγγραφέας ID: 30','127.0.0.1','2025-07-03 12:26:56'),(310,1,'admin','DELETE','authors','Διαγράφηκε συγγραφέας ID: 43','127.0.0.1','2025-07-03 12:26:57'),(311,1,'admin','DELETE','authors','Διαγράφηκε συγγραφέας ID: 12','127.0.0.1','2025-07-03 12:26:58'),(312,1,'admin','DELETE','authors','Διαγράφηκε συγγραφέας ID: 6','127.0.0.1','2025-07-03 12:26:58'),(313,1,'admin','DELETE','authors','Διαγράφηκε συγγραφέας ID: 49','127.0.0.1','2025-07-03 12:26:59'),(314,1,'admin','DELETE','authors','Διαγράφηκε συγγραφέας ID: 19','127.0.0.1','2025-07-03 12:27:00'),(315,1,'admin','DELETE','authors','Διαγράφηκε συγγραφέας ID: 40','127.0.0.1','2025-07-03 12:27:01'),(316,1,'admin','DELETE','authors','Διαγράφηκε συγγραφέας ID: 27','127.0.0.1','2025-07-03 12:27:01'),(317,1,'admin','DELETE','authors','Διαγράφηκε συγγραφέας ID: 29','127.0.0.1','2025-07-03 12:27:03'),(318,1,'admin','DELETE','authors','Διαγράφηκε συγγραφέας ID: 24','127.0.0.1','2025-07-03 12:27:03'),(319,1,'admin','DELETE','authors','Διαγράφηκε συγγραφέας ID: 11','127.0.0.1','2025-07-03 12:27:04'),(320,1,'admin','DELETE','authors','Διαγράφηκε συγγραφέας ID: 5','127.0.0.1','2025-07-03 12:27:05'),(321,1,'admin','DELETE','authors','Διαγράφηκε συγγραφέας ID: 42','127.0.0.1','2025-07-03 12:27:05'),(322,1,'admin','DELETE','authors','Διαγράφηκε συγγραφέας ID: 41','127.0.0.1','2025-07-03 12:27:06'),(323,1,'admin','DELETE','authors','Διαγράφηκε συγγραφέας ID: 4','127.0.0.1','2025-07-03 12:27:07'),(324,1,'admin','DELETE','authors','Διαγράφηκε συγγραφέας ID: 23','127.0.0.1','2025-07-03 12:27:08'),(325,1,'admin','DELETE','authors','Διαγράφηκε συγγραφέας ID: 50','127.0.0.1','2025-07-03 12:27:08'),(326,1,'admin','DELETE','authors','Διαγράφηκε συγγραφέας ID: 13','127.0.0.1','2025-07-03 12:27:10'),(327,1,'admin','DELETE','authors','Διαγράφηκε συγγραφέας ID: 14','127.0.0.1','2025-07-03 12:27:10'),(328,1,'admin','DELETE','authors','Διαγράφηκε συγγραφέας ID: 22','127.0.0.1','2025-07-03 12:27:11'),(329,1,'admin','DELETE','authors','Διαγράφηκε συγγραφέας ID: 16','127.0.0.1','2025-07-03 12:27:12'),(330,1,'admin','DELETE','authors','Διαγράφηκε συγγραφέας ID: 3','127.0.0.1','2025-07-03 12:27:13'),(331,1,'admin','DELETE','authors','Διαγράφηκε συγγραφέας ID: 44','127.0.0.1','2025-07-03 12:27:13'),(332,1,'admin','DELETE','authors','Διαγράφηκε συγγραφέας ID: 36','127.0.0.1','2025-07-03 12:27:14'),(333,1,'admin','DELETE','authors','Διαγράφηκε συγγραφέας ID: 26','127.0.0.1','2025-07-03 12:27:15'),(334,1,'admin','DELETE','authors','Διαγράφηκε συγγραφέας ID: 47','127.0.0.1','2025-07-03 12:27:15'),(335,1,'admin','DELETE','authors','Διαγράφηκε συγγραφέας ID: 48','127.0.0.1','2025-07-03 12:27:18'),(336,1,'admin','DELETE','authors','Διαγράφηκε συγγραφέας ID: 21','127.0.0.1','2025-07-03 12:27:19'),(337,1,'admin','DELETE','authors','Διαγράφηκε συγγραφέας ID: 7','127.0.0.1','2025-07-03 12:27:19'),(338,1,'admin','DELETE','authors','Διαγράφηκε συγγραφέας ID: 8','127.0.0.1','2025-07-03 12:27:20'),(339,1,'admin','DELETE','authors','Διαγράφηκε συγγραφέας ID: 37','127.0.0.1','2025-07-03 12:27:21'),(340,1,'admin','DELETE','authors','Διαγράφηκε συγγραφέας ID: 32','127.0.0.1','2025-07-03 12:27:21'),(341,1,'admin','DELETE','authors','Διαγράφηκε συγγραφέας ID: 46','127.0.0.1','2025-07-03 12:27:22'),(342,1,'admin','DELETE','authors','Διαγράφηκε συγγραφέας ID: 34','127.0.0.1','2025-07-03 12:27:23'),(343,1,'admin','DELETE','authors','Διαγράφηκε συγγραφέας ID: 25','127.0.0.1','2025-07-03 12:27:23'),(344,1,'admin','DELETE','authors','Διαγράφηκε συγγραφέας ID: 20','127.0.0.1','2025-07-03 12:27:24'),(345,1,'admin','DELETE','authors','Διαγράφηκε συγγραφέας ID: 28','127.0.0.1','2025-07-03 12:27:25'),(346,1,'admin','DELETE','authors','Διαγράφηκε συγγραφέας ID: 39','127.0.0.1','2025-07-03 12:27:26'),(347,1,'admin','DELETE','authors','Διαγράφηκε συγγραφέας ID: 51','127.0.0.1','2025-07-03 12:27:27'),(348,1,'admin','UPDATE','system_settings','Ενημέρωση διαμόρφωσης συστήματος','127.0.0.1','2025-07-03 12:28:13'),(349,1,'admin','UPDATE','system_settings','Updated system configuration','127.0.0.1','2025-07-03 21:23:06');
/*!40000 ALTER TABLE `activity_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `authors`
--

DROP TABLE IF EXISTS `authors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `authors` (
  `author_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `bio` text,
  `last_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`author_id`)
) ENGINE=InnoDB AUTO_INCREMENT=52 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `authors`
--

LOCK TABLES `authors` WRITE;
/*!40000 ALTER TABLE `authors` DISABLE KEYS */;
INSERT INTO `authors` VALUES (18,'Alexander Fleming',NULL,'2025-06-19 19:35:59');
/*!40000 ALTER TABLE `authors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `category_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `last_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`category_id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (2,'Computer Science','The Computer Science','active','2025-07-03 12:24:57');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `item_authors`
--

DROP TABLE IF EXISTS `item_authors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `item_authors` (
  `item_id` int NOT NULL,
  `author_id` int NOT NULL,
  PRIMARY KEY (`item_id`,`author_id`),
  KEY `author_id` (`author_id`),
  CONSTRAINT `item_authors_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `library_items` (`item_id`) ON DELETE CASCADE,
  CONSTRAINT `item_authors_ibfk_2` FOREIGN KEY (`author_id`) REFERENCES `authors` (`author_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `item_authors`
--

LOCK TABLES `item_authors` WRITE;
/*!40000 ALTER TABLE `item_authors` DISABLE KEYS */;
/*!40000 ALTER TABLE `item_authors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `library_items`
--

DROP TABLE IF EXISTS `library_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `library_items` (
  `item_id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `type_id` int NOT NULL,
  `category_id` int DEFAULT NULL,
  `publisher_id` int DEFAULT NULL,
  `language` varchar(10) NOT NULL DEFAULT 'EN',
  `publication_year` int DEFAULT NULL,
  `edition` int DEFAULT NULL,
  `isbn` varchar(17) DEFAULT NULL,
  `issn` varchar(9) DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `added_by` int NOT NULL,
  `added_date` date DEFAULT (curdate()),
  `status` enum('available','archived') NOT NULL DEFAULT 'available',
  PRIMARY KEY (`item_id`),
  UNIQUE KEY `isbn` (`isbn`),
  UNIQUE KEY `issn` (`issn`),
  KEY `type_id` (`type_id`),
  KEY `category_id` (`category_id`),
  KEY `publisher_id` (`publisher_id`),
  KEY `added_by` (`added_by`),
  CONSTRAINT `library_items_ibfk_1` FOREIGN KEY (`type_id`) REFERENCES `material_types` (`type_id`),
  CONSTRAINT `library_items_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`),
  CONSTRAINT `library_items_ibfk_3` FOREIGN KEY (`publisher_id`) REFERENCES `publishers` (`publisher_id`),
  CONSTRAINT `library_items_ibfk_4` FOREIGN KEY (`added_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=77 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `library_items`
--

LOCK TABLES `library_items` WRITE;
/*!40000 ALTER TABLE `library_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `library_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `material_types`
--

DROP TABLE IF EXISTS `material_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `material_types` (
  `type_id` int NOT NULL AUTO_INCREMENT,
  `type_name` varchar(50) NOT NULL,
  PRIMARY KEY (`type_id`),
  UNIQUE KEY `type_name` (`type_name`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `material_types`
--

LOCK TABLES `material_types` WRITE;
/*!40000 ALTER TABLE `material_types` DISABLE KEYS */;
INSERT INTO `material_types` VALUES (1,'Book'),(4,'Journal'),(2,'Magazine'),(5,'Manuscript'),(3,'Newspaper');
/*!40000 ALTER TABLE `material_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_resets` (
  `reset_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`reset_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_resets`
--

LOCK TABLES `password_resets` WRITE;
/*!40000 ALTER TABLE `password_resets` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_resets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `publishers`
--

DROP TABLE IF EXISTS `publishers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `publishers` (
  `publisher_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `contact_info` text,
  `website` varchar(255) DEFAULT NULL,
  `last_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`publisher_id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `publishers`
--

LOCK TABLES `publishers` WRITE;
/*!40000 ALTER TABLE `publishers` DISABLE KEYS */;
INSERT INTO `publishers` VALUES (5,'Academic Press','test@gmail.com','https://www.test.com','2025-07-03 12:26:05');
/*!40000 ALTER TABLE `publishers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `role_id` int NOT NULL AUTO_INCREMENT,
  `role_name` varchar(50) NOT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  PRIMARY KEY (`role_id`),
  UNIQUE KEY `role_name` (`role_name`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'Administrator',NULL,'2025-06-22 11:49:49','active'),(2,'Librarian',NULL,'2025-06-22 11:49:49','active');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_settings`
--

DROP TABLE IF EXISTS `system_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `system_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(255) NOT NULL,
  `setting_value` text,
  `last_modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_settings`
--

LOCK TABLES `system_settings` WRITE;
/*!40000 ALTER TABLE `system_settings` DISABLE KEYS */;
INSERT INTO `system_settings` VALUES (1,'library_name','GPLMS','2025-07-03 21:23:06'),(2,'default_language','GR','2025-07-03 21:23:06'),(3,'items_per_page','5','2025-07-03 21:23:06'),(4,'default_theme','light','2025-07-03 21:23:06'),(5,'maintenance_mode','0','2025-07-03 21:23:06'),(6,'allow_user_registration','1','2025-07-03 21:23:06'),(7,'default_user_role','2','2025-07-03 21:23:06'),(8,'password_reset_expiry_hours','22','2025-07-03 21:23:06'),(9,'email_notifications','1','2025-07-03 21:23:06'),(10,'mailersend_api_key','mlsn.964569ead0554911e4a75c845f3e0d4ef35b9ea34ef3314f58b93a452d8b2ff0','2025-06-30 08:44:35'),(11,'mailersend_sender_email','noreply@test-r9084zvr8wxgw63d.mlsender.net','2025-07-03 21:23:06'),(12,'mailersend_sender_name','Library System','2025-07-03 21:23:06'),(13,'contact_form_recipient_email','pkotsorgios654@gmail.com','2025-07-03 21:23:06'),(14,'contact_form_recipient_name','System Administrator','2025-07-03 21:23:06');
/*!40000 ALTER TABLE `system_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `user_id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role_id` int NOT NULL,
  `status` enum('active','suspended') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login` datetime DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `role_id` (`role_id`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin','$2y$10$xhW7NZLUJWbQrt5qELgNhOOPviYJJgz28k1FV6TsHALB4QsXqvk..','System Admin','admin@library.com','',1,'active','2025-06-17 21:19:37',NULL),(15,'user','$2y$10$CwxJh3S06SfTMQssvhtmNuZ6o4fdnqSwwhh5b5Bt8oDzYZmGEs/tK','kotsorgios Panagiotis','pkotsorgios654@gmail.com','',2,'active','2025-07-02 08:58:49',NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'gplms_general'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-07-04  0:29:36
