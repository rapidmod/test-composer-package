<?php
namespace Rapidmod;
use \Rapidmod\Data\Model;
use \PDO;
use \Rapidmod\Mysql\Select;

class Mysql extends Model {




	protected $current_database;
	protected $query_type = NULL;
	protected $stmt = NULL;
	protected $_table_exists = NULL;
	protected $dbh = NULL;
	protected $return_key_overrride;


	private $_query_index = 0;
	private $_query_log = array();
	protected $whereClauses = array();
	protected $checkKeys = array();
	protected $queryParams = array();
	protected $_select = "*";




	public function dataSchema(){
		return \Rapidmod\Mysql\Schema::init();
	}

	public function delete(){
		$key = $this->return_param();
		if($this->_get($key)){
			$query = "DELETE FROM {$this->current_table} WHERE {$key} = :a";
			$this->prepare($query);
			$this->execute(array(":a"=>$this->_get($key)));
		}
		return $this;
	}

	public function pdo(){
		return \Rapidmod\Mysql\Connection::init();
	}

	public function __construct($database_name = "database"){
		//parent::__construct();
		if(empty($database_name)){
			$database_name = "database";
		}

		$this->current_database = $this->pdo()->setDataBase($database_name);
		$this->_database = $this->pdo()->_get("current_database");


		if(!$this->dbh){$this->dbh = $this->pdo()->_get("connection");}
		$table = $this->current_table;
		if($this->setTable($this->current_table)){
			return true;
		}
		return $this->setTable($table);

	}


	public function execute($params = array()){
		if(!$this->dbh){$this->dbh = $this->pdo()->_get("connection");}
		if(!$this->stmt){
			die(get_called_class().": No statemnt");
			return false;
		}
		$key = $this->return_param();

		$continue_try = "";
		if(isset($this->return_key_overrride) && !empty($this->return_key_overrride)){
			$this->return_key_overrride = false;
			$this->return_key = false;
		}


		foreach($params as $k => $v){
			if(!isset($return_set)){
				if($k[0] === ":"){
					$key = ":".$key;
					$return_set = true;
				}
			}
		}
		$executed = false;


		$queryLog = $this->_query_log[$this->_query_index]["query"];
		$debug = true;
		$debug = false;
		if($debug && is_array($params) && !empty($params)){
			foreach ($params as $k => $v){
				$queryLog = str_replace($k,"'".$v."'",$queryLog);
			}
			echo $queryLog."<br>";
		}
//die("done");

		if(stristr($this->query_type,"update")){
			//die(get_called_class()." ".$key." ".$queryLog);
		}
		try{
			if(is_array($this->stmt)){die("done is array");}
			$this->stmt->execute($params);
			$executed = true;
		}catch (Exception $e){
			$continue_try = true;
		}
		if($continue_try){
			$continue_try = false;
			try{
				$this->stmt->execute($params);
			}catch (Exception $e){
				$continue_try = true;
			}
			if(!$continue_try){
				$executed = true;
			}
		}

		/**
		 * @todo something with this catch and die
		 */
		if($continue_try){
			$continue_try = false;
			try{
				$this->stmt->execute($params);
			}catch (Exception $e){
				$continue_try = true;
				error_log(get_called_class()." QUERY FAILED: The query failed 3 times. ".$e->getMessage()." --- STATEMENT: ".json_encode($this->stmt));
				die("<pre>".print_r($this->stmt,1));
				// echo "<pre>".print_r(debug_print_backtrace(),1)."</pre>";
			}
			if(!$continue_try){
				$executed = true;
			}
		}
		if($executed){
			switch($this->query_type){
				case "insert" : return $this->last_insert_id();
			}
			if(isset($params[$key])){
				return $params[$key];
			}else{
				return true;
			}
		}

		return false;
	}


	public function fetchAll(){
		return $this->
		returnArray("SELECT * FROM `".$this->current_table."`",array());
	}

	function insert($query,$params){
		if(!$this->dbh){$this->dbh = $this->pdo()->_get("connection");}
		$this->prepare($query);
		$this->execute($params);
		return $this->dbh->lastInsertId();
	}


	function last_insert_id(){
		if(!$this->dbh){return false;}
		return $this->dbh->lastInsertId();
	}

	/**
	 *
	 * Name load
	 * @return * @param $value
	 * @param bool $key
	 * @return $this|bool
	 *
	 * @author RapidMod.com
	 * @author 813.330.0522
	 *
	 * @todo re implement to check if we are loading by a tring so we can use
	 * the LIKE operator
	 *
	 */
	function load($value,$key = false,$operator = "="){
		if(!$this->current_table){
			return false;
		}
		$this->reset();
		if(!$key){
			$key = $this->return_param();
		}

		if(!$key){return false;}
		$params[":value"] = $value;

		$query = "SELECT * FROM {$this->current_table} WHERE {$key} {$operator} :value";
		if(strstr($this->current_table,"face")){
			//die(str_replace(":value","'{$params[":value"]}'",$query));
		}

		$Select = new Select();
		$result = $Select->fetch($query,$params);
		if(is_array($result) && !empty($result) && empty($result[$key])){
			$result = $result[0];
		}
		if(!empty($result)){
			$this->buildObject($result);

		}
		return $this;
	}

