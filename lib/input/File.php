<?php
	namespace lib\input;
	class File {
		public $info;

		public function content() {
			return file_get_contents($this->info['tmp_name']);
		}

		public function toBase64() {
			return base64_encode($this->content());
		}

		public function moveTo($fullPath) {
			$fullPath = str_replace('\\','/',$fullPath);
			$pathExp = explode('/',$fullPath);
			$fileName = $pathExp[count($pathExp)-1];
			unset($pathExp[count($pathExp)-1]);
			$path = implode('/',$pathExp);
			$path = realpath($path);
			if($path === false) {
				throw new \Exception('Invalid file path given for file move');
			}
			return move_uploaded_file($this->info['tmp_name'], $path.'/'.$fileName);
		}
	}