<?php

class MeprTrelisGateway extends \MeprBaseRealGateway
{
	/** This will be where the gateway api will interacted from */
	public $trelis_api;

	/** Used in the view to identify the gateway */
	public function __construct()
	{
		$this->name = 'Trelis';
		$this->icon = TRELIS_PLUGIN_URL . 'assets/icons/trelis.png';
		$this->desc = __('Pay with Wallet', 'memberpress');
		$this->key  = 'trelis';
		$this->set_defaults();
		$this->has_spc_form = false;

		$this->capabilities = array(
			'process-payments',
			'process-refunds',
			'create-subscriptions',
			'cancel-subscriptions',
			'update-subscriptions',
			'suspend-subscriptions',
			'resume-subscriptions',
			'subscription-trial-payment'
		);

		// Setup the notification actions for this gateway
		$this->notifiers = array(
			'treliswhk' => 'webhook_listener',
			'return' => 'return_callback',
		);

		add_filter('mepr_options_helper_payment_methods', array($this, 'exclude_gateways'), 10, 2);
	}

	public function exclude_gateways($pm_ids, $field_name)
	{
		global $post;
		$product = new MeprProduct($post->ID);
		$payment_gateways = [];
		if (!(!$product->trial) && (in_array($product->period_type, ['months', 'years']) && $product->period == 1)) {
			foreach ($pm_ids as $key => $pm_id) {
				if ($pm_id !== $this->id) {
					$payment_gateways[] = $pm_id;
				}
			}
			return $payment_gateways;
		}
		return $pm_ids;
	}

	public function load($settings)
	{
		$this->settings = (object)$settings;
		$this->set_defaults();
		$this->trelis_api = new MeprTrelisAPI($this->settings);
	}

	protected function set_defaults()
	{
		if (!isset($this->settings)) {
			$this->settings = array();
		}

		$this->settings = (object)array_merge(
			array(
				'gateway' => 'MeprTrelisGateway',
				'id' => $this->generate_id(),
				'label' => 'Trelis',
				'use_label' => true,
				'icon' => TRELIS_PLUGIN_URL . 'assets/icons/trelis.png',
				'use_icon' => true,
				'desc' => __('Pay with crypto', 'memberpress'),
				'use_desc' => true,
				'api_key' => '',
				'api_secret' => '',
				'webhook_secret' => '',
				'is_prime' => '',
				'is_gasless' => '',
				'webhook_url' => __(home_url() . "/mepr/notify/" . $this->id . "/treliswhk"),
				'signature' => '',
				'sandbox' => false,
				'debug' => false
			),
			(array)$this->settings
		);

		$this->id = $this->settings->id;
		$this->label = $this->settings->label;
		$this->use_label = $this->settings->use_label;
		$this->icon = $this->settings->icon;
		$this->use_icon = $this->settings->use_icon;
		$this->desc = $this->settings->desc;
		$this->use_desc = $this->settings->use_desc;

		if ($this->is_test_mode()) {
			$this->settings->url     = 'aa';
			$this->settings->api_url = 'aa';
		} else {
			$this->settings->url = 'bb';
			$this->settings->api_url = 'bb';
		}

		// An attempt to correct people who paste in spaces along with their credentials
		$this->settings->api_key = trim($this->settings->api_key);
		$this->settings->api_secret = $this->settings->api_secret;
		$this->settings->webhook_secret = $this->settings->webhook_secret;
		$this->settings->webhook_url = $this->settings->webhook_url;
		$this->settings->is_prime = ($this->settings->is_prime ? $this->settings->is_prime : false);
		$this->settings->is_gasless = ($this->settings->is_gasless ? $this->settings->is_gasless : false);
	}


