<?php

namespace Sonet;


final class View implements Cacheable {
	
	private $templates_path = 'templates';
	private $engine;
	private $template;
	private $prerenders = [];
	private $context = [];
	private $period = 0;
	
	
	public function __construct($template, $context = []) {
		$loader = new \Twig\Loader\FilesystemLoader("./{$this->templates_path}");
		
		$this->engine = new \Twig\Environment($loader, [
			'cache' => './cache/twig',
		]);

		$this->assign([
			'path' => "/{$this->templates_path}",
			'fieldnames' => Config::get()->fieldnames,
			'user' => [
				'username' => $_SESSION['username'] ?? "Guest" . uniqid(),
				'group'    => $_SESSION['user_group'] ?? "guest",
				'isLogged' => (!empty($_SESSION['user_group'])) ? true : false,
				'isAdmin'  => (isset($_SESSION['user_group']) && $_SESSION['user_group'] == "administrateur") ? true : false
			]
		]);
		
		$this->assign($context);
		
		$this->template  = $template;
	}
	
	
	public static function periodic($period, $template, $data = []) {
		$instance = new self($template, $data);
		$instance->period = $period;
		return $instance;
	}
	
	
	public function setPeriod($period) {
		$this->period = $period;
		return $this;
	}
	
	
	public function getPeriod() {
		return $this->period;
	}
	
	
	public function assign() {
		$args = func_get_args();
		
		if (count($args) === 1 && is_array($args[0])) {
			foreach ($args[0] as $k=>$v) $this->assign($k, $v);
		} else {
			$key = $args[0];
			$value = $args[1];
			
			if (!isset($this->context[$key]))
				$this->context[$key] = $value;
			else
				trigger_error("Key '$key' is already set.", E_USER_ERROR);
		}
		
		return $this;
	}
	
	
	public function prepare($prerender) {
		if (is_callable($prerender))
			$this->prerenders[] = \Closure::bind($prerender, $this);
		else
			trigger_error("Prerender is not callable.", E_USER_ERROR);
		
		return $this;
	}
	
	
	public function render() {
		foreach ($this->prerenders as $prerender) $prerender($this);
		return $this->engine->render($this->template, $this->context);
	}
	
}
