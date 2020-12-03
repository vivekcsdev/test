-- phpMyAdmin SQL Dump
-- version 4.8.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Aug 26, 2020 at 05:32 AM
-- Server version: 10.1.33-MariaDB
-- PHP Version: 7.2.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

--
-- Database: `autopost`
--

-- --------------------------------------------------------

--
-- Table structure for table `accounts`
--

CREATE TABLE IF NOT EXISTS `accounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `sid` varchar(255) NOT NULL,
  `social_type` varchar(255) DEFAULT NULL,
  `account_type` varchar(255) DEFAULT NULL,
  `url` text,
  `avatar` varchar(255) NOT NULL,
  `username` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `access_token` text,
  `is_official` int(11) NOT NULL DEFAULT '1',
  `proxy` varchar(255) DEFAULT NULL,
  `default_proxy` varchar(255) DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT '1',
  `changed` int(11) DEFAULT NULL,
  `created` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `userid` (`userid`)
) ENGINE=InnoDB AUTO_INCREMENT=80 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `automations`
--

CREATE TABLE IF NOT EXISTS `automations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `account` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  `features` text,
  `time` int(11) DEFAULT NULL,
  `settings` text,
  `status` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `automations_actions`
--

CREATE TABLE IF NOT EXISTS `automations_actions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `automation_id` int(11) NOT NULL,
  `action` varchar(255) NOT NULL,
  `time` int(11) NOT NULL,
  `current_hour` int(11) DEFAULT NULL,
  `hour_ran` int(11) DEFAULT NULL,
  `data` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `automations_log`
--

CREATE TABLE IF NOT EXISTS `automations_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `automation_id` int(11) NOT NULL,
  `action_id` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  `action` varchar(255) NOT NULL,
  `data` text,
  `created` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `blogs`
--

CREATE TABLE IF NOT EXISTS `blogs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `description` text NOT NULL,
  `slug` varchar(255) NOT NULL,
  `image` varchar(255) NOT NULL,
  `changed` int(11) NOT NULL,
  `created` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `bulks`
--

CREATE TABLE IF NOT EXISTS `bulks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `userid` int(11) NOT NULL,
  `status` int(11) NOT NULL DEFAULT '0',
  `created` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `bulk_account`
--

CREATE TABLE IF NOT EXISTS `bulk_account` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bulk_id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `bulk_completed`
--

CREATE TABLE IF NOT EXISTS `bulk_completed` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bulk_id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `created` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `bulk_posts`
--

CREATE TABLE IF NOT EXISTS `bulk_posts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bulk_id` int(11) NOT NULL,
  `caption` text NOT NULL,
  `file` int(11) NOT NULL,
  `completed` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `bulk_rules`
--

CREATE TABLE IF NOT EXISTS `bulk_rules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bulk_id` int(11) NOT NULL,
  `mon` int(11) NOT NULL DEFAULT '1',
  `tues` int(11) NOT NULL DEFAULT '1',
  `wed` int(11) NOT NULL DEFAULT '1',
  `thur` int(11) NOT NULL DEFAULT '1',
  `fri` int(11) NOT NULL DEFAULT '1',
  `sat` int(11) NOT NULL DEFAULT '1',
  `sun` int(11) NOT NULL DEFAULT '1',
  `from_date` varchar(255) DEFAULT NULL,
  `to_date` varchar(255) DEFAULT NULL,
  `post_order` int(11) NOT NULL DEFAULT '1',
  `post_daily` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `captions`
--

CREATE TABLE IF NOT EXISTS `captions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `title` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `caption` text CHARACTER SET utf8 COLLATE utf8_unicode_ci,
  `changed` int(11) DEFAULT NULL,
  `created` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `userid` (`userid`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `coupons`
--

CREATE TABLE IF NOT EXISTS `coupons` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `code` varchar(255) DEFAULT NULL,
  `type` int(11) DEFAULT NULL,
  `amount` int(11) DEFAULT NULL,
  `packages` text,
  `end_date` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `designs`
--

CREATE TABLE IF NOT EXISTS `designs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `primary_color` varchar(255) NOT NULL,
  `data` text NOT NULL,
  `is_default` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;

--
-- Dumping data for table `designs`
--

