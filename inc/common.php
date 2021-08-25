<?php
class CommonIntegrationFunctions {
	/**
	 * Handling API response for ADD operations
	 */
	public function handleAPIAddResponse( $response, $object) {
		if (!$response->writeResponse->status->isSuccess) {
			$error_msg = "'" . ucfirst($object) . " Add' operation failed for WooCommerce " . $object . ', ID = ' . $this->object_id . '. ';
			$error_msg .= 'Error Message : ' . $response->writeResponse->status->statusDetail[0]->message;

			$this->handleLog(0, $this->object_id, $object, $error_msg);
			return 0;
		} else {
			$this->handleLog(1, $this->object_id, $object);
			return $response->writeResponse->baseRef->internalId;
		}
	}
	 //Handling API "update operation" response 
	public function handleAPIUpdateResponse( $response, $object) {
		if (!$response->writeResponse->status->isSuccess) {
			$error_msg = "'" . ucfirst($object) . " Update' operation failed for WooCommerce " . $object . ', ID = ' . $this->object_id . '. ';
			$error_msg .= 'Error Message : ' . $response->writeResponse->status->statusDetail[0]->message;

			$this->handleLog(0, $this->object_id, $object, $error_msg);

			return 0;
		} else {
			$this->handleLog(1, $this->object_id, $object);
			return $response->writeResponse->baseRef->internalId;
		}
	}

	/**
	 * Handling API response for search operations
	 */
	public function handleAPISearchResponse( $response, $object, $search_keyword = '') {
		if (!$response->searchResult->status->isSuccess) {
			$error_msg = "'" . ucfirst($object) . " Search' operation failed for WooCommerce " . $object . ', ID = ' . $this->object_id . '. ';
			if (!empty($search_keyword)) {
				$error_msg .= 'Search Keyword:' . $search_keyword;
			}

			$error_msg .= 'Error Message : ' . $response->writeResponse->status->statusDetail[0]->message;

			$this->handleLog(0, $this->object_id, $object, $error_msg);
		} else {
			if (0 == $response->searchResult->totalRecords) {
				$error_msg = "'" . ucfirst($object) . " Search' operation returned no results for WooCommerce " . $object . ', ID = ' . $this->object_id . '. ';
				if (!empty($search_keyword)) {
					$error_msg .= 'Search Keyword:' . $search_keyword;
				}

				$this->handleLog(1, $this->object_id, $object, $error_msg);
				return 0;
			} else {
				$this->handleLog(1, $this->object_id, $object);
				return $response->searchResult->recordList->record[0]->internalId;
			}
		}
	}

	public function handleLog( $status, $object_id, $object, $error = '') {
		$this->writeLogtoDB($status, $object_id, $object, $error);
		if (0 == $status) {
			$this->logNetsuiteApiError($error);
		}
	}

	public function writeLogtoDB( $status, $object_id, $object, $error = '') {
		global $wpdb;
		$query_array = ['status' => $status, 'woo_object_id' => $object_id, 'operation' => $object];
		$query_array['notes'] = $error;
		$wpdb->insert($wpdb->prefix . 'tm_woo_netsuite_logs', $query_array);
		return false;
	}
	/**
	 * API Error logging function
	 */
	public function logNetsuiteApiError( $error) {
		$error_log_file = wc_get_log_file_path( 'netsuite_errors.log' );
		if (!file_exists($error_log_file)) {
			fopen($error_log_file, 'w');
			chmod($error_log_file, 0777); 
		}
		
		if (!is_writable($error_log_file)) {
			chmod($error_log_file, 0777);
		}
		$error = "\n" . gmdate('Y-m-d H:i:s') . '->' . $error . ' ;';
		file_put_contents($error_log_file, $error, FILE_APPEND);
	}
}

