		<div class="MainSection">
			<p class="support" style="color: black;">In case of any query please contact us on our support <a href="mailto:https://techmarbles.com/"><strong>support@techmarbles.com</strong></a></p>
			<h3 style="text-transform: uppercase;">Steps to get Netsuite Credentials</h3>
			<div class="class-A">
				<h4>A. Obtaining a NetSuite HOST URL & Account ID</h4>
				<div class="class-A-li">
					<ol>
						<li>Log in to Netsuite</li>
						<li>Navigate to Setup > Company Information</li>
						<li>You can find account ID on this window</li>
						<li> For Host URL > Go to "Company URLs" subtab.Get "SUITETALK (SOAP AND REST WEB SERVICES)" URL from here</li>
					</ol>

				</div>
				<div class="notice notice-warning">
					<h5><b>Example:</b> Account ID will look like "1234567"</h5>
				</div>
				<img src="<?php echo esc_url(TMWNI_URL); ?>/assets/images/NS-Help-1.png" style='height: 563px;width: 1090px;'/>

			</div>
			<div class="class-B">
				<h4>B. Obtaining Netsuite Application ID &amp; Client Credentials</h4>
				<div class="class-B-li">
					<ol>
						<li>Log in to your NetSuite account.</li>
						<li>Click on Setup &gt; Integrations &gt; Manage Integrations &gt; New</li>
						<li>
							On the resulting page, Name the application that will be integrating with NetSuite (for example, "TM Shopify NetSuite")
							<br>
							Make sure "User Credentials" &amp; "Token Based Authentication" both options are checked
						</li>
						<li>
							Press Save. On the resulting page, Application ID, Consumer Key and Consumer Secret will be generated for the application
						</li>
						<li>Copy Application ID, Consumer Key and Consumer Secret and paste them in TM NetSuite plugin settings.</li>
					</ol>
				</div>
				<div class="notice notice-warning">
					<h5><b>Example:</b> Application ID=2A5E28FC-C468-4641-9C99-947FB7082987</h5>
				</div>
				<img src="<?php echo esc_url(TMWNI_URL); ?>/assets/images/NS-Help-2.png" style='height: 563px;width: 1090px;'/>
				<img src="<?php echo esc_url(TMWNI_URL); ?>/assets/images/NS-Help-3.png" style='height: 563px;width: 1090px;'/>

			</div>
			<div class="class-C">
				<h4>C. Obtaining Netsuite Token ID &amp; Token Secret</h4>
				<div class="class-C-li">
					<ol>
						<li>In NetSuite, navigate to Setup &gt; Users/Roles &gt; Access Tokens &gt; New.</li>
						<li>On the Access Tokens page, click New Access Token.</li>
						<li>On the Access Token page:<br>
							<ul class="help-sub-ui">
								<li> Select the Application Name *Note: Make sure that the application is called "TM Shopify NetSuite". If you don't see it, please redo the instructions in the first section.</li>
								<li>Select the User you enabled with Full Access. *Note: Select a user that will always have access.&lt;&gt;
								</li>
								<li>Select the Role with Full Access for example "admin" or "administrator"</li>
								<li>The Token Name is already populated by default with a concatenation of Application Name, User, and Role. You can enter your own name for this token if desired.</li>
							</ul>
						</li>
						<li>
							Click Save.
							<ul class="help-sub-ui">
								<li>
									The confirmation page displays the Token ID and Token Secret. Copy the Token ID and Token Secret and paste them in TM NetSuite plugin settings.</li>
								<li>eat this section to obtain new values.
								</li>
							</ul>
						</li>
					</ol>
				</div>
			</div> 
			<img src="<?php echo esc_url(TMWNI_URL); ?>/assets/images/NS-Help-4.png" style='height: 563px;width: 1090px;'/>

		</div>
