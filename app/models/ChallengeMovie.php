<?php
	class ChallengeMovie extends lib\modelling\BaseModel {
		protected $table = 'challenge_movies';

		public function challenge() {
			return $this->belongsTo('Challenge','challenge_id');
		}

		public function imdbMovie() {
			return $this->belongsTo('IMDBMovie','imdb_id');
		}
	}