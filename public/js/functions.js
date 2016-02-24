function ajaxRequest(params) {
	var xhr = new XMLHttpRequest();
	xhr.open(params.method,params.url,true);
	xhr.setRequestHeader('HTTP_X_REQUESTED_WITH','xmlhttprequest');
	var paramString = '';
	if(params.method.toLowerCase() == 'post') {
		xhr.setRequestHeader('Content-type','application/x-www-form-urlencoded');
		
		if(typeof params.data === 'object') {
			for(key in params.data) {
				paramString += key+'='+params.data[key]+'&';
			}
			paramString = paramString.slice(0,-1);
		}
	}
	if(typeof params.progress !== 'undefined') {
		xhr.addEventListener('progress',params.progress);
	}
	xhr.addEventListener('readystatechange',function() {
		if(xhr.readyState == 4) {
			if(xhr.status == 200) {
				if(typeof params.pass_thru !== "undefined") {
					params.success(xhr.responseText,params.pass_thru);
				} else {
					params.success(xhr.responseText);
				}
			} else {
				if(typeof params.error !== 'undefined') {
					params.error(xhr.responseText);
				}
			}
		}
	});
	xhr.send(paramString);
	return xhr;
}

function toast(message) {
	var toast = document.createElement('div');
	toast.classList.add('toast');
	toast.style.opacity = 0;
	toast.innerHTML = message;
	document.getElementById('toast_container').appendChild(toast);
	setTimeout(function() {
		toast.style.opacity = 1;
	},10);
	setTimeout(function() {
		toast.style.opacity = 0;
		setTimeout(function() {
			toast.parentNode.removeChild(toast);
		},400);
	},2000);
}