INSERT INTO `designs` (`id`, `title`, `primary_color`, `data`, `is_default`) VALUES
(1, 'Default', '#f98e3b', 'YToxNjp7czoxMToiY29sLW1lbnUtYmciO3M6NzoiI2ZmZmZmZiI7czoxOToiY29sLW1lbnUtbGluay1jb2xvciI7czo3OiIjODA4MDgwIjtzOjE3OiJyZXBvcnQtaWNvbi1jb2xvciI7czo3OiIjODA4MDgwIjtzOjE0OiJyc3MtaWNvbi1jb2xvciI7czo3OiIjODA4MDgwIjtzOjE5OiJjYXB0aW9ucy1pY29uLWNvbG9yIjtzOjc6IiM4MDgwODAiO3M6MjI6ImZpbGVtYW5hZ2VyLWljb24tY29sb3IiO3M6NzoiIzgwODA4MCI7czoxNzoiZ3JvdXBzLWljb24tY29sb3IiO3M6NzoiIzgwODA4MCI7czoyMDoiYWZmaWxpYXRlLWljb24tY29sb3IiO3M6NzoiIzgwODA4MCI7czoxMToiZXhwLW1lbnUtYmciO3M6NzoiIzE1MTUxNSI7czoxOToiZXhwLW1lbnUtbGluay1jb2xvciI7czo3OiIjODA4MDgwIjtzOjEzOiJjb21wb3NlLWZpcnN0IjtzOjc6IiNmZjRkOTMiO3M6MTQ6ImNvbXBvc2Utc2Vjb25kIjtzOjc6IiNmZjc1MTIiO3M6MjA6Im1lbnUtdG9nZ2xlLWJnLWNvbG9yIjtzOjQ6IiNmZmYiO3M6MTc6Im1lbnUtdG9nZ2xlLWNvbG9yIjtzOjc6IiM4MDgwODAiO3M6MjQ6Im1lbnUtdG9nZ2xlLWJvcmRlci1jb2xvciI7czo3OiIjODA4MDgwIjtzOjI3OiJuYXYtYWN0aXZlLWJhY2tncm91bmQtY29sb3IiO3M6NzoiI0Y5RkFGQiI7fQ==', 1),
(2, 'Awesome', '#813bf9', 'YToxNjp7czoxMToiY29sLW1lbnUtYmciO3M6NzoiIzY5NGFkMCI7czoxOToiY29sLW1lbnUtbGluay1jb2xvciI7czo3OiIjYjZhY2Q2IjtzOjE3OiJyZXBvcnQtaWNvbi1jb2xvciI7czo3OiIjMWNkOTEwIjtzOjE0OiJyc3MtaWNvbi1jb2xvciI7czo3OiIjOWQyNTI1IjtzOjE5OiJjYXB0aW9ucy1pY29uLWNvbG9yIjtzOjc6IiNiY2U1MzYiO3M6MjI6ImZpbGVtYW5hZ2VyLWljb24tY29sb3IiO3M6NzoiIzRiY2FjYSI7czoxNzoiZ3JvdXBzLWljb24tY29sb3IiO3M6NzoiI2MzNTBhMSI7czoyMDoiYWZmaWxpYXRlLWljb24tY29sb3IiO3M6NzoiI2IyZDY1NSI7czoxMToiZXhwLW1lbnUtYmciO3M6NzoiI2JlNTBmMiI7czoxOToiZXhwLW1lbnUtbGluay1jb2xvciI7czo3OiIjZTRjNmYwIjtzOjEzOiJjb21wb3NlLWZpcnN0IjtzOjc6IiNlZWVlZWUiO3M6MTQ6ImNvbXBvc2Utc2Vjb25kIjtzOjc6IiNlZThjYzAiO3M6MjA6Im1lbnUtdG9nZ2xlLWJnLWNvbG9yIjtzOjQ6IiNmZmYiO3M6MTc6Im1lbnUtdG9nZ2xlLWNvbG9yIjtzOjc6IiM4MDgwODAiO3M6MjQ6Im1lbnUtdG9nZ2xlLWJvcmRlci1jb2xvciI7czo3OiIjODA4MDgwIjtzOjI3OiJuYXYtYWN0aXZlLWJhY2tncm91bmQtY29sb3IiO3M6NzoiI2VkZGVmNCI7fQ==', 0),
(3, 'anotherAwesome', '#f93beb', 'YToxNjp7czoxMToiY29sLW1lbnUtYmciO3M6NzoiIzhiMWZhYyI7czoxOToiY29sLW1lbnUtbGluay1jb2xvciI7czo3OiIjZTdiZWJlIjtzOjE3OiJyZXBvcnQtaWNvbi1jb2xvciI7czo3OiIjYzI4ZGUxIjtzOjE0OiJyc3MtaWNvbi1jb2xvciI7czo3OiIjYzI4ZGUxIjtzOjE5OiJjYXB0aW9ucy1pY29uLWNvbG9yIjtzOjc6IiNjMjhkZTEiO3M6MjI6ImZpbGVtYW5hZ2VyLWljb24tY29sb3IiO3M6NzoiI2MyOGRlMSI7czoxNzoiZ3JvdXBzLWljb24tY29sb3IiO3M6NzoiI2MyOGRlMSI7czoyMDoiYWZmaWxpYXRlLWljb24tY29sb3IiO3M6NzoiI2MyOGRlMSI7czoxMToiZXhwLW1lbnUtYmciO3M6NzoiIzhjMjFhNSI7czoxOToiZXhwLW1lbnUtbGluay1jb2xvciI7czo3OiIjYzI4ZGUxIjtzOjEzOiJjb21wb3NlLWZpcnN0IjtzOjc6IiNmZjRkOTMiO3M6MTQ6ImNvbXBvc2Utc2Vjb25kIjtzOjc6IiNmZjc1MTIiO3M6MjA6Im1lbnUtdG9nZ2xlLWJnLWNvbG9yIjtzOjc6IiNkZDU5ZWMiO3M6MTc6Im1lbnUtdG9nZ2xlLWNvbG9yIjtzOjc6IiNmZmZmZmYiO3M6MjQ6Im1lbnUtdG9nZ2xlLWJvcmRlci1jb2xvciI7czo3OiIjY2Y1MWU1IjtzOjI3OiJuYXYtYWN0aXZlLWJhY2tncm91bmQtY29sb3IiO3M6NzoiI2YyZTlmNCI7fQ==', 0);

-- --------------------------------------------------------

--
-- Table structure for table `draft_access`
--

CREATE TABLE IF NOT EXISTS `draft_access` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `ownerid` int(11) NOT NULL,
  `code` varchar(255) NOT NULL,
  `status` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `draft_collections`
--

CREATE TABLE IF NOT EXISTS `draft_collections` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `userid` int(11) NOT NULL,
  `ownerid` int(11) NOT NULL,
  `sharable` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `draft_posts`
