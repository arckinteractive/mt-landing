<?php
/*
Template Name: Full width
*/
?>

<?php get_header(); ?>
<div id="content-fullwidth">
	<?php if($bp_existed == 'true') : ?>
	<?php do_action( 'bp_before_blog_page' ) ?>
	<?php endif; ?>
	<?php get_template_part( 'content', 'page' ); ?>
	<?php if($bp_existed == 'true') : ?>
	<?php do_action( 'bp_after_blog_page' ) ?>
	<?php endif; ?>
</div>
<?php get_footer() ?>