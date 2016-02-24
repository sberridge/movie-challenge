<?php
	class Auth {

		private static $user;
		private static $formToken;

		/**
		 * Check if currently logged in session has timed out
		 * @return bool
		 */
		public static function checkTimeout() {
			$auth = Session::get('__auth__');
			$time = $auth['time'];
			if($time === 'never') return true;
			$now = time();
			if($now - $time > Config::get('app.authTimeout')) {
				self::logout();
				return false;
			}
			$auth['time'] = time();
			Session::set('__auth__',$auth);
			return true;
		}

		/**
		 * Get currently logged in user model
		 * @return bool|object false if not logged in or model
		 */
		public static function user() {
			if(!Session::has("__auth__")) {
				return false;
			}
			if(!isset(self::$user)) {
				$auth = Session::get("__auth__");
				
				$class = $auth['class'];
				$model = new $class;

				$user = $model->getById($auth['id']);

				self::$user = $user;
			}
			

			return self::$user;
		}

		/**
		 * Force log in a model
		 * @param object $model model to log in
		 * @return bool true if successfully logged in or false
		 */
		public static function login($model) {
			$implements = class_implements($model);
			if(!in_array("lib\app\authentication\Authenticator",$implements)) {
				throw new Exception("The model does not implement Authenticator");
				return false;
			}
			
			Session::set("__auth__",array(
				"id"=>$model->{$model->getIdentifier()},
				"class"=>get_class($model),
				"time"=>time()
			));
			return true;
		}

		/**
		 * Log out any logged in users
		 */
		public static function logout() {
			Session::remove("__auth__");
			session_destroy();
		}

		/** 
		 * Return whether or not there is a logged in user
		 * @return bool
		 */
		public static function check() {
			return Session::has("__auth__");
		}

		/**
		 * Return an input field for authenticating form submits
		 * @return string html input
		 */
		public static function formToken() {
			if(!isset(self::$formToken)) {
				self::$formToken = Str::random(20);
				Session::flash("csrf_token",self::$formToken);
			}
			return "<input type='hidden' name='csrf_token' value='".self::$formToken."'>";
		}

		/**
		 * check posted CSRF token with one in the session
		 * @return bool
		 */
		public static function csrfCheck() {
			if(!Input::has("csrf_token") || !Session::has("csrf_token") || Input::get("csrf_token") !== Session::get("csrf_token")) {
				throw new exception("CSRF Token mismatch");
			}
			return true;
		}
	}