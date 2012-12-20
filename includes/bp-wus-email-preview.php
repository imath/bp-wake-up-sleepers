<?php
$admin = dirname( __FILE__ ) ;
$admin = substr( $admin , 0 , strpos( $admin , "wp-content" ) ) ;
require_once( $admin . 'wp-admin/admin.php' ) ;

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
	<title><?php _e('A preview of your mail', 'bp-wake-up-sleepers');?></title>
	<meta name="generator" content="TextMate http://macromates.com/">
	<meta name="author" content="imath">
	<!-- Date: 2012-02-17 -->
	<?php 
	do_action('admin_print_styles');
	do_action('admin_print_scripts');
	do_action('admin_head');
	?>
</head>
<body class="wp-core-ui">
	<div class="wrap" style="margin:0 auto;width:90%">
		<p><?php _e('A preview of your mail', 'bp-wake-up-sleepers');?></p>
		
		<div id="email-preview" style="width:90%;height:430px;overflow:auto">
			<?php
			
			$preview = new BP_Alarm_Sleepers( $_GET['sleepertype'], 1, 1 );
			$preview->preview_mail();
			
			?>
		</div>
		
		<p><a href="#" class="button-primary closeWindow"><?php _e('Close preview', 'bp-wake-up-sleepers');?></a></p>
		
	</div>
	
	
	<script type="text/javascript">
	
	jQuery(document).ready(function($) {
		
		$('.closeWindow').click( function() {
			var win = window.dialogArguments || opener || parent || top;
			win.tb_remove();
		});
		
	});
	
	
	</script>
</body>
</html>