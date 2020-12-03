<?php
$db = Database::getInstance();
try {
    $db->query("ALTER TABLE `rss` ADD `per_hour` INT NOT NULL DEFAULT '1' AFTER `enabled`, ADD `post_per_hour` INT NOT NULL DEFAULT '5' AFTER `per_hour`;
ALTER TABLE `rss` ADD `next_post_time` INT NULL DEFAULT NULL AFTER `post_per_hour`;");

    $db->query("CREATE TABLE IF NOT EXISTS `inboxes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `account` int(11) NOT NULL,
  `unread` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8;

");

}catch (Exception $e){}