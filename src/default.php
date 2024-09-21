<?php

const DB_DSN = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8";

/* Cache configuration */
define('CACHE_DIR', 'cache/sonet');
define('CACHE_KEY', 'd12414bc18e5e8e4e922ad7e');
define('CACHE_CLEANUP_INTERVAL', 24);



const SONET_DEFAULT_USER = [
	'username' => "Guest",
	'level' => 0
];
