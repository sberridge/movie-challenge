<?php
	class Config {

		/**
		 * Get configuration from config file
		 * Config::get('app.url');
		 * @param string $config file and array path for retrieving config value
		 * @return mixed configuration setting
		 */
		public static function get($config) {
			$filePath = explode("/",$config);
			$path = "config/";
			$addEnv = false;
			if(defined("ENVIRONMENT")) {
				$envPath = "config/".ENVIRONMENT."/";
				$addEnv = true;
			}
			while(count($filePath) > 1) {
				$path .= $filePath[0]."/";
				if($addEnv) {
					$envPath .= $filePath[0]."/";
				}
				array_shift($filePath);
			}
			$arrayPath = explode(".",end($filePath));
			$file = $arrayPath[0];
			array_shift($arrayPath);
			$config = include(ROOT."/".$path.$file.".php");
			if($addEnv && file_exists(ROOT."/".$envPath.$file.".php")) {
				$config = array_merge($config,include(ROOT."/".$envPath.$file.".php"));
			}
			while(count($arrayPath) > 0) {
				$config = $config[$arrayPath[0]];
				array_shift($arrayPath);
			}
			return $config;
		}
	}