<?php get_header(); ?>
<div id="content-fullwidth">
	<?php if($bp_existed == 'true') : ?>
		<?php do_action( 'bp_before_attachment' ) ?>
	<?php endif; ?>
		<div class="single-attachment">
						<?php
						get_template_part( 'content', 'attachment' );
						?>
		</div>
		<?php if($bp_existed == 'true') : ?>
			<?php do_action( 'bp_after_attachment' ) ?>
		<?php endif; ?>
</div>
<?php get_footer() ?>