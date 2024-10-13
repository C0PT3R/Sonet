<?php

namespace Sonet;


class UserGroup {

	private static $groups = [];

	private $name;
	private $privileges = [];

	private function __construct($name) {
		$this->name = $name;
	}

	public static function create($name) {
		array_push(self::$groups, new self($name));
	}

	public static function get($name) {
		for ($i = 0; $i<count(self::$groups); $i++) {
			if (self::$groups[$i]->name == $name) {
				return self::$groups[$i];
			}
		}
		trigger_error("Group '$name' does not exist.", E_USER_ERROR);
	}

	public static function getAll() {
		return self::$groups;
	}

	public function grant(string|array ...$privileges) {
		foreach ($privileges as $privilege) {
			if (is_array($privilege)) {
				foreach ($privilege as $p) $this->grant($p);
			} else {
				$this->privileges[] = Privilege::get($privilege);
			}
		}
	}

	public function grantAll() {
		$this->privileges = Privilege::getAll();
	}

	public function hasPrivilege(string|array ...$privileges) {
		$names = [];

		foreach ($privileges as $privilege) {
			if (is_array($privilege)) {
				foreach ($privilege as $name) $names[] = $name;
			} else {
				$names[] = $privilege;
			}
		}

		foreach ($names as $name) {
			$res = false;
			foreach ($this->privileges as $privilege) {
				if ($privilege->name == $name) $res = true;
			}
			if (!$res) return false;
		}

		return true;
	}

}