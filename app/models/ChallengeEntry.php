<?php
	class ChallengeEntry extends lib\modelling\BaseModel {
		protected $table = 'challenge_entries';

		public function challenge() {
			return $this->belongsTo('Challenge','challenge_id');
		}

		public function user() {
			return $this->belongsTo('User','user_id');
		}

		public function imdbInfo() {
			return app\apis\IMDB::getById($this->imdb_id);
		}

		public function imdbMovie() {
			return $this->belongsTo('IMDBMovie','imdb_id');
		}
	}