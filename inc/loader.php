<?php
require_once TMWNI_DIR . 'inc/NS_Toolkit/src/NetSuiteService.php';

foreach (glob(TMWNI_DIR . 'inc/NS_Toolkit/src/Classes/*.php') as $filename) {
	require_once $filename;
}





use NetSuite\NetSuiteService;
use NetSuite\Classes\CustomerAddressbookList;
use NetSuite\Classes\CustomerAddressbook;
use NetSuite\Classes\Address;
use NetSuite\Classes\Customer;
use NetSuite\Classes\GetRequest;
use NetSuite\Classes\GetResponse;

use NetSuite\Classes\RecordRef;
use NetSuite\Classes\GetAllRequest;
use NetSuite\Classes\GetAllRecord;
use NetSuite\Classes\GetListRequest;
use NetSuite\Classes\ListOrRecordRef;
use NetSuite\Classes\Record;
use NetSuite\Classes\BaseRef;
use NetSuite\Classes\PriceLevel;












class TMWNI_Loader {

	private static $instance = null;

	public static function getInstance() {
		if (null === self::$instance) {
			self::$instance = new TMWNI_Loader();
		}
		return self::$instance;
	}

	public $netsuiteOrderClient = '';

	/**
	 * Construct
	 *
	*/ 
	public function __construct() {

				global $TMWNI_OPTIONS;
				$this->netsuiteService = new NetSuiteService(null, array('exceptions' => true));

		if (TMWNI_Settings::areCredentialsDefined()) {

						
			require_once TMWNI_DIR . 'inc/inventory.php';
			
			
			if (isset($TMWNI_OPTIONS['enableCustomerSync']) && 'on' == $TMWNI_OPTIONS['enableCustomerSync']) {
				//USER PROFILE HOOKS
				//wordpress user register
				add_action('user_register', array($this, 'addUpdateNetsuiteCustomer'));

				//wooocommerce customer created
				add_action('woocommerce_created_customer', array($this, 'addUpdateNetsuiteCustomer'));

				//hook for detecting customer address save
				add_action('woocommerce_customer_save_address', array($this, 'addUpdateNetsuiteCustomer'));

				//hook for detetcting update in user profile
				add_action('profile_update', array($this, 'profileUpdateNetSuiteCustomer'));
			}

			add_action('wp_ajax_manual_order_sync', array($this, 'ManualOrderSync'));



			add_action('woocommerce_order_actions', array($this, 'sync_to_netsuite_action'));

			add_action('woocommerce_order_action_sync_to_netsuite', array($this, 'sync_to_netsuite'));
			
			if (isset($TMWNI_OPTIONS['ns_order_autosync_status']) && !empty($TMWNI_OPTIONS['ns_order_autosync_status'])) {

				if (isset($TMWNI_OPTIONS['enableOrderSync']) && 'on' == $TMWNI_OPTIONS['enableOrderSync']) {
					//dynamic order status
					add_action('woocommerce_order_status_' . $TMWNI_OPTIONS['ns_order_autosync_status'], array($this, 'addNetsuiteOrder'));
				}
			} else {
				//order status processing
				add_action('woocommerce_order_status_processing', array($this, 'sync_to_netsuite'));
			}

			if (isset($TMWNI_OPTIONS['ns_autosync_on_order_status_changes']) && !empty($TMWNI_OPTIONS['ns_autosync_on_order_status_changes'])) {
				foreach ($TMWNI_OPTIONS['ns_autosync_on_order_status_changes'] as $key => $value ) {
				  add_action( 'woocommerce_order_status_' . $value, array($this, 'addNetsuiteOrderOnStatusChange'), 10, 2 );
				}
			}

		  add_filter('cron_schedules', array($this, 'orderTrackingCron'));

			if (!wp_next_scheduled('fetch_order_tracking_info')) {
			  wp_schedule_event(time(), 'once_every_hour', 'fetch_order_tracking_info');
			}

			//Fetching Order tracking info
		add_action('fetch_order_tracking_info', array($this, 'fetchOrderTrackingInfo'));

			//Custom woo order tracking email
		add_filter( 'woocommerce_email_classes', array($this,'ns_ups_order_tracking_woocommerce_email') );

			if (isset($TMWNI_OPTIONS['syncDeletedOrders']) && 'on' == $TMWNI_OPTIONS['syncDeletedOrders']) {
				add_action( 'wp_trash_post', array($this, 'deleteNetsuiteOrder') );
			}
		
			if (isset($TMWNI_OPTIONS['recreateOnRestore']) && 'on' == $TMWNI_OPTIONS['recreateOnRestore']) {
				add_action( 'untrashed_post', array($this, 'restoreNetsuiteOrder') );
			}
		
		}

	
	}

