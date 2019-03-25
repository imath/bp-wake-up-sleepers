<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

function bp_wus_admin_tabs( $active_tab = '' ) {

	// Declare local variables
	$tabs_html    = '';
	$idle_class   = 'nav-tab';
	$active_class = 'nav-tab nav-tab-active';
	$admin_page = 'admin.php';

	// Setup core admin tabs
	$tabs = array(
		'0' => array(
			'href' => bp_get_admin_url( add_query_arg( array( 'page' => 'bp-wus' ), $admin_page ) ),
			'name' => __( '1- Select Sleepers', 'bp-wake-up-sleepers' )
		),
		'1' => array(
			'href' => bp_get_admin_url( add_query_arg( array( 'page' => 'bp-wus', 'tab' => 'template'   ), $admin_page ) ),
			'name' => __( '2- Compose Mail', 'bp-wake-up-sleepers' )
		),
		'2' => array(
			'href' => bp_get_admin_url( add_query_arg( array( 'page' => 'bp-wus', 'tab' => 'send'   ), $admin_page ) ),
			'name' => __( '3- Send Mails', 'bp-wake-up-sleepers' )
		),
		'3' => array(
			'href' => bp_get_admin_url( add_query_arg( array( 'page' => 'bp-wus', 'tab' => 'manage'   ), $admin_page ) ),
			'name' => __( '4- Manage Unsubscribed', 'bp-wake-up-sleepers' )
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


function bp_wus_admin() {
	global $allowedtags;

	$class_selbox = '';
	$bp_wus_message = '';
	$class = 'hide';
	$active = __( '1- Select Sleepers', 'bp-wake-up-sleepers' );
	if ( isset( $_GET['sleepers-type'] ) ) {
		$sleepers_type = $_GET['sleepers-type'];
	}

	if( ! isset( $sleepers_type) || empty( $sleepers_type ) )
		$sleepers_type = 'unactivated';

	if( isset( $_POST["email_content"] ) ) {
		if ( !check_admin_referer('bp-wus-template-save') )
			return false;

		if( bp_wus_save_user_email_template( $_POST["email_content"], $sleepers_type) ) {
			$bp_wus_message = __( 'Email saved', 'bp-wake-up-sleepers' );
		} else {
			$bp_wus_message = __( 'Oops something went wrong', 'bp-wake-up-sleepers' );
		}

		$class = "show-info";

	}
	if( isset( $_GET['tab'] ) && $_GET['tab'] == 'template' ) {

		$active = __( '2- Compose Mail', 'bp-wake-up-sleepers' );
		$email_content = get_option( 'bp_wus_user_email_template_'.$sleepers_type );
		$email_content = format_to_edit($email_content, user_can_richedit());

		$args = array("textarea_name" => "email_content",
			'wpautop' => true,
			'media_buttons' => true,
			'textarea_rows' => get_option('default_post_edit_rows', 10),
			'teeny' => false,
			'dfw' => false,
			'tinymce' => true,
			'quicktags' => true
		);

	} else if( isset( $_GET['tab'] ) && $_GET['tab'] == 'send' ) {

		$active = __( '3- Send Mails', 'bp-wake-up-sleepers' );

	} else if( isset( $_GET['tab'] ) && $_GET['tab'] == 'manage' ) {

		$active = __( '4- Manage Unsubscribed', 'bp-wake-up-sleepers' );
		$unsubscribed = get_option( 'bp_wus_unsubscribed' );
		$class_selbox = 'class="hide"';

	} else {

		$args = array( 'type' => $sleepers_type );
		$previous_mailing = get_option( 'bp_wus_last_mailing_' .$sleepers_type );
	}
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Wake up sleepers', 'bp-wake-up-sleepers' );?></h1>
		<h2 class="nav-tab-wrapper"><?php bp_wus_admin_tabs( $active );?></h2>

		<p <?php echo $class_selbox;?>>
			<label for="sleepers-type"><?php _e('Select the type of sleepers', 'bp-wake-up-sleepers');?></label>
			<select name="sleepers-type" id="sleepers-type">
				<option value="unactivated" <?php if( !$sleepers_type || $sleepers_type == 'unactivated') echo 'selected';?> ><?php _e('Not activated', 'bp-wake-up-sleepers');?></option>
				<option value="never_loggedin" <?php if( $sleepers_type == 'never_loggedin') echo 'selected';?> ><?php _e('Never logged in', 'bp-wake-up-sleepers');?></option>
				<option value="sleeping_buddies" <?php if( $sleepers_type == 'sleeping_buddies') echo 'selected';?> ><?php _e('Sleeping Buddies', 'bp-wake-up-sleepers');?></option>
			</select>
		</p>

		<?php if( isset( $_GET['tab'] ) && $_GET['tab'] == 'template' ):?>

			<div id="message" class="updated <?php echo $class;?>">
				<p><?php echo $bp_wus_message; ?></p>
			</div>

			<form id="bp-wus-template-form" action="" method="post">

				<div id="editor_container">
					<?php wp_editor( $email_content, "email_editor", $args );?>
				</div>

				<div id="help">
					<h4><?php _e('Some code to customize your email', 'bp-wake-up-sleepers');?></h4>
					<p class="description">
						<ul>
							<li><strong><code>{{displayname}}</code></strong> : <?php _e('will echo the display name of the user', 'bp-wake-up-sleepers')?></li>
							<?php if( $sleepers_type == 'unactivated'):?>
							<li><strong><code>{{activationlink}}</code></strong> : <?php _e('will echo a link to activate the user&#39;s account', 'bp-wake-up-sleepers')?></li>
							<?php endif;?>
							<li><strong><code>{{userlogin}}</code></strong> : <?php _e('will echo the login of the user', 'bp-wake-up-sleepers')?></li>
							<?php if( $sleepers_type != 'unactivated'):?>
							<li><strong><code>{{memberlink}}</code></strong> : <?php _e('will echo a link to user&#39;s profile page', 'bp-wake-up-sleepers')?></li>
							<?php endif;?>
						</ul>
					</p>
				</div>

				<?php wp_nonce_field( 'bp-wus-template-save' ); ?>
			</form>
		<?php elseif( isset( $_GET['tab'] ) && $_GET['tab'] == 'send' ):?>

			<p class="submit">
				<a href="<?php echo wp_nonce_url( BP_WUS_PLUGIN_URL .'/includes/bp-wus-send-mails.php?sleeperstype=' . $sleepers_type, 'bp_wus_send_all' ) ?>" id="bp-wus-send-all" class="button-primary" target="bp-wus-mailer"><?php _e('Send all', 'bp-wake-up-sleepers')?></a>
				<a href="<?php echo wp_nonce_url( BP_WUS_PLUGIN_URL .'/includes/bp-wus-send-mails.php?test=1&sleeperstype=' . $sleepers_type, 'bp_wus_send_test' ) ?>" id="bp-wus-send-test" class="button-secondary" target="bp-wus-mailer"><?php _e('Send a test to admin email', 'bp-wake-up-sleepers')?></a>
			</p>

			<div id="message" class="updated hide info-emailing">
				<p><?php _e('Please wait till the <b>Result</b> appears at the bottom of the iframe below', 'bp-wake-up-sleepers'); ?></p>
			</div>

			<iframe name="bp-wus-mailer" id="bp-wus-mailer" style="border:0;overflow:hidden" width="100%" height="170px" src=""></iframe>

		<?php elseif( isset( $_GET['tab'] ) && $_GET['tab'] == 'manage' ):?>

			<br/>
			<p class="description"><?php _e('Below the list of the unsubscribed users and their types, you can remove a user from the unsubscribed list, delete users that unsubscribed and never logged in or never activated their accounts', 'bp-wake-up-sleepers');?></p>

			<table class="widefat">
				<thead>
					<tr><th><?php _e( 'email', 'bp-wake-up-sleepers' );?></th><th><?php _e( 'Type', 'bp-wake-up-sleepers' );?></th><th colspan="2"><?php _e( 'Actions', 'bp-wake-up-sleepers');?></th></tr>
				</thead>
				<tfoot>
					<tr><th><?php _e( 'email', 'bp-wake-up-sleepers' );?></th><th><?php _e( 'Type', 'bp-wake-up-sleepers' );?></th><th colspan="2"><?php _e( 'Actions', 'bp-wake-up-sleepers');?></th></tr>
				</tfoot>
				<tbody>
			<?php if( !empty( $unsubscribed ) && is_array( $unsubscribed ) && count( $unsubscribed) >=1 ) :?>

				<?php foreach( $unsubscribed as $unsubcriber ):?>

					<tr>
						<td><?php echo $unsubcriber;?></td>

						<?php bp_wus_get_unsubcriber_type( $unsubcriber );?>
					</tr>

				<?php endforeach;?>

			<?php else:?>
				<tr><td colspan="4"><?php _e( 'Unsubscribed list is empty', 'bp-wake-up-sleepers' );?></td></tr>
			<?php endif;?>
				</tbody>
			</table>

			<?php wp_nonce_field( 'ununsubscribe', '_wpnonce_ununsubscribe' ); ?>

		<?php else:?>

			<?php if( !empty( $previous_mailing ) && is_array( $previous_mailing ) ) :?>

				<h4><?php _e('Latest emailing results for this kind of sleepers', 'bp-wake-up-sleepers');?></h4>
				<p class="description">
					<?php _e('Date:', 'bp-wake-up-sleepers');?> <strong><?php echo mysql2date( get_option('date_format'), $previous_mailing['date'] );?></strong><br/>
					<?php _e('Number of email(s) sent:', 'bp-wake-up-sleepers');?> <strong><?php echo $previous_mailing['results']['ok'];?></strong><br/>
					<?php _e('Number of error(s):', 'bp-wake-up-sleepers');?><strong><?php echo $previous_mailing['results']['ko'];?></strong><br/>
				</p>
				<br/>

			<?php endif;?>
			<table class="widefat">
				<thead>
					<tr><th><?php _e( 'Avatar', 'bp-wake-up-sleepers');?></th><th><?php _e( 'Since', 'bp-wake-up-sleepers');?></th><th><?php _e( 'Display name', 'bp-wake-up-sleepers' );?></th><th><?php _e( 'email', 'bp-wake-up-sleepers' );?></th></tr>
				</thead>
				<tfoot>
					<tr><th><?php _e( 'Avatar', 'bp-wake-up-sleepers');?></th><th><?php _e( 'Since', 'bp-wake-up-sleepers');?></th><th><?php _e( 'Display name', 'bp-wake-up-sleepers' );?></th><th><?php _e( 'email', 'bp-wake-up-sleepers' );?></th></tr>
				</tfoot>

				<tbody>
					<?php if( bp_wus_has_sleepers( $args ) ):?>

						<?php while ( bp_wus_sleepers() ) : bp_wus_the_sleeper(); ?>
							<tr>
								<td><?php bp_wus_sleepers_avatar();?></td>
								<td><?php bp_wus_sleepers_since();?></td>
								<td><?php bp_wus_sleepers_displayname();?></td>
								<td><?php bp_wus_sleepers_umail();?></td>
							</tr>

						<?php endwhile; ?>

						<?php if( bp_wus_sleepers_get_pagination_links() ) :?>
							<tr>
								<th colspan="4"><?php _e('Pages : ', 'bp-wake-up-sleepers');?> <?php bp_wus_sleepers_pagination_links();?></th>
							</tr>
						<?php endif;?>
					<?php else:?>
						<tr><td colspan="4"><?php _e( 'No sleeper to display', 'bp-wake-up-sleepers' );?></td></tr>
					<?php endif;?>

				</tbody>

			</table>
		<?php endif;?>
	</div>
	<?php
}

function bp_wus_admin_options() {
	if ( isset( $_POST['bp_wus_admin_submit'] ) && isset( $_POST['bp-wus-admin'] ) ) {
		if ( !check_admin_referer('bp-wus-options') )
			return false;

		// Settings form submitted, now save the settings.
		foreach ( (array)$_POST['bp-wus-admin'] as $key => $value )
			bp_update_option( $key, $value );

	}

	?>
	<div class="wrap">
		<h2><?php _e('BP Wake Up Sleepers Settings', 'bp-wake-up-sleepers')?></h2>

		<form action="" method="post">

			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row"><?php _e( 'Which template do you wish to use ?', 'bp-wake-up-sleepers' ) ?></th>
						<td>
							<input type="radio" name="bp-wus-admin[bp-wus-which-template]"<?php if ( (int)bp_get_option( 'bp-wus-which-template' ) ) : ?> checked="checked"<?php endif; ?> id="bp-wus-which-template-light" value="1" /> <?php _e( 'Light', 'bp-wake-up-sleepers' ) ?> &nbsp;
							<input type="radio" name="bp-wus-admin[bp-wus-which-template]"<?php if ( !(int)bp_get_option( 'bp-wus-which-template' ) || '' == bp_get_option( 'bp-wus-which-template' ) ) : ?> checked="checked"<?php endif; ?> id="bp-wus-which-template-full" value="0" /> <?php _e( 'Full', 'bp-wake-up-sleepers' ) ?>
						</td>
					</tr>
					<tr>
						<td colspan="2">
							<p class="description"><?php _e('Full template is adding an header and a footer based on your site&#39;s information', 'bp-wake-up-sleepers')?></p>
						</td>
					</tr>
					<?php if( get_header_image() ):?>
						<tr>
							<th scope="row"><?php _e( 'Use header image instead of blog name and description (full template only)', 'bp-wake-up-sleepers' ) ?></th>
							<td>
								<input type="radio" name="bp-wus-admin[bp-wus-enable-theme-header-image]"<?php if ( (int)bp_get_option( 'bp-wus-enable-theme-header-image' ) ) : ?> checked="checked"<?php endif; ?> id="bp-wus-enable-theme-header-image-yes" value="1" /> <?php _e( 'Yes', 'bp-wake-up-sleepers' ) ?> &nbsp;
								<input type="radio" name="bp-wus-admin[bp-wus-enable-theme-header-image]"<?php if ( !(int)bp_get_option( 'bp-wus-enable-theme-header-image' ) || '' == bp_get_option( 'bp-wus-enable-theme-header-image' ) ) : ?> checked="checked"<?php endif; ?> id="bp-wus-enable-theme-header-image-no" value="0" /> <?php _e( 'No', 'bp-wake-up-sleepers' ) ?>
							</td>
						</tr>
					<?php endif;?>
				</tbody>
			</table>

			<p class="submit">
				<input class="button-primary" type="submit" name="bp_wus_admin_submit" id="bp-wus-admin-submit" value="<?php _e( 'Save Settings', 'bp-wake-up-sleepers' ); ?>" />
			</p>

			<?php wp_nonce_field( 'bp-wus-options' ); ?>

		</form>

	</div>
	<?php
}
