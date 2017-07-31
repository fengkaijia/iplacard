/*
 * iPlacard
 * @author Kaijia Feng <fengkaijia@gmail.com>
 * @copyright (c) 2013, Kaijia Feng
 * @since 2.0
 */

function loader(item, size, background) {
	if($('meta[name=css3-support]').attr("content") === 'no-css3') {
		if(item === '')
			return '';
		return false;
	}
	
	if(size === undefined) {
		var size = $(item).css('line-height');
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

/**
 * @link https://stackoverflow.com/questions/2477862/jquery-password-generator
 */
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

function nav_menu_top()
{
	if($(window).width() < 992) {
		if($('.menu-tabs').html() !== '') {
			$('.menu-pills').html($('.menu-tabs').html());
			$('.menu-pills .nav-menu').removeClass('nav-tabs');
			$('.menu-pills .nav-menu').addClass('nav-pills');
			$('.menu-tabs').empty();
		}
	}
	else
	{
		if($('.menu-tabs').html() === '') {
			$('.menu-tabs').html($('.menu-pills').html());
			$('.menu-tabs .nav-menu').removeClass('nav-pills');
			$('.menu-tabs .nav-menu').addClass('nav-tabs');
			$('.menu-pills').empty();
		}
		
		$('.nav-menu').css({'top': $('.page-header').height() + parseInt($('.page-header').css('padding-bottom').replace('px','')) - $('.nav-menu').height()});
	}
}

function nav_menu_switch()
{
	var url = document.location.toString();
	if(url.match('#')) {
		$('.nav-menu a[href=#'+url.split('#')[1]+']').tab('show');
		$('.nav-menu a[href=#'+url.split('#')[1]+']').tab('show');
	}

	$('.nav-menu a').on('shown', function (e) {
		window.location.hash = e.target.hash;
	});
}