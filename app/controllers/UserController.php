<?php
	class UserController extends BaseController {
		public function login() {
			$checks = array(
				'username'=>array('required'),
				'password'=>array('required')
			);
			$action = strtolower(Input::get('action'));
			if($action == 'sign up') {
				$checks = array_merge($checks,array(
					'username'=>array('required','unique:users,username'),
					'password_confirm'=>array('required','equals:password'),
					'first_name'=>array('required'),
					'surname'=>array('required'),
					'email'=>array('required','email')
				));
			}
			$valid = Input::validate($checks);
			if($valid->hasError) {
				$this->prepareError($valid);
				Redirect::route('showHome');
			}
			if($action == 'sign up') {
				$user = new User;
				$user->username = Input::get('username');
				$user->password = Hasher::hash(Input::get('password'));
				$user->first_name = Input::get('first_name');
				$user->surname = Input::get('surname');
				$user->email = Input::get('email');
				if($user->save()) {
					Session::flash('messages',array(
						'success'=>array('Account created, sign in to start making challenges!')
					));
				} else {
					Session::flash('messages',array(
						'error'=>array('Something went wrong, please try again')
					));
				}
			} else {
				if(User::authAttempt(Input::get('username'),Input::get('password'))) {
					Session::flash('messages',array(
						'success'=>array('Hello '.Auth::user()->first_name)
					));
				} else {
					Session::flash('messages',array(
						'error'=>array('The username or password entered was incorrect')
					));
				}
			}
			Redirect::route('showHome');

		}

		public function logout() {
			Auth::logout();
			Redirect::route('showHome');
		}

		public function userSearch() {
			$q = Input::get('q');
			$users = User::all()->where('username','LIKE',$q.'%')->where('id','!=',Auth::user()->id);

			if(Input::has('exclude_challenge')) {
				$users->whereNotIn('users.id',Sql::table('user_challenge')
					->select(array('user_id'))
					->where('challenge_id','=',Input::get('exclude_challenge')));
			}
			$users = $users->get();
			$results = array();
			foreach($users as $user) {
				$results[] = $user->toArray();
			}
			echo json_encode($results);
			die;
		}

		public function showAccount($slug) {
			$slug = explode('-',$slug);
			$user = User::find($slug[0]);
			$completedCount = $user->completedChallenges()->count();
			$failedCount = $user->failedChallenges()->count();
			$ongoingChallenges = $user->currentChallenges()->order('end_date','ASC')->get()->eagerLoad(array('owner'));
			$upcomingChallenges = $user->upcomingChallenges()->order('start_date','ASC')->get()->eagerLoad(array('owner'));
			$pastChallenges = $user->pastChallenges()->order('end_date','DESC')->paginate(20,'p')->get();
			$paginator = Paginator::make('p');
			$pastMovies = Sql::table('challenge_entries')
				->select(array(
					'challenge_id',
					'COUNT(*) movie_count'
				))
				->where('user_id','=',$user->id)
				->whereIn('challenge_id',$pastChallenges->ids())
				->group('challenge_id')
				->get();
			$pastChallengeMovieCounts = array();
			foreach($pastChallenges as $pastChallenge) {
				$pastChallengeMovieCounts[$pastChallenge->id] = 0;
			}
			foreach($pastMovies as $pastCount) {
				$pastChallengeMovieCounts[$pastCount['challenge_id']] = $pastCount['movie_count'];
			}
			return View::make('user/showAccount',array(
				'user'=>$user,
				'completedCount'=>$completedCount,
				'failedCount'=>$failedCount,
				'ongoingChallenges'=>$ongoingChallenges,
				'upcomingChallenges'=>$upcomingChallenges,
				'pastChallenges'=>$pastChallenges,
				'pastChallengeMovieCounts'=>$pastChallengeMovieCounts,
				'paginator'=>$paginator
			));
		}
	}