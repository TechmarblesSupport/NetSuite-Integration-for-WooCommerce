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




class OrdertrackingClient extends CommonIntegrationFunctions {


	public $netsuiteService;
	public $object_id;


	public function __construct() {
		//set netsuite API client object
		$this->netsuiteService = new NetSuiteService();
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
		);

		$orders = wc_get_orders( $args );

		foreach ($orders as $key => $value) {

			$order_id = $value->get_id();
			$netSuiteSOInternalID = get_post_meta($order_id, 'ns_order_internal_id', true);
			if (!empty($netSuiteSOInternalID)) {

				$request = new GetRequest();
				$request->baseRef = new RecordRef();
				$request->baseRef->internalId = $netSuiteSOInternalID; //<< REPLACE THIS WITH YOUR INTERNAL ID
				$request->baseRef->type = 'salesOrder';

				try {
					$getResponse = $this->netsuiteService->get($request);

					if (isset($getResponse->readResponse->status->isSuccess) && 1 == $getResponse->readResponse->status->isSuccess ) {
						if (isset($getResponse->readResponse->record->linkedTrackingNumbers) && !empty($getResponse->readResponse->record->linkedTrackingNumbers)) {

							$trackingNo = $getResponse->readResponse->record->linkedTrackingNumbers;

							update_post_meta($order_id, 'ywot_tracking_code', $trackingNo);
							update_post_meta($order_id, 'ywot_picked_up', 'on');

							if (empty(get_post_meta($order_id, 'trackingno_email_sent', true))) {
								if (isset($TMWNI_OPTIONS['ns_order_tracking_email']) && !empty($TMWNI_OPTIONS['ns_order_tracking_email'])) {
									$wc_emails = WC()->mailer()->get_emails();
									$wc_emails['WC_Ups_Order_Tracking_No']->trigger($order_id);
									update_post_meta($order_id, 'trackingno_email_sent', 'sent');
								}
							}

						}

						if (isset($getResponse->readResponse->record->shipMethod) && !empty($getResponse->readResponse->record->shipMethod)) {

							$ShippingCarrier = $getResponse->readResponse->record->shipMethod->name;

							update_post_meta($order_id, 'ywot_carrier_name', $ShippingCarrier);
						}

						if (isset($getResponse->readResponse->record->shipDate) && !empty($getResponse->readResponse->record->shipDate)) {

							$ShipDate = gmdate('Y-m-d', strtotime($getResponse->readResponse->record->shipDate));

							update_post_meta($order_id, 'ywot_pick_up_date', $ShipDate);
							//update_post_meta($order_id,'ywot_picked_up','on');
						}

						if (isset($getResponse->readResponse->record->orderStatus) && '_fullyBilled' ==  $getResponse->readResponse->record->orderStatus) {
							if (isset($TMWNI_OPTIONS['ns_order_auto_complete']) && !empty($TMWNI_OPTIONS['ns_order_auto_complete'])) {
								$order = new WC_Order($order_id);
								$order->update_status('completed');
							}
						}
						
					}

				} catch (SoapFault $e) {
					return 0;
				}
			}
		}
	}

}

$this->netsuiteOrderTrackingClient = new OrdertrackingClient();

$this->netsuiteOrderTrackingClient->getProcessingOrders();


