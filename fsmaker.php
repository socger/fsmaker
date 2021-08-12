<?php
/**
 * @author  Carlos García Gómez <carlos@facturascripts.com>
 */

if (php_sapi_name() !== 'cli') {
    die("Usar: php fsmaker.php");
}

class fsmaker
{
    const TRANSLATIONS = 'ca_ES,de_DE,en_EN,es_AR,es_CL,es_CO,es_CR,es_DO,es_EC,es_ES,es_GT,es_MX,es_PE,es_UY,eu_ES,fr_FR,gl_ES,it_IT,pt_PT,va_ES';
    const VERSION = 0.4;

    public function __construct($argv)
    {
        if(count($argv) < 2) {
            echo $this->help();
        } elseif($argv[1] === 'plugin') {
            echo $this->createPluginAction();
        } elseif($argv[1] === 'model') {
            echo $this->createModelAction();
        } elseif($argv[1] === 'controller') {
            echo $this->createControllerAction();
        } elseif($argv[1] === 'translations') {
            echo $this->updateTranslationsAction();
        } elseif($argv[1] === 'extension') {
            echo $this->createExtensionAction();
        } else {
            echo $this->help();
        }
    }

    private function createControllerAction()
    {
        $option = (int) $this->prompt('1=Controller, 2=ListController, 3=EditController');
        if(false === $this->isCoreFolder() && false === $this->isPluginFolder()) {
            return "Esta no es la carpeta raíz del plugin.\n";
        } elseif($option === 2) {
            $modelName = $this->prompt('Nombre del modelo a utilizar', '/^[A-Z][a-zA-Z0-9_]*$/');
            return $this->createListController($modelName);
        } elseif($option === 3) {
            $modelName = $this->prompt('Nombre del modelo a utilizar', '/^[A-Z][a-zA-Z0-9_]*$/');
            return $this->createEditController($modelName);
        } elseif($option < 1 || $option > 3) {
            return "Opción no válida.\n";
        }

        $name = $this->prompt('Nombre del controlador', '/^[A-Z][a-zA-Z0-9_]*$/');
        $filename = $this->isCoreFolder() ? 'Core/Controller/'.$name.'.php' : 'Controller/'.$name.'.php';
        if(file_exists($filename)) {
            return "El controlador ".$name." ya existe.\n";
        } elseif(empty($name)) {
            return '';
        }

        echo '* '.$filename."\n";
        file_put_contents($filename, '<?php
namespace FacturaScripts\\'.$this->getNamespace().'\\Controller;

class '.$name.' extends \\FacturaScripts\\Core\\Base\\Controller
{
    public function getPageData() {
        $pageData = parent::getPageData();
        $pageData["title"] = "'.$name.'";
        $pageData["menu"] = "admin";
        $pageData["icon"] = "fas fa-page";
        return $pageData;
    }
    
    public function privateCore(&$response, $user, $permissions) {
        parent::privateCore($response, $user, $permissions);
        /// tu código aquí
    }
}');
        $viewFilename = $this->isCoreFolder() ? 'Core/View/'.$name.'.html.twig' : 'View/'.$name.'.html.twig';
        if(file_exists($viewFilename)) {
            return;
        }

        echo '* '.$viewFilename."\n";
        file_put_contents($viewFilename, '{% extends "Master/MenuTemplate.html.twig" %}

{% block body %}
    {{ parent() }}
{% endblock %}

{% block css %}
    {{ parent() }}
{% endblock %}

{% block javascripts %}
    {{ parent() }}
{% endblock %}');
    }

    private function createCron($folder)
    {
        echo '* '.$folder."/Cron.php\n";
        file_put_contents($folder.'/Cron.php', "<?php
namespace FacturaScripts\\Plugins\\".$folder.';

class Cron extends \\FacturaScripts\\Core\\Base\\CronClass
{
    public function run() {
        /*
        if ($this->isTimeForJob("my-job-name", "6 hours")) {
            /// su código aquí
            $this->jobDone("my-job-name");
        }
        */
    }
}');
    }

    private function createEditController($modelName)
    {
        $filename = $this->isCoreFolder() ? 'Core/Controller/Edit'.$modelName.'.php' : 'Controller/Edit'.$modelName.'.php';
        if(file_exists($filename)) {
            return "El controlador ".$filename." ya existe.\n";
        } elseif(empty($modelName)) {
            return '';
        }

        echo '* '.$filename."\n";
        file_put_contents($filename, '<?php
namespace FacturaScripts\\'.$this->getNamespace().'\\Controller;

class Edit'.$modelName.' extends \\FacturaScripts\\Core\\Lib\\ExtendedController\\EditController
{
    public function getModelClassName() {
        return "'.$modelName.'";
    }

    public function getPageData() {
        $pageData = parent::getPageData();
        $pageData["title"] = "'.$modelName.'";
        $pageData["icon"] = "fas fa-search";
        return $pageData;
    }
}');
        $xmlviewFilename = $this->isCoreFolder() ? 'Core/XMLView/Edit'.$modelName.'.xml' : 'XMLView/Edit'.$modelName.'.xml';
        if(file_exists($xmlviewFilename)) {
            return '';
        }

        echo '* '.$xmlviewFilename."\n";
        file_put_contents($xmlviewFilename, '<?xml version="1.0" encoding="UTF-8"?>
<view>
    <columns>
        <group name="data" numcolumns="12">
            <column name="code" display="none" order="100">
                <widget type="text" fieldname="id" />
            </column>
            <column name="name" order="110">
                <widget type="text" fieldname="name" />
            </column>
            <column name="creation-date" order="120">
                <widget type="datetime" fieldname="creationdate" readonly="dinamic" />
            </column>
        </group>
    </columns>
</view>');
    }

    private function createGitIgnore($folder)
    {
        echo '* '.$folder."/.gitignore\n";
        file_put_contents($folder.'/.gitignore', "/.idea/\n/nbproject/\n/node_modules/\n"
            ."/vendor/\n.DS_Store\n.htaccess\n*.cache\n*.lock\n.vscode\n*.code-workspace");
    }

    private function createIni($folder)
    {
        echo '* '.$folder."/facturascripts.ini\n";
        file_put_contents($folder.'/facturascripts.ini', "description = '".$folder."'
min_version = 2021
name = ".$folder."
version = 0.1");
    }

    private function createInit($folder)
    {
        echo '* '.$folder."/Init.php\n";
        file_put_contents($folder.'/Init.php', "<?php
namespace FacturaScripts\\Plugins\\".$folder.";

class Init extends \\FacturaScripts\\Core\\Base\\InitClass
{
    public function init() {
        /// se ejecutar cada vez que carga FacturaScripts (si este plugin está activado).
    }

    public function update() {
        /// se ejecutar cada vez que se instala o actualiza el plugin
    }
}");
    }

    private function createListController($modelName)
    {
        $menu = $this->prompt('Menú');
        $title = $this->prompt('Título');
        $filename = $this->isCoreFolder() ? 'Core/Controller/List'.$modelName.'.php' : 'Controller/List'.$modelName.'.php';
        if(file_exists($filename)) {
            return "El controlador ".$filename." ya existe.\n";
        } elseif(empty($modelName)) {
            return '';
        }

        echo '* '.$filename."\n";
        file_put_contents($filename, '<?php
namespace FacturaScripts\\'.$this->getNamespace().'\\Controller;

class List'.$modelName.' extends \\FacturaScripts\\Core\\Lib\\ExtendedController\\ListController
{
    public function getPageData() {
        $pageData = parent::getPageData();
        $pageData["title"] = "'.$title.'";
        $pageData["menu"] = "'.$menu.'";
        $pageData["icon"] = "fas fa-search";
        return $pageData;
    }

    protected function createViews() {
        $this->createViews'.$modelName.'();
    }

    protected function createViews'.$modelName.'(string $viewName = "List'.$modelName.'") {
        $this->addView($viewName, "'.$modelName.'", "'.$title.'");
        $this->addOrderBy($viewName, ["id"], "id");
        $this->addOrderBy($viewName, ["name"], "name", 1);
        $this->addSearchFields($viewName, ["id", "name"]);
    }
}');
        $xmlviewFilename = $this->isCoreFolder() ? 'Core/XMLView/List'.$modelName.'.xml' : 'XMLView/List'.$modelName.'.xml';
        if(file_exists($xmlviewFilename)) {
            return '';
        }

        echo '* '.$xmlviewFilename."\n";
        file_put_contents($xmlviewFilename, '<?xml version="1.0" encoding="UTF-8"?>
<view>
    <columns>
        <column name="code" order="100">
            <widget type="text" fieldname="id" />
        </column>
        <column name="name" order="110">
            <widget type="text" fieldname="name" />
        </column>
        <column name="creation-date" display="right" order="120">
            <widget type="datetime" fieldname="creationdate" />
        </column>
    </columns>
</view>');
    }

    private function createModelAction()
    {
        $name = $this->prompt('Nombre del modelo (singular)', '/^[A-Z][a-zA-Z0-9_]*$/');
        $tableName = strtolower($this->prompt('Nombre de la tabla (plural)', '/^[a-zA-Z][a-zA-Z0-9_]*$/'));
        if(empty($name) || empty($tableName)) {
            return '';
        } elseif(false === $this->isCoreFolder() && false === $this->isPluginFolder()) {
            return "Esta no es la carpeta raíz del plugin.\n";
        }

        $filename = $this->isCoreFolder() ? 'Core/Model/'.$name.'.php' : 'Model/'.$name.'.php';
        if(file_exists($filename)) {
            return "El modelo ".$name." ya existe.\n";
        }

        echo '* '.$filename."\n";
        file_put_contents($filename, '<?php
namespace FacturaScripts\\'.$this->getNamespace().'\\Model;

class '.$name.' extends \\FacturaScripts\\Core\\Model\\Base\\ModelClass
{
    use \\FacturaScripts\\Core\\Model\\Base\\ModelTrait;

    public $creationdate;
    public $id;
    public $name;

    public function clear() {
        parent::clear();
        $this->creationdate = \date(self::DATETIME_STYLE);
    }

    public static function primaryColumn() {
        return "id";
    }

    public static function tableName() {
        return "'.$tableName.'";
    }
}');
        $tableFilename = $this->isCoreFolder() ? 'Core/Table/'.$tableName.'.xml' : 'Table/'.$tableName.'.xml';
        if(false === file_exists($tableFilename)) {
            echo '* '.$tableFilename."\n";
            file_put_contents($tableFilename, '<?xml version="1.0" encoding="UTF-8"?>
<table>
    <column>
        <name>creationdate</name>
        <type>timestamp</type>
    </column>
    <column>
        <name>id</name>
        <type>serial</type>
    </column>
    <column>
        <name>name</name>
        <type>character varying(100)</type>
    </column>
    <constraint>
        <name>'.$tableName.'_pkey</name>
        <type>PRIMARY KEY (id)</type>
    </constraint>
</table>');
        }

        if($this->prompt('¿Crear EditController? 1=Si, 0=No') === '1') {
            $this->createEditController($name);
        }

        if($this->prompt('¿Crear ListController? 1=Si, 0=No') === '1') {
            $this->createListController($name);
        }
    }

    private function createPluginAction()
    {
        $name = $this->prompt('Nombre del plugin', '/^[A-Z][a-zA-Z0-9_]*$/');
        if(empty($name)) {
            return '';
        } elseif(file_exists('.git') || file_exists('.gitignore') || file_exists('facturascripts.ini')) {
            return "No se puede crear un plugin en esta carpeta.\n";
        } elseif(file_exists($name)) {
            return "El plugin ".$name." ya existe.\n";
        }
        
        mkdir($name, 0755);
        $folders = [
            'Assets/CSS','Assets/Images','Assets/JS','Controller','Data/Codpais/ESP','Data/Lang/ES','Extension/Controller',
            'Extension/Model','Extension/Table','Extension/XMLView','Model/Join','Table','Translation','View','XMLView'
        ];
        foreach($folders as $folder) {
            echo '* '.$name.'/'.$folder."\n";
            mkdir($name.'/'.$folder, 0755, true);
        }

        foreach(explode(',', self::TRANSLATIONS) as $filename) {
            echo '* '.$name.'/Translation/'.$filename.".json\n";
            file_put_contents($name.'/Translation/'.$filename.'.json', '{
    "'.$name.'": "'.$name.'"
}');
        }

        $this->createGitIgnore($name);
        $this->createCron($name);
        $this->createIni($name);
        $this->createInit($name);
    }

    private function getNamespace()
    {
        if($this->isCoreFolder()) {
            return 'Core';
        }

        $ini = parse_ini_file('facturascripts.ini');
        return 'Plugins\\'.$ini['name'];
    }

    private function help()
    {
        return 'FacturaScripts Maker v' . self::VERSION . "

create:
$ fsmaker plugin
$ fsmaker model
$ fsmaker controller
$ fsmaker extension

download:
$ fsmaker translations\n";
    }

    private function isCoreFolder()
    {
        return file_exists('Core/Translation') && false === file_exists('facturascripts.ini');
    }

    private function isPluginFolder()
    {
        return file_exists('facturascripts.ini');
    }

    private function prompt($label, $pattern = '')
    {
        echo $label . ': ';
        $matches = [];
        $value = trim(fgets(STDIN));
        if(!empty($pattern) && 1 !== preg_match($pattern, $value, $matches)) {
            echo "Valor no válido. Debe cumplir: ".$pattern."\n";
            return '';
        }

        return $value;
    }

    private function updateTranslationsAction()
    {
        $folder = '';
        $project = '';
        if($this->isPluginFolder()) {
            $folder = 'Translation/';
            $ini = parse_ini_file('facturascripts.ini');
            $project = $ini['name'] ?? '';
        } elseif($this->isCoreFolder()) {
            $folder = 'Core/Translation/';
            $project = 'CORE-2018';
        } else {
            return "Esta no es la carpeta raíz del plugin.\n";
        }

        if(empty($project)) {
            return "Proyecto desconocido.\n";
        }

        /// download json from facturascripts.com
        foreach (explode(',', self::TRANSLATIONS) as $filename) {
            echo "D ".$folder.$filename.".json";
            $url = "https://facturascripts.com/EditLanguage?action=json&project=".$project."&code=".$filename;
            $json = file_get_contents($url);
            if(!empty($json) && strlen($json) > 10) {
                file_put_contents($folder.$filename.'.json', $json);
                echo "\n";
                continue;
            }

            echo " - vacío\n";
        }
    }

    private function createExtensionAction()
    {
       $option = (int) $this->prompt('Extensión de ... 1=Tabla, 2=Modelo, 3=Controlador, 4=XMLView');
        if(false === $this->isCoreFolder() && false === $this->isPluginFolder()) {
            return "Esta no es la carpeta raíz del plugin.\n";
        } elseif($option === 1) {
            $name = strtolower($this->prompt('Nombre de la tabla (plural)', '/^[a-zA-Z][a-zA-Z0-9_]*$/'));
            return $this->createExtensionTabla($name);
        } elseif($option === 2) {
            $name = $this->prompt('Nombre del modelo (singular)', '/^[A-Z][a-zA-Z0-9_]*$/');
            return $this->createExtensionModelo($name);
        } elseif($option === 3) {
            $name = $this->prompt('Nombre del controlador', '/^[A-Z][a-zA-Z0-9_]*$/');
            return $this->createExtensionControlador($name);
        } elseif($option === 4) {
            $name = $this->prompt('Nombre del XMLView', '/^[A-Z][a-zA-Z0-9_]*$/');
            return $this->createExtensionXMLView($name);
        } elseif($option < 1 || $option > 4) {
            return "Opción no válida.\n";
        }
    }
    
    private function createExtensionModelo($name)
    {
        if(empty($name)) {
            return '';
        }

        $filename = 'Extension/Model/' . $name . '.php';
        if(file_exists($filename)) {
            return "La extensión del modelo " . $name . " ya existe.\n";
        }
        
        echo '* '.$filename."\n";
        file_put_contents($filename, '<?php
namespace FacturaScripts\\'.$this->getNamespace().'\\Extension\\Model;

class '.$name.'
{
    /* 
    // Ejemplo para añadir un método ... añadir el método usado()
    public function usado() {
        return function() {
            return $this->usado;
        };
    }
    
    // ***************************************
    // ** Métodos disponibles para extender **
    // ***************************************
    
    // clear()
    public function clear() {
       return function() {
            /// tu código aquí
         };
    }
    
    // delete() se ejecuta una vez realizado el delete() del modelo.
    public function delete() {
       return function() {
            /// tu código aquí
         };
    }
    
    // deleteBefore() se ejecuta antes de hacer el delete() del modelo. Si devolvemos false, impedimos el delete().
    public function deleteBefore() {
       return function() {
            /// tu código aquí
         };
    }

    // save() se ejecuta una vez realizado el save() del modelo.
    public function save() {
       return function() {
            /// tu código aquí
         };
    }
    
    // saveBefore() se ejecuta antes de hacer el save() del modelo. Si devolvemos false, impedimos el save().
    public function saveBefore() {
       return function() {
            /// tu código aquí
         };
    }

    // saveInsert() se ejecuta una vez realizado el saveInsert() del modelo.
    public function saveInsert() {
       return function() {
            /// tu código aquí
         };
    }
    
    // saveInsertBefore() se ejecuta antes de hacer el saveInsert() del modelo. Si devolvemos false, impedimos el saveInsert().
    public function saveInsertBefore() {
       return function() {
            /// tu código aquí
         };
    }
    
    // saveUpdate() se ejecuta una vez realizado el saveUpdate() del modelo.
    public function saveUpdate() {
       return function() {
            /// tu código aquí
         };
    }
    
    // saveUpdateBefore() se ejecuta antes de hacer el saveUpdate() del modelo. Si devolvemos false, impedimos el saveUpdate().
    public function saveUpdateBefore() {
       return function() {
            /// tu código aquí
         };
    }
    */

}');
        $aDevolver = "La extensión del modelo fué creada correctamente ... " . $name . "\n\n"
                   . "Las extensiones de archivos xml se integran automáticamente al activar el plugin o reconstruir Dinamic.\n"
                   . "En cambio, las extensiones de archivo php se deben cargar explícitamente, y se deben hacer desde el \n"
                   . "archivo Init.php del plugin, en el método init().\n\n"
                   . "Para más información visite https://facturascripts.com/publicaciones/extensiones-de-modelos";
        return $aDevolver;
    }

    private function createExtensionTabla($name)
    {
        if(empty($name)) {
            return '';
        }

        $filename = 'Extension/Table/' . $name . '.xml';
        if(file_exists($filename)) {
            return "La extensión de la tabla " . $name . " ya existe.\n";
        }
        
        echo '* '.$filename."\n";
        file_put_contents($filename, '<?xml version="1.0" encoding="UTF-8"?>
<table>
    <column>
        <name>usado</name>
        <type>boolean</type>
    </column>
</table>

');
     
    }

    private function createExtensionControlador($name)
    {
        if(empty($name)) {
            return '';
        }

        $filename = 'Extension/Controller/' . $name . '.php';
        if(file_exists($filename)) {
            return "La extensión del controlador " . $name . " ya existe.\n";
        }
        
        echo '* '.$filename."\n";
        file_put_contents($filename, '<?php
namespace FacturaScripts\\'.$this->getNamespace().'\\Extension\\Controller;

class '.$name.'
{
    /* 
    // createViews() se ejecuta una vez realiado el createViews() del controlador.
    public function createViews() {
       return function() {
          /// tu código aquí
       };
    }

    // execAfterAction() se ejecuta tras el execAfterAction() del controlador.
    public function execAfterAction() {
       return function($action) {
          /// tu código aquí
       };
    }

    // execPreviousAction() se ejecuta después del execPreviousAction() del controlador. Si devolvemos false detenemos la ejecución del controlador.
    public function execPreviousAction() {
       return function($action) {
          /// tu código aquí
       };
    }

    // loadData() se ejecuta tras el loadData() del controlador. Recibe los parámetros $viewName y $view.
    public function loadData() {
       return function($viewName, $view) {
          /// tu código aquí
       };
    }
    */

}');
     
        $aDevolver = "La extensión del controlador fué creada correctamente ... " . $name . "\n\n"
                   . "Las extensiones de archivos xml se integran automáticamente al activar el plugin o reconstruir Dinamic.\n"
                   . "En cambio, las extensiones de archivo php se deben cargar explícitamente, y se deben hacer desde el \n"
                   . "archivo Init.php del plugin, en el método init().\n\n"
                   . "Para más información visite https://facturascripts.com/publicaciones/extensiones-de-controladores";
        return $aDevolver;
        
    }

    private function createExtensionXMLView($name)
    {
        if(empty($name)) {
            return '';
        }

        $filename = 'Extension/XMLView/' . $name . '.xml';
        if(file_exists($filename)) {
            return "El fichero XMLView " . $name . " ya existe.\n";
        }
        
        echo '* '.$filename."\n";
        file_put_contents($filename, '<?xml version="1.0" encoding="UTF-8"?>
<view>
    <columns>
        <group name="options" numcolumns="12" valign="bottom">
           <column name="usado">
              <widget type="checkbox" fieldname="usado" />
           </column>
        </group>
    </columns>
</view>

');
     
    }

    
}
    
new fsmaker($argv);




