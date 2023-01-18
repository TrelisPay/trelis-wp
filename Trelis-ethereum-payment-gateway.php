<?php
/**
 * @link              https://www.Trelis.com
 * @since             1.0.18
 * @package           Trelis_Crypto_Payments
 *
 * @wordpress-plugin
 * Plugin Name:       Trelis Crypto Payments
 * Plugin URI:        https://docs.trelis.com/products/woocommerce-plugin
 * Description:       Accept USDC or Ether payments directly to your wallet. Your customers pay by connecting any Ethereum wallet. No Trelis fees!
 * Version:           1.0.19
 * Requires at least: 6.1
 * Requires PHP:      7.4
 * Author:            Trelis
 * Author URI:        https://www.Trelis.com
 * License:           GPL-3.0
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain:       trelis-crypto-payments
 * Domain Path:       /languages
 */
	
	
// run auto loader.
	
	require 'autoloader.php';
	// run plugin initialization file.
	require 'base.php';
	require  'includes/functions.php';
	
add_filter( 'woocommerce_currencies', 'trelis_add_crypto' );

function trelis_add_crypto( $currencies ) {
    $currencies['ETH'] = __( 'ETH', 'woocommerce' );
    $currencies['USDC'] = __( 'USDC', 'woocommerce' );
    return $currencies;
}

add_filter('woocommerce_currency_symbol', 'trelis_add_currency_symbols', 10, 2);

function trelis_add_currency_symbols( $currency_symbol, $currency ) {
    switch( $currency ) {
        case 'ETH': $currency_symbol = 'ETH'; break;
        case 'USDC': $currency_symbol = 'USDC'; break;
    }
    return $currency_symbol;
}

function trelis_get_currency() {
    global  $woocommerce;
    $currency = get_woocommerce_currency();

    switch ($currency) {
        case 'ETH':
        case 'USDC':
            return null;
        default:
            return $currency;
    }
}

function trelis_get_token() {
    global  $woocommerce;
    $currency = get_woocommerce_currency();

    switch ($currency) {
        case 'ETH':
        case 'USDC':
            return $currency;
        default:
            return 'USDC';
    }
}


/*
* Payment callback Webhook, Used to process the payment callback from the payment gateway
*/

if (!defined('ABSPATH')) exit;
function trelis_payment_confirmation_callback()
{
    $trelis = WC()->payment_gateways->payment_gateways()['trelis'];
    $json = file_get_contents('php://input');

    $expected_signature = hash_hmac('sha256', $json,  $trelis->get_option('webhook_secret'));
    if ( $expected_signature != $_SERVER["HTTP_SIGNATURE"])
        return __('Failed','trelis-crypto-payments');

    $data = json_decode($json);

    $orders = get_posts( array(
        'post_type' => 'shop_order',
        'posts_per_page' => -1,
        'post_status' => 'any',
        'meta_key'   => '_transaction_id',
        'meta_value' => json_decode(json_encode($data->mechantProductKey)),
    ));

    if (empty($orders))
        return __('Failed','trelis-crypto-payments');

    $order_id = $orders[0]->ID;
    $order = wc_get_order($order_id);

    if ($order->get_status() == 'processing' || $order->get_status() == 'complete')
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
    return __('Processed!','trelis-crypto-payments');
}

add_action("rest_api_init", function () {
    register_rest_route(
        'trelis/v3',
        '/payment',
        array(
            'methods' => 'POST',
            'callback' => 'trelis_payment_confirmation_callback',
            'permission_callback' => '__return_true'
        ),
    );
});



if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {


	
}

function wc_trelis_gateway(){
	$wc = Trelis\Classes\Wc_Trelis_Gateway::get_instance();
	
	return $wc;
}
add_action('plugins_loaded', 'plugins_loading');

function plugins_loading(){
	Trelis\Base::instance()->init();
	
	trelis_wc_gateway();
}

