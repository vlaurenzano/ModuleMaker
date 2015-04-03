<?php

/**
 * Class to assist in making modules for magento
 */
class ModuleCreator {

    /**
     * The root directory of your application
     * @var type 
     */
    protected $root;

    /**
     * The package / company name
     * @var type 
     */
    protected $packageName;

    /**
     * The app directroy
     * @var type 
     */
    protected $appDir;

    /**
     * The package directory
     * @var type 
     */
    protected $packageDir;

    /**
     * The module name that you are adding or manipulating
     * @var type 
     */
    protected $moduleName;

    /**
     * The module directory 
     * @var type 
     */
    protected $moduleDir;

    /**
     * The magento namespaces name
     * @var type 
     */
    protected $magentoName;

    /**
     * The lowercase module name / short name thing
     * @var type 
     */
    protected $lowercaseModuleName;

    /**
     * Roote, package name, module name
     * @param type $root
     * @param type $package
     * @param type $moduleName
     */
    public function __construct($root, $package, $moduleName) {
        $this->root = $root;
        $this->packageName = $package;
        $this->appDir = "$root/app";
        $this->packageDir = "$root/app/code/local/$package";
        $this->moduleName = $moduleName;
        $this->moduleDir = "{$this->packageDir}/$moduleName";
        $this->magentoName = "{$package}_{$moduleName}";        
        $this->lowercaseModuleName = strtolower($moduleName);
    }

    /**
     * Creates a new moudule
     * @param type $createWithIndexController
     */
    public function createNewModule() {
        $this->makeDirsIfNotExist(array(
            $this->packageDir,
            $this->moduleDir
        ));
        $this->createConfigXML();
        $this->registerModule();
    }

    /**
     * Creates the module directories, might want to change this to creating only when used
     */
    public function createDirectories() {
        $directories = array(
            $this->packageDir,
            $this->moduleDir,
            "{$this->moduleDir}/Block",
            "{$this->moduleDir}/controllers",
            "{$this->moduleDir}/etc",
            "{$this->moduleDir}/Helper",
            "{$this->moduleDir}/Model",
            "{$this->moduleDir}/sql",
        );
        //make the directories 
        foreach ($directories as $d) {
            $this->makeDirIfNotExist($d);
        }
    }

    /**
     * Creates the config xml
     * @param type $createIndexController
     */
    public function createConfigXML() {
        $this->makeDirIfNotExist("{$this->moduleDir}/etc");
        $config = file_get_contents( __DIR__ . "/templates/module/config/config.xml");
        $config = str_replace("Package_Module", $this->magentoName, $config);
        file_put_contents("{$this->moduleDir}/etc/config.xml", $config);
    }

    /**
     * Create a standard model w/ collection and resource
     * @param $name
     */
    public function createModelPackage($name) {
       $this->createModelConfigXml($name);        
       $this->createModel($name);
       $this->createResourceModel($name);
       $this->createCollectionModel($name);       
    }
    
