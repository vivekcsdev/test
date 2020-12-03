<?php
$db = Database::getInstance();

try {
    $db->query("CREATE TABLE IF NOT EXISTS `subscriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `subscription_id` varchar(255) NOT NULL,
  `method` varchar(255) NOT NULL,
  `plan_id` varchar(255) NOT NULL,
  `duration_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
ALTER TABLE `subscriptions` ADD `customer_id` VARCHAR(255) NOT NULL AFTER `subscription_id`;
ALTER TABLE `subscriptions` ADD `package_id` INT NULL DEFAULT NULL AFTER `plan_id`;

CREATE TABLE IF NOT EXISTS stripe_products` ( `id` INT NOT NULL AUTO_INCREMENT , `package_id` INT NOT NULL , `product_id` VARCHAR(255) NOT NULL , `monthly_id` VARCHAR(255) NOT NULL , `yearly_id` VARCHAR(255) NOT NULL , PRIMARY KEY (`id`));
CREATE TABLE IF NOT EXISTS paypal_products` ( `id` INT NOT NULL AUTO_INCREMENT , `package_id` INT NOT NULL , `product_id` VARCHAR(255) NOT NULL , `monthly_id` VARCHAR(255) NOT NULL , `yearly_id` VARCHAR(255) NOT NULL , PRIMARY KEY (`id`));

");
    $db->query("CREATE TABLE IF NOT EXISTS `designs` (
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
-- Table structure for table `draft_collections`
--

CREATE TABLE IF NOT EXISTS `draft_collections` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `userid` int(11) NOT NULL,
  `ownerid` int(11) NOT NULL,
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
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;

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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
COMMIT;");

    $db->query("ALTER TABLE `users` ADD `payment_mode` INT NOT NULL DEFAULT '1' AFTER `expire_date`;");
    $db->query("ALTER TABLE `users` ADD `default_color` INT NOT NULL DEFAULT '1' AFTER `time_format`;");
    $db->query("ALTER TABLE `posts` CHANGE `account` `account` VARCHAR(255) NULL DEFAULT NULL;");
} catch (Exception $e) {}