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

class ListenerTestevents extends jEventListener{

   /**
   *
   */
   function onTestEventWithParams ($event) {
        $event->Add(array('params'=>$event->getParam('hello')));

   }

   /**
   *
   */
   function onTestEvent ($event) {
        $event->Add(array('module'=>'unittest','ok'=>true));
   }

}
?>