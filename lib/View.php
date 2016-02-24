<?php
	class View {

		private static $shared = array();
		private static $rendered = array();
		private $file;
		private $viewName;

		public static function share($array) {
			self::$shared = array_merge(self::$shared,$array);
		}

		public function render() {
			if(!in_array($this->viewName,self::$rendered)) {
				self::$rendered[] = $this->viewName;
			}
			
			extract(array_merge($this->vars,self::$shared));
			include($this->file);
		}

		public static function current() {
			$backtrace = debug_backtrace(10);
			foreach($backtrace as $trace) {
				$file = str_replace('\\', '/', $trace['file']);
				$pos = strpos($file,'app/views');
				if($pos === false) {
					continue;
				}
				$file = str_replace('app/views/','',substr($file,$pos));
				$file = str_replace('.php','',$file);
				foreach(self::$rendered as $view) {
					if($view == $file) {
						return $view;
					}
				}
			}
			return null;
		}

		public static function make($file,$vars=array(),$basePath=null) {
			$view = new Static;
			$vars = array_merge($vars,self::$shared);
			$view->vars = $vars;
			$view->viewName = $file;
			$filePath = explode("/",$file);
			if(!is_null($basePath)) {
				$path = $basePath;
			} else {
				$path = "/app/views/";
			}			
			while(count($filePath) > 1) {
				$path .= $filePath[0]."/";
				array_shift($filePath);
			}
			$file = end($filePath);
			$view->file = ROOT.$path.$file.".php";
			return $view;
		}
	}