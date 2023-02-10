<?php
	namespace Trelis\TrelisWp;

	use Trelis\Traits\WC_Trelis_Subscriptions_Trait;

//	This file is no need. Woo deprecicate the way extend payment gateway. new in includes/functions.php





if (class_exists('WC_Payment_Gateway')) {
		
		class Wc_Trelis_Subscription_Gateway extends \WC_Payment_Gateway {
			
			use WC_Trelis_Subscriptions_Trait;
			
			private static $instance;
			
			/**
			 * Returns the *Singleton* instance of this class.
			 *
			 * @return The *Singleton* instance.
			 */
			public static function get_instance() {
				if ( null === self::$instance ) {
					self::$instance = new self();
				}
				return self::$instance;
			}
			
			public function __construct() {
				$this -> id       = 'trelis_subs';
				$this -> icon     = TRELIS_PLUGIN_URL . 'assets/icons/trelis.png';
				$this -> supports = array ();
				
				$this->maybe_init_subscriptions();
				
				$this -> trelis_init_form_fields();
				$this -> init_settings();
				
				$this -> enabled    = $this -> get_option( 'enabled' );
				$this -> api_key    = $this -> get_option( 'api_key' );
				$this -> api_secret = $this -> get_option( 'api_secret' );
				add_action( 'woocommerce_update_options_payment_gateways_' . $this -> id, array ( $this, 'process_admin_options' ) );
				
				if ( $this -> get_option( 'prime' ) === "yes" ) {
					$this -> title = __( 'Trelis Prime - 1% discount', 'trelis-crypto-payments' );
				} else {
					$this -> title = __( 'Trelis Crypto Payments - Subscriptions', 'trelis-crypto-payments' );
				}
				
				add_action('wp_enqueue_scripts', [$this, 'style_enqueue']);
			}
			
			public function trelis_init_form_fields() {
				$this -> form_fields = array (
					'enabled'        => array (
						'title'       => __( 'Trelis Pay Gateway', 'trelis-crypto-payments' ),
						'label'       => __( 'Enable', 'trelis-crypto-payments' ),
						'type'        => 'checkbox',
						'description' => '',
						'default'     => 'yes'
					),
					'prime'          => array (
						'title'       => __( 'Trelis Prime', 'trelis-crypto-payments' ),
						'label'       => __( 'Offer a 1% discount for using Trelis Pay', 'trelis-crypto-payments' ),
						'type'        => 'checkbox',
						'description' => '<a href="https://docs.trelis.com/features/trelis-prime">' . esc_html__( 'Learn how to minimise payment processing charges', 'trelis-crypto-payments' ) . '</a>',
						'default'     => ''
					),
					'gasless'        => array (
						'title'       => __( 'Gasless Payments', 'trelis-crypto-payments' ),
						'label'       => __( 'Cover gas costs for customer payments', 'trelis-crypto-payments' ),
						'type'        => 'checkbox',
						'description' => '<a href="https://docs.trelis.com/features/gasless-payments">' . esc_html__( 'Buy gas credits OR learn more about gasless payments', 'trelis-crypto-payments' ) . '</a>',
						'default'     => ''
					),
					'api_url'        => array (
						'title'             => 'API Webhook URL',
						'type'              => 'text',
						'custom_attributes' => array ( 'readonly' => 'readonly' ),
						'default'           => home_url() . "/wp-json/trelis/v3/payment"
					),
					'api_key'        => array (
						'title' => 'API Key',
						'type'  => 'text'
					),
					'api_secret'     => array (
						'title' => 'API Secret',
						'type'  => 'password'
					),
					'webhook_secret' => array (
						'title' => 'Webhook Secret',
						'type'  => 'password'
					),
				);
			}

			public function style_enqueue(){
				if ( is_checkout() ) {
					wp_enqueue_style( "trelis", TRELIS_PLUGIN_URL .'assets/css/trelis.css', '1.0.0' );
				}
			}
			
			// process the payment and return the result from this api https://api.trelis.com/dev-env/dev-api/create-subscription-link by remote post with the following parameters subscriptionPrice, frequency, subscriptionName, fiatCurrency, subscriptionType, redirectLink
			public function process_payment( $order_id ) {
				global $woocommerce;
				$order = wc_get_order( $order_id );
				
				$subscription = wcs_get_subscriptions_for_order( $order_id );
				$subscription = reset( $subscription );
				
				
				$subscription_id = $subscription -> get_id();
				
//				$subscription_name = $subscription -> get_name();
				$subscription_name = '';
				foreach( $subscription->get_items() as $item_id => $product_subscription ){
					// Get the name
					$subscription_name = $product_subscription->get_name();
				}
				$subscription_price = $subscription -> get_total();
				
				$subscription_frequency = get_frequency($subscription -> get_billing_period() );
				
				$subscription_type = $subscription -> get_billing_interval();
				
				$redirect_link = $this -> get_return_url( $order );
				
				$fiat_currency = trelis_get_currency();
				
				
				$api_key    = $this -> get_option( 'api_key' );
				$api_secret = $this -> get_option( 'api_secret' );
				
//				$webhook_secret =  $this -> get_option( 'webhook_secret' );
				
				if ( DEV_MODE == true ) {
					$api_url = 'https://api.trelis.com/dev-env/dev-api/create-subscription-link?apiKey=' . $api_key . "&apiSecret=" . $api_secret;
				}else{
					$api_url = 'https://api.trelis.com/dev-env/dev-api/create-subscription-link?apiKey=' . $api_key . "&apiSecret=" . $api_secret;
				}
				
				
//				
				
				$args = array (
					'headers' => array (
						'Content-Type' => "application/json"
					),
					'body'    => json_encode( array (
						'subscriptionPrice' => $subscription_price,
						'frequency'         => "MONTHLY",
						'subscriptionName'  => $subscription_name,
						'fiatCurrency'      => $fiat_currency,
						'subscriptionType'  => "automatic",
						'redirectLink'      => $redirect_link,
					) )
				);
				
				$response = wp_remote_post( $api_url, $args );
				
				
				if ( ! is_wp_error( $response ) ) {
					$body = json_decode( $response['body'], true );
					
					if ( $body["data"]["message"] == 'Successfully created subscription link' ) {
						$order -> add_order_note( $response['body'], false );
						$str       = explode( "/", $body["data"]["subscriptionLink"] );
						$paymentID = $str[ count( $str ) - 1 ];
						$order -> set_transaction_id( $paymentID );
						$order -> save();
						
						return array (
							'result'   => 'success',
							'redirect' => $body["data"]["subscriptionLink"],
						);
						
						// Process valid response.
						$this->process_response( $response, $order );
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
						wc_add_notice( $body["error"], 'error' );
						
						return;
					}
				} else {
					wc_add_notice( $response -> get_error_message(), 'error' );
					wc_add_notice( __( 'Connection error', 'trelis-crypto-payments' ), 'error' );
					
					return;
				}
				
			}
			// Cancel woocommerce subscription when the user cancel the subscription from trelis
			public function cancel_subscription( $order_id ) {
				$order = wc_get_order( $order_id );
				$subscription = wcs_get_subscriptions_for_order( $order_id );
				$subscription = reset( $subscription );
				$subscription -> update_status( 'cancelled' );
				$order -> add_order_note( __( 'Subscription cancelled', 'trelis-crypto-payments' ), false );
				// also cancel from api https://api.trelis.com/dev-env/dev-api/cancel-subscription
				
			}
			
			/**
			 * Store extra meta data for an order from a Stripe Response.
			 */
			public function process_response( $response, $order ) {
				
				
				$trelis = WC()->payment_gateways->payment_gateways()[$this -> id];
				$json = file_get_contents('php://input');
				
				$expected_signature = hash_hmac('sha256', $json,  $trelis->get_option('webhook_secret'));
				if ( $expected_signature != $_SERVER["HTTP_SIGNATURE"])
					return __('Failed','trelis-crypto-payments');
				
				$data = json_decode($json);
				
				$orders = get_posts( array(
					'post_type' => 'shop_subscription',
					'posts_per_page' => -1,
					'post_status' => 'any',
					'meta_key'   => '_transaction_id',
					'meta_value' => json_decode(json_encode($data->mechantProductKey)),
				));
				
				if (empty($orders))
					return __('Failed','trelis-crypto-payments');
				
				$order_id = $orders[0]->ID;
				$order = wc_get_order($order_id);
				
				if ( $order->get_status() == 'processing' || $order->get_status() == 'complete')
					return __('Already processed','trelis-crypto-payments');
				
				if ($data->event === "submission.failed" || $data->event === "charge.failed") {
					$order->add_order_note(__('Trelis Payment Failed! Expected amount ','trelis-crypto-payments') . $data->requiredPaymentAmount . __(', attempted ','trelis-crypto-payments') . $data->paidAmount, true);
					$order->save();
					return __('Failed','trelis-crypto-payments');
				}
				
				if ($data->event !== "charge.success") {
					return __('Pending','trelis-crypto-payments');
				}
				
				$order->add_order_note(__('Payment complete!','trelis-crypto-payments'), true);
				$order->payment_complete();
				$order->reduce_order_stock();
				return __('Active!','trelis-crypto-payments');
				
//				return $response;
			}
			
		}
	}
