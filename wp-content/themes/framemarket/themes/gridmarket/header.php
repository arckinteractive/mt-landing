<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>" />
 		<?php include (get_template_directory() . '/buddypress/buddypress-globals.php'); ?>
	<title><?php wp_title( '|', true, 'right' ); bloginfo( 'name' ); ?></title>
	<?php if($bp_existed == 'true') : ?>
	<?php do_action( 'bp_head' ) ?>
	<?php endif; ?>
	<link rel="profile" href="http://gmpg.org/xfn/11" />
		<link rel="stylesheet" type="text/css" media="all" href="<?php bloginfo( 'stylesheet_url' ); ?>" />
		<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>" />
		<?php wp_head(); ?>
		<?php include(get_stylesheet_directory() . '/custom-styles.php'); ?>
	</head>
	<body <?php body_class() ?>>
			<?php if($bp_existed == 'true') : ?>
				<?php do_action( 'bp_before_header' ) ?>
			<?php endif; ?>
			<div id="header-wrapper">
				<div id="header">
					<?php if ( class_exists( 'MarketPress' ) ) {

						$settings = get_option('mp_settings');
						if (!$settings['disable_cart']) {
						?>
									<div id="cart-contents"><?php _e( 'Cart:', 'framemarket' ) ?> <span><?php echo sprintf(__('%s item(s)', 'framemarket' ), mp_items_count_in_cart()); ?></span></div>
						<div id="mp-cartsmall">
									<div id="toggle">
								<a id="open" class="open button" href="#"><?php _e( 'View Cart', 'framemarket' ) ?></a>

							<a id="close" style="display: none;" class="close button" href="#"><?php _e( 'Hide Cart', 'framemarket' ) ?></a>

							</div>
						</div>

						<?php
						}
							?>
						<?php  if ( is_multisite() ) {?>
								<?php
									$options = get_option('framemarket_theme_options');
									$showpicker = isset($options['pickerinput']) ? $options['pickerinput'] : '';
									if ($showpicker == "Yes"){
								?>
						<div id="mp-storepicker">
							<?php _e( 'Pick a store:', 'framemarket' ) ?>&nbsp;&nbsp;<?php framemarket_listall_shops(); ?>
						</div>
						<?php } ?>
						<?php } ?>
						<?php
					}
					?>
					<?php
						$options = get_option('framemarket_theme_options');
						$searchbar = isset($options['searchinput']) ? $options['searchinput'] : '';
					?>
					<?php if(($searchbar == '' || $searchbar == 'BuddyPress') && $bp_existed == 'true') : ?>
						<?php if ( apply_filters( 'bp_search_form_enabled', true ) ): $bp_search_form = true; ?>
							<div id="buddypress-searchbar">
								<form action="<?php echo bp_search_form_action() ?>" method="post" id="search-form">
									<input type="text" id="search-terms" name="search-terms" value="" />
									<?php echo bp_search_form_type_select() ?>

									<input type="submit" name="search-submit" id="search-submit" value="<?php _e( 'Search', 'framemarket' ) ?>" />
									<?php wp_nonce_field( 'bp_search_form' ) ?>
								</form><!-- #search-form -->
								<?php do_action( 'bp_search_login_bar' ) ?>
							</div>
						<?php endif; ?>
					<?php endif; ?>
					<?php if ( ! isset($bp_search_form) || !$bp_search_form ): ?>
						<div id="search-bar">
							<?php get_search_form(); ?>
						</div>
					<?php endif ?>
					<div class="clear"></div>
				</div>
				<?php if($bp_existed == 'true') : ?>
					<?php do_action( 'bp_header' ) ?>
				<?php endif; ?>
			</div>
				<?php if ( class_exists( 'MarketPress' ) ) {
					$settings = get_option('mp_settings');
					if (!$settings['disable_cart']) {
					?>
						<div id="panel">
							<div id="panel-inner" class="mp_cart_widget">
								<div class="mp_cart_widget_content">
								<?php echo mp_show_cart('widget'); ?>
								</div>
							<div class="clear"></div>
							</div>
							<div class="clear"></div>
					</div>
					<?php
					}
				}
				?>
			<div id="branding-wrapper">
				<div id="branding">
					<div id="branding-inner">
					<?php
						$options = get_option('framemarket_theme_options');
						$logotype = isset($options['logoinput']) ? $options['logoinput'] : '';
						$logotext = isset($options['logotext']) ? $options['logotext'] : '';
						if ($logotype == "Text" ){
					?>
							<div id="site-logo">
							<a href="<?php echo home_url(); ?>"><?php echo $logotext; ?></a>
							</div>
					<?php
					}
					else if ($logotype == "Name" ){
					?>
							<div id="site-logo"><a href="<?php echo home_url(); ?>"><?php bloginfo('name'); ?></a></div>
					<?php
					}
					else if ($logotype == "Header"){
						?>
						<h1><a href="<?php echo home_url(); ?>"><?php bloginfo('name'); ?></a></h1>
						<?php
					}
					else{
						?>
						<div id="site-logo"><a href="<?php echo home_url(); ?>"><?php _e( 'gridmarket', 'framemarket' ) ?></a></div>
						<?php
					}
					?>
					<div id="site-advert">
						<?php
						$options = get_option('framemarket_theme_options');
						$advert = isset($options['adverttextarea']) ? $options['adverttextarea'] : '';
						if ($advert != ""){
							echo stripslashes($advert);
						}
						?>
					</div>
					</div>
				</div>
			</div>
			<div id="navigation-wrapper">
				<div id="navigation">
					<?php wp_nav_menu( array('theme_location' => 'main_menu', 'menu_class' => 'nav', 'container' => '', )); ?>
						<?php if($bp_existed == 'true') : ?>
							<ul class="nav">
								<li><a href="">Community</a>
								<ul class="submenu">
								<?php if ( 'activity' != bp_dtheme_page_on_front() && bp_is_active( 'activity' ) ) : ?>
									<li<?php if ( bp_is_page( BP_ACTIVITY_SLUG ) ) : ?> class="selected"<?php endif; ?>>
										<a href="<?php echo site_url() ?>/<?php echo BP_ACTIVITY_SLUG ?>/" title="<?php _e( 'Activity', 'framemarket' ) ?>"><?php _e( 'Activity', 'framemarket' ) ?></a>
									</li>
								<?php endif; ?>

								<li<?php if ( bp_is_page( BP_MEMBERS_SLUG ) || bp_is_user() ) : ?> class="selected"<?php endif; ?>>
									<a href="<?php echo site_url() ?>/<?php echo BP_MEMBERS_SLUG ?>/" title="<?php _e( 'Members', 'framemarket' ) ?>"><?php _e( 'Members', 'framemarket' ) ?></a>
								</li>

								<?php if ( bp_is_active( 'groups' ) ) : ?>
									<li<?php if ( bp_is_page( BP_GROUPS_SLUG ) || bp_is_group() ) : ?> class="selected"<?php endif; ?>>
										<a href="<?php echo site_url() ?>/<?php echo BP_GROUPS_SLUG ?>/" title="<?php _e( 'Groups', 'framemarket' ) ?>"><?php _e( 'Groups', 'framemarket' ) ?></a>
									</li>
								<?php endif; ?>

								<?php if ( bp_is_active( 'forums' ) && bp_is_active( 'groups' ) && ( function_exists( 'bp_forums_is_installed_correctly' ) && !(int) bp_get_option( 'bp-disable-forum-directory' ) ) && bp_forums_is_installed_correctly() ) : ?>
									<li<?php if ( bp_is_forums_component() && !bp_is_user_forums() ) : ?> class="selected"<?php endif; ?>>
										<a href="<?php echo site_url() ?>/<?php echo bp_forums_root_slug() ?>/" title="<?php _e( 'Forums', 'framemarket' ) ?>"><?php _e( 'Forums', 'framemarket' ) ?></a>
									</li>
								<?php elseif ( function_exists('bbpress') ): ?>
									<li<?php if ( bbp_is_forum($post->ID) || bbp_is_topic($post->ID) ) : ?> class="selected"<?php endif; ?>>
										<a href="<?php bbp_forums_url(); ?>"><?php _e( 'Forums', 'framemarket' ) ?></a>
									</li>
								<?php endif; ?>

								<?php if ( bp_is_active( 'blogs' ) && is_multisite() ) : ?>
									<li<?php if ( bp_is_page( BP_BLOGS_SLUG ) ) : ?> class="selected"<?php endif; ?>>
										<a href="<?php echo site_url() ?>/<?php echo BP_BLOGS_SLUG ?>/" title="<?php _e( 'Blogs', 'framemarket' ) ?>"><?php _e( 'Blogs', 'framemarket' ) ?></a>
									</li>
								<?php endif; ?>
								<?php do_action( 'bp_nav_items' ); ?>
								</ul>
								</li>
							</ul>
						<?php endif; ?>
					<div class="clear"></div>
				</div>
				<div class="clear"></div>
			</div>
			<?php if($bp_existed == 'true') : ?>
					<?php do_action( 'bp_after_header' ) ?>
					<?php do_action( 'bp_before_container' ) ?>
			<?php endif; ?>
			<div id="site-wrapper">
				<div id="container">