<?php
	namespace lib\app\authentication;
	use \lib\modelling\BaseModel;
	use \Hasher;
	use \Session;
	class Auth extends BaseModel {

		public static function authAttempt($identifier,$password) {
			$class = get_called_class();
			$model = new $class;
			$implements = class_implements($model);
			if(!in_array("lib\app\authentication\Authenticator",$implements)) {
				throw new Exception("The model does not implement Authenticator");
				return false;
			}

			$uniqueField = isset($model->authUniqueField) ? $model->authUniqueField : "email";

			$model = $class::where($uniqueField,"=",$identifier)->limit(1)->get();

			if(count($model) == 0) {
				return false;
			} else {
				$model = $model[0];
			}

			$hashCheck = Hasher::check($password,$model->getPassword());

			if($hashCheck === true) {
				\Auth::login($model);
				return true;
			}
			return false;
		}

	}