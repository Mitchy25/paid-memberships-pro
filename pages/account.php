<?php
	global $wpdb, $pmpro_msg, $pmpro_msgt, $pmpro_levels, $current_user, $levels;
	
	// $atts    ::= array of attributes
	// $content ::= text within enclosing form of shortcode element
	// $code    ::= the shortcode found, when == callback name
	// examples: [pmpro_account] [pmpro_account sections="membership,profile"/]
	
	if (!isset($atts)){
		$atts = [];
	}

	extract(shortcode_atts(array(
		'section' => '',
		'sections' => 'membership,profile,invoices,links'		
	), $atts));
	
	//did they use 'section' instead of 'sections'?
	if(!empty($section))
		$sections = $section;

	//Extract the user-defined sections for the shortcode
	$sections = array_map('trim',explode(",",$sections));	
	ob_start();

	$membershipStatus = get_user_meta($current_user->ID,'pauseStatus');
	
	//if a member is logged in, show them some info here (1. past invoices. 2. billing information with button to update.)
	if(pmpro_hasMembershipLevel()){
		$ssorder = new MemberOrder();
		$ssorder->getLastMemberOrder();
		$mylevels = pmpro_getMembershipLevelsForUser();
		$pmpro_levels = pmpro_getAllLevels(false, true); // just to be sure - include only the ones that allow signups
		$invoices = $wpdb->get_results("SELECT *, UNIX_TIMESTAMP(timestamp) as timestamp FROM $wpdb->pmpro_membership_orders WHERE user_id = '$current_user->ID' AND status NOT IN('review', 'token', 'error') ORDER BY timestamp DESC LIMIT 6");
		?>	
	<div id="pmpro_account">
	<script src="https://code.jquery.com/jquery-1.10.2.js"></script>
	<script src="https://code.jquery.com/ui/1.10.4/jquery-ui.js"></script>	
	<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js" integrity="sha384-B4gt1jrGC7Jh4AgTPSdUtOBvfO8shuf57BaghqFfPlYxofvL8/KUEfYiJOMMV+rV" crossorigin="anonymous"></script>
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" integrity="sha384-JcKb8q3iqJ61gNV9KGb8thSsNjpSL0n8PARn9HuZOnIxN0hoP+VmmDGMN5t9UJ0Z" crossorigin="anonymous">
		<?php if(in_array('membership', $sections) || in_array('memberships', $sections)) { ?>
			<div id="pmpro_account-membership" class="pmpro_box">
				
				<h3><?php _e("My Memberships", 'buddyboss-theme' );?></h3>
				<table width="100%" cellpadding="0" cellspacing="0" border="0">
					<thead>
						<tr>
							<th><?php _e("Level", 'buddyboss-theme' );?></th>
							<th><?php _e("Actions", 'buddyboss-theme' ); ?></th>
							<th><?php _e("Status", 'buddyboss-theme' );?></th>
							<!-- <th><?php //_e("Billing", 'buddyboss-theme' ); ?></th> -->
							<th><?php _e("Expiration", 'buddyboss-theme' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php
							foreach($mylevels as $level) {
						?>
						<tr>
							<td class="pmpro_account-membership-levelname">
								<?php echo $level->name?>
							</td>
							<td>
							<div class="pmpro_actionlinks">
									<?php do_action("pmpro_member_action_links_before"); ?>
									
									<?php if( array_key_exists($level->id, $pmpro_levels) && pmpro_isLevelExpiringSoon( $level ) ) { ?>
										<a id="pmpro_actionlink-renew" href="<?php echo pmpro_url("checkout", "?level=" . $level->id, "https")?>"><?php _e("Renew", 'buddyboss-theme' );?></a>
									<?php } ?>

									<?php if((isset($ssorder->status) && $ssorder->status == "success") && (isset($ssorder->gateway) && in_array($ssorder->gateway, array("authorizenet", "paypal", "stripe", "braintree", "payflow", "cybersource"))) && pmpro_isLevelRecurring($level)) { ?>
										<a id="pmpro_actionlink-update-billing" href="<?php echo pmpro_url("billing", "", "https")?>"><?php _e("Update Billing Info", 'buddyboss-theme' ); ?></a><br>
									<?php } ?>
									<?php if ($level->name != "Client"){ ?>
										<a id="changePayoutLocation" href="<?php echo 'payouts'; ?>"><?php _e("Change Referral Account", 'buddyboss-theme' );?></a>
									<?php } ?>
									<?php 
										//To do: Only show CHANGE link if this level is in a group that has upgrade/downgrade rules
										if(count($pmpro_levels) > 1 && !defined("PMPRO_DEFAULT_LEVEL") && $level->id > 2) { ?>
										<br><a id="pmpro_actionlink-change" href="<?php echo pmpro_url("levels")?>" id="pmpro_account-change"><?php _e("Change Membership", 'buddyboss-theme' );?></a>
									<?php } ?>
									
									<?php 
										if ($level->id == 1){
											//Membership Pause only available to Coaches
											$member_ids = [];
											$codes = get_option("pmpro_code_user_ids");
											$user_id = $current_user->ID;
											$codeList = array();
											if(is_array($codes)) {
												foreach($codes as $code_id => $code_user_id) {
													if($code_user_id == $user_id){
														array_push($codeList,$code_id);
													}
												}
											}
											$code_urls = array();

											foreach ($codeList as $code){
												$codeLevel = $wpdb->get_var("SELECT level_id FROM $wpdb->pmpro_discount_codes_levels WHERE code_id = '" . esc_sql($code) . "' LIMIT 1");
												$codeValue = $wpdb->get_var("SELECT code FROM $wpdb->pmpro_discount_codes WHERE id = '" . esc_sql($code) . "' LIMIT 1");
												$codeUses = $wpdb->get_var("SELECT uses FROM $wpdb->pmpro_discount_codes WHERE id = '" . esc_sql($code) . "' LIMIT 1");
												if ($codeLevel == 1){
													$codeName = "Coach";
													$code_id = $code;
												} else {
													$codeName = "Client";
												}
												if ($codeName == "Client"){
													$codeType = "discount_code";
												} else {
													$codeType = "affiliate_code";
												}
												array_push($code_urls,array("name"=>$codeName,"level"=>$codeLevel,"uses"=>$codeUses,"id"=>$code,"codeValue"=>$codeValue,"url"=>pmpro_url("checkout", "?level=" . $codeLevel . "&" . $codeType . "=" . $codeValue)));
											}
											
											//Initiate for coaches who haven't purchases license yet
											$codeUses = 0;
											foreach($code_urls as $code_url) {
												if ($code_url['level'] == 2){
													// $clientCodeID = $code_url['id'];
													// $member_ids = $wpdb->get_col("SELECT user_id FROM $wpdb->pmpro_memberships_users WHERE code_id = '$clientCodeID' AND status = 'active'");
													$codeUses = $code_url['uses'];
												}
											}

											// $numberOfClients = count($member_ids);
											if ($codeUses == 0){
												//Membership Pause only available to Coaches with no Clients and no client licenses
												$courseComplete = get_user_meta($current_user->ID, 'course_completed_3228');
												if ($courseComplete && $current_user->membership_level->id == 1){
													$courseComplete = 1;
												} else {
													$courseComplete = 0;
												}
												if ($courseComplete){
													//Membership Pause only available to Coaches who have completed training
													?>
													<br><a href="<?php echo "pause" ?>" id="pauseAccount"><?php 
													
														if (!$membershipStatus){
															_e("Pause Membership", 'buddyboss-theme' );
														} else {
															_e("Activate Membership", 'buddyboss-theme' );
														}

														?></a>
													
													<?php
												}
											}
										} ?>
									<br><a id="pmpro_actionlink-cancel" href="<?php echo pmpro_url("cancel", "?levelstocancel=" . $level->id)?>"><?php _e("Cancel Membership", 'buddyboss-theme' );?></a>
									<?php do_action("pmpro_member_action_links_after"); ?>
								</div> <!-- end pmpro_actionlinks -->
							</td>
							<td>
							<?php
								if (!$membershipStatus){
									?>
									<div id='membershipStatus' style="color:rgb(6, 189, 103);"><strong>Active</strong></div>
									<?php
								} else {
									//Paused
									?>
									<div id='membershipStatus' style="color:rgb(237, 36, 9);"><strong>Paused</strong></div>
									<?php
								}
								?>
							</td>
							<!-- <td class="pmpro_account-membership-levelfee">							
								<p><?php //echo pmpro_getLevelCost($level, true, true);?></p>
							</td> -->
							<td class="pmpro_account-membership-expiration">
							<?php 
								if($level->enddate)
									$expiration_text = date_i18n(get_option('date_format'), $level->enddate);
								else
									$expiration_text = "---";
									echo apply_filters( 'pmpro_account_membership_expiration_text', $expiration_text, $level );
							?>
							</td>
						</tr>
						<?php } ?>
					</tbody>
				</table>
				<?php //Todo: If there are multiple levels defined that aren't all in the same group defined as upgrades/downgrades ?>
				<div class="pmpro_actionlinks">
					<a style="display:none;" id="pmpro_actionlink-levels" href="<?php echo pmpro_url("levels")?>"><?php _e("View all Membership Options", 'buddyboss-theme' );?></a>
				</div>

			</div> <!-- end pmpro_account-membership -->
		<?php } ?>
		
		<?php if(in_array('profile', $sections)) { ?>
			<div id="pmpro_account-profile" class="pmpro_box">	
				<?php wp_get_current_user(); 
					$membershipLevelName = $current_user->membership_level->name;
					$trainingCertificate = "";
					if ($membershipLevelName == "Coach"){
						$trainingCertificate = learndash_get_course_certificate_link(3228);
					}
					

				?>
				<h3><?php _e("My Account", 'buddyboss-theme' );?></h3>
                <div class="bb-pmpro_account-profile">
    				<?php if($current_user->user_firstname) { ?>
    					<p><?php echo $current_user->user_firstname?> <?php echo $current_user->user_lastname?></p>
    				<?php } ?>
    				<ul>
    					<?php do_action('pmpro_account_bullets_top');?>
    					<li><strong><?php _e("Username", 'buddyboss-theme' );?>:</strong> <?php echo $current_user->user_login?></li>
    					<li><strong><?php _e("Email", 'buddyboss-theme' );?>:</strong> <?php echo $current_user->user_email?></li>
    					<?php do_action('pmpro_account_bullets_bottom');?>
    				</ul>
    				<div class="pmpro_actionlinks">
    					<a id="pmpro_actionlink-profile" href="<?php echo bp_loggedin_user_domain() ;?>" id="pmpro_account-edit-profile"><?php _e("Edit Profile", 'buddyboss-theme' );?></a><br>
    					<a id="pmpro_actionlink-password" href="<?php echo bp_loggedin_user_domain() . bp_get_settings_slug(); ?>" id="pmpro_account-change-password"><?php _e('Change Password', 'buddyboss-theme' );?></a>
						<?php
							if ($trainingCertificate){
								?>
								<br><a id="certificateLink" href="<?php echo $trainingCertificate; ?>">Download Training Certificate</a>
								<?php
							}
						?>						
    				</div>
                </div>
			</div> <!-- end pmpro_account-profile -->
		<?php } ?>
	
		<?php if(in_array('invoices', $sections) && !empty($invoices)) { ?>		
		<div id="pmpro_account-invoices" class="pmpro_box">
			<h3><?php _e("Past Invoices", 'buddyboss-theme' );?></h3>
			<table width="100%" cellpadding="0" cellspacing="0" border="0">
				<thead>
					<tr>
						<th><?php _e("Date", 'buddyboss-theme' ); ?></th>
						<th><?php _e("Level", 'buddyboss-theme' ); ?></th>
						<th><?php _e("Amount", 'buddyboss-theme' ); ?></th>
						<th><?php _e("Status", 'buddyboss-theme'); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php 
					$count = 0;
					foreach($invoices as $invoice) 
					{ 
						if($count++ > 4)
							break;

						//get an member order object
						$invoice_id = $invoice->id;
						$invoice = new MemberOrder;
						$invoice->getMemberOrderByID($invoice_id);
						$invoice->getMembershipLevel();		

						if ( in_array( $invoice->status, array( '', 'success', 'cancelled' ) ) ) {
						    $display_status = __( 'Paid', 'buddyboss-theme' );
						} elseif ( $invoice->status == 'pending' ) {
						    // Some Add Ons set status to pending.
						    $display_status = __( 'Pending', 'buddyboss-theme' );
						} elseif ( $invoice->status == 'refunded' ) {
						    $display_status = __( 'Refunded', 'buddyboss-theme' );
						}				
						?>
						<tr id="pmpro_account-invoice-<?php echo $invoice->code; ?>">
							<td><a href="<?php echo pmpro_url("invoice", "?invoice=" . $invoice->code)?>"><?php echo date_i18n(get_option("date_format"), $invoice->timestamp)?></td>
							<td><?php if(!empty($invoice->membership_level)) echo $invoice->membership_level->name; else echo __("N/A", 'buddyboss-theme' );?></td>
							<td><?php echo pmpro_formatPrice($invoice->total)?></td>
							<td><?php echo $display_status; ?></td>
						</tr>
						<?php 
					}
				?>
				</tbody>
			</table>						
			<?php if($count == 6) { ?>
				<div class="pmpro_actionlinks"><a id="pmpro_actionlink-invoices" href="<?php echo pmpro_url("invoice"); ?>"><?php _e("View All Invoices", 'buddyboss-theme' );?></a></div>
			<?php } ?>
		</div> <!-- end pmpro_account-invoices -->
		<?php } ?>
		
		<?php if(in_array('links', $sections) && (has_filter('pmpro_member_links_top') || has_filter('pmpro_member_links_bottom'))) { ?>
		<div id="pmpro_account-links" class="pmpro_box">
			<h3><?php _e("Member Links", 'buddyboss-theme' );?></h3>
			<ul>
				<?php 
					do_action("pmpro_member_links_top");
				?>
				
				<?php 
					do_action("pmpro_member_links_bottom");
				?>
			</ul>
		</div> <!-- end pmpro_account-links -->		
		<?php } ?>
	</div> <!-- end pmpro_account -->		

	<script>
		jQuery(document).ready(function(){
			
			const queryString = window.location.search;
			const urlParams = new URLSearchParams(queryString);
			const focus = urlParams.get('focus');

			if (focus){
				if (focus == "clients"){
					if (jQuery('h3:contains("My Clients")').length>0){
						jQuery('html, body').animate({
							scrollTop: jQuery('h3:contains("My Clients")').offset().top
						}, 2000);
						jQuery('h3:contains("My Clients")').effect("highlight", {}, 2000);
					}
				} else if (focus == "coachReferrals"){
					jQuery('html, body').animate({
						scrollTop: jQuery('h3:contains("My Coach Referrals")').offset().top
					}, 2000);
					jQuery('h3:contains("My Coach Referrals")').effect("highlight", {}, 2000);
				} else if (focus == "clientLicences"){
					if (jQuery('h3:contains("My Clients")').length>0){
						jQuery('html, body').animate({
							scrollTop: jQuery('h3:contains("My Clients")').offset().top
						}, 2000);
						jQuery('h3:contains("My Clients")').effect("highlight", {}, 2000);
					}
				}
			}


			jQuery.ajax({ 
				url: "<?php echo admin_url( 'admin-ajax.php' );?>",
				type: 'POST',
				async: false,
				data: {
					action: "referredCoachCount"
				},
				success: function(value) {
					//Show success message
					jQuery('#referredCoachCount').text(value)
				},
			});
			
			jQuery('#addSeats').click(function(){
                window.location = "<?php echo home_url( '/membership-account/membership?seats=1'); ?>";
            })

			jQuery('#confirmDeleteUser').click(function(){

				$('.spinner-border').show(1000,function(){
					var deleteUserID = jQuery('#confirmDeleteUser').attr('userid');
					console.log(deleteUserID);
					jQuery.ajax({ 
						url: "<?php echo admin_url( 'admin-ajax.php' );?>",
						type: 'POST',
						async: false,
						data: {
							action: "removeUserFromCoach",
							deleteUserID: deleteUserID
						},
						success: function(value) {
							//Show success message
							console.log(value)
							$('#deleteUserModal').modal('hide');
							location.reload()
						},
					});
				});
			})
			jQuery('.deleteUser').click(function(){
				jQuery('#confirmDeleteUser').attr('userid',jQuery(this).attr('userid'));
				jQuery('#deleteUserModal').modal({
					keyboard: false
				})	
			})

			jQuery('#referralLink').click(function(){
				var copyText = jQuery(this).attr('referralLink');
				var textarea = document.createElement("textarea");
				textarea.textContent = copyText;
				textarea.style.position = "fixed"; // Prevent scrolling to bottom of page in MS Edge.
				document.body.appendChild(textarea);
				textarea.select();
				document.execCommand("copy"); 
				document.body.removeChild(textarea);

				/* Alert the copied text */
				jQuery(this).text('Copied!')

				setTimeout(function(){
					jQuery('#referralLink').text('Copy')
				}, 1500)
			})

			jQuery('#coachReferralLink').click(function(){
				var copyText = jQuery(this).attr('referralLink');
				var textarea = document.createElement("textarea");
				textarea.textContent = copyText;
				textarea.style.position = "fixed"; // Prevent scrolling to bottom of page in MS Edge.
				document.body.appendChild(textarea);
				textarea.select();
				document.execCommand("copy"); 
				document.body.removeChild(textarea);

				/* Alert the copied text */
				jQuery(this).text('Copied!')

				setTimeout(function(){
					jQuery('#coachReferralLink').text('Copy')
				}, 1500)
			})

			jQuery('#licenseCount').change(function(){

				jQuery('#updateLicenseCount').addClass('inactiveLink');
				var newLicenseCount = jQuery(this).val();
				var oldLicenseCount = jQuery(this).attr('oldcount');

				console.log(newLicenseCount)
				var minimum = jQuery(this).prop('min')
				if (newLicenseCount < parseInt(minimum)){
					jQuery(this).val(minimum)
				}
				

				if (newLicenseCount != oldLicenseCount){
					jQuery('#updateLicenseCount').removeClass("inactiveLink");
				}
				jQuery.ajax({ 
					url: "<?php echo admin_url( 'admin-ajax.php' );?>",
					type: 'POST',
					async: true,
					data: {
						action: "calculateMonthlyCost",
					},
					success: function(value) {
						var coachLicenseCost = value
						jQuery.ajax({ 
							url: "<?php echo admin_url( 'admin-ajax.php' );?>",
							type: 'POST',
							async: true,
							data: {
								action: "calculateNewSubscriptionCost",
								newSeats: newLicenseCount
							},
							success: function(value) {
								var billingAmount = value;
								var seatCost = billingAmount-coachLicenseCost;
								if (seatCost == 0){
									var perSeatCost = 0
								} else {
									var perSeatCost = seatCost/newLicenseCount;
								}
								jQuery('#originalLicenseCount').text(oldLicenseCount);
								jQuery('#reduceLicenseCount,#reduceLicenseCount2').text(newLicenseCount);
								jQuery('#reduceLicensePrice').text(perSeatCost);
								jQuery('#reduceLicenseMonthlyFee').text(billingAmount);
								jQuery('#coachLicenseCost').text(coachLicenseCost);
							}
						})
					}
				})
			})

			function downgradeSeatCount(newLicenseCount,oldLicenseCount,additionalLicenses,currentUserID,nextChargeDay,nextChargeMonth,nextChargeYear){

				jQuery.ajax({ 
					url: "<?php echo admin_url( 'admin-ajax.php' );?>",
					type: 'POST',
					async: true,
					data: {
						action: "calculateNewSubscriptionCost",
						newSeats: newLicenseCount
					},
					success: function(value) {
						var billingAmount = value;
						jQuery.ajax({ 
							url: "<?php echo admin_url( 'admin-ajax.php' );?>",
							type: 'POST',
							async: false,
							data: {
								action: "updateStripeSubscription",
								currentUserID: currentUserID ,
								billingAmount: billingAmount,
								nextChargeDay: nextChargeDay,
								nextChargeMonth: nextChargeMonth,
								nextChargeYear: nextChargeYear,
								newSeats: newLicenseCount,
								oldSeats: oldLicenseCount,
								additionalSeats: additionalLicenses
							},
							success: function(value) {
								//Show success message
								//alert("Successfully updated to " + newLicenseCount + " client licenses");
								//myAlertTop()
								console.log(value)
								location.reload()
							},
						});
					}
				})
			}

			

			jQuery('#updateLicenseCount').click(function(){

				console.log(<?php echo date_i18n( "Y-m-d", strtotime( "+1 day", current_time( 'timestamp' ) ) );?>);

				var newLicenseCount = parseInt(jQuery('#licenseCount').val());
				var oldLicenseCount = parseInt(jQuery('#licenseCount').attr('oldcount'));

				console.log(newLicenseCount);
				console.log(oldLicenseCount);

				var additionalLicenses = newLicenseCount - oldLicenseCount;

				var currentUserID = <?php echo $current_user->ID; ?>;
				<?php
					global $current_user;
					$clevel = $current_user->membership_level;
					$morder = new MemberOrder();
					$morder->getLastMemberOrder($current_user->ID, array('success', '', 'cancelled'));
					
					//Find Order
					if(!empty($morder->timestamp)){

						$payment_date = strtotime(date("Y-m-d", $morder->timestamp));			
						$payment_day = intval(date("j", $morder->timestamp));
									
						//when would the next payment be			
						$next_payment_date = strtotime(date("Y-m-d", $payment_date) . " + " . $clevel->cycle_number . " " . $clevel->cycle_period);
						if ($clevel->name == "Influencer" || $clevel->name == "BDM"){
							$next_payment_date = "1901-01-01";
						}
					}
				?>
				var nextPaymentDate = <?php echo $next_payment_date ?>;
				var a = new Date(nextPaymentDate * 1000);
				var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
				var nextChargeYear = a.getFullYear();
				var nextChargeMonth = a.getMonth()+1;
				var nextChargeDay = a.getDate();

				if (newLicenseCount < oldLicenseCount){
					jQuery("#downgradeSeatCount").click(function(){
						downgradeSeatCount(newLicenseCount,oldLicenseCount,additionalLicenses,currentUserID,nextChargeDay,nextChargeMonth,nextChargeYear)
					});
				
					//Popup Confirmation
					jQuery('#downgradeModal').modal({
						keyboard: false
					})					
				} else {
					window.location.href = "<?php echo home_url( '/membership-account/membership');?>?seats=" + newLicenseCount 
				}
				
			})
		})
		
	</script>
	<div class="modal fade" id="downgradeModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="exampleModalLabel" style="width:100%;text-align:center;">Updating Client Licences</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
				<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body">
				You are reducing the number of client licences from <span id="originalLicenseCount"></span> to <span id="reduceLicenseCount"></span>.<br><br>From your <strong>next</strong> billing cycle, you will be charged for <span id="reduceLicenseCount2"></span> client licences at a price of £<span id="reduceLicensePrice"></span> per licence plus your coaches licence of £<span id="coachLicenseCost"></span>.<br><br>This will be a total of <strong>£<span id="reduceLicenseMonthlyFee"></span></strong> per month.
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
				<button id="downgradeSeatCount" type="button" class="btn btn-primary" data-dismiss="modal">Proceed</button>
			</div>
			</div>
		</div>
	</div>
	<div class="modal fade" id="deleteUserModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="exampleModalLabel" style="width:100%;text-align:center;">Confirm Client Deletion</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
				<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body">
			You are about to delete this client (user).<br /><br />If you continue, this will delete the client, including all of their personal information and their PBC Assessment information. <br /><br />If you continue, this action cannot be undone and the information cannot be retrieved. <br /><br />Once a client has been deleted, the client licence assigned to them can be re-assigned or deleted. <br /><br />Would you like to continue?.
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-danger" data-dismiss="modal">No, Do Not Continue</button>
				
				<button id="confirmDeleteUser" type="button" class="btn btn-primary">
					<span style="display:none;"class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
					Yes, Continue
				</button>
			</div>
			</div>
		</div>
	</div>

	<!-- Modal -->
	<div class="modal fade" role="dialog" id="sendEmailPopup">
		<div class="modal-dialog">
			<!-- Modal content-->
			<div class="modal-content">
				<div class="modal-header">
					<h4 class="modal-title">Send Coach Invite</h4>
					<button type="button" class="close" data-dismiss="modal">×</button>
				</div>
				<div class="modal-body">

					<form role="form" method="post" id="sendInviteForm">
						<div class="form-group">
							<label for="name" style='font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif,"Apple Color Emoji","Segoe UI Emoji","Segoe UI Symbol"; font-size: 1rem; font-weight: 400; line-height: 1.5; color: #212529;'>Name:</label>
							<input type="text" class="form-control"	id="name" name="name" placeholder="Enter recipient name" required>
						</div>

						<div class="form-group">
							<label for="email" style='font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif,"Apple Color Emoji","Segoe UI Emoji","Segoe UI Symbol"; font-size: 1rem; font-weight: 400; line-height: 1.5; color: #212529;'>	Email:</label>
							<input type="email" placeholder="Enter recipient email" class="form-control"
							id="email" name="email" required>
						</div>
						<div class="form-group" style="text-align:center;">
							<label for="name" style='font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif,"Apple Color Emoji","Segoe UI Emoji","Segoe UI Symbol"; font-size: 1rem; font-weight: 400; line-height: 1.5; color: #212529;'>	Preview:</label>
							<div name="message"	id="message" disabled style="text-align:left; border:1px solid black; padding:15px; border-radius:10px;font-size:14px;">
							<div align="center"><img src="https://poweredbychange.com/wp-content/uploads/2021/01/oie_QU2nkbCDr0F3.png" width="200"><br><strong>Sent by PBC on behalf of <?php echo $current_user->user_firstname . " " . $current_user->user_lastname?></strong></div><span id="messageContents"></span></div>
						</div>
						<button type="submit" class="btn btn-lg btn-success btn-block" id="btnSendEmails">Send Invite →</button>
					</form>
					<div class="alert alert-success" role="alert" id="success_message" style="width:100%; height:100%; display:none; ">
						Sent your message successfully! Ready to send another invite
					</div>
					<div class="alert alert-warning" role="alert" id="error_message"	style="width:100%; height:100%; display:none; ">
						Error - Sorry there was an error sending your email.
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="modal fade" role="dialog" id="sendEmailPopupClients">
		<div class="modal-dialog">
			<!-- Modal content-->
			<div class="modal-content">
				<div class="modal-header">
					<h4 class="modal-title">Send Client Invite</h4>
					<button type="button" class="close" data-dismiss="modal">×</button>
				</div>
				<div class="modal-body">

					<form role="form" method="post" id="sendInviteFormClients">
						<div class="form-group">
							<label for="name_clients" style='font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif,"Apple Color Emoji","Segoe UI Emoji","Segoe UI Symbol"; font-size: 1rem; font-weight: 400; line-height: 1.5; color: #212529;'>Name:</label>
							<input type="text" class="form-control"	id="name_clients" name="name_clients" placeholder="Enter recipient name" required>
						</div>

						<div class="form-group">
							<label for="email_clients" style='font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif,"Apple Color Emoji","Segoe UI Emoji","Segoe UI Symbol"; font-size: 1rem; font-weight: 400; line-height: 1.5; color: #212529;'>	Email:</label>
							<input type="email" placeholder="Enter recipient email" class="form-control" id="email_clients" name="email_clients" required>
						</div>
						<div class="form-group" style="text-align:center;">
							<label for="message_clients" style='font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif,"Apple Color Emoji","Segoe UI Emoji","Segoe UI Symbol"; font-size: 1rem; font-weight: 400; line-height: 1.5; color: #212529;'>	Preview:</label>
							<div name="message_clients"	id="message_clients" disabled style="text-align:left; border:1px solid black; padding:15px; border-radius:10px;font-size:14px;">
							<div align="center"><img src="https://poweredbychange.com/wp-content/uploads/2021/01/oie_QU2nkbCDr0F3.png" width="200"><br><strong>Sent by PBC on behalf of <?php echo $current_user->user_firstname . " " . $current_user->user_lastname?></strong></div><span id="messageContents_clients"></span></div>
						</div>
						<button type="submit" class="btn btn-lg btn-success btn-block" id="btnSendEmailsClients">Send Invite →</button>
					</form>
					<div class="alert alert-success" role="alert" id="success_message_clients" style="width:100%; height:100%; display:none; ">
						Sent your message successfully! Ready to send another invite
					</div>
					<div class="alert alert-warning" role="alert" id="error_message_clients"	style="width:100%; height:100%; display:none; ">
						Error - Sorry there was an error sending your email.
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="modal fade" role="dialog" id="notEnoughLicenses">
		<div class="modal-dialog">
			<!-- Modal content-->
			<div class="modal-content">
				<div class="modal-header">
					<h4 class="modal-title">Not Enough Client Licences</h4>
					<button type="button" class="close" data-dismiss="modal">×</button>
				</div>
				<div class="modal-body">
					You currently have used all of your client licences. Please purchase more licences to send invites.
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-success" data-dismiss="modal">Ok</button>
				</div>
			</div>
		</div>
	</div>
	<script>
		//Coaches
		function after_form_submitted(data) {
			console.log(data.response)
			if(data.result == 'success') {
				//$('form#sendInviteForm').hide();
				$('#success_message').show();
				$('#email').val('')
				$('#name').val('')
				$('#error_message').hide();
				updateEmailCopy();
			} else {
				$('#error_message').append('<ul></ul>');
				jQuery.each(data.errors,function(key,val){
					$('#error_message ul').append('<li>'+key+':'+val+'</li>');
				});
				$('#success_message').hide();
				$('#error_message').show();
			}

			//reverse the response on the button
			$('button[type="button"]', $form).each(function() {
				$btn = $(this);
				label = $btn.prop('orig_label');
				if(label) {
					$btn.prop('type','submit' );
					$btn.text(label);
					$btn.prop('orig_label','');
				}
			});
		}

		$('#sendCoachReferralLink').click(function(){
			//Update Email Copy
			updateEmailCopy();
		})

		$('#name').on('input selectionchange propertychange',function(){
			//Update EMail Copy
			updateEmailCopy();
		})

		function updateEmailCopy(){
			var name = $('#name').val();
			if (!name){
				name = "there";
			}
			var referralLink = $('#coachReferralLink').attr('referrallink');
			referralLink.replace('"','');
			var coachName = "<?php echo $current_user->user_firstname . " " . $current_user->user_lastname?>"
			var coachEmail = "<?php echo $current_user->user_email ?>"
			<?php 
				if ($current_user->membership_level->name == "Coach"){
					?>
					var emailCopy = "<br>Hi " + name +",<br><br>I’ve just become a PBC coach. Here’s my referral link you can use to do the same: <a href='" + referralLink + "'>Link</a><br><br>To find out how PBC can enhance your business coaching and generate an income stream, check out the <a href='https://poweredbychange.com/coaches-home/'>Powered By Change</a> website as well as the <a href='https://youtu.be/moWYXA9FghE'>coaches revenue video</a> for more information on the benefits for you.<br><br>Feel free to get in touch with me if you have any questions (" + coachEmail + ").<br><br>Regards,<br>" + coachName;
				<?php
				} else {
					?>
					var emailCopy = "<br>Hi " + name +",<br><br>I’m inviting you to check-out PBC so you can use this platform to supercharge your clients results. <br><br>To find out how PBC can enhance your business coaching and generate an income stream, check out the <a href='https://poweredbychange.com/coaches-home/'>Powered By Change</a> website as well as the <a href='https://youtu.be/moWYXA9FghE'>coaches revenue video</a> for more information on the benefits for you.<br><br>Here’s my referral link you can use to join PBC so we can collaborate and enhance your success: <a href='" + referralLink + "'>Link</a><br><br>I’ll be in touch shortly. In the meantime, feel free to get in touch with me if you have any questions (" + coachEmail + ").<br><br>Regards,<br>" + coachName;
				<?php
				}
			?>
			$('#messageContents').html(emailCopy);
		}

		$('#sendInviteForm').submit(function(e) {
			e.preventDefault();

			$form = $(this);
			//show some response on the button
			$('button[type="submit"]', $form).each(function() {
				$btn = $(this);
				$btn.prop('type','button' );
				$btn.prop('orig_label',$btn.text());
				$btn.text('Sending ...');
			});

			var coachName = "<?php echo $current_user->user_firstname . " " . $current_user->user_lastname?>"

			jQuery.ajax({ 
				url: "<?php echo admin_url( 'admin-ajax.php' );?>",
				type: 'POST',
				async: true,
				data: {
					action: "sendEmailInvites",
					email: $('#email').val(),
					message: $('#messageContents').html(),
					coachName: coachName,
					emailType: "Coach"
				},
				success: after_form_submitted,
				dataType: 'json'
			});
		});

		//Clients
		function after_form_submitted_clients(data) {
			console.log(data.response)
			if(data.result == 'success') {
				//$('form#sendInviteForm').hide();
				$('#success_message_clients').show();
				$('#email_clients').val('')
				$('#name_clients').val('')
				$('#error_message_clients').hide();
				updateEmailCopyClients();
			} else {
				$('#error_message_clients').append('<ul></ul>');
				jQuery.each(data.errors,function(key,val){
					$('#error_message_clients ul').append('<li>'+key+':'+val+'</li>');
				});
				$('#success_message_clients').hide();
				$('#error_message_clients').show();
			}

			//reverse the response on the button
			$('button[type="button"]', $form).each(function() {
				$btn = $(this);
				label = $btn.prop('orig_label');
				if(label) {
					$btn.prop('type','submit' );
					$btn.text(label);
					$btn.prop('orig_label','');
				}
			});
		}

		$('#sendClientReferralLink').click(function(){
			var usedClientLicenses = parseInt($('#usedClientLicenses').text())
			var totalClientLicenses = parseInt($('#totalClientLicenses').text())

			console.log(usedClientLicenses)
			console.log(totalClientLicenses)
			if (usedClientLicenses == totalClientLicenses){
				$('#notEnoughLicenses').modal('show');
			} else {
				//Update Email Copy
				updateEmailCopyClients();
				$('#sendEmailPopupClients').modal('show');
			}
			
			
		})

		$('#name_clients').on('input selectionchange propertychange',function(){
			//Update EMail Copy
			updateEmailCopyClients();
		})

		$('#email_clients').on('input selectionchange propertychange',function(){
			//Update EMail Copy
			updateEmailCopyClients();
		})

		function updateEmailCopyClients(){
			var name = $('#name_clients').val();
			if (!name){
				name = "there";
			}
			var referralLink = $('#referralLink').attr('referrallink');
			referralLink.replace('"','');
			var coachName = "<?php echo $current_user->user_firstname . " " . $current_user->user_lastname?>"
			var coachEmail = "<?php echo $current_user->user_email;?>"
			var emailCopy = "<br>Hi " + name +",<br><br>I’m inviting you to join me in using the Powered By Change (PBC) platform to supercharge your results. <br><br>Here’s my referral link. You can use this to join PBC so we can collaborate and enhance your success: <a href='" + referralLink + "&bemail=" + jQuery('#email_clients').val() + "'>Link</a><br><br>To find out more about PBC and how it can help improve your business success, you can register for the complimentary <a href='https://poweredbychange.com/clients-home/'>webinar through the website</a>.<br><br>Feel free to get in touch with me if you have any questions (" + coachEmail + ").<br><br>Regards,<br>" + coachName;

			$('#messageContents_clients').html(emailCopy);
		}

		$('#sendInviteFormClients').submit(function(e) {
			e.preventDefault();

			$form = $(this);
			//show some response on the button
			$('button[type="submit"]', $form).each(function() {
				$btn = $(this);
				$btn.prop('type','button' );
				$btn.prop('orig_label',$btn.text());
				$btn.text('Sending ...');
			});

			var coachName = "<?php echo $current_user->user_firstname . " " . $current_user->user_lastname?>"

			jQuery.ajax({ 
				url: "<?php echo admin_url( 'admin-ajax.php' );?>",
				type: 'POST',
				async: true,
				data: {
					action: "sendEmailInvites",
					email: $('#email_clients').val(),
					message: $('#messageContents_clients').html(),
					coachName: coachName,
					emailType: "Client"
				},
				success: after_form_submitted_clients,
				dataType: 'json'
			});
		});
	</script>
	<?php
	}
?>
