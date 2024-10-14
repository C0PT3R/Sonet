<?php

namespace Sonet;


class Response {
	
	public $status;
	private VirtualPath $path;
	public $requirements;
	
	
	public function send($body) {
		$args = func_get_args();
		
		if ($body instanceof Cacheable) {
			echo new Cache($body);
		} else if (is_object($body) || is_array($body)) {
			header('Content-type: text/plain');
			echo serialize($body);
		} else if (is_string($body)) {
			echo $body;
		} else {
			trigger_error("No available way of sending this data.", E_USER_ERROR);
		}
	}
	
	
	public function html($body) {
		$this->send(new View('./wrapper.html', [
			'body' => $body
		]));
	}
	
	
	public function json($body) {
		header('Content-type: application/json');
		echo json_encode($body);
	}


	public function setPath(VirtualPath $path) {
		$this->path = $path;
	}
	
	
	public function redirect(string $location) {
		$domain_host = $_SERVER['HTTP_HOST'];
		$referer_host = isset($_SERVER["HTTP_REFERER"]) ? parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST) : $domain_host;

		if ($domain_host !== $referer_host) {
			trigger_error("Cross-origin redirection is prohibited.", E_USER_ERROR);
		}

		if ($location == "#referer") {
			$location = isset($_SERVER["HTTP_REFERER"]) ? parse_url($_SERVER["HTTP_REFERER"], PHP_URL_PATH) : "/";
		} else {
			$path = new VirtualPath($this->path, $location);
			$location = $path->resolve();
		}

		header("location: $location");
	}

}
