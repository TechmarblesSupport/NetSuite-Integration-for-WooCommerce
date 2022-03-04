<?php

require_once TMWNI_DIR . 'inc/common.php';
$this->netsuiteCommonClient = new CommonIntegrationFunctions();



if (TMWNI_Settings::areCredentialsDefined()) {
	$customer_sync = $this->netsuiteCommonClient->getNetsuiteCustomerSync();
	$customer_sync_count = count($customer_sync);
	$guest_customer_sync = $this->netsuiteCommonClient->getNetsuiteGuestCustomerSync();
	$guest_customer_sync_count = count($guest_customer_sync);
	$total_customer_sync = $customer_sync_count + $guest_customer_sync_count;
	$order_syncc = $this->netsuiteCommonClient->getNetsuiteOrderSync();
	$order_sync = count($order_syncc);
	$inventory_update_time = get_option('ns_woo_inventory_update');

} else {
	$total_customer_sync = '';
	$order_sync = '';
	$inventory_update_time = '';
}

//$order_sync_data = $this->netsuiteCommonClient->getOrderSyncLogs();
?>
<div class="container-fluid">
	<div class="row">
		<div class="col-md-4">
			<div class="dashboard-view-boxs">
				<div class="inner-box">
					<i class="glyphicon glyphicon-user" aria-hidden="true"></i>
					<p><span><?php esc_attr_e($total_customer_sync); ?></span> Customer(s) Synced </p>
				</div>
			</div>
		</div>
		<div class="col-md-4">
			<div class="dashboard-view-boxs">
				<div class="inner-box">
					<i class="glyphicon glyphicon-folder-close" aria-hidden="true"></i>
					<p><span><?php esc_attr_e($order_sync); ?></span> Order(s) Synced </p>
				</div>
			</div>
		</div>
		<div class="col-md-4">
			<div class="dashboard-view-boxs">
				<div class="inner-box">
					<i class="glyphicon glyphicon-th-list" aria-hidden="true"></i>
					<p>Inventory last updated on <?php esc_attr_e($inventory_update_time); ?></p>

				</div>
			</div>
		</div>
	</div>
	<div class="row" style="display: block;">
		<div class="col-md-12">
			<div id="autoSyncTabledcantainer">
			</div>
		</div>
	</div>
</div>
<button id="cleardashboardlogs" class="btn btn-danger" value="clearDashboardLogs">Clear All Logs</button>

<table class="dashboard-form-table dataTable" id="dashboardList" style="width: 1200px;">
	<thead>
		<tr role="row">
			<th>ID</th>
			<th>Order Id</th>
			<th>Order Date</th>
			<th>View Order On Netsuite</th>
			<th>Order Status</th>
			<th>Action</th>
		</tr>
	</thead>
</table>

