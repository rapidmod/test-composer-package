<?php
/**
 * @author RapidMod.com
 * @author 813.330.0522
 */


namespace Rapidmod\Mysql;
use \Rapidmod\Data\Singleton;
use \stdClass;

class Schema extends Singleton {
    private $_PDO;

    protected $return_param;

    protected $_database = array();
    protected $_schema;
    protected $_tables;
    protected $_table_fields;
    protected $_primary_key;
    protected $_unique_key;
    protected $_return_param;
    protected $obj;


    protected function pdo(){
        if(empty($this->_PDO)){
            $this->_PDO = new \Rapidmod\Mysql();
        }
        return $this->_PDO;
    }

    /**
     * getSchema($table)
     *
     * @param $table
     * @return $object; $key = field, $value = field object
     */
    private function buildSchema($table){
        if(empty($table)){return false;}
        if(isset($this->_schema->{$this->_get("current_database")}->{$table})){
            return $this->_schema->{$this->_get("current_database")}->{$table};
        }else{
            $x = $this->pdo()->returnArray("DESC ".$table,array());
            if(!empty($x)){
                foreach($x as $k){
                    $this->_table_fields->{$this->_get("current_database")}->{$table}[] = $k["Field"];
                    $this->setTableFieldSchema($table,$k["Field"],"type",$k["Type"]);
                    $this->setTableFieldSchema($table,$k["Field"],"null",$k["Null"]);
                    $this->setTableFieldSchema($table,$k["Field"],"key",$k["Key"]);
                    $this->setTableFieldSchema($table,$k["Field"],"default",$k["Default"]);
                    $this->setTableFieldSchema($table,$k["Field"],"extra",$k["Extra"]);
                    if($k["Key"]==="UNI"){
                        $this->setUniqueKey($table,$k["Field"]);
                    }
                    if($k["Key"]==="PRI"){
                        $this->setPrimaryKey($table,$k["Field"]);
                    }
                }
                if(isset($this->_schema->{$this->_get("current_database")}->{$table})){
                    return $this->_schema->{$this->_get("current_database")}->{$table};
                    exit;
                }
            }
            return false;
        }
    }

    public function getFields($table){
        if(isset($this->_table_fields->{$this->_get("current_database")}->{$table})){
            return $this->_table_fields->{$this->_get("current_database")}->{$table};
        }else{
            return false;
        }
    }

    public function index($database){
        $this->_set("current_database",$database);
        if(in_array($this->_get("current_database"),$this->_database)){
            return $this->schemaArray();
        }
        //$cache = $this->loadCache(md5(get_called_class()));
        $cache = array();
        if(empty($cache)){
            $this->_database[] = $database;
            $this->indexTables();
            //$this->saveCache(md5(get_called_class()),$this->schemaArray());
        }else{
            foreach($cache as $key => $value){
                $this->{$key} = $value;
            }
        }
        return $this->schemaArray();

    }

    private function indexTables(){
        if(!$this->_get("current_database")){return false;}
        if(isset($this->_tables->{$this->_get("current_database")})){
            return $this->_tables->{$this->_get("current_database")};
        }else{
            if(!$this->_tables){
                $this->_tables = new stdClass();
            }
            $this->_tables->{$this->_get("current_database")} = array();
            $x = $this->pdo()->returnArray("SHOW TABLES",array());

            if(empty($x)){return false;}
            else{
                foreach($x as $t){
                    if(is_array($t)){
                        $t = $t["Tables_in_".$this->_get("current_database")];
                    }
                    $this->buildSchema($t);
                    $this->return_param($t);
                    $tables[] = $t;
                }
                $this->_tables->{$this->_get("current_database")} = $tables;
                unset($x,$t,$tables);
                return $this->_tables->{$this->_get("current_database")};
            }
        }
    }

    public function return_param($table){
        if(!$this->_get("current_database")){return false;}
        if(empty($table)){return false;}
        if(!isset($this->_return_param)){
            $this->_return_param = new stdClass();
        }
        if(!isset($this->_return_param->{$this->_get("current_database")})){
            $this->_return_param->{$this->_get("current_database")} = new stdClass();
        }

        if(isset($this->_return_param->{$this->_get("current_database")}->{$table})){
            return $this->_return_param->{$this->_get("current_database")}->{$table};
        }elseif(isset($this->_primary_key->{$this->_get("current_database")}->{$table})){
            $this->_return_param->{$this->_get("current_database")}->{$table}
                = $this->_primary_key->{$this->_get("current_database")}->{$table};
        }elseif (is_array($this->_database) && !empty($this->_database)){
            foreach ($this->_database as $db){
                if(isset($this->_unique_key->{$db}->{$table})){
                    $this->_return_param->{$this->_get("current_database")}->{$table}
                        = $this->_unique_key->{$this->_get("current_database")}->{$table};
                    continue;
                }
            }
        }elseif(!array($this->_database) && isset($this->_unique_key->{$this->_database}->{$table})){
            $this->_return_param->{$this->_get("current_database")}->{$table}
                = $this->_unique_key->{$this->_get("current_database")}->{$table};
        }

        if(empty( $this->_return_param->{$this->_get("current_database")}->{$table})){
            $this->_return_param->{$this->_get("current_database")}->{$table} = "id";
        }
        return $this->_return_param->{$this->_get("current_database")}->{$table};
    }

