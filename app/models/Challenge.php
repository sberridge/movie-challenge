<?php
	class Challenge extends lib\modelling\BaseModel {
		protected $table = 'challenges';

		public function users() {
			return $this->belongsToMany('User','user_challenge','challenge_id','user_id')->withLink(array('user_challenge.id'));
		}

		public function owner() {
			return $this->belongsTo('User','user_id');
		}

		public function entries() {
			return $this->hasMany('ChallengeEntry','challenge_id');
		}

		public function movies() {
			return $this->hasMany('ChallengeMovie','challenge_id');
		}

		public function invites() {
			return $this->hasMany('ChallengeInvite','challenge_id');
		}

		public function comments() {
			return $this->hasMany('ChallengeComment','challenge_id');
		}

		public function slug() {
			return Str::slugify($this->id.'-'.$this->name);
		}

		public function top5Movies() {
			$getCount = Sql::table('challenge_entries')
				->select(array('COUNT(*) times_watched','imdb_id'))
				->where('challenge_id','=',$this->id)
				->group('imdb_id')
				->order('times_watched','DESC')
				->limit(5)
				->get();
			$imdbIds = array();
			foreach($getCount as $movieCount) {
				$imdbIds[$movieCount['imdb_id']] = $movieCount['times_watched'];
			}
			$movies = IMDBMovie::find(array_keys($imdbIds));
			$results = array();
			foreach($movies as $movie) {
				$results[] = array(
					'movie'=>$movie,
					'times_watched'=>$imdbIds[$movie->imdb_id]
				);
			};
			usort($results, function($a,$b) {
				$a = $a['times_watched'];
				$b = $b['times_watched'];
				return $a == $b ? 0 : ($a < $b ? 1 : -1);
			});
			return $results;
		}

		public function isFinished() {
			$today = new DateTime;
			$todayDate = $today->format('Y-m-d');
			return $this->end_date < $todayDate;
		}

		public function allowedEntry() {
			$allowedEntry = false;			
			$today = new DateTime;
			$todayDate = $today->format('Y-m-d');
			if($this->start_date <= $todayDate && $this->end_date >= $todayDate) {
				if(!$this->daily) {
					$allowedEntry = true;
				} else {
					$check = Sql::table('challenge_entries')
						->where('user_id','=',Auth::user()->id)
						->where('challenge_id','=',$this->id)
						->where('DATE(`date`)','=',$todayDate)
						->count();
					if(!$check) {
						$allowedEntry = true;
					}
				}
			}
			return $allowedEntry;
		}

		public static function openPublicChallenges() {
			return Challenge::all()
				->leftJoin(
					Sql::table('user_challenge')
						->select(array('user_id','challenge_id'))
						->where('user_id','=',Auth::user()->id),
					'user_check',
					'user_check.challenge_id',
					'challenges.id'
				)
				->where('public','=',1)
				->where('start_date','>=',Dater::make()->format('Y-m-d'))
				->whereNull('user_check.user_id');
		}
	}