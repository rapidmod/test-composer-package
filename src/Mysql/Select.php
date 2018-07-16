<?php
/**
 * @author RapidMod.com
 * @author 813.330.0522
 */


namespace Rapidmod\Mysql;


class Select extends Query{
    //make sure we dont include the same column twice
    public $checkKeys = array();
    //command IE INSERT, SELECT, UPDATE,SHOW TABLES, etc
    public $command = "SELECT";
    //the columns we expect
    public $columns = array("*");
    //database name
	public $database = "database";    //the limit of records to fetch at once
    public $limit = 0;

    // to track the tables we have already joined
    public $joined_tables = array();
    //The join clauses
    public $joins = array();
    // the name of the clas you want to fetch data into
    public $model_name = "Rapidmod\\Data\\Model";
    //order by clause
    public $order_by = "";
    //pagination
    public $page_number = 1;
    //the tables we have already added to select
    public $select_tables = array();
    //table name
    public $table = NULL;
    // incase we want to fetch all the results for additional processing
    public $count_query = NULL;
    public $count_params = array();
    //the paramaters associated with this query
    public $queryParams = array();
    //the where clauses
    // @TODO use and / or
    public $whereClauses = array();

    public $totalResultCount = 0;


    public function __construct( $table = "",$database = "") {
    	if($table){$this->table = $table;}
    	if($database){$this->database = $database;}
	    parent::__construct( $this->database );
    }

	/**
     * Name getParams
     * @return array
     * @author RapidMod.com
     * @author 813.330.0522
     *
     */
    public function getParams(){
        return $this->queryParams;
    }

    /**
     * Name getQuery
     * @return bool|null|string
     *
     * @author RapidMod.com
     * @author 813.330.0522
     *
     */
    public function getQuery(){
        if(is_null($this->command)){return false;}
        $query = $this->command;
        $countquery = NULL;
        if($query === "SELECT"){
            if(isset($this->columns[0]) &&  isset($this->columns[1]) && $this->columns[0] === "*"){
                unset($this->columns[0]);
            }
            $query .= " ".implode(",",$this->columns)." FROM {$this->table} ";
            $countquery = "SELECT count(*) as count  FROM {$this->table} ";
        }
        if(!empty($this->joins)){
            $query .= " ".implode(" ",$this->joins)." ";
            if($countquery){$countquery .= " ".implode(" ",$this->joins)." ";}

        }
        if(!empty($this->whereClauses)){
            $query .= " WHERE ".implode(" AND ",$this->whereClauses);
            if($countquery){
                $countquery .= " WHERE ".implode(" AND ",$this->whereClauses);
            }
        }
        if($countquery){
            $this->count_query = $countquery;
            $this->count_params = $this->getParams();
        }

        if(!empty($this->order_by)){
            $query .= " {$this->order_by} ";
        }

        if(!empty($this->limit)){
            if($this->page_number > 1){
                $offset = (int)(($this->page_number - 1)*$this->limit);
                if($offset > 0){
                    $query .= " LIMIT {$offset},{$this->limit}";
                }else{$query .= " LIMIT {$this->limit}";}
            }else{
                $query .= " LIMIT {$this->limit}";
            }
        }

        return $query;
    }



    /**
     * Name from
     * @param $table
     * @param null $database
     * @return $this
     *
     * @author RapidMod.com
     * @author 813.330.0522
     *
     */
    public function from($table,$database = NULL){
        $this->table = $table;
        if(!is_null($database)){
            $this->database = $database;
        }
        return $this;
    }

    public function formatQuery(){
        $query = $this->getQuery();
        $params = $this->getParams();
        if(!empty($params)){
            foreach ($params as $k => $v){
                if(strstr($query,$k)){
                    if(!is_numeric($v)){$v = "'{$v}'";}
                    $query = str_replace($k,$v,$query);
                }
            }
        }

        return $query;
    }

    /**
     * Name limit
     * @param $integer
     * @return $this
     *
     * @author RapidMod.com
     * @author 813.330.0522
     *
     */
    public function limit($integer){
        $this->limit = (int)$integer;
        return $this;
    }

    /**
     * Name order_by
     *
     * @param string $table table name or clause if column empty
     * @param string $column
     * @param string $direction
     * @return $this
     *
     * @author RapidMod.com
     * @author 813.330.0522
     *
     */
    public function order_by($column,$direction = ""){
        if($direction !== "DESC"){
            $direction = "";
        }
        $this->order_by = " ORDER BY {$column} {$direction} ";
        return $this;
    }

    /**
     * Name page
     * @param $integer
     * @return $this
     *
     * @author RapidMod.com
     * @author 813.330.0522
     *
     */
    public function page($integer){
        $this->page_number = (int)$integer;
        if($this->page_number < 1){
            $this->page_number = 1;
        }
        return $this;
    }

    /**
     * Name select
     * @param string $columns
     * @return $this
     *
     * @author RapidMod.com
     * @author 813.330.0522
     *
     */
    public function select($columns = "*"){
        $this->command = "SELECT";

        if(!empty($columns)){
            if(!is_array($columns)){
                $columns = array($columns);
            }
            foreach ($columns as $col){
                $q = "{$col}";
                if(!in_array($q,$this->columns)){
                    $this->columns[] = $q;
                }

            }
        }
        return $this;
    }

    public function getTotalResultCount(){
        if(!empty($this->totalResultCount)){return $this->totalResultCount;}
        $x = $this->result_count;
        $data = $this->fetch($this->count_query,$this->count_params);
        $this->result_count = $x;
        if(!empty($data[0]) && !empty($data[0]["count"])){
            $this->totalResultCount = (int)$data[0]["count"];
        }
        return $this->totalResultCount;
    }





    /**
     * Name join
     * @param $table
     * @param $on
     * @param string $joinType
     * @return $this
     *
     * @author RapidMod.com
     * @author 813.330.0522
     *
     */
    public function join($table,$on,$joinType="INNER JOIN"){
        if(empty($table) || empty($on) || empty($joinType) || in_array($table,$this->joined_tables)){return $this;}
        $this->joined_tables[] = $table;
        $this->joins[] = "{$joinType} {$table} ON {$on}";
        return $this;
    }
}
?>