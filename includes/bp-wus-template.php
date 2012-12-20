<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

class BP_Wus_Sleepers_Template {
	var $current_sleeper = -1;
	var $sleeper_count;
	var $sleepers;
	var $sleeper;

	var $in_the_loop;

	var $pag_page;
	var $pag_num;
	var $pag_links;
	var $total_sleeper_count;

	function __construct( $type, $page_number, $per_page, $page_arg = 'spage' ) {

		$this->pag_page = !empty( $_REQUEST[$page_arg] ) ? intval( $_REQUEST[$page_arg] ) : (int) $page_number;
		$this->pag_num  = !empty( $_REQUEST['num'] )   ? intval( $_REQUEST['num'] )   : (int) $per_page;
		$this->type     = $type;
		
		$this->sleepers = new BP_Alarm_Sleepers( $this->type, $this->pag_page, $this->pag_num );
		
		$this->total_sleeper_count = $this->sleepers->query['total'];
		$this->sleepers = $this->sleepers->query['sleepers'];
		
		$this->sleeper_count = count( $this->sleepers );
		
		if ( (int) $this->total_sleeper_count && (int) $this->pag_num ) {
			$this->pag_links = paginate_links( array(
				'base'      => add_query_arg( $page_arg, '%#%' ),
				'format'    => '',
				'total'     => ceil( (int) $this->total_sleeper_count / (int) $this->pag_num ),
				'current'   => (int) $this->pag_page,
				'prev_text' => _x( '&larr;', 'Sleeper pagination previous text', 'bp-wake-up-sleepers' ),
				'next_text' => _x( '&rarr;', 'Sleeper pagination next text', 'bp-wake-up-sleepers' ),
				'mid_size'   => 1
			) );
		}
		
	}

	function has_sleepers() {
		if ( $this->sleeper_count )
			return true;

		return false;
	}

	function next_sleeper() {
		$this->current_sleeper++;
		$this->sleeper = $this->sleepers[$this->current_sleeper];

		return $this->sleeper;
	}

	function rewind_sleepers() {
		$this->current_sleeper = -1;
		if ( $this->sleeper_count > 0 ) {
			$this->sleeper = $this->sleepers[0];
		}
	}

	function sleepers() {
		if ( $this->current_sleeper + 1 < $this->sleeper_count ) {
			return true;
		} elseif ( $this->current_sleeper + 1 == $this->sleeper_count ) {
			$this->rewind_sleepers();
		}

		$this->in_the_loop = false;
		return false;
	}

	function the_sleeper() {

		$this->in_the_loop = true;
		$this->sleeper      = $this->next_sleeper();

		// loop has just started
		if ( 0 == $this->current_sleeper )
			do_action( 'sleeper_loop_start' );
	}
}

function bp_wus_has_sleepers( $args = '' ) {
	global $sleepers_template;
	
	$type         = 'unactivated';
	$page         = 1;
	$per_page     = 20;
	
	$defaults = array( 'type' => $type, 'page' => $page, 'per_page' => $per_page );
	
	$r = wp_parse_args( $args, $defaults );
	extract( $r );
	
	$sleepers_template = new BP_Wus_Sleepers_Template( $type, $page, $per_page );
	
	return apply_filters( 'bp_wus_has_sleepers', $sleepers_template->has_sleepers(), $sleepers_template );
}

function bp_wus_the_sleeper() {
	global $sleepers_template;
	return $sleepers_template->the_sleeper();
}

function bp_wus_sleepers() {
	global $sleepers_template;
	return $sleepers_template->sleepers();
}

function bp_wus_sleepers_pagination_links() {
	echo bp_wus_sleepers_get_pagination_links();
}
	function bp_wus_sleepers_get_pagination_links() {
		global $sleepers_template;

		return apply_filters( 'bp_wus_sleepers_get_pagination_links', $sleepers_template->pag_links );
	}

function bp_wus_sleepers_id() {
	echo bp_wus_get_sleepers_id();
}
	function bp_wus_get_sleepers_id() {
		global $sleepers_template;
		return $sleepers_template->sleeper->uid;
	}

function bp_wus_sleepers_avatar() {
	echo bp_wus_get_sleepers_avatar();
}

	function bp_wus_get_sleepers_avatar() {
		global $sleepers_template;
		
		$avatar = get_avatar( $sleepers_template->sleeper->umail, 32 );
		
		return apply_filters( 'bp_wus_get_sleepers_avatar', $avatar );
	}

