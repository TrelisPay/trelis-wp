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
class WC_Trelis_Gateway extends WC_Payment_Gateway {

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

	/**
	 * The id of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $id    The id of this plugin.
	 */
	public $id;

	/**
	 * The logo icon of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $icon    The logo of this plugin.
	 */
	public $icon;

	/**
	 * The supported types 
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $supports   
	 */
	public $supports;

	/**
	 * Payment gateway title 
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $title   
	 */
	public $title;

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
		$this->id = 'trelis';
		$this->icon = 'https://www.trelis.com/assets/trelis-2e0ed160.png';
		
		if($this->get_option('prime') === "yes"){
			$this->title = __('Trelis Prime - 1% discount','trelis-crypto-payments');
		} else {
			$this->title = __('Trelis Crypto Payments','trelis-crypto-payments');
		}
		$this->enabled = $this->get_option('enabled');
		$this->apiKey = $this->get_option('api_key');
		$this->apiSecret = $this->get_option('api_secret');
		$this->isPrime = $this->get_option('prime') === "yes";
		$this->isGasless = $this->get_option('gasless') === "yes";
		

		$this->supports = array(
			'product'
		);

		if($this->is_subscriptions_plugin_active())
		{
			$this->supports = array_merge($this->supports,array(
				'subscriptions',
				'subscription_cancellation',
				'subscription_suspension',
				'subscription_reactivation',
				// 'subscription_amount_changes',
				'subscription_date_changes',
				'subscription_payment_method_change',
				'subscription_payment_method_change_customer',
				'subscription_payment_method_change_admin',
				// 'multiple_subscriptions'
				)
			);
		}

