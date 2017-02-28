CREATE DATABASE  IF NOT EXISTS `mlg` /*!40100 DEFAULT CHARACTER SET latin1 */;
USE `mlg`;
-- MySQL dump 10.13  Distrib 5.5.53, for debian-linux-gnu (x86_64)
--
-- Host: 127.0.0.1    Database: mlg
-- ------------------------------------------------------
-- Server version	5.5.53-0ubuntu0.14.04.1-log

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `block_user_courses`
--

DROP TABLE IF EXISTS `block_user_courses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `block_user_courses` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `block_user_courses`
--

LOCK TABLES `block_user_courses` WRITE;
/*!40000 ALTER TABLE `block_user_courses` DISABLE KEYS */;
/*!40000 ALTER TABLE `block_user_courses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `course_contents`
--

DROP TABLE IF EXISTS `course_contents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `course_contents` (
  `id` int(11) NOT NULL,
  `node_id` int(11) DEFAULT NULL,
  `name` varchar(45) DEFAULT NULL,
  `url` varchar(100) DEFAULT NULL,
  `meta_tags` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `course_contents`
--

LOCK TABLES `course_contents` WRITE;
/*!40000 ALTER TABLE `course_contents` DISABLE KEYS */;
/*!40000 ALTER TABLE `course_contents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `course_details`
--

DROP TABLE IF EXISTS `course_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `course_details` (
  `id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `node_id` int(11) DEFAULT NULL,
  `name` varchar(45) DEFAULT NULL,
  `meta_tags` varchar(255) DEFAULT NULL,
  `descriptions` text,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `validity` datetime DEFAULT NULL,
  `status` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `course_details`
--

