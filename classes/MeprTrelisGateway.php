<?php
	
	
	if(class_exists('MeprBaseCtrl')){
	// create custom payment gateway class for memberpress
	class MeprTrelisGateway extends \MeprBaseRealGateway {
		
		/** Used in the view to identify the gateway */
		public function __construct() {
			$this -> name = 'Trelis';
			$this -> icon = TRELIS_PLUGIN_URL . 'assets/icons/trelis.png';
			$this -> desc = __( 'Pay with Wallet', 'memberpress' );
			$this -> key  = __( 'trelis', 'memberpress' );
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
					'use_icon' => true,
					'use_desc' => true,
					'email' => '',
					'sandbox' => false,
					'force_ssl' => false,
					'debug' => false,
					'test_mode' => false,
					'api_keys' => array(
						'live' => array(
							'api_key' => '',
							'api_secret' => ''
						),
						'test' => array(
							'api_key' => '',
							'api_secret' => ''
						)
					),
					'connect_status' => false,
					'service_account_id' => '',
					'service_account_name' => '',
				),
				(array)$this->settings
			);
			
			$this->id = $this->settings->id;
			$this->label = $this->settings->label;
			$this->use_label = $this->settings->use_label;
			$this->use_icon = $this->settings->use_icon;
			$this->use_desc = $this->settings->use_desc;
			// $this->connect_status = $this->settings->connect_status;
			// $this->service_account_id = $this->settings->service_account_id;
			// $this->service_account_name = $this->settings->service_account_name;
			//$this->recurrence_type = $this->settings->recurrence_type;
			
			if($this->is_test_mode()) {
				$this->settings->public_key = trim($this->settings->api_keys['test']['api_key']);
				$this->settings->secret_key = trim($this->settings->api_keys['test']['api_secret']);
				$this->settings->api_url = '';
			}
			else {
				$this->settings->public_key = trim($this->settings->api_keys['live']['api_key']);
				$this->settings->secret_key = trim($this->settings->api_keys['live']['api_secret']);
				$this->settings->api_url = '';
			}
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
		
		}
		
		public function validate_options_form($errors) {
		
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