	public function display_options_form()
	{
		$mepr_options = MeprOptions::fetch();
		$api_key = trim($this->settings->api_key);
		$api_secret = trim($this->settings->api_secret);
		$webhook_secret    = trim($this->settings->webhook_secret);
		$is_prime = $this->settings->is_prime;
		$is_gasless = $this->settings->is_gasless;
?>

		<div x-data="{ open: true }">
			<table x-show="open">
				<tr class="advanced_mode_row-<?php echo $this->id; ?> ">
					<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<em><?php _e('API Key:', 'memberpress'); ?></em></td>
					<td><input type="text" class="mepr-auto-trim" name="<?php echo $mepr_options->integrations_str; ?>[<?php echo $this->id; ?>][api_key]" value="<?php echo $api_key; ?>" /></td>
				</tr>
				<tr class="advanced_mode_row-<?php echo $this->id; ?> ">
					<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<em><?php _e('API Secret:', 'memberpress'); ?></em></td>
					<td><input type="password" class="mepr-auto-trim" name="<?php echo $mepr_options->integrations_str; ?>[<?php echo $this->id; ?>][api_secret]" value="<?php echo $api_secret; ?>" /></td>
				</tr>
				<tr class="advanced_mode_row-<?php echo $this->id; ?>">
					<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<em><?php _e('Webhook Secret:', 'memberpress'); ?></em></td>
					<td><input type="password" class="mepr-auto-trim" name="<?php echo $mepr_options->integrations_str; ?>[<?php echo $this->id; ?>][webhook_secret]" value="<?php echo $webhook_secret; ?>" /></td>
				</tr>
				<tr class="advanced_mode_row-<?php echo $this->id; ?>">
					<th scope="row"><label><?php _e('Trelis Webhook URL:', 'memberpress'); ?></label></th>
					<td><?php MeprAppHelper::clipboard_input($this->notify_url('treliswhk')); ?></td>
				</tr>
				<tr class="advanced_mode_row-<?php echo $this->id; ?>">
					<th scope="row"><label for="<?php echo $is_prime; ?>"><?php _e('Trelis Prime', 'memberpress'); ?></label></th>
					<td><input type="checkbox" name="<?php echo $mepr_options->integrations_str; ?>[<?php echo $this->id; ?>][is_prime]" <?php echo ($this->settings->is_prime ? "checked" : ''); ?> /></td>
				</tr>
				<tr class="advanced_mode_row-<?php echo $this->id; ?>">
					<th scope="row"><label for="<?php echo $is_gasless; ?>"><?php _e('Gasless Payments', 'memberpress'); ?></label></th>
					<td><input type="checkbox" name="<?php echo $mepr_options->integrations_str; ?>[<?php echo $this->id; ?>][is_gasless]" <?php echo ($this->settings->is_gasless ? "checked" : ''); ?> /></td>
				</tr>
			</table>
		</div>
	<?php }

	public function validate_options_form($errors)
	{
		$mepr_options = MeprOptions::fetch();
		if (!isset($_POST[$mepr_options->integrations_str][$this->id]['api_key']) or empty($_POST[$mepr_options->integrations_str][$this->id]['api_key'])) {
			$errors[] = __("API Key field can't be blank.", 'memberpress');
		}

		if (!isset($_POST[$mepr_options->integrations_str][$this->id]['api_secret']) or empty($_POST[$mepr_options->integrations_str][$this->id]['api_secret'])) {
			$errors[] = __("API Secret field can't be blank.", 'memberpress');
		}

		return $errors;
	}

	public function is_test_mode()
	{
		if (defined('TRELIS_TESTING') && TRELIS_TESTING == true) {
			$this->settings->test_mode = true;
			return true;
		}
		return (isset($this->settings->test_mode) && $this->settings->test_mode);
	}

	//create recurring subscription payment
	public function process_create_subscription($txn)
	{
		if (isset($txn) and $txn instanceof MeprTransaction) {
			$usr = $txn->user();
			$prd = $txn->product();
		} else {
			throw new MeprGatewayException(__('Payment transaction intialization was unsuccessful, please try again.', 'memberpress'));
		}

		$subscription = new MeprProduct($txn->product_id);

		$subscription_type = "MONTHLY";
		if ($subscription_type == "months") {
			$subscription_type = "MONTHLY";
		}
		if ($subscription_type == "years") {
			$subscription_type = "YEARLY";
		}

		$args = MeprHooks::apply_filters('mepr_trelis_payment_args', array(
			'subscriptionPrice' => $subscription->price,
			'frequency'         => $subscription_type,
			'subscriptionName'  => sanitize_title($subscription->post_title),
			'fiatCurrency'      => $this->trelis_api->get_mepr_currency(),
			'subscriptionType'  => "automatic",
			"redirectLink" => $this->notify_url('return') . '?txn=' . $txn->trans_num
		), $txn);

		// Initialize a new payment here
		$response = (object) $this->trelis_api->send_request("create-subscription-link", $args);
		print_r($response); die;

		$str = explode("/", $response->data['subscriptionLink']);
		$linkId = $str[4];
		$this->update_mepr_transaction_meta($txn->id, '_trelis_payment_link_id', $linkId);

		return MeprUtils::wp_redirect("{$response->data['subscriptionLink']}");
	}


