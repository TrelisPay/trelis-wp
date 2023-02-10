<?php
	
	
	if(class_exists('MeprBaseCtrl')){
	// create custom payment gateway class for memberpress
	class MeprTrelisGateway extends \MeprBaseRealGateway {
		
		/** Used in the view to identify the gateway */
		public function __construct() {
			$this -> name = 'Trelis';
			$this -> icon = TRELIS_PLUGIN_URL . 'assets/icons/trelis.png';
			$this -> desc = __( 'Pay with Wallet', 'memberpress' );
			$this -> key  = 'trelis';
			$this -> set_defaults();
			$this -> has_spc_form = false;
			
			$this -> capabilities = array (
				'process-payments',
				'process-refunds',
				'create-subscriptions',
				'cancel-subscriptions',
				'update-subscriptions',
				'suspend-subscriptions',
				'resume-subscriptions',
				'subscription-trial-payment'
			);
		}
		
		
		public function load($settings) {
			$this->settings = (object)$settings;
			$this->set_defaults();
		}
		
		protected function set_defaults() {
			if(!isset($this->settings)) {
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
				  'desc' => __('Pay via your Trelis account', 'memberpress'),
				  'use_desc' => true,
				  'api_key' => '',
				  'api_secret' => '',
				  'webhook_secret' => '',
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
		  
			  if($this->is_test_mode()) {
				$this->settings->url     = 'aa';
				$this->settings->api_url = 'aa';
			  }
			  else {
				$this->settings->url = 'bb';
				$this->settings->api_url = 'bb';
			  }
		  
			//   $this->settings->api_version = 69;
		  
			  // An attempt to correct people who paste in spaces along with their credentials
			  $this->settings->api_key = trim($this->settings->api_key);
			  $this->settings->api_secret = $this->settings->api_secret;
			  $this->settings->webhook_secret = $this->settings->webhook_secret;
		}
		
		public function process_payment($txn) {
		
		}
		
		public function process_refund($txn) {
		
		}
		public function record_payment() {
		
		}
		public function record_refund() {
		
		}
		public function record_subscription_payment() {
		
		}
		public function record_payment_failure(  ) {
		
		}
		
		public function process_trial_payment( $transaction ) {
		
		}
		
		public function record_trial_payment( $transaction ) {
		
		
		}
		
		public function process_create_subscription($transaction) {
		
		}
		
		public function  record_create_subscription() {
		
		}
		
		public function process_update_subscription($subscription_id) {
		
		}
		
		public function record_update_subscription() {
		
		}
		
		public function process_suspend_subscription($subscription_id) {
		
		}
		
		public function record_suspend_subscription(  ) {
		
		}
		
		public function process_resume_subscription($subscription_id) {
		
		}
		
		public function record_resume_subscription() {
		
		}
		
		public function process_cancel_subscription($subscription_id) {
		
		}
		
		public function  record_cancel_subscription(  ) {
		
		}
		
		public function process_signup_form($txn) {
		
		}
		
		public function display_payment_page($txn) {
		
		}
		
		public function display_options_form(  ) {
			$mepr_options = MeprOptions::fetch();

			$api_key = trim($this->settings->api_key);
			$api_secret = trim($this->settings->api_secret);
			$webhook_secret    = trim($this->settings->webhook_secret);

			$sandbox      = ($this->settings->sandbox == 'on' or $this->settings->sandbox == true);
			$debug        = ($this->settings->debug == 'on' or $this->settings->debug == true);
		 ?>
      <div x-data="{ open: true }">
    


    <table x-show="open">
      
      <tr class="advanced_mode_row-<?php echo $this->id;?> ">
        <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<em><?php _e('API Key:', 'memberpress'); ?></em></td>
        <td><input type="text" class="mepr-auto-trim" name="<?php echo $mepr_options->integrations_str; ?>[<?php echo $this->id;?>][api_key]" value="<?php echo $api_key; ?>" /></td>
      </tr>
      <tr class="advanced_mode_row-<?php echo $this->id;?> ">
        <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<em><?php _e('API Secret:', 'memberpress'); ?></em></td>
        <td><input type="password" class="mepr-auto-trim" name="<?php echo $mepr_options->integrations_str; ?>[<?php echo $this->id;?>][api_secret]" value="<?php echo $api_secret; ?>" /></td>
      </tr>
      <tr class="advanced_mode_row-<?php echo $this->id;?>">
        <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<em><?php _e('Webhook Secret:', 'memberpress'); ?></em></td>
        <td><input type="password" class="mepr-auto-trim" name="<?php echo $mepr_options->integrations_str; ?>[<?php echo $this->id;?>][webhook_secret]" value="<?php echo $webhook_secret; ?>" /></td>
      </tr>
     
      
    	</table>
      </div>
    <?php
	}
		
		public function validate_options_form($errors) {
			$mepr_options = MeprOptions::fetch();

			if( !isset($_POST[$mepr_options->integrations_str][$this->id]['api_key']) or
				empty($_POST[$mepr_options->integrations_str][$this->id]['api_key'])) {
				$errors[] = __("API Key field can't be blank.", 'memberpress');
			}

			if( !isset($_POST[$mepr_options->integrations_str][$this->id]['api_secret']) or
				empty($_POST[$mepr_options->integrations_str][$this->id]['api_secret']) ) {
				$errors[] = __("API Secret field can't be blank.", 'memberpress');
			}

    		return $errors;
		}
		
		public function  enqueue_payment_form_scripts() {
		
		}
		
		public function  display_payment_form($amount, $user, $product_id, $transaction_id) {
		
		}
		
		public function validate_payment_form($errors) {
		
		}
		
		public function display_update_account_form($subscription_id, $errors=array(), $message="") {
			
		}
		
		public function validate_update_account_form($errors=array()) {
			
		}
		
		public function process_update_account_form($subscription_id) {
			
		}
		
		public function is_test_mode() {
			if (defined('TRELIS_TESTING') && TRELIS_TESTING == true) {
				$this->settings->test_mode = true;
				return true;
			  }
			  return (isset($this->settings->test_mode) && $this->settings->test_mode);
		}
		
		public function force_ssl(  ) {
			
		}
	}
}