<?php
include(get_stylesheet_directory() . '/options/options-values.php');

$themename = wp_get_theme();

$section = $themename.'_style';

framemarket_customize_add_section($wp_customize, array(
	'section' => $section,
	'title' => __('Theme Styling', 'framemarket'),
	'priority' => 30
));
$style_choices = array();
foreach ( $style_options as $option ) {
	$style_choices[$option['value']] = $option['label'];
}
framemarket_customize_add_option( $wp_customize, array(
	'id' => $themename . '_styleinput',
	'type' => 'select',
	'section' => $section,
	'label' => __( 'Select base style', 'framemarket'),
	'choices' => $style_choices,
	'priority' => 1,
	//'transport' => 'refresh'
) );
framemarket_customize_add_option( $wp_customize, array(
	'id' => $themename . '_maincolor',
	'type' => 'color',
	'section' => $section,
	'label' => __( 'Pick a main color', 'framemarket'),
	'priority' => 2
) );
framemarket_customize_add_option( $wp_customize, array(
	'id' => $themename . '_subcolor',
	'type' => 'color',
	'section' => $section,
	'label' => __( 'Pick a sub color', 'framemarket'),
	'priority' => 3
) );
framemarket_customize_add_option( $wp_customize, array(
	'id' => $themename . '_mainfontcolor',
	'type' => 'color',
	'section' => $section,
	'label' => __( 'Pick a main font color', 'framemarket'),
	'priority' => 4
) );
framemarket_customize_add_option( $wp_customize, array(
	'id' => $themename . '_subfontcolor',
	'type' => 'color',
	'section' => $section,
	'label' => __( 'Pick a sub font color', 'framemarket'),
	'priority' => 5
) );
framemarket_customize_add_option( $wp_customize, array(
	'id' => $themename . '_subbackgroundcolor',
	'type' => 'color',
	'section' => $section,
	'label' => __( 'Pick a navigation, body and footer wrapper color', 'framemarket'),
	'priority' => 6
) );
framemarket_customize_add_option( $wp_customize, array(
	'id' => $themename . '_subbackgroundhovercolor',
	'type' => 'color',
	'section' => $section,
	'label' => __( 'Pick a navigation, body and footer wrapper hover color', 'framemarket'),
	'priority' => 7
) );
framemarket_customize_add_option( $wp_customize, array(
	'id' => $themename . '_subbackgroundfontcolor',
	'type' => 'color',
	'section' => $section,
	'label' => __( 'Pick a navigation, body and footer wrapper font color', 'framemarket'),
	'priority' => 8
) );
$fontheader_choices = array();
foreach ( $fontheader_options as $option ) {
	$fontheader_choices[$option['value']] = $option['label'];
}
framemarket_customize_add_option( $wp_customize, array(
	'id' => $themename . '_fontheaderinput',
	'type' => 'select',
	'section' => $section,
	'label' => __( 'Pick a header font', 'framemarket'),
	'choices' => $fontheader_choices,
	'priority' => 9
) );
$fontbody_choices = array();
foreach ( $fontbody_options as $option ) {
	$fontbody_choices[$option['value']] = $option['label'];
}
framemarket_customize_add_option( $wp_customize, array(
	'id' => $themename . '_fontbodyinput',
	'type' => 'select',
	'section' => $section,
	'label' => __( 'Pick a body font', 'framemarket'),
	'choices' => $fontbody_choices,
	'priority' => 10
) );


?>