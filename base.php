<?php

namespace Trelis;


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
		Autoloader::run();
	}
	
	/**
	 * Defining constant function
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function define_constant() {
		
		define( 'TRELIS_VERSION', '1.0.19' );
		define( 'TRELIS_PLUGIN_URL', trailingslashit( plugin_dir_url( __FILE__ ) ) );
		define( 'TRELIS_PLUGIN_DIR', trailingslashit( plugin_dir_path( __FILE__ ) ) );
	}
	
	public function init(){
		
		add_filter('woocommerce_payment_gateways', [$this, 'trelis_add_gateway_class']);
		
	}
	
	function trelis_add_gateway_class($gateways)
	{
		$gateways[] = 'Wc_Trelis_Gateway';
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