<?php 
global $wpdb, $pmpro_msg, $pmpro_msgt, $current_user;


if (!($current_user) || !($current_user->membership_level)){
	//Redirect
	wp_redirect("/");
	exit(0);
}

$pmpro_levels = pmpro_getAllLevels(false, true);
$pmpro_level_order = pmpro_getOption('level_order');

if(!empty($pmpro_level_order))
{
	$order = explode(',',$pmpro_level_order);

	//reorder array
	$reordered_levels = array();
	foreach($order as $level_id) {
		foreach($pmpro_levels as $key=>$level) {
			if($level_id == $level->id)
				$reordered_levels[] = $pmpro_levels[$key];
		}
	}

	$pmpro_levels = $reordered_levels;
}

$pmpro_levels = apply_filters("pmpro_levels_array", $pmpro_levels);

if($pmpro_msg)
{
?>
<div class="<?php echo pmpro_get_element_class( 'pmpro_message ' . $pmpro_msgt, $pmpro_msgt ); ?>"><?php echo $pmpro_msg?></div>
<?php
}
?>
<table id="pmpro_levels_table" class="<?php echo pmpro_get_element_class( 'pmpro_table pmpro_checkout', 'pmpro_levels_table' ); ?>">
<thead>
  <tr>
	<th><?php _e('Level', 'paid-memberships-pro' );?></th>
	<th><?php _e('Price', 'paid-memberships-pro' );?></th>	
	<th>&nbsp;</th>
  </tr>
</thead>
<tbody>
	<?php	
	$count = 0;
	$has_any_level = false;
	foreach($pmpro_levels as $level)
	{
		$user_level = pmpro_getSpecificMembershipLevelForUser( $current_user->ID, $level->id );
		$has_level = ! empty( $user_level );
		$has_any_level = $has_level ?: $has_any_level;
	?>
	<tr class="<?php if($count++ % 2 == 0) { ?>odd<?php } ?><?php if( $has_level ) { ?> active<?php } ?>">
		<td><?php echo $has_level ? "<strong>{$level->name}</strong>" : $level->name?></td>
		<td>
			<?php
				$cost_text = pmpro_getLevelCost($level, true, true); 
				$expiration_text = pmpro_getLevelExpiration($level);
				if(!empty($cost_text) && !empty($expiration_text))
					echo $cost_text . "<br />" . $expiration_text;
				elseif(!empty($cost_text))
					echo $cost_text;
				elseif(!empty($expiration_text))
					echo $expiration_text;
			?>
		</td>
		<td>
		<?php if ( ! $has_level ) { ?>                	
			<a class="<?php echo pmpro_get_element_class( 'pmpro_btn pmpro_btn-select', 'pmpro_btn-select' ); ?>" href="<?php echo pmpro_url("checkout", "?level=" . $level->id, "https")?>"><?php _e('Select', 'paid-memberships-pro' );?></a>
		<?php } else { ?>      
			<?php
				//if it's a one-time-payment level, offer a link to renew	
				if( pmpro_isLevelExpiringSoon( $user_level ) && $level->allow_signups ) {
					?>
						<a class="<?php echo pmpro_get_element_class( 'pmpro_btn pmpro_btn-select', 'pmpro_btn-select' ); ?>" href="<?php echo pmpro_url("checkout", "?level=" . $level->id, "https")?>"><?php _e('Renew', 'paid-memberships-pro' );?></a>
					<?php
				} else {
					?>
						<a class="<?php echo pmpro_get_element_class( 'pmpro_btn disabled', 'pmpro_btn' ); ?>" href="<?php echo pmpro_url("account")?>"><?php _e('Your&nbsp;Level', 'paid-memberships-pro' );?></a>
					<?php
				}
			?>
		<?php } ?>
		</td>
	</tr>
	<?php
	}
	?>
</tbody>
</table>

<?php
if ($current_user->membership_level->id==9){
	?>
	<div>
	At any time during your free training programme, you can upgrade your Coach (Training) membership to a full PBC Coach licence.
	<br><br>
	By upgrading to a full PBC Coaches licence, you will retain any progress made with the training, including certification if you have completed it. In addition, you will have full access to the PBC Assessment and all of the content available for coaches. 
	<br><br>
	If you upgrade to a full licence, any coaches you have referred to PBC will continue to be linked to you. This means you may be eligible to be paid referral income for any coaches you have referred to this date, assuming they have purchased a PBC Coaches Licence and any PBC Client Licences. Referral income is based on 10% of the revenue PBC receives from referred coaches.
	<br><br>
	If you choose to upgrade, you will be charged immediately for the first month of your PBC Coaches Licence.
	<br><br>
	After completing the training your membership will be paused automatically. To continue to use PBC at the end of your free training, you will need to purchase a PBC Coaches Licence. You can choose to purchase a PBC Coaches Licence immediately. However, if you need more time, you can leave your membership on pause for up to 6 months if needed.
	</div>
<?php 
} elseif ($current_user->membership_level->id==7 || $current_user->membership_level->id==8) {
	?>
	<div>
	For each coach you refer to PBC, who purchases a PBC Coaches Licences, you receive a monthly 10% referral payment for the licences the coach purchases for a period of 12 months from the date the coach signs up to PBC, as long as the coach maintains an active PBC Coaches Licence. 
	<br><br>
	If you change your membership level to a Coach, you will continue to be paid referral income for any coaches you have referred to this date, who have purchased a PBC Coaches Licence. The payment for these referrals will continue to be paid under your existing agreement. Meaning referral payments will be made for your existing referrals until the 12-month expiration of the referred coaches sign-up date.
	<br><br>
	If you choose to change your membership level to a Coach, you are agreeing to the above. In addition, it will involve signing-up as a Coach and commencing your PBC Coaches Licence subscription. 
	</div>
<?php
}
?>
<br>
<p class="<?php echo pmpro_get_element_class( 'pmpro_actions_nav' ); ?>">
	<a href="<?php echo pmpro_url("account")?>" id="pmpro_levels-return-account"><?php _e('&larr; Return to Your Account', 'paid-memberships-pro' );?></a>
</p> <!-- end pmpro_actions_nav -->
