<?php

	class ChallengeInvite extends lib\modelling\BaseModel {
		protected $table = 'challenge_invites';

		public function user() {
			return $this->belongsTo('User','user_id');
		}

		public function challenge() {
			return $this->belongsTo('Challenge','challenge_id');
		}
	}