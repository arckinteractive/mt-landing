 		<?php include (get_template_directory() . '/buddypress/buddypress-globals.php'); ?>
<?php if($bp_existed == 'true') : ?>
<?php do_action( 'bp_before_blog_search_form' ) ?>
<?php endif; ?>
<form method="get" id="searchform" action="<?php echo home_url( '/' ); ?>">
    <div><label class="screen-reader-text" for="s"><?php _e( 'Search for :', 'framemarket' ) ?></label>
        <input type="text" value="<?php echo get_search_query() ?>" name="s" id="s" />
        <input type="submit" id="searchsubmit" value="Search" />
		<?php if($bp_existed == 'true') : ?>
		<?php do_action( 'bp_blog_search_form' ) ?>
		<?php endif; ?>
    </div>
</form>
<?php if($bp_existed == 'true') : ?>
<?php do_action( 'bp_after_blog_search_form' ) ?>
<?php endif; ?>