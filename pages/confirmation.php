<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js" integrity="sha384-B4gt1jrGC7Jh4AgTPSdUtOBvfO8shuf57BaghqFfPlYxofvL8/KUEfYiJOMMV+rV" crossorigin="anonymous"></script>
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" integrity="sha384-JcKb8q3iqJ61gNV9KGb8thSsNjpSL0n8PARn9HuZOnIxN0hoP+VmmDGMN5t9UJ0Z" crossorigin="anonymous">
<div class="<?php echo pmpro_get_element_class( 'pmpro_confirmation_wrap' ); ?>">
<?php
	global $wpdb, $current_user, $pmpro_invoice, $pmpro_msg, $pmpro_msgt;

	if($pmpro_msg){
	?>
		<div class="<?php echo pmpro_get_element_class( 'pmpro_message ' . $pmpro_msgt, $pmpro_msgt ); ?>"><?php echo wp_kses_post( $pmpro_msg );?></div>
	<?php
	}

	if(empty($current_user->membership_level)){
		$confirmation_message = "<p>" . __('Your payment has been submitted. Your membership will be activated shortly.', 'paid-memberships-pro' ) . "</p>";
	} else {
		//Seat Check
		if (isset($_REQUEST['seats'])){
			$seats = $_REQUEST['seats'];
			$originalSeats = $_REQUEST['originalSeats'];
			$newSeats = intval($seats) - intval($originalSeats);
			if ($originalSeats == 0){
				//First Seat Checkout
				$confirmation_message = "<p>" . sprintf(__('Thank you for purchasing %s client licences to %s.', 'paid-memberships-pro' ), $seats, get_bloginfo("name")) . "</p>";
			} else {
				//Additional Seat Checkout
				$confirmation_message = "<p>" . sprintf(__('Thank you for purchasing an additional %s client licences to %s. You now have a total of %s client licences.', 'paid-memberships-pro' ),$newSeats, get_bloginfo("name"), $seats) . "</p>";
			}
			
		} else {
			$confirmation_message = "<p>" . sprintf(__('Thank you for your membership to %s. Your %s membership is now active.', 'paid-memberships-pro' ), get_bloginfo("name"), $current_user->membership_level->name) . "</p>";
		}
		
	}
	//confirmation message for this level
	$level_message = $wpdb->get_var("SELECT l.confirmation FROM $wpdb->pmpro_membership_levels l LEFT JOIN $wpdb->pmpro_memberships_users mu ON l.id = mu.membership_id WHERE mu.status = 'active' AND mu.user_id = '" . $current_user->ID . "' LIMIT 1");
	if(!empty($level_message)){
		$confirmation_message .= "\n" . stripslashes($level_message) . "\n";
	}
?>