--

CREATE TABLE IF NOT EXISTS `draft_posts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `collection_id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `last_save` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `files`
--

CREATE TABLE IF NOT EXISTS `files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `resize_image` varchar(255) DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_size` varchar(255) DEFAULT NULL,
  `file_type` varchar(255) DEFAULT NULL,
  `folder_id` int(11) NOT NULL DEFAULT '0',
  `sort_number` int(11) NOT NULL DEFAULT '0',
  `created` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `userid` (`userid`)
) ENGINE=InnoDB AUTO_INCREMENT=222 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `groups`
--

CREATE TABLE IF NOT EXISTS `groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `accounts` text NOT NULL,
  `date_created` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `hashtags`
--

CREATE TABLE IF NOT EXISTS `hashtags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `inboxes`
--

CREATE TABLE IF NOT EXISTS `inboxes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `account` int(11) NOT NULL,
  `unread` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `instagram_analytics`
--

CREATE TABLE IF NOT EXISTS `instagram_analytics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `result` text NOT NULL,
  `fetch_time` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `instagram_analytics_stats`
--

CREATE TABLE IF NOT EXISTS `instagram_analytics_stats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `result` text NOT NULL,
  `time` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `instagram_sessions`
--

CREATE TABLE IF NOT EXISTS `instagram_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) DEFAULT NULL,
  `settings` mediumblob,
  `cookies` mediumblob,
  `last_modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `languages`
--

CREATE TABLE IF NOT EXISTS `languages` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `is_default` int(11) NOT NULL DEFAULT '0',
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `languages`
--

INSERT INTO `languages` (`id`, `name`, `is_default`) VALUES
('ar', 'Arabic', 0),
('en', 'English', 1),
('es', 'Spanish', 0),
('fr', 'French', 0),
('it', 'Italian', 0),
('ja', 'Japanese', 0),
('nl', 'Dutch', 0),
('pl', 'Polish', 0),
('pt', 'Portuguese', 0),
('ru', 'Russian', 0);

-- --------------------------------------------------------

--
-- Table structure for table `packages`
--

CREATE TABLE IF NOT EXISTS `packages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` int(11) NOT NULL DEFAULT '2',
  `title` varchar(255) DEFAULT NULL,
  `description` text,
  `price_monthly` int(11) DEFAULT NULL,
  `price_annually` int(11) DEFAULT NULL,
  `account_limit` int(11) NOT NULL DEFAULT '5',
  `is_default` int(11) NOT NULL DEFAULT '0',
  `trial_day` int(11) NOT NULL DEFAULT '7',
  `permissions` text,
  `status` int(11) NOT NULL DEFAULT '1',
  `sort` int(11) NOT NULL DEFAULT '0',
  `changed` int(11) DEFAULT NULL,
  `created` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;

--
-- Dumping data for table `packages`
--

INSERT INTO `packages` (`id`, `type`, `title`, `description`, `price_monthly`, `price_annually`, `account_limit`, `is_default`, `trial_day`, `permissions`, `status`, `sort`, `changed`, `created`) VALUES
(1, 1, 'Trial Mode', 'Trial for 7 days', 0, 0, 0, 0, 7, 'YTo0NTp7czo3OiJzdG9yYWdlIjtzOjM6IjEwMCI7czo5OiJmaWxlX3NpemUiO3M6MjoiMTAiO3M6NjoibnVtYmVyIjtzOjI6IjMwIjtzOjE2OiJtYW5hZ2UtdGVhbS11c2VyIjtzOjE6IjUiO3M6MTI6Imdvb2dsZS1kcml2ZSI7czoxOiIxIjtzOjc6ImRyb3Bib3giO3M6MToiMSI7czo5OiJvbmUtZHJpdmUiO3M6MToiMSI7czo1OiJwaG90byI7czoxOiIxIjtzOjU6InZpZGVvIjtzOjE6IjEiO3M6OToid2F0ZXJtYXJrIjtzOjE6IjEiO3M6MTI6ImltYWdlLWVkaXRvciI7czoxOiIxIjtzOjY6ImRyYWZ0cyI7czoxOiIwIjtzOjM6InJzcyI7czoxOiIwIjtzOjExOiJhdXRvbWF0aW9ucyI7czoxOiIwIjtzOjEzOiJidWxrLXNjaGVkdWxlIjtzOjE6IjAiO3M6ODoiZmFjZWJvb2siO3M6MToiMSI7czoxMzoiZmFjZWJvb2stcG9zdCI7czoxOiIxIjtzOjE5OiJmYWNlYm9vay1saXZlc3RyZWFtIjtzOjE6IjAiO3M6OToiaW5zdGFncmFtIjtzOjE6IjEiO3M6MTQ6Imluc3RhZ3JhbS1wb3N0IjtzOjE6IjEiO3M6MTk6Imluc3RhZ3JhbS1hbmFseXRpY3MiO3M6MToiMSI7czoyMDoiaW5zdGFncmFtLWxpdmVzdHJlYW0iO3M6MToiMCI7czo3OiJ0d2l0dGVyIjtzOjE6IjEiO3M6MTI6InR3aXR0ZXItcG9zdCI7czoxOiIwIjtzOjk6InBpbnRlcmVzdCI7czoxOiIwIjtzOjE0OiJwaW50ZXJlc3QtcG9zdCI7czoxOiIwIjtzOjI6InZrIjtzOjE6IjAiO3M6NzoidmstcG9zdCI7czoxOiIwIjtzOjg6ImxpbmtlZGluIjtzOjE6IjAiO3M6MTM6ImxpbmtlZGluLXBvc3QiO3M6MToiMCI7czo2OiJnb29nbGUiO3M6MToiMSI7czoxMToiZ29vZ2xlLXBvc3QiO3M6MToiMSI7czo2OiJ0dW1ibHIiO3M6MToiMCI7czoxMToidHVtYmxyLXBvc3QiO3M6MToiMCI7czo2OiJyZWRkaXQiO3M6MToiMCI7czoxMToicmVkZGl0LXBvc3QiO3M6MToiMCI7czo1OiJ2aW1lbyI7czoxOiIwIjtzOjEwOiJ2aW1lby1wb3N0IjtzOjE6IjAiO3M6ODoidGVsZWdyYW0iO3M6MToiMCI7czoxMzoidGVsZWdyYW0tcG9zdCI7czoxOiIwIjtzOjExOiJkYWlseW1vdGlvbiI7czoxOiIwIjtzOjE2OiJkYWlseW1vdGlvbi1wb3N0IjtzOjE6IjAiO3M6NzoieW91dHViZSI7czoxOiIxIjtzOjEyOiJ5b3V0dWJlLXBvc3QiO3M6MToiMSI7czoxODoieW91dHViZS1saXZlc3RyZWFtIjtzOjE6IjAiO30=', 1, 1, 1592261162, 1576005715),
(3, 2, 'Business', 'Business plan', 12, 144, 0, 0, 0, 'YTo0Mzp7czo3OiJzdG9yYWdlIjtzOjM6IjIwMCI7czo5OiJmaWxlX3NpemUiO3M6MToiMiI7czo2OiJudW1iZXIiO3M6MjoiMTIiO3M6MTY6Im1hbmFnZS10ZWFtLXVzZXIiO3M6MToiNSI7czoxMjoiZ29vZ2xlLWRyaXZlIjtzOjE6IjEiO3M6NzoiZHJvcGJveCI7czoxOiIxIjtzOjk6Im9uZS1kcml2ZSI7czoxOiIxIjtzOjU6InBob3RvIjtzOjE6IjEiO3M6NToidmlkZW8iO3M6MToiMSI7czo5OiJ3YXRlcm1hcmsiO3M6MToiMSI7czoxMjoiaW1hZ2UtZWRpdG9yIjtzOjE6IjEiO3M6NjoiZHJhZnRzIjtzOjE6IjEiO3M6MzoicnNzIjtzOjE6IjEiO3M6ODoiZmFjZWJvb2siO3M6MToiMSI7czoxMzoiZmFjZWJvb2stcG9zdCI7czoxOiIxIjtzOjE5OiJmYWNlYm9vay1saXZlc3RyZWFtIjtzOjE6IjAiO3M6OToiaW5zdGFncmFtIjtzOjE6IjEiO3M6MTQ6Imluc3RhZ3JhbS1wb3N0IjtzOjE6IjEiO3M6MTk6Imluc3RhZ3JhbS1hbmFseXRpY3MiO3M6MToiMSI7czoyMDoiaW5zdGFncmFtLWxpdmVzdHJlYW0iO3M6MToiMCI7czo3OiJ0d2l0dGVyIjtzOjE6IjEiO3M6MTI6InR3aXR0ZXItcG9zdCI7czoxOiIxIjtzOjk6InBpbnRlcmVzdCI7czoxOiIxIjtzOjE0OiJwaW50ZXJlc3QtcG9zdCI7czoxOiIxIjtzOjI6InZrIjtzOjE6IjEiO3M6NzoidmstcG9zdCI7czoxOiIxIjtzOjg6ImxpbmtlZGluIjtzOjE6IjEiO3M6MTM6ImxpbmtlZGluLXBvc3QiO3M6MToiMSI7czo2OiJnb29nbGUiO3M6MToiMSI7czoxMToiZ29vZ2xlLXBvc3QiO3M6MToiMSI7czo2OiJ0dW1ibHIiO3M6MToiMSI7czoxMToidHVtYmxyLXBvc3QiO3M6MToiMSI7czo2OiJyZWRkaXQiO3M6MToiMSI7czoxMToicmVkZGl0LXBvc3QiO3M6MToiMSI7czo1OiJ2aW1lbyI7czoxOiIwIjtzOjEwOiJ2aW1lby1wb3N0IjtzOjE6IjAiO3M6ODoidGVsZWdyYW0iO3M6MToiMSI7czoxMzoidGVsZWdyYW0tcG9zdCI7czoxOiIxIjtzOjExOiJkYWlseW1vdGlvbiI7czoxOiIwIjtzOjE2OiJkYWlseW1vdGlvbi1wb3N0IjtzOjE6IjAiO3M6NzoieW91dHViZSI7czoxOiIxIjtzOjEyOiJ5b3V0dWJlLXBvc3QiO3M6MToiMSI7czoxODoieW91dHViZS1saXZlc3RyZWFtIjtzOjE6IjAiO30=', 1, 3, 1589677006, 1576005715),
(4, 2, 'Starter', 'Get on with our starter plan', 4, 59, 0, 0, 0, 'YTo1MDp7czo3OiJzdG9yYWdlIjtzOjM6IjIwMCI7czo5OiJmaWxlX3NpemUiO3M6MjoiMjAiO3M6NjoibnVtYmVyIjtzOjE6IjUiO3M6MTY6Im1hbmFnZS10ZWFtLXVzZXIiO3M6MToiNSI7czoxMjoiZ29vZ2xlLWRyaXZlIjtzOjE6IjAiO3M6NzoiZHJvcGJveCI7czoxOiIxIjtzOjk6Im9uZS1kcml2ZSI7czoxOiIxIjtzOjU6InBob3RvIjtzOjE6IjEiO3M6NToidmlkZW8iO3M6MToiMSI7czo5OiJ3YXRlcm1hcmsiO3M6MToiMCI7czoxMjoiaW1hZ2UtZWRpdG9yIjtzOjE6IjEiO3M6NjoiZHJhZnRzIjtzOjE6IjEiO3M6MzoicnNzIjtzOjE6IjEiO3M6MTE6ImF1dG9tYXRpb25zIjtzOjE6IjAiO3M6MTM6ImJ1bGstc2NoZWR1bGUiO3M6MToiMSI7czo4OiJmYWNlYm9vayI7czoxOiIxIjtzOjEzOiJmYWNlYm9vay1wb3N0IjtzOjE6IjEiO3M6MTk6ImZhY2Vib29rLWxpdmVzdHJlYW0iO3M6MToiMSI7czoyMzoiZmFjZWJvb2stZGlyZWN0LW1lc3NhZ2UiO3M6MToiMSI7czo5OiJpbnN0YWdyYW0iO3M6MToiMSI7czoxNDoiaW5zdGFncmFtLXBvc3QiO3M6MToiMSI7czoxOToiaW5zdGFncmFtLWFuYWx5dGljcyI7czoxOiIxIjtzOjIwOiJpbnN0YWdyYW0tbGl2ZXN0cmVhbSI7czoxOiIxIjtzOjE0OiJpbnN0YWdyYW0taWd0diI7czoxOiIxIjtzOjEzOiJpbnN0YWdyYW0tdGFnIjtzOjE6IjEiO3M6MjQ6Imluc3RhZ3JhbS1kaXJlY3QtbWVzc2FnZSI7czoxOiIxIjtzOjc6InR3aXR0ZXIiO3M6MToiMSI7czoxMjoidHdpdHRlci1wb3N0IjtzOjE6IjEiO3M6MjI6InR3aXR0ZXItZGlyZWN0LW1lc3NhZ2UiO3M6MToiMSI7czo5OiJwaW50ZXJlc3QiO3M6MToiMSI7czoxNDoicGludGVyZXN0LXBvc3QiO3M6MToiMSI7czoyOiJ2ayI7czoxOiIwIjtzOjc6InZrLXBvc3QiO3M6MToiMCI7czo4OiJsaW5rZWRpbiI7czoxOiIwIjtzOjEzOiJsaW5rZWRpbi1wb3N0IjtzOjE6IjAiO3M6NjoiZ29vZ2xlIjtzOjE6IjAiO3M6MTE6Imdvb2dsZS1wb3N0IjtzOjE6IjAiO3M6NjoidHVtYmxyIjtzOjE6IjAiO3M6MTE6InR1bWJsci1wb3N0IjtzOjE6IjAiO3M6NjoicmVkZGl0IjtzOjE6IjAiO3M6MTE6InJlZGRpdC1wb3N0IjtzOjE6IjAiO3M6NToidmltZW8iO3M6MToiMCI7czoxMDoidmltZW8tcG9zdCI7czoxOiIwIjtzOjg6InRlbGVncmFtIjtzOjE6IjAiO3M6MTM6InRlbGVncmFtLXBvc3QiO3M6MToiMCI7czoxMToiZGFpbHltb3Rpb24iO3M6MToiMCI7czoxNjoiZGFpbHltb3Rpb24tcG9zdCI7czoxOiIwIjtzOjc6InlvdXR1YmUiO3M6MToiMCI7czoxMjoieW91dHViZS1wb3N0IjtzOjE6IjAiO3M6MTg6InlvdXR1YmUtbGl2ZXN0cmVhbSI7czoxOiIwIjt9', 1, 1, 1594867685, 1577249008),
(5, 2, 'Standard', 'Get on with our standard plan', 8, 89, 0, 1, 0, 'YTo0MTp7czo3OiJzdG9yYWdlIjtzOjM6IjUwMCI7czo5OiJmaWxlX3NpemUiO3M6MjoiNTAiO3M6NjoibnVtYmVyIjtzOjI6IjEwIjtzOjEyOiJnb29nbGUtZHJpdmUiO3M6MToiMSI7czo3OiJkcm9wYm94IjtzOjE6IjEiO3M6OToib25lLWRyaXZlIjtzOjE6IjEiO3M6NToicGhvdG8iO3M6MToiMSI7czo1OiJ2aWRlbyI7czoxOiIxIjtzOjk6IndhdGVybWFyayI7czoxOiIxIjtzOjEyOiJpbWFnZS1lZGl0b3IiO3M6MToiMSI7czozOiJyc3MiO3M6MToiMCI7czo4OiJmYWNlYm9vayI7czoxOiIxIjtzOjEzOiJmYWNlYm9vay1wb3N0IjtzOjE6IjEiO3M6MTk6ImZhY2Vib29rLWxpdmVzdHJlYW0iO3M6MToiMCI7czo5OiJpbnN0YWdyYW0iO3M6MToiMSI7czoxNDoiaW5zdGFncmFtLXBvc3QiO3M6MToiMSI7czoxOToiaW5zdGFncmFtLWFuYWx5dGljcyI7czoxOiIxIjtzOjIwOiJpbnN0YWdyYW0tbGl2ZXN0cmVhbSI7czoxOiIwIjtzOjc6InR3aXR0ZXIiO3M6MToiMSI7czoxMjoidHdpdHRlci1wb3N0IjtzOjE6IjEiO3M6OToicGludGVyZXN0IjtzOjE6IjEiO3M6MTQ6InBpbnRlcmVzdC1wb3N0IjtzOjE6IjEiO3M6MjoidmsiO3M6MToiMSI7czo3OiJ2ay1wb3N0IjtzOjE6IjAiO3M6ODoibGlua2VkaW4iO3M6MToiMSI7czoxMzoibGlua2VkaW4tcG9zdCI7czoxOiIxIjtzOjY6Imdvb2dsZSI7czoxOiIxIjtzOjExOiJnb29nbGUtcG9zdCI7czoxOiIxIjtzOjY6InR1bWJsciI7czoxOiIwIjtzOjExOiJ0dW1ibHItcG9zdCI7czoxOiIwIjtzOjY6InJlZGRpdCI7czoxOiIxIjtzOjExOiJyZWRkaXQtcG9zdCI7czoxOiIxIjtzOjU6InZpbWVvIjtzOjE6IjAiO3M6MTA6InZpbWVvLXBvc3QiO3M6MToiMCI7czo4OiJ0ZWxlZ3JhbSI7czoxOiIxIjtzOjEzOiJ0ZWxlZ3JhbS1wb3N0IjtzOjE6IjEiO3M6MTE6ImRhaWx5bW90aW9uIjtzOjE6IjAiO3M6MTY6ImRhaWx5bW90aW9uLXBvc3QiO3M6MToiMCI7czo3OiJ5b3V0dWJlIjtzOjE6IjEiO3M6MTI6InlvdXR1YmUtcG9zdCI7czoxOiIxIjtzOjE4OiJ5b3V0dWJlLWxpdmVzdHJlYW0iO3M6MToiMCI7fQ==', 1, 2, 1582430817, 1577249113);

-- --------------------------------------------------------

--
-- Table structure for table `pages`
--

CREATE TABLE IF NOT EXISTS `pages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `content` text,
  `position` varchar(255) DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT '1',
  `changed` int(11) DEFAULT NULL,
  `created` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;

