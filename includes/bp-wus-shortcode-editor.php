<?php
$admin = dirname( __FILE__ ) ;
$admin = substr( $admin , 0 , strpos( $admin , "wp-content" ) ) ;
require_once( $admin . 'wp-admin/admin.php' ) ;
global $bp;

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
	<title><?php _e('Add some of your favorite activities', 'bp-wake-up-sleepers');?></title>
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
		<p><?php _e('Choose the activity you want to insert in your mail', 'bp-wake-up-sleepers');?></p>
		<table class="widefat">
			<thead>
				<tr><th>&nbsp;</th><th><?php _e('Favorite Activities', 'bp-wake-up-sleepers');?></th></tr>
			</thead>
			<tfoot>
				<tr><th>&nbsp;</th><th><?php _e('Favorite Activities', 'bp-wake-up-sleepers');?></th></tr>
			</tfoot>
			
				<?php if ( bp_has_activities( array( 'scope' => 'favorites', 'filter' => array( 'user_id' => $bp->loggedin_user->id ) ) ) ): ?>
			
					<tbody>
						
						<?php while ( bp_activities() ) : bp_the_activity(); ?>

							<tr><td><input type="checkbox" class="cb_activity" name="activities[]" value="<?php bp_activity_id();?>"/></td><td><?php bp_activity_content_body(); ?></td></tr>

						<?php endwhile; ?>
						
					</tbody>
			
				<?php else:?>
					
					<tbody>
						
						<tr><td colspan="2"><?php _e('No activity favorited','bp-wake-up-sleepers');?></td></tr>
						
					</tbody>
					
				<?php endif;?>
		</table>
		
		<p><a href="#" class="button-primary insertFavorite"><?php _e('Insert selected activities', 'bp-wake-up-sleepers');?></a></p>
		
	</div>
	
	
	<script type="text/javascript">
	
	jQuery(document).ready(function($) {
		
		$('.insertFavorite').click( function() {

			var shortcode = '[wus_favorites ids="#"]';
			var ids = "";
			
			$("input[type='checkbox']:checked").each( function() {
				ids += $(this).val() + ',';
			});
			
			ids = ids.substring( 0, ids.length - 1);
			shortcode = shortcode.replace("#", ids);

			var win = window.dialogArguments || opener || parent || top;
			win.send_to_editor(shortcode);
		});
		
	});
	
	
	</script>
</body>
</html>