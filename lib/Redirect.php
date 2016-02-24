<?php
	class Redirect extends lib\app\Router {

		public static function route($name,$params=array()) {
			if(!isset(self::$routes['named'][$name])) return false;
			\lib\app\App::end();
			header('location: '.self::$routes['named'][$name]->generateUrl($params));
			exit;
		}

		public static function url($url) {
			\lib\app\App::end();
			header('location: '.$url);
			exit;
		}
	}