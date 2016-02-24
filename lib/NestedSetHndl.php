<?php
	class NestedSetHndl {

		private static $lft = 0;

		private static function loop($models) {
			$startedLoop = false;
			foreach($models as $key=>$model) {
				$nested = new \NestedSet;
				$nested->object_type = get_class($model);
				$nested->object_id = $model->{$model->getPrimaryKey()};
				$nested->lft = self::$lft;
				self::$lft++;
				$children = (property_exists($model, 'nestedChildren') ? $model->nestedChildren : array());
				if(count($children) > 0) {
					$hasChildNodes = false;
					foreach($children as $child) {
						
						$nextModels = $model->$child;
						if(count($nextModels) > 0) {
							$hasChildNodes = true;
						}
						self::loop($nextModels);
					}
					if($hasChildNodes) {
						self::$lft++;
					}
				}
				
				$nested->rht = self::$lft;
				$nested->save();
				if($key !== count($models)-1) {
					self::$lft++;
				}
			}
		}
		public static function init($class) {
			$models = $class::all()->get();
			self::loop($models);
		}

		public static function insert($class) {
			$increaseBy = 2;
			if($class->nestedSet) {
				$increaseBy = ($class->nestedSet->rht - $class->nestedSet->lft)+1;
				
				self::delete($class);
			}
			if(isset($class->nestedParent)) {
				$nestedSet = $class->{$class->nestedParent}->nestedSet;
				$lastRight = $nestedSet->rht;
				/*var_dump($lastRight);
				die;*/
				NestedSet::run('UPDATE nested_set SET lft = lft + '.$increaseBy.' WHERE lft >= ?',array($lastRight));
				NestedSet::run('UPDATE nested_set SET rht = rht + '.$increaseBy.' WHERE rht >= ?',array($lastRight));

			} else {
				$lastRight = Sql::table('nested_set')->select('max(`rht`) `rht`')->where('object_type','=',get_class($class))->get();
				if(count($lastRight) == 1) {
					$lastRight = $lastRight[0]['rht']+1;
				} else {
					$lastRight = 0;
				}
			}
			self::$lft = $lastRight;
			$models = array($class);
			self::loop($models);
			/*$nestedSet = new NestedSet;
			$nestedSet->object_type = get_class($class);
			$nestedSet->object_id = $class->{$class->getPrimaryKey()};
			$nestedSet->lft = $lastRight;
			$nestedSet->rht = $lastRight+1;
			$nestedSet->save();*/
		}

		public static function delete($class,$promote=false) {
			$nestedSet = $class->nestedSet;
			if(!$promote) {
				$toRemove = ($nestedSet->rht - $nestedSet->lft)+1;
				NestedSet::where('`lft`','>=',$nestedSet->lft)->where('`rht`','<=',$nestedSet->rht)->delete();
				NestedSet::run('UPDATE nested_set SET `lft` = `lft` - '.$toRemove.' WHERE `lft` > ?',array($nestedSet->rht));
				NestedSet::run('UPDATE nested_set SET `rht` = `rht` - '.$toRemove.' WHERE `rht` > ?',array($nestedSet->rht));
			}
		}
	}