<?php if(!empty($pmpro_invoice) && !empty($pmpro_invoice->id)) { ?>

	<?php
		$pmpro_invoice->getUser();
		$pmpro_invoice->getMembershipLevel();
		//Seat Check
		if (isset($_REQUEST['seats'])){
			$confirmation_message .= "<p>" . sprintf(__('Below are details about your account and a receipt for your invoice. A PBC Client Licence purchase confirmation email with a copy of your invoice has been sent to %s.', 'paid-memberships-pro' ), $pmpro_invoice->user->user_email) . "</p>";
		} else {
			$confirmation_message .= "<p>" . sprintf(__('Below are details about your account and a receipt for your initial invoice. A welcome email with a copy of your initial invoice has been sent to %s.', 'paid-memberships-pro' ), $pmpro_invoice->user->user_email) . "</p>";
		}
		// Check instructions
		if ( $pmpro_invoice->gateway == "check" && ! pmpro_isLevelFree( $pmpro_invoice->membership_level ) ) {
			$confirmation_message .= '<div class="' . pmpro_get_element_class( 'pmpro_payment_instructions' ) . '">' . wpautop( wp_unslash( pmpro_getOption("instructions") ) ) . '</div>';
		}

		/**
		 * All devs to filter the confirmation message.
		 * We also have a function in includes/filters.php that applies the the_content filters to this message.
		 * @param string $confirmation_message The confirmation message.
		 * @param object $pmpro_invoice The PMPro Invoice/Order object.
		 */
		$confirmation_message = apply_filters("pmpro_confirmation_message", $confirmation_message, $pmpro_invoice);

		echo wp_kses_post( $confirmation_message );
	?>
	<h3>
		<?php printf(__('Invoice #%s on %s', 'paid-memberships-pro' ), $pmpro_invoice->code, date_i18n(get_option('date_format'), $pmpro_invoice->getTimestamp()));?>
	</h3>
	<br>
	<a class="<?php echo pmpro_get_element_class( 'pmpro_a-print' ); ?>" href="javascript:window.print()"><?php _e('Print', 'paid-memberships-pro' );?></a>
	<ul>
		<?php do_action("pmpro_invoice_bullets_top", $pmpro_invoice); ?>
		<li><strong><?php _e('Account', 'paid-memberships-pro' );?>:</strong> <?php echo esc_html( $current_user->display_name );?> (<?php echo esc_html( $current_user->user_email );?>)</li>
		<li><strong><?php _e('Membership Level', 'paid-memberships-pro' );?>:</strong> <?php echo esc_html( $current_user->membership_level->name);?></li>
		<?php if($current_user->membership_level->enddate) { ?>
			<li><strong><?php _e('Membership Expires', 'paid-memberships-pro' );?>:</strong> <?php echo date_i18n(get_option('date_format'), $current_user->membership_level->enddate)?></li>
		<?php } ?>
		<?php if($pmpro_invoice->getDiscountCode()) { ?>
			<li><strong><?php _e('Discount Code', 'paid-memberships-pro' );?>:</strong> <?php echo esc_html( $pmpro_invoice->discount_code->code );?></li>
		<?php } ?>
		<?php if($pmpro_invoice->getAffiliateCode()) { ?>
			<li><strong><?php _e('Coach Referral Code', 'paid-memberships-pro' );?>:</strong> <?php echo esc_html( $pmpro_invoice->affiliate_id );?></li>
		<?php } ?>
		<?php do_action("pmpro_invoice_bullets_bottom", $pmpro_invoice); ?>
	</ul>
	<hr />
	<div class="<?php echo pmpro_get_element_class( 'pmpro_invoice_details' ); ?>">
		<?php if(!empty($pmpro_invoice->billing->name)) { ?>
			<div class="<?php echo pmpro_get_element_class( 'pmpro_invoice-billing-address' ); ?>">
				<strong><?php _e('Billing Address', 'paid-memberships-pro' );?></strong>
				<p><?php echo esc_html( $pmpro_invoice->billing->name );?><br />
				<?php echo esc_html( $pmpro_invoice->billing->street );?><br />
				<?php if($pmpro_invoice->billing->city && $pmpro_invoice->billing->state) { ?>
					<?php echo esc_html( $pmpro_invoice->billing->city );?>, <?php echo esc_html( $pmpro_invoice->billing->state );?> <?php echo esc_html( $pmpro_invoice->billing->zip );?> <?php echo esc_html( $pmpro_invoice->billing->country );?><br />
				<?php } ?>
				<?php echo formatPhone($pmpro_invoice->billing->phone)?>
				</p>
			</div> <!-- end pmpro_invoice-billing-address -->
		<?php } ?>

		<?php if ( ! empty( $pmpro_invoice->accountnumber ) || ! empty( $pmpro_invoice->payment_type ) ) { ?>
			<div class="<?php echo pmpro_get_element_class( 'pmpro_invoice-payment-method' ); ?>">
				<strong><?php _e('Payment Method', 'paid-memberships-pro' );?></strong>
				<?php if($pmpro_invoice->accountnumber) { ?>
					<p><?php echo esc_html( ucwords( $pmpro_invoice->cardtype ) ); ?> <?php _e('ending in', 'paid-memberships-pro' );?> <?php echo esc_html( last4($pmpro_invoice->accountnumber ) );?>
					<br />
					<?php _e('Expiration', 'paid-memberships-pro' );?>: <?php echo esc_html( $pmpro_invoice->expirationmonth );?>/<?php echo esc_html( $pmpro_invoice->expirationyear );?></p>
				<?php } else { ?>
					<p><?php echo esc_html( $pmpro_invoice->payment_type ); ?></p>
				<?php } ?>
			</div> <!-- end pmpro_invoice-payment-method -->
		<?php } ?>

		<div class="<?php echo pmpro_get_element_class( 'pmpro_invoice-total' ); ?>">
			<strong><?php _e('Total Billed', 'paid-memberships-pro' );?></strong>
			<p><?php if($pmpro_invoice->total != '0.00') { ?>
				<?php if(!empty($pmpro_invoice->tax)) { ?>
					<?php _e('Subtotal', 'paid-memberships-pro' );?>: <?php echo pmpro_formatPrice($pmpro_invoice->subtotal);?><br />
					<?php _e('Tax', 'paid-memberships-pro' );?>: <?php echo pmpro_formatPrice($pmpro_invoice->tax);?><br />
					<?php if(!empty($pmpro_invoice->couponamount)) { ?>
						<?php _e('Coupon', 'paid-memberships-pro' );?>: (<?php echo pmpro_formatPrice($pmpro_invoice->couponamount);?>)<br />
					<?php } ?>
					<strong><?php _e('Total', 'paid-memberships-pro' );?>: <?php echo pmpro_formatPrice($pmpro_invoice->total);?></strong>
				<?php } else { ?>
					<?php echo pmpro_formatPrice($pmpro_invoice->total);?>
				<?php } ?>
			<?php } else { ?>
				<small class="<?php echo pmpro_get_element_class( 'pmpro_grey' ); ?>"><?php echo esc_html( pmpro_formatPrice(0) );?></small>
			<?php } ?></p>
		</div> <!-- end pmpro_invoice-total -->

	</div> <!-- end pmpro_invoice -->
	<hr />
<?php
	}
	else
	{
		$confirmation_message .= "<p>" . sprintf(__('Below are details about your membership account. A welcome email has been sent to %s.', 'paid-memberships-pro' ), $current_user->user_email) . "</p>";

		/**
		 * All devs to filter the confirmation message.
		 * Documented above.
		 * We also have a function in includes/filters.php that applies the the_content filters to this message.
		 */
		$confirmation_message = apply_filters("pmpro_confirmation_message", $confirmation_message, false);

		echo wp_kses_post( $confirmation_message );
	?>
	<ul>
		<li><strong><?php _e('Account', 'paid-memberships-pro' );?>:</strong> <?php echo esc_html( $current_user->display_name );?> (<?php echo esc_html( $current_user->user_email );?>)</li>
		<li><strong><?php _e('Membership Level', 'paid-memberships-pro' );?>:</strong> <?php if(!empty($current_user->membership_level)) echo esc_html( $current_user->membership_level->name ); else _e("Pending", 'paid-memberships-pro' );?></li>
	</ul>
<?php
	}
