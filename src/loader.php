<?php

namespace Sonet;


require_once './lib/Sonet/config.php';
require_once './vendor/autoload.php';


spl_autoload_register(function($class) {
	$class = str_replace(__NAMESPACE__ . '\\', '', $class);
	$class = str_replace(array( '\\', '/' ), DIRECTORY_SEPARATOR, __DIR__ . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . $class . '.php');

	if (false === ($class = realpath($class))) {
		return false;
	}
	else {
		require_once $class;
		return true;
	}
});
