<?php
namespace Rapidmod\Mysql;
use \Rapidmod\Mysql;
use \Rapidmod\Application;
use \Rapidmod\Mysql\Schema;
use \PDO;

class Query extends Mysql{

	public $table = NULL;
	public $database = "database";
	public $query_log = array();
	public $query_index = 0;
	public $return_param = NULL;
	public $fetch_style = PDO::FETCH_ASSOC;
	public $result_count = 0;
	public $model_name = "\\Rapidmod\\Data\\Model";

	// for keeping track of in paramaters
	public $in_increment = 1;

	private $_DataSchema = NULL;
	private $_LASTINSERTID = NULL;
	private $_PDO = NULL;




	public function execute($query = "",$params = array()) {
		if(empty($query)){
			$query = $this->getQuery();
			$params = $this->getParams();
		}
		if(empty($params)){$params = "";}
		if(is_array($query)){
			\Rapidmod\Dev::printVar($this);
		}
		if(!$this->dbh){$this->dbh = $this->pdo()->_get("connection");}
			echo "<h1>{$query}</h1>";
		$stmnt = $this->dbh->prepare($query,array(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true));
		$stmnt->execute($params);
		//	echo "<pre>{$query}</pre>";
		return $stmnt;
	}

	public function fetch($query = "",$params = array()){
		$this->result_count = 0;
		if(empty($query)){
			$query = $this->getQuery();
			$params = $this->getParams();
		}


		$x = array();
		if(!$this->dbh){$this->dbh = $this->pdo()->_get("connection");}
		$pdo = $this->dbh->prepare($query,array(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true));
		if(isset($params[0]) && is_array($params[0])){$params = $params[0];}
		$stmnt =
			$pdo->execute(
				$params
			);
	//	\Rapidmod\Dev::printVar($stmnt);
		if($stmnt){
			while($row = $pdo->fetch($this->fetch_style)){
				$x[] = $row; $this->result_count++;
			}
		}
		return $x;
	}

	public function fetchIntoObject($query = "",$params = array(),$modelName=""){
		$this->result_count = 0;
		if(empty($query)){
			$query = $this->getQuery();
			$params = $this->getParams();
		}
		$debug = true;
		$debug = false;
		if($debug && is_array($params) && !empty($params)){
			$queryLog = $query;
			foreach ($params as $k => $v){
				$queryLog = str_replace($k,"'".$v."'",$queryLog);
			}
			echo $queryLog."<br>";
		}

		$stmnt = $this->execute($query,$params);

		if(empty($modelName)){$modelName = $this->model_name;}
		$results = $stmnt->fetchAll(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, $modelName);
		if(!empty($results)){
			$this->result_count = count($results);
			return $results;
		}
		return array();
	}

	/**
	 * Name where
	 * @param string|array $key
	 * @param string|null $value
	 * @param string $operator (=|LIKE)
	 * @param int $wildcards (0|1|2|3) off,both,right,left
	 * @return $this
	 *
	 * @author RapidMod.com
	 * @author 813.330.0522
	 *
	 * @TODO: add IN and NOT IN to the operators
	 *
	 */
	public function where($key,$value=NULL,$operator = "=",$wildcards = 0){

		if(is_array($key)){
			if(isset($key[0])){

				foreach ($key as $a){
					if(!isset($a["wildcards"])){ $a["wildcards"] = 0;}
					if(!isset($a["operator"])){ $a["operator"] = "=";}
					extract($a);
					$this->_doFromWhere($key,$value,$operator,$wildcards);
				}
			}else{

				foreach ($key as $k=> $v){
					$this->_doFromWhere($k,$v,$operator,$wildcards);
				}
			}
		}elseif(!is_array($key) && is_string($key)){
			if(!is_null($value)){
				return $this->_doFromWhere($key,$value,$operator,$wildcards);
			}
			$this->whereClauses[] = $key;
			return $this;
		}
		return $this;

	}

