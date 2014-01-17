<?php
function framemarket_page_menu_args($args) {
	$args['show_home'] = true;
	return $args;
}

function framemarket_excerpt_length( $length ) {
	return 40;
}

if ( !function_exists( 'wpmudev_comment_form' ) ) :
function wpmudev_comment_form( $default_labels ) {
	global $themename, $shortname, $options, $options2, $options3, $bp_existed, $multi_site_on;

	if($bp_existed == 'true') :
	global $user_identity;

	$commenter = wp_get_current_commenter();
	$req = get_option( 'require_name_email' );
	$aria_req = ( $req ? " aria-required='true'" : '' );
	$fields =  array(
		'author' => '<p class="comment-form-author">' . '<label for="author">' . __( 'Name', 'framemarket' ) . ( $req ? '<span class="required"> *</span>' : '' ) . '</label> ' .
		            '<input id="author" name="author" type="text" value="' . esc_attr( $commenter['comment_author'] ) . '" size="30"' . $aria_req . ' /></p>',
		'email'  => '<p class="comment-form-email"><label for="email">' . __( 'Email', 'framemarket' ) . ( $req ? '<span class="required"> *</span>' : '' ) . '</label> ' .
		            '<input id="email" name="email" type="text" value="' . esc_attr(  $commenter['comment_author_email'] ) . '" size="30"' . $aria_req . ' /></p>',
		'url'    => '<p class="comment-form-url"><label for="url">' . __( 'Website', 'framemarket' ) . '</label>' .
		            '<input id="url" name="url" type="text" value="' . esc_attr( $commenter['comment_author_url'] ) . '" size="30" /></p>',
	);

	$new_labels = array(
		'comment_field'  => '<p class="form-textarea"><textarea name="comment" id="comment" cols="60" rows="10" aria-required="true"></textarea></p>',
		'fields'         => apply_filters( 'comment_form_default_fields', $fields ),
		'logged_in_as'   => '',
		'must_log_in'    => '<p class="alert">' . sprintf( __( 'You must be <a href="%1$s">logged in</a> to post a comment.', 'framemarket' ), wp_login_url( get_permalink() ) )	. '</p>',
		'title_reply'    => __( 'Leave a reply', 'framemarket' )
	);

	return apply_filters( 'wpmudev_comment_form', array_merge( $default_labels, $new_labels ) );
	endif;
}
endif;

if ( !function_exists( 'wpmudev_blog_comments' ) ) :
function wpmudev_blog_comments( $comment, $args, $depth ) {
global $themename, $shortname, $options, $options2, $options3, $bp_existed, $multi_site_on;

if($bp_existed == 'true') {
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
		<div class="comment-author vcard">
				<a href="<?php echo get_comment_author_url() ?>" rel="nofollow">
					<?php if ( $comment->user_id ) : ?>
						<?php echo bp_core_fetch_avatar( array( 'item_id' => $comment->user_id, 'width' => $avatar_size, 'height' => $avatar_size, 'email' => $comment->comment_author_email ) ) ?>
					<?php else : ?>
						<?php echo get_avatar( $comment, $avatar_size ) ?>
					<?php endif; ?>
				</div>
					<?php if ( $comment->comment_approved == '0' ) : ?>
						<em><?php _e( 'Your comment is awaiting moderation.', 'framemarket'); ?></em>
						<br />
					<?php endif; ?>

					<div class="comment-meta commentmetadata"><a href="<?php echo esc_url( get_comment_link( $comment->comment_ID ) ); ?>">
						<?php
							printf( __( '%1$s at %2$s', 'framemarket' ), get_comment_date(),  get_comment_time() ); ?></a><?php edit_comment_link( __( '(Edit)', 'framemarket'), ' ' );
						?>
					</div>

					<div class="comment-body"><?php comment_text(); ?></div>

					<div class="reply">
						<?php comment_reply_link( array_merge( $args, array( 'depth' => $depth, 'max_depth' => $args['max_depth'] ) ) ); ?>
					</div>
				</div>

		<?php

			 } else {

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
	<div class="comment-author vcard">
				<a href="<?php echo get_comment_author_url() ?>" rel="nofollow">
					<?php if ( $comment->user_id ) : ?>
							<?php echo get_avatar( $comment, 40 ); ?>
					<?php else : ?>
						<?php echo get_avatar( $comment, $avatar_size ) ?>
					<?php endif; ?>
	</div>
		<?php if ( $comment->comment_approved == '0' ) : ?>
			<em><?php _e( 'Your comment is awaiting moderation.', 'framemarket'); ?></em>
			<br />
		<?php endif; ?>

		<div class="comment-meta commentmetadata"><a href="<?php echo esc_url( get_comment_link( $comment->comment_ID ) ); ?>">
			<?php
				printf( __( '%1$s at %2$s', 'framemarket' ), get_comment_date(),  get_comment_time() ); ?></a><?php edit_comment_link( __( '(Edit)', 'framemarket'), ' ' );
			?>
		</div>

		<div class="comment-body"><?php comment_text(); ?></div>

		<div class="reply">
			<?php comment_reply_link( array_merge( $args, array( 'depth' => $depth, 'max_depth' => $args['max_depth'] ) ) ); ?>
		</div>
	</div>

<?php
	}
}
endif;

