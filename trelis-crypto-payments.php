<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://www.Trelis.com
 * @since             1.0.0
 * @package           Trelis_Crypto_Payments
 *
 * @wordpress-plugin
 * Plugin Name:       Trelis Crypto Payments Jalpesh
 * Plugin URI:        https://docs.trelis.com/products/woocommerce-plugin
 * Description:       Accept USDC or Ether payments directly to your wallet. Your customers pay by connecting any Ethereum wallet. No Trelis fees!
 * Version:           1.0.20
 * Requires at least: 6.1
 * Requires PHP:      7.4
 * Author:            Trelis
 * Author URI:        https://www.Trelis.com
 * License:           GPL-3.0
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       trelis-crypto-payments
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'TRELIS_CRYPTO_PAYMENTS_VERSION', '1.0.0' );


define('TRELIS_API_URL','https://api.trelis.com/dev-env/dev-api/');


/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-trelis-crypto-payments-activator.php
 */
function activate_trelis_crypto_payments() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-trelis-crypto-payments-activator.php';
	Trelis_Crypto_Payments_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-trelis-crypto-payments-deactivator.php
 */
function deactivate_trelis_crypto_payments() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-trelis-crypto-payments-deactivator.php';
	Trelis_Crypto_Payments_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_trelis_crypto_payments' );
register_deactivation_hook( __FILE__, 'deactivate_trelis_crypto_payments' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-trelis-crypto-payments.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_trelis_crypto_payments() {

	$plugin = new Trelis_Crypto_Payments();
	$plugin->run();

}
run_trelis_crypto_payments();
