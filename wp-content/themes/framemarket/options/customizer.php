<?php

/*
 * Custom control class
 *
 * Add description on control
 * */
if ( class_exists('WP_Customize_Control') ) {
class WPMUDEV_Customize_Control extends WP_Customize_Control {

	public $description = '';

	protected function render_content() {
		switch( $this->type ) {
			default:
				return parent::render_content();
			case 'text':
				?>
				<label>
					<span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
					<?php if ( isset($this->description) && !empty($this->description) ): ?>
					<span class="customize-control-description"><?php echo $this->description ?></span>
					<?php endif ?>
					<input type="text" value="<?php echo esc_attr( $this->value() ); ?>" <?php $this->link(); ?> />
				</label>
				<?php
				break;
			case 'checkbox':
				?>
				<label>
					<input type="checkbox" value="<?php echo esc_attr( $this->value() ); ?>" <?php $this->link(); checked( $this->value() ); ?> />
					<?php echo esc_html( $this->label ); ?>
				</label>
				<?php if ( isset($this->description) && !empty($this->description) ): ?>
				<span class="customize-control-description"><?php echo $this->description ?></span>
				<?php endif ?>
				<?php
				break;
			case 'radio':
				if ( empty( $this->choices ) )
					return;

				$name = '_customize-radio-' . $this->id;

				?>
				<span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
				<?php if ( isset($this->description) && !empty($this->description) ): ?>
				<span class="customize-control-description"><?php echo $this->description ?></span>
				<?php endif ?>
				<?php
				foreach ( $this->choices as $value => $label ) :
					?>
					<label>
						<input type="radio" value="<?php echo esc_attr( $value ); ?>" name="<?php echo esc_attr( $name ); ?>" <?php $this->link(); checked( $this->value(), $value ); ?> />
						<?php echo esc_html( $label ); ?><br/>
					</label>
					<?php
				endforeach;
				break;
			case 'custom-radio':
				if ( empty( $this->choices ) )
					return;

				$name = '_customize-radio-' . $this->id;

				?>
				<span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
				<?php if ( isset($this->description) && !empty($this->description) ): ?>
				<span class="customize-control-description"><?php echo $this->description ?></span>
				<?php endif ?>
				<?php
				foreach ( $this->choices as $value => $label ) :
					$screenshot_img = substr($value,0,-4);
					?>
					<label>
						<div class="theme-img">
							<img src="<?php echo get_template_directory_uri(); ?>/_inc/preset-styles/images/<?php echo $screenshot_img . '.png'; ?>" alt="<?php echo $screenshot_img ?>" />
						</div>
						<input type="radio" value="<?php echo esc_attr( $value ); ?>" name="<?php echo esc_attr( $name ); ?>" <?php $this->link(); checked( $this->value(), $value ); ?> />
						<?php echo esc_html( $label ); ?><br/>
					</label>
					<?php
				endforeach;
				break;
			case 'select':
				if ( empty( $this->choices ) )
					return;

				?>
				<label>
					<span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
					<?php if ( isset($this->description) && !empty($this->description) ): ?>
					<span class="customize-control-description"><?php echo $this->description ?></span>
					<?php endif ?>
					<select <?php $this->link(); ?>>
						<?php
						foreach ( $this->choices as $value => $label )
							echo '<option value="' . esc_attr( $value ) . '"' . selected( $this->value(), $value, false ) . '>' . $label . '</option>';
						?>
					</select>
				</label>
				<?php
				break;
			// Handle textarea
			case 'textarea':
				?>
				<label>
					<span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
					<?php if ( isset($this->description) && !empty($this->description) ): ?>
					<span class="customize-control-description"><?php echo $this->description ?></span>
					<?php endif ?>
					<textarea rows="10" cols="40" <?php $this->link(); ?>><?php echo esc_attr( $this->value() ); ?></textarea>
				</label>
				<?php
				break;
		}
	}

}
}

if ( class_exists('WP_Customize_Color_Control') ) {
class WPMUDEV_Customize_Color_Control extends WP_Customize_Color_Control {

	public $description = '';

	public function render_content() {
		$this_default = $this->setting->default;
		$default_attr = '';
		if ( $this_default ) {
			if ( false === strpos( $this_default, '#' ) )
				$this_default = '#' . $this_default;
			$default_attr = ' data-default-color="' . esc_attr( $this_default ) . '"';
		}
		// The input's value gets set by JS. Don't fill it.
		?>
		<label>
			<span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
			<?php if ( isset($this->description) && !empty($this->description) ): ?>
			<span class="customize-control-description"><?php echo $this->description ?></span>
			<?php endif ?>
			<div class="customize-control-content">
				<input class="color-picker-hex" type="text" maxlength="7" placeholder="<?php esc_attr_e( 'Hex Value' ); ?>"<?php echo $default_attr ?> />
			</div>
		</label>
		<?php
	}
}
}

