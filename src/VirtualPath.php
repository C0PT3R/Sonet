<?php

namespace Sonet;


const PATH_REGULAR = "([\w\-_]*)";
const PATH_REQUIRED = "!([A-z_][\w_]*)";
const PATH_OPTIONAL = "\?([A-z_][\w_]*)";
const VALID_PATH = "#^((\/)|(((\/" . PATH_REGULAR . ")|(\/" . PATH_REQUIRED . "))+((\/" . PATH_OPTIONAL . ")|(\/" . PATH_REGULAR . "))*))$#";


class PathStruct {
	public array $segments = [];
	public bool $absolute = false;
}


final class VirtualPath {

	private static function destruct(string ...$values) {
		$struct = new PathStruct();
		
		foreach ($values as $value) {
			if (!empty($value)) {
				if ($value[0] === "/") {
					$struct->absolute = true;
					$struct->segments = [];
				}
				foreach (explode('/', $value) as $segment) {
					if (!empty($segment) && $segment != '.')
						$struct->segments[] = $segment;
				}
			}
		}

		return $struct;
	}


	public static function build(string ...$values) {
		$struct = self::destruct(...$values);
		$path = self::resolve(...$values);

		$optionals = [];
		$opt_pos = [];

		// Get positions of optional segments
		foreach ($struct->segments as $k => $segment) {
			if ($segment[0] == '?') {
				$opt_pos[] = $k;
			}
		}

		// Create paths
		$pos = 0;
		foreach ($opt_pos as $v) {
			$s = [];
			while ($pos < $v) {
				$s[] = str_replace("?", "!", $struct->segments[$pos++]);
			}
			$pos = 0;
			$opt = $struct->absolute ? '/': '';
			$optionals[] = $opt . join('/', $s);
		}

		$optionals[] = str_replace("?", "!", $path);

		return $optionals;
	}


	public static function resolve(string ...$values): string {
		$struct = self::destruct(...$values);
		
		// Resolve .. directories
		for ($i = 0; $i < count($struct->segments); $i++) {
			if ($struct->segments[$i] == '..') {
				if ($i - 1 >= 0) {
					if ($struct->segments[$i-1] == '..') continue;
					array_splice($struct->segments, $i-1, 2);
					$i -= 2;
				}
			}
		}

		// Remove all .. at the start of an absolute path
		if ($struct->absolute) {
			while (array_key_exists(0, $struct->segments) && $struct->segments[0] == '..') {
				array_splice($struct->segments, 0, 1);
			}
		}

		$str = $struct->absolute ? '/' : '';
		$str .= join("/", $struct->segments);
		return $str;
	}


	public static function parseParams(string $path, string $uri): \stdClass|null {
		$params = new \stdClass();

		// Get params names
		if (!preg_match_all("#" . PATH_REQUIRED . "#", $path, $pnames)) return null;
		$pnames = $pnames[1];

		// Get params values
		$pattern = preg_replace("#" . PATH_REQUIRED . "#", '([^/]+)', trim($path, '/'));
		if (!preg_match("#^$pattern$#", trim($uri, '/'), $pvals)) return null;
		array_shift($pvals); // Remove the first item as it's not relevant

		// Assign params
		for ($i = 0; $i < count($pvals); $i++) {
			$params->{$pnames[$i]} = $pvals[$i];
		}

		return $params;
	}


	public static function matchesURI(string $path, string $uri): bool {
		//var_dump($path);
		//var_dump($uri);
		$pattern = preg_replace("#" . PATH_REQUIRED . "#", '[^/]+', trim($path, '/'));
		return preg_match("#^$pattern$#", trim($uri, '/')) ? true : false;
	}

}
