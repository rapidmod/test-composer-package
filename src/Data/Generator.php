<?php
/**
 * @author RapidMod.com
 * @author 813.330.0522
 */


namespace Rapidmod\Data;


class Generator {

	public static $defaultStringLength = 16;


	protected static $rfcSecondaryKeys = array(8,9,"a","b");

	/**
	 *
	 * Name guid
	 * @return
	 * @return string  36-character string "XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX"
	 *  blocks of 8, 4, 4, 4 and 12 hex digits
	 * @static static
	 * @throws
	 *
	 * @author RapidMod.com
	 * @author 813.330.0522
	 *
	 */
	public static function guid(){
		$c = self::randomString(16);
		$h = "-";
		return 	substr($c, 0, 8).$h
		          .substr($c, 8, 4).$h
		          .substr($c,12, 4).$h
		          .substr($c,16, 4).$h
		          .substr($c,20,12);
	}

	public static function randomString($length){
		$length = (int)$length;
		if( $length < 1 ){ $length = (int)self::$defaultStringLength; }
		return bin2hex( random_bytes( $length ) );
		//return md5(bin2hex( random_bytes( $length ) ));
	}

	/**
	 *
	 * Name uuid
	 * @return string  formatted for RFC 4122 36-character string "XXXXXXXX-XXXX-4XXX-(8,9,a,b)XXX-XXXXXXXXXXXX" see link below
	 *  blocks of 8, 4, 4, 4 and 12 hex digits
	 * @static static
	 * @throws
	 *
	 * @author RapidMod.com
	 * @author 813.330.0522
	 *
	 * @link  https://www.cryptosys.net/pki/uuid-rfc4122.html
	 * @link http://www.ietf.org/rfc/rfc4122.txt
	 */
	public static function uuid(){
		$c = self::randomString(16);
		$c[12] = 4;
		$x = random_int(0,3);
		$c[16] = self::$rfcSecondaryKeys[$x];
		$h = chr(45);
		return 	substr($c, 0, 8).$h
		          .substr($c, 8, 4).$h
		          .substr($c,12, 4).$h
		          .substr($c,16, 4).$h
		          .substr($c,20,12);
	}

}