	/**
	 * Order Tracking Woo email
	 *
	*/ 
	public function ns_ups_order_tracking_woocommerce_email( $email_classes ) {

		// add the custom email class to the list of email classes that WooCommerce loads
		$email_classes['WC_Ups_Order_Tracking_No'] = require( TMWNI_DIR . 'inc/woo-email-class/class-wc-ups-order-trackingno-email.php' );
	
		return $email_classes;

	}

	/**
	 * Sync  to NS
	 *
	*/ 
	public function sync_to_netsuite_action( $actions) {
		$actions['sync_to_netsuite'] = __('Sync to NetSuite', 'songlify');
		return $actions;
	}

	public function ManualOrderSync() {
		global $TMWNI_OPTIONS;

		if (isset($_POST['nonce']) && !empty($_POST['nonce']) && !wp_verify_nonce(sanitize_text_field($_POST['nonce']), 'security_nonce') ) {
			die('Order Logs Nonce Error'); 
		}
		
		if (isset($_POST['order_id']) && !empty($_POST['order_id']) ) {
			if (isset($TMWNI_OPTIONS['enableOrderSync']) && 'on' == $TMWNI_OPTIONS['enableOrderSync']) {
			$order_id = intval($_POST['order_id']);
			$response = $this->addNetsuiteOrder($order_id);
			esc_attr_e($response); 
			}
		}
			

	}


	/**
	 * User Update
	 *
	*/ 
	public function profileUpdateNetSuiteCustomer( $customer_id) {
		if (!empty($_GET['wc-ajax']) && 'checkout' ==$_GET['wc-ajax']) {
			$var = 'checkout';
		} else {
			$this->addUpdateNetsuiteCustomer($customer_id);
		}
	}

	/**
	 * Order status change
	 *
	*/ 
	public function addNetsuiteOrderOnStatusChange( $order_id, $instance) {
		global $TMWNI_OPTIONS;
		if (isset($TMWNI_OPTIONS['enableOrderSync']) && 'on' == $TMWNI_OPTIONS['enableOrderSync']) {
			$this->addNetsuiteOrder($order_id);
		}
		return;
	}

	/**
	 * Sync Order to Netsuite
	 *
	*/ 
	public function sync_to_netsuite( $order) {
		global $TMWNI_OPTIONS;

		if (isset($TMWNI_OPTIONS['enableOrderSync']) && 'on' == $TMWNI_OPTIONS['enableOrderSync']) {
			if (!is_object($order)) {
				$this->addNetsuiteOrder($order);

			} else {
				$this->addNetsuiteOrder($order->get_id());

			}
		}
		return;
	}