	public function loadBy($params){
		$this->reset();
		if(!empty($params)){
			$_fields = $this->tableFields();
			$stmnt = new Select($this->current_table);
			foreach ($params as $k => $v){
				if(in_array($k,$_fields)){
					$stmnt->where($k,$v);
				}
			}
			$result = $stmnt->fetch();
			if(is_array($result) && !empty($result) && empty($result[$this->return_param()])){
				$result = $result[0];
			}
			if(!empty($result)){
				$this->buildObject($result);

			}
		}
		return $this;
	}

	function prepare($query){
		if(!$this->dbh){$this->dbh = $this->pdo()->_get("connection");}
		if(!$this->dbh){return false;}
		$check = substr( trim($query) , 0 ,15 );
		if(stristr($check,"insert")){
			$this->query_type = "insert";
		}elseif(stristr($check,"update")){
			$this->query_type = "update";
		}elseif(stristr($check,"select")){
			$this->query_type = "select";
		}elseif(stristr($check,"delete")){
			$this->query_type = "delete";
		}else{
			$this->query_type = "unknown";
		}
		//$this->_query_index++;
		$this->_query_log[$this->_query_index]["query"] = $query;
		//echo $query."<hr>";
		$this->stmt = $this->dbh->prepare($query,array(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true));
		return $this;
	}

	function returnArray($query,$params=array()){
		if(!$this->dbh){$this->dbh = $this->pdo()->_get("connection");}
		if(!$this->dbh){return false;}
		$this->prepare($query);
		$this->execute($params);
		$x = array();
//echo get_called_class()."<pre>".print_r($this->stmt->fetch(PDO::FETCH_ASSOC),1)."</pre>";
		while($row = $this->stmt->fetch(PDO::FETCH_ASSOC)){
			//echo get_called_class()."<pre>".print_r($row,1)."</pre>";
			$x[] = $row;
		}
		return $x;
	}

	function returnAssoc($query,$params=array()){
		if(!$this->dbh){$this->dbh = $this->pdo()->_get("connection");}
		$this->prepare($query);
		//echo $query." : ".json_encode($params)."<hr>";
		$this->execute($params);
		return $this->stmt->fetch(PDO::FETCH_ASSOC);
	}

	function return_param(){
		return $this->dataSchema()->return_param($this->current_table);

	}
	function save(){

		if($this->current_table && !empty($this->toArray()) && \Rapidmod\Application::config()->_get("dev_mode")){
			//$Track = new TableRcoreTableColumnTracker( );
			//$Track->trackAll($this->current_table , $this->toArray());
		}

		$key_param = $this->return_param();
		if(!$key_param){ $key_param = "id"; }
		if($key_param && $this->_get($key_param) ) {

			$where[$key_param] = $this->_get($key_param);
			$id = $this->updateTable($this->toArray() , $where );
		}else{
			$params = $this->toArray();
			if($params){
				if(empty($params[$key_param])){
					unset($params[$key_param]);
				}
			}
			$id =  $this->insertIntoTable($params);
		}

		$this->reset();
		if($id){
			$this->load($id);
		}
		return $this;

	}
	public function setDatabase($dataBase = false){
		$this->pdo()->setDatabase($dataBase);
		$this->current_database = $this->pdo()->_get("current_database");
		return $this->current_database;
	}
	public function setTable($table){
		if(!empty($table)){
			$this->_table_exists = $this->dataSchema()->table_exists($this->current_table);
			$this->current_table = $table;
		}
		return $this->current_table;
	}

	/**
	 * @param $table
	 * @param $params
	 * @return mixed
	 */
	function insertIntoTable($params){
		//$params["created"] = date("Y-m-d H:i:s");
		$_fields = $this->dataSchema()->getFields($this->current_table);
		if($_fields){
			//$params = $this->SetTimestamps($params,$this->current_table);
			if(is_object($params)){
				//simplest way to convert to array
				$params = json_decode(json_encode($params), true);
			}

			foreach($_fields as $field){
				if(isset($params[$field]) && !is_array($params[$field])){
					$keys[] = "`{$this->current_table}`.`{$field}`";
					$_xkey = ":".$field;
					$values[] = $_xkey;
					$key_values[$_xkey] = $params[$field];
				}

			}
			$params = false;
			$query = "INSERT INTO `".$this->current_table."` "
				."(".implode(",",$keys).")"
				."VALUES"
				."(".implode(",",$values).")"
			;



			$this->prepare($query);
			$query = false;
			$this->execute($key_values);
			$key_values = false;
			return $this->dbh->lastInsertId();
		}else{
				die(get_called_class().": No table schema");
		}
	}



