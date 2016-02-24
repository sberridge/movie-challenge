<?php
	Route::get('/',array(
		'controller'=>'HomeController@showHome',
		'name'=>'showHome'
	));

	Route::post('login',array(
		'controller'=>'UserController@login',
		'name'=>'login'
	));

	Route::get('logout',array(
		'controller'=>'UserController@logout',
		'name'=>'logout',
		'before'=>array('checkAuth')
	));

	Route::get('users/{slug}',array(
		'controller'=>'UserController@showAccount',
		'name'=>'showAccount',
		'before'=>array('checkAuth')
	));

	Route::get('challenges/{id}/comments/{commentId}/replies',array(
		'controller'=>'ChallengeController@getCommentReplies',
		'name'=>'getCommentReplies',
		'before'=>array('checkAuth')
	));

	Route::get('challenges/{id}/join',array(
		'controller'=>'ChallengeController@joinChallenge',
		'name'=>'joinChallenge',
		'before'=>array('checkAuth')
	));

	Route::get('challenges/{id}/comments',array(
		'controller'=>'ChallengeController@getComments',
		'name'=>'getChallengeComments',
		'before'=>array('checkAuth')
	));

	Route::post('challenges/{id}/comments',array(
		'controller'=>'ChallengeController@addComment',
		'name'=>'addChallengeComment',
		'before'=>array('checkAuth')
	));

	Route::post('challenges/{id}/comments/{commentId}/reply',array(
		'controller'=>'ChallengeController@addCommentReply',
		'name'=>'addChallengeCommentReply',
		'before'=>array('checkAuth')
	));

	Route::get('challenges/new',array(
		'controller'=>'ChallengeController@showNewChallenge',
		'name'=>'showNewChallenge',
		'before'=>array('checkAuth')
	));

	Route::post('challenges/new',array(
		'controller'=>'ChallengeController@createChallenge',
		'name'=>'createChallenge',
		'before'=>array('checkAuth')
	));


	Route::get('challenges/invites',array(
		'controller'=>'ChallengeController@showInvites',
		'name'=>'showInvites',
		'before'=>array('checkAuth')
	));

	Route::get('challenges/{slug}',array(
		'controller'=>'ChallengeController@showChallenge',
		'name'=>'showChallenge',
		'before'=>array('checkAuth')
	));

	Route::get('challenges/invites/{id}/accept',array(
		'controller'=>'ChallengeController@acceptInvite',
		'name'=>'acceptChallengeInvite',
		'before'=>array('checkAuth')
	));

	Route::get('challenges/invites/{id}/remove',array(
		'controller'=>'ChallengeController@removeInvite',
		'name'=>'removeChallengeInvite',
		'before'=>array('checkAuth')
	));

	Route::get('challenges/{challengeSlug}/users/{userSlug}',array(
		'controller'=>'ChallengeController@showChallengeUser',
		'name'=>'showChallengeUser',
		'before'=>array('checkAuth')
	));

	Route::post('challenges/{id}/entries',array(
		'controller'=>'ChallengeController@addEntry',
		'name'=>'addChallengeEntry',
		'before'=>array('checkAuth')
	));

	Route::post('challenges/{id}/invites',array(
		'controller'=>'ChallengeController@sendInvites',
		'name'=>'sendChallengeInvites',
		'before'=>array('checkAuth')
	));

	Route::post('movies/search',array(
		'controller'=>'MovieController@movieSearch',
		'name'=>'movieSearch',
		'before'=>array('checkAuth')
	));

	Route::post('users/search',array(
		'controller'=>'UserController@userSearch',
		'name'=>'userSearch',
		'before'=>array('checkAuth')
	));
