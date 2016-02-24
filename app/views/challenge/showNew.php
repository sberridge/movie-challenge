<?php View::make('global/start')->render(); ?>

<div class="box center">
	<div class="inner-box">
		<h2>New Challenge</h2>
		<form action="<?=Url::route('createChallenge')?>" class='challenge-form' method='post'>
			<input type="text" value='<?=array_key_exists('title', $previous_input) ? $previous_input['title'] : ''?>' name='title' placeholder='Challenge Title'>
			<textarea name="details" value='<?=array_key_exists('details', $previous_input) ? $previous_input['details'] : ''?>' placeholder='Details...' class='full-width'></textarea>
			<input type="date" value='<?=array_key_exists('start_date', $previous_input) && !empty($previous_input['start_date']) ? Dater::make($previous_input['start_date'])->format('Y-m-d') : ''?>' name='start_date' placeholder='Start Date'>
			<input type="date" name='finish_date' value='<?=array_key_exists('finish_date', $previous_input) && !empty($previous_input['finish_date']) ? Dater::make($previous_input['finish_date'])->format('Y-m-d') : ''?>' placeholder='Finish Date'>
			<label for="public" class="checkbox"><span>Make public?</span> <input type="checkbox" name='public' id='public'></label>
			<label for="daily" class="checkbox"><span>One movie a day?</span> <input type="checkbox" name='daily' id='daily'></label>
			<input type="number" value='<?=array_key_exists('no_of_movies', $previous_input) ? $previous_input['no_of_movies'] : ''?>' name='no_of_movies' id='no_of_movies' placeholder='Target Number of Movies'>
			<label for="movie_search">Select Required Movies (Optional)</label>
			<div class="movie-selector">
				<div class="search">
					<input type="text" data-multiple='1' id='movie_search' autocomplete='off' placeholder='Movie Title'>
					<input type="text" id='year_search' autocomplete='off' placeholder='Release Year'>
				</div>				
				<div class="selected-movies item-list movie-list" id="selected_movies">
					
				</div>
				<div class="search-results item-list movie-list" id="search_results">
					
				</div>
			</div>
			<label for="user_search">Send Invites (Optional)</label>
			<div class="user-selector">
				<div class="search">
					<input type="text" data-multiple='1' id='user_search' autocomplete='off' placeholder='Username'>
				</div>				
				<div class="selected-users item-list" id="selected_users">
					
				</div>
				<div class="search-results item-list" id="user_search_results">
					
				</div>
			</div>
			<input type="submit" class='btn' value='Create'>
		</form>
	</div>
</div>
<script src='/js/user-selector.js'></script>
<script src='/js/movie-selector.js'></script>
<?php View::make('global/end')->render(); ?>