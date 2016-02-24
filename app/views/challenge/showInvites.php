<?php View::make('global/start')->render(); ?>
<div class="box center">
	<div class="inner-box">
		<h2>Challenge Invites</h2>
		<?php if(count($invites) == 0): ?>
			<p>You don't have any open invites.</p>
		<?php else: ?>
			<div class="invite-list item-list">
				<?php foreach($invites as $invite): ?>
				<div class="item invite">
					<div class="left">
						<h4><?=$invite->challenge->name?></h4>
						<p class="sub-text">Invited by: <?=$invite->challenge->owner->username?></p>
						<p class="sub-text"><strong>Start: </strong><?=Dater::make($invite->challenge->start_date)->format('d M Y')?> <strong>End: </strong><?=Dater::make($invite->challenge->end_date)->format('d M Y')?></p>
						<p class="sub-text"><strong># of movies: </strong><?=$invite->challenge->no_of_movies?></p>
					</div>
					<div class="right">
						<a href="#" data-id='<?=$invite->id?>' data-type='join' class='btn btn-green'>Join</a>
						<a href="#" data-id='<?=$invite->id?>' data-type='remove' class='btn btn-red'>Remove</a>
					</div>
				</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</div>
<script>
	(function() {
		function actionInvite(e) {
			var target = e.target,
			type = target.getAttribute('data-type'),
			id = target.getAttribute('data-id'),
			invite = target;
			while(!invite.classList.contains('invite')) {
				invite = invite.parentNode;
			}

			if(type == 'join') {
				ajaxRequest({
					url: '/challenges/invites/'+id+'/accept',
					method: 'get',
					success: function(r) {
						var r = JSON.parse(r);
						console.log(r);
						if(r.success) {
							toast('Invite Accepted');
						} else {
							if(message in r) {
								toast(r.message);
							} else {
								toast('Something went wrong');
							}
						}
						if(r.remove) {							
							invite.parentNode.removeChild(invite);
						}
					}
				});
			} else {
				ajaxRequest({
					url: '/challenges/invites/'+id+'/remove',
					method: 'get',
					success: function(r) {
						var r = JSON.parse(r);
						if(r.success) {
							toast('Invite Removed');
							invite.parentNode.removeChild(invite);
						} else {
							if(message in r) {
								toast(r.message);
							} else {
								toast('Something went wrong');
							}
						}
						if(r.remove) {							
							invite.parentNode.removeChild(invite);
						}
					}
				});
			}
		}

		var actionBtns = document.querySelectorAll('[data-type]');
		for(var i = 0, l = actionBtns.length; i < l; i++) {
			actionBtns[i].addEventListener('click',actionInvite);
		}
	})();
</script>
<?php View::make('global/end')->render(); ?>