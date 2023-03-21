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
class Trelis_Crypto_Payments_Admin
{

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
	private $id;

	/**
	 * The logo icon of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $trelisIcon    The logo of this plugin.
	 */
	private $trelisIcon;


	/**
	 * The supported types 
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $pluginSupports   
	 */
	private $pluginSupports;


	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct($plugin_name, $version)
	{

		$this->plugin_name = $plugin_name;
		$this->version = $version;

		// print_r(TRELIS_PLUGIN_DIR); die;

		if (class_exists('MeprBaseCtrl')) {
			// new \MeprTrelisGateway();
		}
	}


	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles()
	{

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Trelis_Crypto_Payments_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Trelis_Crypto_Payments_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/trelis-crypto-payments-admin.css', array(), $this->version, 'all');
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts()
	{

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Trelis_Crypto_Payments_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Trelis_Crypto_Payments_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/trelis-crypto-payments-admin.js', array('jquery'), $this->version, false);
	}

	/**
	 * Include the Trelis Gateway option to the woocommerce Payments
	 */
	public function trelis_add_gateway_class($gateways)
	{
		$gateways[] = 'WC_Trelis_Gateway';
		return $gateways;
	}

	/**
	 * Include USD Coin(USDC) & Ehterium (ETH) currency to the currency dropdown
	 */
	public function trelis_add_crypto($currencies)
	{
		$currencies['ETH'] = __('ETH', 'woocommerce');
		$currencies['USDC'] = __('USDC', 'woocommerce');
		return $currencies;
	}

	public function mepr_gateway_path($paths)
	{

		$paths[] = TRELIS_PLUGIN_DIR . 'admin';

		return $paths;
	}

	/**
	 * Include USD Coin(USDC) & Ehterium (ETH) currency symbole
	 */
	public function trelis_add_currency_symbols($currency_symbol, $currency)
	{
		switch ($currency) {
			case 'ETH':
				$currency_symbol = 'ETH';
				break;
			case 'USDC':
				$currency_symbol = 'USDC';
				break;
		}
		return $currency_symbol;
	}

	/**
	 * Include treslis payment gateway class
	 */
	public function trelis_init_gateway_class()
	{
		if ($this->is_woocommer_plugin_active()) {
			require_once plugin_dir_path(__FILE__) . 'class-wc-trelis-gateway.php';
			require_once plugin_dir_path(__FILE__) . 'class-wc-trelis-rest-api.php';
			$apiObject = new WC_Trelis_Rest_Api();
		}
	}

	/**
	 * Checks if the WooCommerce plugin is active.
	 *
	 * @return bool Whether the plugin is active or not.
	 */
	public function is_woocommer_plugin_active()
	{
		return class_exists('WC_Payment_Gateway');
	}


	/**
	 * Checks if current added to cart product is not simple product if subscription product is already in cart and vice versa.
	 *
	 * @return bool
	 */

	public function trelis_validate_add_to_cart($valid, $product_id)
	{
		if (WC()->cart) {
			foreach (WC()->cart->get_cart() as  $cart_item) {

				$current_product = wc_get_product($product_id);
				$product = $cart_item['data'];

				if (class_exists('WC_Subscriptions_Product') && WC_Subscriptions_Product::is_subscription($product)) {

					if (!WC_Subscriptions_Product::is_subscription($current_product)) {
						wc_add_notice(__("You can not add single product when you have Subscription product in Cart.", 'notice'));
						return false;
					}
				} else {

					if (WC_Subscriptions_Product::is_subscription($current_product)) {
						wc_add_notice(__("You can not add Subscription product when you have Single product in Cart.", 'notice'));
						return false;
					}
				}
			}
		}
		return true;
	}


	function check_if_subscription_is_day_or_week($available_gateways)
	{
		if (is_checkout()) {
			if (WC()->cart) {
				foreach (WC()->cart->get_cart() as $cart_item) {
					$product = $cart_item['data'];

					if (class_exists('WC_Subscriptions_Product') && WC_Subscriptions_Product::is_subscription($product)) {
						$period = WC_Subscriptions_Product::get_period($product);

						if ($period == 'day' || $period == 'week') {
							unset($available_gateways['trelis']);
						}
					}
				}
			}
		}
		return $available_gateways;
	}
}