function framemarket_footerlinks(){
	?>
	<a href="<?php echo home_url(); ?>"><?php _e( 'Copyright', 'framemarket' ) ?> &copy;<?php echo gmdate(__('Y')); ?> <?php bloginfo('name'); ?></a><a href="#header-wrapper"><?php _e('Go back to top &uarr;', 'framemarket'); ?></a>
	<?php
}

function framemarket_postedon() {
	printf( __( '<span class="%1$s">Posted on</span> %2$s by %3$s',  'framemarket'),
		'meta-prep meta-prep-author',
		sprintf( '<a href="%1$s" title="%2$s" rel="bookmark"><span class="entry-date">%3$s</span></a>',
			get_permalink(),
			esc_attr( get_the_time() ),
			get_the_date()
		),
		sprintf( '<span class="author vcard"><a class="url fn n" href="%1$s" title="%2$s">%3$s</a></span>',
			get_author_posts_url( get_the_author_meta( 'ID' ) ),
			sprintf( esc_attr__( 'View all posts by %s', 'framemarket'), get_the_author() ),
			get_the_author()
		)
	);
}

function framemarket_postedin() {
	// Retrieves tag list of current post, separated by commas.
	$tag_list = get_the_tag_list( '', ', ' );
	if ( $tag_list ) {
		$posted_in = __( 'This entry was posted in %1$s and tagged %2$s. Bookmark the <a href="%3$s" title="Permalink to %4$s" rel="bookmark">permalink</a>.', 'framemarket');
	} elseif ( is_object_in_taxonomy( get_post_type(), 'category' ) ) {
		$posted_in = __( 'This entry was posted in %1$s. Bookmark the <a href="%3$s" title="Permalink to %4$s" rel="bookmark">permalink</a>.', 'framemarket');
	} else {
		$posted_in = __( 'Bookmark the <a href="%3$s" title="Permalink to %4$s" rel="bookmark">permalink</a>.', 'framemarket');
	}
	// Prints the string, replacing the placeholders.
	printf(
		$posted_in,
		get_the_category_list( ', ' ),
		$tag_list,
		get_permalink(),
		the_title_attribute( 'echo=0' )
	);
}

function framemarket_continue_reading_link() {
	return ' <a href="'. get_permalink() . '">' . __( 'Continue reading <span class="meta-nav">&rarr;</span>', 'framemarket') . '</a>';
}

