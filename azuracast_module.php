<?php

class AzuracastModule extends Module {

    public $Record;

    public function __construct() {
        Loader::loadComponents($this, array("Record"));
        Language::loadLang("azuracast", null, dirname(__FILE__) . DS . "language" . DS);
    }

    public function getName() {
        return "AzuraCast";
    }

    public function getVersion() {
        return "1.0.0";
    }

    public function getDescription() {
        return "This module integrates AzuraCast radio station management with Blesta.";
    }

    public function getLogo() {
        return "views/default/logo.png"; 
    }

    public function install() {
        $table_exists = $this->Record->query("SHOW TABLES LIKE 'azuracast_login_tokens'")->fetch();
        if (!$table_exists) {
            $this->Record->setField('station_id', array('type' => 'int', 'size' => 11, 'unsigned' => true))
                ->setField('token', array('type' => 'varchar', 'size' => 255))
                ->setField('expires_at', array('type' => 'datetime'))
                ->setKey(array('station_id'), 'primary')
                ->create('azuracast_login_tokens');
        }
    }

    // Get the configuration settings for the module
    public function getSettings($module_row = null) {
        error_log("getSettings method called"); // Debug log

        $fields = new ModuleFields();

        // Station URL field
        $station_url = $fields->label(Language::_("AzuracastModule.config.station_url", true), "station_url");
        $station_url->attach($fields->fieldText(
            "station_url", 
            isset($module_row->meta->station_url) ? $module_row->meta->station_url : "", 
            array('id' => "station_url", 'class' => 'form-control')
        ));
        $fields->setField($station_url);

        // API Key field
        $api_key = $fields->label(Language::_("AzuracastModule.config.api_key", true), "api_key");
        $api_key->attach($fields->fieldText(
            "api_key", 
            isset($module_row->meta->api_key) ? $module_row->meta->api_key : "", 
            array('id' => "api_key", 'class' => 'form-control')
        ));
        $fields->setField($api_key);

        error_log(print_r($fields, true)); // Log the fields to debug

        return $fields;  // Ensure fields are returned for rendering
    }

    // Save the settings for the module
    public function saveSettings(array $vars = null) {
        error_log("saveSettings method called"); // Debug log

        $meta = array(
            array('key' => "station_url", 'value' => $vars['station_url']),
            array('key' => "api_key", 'value' => $vars['api_key'])
        );

        return $meta;
    }

    // Render the module management page
    public function manageModule($module, array &$vars) {
        // Set up the view for the admin settings page
        $this->view = new View("admin_main", "default");
        
        // Pass the base URI to the view
        $this->view->set("base_uri", $this->base_uri); 
        
        // Pass the fields and module object to the view
        $this->view->set("fields", $this->getSettings($module));
        $this->view->set("module", $module);
        
        return $this->view->fetch();
    }
    
}
