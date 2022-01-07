<?php

/**
 * This class handles all API operations related to creating inventory update CRON
 * API Ref : http://tellsaqib.github.io/NSPHP-Doc/index.html
 *
 * Author : Manish Gautam
 */
// including development toolkit provided by Netsuite
require_once TMWNI_DIR . 'inc/NS_Toolkit/src/NetSuiteService.php';
require_once TMWNI_DIR . 'inc/common.php';
foreach (glob(TMWNI_DIR . 'inc/NS_Toolkit/src/Classes/*.php') as $filename) {
	require_once $filename;
}
use NetSuite\NetSuiteService;
use Netsuite\Classes\SearchStringField;
use Netsuite\Classes\ItemSearchBasic;
use Netsuite\Classes\SearchRequest;
use Netsuite\Classes\AssemblyItem;
use Netsuite\Classes\SearchMultiSelectField;
use Netsuite\Classes\RecordRef;
use Netsuite\Classes\ItemAvailabilityFilter;
use Netsuite\Classes\RecordRefList;
use Netsuite\Classes\GetItemAvailabilityRequest;
use Netsuite\Classes\SearchCustomFieldList;
use NetSuite\Classes\SearchCustomField;
use NetSuite\Classes\SearchStringCustomField;




class ItemClient extends CommonIntegrationFunctions {

	public $netsuiteService = '';
	public $object_id;

	public function __construct() {

		if (empty($this->netsuiteService)) {
			//intialising netsuite service
			if (TMWNI_Settings::areCredentialsDefined()) {
				$this->netsuiteService = new NetSuiteService(null, array('exceptions' => true));
			}
		}
	}

	/**
	 * Get Product From NS
	 *
	*/ 
	public function getList() {

		//search preference
		$this->netsuiteService->setSearchPreferences(false, 1000, true);

		$SearchField = new SearchMultiSelectField();
		$SearchField->operator = 'anyOf';

		$SearchField->searchValue = array('internalId' => 2);

		//search on items
		$search = new ItemSearchBasic();
		$search->department = $SearchField;

		//set search request
		$request = new SearchRequest();
		$request->searchRecord = $search;
		//perofrm search request
		$searchResponse = $this->netsuiteService->search($request);
		$i = 1;
		if ($searchResponse->searchResult->status->isSuccess) {
			$file_name = __DIR__ . '/product_data_' . $i . '.json';
			touch($file_name);
			file_put_contents($file_name, json_encode($searchResponse->searchResult->recordList->record));

			for ($i = 2; $i <= $searchResponse->searchResult->totalPages; $i++) {
				$file_name = __DIR__ . '/product_data_' . $i . '.json';
				touch($file_name);

				$searchMoreRequest = new SearchMoreWithIdRequest();
				$searchMoreRequest->pageIndex = $i;
				$searchMoreRequest->searchId = $searchResponse->searchResult->searchId;
				$moreResults = $this->netsuiteService->searchMoreWithId($searchMoreRequest);

				if ($moreResults->searchResult->status->isSuccess) {
					file_put_contents($file_name, json_encode($moreResults->searchResult->recordList->record));
				}
			}
		}

		// pr($moreResults);die;
	}

	/**
	 * Search Product From NS By Sku and Update Internal Id
	 *
	*/ 
	public function searchItemUpdateInternalID( $item_sku, $product_id) {
		$response = $this->_searchItem($item_sku, $product_id);
		if ($response['status']) {
			$searchResponse = $response['search_response'];
			$item_internal_id = $searchResponse->searchResult->recordList->record[0]->internalId;
			update_post_meta($product_id, TMWNI_Settings::$ns_product_id, $item_internal_id);

			$item_location_id = $searchResponse->searchResult->recordList->record[0]->location->internalId;

			if (!empty($item_location_id)  || !is_null($item_location_id)) { 
				update_post_meta($product_id, 'ns_item_location_id', $item_location_id);
			}			
		}
	}




