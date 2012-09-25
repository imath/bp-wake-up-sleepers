<?php
/* 
Plugin Name: BP Wake Up Sleepers
Plugin URI: http://imath.owni.fr/
Description: Need to wake up your sleeping users ?
Version: 0.1
Author: imath
Author URI: http://imath.owni.fr
License: GPLv2
Network: true
*/

function bp_wus_list_unactivated_users() {
	global $wpdb;
	
	if( is_multisite() ) {
		return $wpdb->get_results("SELECT user_email as umail, activation_key as uid FROM {$wpdb->signups} WHERE active = 0", OBJECT);
	} else {
		return $wpdb->get_results("SELECT user_email as umail, meta_value as activation_key, user_id as uid FROM {$wpdb->usermeta} m LEFT JOIN {$wpdb->users} a ON(m.user_id = a.ID)  WHERE meta_key = 'activation_key'", OBJECT);
	}
}

function bp_wus_list_never_loggedin_users() {
	global $wpdb;
	
	if( is_multisite() ) {
		
		return $wpdb->get_results("SELECT u.user_email as umail, u.ID as uid, m.meta_value FROM {$wpdb->users} u LEFT JOIN {$wpdb->usermeta} m ON(m.user_id = u.ID) AND meta_key = 'last_activity' WHERE meta_value is null AND u.user_email NOT IN( SELECT su.user_email FROM {$wpdb->signups} su WHERE su.active = 0)", OBJECT);
		
	} else {
		
		return $wpdb->get_results("SELECT u.user_email as umail, u.ID as uid, m.meta_value FROM {$wpdb->users} u LEFT JOIN {$wpdb->usermeta} m ON(m.user_id = u.ID) AND meta_key = 'last_activity' WHERE meta_value is null AND u.ID NOT IN( SELECT mm.user_id FROM {$wpdb->usermeta} mm WHERE mm.meta_key = 'activation_key')", OBJECT);
		
	}
}

function bp_wus_list_sleeping_users( $days = 30 ) {
	global $wpdb;
	
	$days = apply_filters( 'bp_wus_list_sleeping_users_days', $days );
	
	if( empty( $days ) )
		return false;
	
	$from = bp_core_current_time();
	
	return $wpdb->get_results("SELECT m.user_id as uid, u.user_email as umail, m.meta_value as last_activity FROM {$wpdb->usermeta} m LEFT JOIN {$wpdb->users} u ON(m.user_id = u.ID) WHERE m.meta_key = 'last_activity' AND DATEDIFF( '{$from}', m.meta_value ) > {$days} ORDER BY last_activity ASC", OBJECT);
}


add_action( is_multisite() ? 'network_admin_menu' : 'admin_menu', 'bp_wus_administration_menu', 21);

function bp_wus_administration_menu() {
	global $bp, $bp_wus_admin_page;

	if ( !$bp->loggedin_user->is_site_admin )
		return false;
		
	$admin_page = bp_wus_16_new_admin();
	
	if( $admin_page == 'bp-general-settings.php' )
		$submenu = 'bp-general-settings';
	else
		$submenu = $admin_page;
		
	$bp_wus_admin_page = add_submenu_page( $submenu, __( 'BP Wake Up Sleepers', 'bp-wake-up-sleepers' ), __( 'BP Wake Up Sleepers', 'bp-wake-up-sleepers' ), 'manage_options', 'bp-wus', 'bp_wus_settings_admin' );
		
		
	add_action("load-$bp_wus_admin_page", 'bp_wus_admin_css');
}

function bp_wus_admin_tabs( $active_tab = '' ) {

	// Declare local variables
	$tabs_html    = '';
	$idle_class   = 'nav-tab';
	$active_class = 'nav-tab nav-tab-active';
	$admin_page = bp_wus_16_new_admin();
	
	if( $admin_page == 'bp-general-settings.php' )
		$admin_page = 'admin.php';

	// Setup core admin tabs
	$tabs = array(
		'0' => array(
			'href' => bp_get_admin_url( add_query_arg( array( 'page' => 'bp-wus' ), $admin_page ) ),
			'name' => __( 'Unactivated users', 'bp-wake-up-sleepers' )
		),
		'1' => array(
			'href' => bp_get_admin_url( add_query_arg( array( 'page' => 'bp-wus', 'tab' => 'nli-users'   ), $admin_page ) ),
			'name' => __( 'Never loggedin users', 'bp-wake-up-sleepers' )
		),
		'2' => array(
			'href' => bp_get_admin_url( add_query_arg( array( 'page' => 'bp-wus', 'tab' => 'sleepers'   ), $admin_page ) ),
			'name' => __( 'Sleeping users', 'bp-wake-up-sleepers' )
		)
	);

	// Loop through tabs and build navigation
	foreach( $tabs as $tab_id => $tab_data ) {
		$is_current = (bool) ( $tab_data['name'] == $active_tab );
		$tab_class  = $is_current ? $active_class : $idle_class;
		$tabs_html .= '<a href="' . $tab_data['href'] . '" class="' . $tab_class . '">' . $tab_data['name'] . '</a>';
	}

	// Output the tabs
	echo $tabs_html;
}