--
-- Dumping data for table `pages`
--

INSERT INTO `pages` (`id`, `name`, `slug`, `content`, `position`, `status`, `changed`, `created`) VALUES
(1, 'Terms and conditions', 'terms-and-condition', '<p>This is the terms and conditions page content</p>', 'footer', 1, 1575969883, 1575969740),
(2, 'Privacy', 'privacy', '<p>Privacy content is here</p>', 'footer', 1, 1577555371, 1577555371),
(3, 'Faq', 'faq', '<p>Frequently asked Questions</p><p>is here lol</p>', 'footer', 1, 1577555414, 1577555404),
(5, 'Contact', 'contact-us', '<p>Contact Us is for content </p>', 'footer', 1, 1589179253, 1589179253);

-- --------------------------------------------------------

--
-- Table structure for table `paypal_products`
--

CREATE TABLE IF NOT EXISTS `paypal_products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `package_id` int(11) NOT NULL,
  `product_id` varchar(255) NOT NULL,
  `monthly_id` varchar(255) NOT NULL,
  `yearly_id` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `plugins`
--

CREATE TABLE IF NOT EXISTS `plugins` (
  `id` varchar(255) NOT NULL,
  `active` int(11) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

CREATE TABLE IF NOT EXISTS `posts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `account` varchar(255) DEFAULT NULL,
  `social_type` varchar(255) DEFAULT NULL,
  `type` varchar(255) DEFAULT NULL,
  `type_data` text,
  `is_scheduled` int(11) NOT NULL DEFAULT '0',
  `schedule_date` varchar(255) DEFAULT NULL,
  `repeat_freq` varchar(255) DEFAULT NULL,
  `repeat_end` varchar(225) DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT '0',
  `result` text,
  `tags` text,
  `created_date` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=797 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `proxies`
