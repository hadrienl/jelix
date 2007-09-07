<?php
/**
* @package     testapp
* @subpackage  jelix_tests module
* @author      Jouanneau Laurent
* @contributor
* @copyright   2007 Jouanneau laurent
* @link        http://www.jelix.org
* @licence     GNU Lesser General Public Licence see LICENCE file or http://www.gnu.org/licenses/lgpl.html
*/

require_once(JELIX_LIB_DAO_PATH.'jDaoCompiler.class.php');

class testDaoGenerator extends jDaoGenerator {

    function GetPropertiesBy ($captureMethod){
        return $this->_getPropertiesBy ($captureMethod);
    }

    function BuildSimpleConditions (&$fields, $fieldPrefix='', $forSelect=true){
        return $this->_buildSimpleConditions ($fields, $fieldPrefix, $forSelect);
    }

    function BuildSQLCondition ($condition, $fields, $params, $withPrefix){
        return $this->_buildSQLCondition ($condition, $fields, $params, $withPrefix, true);
    }

    function GetPreparePHPValue($value, $fieldType, $checknull=true){
        return $this->_preparePHPValue($value, $fieldType, $checknull);
    }

    function GetPreparePHPExpr($expr, $fieldType, $checknull=true, $forCondition=''){
        return $this->_preparePHPExpr($expr, $fieldType, $checknull, $forCondition);
    }
    function GetEncloseName($name){
        return $this->_encloseName($name);
    }
}

class UTDao_generator extends jUnitTestCase {
    protected function getSimpleGenerator(){
        $doc ='<?xml version="1.0"?>
<dao xmlns="http://jelix.org/ns/dao/1.0">
   <datasources>
      <primarytable name="product_test" primarykey="id" />
   </datasources>
   <record>
      <property name="id"   fieldname="id" datatype="autoincrement" required="true" />
      <property name="name" fieldname="name" datatype="string"  required="true"/>
      <property name="price" fieldname="price" datatype="float"/>
   </record>
</dao>';
        $parser = new jDaoParser ();
        $parser->parse(simplexml_load_string($doc));
        return new testDaoGenerator('cDao_foo_Jx_bar_Jx_mysql', 'cDaoRecord_foo_Jx_bar_Jx_mysql', $parser);
    }


    function setUp() {
        jDaoCompiler::$daoId ='';
        jDaoCompiler::$daoPath = '';
        jDaoCompiler::$dbType='mysql';
    }

    function testEncloseName(){
        $doc ='<?xml version="1.0"?>
<dao xmlns="http://jelix.org/ns/dao/1.0">
   <datasources>
      <primarytable name="product_test" primarykey="id" />
   </datasources>
   <record>
      <property name="id"   fieldname="id" datatype="autoincrement" required="true" />
      <property name="name" fieldname="name" datatype="string"  required="true"/>
      <property name="price" fieldname="price" datatype="float"/>
   </record>
</dao>';
        $parser = new jDaoParser ();
        $parser->parse(simplexml_load_string($doc));

        jDaoCompiler::$dbType='mysql';
        $generator= new testDaoGenerator('cDao_foo_Jx_bar_Jx_mysql', 'cDaoRecord_foo_Jx_bar_Jx_mysql', $parser);
        $result = $generator->GetEncloseName('foo');
        $this->assertEqualOrDiff('`foo`',$result);

        jDaoCompiler::$dbType='postgresql';
        $generator= new testDaoGenerator('cDao_foo_Jx_bar_Jx_mysql', 'cDaoRecord_foo_Jx_bar_Jx_mysql', $parser);
        $result = $generator->GetEncloseName('foo');
        $this->assertEqualOrDiff('"foo"',$result);

        jDaoCompiler::$dbType='oracle';
        $generator= new testDaoGenerator('cDao_foo_Jx_bar_Jx_mysql', 'cDaoRecord_foo_Jx_bar_Jx_mysql', $parser);
        $result = $generator->GetEncloseName('foo');
        $this->assertEqualOrDiff('foo',$result);

        jDaoCompiler::$dbType='oci8';
        $generator= new testDaoGenerator('cDao_foo_Jx_bar_Jx_mysql', 'cDaoRecord_foo_Jx_bar_Jx_mysql', $parser);
        $result = $generator->GetEncloseName('foo');
        $this->assertEqualOrDiff('foo',$result);

        jDaoCompiler::$dbType='sqlite';
        $generator= new testDaoGenerator('cDao_foo_Jx_bar_Jx_mysql', 'cDaoRecord_foo_Jx_bar_Jx_mysql', $parser);
        $result = $generator->GetEncloseName('foo');
        $this->assertEqualOrDiff('foo',$result);
    }