	/**
	 * Hook function which will recieve customer id and pass it to net  update_user_meta($customer_id, TMWNI_Settings::$ns_customer_id, $customer_internal_id);
	 */
	public function addUpdateNetsuiteCustomer( $customer_id, $order_id = 0) {
	 

		
		global $TMWNI_OPTIONS;

		//get and set customer data 
		$woo_customer_data = get_userdata($customer_id);
		
		//check if passed user is a customer
		if (isset($woo_customer_data->roles[0]) && in_array($woo_customer_data->roles[0], $TMWNI_OPTIONS['customer_roles'])) {
			require_once TMWNI_DIR . 'inc/customer.php';

			//instance of API client
			$netsuiteCustomerClient = new CustomerClient();
			$email = $woo_customer_data->data->user_email;
			$customer_internal_id = get_user_meta($customer_id, TMWNI_Settings::$ns_customer_id, true);
			
			if (empty($customer_internal_id)) {
				//check if customer already registered on netsuite
				$customer_internal_id = $netsuiteCustomerClient->searchCustomer($woo_customer_data->data->user_email, $customer_id);
			}


			if (!empty($woo_customer_data->first_name) && !empty($woo_customer_data->last_name)) {
				$first_name = $woo_customer_data->first_name;
				$last_name = $woo_customer_data->last_name;
			} else {
				$first_name = get_user_meta($customer_id, 'billing_first_name', true);
				$last_name = get_user_meta($customer_id, 'billing_last_name', true);
			}

			$company_name = get_user_meta($customer_id, 'billing_company', true);
			$phone = get_user_meta($customer_id, 'billing_phone', true);

			$customer_data = array(
				'customer_id' => $customer_id,
				'firstName' => $first_name,
				'lastName' => $last_name,
				'email' => $email,
				'companyName' => $company_name,
				'phone' => $phone
			);
			update_user_meta($customer_id, TMWNI_Settings::$ns_customer_id, $customer_internal_id);
			$address_type = array('billing', 'shiping');
			$addresses = array();
			foreach ($address_type as $single_address) {
				$address['firstName'] = get_user_meta($customer_id, $single_address . '_first_name', true);
				$address['lastName'] = get_user_meta($customer_id, $single_address . '_last_name', true);
				$address['companyName'] = get_user_meta($customer_id, $single_address . '_company', true);
				$address['address1'] = get_user_meta($customer_id, $single_address . '_address_1', true);
				$address['address2'] = get_user_meta($customer_id, $single_address . '_address_2', true);
				$address['city'] = get_user_meta($customer_id, $single_address . '_city', true);
				$address['state'] = get_user_meta($customer_id, $single_address . '_state', true);
				$address['postcode'] = get_user_meta($customer_id, $single_address . '_postcode', true);
				$address['country'] = get_user_meta($customer_id, $single_address . '_country', true);
				$addresses[$single_address] = $address;
			}

			foreach ($addresses as $key => $address) {
				if (isset($address['country']) && !empty($address['country'])) {
					$ns_country = TMWNI_Settings::$netsuite_country[$address['country']];
				} else {
					$ns_country = '';
				}
				if ('billing' == $key) {
					$al = new CustomerAddressbookList();
					$al->addressbook = new CustomerAddressbook();
					$al->addressbook->internalId = $customer_internal_id;
					$al->addressbook->defaultShipping = false;
					$al->addressbook->defaultBilling = true;
					$al->addressbook->isResidential = true;
					$al->addressbook->label = 'Customer Address';
					$al->addressbook->addressbookAddress = new Address();
					$al->addressbook->addressbookAddress->addr1 = $address['address1'];
					$al->addressbook->addressbookAddress->addr2 = $address['address2'];
					$al->addressbook->addressbookAddress->addr3 = '';
					$al->addressbook->addressbookAddress->addressee = $address['firstName'] . ' ' . $address['lastName'];
;
					$al->addressbook->addressbookAddress->addrPhone = '';
					$al->addressbook->addressbookAddress->addrText = $address['address1'];
					$al->addressbook->addressbookAddress->attention = $address['companyName'];
					$al->addressbook->addressbookAddress->city = $address['city'];
					$al->addressbook->addressbookAddress->country = $ns_country;
					$al->addressbook->addressbookAddress->internalId = $customer_internal_id;
					$al->addressbook->addressbookAddress->override = false;
					$al->addressbook->addressbookAddress->state = $address['state'];
					$al->addressbook->addressbookAddress->zip = $address['postcode'];


					$al = apply_filters('tm_netsuite_customer_data', $al);

					if (!empty($customer_internal_id)) {
						$netsuiteCustomerClient->updateCustomer($customer_data, $customer_internal_id, $al, $address['state'], $order_id);
					} else {
						//add customer to netsuite
						$customer_internal_id = $netsuiteCustomerClient->addCustomer($customer_data, $al, $address['state'], $order_id);
						if ($customer_internal_id) {
							update_user_meta($customer_id, TMWNI_Settings::$ns_customer_id, $customer_internal_id);
						}
					}
					
				}
			}

			//update customer if it already exists on netsuite

			return $customer_internal_id;
		}
		return 0;
	}
	/**
	 * Sync Guest Customer
	 *
	*/ 
	public function addUpdateNetsuiteGuestCustomer( $order) {
		require_once TMWNI_DIR . 'inc/customer.php';
		//instance of API client
		$netsuiteCustomerClient = new CustomerClient();

		$email = $order->get_billing_email();

		$customer_id = 0;
		$customer_internal_id = 0;
		//check if customer already registered on netsuite
		$customer_internal_id = $netsuiteCustomerClient->searchCustomer($email);
		$first_name = $order->get_billing_first_name();
		$last_name = $order->get_billing_last_name();
		$company_name = $order->get_billing_company();
		$cust_address = $order->get_Address();
		$phone = $order->get_billing_phone();
		$cust_order_id = $order->get_id();

		$customer_data = array(
			'customer_id' => $customer_id,
			'firstName' => $first_name,
			'lastName' => $last_name,
			'email' => $email,
			'companyName' => $company_name,
			'phone' => $phone,
		);

		if (isset($cust_address['country']) && !empty($cust_address['country'])) {
			$ns_country = TMWNI_Settings::$netsuite_country[$cust_address['country']];
		} else {
			$ns_country = '';
		}

		$al = new CustomerAddressbookList();
		$al->addressbook = new CustomerAddressbook();
		$al->addressbook->internalId = $customer_internal_id;
		$al->addressbook->defaultShipping = false;
		$al->addressbook->defaultBilling = true;
		$al->addressbook->isResidential = true;
		$al->addressbook->label = 'Customer Address';
		$al->addressbook->addressbookAddress = new Address();
		$al->addressbook->addressbookAddress->addr1 = $cust_address['address_1'];
		$al->addressbook->addressbookAddress->addr2 = $cust_address['address_2'];
		$al->addressbook->addressbookAddress->addr3 = '';
		$al->addressbook->addressbookAddress->addressee = $first_name . ' ' . $last_name;
		$al->addressbook->addressbookAddress->addrPhone = $phone;
		$al->addressbook->addressbookAddress->addrText = $cust_address['address_1'];
		$al->addressbook->addressbookAddress->attention = $company_name;
		$al->addressbook->addressbookAddress->city = $cust_address['city'];
		$al->addressbook->addressbookAddress->country = $ns_country;
		$al->addressbook->addressbookAddress->internalId = $customer_internal_id;
		$al->addressbook->addressbookAddress->override = false;
		$al->addressbook->addressbookAddress->state = $cust_address['state'];
		$al->addressbook->addressbookAddress->zip = $cust_address['postcode'];

		//update customer if it already exists on netsuite

		$al = apply_filters('tm_netsuite_customer_data', $al);

		if (!empty($customer_internal_id)) {
			$netsuiteCustomerClient->updateCustomer($customer_data, $customer_internal_id, $al, $cust_address['state'], $cust_order_id);
		} else {
			//add customer to netsuite
			$customer_internal_id = $netsuiteCustomerClient->addCustomer($customer_data, $al, $cust_address['state'], $cust_order_id);
		}
		return $customer_internal_id;
	}
	/**
	 * Sync Order
	 *
	*/ 
	public function addNetsuiteOrder( $order_id) {

		global $TMWNI_OPTIONS;

		require_once TMWNI_DIR . 'inc/order.php';
		//instance of API client
		$this->netsuiteOrderClient = new OrderClient();


		$order = new WC_Order($order_id);
		//get user id assocaited with order
		$user_id = $order->get_user_id();

		$check_if_sent = get_post_meta($order_id, TMWNI_Settings::$ns_order_id, true);
	

		if (empty($check_if_sent)) {
			if (0 == $user_id) {
				$customer_internal_id = $this->addUpdateNetsuiteGuestCustomer($order);
				if (!empty($customer_internal_id)) {
					update_post_meta($order_id, TMWNI_Settings::$ns_guest_customer_id, $customer_internal_id);
				}
			} else {
				$customer_internal_id = $this->addUpdateNetsuiteCustomer($user_id, $order_id);
			}

			//get required order data

			$order_data = $this->getOrderData($order_id , TMWNI_Settings::$ns_rec_type_order);
			$order_data = apply_filters('tm_netsuite_order_data', $order_data);

			//set order id
			$this->netsuiteOrderClient->order_id = $order_id;


			if (0 != $customer_internal_id) {
				//add order on netsuite
				$order_netsuite_internal_id = $this->netsuiteOrderClient->addOrder($order_data, $customer_internal_id);
				// pr($order_netsuite_internal_id);die;
				if (0 !== $order_netsuite_internal_id) {
					update_post_meta($order_id, TMWNI_Settings::$ns_order_id, $order_netsuite_internal_id);
				}
			}
		} else {
			if (0 == $user_id) {
				$customer_internal_id = $this->addUpdateNetsuiteGuestCustomer($order);
			} else {
				$customer_internal_id = $this->addUpdateNetsuiteCustomer($user_id, $order_id);
			}
			

			//get required order data
			$order_data = $this->getOrderData($order_id, TMWNI_Settings::$ns_rec_type_order);
			$order_data = apply_filters('tm_netsuite_order_data', $order_data);

			$order_netsuite_internal_id = $this->netsuiteOrderClient->updateOrder($order_data, $customer_internal_id, $check_if_sent);
			
		}
		return $order_netsuite_internal_id;
	}

