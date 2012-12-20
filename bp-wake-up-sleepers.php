<?php
/* 
Plugin Name: BP wake up sleepers
Plugin URI: http://imath.owni.fr/2012/12/20/bp-wake-up-sleepers/
Description: Need to wake the sleeping users of your BuddyPress community ?
Version: 1.0-beta1
Author: imath
Author URI: http://imath.owni.fr
License: GPLv2
Network: true
Text Domain: bp-wake-up-sleepers
Domain Path: /languages/
*/

define ( 'BP_WUS_PLUGIN_NAME', 'bp-wake-up-sleepers' );
define ( 'BP_WUS_PLUGIN_URL',  plugins_url('' , __FILE__) );
define ( 'BP_WUS_PLUGIN_URL_JS',  plugins_url('js' , __FILE__) );
define ( 'BP_WUS_PLUGIN_URL_CSS',  plugins_url('css' , __FILE__) );
define ( 'BP_WUS_PLUGIN_URL_IMG',  plugins_url('images' , __FILE__) );
define ( 'BP_WUS_PLUGIN_DIR',  WP_PLUGIN_DIR . '/' . basename( dirname( __FILE__ ) ) );
define ( 'BP_WUS_PLUGIN_VERSION', '1.0-beta1');

add_action('bp_include', 'bp_wus_init');

function bp_wus_init() {
	global $bp;

	require( BP_WUS_PLUGIN_DIR . '/includes/bp-wus-loader.php' );

	$bp_wus = new BP_Wus;

}

function bp_wus_install(){
	if( BP_WUS_PLUGIN_VERSION != get_option( 'bp-wus-version' ) ){
		
		$page_id = get_option( 'bp-wus-page' );
		
		if( empty( $page_id ) ){
			$page_id = wp_insert_post( array( 'comment_status' => 'closed', 'ping_status' => 'closed', 'post_title' => 'Unsubscribe', 'post_status' => 'publish', 'post_type' => 'page' ) );
			
			update_option( 'bp-wus-page', $page_id );
		}

		update_option( 'bp-wus-version', BP_WUS_PLUGIN_VERSION );
	}
}

register_activation_hook( __FILE__, 'bp_wus_install' );


/**
* bp_checkins_load_textdomain
* translation!
* 
*/
function bp_wus_load_textdomain() {

	// try to get locale
	$locale = apply_filters( 'bp_wus_load_textdomain_get_locale', get_locale() );

	// if we found a locale, try to load .mo file
	if ( !empty( $locale ) ) {
		// default .mo file path
		$mofile_default = sprintf( '%s/languages/%s-%s.mo', BP_WUS_PLUGIN_DIR, BP_WUS_PLUGIN_NAME, $locale );
		// final filtered file path
		$mofile = apply_filters( 'bp_wus_load_textdomain_mofile', $mofile_default );
		// make sure file exists, and load it
		if ( file_exists( $mofile ) ) {
			load_textdomain( BP_WUS_PLUGIN_NAME, $mofile );
		}
	}
}
add_action ( 'init', 'bp_wus_load_textdomain', 8 );
