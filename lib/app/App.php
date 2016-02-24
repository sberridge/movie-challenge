<?php
	namespace lib\app;
	class App {
		private static $codes = array(
			'404'=>'404 Not Found',
			'403'=>'403 Forbidden'
		);
		private static $errorControllers = array(

		);
		public static function start() {
            Plugin::registerAll();
			Router::begin();
		}

		public static function end() {
			\Session::manage();
		}

		public static function stop($status) {
			self::end();
			header('HTTP/1.0 '.self::$codes[$status]);
			if(isset(self::$errorControllers[$status])) {
				$controller = self::$errorControllers[$status];
				if(is_object($controller)) {
					$controller();
				} else {
					$controllerExp = explode('@', $controller);
					$controller = new $controllerExp[0];
					$controller->{$controllerExp[1]}();
				}
			}
			die;
		}

		public static function registerErrorController($code,$controller) {
			self::$errorControllers[$code] = $controller;
		}
	}