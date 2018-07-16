<?php
/**
 * @author RapidMod.com
 * @author 813.330.0522
 */


namespace Rapidmod\Mysql;
use \Rapidmod\Data\Singleton;
use \Rapidmod\Mysql\Schema;
use \PDO;

class Connection extends Singleton{
    private $_handles = NULL;
    private $_DataSchema = NULL;
    private $obj;

    public function dataSchema(){
        return Schema::init();
    }

    public function _get($key){
        switch($key){
            case "connection" : return $this->connect();break;
	        case "current_database" : return parent::_get($key); break;
            default: break;
        }
        if(!$this->obj){
            $this->obj = new \stdClass();
        }
        if(!isset($this->obj->{$this->_get("current_database")}->{$key})){
            return false;
        }else{
            return $this->obj->{$this->_get("current_database")}->{$key};
        }

    }

    public function _set($key,$value){
        if(empty($key)){
            return false;
        }
        if($key === "current_database"){
	        return parent::_set($key,$value);

        }else{
            if(!$this->obj){
                $this->obj = new \stdClass();
            }
            if($this->_get("current_database")){
                if(!isset($this->obj->{$this->_get("current_database")})){
                    $this->obj->{$this->_get("current_database")} = new \stdClass();
                }
                if(!isset($this->obj->{$this->_get("current_database")}->{$key})){$this->obj->{$this->_get("current_database")}->{$key} =  $value;}
                else{$this->obj->{$this->_get("current_database")}->{$key} = $value;}
                // echo $this->_get("current_database")." => ".$key." => ".$value."<br>";
                return $this->obj->{$this->_get("current_database")}->{$key} = trim($value);
            }
        }
    }


    public function close(){
        if(
        !empty($this->_handles->{$this->_get("current_database")})
        ){
            unset($this->_handles->{$this->_get("current_database")});
        }

    }

    private function connect(){

        if(!isset($this->_handles)){
            $this->_handles = new \stdClass();
        }
        if(
        !empty($this->_handles->{$this->_get("current_database")})
        ){
            return $this->_handles->{$this->_get("current_database")};
        }else{
            try{
                $this->_handles->{$this->_get("current_database")} =
                    new PDO(
                        'mysql:host='.$this->_get("host")
                        .'; dbname='.$this->_get("db_name")
                        , $this->_get("db_user")
                        , $this->_get("password")
                    );
                $this->_handles->{$this->_get("current_database")}
                    ->setAttribute( PDO::ERRMODE_EXCEPTION,PDO::MYSQL_ATTR_USE_BUFFERED_QUERY);
                return $this->_handles->{$this->_get("current_database")};
            }catch (Exception $e){
                die("couldnt connect".$e->getMessage());
            }

        }
    }

    public function connection(){
    	return $this->connect();
    }

    /**
     * @param $name
     * @return bool|pdo handle
     */
    public function setDatabase($name=""){
        if(empty($name)){$name = "database";}
        $name = (string)trim($name);

        if(empty($name)){return $this->_get("current_database");}
        if(
            isset($this->_handles->{$name})
            &&
            !empty($this->_handles->{$name})
        ){
            $this->_set("current_database",$name);
            return $this->_get("current_database");
            exit;
        }

        $database = array();
//        include("Config".DIRECTORY_SEPARATOR.$name.".php");
        return $this->setConnectionInfo(\Rapidmod\Application::config()->{$name});

    }

    public function addConnection($data){
    	return $this->setConnectionInfo($data);
    }

    /**
     * * @param $arrayParams
     * ${name}
     * @return bool
     *
     * @author RapidMod.com
     * @author 813.330.0522
     *
     */
    public function setConnectionInfo($arrayParams=array()){

        if(empty($arrayParams)){return $this->_get("current_database");}
        if(
            isset($this->_handles->{$arrayParams["name"]})
            &&
            !empty($this->_handles->{$arrayParams["name"]})
        ){
            $this->_set("current_database",$arrayParams["name"]);
            return $this->_get("current_database");

        }
       // echo json_encode($arrayParams)."-----setConnectionInfo()<br>";
        $this->_set("current_database",$arrayParams["name"]);
        $this->_set("db_type",$arrayParams["type"]);
        $this->_set("db_name",$arrayParams["name"]);
        $this->_set("db_user",$arrayParams["user"]);
        $this->_set("password",$arrayParams["password"]);
        $this->_set("host",$arrayParams["host"]);
        $this->_set("port",$arrayParams["port"]);
        //die("setting");
        // die("1234<pre>".print_r($this->toArray(),1));
        $this->connect();

        $Schema = Schema::init();
        $Schema->index($this->_get("current_database"));
	  //  $this->_handles->{$arrayParams["name"]} = $this->toArray();

        return $this->_get("current_database");
    }
}