 	<?php include (get_template_directory() . '/buddypress/buddypress-globals.php'); ?>
	<?php if($bp_existed == 'true') : ?>
		<?php do_action( 'bp_before_sidebar' ) ?>
	<?php endif; ?>
		<div id="sidebar">
				<div class="widget">
					<?php if($bp_existed != 'true' || ($bp_existed == 'true' && bp_is_blog_page())) : ?>
							<?php if ( is_active_sidebar( 'sidebar' ) ) { ?>
									<?php dynamic_sidebar( 'sidebar' ); ?>
							<?php }else{
								?>
								<?php if ( class_exists( 'MarketPress' ) ) {
											?>
															<h3 class="widgettitle"><?php _e('Categories', 'framemarket'); ?></h3>
															<div class="widget">
											<?php framemarket_mp_list_categories(); ?>
											</div>

													<h3 class="widgettitle"><?php _e('Popular Products', 'framemarket'); ?></h3>
													<div class="widget">
									<?php mp_popular_products(); ?>
									</div>
													<h3 class="widgettitle"><?php _e('Tag Cloud', 'framemarket'); ?></h3>
													<div class="widget">
									<?php mp_tag_cloud(); ?>
									</div>
									<?php
								}
								?>

									<h3 class="widgettitle"><?php _e('Meta', 'framemarket'); ?></h3>
									<div class="widget">
									<?php wp_register(); ?>
									<?php wp_loginout(); ?>
										<?php wp_meta(); ?>
									</div>
								<?php
							} ?>
					<?php endif ?>

					<?php if($bp_existed == 'true' && !bp_is_blog_page()) : ?>

						<?php do_action( 'bp_inside_before_sidebar' ) ?>
							<?php if ( is_active_sidebar( 'sidebar-members' ) ) : ?>
									<?php dynamic_sidebar( 'sidebar-members' ); ?>
							<?php endif; ?>
						<?php if ( is_user_logged_in() ) : ?>

							<?php do_action( 'bp_before_sidebar_me' ) ?>

							<div id="sidebar-me">
								<h3 class="widgettitle"><?php echo bp_core_get_userlink( bp_loggedin_user_id() ); ?></h3>
									<div class="widget">
							<h4><?php echo bp_core_get_userlink( bp_loggedin_user_id() ); ?></h4>
							<a class="button logout" href="<?php echo wp_logout_url( bp_get_root_domain() ) ?>"><?php _e( 'Log Out', 'framemarket') ?></a>

								<?php do_action( 'bp_sidebar_me' ) ?>
								</div>
							</div>

							<?php do_action( 'bp_after_sidebar_me' ) ?>

							<?php if ( function_exists( 'bp_message_get_notices' ) ) : ?>
								<?php bp_message_get_notices(); /* Site wide notices to all users */ ?>
							<?php endif; ?>

						<?php else : ?>

							<?php do_action( 'bp_before_sidebar_login_form' ) ?>
								<h3 class="widgettitle"><?php _e('Login', 'framemarket'); ?></h3>
								<div class="widget">
							<p id="login-text">
								<?php _e( 'To start connecting please log in first.', 'framemarket' ) ?>
								<?php if ( bp_get_signup_allowed() ) : ?>
									<?php printf( __( ' You can also <a href="%s" title="Create an account">create an account</a>.', 'framemarket' ), site_url( BP_REGISTER_SLUG . '/' ) ) ?>
								<?php endif; ?>
							</p>

							<form name="login-form" id="sidebar-login-form" class="standard-form" action="<?php echo site_url( 'wp-login.php', 'login_post' ) ?>" method="post">
								<label><?php _e( 'Username', 'framemarket' ) ?><br />
								<input type="text" name="log" id="sidebar-user-login" class="input" value="<?php if(isset($user_login))echo esc_attr(stripslashes($user_login)); ?>" tabindex="97" /></label>

								<label><?php _e( 'Password', 'framemarket' ) ?><br />
								<input type="password" name="pwd" id="sidebar-user-pass" class="input" value="" tabindex="98" /></label>

								<p class="forgetmenot"><label><input name="rememberme" type="checkbox" id="sidebar-rememberme" value="forever" tabindex="99" /> <?php _e( 'Remember Me', 'framemarket' ) ?></label></p>

								<?php do_action( 'bp_sidebar_login_form' ) ?>
								<input type="submit" name="wp-submit" id="sidebar-wp-submit" value="<?php _e('Log In'); ?>" tabindex="100" />
								<input type="hidden" name="testcookie" value="1" />
							</form>

							<?php do_action( 'bp_after_sidebar_login_form' ) ?>
							</div>
						<?php endif; ?>

						<?php /* Show forum tags on the forums directory */
						if ( BP_FORUMS_SLUG == bp_current_component() && bp_is_directory() ) : ?>
							<h3 class="widgettitle"><?php _e( 'Forum Topic Tags', 'framemarket' ) ?></h3>
							<div class="widget">
							<div id="forum-directory-tags" class="widget tags">
								<?php if ( function_exists('bp_forums_tag_heat_map') ) : ?>
									<div id="tag-text"><?php bp_forums_tag_heat_map(); ?></div>
								<?php endif; ?>
							</div>
							</div>
						<?php endif; ?>

						<?php do_action( 'bp_inside_after_sidebar' ) ?>
					<?php endif; ?>
			</div>
		</div>
		<?php if($bp_existed == 'true') : ?>
			<?php do_action( 'bp_after_sidebar' ) ?>
		<?php endif; ?>