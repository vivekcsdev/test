<?php
$db = Database::getInstance();
try {
    $db->query("ALTER TABLE `draft_collections` ADD `sharable` INT NOT NULL DEFAULT '1' AFTER `ownerid`;");
    $db->query("CREATE TABLE IF NOT EXISTS `automations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `account` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  `features` text,
  `time` int(11) DEFAULT NULL,
  `settings` text,
  `status` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
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
CREATE TABLE IF NOT EXISTS `bulks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `userid` int(11) NOT NULL,
  `status` int(11) NOT NULL DEFAULT '0',
  `created` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
CREATE TABLE IF NOT EXISTS `bulk_account` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bulk_id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
CREATE TABLE IF NOT EXISTS `bulk_completed` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bulk_id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `created` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
CREATE TABLE IF NOT EXISTS `bulk_posts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bulk_id` int(11) NOT NULL,
  `caption` text NOT NULL,
  `file` int(11) NOT NULL,
  `completed` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
CREATE TABLE IF NOT EXISTS `draft_access` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `ownerid` int(11) NOT NULL,
  `code` varchar(255) NOT NULL,
  `status` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");
}catch (Exception $e){}