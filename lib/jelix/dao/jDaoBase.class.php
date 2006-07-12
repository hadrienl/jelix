<?php
/**
 * @package    jelix
 * @subpackage dao
 * @version    $Id:$
 * @author     Laurent Jouanneau
 * @contributor
 * @copyright  2005-2006 Laurent Jouanneau
 * @link        http://www.jelix.org
 * @licence  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public Licence, see LICENCE file
 */

abstract class jDaoRecordBase {

   const ERROR_REQUIRED=1;
   const ERROR_BAD_TYPE=2;
   const ERROR_BAD_FORMAT=3;
   const ERROR_MAXLENGTH = 4;
   const ERROR_MINLENGTH = 5;


   protected $_properties=array();

   public function getProperties(){ return $this->_properties; }

   public function check(){
      $errors=array();
      foreach($this->_properties as $prop=>$infos){
         $value = $this->$prop;

         // test required
         if($infos['required'] && $value === null && $infos['datatype'] != 'autoincrement' && $infos['datatype'] != 'bigautoincrement'){
            $errors[$prop][] = self::ERROR_REQUIRED;
            continue;
         }

         if($infos['datatype']=='varchar' || $infos['datatype']=='string'){
            if(!is_string($value) && $value !== null){
               $errors[$prop][] = self::ERROR_BAD_TYPE;
               continue;
            }
            // test regexp
            if ($infos['regExp'] !== null && preg_match ($infos['regExp'], $value) === 0){
               $errors[$prop][] = self::ERROR_BAD_FORMAT;
               continue;
            }

            //  test maxlength et minlength
            $len = strlen($value);
            if($infos['maxlength'] !== null && $len > intval($infos['maxlength'])){
               $errors[$prop][] = self::ERROR_MAXLENGTH;
            }

            if($infos['minlength'] !== null && $len < intval($infos['minlength'])){
               $errors[$prop][] = self::ERROR_MINLENGTH;
            }


         }elseif( in_array($infos['datatype'], array('int','integer','numeric', 'double', 'float'))) {
            // test datatype
            if($value !== null && !is_numeric($value)){
               $errors[$prop][] = self::ERROR_BAD_TYPE;
               continue;
            }
         }elseif( in_array($infos['datatype'], array('datetime', 'time','varchardate', 'date'))) {
            if (jLocale::timestampToDate ($value) === false){
               $errors[$prop][] = self::ERROR_BAD_FORMAT;
               continue;
            }
         }
      }
      return $errors;
   }

   public function setPk(){
      $args=func_get_args();
      if(count($args) == 0) throw new jException('jelix~dao.error.keys.missing');
      if(count($args)==1 && is_array($args[0])){
            $args=$args[0];
      }
      $i=0;
      foreach($this->_properties as $prop=>$infos){
         if($infos['isPk']){
            if($i>= count($args))
               throw new jException('jelix~dao.error.keys.missing');
            $this->$prop = $args[$i++];
         }
      }
      return true;
   }

}







/**
 */
abstract class jDaoFactoryBase  {

   protected $_tables;
   protected $_primaryTable;
   protected $_conn;
   protected $_selectClause;
   protected $_fromClause;
   protected $_whereClause;
   protected $_DaoRecordClassName;
   protected $_pkFields;


   function  __construct($conn){
      $this->_conn = $conn;
   }

   public function findAll(){
      $rs =  $this->_conn->query ($this->_selectClause.$this->_fromClause.$this->_whereClause);
      $rs->setFetchMode(8,$this->_DaoRecordClassName);
      return $rs;
   }

   public function countAll(){
     $query = 'SELECT COUNT(*) as c '.$this->_fromClause.$this->_whereClause;
     $rs  =  $this->_conn->query ($query);
     $res =  $rs->fetch ();
     return $res->c;
   }

   public function get(){
      $args=func_get_args();
      if(count($args)==1 && is_array($args[0])){
         $args=$args[0];
      }
      $keys = array_combine($this->_pkFields,$args );

      if($keys === false){
         throw new jException('jelix~dao.error.keys.missing');
      }

      $q = $this->_selectClause.$this->_fromClause.$this->_whereClause;
      $q .= $this->_getPkWhereClauseForSelect($keys);

      $rs  =  $this->_conn->query ($q);
      $rs->setFetchMode(8,$this->_DaoRecordClassName);
      $record =  $rs->fetch ();
      return $record;
   }

