<?php
	namespace lib\modelling\relations;
	use \Sql;
	class HasMany extends Sql {

		public $returnsMultiple = true;

		public function __construct($model,$relatedModel,$foreignKey) {
			parent::__construct();
			$this->model = $model;
			$this->relatedModel = $relatedModel;
			$this->foreignKey = $foreignKey;

			$this->table(Sql::quote($model->getTable()).' __primary__')
				->join(
					Sql::quote($relatedModel->getTable()),
					"__primary__.".Sql::quote($model->getPrimaryKey()),
					Sql::quote($relatedModel->getTable()).".".Sql::quote($foreignKey)
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
				unset($this->where[0]);
				unset($this->params[0]);
				$this->whereIn(
					"__primary__.".Sql::quote($this->model->getPrimaryKey()),
					$ids
				);				
			}		
			return parent::get();
		}
	}