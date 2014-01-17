<?php get_header(); ?>
<div id="content">
	<?php if($bp_existed == 'true') : ?>
	<?php do_action( 'bp_before_blog_single_post' ) ?>
	<?php endif; ?>
	<?php get_template_part( 'content', 'single' ); ?>
	<?php if($bp_existed == 'true') : ?>
	<?php do_action( 'bp_after_blog_single_post' ) ?>
	<?php endif; ?>
</div>
	<?php get_sidebar(); ?>
<?php get_footer() ?>