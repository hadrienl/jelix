<?php
/**
* @package     testapp
* @subpackage  unittest module
* @version     $Id$
* @author      Jouanneau Laurent
* @contributor
* @copyright   2006 Jouanneau laurent
* @link        http://www.jelix.org
* @licence     GNU Lesser General Public Licence see LICENCE file or http://www.gnu.org/licenses/lgpl.html
*/

require_once(JELIX_LIB_DAO_PATH.'jDaoCompiler.class.php');
require_once(JELIX_LIB_DAO_PATH.'jDaoConditions.class.php');

require_once(dirname(__FILE__).'/junittestcase.class.php');

class UTDao_Conditions extends jUnitTestCase {


    function testConditions() {

        try{
            $cond=new jDaoConditions();

            $check='<?xml version="1.0"?>
            <object class="jDaoConditions">
                <array p="order">array()</array>
                <boolean m="isEmpty()" value="true" />
                <object p="condition" class="jDaoCondition">
                    <null p="parent" />
                    <array p="conditions">array()</array>
                    <array p="group">array()</array>
                    <string p="glueOp" value="AND"/>
                </object>
            </object>';

            $this->assertComplexIdenticalStr($cond, $check);


            $cond=new jDaoConditions();
            $cond->addItemOrder('foo', 'DESC');
            $check='<?xml version="1.0"?>
            <object class="jDaoConditions">
                <array p="order">array("foo"=>"DESC")</array>
                <boolean m="isEmpty()" value="false" />
                <object p="condition" class="jDaoCondition">
                    <null p="parent" />
                    <array p="conditions">array()</array>
                    <array p="group">array()</array>
                    <string p="glueOp" value="AND"/>
                </object>
            </object>';

            $this->assertComplexIdenticalStr($cond, $check);


            $cond=new jDaoConditions();
            $cond->addCondition('foo', '=', 'toto', false);

            $check='<?xml version="1.0"?>
            <object class="jDaoConditions">
                <array p="order">array()</array>
                <boolean m="isEmpty()" value="false" />
                <object p="condition" class="jDaoCondition">
                    <null p="parent" />
                    <array p="conditions">array(array("field_id"=>"foo","value"=>"toto", "operator"=>"=", "expr"=>false))</array>
                    <array p="group">array()</array>
                    <string p="glueOp" value="AND"/>
                </object>
            </object>';

            $this->assertComplexIdenticalStr($cond, $check);


            $cond->startGroup('OR');
            $cond->addCondition('foo1', '<', '100');
            $cond->addCondition('foo1', '>', '0');
            $cond->endGroup ();
            $check='<?xml version="1.0"?>
            <object class="jDaoConditions">
                <array p="order">array()</array>
                <boolean m="isEmpty()" value="false" />
                <object p="condition" class="jDaoCondition">
                    <null p="parent" />
                    <array p="conditions">array(array("field_id"=>"foo","value"=>"toto", "operator"=>"=", "expr"=>false))</array>
                    <array p="group">
                        <object p="condition" class="jDaoCondition">
                            <object p="parent" class="jDaoCondition" />
                            <array p="conditions">array(
                             array("field_id"=>"foo1","value"=>"100", "operator"=>"&lt;", "expr"=>false),
                             array("field_id"=>"foo1","value"=>"0", "operator"=>"&gt;", "expr"=>false))</array>
                            <array p="group">array()</array>
                            <string p="glueOp" value="OR"/>
                        </object>
                    </array>
                    <string p="glueOp" value="AND"/>
                </object>
            </object>';

            $this->assertComplexIdenticalStr($cond, $check);

        }catch(jDaoXmlException $e){
            $this->fail("Exception sur le contenu xml inattendue : ".$e->getLocaleMessage().' ('.$e->getMessage().')');
        }catch(Exception $e){
            $this->fail("Exception inconnue : ".$e->getMessage());
        }
    }



}


?>