    function testPreparePHPExpr(){
        $generator=$this->getSimpleGenerator();

        // with no checknull
        $result = $generator->GetPreparePHPExpr('$foo', 'int',false);
        $this->assertEqualOrDiff('intval($foo)',$result);
        $result = $generator->GetPreparePHPExpr('$foo', 'integer',false);
        $this->assertEqualOrDiff('intval($foo)',$result);
        $result = $generator->GetPreparePHPExpr('$foo', 'autoincrement',false);
        $this->assertEqualOrDiff('intval($foo)',$result);
        $result = $generator->GetPreparePHPExpr('$foo', 'string',false);
        $this->assertEqualOrDiff('$this->_conn->quote($foo)',$result);
        $result = $generator->GetPreparePHPExpr('$foo', 'double',false);
        $this->assertEqualOrDiff('doubleval($foo)',$result);
        $result = $generator->GetPreparePHPExpr('$foo', 'float',false);
        $this->assertEqualOrDiff('doubleval($foo)',$result);
        $result = $generator->GetPreparePHPExpr('$foo', 'numeric',false);
        $this->assertEqualOrDiff('(is_numeric ($foo) ? $foo : intval($foo))',$result);
        $result = $generator->GetPreparePHPExpr('$foo', 'bigautoincrement',false);
        $this->assertEqualOrDiff('(is_numeric ($foo) ? $foo : intval($foo))',$result);

        // with checknull 
        $result = $generator->GetPreparePHPExpr('$foo', 'integer',true);
        $this->assertEqualOrDiff('($foo === null ? \'NULL\' : intval($foo))',$result);
        $result = $generator->GetPreparePHPExpr('$foo', 'autoincrement',true);
        $this->assertEqualOrDiff('($foo === null ? \'NULL\' : intval($foo))',$result);
        $result = $generator->GetPreparePHPExpr('$foo', 'string',true);
        $this->assertEqualOrDiff('($foo === null ? \'NULL\' : $this->_conn->quote($foo,false))',$result);
        $result = $generator->GetPreparePHPExpr('$foo', 'double',true);
        $this->assertEqualOrDiff('($foo === null ? \'NULL\' : doubleval($foo))',$result);
        $result = $generator->GetPreparePHPExpr('$foo', 'float',true);
        $this->assertEqualOrDiff('($foo === null ? \'NULL\' : doubleval($foo))',$result);
        $result = $generator->GetPreparePHPExpr('$foo', 'numeric',true);
        $this->assertEqualOrDiff('($foo === null ? \'NULL\' : (is_numeric ($foo) ? $foo : intval($foo)))',$result);
        $result = $generator->GetPreparePHPExpr('$foo', 'bigautoincrement',true);
        $this->assertEqualOrDiff('($foo === null ? \'NULL\' : (is_numeric ($foo) ? $foo : intval($foo)))',$result);

        // with checknull and operator =
        $result = $generator->GetPreparePHPExpr('$foo', 'integer',true,'=');
        $this->assertEqualOrDiff('($foo === null ? \'IS NULL\' : \'=\'.intval($foo))',$result);
        $result = $generator->GetPreparePHPExpr('$foo', 'autoincrement',true,'=');
        $this->assertEqualOrDiff('($foo === null ? \'IS NULL\' : \'=\'.intval($foo))',$result);
        $result = $generator->GetPreparePHPExpr('$foo', 'string',true,'=');
        $this->assertEqualOrDiff('($foo === null ? \'IS NULL\' : \'=\'.$this->_conn->quote($foo,false))',$result);
        $result = $generator->GetPreparePHPExpr('$foo', 'double',true,'=');
        $this->assertEqualOrDiff('($foo === null ? \'IS NULL\' : \'=\'.doubleval($foo))',$result);
        $result = $generator->GetPreparePHPExpr('$foo', 'float',true,'=');
        $this->assertEqualOrDiff('($foo === null ? \'IS NULL\' : \'=\'.doubleval($foo))',$result);
        $result = $generator->GetPreparePHPExpr('$foo', 'numeric',true,'=');
        $this->assertEqualOrDiff('($foo === null ? \'IS NULL\' : \'=\'.(is_numeric ($foo) ? $foo : intval($foo)))',$result);
        $result = $generator->GetPreparePHPExpr('$foo', 'bigautoincrement',true,'=');
        $this->assertEqualOrDiff('($foo === null ? \'IS NULL\' : \'=\'.(is_numeric ($foo) ? $foo : intval($foo)))',$result);

        // with checknull and operator <>
        $result = $generator->GetPreparePHPExpr('$foo', 'integer',true,'<>');
        $this->assertEqualOrDiff('($foo === null ? \'IS NOT NULL\' : \'<>\'.intval($foo))',$result);
        $result = $generator->GetPreparePHPExpr('$foo', 'autoincrement',true,'<>');
        $this->assertEqualOrDiff('($foo === null ? \'IS NOT NULL\' : \'<>\'.intval($foo))',$result);
        $result = $generator->GetPreparePHPExpr('$foo', 'string',true,'<>');
        $this->assertEqualOrDiff('($foo === null ? \'IS NOT NULL\' : \'<>\'.$this->_conn->quote($foo,false))',$result);
        $result = $generator->GetPreparePHPExpr('$foo', 'double',true,'<>');
        $this->assertEqualOrDiff('($foo === null ? \'IS NOT NULL\' : \'<>\'.doubleval($foo))',$result);
        $result = $generator->GetPreparePHPExpr('$foo', 'float',true,'<>');
        $this->assertEqualOrDiff('($foo === null ? \'IS NOT NULL\' : \'<>\'.doubleval($foo))',$result);
        $result = $generator->GetPreparePHPExpr('$foo', 'numeric',true,'<>');
        $this->assertEqualOrDiff('($foo === null ? \'IS NOT NULL\' : \'<>\'.(is_numeric ($foo) ? $foo : intval($foo)))',$result);
        $result = $generator->GetPreparePHPExpr('$foo', 'bigautoincrement',true,'<>');
        $this->assertEqualOrDiff('($foo === null ? \'IS NOT NULL\' : \'<>\'.(is_numeric ($foo) ? $foo : intval($foo)))',$result);

        // with checknull and other operator <=
        $result = $generator->GetPreparePHPExpr('$foo', 'integer',true,'<=');
        $this->assertEqualOrDiff('\'<=\'.intval($foo)',$result);
        $result = $generator->GetPreparePHPExpr('$foo', 'autoincrement',true,'<=');
        $this->assertEqualOrDiff('\'<=\'.intval($foo)',$result);
        $result = $generator->GetPreparePHPExpr('$foo', 'string',true,'<=');
        $this->assertEqualOrDiff('\'<=\'.$this->_conn->quote($foo)',$result);
        $result = $generator->GetPreparePHPExpr('$foo', 'double',true,'<=');
        $this->assertEqualOrDiff('\'<=\'.doubleval($foo)',$result);
        $result = $generator->GetPreparePHPExpr('$foo', 'float',true,'<=');
        $this->assertEqualOrDiff('\'<=\'.doubleval($foo)',$result);
        $result = $generator->GetPreparePHPExpr('$foo', 'numeric',true,'<=');
        $this->assertEqualOrDiff('\'<=\'.(is_numeric ($foo) ? $foo : intval($foo))',$result);
        $result = $generator->GetPreparePHPExpr('$foo', 'bigautoincrement',true,'<=');
        $this->assertEqualOrDiff('\'<=\'.(is_numeric ($foo) ? $foo : intval($foo))',$result);
    }

