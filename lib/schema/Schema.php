<?php
	namespace lib\schema;
	use \Sql;
	class Schema extends Sql {

		private $columns = array();
		private $engine = 'INNODB';

		public function int($name) {
			$column = new Column($name,"int");
			$this->columns[] = $column;
			return $column;
		}

		public function bool($name) {
			$column = new Column($name,"tinyint");
			$column->length = 1;
			$this->columns[] = $column;
			return $column;
		}

		public function text($name,$type='medium') {
			$column = new Column($name,$type."text");
			$this->columns[] = $column;
			return $column;
		}

		public function date($name) {
			$column = new Column($name,"datetime");
			$this->columns[] = $column;
			return $column;
		}

		public function string($name,$length) {
			$column = new Column($name,"varchar",$length);
			$this->columns[] = $column;
			return $column;
		}

		public function float($name) {
			$column = new Column($name,"float");
			$this->columns[] = $column;
			return $column;
		}

		public function drop() {
			$sql = "DROP TABLE ".self::quote($this->table);
			self::raw($sql)->run();
		}

		public function column($name) {
			$column = new Column($name);
			$column->table = $this->table;
			return $column;
		}

		private function addIndexes($columns) {
			foreach($columns as $index) {
				$sql = "CREATE ";
				if($index->index !== true) {
					$sql .= $index->index.' ';
				}
				$sql .= "INDEX ".self::quote($index->name.'_index').' ON '.self::quote($this->table).' ('.self::quote($index->name).')';
				self::raw($sql)->run();
			}
		}

		public function engine($engine) {
			$this->engine = $engine;
		}

		public static function table($table,$function=null) {
			$schema = new Static;
			$schema->table = $table;
			if(!is_null($function)) {
				$function($schema);
				$indexes = array();
				foreach($schema->columns as $column) {
					$sql = 'ALTER TABLE '.self::quote($schema->table).' ADD '.self::quote($column->name).' ';
					$sql .= $column->type.(!is_null($column->length) ? '('.$column->length.') ' : ' ');
					$sql .= !$column->nullable ? ' NOT NULL ' : '';
					self::raw($sql)->run();
					if($column->index) {
						$indexes[] = $column;
					}
				}
				$schema->addIndexes($indexes);
			}
			return $schema;
		}

		public static function create($table,$function) {
			$schema = new Static;
			$schema->table = $table;
			$function($schema);
			$sql = "CREATE TABLE ".self::quote($schema->table)." (\n\r";
			$primaries = null;
			$indexes = array();
			$primary = null;
			foreach($schema->columns as $key=>$column) {
				$sql .= self::quote($column->name).' '.$column->type.(!is_null($column->length) ? '('.$column->length.')' : '').' ';
				$sql .= ($column->nullable ? '' : 'NOT NULL ');
				$sql .= ($column->primary && $column->increments ? 'AUTO_INCREMENT' : '');
				if($key !== count($schema->columns)-1) {
					$sql .= ','."\n\r";
				}
				if($column->primary) {
					if($column->increments) {
						$primary = $column;
					}
					$primaries[] = $column;
				}
				if($column->index) {
					$indexes[] = $column;
				}
			}

			if(!is_null($primary)) {
				$sql .= ', PRIMARY KEY ('.self::quote($primary->name).') ';
			}

			$sql .= ')';
			$sql .= ' ENGINE = '.$schema->engine;
			self::raw($sql)->run();
			if(count($primaries) > 0 && is_null($primary)) {
				$keys = array();
				foreach($primaries as $p) {
					$keys[] = $p->name;
				}
				$primaries = self::quote($keys);
				$sql = 'ALTER table '.self::quote($schema->table).' ADD PRIMARY KEY ('.implode(',',$primaries).')';
				self::raw($sql)->run();
			}

			$schema->addIndexes($indexes);
			
		}
	}