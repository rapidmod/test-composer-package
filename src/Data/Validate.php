<?php
/**
 * @author RapidMod.com
 * @author 813.330.0522
 */


namespace Rapidmod\Data;


class Validate
{
	public function domain($domain){
		return filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME);
	}

	public function ip($ip){
		return filter_var($ip, FILTER_VALIDATE_IP);
	}

	public function url($url){
		return filter_var($url, FILTER_VALIDATE_URL);
	}


}