function framemarket_font_show($theme_fonttype, $theme_bodytype){
	$fonttype = $theme_fonttype;
	$bodytype = $theme_bodytype;

	if (($fonttype == "Cantarell, arial, serif") || ($bodytype == "Cantarell, arial, serif")){
	?>
	<link href='http://fonts.googleapis.com/css?family=Cantarell' rel='stylesheet' type='text/css'/>
<style type="text/css" media="screen">
	      h1, h2, h3, h4, h5, h6, #site-logo{
	font-family: <?php echo $fonttype; ?>;
		}
		body{
			font-family: <?php echo $bodytype; ?>;
		}
	    </style>
	<?php
	}
	else if (($fonttype == "Cardo, arial, serif") || ($bodytype == "Cardo, arial, serif")){
	?>
	<link href='http://fonts.googleapis.com/css?family=Cardo' rel='stylesheet' type='text/css'/>
<style type="text/css" media="screen">
	      h1, h2, h3, h4, h5, h6, #site-logo{
	font-family: <?php echo $fonttype; ?>;
		}
		body{
			font-family: <?php echo $bodytype; ?>;
		}
	    </style>
	<?php
	}
	else if (($fonttype == "Crimson Text, arial, serif") || ($bodytype == "Crimson Text, arial, serif")){
	?>
	<link href='http://fonts.googleapis.com/css?family=Crimson+Text' rel='stylesheet' type='text/css'/>
<style type="text/css" media="screen">
	      h1, h2, h3, h4, h5, h6, #site-logo{
	font-family: <?php echo $fonttype; ?>;
		}
		body{
			font-family: <?php echo $bodytype; ?>;
		}
	    </style>
	<?php
	}
	else if (($fonttype == "Droid Sans, arial, serif") || ($bodytype == "Droid Sans, arial, serif")){
	?>
	<link href='http://fonts.googleapis.com/css?family=Droid+Sans' rel='stylesheet' type='text/css'/>
	<style type="text/css" media="screen">
	      h1, h2, h3, h4, h5, h6, #site-logo{
	font-family: <?php echo $fonttype; ?>;
		}
		body{
			font-family: <?php echo $bodytype; ?>;
		}
	    </style>
	<?php
	}
	else if (($fonttype == "Droid Serif, arial, serif") || ($bodytype == "Droid Serif, arial, serif")){
	?>
	<link href='http://fonts.googleapis.com/css?family=Droid+Serif' rel='stylesheet' type='text/css'/>
	<style type="text/css" media="screen">
	      h1, h2, h3, h4, h5, h6, #site-logo{
	font-family: <?php echo $fonttype; ?>;
		}
		body{
			font-family: <?php echo $bodytype; ?>;
		}
	    </style>
	<?php
	}
	else if (($fonttype == "IM Fell SW Pica, arial, serif") || ($bodytype == "IM Fell SW Pica, arial, serif")){
	?>
	<link href='http://fonts.googleapis.com/css?family=IM+Fell+DW+Pica' rel='stylesheet' type='text/css'/>
<style type="text/css" media="screen">
	      h1, h2, h3, h4, h5, h6, #site-logo{
	font-family: <?php echo $fonttype; ?>;
		}
		body{
			font-family: <?php echo $bodytype; ?>;
		}
	    </style>
	<?php
	}
	else if (($fonttype == "Josefin Sans Std Light, arial, serif") || ($bodytype == "Josefin Sans Std Light, arial, serif")){
	?>
	<link href='http://fonts.googleapis.com/css?family=Josefin+Sans+Std+Light' rel='stylesheet' type='text/css'/>
<style type="text/css" media="screen">
	      h1, h2, h3, h4, h5, h6, #site-logo{
	font-family: <?php echo $fonttype; ?>;
		}
		body{
			font-family: <?php echo $bodytype; ?>;
		}
	    </style>
	<?php
	}
	else if (($fonttype == "Lobster, arial, serif") || ($bodytype == "Lobster, arial, serif")){
	?>
	<link href='http://fonts.googleapis.com/css?family=Lobster' rel='stylesheet' type='text/css'/>
<style type="text/css" media="screen">
	      h1, h2, h3, h4, h5, h6, #site-logo{
	font-family: <?php echo $fonttype; ?>;
		}
		body{
			font-family: <?php echo $bodytype; ?>;
		}
	    </style>
	<?php
	}
	else if (($fonttype == "Molengo, arial, serif") || ($bodytype == "Molengo, arial, serif")){
	?>
	<link href='http://fonts.googleapis.com/css?family=Molengo' rel='stylesheet' type='text/css'/>
<style type="text/css" media="screen">
	      h1, h2, h3, h4, h5, h6, #site-logo{
	font-family: <?php echo $fonttype; ?>;
		}
		body{
			font-family: <?php echo $bodytype; ?>;
		}
	    </style>
	<?php
	}
	else if (($fonttype == "Neuton, arial, serif") || ($bodytype == "Neuton, arial, serif")){
	?>
	<link href='http://fonts.googleapis.com/css?family=Neuton' rel='stylesheet' type='text/css'/>
<style type="text/css" media="screen">
	      h1, h2, h3, h4, h5, h6, #site-logo{
	font-family: <?php echo $fonttype; ?>;
		}
		body{
			font-family: <?php echo $bodytype; ?>;
		}
	    </style>
	<?php
	}
	else if (($fonttype == "Nobile, arial, serif") || ($bodytype == "Nobile, arial, serif")){
	?>
	<link href='http://fonts.googleapis.com/css?family=Nobile' rel='stylesheet' type='text/css'/>
	<style type="text/css" media="screen">
	      h1, h2, h3, h4, h5, h6, #site-logo{
	font-family: <?php echo $fonttype; ?>;
		}
		body{
			font-family: <?php echo $bodytype; ?>;
		}
	    </style>
	<?php
	}
	else if (($fonttype == "OFL Sorts Mill Goudy TT, arial, serif") || ($bodytype == "OFL Sorts Mill Goudy TT, arial, serif")){
	?>
	<link href='http://fonts.googleapis.com/css?family=OFL+Sorts+Mill+Goudy+TT' rel='stylesheet' type='text/css'/>
<style type="text/css" media="screen">
	      h1, h2, h3, h4, h5, h6, #site-logo{
	font-family: <?php echo $fonttype; ?>;
		}
		body{
			font-family: <?php echo $bodytype; ?>;
		}
	    </style>
	<?php
	}
	else if (($fonttype == "Reenie Beanie, arial, serif") || ($bodytype == "Reenie Beanie, arial, serif")){
	?>
	<link href='http://fonts.googleapis.com/css?family=Reenie+Beanie' rel='stylesheet' type='text/css'/>
<style type="text/css" media="screen">
	      h1, h2, h3, h4, h5, h6, #site-logo{
	font-family: <?php echo $fonttype; ?>;
		}
		body{
			font-family: <?php echo $bodytype; ?>;
		}
	    </style>
	<?php
	}
	else if (($fonttype == "Tangerine, arial, serif") || ($bodytype == "Tangerine, arial, serif")){
	?>
	<link href='http://fonts.googleapis.com/css?family=Tangerine' rel='stylesheet' type='text/css'/>
<style type="text/css" media="screen">
	      h1, h2, h3, h4, h5, h6, #site-logo{
	font-family: <?php echo $fonttype; ?>;
		}
		body{
			font-family: <?php echo $bodytype; ?>;
		}
	    </style>
	<?php
	}
	else if (($fonttype == "Old Standard TT, arial, serif") || ($bodytype == "Old Standard TT, arial, serif")){
	?>
	<link href='http://fonts.googleapis.com/css?family=Old+Standard+TT' rel='stylesheet' type='text/css'/>
	<style type="text/css" media="screen">
	      h1, h2, h3, h4, h5, h6, #site-logo{
	font-family: <?php echo $fonttype; ?>;
		}
		body{
			font-family: <?php echo $bodytype; ?>;
		}
	    </style>
	<?php
	}
	else if (($fonttype == "Volkorn, arial, serif") || ($bodytype == "Volkorn, arial, serif")){
	?>
	<link href='http://fonts.googleapis.com/css?family=Volkorn' rel='stylesheet' type='text/css'/>
	<style type="text/css" media="screen">
	      h1, h2, h3, h4, h5, h6, #site-logo{
	font-family: <?php echo $fonttype; ?>;
		}
		body{
			font-family: <?php echo $bodytype; ?>;
		}
	    </style>
	<?php
	}
	else if (($fonttype == "Yanone Kaffessatz, arial, serif") || ($bodytype == "Yanone Kaffessatz, arial, serif")){
	?>
	<link href='http://fonts.googleapis.com/css?family=Yanone+Kaffeesatz' rel='stylesheet' type='text/css'/>
<style type="text/css" media="screen">
	      h1, h2, h3, h4, h5, h6, #site-logo{
	font-family: <?php echo $fonttype; ?>;
		}
		body{
			font-family: <?php echo $bodytype; ?>;
		}
	    </style>
	<?php
	}
	else if (($fonttype == "Choose a font") || ($bodytype == "Choose a font") || ($fonttype == "") || ($bodytype == "")){
		?>
		<?php
	}
	else{
		?>
		<style type="text/css" media="screen">
			      h1, h2, h3, h4, h5, h6, #site-logo{
			font-family: <?php echo $fonttype; ?>;
				}
				body{
					font-family: <?php echo $bodytype; ?>;
				}
			    </style>
		<?php
	}

}
?>