<?php
namespace DIQA\Util\Configuration;

use Exception;

/**
 * Loads the configuration files in a specific order:
 * 1. $MW/env-default.json
 * 2. defaultSettings.php (for an extension)
 * 3. $MW/env.json
 * 4. $MW/LocalVariables.php (for all of MW and extensions)
 *
 * Files coming later in this list will overwrite (and can use) values
 * from files that come earlier in this list.
 *
 * LocalVariables.php should not touch any configuration from the env*.json files,
 * because env.json will be configured using the installation tool.
 *
 * LocalSettings.php should use this ConfigLoader to configure MW and all extensions.
 *
 * Apps, that are (also) used outside of the MW-Stack (i.e. that will not go
 * through LocalSettings.php) should also use this ConfigLoader to initialze
 * the configuration variables in a consistent way.
 *
 */

class ConfigLoader {
    
    private $mediaWikiPath = '';
    private $defaultSettingsFile = '';
    private $localVariablesFile = '';
    private $envJsonFile = '';
    private $envDefaultJsonFile = '';
    
    private $configVariables = [];
 
    /**
     * @param string $mediaWikiPath
     *                      root folder of MediaWiki, holding these config files:
     *                      env-default.json, env.json, LocalVariables.php
     *                      do not put the "/" at the end
     * @param string $defaultSettingsFile
     *                      filename (incl. path) for DefaultSettings.php for an Extension
     * @param array $configVariables
     *                      list of config-variables that must be defined in (one of) the config files
     *                      used to verify a ConfigLoader properly fetched expected variables
     */
    public function __construct(
            $mediaWikiPath,
            $defaultSettingsFile,
            $configVariables = []) {

        $this->envDefaultJsonFile = "$mediaWikiPath/env-default.json";
        $this->defaultSettingsFile = $defaultSettingsFile;
        $this->envJsonFile = "$mediaWikiPath/env.json";
        $this->localVariablesFile = "$mediaWikiPath/LocalVariables.php";
        
        $this->configVariables = $configVariables;
    }
    
    public function loadConfig() {
        //$this->logger->debug("Starting loading configuration files.");
        
        $ed = $this->loadEnvDefault();
        $ds = $this->loadDefaultSettings();
        $ej = $this->loadEnvJson();
        $lv = $this->loadLocalVariables();
        
        if(!$ed && !$ds && !$ej && !$lv) {
            $msg = "No configuration files found.";
            $this->error($msg);
            die($msg);
        }
        $this->checkConfig();
    }
    
    /**
     * loads env-default.json and then env.json
     */
    public function loadEnv() {
        $ed = $this->loadEnvDefault();
        $ej = $this->loadEnvJson();
        
        if(!$ed && !$ej ) {
            $msg = "No configuration files found.";
            $this->error($msg);
            die($msg);
        }
    }
    
    private function loadEnvDefault() {
        return $this->loadJsonFile($this->envDefaultJsonFile);
    }
    
    private function loadEnvJson() {
        $mw = $this->loadJsonFile($this->envJsonFile);
        $apps = $this->loadJsonFile($this->envJsonFile);
        return $mw && $apps;
    }

    private function loadJsonFile($fileName = '', $appId = '') {
        if ($fileName) {
            if(file_exists($fileName) && is_readable($fileName)) {
                //$this->logger->debug("Loading $fileName");
                try {
                    $jsonString = file_get_contents($fileName);
                    $json = json_decode($jsonString, true);

                    if($json) {
                        $this->processApps($json, $appId);
                    } else {
                        return false;
                    }
                } catch (Exception $e) {
                    $this->error("Cannot read configuration file '$fileName': " . $e->getMessage());
                    return false;
                }
            } else {
                $this->error("Failed to load $fileName");
                return false;
            }
        }
        return true;
    }

    /**
     * @param array $json maps an appId (e,g, MW) to a config-array
     */
    private function processApps($json = [], $appFilter = '') {
        foreach ($json as $app => $value) {
            if( !$appFilter || $appFilter && $app == $appFilter ) {
                $this->makeGlobals($value);
            }
        }
    }
    
    /**
     * @param array $json maps config variables to their values
     */
    private function makeGlobals($json = []) {
        foreach ($json as $key => $value) {
            $GLOBALS[$key] = $value;
        }
    }
            
    private function loadDefaultSettings() {
        return $this->loadPhpFile($this->defaultSettingsFile);
    }
    
    private function loadLocalVariables() {
        return $this->loadPhpFile($this->localVariablesFile);
    }

    private function loadPhpFile($fileName = '') {
        if ($fileName) {
            if(file_exists($fileName) && is_readable($fileName)) {
                //$this->logger->debug("Loading $fileName");
                try {
                    @require $fileName;
                    return true;
                } catch (\Exception $e) {
                    $this->error("Cannot read configuration file '$fileName': " . $e->getMessage());
                    return false;
                }
            } else {
                $this->error("Failed to load $fileName");
                return false;
            }
        } else {
            return true;
        }
    }

    private function checkConfig() {
        foreach ($this->configVariables as $var ) {
            $this->checkIfConfigured($var);
        }
    }
    
    private function checkIfConfigured($var) {
        if (!isset($GLOBALS[$var])) {
            $msg = "'$var' is not configured in any of the configuration files.";
            $this->error($msg);
            die($msg);
        }
    }
    
    private function error($msg) {
        //echo "$msg\n";
        //trigger_error($msg);
        //$this->logger->error($msg);
    }

    public static function test() {
        $mwPath = __DIR__ . '/../../../../..';
        $ds = "$mwPath/LocalVariables.php";
        $configVariables = [
            'wgServerHTTP',
            'wgScriptPath'
        ];
        
        $loader = new ConfigLoader($mwPath, $ds, $configVariables);
        $loader->loadConfig();
    }
}

// ConfigLoader::test();
// echo "--------------------------------------\n";
// var_dump($GLOBALS);
