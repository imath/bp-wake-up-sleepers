<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
*
*/
class BP_Wus {

	function __construct() {
		$this->includes();

		add_action( bp_core_do_network_admin() ? 'network_admin_menu' : 'admin_menu', array ( $this, 'admin_menu') , 10 );
	}

	function includes() {
		require BP_WUS_PLUGIN_DIR . '/includes/bp-wus-functions.php';

		if ( is_admin() ) {
			require BP_WUS_PLUGIN_DIR . '/includes/bp-wus-class.php';
			require BP_WUS_PLUGIN_DIR . '/includes/bp-wus-template.php';
			require BP_WUS_PLUGIN_DIR . '/includes/bp-wus-admin.php';
		}

	}

	function admin_menu() {
		$bp_wus_admin = add_menu_page( __('BP Wake Up Sleepers', 'bp-wake-up-sleepers'), __('BP Wake Up Sleepers', 'bp-wake-up-sleepers'), 'manage_options', 'bp-wus', 'bp_wus_admin', BP_WUS_PLUGIN_URL_IMG.'/alarm-18x18.png' );

		$bp_wus_admin_page = add_submenu_page( 'bp-wus', __( 'Options', 'bp-wake-up-sleepers' ), __( 'Options', 'bp-wake-up-sleepers' ), 'manage_options', 'bp-wus-options', 'bp_wus_admin_options' );

		add_action("load-$bp_wus_admin", array ( $this, 'admin_css_js') , 10 );
	}

	function admin_css_js() {
		wp_enqueue_style( 'bp-wus-style', BP_WUS_PLUGIN_URL_CSS . '/style.css' );
		wp_enqueue_script( 'bp-wus_script', BP_WUS_PLUGIN_URL_JS . '/script.js', array('jquery') );
		wp_localize_script('bp-wus_script', 'bp_wus_vars', array(
					'confirm'        => __('Are you sure ?','bp-wake-up-sleepers')
				)
			);

		add_action('media_buttons', array ( $this, 'bp_wus_editor_buttons'), 20 );
	}

	function bp_wus_editor_buttons() {

		$buttons = array(
					'bp-wus-favorites' => array(
										'path'  => 'includes/bp-wus-shortcode-editor.php',
										'class' => 'thickbox wus-insert-favorites',
										'title' =>  __('Add Favorites','bp-wake-up-sleepers') ),
					'bp-wus-save'      => array(
										'path'  => '#',
										'class' => 'wus-save',
										'title' => __('Save email','bp-wake-up-sleepers') ),
					'bp-wus-preview'      => array(
										'path'  => 'includes/bp-wus-email-preview.php',
										'class' => 'thickbox wus-preview',
										'title' =>  __('Preview email','bp-wake-up-sleepers') ),
				);

		foreach( $buttons as $button ) {
			$url = $class = $title = '';

			if( $button['path'] != '#' ) {
				$url = BP_WUS_PLUGIN_URL .'/'. $button['path'] .'?TB_iframe=true&amp;height=500&amp;width=640';
				if (is_ssl()) $url = str_replace( 'http://', 'https://',  $url );
			} else {
				$url = $button['path'];
			}

			$class = $button['class'];
			$title = $button['title'];

			echo '<a href="'.$url.'" class="button '.$class.'" title="'.$title.'"><span class="wus-button">'.$title.'</span></a>';
		}

	}
}
