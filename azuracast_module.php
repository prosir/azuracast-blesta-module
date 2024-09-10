<?php

class AzuracastModule extends Module {

    public $Record;

    // Hardcode your API URL and API Key here
    private $api_url = 'https://your-azuracast-instance.com/api';
    private $api_key = 'your-hardcoded-api-key';

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

    // Add service with hardcoded API URL and API Key
    public function addService($package, ?array $vars = null, $parent_package = null, $parent_service = null, $status = 'pending') {
        $data = array(
            'name' => $vars['station_name'],
            'short_name' => $vars['station_short_name'],
            'description' => $vars['description']
        );

        // Send API request using the hardcoded API URL and API Key
        $response = $this->azuracastApiRequest($this->api_url . '/station', $data, $this->api_key);

        if ($response['status'] == 'success') {
            $login_token = $this->generateAzuraCastLoginToken($vars['email']);
            $this->storeLoginToken($response['station_id'], $login_token);
            return array('success' => true, 'message' => "Station created successfully.");
        } else {
            return array('error' => true, 'message' => $response['message']);
        }
    }

    // Generate login token using the hardcoded API Key
    private function generateAzuraCastLoginToken($email) {
        $data = array(
            'user' => $email,
            'expires_at' => strtotime("+1 hour")
        );

        $response = $this->azuracastApiRequest($this->api_url . '/auth/login-token', $data, $this->api_key);

        if (isset($response['token'])) {
            return $response['token'];
        }
        return null;
    }

    // Perform an API request using cURL
    private function azuracastApiRequest($url, $data, $api_key) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Authorization: Bearer ' . $api_key
        ));
        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    // Store the login token in the database
    private function storeLoginToken($station_id, $token) {
        $this->Record->insert('azuracast_login_tokens', [
            'station_id' => $station_id,
            'token' => $token,
            'expires_at' => date('Y-m-d H:i:s', strtotime("+1 hour"))
        ]);
    }
}
