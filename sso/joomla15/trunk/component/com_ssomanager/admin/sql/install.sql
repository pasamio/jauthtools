CREATE TABLE IF NOT EXISTS `#__sso_plugins` (
  `plugin_id` int(11) NOT NULL default '0',
  `filename` text,
  `type` char(1) default NULL,
  `key` varchar(50) default NULL,
  `cache` text,
  PRIMARY KEY  (`plugin_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Holds a copy of the SSO plugins and useful info';

CREATE TABLE IF NOT EXISTS `#__sso_providers` (
  `id` int(11) NOT NULL auto_increment,
  `plugin_id` int(11) default NULL,
  `name` varchar(100) default NULL,
  `description` text,
  `key` varchar(255) default NULL,
  `published` tinyint(4) default NULL,
  `trusted` tinyint(4) default NULL,
  `status` tinyint(4) default NULL,
  `remotestatus` tinyint(4) default NULL,
  `origin` tinyint(4) default NULL,
  `abbreviation` varchar(10) default NULL,
  `params` text,
  `ordering` int(11) default NULL,
  PRIMARY KEY  (`id`),
  KEY `newindex` (`published`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='SSO Identity Providers';

CREATE TABLE IF NOT EXISTS `#__sso_users` (
  `id` int(12) NOT NULL default '0',
  `ssoIdentityProvider` int(10) unsigned NOT NULL default '0',
  `ssoOrigUsername` varchar(25) NOT NULL default '',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__sso_handles` (
  `handle` varchar(128) NOT NULL default '',
  `ssoIdentityProvider` int(10) unsigned default NULL,
  `username` varchar(25) NOT NULL default '',
  `userIP` varchar(15) NOT NULL default '',
  `time` timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (`handle`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