    function testPreparePHPValue(){
        $generator=$this->getSimpleGenerator();

        // with no checknull
        $result = $generator->GetPreparePHPValue('5', 'int',false);
        $this->assertEqualOrDiff(5,$result);
        $result = $generator->GetPreparePHPValue('5', 'integer',false);
        $this->assertEqualOrDiff(5,$result);
        $result = $generator->GetPreparePHPValue('5', 'autoincrement',false);
        $this->assertEqualOrDiff(5,$result);
        $result = $generator->GetPreparePHPValue('$foo', 'string',false);
        $this->assertEqualOrDiff('\\\'$foo\\\'',$result);
        $result = $generator->GetPreparePHPValue('$f\'oo', 'string',false);
        $this->assertEqualOrDiff('\'.$this->_conn->quote(\'$f\\\'oo\').\'',$result);
        $result = $generator->GetPreparePHPValue('5.63', 'double',false);
        $this->assertEqualOrDiff(5.63,$result);
        $result = $generator->GetPreparePHPValue('5.63', 'float',false);
        $this->assertEqualOrDiff(5.63,$result);
        $result = $generator->GetPreparePHPValue('565465465463', 'numeric',false);
        $this->assertEqualOrDiff('565465465463',$result);
        $result = $generator->GetPreparePHPValue('565469876543139798641315465463', 'numeric',false);
        $this->assertEqualOrDiff('565469876543139798641315465463',$result);
        $result = $generator->GetPreparePHPValue('565469876543139798641315465463', 'bigautoincrement',false);
        $this->assertEqualOrDiff('565469876543139798641315465463',$result);

        // with checknull 
        $result = $generator->GetPreparePHPValue('5', 'integer',true);
        $this->assertEqualOrDiff(5,$result);
        $result = $generator->GetPreparePHPValue('5', 'autoincrement',true);
        $this->assertEqualOrDiff(5,$result);
        $result = $generator->GetPreparePHPValue('$foo', 'string',true);
        $this->assertEqualOrDiff('\\\'$foo\\\'',$result);
        $result = $generator->GetPreparePHPValue('5.28', 'double',true);
        $this->assertEqualOrDiff(5.28,$result);
        $result = $generator->GetPreparePHPValue('5.26', 'float',true);
        $this->assertEqualOrDiff(5.26,$result);
        $result = $generator->GetPreparePHPValue('565469876543139798641315465463', 'numeric',true);
        $this->assertEqualOrDiff('565469876543139798641315465463',$result);
        $result = $generator->GetPreparePHPValue('565469876543139798641315465463', 'bigautoincrement',true);
        $this->assertEqualOrDiff('565469876543139798641315465463',$result);

    }

