<?php
	define("ROOT",dirname(dirname(__FILE__)));
	include("../shared/bootstrap.php");
	lib\app\App::start();
	lib\app\App::end();