<?php

date_default_timezone_set('America/Montreal');

define('SONET_DIR', __DIR__);

/* Database parameters */
define('DB_HOST'     , 'localhost');
define('DB_USER'     , 'root');
define('DB_PASSWORD' , '');
define('DB_NAME'     , 'bidouilleur');
define('DB_DSN'      , 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8');

/* Cache configuration */
define('CACHE_DIR'              , 'cache/sonet');
define('CACHE_KEY'              , 'd12414bc18e5e8e4e922ad7e');
define('CACHE_CLEANUP_INTERVAL' , 24);

/* Login input fields */
define('LOGIN_FIELD_USERNAME'   , 'username');
define('LOGIN_FIELD_PASSWORD'   , 'password');
define('LOGIN_FIELD_PERSISTANT' , 'persistant');

/* Youtube parameters */
define('YT_API_KEY'     , 'AIzaSyATmpECHYSgJPgaVYxRN31uZsuYM5IK46U');
define('YT_CHANNEL_ID'  , 'UCvv3bLR4Ws1GVycHG-t3dJw');


$Sonet_user_levels = [
	0 => [
		'const' => 'USER_LVL_GUEST',
		'title' => 'invité'
	],
	1 => [
		'const' => 'USER_LVL_BASIC',
		'title' => 'basique'
	],
	5 => [
		'const' => 'USER_LVL_VIP',
		'title' => 'VIP'
	],
	10 => [
		'const' => 'USER_LVL_ADMIN',
		'title' => 'administrateur'
	]
];


define("USER_LVL_GUEST", 0);
define("USER_LVL_BASIC", 1);
define("USER_LVL_VIP", 5);
define("USER_LVL_ADMIN", 10);

		
$Sonet_default_user = [
	'username' => 'Invité_' . uniqid(),
	'level'    => USER_LVL_GUEST
];
