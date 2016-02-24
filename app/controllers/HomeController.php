<?php
	class HomeController extends BaseController {
		public function showHome() {
			if(!Auth::check()) {
				return View::make('home/showHome');
			}
			$ongoingChallenges = Auth::user()->currentChallenges()->order('end_date','ASC')->get()->eagerLoad(array('owner'));
			$upcomingChallenges = Auth::user()->upcomingChallenges()->order('start_date','ASC')->get()->eagerLoad(array('owner'));
			$openPublicChallenges = Challenge::openPublicChallenges()->order('start_date','ASC')->get()->eagerLoad(array('owner'));
			return View::make('home/showHome',array(
				'ongoingChallenges'=>$ongoingChallenges,
				'upcomingChallenges'=>$upcomingChallenges,
				'publicChallenges'=>$openPublicChallenges
			));
		}
	}