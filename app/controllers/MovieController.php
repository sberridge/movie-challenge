<?php
	class MovieController extends BaseController {
		public function movieSearch() {
			$q = Input::get('q');
			$year = Input::get('year');
			if(strlen($q) >= 1) {
				$res = app\apis\IMDB::search($q,$year);
				if(isset($res->Error)) {
					echo json_encode(array());
				} else {
					echo json_encode($res->Search);
				}
			}
			die;
		}
	}