	//create One time payment
	public function process_payment($txn, $trial = false)
	{
		if (isset($txn) and $txn instanceof MeprTransaction) {

			$usr = $txn->user();
			$prd = $txn->product();
		} else {
			throw new MeprGatewayException(__('Payment transaction intialization was unsuccessful, please try again.', 'memberpress'));
		}
		$product = new MeprProduct($txn->product_id);

		$productName = sanitize_title($product->post_title);
		$productPrice = $product->price;

		// Initialize the charge on Trelis's servers - this will charge the user's card
		$args = MeprHooks::apply_filters('mepr_trelis_payment_args', array(
			"productName" => $productName,
			"productPrice"  => $productPrice,
			"token" => $this->trelis_api->get_mepr_token(),
			"redirectLink" => $this->notify_url('return') . '?txn=' . $txn->trans_num,
			"fiatCurrency" => $this->trelis_api->get_mepr_currency(),
			"isPrime" => $this->settings->is_prime,
			"isGasless" => $this->settings->is_gasless
		), $txn);

		// Initialize a new payment here
		$response = (object) $this->trelis_api->send_request("create-dynamic-link", $args);

		$str = explode("/", $response->data['productLink']);
		$linkId = $str[4];
		$this->update_mepr_transaction_meta($txn->id, '_trelis_payment_link_id', $linkId);

		return MeprUtils::wp_redirect("{$response->data['productLink']}");
	}

	/** 
	 * This method should be used by the class to verify a successful payment by the given
	 * the gateway. This method should also be used by any IPN requests or Silent Posts.
	 */
	public function return_callback()
	{
		$mepr_options = MeprOptions::fetch();
		$obj = MeprTransaction::get_one_by_trans_num($_GET['txn']);
		if (is_object($obj) and isset($obj->id)) {

			// Redirect to thank you page
			$product = new MeprProduct($obj->product_id);
			$sanitized_title = sanitize_title($product->post_title);

			$query_params = array(
				'membership' => $sanitized_title,
				'trans_num' => $obj->trans_num,
				'membership_id' => $product->ID
			);

			MeprUtils::wp_redirect($mepr_options->thankyou_page_url(build_query($query_params)));
		}
	}

	/** Trelis SPECIFIC METHODS **/
	public function webhook_listener()
	{
		// retrieve the request's body
		$json = file_get_contents('php://input');
		$request =  (object)json_decode($json, true);

		//debug mail
		// $headers = array('Content-Type: text/html; charset=UTF-8');
		// wp_mail( 'jalpesh@yopmail.com', 'MEPR Process Payment webhook',print_r($json, true), $headers );
		// debug mail end

		$merchantKey = '';
		if ($request->merchantProductKey) {
			$merchantKey = $request->merchantProductKey;
		} else {
			$merchantKey = $request->subscriptionLink;
		}

		if ($request->from) {
			$customerWalletId = $request->from;
		} else {
			$customerWalletId = $request->customer;
		}

		if ($transaction_meta = $this->get_mepr_transaction_id($merchantKey)) {

			$transaction_id = $transaction_meta[0]->transaction_id;
			$txn = new MeprTransaction($transaction_id);
			$sub = new MeprSubscription($txn->subscription_id);

			if ($request->event == 'submission.success') {
				$txn->status = MeprTransaction::$pending_str;
				$sub->status = MeprSubscription::$pending_str;

				MeprTransaction::update($txn);
				MeprSubscription::update($sub);
				
			} else if ($request->event == 'charge.success') {
				$txn->status = MeprTransaction::$confirmed_str;
				$sub->status = MeprSubscription::$active_str;

				MeprTransaction::update($txn);
				MeprSubscription::update($sub);

			} else if ($request->event == 'charge.failed') {
				$txn->status = MeprTransaction::$pending_str;
				$sub->status = MeprSubscription::$pending_str;
				
				MeprTransaction::update($txn);
				MeprSubscription::update($sub);

			} else if ($request->event == 'subscription.create.success') {
				$this->update_mepr_subscription_meta($txn->subscription_id, '__trelis_customer_wallet_id', $customerWalletId);

				$txn->status = MeprTransaction::$confirmed_str;
				$sub->status = MeprSubscription::$pending_str;

				MeprTransaction::update($txn);
				MeprSubscription::update($sub);

			} else if ($request->event == 'subscription.create.failed') {
				$this->update_mepr_subscription_meta($txn->subscription_id, '__trelis_customer_wallet_id', $customerWalletId);

				$txn->status = MeprTransaction::$pending_str;
				$sub->status = MeprSubscription::$pending_str;

				MeprTransaction::update($txn);
				MeprSubscription::update($sub);

			} else if ($request->event == 'subscription.charge.failed') {
				$this->update_mepr_subscription_meta($txn->subscription_id, '__trelis_customer_wallet_id', $customerWalletId);

				$txn->status = MeprTransaction::$failed_str;
				$sub->status = MeprSubscription::$pending_str;
				
				MeprTransaction::update($txn);
				MeprSubscription::update($sub);

			} else if ($request->event == 'subscription.charge.success') {

				$this->update_mepr_subscription_meta($txn->subscription_id, '__trelis_customer_wallet_id', $customerWalletId);

				$txn->status = MeprTransaction::$confirmed_str;
				$sub->status = MeprSubscription::$active_str;
				
				MeprTransaction::update($txn);
				MeprSubscription::update($sub);

			} else if ($request->event == 'subscription.cancellation.success') {
				$this->update_mepr_subscription_meta($txn->subscription_id, '__trelis_customer_wallet_id', $customerWalletId);

				$txn->status = MeprTransaction::$pending_str;
				$sub->status = MeprSubscription::$cancelled_str;

				MeprTransaction::update($txn);
				MeprSubscription::update($sub);
				
			} else if ($request->event == 'subscription.cancellation.failed') {
				$this->update_mepr_subscription_meta($txn->subscription_id, '__trelis_customer_wallet_id', $customerWalletId);
			}
		} else {
			throw new MeprGatewayException(__('Transaction id is not available in transaction_meta.', 'memberpress'));
		}
	}

