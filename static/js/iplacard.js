/*
 * iPlacard Project
 */

function loader(item, size, background) {
	if($('meta[name=css3-support]').attr("content") === 'no-css3') {
		if(item === '')
			return '';
		return false;
	}
	
	if(background === undefined) {
		var background = '#ffffff';
	}
	
	var random = random_string(4);
	$('#loader-' + random +' .circle').css('background', background, 'important');
	
	var string = '<div id="loader-' + random + '" class="loader" style="width: ' + size + ' !important; height: ' + size + ' !important;"><div class="circle"></div><div class="circle"></div><div class="circle"></div><div class="circle"></div><div class="circle"></div></div>';
	
	if(item !== '') {
		var width = $(item).css('width');
		var height = $(item).css('height');

		$(item).css('width', width, 'important');
		$(item).css('height', height, 'important');
		
		$(item).html(string);
	}	
	return string;
}

function random_string(length, special) {
	var iteration = 0;
	var password = "";
	var randomNumber;
	if(special === undefined) {
		var special = false;
	}
	while(iteration < length) {
		randomNumber = (Math.floor((Math.random() * 100)) % 94) + 33;
		if(!special) {
			if ((randomNumber >= 33) && (randomNumber <= 47)) { continue; }
			if ((randomNumber >= 58) && (randomNumber <= 64)) { continue; }
			if ((randomNumber >= 91) && (randomNumber <= 96)) { continue; }
			if ((randomNumber >= 123) && (randomNumber <= 126)) { continue; }
		}
		iteration++;
		password += String.fromCharCode(randomNumber);
	}
	return password;
}

function form_auth_center() {
	if($(window).height() > ($('.form-auth').height() + $('.navbar').height() + $('#footer').height())) {
		var head = ($(window).height() - $('.form-auth').height() - $('#footer').height()) / 4;
		$('.form-auth').css({'margin-top':head});
	}
}