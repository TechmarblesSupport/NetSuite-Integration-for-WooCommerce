<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly


if ( ! class_exists( 'TM_NetSuite_Checkout_Process_Schedular' ) ) {
	class TM_NetSuite_Checkout_Process_Schedular {

		/**
		 * Schedule a fraud check
		 *
		 * Param $order_id
		 *
		 * Since  1.0.0
		 * Access public
		 */
		public function schedule_checkout_process( $order_id, $checknow = false ) {
			// Try to get the Anti Fraud score
			// $processed = get_post_meta( $order_id, 'wc_checkout_processed', true );

			// Check if the order is already checked
			// if ( '' != $processed ) {
			// 	return;
			// }

			// Get the order
			$order = wc_get_order( $order_id );
			update_post_meta( $order_id, '_tm_netsuite_process_waiting', true );
			if ( !$checknow ) {
				 wp_schedule_single_event( time(), 'tm_ns_process_order_queue', array( 'order_id' => $order_id ) );
											// $this->do_check( $order_id );
			} 
		}

		/**
		 * Returns a flag indicating if a fraud check has been queued but not yet completed
		 *
		 * Param $order_id
		 *
		 * Since  1.0.2
		 * Access public
		 */
		public static function is_checkout_process_queued( $order_id ) {

			$waiting = get_post_meta( $order_id, '_tm_netsuite_process_waiting', true );
			return ( ! empty( $waiting ) );

		}

		/**
		 * Returns a flag indicating if a fraud check has been completed
		 *
		 * Param $order_id
		 *
		 * Since  1.0.2
		 * Access public
		 */
		public static function is_checkout_process_complete( $order_id ) {

			$processed = get_post_meta( $order_id, 'tm_netsuite_order_processed', true );
			return ( ! empty( $processed ) );

		}
	}
}
