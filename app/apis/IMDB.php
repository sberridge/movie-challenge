<?php
	namespace app\apis;
	class IMDB {
		private static $url = 'http://www.omdbapi.com/?r=json&type=movie';
		private static function sendRequest($url) {
			$curl = new \CurlRequest('GET',$url);
			return $curl->getResponse();
		}

		public static function getByTitle($title) {
			$url = self::$url.'&t='.urlencode($title);
			return json_decode(self::sendRequest($url));
		}

		public static function getById($id) {
			$url = self::$url.'&i='.urlencode($id);
			return json_decode(self::sendRequest($url));
		}

		public static function search($title,$year=null) {
			$url = self::$url.'&s='.urlencode($title);
			if(!is_null($year) && strlen($year) >= 4) {
				$url .='&y='.$year;
			}
			return json_decode(self::sendRequest($url));
		}
	}