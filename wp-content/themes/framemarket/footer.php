	 		<?php include (get_template_directory() . '/buddypress/buddypress-globals.php'); ?>
			</div>
				<?php if($bp_existed == 'true') : ?>
					<?php do_action( 'bp_after_container' ) ?>
					<?php do_action( 'bp_before_footer' ) ?>
				<?php endif; ?>
			<div id="footer">
					<?php
					$options = get_option('framemarket_theme_options');
					$footerlinks = isset($options['footertextarea']) ? $options['footertextarea'] : '';
					if ($footerlinks != ""){
						?>
							<div id="footer-links">
						<?php
						echo stripslashes($footerlinks);
						?>
							</div>
						<?php
					}
					else{
							?>
								<div id="footer-links">
							<?php
						framemarket_footerlinks();
							?>
								</div>
							<?php
			}
			?>
			</div>
		</div>
		<?php
		$options = get_option('framemarket_theme_options');
		$analytics = isset($options['googletextarea']) ? $options['googletextarea'] : '';
		if ($analytics != ""){
			echo stripslashes($analytics);
		}
		?>
		<?php if($bp_existed == 'true') : ?>
			<?php do_action( 'bp_footer' ) ?>
		<?php endif; ?>
				<?php wp_footer(); ?>
	</body>
</html>