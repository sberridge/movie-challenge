<?php
	namespace app\plugins;
	class Auth extends \lib\app\Plugin {
		public function routeFound($route) {
			if(isset($route->before)) {
				$before = $route->before;
				if(!is_array($before)) {
					$before = array($before);
				}
				if(in_array('checkAuth', $before) && !\Auth::check()) {
					\Redirect::route('showHome');
					return false;
				}
			}
		}
	}