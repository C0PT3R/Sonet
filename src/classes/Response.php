<?php

namespace Sonet;


class Response {
	
	public $status;
	private $root_path;
	
	
	public function send($body) {
		$args = func_get_args();
		
		if ($body instanceof Cacheable) {
			echo new Cache($body);
		}
		
		else if (is_object($body)) {
			$format = $args[1] ?? 'json';
			
			switch ($format) {
				case 'dump':
					header('Content-type: text/plain');
					echo serialize($body);
					break;
				case 'json':
				default:
					header('Content-type: application/json');
					echo json_encode($body);
			}
		}
		
		else if (is_string($body))
			echo $body;
		
		else
			trigger_error("Can not send this data.", E_USER_ERROR);
	}


	public function setRootPath($path) {
		$this->root_path = $path;
	}
	
	
	public function json($body) {
		header('Content-type: application/json');
		$this->send($body);
	}
	
	
	public function redirect($target) {
		$domain_host = $_SERVER['HTTP_HOST'];
		$referer_host = isset($_SERVER["HTTP_REFERER"]) ? parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST) : $domain_host;

		if ($domain_host !== $referer_host) {
			trigger_error("Cross-origin redirection is prohibited.", E_USER_ERROR);
		}

		if ($target == "#referer") {
			$location = isset($_SERVER["HTTP_REFERER"]) ? parse_url($_SERVER["HTTP_REFERER"], PHP_URL_PATH) : "/";
		} else {
			$location = "/" . $this->root_path . "/" . trim($target, "/");
		}

		if (substr($location, -1) == "/") $location = substr($location, 0, -1);
		if (empty($location)) $location = "/";

		//echo $domain_host . "</ br>\r\n" . $referer_host;
		header("location: $location");
	}

}
