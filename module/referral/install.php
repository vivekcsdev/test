<?php
$db = Database::getInstance();
try {
    $db->query("DROP TABLE IF EXISTS `referrals`;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `referral_payouts`
--

DROP TABLE IF EXISTS `referral_payouts`;
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

DROP TABLE IF EXISTS `referral_tracking`;
CREATE TABLE IF NOT EXISTS `referral_tracking` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `referral_id` int(11) NOT NULL,
  `package` int(11) DEFAULT '1',
  `status` int(11) NOT NULL DEFAULT '0',
  `commission` varchar(255) DEFAULT NULL,
  `signup_date` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
} catch (Exception $e){}