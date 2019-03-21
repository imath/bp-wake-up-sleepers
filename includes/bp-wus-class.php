<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
* BP Alarm Sleepers :
* list sleepers depending on the 3 possible requests :
* - account not activated
* - users who never logged in
* - users who posted their last activity more than 30 days ago.
* Handles email personalization, email preview and email sending
* 
* filters available :
* - bp_wus_list_sleeping_users_days to change the 30 days period
* - bp_wus_select_sleepers to change the select query
* - bp_wus_total_sleepers to change the count query
* - bp_wus_email_subject_'.$this->type to change the subjet of the email depending on the type of sleepers
*/
class BP_Alarm_Sleepers
{
	
	var $type;
	var $query;
	var $per_page;
	var $page;
	var $mail_results;
	
	function __construct( $type = false, $page = false, $per_page = false )
	{
		if( empty( $type ) )
			$this->type = 'unactivated';
		else
			$this->type = $type;
		
		$this->page = $page;
		$this->per_page = $per_page;
			
		$this->populate();
	}
	
	function populate()
	{
		global $wpdb;
		$from = bp_core_current_time();
		
		switch ( $this->type ) {
			
			case 'sleeping_buddies' :
				$days = apply_filters( 'bp_wus_list_sleeping_users_days', 30 );
				$sql['select']  = "SELECT m.user_id as uid, u.user_email as umail, m.meta_value as last_activity, u.user_login, u.display_name, m.meta_value as since";
				$sql['count']   = "SELECT COUNT(m.user_id)";
				$sql['from']    = "FROM {$wpdb->usermeta} m LEFT JOIN {$wpdb->users} u ON(m.user_id = u.ID)";
				$sql['where'][] = $wpdb->prepare( "m.meta_key = %s", 'last_activity' );
				$sql['where'][] = "DATEDIFF( '{$from}', m.meta_value ) > {$days}";
				$sql['orderby'] = "ORDER BY since";
				$sql['order']   = "ASC";
				$sql['having']  = "u.user_email";

				break;
			
			case 'never_loggedin' :
				$sql['select']  = "SELECT u.user_email as umail, u.ID as uid, m.meta_value as latest_activity, u.user_login, u.display_name";
				$sql['count']   = "SELECT COUNT(u.user_email)";
				$sql['from']    = "FROM {$wpdb->users} u LEFT JOIN {$wpdb->usermeta} m ON(m.user_id = u.ID) AND meta_key = 'last_activity'";
				$sql['where'][] = "meta_value is null";
				
				if( is_multisite() )
					$sql['where'][] = "u.user_email NOT IN( SELECT su.user_email FROM {$wpdb->signups} su WHERE su.active = 0)";
				else
					$sql['where'][] = "u.ID NOT IN( SELECT mm.user_id FROM {$wpdb->usermeta} mm WHERE mm.meta_key = 'activation_key')";
					
				$sql['orderby'] = "ORDER BY uid";
				$sql['order']   = "DESC";
				$sql['having']  = "u.user_email";

				break;
			
			default :
				if( is_multisite() ) {
					$sql['select']  = "SELECT user_email as umail, activation_key, 0 as uid, user_login, meta as display_name, registered as since";
					$sql['count']   = "SELECT COUNT(user_email)";
					$sql['from']    = "FROM {$wpdb->signups}";
					$sql['where'][] = "active = 0";
					$sql['having']  = "user_email";
				} else {
					$sql['select']  = "SELECT u.user_email as umail, meta_value as activation_key, user_id as uid, user_login, display_name, u.user_registered as since";
					$sql['count']   = "SELECT COUNT(u.user_email)";
					$sql['from']    = " FROM {$wpdb->usermeta} m LEFT JOIN {$wpdb->users} u ON(m.user_id = u.ID)";
					$sql['where'][] = "meta_key = 'activation_key'";
					$sql['having']  = "u.user_email";
				}
				
				$sql['orderby'] = "ORDER BY since";
				$sql['order']   = "ASC";

				break;
		}
		
		// Checking if unsubscribed users
		$unsubscribed = get_option( 'bp_wus_unsubscribed' );
		if( !empty( $unsubscribed ) && is_array( $unsubscribed ) && count( $unsubscribed ) > 0 )
			$sql['where'][] = "{$sql['having']} NOT IN('".implode("','", $unsubscribed )."')";
		
		$sql['where'] = ! empty( $sql['where'] ) ? 'WHERE ' . implode( ' AND ', $sql['where'] ) : '';
		
		if ( !empty( $this->per_page ) && !empty( $this->page ) ) {

			// Make sure page values are absolute integers
			$page     = absint( $this->page );
			$per_page = absint( $this->per_page );

			$sql['limit']    = $wpdb->prepare( "LIMIT %d, %d", absint( ( $page - 1 ) * $per_page ), $per_page );
			
			$select_sleepers = apply_filters( 'bp_wus_select_sleepers', "{$sql['select']} {$sql['from']} {$sql['where']} {$sql['orderby']} {$sql['order']} {$sql['limit']}", $sql );
		} else {
			$select_sleepers = apply_filters( 'bp_wus_select_sleepers', "{$sql['select']} {$sql['from']} {$sql['where']} {$sql['orderby']} {$sql['order']}", $sql );
		}
		
		$total_sleepers  = apply_filters( 'bp_wus_total_sleepers', "{$sql['count']} {$sql['from']} {$sql['where']}", $sql );
		
		$query_sleepers = $wpdb->get_results( $select_sleepers, OBJECT );
		
		$count_sleepers = $wpdb->get_var( $total_sleepers );
		
		if( $query_sleepers )
			$this->query = array( 'sleepers' => $query_sleepers, 'total' => (int) $count_sleepers );
	}
	
