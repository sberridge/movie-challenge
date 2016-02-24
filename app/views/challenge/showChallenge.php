<?php View::make('global/start')->render(); ?>
<div class="box center">
	<div class="inner-box">
		<h2><?=$challenge->name?></h2>
		<?php if($challengeCompleted): ?>
		<div class="challenge-status-banner">
			<div class="completed">
				<p>Challenge Complete</p>
			</div>
		</div>
		<?php elseif($challenge->isFinished() && $inChallenge): ?>
		<div class="challenge-status-banner">
			<div class="failed">
				<p>Challenge Failed</p>
			</div>
		</div>
		<?php endif; ?>
		<?php if($inChallenge): ?>
		<div class="challenge-progress">
			<span class="progress-text">Progress: <?=$noOfEntries?>/<?=$challenge->no_of_movies?></span>
			<div data-percent='<?=($noOfEntries / $challenge->no_of_movies) * 100?>' class="progress-bar">
				
			</div>
			
		</div>
		<?php endif; ?>
		<p><strong>Created by: </strong><?=$challenge->owner->username?></p>
		<p><strong>From: </strong><?=Dater::make($challenge->start_date)->format('d M Y')?> <strong>To: </strong><?=Dater::make($challenge->end_date)->format('d M Y')?></p>
		<p><strong>Target: </strong><?=$challenge->no_of_movies?> movies</p>
		<p><?=nl2br($challenge->details)?></p>
		<h3>Required Movies</h3>
		<?php if(count($challenge->movies) == 0): ?>
			<p>There aren't any required movies, you can watch whatever you want to complete the challenge.</p>
		<?php else: ?>
			<div class="item-list movie-list">
				<?php foreach($challenge->movies as $movie): ?>
				<div class="item movie"  data-type='<?=!in_array($movie->imdb_id,$requiredWatched) ? 'movie' : 'watched'?>' data-info='<?=urlencode(json_encode(array('id'=>$movie->imdb_id,'title'=>$movie->title,'releaseYear'=>$movie->release_year)))?>' data-id='<?=$movie->imdb_id?>'>
					<div class="left">
						<h4><?=$movie->imdbMovie->title?></h4>
						<div class="sub-text">
							<?=$movie->imdbMovie->release_year?>
						</div>
					</div>
					<div class="right">
						<a href="http://www.imdb.com/title/<?=$movie->imdb_id?>" target='_blank' class="imdb-icon"></a>
					</div>
				</div>
				<?php endforeach; ?>
			</div>	
		<?php endif; ?>
		<?php if($challenge->isFinished()): ?>
			<h3>Top 5 Movies</h3>
			<?php if(count($top5Movies) === 0): ?>
				<p>It doesn't look like anyone took part in the challenge :(</p>
			<?php else: ?>
				<div class="item-list movie-list">
					<?php foreach($top5Movies as $movie): ?>
						<div class="item movie">
							<div class="left">
								<h4><?=$movie['movie']->title?></h4>
								<p class="sub-text"><?=$movie['movie']->release_year?></p>
								<p class="sub-text">Watched <?=$movie['times_watched']?> <?=$movie['times_watched'] > 1 ? 'times' : 'time'?></p>
							</div>
							<div class="right">
							<a href="http://www.imdb.com/title/<?=$movie['movie']->imdb_id?>" target='_blank' class="imdb-icon"></a>
						</div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		<?php endif; ?>
		<?php if($allowedEntry): ?>
			<h3>Enter<?=$challenge->daily ? ' Todays' : ''?> Movie</h3>
			<form action="<?=Url::route('addChallengeEntry',array($challenge->id))?>" method='post' class='challenge-form'>
				<div class="movie-selector">
					<div class="search">
						<input type="text" data-multiple='0' id='movie_search' autocomplete='off' placeholder='Movie Title'>
						<input type="text" id='year_search' autocomplete='off' placeholder='Release Year'>
					</div>				
					<div class="selected-movies item-list movie-list" id="selected_movies">
						
					</div>
					<div class="search-results item-list movie-list" id="search_results">
						
					</div>
				</div>
				<input type="submit" value='Enter Movie' class="btn">
			</form>
		<?php endif; ?>
		<?php if(Auth::user()->id === $challenge->user_id && $challenge->start_date >= Dater::make()->format('Y-m-d')): ?>
		<h3>Send Invites</h3>
		<form action="<?=Url::route('sendChallengeInvites',array($challenge->id))?>" method='post' class='challenge-form'>
			<div class="user-selector">
				<div class="search">
					<input type="text" data-multiple='1' data-exclude-challenge='<?=$challenge->id?>' id='user_search' autocomplete='off' placeholder='Username'>
				</div>				
				<div class="selected-users item-list" id="selected_users">
					
				</div>
				<div class="search-results item-list" id="user_search_results">
					
				</div>
			</div>
			<input type="submit" class='btn' value='Send Invites'>
		</form>
		<?php endif ?>
		<h3>Participants</h3>
		<?php if(!$inChallenge && $challenge->public && $challenge->start_date >= Dater::make()->format('Y-m-d')): ?>
		<a href="<?=Url::route('joinChallenge',array($challenge->id))?>" class="btn">Join Challenge</a>
		<?php endif; ?>
		<div class="item-list user-list">
		<?php foreach($participants as $participant): ?>
			<div class="item <?=$participant['challenge_completed'] ? 'challenge-completed' : ($challenge->isFinished() ? 'challenge-failed' : '')?> user">
				<a href="<?=Url::route('showChallengeUser',array($challenge->slug,$participant['user']->slug))?>"><div class="left">
						<h4><?=$participant['user']->username?></h4>
						<p class="sub-text"><?=$participant['user']->fullName?></p>
						<p class="sub-text"><strong>Completed: </strong><?=count($participant['user']->challengeEntries)?>/<?=$challenge->no_of_movies?></p>
					</div>
				</a>
			</div>
		<?php endforeach; ?>
		</div>		
	</div>
