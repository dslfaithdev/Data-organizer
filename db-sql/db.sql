-- MySQL dump 10.15  Distrib 10.0.27-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: sincere
-- ------------------------------------------------------
-- Server version	10.0.27-MariaDB-1~trusty

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
-- Table structure for table `application`
--

DROP TABLE IF EXISTS `application`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `application` (
  `id` bigint(20) NOT NULL DEFAULT '0',
  `name` varchar(256) DEFAULT NULL,
  `namespace` varchar(256) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=TokuDB DEFAULT CHARSET=utf8 `compression`=TOKUDB_LZMA;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `reply`
--

DROP TABLE IF EXISTS `reply`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reply` (
  `id` bigint(20) NOT NULL DEFAULT '0',
  `post_id` bigint(20) NOT NULL DEFAULT '0',
  `page_id` bigint(20) NOT NULL DEFAULT '0',
  `parent_id` bigint(20) NOT NULL DEFAULT '0',
  `fb_id` bigint(20) DEFAULT NULL,
  `message` text,
  `can_remove` tinyint(1) DEFAULT NULL,
  `created_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`page_id`,`post_id`,`id`),
  KEY `comment_id` (`id`),
  KEY `parent_id` (`id`),
  KEY `fb_id` (`fb_id`),
  KEY `created_timeIDX` (`created_time`)
) ENGINE=TokuDB DEFAULT CHARSET=utf8 `compression`=TOKUDB_LZMA;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `comment`
--

DROP TABLE IF EXISTS `comment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `comment` (
  `id` bigint(20) NOT NULL DEFAULT '0',
  `post_id` bigint(20) NOT NULL DEFAULT '0',
  `page_id` bigint(20) NOT NULL DEFAULT '0',
  `fb_id` bigint(20) DEFAULT NULL,
  `message` text,
  `can_remove` tinyint(1) DEFAULT NULL,
  `created_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`page_id`,`post_id`,`id`),
  KEY `comment_id` (`id`),
  KEY `fb_id` (`fb_id`),
  KEY `created_timeIDX` (`created_time`)
) ENGINE=TokuDB DEFAULT CHARSET=utf8 `compression`=TOKUDB_LZMA;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Temporary table structure for view `comments`
--

