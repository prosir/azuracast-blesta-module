<?php
class AzuracastModule extends Module {

    public function __construct() {
        Language::loadLang("azuracast_module", null, dirname(__FILE__) . DS . "language" . DS);

        $this->name = "Azuracast Module";
        $this->version = "1.0.0";
        $this->authors = array(array('name' => 'Prosir', 'url' => 'https://jaccodw.nl'));
    }

    public function install() {
        // Create a table to store AzuraCast login tokens
        $this->Record->setField('station_id', array('type' => 'int', 'size' => 11, 'unsigned' => true))
            ->setField('token', array('type' => 'varchar', 'size' => 255))
            ->setField('expires_at', array('type' => 'datetime'))
            ->setKey(array('station_id'), 'primary')
            ->create('azuracast_login_tokens');
    }

    public function addService($package, array $vars = null) {
        $api_url = "https://your-azuracast-instance.com/api/station";
        $api_key = "your_azuracast_api_key"; 
        
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

    private function generateAzuraCastLoginToken($email) {
        $api_url = "https://your-azuracast-instance.com/api/auth/login-token";
        $api_key = "your_azuracast_api_key"; 
        
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

    private function storeLoginToken($station_id, $token) {
        $this->Record->insert('azuracast_login_tokens', [
            'station_id' => $station_id,
            'token' => $token,
            'expires_at' => date('Y-m-d H:i:s', strtotime('+1 hour'))
        ]);
    }

    private function getLoginToken($station_id) {
        return $this->Record->select('token')
            ->from('azuracast_login_tokens')
            ->where('station_id', '=', $station_id)
            ->fetch();
    }

    public function loginToAzuraCast($station_id) {
        $token = $this->getValidLoginToken($station_id);
        
        if ($token) {
            $login_url = "https://your-azuracast-instance.com/api/auth/login?token=" . $token;
            header("Location: " . $login_url);
            exit;
        } else {
            return array('error' => true, 'message' => "Login token not found.");
        }
    }

    private function getValidLoginToken($station_id) {
        $token_data = $this->Record->select(['token', 'expires_at'])
            ->from('azuracast_login_tokens')
            ->where('station_id', '=', $station_id)
            ->fetch();
        
        if ($token_data && strtotime($token_data->expires_at) > time()) {
            return $token_data->token;
        }

        return $this->generateAzuraCastLoginToken($station_id);
    }

}
?>
