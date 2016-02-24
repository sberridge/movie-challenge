(function() {
	HTMLCollection.prototype.indexOf = function(element) {
		for(var i = 0, l = this.length; i < l; i++) {
			if(this[i] === element) {
				return i;
			}
		}
		return false;
	};
	Element.prototype.slider = function(delay,speed,controls,dir) {
		this.slider = new Slider(this,delay,speed,controls,dir);
	};

	var Slider = function(elem,delay,speed,controls,dir) {
		var delay = delay, speed = speed, elem, that=this, moving = false,buttons,controls = typeof controls !== "undefined" ? controls : true, dir = typeof dir !== "undefined" ? dir : "horizontal";

		function next(index) {
			moving = true;
			var current = elem.getElementsByClassName('active')[0];
			if(index !== "undefined" && index <= current.parentElement.children.length-1 && index >= 0) {

				if(current.parentElement.children.indexOf(current) > index) {
					previous(index);
					return false;
				}  else if(current.parentElement.children.indexOf(current) === index) {
					return false;
				}
				var next = current.parentElement.children[index];
				
			} else {
				var next = current.nextElementSibling;
				if(next === null) {
					next = current.parentElement.children[0];
				}
			}
			if(controls) {
				var index = current.parentElement.children.indexOf(next);
				buttons.getElementsByClassName('current')[0].className = "";
				buttons.children[index].className = "current";
			}
			if(dir == "random") {
				var rand = Math.floor(Math.random()*2);
			}
			var className = "item active "+(dir == "horizontal" ? "right" : dir == "vertical" ? "top" : (dir == "random" ? (rand === 1 ? "right" : "top") : "right"));
			next.className = className;
			
			setTimeout(function() {
				current.className = "item active "+(dir == "horizontal" ? "left" : dir == "vertical" ? "bottom" : (dir == "random" ? (rand === 1 ? "left" : "bottom") : "left"));
				next.className = "item active";
				setTimeout(function() {
					current.className = "item";
					moving = false;
				},speed);
			},100);
		}
		function previous(index) {
			moving = true;
			var current = elem.getElementsByClassName('active')[0];
			if(typeof index !== "undefined" && index <= current.parentElement.children.length-1 && index >= 0) {
				if(current.parentElement.children.indexOf(current) < index) {
					next(index);
					return false;
				}  else if(current.parentElement.children.indexOf(current) === index) {
					return false;
				}
				var previous = current.parentElement.children[index];
				
			} else {
				var previous = current.previousElementSibling;
				if(previous === null) {
					previous = current.parentElement.children[current.parentElement.children.length-1];
				}
			}
			var index = current.parentElement.children.indexOf(previous);
			buttons.getElementsByClassName('current')[0].className = "";
			buttons.children[index].className = "current";
			if(dir == "random") {
				var rand = Math.floor(Math.random()*2);
			}
			var className = "item active "+(dir == "horizontal" ? "left" : (dir == "vertical" ? "bottom" : (dir == "random" ? (rand === 1 ? "left" : "bottom") : "left")));
			previous.className = className
			
			setTimeout(function() {
				current.className = "item active "+(dir == "horizontal" ? "right" : (dir == "vertical" ? "top" : (dir == "random" ? (rand === 1 ? "right" : "top") : "left")));
				previous.className = "item active";
				setTimeout(function() {
					moving = false;
					current.className = "item";
				},speed);
			},100);
		}
		function Timer(callback, delay) {
		    var timerId, start, delay;

		    this.pause = function() {
		        window.clearTimeout(timerId);
		    };

		    var resume = function() {
		        start = new Date();
		        timerId = window.setTimeout(function() {
		            
		            resume();
		            callback();
		        }, delay);
		    };
		    this.resume = resume;
		    this.resume();
		}
		function moveTo(e) {
			e.preventDefault();
			if(moving) return false;
			var target = e.target;
			while(target.nodeName !== "LI") {
				target = target.parentElement;
			}
			var index = target.parentElement.children.indexOf(target);
			next(index);
			
			
		}
		if(!/ ?slider ?/.test(elem.className)) {
			elem.className += " slider";
		}
		
		var ul = document.createElement('ul');
		for(var i = 0, l = elem.children.length; i < l; i++) {
			var li = document.createElement('li');
			var a = document.createElement('a');
			a.addEventListener('click',moveTo);
			a.innerHTML = i;
			li.appendChild(a);
			a.href = "#";
			ul.appendChild(li);
		}
		if(elem.getAttribute("height") !== null) {
			elem.style.height = elem.getAttribute("height");
		}
		if(elem.getAttribute("width") !== null) {
			elem.style.width = elem.getAttribute("width");	
		}
		
		var inner = document.createElement('div');
		inner.className = "slider-inner";
		
		while(elem.children.length > 0) {
			elem.children[0].className = "item";
			elem.children[0].style.transitionDuration = speed/1000+"s";
			elem.children[0].style.webkitTransitionDuration = speed/1000+"s";
			elem.children[0].style.mozTransitionDuration = speed/1000+"s";
			inner.appendChild(elem.children[0]);
		}
		inner.children[0].className = "item active";
		elem.appendChild(inner);
		if(controls) {
			elem.appendChild(ul);
			ul.style.left = (elem.offsetWidth/2-ul.offsetWidth/2)+"px";
			buttons = ul;
			ul.children[0].className = "current";
		}
		
		this.timer = new Timer(next,delay+speed);
		this.pause = function() {
			this.timer.pause();
		};
		this.resume = function() {
			this.timer.resume();
		};
		elem.addEventListener('mouseover',function() {
			that.timer.pause();
		});
		elem.addEventListener('mouseout',function() {
			that.timer.resume();
		});
		if(controls) {
			var nextBtn = document.createElement('a');
			nextBtn.href = "#";
			nextBtn.innerHTML = ">";
			nextBtn.className = "control right";
			nextBtn.addEventListener('click',function(e) {
				e.preventDefault();
				if(moving) return false;
				next();
				
			});
			elem.appendChild(nextBtn);
			nextBtn.style.left = (parseInt(ul.style.left)+ul.offsetWidth+5)+"px";
			var prevBtn = document.createElement('a');
			prevBtn.href = "#";
			prevBtn.innerHTML = "<";
			prevBtn.className = "control left"
			prevBtn.addEventListener('click',function(e) {
				e.preventDefault();
				if(moving) return false;
				previous();
				
			});
			elem.appendChild(prevBtn);
			prevBtn.style.left = (parseInt(ul.style.left)-prevBtn.offsetWidth-5)+"px";
			var top = "5px";
			nextBtn.style.top = top;
			prevBtn.style.top = top;
		}
	};
})();
