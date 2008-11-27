CREATE TABLE IF NOT EXISTS `#__jauthtools_tokens` (
  `logintoken` varchar(150)  NOT NULL,
  `username` varchar(150)  NOT NULL,
  `logins` int UNSIGNED NOT NULL,
  `expiry` VARCHAR(14)  NOT NULL,
  `landingpage` TEXT NOT NULL,
  PRIMARY KEY (`logintoken`),
  INDEX `usernames`(`username`),
  INDEX `expiry`(`expiry`)
)
ENGINE = MyISAM
COMMENT = 'JAuthTools Token Store';