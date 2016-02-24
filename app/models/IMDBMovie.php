<?php
	class IMDBMovie extends lib\modelling\BaseModel {
		protected $table = 'imdb_movies';
		protected $primary_key = 'imdb_id';

		public function challengeMovies() {
			return $this->hasMany('ChallengeMovie','imdb_id');
		}

		public function challengeEntries() {
			return $this->hasMany('ChallengeEntry','imdb_id');
		}

		public static function create($movies) {
			$created = IMDBMovie::all()->whereIn('imdb_id',array_keys($movies))->get();
			foreach($movies as $id=>$movie) {
				if(!$created->has($id)) {
					$imdbMovie = new IMDBMovie;
					$imdbMovie->imdb_id = $id;
					$imdbMovie->title = $movie->title;
					$imdbMovie->release_year = $movie->releaseYear;
					$imdbMovie->save();
				}
			}
		}

	}