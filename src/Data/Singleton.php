<?php
/**
 * @author RapidMod.com
 * @author 813.330.0522
 */


namespace Rapidmod\Data;
use \Rapidmod\Data\Model;

class Singleton extends Model{
    const VERSION = "0.0.0.1";
    private static $_rcore_instances = array();
    protected $_called_class;

    protected function __construct() {
        $this->_called_class = get_called_class();
    }

    protected function __clone() {}

    public function __wakeup(){
        throw new Exception("Cannot unserialize singleton");
    }

    public static function init()
    {
        $class = md5(get_called_class());
        //$class = get_called_class();
        if (!isset(self::$_rcore_instances[$class])) {
            self::$_rcore_instances[$class] = new static();
        }
        return self::$_rcore_instances[$class];
    }

    public static function singleton(){
        return self::init();
    }
}