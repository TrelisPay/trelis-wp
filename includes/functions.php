<?php
	
	function trelis_wc_gateway(){
		if (class_exists('WC_Payment_Gateway')) {
			
			class Wc_Trelis_Gateway extends \WC_Payment_Gateway {
				
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
					$this -> id       = 'trelis';
					$this -> icon     = 'https://www.trelis.com/assets/trelis.2e0ed160.png';
					$this -> supports = array (
						'products',
						'subscriptions',
						'subscription_cancellation',
						'subscription_suspension',
						'subscription_reactivation',
						'subscription_amount_changes',
						'subscription_date_changes',
						'subscription_payment_method_change',
						'subscription_payment_method_change_customer',
						'subscription_payment_method_change_admin',
						'multiple_subscriptions',
					);
					
					$this -> trelis_init_form_fields();
					$this -> init_settings();
					
					$this -> enabled    = $this -> get_option( 'enabled' );
					$this -> api_key    = $this -> get_option( 'api_key' );
					$this -> api_secret = $this -> get_option( 'api_secret' );
					add_action( 'woocommerce_update_options_payment_gateways_' . $this -> id, array (
						$this,
						'process_admin_options'
					) );
					
					if ( $this -> get_option( 'prime' ) === "yes" ) {
						$this -> title = __( 'Trelis Prime - 1% discount', 'trelis-crypto-payments' );
					} else {
						$this -> title = __( 'Trelis Crypto Payments', 'trelis-crypto-payments' );
					}
					
					if ( is_checkout() ) {
						wp_register_style( "trelis", plugins_url( '/assets/css/trelis.css', __FILE__ ), '', '1.0.0' );
						wp_enqueue_style( 'trelis' );
					}
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
				
				public function process_payment( $order_id ) {
					global $woocommerce;
					$order = wc_get_order( $order_id );
					
					$apiKey    = $this -> get_option( 'api_key' );
					$apiSecret = $this -> get_option( 'api_secret' );
					$isPrime   = $this -> get_option( 'prime' ) === "yes";
					$isGasless = $this -> get_option( 'gasless' ) === "yes";
					
					$apiUrl = 'https://api.trelis.com/dev-api/create-dynamic-link?apiKey=' . $apiKey . "&apiSecret=" . $apiSecret;
					
					$args = array (
						'headers' => array (
							'Content-Type' => "application/json"
						),
						'body'    => json_encode( array (
							'productName'  => get_bloginfo( 'name' ),
							'productPrice' => $order -> total,
							'token'        => trelis_get_token(),
							'redirectLink' => $this -> get_return_url( $order ),
							'isGasless'    => $isGasless,
							'isPrime'      => $isPrime,
							'fiatCurrency' => trelis_get_currency()
						) )
					);
					
					$response = wp_remote_post( $apiUrl, $args );
					
					if ( ! is_wp_error( $response ) ) {
						$body = json_decode( $response['body'], true );
						
						if ( $body["message"] == 'Successfully created product' ) {
							$order -> add_order_note( $response['body'], false );
							$str       = explode( "/", $body["data"]["productLink"] );
							$paymentID = $str[ count( $str ) - 1 ];
							$order -> set_transaction_id( $paymentID );
							$order -> save();
							$woocommerce -> cart -> empty_cart();
							
							return array (
								'result'   => 'success',
								'redirect' => $body["data"]["productLink"],
							);
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
			}
		}
	}
