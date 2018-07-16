<?php
/**
 * @author RapidMod.com
 * @author 813.330.0522
 */


namespace Rapidmod\Mysql;
use \Rapidmod\Data\Model;


class Table extends \Rapidmod\Mysql
{
	protected $current_table = NULL;

	protected $_fields = array();
	protected $_schema = array();

	function __construct($table,$database="default") {
		$this->current_table = $table;
		parent::__construct($database);
		$this->_fields = $this->tableFields();
		$this->_schema = $this->dataSchema()->tableSchema($this->current_table);
		return $this;
	}


	public function validateTable($table){
		if(empty($table)){return false;}
		if(!$this->dataSchema()->table_exists($table)){
			//$table = $this->_install_table($table);
		}
		return $this->setTable($table);
	}

	public function tableFields(){
		return $this->dataSchema()->getFields($this->current_table);
	}

	public function tableName(){
		return $this->current_table;
	}
}