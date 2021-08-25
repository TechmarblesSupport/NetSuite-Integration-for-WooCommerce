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




class ItemClient extends CommonIntegrationFunctions {

	public $netsuiteService = '';
	public $object_id;

	public function __construct() {

		if (empty($this->netsuiteService)) {
			//intialising netsuite service
			$this->netsuiteService = new NetSuiteService(null, array('exceptions' => true));
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

			
			if (!empty($searchResponse->searchResult->recordList->record[0]->memberList->itemMember)) {
				$this->_updatekitItemData($searchResponse, $product_id, $item_location_id);				
			} else {
				$this->_updateItemData($searchResponse, $product_id, $item_location_id);
			}
		}

	}


	/**
	 * Earch Product From NS By Sku and Update Inventory
	 *
	*/ 
	private function _searchItem( $item_sku, $product_id) {
		// $item_sku = 'WEROS14BOTLBL';
		// pr($item_sku);

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
		$search->itemId = $SearchField;

		//set search request
		$request = new SearchRequest();
		$request->searchRecord = $search;
		$quantity = false;

		try {
			//perofrm search request

			$searchResponse = $this->netsuiteService->search($request);
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
					$last_quantity += $quantity;
				}

			}
			if (!empty($last_quantity)) {
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

			if (!empty($quantity)) {				
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
		$quantity = false;
		global $TMWNI_OPTIONS;
		if (isset($TMWNI_OPTIONS['inventoryDefaultLocation']) && 'on' == $TMWNI_OPTIONS['inventoryDefaultLocation'] ) {
			if (isset($item_location_id) && !empty($item_location_id)) {
				foreach ($item_locations as $item_location) {
					if ($item_location_id == $item_location->locationId->internalId && !is_null($item_location->quantityAvailable)) {
					$quantity = (int) $item_location->quantityAvailable;
					}
				}
			}
		} else {
			foreach ($item_locations as $item_location) {
				if (!is_null($item_location->quantityAvailable)) {
					$quantity += (int) $item_location->quantityAvailable;
				}
			}
		}
		return $quantity; 


	}


	private function updateWooQuantity( $product_id, $quantity) {
		if (false !== $quantity) {
			update_post_meta($product_id, '_stock', $quantity);
			update_post_meta($product_id, '_manage_stock', 'yes');
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


}