    /**
     * @param $table
     * @param $column
     * @param $setting (exists,type,null,key,default,extra)
     * @param $value
     * @return bool|string
     */
    private function setTableFieldSchema($table,$column,$setting,$value){
        //echo "setTableFieldSchema($table,$column,$setting,$value)<br>";
        $table = trim($table);
        $column = trim($column);
        $setting = trim($setting);
        if(empty($table) || empty($column) || empty($setting)){
            die("empty bro");
            return false;
        }else{
            if(!isset($this->_schema)){
                $this->_schema = new stdClass();
            }
            if(!isset($this->_schema->{$this->_get("current_database")})){
                $this->_schema->{$this->_get("current_database")} = new stdClass();
            }
            if(!isset($this->_schema->{$this->_get("current_database")}->{$table})){
                $this->_schema->{$this->_get("current_database")}->{$table} = new stdClass();
            }
            if(!isset($this->_schema->{$this->_get("current_database")}->{$table}->{$column})){
                $this->_schema->{$this->_get("current_database")}->{$table}->{$column} = new stdClass();
            }
            $this->_schema->{$this->_get("current_database")}->{$table}->{$column}->{$setting} = $value;

            return $this->_schema->{$this->_get("current_database")}->{$table}->{$column}->{$setting};
        }
    }

    /**
     * @param $table //the table this field belongs too
     * @param $field //the field that is unique
     * @return bool|string
     */
    private function setUniqueKey($table,$field){
        $table = (string)trim($table);
        $field = (string)trim($field);
        if(empty($field) || empty($table)){
            return false;
        }else{
            if(!isset($this->_unique_key)){
                $this->_unique_key = new stdClass();
            }
            if(!isset($this->_unique_key->{$this->_get("current_database")})){
                $this->_unique_key->{$this->_get("current_database")} = new stdClass();
            }
            if(!isset($this->_unique_key->{$this->_get("current_database")}->{$table})){
                $this->_unique_key->{$this->_get("current_database")}->{$table} = new stdClass();
            }
            return $this->_unique_key
                ->{$this->_get("current_database")}
                ->{$table} = $field;
        }
    }

    /**
     * @param $table //the table this field belongs too
     * @param $field //the primary field name
     * @return bool|string
     */
    private function setPrimaryKey($table,$field){
        $table = (string)trim($table);
        $field = (string)trim($field);
        if(empty($field) || empty($table)){
            return false;
        }else{
            if(!isset($this->_primary_key)){
                $this->_primary_key = new stdClass();
            }
            if(!isset($this->_primary_key->{$this->_get("current_database")})){
                $this->_primary_key->{$this->_get("current_database")} = new stdClass();
            }
            return $this->_primary_key
                ->{$this->_get("current_database")}
                ->{$table} = $field;
        }
    }

    public function schemaArray(){
        return array(
            "object" => $this->obj,
            "_database" => $this->_database,
            "_schema" => $this->_schema,
            "_tables" => $this->_tables,
            "_table_fields" => $this->_table_fields,
            "_primary_key" => $this->_primary_key,
            "_unique_key" => $this->_unique_key,
            "_return_param" => $this->_return_param
        );
    }

    public function tableSchema($name,$database=""){
    	if(!$database){$database = $this->_get("current_database");}
    	if($name && $database){
		    return $this->_schema->{$database}->{$name};
	    }
    	return false;
    }

    public function table_exists($table){
        $this->indexTables();
        if(
            !isset($this->_tables->{$this->_get("current_database")})
            ||
            !is_array($this->_tables->{$this->_get("current_database")})
            ||
            !in_array($table,$this->_tables->{$this->_get("current_database")})
        ){
            return false;
        }else{
            return true;
        }

    }

    public function showDatabase(){
        return $this->pdo()->returnArray("SHOW DATABASES",array());

    }
}