<?php

namespace Trelis\TrelisWp;


use Trelis\TrelisWp\MemberpressGateway as TrelisWpMemberpressGateway;

defined( 'ABSPATH' ) || exit;

final class Base{
	/**
	 * Accesing for object of this class
	 *
	 * @var object
	 */
	private static $instance;
	
	/**
	 * Construct function of this class
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->define_constant();
		
		
		if ( class_exists( 'MeprBaseCtrl' ) ) {
			new TrelisWpMemberpressGateway();
		}
	}
	
	/**
	 * Defining constant function
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function define_constant() {
		define( 'DEV_MODE', true);
		define( 'TRELIS_VERSION', '1.0.19' );
		define( 'TRELIS_PLUGIN_URL', trailingslashit( plugin_dir_url( __FILE__ ) ) );
		define( 'TRELIS_PLUGIN_DIR', trailingslashit( plugin_dir_path( __FILE__ ) ) );
	}
	
	public function init(){
		
		add_filter('woocommerce_payment_gateways', [$this, 'trelis_add_gateway_class']);
		//check if memberpress is active
		
		
	}
	
	function trelis_add_gateway_class($gateways)
	{
		$gateways[] = Wc_Trelis_Gateway::class;

		if(class_exists( 'WC_Subscriptions') ){
			$gateways[] = Wc_Trelis_Subscription_Gateway::class;
		}
		return $gateways;
	}
	
	/**
	 * singleton instance create function
	 *
	 * @return object
	 * @since 1.0.0
	 */
	public static function instance() {
		if (!self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}
}