?>
<p class="<?php echo pmpro_get_element_class( 'pmpro_actions_nav' ); ?>">
	<?php if ( ! empty( $current_user->membership_level ) ) { ?>
		<a style="display:none;" href="<?php echo pmpro_url( 'account' ); ?>"><?php _e( 'View Your Membership Account &rarr;', 'paid-memberships-pro' ); ?></a>
		<?php 
			if( get_user_meta($current_user->ID, 'first_login')){
				if (get_user_meta($current_user->ID, 'first_login')[0] == 1 ){
					//This is users first login
					update_user_meta($current_user->ID, 'first_login',0);
					$welcome="?welcome=1";
				} else {
					$welcome="";
				}
			} else {
				update_user_meta($current_user->ID, 'first_login',0);
				$welcome="";
			}
			if ($current_user->membership_level->name == "Coach"){ 
				$link = "https://poweredbychange.com/coach-home".$welcome;
			} else {
				$link = "https://poweredbychange.com/home".$welcome;
			}
		?>
		<a href="<?php echo $link?>"><?php _e( 'Continue &rarr;', 'paid-memberships-pro' ); ?></a>
	<?php } else { ?>
		<?php _e( 'If your account is not activated within a few minutes, please contact the site owner.', 'paid-memberships-pro' ); ?>
	<?php } ?>
</p> <!-- end pmpro_actions_nav -->
</div> <!-- end pmpro_confirmation_wrap -->
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