function bp_wus_sleepers_since() {
	echo bp_wus_get_sleepers_since();
}

	function bp_wus_get_sleepers_since() {
		global $sleepers_template;
		
		// d'abord il faut vérifier s'il existe !==false
		$since = $sleepers_template->sleeper->since;
		
		if( null === $since ) {
			$email_or_id = is_multisite() ? $sleepers_template->sleeper->umail : $sleepers_template->sleeper->uid ;
			$since = bp_wus_since_activation_date( $email_or_id );
		}
		
		if( empty( $since ) ) {
			$since = __('Unknown', 'bp-wake-up-sleepers');
		} else {
			$now = current_time('timestamp');
			$start = strtotime( $since );
			$since = human_time_diff( $start, $now );
		}
		
		return apply_filters('bp_wus_get_sleepers_since', $since, $sleepers_template->type );
	}

function bp_wus_sleepers_displayname() {
	echo bp_wus_get_sleepers_displayname();
}

	function bp_wus_get_sleepers_displayname() {
		global $sleepers_template;
		
		$display_name = $sleepers_template->sleeper->display_name;
		
		if( is_multisite() && 'unactivated' == $sleepers_template->type ) {
			$meta = maybe_unserialize( $display_name );
			$display_name = $meta['field_1'];
		}
		
		return apply_filters('bp_wus_get_sleepers_displayname', $display_name );
	}

function bp_wus_sleepers_umail() {
	echo bp_wus_get_sleepers_umail();
}

	function bp_wus_get_sleepers_umail() {
		global $sleepers_template;
	
		return apply_filters('bp_wus_get_sleepers_umail', $sleepers_template->sleeper->umail );
	}
	
	
function bp_wus_get_unsubcriber_type( $email = false ) {
	global $wpdb;
	
	$output = "";
	
	if( empty( $email ) )
		$output = '<td colspan="2">&nbsp;</td>';
		
	$user_id = $wpdb->get_var( $wpdb->prepare("SELECT ID FROM {$wpdb->users} WHERE user_email = %s", $email ) );
	
	// if no user_id, then it must be in signups !
	if( empty( $user_id ) && is_multisite() ) {
		$activation_key = $wpdb->get_var( $wpdb->prepare("SELECT activation_key FROM {$wpdb->signups} WHERE user_email = %s", $email ) );
		
		$output = '<td>'.__('Account not activated', 'bp-wake-up-sleepers').'</td><td><a href="#" class="button-primary delnot-m-active" data-activationkey="'.$activation_key.'" data-mail="'.$email.'">'.__('Delete registration', 'bp-wake-up-sleepers').'</a></td><td><a href="#" class="button-secondary delunsubscribe" data-mail="'.$email.'">'.__('Remove from unsubsribed list', 'bp-wake-up-sleepers').'</a></td>';
	} else {
		$activation_key = get_user_meta( $user_id, 'activation_key', true );
		
		if( !empty( $activation_key ) ) {
			$output = '<td>'.__('Account not activated', 'bp-wake-up-sleepers').'</td><td><a href="#" class="button-primary delnot-u-active" data-userid="'.$user_id.'" data-mail="'.$email.'">'.__('Delete registration', 'bp-wake-up-sleepers').'</a></td><td><a href="#" class="button-secondary delunsubscribe" data-mail="'.$email.'">'.__('Remove from unsubsribed list', 'bp-wake-up-sleepers').'</a></td>';
		} else {
			$last_activity = get_user_meta( $user_id, 'last_activity', true );
			
			if( empty( $last_activity ) ) {
				$output = '<td>'.__('Never logged in', 'bp-wake-up-sleepers').'</td><td><a href="#" class="button-primary delnot-u-active" data-userid="'.$user_id.'" data-mail="'.$email.'">'.__('Delete account', 'bp-wake-up-sleepers').'</a></td><td><a href="#" class="button-secondary delunsubscribe" data-mail="'.$email.'">'.__('Remove from unsubsribed list', 'bp-wake-up-sleepers').'</a></td>';
			} else {
				$output = '<td>'.__('Sleeping member', 'bp-wake-up-sleepers').'</td><td>&nbsp;</td><td><a href="#" class="button-secondary delunsubscribe" data-mail="'.$email.'">'.__('Remove from unsubsribed list', 'bp-wake-up-sleepers').'</a></td>';
			}
			
		}
	}
	
	echo $output;
}