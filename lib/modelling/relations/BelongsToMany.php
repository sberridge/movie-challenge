<?php
	namespace lib\modelling\relations;
	use \Sql;
	class BelongsToMany extends Sql {

		public $returnsMultiple = true;

		public function __construct($model,$relatedModel,$linkTable,$modelKey,$relatedKey) {
			parent::__construct();
			$this->model = $model;
			$this->relatedModel = $relatedModel;
			$this->modelKey = $modelKey;
			$this->linkTable = $linkTable;
			$this->relatedKey = $relatedKey;
			$this->table(Sql::quote($model->getTable()).' __primary__')
				->join(
					Sql::quote($linkTable),
					"__primary__.".Sql::quote($model->getPrimaryKey()),
					Sql::quote($linkTable).".".Sql::quote($modelKey)
				)
				->join(
					Sql::quote($relatedModel->getTable()),
					Sql::quote($linkTable).".".Sql::quote($relatedKey),
					Sql::quote($relatedModel->getTable()).".".Sql::quote($relatedModel->getPrimaryKey())
				)
				->toClass(get_class($relatedModel))
				->select(array(
					Sql::quote($relatedModel->getTable()).".*",
					"__primary__.".Sql::quote($model->getPrimaryKey())." __table_".$model->getTable()."__key"
				));

			$this->whereIn(
				"__primary__.".Sql::quote($this->model->getPrimaryKey()),
				array($this->model->{$this->model->getPrimaryKey()})
			);

			if($this->relatedModel->hasColumn('deleted_at')) {
				$this->whereNull(Sql::quote($relatedModel->getTable()).'.'.'deleted_at');
			}
		}

		public function withLink($fields) {
			foreach($fields as &$field) {
				$fieldExp = explode(".",$field);
				if(count($fieldExp) == 2) {
					$fieldEnd = str_replace('`', '', $fieldExp[1]);
					$field = $field." __link__".$fieldEnd;
				} else {
					$field = Sql::quote($field)." __link__".$field;
				}
			}
			$this->select($fields);
			return $this;
		}

		public function get($ids=array()) {

			if(count($ids) > 0) {
				unset($this->where[0]);
				unset($this->params[0]);
				$this->whereIn(
					"__primary__.".Sql::quote($this->model->getPrimaryKey()),
					$ids
				);				
			}			
			return parent::get();
		}

		public function unlink() {
			$sql = new Sql;
			$sql->useHandler($this->model->getDatabase());
			$sql->table($this->linkTable)
				->where(Sql::quote($this->modelKey),"=",$this->model->{$this->model->getPrimaryKey()});
			return $sql->delete();
		}

		public function link($related,$attrs=array()) {
			$sql = new Sql;			
			$sql->useHandler($this->model->getDatabase());
			$cols = $sql->describe($this->linkTable);
			$sql = new Sql;
			$sql->useHandler($this->model->getDatabase());
			$insert = array(
				$this->modelKey => $this->model->{$this->model->getPrimaryKey()},
				$this->relatedKey => $related->{$related->getPrimaryKey()}
			);
			foreach($attrs as $field=>$val) {
				$insert[$field] = $val;
			}
			foreach($cols as $col) {
				$increments = strpos($col['Extra'], "auto_increment");
				$field = $col['Field'];
				$nullable = $col['Null'] === "YES";
				if(!$nullable && $increments === false && (!isset($insert[$field]) || is_null($insert[$field]))) {
					//TODO: THROW EXCEPTION
					return false;
				}
			}
			$data = array();
			foreach($insert as $field=>$val) {
				$data[Sql::quote($field)] = $val;
			}
			$sql->table($this->linkTable)
				->insert($data);

			return $sql->save();
		}
	}