	/**
	 * Get Order Data
	 *
	*/ 
	public function getOrderData( $order_id, $rec_type) {
		global $TMWNI_OPTIONS;
		$data = array();
		
		//instance of woocommerce order class
		$order = new WC_Order($order_id);
		
		//set other order related data
		$data['order_id'] = $order_id;
		$data['order'] = $order;
		
		//get user id assocaited with order
		$user_id = $order->get_user_id();
		if (!$user_id) { //if customer is not a registered woocommerce user then use billing email
			$data['customer_email'] = $order->billing_email;
		} else {  //else fetch user email from woocommerce 
			$user_data = get_userdata($user_id);
			$data['customer_email'] = $user_data->data->user_email;
		}
		$data['order_status'] = $order->get_status();
		$data['order_currency'] = $order->get_currency();
		$data['total_shipping'] = $order->get_total_shipping();
		$billing_address = $order->get_Address();
		if (isset($billing_address['country']) && !empty($billing_address['country'])) {
			$ns_country = TMWNI_Settings::$netsuite_country[$billing_address['country']];
		} else {
			$ns_country = '';
		}
		$billing_address['country'] = $ns_country;
		$data['billing_address'] = $billing_address;
		//old shiiping code
		$data['shipping_address'] = $order->get_Address('shipping');
		if (isset($data['shipping_address']['country']) && !empty($data['shipping_address']['country'])) {
			$ns_shipping_country = TMWNI_Settings::$netsuite_country[$data['shipping_address']['country']];
		} else {
			$ns_shipping_country = '';
		}
		//breaking shipping address
		$data['shipping_address']['country'] = $ns_shipping_country;
		$order_payment_method_id = $order->get_payment_method();
		$data['order_payment_method'] = $order_payment_method_id;
		// pr(WC()->shipping->get_shipping_methods());die;
		$shipping_method = array_values($order->get_shipping_methods());
		$data['order_shipping_method'] = $shipping_method;
		$order_items = array_values($order->get_items());
		$data['items'] = array();
		//get items associated with orders
		$data['items'] = $this->checkOrderItems($order_items, $order_id, $rec_type);
		// pr($data['items']);exit;
		return $data;
	}
	/**
	 * Check Order Items
	 *
	*/ 
	public function checkOrderItems( $order_items, $order_id, $rec_type) {
		$items = array();
		$count = 0;
		foreach ($order_items as $key => $order_item) {
			$product = new WC_Product($order_item['product_id']);
			$product_sku = $product->get_sku(); // MANISH : CHANGE THIS TO CUSTOM FIELD
			if (isset($order_item['variation_id']) && !empty($order_item['variation_id'])) {
				$variation_obj = new WC_Product_Variation($order_item['variation_id']);
				if ($variation_obj->variation_has_sku) {
					$product_sku = $variation_obj->get_sku();
				}
				$unit_price = $variation_obj->get_price();
				$netsuite_internal_id = get_post_meta($order_item['variation_id'], TMWNI_Settings::$ns_product_id, true);

			} else {
				$unit_price = $product->get_price();
				$netsuite_internal_id = get_post_meta($order_item['product_id'], TMWNI_Settings::$ns_product_id, true);
			}
			//Search order item's internalId from Netsuite based on woocommerce product's SKU
			if (empty($netsuite_internal_id)) {
				if (TMWNI_Settings::$ns_rec_type_order == $rec_type) {
					$netsuite_internal_id = $this->netsuiteOrderClient->searchItem($product_sku, $order_item['product_id'], $order_item['variation_id']);
				}
			//if internalId is not found for any one of the order item, stop order transfer script
				if (0 == $netsuite_internal_id) {
					continue;
				}

			}
			
			if (isset($order_item['variation_id']) && !empty($order_item['variation_id'])) { 
				$location_id = get_post_meta($order_item['variation_id'], 'ns_item_location_id', true);
			} else {
				$location_id = get_post_meta($order_item['product_id'], 'ns_item_location_id', true);
			}


			$items[$key]['internalId'] = $netsuite_internal_id;
			$items[$key]['total'] = $order_item['total'];
			$items[$key]['unit_price'] = $unit_price;
			$items[$key]['qty'] = $order_item['qty'];
			$items[$key]['total_tax'] = $order_item['total_tax'];
			$items[$key]['locationId'] = $location_id;
			$items[$key]['productId'] = $order_item['product_id'];
		}
		$items = array_reverse($items);
		
		return $items;
	}

