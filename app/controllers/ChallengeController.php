<?php
	class ChallengeController extends BaseController {
		public function showNewChallenge() {

			return View::make('challenge/showNew');
		}

		public function showChallenge($slug) {
			$slug = explode('-',$slug);
			$challenge = Challenge::find($slug[0])
				->eagerLoad(array(
					'users.challengeEntries'=>function($q) use($slug) {
						$q->where('challenge_id','=',$slug[0]);
					},
					'owner',
					'movies.imdbMovie'
				));
			$challengeCompleted = Auth::user()->hasCompletedChallenge($challenge);
			$inChallenge = Auth::user()->isInChallenge($challenge);
			$allowedEntry = $challenge->allowedEntry() && !$challengeCompleted && $inChallenge;
			$requiredWatched = array();
			$challengeFinished = $challenge->isFinished();
			if(count($challenge->movies) > 0) {
				$requiredIds = array();
				foreach($challenge->movies as $movie) {
					$requiredIds[] = $movie->imdb_id;
				}
				$watched = Auth::user()->challengeEntries()->where('challenge_id','=',$challenge->id)->whereIn('imdb_id',$requiredIds)->get();
				foreach($watched as $entry) {
					$requiredWatched[] = $entry->imdb_id;
				}
			}
			$participants = array();
			foreach($challenge->users as $user) {
				$participants[] = array(
					'user'=>$user,
					'challenge_completed'=>count($user->challengeEntries) >= $challenge->no_of_movies
				);
			}

			$top5Movies = null;
			//if($challengeFinished) {
				$top5Movies = $challenge->top5Movies();
			//}

			$noOfEntries = 0;
			if($inChallenge) {
				$noOfEntries = count($challenge->users->find(Auth::user()->id)->challengeEntries);
			}

			$getComments = $challenge->comments()->whereNull('parent_id')->order('date','DESC')->paginate(10)->get()->eagerLoad(array('author'));
			$paginator = Paginator::make('p');
			$totalPages = $paginator->totalPages;
			$comments = array();
			foreach($getComments as $comment) {
				$comments[] = $comment;
			}
			$comments = array_reverse($comments);
			$ids = $getComments->ids();
			
			
			$childComments = array();

			if(count($ids) > 0) {
				$lastChildComments = Sql::raw('SELECT 
						child.* 
					FROM 
						challenge_comments parent 
					INNER JOIN 
						challenge_comments child 
					ON 
						child.id = (
							SELECT 
								id 
							FROM 
								challenge_comments 
							WHERE 
								challenge_comments.parent_id = parent.id 
							ORDER BY 
								challenge_comments.`date` 
							DESC limit 1
						) 
					WHERE parent.id IN ('.Sql::generatePlaceholders($ids).')',$ids)->toClass('ChallengeComment')->run()->eagerLoad(array('author'));	
				foreach($lastChildComments as $childComment) {
					$childComments[$childComment->parent_id][] = $childComment;
				}
			}
			
			$numOfChildComments = Sql::table('challenge_comments')
				->select(array('count(*) num','parent_id'))
				->whereIn('parent_id',$ids)
				->group('parent_id')
				->get();
			$childCommentCount = array();
			foreach($numOfChildComments as $num) {
				$childCommentCount[$num['parent_id']] = $num['num'];
			}

			return View::make('challenge/showChallenge',array(
				'challenge'=>$challenge,
				'allowedEntry'=>$allowedEntry,
				'requiredWatched'=>$requiredWatched,
				'challengeCompleted'=>$challengeCompleted,
				'participants'=>$participants,
				'inChallenge'=>$inChallenge,
				'noOfEntries'=>$noOfEntries,
				'comments'=>$comments,
				'childComments'=>$childComments,
				'childCommentCount'=>$childCommentCount,
				'totalPages'=>$totalPages,
				'top5Movies'=>$top5Movies
			));
		}

		public function joinChallenge($id) {
			$challenge = Challenge::find($id);
			if(!$challenge->public) {
				Redirect::route('showHome');
			}
			$check = Auth::user()->challenges()->where('challenges.id','=',$id)->get()->first();
			if($check || $challenge->start_date < Dater::make()->format('Y-m-d')) {
				Redirect::route('showChallenge',array($challenge->slug));
			}
			Auth::user()->challenges()->link($challenge);
			Redirect::route('showChallenge',array($challenge->slug));
		}

		public function getComments($challengeId) {
			$challenge = Challenge::find($challengeId);
			$getComments = $challenge->comments()->whereNull('parent_id')->order('date','DESC')->paginate(10)->get()->eagerLoad(array('author'));
			$paginator = Paginator::make('p');
			$totalPages = $paginator->totalPages;
			$comments = array();
			foreach($getComments as $comment) {
				$comments[] = $comment;
			}
			$comments = array_reverse($comments);
			$ids = $getComments->ids();
			$lastChildComments = Sql::raw('SELECT 
					child.* 
				FROM 
					challenge_comments parent 
				INNER JOIN 
					challenge_comments child 
				ON 
					child.id = (
						SELECT 
							id 
						FROM 
							challenge_comments 
						WHERE 
							challenge_comments.parent_id = parent.id 
						ORDER BY 
							challenge_comments.`date` 
						DESC limit 1
					) 
				WHERE parent.id IN ('.Sql::generatePlaceholders($ids).')',$ids)->toClass('ChallengeComment')->run()->eagerLoad(array('author'));
			$childComments = array();
			foreach($lastChildComments as $childComment) {
				$childComments[$childComment->parent_id][] = $childComment;
			}
			$numOfChildComments = Sql::table('challenge_comments')
				->select(array('count(*) num','parent_id'))
				->whereIn('parent_id',$ids)
				->group('parent_id')
				->get();
			$childCommentCount = array();
			foreach($numOfChildComments as $num) {
				$childCommentCount[$num['parent_id']] = $num['num'];
			}
			$return = array();
			foreach($comments as $comment) {
				$arr = $comment->toArray();
				$arr['date_formatted'] = Dater::make($comment->date)->format('d M Y H:i');
				$arr['author'] = $comment->author->username;
				$arr['reply_count'] = 0;
				if(array_key_exists($comment->id, $childComments)) {
					$replyArr = $childComments[$comment->id][0]->toArray();
					$replyArr['date_formatted'] = Dater::make($replyArr['date'])->format('d M Y H:i');
					$replyArr['author'] = $childComments[$comment->id][0]->author->username;
					$arr['reply'] = $replyArr;
					$arr['reply_count'] = $childCommentCount[$comment->id];
				}
				$return[] = $arr;
			}
			echo json_encode($return);
			die;
		}

		public function getCommentReplies($challengeId,$commentId) {
			$challenge = Challenge::find($challengeId);
			$comment = $challenge->comments()->where('challenge_comments.id','=',$commentId)->get()->first();
			$replies = $comment->childComments()->order('date','ASC')->get()->eagerLoad(array('author'));
			$return = array();
			foreach($replies as $reply) {
				$arr = $reply->toArray();
				$arr['date_formatted'] = Dater::make($reply->date)->format('d M Y H:i');
				$arr['author'] = $reply->author->username;
				$return[] = $arr;
			}
			echo json_encode($return);
			die;
		}

		public function addComment($id) {
			$challenge = Challenge::find($id);
			$checks = array(
				'comment'=>array('required')
			);
			$valid = Input::validate($checks);
			if($valid->hasError) {
				$this->prepareError($valid);
				Redirect::route('showChallenge',array($challenge->slug));
			}
			$comment = new ChallengeComment;
			$comment->challenge_id = $challenge->id;
			$comment->user_id = Auth::user()->id;
			$comment->content = strip_tags(htmlentities(Input::get('comment')));
			$comment->date = Dater::make()->format('Y-m-d H:i:s');
			if($comment->save()) {
				Session::flash('messages',array(
					'success'=>array('Comment Added')
				));
			} else {
				Session::flash('messages',array(
					'error'=>array('Something went wrong, try again')
				));
			}
			Redirect::route('showChallenge',array($challenge->slug));
		}

		public function addCommentReply($challengeId,$commentId) {
			$challenge = Challenge::find($challengeId);
			$comment = $challenge->comments()->where('challenge_comments.id','=',$commentId)->get()->first();
			$reply = new ChallengeComment;
			$reply->challenge_id = $challenge->id;
			$reply->parent_id = $comment->id;
			$reply->content = strip_tags(htmlentities(Input::get('comment')));
			$reply->user_id = Auth::user()->id;
			$reply->date = Dater::make()->format('Y-m-d H:i:s');
			if($reply->save()) {
				Session::flash('messages',array(
					'success'=>array('Reply Saved')
				));
			} else {
				Session::flash('messages',array(
					'error'=>array('Something went wrong, please try again')
				));
			}
			Redirect::route('showChallenge',array($challenge->slug));
		}

		public function showChallengeUser($challengeSlug,$userSlug) {
			$challengeSlug = explode('-',$challengeSlug);
			$userSlug = explode('-',$userSlug);
			$challenge = Challenge::find($challengeSlug[0]);

			$user = $challenge->users()->where('users.id','=',$userSlug[0])->get()->first();
			if(!$user) {
				lib\app\App::stop(404);
			}
			$user->eagerLoad(array(
				'challengeEntries'=>function($q) use($challenge) {
					$q->where('challenge_id','=',$challenge->id)->order('date','ASC');
				},
				'challengeEntries.imdbMovie'
			));
			$entries = $user->challengeEntries;
			if($challenge->daily) {
				$entries = array();
				foreach($user->challengeEntries as $entry) {
					$entries[Dater::make($entry->date)->format('Y-m-d')] = $entry;
				}
			}

			return View::make('challenge/showUser',array(
				'challenge'=>$challenge,
				'user'=>$user,
				'entries'=>$entries
			));
		}

		public function sendInvites($id) {
			$challenge = Auth::user()->ownedChallenges()->where('challenges.id','=',$id)->get()->first();
			if(!$challenge) {
				lib\app\App::stop(404);
			}
			$challengeUsers = $challenge->users;
			$users = Input::get('users');
			$today = new DateTime;
			$invites = $challenge->invites;
			$alreadyInvited = array();
			foreach($invites as $invite) {
				$alreadyInvited[] = $invite->user_id;
			}
			foreach($users as $user) {
				if(!$challengeUsers->has($user) && !in_array($user,$alreadyInvited)) {
					$invite = new ChallengeInvite;
					$invite->challenge_id = $challenge->id;
					$invite->user_id = $user;
					$invite->date = $today->format('Y-m-d H:i:s');
					$invite->save();
				}
			}
			Session::flash('messages',array(
				'success'=>array('Invites sent')
			));
			Redirect::route('showChallenge',array($challenge->slug));
		}

		public function addEntry($id) {
			$challenge = Challenge::find($id);
			if(!Auth::user()->isInChallenge($challenge)) {
				lib\app\App::stop(404);
			}
			$allowedEntry = $challenge->allowedEntry();
			if(!$allowedEntry) {
				Session::flash('messages',array(
					'error'=>array('You are not allowed to enter movies into this challenge right now')
				));
				Redirect::route('showChallenge',array($challenge->slug));
			}
			if(Auth::user()->hasCompletedChallenge($challenge)) {
				Session::flash('messages',array(
					'success'=>array('You have completed this challenge')
				));
				Redirect::route('showChallenge',array($challenge->slug));
			}
			if(!Input::has('movies')) {
				Session::flash('messages',array(
					'error'=>array('You must select a movie')
				));
				Redirect::route('showChallenge',array($challenge->slug));
			}
			$imdbMovies = array();
			foreach(Input::get('movies') as $movie) {
				$movie = json_decode(urldecode($movie));
				$imdbMovies[$movie->id] = $movie;
			}
			IMDBMovie::create($imdbMovies);
			$movie = array_values($imdbMovies)[0];
			$alreadyEntered = Auth::user()->challengeEntries()->where('challenge_id','=',$challenge->id)->get();
			$enteredById = array();
			foreach($alreadyEntered as $entered) {
				$enteredById[$entered->imdb_id] = $entered;
			}
			if(array_key_exists($movie->id,$enteredById)) {
				Session::flash('messages',array(
					'error'=>array('You have already entered that movie for this challenge')
				));
				Redirect::route('showChallenge',array($challenge->slug));
			}
			if(count($challenge->movies) > 0) {
				$notEntered = array();
				foreach($challenge->movies as $required) {
					if(!array_key_exists($required->imdb_id, $enteredById)) {
						$notEntered[] = $required->imdb_id;
					}
				}
				$leftToEnter = $challenge->no_of_movies - count($alreadyEntered);
				if($leftToEnter === count($notEntered) && !in_array($movie->id, $notEntered)) {
					Session::flash('messages',array(
						'error'=>array('You must watch the required movies to complete the challenge')
					));
					Redirect::route('showChallenge',array($challenge->slug));
				}
			}
			$entry = new ChallengeEntry;
			$entry->user_id = Auth::user()->id;
			$entry->date = Dater::make()->format('Y-m-d H:i:s');
			$entry->challenge_id = $challenge->id;
			$entry->imdb_id = $movie->id;
			if($entry->save()) {
				Session::flash('messages',array(
					'success'=>array('Movie Entered')
				));
			} else {
				Session::flash('messages',array(
					'error'=>array('Something went wrong, please try again')
				));
			}
			Redirect::route('showChallenge',array($challenge->slug));
		}

		public function createChallenge() {
			$checks = array(
				'title'=>array('required'),
				'start_date'=>array('required'),
				'finish_date'=>array('required'),
				'no_of_movies'=>array('requiredWithout:daily','numeric','greaterThan:0'),
				'daily'=>array('requiredWithout:no_of_movies')
			);
			$valid = Input::validate($checks);
			if($valid->hasError) {
				$this->prepareError($valid);
				Redirect::route('showNewChallenge');
			}

			$challenge = new Challenge;
			$challenge->name = Input::get('title');
			$challenge->user_id = Auth::user()->id;
			$start = new DateTime(Input::get('start_date'));
			$end = new DateTime(Input::get('finish_date'));
			$challenge->start_date = $start->format('Y-m-d');
			$challenge->end_date = $end->format('Y-m-d');
			$challenge->details = Input::has('details') ? Input::get('details') : null;
			$today = new DateTime;
			if($challenge->start_date < $today->format('Y-m-d')) {
				Session::flash('messages',array(
					'error'=>array('The challenge cannot begin in the past')
				));
				Redirect::route('showNewChallenge');
			}
			if($challenge->end_date < $challenge->start_date) {
				Session::flash('messages',array(
					'error'=>array('The challenge cannot end before it begins')
				));
				Redirect::route('showNewChallenge');
			}
			$challenge->daily = Input::has('daily') ? 1 : 0;
			$challenge->public = Input::has('public') ? 1 : 0;
			if(!Input::has('daily')) {
				$challenge->no_of_movies = Input::get('no_of_movies');
			} else {
				$diff = $start->diff($end);
				$challenge->no_of_movies = $diff->days+1;
			}
			$challenge->public = Input::has('public') ? 1 : 0;

			if($challenge->save()) {
				if(Input::has('movies')) {
					$imdbMovies = array();
					foreach(Input::get('movies') as $movie) {
						$movie = json_decode(urldecode($movie));
						$challengeMovie = new ChallengeMovie;
						$imdbMovies[$movie->id] = $movie;
						$challengeMovie->imdb_id = $movie->id;
						$challengeMovie->challenge_id = $challenge->id;
						$challengeMovie->save();
					}
					IMDBMovie::create($imdbMovies);
				}
				if(Input::has('users')) {
					$users = User::find(Input::get('users'));
					foreach($users as $user) {
						$invite = new ChallengeInvite;
						$invite->challenge_id = $challenge->id;
						$invite->user_id = $user->id;
						$invite->date = $today->format('Y-m-d H:i:s');
						$invite->save();
					}
				}
				$challenge->users()->link(Auth::user());
				Session::flash('messages',array(
					'success'=>array('Challenge Created')
				));
			} else {
				Session::flash('messages',array(
					'error'=>array('Challenge Not Created')
				));
			}
			Redirect::route('showHome');
		}

		public function showInvites() {
			$invites = Auth::user()->invites()->order('date','DESC')->get()->eagerLoad(array('challenge.owner'));
			return View::make('challenge/showInvites',array(
				'invites'=>$invites
			));
		}

		public function acceptInvite($id) {
			$invite = Auth::user()->invites()->where('challenge_invites.id','=',$id)->get()->first();
			if(!$invite) {
				echo json_encode(array(
					'success'=>false,
					'message'=>'Invite not found',
					'remove'=>false
				));
				die;
			}
			$challenge = $invite->challenge;
			$today = new DateTime;
			if($today->format('Y-m-d') > $invite->challenge->start_date) {
				echo json_encode(array(
					'success'=>false,
					'remove'=>true,
					'message'=>'Challenge already started'
				));
				$invite->delete();
				die;
			}
			if(Auth::user()->challenges()->link($challenge)) {
				$invite->delete();
				echo json_encode(array(
					'success'=>true,
					'remove'=>true
				));
				die;
			}
			echo json_encode(array(
				'success'=>false,
				'remove'=>false
			));
			die;
		}

		public function removeInvite($id) {
			$invite = Auth::user()->invites()->where('challenge_invites.id','=',$id)->get()->first();
			if(!$invite) {
				echo json_encode(array(
					'success'=>false,
					'message'=>'Invite not found',
					'remove'=>false
				));
				die;
			}
			if($invite->delete()) {
				echo json_encode(array(
					'success'=>true,
					'remove'=>true
				));
				die;
			}
			echo json_encode(array(
				'success'=>false,
				'remove'=>false
			));
			die;
		}
	}