   public function delete(){
      $args=func_get_args();
      if(count($args)==1 && is_array($args[0])){
         $args=$args[0];
      }
      $keys = array_combine($this->_pkFields, $args);
      if($keys === false){
         throw new jException('jelix~dao.error.keys.missing');
      }
      $q = 'DELETE FROM '.$this->_tables[$this->_primaryTable]['realname'].' ';
      $q.= $this->_getPkWhereClauseForNonSelect($keys);
      return $this->_conn->exec ($q);
   }

   abstract public function insert ($record);
   abstract public function update ($record);


   /**
    * @param jDaoConditions $searchcond
    */
   public function findBy ($searchcond){
      $query = $this->_selectClause.$this->_fromClause.$this->_whereClause;
      if (!$searchcond->isEmpty ()){
         $query .= ($this->_whereClause !='' ? ' AND ' : ' WHERE ');
         $query .= $this->_createConditionsClause($searchcond);
      }

      $rs  =  $this->_conn->query ($query);
      $rs->setFetchMode(8,$this->_DaoRecordClassName);
      return $rs;
   }

   abstract protected function _getPkWhereClauseForSelect($pk);
   abstract protected function _getPkWhereClauseForNonSelect($pk);

   /**
    *
    */
   protected function _createConditionsClause($daocond){

      $c = $this->_DaoRecordClassName;
      $rec= new $c();
      $fields = $rec->getProperties();

      $sql = $this->_generateCondition ($daocond->condition, $fields, true);

      $order = array ();
      foreach ($daocond->order as $name => $way){
         if (isset($fields[$name])){
            $order[] = $name.' '.$way;
         }
      }
      if(count ($order) > 0){
         if(trim($sql) =='') {
            $sql.= ' 1=1 ';
         }
         $sql.=' ORDER BY '.implode (', ', $order);
      }
      return $sql;

   }


   protected function _generateCondition($condition, &$fields, $principal=true){
      $r = ' ';
      $notfirst = false;
      foreach ($condition->conditions as $cond){
         if ($notfirst){
            $r .= ' '.$condition->glueOp.' ';
         }else
            $notfirst = true;

         $prop=$fields[$cond['field_id']];

         $prefixNoCondition = $this->_tables[$prop['table']]['name'].'.'.$prop['fieldName'];
         $prefix=$prefixNoCondition.' '.$cond['operator'].' '; // ' ' pour les like..

         if (!is_array ($cond['value'])){
            $value = $this->_prepareValue($cond['value'],$prop['datatype']);
            if ($value === 'NULL'){
               if($cond['operator'] == '='){
                  $r .= $prefixNoCondition.' IS NULL';
               }else{
                  $r .= $prefixNoCondition.' IS NOT NULL';
               }
            } else {
               $r .= $prefix.$value;
            }
         }else{
            $r .= ' ( ';
            $firstCV = true;
            foreach ($cond['value'] as $conditionValue){
               if (!$firstCV){
                  $r .= ' or ';
               }
               $value = $this->_prepareValue($conditionValue,$prop['datatype']);
               if ($value === 'NULL'){
                  if($cond['operator'] == '='){
                     $r .= $prefixNoCondition.' IS NULL';
                  }else{
                     $r .= $prefixNoCondition.' IS NOT NULL';
                  }
               }else{
                  $r .= $prefix.$value;
               }
               $firstCV = false;
            }
            $r .= ' ) ';
         }
      }
      //sub conditions
      foreach ($condition->group as $conditionDetail){
         if ($notfirst){
            $r .= ' '.$condition->glueOp.' ';
         }else{
           $notfirst=true;
         }
         $r .= $this->_generateCondition($conditionDetail, $fields, false);
      }

      //adds parenthesis around the sql if needed (non empty)
      if (strlen (trim ($r)) > 0 && !$principal){
         $r = '('.$r.')';
      }
      return $r;
   }
   /**
    * prepare the value ready to be used in a dynamic evaluation
    */
   protected function _prepareValue($value, $fieldType){
      switch(strtolower($fieldType)){
         case 'int':
         case 'integer':
         case 'autoincrement':
            $value = $value === null ? 'NULL' : intval($value);
            break;
         case 'double':
         case 'float':
            $value = $value === null ? 'NULL' : doubleval($value);
            break;
         case 'numeric'://usefull for bigint and stuff
         case 'bigautoincrement':
            if (is_numeric ($value)){
               //was numeric, we can sends it as is
               // no cast with intval else overflow
               return $value === null ? 'NULL' : $value;
            }else{
               //not a numeric, nevermind, casting it
               return $value === null ? 'NULL' : intval ($value);
            }
            break;
         default:
            $value = $this->_conn->quote ($value);
      }
      return $value;
   }

}
?>