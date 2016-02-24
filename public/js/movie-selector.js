(function() {
	var searchBox = document.getElementById('movie_search'),
	searchResults = document.getElementById('search_results'),
	selectedMovies = document.getElementById('selected_movies');
	if(searchBox) {
		var multiple = parseInt(searchBox.getAttribute('data-multiple'));	
	} else {
		return false;
	}
	
	var selectedMovieIds = [],
	yearSearch = document.getElementById('year_search'),
	movies = document.querySelectorAll('[data-type="movie"]');

	for(var i = 0, l = movies.length; i < l; i++) {
		movies[i].addEventListener('click',selectMovie);
	}
	function removeMovie(movie) {
		movie.removeChild(movie.getElementsByTagName('input')[0]);
		searchResults.appendChild(movie);
		movie.removeEventListener('click',removeMovieHndl);
		movie.addEventListener('click',selectMovie);
		selectedMovieIds.splice(selectedMovieIds.indexOf(movie.getAttribute('data-id')),1);
	}
	function removeMovieHndl(e) {
		var target = e.target;
		if(target.nodeName !== 'A') {
			e.preventDefault();
		}
		if(target.nodeName == 'A') {
			return false;
		}
		while(!target.classList.contains('movie')) {
			target = target.parentNode;
		}
		removeMovie(target);
	}
	function selectMovie(e) {
		if(e.target.nodeName !== 'A') {
			e.preventDefault();
		}
		if(e.target.nodeName == 'A') {
			return false;
		}
		var target = e.target;
		while(!target.classList.contains('movie')) {
			target = target.parentNode;
		}
		if(!multiple && selectedMovies.children.length > 0) {
			removeMovie(selectedMovies.children[0]);
		}
		selectedMovies.appendChild(target);
		target.removeEventListener('click',selectMovie);
		target.addEventListener('click',removeMovieHndl);
		selectedMovieIds.push(target.getAttribute('data-id'));
		var input = document.createElement('input');
		input.type = 'hidden';
		input.name = 'movies[]';
		input.value = target.getAttribute('data-info');
		target.appendChild(input);
	}
	var searchXHR = null;
	function doSearch() {
		if(searchBox.value.length < 1) {
			return false;
		}
		searchResults.innerHTML = 'Loading...';
		searchXHR = ajaxRequest({
			url: '/movies/search',
			method: 'post',
			data: {
				q: searchBox.value,
				year: yearSearch.value
			},
			success: function(r) {
				var movies = JSON.parse(r);
				searchResults.innerHTML = '';
				for(var i = 0, l = movies.length; i < l; i++) {
					if(selectedMovieIds.indexOf(movies[i].imdbID) > -1) {
						continue;
					}
					var movieContainer = document.createElement('div');
					movieContainer.classList.add('movie');
					movieContainer.classList.add('item');
					movieContainer.setAttribute('data-id',movies[i].imdbID);
					movieContainer.setAttribute('data-info',encodeURIComponent(JSON.stringify({title:movies[i].Title,id:movies[i].imdbID,releaseYear:movies[i].Year})));
					var left = document.createElement('div');
					left.classList.add('left');
					var h4 = document.createElement('h4');
					h4.innerHTML = movies[i].Title;
					left.appendChild(h4);
					var release = document.createElement('div');
					release.classList.add('sub-text');
					release.innerHTML = 'Released: '+movies[i].Year;
					left.appendChild(release);
					movieContainer.appendChild(left);

					var right = document.createElement('div');
					right.classList.add('right');

					var a = document.createElement('a');
					a.classList.add('imdb-icon');
					a.target = '_blank';
					a.href = 'http://www.imdb.com/title/'+movies[i].imdbID;
					right.appendChild(a);
					movieContainer.appendChild(right);

					searchResults.appendChild(movieContainer);
					movieContainer.addEventListener('click',selectMovie);
					searchXHR = null;
				}
			}
		})
	}
	var searchTimeout = null;
	searchBox.addEventListener('keyup',function(e) {
		e.preventDefault();
		var keycode = e.keyCode;
		if(searchTimeout !== null) {
			clearTimeout(searchTimeout);
			searchTimeout = null;
		}
		if(searchXHR !== null) {
			searchXHR.abort();
		}
		if(searchBox.value.length > 1) {
			searchTimeout = setTimeout(function() {
				doSearch();
			},500);
		} else {
			searchResults.innerHTML = '';
		}
		return false;
	});

	yearSearch.addEventListener('keyup',function(e) {
		e.preventDefault();
		var keycode = e.keyCode;
		if(searchTimeout !== null) {
			clearTimeout(searchTimeout);
			searchTimeout = null;
		}
		if(searchXHR !== null) {
			searchXHR.abort();
		}
		if(searchBox.value.length > 1) {
			searchTimeout = setTimeout(function() {
				doSearch();
			},500);
		} else {
			searchResults.innerHTML = '';
		}
		return false;
	});
	searchBox.addEventListener('keydown',function(e) {
		var keycode = e.keyCode;
		if(keycode == 13) {
			e.preventDefault();
		}
		return false;
	});
	searchBox.addEventListener('keypress',function(e) {
		var keycode = e.keyCode;
		if(keycode == 13) {
			e.preventDefault();
		}
		return false;
	});

})();