	function get_email_template( $type = 'unactivated', $preview = false )
	{
		
		$which_template = (int)bp_get_option( 'bp-wus-which-template' ) ? 'email-light.html' : 'email-full.html';
		
		$email_template = file_get_contents( BP_WUS_PLUGIN_DIR . '/email-template/' . $which_template  );
		
		$message_email = get_option( 'bp_wus_user_email_template_'.$type );
		$message_email = apply_filters('the_content', $message_email );
		
		if( empty( $message_email ) )
			$message_email = __('Please add some content to this email thanks to the Compose Mail tab', 'bp-wake-up-sleepers');
			
		$pagetitle = esc_attr( get_bloginfo( 'name', 'display' ) );
		$email = str_replace('{{pagetitle}}', $pagetitle, $email_template);
		
		
		$email = str_replace( '{{content}}', $message_email, $email );

		if( $which_template == 'email-full.html' ) {
			if( get_header_image() && 1 == get_option('bp-wus-enable-theme-header-image') )
				$header = '<a href="'.site_url().'" title="'.get_bloginfo('name').'"><img src="'.get_header_image().'" style="max-width:100%"></a>';

			else 
				$header = '<div style="margin:5px;"><h2><a href="'.site_url().'" title="'.get_bloginfo('name').'">'.get_bloginfo('name').'</a></h2><p>'.get_bloginfo('description').'</p></div>';
				
			$email = str_replace('{{header}}', $header, $email );
				
			$footer = '<div align="center"><span><a href="'.site_url().'" title="'.get_bloginfo('name').'">'.get_bloginfo('name').'</a></span> {{unsubscribelink}}</div>'; 
			$email = str_replace('{{footer}}', $footer, $email );
		}
		
		if( !empty( $preview ) ) {
			if ( isset( $this->query['sleepers'][0]->display_name ) ) {
				$display_name = $this->query['sleepers'][0]->display_name;
			}
			
			if( is_multisite() && 'unactivated' == $type ) {
				$meta = maybe_unserialize( $display_name );
				$display_name = $meta['field_1'];
			}
			
			if ( isset($display_name ) ) {
				$email = str_replace('{{displayname}}', $display_name, $email );
			}
			$email = str_replace('{{activationlink}}', '<a href="#">'.__('activate your account', 'bp-wake-up-sleepers').'</a>', $email );
			if ( isset( $this->query['sleepers'][0]->user_login ) ) {
				$email = str_replace('{{userlogin}}', $this->query['sleepers'][0]->user_login, $email );
			}
			$email = str_replace('{{memberlink}}', '<a href="#">'.__('profile page', 'bp-wake-up-sleepers').'</a>', $email );
			$email = str_replace('{{unsubscribelink}}', '| <span><a href="#" title="'.__('Unsubscribe', 'bp-wake-up-sleepers').'">'.__('Unsubscribe', 'bp-wake-up-sleepers').'</a></span>', $email );
		}
		
		return $email;
	}
	
