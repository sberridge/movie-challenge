<?php
	namespace lib\app\authentication;
	interface Authenticator {
		public function getPassword();

		public static function getById($id);

		public function getIdentifier();
	}