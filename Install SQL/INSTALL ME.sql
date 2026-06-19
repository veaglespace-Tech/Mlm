-- Full Database Dump
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- --------------------------------------------------------

-- Table structure for table `affiliateuser`

CREATE TABLE IF NOT EXISTS `affiliateuser` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(15) NOT NULL,
  `password` text NOT NULL,
  `fname` text NOT NULL,
  `address` text NOT NULL,
  `email` text NOT NULL,
  `referedby` varchar(15) NOT NULL DEFAULT 'none',
  `parent_id` int DEFAULT NULL,
  `position` enum('L','R') DEFAULT NULL,
  `default_leg` enum('L','R','AUTO') NOT NULL DEFAULT 'AUTO',
  `left_count` int NOT NULL DEFAULT '0',
  `right_count` int NOT NULL DEFAULT '0',
  `paid_pairs` int NOT NULL DEFAULT '0',
  `ipaddress` int unsigned NOT NULL,
  `mobile` bigint NOT NULL,
  `active` int NOT NULL,
  `doj` date NOT NULL,
  `country` text NOT NULL,
  `tamount` double NOT NULL DEFAULT '0',
  `payment` varchar(10) NOT NULL,
  `signupcode` text NOT NULL,
  `level` int NOT NULL DEFAULT '2',
  `pcktaken` int NOT NULL DEFAULT '0',
  `launch` int NOT NULL DEFAULT '0',
  `launch_time` datetime DEFAULT NULL,
  `is_binary_qualified` int NOT NULL DEFAULT '0',
  `expiry` date NOT NULL DEFAULT '2199-12-31',
  `bankname` varchar(250) NOT NULL DEFAULT 'Not Available',
  `accountname` varchar(250) NOT NULL DEFAULT 'Not Available',
  `accountno` double NOT NULL DEFAULT '0',
  `accounttype` int NOT NULL DEFAULT '0',
  `ifsccode` varchar(100) NOT NULL DEFAULT 'Not Available',
  `getpayment` int NOT NULL DEFAULT '1',
  `renew` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`username`),
  UNIQUE KEY `Id` (`Id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=latin1;

-- Dumping default admin for table `affiliateuser`

INSERT INTO `affiliateuser` (`Id`, `username`, `password`, `fname`, `address`, `email`, `referedby`, `ipaddress`, `mobile`, `active`, `doj`, `country`, `tamount`, `payment`, `signupcode`, `level`, `pcktaken`, `launch`, `expiry`, `bankname`, `accountname`, `accountno`, `accounttype`, `ifsccode`, `getpayment`, `renew`) VALUES
(1, 'adminadmin', '123123123', 'Full Admin Name', 'Address OF Company Or Individual', 'EmailofAdmin@Domain.com', 'none', 0, 0, 1, '0000-00-00', 'Country', 0, '', '0', 1, 1, 0, '0000-00-00', 'Not Available', 'Not Available', 0, 0, 'Not Available', 1, 0);

-- --------------------------------------------------------

-- Table structure for table `banners`

CREATE TABLE IF NOT EXISTS `banners` (
  `bannerid` double NOT NULL AUTO_INCREMENT,
  `bannerdesc` varchar(100) NOT NULL,
  `bannerhtml` text NOT NULL,
  `banneractive` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`bannerid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- Table structure for table `currency`

CREATE TABLE IF NOT EXISTS `currency` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` text NOT NULL,
  `code` text NOT NULL,
  `comment` text NOT NULL,
  PRIMARY KEY (`id`),
  FULLTEXT KEY `code` (`code`)
) ENGINE=MyISAM AUTO_INCREMENT=27 DEFAULT CHARSET=latin1;

-- Dumping data for table `currency`

INSERT INTO `currency` (`id`, `name`, `code`, `comment`) VALUES ('1', 'US Dollar', 'USD', '');
INSERT INTO `currency` (`id`, `name`, `code`, `comment`) VALUES ('2', 'Australian Dollar', 'AUD', '');
INSERT INTO `currency` (`id`, `name`, `code`, `comment`) VALUES ('3', 'Brazilian Real', 'BRL', '');
INSERT INTO `currency` (`id`, `name`, `code`, `comment`) VALUES ('4', 'Canadian Dollar', 'CAD', '');
INSERT INTO `currency` (`id`, `name`, `code`, `comment`) VALUES ('5', 'Czech Koruna', 'CZK', '');
INSERT INTO `currency` (`id`, `name`, `code`, `comment`) VALUES ('6', 'Danish Krone', 'DKK', '');
INSERT INTO `currency` (`id`, `name`, `code`, `comment`) VALUES ('7', 'Euro', 'EUR', '');
INSERT INTO `currency` (`id`, `name`, `code`, `comment`) VALUES ('8', 'Thai Baht', 'THB', '');
INSERT INTO `currency` (`id`, `name`, `code`, `comment`) VALUES ('9', 'Hong Kong Dollar', 'HKD', '');
INSERT INTO `currency` (`id`, `name`, `code`, `comment`) VALUES ('10', 'Hungarian Forint', 'HUF', '');
INSERT INTO `currency` (`id`, `name`, `code`, `comment`) VALUES ('11', 'Israeli New Sheqel', 'ILS', '');
INSERT INTO `currency` (`id`, `name`, `code`, `comment`) VALUES ('12', 'Japanese Yen', 'JPY', '');
INSERT INTO `currency` (`id`, `name`, `code`, `comment`) VALUES ('13', 'Malaysian Ringgit', 'MYR', '');
INSERT INTO `currency` (`id`, `name`, `code`, `comment`) VALUES ('14', 'Mexican Peso', 'MXN', '');
INSERT INTO `currency` (`id`, `name`, `code`, `comment`) VALUES ('15', 'Norwegian Krone', 'NOK', '');
INSERT INTO `currency` (`id`, `name`, `code`, `comment`) VALUES ('16', 'New Zealand Dollar', 'NZD', '');
INSERT INTO `currency` (`id`, `name`, `code`, `comment`) VALUES ('17', 'Philippine Peso', 'PHP', '');
INSERT INTO `currency` (`id`, `name`, `code`, `comment`) VALUES ('18', 'Polish Zloty ', 'PLN', '');
INSERT INTO `currency` (`id`, `name`, `code`, `comment`) VALUES ('19', 'Pound Sterling', 'GBP', '');
INSERT INTO `currency` (`id`, `name`, `code`, `comment`) VALUES ('20', 'Russian Ruble', 'RUB', '');
INSERT INTO `currency` (`id`, `name`, `code`, `comment`) VALUES ('21', 'Singapore Dollar', 'SGD', '');
INSERT INTO `currency` (`id`, `name`, `code`, `comment`) VALUES ('22', 'Swedish Krona', 'SEK', '');
INSERT INTO `currency` (`id`, `name`, `code`, `comment`) VALUES ('23', 'Swiss Franc', 'CHF', '');
INSERT INTO `currency` (`id`, `name`, `code`, `comment`) VALUES ('24', 'Taiwan New Dollar', 'TWD', '');
INSERT INTO `currency` (`id`, `name`, `code`, `comment`) VALUES ('26', 'Turkish Lira', 'TRY', '');

-- --------------------------------------------------------

-- Table structure for table `emailtext`

CREATE TABLE IF NOT EXISTS `emailtext` (
  `id` int NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `etext` text NOT NULL,
  `emailactive` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=latin1;

-- Dumping data for table `emailtext`

INSERT INTO `emailtext` (`id`, `code`, `etext`, `emailactive`) VALUES ('1', 'SIGNUP', 'This email is the confirmation for your order you have just signed up. Thank you for your interest. Our team welcomes you to our website. \n\nRegards\nTeam Veagle Space', '1');
INSERT INTO `emailtext` (`id`, `code`, `etext`, `emailactive`) VALUES ('2', 'FORGOTPASSWORD', 'Hi, \nYou have requested a password on our website and therefore we have sent the password on this email. If you haven\'t do it please ignore the email.\n\nRegards\nTeam Veagle Space', '1');
INSERT INTO `emailtext` (`id`, `code`, `etext`, `emailactive`) VALUES ('3', 'NEWMEMBER', 'You have got new order, bingo!', '1');
INSERT INTO `emailtext` (`id`, `code`, `etext`, `emailactive`) VALUES ('4', 'NEWMEMBER', 'You have got new order, bingo!', '1');
INSERT INTO `emailtext` (`id`, `code`, `etext`, `emailactive`) VALUES ('5', 'SIGNUP', 'This email is the confirmation for your order you have just signed up. Thank you for your interest. Our team welcomes you to our website. \r\n\r\nRegards\r\nTeam Veagle Space', '1');
INSERT INTO `emailtext` (`id`, `code`, `etext`, `emailactive`) VALUES ('6', 'NEWMEMBER', 'You have got new order, bingo!', '1');
INSERT INTO `emailtext` (`id`, `code`, `etext`, `emailactive`) VALUES ('7', 'SIGNUP', 'This email is the confirmation for your order you have just signed up. Thank you for your interest. Our team welcomes you to our website. \r\n\r\nRegards\r\nTeam Veagle Space', '1');
INSERT INTO `emailtext` (`id`, `code`, `etext`, `emailactive`) VALUES ('8', 'NEWMEMBER', 'You have got new order, bingo!', '1');
INSERT INTO `emailtext` (`id`, `code`, `etext`, `emailactive`) VALUES ('9', 'SIGNUP', 'This email is the confirmation for your order you have just signed up. Thank you for your interest. Our team welcomes you to our website. \r\n\r\nRegards\r\nTeam Veagle Space', '1');

-- --------------------------------------------------------

-- Table structure for table `notifications`

CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `subject` text NOT NULL,
  `body` text NOT NULL,
  `posteddate` date NOT NULL,
  `valid` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- Table structure for table `packages`

CREATE TABLE IF NOT EXISTS `packages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` text NOT NULL,
  `price` double NOT NULL DEFAULT '0',
  `currency` text NOT NULL,
  `details` text NOT NULL,
  `tax` double NOT NULL DEFAULT '0',
  `mpay` double NOT NULL DEFAULT '0',
  `sbonus` double DEFAULT '0',
  `cdate` date NOT NULL,
  `active` int NOT NULL DEFAULT '1',
  `level1` double NOT NULL DEFAULT '0',
  `level2` double NOT NULL DEFAULT '0',
  `level3` double NOT NULL DEFAULT '0',
  `level4` double NOT NULL DEFAULT '0',
  `level5` double NOT NULL DEFAULT '0',
  `level6` double NOT NULL DEFAULT '0',
  `level7` double NOT NULL DEFAULT '0',
  `level8` double NOT NULL DEFAULT '0',
  `level9` double NOT NULL DEFAULT '0',
  `level10` double NOT NULL DEFAULT '0',
  `level11` double NOT NULL DEFAULT '0',
  `level12` double NOT NULL DEFAULT '0',
  `level13` double NOT NULL DEFAULT '0',
  `level14` double NOT NULL DEFAULT '0',
  `level15` double NOT NULL DEFAULT '0',
  `level16` double NOT NULL DEFAULT '0',
  `level17` double NOT NULL DEFAULT '0',
  `level18` double NOT NULL DEFAULT '0',
  `level19` double NOT NULL DEFAULT '0',
  `level20` double NOT NULL DEFAULT '0',
  `gateway` int NOT NULL DEFAULT '3',
  `validity` int NOT NULL DEFAULT '0',
  `binary_percent` decimal(5,2) NOT NULL DEFAULT '30.00',
  `sponsor_percent` decimal(5,2) NOT NULL DEFAULT '10.00',
  `capping_status` tinyint(1) NOT NULL DEFAULT '1',
  `capping_limit` int NOT NULL DEFAULT '10',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;

-- Dumping data for table `packages`

INSERT INTO `packages` (`id`, `name`, `price`, `currency`, `details`, `tax`, `mpay`, `sbonus`, `cdate`, `active`, `level1`, `level2`, `level3`, `level4`, `level5`, `level6`, `level7`, `level8`, `level9`, `level10`, `level11`, `level12`, `level13`, `level14`, `level15`, `level16`, `level17`, `level18`, `level19`, `level20`, `gateway`, `validity`, `binary_percent`, `sponsor_percent`, `capping_status`, `capping_limit`) VALUES ('1', 'Starter Plan', '1000', 'INR', 'One time lifetime purchase plan.', '0', '0', '0', '2026-05-18', '1', '200', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '3', '99999', '30.00', '10.00', '1', '10');

-- --------------------------------------------------------

-- Table structure for table `pair_countdowns`

CREATE TABLE IF NOT EXISTS `pair_countdowns` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `pair_no` int NOT NULL,
  `first_member_joined_at` datetime NOT NULL,
  `completed_at` datetime DEFAULT NULL,
  `qualified` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_user_pair` (`user_id`,`pair_no`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

-- Table structure for table `pairing_transactions`

CREATE TABLE IF NOT EXISTS `pairing_transactions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `gross_amount` decimal(10,2) NOT NULL,
  `tds_amount` decimal(10,2) NOT NULL,
  `net_amount` decimal(10,2) NOT NULL,
  `pairs_count` int NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

-- Table structure for table `paymentgateway`

CREATE TABLE IF NOT EXISTS `paymentgateway` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Name` text NOT NULL,
  `status` int NOT NULL DEFAULT '0',
  `comment` int NOT NULL,
  `date` date NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=latin1;

-- Dumping data for table `paymentgateway`

INSERT INTO `paymentgateway` (`id`, `Name`, `status`, `comment`, `date`) VALUES ('1', 'PayPal', '0', '0', '0000-00-00');
INSERT INTO `paymentgateway` (`id`, `Name`, `status`, `comment`, `date`) VALUES ('2', 'Cash On Delivery', '0', '0', '0000-00-00');
INSERT INTO `paymentgateway` (`id`, `Name`, `status`, `comment`, `date`) VALUES ('3', 'Payza', '0', '0', '0000-00-00');
INSERT INTO `paymentgateway` (`id`, `Name`, `status`, `comment`, `date`) VALUES ('4', 'SolidTrustPay', '0', '0', '0000-00-00');

-- --------------------------------------------------------

-- Table structure for table `payments`

CREATE TABLE IF NOT EXISTS `payments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `userid` varchar(50) NOT NULL,
  `payment_amount` double NOT NULL DEFAULT '0',
  `payment_status` int NOT NULL DEFAULT '0',
  `itemid` varchar(25) NOT NULL,
  `createdtime` datetime NOT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `review_note` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- Table structure for table `payu_payments`

CREATE TABLE IF NOT EXISTS `payu_payments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `orderid` varchar(255) NOT NULL,
  `transacid` text NOT NULL,
  `price` double DEFAULT '0',
  `currency` text NOT NULL,
  `date` date NOT NULL,
  `pckid` double NOT NULL,
  `gateway` varchar(25) NOT NULL,
  `cod` int NOT NULL DEFAULT '0',
  `renew` int NOT NULL DEFAULT '0',
  `renacid` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- Table structure for table `pending_registrations`

CREATE TABLE IF NOT EXISTS `pending_registrations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(15) NOT NULL,
  `password` text NOT NULL,
  `fname` text NOT NULL,
  `address` text NOT NULL,
  `email` text NOT NULL,
  `referedby` varchar(15) NOT NULL,
  `mobile` bigint NOT NULL,
  `country` text NOT NULL,
  `ipaddress` int unsigned NOT NULL,
  `doj` date NOT NULL,
  `signupcode` text NOT NULL,
  `pcktaken` int NOT NULL DEFAULT '0',
  `expiry` date NOT NULL DEFAULT '2199-12-31',
  `admin_approval_status` varchar(20) NOT NULL DEFAULT 'Pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

-- Table structure for table `products`

CREATE TABLE IF NOT EXISTS `products` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `icon` varchar(100) NOT NULL DEFAULT 'fa-star',
  `emoji` varchar(10) NOT NULL DEFAULT 0xF09F8C9F,
  `active` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table `products`

INSERT INTO `products` (`id`, `name`, `description`, `icon`, `emoji`, `active`) VALUES ('1', 'Veagle Affiliate Digital Kit', 'Premium digital workbook, marketing banners, and tracking templates.', 'fa-cloud-download', '💻', '1');
INSERT INTO `products` (`id`, `name`, `description`, `icon`, `emoji`, `active`) VALUES ('2', 'Veagle Physical Handbook', 'High-quality printed network guide shipped straight to your address.', 'fa-book', '📘', '1');
INSERT INTO `products` (`id`, `name`, `description`, `icon`, `emoji`, `active`) VALUES ('3', 'Veagle Developer Growth Pack', 'Advanced API guides, developer tools, and responsive landing pages.', 'fa-code', '🚀', '1');

-- --------------------------------------------------------

-- Table structure for table `rejected_registrations`

CREATE TABLE IF NOT EXISTS `rejected_registrations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(15) NOT NULL,
  `fname` text NOT NULL,
  `email` text NOT NULL,
  `referedby` varchar(15) NOT NULL,
  `mobile` bigint NOT NULL,
  `country` text NOT NULL,
  `rejected_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

-- Table structure for table `settings`

CREATE TABLE IF NOT EXISTS `settings` (
  `email` varchar(100) NOT NULL DEFAULT 'Enter Your E-Mail Address',
  `sno` int NOT NULL,
  `wlink` varchar(100) NOT NULL DEFAULT 'www.yourwebsite.com',
  `invoicedetails` text NOT NULL,
  `coname` text NOT NULL,
  `fblink` text NOT NULL,
  `twitterlink` text NOT NULL,
  `paypalid` text NOT NULL,
  `maintain` int NOT NULL DEFAULT '0',
  `alwdpayment` int NOT NULL DEFAULT '0' COMMENT 'user will get payment via paypal or via payment in bank account.',
  `minmobile` double NOT NULL,
  `maxmobile` double NOT NULL,
  `footer` varchar(50) NOT NULL,
  `header` varchar(50) NOT NULL,
  `payzaid` varchar(128) NOT NULL DEFAULT 'Not Available',
  `solidtrustid` varchar(128) NOT NULL DEFAULT 'Not Available',
  `solidbutton` varchar(128) NOT NULL DEFAULT 'Not Available',
  `referral_bonus_referrer` decimal(10,2) NOT NULL DEFAULT '50.00',
  `referral_bonus_joinee` decimal(10,2) NOT NULL DEFAULT '25.00',
  `admin_referral_bonus` decimal(10,2) NOT NULL DEFAULT '25.00',
  `smtp_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `smtp_host` varchar(255) NOT NULL DEFAULT '',
  `smtp_port` int NOT NULL DEFAULT '587',
  `smtp_username` varchar(255) NOT NULL DEFAULT '',
  `smtp_password` varchar(255) NOT NULL DEFAULT '',
  `smtp_encryption` varchar(10) NOT NULL DEFAULT 'tls',
  PRIMARY KEY (`sno`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- Dumping data for table `settings`

INSERT INTO `settings` (`email`, `sno`, `wlink`, `invoicedetails`, `coname`, `fblink`, `twitterlink`, `paypalid`, `maintain`, `alwdpayment`, `minmobile`, `maxmobile`, `footer`, `header`, `payzaid`, `solidtrustid`, `solidbutton`, `referral_bonus_referrer`, `referral_bonus_joinee`, `admin_referral_bonus`, `smtp_enabled`, `smtp_host`, `smtp_port`, `smtp_username`, `smtp_password`, `smtp_encryption`) VALUES ('abhijeet.veaglespace@gmail.com', '0', 'www.veaglespace.in', 'Paharganj, India', 'Veagle Space', 'https://fb.com/VeagleSpace', 'https://twitter.com/VeagleSpace', 'play4s-facilitator@yahoo.co.in', '0', '1', '0', '0', 'Powered By - Veagle Space | Made With Love :)', 'Header', 'Payza', 'Solid', 'Button', '50.00', '25.00', '25.00', '1', 'smtp.gmail.com', '587', 'abhijeet.veaglespace@gmail.com', 'olkpfpodnlrsnkdi', 'tls');

