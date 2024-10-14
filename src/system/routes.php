<?php

$router->get('./user', function($req, $res) {
	$user = new stdClass();
	$user->username = $_SESSION['username'] ?? "Invité";
	$user->group = $_SESSION['user_group'] ?? "guest";
	$res->json($user);
});
