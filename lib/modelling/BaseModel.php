<?php
	namespace lib\modelling;
	use \Sql;
	use \Str;
	abstract class BaseModel {
		
		protected $changed = array();
		protected $relations = array();
		public $link;
		protected $original = array();
		private $sql;
		protected $visible = array();
		public $new = true;
		private $events = array();

		public function __construct() {
			$table = $this->table;
			$sql = new Sql;
			$sql->useHandler($this->getDatabase());
			$columns = $sql->describe($table);
			foreach($columns as $col) {
				$this->original[$col['Field']] = null;
			}
		}

		public function registerEvent($type,$callback) {
			$this->events[$type] = $callback;
		}
		
		public static function find($id) {
			$class = get_called_class();
			$model = new $class;
			$softDeletes = $model->hasColumn('deleted_at');
			if(is_array($id)) {
				$primaryKey = Sql::quote($model->getPrimaryKey());
				$find = Sql::table(Sql::quote($model->table))->select('*')->toClass($class)->whereIn($primaryKey,$id);
				if($softDeletes) {
					$find->whereNull('deleted_at');
				}
				$find->useHandler($model->getDatabase());
				$find = $find->get();
				$model = $find;
			} else {
				$primaryKey = Sql::quote($model->getPrimaryKey());
				$find = Sql::table(Sql::quote($model->table))->select('*')->toClass($class)->limit(1)->where($primaryKey,"=",$id);
				if($softDeletes) {
					$find->whereNull('deleted_at');
				}
				$find->useHandler($model->getDatabase());
				$find = $find->get();
				$model = $find[0];
			}
			
			return $model;
		}

		public static function all() {
			$class = get_called_class();
			$model = new $class;
			$sql = Sql::table(Sql::quote($model->table))->toClass($class)->select(Sql::quote($model->table).'.*');
			if($model->hasColumn('deleted_at')) {
				$sql = $sql->whereNull('deleted_at');
			}
			$sql->useHandler($model->getDatabase());
			return $sql;
		}

		public static function where($prop,$comparison,$value) {
			$class = get_called_class();
			$model = new $class;
			$sql = Sql::table(Sql::quote($model->table))->toClass($class)->select(Sql::quote($model->table).".*")->where($prop,$comparison,$value);
			
			$sql->useHandler($model->getDatabase());
			
			$model->sql = $sql;
			return $sql;
		}

		public function hasColumn($col) {
			$sql = new Sql;
			$sql->useHandler($this->getDatabase());
			$cols = $sql->describe($this->table);
			foreach($cols as $c) {
				if($c['Field'] == $col) {
					return true;
				}
			}
			return false;
		}

		public function save() {
			$s = new Sql;
			$sql = Sql::table(Sql::quote($this->table));
			$s->useHandler($this->getDatabase());
			$sql->useHandler($this->getDatabase());
			$cols = $s->describe($this->table);
			$primaryKey = $this->getPrimaryKey();
			$hasUpdatedAt = false;
			$hasCreatedAt = false;
			foreach($cols as $col) {
				$field = $col['Field'];
				if($field === "updated_at") {
					$hasUpdatedAt = true;
					continue;
				} elseif($field === "created_at") {
					$hasCreatedAt = true;
					continue;
				}
				$nullable = $col['Null'] === "YES";
				if(!$nullable && $this->$field === null && $field !== $primaryKey) {
					//TODO: Throw exception
					return false;
				}
			}
			$toSave = array();
			foreach($this->original as $key=>$val) {
				if($this->$key !== $val) {
					$toSave[Sql::quote($key)] = $this->$key;
				}
			}
			if(count($toSave) == 0) return false;
			if($hasUpdatedAt) {
				$toSave[Sql::quote("updated_at")] = date("Y-m-d G:i:s");
			}
			if($this->new) {

				if($hasCreatedAt) {
					$toSave[Sql::quote("created_at")] = date("Y-m-d G:i:s");
				}
				$success = $sql->insert($toSave)->save();
				if($success && is_null($this->$primaryKey)) {
					$this->$primaryKey = $sql->lastId();
				}
			} else {
				$success = $sql->update($toSave)->where(Sql::quote($primaryKey),"=",$this->$primaryKey)->save();
			}
			if($success) {
				if($this->new) {
					$this->new = false;
					if(array_key_exists('create', $this->events)) {
						$this->events['create']($this);
					}
				} else {
					if(array_key_exists('save', $this->events)) {
						$this->events['save']($this);
					}
				}
				foreach($toSave as $key=>$val) {
					$this->original[substr($key, 1, strlen($key)-2)] = $val;
				}
				return true;
			}
			return false;
		}

		public function delete() {
			$sql = new Sql;
			$sql->useHandler($this->getDatabase());
			$cols = $sql->describe($this->table);
			$hasDeletedAt = false;
			foreach($cols as $col) {
				if($col['Field'] == 'deleted_at') {
					$hasDeletedAt = true;
					break;
				}
			}
			$query = Sql::table(Sql::quote($this->table));
			
			$query->useHandler($this->getDatabase());
			
			if($hasDeletedAt) {
				$update = array('deleted_at'=>date('Y-m-d G:i:s'));
				$query->update($update);
			}

			$query->where(Sql::quote($this->getPrimaryKey()),'=',$this->{$this->getPrimaryKey});
			$success = false;
			if($hasDeletedAt) {
				$success = $query->save();
			} else {
				$success = $query->delete();
			}
			if($success && array_key_exists('delete', $this->events)) {
				$this->events['delete']($this);
			}
			return $success;

		}

		public function restore() {
			if($this->hasColumn('deleted_at') && !is_null($this->deleted_at)) {
				$this->deleted_at = null;
				if($this->save()) {
					if(array_key_exists('restore', $this->events)) {
						$this->events['restore']($this);
					}
					return true;
				}
			}
			return false;
		}

		private function load($data) {
			foreach($data as $key=>$val) {
				$this->original[$key] = $val;
			}
		}

		

		protected function hasMany($class,$foreignKey=null) {

			$class = strpos($class,'\\') === false ? Str::studly_case($class) : $class;
			$model = new $class;
			$foreignKey = $foreignKey ? $foreignKey : $this->table."_id";

			$hasMany = new relations\HasMany($this,$model,$foreignKey);
			$hasMany->useHandler($this->getDatabase());
			return $hasMany;
		}

		protected function hasOne($class,$foreignKey=null) {
			$class = strpos($class,'\\') === false ? Str::studly_case($class) : $class;
			$model = new $class;
			$foreignKey = $foreignKey ? $foreignKey : $this->table."_id";

			$hasMany = new relations\HasOne($this,$model,$foreignKey);
			$hasMany->useHandler($this->getDatabase());
			return $hasMany;
		}

		protected function belongsTo($class,$foreignKey=null) {
			$class = strpos($class,'\\') === false ? Str::studly_case($class) : $class;
			$model = new $class;
			$foreignKey = $foreignKey ? $foreignKey : $model->table."_id";
			$belongsTo = new relations\BelongsTo($this,$model,$foreignKey);
			$belongsTo->useHandler($this->getDatabase());
			return $belongsTo;
		}

		protected function belongsToMany($class,$link=null,$thisKey=null,$classKey=null) {
			$class = strpos($class,'\\') === false ? Str::studly_case($class) : $class;
			$model = new $class;
			$thisTable = $this->table;
			$classTable = $model->table;
			$link = $link ? $link : ($classTable > $thisTable ? $thisTable."_".$classTable : $classTable."_".$thisTable);
			$thisKey = $thisKey ? $thisKey : $this->table."_id";
			$classKey = $classKey ? $classKey : $model->table."_id";
			$classPrimaryKey = $model->getPrimaryKey();
			$primaryKey = $this->getPrimaryKey();
			$belongsToMany = new relations\BelongsToMany($this,$model,$link,$thisKey,$classKey);
			$belongsToMany->useHandler($this->getDatabase());
			return $belongsToMany;
		}

		protected function morphMany($class,$key=null) {
			$key = $key ? $key : "object";
			$class = strpos($class,'\\') === false ? Str::studly_case($class) : $class;
			$model = new $class;
			$morphMany = new relations\MorphMany($this,$model,$key);
			$morphMany->useHandler($this->getDatabase());
			return $morphMany;
		}

		protected function morphOne($class,$key=null) {
			$key = $key ? $key : "object";
			$class = strpos($class,'\\') === false ? Str::studly_case($class) : $class;
			$model = new $class;
			$morphOne = new relations\MorphOne($this,$model,$key);
			$morphOne->useHandler($this->getDatabase());
			return $morphOne;
		}

		protected function morphTo($key=null) {
			$key = $key ? $key : "object";
			$morphTo = new relations\MorphTo($this,$key);
			$morphTo->useHandler($this->getDatabase());
			return $morphTo;
		}

		public function getPrimaryKey() {
			return (isset($this->primary_key) ? $this->primary_key : "id");
		}

		public function getTable() {
			return $this->table;
		}

		public function getDatabase() {
			return (isset($this->database) ? $this->database : 'default');
		}

		public function hasRelation($key) {
			return isset($this->relations[$key]);
		}

		public function setVisible($keys=array()) {
			$this->visible = $keys;
		}

		public function toArray() {
			$props = array_merge($this->original,$this->changed);
			if(count($this->visible) > 0) {
				$tempArr = array();
				foreach($this->visible as $key) {
					if(isset($props[$key])) {
						$tempArr[$key] = $props[$key];
					}
				}
				$props = $tempArr;
			}
			return $props;
		}

		public function eagerLoad($loadArr,$data=false) {
			if($data !== false) {
				$this->relations[$loadArr] = $data;
			} else {
				$loaded = array();
				$nextLoad = array();
				foreach($loadArr as $key=>$str) {
					$recurLoad = false;
					$query = null;
					if(is_object($str)) {
						$query = $str;
						$str = $key;
					}
					$exp = explode(".",$str);
					$relationProp = $exp[0];
					if(!method_exists($this, $relationProp)) continue;
					if(isset($exp[1])) {
						unset($exp[0]);
						$recurLoad = true;
						$nextLoadKey = implode('.',$exp);
						$nextLoad[$nextLoadKey] = array(
							"collection"=>$relationProp,
							"load"=>$nextLoadKey
						);
						if(!is_null($query)) {
							$nextLoad[$nextLoadKey]["query"] = $query;
						}
					}
					if(isset($loaded[$relationProp])) continue;

					$relation = $this->$relationProp();
					if($relation->returnsMultiple) {
						$loaded[$relationProp] = new Collection;
					} else {
						$loaded[$relationProp] = null;
					}
					if(!isset($this->relations[$relationProp])) {
						
						if(!is_null($query) && $relationProp === end($exp)) {
							$query($relation);
						}
						$relations = $relation->get();
					} else {
						$relations = $this->relations[$relationProp];
					}
					
					foreach($relations as $key=>$m) {
						if($relation->returnsMultiple) {

							$loaded[$relationProp][] = $m;
						} else {
							$loaded[$relationProp] = $m;
						}
					}
				}
				if(count($nextLoad) > 0) {
					foreach($nextLoad as $str=>$load) {
						if(!$loaded[$load['collection']]) continue;
						if(isset($load['query'])) {
							$nextLoadArr = array($load['load'] => $load['query']);
						} else {
							$nextLoadArr = array($load['load']);
						}
						$loaded[$load['collection']]->eagerLoad($nextLoadArr);
					}
				}
				foreach($loaded as $prop=>$related) {
					$this->relations[$prop] = $related;
				}
			}

			return $this;
		}

		public function __get($prop) {
			if(array_key_exists($prop, $this->changed)) {
				return $this->changed[$prop];
			}
			if(array_key_exists($prop, $this->original)) {
				return $this->original[$prop];
			}
			if(array_key_exists($prop,$this->relations)) {
				return $this->relations[$prop];
			}
			if(method_exists($this, $prop)) {

				$primaryKey = $this->getPrimaryKey();
				$sql = $this->$prop();
				if(!is_object($sql)) return $sql;

				
				$sql->useHandler($this->getDatabase());
				

				$results = $sql->get();
				if($sql->returnsMultiple) {
					$this->relations[$prop] = $results;
				} else {
					if(count($results) == 0) {
						$this->relations[$prop] = null;
					} else {
						$this->relations[$prop] = $results[0];
					}
				}
				return $this->relations[$prop];
			}
			return null;
		}

		public function __set($prop,$val) {
			$backTrace = debug_backtrace(11,2);
			$last = $backTrace[1];
			if($last['class'] == "Sql") {
				if(array_key_exists($prop,$this->original)) {
					$this->original[$prop] = $val;	
				}
				
			} elseif(array_key_exists($prop, $this->original)) {
				$this->changed[$prop] = $val;
			}
		}
	}
?>