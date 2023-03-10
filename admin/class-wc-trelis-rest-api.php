<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://www.Trelis.com
 * @since      1.0.0
 *
 * @package    Trelis_Crypto_Payments
 * @subpackage Trelis_Crypto_Payments/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Trelis_Crypto_Payments
 * @subpackage Trelis_Crypto_Payments/admin
 * @author     Trelis <jalpesh.fullstack10@gmail.com>
 */
class WC_Trelis_Rest_Api extends WC_Payment_Gateway {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	public $apiKey;
	public $apiSecret;
	public $isPrime;
	public $isGasless;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->apiKey = $this->get_option('api_key');
		$this->apiSecret = $this->get_option('api_secret');
		$this->isPrime = $this->get_option('prime') === "yes";
		$this->isGasless = $this->get_option('gasless') === "yes";
		add_action( 'rest_api_init', array( $this, 'register_trelis_payment' ) );
	}
	
	public function register_trelis_payment()
	{
		register_rest_route(
			'trelis/v3',
			'/payment',
			array(
				'methods' => 'POST',
				'callback' => array( $this, 'trelis_payment_confirmation_callback' ),
				'permission_callback' => '__return_true'
			),
		);
	}

	/*
	* Payment callback Webhook, Used to process the payment callback from the payment gateway
	*/
	public function trelis_payment_confirmation_callback()
	{
		global $woocommerce;
		$trelis = WC()->payment_gateways->payment_gateways()['trelis'];
		$json = file_get_contents('php://input');
		// $json = '{"merchantProductKey":"aab9180b-d7be-411d-b40a-b2863604f7f9","customerWalletId":"0x410129Ed1901Da941BF473315d8c1dDe4fEcC979","paidAmount":0.0006,"requiredPaymentAmount":0.0006,"paidCurrencyType":"ETH","isSuccessful":true,"txHash":"0x9f54dcf1bf31e98eab6cfd00918d66dde837570479dba6782defa56c565b2015","chainId":5,"overPayment":0,"underPayment":0,"paymentUniqueKey":"ecde60d7-4d1b-4a77-b4ca-3607112cbdef","event":"subscription.create.success"}';

		$headers = array('Content-Type: text/html; charset=UTF-8');
		wp_mail( 'jalpesh@yopmail.com', 'Trelis payment webhook response', $json, $headers );
		$this->custom_logs_rest('payment webhook response',$json);

		$expected_signature = hash_hmac('sha256', $json,  $trelis->get_option('webhook_secret'));
		if ( $expected_signature != $_SERVER["HTTP_SIGNATURE"])
			return __('Failed','trelis-crypto-payments');

		$data = json_decode($json);

		$orders = get_posts( array(
			'post_type' => 'shop_order',
			'posts_per_page' => -1,
			'post_status' => 'any',
			'meta_key'   => '_transaction_id',
			'meta_value' =>$data->mechantProductKey,
		));
		if (empty($orders))
			return __('Failed','trelis-crypto-payments');

		$order_id = $orders[0]->ID;
		$order = wc_get_order($order_id);
		$subscription = wcs_get_subscriptions_for_order( $order_id );
		$subscription = reset( $subscription );
		$subscriptionId   = $subscription->get_id();

		if(strpos($data->event, 'subscription') !== false) {

			if($data->event === 'subscription.charge.failed' || $data->event === "charge.failed") {
				$order->add_order_note(__('Trelis Payment Failed! Expected amount ','trelis-crypto-payments') . $data->requiredPaymentAmount . __(', attempted ','trelis-crypto-payments') . $data->paidAmount, true);
				$order->save();
				return __('Failed','trelis-crypto-payments');
			}

			if ($data->event == "subscription.create.failed") {
				$order->add_order_note(__('Subscription not created','trelis-crypto-payments'), true);
				return __('Subscription not created','trelis-crypto-payments');
			}

			if ($data->event == "subscription.create.success") {
				$order->add_order_note(__('Subscription created!','trelis-crypto-payments'), true);
				return __('Pending','trelis-crypto-payments');
			}

			if($data->event == "subscription.charge.success") {
				
				$customerWalletId = $data->from;
				update_post_meta( $subscriptionId, 'customerWalletId', $customerWalletId );
				
				$order->add_order_note(__('Payment complete!','trelis-crypto-payments'), true);
				$order->payment_complete();
				$order->reduce_order_stock();
	
				// call the run subscription API once subscription completes
				$this->run_subscription_api($order,$subscriptionId,array($subscriptionId));
				
				if ( isset( WC()->cart ) ) {
					WC()->cart->empty_cart();
				}
				return __('Processed!','trelis-crypto-payments');
			}

			if($data->event == "subscription.cancellation.success") { 
				$customerWalletId = $data->from;
				update_post_meta( $subscriptionId, 'trelis_payment_method', 0 );
				$order->add_order_note(__('Subscription cancel successfully !','trelis-crypto-payments'), true);
			}

			if($data->event == "subscription.cancellation.failed") { 
				$customerWalletId = $data->from;
				update_post_meta( $subscriptionId, 'trelis_payment_method', 0 );
				$order->add_order_note(__('Subscription cancelation failed !','trelis-crypto-payments'), true);
			}

		} else {
			
			if ($order->get_status() == 'processing' || $order->get_status() == 'complete'){
				return __('Already processed','trelis-crypto-payments');
			}

			if ($data->event === "submission.failed" || $data->event === "charge.failed") {
				$order->add_order_note(__('Trelis Payment Failed! Expected amount ','trelis-crypto-payments') . $data->requiredPaymentAmount . __(', attempted ','trelis-crypto-payments') . $data->paidAmount, true);
				$order->save();
				return __('Failed','trelis-crypto-payments');
			}

			if ($data->event == "charge.failed") {
				$order->add_order_note(__('Payment not complete','trelis-crypto-payments'), true);
				return __('Pending','trelis-crypto-payments');
			}

			if ($data->event == "charge.success") {
				$order->add_order_note(__('Payment complete!','trelis-crypto-payments'), true);
				$order->payment_complete();
				$order->reduce_order_stock();
				// Remove cart.
				if ( isset( WC()->cart ) ) {
					WC()->cart->empty_cart();
				}
				return __('Processed!','trelis-crypto-payments');
			}
			// return __('Processed!','trelis-crypto-payments');
		}
		
	}
	public function custom_logs_rest($apitype,$message) { 
		if(is_array($message)) { 
			$message = json_encode($message); 
		} 
		$upload_dir = wp_get_upload_dir();
		$file = fopen($upload_dir['basedir']."/trelis_logs.log","a"); 
		echo fwrite($file, "\n" . date('Y-m-d h:i:s') ." :: ". $apitype ." :: " . $message); 
		fclose($file); 
	}

	public function run_subscription_api($order, $subscriptionId, $customerWalletIds) {
		$args = array (
			'headers' => array (
				'Content-Type' => "application/json"
			),
			'body' => json_encode( array (
				'customers' => $customerWalletIds
			) )
		);

		$apiUrl = TRELIS_API_URL.'run-subscription?apiKey=' . $this->apiKey . '&apiSecret=' . $this->apiSecret;
		$response = wp_remote_post($apiUrl, $args);
		
		// Debug Start
		$headers = array('Content-Type: text/html; charset=UTF-8');
		wp_mail( 'jalpesh@yopmail.com', 'Trelis run subscription API', print_r($response,true), $headers );
		$this->custom_logs('run subscription api in webhook response',$response);
		// Debug End

		/*if (!is_wp_error($response)) {
			$body = json_decode($response['body'], true);
			if($body['data']['event'] == 'subscription.create.success')
			{
				update_post_meta( $subscriptionId, 'trelis_subscriptionLink', $body['data']['subscriptionLink'] );
				update_post_meta( $subscriptionId, 'trelis_payment_method', 1 );
				$order->add_order_note(__('Subscription Created!','trelis-crypto-payments'), true);
			}else{
				wc_add_notice(__('Subscription not complete at Trelis','trelis-crypto-payments'), 'error');
				$order->add_order_note(__('Subscription not complete at Trelis','trelis-crypto-payments'), true);
			}
			return;
		} else {
			wc_add_notice($response->get_error_message(), 'error');
			wc_add_notice(__('Connection error','trelis-crypto-payments'), 'error');
			return;
		} */
	}
}


