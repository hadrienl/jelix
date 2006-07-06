<?php
/**
* @package    jelix
* @subpackage core
* @version    $Id$
* @author     Jouanneau Laurent
* @contributor
* @copyright  2005-2006 Jouanneau laurent
* @link        http://www.jelix.org
* @licence    GNU Lesser General Public Licence see LICENCE file or http://www.gnu.org/licenses/lgpl.html
*/


class jCoordinator {

   /**
    * liste des plugins utilis�s
    * @var  array
    */
    public $plugins=array();

    /**
     * Reponse courante
     * @var jResponse
     */
    public $response = null;

    /**
     * @var jRequest
     */
    public $request = null;

    /**
     * @var jActionDesc
     */
    public $action = null;
    /**
     * @var string
     */
    private $configFile;

    /**
     * @var string
     */
    public $moduleName;
    /**
     * @var string
     */
    public $actionName;

    /**
     * List of all errors
     * @var array
     */
    public $errorMessages=array();

    /**
     * @param  string $configFile name of the ini file to configure the framework
     */
    function __construct ($configFile) {
        global $gJCoord, $gJConfig;

        $gJCoord =  $this;

        if(JELIX_APP_TEMP_PATH=='/'){ // le realpath dans application.ini.php a renvoy� false...
            die('Jelix Error: Application temp directory doesn\'t exist !');
        }
        if(!is_writable(JELIX_APP_TEMP_PATH)){
            die('Jelix Error: Application temp directory is not writable');
        }
        // load configuration data
        $this->configFile = $configFile;
        $gJConfig = jConfig::load($configFile);


        // set Error and exception handler
        // ne devrait �tre d�sactiv� que lors de certains tests de jelix
        if($gJConfig->use_error_handler){
            set_error_handler('jErrorHandler');
            set_exception_handler('JExceptionHandler');
        }

        $this->_loadPlugins();
    }

    /**
     * instanciation des plugins
     */
    private function _loadPlugins(){

        foreach($GLOBALS['gJConfig']->plugins as $name=>$conf){
            if($conf && isset($GLOBALS['gJConfig']->_pluginsPathList[$name])){
                if($conf=='1')
                    $conf=$name.'.plugin.ini.php';
                if(file_exists(JELIX_APP_CONFIG_PATH.$conf)){
                   $conf = parse_ini_file(JELIX_APP_CONFIG_PATH.$conf,true);
                }else{
                    $conf = array();
                }
                include( $GLOBALS['gJConfig']->_pluginsPathList[$name].$name.".plugin.php");
                $class= $name.'Plugin';
                $this->plugins[strtolower($name)] = new $class($conf);
            }
        }
    }

    /**
     * stocke un message d'erreur/warning/notice � prendre en compte par les r�ponses
     * @param  string $type  type d'erreur 'error', 'warning', 'notice'
     * @param  integer $code  code d'erreur
     * @param  string $message le message d'erreur
     * @param  string $file  nom du fichier o� s'est produite l'erreur
     * @param  integer $line  ligne o� s'est produite l'erreur
     * @return boolean    true= arret immediat ordonn�, false = on laisse le gestionnaire d'erreur agir en cons�quence
     */
    public function addErrorMsg($type, $code, $message, $file, $line){
        $this->errorMessages[] = array($type, $code, $message, $file, $line);
        if(!$this->response){
            if($this->initDefaultResponseOfRequest())
                return true;
        }
        return !$this->response->acceptSeveralErrors();
    }

    /**
    * Fonction principale du coordinateur � appeler dans le index.php pour d�marrer
    * le traitement de l'action
    * @param  jRequest  $request the request data
    */
    public function process ($request){
        global $gJConfig;

        $this->request = $request;
        $this->request->init();
        session_start();

        $this->moduleName = $this->request->getParam('module');
        $this->actionName = $this->request->getParam('action');

        if(empty($this->moduleName)){
            $this->moduleName = $gJConfig->defaultModule;
            if(empty($this->actionName))
               $this->actionName = $gJConfig->defaultAction;
        }
        if(empty($this->actionName)){
            $this->actionName = 'default_index';
        }


        // verification du module
        if(!in_array($this->moduleName,$gJConfig->_trustedModules)){
            trigger_error(jLocale::get('jelix~errors.module.untrusted',$this->moduleName), E_USER_ERROR);
            return;
        }

        jContext::push ($this->moduleName);

        $this->action = new jSelectorAct($this->actionName);

        $ctrl = $this->getController($this->action);

        $pluginparams = array();
        if(isset($ctrl->pluginParams['*'])){
            $pluginparams = $ctrl->pluginParams['*'];
        }

        if(isset($ctrl->pluginParams[$this->action->method])){
            $pluginparams = array_merge($pluginparams, $ctrl->pluginParams[$this->action->method]);
        }

        foreach ($this->plugins as $name => $obj){
            $result = $this->plugins[$name]->beforeAction ($pluginparams);
            if($result){
               $this->action = $result;
               $ctrl = $this->getController($this->action);
               break;
            }
        }

        $this->response = $ctrl->{$this->action->method}();

        if($this->response == null){
            trigger_error(jLocale::get('jelix~errors.response.missing',$this->action->toString()), E_USER_ERROR);
            return;
        }

        foreach ($this->plugins as $name => $obj){
           $this->plugins[$name]->beforeOutput ();
        }

        // envoie de la r�ponse
        if(!$this->response->output()){
           $this->response->outputErrors();
        }

        foreach ($this->plugins as $name => $obj){
            $this->plugins[$name]->afterProcess ();
        }

        jContext::pop();
    }