    /**
     * Creates configuration and saves xml
     * @param type $name
     */
    public function createModelConfigXml($name){
        $xml = simplexml_load_file("{$this->moduleDir}/etc/config.xml");        
        if( !$xml ){
            exit( "Config xml does not exist, run create module or creat config xml first");
        }        
        $modelName = "{$this->magentoName}_Model";
        $resourceName = "{$this->lowercaseModuleName}_resource";
        $resourceClass = $modelName . '_Resource';
        $lname = strtolower($name);
        
        $xml->global->models->{$this->lowercaseModuleName}->class = $modelName;
        $xml->global->models->{$this->lowercaseModuleName}->resourceModel = $resourceName;
        $xml->global->models->$resourceName->class = $resourceClass;
        $xml->global->models->$resourceName->entities->$lname->table = $this->lowercaseModuleName . '_' . $lname;
        
        $setup = $this->lowercaseModuleName . "_setup";
        $write = $this->lowercaseModuleName . "_write";
        $read = $this->lowercaseModuleName . "_read";
        $xml->global->resources->$setup->setup->module = $this->magentoName;  
        $xml->global->resources->$setup->setup->class = 'Mage_Core_Model_Resource_Setup';  
        $xml->global->resources->$setup->connection->use = 'core_setup';  
        $xml->global->resources->$write->connection->use = 'core_write';
        $xml->global->resources->$read->connection->use = 'core_read';        
        $this->createSetupDir();                
        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xml->asXML());
        file_put_contents("{$this->moduleDir}/etc/config.xml", $dom->saveXML());        
        
    }
    
    /**
     * Creates the setup dir
     */
    public function createSetupDir(){
        $this->makeDirsIfNotExist(array(
            "$this->moduleDir/sql",
            "$this->moduleDir/sql/{$this->lowercaseModuleName}_setup",
        ));
    }
    
    
    /**
     * Creates model file
     * @param type $name
     */
    public function createModel($name) {
        $nameParts = explode("_", $name);
        $dirs = array( "{$this->moduleDir}/Model" );
        $lastName = array_pop( $nameParts );        
        $last = '';
        foreach( $nameParts as $n ){
            if( !$last ){
                $dirs[] = "{$this->moduleDir}/Model/" . $n;
                $last = "{$this->moduleDir}/Model/" . $n;
            } else {
                $dirs[] = $last . "/" . $n;
                $last .= "/$n";
            }            
        }
        $this->makeDirsIfNotExist($dirs);
        $template = str_replace(
                array(
                    "Package_Module",
                    "ModelName",
                    "shortname",
                    "model_lowercase_underscore"
                ), array(
                    $this->magentoName,
                    $name,
                    $this->lowercaseModuleName,
                    strtolower($name)
                ), file_get_contents( __DIR__ . "/templates/module/Model/Model.php"));
        $lastDir = end($dirs);        
        file_put_contents($lastDir ."/$lastName.php", $template);                       
    }
    
    /**
     * Creates Resource Module
     * @param type $name
     */
    public function createResourceModel($name) {
        $nameParts = explode("_", $name);
        $dirs = array( "{$this->moduleDir}/Model", "{$this->moduleDir}/Model/Resource" );
        $lastName = array_pop( $nameParts );
        $last = '';
        foreach( $nameParts as $n ){
             if( !$last ){
                $dirs[] = "{$this->moduleDir}/Model/Resource/" . $n;
                $last = "{$this->moduleDir}/Model/Resource/" . $n;
            } else {
                $dirs[] = $last . "/" . $n;
                $last .= "/$n";
            }                        
        }
        $this->makeDirsIfNotExist($dirs);
        $template = str_replace(
                array(
                    "Package_Module",
                    "ModelName",
                    "shortname",
                    "model_lowercase_underscore"
                ), array(
                    $this->magentoName,
                    $name,
                    $this->lowercaseModuleName,
                    strtolower($name)
                ), file_get_contents( __DIR__ . "/templates/module/Model/Resource/Model.php"));
        $lastDir = end($dirs);        
        file_put_contents($lastDir ."/$lastName.php", $template);        
    }
    
    /**
     * Creates Resource Module
     * @param type $name
     */
    public function createCollectionModel($name) {
        $nameParts = explode("_", $name);
        $dirs = array( "{$this->moduleDir}/Model", "{$this->moduleDir}/Model/Resource" );
        
        for($i = 0; $i < count($nameParts); $i++){
            $dirs[] = "{$this->moduleDir}/Model/Resource/" . join( "/" , array_slice( $nameParts, 0, $i+1 ) );
        }
        $this->makeDirsIfNotExist($dirs);
        $template = str_replace(
                array(
                    "Package_Module",
                    "ModelName",
                    "shortname",
                    "model_lowercase_underscore"
                ), array(
                    $this->magentoName,
                    $name,
                    $this->lowercaseModuleName,
                    strtolower($name)
                ), file_get_contents( __DIR__ . "/templates/module/Model/Resource/Model/Collection.php"));
        $lastDir = end($dirs);        
        file_put_contents($lastDir ."/Collection.php", $template);        
    }

    /**
     * Just make a directory if not there
     * @param $d
     */
    protected function makeDirsIfNotExist(array $dirs) {
        foreach ($dirs as $d) {
            $this->makeDirIfNotExist($d);
        }
    }

    /**
     * Just make a directory if not there
     * @param $d
     */
    protected function makeDirIfNotExist($d) {
        if (!is_dir($d)) {
            mkdir($d);
        }
    }

    /**
     * Registers module in the etc directory
     */
    public function registerModule() {
        $xml = str_replace("Package_Module", $this->magentoName, file_get_contents( __DIR__ . "/templates/etc/Package_Module.xml"));
        file_put_contents("{$this->appDir}/etc/modules/{$this->magentoName}.xml", $xml);
    }


}
