<?php
	class BaseController {

		public function __construct() {
			if(Session::has("messages")) {
				View::share(array(
					"page_messages"=>Session::get("messages")
				));
			}
			$previousInput = array();
			if(Session::has("previous_input")) {
				$previousInput = Session::get('previous_input');
			}
			View::share(array(
				"previous_input"=>$previousInput
			));
			$erroredFields = array();
			if(Session::has("errored_fields")) {
				$erroredFields = Session::get('errored_fields');
			}			
			
			View::share(array(
				"errored_fields"=>$erroredFields
			));

			if(Auth::check()) {
				$inviteCount = Auth::user()->invites()->count();
				View::share(array(
					'invite_count'=>$inviteCount
				));
			}
		}

		public function prepareError($input) {
			$toFlash = array(
				"previous_input"=>Input::all(),
				"errored_fields"=>array_keys($input->errors),
				"messages"=>array(
					"error"=>$input->errors
				)
			);
			foreach($toFlash as $key=>$val) {
				Session::flash($key,$val);
			}
		} 
	}