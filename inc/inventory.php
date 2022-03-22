<?php

class NS_Inventory {

	private $logger;
		public $Manual_update_inventory;

	/**
	 * Construct function
	 */
	public function __construct() {
		global $TMWNI_OPTIONS;
		

		if (TMWNI_Settings::areCredentialsDefined()) {
			if (( isset($TMWNI_OPTIONS['enableInventorySync']) && 'on' == $TMWNI_OPTIONS['enableInventorySync'] ) || ( isset($TMWNI_OPTIONS['enablePriceSync']) && 'on' == $TMWNI_OPTIONS['enablePriceSync'] )) {
				add_action('wp_ajax_update_woo_inventory', array($this,'tmNsUpdateWooInventory'));
				add_action('wp_ajax_fetch_inventory_progress', array($this,'fetchInventoryUpdateStatus'));
				// add_action('wp_ajax_fetch_inventory_progress', array($this,'fetchInventoryUpdateStatus'));
				require_once( 'background-process/class-manual-update-inventory.php' );
				
				$this->Manual_update_inventory = new Manually_Update_Inventory();
					
				add_action( 'init', array( $this, 'register_inventory_cron'));
				add_action('tm_ns_process_inventories', array($this, 'updateWooInventory'));
			}
		}
		add_filter('cron_schedules', array($this,'custom_cron_schedules'));
		//add_filter('init', array($this,'test'));
	}


	// public function test(){
	// 	if (!empty($_GET['tester']) && 1 == $_GET['tester']) {
	// 		require_once(TMWNI_DIR . 'inc/item.php');
	// 	$netsuiteClient = new ItemClient();
	// 	$netsuiteClient->searchItemUpdateInventory('94590302',17260);

	// 	}

	// } 


	public function tmNsUpdateWooInventory() {
		global $TMWNI_OPTIONS;
		if (( isset($TMWNI_OPTIONS['enableInventorySync']) && 'on' == $TMWNI_OPTIONS['enableInventorySync'] ) || ( isset($TMWNI_OPTIONS['enablePriceSync']) && 'on' == $TMWNI_OPTIONS['enablePriceSync'] )) {
			if (TMWNI_Settings::areCredentialsDefined()) {
				$this->manualUpdateWooInventory();
			} else {
				die('Please setup API credentials first');
			}
		} else {
			die('Please enable inventory sync first');
		}
	}

	public function fetchInventoryUpdateStatus() {
		if (isset($_POST['nonce']) && !empty($_POST['nonce']) && !wp_verify_nonce(sanitize_text_field($_POST['nonce']), 'security_nonce') ) {
			die('Nonce Error'); 
		}
		if (!empty($_POST['action'])) {
			// sleep(1); 
			$processed_count = get_option('processed_products');
			$not_found_skus = get_option('not_found_skus');
			$empty_skus = get_option('empty_skus');

			$updated_count = get_option('updated_products_count');
			$file_dir = wp_upload_dir();

			$log_file = $file_dir['basedir'] . '/' . TMWNI_Settings::$ns_inventory_log_file;
			
			
			$logs = file_get_contents($log_file);
			wp_send_json(array('success' => true, 'processed_count' => $processed_count, 'skus' => $not_found_skus, 'updated_count' => $updated_count, 'logs' => $logs, 'empty_skus' => $empty_skus));
			die();
		}
	}

	/**
	 * Custom cron function
	 */
	public function custom_cron_schedules( $schedules) {
		if (!isset($schedules['10min'])) {
			$schedules['10min'] = array(
				'interval' => 600,
				'display' => __('Once every 10 minutes'));
		}
			
		return $schedules;
	}

	/**
	 * Register inventory cron
	 */
	public function register_inventory_cron() {
		global $TMWNI_OPTIONS;
		$inventorySyncFrequency = $TMWNI_OPTIONS['inventorySyncFrequency'];
		if ( !wp_next_scheduled( 'tm_ns_process_inventories' ) ) {
			wp_schedule_event( time(), $inventorySyncFrequency, 'tm_ns_process_inventories' );
		}
	} 

	/**
	 * Assign NetSuite Internal id
	 */
	public function assignInternalID() {
		set_time_limit(0);
		wp_raise_memory_limit(-1);

		 // echo date("Y-m-d H:i:s");die;
		global $wpdb;
		$product_count = $wpdb->get_row("SELECT COUNT(*) as total_products FROM {$wpdb->posts} WHERE (post_type='product' OR post_type='product_variation') AND post_status='publish'");



		$total_count = $product_count->total_products;
			
		$limit = TMWNI_Settings::$inventory_sku_lot_limit;

		$total_loop_pages = ceil($total_count/$limit);

		require_once(TMWNI_DIR . 'inc/item.php');
		$netsuiteClient = new ItemClient();
			
		for ($i=0; $i<=$total_loop_pages; $i++) { 
			$sku_lot = $this->getProductSKULot($i);

			foreach ($sku_lot as $product_id => $woo_product_sku) {
			   
				$netsuiteClient->searchItemUpdateInternalID($woo_product_sku, $product_id);
				usleep(500000);
			}
			  
			sleep(2);
		}
	}