function bp_wus_settings_admin() {
	$active = __( 'Unactivated users', 'bp-wake-up-sleepers' );
	
	if($_GET['tab'] == 'nli-users' ) {
		
		$active = __( 'Never loggedin users', 'bp-wake-up-sleepers' );
		$list_users = bp_wus_list_never_loggedin_users();
		
	} else if($_GET['tab'] == 'sleepers' ) {
		
		$active = __( 'Sleeping users', 'bp-wake-up-sleepers' );
		$list_users = bp_wus_list_sleeping_users();
		
	} else {
		
		$list_users = bp_wus_list_unactivated_users();
		
	}
	do_action('bp_wus_settings_admin', $list_users, $_GET['tab'] );
	
	$admin_page = bp_wus_16_new_admin();
	
	if( $admin_page == 'bp-general-settings.php' )
		$admin_page = 'admin.php';
	?>
	<div class="wrap">
		<h2 class="nav-tab-wrapper"><?php bp_wus_admin_tabs( $active );?></h2>
		
		<form action="" method="post" id="bp-admin-form">
			
			<table class="form-table">
				<tr>
					<th><label for="_bp_wus_mail_message"><?php _e( 'Your message', 'bp-wake-up-sleepers' );?></label></th>
					<td><textarea name="_bp_wus_mail_message" id="_bp_wus_mail_message" cols="80" rows="3"><?php do_action('bp_wus_get_mail_template', $_GET['tab']);?></textarea></td>
				</tr>
				<tr>
					<td colspan="2"><p><input type="submit" class="button-primary" value="Send mail" name="_bp_wus_submit"></p></td>
				</tr>
			</table>
			
			<table class="widefat">
				<thead>
					<tr><th><?php _e( 'User ID', 'bp-wake-up-sleepers' );?></th><th><?php _e( 'email', 'bp-wake-up-sleepers' );?></th></tr>
				</thead>
				
				<tbody>
					<?php if( count( $list_users ) >= 1 ):?>
						
						<?php foreach( $list_users as $user ) :?>
							<tr><td><?php echo $user->uid;?></td><td><?php echo $user->umail;?></td></tr>
						<?php endforeach;?>
						
					<?php else:?>
						<tr><td colspan="2"><?php _e( 'No user to display', 'bp-wake-up-sleepers' );?></td></tr>
					<?php endif;?>
					
				</tbody>
			
				<tfoot>
					<tr><th><?php _e( 'User ID', 'bp-wake-up-sleepers' );?></th><th><?php _e( 'email', 'bp-wake-up-sleepers' );?></th></tr>
				</tfoot>
			</table>
			
		</form>
	</div>
	<?php
}

add_action('bp_wus_get_mail_template','bp_wus_mail_template' , 1, 1);

function bp_wus_mail_template( $tab = '' ) {
	if( empty( $tab) )
		echo bp_wus_return_template();
	else
		echo bp_wus_return_template( $tab );
}

function bp_wus_return_template( $type = "unactivated" ) {
	$template = get_option('bp_wus_template_'.$type);
	
	return apply_filters( 'bp_wus_return_template', $template, $type);
}

function bp_wus_16_new_admin(){
	if( defined( 'BP_VERSION' ) && version_compare( BP_VERSION, '1.6', '>=' ) ){
		$page  = bp_core_do_network_admin()  ? 'settings.php' : 'options-general.php';
		return $page;
	}
	else return 'bp-general-settings.php';
}

function bp_wus_admin_css(){
	// in case i need to add custom css.
	return true;
}

add_action('bp_wus_settings_admin', 'bp_wus_mail_users', 1, 2);

function bp_wus_mail_users( $users, $type = "unactivated" ) {
	
	if( empty( $type ) )
		$type = "unactivated";

	if( empty( $_POST['_bp_wus_submit']) )
		return false;
		
	$message = wp_kses( $_POST['_bp_wus_mail_message'], array() );
	
	if ( empty( $message) || empty( $users ) ) {
		echo '<div id="message" class="error"><p>' . __( 'Aïe Caramba ! something went wrong', 'bp-wake-up-sleepers' ) . '</p></div>';
	}	
	else {
		update_option( 'bp_wus_template_'.$type, $message );
		
		//
		
		if( is_array( $users) ) {
			foreach( $users as $user ) {
				
				$key = is_multisite() ? $user->uid : $user->activation_key ;
				
				bp_wus_send_mail_to( $user->uid, $user->umail, $message, $type, $key );

			}
			
		} else {
			
			$key = is_multisite() ? $users->uid : $users->activation_key ;
			
			bp_wus_send_mail_to( $users->uid, $users->umail, $message, $type, $key );
			
		}
		
		echo '<div id="message" class="updated"><p>Mail sent</p></div>';
	}
}


function bp_wus_send_mail_to( $user_id, $user_email, $message, $type, $key = '' ) {
	
	$extra_link = '';
	
	if( !empty( $key ) && $type == "unactivated" )
		$extra_link = "\n\n________________\n" . sprintf( __('Activate your account : %s', 'bp-wake-up-sleepers'), bp_get_activation_page() . '?key='.$key ) ;
	
	$user = get_userdata( $user_id );
	
	if( !empty( $user) )
		$to = $user->user_email;
		
	else
		$to = $user_email;
	
	$from_name = ( '' == get_option( 'blogname' ) ) ? __( 'BuddyPress', 'buddypress' ) : esc_html( get_option( 'blogname' ) );
	
	// edit the message below to suit your needs !
	$message .= $extra_link;
	
	$message = apply_filters( 'bp_wus_send_mail_message', $message );
	
	// edit the subject below to suit your needs !
	$subject = apply_filters( 'bp_wus_send_mail_from', '[' . $from_name . '] ' . __( 'Info!', 'bp-wake-up-sleepers' ), $type);
	
	
	wp_mail( $to, $subject, $message );

}