	/**
	 * @param $table
	 * @param $params
	 * @param $where
	 */
	function UpdateTable($params,$where){

		if(is_object($params)){
			//simplest way to convert to array
			$params = json_decode(json_encode($params), true);
		}
		if(isset($params["last_update"])){
			unset($params["last_update"]);
		}
		if(isset($params["created"])){
			unset($params["created"]);
		}

		$_fields = $this->dataSchema()->getFields($this->current_table);
		$params["last_update"] = date("Y-m-d H:i:s");

		$where_fields = false;
		if(is_array($_fields) && !empty($_fields)){
			foreach($_fields as $field){

				if(isset($params[$field])){
					if(is_array( $params[$field])){continue;}
					$key_values[":{$field}"] = $params[$field];
					if(!isset($where[$field])){
						$query_fields[] = "`".$field."` = :".$field;
					}else{
						$where_keys[":{$field}"] = $params[$field];
						if(!$where_fields){
							$where_fields = " `".$field."` = :".$field;
						}else{
							$where_fields .= " AND `".$field."` = :".$field;
						}
					}
				}


			}
			if($where_fields){

				$query = "SELECT * FROM `".$this->current_table."` WHERE ".$where_fields;
				$check = $this->returnAssoc($query,$where_keys);
				if(!$check){
					//die(get_called_class().": could not update the record, it did not exist");
					error_log(get_called_class().": could not update the record, it did not exist");
				}
				$changed = false;
				foreach($_fields as $field){
					if(isset($params[$field])){
						if($check[$field] != $params[$field]){
							$changed = true;
							continue;
						}
					}

				}
				if($changed){

					$query = "UPDATE `".$this->current_table."` SET ".implode(",",$query_fields)." WHERE ".$where_fields;

					$this->prepare($query);
					if($this->execute($key_values)){
						return $params[$this->return_param()];
					}else{
						die(get_called_class().": execute returned false");
						error_log(get_called_class().": execute returned false");
					}
				}else{
					//die(get_called_class()." not necasary");
					return $check[$this->return_param($this->current_table)];
				}
			}else{
				$this->error = "Missing the where statement";
				die(get_called_class().": Missing the where statement");
			}
		}else{
			error_log(get_called_class().": The fields where not set");
			die(get_called_class().": The fields where not set");
		}
	}


	/**
	 *
	 * @param unknown $table
	 *
	 * @todo re configure or delete this
	 * updated, probably delete feb 15 2016
	 */
	private function _install_table($table){
		return;
		$config = RcoreConfig::init();
		$packageDirectory = $config->getPackageDirectory();
		if(!empty($packageDirectory)){

			$tableFile = $packageDirectory."sql".DIRECTORY_SEPARATOR."table".DIRECTORY_SEPARATOR
				."install".DIRECTORY_SEPARATOR.$table.".sql";
			clearstatcache();

			if(file_exists($tableFile)){
				$query = str_replace("<TABLENAME>",$table,file_get_contents($tableFile));
				$this->prepare($query);
				$this->execute(array());

				return $table;
			}
		}


		//die($dir);

	}


	public function fetch($params = array()){
		//if(empty($this->_select))
		$query = "SELECT {$this->_select} FROM `{$this->current_table}`";

		if(!empty($params)){
			foreach($params as $key => $value){
				$this->addWhereClause($key,$value);
			}

		}
		if(!empty($this->whereClauses)){
			$query .= " WHERE ".implode(" AND ",$this->whereClauses);
		}

		//echo $query."--".json_encode($this->queryParams)."<br>";
		$data = $this->returnArray($query,$this->queryParams);
		$this->whereClauses = array();
		$this->checkKeys = array();
		$this->queryParams = array();
		return $data;
	}

	public function fetchObjects($params = array()){
		//if(empty($this->_select))
		$query = "SELECT {$this->_select} FROM `{$this->current_table}`";

		if(!empty($params)){
			foreach($params as $key => $value){
				$this->addWhereClause($key,$value);
			}

		}
		if(!empty($this->whereClauses)){
			$query .= " WHERE ".implode(" AND ",$this->whereClauses);
		}

		$Select = new RcorePdoQuerySelect();
		$data = $Select->fetchIntoObject($query,$this->queryParams,get_called_class());
		$this->whereClauses = array();
		$this->checkKeys = array();
		$this->queryParams = array();
		return $data;
	}

	public function fetchInIds($ids){
		if(!is_array($ids) || empty($ids)){return array();}
		$query = "SELECT * FROM `{$this->current_table}` WHERE `id` IN (".implode(",",$ids).");";
		return $this->returnArray($query,array());
	}

	public function fetchNotInIds($ids){
		if(!is_array($ids) || empty($ids)){return array();}
		$query = "SELECT * FROM `{$this->current_table}` WHERE `id` NOT IN (".implode(",",$ids).");";
		return $this->returnArray($query,array());
	}



	public function addWhereClause($key,$value,$operator = "="){
		if(empty($key) || in_array($key,$this->checkKeys)){return $this;}
		if($operator === "="){
			if(!is_numeric($value) && !is_bool($value) && !is_null($value)){
				$operator = "LIKE";
			}
		}

		$this->checkKeys[] = $key;

		$this->queryParams[":".$key] = $value;
		$this->whereClauses[] = "`{$key}` {$operator} :{$key}";
	}

}
?>