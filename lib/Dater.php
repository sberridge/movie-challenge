<?php
	class Dater {

		private $date;
		private static $intervals = array();

		public static function make($date=null) {
			$dater = new Dater;
			try {
				$dater->date = new DateTime($date);
			} catch (Exception $e) {
				return false;
			}			
			return $dater;
		}

		public function format($format) {
			return $this->date->format($format);
		}

		public function add($time) {
			if(!array_key_exists($time, self::$intervals)) {
				$interval = new DateInterval($time);
				self::$intervals[$time] = $interval;
			}
			$interval = self::$intervals[$time];
			$this->date->add($interval);
			return $this;
		}
	}