    function testBuildSQLCondition(){
        $doc ='<?xml version="1.0" encoding="UTF-8"?>
<dao xmlns="http://jelix.org/ns/dao/1.0">
    <datasources>
        <primarytable name="grp" realname="jacl_group" primarykey="id_aclgrp" />
    </datasources>
    <record>
      <property name="id_aclgrp" fieldname="id_aclgrp" datatype="autoincrement" required="yes"/>
      <property name="parent_id" required="false" datatype="int" />
      <property name="name" fieldname="name" datatype="string" required="yes"/>
      <property name="grouptype" fieldname="grouptype" datatype="int" required="yes"/>
      <property name="ownerlogin" fieldname="ownerlogin" datatype="string" />
    </record>
    <factory>
        <method name="method1" type="select">
            <conditions>
               <eq property="grouptype" value="1" />
            </conditions>
        </method>

        <method name="method2" type="select">
           <conditions>
              <neq property="grouptype" value="2" />
           </conditions>
           <order>
               <orderitem property="name" way="asc" />
           </order>
        </method>

        <method name="method3" type="select">
           <parameter name="login" />
           <conditions>
              <eq property="grouptype" value="2" />
              <eq property="ownerlogin" expr="$login" />
           </conditions>
        </method>

        <method name="method4" type="select">
           <parameter name="parent" />
           <parameter name="group" />
           <conditions>
              <eq property="grouptype" expr="$group" />
              <eq property="parent_id" expr="$parent" />
           </conditions>
        </method>
         <method name="method5" type="select">
           <parameter name="parent" />
           <parameter name="group" />
           <conditions>
              <eq property="grouptype" expr="$group" />
              <conditions logic="or">
                <eq property="parent_id" expr="$parent" />
                <eq property="id_aclgrp" expr="$parent" />
              </conditions>
           </conditions>
        </method>
        <method name="method6" type="select">
           <conditions>
              <in property="grouptype" value="1,2,3" />
              <isnull property="parent_id" />
           </conditions>
        </method>
        <method name="method7" type="select">
           <parameter name="parent" />
           <parameter name="group" />
           <conditions>
              <in property="grouptype" expr="$group" />
              <lt property="parent_id" expr="$parent" />
           </conditions>
        </method>
        <method name="method8" type="select">
           <parameter name="login" />
           <conditions>
              <eq property="grouptype" value="2" />
              <eq property="ownerlogin" expr="TOUPPER($login)" />
           </conditions>
        </method>        
        <method name="method9" type="select">
           <parameter name="login" />
           <conditions>
              <eq property="grouptype" value="2" />
              <eq property="name" expr="TOUPPER($login)" />
           </conditions>
        </method> 
        
    </factory>
</dao>';
        $parser = new jDaoParser ();
        $parser->parse(simplexml_load_string($doc));
        $generator = new testDaoGenerator('cDao_foo_Jx_bar_Jx_mysql', 'cDaoRecord_foo_Jx_bar_Jx_mysql', $parser);
        
        $methods=$parser->getMethods();
    
        $where = $generator->BuildSQLCondition ($methods['method1']->getConditions()->condition, $parser->getProperties(),
                                                $methods['method1']->getParameters(), false);
        $this->assertEqualOrDiff(' `grouptype` = 1',$where);
    
        $where = $generator->BuildSQLCondition ($methods['method2']->getConditions()->condition, $parser->getProperties(),
                                                $methods['method2']->getParameters(), false);
        $this->assertEqualOrDiff(' `grouptype` <> 2',$where);
        
        $where = $generator->BuildSQLCondition ($methods['method3']->getConditions()->condition, $parser->getProperties(),
                                                $methods['method3']->getParameters(), false);
        $this->assertEqualOrDiff(' `grouptype` = 2 AND `ownerlogin` \'.($login === null ? \'IS NULL\' : \'=\'.$this->_conn->quote($login,false)).\'',$where);
        
        $where = $generator->BuildSQLCondition ($methods['method4']->getConditions()->condition, $parser->getProperties(),
                                                $methods['method4']->getParameters(), false);
        $this->assertEqualOrDiff(' `grouptype` \'.\'=\'.intval($group).\' AND `parent_id` \'.($parent === null ? \'IS NULL\' : \'=\'.intval($parent)).\'',$where);
        
        $where = $generator->BuildSQLCondition ($methods['method5']->getConditions()->condition, $parser->getProperties(),
                                                $methods['method5']->getParameters(), false);
        $this->assertEqualOrDiff(' `grouptype` \'.\'=\'.intval($group).\' AND ( `parent_id` \'.($parent === null ? \'IS NULL\' : \'=\'.intval($parent)).\' OR `id_aclgrp` \'.\'=\'.intval($parent).\')',$where);
        
        $where = $generator->BuildSQLCondition ($methods['method6']->getConditions()->condition, $parser->getProperties(),
                                                $methods['method6']->getParameters(), false);
        $this->assertEqualOrDiff(' `grouptype` IN (1,2,3) AND `parent_id` IS NULL ',$where);
        
        $where = $generator->BuildSQLCondition ($methods['method7']->getConditions()->condition, $parser->getProperties(),
                                                $methods['method7']->getParameters(), false);
        $this->assertEqualOrDiff(' `grouptype` IN (\'.implode(\',\', array_map( create_function(\'$__e\',\'return intval($__e);\'), $group)).\') AND `parent_id` \'.\'<\'.intval($parent).\'',$where);
        
        // with prefix
        $where = $generator->BuildSQLCondition ($methods['method1']->getConditions()->condition, $parser->getProperties(),
                                                $methods['method1']->getParameters(), true);
        $this->assertEqualOrDiff(' `grp`.`grouptype` = 1',$where);
    
        $where = $generator->BuildSQLCondition ($methods['method2']->getConditions()->condition, $parser->getProperties(),
                                                $methods['method2']->getParameters(), true);
        $this->assertEqualOrDiff(' `grp`.`grouptype` <> 2',$where);
        
        $where = $generator->BuildSQLCondition ($methods['method3']->getConditions()->condition, $parser->getProperties(),
                                                $methods['method3']->getParameters(), true);
        $this->assertEqualOrDiff(' `grp`.`grouptype` = 2 AND `grp`.`ownerlogin` \'.($login === null ? \'IS NULL\' : \'=\'.$this->_conn->quote($login,false)).\'',$where);
        
        $where = $generator->BuildSQLCondition ($methods['method4']->getConditions()->condition, $parser->getProperties(),
                                                $methods['method4']->getParameters(), true);
        $this->assertEqualOrDiff(' `grp`.`grouptype` \'.\'=\'.intval($group).\' AND `grp`.`parent_id` \'.($parent === null ? \'IS NULL\' : \'=\'.intval($parent)).\'',$where);
        
        $where = $generator->BuildSQLCondition ($methods['method5']->getConditions()->condition, $parser->getProperties(),
                                                $methods['method5']->getParameters(), true);
        $this->assertEqualOrDiff(' `grp`.`grouptype` \'.\'=\'.intval($group).\' AND ( `grp`.`parent_id` \'.($parent === null ? \'IS NULL\' : \'=\'.intval($parent)).\' OR `grp`.`id_aclgrp` \'.\'=\'.intval($parent).\')',$where);
        
        $where = $generator->BuildSQLCondition ($methods['method6']->getConditions()->condition, $parser->getProperties(),
                                                $methods['method6']->getParameters(), true);
        $this->assertEqualOrDiff(' `grp`.`grouptype` IN (1,2,3) AND `grp`.`parent_id` IS NULL ',$where);
        
        $where = $generator->BuildSQLCondition ($methods['method7']->getConditions()->condition, $parser->getProperties(),
                                                $methods['method7']->getParameters(), true);
        $this->assertEqualOrDiff(' `grp`.`grouptype` IN (\'.implode(\',\', array_map( create_function(\'$__e\',\'return intval($__e);\'), $group)).\') AND `grp`.`parent_id` \'.\'<\'.intval($parent).\'',$where);

        $where = $generator->BuildSQLCondition ($methods['method8']->getConditions()->condition, $parser->getProperties(),
                                                $methods['method8']->getParameters(), false);
        $this->assertEqualOrDiff(' `grouptype` = 2 AND `ownerlogin` = TOUPPER(\'.($login === null ? \'NULL\' : $this->_conn->quote($login,false)).\')',$where);

        $where = $generator->BuildSQLCondition ($methods['method9']->getConditions()->condition, $parser->getProperties(),
                                                $methods['method9']->getParameters(), false);
        $this->assertEqualOrDiff(' `grouptype` = 2 AND `name` = TOUPPER(\'.$this->_conn->quote($login).\')',$where);
    }