		$this->trelis_init_form_fields();
		WC_Payment_Gateway::init_settings();
		add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
		add_action('woocommerce_subscription_status_cancelled', array($this, 'trelis_subscription_status_cancelled'));
		add_action('woocommerce_subscription_status_updated', array($this, 'trelis_subscription_status_updated'),10,3);
		add_action( 'woocommerce_scheduled_subscription_payment',array($this,'scheduled_subscription_payment') ,1, 3 );
	}

	

	public function trelis_init_form_fields()
	{
		$this->form_fields = array(
			'enabled' => array(
				'title' => __('Trelis Pay Gateway','trelis-crypto-payments'),
				'label' => __('Enable','trelis-crypto-payments'),
				'type' => 'checkbox',
				'description' => '',
				'default' => 'yes'
			),
			'prime' => array(
				'title' => __('Trelis Prime','trelis-crypto-payments'),
				'label' => __('Offer a 1% discount for using Trelis Pay','trelis-crypto-payments'),
				'type' => 'checkbox',
				'description' => '<a href="https://docs.trelis.com/features/trelis-prime">' . esc_html__('Learn how to minimise payment processing charges', 'trelis-crypto-payments') . '</a>',
				'default' => ''
			),
			'gasless' => array(
				'title' => __('Gasless Payments','trelis-crypto-payments'),
				'label' => __('Cover gas costs for customer payments','trelis-crypto-payments'),
				'type' => 'checkbox',
				'description' => '<a href="https://docs.trelis.com/features/gasless-payments">' . esc_html__('Buy gas credits OR learn more about gasless payments', 'trelis-crypto-payments') . '</a>',
				'default' => ''
			),
			'api_url' => array(
				'title' => 'API Webhook URL',
				'type' => 'text',
				'custom_attributes' => array('readonly' => 'readonly'),
				'default' => home_url()."/wp-json/trelis/v3/payment"
			),
			'api_key' => array(
				'title' => 'API Key',
				'type' => 'text'
			),
			'api_secret' => array(
				'title' => 'API Secret',
				'type' => 'password'
			),
			'webhook_secret' => array(
				'title' => 'Webhook Secret',
				'type' => 'password'
			),
		);
	}

	/**
	 * Process the payment for a given order.
	 *
	 * @param int $order_id Order ID to process the payment for.
	 *
	 * @return array|null An array with result of payment and redirect URL, or nothing.
	 * @throws Process_Payment_Exception Error processing the payment.
	 * @throws Exception Error processing the payment.
	 */
	public function process_payment($order_id)
	{
		$order = wc_get_order($order_id);
		$subscription = wcs_get_subscriptions_for_order( $order_id );
		$subscription = reset( $subscription );
		
		
		if(isset($subscription) && !empty($subscription)){

			$subscription_name = '';

			foreach( $subscription->get_items() as $product_subscription ){
				$subscription_name = $product_subscription -> get_name();
			}
			$subscription_type = "MONTHLY";
			$subscription_type = $subscription -> get_billing_period();
			if($subscription_type == "month") {
				$subscription_type = "MONTHLY";
			} 
			if( $subscription_type == "year" ) {
				$subscription_type = "YEARLY";
			} 
			
			$args = array (
				'headers' => array (
					'Content-Type' => "application/json"
				),
				'body' => json_encode( array (
					'subscriptionPrice' => $subscription->get_total(),
					'frequency'         => $subscription_type,
					'subscriptionName'  => $subscription_name,
					'fiatCurrency'      => $this->trelis_get_currency(),
					'subscriptionType'  => "manual",
					'redirectLink'      => $this->get_return_url( $order ),
				) )
			);

			$apiUrl = TRELIS_API_URL.'create-subscription-link?apiKey=' . $this->apiKey . '&apiSecret=' . $this->apiSecret;
			$response = wp_remote_post($apiUrl, $args);
			$this->custom_logs('subscription api response',$response);
			if (!is_wp_error($response)) {
				$body = json_decode($response['body'], true);
				
				if ($body["data"]["message"] == 'Successfully created subscription link') {

					$order->add_order_note($response['body'], false);
					$str = explode("/", $body["data"]["subscriptionLink"]);
					
					$paymentID = $str[count($str)-1];
					$order->set_transaction_id($paymentID);
					$order->save();
					
					return array(
						'result' => 'success',
						'redirect' => $body["data"]["subscriptionLink"],
					);

					// Remove cart.
					if ( isset( WC()->cart ) ) {
						WC()->cart->empty_cart();
					}
					
					
					// Return thank you page redirect.
					return [
						'result'   => 'success',
						'redirect' => $this->get_return_url( $order ),
					];

				} else {
					wc_add_notice($body["error"], 'error');
					return;
				}
			} else {
				wc_add_notice($response->get_error_message(), 'error');
				wc_add_notice(__('Connection error','trelis-crypto-payments'), 'error');
				return;
			}

		} else {
			$args = array(
				'headers' => array(
					'Content-Type' => "application/json"
				),
				'body' => json_encode(array(
					'productName' => get_bloginfo( 'name' ),
					'productPrice' => $order->total,
					'token' => $this->trelis_get_token(),
					'redirectLink' => $this->get_return_url($order),
					'isGasless' => $this->isGasless,
					'isPrime' => $this->isPrime,
					'fiatCurrency' => $this->trelis_get_currency()
				))
			);
			$apiUrl = TRELIS_API_URL.'create-dynamic-link?apiKey=' . $this->apiKey . '&apiSecret=' . $this->apiSecret;

			$response = wp_remote_post($apiUrl, $args);
			$this->custom_logs('payment api response',$response);
			if (!is_wp_error($response)) {
				$body = json_decode($response['body'], true);
				
				if(!empty($body)){
					
					if (isset($body["message"]) && $body["message"] == 'Successfully created product') {
						//
						$order->add_order_note($response['body'], false);
						$str = explode("/", $body["data"]["productLink"]);
						$paymentID = $str[count($str)-1];
						$order->set_transaction_id($paymentID);
						$order->save();
	
						return array(
							'result' => 'success',
							'redirect' => $body["data"]["productLink"],
						);
					} else {
						wc_add_notice($body["error"], 'error');
						return;
					}
				}
			} else {
				wc_add_notice($response->get_error_message(), 'error');
				wc_add_notice(__('Connection error','trelis-crypto-payments'), 'error');
				return;
			}
		}
	}

	/**
	 * Checks if the WC Subscriptions plugin is active.
	 *
	 * @return bool Whether the plugin is active or not.
	 */
	public function is_subscriptions_plugin_active() {
		return class_exists( 'WC_Subscriptions' );
	}

	public function trelis_get_token() {
		$currency = get_woocommerce_currency();
	
		switch ($currency) {
			case 'ETH':
			case 'USDC':
				return $currency;
			default:
				return 'USDC';
		}
	}

	public function trelis_get_currency() {
		$currency = get_woocommerce_currency();

		switch ($currency) {
			case 'ETH':
			case 'USDC':
				return null;
			default:
				return $currency;
		}
	}

	public function trelis_subscription_status_cancelled( $subscription )
	{
		$subscriptionId = $subscription->get_data()['id'];
		if($subscription->get_data()['status'] == 'cancelled') 
		{
			$customerWalletId = get_post_meta($subscriptionId,'customerWalletId',true);
			$args = array (
				'headers' => array (
					'Content-Type' => "application/json"
				),
				'body' => json_encode( array (
					'customer' => $customerWalletId
				) )
			);
	
			$apiUrl = TRELIS_API_URL.'cancel-subscription?apiKey=' . $this->apiKey . '&apiSecret=' . $this->apiSecret;
			
			$response = wp_remote_post($apiUrl, $args);
			$headers = array('Content-Type: text/html; charset=UTF-8');
			$emailBody = array($args,$response);
			wp_mail( 'jalpesh@yopmail.com', 'Trelis cancel subscription API response', print_r($emailBody,true), $headers );
			$this->custom_logs('run subscription api response',$emailBody);
			if (!is_wp_error($response)) {
				$body = json_decode($response['body'], true);
				return;
			} else {
				wc_add_notice($response->get_error_message(), 'error');
				wc_add_notice(__('Connection error','trelis-crypto-payments'), 'error');
				return;
			}
		}
	}

	public function trelis_subscription_status_updated( $subscription, $new_status, $old_status )
	{	
		if($new_status == 'active' && $old_status == 'pending-cancel' || $new_status == 'active' && $old_status == 'on-hold') 
		{
			$subscriptionId = $subscription->get_data()['id'];
			$customerWalletId = get_post_meta($subscriptionId,'customerWalletId',true);
			$args = array (
				'headers' => array (
					'Content-Type' => "application/json"
				),
				'body' => json_encode( array (
					'customers' => array($customerWalletId)
				) )
			);

			$apiUrl = TRELIS_API_URL.'run-subscription?apiKey=' . $this->apiKey . '&apiSecret=' . $this->apiSecret;
			
			$response = wp_remote_post($apiUrl, $args);
			$headers = array('Content-Type: text/html; charset=UTF-8');
			wp_mail( 'jalpesh@yopmail.com', 'Trelis run subscription API', print_r($response,true), $headers );
			$this->custom_logs('run subscription api response',$response);
			if (!is_wp_error($response)) {
				$body = json_decode($response['body'], true);
				return;
			} else {
				wc_add_notice($response->get_error_message(), 'error');
				wc_add_notice(__('Connection error','trelis-crypto-payments'), 'error');
				return;
			}
		}
	}

	public function custom_logs($apitype,$message) { 
		if(is_array($message)) { 
			$message = json_encode($message); 
		} 
		$upload_dir = wp_get_upload_dir();
		$file = fopen($upload_dir['basedir']."/trelis_logs.log","a"); 
		echo fwrite($file, "\n" . date('Y-m-d h:i:s') ." :: ". $apitype ." :: " . $message); 
		fclose($file); 
	}

	public function scheduled_subscription_payment(  $subscriptionId  ) {
		$headers = array('Content-Type: text/html; charset=UTF-8');
		$temp = wp_mail( 'jalpesh@yopmail.com', 'Trelis cancel subscription API response', print_r($subscriptionId,true), $headers );
		
		$customerWalletId = get_post_meta($subscriptionId,'customerWalletId',true);
		if($customerWalletId){
			$args = array (
				'headers' => array (
					'Content-Type' => "application/json"
				),
				'body' => json_encode( array (
					'customers' => array($customerWalletId)
				) )
			);
	
			$apiUrl = TRELIS_API_URL.'run-subscription?apiKey=' . $this->apiKey . '&apiSecret=' . $this->apiSecret;
			
			$response = wp_remote_post($apiUrl, $args);
			$headers = array('Content-Type: text/html; charset=UTF-8');
			wp_mail( 'jalpesh@yopmail.com', 'Trelis run subscription API', print_r($response,true), $headers );
			$this->custom_logs('run subscription api response',$response);
			if (!is_wp_error($response)) {
				$body = json_decode($response['body'], true);
				return;
			} else {
				wc_add_notice($response->get_error_message(), 'error');
				wc_add_notice(__('Connection error','trelis-crypto-payments'), 'error');
				return;
			}
		} else {
			wc_add_notice($response->get_error_message(), 'error');
			wc_add_notice(__('Customer Wallet Id missing','trelis-crypto-payments'), 'error');
			return;
		}
	
	}

}