	public function searchItemUpdateInventory( $item_sku, $product_id) {
		$this->object_id = $product_id;
		$kit_item_sync_status = true;
		$item_sync_status  = true;
		$file_dir = wp_upload_dir();

		$log_file = $file_dir['basedir'] . '/' . TMWNI_Settings::$ns_inventory_log_file;
		if (empty($item_sku)) {
			$empty_skus = get_option('empty_skus');
			
			$empty_skus[] = $product_id;
			update_option('empty_skus', array_unique($empty_skus));
			$content = '<p><b>SKU Missing for Product ID ' . $product_id . '</b></p>';
			file_put_contents($log_file, $content . PHP_EOL, FILE_APPEND);
			$content = '<p><b>Skipped</b></p>';
			file_put_contents($log_file, $content . PHP_EOL, FILE_APPEND);
			return;

		}

		$content = '<p>Product SKU ' . $item_sku . '</p>';
		file_put_contents($log_file, $content . PHP_EOL, FILE_APPEND);
		$content = '<p><b>Action: Inventory/Price Update</b></p>';
		file_put_contents($log_file, $content . PHP_EOL, FILE_APPEND);
		
		$response = $this->_searchItem($item_sku, $product_id);

		if ($response['status']) {
			
			$searchResponse = $response['search_response'];

			//NetSuite item interanl id
			$item_internal_id = $searchResponse->searchResult->recordList->record[0]->internalId;
			update_post_meta($product_id, TMWNI_Settings::$ns_product_id, $item_internal_id);

			//netsuite item dafault location id 

			$item_location_id = '';
			if (isset($searchResponse->searchResult->recordList->record[0]->location->internalId)) {
				$item_location_id = $searchResponse->searchResult->recordList->record[0]->location->internalId;
				if (!empty($item_location_id)  || !is_null($item_location_id)) { 
					update_post_meta($product_id, 'ns_item_location_id', $item_location_id);
				}
			}

			
			$kit_item_sync_status = apply_filters('tm_ns_kit_item_status', $kit_item_sync_status, $searchResponse);
			$item_sync_status = apply_filters('tm_ns_item_status', $item_sync_status, $searchResponse);	
			$class = get_class($searchResponse->searchResult->recordList->record[0]);
			$pieces = explode('\\', $class);
			$item_type = end($pieces);

			if ('KitItem' == $item_type) {
				if (false != $kit_item_sync_status) {
					$this->_updatekitItemData($searchResponse, $product_id, $item_location_id);
				}					
			} else {
				if (false != $item_sync_status) {
					$this->_updateItemData($searchResponse, $product_id, $item_location_id);		
				}
			}
			
		} else {
			
			$not_found_skus = get_option('not_found_skus');
			$not_found_skus[] = $item_sku;
			update_option('not_found_skus', $not_found_skus);
			$content = '<p><b>Item Number/SKU not found on NetSuite</b></p>';
			file_put_contents($log_file, $content . PHP_EOL, FILE_APPEND);
		}
		

	}


	/**
	 * Earch Product From NS By Sku and Update Inventory
	 *
	*/ 
	private function _searchItem( $item_sku, $product_id) {
		//pr($item_sku); die('zzzz');
		//$item_sku = 'test store';

		$response = array();
		$response['status'] = 0;
		global $TMWNI_OPTIONS;

		//search preference
		$this->netsuiteService->setSearchPreferences(false, 20);
		//set search field
		$SearchField = new SearchStringField();
		$SearchField->operator = 'is';
		$SearchField->searchValue = $item_sku;


		//search on items
		$search = new ItemSearchBasic();

		if (!isset($TMWNI_OPTIONS['sku_mapping_field']) || empty($TMWNI_OPTIONS['sku_mapping_field']) ) {
			$search->itemId = $SearchField;
		} elseif ('customFieldList' == $TMWNI_OPTIONS['sku_mapping_field']) {
			$search->{$TMWNI_OPTIONS['sku_mapping_field']} = $this->customSearchStringField($TMWNI_OPTIONS['sku_mapping_custom_field'], $item_sku);
		} else {
			$search->{$TMWNI_OPTIONS['sku_mapping_field']} = $SearchField;
		}

		//set search request
		$search = apply_filters('tm_ns_search_item', $search, $item_sku, $product_id);
		$request = new SearchRequest();
		$request->searchRecord = $search;
		$quantity = false;

		// pr($search);
		// die('*!*');

		try {
			//perofrm search request
			$searchResponse = $this->netsuiteService->search($request);

			apply_filters('tm_ns_search_item_response', $searchResponse, $product_id);
			if (!$searchResponse->searchResult->status->isSuccess) {

				$object = 'inventory_item';
				$error_msg = "'" . ucfirst($object) . " Search' operation failed for WooCommerce " . $object . ', ID = ' . $product_id . '. ';

				$error_msg .= 'Search Keyword:' . $item_sku . '. ';

				$error_msg .= 'Error Message : ' . $response->writeResponse->status->statusDetail[0]->message;
				$this->handleLog(0, $product_id, $object, $error_msg);
			} else {

				//Check if search record found
				if (1 == $searchResponse->searchResult->totalRecords) {
					$response['status'] = 1;
					$response['search_response'] = $searchResponse;
				}
			}
		} catch (SoapFault $e) {

			$object = 'inventory_item';
			$error_msg = "SOAP API Error occured on '" . ucfirst($object) . " Search' operation failed for WooCommerce " . $object . ', ID = ' . $this->object_id . '. ';
			$error_msg .= 'Search Keyword: ' . $item_sku . '. ';
			$error_msg .= 'Error Message: ' . $e->getMessage();

			$this->handleLog(0, $product_id, $object, $error_msg);

		}
		return $response;
	}



