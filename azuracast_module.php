<?php
class AzuracastModule extends Module {

    public function __construct() {
        Language::loadLang("azuracast_module", null, dirname(__FILE__) . DS . "language" . DS);
        
        $this->name = "Azuracast Module";
        $this->version = "1.0.0";
        $this->authors = array(array('name' => 'Your Name', 'url' => 'https://yourwebsite.com'));
    }

    public function install() {
        // Create a table to store AzuraCast login tokens
        $this->Record->setField('station_id', array('type' => 'int', 'size' => 11, 'unsigned' => true))
            ->setField('token', array('type' => 'varchar', 'size' => 255))
            ->setField('expires_at', array('type' => 'datetime'))
            ->setKey(array('station_id'), 'primary')
            ->create('azuracast_login_tokens');
    }

    /**
     * Module configuration fields (Station URL and API Key)
     */
    public function getSettings($module_row_id = null) {
        return array(
            'station_url' => array(
                'label' => Language::_("AzuracastModule.config.station_url", true),
                'type' => 'text',
                'tooltip' => Language::_("AzuracastModule.config.station_url.tooltip", true),
                'value' => (isset($vars['station_url']) ? $vars['station_url'] : null),
                'required' => true
            ),
            'api_key' => array(
                'label' => Language::_("AzuracastModule.config.api_key", true),
                'type' => 'text',
                'tooltip' => Language::_("AzuracastModule.config.api_key.tooltip", true),
                'value' => (isset($vars['api_key']) ? $vars['api_key'] : null),
                'required' => true
            )
        );
    }

    /**
     * Save settings when admin updates configuration
     */
    public function saveSettings($vars) {
        if (isset($vars['station_url']) && isset($vars['api_key'])) {
            // Encrypt the API key before storing
            $encrypted_api_key = $this->encryptData($vars['api_key']);

            // Store encrypted API key and station URL
            $meta = [
                'station_url' => $vars['station_url'],
                'api_key' => $encrypted_api_key
            ];

            $this->saveModuleSettings($meta);  // Save module settings to the database
            return array('success' => true);
        } else {
            return array('error' => true, 'message' => "Both Station URL and API Key are required.");
        }
    }

    /**
     * Fetch stored module configuration (Station URL and API Key)
     */
    private function getConfig($module_row_id = null) {
        $module_row = $this->getModuleRow($module_row_id);

        // Decrypt the API key before returning it
        $decrypted_api_key = $module_row ? $this->decryptData($module_row->meta->api_key) : null;

        return [
            'station_url' => $module_row ? $module_row->meta->station_url : null,
            'api_key' => $decrypted_api_key
        ];
    }

    /**
     * Add Service (Create a station)
     */
    public function addService($package, array $vars = null) {
        $config = $this->getConfig();

        $api_url = $config['station_url'] . "/api/station";
        $api_key = $config['api_key'];

        $data = array(
            'name' => $vars['station_name'],
            'short_name' => $vars['station_short_name'],
            'description' => $vars['description']
        );

        $response = $this->azuracastApiRequest($api_url, $data, $api_key);

        if ($response['status'] == 'success') {
            $login_token = $this->generateAzuraCastLoginToken($vars['email']);
            $this->storeLoginToken($response['station_id'], $login_token);

            return array('success' => true, 'message' => "Station created successfully.");
        } else {
            return array('error' => true, 'message' => $response['message']);
        }
    }

    /**
     * Generate AzuraCast login token
     */
    private function generateAzuraCastLoginToken($email) {
        $config = $this->getConfig();
        $api_url = $config['station_url'] . "/api/auth/login-token";
        $api_key = $config['api_key'];

        $data = array(
            'user' => $email,
            'expires_at' => strtotime("+1 hour")
        );

        $response = $this->azuracastApiRequest($api_url, $data, $api_key);

        if (isset($response['token'])) {
            return $response['token'];
        }

        return null;
    }

    /**
     * API request helper function
     */
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

    /**
     * Store login token in the database (encrypted)
     */
    private function storeLoginToken($station_id, $token) {
        // Encrypt the session token before storing
        $encrypted_token = $this->encryptData($token);

        $this->Record->insert('azuracast_login_tokens', [
            'station_id' => $station_id,
            'token' => $encrypted_token,
            'expires_at' => date('Y-m-d H:i:s', strtotime('+1 hour'))
        ]);
    }

    /**
     * Get stored login token for the station (decrypt before returning)
     */
    private function getLoginToken($station_id) {
        $token_data = $this->Record->select('token')
            ->from('azuracast_login_tokens')
            ->where('station_id', '=', $station_id)
            ->fetch();

        // Decrypt the token before returning it
        return $token_data ? $this->decryptData($token_data->token) : null;
    }

    /**
     * Redirect client to AzuraCast with token
     */
    public function loginToAzuraCast($station_id) {
        $token = $this->getValidLoginToken($station_id);

        if ($token) {
            $config = $this->getConfig();
            $login_url = $config['station_url'] . "/api/auth/login?token=" . $token;
            header("Location: " . $login_url);
            exit;
        } else {
            return array('error' => true, 'message' => "Login token not found.");
        }
    }

    /**
     * Get valid login token (check expiration)
     */
    private function getValidLoginToken($station_id) {
        $token_data = $this->Record->select(['token', 'expires_at'])
            ->from('azuracast_login_tokens')
            ->where('station_id', '=', $station_id)
            ->fetch();

        if ($token_data && strtotime($token_data->expires_at) > time()) {
            return $this->decryptData($token_data->token);
        }

        return $this->generateAzuraCastLoginToken($station_id);
    }

    /**
     * Encrypt sensitive data (API key, session token)
     */
    private function encryptData($data) {
        $encryption_key = getenv('BL_ENCRYPTION_KEY'); // Use environment variable
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));

        $encrypted_data = openssl_encrypt($data, 'aes-256-cbc', $encryption_key, 0, $iv);

        // Return encrypted data with IV (base64 encoded)
        return base64_encode($encrypted_data . '::' . $iv);
    }

    /**
     * Decrypt sensitive data (API key, session token)
     */
    private function decryptData($encrypted_data) {
        $encryption_key = getenv('BL_ENCRYPTION_KEY');

        list($encrypted_data, $iv) = explode('::', base64_decode($encrypted_data), 2);

        return openssl_decrypt($encrypted_data, 'aes-256-cbc', $encryption_key, 0, $iv);
    }
}
?>
