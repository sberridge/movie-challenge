<?php
	class Sql {

		protected static $handlers = array();
		protected $handle;
		protected $usedHandle;
		private $query;
		public $result;
		public $params = array();
		private $select = array();
		private $raw = false;
		private static $colCache = array();
		private static $queryLog = array();
		private $alias;

		/**
		 * Initiate the SQL class by creating a connection to the database
		 */
		public function __construct() {
			if(count(self::$handlers) == 0) {
				$dbConf = Config::get('database');
				$databases = array('default'=>array());
				foreach($dbConf as $key=>$val) {
					if(!is_array($val)) {
						$databases['default'][$key] = $val;
					} else {
						foreach($val as $k=>$v) {
							$databases[$key][$k] = $v;
						}
					}
				}
				foreach($databases as $database=>$properties) {
					if(!isset($properties['type']) || strtolower($properties['type']) == 'mysql') {
						$connection = new PDO("mysql:host=".$properties['host'].';dbname='.$properties['database'].';',$properties['user'],$properties['password'],array(PDO::ATTR_EMULATE_PREPARES=>false));
						$type = 'mysql';
					} elseif(strtolower($properties['type']) == 'mssql') {
						$driver = '{SQL Server}';
						if(isset($properties['driver'])) {
							$driver = $properties['driver'];
						}
						$dsn = 'DRIVER='.$driver.';SERVER='.$properties['host'].';UID='.$properties['user'].';PWD='.$properties['password'].';';
						if(array_key_exists('port', $properties)) {
							$dsn .= 'PORT='.$properties['port'].';';
						}
						$type = 'mssql';
						$connection = new PDO('odbc:'.$dsn);
					}
					$handler = array(
						'type'=>$type,
						'connection'=>$connection,
						'driver'=>null
					);
					if(isset($driver)) {
						$handler['driver'] = $driver;
					}
					self::$handlers[$database] = (object)$handler;
				}
			}
			if(array_key_exists('default', self::$handlers)) {
				$this->handle = self::$handlers['default'];
				$this->usedHandle = 'default';
			}
			
		}

		/**
		 * create a raw query
		 * Sql::raw('select * from users where id = ?',array(1))
		 * @param string $query SQL Query string
		 * @param array $params array of query parameters
		 * @return object SQL object
		 */
		public static function raw($query,$params=array()) {
			$sql = new Sql;
			$sql->query = $query;
			$sql->params = $params;
			$sql->raw = true;
			return $sql;
		}

		public function useHandler($handler) {
			if(array_key_exists($handler,self::$handlers)) {
				$this->handle = self::$handlers[$handler];
				$this->usedHandle = $handler;
			}
			return $this;
		}

		/**
		 * run a prepared raw query
		 * @return array|null
		 */
		public function run() {
			if(!$this->raw) {
				throw new Exception('Query must be raw to run');
			}
			$q = $this->handle->connection->prepare($this->query);
			$this->execute($q,$this->params);
			$results = $q->fetchAll(PDO::FETCH_ASSOC);
			if(isset($this->class)) {
				return $this->createCollection($results);
			}
			return $results;
		}

		public static function beginTransaction($handler=null) {
			$foundHandler = null;
			if(is_null($handler) && array_key_exists('default', self::$handlers)) {
				$foundHandler = self::$handlers['default'];
			} elseif(array_key_exists($handler, self::$handlers)) {
				$foundHandler = self::$handlers[$handler];
			}
			if(!is_null($foundHandler)) {
				$foundHandler->connection->beginTransaction();
			}
		}

		public static function rollback($handler=null) {
			$foundHandler = null;
			if(is_null($handler) && array_key_exists('default', self::$handlers)) {
				$foundHandler = self::$handlers['default'];
			} elseif(array_key_exists($handler, self::$handlers)) {
				$foundHandler = self::$handlers[$handler];
			}
			if(!is_null($foundHandler)) {
				$foundHandler->connection->rollback();
			}
		}

		public static function commit($handler=null) {
			$foundHandler = null;
			if(is_null($handler) && array_key_exists('default', self::$handlers)) {
				$foundHandler = self::$handlers['default'];
			} elseif(array_key_exists($handler, self::$handlers)) {
				$foundHandler = self::$handlers[$handler];
			}
			if(!is_null($foundHandler)) {
				$foundHandler->connection->commit();
			}
		}

		/**
		 * run a describe query to get information about the columns in a table
		 * @param string $table table name
		 * @return array
		 */
		public function describe($table) {

			if(!isset(self::$colCache[$this->usedHandle][$table])) {
				if($this->handle->type == 'mssql') {
					$tableSplit = explode('.',$table);
					$sql = Sql::table('INFORMATION_SCHEMA.Columns columns')->useHandler($this->usedHandle)->select(array(
						'columns.COLUMN_NAME',
						'columns.IS_NULLABLE',
						'columns.COLUMN_DEFAULT',
						'columns.DATA_TYPE',
						'columns.CHARACTER_MAXIMUM_LENGTH',
						'tc.CONSTRAINT_TYPE'

					));
					$sql->leftJoin('INFORMATION_SCHEMA.CONSTRAINT_COLUMN_USAGE cu','columns.TABLE_SCHEMA = cu.TABLE_SCHEMA AND columns.TABLE_NAME = cu.TABLE_NAME AND columns.COLUMN_NAME','cu.COLUMN_NAME');
					$sql->leftJoin('INFORMATION_SCHEMA.TABLE_CONSTRAINTS tc','cu.TABLE_SCHEMA = tc.TABLE_SCHEMA AND cu.TABLE_NAME = tc.TABLE_NAME AND cu.CONSTRAINT_NAME','tc.CONSTRAINT_NAME');
					if(count($tableSplit) == 2) {
						$sql->where('columns.TABLE_SCHEMA','=',$tableSplit[0]);
						$sql->where('columns.TABLE_NAME','=',$tableSplit[1]);
					} else {
						$sql->where('columns.TABLE_NAME','=',$table);
					}
					$fields = $sql->get();
					foreach($fields as &$field) {
						$field = array(
							'Field'=>$field['COLUMN_NAME'],
							'Type'=>$field['DATA_TYPE'].'('.$field['CHARACTER_MAXIMUM_LENGTH'].')',
							'Null'=>$field['IS_NULLABLE'],
							'Key'=>$field['CONSTRAINT_TYPE'] ? substr($field['CONSTRAINT_TYPE'],0,3) : '',
							'Default'=>$field['COLUMN_DEFAULT'],
							'Extra'=>''
						);
					}
				} else {
					$query = $this->handle->connection->prepare("DESCRIBE ".self::quote($table));
					$this->execute($query);
					$fields = $query->fetchAll(PDO::FETCH_ASSOC);
				}
				
				self::$colCache[$this->usedHandle][$table] = $fields;
			}
			return self::$colCache[$this->usedHandle][$table];
		}

		/**
		 * static magic method for initiating a query using the table function
		 * @param string $func 'table'
		 * @param array $args array of arguments, in this case needs to have a table name
		 * @return object sql object
		 */
		public static function __callStatic($func,$args) {
			if($func == "table") {
				$sql = new Sql;
				$sql->table = $args[0];
				return $sql;
			}
		}

		/**
		 * magic method for initiating a query using the table function
		 * @param string $func 'table'
		 * @param array $args array of arguments, in this case needs to have a table name
		 * @return object sql object
		 */
		public function __call($func,$args) {
			if($func == "table") {
				$this->table = $args[0];
				return $this;
			}
		}


		/**
		 * Function for defining which fields are to be selected in the query
		 * @param array $cols array of columns to select
		 * @return object sql object
		 */
		public function select($cols) {
			if(!is_array($cols)) {
				$cols = array($cols);
			}
			$this->select = array_merge($this->select,$cols);
			return $this;
		}

		/**
		 * Function to paginate the results of a select query
		 * @param int $perPage number of results to show per page
		 * @param string $pageName name of the variable used when keeping track of the pages
		 * @return object sql object
		 */
		public function paginate($perPage,$pageName="p") {
			$sql = clone($this);
			$sql->setAlias('pageCount');
			$res = new Sql;
			$res->useHandler($this->usedHandle);
			$res = $res->table($sql)
				->select("count(*) num")
				->get();
			$page = Input::has($pageName) && is_numeric(Input::get($pageName)) ? Input::get($pageName) : 1;
			$total = $res[0]['num'];
			$totalPages = ceil($total/$perPage);
			$offset = ($page - 1) * $perPage;
			$this->offset($offset);
			$this->limit($perPage);
			Paginator::register($pageName,$totalPages,$page);
			return $this;
		}

		/**
		 * Perform a count query and return the number of results
		 * @return int number of results
		 */
		public function count($cols=array()) {
			$this->select = $cols;
			$this->toClass(null);
			$res = $this->select("count(*) num")->get();
			$total = $res[0]['num'];
			return $total;
		}

		/**
		 * Return results as a collection of classes
		 * @param string $class class name to return results as
		 * @return object sql object
		 */
		public function toClass($class) {
			$this->class = $class;
			return $this;
		}
		
		/**
		 * Get row count from last performed query
		 * @return int row count
		 */
		public function rowCount() {
			return $this->query->rowCount();
		}

		/**
		 * get last auto incremented ID
		 * @return int last ID
		 */
		public function lastId() {
			return $this->handle->connection->lastInsertId();
		}

		private function addJoin($table,$localKey,$foreignKey,$sqlForeign,$type) {
			if(is_object($table) && $table instanceof Sql) {
				$params = $table->params;
				if($table->raw) {
					$table = $table->query;
					$this->join[] = array($table,$localKey,$foreignKey,$type);
				} else {
					$table = '('.$table->generateSelect().') '.$localKey;
					$this->join[] = array($table,$foreignKey,$sqlForeign,$type);
				}
				if(strtolower($this->handle->type) !== 'mssql') {
					$this->params = array_merge($this->params,$params);
				}
			} else {
				$this->join[] = array($table,$localKey,$foreignKey,$type);
			}
		}

		/**
		 * creates an inner join
		 * can take other raw or created queries in the place of a table
		 * @param string|object table name or Sql object
		 * @param string $localKey can be either the key on the first table or the alias for a non raw sub query
		 * @param string $foreignKey can be either the key for the first table if running a non raw sub query of the key on the second table if regular join
		 * @param string $sqlForeign key on the second table when running non raw sub query
		 * @return object sql object
		 */
		public function join($table,$localKey,$foreignKey,$sqlForeign=null) {
			$this->addJoin($table,$localKey,$foreignKey,$sqlForeign,'INNER');
			return $this;
		}

		/**
		 * creates a left join
		 * can take other raw or created queries in the place of a table
		 * @param string|object table name or Sql object
		 * @param string $localKey can be either the key on the first table or the alias for a non raw sub query
		 * @param string $foreignKey can be either the key for the first table if running a non raw sub query of the key on the second table if regular join
		 * @param string $sqlForeign key on the second table when running non raw sub query
		 * @return object sql object
		 */
		public function leftJoin($table,$localKey,$foreignKey,$sqlForeign=null) {
			$this->addJoin($table,$localKey,$foreignKey,$sqlForeign,'LEFT');
			
			return $this;
		}

		/**
		 * internal function for executing arrays, keeps track of queries that have been executed for log
		 * @param object $query PDO object
		 * @param $params query parameters
		 */
		private function execute($query,$params=array()) {
			if(!$query) {
				$error = $this->handle->connection->errorInfo();
				throw new lib\exceptions\SqlException($error[2]);
			}
			self::$queryLog[] = array(
				"queryString"=>$query->queryString,
				"params"=>$params
			);
			$query->execute($params);
		}

		/**
		 * Get array of executed queries
		 * @return array query list
		 */
		public static function getQueryLog() {
			return self::$queryLog;
		}

		/**
		 * quotes strings with ticks to prepare for use in query
		 * @param string $string
		 * @return string quoted string
		 */
		public static function quote($string) {
			if(is_array($string)) {
				foreach($string as &$str) {
					$str = "`".$str."`";
				}
				return $string;
			}
			return "`".$string."`";
		}

		/**
		 * generates select query from given parameters
		 * @return string query string
		 */
		private function generateSelect() {
			$query = "SELECT ";
	        
        	if(is_array($this->select) && count($this->select) > 0) {
            	$query .= implode(",", $this->select);
        	} else {
        		$query .= '*';
        	}
	        if(is_object($this->table) && $this->table instanceof \Sql) {
	        	$query .= " FROM (".$this->table->generateSelect().") ".$this->table->alias." ";
	        	$this->params = array_merge($this->params,$this->table->params);
	        } else {
	        	$query .= " FROM ".$this->table;
	        }
	        
	        if(isset($this->join)) {
	        	foreach($this->join as $join) {
	        		$query .= " ".$join[3]." JOIN ".$join[0]." ON ".$join[1]." = ".$join[2]." ";
	        	}
	        }
	        $query = $this->applyWhere($query);

	        if(strtolower($this->handle->type) == 'mssql') {

	        	$limitStart = 1;
	        	$limit = false;
	        	if(isset($this->limit)) {
	        		$limit = true;
	        		$limitEnd = $this->limit;       			      		
		        }

		        if(isset($this->offset)) {
		        	$limitStart += $this->offset;
		        	$limitEnd += $this->offset;
		        }

		        if($limit) {
		        	if(isset($this->order)) {
		        		$orderString = 'ORDER BY ';
		        		foreach($this->order as $key=>$order) {
			                $orderString .= $order[0]." ".$order[1];
			                if($key !== count($this->order)-1) {
			                    $orderString .= ", ";
			                }
			            }
		        	} else {
		        		$sql = new Sql;
		        		$sql->useHandler($this->usedHandle);
		        		$tableExp = explode(' ',$this->table);
		        		$cols = $sql->describe($tableExp[0]);
			        	$column = $cols[0]['Field'];
			        	$orderString = 'ORDER BY '.$column;
		        	}
		        	

		        	$query = 'SELECT * FROM ('.str_replace('SELECT ','SELECT ROW_NUMBER() OVER ('.$orderString.') AS __RowNum__, ',$query).') as result WHERE __RowNum__ >= '.$limitStart.' AND __RowNum__ <= '.$limitEnd.' ';
		        	
		        }

		        if(isset($this->group)) {
		        	$query .= " GROUP BY ".$this->group;
		        }

		        if(isset($this->order)) {
		            $query .= " ORDER BY ";
		            foreach($this->order as $key=>$order) {
		                $query .= $order[0]." ".$order[1];
		                if($key !== count($this->order)-1) {
		                    $query .= ", ";
		                }
		            }
		        } elseif($limit) {
		        	$query .= ' ORDER BY __RowNum__';
		        }
	        } else {

	        	if(isset($this->group)) {
		        	$query .= " GROUP BY ".$this->group;
		        }

		        if(isset($this->order)) {
		            $query .= " ORDER BY ";
		            foreach($this->order as $key=>$order) {
		                $query .= $order[0]." ".$order[1];
		                if($key !== count($this->order)-1) {
		                    $query .= ", ";
		                }
		            }
		        }

		        if(isset($this->limit)) {
		        	$query .= " LIMIT ".$this->limit;
		        }

		        if(isset($this->offset)) {
		        	$query .= " OFFSET ".$this->offset;
		        }

	        }

	        if(strtolower($this->handle->type) == 'mssql' && count($this->params) > 0) {
	        	$params = $this->params;
	        	$query = preg_replace_callback('/\?/', function($match) use(&$params) {
	        		$param = addslashes(array_shift($params));
	        		return "'".$param."'";
	        	}, $query);
	        	$this->params = $params;
	        }
	        
	        return $query;
		}

		private function createCollection($results) {
			$model = new $this->class;
			$cols = array();
			if(is_subclass_of($model, 'lib\modelling\BaseModel')) {
				$describe = $this->describe($model->getTable());
				$modelling = true;
				foreach($describe as $col) {
					$cols[] = $col['Field'];
				}
				$collection = new lib\modelling\Collection;
			} else {
				$modelling = false;
				$collection = array();
			}

			foreach($results as $result) {
				$model = new $this->class;
				if($modelling) {
					$model->new = false;
					if(get_class($this) === "lib\\modelling\\relations\\BelongsToMany") {
						$model->link = new lib\modelling\relations\Link;
						$model->link->handler = $this->usedHandle;
						$model->link->keys = array(
							$this->modelKey=>$result["__table_".$this->model->getTable()."__key"],
							$this->relatedKey=>$result[$this->relatedModel->getPrimaryKey()]
						);
						$model->link->table = $this->linkTable;
					}
					foreach($result as $key=>$val) {
						if(in_array($key, $cols)) {
							$model->$key = $val;
						} else {
							if(substr($key,0,8) == "__link__") {
								
								$model->link->{substr($key,8,strlen($key))} = $val;

							}

						}
						
					}
				} else {
					foreach($result as $key=>$val) {
						$model->$key = $val;
					}
				}
				
				$result = $model;
				$collection[] = $result;
				
			}
			$this->result = $results;
			return $collection;
		}

		/**
		 * perform a select query from given parameters
		 * @return object|array collection of models if used toClass or array of results
		 */
		public function get() {
			$query = $this->generateSelect();
	        $query = $this->handle->connection->prepare($query);
	        $this->execute($query,$this->params);
	        $this->query = $query;
	        $results = $query->fetchAll(PDO::FETCH_ASSOC);
			$res = array();
			if(isset($this->class)) {
				return $this->createCollection($results);
				
				
			}
			foreach($results as $result) {
				

				$res[] = $result;
				
			}
			$this->result = $results;
			return $res;
			

		}

		/**
		 * Perform insert or update query
		 * @return bool
		 */
		public function save() {
			if(isset($this->insert)) {
				$cols = array_keys($this->insert);
				$query = "INSERT INTO ".$this->table." (".implode(',',$cols).") VALUES (".self::generatePlaceholders($this->insert).")";
				$query = $this->handle->connection->prepare($query);
				$this->execute($query,$this->params);
				if($query->rowCount() > 0) {
					return true;
				} else {
					return false;
				}
			} elseif(isset($this->update)) {
				$query = "UPDATE ".$this->table." SET";
				foreach($this->update as $col=>$val) {
					$query .= " ".$col." = ?,";
				}
				$query = substr($query,0,-1);
				$query = $this->applyWhere($query);
				$query = $this->handle->connection->prepare($query);
				$this->execute($query,$this->params);
				if($query->rowCount() > 0) {
					return true;
				} else {
					return false;
				}
			}
		}

		/**
		 * Perform a delete query
		 * @return bool
		 */
		public function delete() {
			$query = "DELETE FROM ".$this->table;
			$query = $this->applyWhere($query);
			$query = $this->handle->connection->prepare($query);
			$this->execute($query,$this->params);
			if($query->rowCount() > 0) {
				return true;
			} else {
				return false;
			}
		}

		public function setAlias($alias) {
			$this->alias = $alias;
			return $this;
		}

		/**
		 * Build up a 'where' string for query
		 * @param string $query start of query
		 * @return string query string with where appended
		 */
		private function applyWhere($query) {
	        if(isset($this->where) && count($this->where) > 0) {
	            $query .= " WHERE ";
	            $keys = array_keys($this->where);
	            $firstKey = $keys[0];
	            foreach($this->where as $key=>$where) {
	            	if($key > $firstKey) {
	            		$query .= " ".$where[3]." ";
	            	}
	                $query .= $where[0]." ".$where[1]." ".$where[2];
	            }
	        }
	        return $query;
		}

		/**
		 * Add where condition to query
		 * @param string $col column name
		 * @param string $comparision comparator i.e. =
		 * @param mixed $value value to compare
		 * @return object sql object
		 */
		public function where($col,$comparison,$value) {
			$this->where[] = array($col,$comparison,"?","AND");
			$this->params[] = $value;
			return $this;
		}

		/**
		 * Add where null condition to query
		 * @param string $col column name
		 * @return object sql object
		 */
		public function whereNull($col) {
			$this->where[] = array($col,'IS','NULL','AND');
			return $this;
		}

		public function whereNotNull($col) {
			$this->where[] = array($col,'IS NOT','NULL','AND');
			return $this;
		}

		/**
		 * Add 'or where' condition to query
		 * @param string $col column name
		 * @param string $comparison comparator i.e. =
		 * @param mixed $value value to compare
		 * @return object sql object
		 */
		public function orWhere($col,$comparison,$value) {
			$this->where[] = array($col,$comparison,"?","OR");
			$this->params[] = $value;
			return $this;
		}

		public function whereMatch($col,$value,$boolean=false) {
			$against = 'AGAINST(?';
			if($boolean) {
				$against .= ' IN BOOLEAN MODE';
			}
			$against .= ')';
			$this->where[] = array('MATCH('.$col.')',$against,'','AND');
			$this->params[] = $value;
			return $this;
		}

		public function whereRaw($sql,$params=array()) {
			$this->where[] = array($sql,'','','AND');
			$this->params = array_merge($this->params,$params);
			return $this;
		}

		/**
		 * generates placeholders for 'where in' conditions
		 * @param array $array array of values
		 * @return string placeholder string 
		 */
		public static function generatePlaceholders($array) {
			return substr(str_pad('',count($array)*2,'?,'),0,(count($array)*2)-1);
		}

		/**
		 * Add a 'where in' condition to query
		 * @param string $col column name
		 * @param array|object $params either an array of values to compare or a sql object for a sub query
		 * @return object sql object
		 */
		private function addWhereIn($col,$params,$comp='IN',$logic='AND') {
			if(is_object($params) && $params instanceof Sql) {
				if($params->raw) {
					$this->where[] = array($col,$comp,$params->query,$logic);
				} else {
					$this->where[] = array($col,$comp,"(".$params->generateSelect().")",$logic);
				}				
				$this->params = array_merge($this->params,$params->params);
			} elseif(is_array($params)) {
				if(count($params) == 0) {
					$sqlParams = 'SELECT '.$col.' FROM '.$this->table.' WHERE FALSE';
				} else {
					$sqlParams = self::generatePlaceholders($params);
				}
				$this->where[] = array($col,$comp,"(".$sqlParams.")",$logic);
				$this->params = array_merge($this->params,$params);
			}
		}

		
		public function whereIn($col,$params) {
			$this->addWhereIn($col,$params);
			
			return $this;
		}

		public function orWhereIn($col,$params) {
			$this->addWhereIn($col,$params,'IN','OR');
			return $this;
		}

		public function whereNotIn($col,$params) {
			$this->addWhereIn($col,$params,'NOT IN','AND');
			return $this;
		}

		public function orWhereNotIn($col,$params) {
			$this->addWhereIn($col,$params,'NOT IN','OR');
			return $this;
		}

		public function withDeleted() {
			foreach($this->where as $key=>$where) {
				if(strpos($where[0], 'deleted_at') !== false) {
					unset($this->where[$key]);
				}
			}
			$this->where = array_values($this->where);
			return $this;
		}

		/**
		 * Set values to insert into the database
		 * @param array $cols array of values to insert using column name as key
		 * @return object sql object
		 */
		public function insert($cols) {
			$this->insert = $cols;
			foreach($cols as $val) {
				$this->params[] = $val;
			}
			return $this;
		}

		/**
		 * Set values to update
		 * @param array $cols array of values to update using column name as key
		 * @return object sql object
		 */
		public function update($cols) {
			$this->update = $cols;
			foreach($cols as $val) {
				$this->params[] = $val;
			}
			return $this;
		}

		/**
		 * Add order option to query
		 * @param string $col column to order by
		 * @param string $dir direction to sort
		 * @return object sql object
		 */
		public function order($col,$dir="ASC") {
	        $this->order[] = array(
	            $col,
	            $dir
	        );
	        return $this;
	    }

	    /**
	     * Add grouping option to query
	     * @param string $col column to group on
	     * @return object sql object
	     */
	    public function group($col) {
	    	$this->group = $col;
	    	return $this;
	    }

	    /**
	     * Add limit option to query
	     * @param int $limit number of results to return
	     * @return object sql object
	     */
		public function limit($limit) {
			$this->limit = $limit;
			return $this;
		}

		/**
		 * Add offset option to query
		 * @param int $offset number of results to skip
		 * @return object sql object
		 */
		public function offset($offset) {
			$this->offset = $offset;
			return $this;
		}
	}