	/**
	 * Order Cron
	 *
	*/ 
	public function orderTrackingCron( $schedules) {
		$schedules['once_every_hour'] = array('interval' => 3600, 'display' => 'Once every hour');
		return $schedules;
	}

	/**
	 * Order tracking file include
	 *
	*/ 
	public function fetchOrderTrackingInfo() {
		require_once TMWNI_DIR . 'inc/orderTracking.php';
	}

	/**
	 * Delete Order
	 *
	*/ 
	public function deleteNetsuiteOrder( $post_id) {
		global $TMWNI_OPTIONS;
		if ('shop_order' == get_post_type($post_id)) {
			$nsOrderInternalId = get_post_meta($post_id, TMWNI_Settings::$ns_order_id, true);
			
			delete_post_meta( $post_id, TMWNI_Settings::$ns_order_id);

			if (!empty($nsOrderInternalId)) {
				require_once TMWNI_DIR . 'inc/order.php';
				$this->netsuiteOrderClient = new OrderClient();
				$this->netsuiteOrderClient->deleteOrder($nsOrderInternalId);
			}
		}
		return;
	}
	/**
	 * Restore Order
	 *
	*/ 
	public function restoreNetsuiteOrder( $post_id) {
		global $TMWNI_OPTIONS;
		if ('shop_order' == get_post_type($post_id) ) {
			$this->addNetsuiteOrder($post_id);
		}
		return;
	}

	

}

function TMWNI_Loader() {
	return TMWNI_Loader::getInstance();
}