	public function process_refund($txn)
	{
		// var_dump("process_refund"); die;
	}
	public function record_payment()
	{
		// var_dump("record_payment"); die;
	}
	public function record_refund()
	{
		// var_dump("record_refund"); die;
	}
	public function record_subscription_payment()
	{
		// var_dump("record_subscription_payment"); die;	
	}

	public function record_payment_failure()
	{
		// var_dump("record_payment_failure"); die;
	}

	public function process_trial_payment($transaction)
	{
		// var_dump("process_trial_payment"); die;
	}

	public function record_trial_payment($transaction)
	{
		// var_dump("record_trial_payment"); die;
	}

	public function  record_create_subscription()
	{
		// var_dump("record_create_subscription"); die;
	}

	public function process_update_subscription($subscription_id)
	{
		// var_dump("process_update_subscription"); die;
	}

	public function record_update_subscription()
	{
		// var_dump("record_update_subscription"); die;
	}

	public function process_suspend_subscription($subscription_id)
	{
		// var_dump("process_suspend_subscription"); die;
	}

	public function record_suspend_subscription()
	{
		// var_dump("record_suspend_subscription"); die;
	}

	public function process_resume_subscription($subscription_id)
	{
		// var_dump("process_resume_subscription"); die;
	}

	public function record_resume_subscription()
	{
		// var_dump("record_resume_subscription"); die;
	}

	/** Used to cancel a subscription by the given gateway. This method should be used
	 * by the class to record a successful cancellation from the gateway. This method
	 * should also be used by any IPN requests or Silent Posts.
	 */
	public function process_cancel_subscription($sub_id)
	{
		$sub = new MeprSubscription($sub_id);

		if (!isset($sub->id) || (int) $sub->id <= 0)
			throw new MeprGatewayException(__('This subscription is invalid.', 'memberpress'));

		$customer_wallet = $this->get_mepr_subscription_meta($sub->id, '__trelis_customer_wallet_id');

		if ($customer_wallet) {

			$args = MeprHooks::apply_filters('mepr_paystack_cancel_subscription_args', array(
				'customer' => $customer_wallet[0]->meta_value,
			), $sub);

			// Yeah ... we're cancelling here bro ... but this time we don't want to restart again
			$this->trelis_api->send_request("cancel-subscription", $args);

			if (!$sub) {
				return false;
			}

			// Seriously ... if sub was already cancelled what are we doing here?
			if ($sub->status == MeprSubscription::$cancelled_str) {
				return $sub;
			}

			$sub->status = MeprSubscription::$cancelled_str;
			$sub->update();

			$sub->limit_reached_actions();

			MeprUtils::send_cancelled_sub_notices($sub);

			return $sub;
		} else {
			throw new MeprGatewayException(__('Customer Wallet not found.', 'memberpress'));
		}
	}

