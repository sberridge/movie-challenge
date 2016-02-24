<?php
	namespace lib\app;
	class Router {

		protected static $currentMethod;
		protected static $params;
		private static $urlParts;
		public static $currentUrl;
		public static $currentRoute;
		protected static $routes = array("GET"=>array(),"POST"=>array(),"PUT"=>array(),"DELETE"=>array(),"named"=>array());

		protected static function compare($route) {
			$url = $route->url;
			if($url[0] === "/") {
				$url = substr($url,1, strlen($url)-1);
			}
			$routeParts = explode("/",$url);
			$urlParts = self::$urlParts;
			$found = false;
			if(count($routeParts) == 1) {
				if($var = preg_match("/{([A-Za-z0-9-_]+)}/",$routeParts[0],$matches)) {
					$fullUrl = implode('/',$urlParts);
					if(isset($route->where) && array_key_exists($matches[1], $route->where)) {
						if(preg_match("/".$route->where[$matches[1]]."/", $fullUrl)) {
							$found = true;
						}
					} else {
						$found = true;
					}
					if($found) {
						self::$params = array($matches[1]=>$fullUrl);
					}
				}
			}

			if($found) {
				return $found;
			}
			
			if(count($routeParts) !== count($urlParts)) return false;
			$params = array();
			foreach($routeParts as $key=>$part) {
				$var = null;
				if(isset($urlParts[$key]) && ($urlParts[$key] === $part || $var = preg_match("/{([A-Za-z0-9-_]+)}/",$part,$matches))) {
					if($var) {
						if(isset($route->where) && array_key_exists($matches[1], $route->where)) {
							if(!preg_match("/".$route->where[$matches[1]]."/", $urlParts[$key])) {
								$found = false;
								break;
							}
						}
						$params[$matches[1]] = $urlParts[$key];
					}
					if($key == count($routeParts) - 1) {
						$found = true;
					}
				} else {
					$found = false;
					break;
				}
			}
			if($found) {
				self::$params = $params;
			}
			return $found;
		}

		public static function begin() {
			$method = $_SERVER['REQUEST_METHOD'];
			self::$currentMethod = $method;
			$url = str_replace("?".$_SERVER['QUERY_STRING'], "", $_SERVER['REQUEST_URI']);
			if($url[0] === "/") {
				$url = substr($url, 1,strlen($url)-1);
			}
			$urlParts = explode("/",$url);
			self::$urlParts = $urlParts;
			\Config::get("routes");
			$addons = \Config::get('addons');
			foreach($addons as $addon) {
				$path = realpath(ROOT.'/app/addons/'.$addon.'/routes.php');
				if($path !== false) {
					include($path);
				}
			}
			$found = false;
			foreach(self::$routes[$method] as $route) {
				if(self::compare($route)) {
					$controller = $route->controller;					
					self::$currentRoute = $route;
                                        
                    Plugin::execute('routeFound',Array($route));
                                        
					if(is_object($controller) && get_class($controller) == "Closure") {
						$resp = call_user_func_array($controller, self::$params);
					} else {
						$controllerExp = explode("@",$controller);
						$controller = new $controllerExp[0];
						$func = $controllerExp[1];
						$resp = call_user_func_array(array($controller,$func),self::$params);
					}
					if(is_object($resp) && get_class($resp) === "View") {
						$resp->render();
					}
					$found = true;
					break;
				}
			}
			if(!$found) {
				App::stop(404);
			}
				
			
			/*$found = false;
			foreach($routes as $route=>$controller) {
				if(!isset($controller[$method])) continue;
				if($route[0] === "/") {
					$route = substr($route,1, strlen($route)-1);
				}
				$routeParts = explode("/",$route);
				if(count($routeParts) !== count($urlParts)) continue;
				$params = array();
				foreach($routeParts as $key=>$part) {
					$var = null;
					if(isset($urlParts[$key]) && ($urlParts[$key] === $part || $var = preg_match("/{([A-Za-z0-9-_]+)}/",$part,$matches))) {
						if($var) {
							$params[$matches[1]] = $urlParts[$key];
						}
						if($key == count($routeParts) - 1) {
							$found = true;
						}
					} else {
						$found = false;
						break;
					}
				}
				if($found) {
					$controller = $controller[$method];
					if(is_object($controller) && get_class($controller) === "Closure") {						
						$resp = call_user_func_array($controller, $params);
					} else {
						$controllerExp = explode("@",$controller);
						$controller = new $controllerExp[0];
						$func = $controllerExp[1];
						$resp = call_user_func_array(array($controller,$func),$params);
					}
					if(is_object($resp) && get_class($resp) === "View") {
						$resp->render();
					}
					break;
				}
			}*/
			
		}
	}