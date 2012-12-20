<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/* If WordPress is not multisite, then the user's activation date is not stored.. Let's change this ! */

add_action('bp_core_activated_user', 'bp_wus_add_activation_date', 10, 3 );

function bp_wus_add_activation_date( $user_id, $key, $user ) {
	if( !is_multisite() ) {
		update_user_meta( $user_id, 'wus_activated_account', bp_core_current_time() );
	}
	return $usermeta;
}

function bp_wus_since_activation_date( $email_or_id ) {
	global $wpdb;
	
	if( empty( $email_or_id ) )
		return false;
		
	if( is_numeric( $email_or_id ) )
		return get_user_meta( $email_or_id, 'wus_activated_account', true );
	
	else
		return $wpdb->get_var( $wpdb->prepare( "SELECT activated FROM {$wpdb->signups} WHERE user_email = %s", $email_or_id ) );
}

add_shortcode( 'wus_favorites', 'bp_wus_activity_favorites');

function bp_wus_activity_favorites( $atts, $content = false ) {

	extract(shortcode_atts( array( 'ids' => 0 ), $atts) );
	$output = '';
	
	if( !empty( $ids ) ){
		if ( bp_has_activities( array( 'include' => $ids ) ) ) {
			$output = '<table cellpadding="0" cellspacing="0" border="0" align="center" id="activity">';
			while ( bp_activities() ) : bp_the_activity();
			
				$output .= '<tr><td><table><tr><td>&nbsp;</td><td width="50">'.bp_get_activity_avatar().'</td>';
				$output .='<td width="24" style="padding:0;margin:0"><img src="'.BP_WUS_PLUGIN_URL_IMG.'/talking.gif" alt=""></td><td bgcolor="#f1f1f1"><p>'.bp_get_activity_content_body().'</p><div style="clear:both"></div></td><td>&nbsp;</td></tr>';
				$output .='<tr><td>&nbsp;</td><td colspan="3" align="right"><div>'.bp_get_activity_action().'</div></td><td>&nbsp;</td></tr></table></td></tr>';
			
			endwhile;
			$output .= '</table>';
		}
	}

	return apply_filters('bp_wus_activity_favorites', $output );
		
}

function bp_wus_save_user_email_template( $content, $type = false ) {
	
	if( empty( $type ) )
		return false;
		
	if( empty( $content ) )
		return false;
	
	$content = apply_filters('content_save_pre', $content );
	$content = stripslashes( $content );
	
	update_option('bp_wus_user_email_template_'.$type, $content );
	return true;
	
}

/** this way, our unsubscribe page wont be visible in menus **/
add_filter( 'wp_list_pages_excludes', 'bp_wus_exclude_pages', 10, 1 );

function bp_wus_exclude_pages( $pages ) {
	
	$page_id = get_option( 'bp-wus-page' );
	
	if( !empty( $page_id ) )
		$pages[] = $page_id;
		
	return $pages;
}

add_filter('the_content', 'bp_wus_display_unsubscribe_choices', 99, 1);

function bp_wus_display_unsubscribe_choices( $content ) {
	$page_id = get_option( 'bp-wus-page' );
	
	if( !empty( $page_id ) && is_page( $page_id ) ){
		
		if( empty( $_GET['hash'] ) )
			return '<h4>' .__('OOps, we could not identify you.', 'bp-wake-up-sleepers' ) .'</h4>';
		
		else {
			return '<h4>' . bp_wus_add_sleeper_choices( $_GET['hash'] ) .'</h4>';
		}
		
	} else {
		return $content;
	}
}

