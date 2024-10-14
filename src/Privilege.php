<?php

namespace Sonet;


class Privilege {

	private static $privileges = [];
	public $name;

	private function __construct($name) {
		$this->name = $name;
	}

	public static function create($name): void {
		self::$privileges[] = new self($name);
	}

	public static function get($name) {
		for ($i = 0; $i<count(self::$privileges); $i++) {
			if (self::$privileges[$i]->name == $name) {
				return self::$privileges[$i];
			}
		}
		trigger_error("Privilege {$name} does not exist.", E_USER_ERROR);
	}

	public static function getAll() {
		return self::$privileges;
	}

}