Effect.Center = function(element) {
	try {
		element = $(element);
	}

	catch(e) {
		return;
	}

	var my_width  = 0;
	var my_height = 0;

	if (typeof(window.innerWidth) == 'number') {
		my_width  = window.innerWidth;
		my_height = window.innerHeight;
	} else if (document.documentElement && (document.documentElement.clientWidth || document.documentElement.clientHeight)) {
		my_width  = document.documentElement.clientWidth;
		my_height = document.documentElement.clientHeight;
	} else if (document.body && (document.body.clientWidth || document.body.clientHeight)) {
		my_width  = document.body.clientWidth;
		my_height = document.body.clientHeight;
	}

	var scrollY = 0;

	if (document.documentElement && document.documentElement.scrollTop) {
		scrollY = document.documentElement.scrollTop;
	} else if (document.body && document.body.scrollTop) {
		scrollY = document.body.scrollTop;
	} else if (window.pageYOffset) {
		scrollY = window.pageYOffset;
	} else if (window.scrollY) {
		scrollY = window.scrollY;
	}

	var elementDimensions = Element.getDimensions(element);

	element.style.width = elementDimensions.width+'px';

	var setX = ((my_width - elementDimensions.width)/2) - 12;
	var setY = ((my_height - elementDimensions.height)/2) + scrollY;

	setX = (setX < 0)?0:setX;
	setY = (setY < 0)?0:setY;

	element.style.left = setX+'px';
	element.style.top  = setY+'px';
}
