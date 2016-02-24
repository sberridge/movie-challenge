<?php View::make('global/start')->render(); ?>
<div class="box center">
	<div class="inner-box">
		<h2><?=$challenge->name?> - <?=$user->username?></h2>
		<table class="entry-list standard">
			<thead>
				<tr>
					<th><?=$challenge->daily ? 'Day' : '#'?></th>
					<th>Title</th>
					<th>Date</th>
				</tr>
			</thead>
			<tbody>
				<?php
					if($challenge->daily) {
						$date = new DateTime($challenge->start_date);
						$dayInt = new DateInterval('P1D');
					} 
					for($i = 0; $i < $challenge->no_of_movies; $i++): 
				?>
				<tr>
					<th>
						<?php if($challenge->daily): ?>
							<?=$date->format('d M Y')?>
						<?php else: ?>
							<?=$i+1?>
						<?php endif; ?>
					</th>
					<?php 
						if(($challenge->daily && array_key_exists($date->format('Y-m-d'), $entries)) || (!$challenge->daily && $entries->offsetExists($i))): 
							$key = $i;
							if($challenge->daily) {
								$key = $date->format('Y-m-d');
							}
					?>
					<td>
						<a href="http://www.imdb.com/title/<?=$entries[$key]->imdb_id?>" class='imdb-icon'><?=$entries[$key]->imdbMovie->title?></a>
					</td>
					<td>
						<?=Dater::make($entries[$key]->date)->format('d M Y H:i:s')?>
					</td>
					<?php else: ?>
					<td colspan='1'>No Entry</td>
					<td>&nbsp;</td>
					<?php endif; ?>
				</tr>
				<?php 
						if($challenge->daily) {
							$date->add($dayInt);
						} 
					endfor; 
				?>
			</tbody>
		</table>
	</div>
</div>
<?php View::make('global/end')->render(); ?>