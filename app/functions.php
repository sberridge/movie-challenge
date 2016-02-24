<?php

function dd($val) {
	call_user_func_array("var_dump", func_get_args());
	die;
}

function redirect($url) {
	\lib\app\App::end();
	header('location: '.$url);
	exit;
}