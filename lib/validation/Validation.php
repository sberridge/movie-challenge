<?php
	namespace lib\validation;
	use \Input;
	use \Sql;
	abstract class Validation {
		public static function validate($fields) {
			$valid = true;
			$errors = array();
			foreach($fields as $field=>$rules) {
				foreach($rules as $rule) {
					$exp = explode(':',$rule);
					$params = null;
					if(count($exp) > 1) {
						$rule = $exp[0];
						$params = explode(',',$exp[1]);
					}
					$func = 'validate'.ucfirst($rule);
					if(method_exists('lib\validation\Validation', $func)) {
						if($params) {
							$test = self::$func($field,$params);
						} else {
							$test = self::$func($field);
						}
						if($test !== true) {
							$valid = false;
							$errors[$field] = $test;
							continue;
						}
					}
				}
			}

			$validation = new Static;
			if(!$valid) {
				$validation->errors = $errors;
				$validation->hasError = true;
				return $validation;
			} else {
				$validation->hasError = false;
				return $validation;
			}
		}

		private static function validateNumeric($field) {
			if(Input::has($field)) {
				if(!is_numeric(Input::get($field))) {
					return "The ".\Str::separate($field)." field must be a number";
				}
			}
			return true;
		}

		private static function validateGreaterThan($field,$params) {
			if(Input::has($field)) {
				if(Input::get($field) <= $params[0]) {
					return "The ".\Str::separate($field)." field must be greater than ".$params[0];
				}
			}
			return true;
		}

		private static function validateRequired($field) {
			if(!Input::has($field)) {
				return "The ".\Str::separate($field)." field must be filled out";
			} else {
				return true;
			}
		}

		private static function validateEmail($field) {
			if(Input::has($field)) {
				if(!filter_var(Input::get($field),FILTER_VALIDATE_EMAIL)) {
					return "The ".\Str::separate($field)." field must be a correctly formatted email address";
				}
			}
			return true;
		}

		private static function validateUnique($field,$params) {
			if(Input::has($field)) {
				$table = $params[0];
				$col = $params[1];
				$count = Sql::table(Sql::quote($table))->where(Sql::quote($col),'=',Input::get($field))->count();
				if($count > 0) {
					return "That ".\Str::separate($field)." is already being used";
				}
			}
			return true;
		}

		private static function validateExists($field,$params) {
			if(Input::has($field)) {
				$class = $params[0];
				$model = $class::find(Input::get($field));
				if(!$model) {
					return "That ".\Str::separate($field)." was not found";
				}
			}
			return true;
		}

		private static function validateEquals($field,$params) {
			if(Input::has($field)) {
				if(Input::get($field) !== Input::get($params[0])) {
					return 'The '.\Str::separate($field).' field and '.\Str::separate($params[0]).' field do not match';
				}
			}
			return true;
		}

		private static function validateDate($field) {
			if(Input::has($field)) {
				$valid = true;
				try {
					$date = new \DateTime(Input::get($field));
				} catch (\Exception $e) {
					$valid = false;
				}
				if(!$valid) {
					return 'An invalid date format was given';
				}
				return true;				
			}
			return true;
		}

		private static function validateRequiredWith($field,$params) {
			if(Input::has($field)) return true;
			foreach($params as $test) {
				if(Input::has($test)) {
					return 'The '.\Str::separate($field).' field is required with the '.\Str::separate($test).' field';
				}
			}
			return true;
		}

		private static function validateRequiredWithout($field,$params) {
			if(Input::has($field)) return true;
			foreach($params as $test) {
				if(!Input::has($test)) {
					return 'The '.\Str::separate($field).' field is required without the '.\Str::separate($test).' field';
				}
			}
			return true;
		}
	}