    function testBuildSQLConditionWithPattern(){
        $doc ='<?xml version="1.0" encoding="UTF-8"?>
<dao xmlns="http://jelix.org/ns/dao/1.0">
    <datasources>
        <primarytable name="grp" realname="jacl_group" primarykey="id_aclgrp" />
    </datasources>
    <record>
      <property name="id_aclgrp" fieldname="id_aclgrp" datatype="autoincrement" required="yes"/>
      <property name="parent_id" required="false" datatype="int" />
      <property name="name" fieldname="name" datatype="string" required="yes" selectpattern="TOUPPER(%s)"/>
      <property name="grouptype" fieldname="grouptype" datatype="int" required="yes"/>
      <property name="ownerlogin" fieldname="ownerlogin" datatype="string" />
    </record>
    <factory>
        <method name="method1" type="select">
            <conditions>
               <eq property="name" value="toto" />
            </conditions>
        </method>

        <method name="method2" type="select">
           <conditions>
              <neq property="name" value="toto" />
           </conditions>
           <order>
               <orderitem property="name" way="asc" />
           </order>
        </method>

        <method name="method3" type="select">
           <parameter name="login" />
           <conditions>
              <eq property="grouptype" value="2" />
              <eq property="name" expr="$login" />
           </conditions>
        </method>

        <method name="method9" type="select">
           <parameter name="login" />
           <conditions>
              <eq property="grouptype" value="2" />
              <eq property="name" expr="TOUPPER($login)" />
           </conditions>
        </method> 
        
    </factory>
</dao>';
        $parser = new jDaoParser ();
        $parser->parse(simplexml_load_string($doc));
        $generator = new testDaoGenerator('cDao_foo_Jx_bar_Jx_mysql', 'cDaoRecord_foo_Jx_bar_Jx_mysql', $parser);

        $methods=$parser->getMethods();

        $where = $generator->BuildSQLCondition ($methods['method1']->getConditions()->condition, $parser->getProperties(),
                                                $methods['method1']->getParameters(), false);
        $this->assertEqualOrDiff(' `name` = \\\'toto\\\'',$where);

        $where = $generator->BuildSQLCondition ($methods['method2']->getConditions()->condition, $parser->getProperties(),
                                                $methods['method2']->getParameters(), false);
        $this->assertEqualOrDiff(' `name` <> \\\'toto\\\'',$where);

        $where = $generator->BuildSQLCondition ($methods['method3']->getConditions()->condition, $parser->getProperties(),
                                                $methods['method3']->getParameters(), false);
        $this->assertEqualOrDiff(' `grouptype` = 2 AND `name` \'.\'=\'.$this->_conn->quote($login).\'',$where);

        // with prefix
        $where = $generator->BuildSQLCondition ($methods['method1']->getConditions()->condition, $parser->getProperties(),
                                                $methods['method1']->getParameters(), true);
        $this->assertEqualOrDiff(' `grp`.`name` = \\\'toto\\\'',$where);

        $where = $generator->BuildSQLCondition ($methods['method2']->getConditions()->condition, $parser->getProperties(),
                                                $methods['method2']->getParameters(), true);
        $this->assertEqualOrDiff(' `grp`.`name` <> \\\'toto\\\'',$where);

        $where = $generator->BuildSQLCondition ($methods['method3']->getConditions()->condition, $parser->getProperties(),
                                                $methods['method3']->getParameters(), true);
        $this->assertEqualOrDiff(' `grp`.`grouptype` = 2 AND `grp`.`name` \'.\'=\'.$this->_conn->quote($login).\'',$where);

        $where = $generator->BuildSQLCondition ($methods['method9']->getConditions()->condition, $parser->getProperties(),
                                                $methods['method9']->getParameters(), false);
        $this->assertEqualOrDiff(' `grouptype` = 2 AND `name` = TOUPPER(\'.$this->_conn->quote($login).\')',$where);

    }



