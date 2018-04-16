<?php

	DEFINE("DEBUG", 0);

	DEFINE("PARSE_CONFIG_FILE", 	"parse.json");

	DEFINE("HOST", 					"database_host");
	DEFINE("DATABASE", 				"database_name");
	DEFINE("USER", 					"database_user");
	DEFINE("PASSWORD", 				"database_password");

	DEFINE("URL_MAIN_LIST", 		"https://www.bvg.de/de/Meine-BVG/Meine-Augenblicke/Alle-Augenblicke/?act=all&pos=%s");
	DEFINE("URL_MESSAGE",	 		"https://www.bvg.de/de/Meine-BVG/Meine-Augenblicke/Alle-Augenblicke/?act=read-moment&id=%s");

	DEFINE('SQL_GET_CONTENT_ID', 	"SELECT * from 001_bvg WHERE contentId = :contentId");
	DEFINE('SQL_COUNT_MESSAGES', 	"SELECT COUNT(*) as messageCount from 001_bvg");
	DEFINE('SQL_LAST_CONTACTID', 	"SELECTSELECT contentId from 001_bvg ORDER BY contentId DESC LIMIT 0,1");
	DEFINE('SQL_INSERT_MESSAGE', 	"INSERT INTO 001_bvg (contentId, date_met, date_posted, author, title, message, url, line, wordcount_message, wordcount_title, time_difference, hidden, valid) VALUES (:contentId, :date_met, :date_posted, :author, :title, :message, :url, :line, WORDCOUNT(message), WORDCOUNT(title), TIMESTAMPDIFF(MINUTE, date_met, date_posted), :hidden, :valid)");
	DEFINE('SQL_ALL_CONNTENTIDS',	"INSERTSELECT contentId from 001_bvg");