	/**
	 * Earch Product From NS By Internal Id
	 *
	*/ 
	private function _searchItemByInternalId( $internalId, $product_id) {
		// $item_sku = 'MISCBOTTLESFLINT';

		$response = array();
		$response['status'] = 0;
		global $TMWNI_OPTIONS;

		//search preference
		$this->netsuiteService->setSearchPreferences(false, 20);
		//set search field
		$SearchField = new SearchMultiSelectField();
		$SearchField->operator = 'anyOf';
		$SearchField->searchValue = array('internalId' => $internalId);


		//search on items
		$search = new ItemSearchBasic();
		$search->internalId = $SearchField;

		//set search request
		$request = new SearchRequest();
		$request->searchRecord = $search;

		try {
			//perofrm search request

			$searchResponse = $this->netsuiteService->search($request);
			apply_filters('tm_ns_search_item_response', $searchResponse, $product_id);

			if (!$searchResponse->searchResult->status->isSuccess) {

				$object = 'inventory_item';
				$error_msg = "'" . ucfirst($object) . " Search' operation failed for WooCommerce " . $object . ', ID = ' . $product_id . '. ';

				$error_msg .= 'Search Keyword:' . $item_sku . '. ';

				$error_msg .= 'Error Message : ' . $response->writeResponse->status->statusDetail[0]->message;
				$this->handleLog(0, $product_id, $object, $error_msg);
			} else {

				//Check if search record found
				if (1 == $searchResponse->searchResult->totalRecords) {
					$response['status'] = 1;
					$response['search_response'] = $searchResponse;
				}
			}
		} catch (SoapFault $e) {

			$object = 'inventory_item';
			$error_msg = "SOAP API Error occured on '" . ucfirst($object) . " Search' operation failed for WooCommerce " . $object . ', ID = ' . $this->object_id . '. ';
			$error_msg .= 'Search Keyword: ' . $item_sku . '. ';
			$error_msg .= 'Error Message: ' . $e->getMessage();

			$this->handleLog(0, $product_id, $object, $error_msg);

		}
		return $response;
	}

	/**
	 * Select item quantity based on default location
	 *
	 * Param type $item_location_id int
	 * Param type $item_locations object of all item locations
	 */
	public function getNetSuiteProductPrices( $searchResponse) {
		global $TMWNI_OPTIONS; 
		if (isset($searchResponse->searchResult->recordList->record[0]->pricingMatrix)) {
			$product = $searchResponse->searchResult->recordList->record[0]->pricingMatrix;
			foreach ($product->pricing as $pricing) {
				$name = $pricing->priceLevel->name;
				if (isset($TMWNI_OPTIONS['price_level_name']) && !empty($TMWNI_OPTIONS['price_level_name'])) {
					$price_level_name = $TMWNI_OPTIONS['price_level_name']; 
				} else {
					$price_level_name = TMWNI_Settings::$pricing_group;
				}
				if ($price_level_name == $name) {
					return $pricing->priceList->price;
				}
			}
		}

		return array();
	}

	

	//update kit item data
	public function _updatekitItemData( $searchResponse, $product_id, $item_location_id) {
		global $TMWNI_OPTIONS; 
		if (( isset($TMWNI_OPTIONS['enablePriceSync']) && 'on' == $TMWNI_OPTIONS['enablePriceSync'] )) {
			$prices = $this->getNetSuiteProductPrices($searchResponse);	
			$this->_updateWooPrice($prices, $product_id);				
		}	

		if (( isset($TMWNI_OPTIONS['enableInventorySync']) && 'on' == $TMWNI_OPTIONS['enableInventorySync'] )) {
			$item_members = $searchResponse->searchResult->recordList->record[0]->memberList->itemMember;
			$last_quantity = 0;

			foreach ($item_members as $key => $item_member) {
				$child_item_internal_id = $item_member->item->internalId;
				$response = $this->_searchItemByInternalId($child_item_internal_id, $product_id);
				if (1 == $response['status']) {
					$searchResponse = $response['search_response'];
					$quantity = $this->getItemQuantity($searchResponse, $item_location_id, $product_id, $child_item_internal_id);

					$quantity = apply_filters('tm_ns_kit_item_quantity', $quantity, $searchResponse, $item_location_id, $product_id, $child_item_internal_id);
					
					$last_quantity += $quantity;
				}

			}
			if (!empty($last_quantity) || 0 != $quantity) {
				$this->updateWooQuantity($product_id, $last_quantity);
			}	

		}

	}

