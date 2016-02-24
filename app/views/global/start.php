<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Movie Challenge</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link href='https://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css'>
	<link rel="stylesheet" href="/css/style.css">
	<link rel="stylesheet" href="/css/pikaday.css">
	<script src='/js/moment.js'></script>
	<script src='/js/pikaday.js'></script>
	<script src='/js/functions.js'></script>
</head>
<body>
	<header>
		<h1><a href="<?=Url::route('showHome')?>">Movie Challenge</a></h1>
		<?php if(Auth::check()): ?>
		<div class="user-area">
			<div class="user-btn" id='user_menu_btn'></div>
			
			<div class='user-menu' id='user_menu'>
				<div class="inner-menu">
					<header>
						<h3><?=Auth::user()->fullName?></h3>
					</header>
					<nav>
						<ul>
							<li>
								<a href="<?=Url::route('showAccount',array(Auth::user()->slug))?>">Your Account</a>
							</li>
							<li>
								<a href="<?=Url::route('showNewChallenge')?>">New Challenge</a>
							</li>
							<li>
								<a href="<?=Url::route('showInvites')?>">Challenge Invites (<?=$invite_count?>)</a>
							</li>
							<li>
								<a href="<?=Url::route('logout')?>">Logout</a>
							</li>
						</ul>	
					</nav>
				</div>
			</div>

		</div>
		<?php endif; ?>
	</header>
	<div class="content-area">
		<?php if(isset($page_messages)): ?>
			<?php foreach($page_messages as $type=>$messages): ?>
				<?php foreach($messages as $message): ?>
					<div class="page-message <?=$type?>"><?=$message?></div>
				<?php endforeach; ?>
			<?php endforeach; ?>
		<?php endif; ?>