if ( class_exists('WP_Customize_Image_Control') ) {

class WPMUDEV_Customize_Image_Control extends WP_Customize_Image_Control {

	public $description = '';

	public function render_content() {
		$src = $this->value();
		if ( isset( $this->get_url ) )
			$src = call_user_func( $this->get_url, $src );

		?>
		<div class="customize-image-picker">
			<span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
			<?php if ( isset($this->description) && !empty($this->description) ): ?>
			<span class="customize-control-description"><?php echo $this->description ?></span>
			<?php endif ?>

			<div class="customize-control-content">
				<div class="dropdown preview-thumbnail" tabindex="0">
					<div class="dropdown-content">
						<?php if ( empty( $src ) ): ?>
							<img style="display:none;" />
						<?php else: ?>
							<img src="<?php echo esc_url( set_url_scheme( $src ) ); ?>" />
						<?php endif; ?>
						<div class="dropdown-status"></div>
					</div>
					<div class="dropdown-arrow"></div>
				</div>
			</div>

			<div class="library">
				<ul>
					<?php foreach ( $this->tabs as $id => $tab ): ?>
						<li data-customize-tab='<?php echo esc_attr( $id ); ?>' tabindex='0'>
							<?php echo esc_html( $tab['label'] ); ?>
						</li>
					<?php endforeach; ?>
				</ul>
				<?php foreach ( $this->tabs as $id => $tab ): ?>
					<div class="library-content" data-customize-tab='<?php echo esc_attr( $id ); ?>'>
						<?php call_user_func( $tab['callback'] ); ?>
					</div>
				<?php endforeach; ?>
			</div>

			<div class="actions">
				<a href="#" class="remove"><?php _e( 'Remove Image' ); ?></a>
			</div>
		</div>
		<?php
	}
}
}

function framemarket_customize_add_section( $wp_customize, $args ) {
	$defaults = array(
		'section' => '',
		'title' => '',
		'priority' => 30
	);
  	$r = wp_parse_args( $args, $defaults );
	$wp_customize->add_section( $r['section'], array(
		'title' => $r['title'],
		'priority' => $r['priority']
	) );
}

function framemarket_customize_add_option( $wp_customize, $args ) {
	$defaults = array(
		'id' => '',
		'type' => 'text',
		'transport' => 'postMessage',
		'section' => '',
		'label' => '',
		'description' => '',
		'choices' => array(),
		'default' => '',
		'priority' => 10
	);
  	$r = wp_parse_args( $args, $defaults );
	$wp_customize->add_setting( 'framemarket_theme_options['.$r['id'].']', array(
		'default' => $r['default'],
		'type' => 'option',
		'capability' => 'edit_theme_options',
		'transport' => $r['transport']
	) );
	$control_param = array(
		'label' => strip_tags($r['label']),
		'description' => $r['description'],
		'section' => $r['section'],
		'settings' => 'framemarket_theme_options['.$r['id'].']',
		'priority' => $r['priority']
	);
	if ( $r['type'] == 'color' ) {
		$wp_customize->add_control( new WPMUDEV_Customize_Color_Control( $wp_customize, $r['id'].'_control', $control_param ) );
	}
	else if ( $r['type'] == 'image' ) {
		$control_param['type'] = $r['type'];
		$wp_customize->add_control( new WPMUDEV_Customize_Image_Control( $wp_customize, $r['id'].'_control', $control_param) );
	}
	else if ( $r['type'] == 'text' || $r['type'] == 'textarea' ) {
		$control_param['type'] = $r['type'];
		$wp_customize->add_control( new WPMUDEV_Customize_Control( $wp_customize, $r['id'].'_control', $control_param) );
	}
	else if ( $r['type'] == 'custom-radio' ) {
		$control_param['type'] ='custom-radio';
		// @TODO choices might get removed in future
		$control_param['choices'] = $r['choices'];
		$wp_customize->add_control( new WPMUDEV_Customize_Control( $wp_customize, $r['id'].'_control', $control_param) );
	}
	else if ( $r['type'] == 'select' || $r['type'] == 'select-preview' ) {
		$control_param['type'] = 'select';
		// @TODO choices might get removed in future
		$control_param['choices'] = $r['choices'];
		$wp_customize->add_control( new WPMUDEV_Customize_Control( $wp_customize, $r['id'].'_control', $control_param) );
	}
}


function framemarket_customize_register( $wp_customize ) {
	// Support Wordpress custom background
	$wp_customize->get_setting('background_color')->transport = 'postMessage';
	$wp_customize->get_setting('background_image')->transport = 'postMessage';
	$wp_customize->get_setting('background_repeat')->transport = 'postMessage';
	$wp_customize->get_setting('background_position_x')->transport = 'postMessage';
	$wp_customize->get_setting('background_attachment')->transport = 'postMessage';
	$wp_customize->get_setting('header_image')->transport = 'postMessage';
	$wp_customize->get_setting('blogname')->transport = 'postMessage';
	$wp_customize->get_setting('blogdescription')->transport = 'postMessage';


	include(get_stylesheet_directory() . '/options/customizer-option.php');
}
add_action('customize_register', 'framemarket_customize_register');

function framemarket_customize_preview() {
	global $options, $shortname, $shortprefix;
	?>
	<div id="theme-customizer-css"></div>

	<script type="text/javascript">
		var themename = "<?php echo wp_get_theme() ?>";
		var style_url = "<?php echo get_stylesheet_directory_uri() ?>";
	</script>
	<?php
}

function framemarket_customize_init() {
	add_action('wp_footer', 'framemarket_customize_preview', 21);
	wp_enqueue_script( 'framemarket-theme-customizer', get_template_directory_uri() . '/options/customizer.js', array( 'customize-preview' ), rand(1,9999999) );
	wp_enqueue_script( 'framemarket-theme-customizer-option', get_stylesheet_directory_uri() . '/options/customizer-option.js', array( 'customize-preview' ), rand(1,9999999) );
}
add_action( 'customize_preview_init', 'framemarket_customize_init' );

// Add additional styling to better fit on Customizer options
function  framemarket_customize_controls_footer() {
	?>
	<style type="text/css">
		.customize-control-title { line-height: 18px; padding: 2px 0; }
		.customize-control-description { font-size: 11px; color: #666; margin: 0 0 3px; display: block; }
		.customize-control input[type="text"], .customize-control textarea { width: 98%; line-height: 18px; margin: 0; }
	</style>
	<?php
}
add_action('customize_controls_print_footer_scripts', 'framemarket_customize_controls_footer');

?>