	//update  item data
	public function _updateItemData( $searchResponse, $product_id, $item_location_id) {
		global $TMWNI_OPTIONS; 
		if (( isset($TMWNI_OPTIONS['enablePriceSync']) && 'on' == $TMWNI_OPTIONS['enablePriceSync'] )) {
			$prices = $this->getNetSuiteProductPrices($searchResponse);	
			$this->_updateWooPrice($prices, $product_id);				
		}	

		if (( isset($TMWNI_OPTIONS['enableInventorySync']) && 'on' == $TMWNI_OPTIONS['enableInventorySync'] )) {
			
			$item_internal_id = $searchResponse->searchResult->recordList->record[0]->internalId;
			
			$quantity = $this->getItemQuantity($searchResponse, $item_location_id, $product_id, $item_internal_id);
			
			$quantity = apply_filters('tm_ns_item_quantity', $quantity, $searchResponse, $item_location_id, $product_id, $item_internal_id);
			if (!empty($quantity) || 0 != $quantity) {
				$this->updateWooQuantity($product_id, $quantity);
			}
		}
	}

	//update woo price
	public function _updateWooPrice( $prices, $product_id) {
		
		if (!empty($prices) && isset($prices[0]->value)) {
			$main_price = $prices[0]->value;
			update_post_meta($product_id, '_regular_price', $main_price);
			update_post_meta($product_id, '_price', $main_price);

			$content = '<p><b>Action: Price updated</b></p>';
			if (( isset($TMWNI_OPTIONS['enableInventorySync']) && 'on' == $TMWNI_OPTIONS['enableInventorySync'] )) {
				$new_count = 0;
			} else {
				$old_count = get_option('updated_products_count');
				$new_count = $old_count + 1;
			}
			$this->updateLogFileContent($content, $new_count);
		}

	}


	private function getItemQuantity( $searchResponse, $item_location_id, $product_id, $item_internal_id) {
		global $TMWNI_OPTIONS;	
		if (!empty($searchResponse->searchResult->recordList->record[0]->locationsList)) {
			$item_locations = $searchResponse->searchResult->recordList->record[0]->locationsList->locations;
		}	
		if (isset($item_locations) && !empty($item_locations)) {
			$quantity = $this->_getItemQuantityfromLocations($item_locations, $item_location_id);

		} else {
			$item_availabitliy = $this->tm_item_availabitlity_search_on_netsuite($product_id, $item_internal_id);
			$quantity = $this->_getItemQuantityfromLocations($item_availabitliy, $item_location_id);
		}

		return $quantity; 
		
	}


	private function _getItemQuantityfromLocations( $item_locations, $item_location_id) {
		global $TMWNI_OPTIONS;

		if (isset($TMWNI_OPTIONS['inventorySyncField']) && !empty($TMWNI_OPTIONS['inventorySyncField'])) {
			$quantityField = $TMWNI_OPTIONS['inventorySyncField'];
		} else {
			$quantityField = 'quantityAvailable';
		}


		if (isset($TMWNI_OPTIONS['inventoryDefaultLocation']) && ( 'on' == $TMWNI_OPTIONS['inventoryDefaultLocation'] || 2 == $TMWNI_OPTIONS['inventoryDefaultLocation'] )) {
			if (isset($item_location_id) && !empty($item_location_id)) {
				foreach ($item_locations as $item_location) {
					if ($item_location_id == $item_location->locationId->internalId && !is_null($item_location->$quantityField)) {
						$quantity = (int) $item_location->$quantityField;
					}
				}
			} else {
				foreach ($item_locations as $item_location) {
					if (!is_null($item_location->$quantityField)) {
						$quantity += (int) $item_location->$quantityField;
					}
				}

			}
		} elseif (!isset($TMWNI_OPTIONS['inventoryDefaultLocation']) || 1 == $TMWNI_OPTIONS['inventoryDefaultLocation'] ) {
			foreach ($item_locations as $item_location) {
				if (!is_null($item_location->$quantityField)) {
					$quantity += (int) $item_location->$quantityField;
				}
			}
		} elseif (isset($TMWNI_OPTIONS['inventoryDefaultLocation']) || 3 == $TMWNI_OPTIONS['inventoryDefaultLocation'] ) {
			foreach ($item_locations as $item_location) {
				if (!is_null($item_location->$quantityField)) {
					if (in_array($item_location->locationId->internalId, $TMWNI_OPTIONS['netstuite_locations'] )) {
						$quantity += (int) $item_location->$quantityField;
					}
				}
			}
		}

		return !empty($quantity) ? $quantity : 0; 
	}


