<?php
/**
 * @author RapidMod.com
 * @author 813.330.0522
 */


namespace Rapidmod;


class Dev {

    public static function printVar($var, $name = NULL){
	    if(is_null($name)){
		    if(is_object($var)){
			    $name = get_class($var);
		    }else{
			    $name = "Data";
		    }
	    }

        echo "{$name}<pre>".print_r($var,1)."</pre>";
    }

	public static function errorLog($var,$name = NULL){
		if(is_null($name)){
			if(is_object($var)){
				$name = get_class($var);
			}else{
				$name = "Data";
			}
		}
		error_log("{$name} *** ".print_r($var,1));
	}

}