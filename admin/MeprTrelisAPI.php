<?php
if (!defined('ABSPATH')) {
    die('You are not allowed to call this page directly.');
}

class MeprTrelisAPI
{
    public $plugin_name;
    protected $apiKey;
    protected $apiSecret;
    protected $webhookUrl;
    protected $webhookSecret;

    public function __construct($settings)
    {
        $this->plugin_name = 'Memberpress Trelis Gateway Addon';
        $this->apiKey = isset($settings->api_key) ? $settings->api_key : '';
        $this->apiSecret = isset($settings->api_secret) ? $settings->api_secret : '';
        $this->webhookSecret = isset($settings->webhook_secret) ? $settings->webhook_secret : '';
        $this->webhookUrl = isset($settings->webhook_url) ? $settings->webhook_url : '';

    }

    /**
     * Validate Webhook Signature
     *
     * @param $input
     * @return boolean
     */
    public function validate_webhook($json)
    {   
	    return $_SERVER['HTTP_X_TRELIS_SIGNATURE'] == hash_hmac('sha256', $json, $this->webhookSecret);
    }

    /**
     * Generates the headers to pass to API request.
     */
    public function get_headers()
    {
        return apply_filters(
            'mepr_trelis_request_headers',
            [
                'apiKey'    => "{$this->apiKey}",
                'apiSecret' => "{$this->apiSecret}",
            ]
        );
    }

     /**
     * Send request to the Paystack Api
     * @param string $endpoint API request path
     * @param array $args API request arguments
     * @param string $method API request method
     * @param string $domain API request uri
     * @return object|null JSON decoded transaction object. NULL on API error.
     */
    public function send_request( $endpoint, $args = array(), $method = 'post', $domain = TRELIS_API_URL ) {
        
        $apiUrl = "{$domain}{$endpoint}?apiKey={$this->apiKey}&apiSecret={$this->apiSecret}";

        $args = MeprHooks::apply_filters('mepr_trelis_request_args', $args);
        
        $arg_array = array(
            'method'    => strtoupper($method),
            'body'      => json_encode($args),
            'headers' => array (
				'Content-Type' => "application/json"
			)
            
        );
        $arg_array = MeprHooks::apply_filters('mepr_trelis_request', $arg_array);

        $resp = wp_remote_request($apiUrl, $arg_array);

        // $headers = array('Content-Type: text/html; charset=UTF-8');
		// wp_mail( 'jalpesh@yopmail.com', 'record_subscription_payment',print_r($resp,true),$headers );
        
        if (is_wp_error($resp)) {
            throw new MeprHttpException(sprintf(__('You had an HTTP error connecting to %s', 'memberpress'), $this->plugin_name));
        } 
        else {
            if (null !== ($json_res = json_decode($resp['body'], true))) {
                
                if (isset($json_res['error']))
                    throw new MeprRemoteException("{$json_res['error']}");
                else
                    return $json_res;
            } else // Un-decipherable message
                throw new MeprRemoteException(sprintf(__('There was an issue with the payment processor. Try again later.', 'memberpress'), $this->plugin_name));
        }

        return false;
    }

    public function get_mepr_currency() {
        $mepr_options = MeprOptions::fetch();
        $currency = $mepr_options->currency_code;

		switch ($currency) {
			case 'ETH':
			case 'USDC':
				return null;
			default:
				return $currency;
		}

    }

    public function get_mepr_token() {

        $mepr_options = MeprOptions::fetch();
        $currency = $mepr_options->currency_code;
	
		switch ($currency) {
			case 'ETH':
			case 'USDC':
				return $currency;
			default:
				return 'USDC';
		}

    }

}
