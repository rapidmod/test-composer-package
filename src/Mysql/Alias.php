<?php
namespace Rapidmod\Mysql;
use \RcorePdoQuerySelect as MysqlSelect;
use \RcorePdoMysql as Table;
class Alias extends \Rapidmod\Model\Alias{


	protected $_TABLE = NULL;
	protected $aliasKeys = array();
	public $_saveAlias = false;


	public function search($params = array(),MysqlSelect $select = NULL){
		if(is_null($select)){
			$stmnt = new MysqlSelect($this->tableName());
			$stmnt->model_name = get_called_class();
		}else{
			$stmnt = $select;
		}

		if(!empty($params)){
			$fields = $this->tableFields();
			foreach ($fields as $f){
				if(isset($params[$f])){
					if(is_array($params[$f])){
						$stmnt->in($f,$params[$f]);
					}else{
						$stmnt->where($f,$params[$f]);
					}
				}
			}
			if(isset($params["limit"]) && !in_array("limit",$fields)){
				$stmnt->limit($params["limit"]);
			}
		}

		//die($stmnt->formatQuery());
		return $stmnt->fetchIntoObject();
		//return $this->Table()->search($params,get_called_class());
	}

	private function setTable($t,$o = array()){
		return $this->_TABLE = new $t($o);
	}


	/**
	 *
	 * Name Table
	 * @return RcorePdoMysql
	 *
	 * @author RapidMod.com
	 * @author 813.330.0522
	 *
	 *	IMPORTANT this method should be overwritten in a child class
	 */
	protected function Table(){
		if(is_null($this->_TABLE)){ $this->_TABLE = new Table();}
		return $this->_TABLE;
	}

	public function canSave(){
		return true;
	}

	public function load($value,$key = ""){
		return $this->buildObject($this->Table()->load($value,$key)->toArray());
	}

	public function save(){
		if(!$this->canSave()){return $this->reset();}
		$data = array();
		if($this->_saveAlias){
			$data = $this->toArray();
		}
		$this->buildObject(
			$this->Table()
			->setData(
				$this->toArray()
			)
			->save()
			->toArray()
		);
		if(!empty($data) && $this->_get($this->primaryKey())){
			return $this->_saveAlias($data);
		}
		return $this;
	}


	public function loadBy($params){
		return $this->buildObject(
			$this->Table()->loadBy($params)->toArray()
		);

	}

	public function tableFields(){
		return $this->Table()->tableFields();
	}

	public function saveAliases($data){
		if(!$this->_saveAlias){return $this;}
		$x = $this->aliasMap();
		if(!empty($x)){
			$keys = array();
			foreach ($x as $k=>$v){
				if(!empty($data[$k])){
					if(!empty($v["hasMany"])){
						foreach ($data[$k] as $model){
							$model->save();
						}
					}else{
						$data[$k]->save();
					}
				}
			}
		}
		return $this;
	}

	public function schema(){
		return $this->Table()->schema();
	}

	public function tableName(){
		return $this->Table()->tableName();
	}

	public function primaryKey(){
		return $this->Table()->return_param();
	}
	public function beginTransaction(){
		return $this->Table()->beginTransaction();
	}
	public function commit(){
		return $this->Table()->commit();
	}
	public function rollBack(){
		return $this->Table()->beginTransaction();
	}


}

?>