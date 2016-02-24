<?php
	namespace lib\modelling\relations;
	use \Sql;
	class MorphTo extends Sql {

		public $returnsMultiple = false;
		public $null = false;

		public function __construct($model,$key) {
			parent::__construct();
			$this->model = $model;
			$this->key = $key;
			$modelKey = $key."_type";
			$idKey = $key."_id";
			$relatedClass = $model->$modelKey;
			if(is_null($relatedClass)) {
				$this->null = true;
				return null;
			}
			$relatedModel = new $relatedClass;
			$this->table(\Sql::quote($model->getTable()).' __primary__')
				->join(
					Sql::quote($relatedModel->getTable()),
					"__primary__.".Sql::quote($idKey),
					Sql::quote($relatedModel->getTable()).".".Sql::quote($relatedModel->getPrimaryKey())
				)
				->where(
					"__primary__.".Sql::quote($modelKey),
					"=",
					$relatedClass
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
			if($relatedModel->hasColumn('deleted_at')) {
				$this->whereNull(Sql::quote($relatedModel->getTable()).'.'.'deleted_at');
			}
		}

		public function get($ids=array()) {
			if(count($ids) > 0) {
				unset($this->where[1]);
				unset($this->params[1]);
				$this->whereIn(
					"__primary__.".Sql::quote($this->model->getPrimaryKey()),
					$ids
				);				
			}		
			return parent::get();
		}
	}