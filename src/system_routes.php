<?php

$router->get('/js/user-levels', function($req, $res) {
	$levels = SONET_USR_LVLS;
	$last = max(array_keys($levels));
	
	header('Content-type: text/javascript');
	
	echo "window['SONET_USR_LVLS'] = [\n";
	
	foreach ($levels as $i=>$level) {
		echo "\t{\n";
		echo "\t\tlevel: $i,\n";
		echo "\t\ttitle: '" . $level['title'] . "'\n";
		echo ($i < $last) ? "\t},\n" : "\t}\n";
	}

	echo "]\n";
});


$router->get('/js/user-data', function($req, $res) {
	header('Content-type: text/javascript');
	
	echo "window['SONET_USR_INFO'] = {\n";
	echo "\tusername: '" . $_SESSION['username']   . "',\n";
	echo "\tlevel: "     . $_SESSION['user_level'] . ",\n";
	echo "\ttitle: '"    . $req->user->getTitle() . "'\n";
	echo "};";
});