	/** This method should be used by the class to record a successful cancellation
	 * from the gateway. This method should also be used by any IPN requests or
	 * Silent Posts.
	 */
	public function record_cancel_subscription()
	{
	}
	public function process_signup_form($txn)
	{
		// var_dump("process_signup_form"); die;
	}

	public function display_payment_page($txn)
	{
		// var_dump("display_payment_page"); die;
	}
	public function  enqueue_payment_form_scripts()
	{
		// var_dump("enqueue_payment_form_scripts"); die;
	}

	/** 
	 * This gets called on the_content and just renders the payment form
	 */
	public function display_payment_form($amount, $user, $product_id, $txn_id)
	{
		$mepr_options = MeprOptions::fetch();
		$prd = new MeprProduct($product_id);
		$coupon = false;

		$txn = new MeprTransaction($txn_id);

		//Artifically set the price of the $prd in case a coupon was used
		if ($prd->price != $amount) {
			$coupon = true;
			$prd->price = $amount;
		}

		$invoice = MeprTransactionsHelper::get_invoice($txn);
		echo $invoice;
	?>
		<div class="mp_wrapper mp_payment_form_wrapper">
			<div class="mp_wrapper mp_payment_form_wrapper">
				<?php MeprView::render('/shared/errors', get_defined_vars()); ?>
				<form action="" method="post" id="mepr_trelis_payment_form" class="mepr-checkout-form mepr-form mepr-card-form" novalidate>
					<input type="hidden" name="mepr_process_payment_form" value="Y" />
					<input type="hidden" name="mepr_transaction_id" value="<?php echo $txn->id; ?>" />

					<?php MeprHooks::do_action('mepr-trelis-payment-form', $txn); ?>
					<div class="mepr_spacer">&nbsp;</div>

					<input type="submit" class="mepr-submit" value="<?php _e('Pay Now', 'memberpress'); ?>" />
					<img src="<?php echo admin_url('images/loading.gif'); ?>" style="display: none;" class="mepr-loading-gif" />
					<?php MeprView::render('/shared/has_errors', get_defined_vars()); ?>
				</form>
			</div>
		</div>
<?php
	}


	public function validate_payment_form($errors)
	{
		// var_dump("validate_payment_form"); die;
	}

	public function display_update_account_form($subscription_id, $errors = array(), $message = "")
	{
		// var_dump("display_update_account_form"); die;
	}

	public function validate_update_account_form($errors = array())
	{
		// var_dump("validate_update_account_form"); die;
	}

	public function process_update_account_form($subscription_id)
	{
		// var_dump("process_update_account_form"); die;
	}
	public function force_ssl()
	{
		// var_dump("force_ssl"); die;
	}

	public function update_mepr_subscription_meta($subscription_id, $meta_key, $meta_value)
	{
		global $wpdb;

		$table_name =  $wpdb->prefix . 'mepr_subscription_meta';
		$data = array(
			'subscription_id' => $subscription_id,
			'meta_key' => $meta_key,
			'meta_value' => $meta_value
		);

		$result = $wpdb->update($table_name, $data, array('subscription_id' => $subscription_id));

		//If nothing found to update, it will try and create the record.
		if ($result === FALSE || $result < 1) {
			$wpdb->insert($table_name, $data);
		}
	}

	public function get_mepr_subscription_meta($subscription_id, $meta_key)
	{
		global $wpdb;
		return $wpdb->get_results("SELECT * FROM  {$wpdb->prefix}mepr_subscription_meta WHERE subscription_id = {$subscription_id} and meta_key= '{$meta_key}'");
	}

	public function update_mepr_transaction_meta($txn_id, $meta_key, $meta_value)
	{
		global $wpdb;

		$table_name =  $wpdb->prefix . 'mepr_transaction_meta';
		$data = array(
			'transaction_id' => $txn_id,
			'meta_key' => $meta_key,
			'meta_value' => $meta_value
		);

		$result = $wpdb->update($table_name, $data, array('transaction_id' => $txn_id));

		//If nothing found to update, it will try and create the record.
		if ($result === FALSE || $result < 1) {
			$wpdb->insert($table_name, $data);
		}
	}

	public function get_mepr_transaction_id($meta_value)
	{
		global $wpdb;
		return $wpdb->get_results("SELECT * FROM  {$wpdb->prefix}mepr_transaction_meta WHERE meta_value = '{$meta_value}'");
	}
}
