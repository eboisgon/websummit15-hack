
CREATE TABLE `companies` (
  `id_companies` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `country` varchar(145) DEFAULT NULL,
  PRIMARY KEY (`id_companies`),
  UNIQUE KEY `name_UNIQUE` (`name`,`country`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `persons` (
  `id_persons` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) DEFAULT NULL,
  `position` varchar(200) DEFAULT NULL,
  `description` text,
  `id_ext` varchar(45) DEFAULT NULL,
  `id_company` int(11) DEFAULT NULL,
  `country` varchar(45) DEFAULT NULL,
  `avatar` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`id_persons`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `startups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_name` varchar(200) DEFAULT NULL,
  `track` varchar(45) DEFAULT NULL,
  `elevator_pitch` text,
  `description` text,
  `city` varchar(100) DEFAULT NULL,
  `country_code` char(2) DEFAULT NULL,
  `twitter_url` varchar(245) DEFAULT NULL,
  `angellist_url` varchar(245) DEFAULT NULL,
  `crunchbase_url` varchar(245) DEFAULT NULL,
  `facebook_url` varchar(245) DEFAULT NULL,
  `linkedin_url` varchar(245) DEFAULT NULL,
  `fundraising_round` varchar(245) DEFAULT NULL,
  `website_url` varchar(245) DEFAULT NULL,
  `brandisty_url` varchar(245) DEFAULT NULL,
  `parent_industry` varchar(145) DEFAULT NULL,
  `child_industry` varchar(145) DEFAULT NULL,
  `amount_raised` varchar(45) DEFAULT NULL,
  `exhibition_day` varchar(245) DEFAULT NULL,
  `stand_number` varchar(45) DEFAULT NULL,
  `country` varchar(145) DEFAULT NULL,
  `twitter_followers_count` int(10) DEFAULT NULL,
  `twitter_friends_count` int(10) DEFAULT '0',
  `twitter_description` text,
  `twitter_url_2` varchar(245) DEFAULT NULL,
  `angel_startup` text,
  `angel_startup_role` text,
  `dns` text,
  `dns_mx` text,
  `dns_spf` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

