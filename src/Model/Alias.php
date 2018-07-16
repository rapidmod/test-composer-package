<?php
namespace Rapidmod\Model;
use \Rapidmod\Data\Model;
use \Rapidmod\Mysql\Select as MysqlSelect;
use \Rapidmod\Mysql as Table;
class Alias extends Model{


	protected $_TABLE = NULL;
	protected $aliasKeys = array();
	public $_saveAlias = false;

	public function __construct($id = NULL,$key=false){
		if(!is_null($id)){$this->load($id,$key);}
	}

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
	 * Name aliasMap
	 * @return array
	 *
	 * @author RapidMod.com
	 * @author 813.330.0522
	 *
	 * this must be configured in the child model, its only here so it does not break things
	 *
	 */
	public function aliasMap(){
		return array();
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




	/**
	 *
	 * Name addAlias
	 *
	 * @param $key
	 * @param array $options
	 *
	 * $options params
	 *  @param action // **DEPRECATED** see method
	 * 	@param parent_table
	 *  @param table
	 * 	@param model //the data model to use
	 *  @param load //to load a definitive record or value to use when calling a method
	 * 	@param method //the method to call
	 * 	@param fetchBy // for many records
	 * 	@param join_on
	 *
	 *
	 * @return $this
	 *
	 * @author RapidMod.com
	 * @author 813.330.0522
	 *
	 */
	public function addAlias($key,$options = array()){
		if(empty($options) || empty($key)){
			return $this;
		}
		$this->aliasKeys[$key] = $options;
		return $this;
		//sample
		$alias = array(
			"User" => array(
				"parent_table" => "post",
				"table" => "user",
				"model" => "ModelUser",
				"load" => "user_id", //to load a definitive record
				"method" => "load",
				"fetchBy" => array("user_id","location_id"),     // for many records
				"join_on" => "post.user_id = user.id"
			)
		);
		return $this;
	}


	/**
	 *
	 * Name _get
	 * @param $key
	 * @return bool
	 *
	 * @author RapidMod.com
	 * @author 813.330.0522
	 *
	 * If the data is not already set this overrides the default get key and
	 * returns the data set in aliasMap
	 */
	public function _get($key){
		if(!parent::_get($key)){
			$this->loadAlias($key);
		}
		return parent::_get($key);
	}

	/**
	 *
	 * Name loadAlias
	 * @param $key
	 * @return $this
	 *
	 * @author RapidMod.com
	 * @author 813.330.0522
	 *
	 * model must be set
	 *
	 * load order = action/load,load{/key},fetchBy/{array(keys)}
	 *
	 */
	public function loadAlias($key){
		if(empty($this->aliasKeys)){
			$this->aliasKeys = $this->aliasMap();
		}

		if(empty($this->aliasKeys) || empty($this->aliasKeys[$key])){
			return $this;
		}
		$params = $this->aliasKeys[$key];

		$Model = false;
		if(!empty($params["model"])){
			if($params["model"] === "this"){
				$Model = $this;
			}else{
				$Model = new $params["model"]();
			}
			if(empty($params["load"])){
				$params["load"] = "";
			}


			if(!empty($params["method"])){

				//echo "{$params["model"]}::{$params["method"]}({$this->_get($params["load"])})<hr>";
				$Model = $Model->{$params["method"]}($this->_get($params["load"]));
			}elseif(!empty($params["load"])){
				if(is_array($params["load"])){
					$id = $params["load"][0];
					$keyColumn = $params["load"][1];
				}else{
					$id = $params["load"];
					$keyColumn = false;
				}
				if($id && $this->_get($id)){
					$Model->load($this->_get($id),$keyColumn);
				}

			}elseif(!empty($params["fetchBy"])){
				if(is_array($params["fetchBy"])){
					$id = $params["fetchBy"][0];
					$keyColumn = $params["fetchBy"][1];
				}else{
					$id = $params["fetchBy"];
					$keyColumn = false;
				}
				$query = new MysqlSelect($params["table"]);
				$Model =  $query->where($keyColumn,$this->_get($id))->fetchIntoObject("","",$params["model"]);
			}
		}

		return $this->_set($key,$Model);
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