--

CREATE TABLE IF NOT EXISTS `proxies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `address` varchar(255) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `usage_limit` int(11) NOT NULL DEFAULT '3',
  `package` text,
  `active` int(11) NOT NULL DEFAULT '1',
  `status` int(11) NOT NULL DEFAULT '1',
  `changed` int(11) DEFAULT NULL,
  `created` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `referrals`
--

CREATE TABLE IF NOT EXISTS `referrals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `code` varchar(255) NOT NULL,
  `balance` varchar(255) NOT NULL DEFAULT '0',
  `paypal_email` varchar(255) DEFAULT NULL,
  `minimum_payout` int(11) NOT NULL DEFAULT '100',
  `clicks` int(11) NOT NULL DEFAULT '0',
  `payout_type` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `referral_payouts`
--

CREATE TABLE IF NOT EXISTS `referral_payouts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `amount` varchar(255) NOT NULL,
  `type` int(11) NOT NULL,
  `status` int(11) NOT NULL DEFAULT '0',
  `created` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `referral_tracking`
--

CREATE TABLE IF NOT EXISTS `referral_tracking` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `referral_id` int(11) NOT NULL,
  `package` int(11) DEFAULT '1',
  `status` int(11) NOT NULL DEFAULT '0',
  `commission` varchar(255) DEFAULT NULL,
  `signup_date` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `rss`
--

CREATE TABLE IF NOT EXISTS `rss` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `url` text,
  `publish_description` int(11) NOT NULL DEFAULT '1',
  `publish_url` int(11) NOT NULL DEFAULT '1',
  `includes` text,
  `excludes` text,
  `referral_code` varchar(255) DEFAULT NULL,
  `autopost` int(11) NOT NULL DEFAULT '1',
  `enabled` int(11) NOT NULL DEFAULT '1',
  `per_hour` int(11) NOT NULL DEFAULT '1',
  `post_per_hour` int(11) NOT NULL DEFAULT '5',
  `next_post_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `rss_accounts`
