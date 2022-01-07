<?php

/**
 * This class handles all API operations related to fetching tracking information for orders 
 * on Netsuite
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
use NetSuite\Classes\GetRequest;
use NetSuite\Classes\RecordRef;
use NetSuite\Classes\SearchStringField;
use NetSuite\Classes\SearchMultiSelectField;
use NetSuite\Classes\SEARCHENUMMULTISELECTFIELDOPERATOR;
use NetSuite\Classes\TransactionSearchBasic;
use NetSuite\Classes\SearchRequest;




class OrdertrackingClient extends CommonIntegrationFunctions {


	public $netsuiteService;
	public $object_id;


	public function __construct() {
		//set netsuite API client object
		if (TMWNI_Settings::areCredentialsDefined()) {
			$this->netsuiteService = new NetSuiteService();
		}
	}


	/**
	 * Search orders within woocommerce 
	 * with status processing and fetches there tracking info. from NetSuite 
	 */

	public function getProcessingOrders() {
		global $TMWNI_OPTIONS;

		$args = array(
			'status' => 'processing',
			'limit' => 1000,
			'meta_key'     => 'ns_order_internal_id', // The postmeta key field
			'meta_compare' => 'EXISTS',

		);

		$orders = wc_get_orders( $args );

		$internalIds_array = array();
		foreach ($orders as $key => $order) {
			$order_id = $order->get_id();
			$netSuiteSOInternalID = get_post_meta($order_id, 'ns_order_internal_id', true);
			$order_status = $order->get_status();
			$tracking_status = get_post_meta($order_id, 'ywot_tracking_code', true);
			if (empty($tracking_status)  ||  'processing' == $order_status) {
				$searchValue = new RecordRef();
				$searchValue->internalId = $netSuiteSOInternalID;	
				$internalIds_array[] = 	$searchValue;	
			}	


		}

		if (!empty($internalIds_array)) {
			$ns_service = new NetSuiteService();

			$selectedField = new SearchMultiSelectField();
			$selectedField->searchValue = $internalIds_array;
			$selectedField->type = 'salesOrder';
			$selectedField->operator = SEARCHENUMMULTISELECTFIELDOPERATOR::ANYOF;


			$tranSearch = new TransactionSearchBasic();
			$tranSearch->internalId = $selectedField;


			$request = new SearchRequest();
			$request->searchRecord = $tranSearch;


			try {
				$searchResponse = $ns_service->search($request);
				if (isset($searchResponse->searchResult->status->isSuccess) && 1 == $searchResponse->searchResult->status->isSuccess) {
					$records = $searchResponse->searchResult->recordList->record;
					foreach ($records as $key => $record) {
						$this->updateFulFIllment($record);
					}

				}

			} catch (SoapFault $e) {
				return 0;
			}
			
		}

	}



	public static function updateFulFIllment( $record) {
		global $TMWNI_OPTIONS; 

		$order_internal_id = $record->internalId;
		$args = array(
			'meta_key'     => 'ns_order_internal_id', // The postmeta key field
			'meta_value' => $order_internal_id,

		);

		$order = wc_get_orders($args);
		$order_id = $order[0]->get_id();


	

		if (isset($record->linkedTrackingNumbers) && !empty($record->linkedTrackingNumbers)) {
			$trackingNo = $record->linkedTrackingNumbers;
			if (isset($TMWNI_OPTIONS['ns_order_tracking_number']) && !empty($TMWNI_OPTIONS['ns_order_tracking_number'])) {
				update_post_meta($order_id, $TMWNI_OPTIONS['ns_order_tracking_number'], $trackingNo);
			}	
			update_post_meta($order_id, 'ywot_tracking_code', $trackingNo);
			update_post_meta($order_id, 'ywot_picked_up', 'on');

			if (empty(get_post_meta($order_id, 'trackingno_email_sent', true))) {
				if (isset($TMWNI_OPTIONS['ns_order_tracking_email']) && !empty($TMWNI_OPTIONS['ns_order_tracking_email'])) {
					$wc_emails = WC()->mailer()->get_emails();
					$wc_emails['WC_NetSuite_Order_Tracking_No']->trigger($order_id);
					update_post_meta($order_id, 'trackingno_email_sent', 'sent');
					//die;
									// die('helloooo');
				}
			}

		}


		if (isset($record->shipMethod) && !empty($record->shipMethod)) {

			$ShippingCarrier = $record->shipMethod->name;
			if (isset($TMWNI_OPTIONS['ns_order_shipping_courier']) && !empty($TMWNI_OPTIONS['ns_order_shipping_courier'])) {

				update_post_meta($order_id, $TMWNI_OPTIONS['ns_order_shipping_courier'], $ShippingCarrier);
			} else {
				update_post_meta($order_id, 'ywot_carrier_name', $ShippingCarrier);
			}	


		}


		if (isset($record->shipDate) && !empty($record->shipDate)) {

			$ShipDate = gmdate('Y-m-d', strtotime($record->shipDate));

			if (isset($TMWNI_OPTIONS['ns_order_pickup_date']) && !empty($TMWNI_OPTIONS['ns_order_pickup_date'])) {
				update_post_meta($order_id, $TMWNI_OPTIONS['ns_order_pickup_date'], $ShipDate);

			} else {
				update_post_meta($order_id, 'ywot_pick_up_date', $ShipDate);

			}	

		}



		if (!empty($record->orderStatus) || !empty($record->status)) {
			if ('_fullyBilled' ==  $record->orderStatus || 'Billed' ==  $record->orderStatus || '_fullyBilled' ==  $record->status || 'Billed' ==  $record->status) {
				if (isset($TMWNI_OPTIONS['ns_order_auto_complete']) && !empty($TMWNI_OPTIONS['ns_order_auto_complete'])) {
					$order = new WC_Order($order_id);
					$order->update_status('completed');
				}


			}
		}

	}




}

$this->netsuiteOrderTrackingClient = new OrdertrackingClient();

$this->netsuiteOrderTrackingClient->getProcessingOrders();