    function testBuildSimpleCondition(){
        $doc ='<?xml version="1.0" encoding="UTF-8"?>
<dao xmlns="http://jelix.org/ns/dao/1.0">
    <datasources>
        <primarytable name="grp" realname="jacl_group" primarykey="id_aclgrp" />
    </datasources>
    <record>
      <property name="id_aclgrp" fieldname="id_aclgrp" datatype="autoincrement" required="yes"/>
      <property name="parent_id" required="false" datatype="int" />
      <property name="name" fieldname="name" datatype="string" required="yes"/>
      <property name="grouptype" fieldname="grouptype" datatype="int" required="yes"/>
      <property name="ownerlogin" fieldname="ownerlogin" datatype="string" />
    </record>
</dao>';
        $parser = new jDaoParser ();
        $parser->parse(simplexml_load_string($doc));
        $generator = new testDaoGenerator('cDao_foo_Jx_bar_Jx_mysql', 'cDaoRecord_foo_Jx_bar_Jx_mysql', $parser);

        $pkFields=$generator->GetPropertiesBy('PkFields');
        $this->assertTrue(count($pkFields) ==1);
        $this->assertTrue(isset($pkFields['id_aclgrp']));

        $where = $generator->BuildSimpleConditions ($pkFields);
        $this->assertEqualOrDiff(' `grp`.`id_aclgrp`\'.\'=\'.intval($id_aclgrp).\'',$where);

        $where = $generator->BuildSimpleConditions ($pkFields, 'record->');
        $this->assertEqualOrDiff(' `grp`.`id_aclgrp`\'.\'=\'.intval($record->id_aclgrp).\'',$where);

        $where = $generator->BuildSimpleConditions ($pkFields, 'record->', false);
        $this->assertEqualOrDiff(' `id_aclgrp`\'.\'=\'.intval($record->id_aclgrp).\'',$where);
    }

}
?>