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
				// 'subscription_date_changes',
				// 'subscription_payment_method_change',
				// 'subscription_payment_method_change_customer',
				// 'subscription_payment_method_change_admin',
				// 'multiple_subscriptions'
				)
			);
		}

		$this->trelis_init_form_fields();
		WC_Payment_Gateway::init_settings();
		add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
		add_action('woocommerce_subscription_status_cancelled', array($this, 'trelis_subscription_status_cancelled'));
		add_action('woocommerce_subscription_status_updated', array($this, 'trelis_subscription_status_updated'),10,3);
		if ( class_exists( 'WC_Subscriptions_Order' ) ) {
			add_action( 'woocommerce_scheduled_subscription_payment_trelis',array($this,'scheduled_subscription_payment') , 10, 2 );
		}
		add_action('woocommerce_order_status_completed', array($this, 'woocommerce_order_status_completed_trelis'));
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

			$subscription_type = $subscription -> get_billing_period();
			$subscription_type = "MONTHLY";
			if($subscription_type == "month") {
				$subscription_type = "MONTHLY";
			} 
			if( $subscription_type == "year" ) {
				$subscription_type = "YEARLY";
			} 

			$api_args = array (
				'subscriptionPrice' => $subscription->get_data()['total'],
				'frequency'         => $subscription_type,
				'subscriptionName'  => $subscription_name,
				'fiatCurrency'      => $this->trelis_get_currency(),
				'subscriptionType'  => "manual",
				'redirectLink'      => $this->get_return_url( $order ),
			);

			$trial_period = $subscription -> get_trial_period();			

			if(!empty($trial_period)) {
				$schedule_start = strtotime(date($subscription->schedule_start));
				$schedule_trial_end = strtotime(date($subscription->schedule_trial_end));
				$datediff = $schedule_trial_end - $schedule_start;
				$freeTrialDays = round($datediff / (60 * 60 * 24));
				$api_args['freeTrialDays'] = $freeTrialDays;
			} 
			
			$args = array (
				'headers' => array (
					'Content-Type' => "application/json"
				),
				'body' => json_encode($api_args)
			);
			
			$apiUrl = TRELIS_API_URL.'create-subscription-link?apiKey=' . $this->apiKey . '&apiSecret=' . $this->apiSecret;
			$response = wp_remote_post($apiUrl, $args);

			$body = json_decode($response['body'], true);
			$str = explode("/", $body["data"]["subscriptionLink"]);

			$linkId = $str[4];
			update_post_meta( $order->get_id(), '_trelis_payment_link_id', $linkId );

			$this->custom_logs('subscription api response',$response);
			if (!is_wp_error($response)) {
				
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
					'productPrice' => $order->get_data()['total'],
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

						$linkId = $str[4];
						update_post_meta( $order->get_id(), '_trelis_payment_link_id', $linkId );

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
				// return $currency;
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
		$order = wc_get_order( $subscriptionId );

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
			
			//debug start
			// $headers = array('Content-Type: text/html; charset=UTF-8');
			// $emailBody = array($subscriptionId, $args, $response);
			// wp_mail( 'jalpesh@yopmail.com', 'Trelis cancel subscription API response', print_r($emailBody,true), $headers );
			// $this->custom_logs('run subscription for cancel subscription  api response',$emailBody);
			//debug end

			if (!is_wp_error($response)) {
				$body = json_decode($response['body'], true);
				update_post_meta( $subscriptionId, 'trelis_payment_method', 0 );
				return array(
					'result' => 'Successfully cancel subscription'
				);
			} else {
				wc_add_notice($response->get_error_message(), 'error');
				wc_add_notice(__('Connection error','trelis-crypto-payments'), 'error');
				return;
			}
		}
	}

	public function trelis_subscription_status_updated( $subscription, $new_status, $old_status )
	{	
		$subscriptionId = $subscription->get_data()['id'];

		$order = wc_get_order( $subscriptionId );

		if($new_status == 'active' && $old_status == 'pending-cancel' || $new_status == 'active' && $old_status == 'on-hold') 
		{
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
			wp_mail( 'jalpesh@yopmail.com', 'Trelis run subscription API status changed to Active', print_r($response,true), $headers );
			$this->custom_logs('run subscription api response',$response);

			if (!is_wp_error($response)) {
				$body = json_decode($response['body'], true);
				if(!empty($body))
				{
					// update_post_meta( $subscriptionId, 'trelis_subscriptionLink', $body['data']['subscriptionLink'] );
					update_post_meta( $subscriptionId, 'trelis_payment_method', 1 );
					$order->add_order_note(__($body["message"],'trelis-crypto-payments'), true);
				}else{
					wc_add_notice(__('Subscription not complete at Trelis','trelis-crypto-payments'), 'error');
					$order->add_order_note(__('Subscription not complete at Trelis','trelis-crypto-payments'), true);
				}
				return;
			} else {
				wc_add_notice($response->get_error_message(), 'error');
				wc_add_notice(__('Connection error','trelis-crypto-payments'), 'error');
				return;
			}
		}
	}

	public function custom_logs($apitype,$message) 
	{ 
		if(is_array($message)) { 
			$message = json_encode($message); 
		} 
		$upload_dir = wp_get_upload_dir();
		$file = fopen($upload_dir['basedir']."/trelis_logs.log","a"); 
		echo fwrite($file, "\n" . date('Y-m-d h:i:s') ." :: ". $apitype ." :: " . $message); 
		fclose($file); 
	}

	public function scheduled_subscription_payment( $amount_to_charge, $renewal_order  )
	{
		$this->process_subscription_payment($renewal_order, $amount_to_charge);
	}

	public function process_subscription_payment( $order = null, $amount = 0 ) 
	{ 
		if ( 0 == $amount ) {
			$order->payment_complete();
			return true;
		}
		// get last transaction id used.
		
		$order_meta = $order->get_meta_data();
		$customerWalletId = '';
		$_subscription_renewal_id = '';
		if(isset($order_meta ) && !empty($order_meta))
		{
			foreach($order->get_meta_data() as $order_item)
			{
				$order_itemdata = $order_item->get_data();
				if($order_itemdata['key'] == "customerWalletId")
				{
					$customerWalletId = $order_itemdata['value'];
				}

				if($order_itemdata['key'] == "_subscription_renewal")
				{
					$_subscription_renewal_id = $order_itemdata['value'];
				}
			}
		}
		if(!empty($customerWalletId) && !empty($_subscription_renewal_id)) {
			if($customerWalletId) {
				$args = array (
					'headers' => array (
						'Content-Type' => "application/json"
					),
					'body' => json_encode( array (
						'customers' => array($customerWalletId)
					) )
				);
		
				$apiUrl = TRELIS_API_URL.'run-subscription?apiKey=' . $this->apiKey . '&apiSecret=' . $this->apiSecret;
				
				//Debug start
				$response = wp_remote_post($apiUrl, $args);
				$headers = array('Content-Type: text/html; charset=UTF-8');
				wp_mail( 'jalpesh@yopmail.com', 'Trelis run subscription API', print_r($response,true), $headers );
				$this->custom_logs('run subscription api inside schedular',$response);
				//Debug End

				if (!is_wp_error($response)) {
					$body = json_decode($response['body'], true);
					if(!empty($body))
					{
						// update_post_meta( $_subscription_renewal_id, 'trelis_subscriptionLink', $body['data']['subscriptionLink'] );
						update_post_meta( $_subscription_renewal_id, 'trelis_payment_method', 1 );
						$order->add_order_note(__($body["message"],'trelis-crypto-payments'), true);
					}else{
						wc_add_notice(__('Subscription renewal failed at Trelis','trelis-crypto-payments'), 'error');
						$order->add_order_note(__('Subscription renewal failed at Trelis','trelis-crypto-payments'), true);
					}
					
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
		} else {
			$this->custom_logs('Action Schedular failed',$order);
			wc_add_notice(__('Action Schedular failed','trelis-crypto-payments'), 'error');
			return;
		}
	}

	public function woocommerce_order_status_completed_trelis($order_id) 
	{
		$order = wc_get_orders($order_id);
		
		$subscription = wcs_get_subscriptions_for_order( $order_id );
		if(!empty($subscription) && isset($subscription))
		{
			foreach($subscription as $subscription_item)
			{
				$subscriptionId    = $subscription_item->get_id();
				$subscriptionPaymentMethod  = $subscription_item->get_data()['payment_method'];
				$checkTrelisStatus = get_post_meta( $subscriptionId, 'trelis_payment_method', 1 );
				if($checkTrelisStatus && $subscriptionPaymentMethod != 'trelis') 
				{
					$customerWalletId = get_post_meta($subscriptionId,'customerWalletId',true);
					if(!empty($customerWalletId))
					{
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

						//Debug Start
						$headers = array('Content-Type: text/html; charset=UTF-8');
						$emailBody = array($args,$response);
						wp_mail( 'jalpesh@yopmail.com', 'Trelis cancel subscription API response', print_r($emailBody,true), $headers );
						$this->custom_logs('run subscription api response',$emailBody);
						//Debug End

						if (!is_wp_error($response)) {
							$body = json_decode($response['body'], true);
							if(!empty($body)) 
							{
								$order->add_order_note(__($body['message']), true);
							}
							update_post_meta( $subscriptionId, 'trelis_payment_method', 0 );
							return;
						} else {
							$order->add_order_note(__('Cancel subscription failed'), true);
							wc_add_notice($response->get_error_message(), 'error');
							wc_add_notice(__('Connection error','trelis-crypto-payments'), 'error');
							return;
						}
					}
				}	else {
					update_post_meta( $subscriptionId, 'trelis_payment_method', 1 );
				}
			}
		}
	}
}


