<?php
$admin = dirname( __FILE__ ) ;
$admin = substr( $admin , 0 , strpos( $admin , "wp-content" ) ) ;
require_once( $admin . 'wp-admin/admin.php' ) ;

$type = $_GET['sleeperstype'];

wp_enqueue_style( 'global' );
wp_enqueue_style( 'wp-admin' );
wp_enqueue_style( 'colors' );
wp_enqueue_script('jquery');

@header('Content-Type: ' . get_option('html_type') . '; charset=' . get_option('blog_charset'));
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN"
   "http://www.w3.org/TR/html4/strict.dtd">

<html lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<title><?php _e('BP Wus Mailer', 'bp-wake-up-sleepers');?></title>
	<meta name="generator" content="TextMate http://macromates.com/">
	<meta name="author" content="imath">
	<!-- Date: 2012-02-17 -->
	<?php 
	do_action('admin_print_styles');
	do_action('admin_print_scripts');
	do_action('admin_head');
	?>
	<style>
	#end{
		background: whiteSmoke;
		border:solid 1px #f1f1f1;
		border-radius:3px;
		padding:1em;
		width:80%;
		margin:0 auto;
	}
	</style>
</head>
<body class="wp-core-ui">
	<div class="wrap" style="margin:0 auto;width:90%">
		<?php

		if( $_GET['test'] == 1 ) {
			check_admin_referer('bp_wus_send_test');
	
			$sleepers = new BP_Alarm_Sleepers( $type, 1, 1);
			echo '<ul>';
			$sleepers->send_mails( bp_core_get_user_email( bp_loggedin_user_id() ) );
			echo '</ul>';
			$result = $sleepers->mail_results ;
		} else {
			check_admin_referer('bp_wus_send_all');
			
			if( 1 == get_transient( 'bp_wus_last_mailing_' . $type ) )
				wp_die( __('OOps, you need to wait untill tomorrow before being able to send this request again', 'bp-wake-up-sleepers') );
	
			$sleepers = new BP_Alarm_Sleepers( $type );
			echo '<ul>';
			$sleepers->send_mails();
			echo '</ul>';
			$result = $sleepers->mail_results ;
		}
		?>
		<div id="end">
			<?php if( !empty( $result ) ):?>
			<h4>Result :</h4>
			<table>
				<tr><td><?php _e('Number of email(s) sent:', 'bp-wake-up-sleepers');?></td><td><?php echo $result['ok'];?></td></tr>
				<tr><td><?php _e('Number of error(s):', 'bp-wake-up-sleepers');?></td><td><?php echo $result['ko'];?></td></tr>
				<?php if( $result['ko'] > 0 ):?>
					<tr>
						<td colspan="2">
							<h5><?php _e('Error in mails:', 'bp-wake-up-sleepers');?></h5>
							<?php echo implode('<br/>', $result['komails'] );?>
						</td>
					</tr>
				<?php endif?>
			</table>
			<?php endif;?>
		</div>
	
		<script type="text/javascript">
			jQuery(document).ready(function($){
				$('html, body').animate({scrollTop: $('.wrap').height()}, 800);
			});
			</script>
	</div>
</body>
</html>