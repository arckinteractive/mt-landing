<?php

add_action( 'admin_init', 'framemarket_options_init' );
add_action( 'admin_menu', 'framemarket_options_add_page' );

// set up options and validate
function framemarket_options_init(){
	register_setting( 'framemarket_options', 'framemarket_theme_options', 'framemarket_options_validate' );
}

// create menu page
function framemarket_options_add_page() {
	add_theme_page( __( 'Theme Options' ), __( 'Theme Options' ), 'edit_theme_options', 'theme_options', 'framemarket_options_create_page' );
}

// create the options page
function framemarket_options_create_page() {
	global $logo_options, $picker_options, $style_options, $fontheader_options, $fontbody_options, $show_options;
	$themename = wp_get_theme();

	if ( ! isset( $_REQUEST['settings-updated'] ) )
		$_REQUEST['settings-updated'] = false;
	if ( ! isset( $_REQUEST['theme_reset'] ) )
		$_REQUEST['theme_reset'] = false;
	?>
	<script type="text/javascript" src="<?php echo get_template_directory_uri(); ?>/library/scripts/jscolor/jscolor.js"></script>
	<div id="poststuff">
	<div class="wrap">
		<?php echo "<h2>" . $themename . __( ' Theme Options', 'framemarket' ) . "</h2>"; ?>
		<?php if ( false !== $_REQUEST['settings-updated'] ) : ?>
			<div class="updated fade"><p><strong><?php _e( 'Options saved', 'framemarket' ); ?></strong></p></div>
		<?php endif; ?>
		<?php if ( false !== $_REQUEST['theme_reset'] ) : ?>
			<div class="updated fade"><p><strong><?php _e( 'Options reset', 'framemarket' ); ?></strong></p></div>
		<?php endif; ?>
			<div id="message" class="updated fade">
				<p style="line-height: 150%;"><strong><?php _e( 'Welcome to ', 'framemarket'); ?><?php echo $themename; ?></strong>. <br /><?php _e( 'We figured life is too short for tons of theme options - you want a theme that works right out of the box with ease right?', 'framemarket'); ?>  <br /><?php _e( 'So, lets cut to the essentials and follow our simple steps to creating your own theme.', 'framemarket'); ?></p>
			</div>
		<form method="post" action="options.php">
			<?php settings_fields( 'framemarket_options' );
			?>
			<?php $options = get_option( 'framemarket_theme_options' );
			?>

				<?php
					/* load up all the option sections */

					include(get_template_directory() . '/options/options-values.php');

					include(get_template_directory() . '/options/options-setup.php');
					include(get_template_directory() . '/options/options-advert.php');
					include(get_template_directory() . '/options/options-google.php');
					include(get_template_directory() . '/options/options-footer.php');
					include(get_stylesheet_directory() . '/options/options-styles.php');
					include(get_template_directory() . '/options/options-menus.php');
					include(get_template_directory() . '/options/options-enjoy.php');
				?>

			<p class="submit">
			<input type="submit" class="button-primary" value="<?php _e( 'Save Options', 'framemarket' ); ?>" />
			</p>
			<p class="gridmarket-options-note">
					<input name="theme_reset" type="submit" class="button-secondary" style="float:right" value="<?php _e('Reset Defaults', 'framemarket'); ?>" />
				<?php _e( 'WARNING: Clicking reset will reset ALL theme options - you will not be able to get them back.', 'framemarket'); ?><br />
			</p>
		</form>
	</div>
	</div>
	<?php
}

// sanitzation
function framemarket_options_validate( $input ) {
	global $logo_options, $picker_options, $style_options, $fontheader_options, $fontbody_options, $show_options;
	$themename = wp_get_theme();
	$themereset = isset($_REQUEST['theme_reset']);
	if( 'theme_reset' == $themereset) {
			delete_option( 'framemarket_theme_options');
			header("Location: themes.php?page=theme_options&theme_reset=true");
			die;
	}

	// Text checks
	$input['logotext'] = wp_filter_nohtml_kses( $input['logotext'] );

/*
	// Radio checks
	if ( ! isset( $input['logoinput'] ) )
		$input['logoinput'] = null;
	if ( ! array_key_exists( $input['logoinput'], $logo_options ) )
		$input['logoinput'] = null;

	// Radio checks
	if ( ! isset( $input['pickerinput'] ) )
		$input['pickerinput'] = null;
	if ( ! array_key_exists( $input['pickerinput'], $picker_options ) )
		$input['pickerinput'] = null;

	// Radio checks
	if ( ! isset( $input['showinput'] ) )
		$input['showinput'] = null;
	if ( ! array_key_exists( $input['showinput'], $show_options ) )
		$input['showinput'] = null;

	if ($themename != "FrameMarket"){
		$themeinput = $themename;
		$themeinput .= '_styleinput';
		$fontheaderinput = $themename;
		$fontheaderinput .= '_fontheaderinput';
		$fontbodyinput = $themename;
		$fontbodyinput .= '_fontbodyinput';
		if ( !array_key_exists( $input[$themeinput], $style_options ) )
			$input[$themeinput] = null;

		if ( !array_key_exists( $input[$fontheaderinput], $fontheader_options ) )
			$input[$fontheaderinput] = null;

		if ( !array_key_exists( $input[$fontbodyinput], $fontbody_options ) )
			$input[$fontbodyinput] = null;
	}*/

	// Textarea checks
	$input['adverttextarea'] = wp_kses( $input['adverttextarea'], 'framemarket_allow_script' );
	$input['googletextarea'] = wp_kses( $input['googletextarea'], 'framemarket_allow_script' );
	$input['footertextarea'] = wp_kses( $input['footertextarea'], 'framemarket_allow_script' );

	return $input;
}


function framemarket_wp_kses_allowed_html_allow_script( $tags, $context ){
	global $allowedposttags;
	if ( $context == 'framemarket_allow_script' ) {
		$tags = $allowedposttags;
		$tags['script'] = array('type' => true);
	}
	return $tags;
}
add_filter( 'wp_kses_allowed_html', 'framemarket_wp_kses_allowed_html_allow_script', 10, 2 );

?>