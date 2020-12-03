<?php
$db = Database::getInstance();
try {
    $db->query("CREATE TABLE IF NOT EXISTS `hashtags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
");
    $db->query("ALTER TABLE `files` ADD `sort_number` INT NOT NULL DEFAULT '0' AFTER `folder_id`;");


}catch (Exception $e){}