<?php
	
	
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
	
	function get_frequency($frequency) {
		switch ($frequency) {
			case 'month':
				return 'MONTHLY';
			case 'year':
				return 'YEARLY';
			default:
				return 'MONTHLY';
		}
	}
