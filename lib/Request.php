<?php
	class Request {
		public function ajax() {
			$headers = getallheaders();
			if(isset($headers['HTTP_X_REQUESTED_WITH']) && $headers['HTTP_X_REQUESTED_WITH'] == 'xmlhttprequest') {
				return true;
			} else {
				return false;
			}
		}
	}