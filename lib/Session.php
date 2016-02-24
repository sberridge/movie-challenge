<?php
	class Session {

		private static $session=null;

		public static function init() {
			if(!defined("TESTING")) {
				self::$session = &$_SESSION;	
			} else {
				self::$session = unserialize(file_get_contents(ROOT.'/storage/testing/session.txt'));
				$_SESSION = &self::$session;
			}
		}

		public static function get($key) {
			
			return isset(self::$session[$key]) ? self::$session[$key] : null;
		}

		public static function set($key,$val) {
			
			self::$session[$key] = $val;
			if(defined("TESTING")) {
				file_put_contents(ROOT.'/storage/testing/session.txt', serialize(self::$session));
			}
		}

		public static function remove($key) {
			if(self::has($key)) {
				unset(self::$session[$key]);
			}
		}

		public static function manage() {
			
			if(isset(self::$session['__flash__'])) {
				foreach(self::$session['__flash__'] as $key=>&$count) {
					if($count === 1) {
						unset(self::$session['__flash__'][$key]);
						unset(self::$session[$key]);
					} else {
						$count++;
					}
					
				}
			}
		}

		public static function has($key) {
			
			return array_key_exists($key, self::$session);
		}

		public static function flash($key,$val) {
			
			self::$session[$key] = $val;
			self::$session["__flash__"][$key] = 0;
			return array_key_exists($key, self::$session);
		}
	}

	Session::init();