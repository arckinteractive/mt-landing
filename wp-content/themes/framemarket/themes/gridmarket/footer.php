	 		<?php include (get_template_directory() . '/buddypress/buddypress-globals.php'); ?>
	<div class="clear"></div>
</div>
</div>

		<?php if($bp_existed == 'true') : ?>
			<?php do_action( 'bp_after_container' ) ?>
			<?php do_action( 'bp_before_footer' ) ?>
		<?php endif; ?>
<div id="footer-wrapper">
	<div id="footer">
		<div id="footer-widgets">
			<?php if ( is_active_sidebar( 'footer-one' ) ) : ?>
				<div class="footer-widget-columns">
					<?php dynamic_sidebar( 'footer-one' ); ?>
				</div>
			<?php endif; ?>
			<?php if ( is_active_sidebar( 'footer-two' ) ) : ?>
				<div class="footer-widget-columns">
					<?php dynamic_sidebar( 'footer-two' ); ?>
				</div>
			<?php endif; ?>
			<?php if ( is_active_sidebar( 'footer-three' ) ) : ?>
				<div class="footer-widget-columns">
					<?php dynamic_sidebar( 'footer-three' ); ?>
				</div>
				<?php endif; ?>
			<?php if ( is_active_sidebar( 'footer-four' ) ) : ?>
				<div class="footer-widget-columns">
					<?php dynamic_sidebar( 'footer-four' ); ?>
				</div>
			<?php endif; ?>
			<?php if ( is_active_sidebar( 'footer-five' ) ) : ?>
				<div class="footer-widget-columns">
					<?php dynamic_sidebar( 'footer-five' ); ?>
				</div>
				<?php endif; ?>
			<div class="clear"></div>
		</div>

		<?php
		$options = get_option('framemarket_theme_options');
		$footerlinks = isset($options['footertextarea']) ? $options['footertextarea'] : '';
		if ($footerlinks != ""){
		?>
			<div id="footer-links">
				<?php echo stripslashes($footerlinks); ?>
			</div>
		<?php
			}else{
		?>
			<div id="footer-links">
				<?php framemarket_footerlinks(); ?>
			</div>
		<?php
			}
		?>
		<?php if($bp_existed == 'true') : ?>
			<?php do_action( 'bp_footer' ) ?>
		<?php endif; ?>
	</div>
</div>
<?php if($bp_existed == 'true') : ?>
		<?php do_action( 'bp_after_footer' ) ?>
<?php endif; ?>
	<?php
	$options = get_option('framemarket_theme_options');
	$analytics = isset($options['googletextarea']) ? $options['googletextarea'] : '';
	if ($analytics != ""){
		echo stripslashes($analytics);
	}
	?>
			<?php wp_footer(); ?>
	</body>
</html>