	private function updateWooQuantity( $product_id, $quantity) {
		global $TMWNI_OPTIONS;
		if (false !== $quantity) {
			update_post_meta($product_id, '_stock', $quantity);
			if (isset($TMWNI_OPTIONS['overrideManageStock']) && 'on' == $TMWNI_OPTIONS['overrideManageStock'] ) {
				$manage_stock = update_post_meta($product_id, '_manage_stock', 'yes');
			} 
			 // else {
			$this->updateStock($product_id, $quantity);
			// }

			if (!empty($quantity) && 0!= $quantity) {
			$old_count = get_option('updated_products_count');
			$new_count = $old_count + 1;
			$content = '<p><b>Action: Inventory updated</b></p>';
			$this->updateLogFileContent($content, $new_count);
			}
			
		}
	}


	private function updateStock( $product_id, $quantity) {

		if ('onbackorder' != get_post_meta($product_id, '_stock_status', true)) {
			if ($quantity > 0) {
				update_post_meta($product_id, '_stock_status', 'instock');
			} else {
				update_post_meta($product_id, '_stock_status', 'outofstock');
			}
		}

	}

	public function tm_item_availabitlity_search_on_netsuite( $product_id, $item_internal_id) {
		global $TMWNI_OPTIONS;
		//search preference
		$this->netsuiteService->setSearchPreferences(false, 20);



		$ItemRecordRef = new RecordRef();
		$ItemRecordRef->internalId = $item_internal_id;
		//$ItemRecordRef->type = 'lotNumberedAssemblyItem';

		$filter = new ItemAvailabilityFilter();
		$filter->item = new RecordRefList();
		$filter->item->recordRef =  array($ItemRecordRef);



		$search = new GetItemAvailabilityRequest();
		$search->itemAvailabilityFilter = $filter;

		try {
			//perofrm search request
			$getResponse = $this->netsuiteService->getItemAvailability($search);
			apply_filters('tm_ns_item_availability_response', $getResponse, $product_id, $item_internal_id);
			if (1 == $getResponse->getItemAvailabilityResult->status->isSuccess) {
				if (isset($getResponse->getItemAvailabilityResult->itemAvailabilityList->itemAvailability)) {
					$item_locations_inventory = $getResponse->getItemAvailabilityResult->itemAvailabilityList->itemAvailability;
					return $item_locations_inventory; 
				}
			}

		} catch (SoapFault $e) {
			$object = 'item_locations';
			$error_msg = "SOAP API Error occured on '" . ucfirst($object) . " Search' operation failed for WooCommerce " . $object . ', ID = ' . $this->object_id . '. ';
			$error_msg .= 'Search Keyword: ' . $internal_id . '. ';
			$error_msg .= 'Error Message: ' . $e->getMessage();

			$this->handleLog(0, $this->object_id, $object, $error_msg);

			return 0;

		}
		
	}


	public function updateLogFileContent( $content, $new_count) {
		$file_dir = wp_upload_dir();
		$log_file = $file_dir['basedir'] . '/' . TMWNI_Settings::$ns_inventory_log_file;
		file_put_contents($log_file, $content . PHP_EOL, FILE_APPEND);
		if (0 != $new_count) {
			update_option('updated_products_count', $new_count);
		}
	}


	/**
	 * Creating custom field list array.
	 */
	public function customSearchFieldList( $custfield) {
		$customFieldList = new SearchCustomFieldList();
		$customFieldList->customField = [$custfield];
		return $customFieldList;
	}


	/**
	 * Creating custom string field instance.
	 */
	public function customSearchField( $scriptId, $value) {
		$custfield = new SearchCustomField();
		$custfield->internalId = $scriptId;
		$custfield->value = $value;
		return $this->customSearchFieldList($custfield);
	}

	/**
	 * Creating custom string field instance.
	 */
	public function customSearchStringField( $scriptId, $value) {
		
		$custfield = new SearchStringCustomField();
		$custfield->scriptId = $scriptId;
		$custfield->searchValue = $value;
		$custfield->operator = 'is';
		return $this->customSearchFieldList($custfield);
	}


}
