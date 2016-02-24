<?php View::make('global/start')->render(); ?>
<div class="box center">
	<div class="inner-box">
		<h2>Welcome to Movie Challenge</h2>
		<p>Challenge your friends and take part in movie watching challenges!</p>
	</div>
</div>
<?php if(!Auth::check()): ?>
<div class="box center">
	<div class="inner-box">
		<h2>Sign In</h2>
		<form class='sign-up-form' action="<?=Url::route('login')?>" method='post'>
			<input type="text" name='username' placeholder='Username'>
			<input type="password" name='password' placeholder='Password'>
			<div class="sign-up-fields" id='sign_up_fields'>
				<input type="password" name='password_confirm' placeholder='Confirm Password'>
				<input type="text" name='first_name' placeholder='First Name'>
				<input type="text" name='surname' placeholder='Surname'>
				<input type="email" name='email' placeholder='Email Address'>
			</div>
			<div class="tc">
				<input type="submit" name='action' value='Sign In'><input type="submit" name='action' id='sign_up_btn' value='Sign Up'>
			</div>
		</form>
	</div>
</div>
<script>
	(function() {

		var signUpBtn = document.getElementById('sign_up_btn'),
		signUpFields = document.getElementById('sign_up_fields');
		setTimeout(function() {
			signUpFields.height = signUpFields.offsetHeight+'px';
			signUpFields.style.height = '0px';
			signUpFields.classList.add('animate');
		},0);
		
		signUpBtn.addEventListener('click',function(e) {
			if(signUpFields.style.height == '0px') {
				signUpFields.style.height = signUpFields.height;
				e.preventDefault();
			}
		});
	})();
</script>
<?php else: ?>
<div class="box center">
	<div class="inner-box">
		<h2>Ongoing Challenges</h2>
		<?php if(count($ongoingChallenges) == 0): ?>
		<p>You don't have any ongoing challenges, <a href='<?=Url::route('showNewChallenge')?>'>why not make one</a>?</p>
		<?php else: ?>
		<div class="item-list">
			<?php foreach($ongoingChallenges as $challenge): ?>
			<div class="item">
				<a href="<?=Url::route('showChallenge',array($challenge->slug))?>">
					<div class="left">
						<h4><?=$challenge->name?></h4>
						<p class='sub-text'><strong>From: </strong><time datetime='<?=$challenge->start_date?>'><?=Dater::make($challenge->start_date)->format('d M Y')?></time> <strong>To: </strong><time datetime='<?=$challenge->end_date?>'><?=Dater::make($challenge->end_date)->format('d M Y')?></time></p>
						
						<p class="sub-text"><strong>Created by: </strong><?=$challenge->owner->username?></p>
					</div>
				</a>
			</div>
			<?php endforeach; ?>
		</div>
		<?php endif; ?>
	</div>
</div>

<div class="box center">
	<div class="inner-box">
		<h2>Upcoming Challenges</h2>
		<?php if(count($upcomingChallenges) == 0): ?>
		<p>You don't have any upcoming challenges, <a href='<?=Url::route('showNewChallenge')?>'>why not make one</a>?</p>
		<?php else: ?>
		<div class="item-list">
			<?php foreach($upcomingChallenges as $challenge): ?>
			<div class='item'>
				<a href="<?=Url::route('showChallenge',array($challenge->slug))?>">
					<div class="left">
						<h4><?=$challenge->name?></h4>
						<p class='sub-text'><strong>From: </strong><time datetime='<?=$challenge->start_date?>'><?=Dater::make($challenge->start_date)->format('d M Y')?></time> <strong>To: </strong><time datetime='<?=$challenge->end_date?>'><?=Dater::make($challenge->end_date)->format('d M Y')?></time></p>
						
						<p class="sub-text"><strong>Created by: </strong><?=$challenge->owner->username?></p>
					</div>
				</a>
			</div>
			<?php endforeach; ?>
		</div>
		<?php endif; ?>
	</div>
</div>

<div class="box center">
	<div class="inner-box">
		<h2>Open Challenges</h2>
		<?php if(count($publicChallenges) == 0): ?>
		<p>There are no open public challenges.</p>
		<?php else: ?>
		<div class="item-list">
			<?php foreach($publicChallenges as $challenge): ?>
			<div class='item'>
				<a href="<?=Url::route('showChallenge',array($challenge->slug))?>">
					<div class="left">
						<h4><?=$challenge->name?></h4>
						<p class='sub-text'><strong>From: </strong><time datetime='<?=$challenge->start_date?>'><?=Dater::make($challenge->start_date)->format('d M Y')?></time> <strong>To: </strong><time datetime='<?=$challenge->end_date?>'><?=Dater::make($challenge->end_date)->format('d M Y')?></time></p>
						
						<p class="sub-text"><strong>Created by: </strong><?=$challenge->owner->username?></p>
					</div>
				</a>
			</div>
			<?php endforeach; ?>
		</div>
		<?php endif; ?>
	</div>
</div>
<?php endif; ?>

<?php View::make('global/end')->render(); ?>