--

CREATE TABLE IF NOT EXISTS `rss_accounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rss_id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `rss_history`
--

CREATE TABLE IF NOT EXISTS `rss_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rss_id` int(11) NOT NULL,
  `rss_post_id` int(11) NOT NULL,
  `account` int(11) NOT NULL DEFAULT '0',
  `status` int(11) NOT NULL DEFAULT '0',
  `post_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=298 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `rss_posts`
--

CREATE TABLE IF NOT EXISTS `rss_posts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rss_id` int(11) NOT NULL,
  `title` text,
  `content` text,
  `url` text,
  `img` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=188 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE IF NOT EXISTS `sessions` (
  `id` varchar(255) NOT NULL,
  `ip_address` varchar(255) NOT NULL,
  `data` blob NOT NULL,
  `timestamp` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE IF NOT EXISTS `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `settings_key` varchar(255) NOT NULL,
  `settings_value` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=177 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `stripe_products`
--

CREATE TABLE IF NOT EXISTS `stripe_products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `package_id` int(11) NOT NULL,
  `product_id` varchar(255) NOT NULL,
  `monthly_id` varchar(255) NOT NULL,
  `yearly_id` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `subscriptions`
--

CREATE TABLE IF NOT EXISTS `subscriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `subscription_id` varchar(255) NOT NULL,
  `customer_id` varchar(255) NOT NULL,
  `method` varchar(255) NOT NULL,
  `plan_id` varchar(255) NOT NULL,
  `package_id` int(11) DEFAULT NULL,
  `duration_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE IF NOT EXISTS `transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `sale_id` varchar(255) NOT NULL,
  `amount` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `date_created` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `translations`
--

CREATE TABLE IF NOT EXISTS `translations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lang` varchar(255) NOT NULL,
  `lang_key` varchar(255) NOT NULL,
  `original` text NOT NULL,
  `translated` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `lang` (`lang`)
) ENGINE=InnoDB AUTO_INCREMENT=6990 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `tutorials`
--

CREATE TABLE IF NOT EXISTS `tutorials` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `video` varchar(255) NOT NULL,
  `image` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `changed` int(11) NOT NULL,
  `created` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;

--
-- Dumping data for table `tutorials`
--

INSERT INTO `tutorials` (`id`, `name`, `description`, `content`, `video`, `image`, `slug`, `changed`, `created`) VALUES
(1, 'Akpan and Oduma \'CORONA KILLER', 'dshjggfsdjhdfgsjh Akpan and Oduma \'CORONA KILLER Akpan and Oduma \'CORONA KILLER Akpan and Oduma \'CORONA KILLER Akpan and Oduma \'CORONA KILLER', '<p><a href=\"https://www.youtube.com/watch?v=WLaDb2IZ-HA\">https://www.youtube.com/watch?v=WLaDb2IZ-HA</a><a href=\"https://www.youtube.com/watch?v=WLaDb2IZ-HA\" style=\"background-color:rgb(255,255,255);\">https://www.youtube.com/watch?v=WLaDb2IZ-HA</a><a href=\"https://www.youtube.com/watch?v=WLaDb2IZ-HA\" style=\"background-color:rgb(255,255,255);\">https://www.youtube.com/watch?v=WLaDb2IZ-HA</a><a href=\"https://www.youtube.com/watch?v=WLaDb2IZ-HA\" style=\"background-color:rgb(255,255,255);\">https://www.youtube.com/watch?v=WLaDb2IZ-HA</a><a href=\"https://www.youtube.com/watch?v=WLaDb2IZ-HA\" style=\"background-color:rgb(255,255,255);\">https://www.youtube.com/watch?v=WLaDb2IZ-HA</a><a href=\"https://www.youtube.com/watch?v=WLaDb2IZ-HA\" style=\"background-color:rgb(255,255,255);\">https://www.youtube.com/watch?v=WLaDb2IZ-HA</a><br /></p>', 'oEWkngkFSOE', 'https://i.ytimg.com/vi/oEWkngkFSOE/hqdefault.jpg', 'dsfsjhfgjh', 1588662477, 1588655377),
(2, 'An Honest Explanation of the Nigerian Civil War | The Biafran Story', 'An Honest Explanation of the Nigerian Civil War | The Biafran StoryAn Honest Explanation of the Nigerian Civil War | The Biafran Story', '<p>An Honest Explanation of the Nigerian Civil War | The Biafran StoryAn Honest Explanation of the Nigerian Civil War | The Biafran StoryAn Honest Explanation of the Nigerian Civil War | The Biafran StoryAn Honest Explanation of the Nigerian Civil War | The Biafran StoryAn Honest Explanation of the Nigerian Civil War | The Biafran StoryAn Honest Explanation of the Nigerian Civil War | The Biafran StoryAn Honest Explanation of the Nigerian Civil War | The Biafran StoryAn Honest Explanation of the Nigerian Civil War | The Biafran Story<br /></p>', '7JCvIvb8PpY', 'https://i.ytimg.com/vi/7JCvIvb8PpY/maxresdefault.jpg', 'an-honest-explanation-of-the-nigerian-civil-war-the-biafran-story', 1588662617, 1588662617),
(6, 'Abédi Pelé ???? Goals and Skills ???? Olympique Marseille 1991/92', 'Abédi Pelé ???? Goals and Skills ???? Olympique Marseille 1991/92Abédi Pelé ???? Goals and Skills ???? Olympique Marseille 1991/92Abédi Pelé ???? Goals and Skills ???? Olympique Marseille 1991/92', '<p></p><div class=\"style-scope ytd-video-primary-info-renderer\" style=\"margin:0px;padding:0px;border:0px;background:rgb(249,249,249);color:rgb(0,0,0);font-family:Roboto, Arial, sans-serif;font-size:10px;font-style:normal;font-weight:400;letter-spacing:normal;text-indent:0px;text-transform:none;white-space:normal;word-spacing:0px;\"></div><h1 class=\"title style-scope ytd-video-primary-info-renderer\" style=\"margin-right:0px;margin-bottom:0px;margin-left:0px;padding:0px;border:0px;background:rgb(249,249,249);max-height:4.8rem;line-height:2.4rem;font-family:Roboto, Arial, sans-serif;\">Abédi Pelé ???? Goals and Skills ???? Olympique Marseille 1991/92</h1><div class=\"style-scope ytd-video-primary-info-renderer\" style=\"margin:0px;padding:0px;border:0px;background:rgb(249,249,249);color:rgb(0,0,0);font-family:Roboto, Arial, sans-serif;font-size:10px;\"></div><h1 class=\"title style-scope ytd-video-primary-info-renderer\" style=\"margin-right:0px;margin-bottom:0px;margin-left:0px;padding:0px;border:0px;background:rgb(249,249,249);max-height:4.8rem;line-height:2.4rem;font-family:Roboto, Arial, sans-serif;\">Abédi Pelé ???? Goals and Skills ???? Olympique Marseille 1991/92</h1><div class=\"style-scope ytd-video-primary-info-renderer\" style=\"margin:0px;padding:0px;border:0px;background:rgb(249,249,249);color:rgb(0,0,0);font-family:Roboto, Arial, sans-serif;font-size:10px;\"></div><h1 class=\"title style-scope ytd-video-primary-info-renderer\" style=\"margin-right:0px;margin-bottom:0px;margin-left:0px;padding:0px;border:0px;background:rgb(249,249,249);max-height:4.8rem;line-height:2.4rem;font-family:Roboto, Arial, sans-serif;\">Abédi Pelé ???? Goals and Skills ???? Olympique Marseille 1991/92</h1><div class=\"style-scope ytd-video-primary-info-renderer\" style=\"margin:0px;padding:0px;border:0px;background:rgb(249,249,249);color:rgb(0,0,0);font-family:Roboto, Arial, sans-serif;font-size:10px;\"></div><h1 class=\"title style-scope ytd-video-primary-info-renderer\" style=\"margin-right:0px;margin-bottom:0px;margin-left:0px;padding:0px;border:0px;background:rgb(249,249,249);max-height:4.8rem;line-height:2.4rem;font-family:Roboto, Arial, sans-serif;\">Abédi Pelé ???? Goals and Skills ???? Olympique Marseille 1991/92</h1><div class=\"style-scope ytd-video-primary-info-renderer\" style=\"margin:0px;padding:0px;border:0px;background:rgb(249,249,249);color:rgb(0,0,0);font-family:Roboto, Arial, sans-serif;font-size:10px;\"></div><h1 class=\"title style-scope ytd-video-primary-info-renderer\" style=\"margin:0px;padding:0px;border:0px;background:rgb(249,249,249);max-height:4.8rem;font-weight:400;line-height:2.4rem;font-family:Roboto, Arial, sans-serif;font-style:normal;letter-spacing:normal;text-indent:0px;text-transform:none;white-space:normal;word-spacing:0px;\">Abédi Pelé ???? Goals and Skills ???? Olympique Marseille 1991/92</h1>', 'zjTj00-kKVk', 'https://i.ytimg.com/vi/zjTj00-kKVk/maxresdefault.jpg', '', 1588662763, 1588662763);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `permission` text,
  `role` int(11) NOT NULL DEFAULT '0',
  `is_team` int(11) NOT NULL DEFAULT '0',
  `team_role` int(11) NOT NULL DEFAULT '0',
  `package` int(11) DEFAULT NULL,
  `expire_date` varchar(225) DEFAULT NULL,
  `payment_mode` int(11) NOT NULL DEFAULT '1',
  `data` text,
  `status` int(11) NOT NULL DEFAULT '1',
  `lang` varchar(255) NOT NULL DEFAULT 'en',
  `timezone` varchar(255) DEFAULT NULL,
  `date_format` varchar(255) DEFAULT NULL,
  `time_format` varchar(255) DEFAULT NULL,
  `default_color` int(11) NOT NULL DEFAULT '1',
  `activation_code` varchar(255) DEFAULT NULL,
  `recovery_code` varchar(255) DEFAULT NULL,
  `changed` int(11) DEFAULT NULL,
  `created` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `package` (`package`)
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `user_team`
--

CREATE TABLE IF NOT EXISTS `user_team` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `userid` int(11) DEFAULT NULL,
  `ownerid` int(11) NOT NULL,
  `permissions` text NOT NULL,
  `invite_code` varchar(255) DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT '0',
  `last_active_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
COMMIT;
