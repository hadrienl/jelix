<?php
/**
* @package    jelix
* @subpackage jforms
* @version    $Id$
* @author     Jouanneau Laurent
* @contributor
* @copyright   2006 Jouanneau laurent
* @link        http://www.jelix.org
* @licence    GNU Lesser General Public Licence see LICENCE file or http://www.gnu.org/licenses/lgpl.html
*/

require_once(JELIX_LIB_FORMS_PATH.'jFormsControl.class.php');

class jFormsCompiler implements jISimpleCompiler {

   public function compile($selector){
      global $gJCoord;
      $sel = clone $selector;

      $sourceFile = $selector->getPath();
      $cachefile = $selector->getCompiledFilePath();

      // compilation du fichier xml
      $xml = simplexml_load_file ( $sourceFile);
      if(!$xml){
         return false;
      }

      /*if(!isset($xml->model)){
         trigger_error(jLocale::get('jelix~formserr.missing.tag',array('model',$sourceFile)), E_USER_ERROR);
         return false;
      }
      */

      $source=array();
      foreach($xml->children() as $controltype=>$control){

         $class = 'jFormsControl'.$controltype;

         if(!class_exists($class,false)){
            trigger_error(jLocale::get('jelix~formserr.unknow.tag',array($controltype,$sourceFile)), E_USER_ERROR);
            return false;
         }


         if(!isset($control['ref'])){
            trigger_error(jLocale::get('jelix~formserr.attribute.missing',array('ref',$controltype,$sourceFile)), E_USER_ERROR);
            return false;
         }
         $source[]='$ctrl= new '.$class.'(\''.(string)$control['ref'].'\');';
         if(isset($control['type'])){
            $source[]='$ctrl->datatype=\''.(string)$control['type'].'\';';
         }
         if(isset($control['readonly'])){
            $readonly=(string)$control['readonly'];

            $source[]='$ctrl->readonly='.($readonly=='true'?'true':'false').';';
         }
         if(isset($control['required'])){
            $required=(string)$control['required'];
            $source[]='$ctrl->required='.($required=='true'?'true':'false').';';
         }
         
         
         if(!isset($control->label)){
            trigger_error(jLocale::get('jelix~formserr.tag.missing',array('label',$controltype,$sourceFile)), E_USER_ERROR);
            return false;
         }
         
         if(isset($control->label['locale'])){
             $source[]='$ctrl->labellocale=\''.(string)$control->label['locale'].'\';';
         }else{
             $source[]='$ctrl->label=\''.(string)$control->label.'\';';
         }
         
         $source[]='$this->addControl($ctrl);';
      }





        return true;
    }
    
    
    
    
    
}



interface jIFormGenerator {

   // on indique un objet form
   // il renvoi dans un tableau le code g�n�r� correspondant
   /*
   startform : code g�n�r� pour le debut du formulaire (balise <form> en html) Peut contenir %ATTR%
   head : code g�n�r� � ajouter dans l'en-t�te de page
   controls : tableau assoc de chaque contr�le g�n�r�. Peuvent contenir %ATTR%
   endform : code g�n�r� pour la fin du formulaire


   %ATTR% : remplac�s par les attributs suppl�mentaires indiqu�s par l'utilisateur dans le template
   */
   function buildForm($formObject);

}


?>