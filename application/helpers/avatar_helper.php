<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * CodeIgniter Gravatar Helper
 *
 * @author		Jason M Horwitz
 * @copyright	Copyright (c) 2012, Sekati LLC.
 * @license		http://www.opensource.org/licenses/mit-license.php
 * @version		1.1.1
 * @usage 		$autoload['helper'] = array('gravatar');
 * @example 	gravatar( 'jason@sekati.com' );	 			// returns gravatar img tag
 * 				gravatar_profile( 'jason@sekati.com' ); 	// returns URL
 * 				gravatar_qr( 'jason@sekati.com' ); 			// returns QR img tag
 */

// ------------------------------------------------------------------------
// GRAVATAR HELPERS

/**
 * Get either a Gravatar URL or complete image tag for a specified email address.
 *
 * @param string 	$email The email address
 * @param string 	$s Size in pixels, defaults to 80px [ 1 - 512 ]
 * @param boolean 	$img True to return a complete IMG tag False for just the URL 
 * @param string 	$d Default imageset to use [ 404 | mm | identicon | monsterid | wavatar ]
 * @param string 	$r Maximum rating (inclusive) [ g | pg | r | x ]
 * @param array 	$atts Optional, additional key/value attributes to include in the IMG tag
 * @return 			String containing either just a URL or a complete image tag
 */
if ( ! function_exists('gravatar'))
{ 
	function gravatar( $email, $s = 80, $img = true, $d = 'identicon', $r = 'x', $atts = array() )
	{
		$url = ( isset($_SERVER['HTTPS']) ) ? 'https://secure.' : 'http://www.';
		$url .= 'gravatar.com/avatar/';
		$url .= md5( strtolower( trim( $email ) ) );
		$url .= "?s=$s&d=$d&r=$r";
		if ( $img )
		{
			$url = '<img src="' . $url . '"';
			foreach ( $atts as $key => $val )
				$url .= ' ' . $key . '="' . $val . '"';
			$url .= ' />';
		}
		return $url;
	}
} 

/**
 * Get a Gravatar profile URL from a primary gravatar email address.
 *
 * @param string 	$email The email address
 * @return 			String containing the users gravatar profile URL.
 */
if ( ! function_exists('gravatar_profile'))
{ 
	function gravatar_profile( $email )
	{
		$url = ( isset($_SERVER['HTTPS']) ) ? 'https://secure.' : 'http://www.';
		$url .= 'gravatar.com/';
		$url .= md5( strtolower( trim( $email ) ) );
		return $url;
	}
} 

/**
 * Get either a Gravatar QR Code URL or complete image tag from a primary gravatar email address.
 *
 * @param string 	$email The email address
 * @param string 	$s Size in pixels, defaults to 80px [ 1 - 512 ]
 * @param boolean 	$img True to return a complete IMG tag False for just the URL 
 * @param array 	$atts Optional, additional key/value attributes to include in the IMG tag
 * @return 			String containing either just a URL or a complete image tag
 */
if ( ! function_exists('gravatar_qr'))
{ 
	function gravatar_qr( $email, $s = 80, $img = true, $atts = array() )
	{
		$url = gravatar_profile($email);
		$url .= ".qr?s=$s";
		if ( $img )
		{
			$url = '<img src="' . $url . '"';
			foreach ( $atts as $key => $val )
				$url .= ' ' . $key . '="' . $val . '"';
			$url .= ' />';
		}
		return $url;
	}
} 

/* End of file avatar_helper.php */
/* Location: ./application/helpers/avatar_helper.php */