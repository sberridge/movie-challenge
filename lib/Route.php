<?php
	use lib\app\Router;
	class Route extends Router {

		protected $url;
		protected $method;
		protected $controller;
		public $name;
        protected $paramList = Array();
        
        public function __isset($name) {
            return array_key_exists($name, $this->paramList);
        }
        
        public function __get($name) {
            if(array_key_exists($name, $this->paramList)) {
                return $this->paramList[$name];
            }
            
            throw new Exception("Parameter '{$name}' does not exist!");
            
        }
                
		public function generateUrl($params=array()) {
			$url = Config::get('app.url');
			if($url[strlen($url)-1] !== "/") {
				$url .= '/';
			}
			$urlEnd = $this->url;
			if($urlEnd == '/') {
				$urlEnd = '';
			}
			if(strlen($urlEnd) > 0 && $urlEnd[0] == '/') {
				$urlEnd = substr($urlEnd, 1,strlen($urlEnd));
			}
			$url .= $urlEnd;
			preg_match_all('/({[A-Za-z0-9-_]+})/', $url,$matches);
			if(count($matches[1]) > 0) {
				foreach($matches[1] as $key=>$match) {
					if(isset($params[$key])) {
						$url = str_replace($match, $params[$key], $url);
					}
				}
			}
			return $url;
		}

		private static function makeRoute($method,$url,$controller) {
			
			$route = new Route;
			$route->url = $url;
			$route->method = $method;
			$name = null;
			if(is_array($controller)) {
				$trace = debug_backtrace(1,4);
				if($trace[3]['function'] == 'group') {
					$args = $trace[3]['args'];
					if(is_array($args[1])) {
						$controller = array_merge($controller,$args[1]);
					}
				}
				if(isset($controller['name'])) {
					$name = $controller['name'];
				}
                                
                                $route->paramList = $controller;
                                $route->paramList['type'] = 'controller';
                                
                                $controller = $controller['controller'];

			}

			if(!is_null($name)) {
                                $route->paramList['type'] = 'function';
				$route->name = $name;
				self::$routes['named'][$name] = $route;
			}
			self::$routes[$method][] = $route;
			$route->controller = $controller;
			/*if(self::$currentMethod == $method) {
				if(!self::$foundRoute && self::compare($route)) {
					self::$foundRoute = $route;
				}
			}*/
			return $route;
		}

		public static function get($url,$controller) {
			
			return self::makeRoute("GET",$url,$controller);
		}

		public static function post($url,$controller) {
			return self::makeRoute("POST",$url,$controller);
		}

		public static function put($url,$controller) {
			return self::makeRoute("PUT",$url,$controller);
		}

		public static function delete($url,$controller) {
			return self::makeRoute('DELETE',$url,$controller);
		}

		public static function group($func,$params) {
			$func();
		}

		public function where($key,$pattern=null) {
			if(is_array($key)) {
				$this->where = $key;
			} else {
				$this->where = array($key=>$pattern);
			}
		}
	}