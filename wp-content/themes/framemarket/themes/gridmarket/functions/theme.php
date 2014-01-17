<?php

function gridmarket_fonts(){
		$themename = wp_get_theme();
		$fontheaderinput = $themename . '_fontheaderinput';
		$fontbodyinput = $themename . '_fontbodyinput';

		$options = get_option('framemarket_theme_options');
		$fontheader = isset($options[$fontheaderinput]) ? $options[$fontheaderinput] : '';
		$bodytype = isset($options[$fontbodyinput]) ? $options[$fontbodyinput] : '';

		if (($fontheader == "")||($fontheader == "Choose a font")){
		?>
		<link href='http://fonts.googleapis.com/css?family=Copse' rel='stylesheet' type='text/css'/>
		<link href='http://fonts.googleapis.com/css?family=Corben:bold' rel='stylesheet' type='text/css'/>
			<style type="text/css" media="screen">
			    #site-logo{ font-family: 'Corben', arial, serif; letter-spacing: 1px; font-weight: normal;}
				h1, h2, h3, h4, h5, h6{ font-family: 'Copse', arial, serif; letter-spacing: 1px; font-weight: normal;}
			</style>
		<?php
		}

		framemarket_font_show($fontheader, $bodytype);
	}

	add_action('wp_head', 'gridmarket_fonts');


	function wpmudev_blog_comments( $comment, $args, $depth ) {
			global $bp_existed;

			$GLOBALS['comment'] = $comment;

			if ( 'pingback' == $comment->comment_type )
				return false;

			if ( 1 == $depth )
				$avatar_size = 50;
			else
				$avatar_size = 25;
			?>

		<li <?php comment_class(); ?> id="li-comment-<?php comment_ID(); ?>">
			<div id="comment-<?php comment_ID(); ?>">

			<?php if ( $comment->comment_approved == '0' ) : ?>
				<em><?php _e( 'Your comment is awaiting moderation.', 'framemarket'); ?></em>
				<br />
			<?php endif; ?>


			<div class="comment-body"><?php comment_text(); ?></div>
			<div class="speech-bottom"></div>


			<div class="comment-author vcard">
						<?php if($bp_existed == 'true') : ?>
							<?php if ( bp_loggedin_user_id() ) : ?>
								<a href="<?php echo bp_loggedin_user_domain() ?>">
									<?php echo get_avatar( bp_loggedin_user_id(), 40 ); ?>
								</a>
							<?php else : ?>
								<?php echo get_avatar( 0, 40 ); ?>
							<?php endif; ?>
						<?php endif; ?>
							<?php if($bp_existed != 'true') : ?>
									<?php echo get_avatar( 0, 40 ); ?>
							<?php endif; ?>
			</div>
				<div class="reply">
					<?php comment_reply_link( array_merge( $args, array( 'depth' => $depth, 'max_depth' => $args['max_depth'] ) ) ); ?>
				</div>

				<div class="comment-meta commentmetadata">
						<?php printf( __( '%s <span class="says">says:</span>', 'framemarket'), sprintf( '<cite class="fn">%s</cite>', get_comment_author_link() ) ); ?>
					<br />
					<a href="<?php echo esc_url( get_comment_link( $comment->comment_ID ) ); ?>">
					<?php

						printf( __( '%1$s at %2$s', 'framemarket' ), get_comment_date(),  get_comment_time() ); ?></a><?php edit_comment_link( __( '(Edit)', 'framemarket'), ' ' );
					?>
				</div>
			<div class="clear"></div>
		</div>

		<?php
	}
?>