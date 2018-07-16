<?php
/**
 * @author RapidMod.com
 * @author 813.330.0522
 */


namespace Rapidmod\Model\Post;


class Params extends \Rapidmod\Model\Alias{

	public function __construct() {
		parent::__construct();
	}

	public function __get ( $key ){
		return $_POST[$key];
	}

	/**
	 *
	 * Name _set
	 * @param $key
	 * @param $value
	 * @return $this
	 *
	 * @author RapidMod.com
	 * @author 813.330.0522
	 * @WTF magic methods blow balls.......
	 */
	public function _set($key,$value){
		return $_POST[$key] = $value;
	}


}