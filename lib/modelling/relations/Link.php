<?php
	namespace lib\modelling\relations;
	use \Sql;
	class Link {
		public $keys = array();
		public $handler;
		private $table;
		private $original = array();
		protected $changed = array();

		public function __set($prop,$val) {
			$backTrace = debug_backtrace();
			$last = $backTrace[1];
			if($last['class'] == "Sql") {
				if(property_exists($this, $prop)) {
					$this->$prop = $val;	
				} else {
					$this->original[$prop] = $val;
				}
				
				
			} else {
				if(array_key_exists($prop, $this->original)) {
					$this->changed[$prop] = $val;
				}
			}
		}

		public function __get($prop) {
			if(array_key_exists($prop,$this->changed)) {
				return $this->changed[$prop];
			} elseif(array_key_exists($prop,$this->original)) {
				return $this->original[$prop];
			}
		}

		public function delete() {
			$sql = new Sql;
			$sql->useHandler($this->handler);
			$cols = $sql->describe($this->table);
			$primary = null;
			foreach($cols as $col) {
				if($col['Key'] === 'PRI') {
					$primary = $col['Field'];
					break;
				}
			}
			$sql = Sql::table($this->table);
			$sql->useHandler($this->handler);
			foreach($this->keys as $field=>$id) {
				$sql->where(Sql::quote($field),"=",$id);
			}
			if(is_null($primary) || !array_key_exists($primary, $this->original)) {
				if(count($this->original) > 0) {
					foreach($this->original as $field=>$val) {
						if(is_null($val)) {
							$sql->whereNull($field);
						} else {
							$sql->where(Sql::quote($field),"=",$val);
						}
					}
				}
			} else {
				$sql->where(Sql::quote($primary),"=",$this->original[$primary]);
			}
			
			return $sql->delete();
		}

		public function save() {
			$toSave = array();
			$sql = new Sql;
			$sql->useHandler($this->handler);
			$cols = $sql->describe($this->table);
			foreach($this->changed as $key=>$val) {
				if(array_key_exists($key,$this->original) && $this->original[$key] !== $val) {
					$toSave[$key] = $val;
				}
			}
			if(count($toSave) === 0) {
				return false;
			}
			$primary = null;
			foreach($cols as $col) {
				$field = $col["Field"];
				$nullable = $col['Null'] === "YES";
				if($col['Key'] === 'PRI') {
					$primary = $col['Field'];
				}
				if(array_key_exists($field,$toSave) && !$nullable && $toSave[$field] === null) {
					//TODO: throw exception
					return false;
				}
			}
			$data = array();
			foreach($toSave as $field=>$val) {
				$data[Sql::quote($field)] = $val;
			}
			$sql = Sql::table($this->table)
				->useHandler($this->handler)
				->update($data);

			foreach($this->keys as $field=>$id) {
				$sql->where(Sql::quote($field),"=",$id);
			}
			if(count($this->original) > 0) {
				if(!is_null($primary)) {
					$sql->where(Sql::quote($primary),"=",$this->original[$primary]);
				} else {
					foreach($this->original as $field=>$val) {
						if(!is_null($val)) {
							$sql->where(Sql::quote($field),"=",$val);
						}
					}
				}
					
			}
			if($sql->save()) {
				foreach($toSave as $key=>$val) {
					$this->original[$key] = $val;
				}
				return true;
			}
			return false;
		}
	}