	function preview_mail() {
		echo $this->get_email_template( $this->type, 1 );
	}
	
	function send_mails( $admin_email = false )
	{

		$sleepers = $this->query['sleepers'];
		$log = array( 'ok' => 0, 'ko' => 0, 'komails' => array() );
		$message = $this->get_email_template( $this->type );
		$title = '['.esc_html( get_option( 'blogname' ) ).']';
		$unsubscribe = apply_filters('bp_wus_unsubscribe_id', intval( get_option( 'bp-wus-page' ) ) );
		
		if( !is_array( $sleepers ) || count( $sleepers ) < 1 ) {
			echo '<li>'.__('OOps, no user seems to match your request!', 'bp-wake-up-sleepers') .'</li>';
			return false;
		}
		
		$adminmail = get_bloginfo('admin_email');
		$from_name = get_bloginfo('name');
		
		$message_headers = "MIME-Version: 1.0\n" . "From: \"{$from_name}\" <{$adminmail}>\n" . "Content-Type: text/html; charset=\"" . get_option( 'blog_charset' ) . "\"\n";
		
		foreach( $sleepers as $sleeper ) {
			
			//initialize datas
			$to = "";
			$subject = "";
			$email_content = "";
			$display_name = "";
			
			$to = empty( $admin_email ) ? $sleeper->umail : $admin_email ;
			
			switch ( $this->type ) {

				case 'unactivated' :
				
					if( is_multisite() ) {
						$meta = maybe_unserialize( $sleeper->display_name );
						$display_name = $meta['field_1'];
					} else {
						$display_name = $sleeper->display_name;
					}
					$email_content = str_replace('{{displayname}}', $display_name, $message );
					
					if( empty( $admin_email ) ) {
						$email_content = str_replace('{{activationlink}}', '<a href="'.bp_get_activation_page() . '?key='.$sleeper->activation_key.'">'.__('activate your account', 'bp-wake-up-sleepers').'</a>', $email_content );
					}
					$email_content = str_replace('{{userlogin}}', $sleeper->user_login, $email_content );
						
					$subject = apply_filters( 'bp_wus_email_subject_'.$this->type, $title . ' ' .__('activation reminder', 'bp-wake-up-sleepers') );

					break;

				default :
					
					$email_content = str_replace('{{displayname}}', $sleeper->display_name, $message );
					$email_content = str_replace('{{memberlink}}', '<a href="'.bp_core_get_user_domain( $sleeper->uid ).'">'.__('profile page', 'bp-wake-up-sleepers').'</a>', $email_content );
					$email_content = str_replace('{{userlogin}}', $sleeper->user_login, $email_content );
					$subject = apply_filters( 'bp_wus_email_subject_'.$this->type, $title . ' ' .__('come visit us !', 'bp-wake-up-sleepers') );

					break;
			}
			
			/* this way if people dont want to allow sleepers to unsubscribe, 
			they just need to delete the unsubscribe page*/
			
			if( !empty( $unsubscribe ) && empty( $admin_email ) ) {
				$unsubscribe_link = '| <span><a href="'.get_permalink( $unsubscribe ).'?hash='.md5( $to ).'" title="'.__('Unsubscribe', 'bp-wake-up-sleepers').'">'.__('Unsubscribe', 'bp-wake-up-sleepers').'</a></span>';
				
				$email_content = str_replace('{{unsubscribelink}}', $unsubscribe_link, $email_content );
			} else {
				$email_content = str_replace('{{unsubscribelink}}', '', $email_content );
			}
				
			
			$mail = wp_mail( $to, $subject, $email_content, $message_headers );
			
			if( $mail ) {
				echo '<li>' . $to .' : mail sent.<li>';
				$log['ok'] += 1;  
			} else {
				echo '<li>' . $to .' : error.<li>';
				$log['ko'] += 1;
				$log['komails'][] = $to;
			}
			
			$this->mail_results = $log;
			
			if( empty( $admin_email ) ) {
				$log_indb = array( 'date' => bp_core_current_time(), 'results' => $log );
				update_option( 'bp_wus_last_mailing_' . $this->type , $log_indb );
				
				// restrict spammers!
				set_transient( 'bp_wus_last_mailing_' . $this->type, 1, 60 * 60 * 24 );
			}
	
		}
	}
}
