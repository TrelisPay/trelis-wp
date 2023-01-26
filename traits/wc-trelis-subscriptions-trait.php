<?php

namespace Trelis\Traits;

/**
 * Trait for Subscriptions compatibility.
 */
trait WC_Trelis_Subscriptions_Trait {

	use WC_Trelis_Subscriptions_Utilities_Trait;

	/**
	 * Initialize subscription support and hooks.
	 *
	 * @since 5.6.0
	 */
	public function maybe_init_subscriptions() {
		if ( ! $this->is_subscriptions_enabled() ) {
			return;
		}

		$this->supports = array_merge(
			$this->supports,
			[
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
			]
		);

		// add_action( 'woocommerce_scheduled_subscription_payment_' . $this->id, [ $this, 'scheduled_subscription_payment' ], 10, 2 );
		// add_action( 'woocommerce_subscription_failing_payment_method_updated_' . $this->id, [ $this, 'update_failing_payment_method' ], 10, 2 );
		// add_action( 'wcs_resubscribe_order_created', [ $this, 'delete_resubscribe_meta' ], 10 );
		// add_action( 'wcs_renewal_order_created', [ $this, 'delete_renewal_meta' ], 10 );
		// add_action( 'woocommerce_subscriptions_change_payment_before_submit', [ $this, 'differentiate_change_payment_method_form' ] );

		// Display the payment method used for a subscription in the "My Subscriptions" table.
		// add_filter( 'woocommerce_my_subscriptions_payment_method', [ $this, 'maybe_render_subscription_payment_method' ], 10, 2 );

		// Allow store managers to manually set Stripe as the payment method on a subscription.
		// add_filter( 'woocommerce_subscription_payment_meta', [ $this, 'add_subscription_payment_meta' ], 10, 2 );
		// add_filter( 'woocommerce_subscription_validate_payment_meta', [ $this, 'validate_subscription_payment_meta' ], 10, 2 );
		// add_filter( 'wc_stripe_display_save_payment_method_checkbox', [ $this, 'display_save_payment_method_checkbox' ] );

		/*
		* WC subscriptions hooks into the "template_redirect" hook with priority 100.
		* If the screen is "Pay for order" and the order is a subscription renewal, it redirects to the plain checkout.
		* See: https://github.com/woocommerce/woocommerce-subscriptions/blob/99a75687e109b64cbc07af6e5518458a6305f366/includes/class-wcs-cart-renewal.php#L165
		* If we are in the "You just need to authorize SCA" flow, we don't want that redirection to happen.
		*/
		// add_action( 'template_redirect', [ $this, 'remove_order_pay_var' ], 99 );
		// add_action( 'template_redirect', [ $this, 'restore_order_pay_var' ], 101 );
	}

	

//	Updates all active subscriptions payment method.
	
	

	/**
	 * Render a dummy element in the "Change payment method" form (that does not appear in the "Pay for order" form)
	 * which can be checked to determine proper SCA handling to apply for each form.
	 *
	 * @since 4.6.1
	 */
	public function differentiate_change_payment_method_form() {
		echo '<input type="hidden" id="wc-trelis-change-payment-method" />';
	}

	/**
	 * Maybe process payment method change for subscriptions.
	 *
	 * @since 5.6.0
	 *
	 * @param int $order_id
	 * @return bool
	 */
	public function maybe_change_subscription_payment_method( $order_id ) {
		return (
			$this->is_subscriptions_enabled() &&
			$this->has_subscription( $order_id ) &&
			$this->is_changing_payment_method_for_subscription()
		);
	}

}
