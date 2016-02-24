<?php View::make('global/start')->render(); ?>
<div class="box center">
	<div class="inner-box">
		<h2><?=$user->username?></h2>
		<p><strong>Completed challenges: </strong><?=$completedCount?></p>
		<p><strong>Failed challenges: </strong><?=$failedCount?></p>
		

		
	</div>
</div>

<div class="box center">
	<div class="inner-box">
		<h2>Ongoing Challenges</h2>
		<?php if(count($ongoingChallenges) == 0): ?>
		<p><?=$user->username?> doesn't have any ongoing challenges.</p>
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
		<p><?=$user->username?> doesn't have any upcoming challenges.</p>
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
		<h2>Past Challenges</h2>
		<?php if(count($pastChallenges) == 0): ?>
		<p><?=$user->username?> doesn't have any past challenges.</p>
		<?php else: ?>
		<div class="item-list challenge-list">
			<?php foreach($pastChallenges as $challenge): ?>
			<div class='item challenge <?=$pastChallengeMovieCounts[$challenge->id] < $challenge->no_of_movies ? 'failed' : 'completed'?>'>
				<a href="<?=Url::route('showChallenge',array($challenge->slug))?>">
					<div class="left">
						<h4><?=$challenge->name?></h4>
						<p class='sub-text'><strong>From: </strong><time datetime='<?=$challenge->start_date?>'><?=Dater::make($challenge->start_date)->format('d M Y')?></time> <strong>To: </strong><time datetime='<?=$challenge->end_date?>'><?=Dater::make($challenge->end_date)->format('d M Y')?></time></p>
						
						<p class="sub-text"><strong>Created by: </strong><?=$challenge->owner->username?></p>
						<p class="sub-text"><strong>Completed: </strong><?=$pastChallengeMovieCounts[$challenge->id]?>/<?=$challenge->no_of_movies?></p>
					</div>
				</a>
			</div>
			<?php endforeach; ?>
		</div>
		<?php endif; ?>
		<?=$paginator->renderLinks()?>
	</div>
</div>
<?php View::make('global/end')->render(); ?>