jQuery(document).ready(function(){

	jQuery('#coachReferralLink').click(function(){
		var copyText = jQuery(this).attr('data-referrallink');
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
	jQuery('#clientReferralLink').click(function(){
		var copyText = jQuery(this).attr('data-referrallink');
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
			jQuery('#clientReferralLink').text('Copy')
		}, 1500)
	})

	//Coaches
	function after_form_submitted(data) {
			console.log(data.response)
			if(data.result == 'success') {
				//$('form#sendInviteForm').hide();
				jQuery('#success_message').show();
				jQuery('#email').val('')
				jQuery('#name').val('')
				jQuery('#error_message').hide();
				updateEmailCopy();
			} else {
				jQuery('#error_message').append('<ul></ul>');
				jQuery.each(data.errors,function(key,val){
					jQuery('#error_message ul').append('<li>'+key+':'+val+'</li>');
				});
				jQuery('#success_message').hide();
				jQuery('#error_message').show();
			}

			//reverse the response on the button
			jQuery('button[type="button"]', $form).each(function() {
				$btn = jQuery(this);
				label = $btn.prop('orig_label');
				if(label) {
					$btn.prop('type','submit' );
					$btn.text(label);
					$btn.prop('orig_label','');
				}
			});
		}

		jQuery('#sendCoachReferralLink').click(function(){
			//Update Email Copy
			updateEmailCopy();
		})

		jQuery('#name').on('input selectionchange propertychange',function(){
			//Update EMail Copy
			updateEmailCopy();
		})

		function updateEmailCopy(){
			var name = jQuery('#name').val();
			if (!name){
				name = "there";
			}
			var referralLink = jQuery('#coachReferralLink').data('referrallink');
			referralLink.replace('"','');
			var coachName = "<?php echo $current_user->user_firstname . " " . $current_user->user_lastname?>"
			var coachEmail = "<?php echo $current_user->user_email ?>"
			var emailCopy = "<br>Hi " + name +",<br><br>I’ve just become a PBC coach. Here’s my referral link you can use to do the same: <a href='" + referralLink + "'>Link</a><br><br>To find out how PBC can enhance your business coaching and generate an income stream, check out the **explainer** video, **webinar** overview, **revenue** video and **coaches revenue calculator**.<br><br>Feel free to get in touch with me if you have any questions (" + coachEmail + ").<br><br>Regards,<br>" + coachName;

			jQuery('#messageContents').html(emailCopy);
		}

		jQuery('#sendInviteForm').submit(function(e) {
			e.preventDefault();

			$form = jQuery(this);
			//show some response on the button
			jQuery('button[type="submit"]', $form).each(function() {
				$btn = jQuery(this);
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
					email: jQuery('#email').val(),
					message: jQuery('#messageContents').html(),
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
				jQuery('#success_message_clients').show();
				jQuery('#email_clients').val('')
				jQuery('#name_clients').val('')
				jQuery('#error_message_clients').hide();
				updateEmailCopyClients();
			} else {
				jQuery('#error_message_clients').append('<ul></ul>');
				jQuery.each(data.errors,function(key,val){
					jQuery('#error_message_clients ul').append('<li>'+key+':'+val+'</li>');
				});
				jQuery('#success_message_clients').hide();
				jQuery('#error_message_clients').show();
			}

			//reverse the response on the button
			jQuery('button[type="button"]', $form).each(function() {
				$btn = jQuery(this);
				label = $btn.prop('orig_label');
				if(label) {
					$btn.prop('type','submit' );
					$btn.text(label);
					$btn.prop('orig_label','');
				}
			});
		}

		jQuery('#sendClientReferralLink').click(function(){
			var usedClientLicenses = parseInt(jQuery('#usedClientLicenses').text())
			var totalClientLicenses = parseInt(jQuery('#totalClientLicenses').text())

			console.log(usedClientLicenses)
			console.log(totalClientLicenses)
			if (usedClientLicenses == totalClientLicenses){
				jQuery('#notEnoughLicenses').modal('show');
			} else {
				//Update Email Copy
				updateEmailCopyClients();
				jQuery('#sendEmailPopupClients').modal('show');
			}
			
			
		})

		jQuery('#name_clients').on('input selectionchange propertychange',function(){
			//Update EMail Copy
			updateEmailCopyClients();
		})

		jQuery('#email_clients').on('input selectionchange propertychange',function(){
			//Update EMail Copy
			updateEmailCopyClients();
		})

		function updateEmailCopyClients(){
			var name = jQuery('#name_clients').val();
			if (!name){
				name = "there";
			}
			var referralLink = jQuery('#clientReferralLink').data('referrallink');
			referralLink.replace('"','');
			var coachName = "<?php echo $current_user->user_firstname . " " . $current_user->user_lastname?>"
			var coachEmail = "<?php echo $current_user->user_email;?>"
			var emailCopy = "<br>Hi " + name +",<br><br>I’m inviting you to join my PBC group so we can use this platform to supercharge your results. <br><br>Here’s my referral link you can use to join my group so we can collaborate and enhance your success: <a href='" + referralLink + "&bemail=" + jQuery('#email_clients').val() + "'>Link</a><br><br>Feel free to get in touch with me if you have any questions (" + coachEmail + ").<br><br>Regards,<br>" + coachName;

			jQuery('#messageContents_clients').html(emailCopy);
		}

		jQuery('#sendInviteFormClients').submit(function(e) {
			e.preventDefault();

			$form = jQuery(this);
			//show some response on the button
			jQuery('button[type="submit"]', $form).each(function() {
				$btn = jQuery(this);
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
					email: jQuery('#email_clients').val(),
					message: jQuery('#messageContents_clients').html(),
					coachName: coachName,
					emailType: "Client"
				},
				success: after_form_submitted_clients,
				dataType: 'json'
			});
		});

	
})


</script>