function bp_wus_add_sleeper_choices( $hash = false ) {
	global $wpdb;
	
	$user_email = $wpdb->get_var( $wpdb->prepare("SELECT user_email FROM {$wpdb->users} WHERE MD5(user_email) = %s", $hash ) );
	
	
	// if no user_email, then it must be in signups !
	if( empty( $user_email ) && is_multisite() ) {
		$user_email = $wpdb->get_var( $wpdb->prepare("SELECT user_email FROM {$wpdb->signups} WHERE MD5(user_email) = %s", $hash ) );
	}
	
	if( !empty( $user_email ) ){
		$unsubscribed = get_option( 'bp_wus_unsubscribed' );
		
		if( empty( $unsubscribed) || ( is_array( $unsubscribed ) && !in_array( $user_email, $unsubscribed ) )  ) {
			$unsubscribed[] = $user_email;
			update_option( 'bp_wus_unsubscribed', $unsubscribed );
			
			return __('You successfully unsubscribed to this website', 'bp-wake-up-sleepers' );
		}
		
		if( !empty( $unsubscribed) && in_array( $user_email, $unsubscribed ) )
			return __('You are already unsubscribed to this website', 'bp-wake-up-sleepers' );
		
	} else {
		return __('OOps, we could not identify you.', 'bp-wake-up-sleepers' );
	}
}

function bp_wus_remove_user_email_from_unsubscribed( $unsubscribed, $user_email ) {
	$unsubscribed_updated = array();
	
	foreach( $unsubscribed as $sleeper_mail ) {
		if( $sleeper_mail != $user_email )
			$unsubscribed_updated[] = $sleeper_mail;
	}
	
	if( count( $unsubscribed_updated ) >= 1 )
		update_option( 'bp_wus_unsubscribed', $unsubscribed_updated );
	else
		delete_option( 'bp_wus_unsubscribed' );
}

/*
admin can remove users from unsubscribed list...
*/
function bp_wus_handle_ajax_ununsubscribe() {
	
	check_admin_referer( 'ununsubscribe', '_wpnonce_ununsubscribe' );
	
	$email = $_POST['user_email'];
	
	$unsubscribed = get_option( 'bp_wus_unsubscribed' );
	
	if( empty( $unsubscribed ) )
		_e('OOps, no unsubscribed user found', 'bp-wake-up-sleepers' );
		
	if( is_array( $unsubscribed ) && count( $unsubscribed ) >= 1 ) {
		bp_wus_remove_user_email_from_unsubscribed( $unsubscribed, $email );
		
		echo 1;
		
	} else {
		_e('OOps, unexpected error', 'bp-wake-up-sleepers' );
	}
	die();
}

add_action('wp_ajax_remove_from_unsubscribe_list', 'bp_wus_handle_ajax_ununsubscribe');

/* we need to create this one in order to delete signups on multisite configs */
function bp_wus_delete_signup( $activation_key ) {
	global $wpdb;
	
	$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->signups} WHERE activation_key = %s", $activation_key ) );
}


/* admin can also delete users if they unsubscribed 
and didn't activated their account or never logged in */
function bp_wus_handle_ajax_user_or_signup_delete() {
	
	check_admin_referer( 'ununsubscribe', '_wpnonce_ununsubscribe' );
	
	$user_id = intval( $_POST['user_id'] );
	$activation_key = $_POST['activation'];
	$email = $_POST['user_email'];
	$unsubscribed = get_option( 'bp_wus_unsubscribed' );
	
	if( is_numeric( $user_id ) && $user_id != 0 ) {
		
		bp_wus_remove_user_email_from_unsubscribed( $unsubscribed, $email );
		wp_delete_user( $user_id );
		echo 1;
		
	} elseif( !empty( $activation_key ) ) {
		
		bp_wus_remove_user_email_from_unsubscribed( $unsubscribed, $email );
		bp_wus_delete_signup( $activation_key );
		echo 1;
		
	} else {
		
		_e('OOps, unexpected error', 'bp-wake-up-sleepers' );
		
	}
	die();
}

add_action('wp_ajax_delete_user_or_signup', 'bp_wus_handle_ajax_user_or_signup_delete');

/*
!important if the admin deletes our unsubsribe page,
we also need to delete our bp-wus-page option...*/

function bp_wus_check_for_unsubscribe_page( $page_id ) {
	$unsubscribe_page = intval( get_option( 'bp-wus-page' ) );
	
	if( $page_id == $unsubscribe_page ) {
		delete_option( 'bp-wus-page' );
	}
}

add_action( 'after_delete_post', 'bp_wus_check_for_unsubscribe_page', 10, 1);