LOCK TABLES `course_details` WRITE;
/*!40000 ALTER TABLE `course_details` DISABLE KEYS */;
/*!40000 ALTER TABLE `course_details` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `course_items`
--

DROP TABLE IF EXISTS `course_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `course_items` (
  `id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `node_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `course_items`
--

LOCK TABLES `course_items` WRITE;
/*!40000 ALTER TABLE `course_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `course_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `courses`
--

DROP TABLE IF EXISTS `courses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `courses` (
  `id` int(11) NOT NULL,
  `course_code` varchar(45) DEFAULT NULL,
  `course_name` varchar(45) DEFAULT NULL,
  `logo` varchar(45) DEFAULT NULL,
  `meta_tags` varchar(255) DEFAULT NULL,
  `descriptions` text,
  `author` int(11) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `paid` tinyint(4) DEFAULT NULL,
  `price` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `courses`
--

LOCK TABLES `courses` WRITE;
/*!40000 ALTER TABLE `courses` DISABLE KEYS */;
/*!40000 ALTER TABLE `courses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `exam_courses`
--

DROP TABLE IF EXISTS `exam_courses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `exam_courses` (
  `id` int(11) NOT NULL,
  `exam_id` int(11) DEFAULT NULL,
  `node_id` int(11) DEFAULT NULL,
  `status` tinyint(4) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `exam_courses`
--

LOCK TABLES `exam_courses` WRITE;
/*!40000 ALTER TABLE `exam_courses` DISABLE KEYS */;
/*!40000 ALTER TABLE `exam_courses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `exams`
--

DROP TABLE IF EXISTS `exams`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `exams` (
  `id` int(11) NOT NULL,
  `name` varchar(45) DEFAULT NULL,
  `is_graded` bit(1) NOT NULL,
  `max_marks` int(11) DEFAULT NULL,
  `max_questions` int(11) DEFAULT NULL,
  `duration` varchar(45) DEFAULT NULL,
  `sections` varchar(45) DEFAULT NULL,
  `status` tinyint(4) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `exams`
--

LOCK TABLES `exams` WRITE;
/*!40000 ALTER TABLE `exams` DISABLE KEYS */;
/*!40000 ALTER TABLE `exams` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `home_works`
--

DROP TABLE IF EXISTS `home_works`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `home_works` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(45) NOT NULL,
  `descriptions` text NOT NULL,
  `node_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `home_works`
--

LOCK TABLES `home_works` WRITE;
/*!40000 ALTER TABLE `home_works` DISABLE KEYS */;
/*!40000 ALTER TABLE `home_works` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `item_types`
--

DROP TABLE IF EXISTS `item_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `item_types` (
  `id` int(11) NOT NULL,
  `name` varchar(45) NOT NULL,
  `status` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `item_types`
--

LOCK TABLES `item_types` WRITE;
/*!40000 ALTER TABLE `item_types` DISABLE KEYS */;
/*!40000 ALTER TABLE `item_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `items`
--

DROP TABLE IF EXISTS `items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `items` (
  `id` int(11) NOT NULL,
  `type` varchar(45) DEFAULT NULL,
  `node_id` int(11) DEFAULT NULL,
  `language` varchar(45) DEFAULT NULL,
  `adaptive` varchar(45) DEFAULT NULL,
  `time_dependent` tinyint(4) DEFAULT NULL,
  `item_body` varchar(45) DEFAULT NULL,
  `difficulty` varchar(45) DEFAULT NULL,
  `marks` varchar(45) DEFAULT NULL,
  `status` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `items`
--

LOCK TABLES `items` WRITE;
/*!40000 ALTER TABLE `items` DISABLE KEYS */;
/*!40000 ALTER TABLE `items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `menus`
--

DROP TABLE IF EXISTS `menus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `menus` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `menus`
--

LOCK TABLES `menus` WRITE;
/*!40000 ALTER TABLE `menus` DISABLE KEYS */;
/*!40000 ALTER TABLE `menus` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quiz_items`
--

DROP TABLE IF EXISTS `quiz_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quiz_items` (
  `id` int(11) NOT NULL,
  `item_id` int(11) DEFAULT NULL,
  `quiz_id` int(11) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `marks` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quiz_items`
--

LOCK TABLES `quiz_items` WRITE;
/*!40000 ALTER TABLE `quiz_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `quiz_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_courses`
--

DROP TABLE IF EXISTS `user_courses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_courses` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `expiry_date` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_courses`
--

LOCK TABLES `user_courses` WRITE;
/*!40000 ALTER TABLE `user_courses` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_courses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_details`
--

DROP TABLE IF EXISTS `user_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_details` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `profile_pic` varchar(255) DEFAULT NULL,
  `father_email` varchar(45) DEFAULT NULL,
  `mother_email` varchar(45) DEFAULT NULL,
  `father_name` varchar(45) DEFAULT NULL,
  `mother_name` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_details`
--

LOCK TABLES `user_details` WRITE;
/*!40000 ALTER TABLE `user_details` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_details` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_menus`
--

DROP TABLE IF EXISTS `user_menus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_menus` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `menu_id` int(11) NOT NULL,
  `validity` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_menus`
--

LOCK TABLES `user_menus` WRITE;
/*!40000 ALTER TABLE `user_menus` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_menus` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_orders`
--

DROP TABLE IF EXISTS `user_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `amount` varchar(45) DEFAULT NULL,
  `discount` varchar(45) DEFAULT NULL,
  `tax` varchar(45) DEFAULT NULL,
  `mode` varchar(45) DEFAULT NULL,
  `order_date` varchar(45) DEFAULT NULL,
  `status` varchar(45) DEFAULT NULL,
  `pg_response` varchar(45) DEFAULT NULL,
  `retry` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_orders`
--

LOCK TABLES `user_orders` WRITE;
/*!40000 ALTER TABLE `user_orders` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_quiz_responses`
--

DROP TABLE IF EXISTS `user_quiz_responses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_quiz_responses` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `response` varchar(45) NOT NULL,
  `correct` tinyint(4) NOT NULL,
  `score` varchar(45) NOT NULL,
  `skip_count` int(11) NOT NULL,
  `time_taken` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_quiz_responses`
--

LOCK TABLES `user_quiz_responses` WRITE;
/*!40000 ALTER TABLE `user_quiz_responses` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_quiz_responses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_quizes`
--

DROP TABLE IF EXISTS `user_quizes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_quizes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `quiz_id` int(11) NOT NULL,
  `attempts` varchar(45) NOT NULL,
  `created` datetime NOT NULL,
  `score` varchar(45) NOT NULL,
  `status` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_quizes`
--

LOCK TABLES `user_quizes` WRITE;
/*!40000 ALTER TABLE `user_quizes` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_quizes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_roles`
--

DROP TABLE IF EXISTS `user_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_roles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `role_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_roles`
--

LOCK TABLES `user_roles` WRITE;
/*!40000 ALTER TABLE `user_roles` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(45) DEFAULT NULL,
  `last_name` varchar(45) DEFAULT NULL,
  `email` varchar(45) DEFAULT NULL,
  `password` varchar(45) DEFAULT NULL,
  `mobile` int(11) DEFAULT NULL,
  `status` tinyint(4) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `modfied` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
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

-- Dump completed on 2017-02-27  9:56:48
