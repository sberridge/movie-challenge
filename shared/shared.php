<?php
	if (!function_exists('getallheaders')) 
	{ 
	    function getallheaders() 
	    { 
	           $headers = array(); 
	       foreach ($_SERVER as $name => $value) 
	       { 
	           if (substr($name, 0, 5) == 'HTTP_') 
	           { 
	               $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value; 
	           } 
	       } 
	       return $headers; 
	    } 
	}

	
	
	
	include(ROOT."/lib/Config.php");

	$envs = Config::get('environments');

	$machineName = gethostname();
	foreach($envs as $env=>$names) {
		if(in_array($machineName,$names)) {
			define("ENVIRONMENT",$env);
			break;
		}
	}
	date_default_timezone_set('Europe/London');
	session_start();

	spl_autoload_register(function($class) {
		$autoload = Config::get("autoload.paths");
		if(strpos($class,"\\") !== false) {
			$exp = explode('\\',$class);
			$class = end($exp);
			unset($exp[count($exp)-1]);
			$path = implode("\\",$exp);
			
			$path = ROOT."/".$path."/".$class.".php";
			$path = str_replace("\\","/",$path);
				
			if(file_exists($path)) {
				include($path);
				return;
			}
		}
		if(class_exists($class)) return;
		foreach($autoload as $path) {

			if(file_exists(ROOT."/".$path."/".$class.".php")) {
				include(ROOT."/".$path."/".$class.".php");
			}
		}
	});

	$headers = getallheaders();
	if(array_key_exists("__testing__",$headers)) {
		DEFINE("TESTING",1);
	}

	if(Auth::check()) {
		Auth::checkTimeout();
	}
	
