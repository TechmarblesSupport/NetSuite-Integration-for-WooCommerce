<div id="wrap" > 
	<h1 id="taps_title"> TM NetSuite</h1>
	<nav class="nav-tab-wrapper">
		<?php
		foreach (TMWNI_Settings::$tabs as $tab_id => $tab_heading) {
			?>

				<a class="nav-tab 
				<?php
				if ($current_tab_id == $tab_id) {
					echo 'nav-tab-active';
				}
				?>
				" 
			
				href="?page=<?php echo esc_attr(TMWNI_Settings::$page_id); ?>&tab=<?php echo esc_attr($tab_id); ?>"><?php echo esc_attr($tab_heading); ?></a>
	   <?php } ?>  
	</nav>
	<div id="tab-<?php echo esc_attr($current_tab_id); ?>">
		<?php if (strpos($current_tab_id, 'settings')) { ?>
			<div class="notice notice-warning">
				<h4>If you made any changes here, please make sure to save settings before moving to another tab :) </h4>
			</div>
		<?php } ?>
		<?php if ('general_settings' == $current_tab_id) { ?>
			<div class="notice notice-success">
				<h4>Need help with API settings? Navigate to <i>Help & Support tab</i> :) </h4>
			</div>
		<?php } ?>
		 <?php if ('inventory_settings' == $current_tab_id) { ?>

			<div class="notice notice-success">
				<h4>Please make sure your NetSuite Item ID/Name matches with WooCommerce product SKU</h4>
			</div>

			<div class="notice notice-error">
				<h4>If you have a large inventory, i.e. more than 1500 products then its recomended to use server crons. Some references on this are <a target="_blank" href="https://www.lucasrolff.com/wordpress/why-wp-cron-sucks/">Ref 1</a> , <a target="_blank" href="https://community.1and1.com/replace-wp-cron-in-wordpress-with-server-side-cron-job/">Ref 2</a>, <a target="_blank" href="http://chrislema.com/understanding-wp-cron/">Ref 3</a></h4>
			</div>
		<?php } ?>
		<div class="tm-netsuite-container container-fluid woocommerce">

		<?php 
		$allow_html =TMWNI_Settings::shapeSpace_allowed_html();
		 echo  wp_kses($tab_content, $allow_html);
		 // echo  ($tab_content);


		?>
		</div>
	</div>
</div>
