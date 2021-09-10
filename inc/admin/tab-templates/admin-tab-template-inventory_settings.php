<form  action="admin-post.php" method="post" id="settings_tm_ns"> 
	<input type="hidden" name="action" value="save_tm_ns_settings"> 
	<input type="hidden" name="current_tab_id" value="<?php echo esc_attr($current_tab_id); ?>">
	<?php wp_nonce_field('nonce'); ?>
	<h2>
		 Product/Inventory Sync Settings
	</h2>
	<div class="inventory-table-main">
	<table class="form-table">
	<tbody>
		<tr valign="top" class="">
			<th scope="row" class="titledesc">
				Update Inventory(Stock quantity) from NetSuite
				<div class="tooltip dashicons-before dashicons-editor-help">
				<span class="tooltiptext">Update WooCommerce Product Stock Quantity from NetSuite</span>
				</div>
			</th>
			<td class="forminp forminp-checkbox">
				<input name="enableInventorySync" 
				<?php 
				if (isset($options['enableInventorySync']) && 'on' == $options['enableInventorySync']) {
echo 'checked ';} 
				?>
				 id="enableInventorySync" type="checkbox">                        
			</td>
		</tr>
		<tr valign="top" class="">
			<th scope="row" class="titledesc">
				Fetch Inventory from default Locations
				<div class="tooltip dashicons-before dashicons-editor-help">
				<span class="tooltiptext">If checked inventory from items default location will be used else inventory from all location will be used</span>
				</div>
			</th>
			<td class="forminp forminp-checkbox">
				<input name="inventoryDefaultLocation" 
				<?php 
				if (isset($options['inventoryDefaultLocation']) && 'on' == $options['inventoryDefaultLocation']) {
echo 'checked ';} 
				?>
				 id="inventoryDefaultLocation" type="checkbox">
				<label for="inventoryDefaultLocation">(Note : This will only work when inventory sync is enabled.)</label>                    
			</td>
		</tr>
		<tr valign="top" class="">
			<th scope="row" class="titledesc">
				Update Product Price from NetSuite
				<div class="tooltip dashicons-before dashicons-editor-help">
				<span class="tooltiptext">Update WooCommerce Product Price from NetSuite</span>
				</div>
			</th>
			<td class="forminp forminp-checkbox">
				<input name="enablePriceSync" 
				<?php 
				if (isset($options['enablePriceSync']) && 'on' == $options['enablePriceSync']) {
echo 'checked ';} 
				?>
				 id="enablePriceSync" type="checkbox">                        
			</td>
		</tr>
		<tr valign="top" class="">
			<th scope="row" class="titledesc">
				Override Manage Stock
				<div class="tooltip dashicons-before dashicons-editor-help">
				<span class="tooltiptext">If checked enable stock management at product level</span>
				</div>
			</th>
			<td class="forminp forminp-checkbox">
				<input name="overrideManageStock" 
				<?php 
				if (isset($options['overrideManageStock']) && 'on' == $options['overrideManageStock']) {
echo 'checked ';} 
				?>
				 id="overrideManageStock" type="checkbox">
				<label for="overrideManageStock">(Note : This will only work when inventory sync is enabled.)</label>                    
			</td>
		</tr>
		<tr valign="top" class="">
			<th scope="row" class="titledesc">
				Price Level Name
				<div class="tooltip dashicons-before dashicons-editor-help">
				<span class="tooltiptext">Mention the price level of the product Example Base Price , Online Price</span>
				</div>
			</th>
			<td class="forminp forminp-checkbox">
				<?php
				if (isset($options['price_level_name']) && !empty($options['price_level_name'])) {
					$price_level_value = $options['price_level_name'];
				} else {
					$price_level_value = TMWNI_Settings::$pricing_group;
				}
				?>

				<input name="price_level_name" id="price_level_name" type="text" value="<?php echo esc_attr($price_level_value); ?>" >                     
			</td>
		</tr>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="inventorySyncFrequency">Inventory and(or) Price Sync Frequency</label>

				<div class="tooltip dashicons-before dashicons-editor-help">
				<span class="tooltiptext">Set a frequency for inventory and(or) price update from NetSuite</span>
				</div>
			</th>
			<td class="forminp forminp-select">
				<select name="inventorySyncFrequency" id="inventorySyncFrequency" style="" class="">
					<?php 
					foreach ($inventory_sync_frequencies as $inventory_sync_frequency_id=>$inventory_sync_frequency_name) { 
						?>
						<option 
						<?php 
						if (isset($options['inventorySyncFrequency']) && $options['inventorySyncFrequency'] == $inventory_sync_frequency_id) {
echo 'selected ';} 
						?>
						 value="<?php echo esc_attr($inventory_sync_frequency_id); ?>"><?php echo esc_attr($inventory_sync_frequency_name); ?> </option>
					<?php } ?>
				</select>
			</td>
		</tr>
		<tr valign="top">
		 <th scope="row" class="titledesc">
		   <input type="submit" class="button-primary" name="save_post" value="Save Settings" /> 
		</th>
		</tr>
		<?php 
		if (isset($options['enableInventorySync']) && 'on' == $options['enableInventorySync'] || ( isset($options['enablePriceSync']) && 'on' == $options['enablePriceSync'] )) {

			?>
		<tr>
		<th scope="row" class="titledesc">
		<a class="button-primary manual-update-inventory">Manual update inventory and(or) price</a> <div class="inventory-loader"></div>
		</th>
		</tr>
		<?php } ?>
		</tbody>
	</table>
	
</div>
	<div id="inventoy_progress">
				
				<span id="right_progress" style="display: none">
					<div class="progress_processed">
						<span>Processed <span class="processed_count">0</span> of <span id="of">0</span> records</span>
					</div>
					<div class="progress_details">
						
						
						<span class="progress_details_item updated_count" style="">
							Updated <span class="updated_records_count">0</span>
						</span>
						<span class="progress_details_item skipped_count">
							Skipped <span class="skipped_records_count">0</span>
						</span>
					</div>
				</span>
			</div>
			<fieldset class="inventory-logs">
				<legend class="log-head">Log</legend>
			<div class="log-list">
			</div>
		</fieldset>
</form>