	/**
	 * Name in
	 * @return * @param $table
	 * @param $key
	 * @param $values
	 * @return $this
	 *
	 * @author RapidMod.com
	 * @author 813.330.0522
	 *
	 */
	public function in($key,$values){
		if(!is_array($values) && !empty($values)){
			$values = array($values);
		}
		if(empty($values)){return $this;}
		$varKeys = array();
		$i=1;
		foreach ($values as $val){
			$varKeys[] = $this->addParam("{$key}{$i}",$val);
			$i++;

		}
		if(!empty($varKeys)){
			$this->whereClauses[] = "{$key} IN (".implode(",",$varKeys).")";
		}

		return $this;
	}

	public function lastInsertId(){
		return $this->_LASTINSERTID;
	}

	/**
	 * Name not_in
	 * @param $table
	 * @param $key
	 * @param $values
	 * @return $this
	 *
	 * @author RapidMod.com
	 * @author 813.330.0522
	 *
	 */
	public function not_in($key,$values){
		if($this->inCheckKeys($key)){return $this;}
		if(!is_array($values) && !empty($values)){
			$values = array($values);
		}
		if(empty($values)){return $this;}
		$varKeys = array();
		$i=1;
		foreach ($values as $val){
			$varKeys[] = $this->addParam("{$key}{$i}",$val);
			$i++;

		}
		if(!empty($varKeys)){
			$this->whereClauses[] = "{$key} NOT IN (".implode(",",$varKeys).")";
		}

		return $this;
	}

	private function _doFromWhere($key,$value,$operator = "=",$wildcards = 0){
		if(empty($key) || $this->inCheckKeys($key)){return $this;}
		if($operator === "in"){
			return $this->in($key,$value);
		}elseif($operator === "not in"){
			return $this->not_in($key,$value);
		}

		//make the query smarter by automatically selecting like whe it can?
		if($operator === "="){
			if(!is_numeric($value) && !is_bool($value) && !is_null($value) && is_string($value)){
				$operator = "LIKE";
			}
		}
		//add wildcards to the value
		switch ($wildcards){
			case 1: $value = "%{$value}%"; break;
			case 2: $value = "{$value}%"; break;
			case 3: $value = "%{$value}"; break;
			default:break;
		}
		$this->whereClauses[] = "{$key} {$operator} {$this->addParam($key,$value)}";
		return $this;
	}

	public function addParam($key,$value){
		if($this->inCheckKeys($key)){return "";}
		$this->checkKeys[] = $key;
		$paramKey = ":p{$this->in_increment}";
		$this->in_increment++;
		$this->queryParams[$paramKey] = $value;
		return $paramKey;
	}

	protected function inCheckKeys($key){
		return in_array($key,$this->checkKeys);
	}

	public function whereString($string){
		$this->whereClauses[] = $string;
		return $this;
	}

	public function printQuery($query = "",$params = array()){
		if(empty($query)){
			$query = $this->getQuery();
			$params = $this->getParams();
		}
		$queryLog = $query;
		if(is_array($params) && !empty($params)){
			foreach ($params as $k => $v){
				$queryLog = str_replace($k,"'".$v."'",$queryLog);
			}
		}
		echo $queryLog;
	}

	function return_param(){
		if(!is_null($this->return_param)){return $this->return_param;}
		$this->return_param = "id";
		if($this->table){
			$this->return_param = $this->dataSchema()->return_param($this->table);
		}
		return $this->return_param;
	}

	public function setFetchStyle($style,$options = array()){
		switch (strtolower($style)){
			case "obj":
			case "object" : $this->fetch_style = PDO::FETCH_OBJ;break;
			case "bound" : $this->fetch_style = PDO::FETCH_BOUND;break;
			case "num" : $this->fetch_style = PDO::FETCH_NUM;break;
			case "lazy": $this->fetch_style = PDO::FETCH_LAZY;break;
			case "named": $this->fetch_style = PDO::FETCH_NAMED;break;
			case "both": $this->fetch_style = PDO::FETCH_BOTH;break;
			default: $this->fetch_style = PDO::FETCH_ASSOC;break;
		}
		return $this;
	}


	public function tableFields(){
		return $this->dataSchema()->getFields($this->current_table);
	}

	public function tableName(){
		return $this->current_table;
	}
	public function schema(){

		$data = $this->dataSchema()->schemaArray($this->current_table);
		if(isset($data["_schema"]->{$this->current_database}->{$this->current_table})){
			return $this->toArray($data["_schema"]->{$this->current_database}->{$this->current_table});
		}
		return array();
	}
}
?>