</div>
<?php if($inChallenge): ?>
<div class="box center">
	<div class="inner-box">
		<h2>Discuss</h2>
		
		<div class="comment-area">
			<?php if($totalPages > 1): ?>
				<a href="#" class='load-comments' data-challenge-id='<?=$challenge->id?>' data-total-pages='<?=$totalPages?>' data-next-page='2'>View Older Comments</a>
			<?php endif; ?>
			<div class="comments">
				<?php foreach($comments as $comment): ?>
				<div class="comment">
					<header>
						<h5><?=$comment->author->username?></h5>
					</header>
					<div class="content">
						<p><?=$comment->content?></p>
					</div>
					<footer>
						<time datetime='<?=$comment->date?>'><?=Dater::make($comment->date)->format('d M Y H:i')?></time>
					</footer>
					<div class="reply-area">
						<?php if(!array_key_exists($comment->id, $childComments)): ?>
						<a href='#' class='reply-btn'>Reply...</a>
						<?php else: ?>
							<?php if($childCommentCount[$comment->id] > 1): ?>
							<a href="#" class='load-replies' data-challenge-id='<?=$challenge->id?>' data-parent-id='<?=$comment->id?>'>View <?=$childCommentCount[$comment->id]-1?> more <?=$childCommentCount[$comment->id]-1 == 1 ? 'reply' : 'replies'?></a>
							<?php endif; ?>
							<div class="comments">
								<?php foreach($childComments[$comment->id] as $reply): ?>
								<div class="comment">
									<header>
										<h5><?=$reply->author->username?></h5>
									</header>
									<div class="content">
										<p><?=$reply->content?></p>
									</div>
									<footer>
										<time datetime='<?=$reply->date?>'><?=Dater::make($reply->date)->format('d M Y H:i')?></time>
									</footer>
								</div>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>

						<form action="<?=Url::route('addChallengeCommentReply',array($challenge->id,$comment->id))?>" class='<?=!array_key_exists($comment->id,$childComments) ? 'hidden' : ''?>' method='post'>
							<?=
								Input::renderInput(array(
									'name'=>'comment',
									'placeholder'=>'Reply...',
									'type'=>'text',
									'class'=>'full-width'
								))
							?>
						</form>
					</div>
				</div>
				<?php endforeach; ?>
			</div>
			<form action="<?=Url::route('addChallengeComment',array($challenge->id))?>" method='post'>
				<?=
					Input::renderInput(array(
						'name'=>'comment',
						'placeholder'=>'Comment...',
						'type'=>'text',
						'class'=>'full-width'
					))
				?>
			</form>
		</div>
	</div>
