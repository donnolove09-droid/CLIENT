DROP TABLE IF EXISTS `{tablepre}account`;

CREATE TABLE `{tablepre}account` (
	`id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
	`name` varchar(255) NOT NULL,
	`pw` char(32) NOT NULL,
	`ip` varchar(15) NOT NULL,
	`date` datetime NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE (`name`)
) ENGINE=MyISAM DEFAULT CHARSET={charset};

DROP TABLE IF EXISTS `{tablepre}session`;

CREATE TABLE `{tablepre}session` (
	`id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
	`salt` char(32) NOT NULL,
	`key` varchar(16) NOT NULL,
	`val` varchar(32) NOT NULL,
	`time` int(10) UNSIGNED NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE (`salt`,`key`)
) ENGINE=MyISAM DEFAULT CHARSET={charset};

DROP TABLE IF EXISTS `{tablepre}shield`;

CREATE TABLE `{tablepre}shield` (
	`id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
	`ip` varchar(15) NOT NULL,
	`key` varchar(16) NOT NULL,
	`val` tinyint(3) UNSIGNED NOT NULL,
	`time` int(10) UNSIGNED NOT NULL,
	PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET={charset};

DROP TABLE IF EXISTS `{tablepre}stat`;

CREATE TABLE `{tablepre}stat` (
	`id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
	`ip` varchar(15) NOT NULL,
	`pv` smallint(5) UNSIGNED NOT NULL,
	`date` datetime NOT NULL,
	PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET={charset};

DROP TABLE IF EXISTS `{tablepre}member`;

CREATE TABLE `{tablepre}member` (
	`id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
	`name` varchar(255) NOT NULL,
	`pw` char(32) NOT NULL,
	`ip` varchar(15) NOT NULL,
	`nick` varchar(255) NOT NULL,
	`gem` mediumint(8) UNSIGNED NOT NULL,
	`pea` mediumint(8) UNSIGNED NOT NULL,
	`lock` tinyint(3) UNSIGNED NOT NULL,
	`reg` datetime NOT NULL,
	`login` int(10) UNSIGNED NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE (`name`)
) ENGINE=MyISAM DEFAULT CHARSET={charset};

DROP TABLE IF EXISTS `{tablepre}buy`;

CREATE TABLE `{tablepre}buy` (
	`id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
	`mid` int(10) UNSIGNED NOT NULL,
	`secret` char(32) NOT NULL,
	`type` tinyint(3) UNSIGNED NOT NULL,
	`point` mediumint(8) UNSIGNED NOT NULL,
	`date` datetime NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE (`secret`)
) ENGINE=MyISAM DEFAULT CHARSET={charset};

DROP TABLE IF EXISTS `{tablepre}bill`;

CREATE TABLE `{tablepre}bill` (
	`id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
	`mid` int(10) UNSIGNED NOT NULL,
	`rid` int(10) UNSIGNED NOT NULL,
	`way` tinyint(3) UNSIGNED NOT NULL,
	`type` tinyint(3) UNSIGNED NOT NULL,
	`point` mediumint(8) UNSIGNED NOT NULL,
	`date` datetime NOT NULL,
	PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET={charset};

DROP TABLE IF EXISTS `{tablepre}talk`;

CREATE TABLE `{tablepre}talk` (
	`id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
	`mid` int(10) UNSIGNED NOT NULL,
	`tid` int(10) UNSIGNED NOT NULL,
	`rid` int(10) UNSIGNED NOT NULL,
	`type` tinyint(3) UNSIGNED NOT NULL,
	`time` int(10) UNSIGNED NOT NULL,
	PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET={charset};

DROP TABLE IF EXISTS `{tablepre}room`;

CREATE TABLE `{tablepre}room` (
	`id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
	`cost` mediumint(8) UNSIGNED NOT NULL,
	`mode` tinyint(3) UNSIGNED NOT NULL,
	`house` tinyint(3) UNSIGNED NOT NULL,
	`base` mediumint(8) UNSIGNED NOT NULL,
	`mult` smallint(5) UNSIGNED NOT NULL DEFAULT '1',
	`aid` int(10) UNSIGNED NOT NULL,
	`bid` int(10) UNSIGNED NOT NULL DEFAULT '0',
	`cid` int(10) UNSIGNED NOT NULL DEFAULT '0',
	`acard` varchar(255) NOT NULL DEFAULT '',
	`bcard` varchar(255) NOT NULL DEFAULT '',
	`ccard` varchar(255) NOT NULL DEFAULT '',
	`afight` varchar(255) NOT NULL DEFAULT '',
	`bfight` varchar(255) NOT NULL DEFAULT '',
	`cfight` varchar(255) NOT NULL DEFAULT '',
	`alast` varchar(255) NOT NULL DEFAULT '',
	`blast` varchar(255) NOT NULL DEFAULT '',
	`clast` varchar(255) NOT NULL DEFAULT '',
	`astate` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
	`bstate` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
	`cstate` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
	`atime` int(10) UNSIGNED NOT NULL,
	`btime` int(10) UNSIGNED NOT NULL DEFAULT '0',
	`ctime` int(10) UNSIGNED NOT NULL DEFAULT '0',
	`card` varchar(255) NOT NULL DEFAULT '',
	`count` varchar(255) NOT NULL DEFAULT '',
	`owner` int(10) UNSIGNED NOT NULL,
	`lord` int(10) UNSIGNED NOT NULL DEFAULT '0',
	`play` int(10) UNSIGNED NOT NULL DEFAULT '0',
	`clock` int(10) UNSIGNED NOT NULL DEFAULT '0',
	`state` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
	`time` int(10) UNSIGNED NOT NULL,
	PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET={charset};