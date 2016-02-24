(function() {
	var dateInputs = document.querySelectorAll('[type="date"]');
	for(var i = 0, l = dateInputs.length; i < l; i++) {
		dateInputs[i].type = 'text';
		if(dateInputs[i].value !== "") {
			var date = dateInputs[i].value.split('-');
			var newDate = date[2]+'-'+date[1]+'-'+date[0];
			dateInputs[i].value = newDate;
		}
		dateInputs[i].setAttribute('autocomplete','off');
		var format = 'DD-MM-YYYY';
		if(dateInputs[i].hasAttribute('data-format')) {
			format = dateInputs[i].getAttribute('data-format');
		}
		new Pikaday({
			field: dateInputs[i],
			format: format
		});
	}
})();

(function() {

	var userMenuBtn = document.getElementById('user_menu_btn'),
	userMenu = document.getElementById('user_menu');

	setTimeout(function() {
		var width = userMenu.offsetWidth;
		if(width < 250) {
			width = 250;
		}
		userMenu.height = userMenu.offsetHeight+'px';
		userMenu.width = width+'px';
		userMenu.children[0].style.height = userMenu.height;
		userMenu.children[0].style.width = userMenu.width;
		userMenu.style.width = '0px';
		userMenu.style.height = '0px';
		userMenu.classList.add('animate');
	},0);

	userMenuBtn.addEventListener('click',function(e) {
		e.preventDefault;
		if(userMenu.style.width === '0px') {
			userMenu.style.width = userMenu.width;
			userMenu.style.height = userMenu.height;
		} else {
			userMenu.style.width = '0px';
			userMenu.style.height = '0px';
		}
		
	})
})();

(function() {
	function changePage(e) {
		var target = e.target;
		var parent = target;
		while(!parent.getAttribute('data-pagename')) {
			parent = parent.parentNode;
		}
		var a = parent.getElementsByTagName('a')[0];
		var link = a.href;
		var origLink = link;
		var name = parent.getAttribute('data-pagename');
		var reg = new RegExp(name+'=[0-9]+');
		link = link.replace(reg,name+'='+target.value);
		a.href = link;
		a.click();
		a.href = origLink;
	}
	function clickHndl(e) {
		var target = e.target;
		while(target.nodeName !== 'LI') {
			target = target.parentNode;
		}
		
		var previous = target.previousElementSibling.getElementsByTagName('a')[0];
		var next = target.nextElementSibling.getElementsByTagName('a')[0];
		var startPage = parseInt(previous.getAttribute('data-page'));
		var endPage = parseInt(next.getAttribute('data-page'));
		var select = document.createElement('select');
		var opt = document.createElement('option');
		opt.value = '';
		opt.innerHTML = '';
		select.appendChild(opt);
		for(var i = startPage+1, l = endPage; i < l; i++) {
			var opt = document.createElement('option');
			opt.value = i;
			opt.innerHTML = 'Page '+i;
			select.appendChild(opt);
		}
		select.addEventListener('change',changePage);
		target.innerHTML = '';
		target.appendChild(select);
	}
	var paginateDivs = document.getElementsByClassName('paginate-divider');
	for(var i = 0, l = paginateDivs.length; i < l; i++) {
		paginateDivs[i].addEventListener('click',clickHndl);
	}
})();