</div>
<?php endif; ?>
<script>
	(function() {
		var progress = document.querySelector('[data-percent]');
		if(progress) {
			setTimeout(function() {
				progress.style.width = progress.getAttribute('data-percent')+'%';
			},50);
		}
			
	})();
	(function() {
		function showReply(e) {
			e.preventDefault();
			var target = e.target,
			form = target.nextElementSibling;
			form.classList.remove('hidden');
			form.children[0].focus();
			target.parentNode.removeChild(target);
		}

		function loadReplies(e) {
			e.preventDefault();
			var target = e.target,
			commentId = target.getAttribute('data-parent-id'),
			challengeId = target.getAttribute('data-challenge-id');
			ajaxRequest({
				url: '/challenges/'+challengeId+'/comments/'+commentId+'/replies',
				method: 'get',
				success: function(r) {
					var replies = JSON.parse(r);
					var commentContainer = target.nextElementSibling,
					comment = commentContainer.children[0];
					for(var i = 0, l = replies.length; i < l; i++) {
						var newComment = comment.cloneNode(1);
						newComment.getElementsByTagName('h5')[0].innerHTML = replies[i].author;
						newComment.getElementsByTagName('p')[0].innerHTML = replies[i].content;
						newComment.getElementsByTagName('time')[0].innerHTML = replies[i].date_formatted;
						newComment.getElementsByTagName('time')[0].setAttribute('datetime',replies[i].date);
						commentContainer.appendChild(newComment);

					}
					comment.parentNode.removeChild(comment);
					target.parentNode.removeChild(target);
				}
			});
		}

		(function() {
			
			var replyBtns = document.getElementsByClassName('reply-btn');
			for(var i = 0, l = replyBtns.length; i < l; i++) {
				replyBtns[i].addEventListener('click',showReply);
			}
		})();
		(function() {

			

			var loadReplyBtns = document.getElementsByClassName('load-replies');
			for(var i = 0, l = loadReplyBtns.length; i < l; i++) {
				loadReplyBtns[i].addEventListener('click',loadReplies);
			}
		})();
		(function() {

			var loadCommentsBtn = document.getElementsByClassName('load-comments')[0];
			if(!loadCommentsBtn) {
				return false;
			}
			var challengeId = loadCommentsBtn.getAttribute('data-challenge-id'),
			totalPages = loadCommentsBtn.getAttribute('data-total-pages'),
			commentArea = loadCommentsBtn.nextElementSibling;
			loadCommentsBtn.addEventListener('click',function(e) {
				e.preventDefault();
				var page = loadCommentsBtn.getAttribute('data-next-page');
				ajaxRequest({
					url: '/challenges/'+challengeId+'/comments?p='+page,
					method: 'get',
					success: function(r) {
						var comments = JSON.parse(r);
						page++;
						loadCommentsBtn.setAttribute('data-next-page',page);
						if(page > totalPages) {
							loadCommentsBtn.parentNode.removeChild(loadCommentsBtn);
						}
						for(var i = 0, l = comments.length; i < l; i++) {
							var comment = comments[i];
							var commentDiv = document.createElement('div'),
							header = document.createElement('header'),
							h5 = document.createElement('h5'),
							contentDiv = document.createElement('div'),
							replyArea = document.createElement('div'),
							p = document.createElement('p'),
							time = document.createElement('time'),
							footer = document.createElement('footer');
							commentDiv.classList.add('comment');
							contentDiv.classList.add('content');
							h5.innerHTML = comment.author;
							header.appendChild(h5);
							commentDiv.appendChild(header);
							p.innerHTML = comment.content;
							contentDiv.appendChild(p);
							commentDiv.appendChild(contentDiv);
							time.innerHTML = comment.date_formatted;
							time.setAttribute('datetime',comment.date);
							footer.appendChild(time);
							commentDiv.appendChild(footer);
							commentArea.insertBefore(commentDiv,commentArea.children[0]);

							replyArea.classList.add('reply-area');
							var form = document.createElement('form');
							form.action = '/challenges/'+challengeId+'/comments/'+comment.id+'/reply';
							form.method = 'post';
							var input = document.createElement('input');
							input.type = 'text';
							input.classList.add('full-width');
							input.setAttribute('placeholder','Reply...');
							input.name = 'comment';
							form.appendChild(input);
							if(comment.reply_count == 0) {
								var replyBtn = document.createElement('a');
								replyBtn.classList.add('reply-btn');
								replyBtn.href = '#';
								replyBtn.innerHTML = 'Reply...';
								replyArea.appendChild(replyBtn);
								replyBtn.addEventListener('click',showReply);
								replyArea.appendChild(replyBtn);
								form.classList.add('hidden');
							} else {
								if(comment.reply_count > 1) {
									var moreReplyBtn = document.createElement('a');
									moreReplyBtn.classList.add('load-replies');
									moreReplyBtn.innerHTML = 'View '+(comment.reply_count-1)+' more '+(comment.reply_count > 2 ? 'replies' : 'reply');
									moreReplyBtn.setAttribute('data-challenge-id',challengeId);
									moreReplyBtn.setAttribute('data-parent-id',comment.id);
									moreReplyBtn.href = '#';
									moreReplyBtn.addEventListener('click',loadReplies);
									replyArea.appendChild(moreReplyBtn);
									var repliesArea = document.createElement('div');
									repliesArea.classList.add('comments');
									var reply = comment.reply;
									var replyDiv = document.createElement('div'),
									header = document.createElement('header'),
									h5 = document.createElement('h5'),
									contentDiv = document.createElement('div'),
									p = document.createElement('p'),
									time = document.createElement('time'),
									footer = document.createElement('footer');
									replyDiv.classList.add('comment');
									contentDiv.classList.add('content');
									h5.innerHTML = reply.author;
									header.appendChild(h5);
									replyDiv.appendChild(header);
									p.innerHTML = reply.content;
									contentDiv.appendChild(p);
									replyDiv.appendChild(contentDiv);
									time.innerHTML = reply.date_formatted;
									time.setAttribute('datetime',reply.date);
									footer.appendChild(time);
									replyDiv.appendChild(footer);
									repliesArea.appendChild(replyDiv);
									replyArea.appendChild(repliesArea);
								}
							}
							replyArea.appendChild(form);
							commentDiv.appendChild(replyArea);

						}

					}
				});
			});
		})();
	})();
</script>
<script src='/js/movie-selector.js'></script>
<?php if(Auth::user()->id === $challenge->user_id): ?>
<script src='/js/user-selector.js'></script>
<?php endif; ?>
<?php View::make('global/end')->render(); ?>