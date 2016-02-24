<?php
	class Input extends \lib\validation\Validation {

		private static function getInput($method,$dontStripTags) {
			$method = strtolower($method);
			$input = $_GET;
			if($method == "request") {
				parse_str(file_get_contents('php://input'),$post_vars);
				$input = array_merge($post_vars,$_POST,$_GET);
			} elseif($method == "post") {
				$input = $_POST;
			} else {
				$input = $_GET;
			}
			if(!$dontStripTags) {
				foreach($input as $key=>$val) {
					if(!is_array($val)) {
						$input[$key] = strip_tags($val);	
					}
				}
			}
			return $input;
		}

		public static function has($key,$method="request",$dontStripTags=false) {
			$input = self::getInput($method,$dontStripTags);
			if(!array_key_exists($key, $input)) {
				return false;
			}
			if(!is_array($input[$key]) && (string)$input[$key] === "0") {
				return true;
			}
			return !empty($input[$key]);
		}

		public static function get($key,$method="request",$dontStripTags=false) {
			
			$input = self::getInput($method,$dontStripTags);
			if(!isset($input[$key])) {
				return null;
			} else {
				return $input[$key];
			}
		}

		public static function all($method="request",$dontStripTags=false) {
			$input = self::getInput($method,$dontStripTags);
			return $input;
		}

		public static function only($arr=array(),$method="request",$dontStripTags=false) {
			
			$return = array();
			foreach($arr as $key) {
				if(self::has($key,$method,$dontStripTags)) {
					$return[$key] = self::get($key);
				}
			}
			return $return;
		}

		public static function except($arr=array(),$method="request",$dontStripTags=false) {
			$return = array();
			foreach(self::all($method,$dontStripTags) as $key=>$val) {
				if(!in_array($key,$arr)) {
					$return[$key] = $val;
				}
			}
			return $return;
		}

		public static function file($name) {
			if(!array_key_exists($name,$_FILES)) {
				return null;
			}
			$fileInfo = $_FILES[$name];
			if(is_array($fileInfo['name'])) {
				$files = array();
				foreach($fileInfo['name'] as $key=>$name) {
					$info = array(
						'name'=>$name,
						'type'=>$fileInfo['type'][$key],
						'tmp_name'=>$fileInfo['tmp_name'][$key],
						'error'=>$fileInfo['error'][$key],
						'size'=>$fileInfo['size'][$key]
					);
					$file = new lib\input\File;
					$file->info = $info;
					$files[] = $file;
				}
				return $files;
			}
			$file = new lib\input\File;
			$file->info = $fileInfo;
			return $file;
			
		}

		public static function files() {
			$files = array();
			foreach($_FILES as $name=>$file) {
				$files[$name] = self::file($name);
			}
			return $files;

		}

		public static function renderInput($attributes=array("class"=>'','value'=>'','type'=>'text')) {
			if(array_key_exists('type',$attributes)) {
				if(in_array($attributes['type'],array('checkbox','radio'))) {
					if(array_key_exists('checked',$attributes) && !$attributes['checked']) {
						unset($attributes['checked']);
					}
				}
			}
			$input = "<input";
			foreach($attributes as $attr=>$value) {
				$input .= ' '.$attr.' ';
				if(!is_null($value)) {
					$input .= '= "'.htmlentities($value).'" ';
				}
			}
			$input .= '>';
			return $input;
		}

		public static function renderSelect($attributes=array('options'=>array(array('value'=>'','text'=>'')))) {
			$select = '<select';
			foreach($attributes as $attr=>$value) {
				if($attr == 'options') continue;
				$select .= ' '.$attr.' ';
				if(!is_null($value)) {
					$select .= '= "'.htmlentities($value).'" ';
				}
			}
			$select .= '>';
			$options = $attributes['options'];
			if(is_object($options) && get_class($options) == "Closure") {
				$options = $options();
			}
			foreach($options as $key=>$opt) {
				if(!is_numeric($key)) {
					$select .= '<optgroup label="'.$key.'">';
					foreach($opt as $o) {
						$option = '<option value="'.$o['value'].'" ';
						if(isset($attributes['value']) && $attributes['value'] == $o['value']) {
							$option .= ' selected ';
						}
						foreach($o as $attr=>$val) {
							if(!in_array($attr,array('value','text'))) {
								$option .= ' '.$attr.' = '.$val;
							}
						}
						$option .= '>'.$o['text'].'</option>';
						$select .= $option;
					}
					$select .= '</optgroup>';
				} else {
					$option = '<option value="'.$opt['value'].'" ';
					if(isset($attributes['value']) && $attributes['value'] == $opt['value']) {
						$option .= ' selected ';
					}
					foreach($opt as $attr=>$val) {
						if(!in_array($attr,array('value','text'))) {
							$option .= ' '.$attr.' = '.$val;
						}
					}
					$option .= '>'.$opt['text'].'</option>';
					$select .= $option;	
				}
				
			}
			$select .= '</select>';
			return $select;
		}

		public static function renderText($attributes=array()) {
			$text = '<textarea';
			foreach($attributes as $attr=>$value) {
				$text .= ' '.$attr.' ';
				if(!is_null($value)) {
					$text .= '= "'.htmlentities($value).'" ';
				}
			}
			$text .= '>';
			if(isset($attributes['value'])) {
				$text .= $attributes['value'];
			}
			$text .= '</textarea>';
			return $text;
		}
	}