    private function getController($selector){

        $ctrlpath = $selector->getPath();
        $class = $selector->getClass();

        if(!file_exists($ctrlpath)){
            trigger_error(jLocale::get('jelix~errors.ad.controller.file.unknow',array($this->actionName,$ctrlpath)),E_USER_ERROR);
            return;
        }
        require_once($ctrlpath);
        if(!class_exists($class,false)){
            trigger_error(jLocale::get('jelix~errors.ad.controller.class.unknow',array($this->actionName,$class, $ctrlpath)),E_USER_ERROR);
            return;
        }

        $ctrl = new $class($this->request);
        if($ctrl instanceof jIRestController){
            $method = $selector->method = strtolower($_SERVER['REQUEST_METHOD']);
        }elseif(!method_exists($ctrl,$selector->method)){
            trigger_error(jLocale::get('jelix~errors.ad.controller.method.unknow',array($this->actionName,$selector->method, $class, $ctrlpath)),E_USER_ERROR);
            return;
        }
        return $ctrl;
    }


    /**
     *
     * @param string $name
     */
    public function initDefaultResponseOfRequest(){
        global $gJConfig;

        $type= $this->request->defaultResponseType;

        if(!isset($gJConfig->responses[$type])){
            return jLocale::get('jelix~errors.default.response.type.unknow',array($this->moduleName.'~'.$this->actionName,$type));
        }

        $respclass = $gJConfig->responses[$type];
        if(file_exists($path=JELIX_LIB_RESPONSE_PATH.$respclass.'.class.php')){
           require_once ($path);
        }elseif(file_exists($path=JELIX_APP_PATH.'responses/'.$respclass.'.class.php')){
           require_once ($path);
        }else{
           return jLocale::get('jelix~errors.default.response.not.loaded',array($this->moduleName.'~'.$this->actionName,$type));
        }

        $this->response = new $respclass();

        return false;
    }


    /**
    * permet � un traitement exterieur (page, zone) de recuperer un element de configuration d'un plugin
    * @param string   $plugin_name   nom du plugin
    * @param string   $plugin_parameter_name   nom de la propriete de l'objet de configuration du plugin
    */
    /*function getPluginConf ($pluginName , $plugin_parameter_name){
        $pluginName = strtolower ($pluginName);
        if (isset ($this->plugins[$pluginName])&& isset($this->plugins[$pluginName]->config->$plugin_parameter_name) ) {
               return $this->plugins[$pluginName]->config->$plugin_parameter_name;
        }
        return null;
    }*/

    /**
    * gets a given plugin if registered
    * @param string   $plugin_name   nom du plugin
    * @param boolean  $required  if the plugin is required or not. If true, will trigger a fatal_error if the plugin is not registered.
    */
    function getPlugin ($pluginName, $required = true){
        $pluginName = strtolower ($pluginName);
        if (isset ($this->plugins[$pluginName])){
            $plugin = $this->plugins[$pluginName];
        }else{
            if ($required){
                trigger_error (jLocale::get ('jelix~errors.plugin.unregister', $pluginName), E_USER_ERROR);
            }
            $plugin = null;
        }
        return $plugin;
    }

    /**
    * Says if the given plugin $name is enabled
    * @param string $pluginName
    * @return boolean true : plugin is ok
    */
    public function isPluginEnabled ($pluginName){
        return isset ($this->plugins[strtolower ($pluginName)]);
    }

    /**
    * Says if the given module $name is enabled
    * @param string $moduleName
    * @return boolean true : plugin is ok
    */
    public function isModuleEnabled ($moduleName){
        return in_array($moduleName, $GLOBALS['gJConfig']->_trustedModules);
    }

}
?>
