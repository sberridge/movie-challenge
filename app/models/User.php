<?php
	use lib\app\authentication\Authenticator;
	use lib\app\authentication\Auth;
	class User extends Auth implements Authenticator {
		public $table = "users";
		public $authUniqueField = "username";

		public function getPassword() {
			return $this->password;
		}

		public static function getById($id) {
			return User::find($id);
		}

		public function getIdentifier() {
			return $this->getPrimaryKey();
		}

		public function challenges() {
			return $this->belongsToMany('Challenge','user_challenge','user_id','challenge_id')->withLink(array('user_challenge.id'));
		}

		public function ownedChallenges() {
			return $this->hasMany('Challenge','user_id');
		}

		public function fullName() {
			return $this->first_name.' '.$this->surname;
		}

		public function currentChallenges() {
			$today = new DateTime;
			$challenges = $this->challenges()->where('end_date','>=',$today->format('Y-m-d'))->where('start_date','<=',$today->format('Y-m-d'));
			return $challenges;
		}

		public function upcomingChallenges() {
			$today = new DateTime;
			$challenges = $this->challenges()->where('end_date','>=',$today->format('Y-m-d'))->where('start_date','>',$today->format('Y-m-d'));
			return $challenges;
		}

		public function pastChallenges() {
			$today = new DateTime;
			$challenges = $this->challenges()->where('end_date','<',$today->format('Y-m-d'));
			return $challenges;
		}

		public function invites() {
			return $this->hasMany('ChallengeInvite','user_id');
		}

		public function challengeEntries() {
			return $this->hasMany('ChallengeEntry','user_id');
		}

		public function challengeComments() {
			return $this->hasMany('ChallengeComment','user_id');
		}

		public function slug() {
			return Str::slugify($this->id.'-'.$this->username);
		}

		public function isInChallenge(Challenge $challenge) {
			$check = Sql::table('user_challenge')
				->select(array('*'))
				->where('user_id','=',$this->id)
				->where('challenge_id','=',$challenge->id)
				->count();
			if($check) {
				return true;
			} 
			return false;
		}

		public function hasCompletedChallenge(Challenge $challenge) {
			return $this->challengeEntries()->where('challenge_id','=',$challenge->id)->count() >= $challenge->no_of_movies;
		}

		public function completedChallenges() {
			$challenges = $this->challenges()
				->join(
					Sql::table('challenge_entries')
						->select(array('COUNT(*) num','challenge_id'))
						->where('user_id','=',$this->id)
						->group('challenge_id'),
					'num_of_entries',
					'num_of_entries.challenge_id',
					'challenges.id'
				)
				->whereRaw('num_of_entries.num >= challenges.no_of_movies');
			return $challenges;
		}

		public function failedChallenges() {
			$today = new DateTime;
			$challenges = $this->challenges()
				->leftJoin(
					Sql::table('challenge_entries')
						->select(array('COUNT(*) num','challenge_id'))
						->where('user_id','=',$this->id)
						->group('challenge_id'),
					'num_of_entries',
					'num_of_entries.challenge_id',
					'challenges.id'
				)
				->where('challenges.end_date','<',$today->format('Y-m-d'))
				->whereRaw('(num_of_entries.num IS NULL OR num_of_entries.num < challenges.no_of_movies)');
			return $challenges;
		}


	}