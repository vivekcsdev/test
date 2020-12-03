<?php
$db = Database::getInstance();
try {
    $db->query("CREATE TABLE IF NOT EXISTS `tutorials` (
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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;");
} catch (Exception $e){}