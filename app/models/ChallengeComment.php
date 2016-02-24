<?php
	class ChallengeComment extends lib\modelling\BaseModel {
		protected $table = 'challenge_comments';

		public function challenge() {
			return $this->belongsTo('Challenge','challenge_id');
		}

		public function author() {
			return $this->belongsTo('User','user_id');
		}

		public function childComments() {
			return $this->hasMany('ChallengeComment','parent_id');
		}

		public function parentComment() {
			return $this->belongsTo('ChallengeComment','parent_id');
		}
	}