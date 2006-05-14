<?php
/**
* @package    jelix
* @subpackage db
* @version    $Id:$
* @author     Loic Mathaud
* @contributor 
* @copyright  2006 Loic Mathaud
* @link      http://www.jelix.org
* @licence  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public Licence, see LICENCE file
*/

/**
 *
 * Couche d'encapsulation des resultset mysql.
 */
class jDbResultSetSqlite extends jDbResultSet {

    protected function  _fetch (){
        $ret =  sqlite_fetch_object($this->_idResult);
        return $ret;
    }
    protected function _free (){
        return;
    }

    public function rowCount(){
        return sqlite_num_rows($this->_idResult);
    }

    public function bindColumn($column, &$param , $type=null )
      {throw new JException('jelix~db.error.feature.unsupported', array('sqlite','bindColumn')); }
    public function bindParam($parameter, &$variable , $data_type =null, $length=null,  $driver_options=null)
      {throw new JException('jelix~db.error.feature.unsupported', array('sqlite','bindParam')); }
    public function bindValue($parameter, $value, $data_type)
      {throw new JException('jelix~db.error.feature.unsupported', array('sqlite','bindValue')); }
    public function columnCount()
      { return sqlite_num_fields($this->_idResult); }
    public function execute($parameters=null)
      {throw new JException('jelix~db.error.feature.unsupported', array('sqlite','bindColumn')); }
}
?>