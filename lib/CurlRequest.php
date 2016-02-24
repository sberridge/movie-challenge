<?php
	class CurlRequest {

		private $ch;

		public function __construct($method,$url,$data=array()) {
			$ch = curl_init();
			$opts = array(
				CURLOPT_URL => $url,
				CURLOPT_POST => (strtoupper($method) === "POST"),
				CURLOPT_POSTFIELDS => $data,
				CURLOPT_RETURNTRANSFER => 1,
			);
			curl_setopt_array($ch, $opts);
			$this->ch = $ch;
		}

		public function setOpt($opt,$val) {
			curl_setopt($this->ch, $opt, $val);
		}

		public static function make($method,$url,$data=array()) {
			$curl = new CurlRequest($method,$url,$data);
			return $curl;
		}

		public function getResponse() {
			$res = curl_exec($this->ch);
			return $res;
		}
	}