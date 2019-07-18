<?php
	$dotenv = Dotenv\Dotenv::create(dirname(__DIR__));
	$dotenv->load();

	define('DB_HOST', getenv('DB_HOST'));
	define('DB_USER', getenv('DB_USER'));
	define('DB_PASSWORD', getenv('DB_PASSWORD'));
	define('DB_NAME', getenv('DB_NAME'));
	define('APPROOT', dirname(dirname(__FILE__)));
	define('URLROOT', getenv('URLROOT'));
	define('SITENAME', getenv('SITENAME'));
	define('SERVER_ERR_MSG', getenv('SERVER_ERR_MSG'));