DROP TABLE IF EXISTS `comments`;
/*!50001 DROP VIEW IF EXISTS `comments`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `comments` (
  `page_id` tinyint NOT NULL,
  `post_id` tinyint NOT NULL,
  `id` tinyint NOT NULL,
  `fb_id` tinyint NOT NULL,
  `page_name` tinyint NOT NULL,
  `fb_name` tinyint NOT NULL,
  `message` tinyint NOT NULL,
  `created_time` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `crawl_stat`
--

DROP TABLE IF EXISTS `crawl_stat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `crawl_stat` (
  `page_id` bigint(20) NOT NULL DEFAULT '0',
  `name` text CHARACTER SET utf8,
  `max(post.created_time)` timestamp NULL DEFAULT NULL,
  `entropy` float DEFAULT NULL
) ENGINE=TokuDB DEFAULT CHARSET=latin1 `compression`=TOKUDB_LZMA;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `fb_user`
--

DROP TABLE IF EXISTS `fb_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fb_user` (
  `id` bigint(20) NOT NULL,
  `name` text,
  `category` bit(1) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=TokuDB DEFAULT CHARSET=utf8 `compression`=TOKUDB_LZMA;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `reaction`
--

DROP TABLE IF EXISTS `reaction`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reaction` (
  `page_id` bigint(20) NOT NULL,
  `post_id` bigint(20) NOT NULL,
  `comment_id` bigint(20) NOT NULL DEFAULT '0',
  `fb_id` bigint(20) NOT NULL,
  `type` enum('NONE', 'LIKE', 'LOVE', 'WOW', 'HAHA', 'SAD', 'ANGRY', 'THANKFUL') DEFAULT 'NONE',
  PRIMARY KEY (`page_id`,`post_id`,`comment_id`,`fb_id`),
  KEY `fb_id` (`fb_id`),
  KEY `comment_id` (`comment_id`)
) ENGINE=TokuDB DEFAULT CHARSET=utf8 `compression`='tokudb_zlib';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `likedby`
--

DROP TABLE IF EXISTS `likedby`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `likedby` (
  `page_id` bigint(20) NOT NULL,
  `post_id` bigint(20) NOT NULL,
  `comment_id` bigint(20) NOT NULL DEFAULT '0',
  `fb_id` bigint(20) NOT NULL,
  `created_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`page_id`,`post_id`,`comment_id`,`fb_id`),
  KEY `fb_id` (`fb_id`),
  KEY `comment_id` (`comment_id`)
) ENGINE=TokuDB DEFAULT CHARSET=utf8 `compression`='tokudb_zlib';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `message_tags`
--

DROP TABLE IF EXISTS `message_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `message_tags` (
  `id` bigint(20) NOT NULL DEFAULT '0',
  `page_id` bigint(20) DEFAULT NULL,
  `post_id` bigint(20) DEFAULT NULL,
  `comment_id` bigint(20) DEFAULT NULL,
  `offset` int(11) DEFAULT NULL,
  `length` int(11) DEFAULT NULL,
  `type` varchar(256) DEFAULT NULL,
  `name` varchar(256) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=TokuDB DEFAULT CHARSET=utf8 `compression`='tokudb_fast';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `page`
--

DROP TABLE IF EXISTS `page`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `page` (
  `id` bigint(20) NOT NULL,
  `name` text,
  `category` text,
  PRIMARY KEY (`id`),
  KEY `page_id` (`id`)
) ENGINE=TokuDB DEFAULT CHARSET=utf8 `compression`=TOKUDB_LZMA;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `place`
--

DROP TABLE IF EXISTS `place`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `place` (
  `id` bigint(20) NOT NULL DEFAULT '0',
  `name` varchar(256) DEFAULT NULL,
  `loc_latitude` float DEFAULT NULL,
  `loc_longitude` float DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=TokuDB DEFAULT CHARSET=utf8 `compression`=TOKUDB_LZMA;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `post`
--

DROP TABLE IF EXISTS `post`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `post` (
  `id` bigint(20) NOT NULL DEFAULT '0',
  `page_id` bigint(20) NOT NULL DEFAULT '0',
  `from_id` bigint(20) NOT NULL,
  `message` text,
  `type` varchar(256) DEFAULT NULL,
  `picture` varchar(256) DEFAULT NULL,
  `story` text,
  `link` text,
  `link_name` text,
  `link_description` text,
  `link_caption` text,
  `icon` varchar(256) DEFAULT NULL,
  `created_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `can_remove` tinyint(1) DEFAULT NULL,
  `shares_count` int(11) DEFAULT NULL,
  `likes_count` int(11) DEFAULT NULL,
  `comments_count` int(11) DEFAULT NULL,
  `entr_pg` float DEFAULT NULL,
  `entr_ug` float DEFAULT NULL,
  `object_id` varchar(40) DEFAULT NULL,
  `status_type` varchar(256) DEFAULT NULL,
  `source` varchar(256) DEFAULT NULL,
  `is_hidden` tinyint(1) DEFAULT NULL,
  `application_id` bigint(20) DEFAULT NULL,
  `place_id` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`page_id`,`id`),
  KEY `post_id` (`id`),
  KEY `created_time_index` (`created_time`),
  KEY `comments_count_index` (`comments_count`),
  KEY `from_id_index` (`from_id`),
  KEY `place_id_index` (`place_id`)
) ENGINE=TokuDB DEFAULT CHARSET=utf8 `compression`=TOKUDB_LZMA;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `shares`
--

DROP TABLE IF EXISTS `shares`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `shares` (
  `id` bigint(20) unsigned NOT NULL,
  `post_id` bigint(20) unsigned NOT NULL,
  `who` bigint(20) unsigned NOT NULL,
  `created_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`,`post_id`),
  KEY `post_id` (`post_id`)
) ENGINE=TokuDB DEFAULT CHARSET=utf8 `compression`='tokudb_fast';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Temporary table structure for view `sharesView`
--

DROP TABLE IF EXISTS `sharesView`;
/*!50001 DROP VIEW IF EXISTS `sharesView`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `sharesView` (
  `page_id` tinyint NOT NULL,
  `post_id` tinyint NOT NULL,
  `fb_id` tinyint NOT NULL,
  `created_time` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `status`
--

DROP TABLE IF EXISTS `status`;
/*!50001 DROP VIEW IF EXISTS `status`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `status` (
  `Table Name` tinyint NOT NULL,
  `# Rows` tinyint NOT NULL,
  `Total Size GB` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `story_tags`
--

DROP TABLE IF EXISTS `story_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `story_tags` (
  `id` bigint(20) NOT NULL DEFAULT '0',
  `page_id` bigint(20) DEFAULT NULL,
  `post_id` bigint(20) DEFAULT NULL,
  `comment_id` bigint(20) DEFAULT NULL,
  `offset` int(11) DEFAULT NULL,
  `length` int(11) DEFAULT NULL,
  `type` varchar(256) DEFAULT NULL,
  `name` varchar(256) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=TokuDB DEFAULT CHARSET=utf8 `compression`=TOKUDB_LZMA;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `with_tags`
--

DROP TABLE IF EXISTS `with_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `with_tags` (
  `page_id` bigint(20) NOT NULL DEFAULT '0',
  `post_id` bigint(20) NOT NULL DEFAULT '0',
  `fb_id` bigint(20) NOT NULL DEFAULT '0',
  PRIMARY KEY (`page_id`,`post_id`,`fb_id`)
) ENGINE=TokuDB DEFAULT CHARSET=utf8 `compression`=TOKUDB_LZMA;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Final view structure for view `comments`
--

/*!50001 DROP TABLE IF EXISTS `comments`*/;
/*!50001 DROP VIEW IF EXISTS `comments`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `comments` AS select `comment`.`page_id` AS `page_id`,`comment`.`post_id` AS `post_id`,`comment`.`id` AS `id`,`comment`.`fb_id` AS `fb_id`,`page`.`name` AS `page_name`,`fb_user`.`name` AS `fb_name`,`comment`.`message` AS `message`,`comment`.`created_time` AS `created_time` from ((`comment` left join `page` on((`comment`.`page_id` = `page`.`id`))) left join `fb_user` on((`comment`.`fb_id` = `fb_user`.`id`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `posts`
--

/*!50001 DROP TABLE IF EXISTS `posts`*/;
/*!50001 DROP VIEW IF EXISTS `posts`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `posts` AS select `post`.`id` AS `id`,`post`.`page_id` AS `page_id`,`page`.`name` AS `page_name`,`post`.`from_id` AS `from_id`,`fb_user`.`name` AS `from_name`,`post`.`message` AS `message`,`post`.`created_time` AS `created_time`,`post`.`updated_time` AS `updated_time`,`post`.`shares_count` AS `shares_count`,`post`.`likes_count` AS `likes_count`,`post`.`comments_count` AS `comments_count` from ((`post` join `page` on((`post`.`page_id` = `page`.`id`))) join `fb_user` on((`post`.`from_id` = `fb_user`.`id`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `sharesView`
--

/*!50001 DROP TABLE IF EXISTS `sharesView`*/;
/*!50001 DROP VIEW IF EXISTS `sharesView`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8 */;
/*!50001 SET character_set_results     = utf8 */;
/*!50001 SET collation_connection      = utf8_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `sharesView` AS select `p`.`page_id` AS `page_id`,`s`.`post_id` AS `post_id`,`s`.`who` AS `fb_id`,`s`.`created_time` AS `created_time` from (`shares` `s` join `post` `p` on((`s`.`post_id` = `p`.`id`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `status`
--

/*!50001 DROP TABLE IF EXISTS `status`*/;
/*!50001 DROP VIEW IF EXISTS `status`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `status` AS select `information_schema`.`TABLES`.`TABLE_NAME` AS `Table Name`,format(`information_schema`.`TABLES`.`TABLE_ROWS`,0) AS `# Rows`,format(round(((`information_schema`.`TABLES`.`DATA_LENGTH` + `information_schema`.`TABLES`.`INDEX_LENGTH`) / ((1024 * 1024) * 1024)),2),2) AS `Total Size GB` from `information_schema`.`TABLES` where (`information_schema`.`TABLES`.`TABLE_SCHEMA` = 'sincere') order by `information_schema`.`TABLES`.`TABLE_ROWS` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2016-10-27 12:38:46
