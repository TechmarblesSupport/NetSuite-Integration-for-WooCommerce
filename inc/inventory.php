<?php
class NS_Inventory {

	private $logger;
	/**
	 * Construct function
	 */
	public function __construct() {
		global $TMWNI_OPTIONS;

		if (!empty($_GET['ns_manual_update_inventory']) && 1 == $_GET['ns_manual_update_inventory']) {
			if (( isset($TMWNI_OPTIONS['enableInventorySync']) && 'on' == $TMWNI_OPTIONS['enableInventorySync'] ) || ( isset($TMWNI_OPTIONS['enablePriceSync']) && 'on' == $TMWNI_OPTIONS['enablePriceSync'] )) {
				if (TMWNI_Settings::areCredentialsDefined()) {
					$this->updateWooInventory();
					$url = isset($_SERVER['HTTP_REFERER']) ? esc_url_raw($_SERVER['HTTP_REFERER']) : '';
					require_once ABSPATH . '/wp-includes/pluggable.php';
					wp_safe_redirect($url);
					exit();
				} else {
					die('Please setup API credentials first');
				}
			} else {
				die('Please enable inventory sync first');
			}
		}

		if (!empty($_GET['ns_manual_search_product']) && 1 == $_GET['ns_manual_search_product']) {
			if (TMWNI_Settings::areCredentialsDefined()) {
				$this->assignInternalID();
				$url = isset($_SERVER['HTTP_REFERER']) ? esc_url_raw($_SERVER['HTTP_REFERER']) : '';
				require_once ABSPATH . '/wp-includes/pluggable.php';
				wp_safe_redirect($url);
				exit();
			} else {
				die('Please setup API credentials first');
			}
		}

		if (TMWNI_Settings::areCredentialsDefined()) {
			if (( isset($TMWNI_OPTIONS['enableInventorySync']) && 'on' == $TMWNI_OPTIONS['enableInventorySync'] ) || ( isset($TMWNI_OPTIONS['enablePriceSync']) && 'on' == $TMWNI_OPTIONS['enablePriceSync'] )) {
					
				add_action( 'init', array( $this, 'register_inventory_cron'));
				add_action('tm_ns_process_inventories', array($this, 'updateWooInventory'));
			}
		}
			add_filter('cron_schedules', array($this,'custom_cron_schedules'));
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
		set_time_limit(0);
		wp_raise_memory_limit('-1');
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
				$netsuiteClient->searchItemUpdateInventory($woo_product_sku, $product_id);
				usleep(100000);
			}
			sleep(2);
		}
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

		$sku_lot = []; 

		foreach ($products as $product) {
			$sku = get_post_meta($product->ID, '_sku', true );
			if (!empty($sku)) {
				$sku_lot[$product->ID] = $sku; 
			}
		}

		return $sku_lot;
	}

}

	new NS_Inventory();
