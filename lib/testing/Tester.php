<?php
	namespace lib\testing;
	abstract class Tester {
		protected function call($method,$url,$data=array()) {

			$url = \Config::get('app.url').$url;

			$curl = \CurlRequest::make($method,$url,$data);
			$curl->setOpt(CURLOPT_HTTPHEADER,array('__testing__: true'));
			return $curl;
		}
	}