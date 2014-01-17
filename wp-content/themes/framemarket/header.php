<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>" />
 		<?php include (get_template_directory() . '/buddypress/buddypress-globals.php'); ?>
		<title><?php wp_title( '|', true, 'right' ); bloginfo( 'name' ); ?></title>
		<?php if($bp_existed == 'true') : ?>
			<?php do_action( 'bp_head' ) ?>
		<?php endif; ?>
			<link rel="stylesheet" type="text/css" media="all" href="<?php bloginfo( 'stylesheet_url' ); ?>" />
			<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>" />
			<?php wp_head(); ?>
	</head>
	<body <?php body_class() ?>>
		<div id="site-wrapper">
				<?php if($bp_existed == 'true') : ?>
					<?php do_action( 'bp_before_header' ) ?>
				<?php endif; ?>
			<div id="header">
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
							<?php if($bp_existed == 'true') : ?>
								<?php do_action( 'bp_header' ) ?>
							<?php endif; ?>
			</div>
				<div id="navigation-wrapper">
					<div id="navigation"><!-- start #navigation -->
						<?php wp_nav_menu( array('theme_location' => 'main_menu', 'menu_class' => 'nav', 'container' => '', )); ?>
							<?php if($bp_existed == 'true') : ?>
								<ul class="nav">
									<li><a href="">Community</a>
									<ul class="submenu">
									<?php if ( 'activity' != bp_dtheme_page_on_front() && bp_is_active( 'activity' ) ) : ?>
										<li<?php if ( bp_is_activity_component() && !bp_is_user_activity() ) : ?> class="selected"<?php endif; ?>>
											<a href="<?php echo site_url() ?>/<?php echo bp_get_activity_root_slug() ?>/" title="<?php _e( 'Activity', 'framemarket' ) ?>"><?php _e( 'Activity', 'framemarket' ) ?></a>
										</li>
									<?php endif; ?>

									<li<?php if ( bp_is_members_component() || bp_is_user() ) : ?> class="selected"<?php endif; ?>>
										<a href="<?php echo site_url() ?>/<?php echo bp_get_members_root_slug() ?>/" title="<?php _e( 'Members', 'framemarket' ) ?>"><?php _e( 'Members', 'framemarket' ) ?></a>
									</li>

									<?php if ( bp_is_active( 'groups' ) ) : ?>
										<li<?php if ( (bp_is_groups_component() || bp_is_group()) && !bp_is_user_groups() ) : ?> class="selected"<?php endif; ?>>
											<a href="<?php echo site_url() ?>/<?php echo bp_groups_root_slug() ?>/" title="<?php _e( 'Groups', 'framemarket' ) ?>"><?php _e( 'Groups', 'framemarket' ) ?></a>
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
										<li<?php if ( bp_is_blogs_component() && !bp_is_user_blogs() ) : ?> class="selected"<?php endif; ?>>
											<a href="<?php echo site_url() ?>/<?php echo bp_blogs_root_slug() ?>/" title="<?php _e( 'Blogs', 'framemarket' ) ?>"><?php _e( 'Blogs', 'framemarket' ) ?></a>
										</li>
									<?php endif; ?>
									<?php do_action( 'bp_nav_items' ); ?>
									</ul>
									</li>
								</ul>
							<?php endif; ?>
						<div class="clear"></div>
					</div>
				</div>
				<?php if($bp_existed == 'true') : ?>
						<?php do_action( 'bp_after_header' ) ?>
						<?php do_action( 'bp_before_container' ) ?>
				<?php endif; ?>
			<div id="container">