	/**
	 * Update Inventory
	 */
	public function updateWooInventory() {


		//save inventory update time in option table
		$updateInventoryDateTime = gmdate('Y-m-d H:i:s a');
		update_option('ns_woo_inventory_update', $updateInventoryDateTime);


		set_time_limit(0);
		wp_raise_memory_limit('-1');
		// echo date("Y-m-d H:i:s");die;
		global $wpdb;
		$product_count = $wpdb->get_row("SELECT COUNT(*) as total_products FROM {$wpdb->posts} WHERE (post_type='product' OR post_type='product_variation') AND post_status='publish'");



		$product_count = apply_filters('tm_netsuite_get_woo_product', $product_count);



		$total_count = $product_count->total_products;
			
		$limit = TMWNI_Settings::$inventory_sku_lot_limit;

		$total_loop_pages = ceil($total_count/$limit);
		require_once(TMWNI_DIR . 'inc/item.php');
		$netsuiteClient = new ItemClient();
		for ($i=0; $i<=$total_loop_pages; $i++) { 
			$sku_lot = $this->getProductSKULot($i);

			//$sku_lot  = apply_filters('tm_woo_products_skus',$i);
			$sku_lot = apply_filters('tm_woo_products_skus', $sku_lot);

			foreach ($sku_lot as $product_id => $woo_product_sku) {
				$netsuiteClient->searchItemUpdateInventory($woo_product_sku, $product_id);
				usleep(100000);
			}
			sleep(2);
		}

	}

	/**
	 * Manually Update Inventory
	 */

	public function manualUpdateWooInventory() {		
		set_time_limit(0);
		wp_raise_memory_limit('-1');
		// echo date("Y-m-d H:i:s");die;
		global $wpdb;


		

		$product_count = $wpdb->get_row("SELECT COUNT(*) as total_products FROM {$wpdb->posts} WHERE (post_type='product' OR post_type='product_variation') AND post_status='publish'");


		$total_count = $product_count->total_products;
		
		add_option('total_product_count', $total_count);
		update_option('processed_products', 0);
		update_option('updated_products_count', 0);
		update_option('not_found_skus', array());
		update_option('empty_skus', array());
		$file_dir = wp_upload_dir();
		$log_file = $file_dir['basedir'] . '/' . TMWNI_Settings::$ns_inventory_log_file;

		file_put_contents($log_file, '');
		
		$limit = TMWNI_Settings::$inventory_sku_lot_limit;

		$total_loop_pages = ceil($total_count/$limit);
		
		require_once(TMWNI_DIR . 'inc/item.php');
		$netsuiteClient = new ItemClient();
		$total_skus = 0;
		for ($i=0; $i<=$total_loop_pages; $i++) { 
			$sku_lot = $this->getProductSKULot($i);
			
			$total_skus = $sku_lot;
			foreach ($sku_lot as $product_id => $woo_product_sku) {
				$this->Manual_update_inventory->push_to_queue(array('product_sku' => $woo_product_sku, 'product_id' => $product_id));
				// $netsuiteClient->searchItemUpdateInventory($woo_product_sku, $product_id);
				// usleep(100000);
			}
			
		}

		
		$this->Manual_update_inventory->save()->dispatch();
		wp_send_json(array('success' => true, 'total_count' => $total_count));
		die();
	}


	/**
	 * Get Product Sku
	 */
	private function getProductSKULot( $page = 0) {
		global $wpdb;

		$limit = TMWNI_Settings::$inventory_sku_lot_limit;

		if (0 == $page) {
			$offset = 0;
		} else {
			$offset = $limit * $page;
		}



		$products = $wpdb->get_results($wpdb->prepare("SELECT ID FROM {$wpdb->posts}  WHERE (post_type='product' OR post_type='product_variation') AND post_status='publish' LIMIT %d,%d", $offset, $limit));

		$products = apply_filters('tm_netsuite_get_all_woo_product', $products, $offset, $limit);

		$sku_lot = []; 

		foreach ($products as $product) {
			$sku = get_post_meta($product->ID, '_sku', true );
			// if (!empty($sku)) {

			$sku_lot[$product->ID] = $sku; 
			// }
		}
		
		return $sku_lot;
	}

}

	new NS_Inventory();
