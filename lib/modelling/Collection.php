<?php
	namespace lib\modelling;
	use \ArrayAccess;
	use \Iterator;
	use \Countable;
	class Collection implements ArrayAccess, Iterator, Countable {
		private $models = array();
		private $index = 0;

		public function count() {
			return count($this->models);
		}

		public function offsetSet($index,$value) {
			if(is_null($index)) {
				$this->models[] = $value;
			} else {
				$this->models[$index] = $value;
			}
		}

		public function offsetExists($index) {
			return array_key_exists($index, $this->models);
		}

		public function offsetGet($index) {
			if(isset($this->models[$index])) {
				return $this->models[$index];
			}
			return null;
		}

		public function offsetUnset($index) {
			if(!isset($this->models[$index])) return;
			unset($this->models[$index]);
		}

		public function rewind(){
			$this->index = 0;
		}
		public function current(){
			$k = array_keys($this->models);
			$var = $this->models[$k[$this->index]];
			return $var;
		}
		public function key(){
			$k = array_keys($this->models);
			$var = $k[$this->index];
			return $var;
		}
		public function next(){
			$k = array_keys($this->models);
			if (isset($k[++$this->index])) {
				$var = $this->models[$k[$this->index]];
				return $var;
			} else {
				return false;
			}
		}
		public function valid(){
			$k = array_keys($this->models);
			$var = isset($k[$this->index]);
			return $var;
		}

		public function find($id) {
			if(count($this->models) === 0) return null;
			$primKey = $this->models[0]->getPrimaryKey();
			foreach($this->models as $model) {
				if($model->$primKey == $id) {
					return $model;
				}
			}
		}

		public function has($id) {
			if(count($this->models) === 0) return null;
			$primKey = $this->models[0]->getPrimaryKey();
			foreach($this->models as $model) {
				if($model->$primKey == $id) {
					return true;
				}
			}
			return false;
		}

		public function first() {
			if(count($this->models) == 0) return null;
			return $this->models[0];
		}

		public function ids() {
			$ids = array();
			if(count($this->models) === 0) return $ids;
			$primKey = $this->models[0]->getPrimaryKey();
			foreach($this->models as $model) {
				$ids[] = $model->$primKey;
			}
			return $ids;
		}

		public function eagerLoad($loadArr) {
			
			if(count($this->models) == 0) return $this;
			$ids = array();
			$primKey = $this->models[0]->getPrimaryKey();
			$difModels = array();
			foreach($this->models as $model) {
				if(!isset($difModels[get_class($model)])) {
					$difModels[get_class($model)] = new Collection;
				}
				$difModels[get_class($model)][] = $model;
				$ids[] = $model->$primKey;
			}
			if(count($difModels) > 1) {
				foreach($difModels as $c) {
					$c->eagerLoad($loadArr);
				}
				return $this;
			}
			$loadedToModal = array();
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
				if(!method_exists($model, $relationProp)) continue;
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
				$loaded[$relationProp] = new Collection;
				$relation = $model->$relationProp();
				$alreadyLoaded = true;
				foreach($this->models as $m) {
					if(!$m->hasRelation($relationProp)) {
						$alreadyLoaded = false;
					}
				}

				$table = $model->getTable();
				if(!$alreadyLoaded) {
					if(get_class($relation) == 'lib\modelling\relations\MorphTo') {
						$relations = new Collection;
						$result = array();
						$rKey = 0;
						foreach($this->models as $m) {
							$relation = $m->$relationProp();
							if(!is_null($query) && $relationProp === end($exp)) {
								$query($relation);
							}
							$relation->useHandler($m->getDatabase());
							$r = $relation->get();
							if(count($r) > 0) {
								$relations[$rKey] = $r[0];
							} else {
								$relations[$rKey] = null;
							}
							$result[$rKey] = array(
								'__table_'.$table.'__key' => $m->{$m->getPrimaryKey()}
							);
							$rKey++;
						}
					} else {
						if(!is_null($query) && $relationProp === end($exp)) {
							$query($relation);
						}
						$relation->useHandler($this->models[0]->getDatabase());
						$relations = $relation->get($ids);
						$result = $relation->result;
					}
					
				} else {
					$rKey = 0;
					$relations = new Collection;
					$result = array();
					foreach($this->models as $m) {
						if($relation->returnsMultiple) {
							foreach($m->$relationProp as $r) {
								$relations[$rKey] = $r;
								$result[$rKey] = array(
									'__table_'.$table.'__key' => $m->{$m->getPrimaryKey()}
								);
								$rKey++;
							}
						} else {
							$relations[$rKey] = $m->$relationProp;
							$result[$rKey] = array(
								'__table_'.$table.'__key' => $m->{$m->getPrimaryKey()}
							);
							$rKey++;
						}
					}
				}
				
				if(!isset($loadedToModal[$relationProp])) {
					$loadedToModal[$relationProp] = array("multiple"=>$relation->returnsMultiple,"models"=>array());
				}
				
				foreach($relations->models as $key=>$m) {
					$loaded[$relationProp][] = $m;
					$res = $result[$key];
					$modelId = $res['__table_'.$table.'__key'];
					if(!isset($loadedToModal[$relationProp]['models'][$modelId])) {
						if($relation->returnsMultiple) {
							$loadedToModal[$relationProp]['models'][$modelId] = new Collection;
						}
					}
					if($relation->returnsMultiple) {
						$loadedToModal[$relationProp]['models'][$modelId][] = $m;
					} else {
						$loadedToModal[$relationProp]['models'][$modelId] = $m;
					}
				}
				
			}
			if(count($nextLoad) > 0) {
				foreach($nextLoad as $str=>$load) {
					if(isset($load['query'])) {
						$nextLoadArr = array($load['load'] => $load['query']);
					} else {
						$nextLoadArr = array($load['load']);
					}
					$loaded[$load['collection']]->eagerLoad($nextLoadArr);
				}
			}
			foreach($this->models as $model) {
				$id = $model->{$model->getPrimaryKey()};
				foreach($loadedToModal as $prop=>$arr) {
					$multiple = $arr['multiple'];
					if(isset($arr['models'][$id])) {
						$model->eagerLoad($prop,$arr['models'][$id]);
					} else {
						if($multiple) {
							$model->eagerLoad($prop,new Collection);
						} else {
							$model->eagerLoad($prop,null);
						}
					}
				}
			}
			return $this;
		}
	}