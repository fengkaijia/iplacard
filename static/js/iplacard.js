/*
 * iPlacard Project
 */

function random_password(length, special) {
	var iteration = 0;
	var password = "";
	var randomNumber;
	if(special === undefined){
		var special = false;
	}
	while(iteration < length){
		randomNumber = (Math.floor((Math.random() * 100)) % 94) + 33;
		if(!special){
			if ((randomNumber >=33) && (randomNumber <=47)) { continue; }
			if ((randomNumber >=58) && (randomNumber <=64)) { continue; }
			if ((randomNumber >=91) && (randomNumber <=96)) { continue; }
			if ((randomNumber >=123) && (randomNumber <=126)) { continue; }
		}
		iteration++;
		password += String.fromCharCode(randomNumber);
	}
	return password;
}

function form_auth_center()
{
	if($(window).height() > ($('.form-auth').height() + $('.navbar').height() + $('#footer').height()))
	{
		var head = ($(window).height() - $('.form-auth').height() - $('#footer').height()) / 4;
		$('.form-auth').css({'margin-top':head});
	}
}