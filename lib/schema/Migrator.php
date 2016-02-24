<?php
	namespace lib\schema;
	use \Sql;
	class Migrator {
		public static function createMigration($table) {
			$date = new \DateTime();
					 
			$fileName = $date->format('Y-m-d-H-i-s-').\Str::studly_case($table);
			$class = 
'<?php	
	use lib\schema\Schema;
	class '.\Str::studly_case($table).$date->format('YmdHis').' {
		public function migrate() {

		}

		public function revert() {

		}
	}';
			file_put_contents(ROOT.'/app/migrations/'.$fileName.'.php',$class);
			echo "Migration ".$fileName." created\n\r";
		}

		private static function createMigrationTable() {
			try {
				$count = Sql::table('__migrations__')->count();
			} catch (\lib\exceptions\SqlException $e) {
				Schema::create('__migrations__',function($t){
					$t->int('id')->primary()->increments();
					$t->date('date');
					$t->string('file',300);
				});					
				echo "Migration Table Created\n\r";
			}
			
		}

		private function fileToClass($file) {
			$fileArr = explode('-',$file);
			$fileName = end($fileArr);
			unset($fileArr[count($fileArr)-1]);
			$className = str_replace('.php','',$fileName).implode('',$fileArr);
			return $className;
		}

		public static function migrate() {
			self::createMigrationTable();
			$dir = ROOT.'/app/migrations';
			$migrated = array();
			foreach(Sql::table('__migrations__')->select('*')->order('date','DESC')->get() as $res) {
				$migrated[] = $res['file'];
			}
			$ignore = array_merge($migrated,array('.','..'));
			$date = new \DateTime();
			$dateString = $date->format('Y-m-d H:i:s');
			foreach(scandir($dir) as $file) {
				if(in_array($file,$ignore)) continue;
				$className = self::fileToClass($file);
				if(!class_exists($className)) {
					include($dir.'/'.$file);
				}
				
				$class = new $className;
				$class->migrate();
				echo 'Migrated: '.$file."\r\n";
				Sql::table('__migrations__')->insert(array(
					'file'=>$file,
					'date'=>$dateString
				))->save();
			}
		}

		public static function revert() {
			$file = Sql::table('__migrations__')->select('*')->order('date','DESC')->order('file','DESC')->limit(1)->get();
			if(count($file) == 0) {
				echo 'Nothing to revert';
				return false;
			}
			$id = $file[0]['id'];
			$file = $file[0]['file'];
			include(ROOT.'/app/migrations/'.$file);
			$className = self::fileToClass($file);
			$class = new $className;
			$class->revert();
			echo 'Reverted: '.$file."\r\n";
			Sql::table('__migrations__')->where('id','=',$id)->delete();
		}

		public static function refresh() {
			$count = Sql::table('__migrations__')->count();
			for($i = 0; $i < $count; $i++) {
				self::revert();
			}
			self::migrate();
		}
	}