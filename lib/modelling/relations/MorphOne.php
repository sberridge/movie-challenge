<?php
	namespace lib\modelling\relations;
	use \Sql;
	class MorphOne extends Sql {
		public $returnsMultiple = false;

		public function __construct($model,$relatedModel,$key) {
			parent::__construct();
			$this->model = $model;
			$this->relatedModel = $relatedModel;
			$this->key = $key;

			$this->table(Sql::quote($model->getTable()).' __primary__')
				->join(
					Sql::quote($relatedModel->getTable()),
					"__primary__.".Sql::quote($model->getPrimaryKey()),
					Sql::quote($relatedModel->getTable()).".".Sql::quote($key."_id")
				)
				->where(
					Sql::quote($relatedModel->getTable()).".".Sql::quote($key."_type"),
					"=",
					get_class($model)
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