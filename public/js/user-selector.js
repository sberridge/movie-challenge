(function() {
	var searchBox = document.getElementById('user_search'),
	searchResults = document.getElementById('user_search_results'),
	selectedUsers = document.getElementById('selected_users');
	if(!searchBox) {
		return false;
	}
	var multiple = parseInt(searchBox.getAttribute('data-multiple')),
	excludeChallenge = searchBox.getAttribute('data-exclude-challenge'),
	selectedUserIds = [];
	function removeUser(user) {
		user.removeChild(user.getElementsByTagName('input')[0]);
		searchResults.appendChild(user);
		user.removeEventListener('click',removeUserHndl);
		user.addEventListener('click',selectUser);
		selectedUserIds.splice(selectedUserIds.indexOf(user.getAttribute('data-id')),1);
	}
	function removeUserHndl(e) {
		var target = e.target;
		if(target.nodeName !== 'A') {
			e.preventDefault();
		}
		if(target.nodeName == 'A') {
			return false;
		}
		while(!target.classList.contains('user')) {
			target = target.parentNode;
		}
		removeUser(target);
	}
	function selectUser(e) {
		if(e.target.nodeName !== 'A') {
			e.preventDefault();
		}
		if(e.target.nodeName == 'A') {
			return false;
		}
		var target = e.target;
		while(!target.classList.contains('user')) {
			target = target.parentNode;
		}
		if(!multiple && selectedUsers.children.length > 0) {
			removeUser(selectedUsers.children[0]);
		}
		selectedUsers.appendChild(target);
		target.removeEventListener('click',selectUser);
		target.addEventListener('click',removeUserHndl);
		selectedUserIds.push(parseInt(target.getAttribute('data-id')));
		var input = document.createElement('input');
		input.type = 'hidden';
		input.name = 'users[]';
		input.value = target.getAttribute('data-id');
		target.appendChild(input);
	}
	var searchXHR = null;
	function doSearch() {
		if(searchBox.value.length < 1) {
			return false;
		}
		searchResults.innerHTML = 'Loading...';
		var url = '/users/search';
		if(excludeChallenge) {
			url += '?exclude_challenge='+excludeChallenge;
		} 
		searchXHR = ajaxRequest({
			url: url,
			method: 'post',
			data: {
				q: searchBox.value
			},
			success: function(r) {
				var users = JSON.parse(r);
				searchResults.innerHTML = '';
				for(var i = 0, l = users.length; i < l; i++) {
					if(selectedUserIds.indexOf(users[i].id) > -1) {
						continue;
					}
					var userContainer = document.createElement('div');
					userContainer.classList.add('user');
					userContainer.classList.add('item');
					userContainer.setAttribute('data-id',users[i].id);
					var left = document.createElement('div');
					left.classList.add('left');
					var h4 = document.createElement('h4');
					h4.innerHTML = users[i].username;
					left.appendChild(h4);
					var release = document.createElement('div');
					release.classList.add('sub-text');
					release.innerHTML = users[i].first_name+' '+users[i].surname;
					left.appendChild(release);
					userContainer.appendChild(left);

					

					searchResults.appendChild(userContainer);
					userContainer.addEventListener('click',selectUser);
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