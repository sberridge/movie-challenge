<?php
	class Url extends lib\app\Router {

		public static function route($name,$params=array()) {
			if(!isset(self::$routes['named'][$name])) return false;
			return self::$routes['named'][$name]->generateUrl($params);
		}

		public static function current() {
			return self::$currentRoute->generateUrl(array_values(self::$params));
		}
	}