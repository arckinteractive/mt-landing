<?php
	// custom styling allowing color picking and creating your own version
	$options = get_option('framemarket_theme_options');

	$themename = wp_get_theme();
	$themeinput = $themename . '_styleinput';
	$maincolor = $themename . '_maincolor';
	$subcolor = $themename . '_subcolor';
	$mainfontcolor = $themename . '_mainfontcolor';
	$subfontcolor = $themename . '_subfontcolor';
	$subbackgroundcolor = $themename . '_subbackgroundcolor';
	$subbackgroundhovercolor = $themename . '_subbackgroundhovercolor';
	$subbackgroundfontcolor = $themename . '_subbackgroundfontcolor';
	$fontheaderinput = $themename . '_fontheaderinput';
	$fontbodyinput = $themename . '_fontbodyinput';

	$maincolor = isset($options[$maincolor]) ? $options[$maincolor] : '';
	$subcolor = isset($options[$subcolor]) ? $options[$subcolor] : '';
	$mainfontcolor = isset($options[$mainfontcolor]) ? $options[$mainfontcolor] : '';
	$subfontcolor = isset($options[$subfontcolor]) ? $options[$subfontcolor] : '';
	$subbackgroundcolor = isset($options[$subbackgroundcolor]) ? $options[$subbackgroundcolor] : '';
	$subbackgroundhovercolor = isset($options[$subbackgroundhovercolor]) ? $options[$subbackgroundhovercolor] : '';
	$subbackgroundfontcolor = isset($options[$subbackgroundfontcolor]) ? $options[$subbackgroundfontcolor] : '';
	$fonttype = isset($options[$fontheaderinput]) ? $options[$fontheaderinput] : '';
	$bodytype = isset($options[$fontbodyinput]) ? $options[$fontbodyinput] : '';
?>
<?php
	if (($maincolor != "") || ($subcolor != "") || ($mainfontcolor != "") || ($subfontcolor != "") || ($subbackgroundcolor != "") || ($subbackgroundhovercolor != "") || ($subbackgroundfontcolor) || ($fonttype != "") || ($bodytype != "")){
?>
<style type="text/css" media="screen">
<?php if ($mainfontcolor != "") { ?>
a, a:link, a:visited,
#header,
#mp-storepicker,
.nav ul a:hover
{
	color: <?php echo $maincolor; ?>;
}
<?php } ?>
<?php if ($mainfontcolor != "") { ?>
a:hover{
	color: <?php echo $subcolor;  ?>;
}
<?php } ?>
<?php if ($maincolor != "") { ?>
.reply a,
input[type="submit"], a.button, a:visited.button, a:link.button,
.mp_cart_actions_widget a, .mp_cart_actions_widget a:link, .mp_cart_actions_widget a:visited,
.mp_button_addcart, .mp_button_buynow, .mp_cart_col_updatecart input[type="submit"],
#mp_shipping_submit, #mp_payment_confirm,
a.mp_link_addcart, a.mp_link_buynow,
form.mp_buy_form .mp_adding_to_cart,
a.mp_cart_direct_checkout_link
{
	 background: none repeat scroll 0 0 <?php echo $maincolor; ?>;
	 border: 1px solid <?php echo $maincolor; ?>;
	 color: <?php echo $mainfontcolor; ?>;
}
<?php } ?>
<?php if ($mainfontcolor != "") { ?>
.reply a:hover, .reply a,
a.mp_cart_direct_checkout_link{
	color: <?php echo $mainfontcolor; ?> !important;
}
<?php } ?>
<?php if ($mainfontcolor != "") { ?>
#site-logo{
	color: <?php echo $mainfontcolor; ?>;
	text-shadow: -2px -2px 0px <?php echo $subcolor; ?>;
}
<?php } ?>
<?php if ($subcolor != "") { ?>
a.mp_cart_direct_checkout_link:hover{
	border-color: <?php echo $subcolor; ?>;
}
<?php } ?>
<?php if ($subcolor != "") { ?>
.reply a:hover,
input[type="submit"]:hover, a:hover.button,
.mp_cart_actions_widget a:hover,
.mp_button_addcart:hover, .mp_button_buynow:hover, .mp_cart_col_updatecart input[type="submit"]:hover,
#mp_shipping_submit:hover, #mp_payment_confirm:hover,
a.mp_link_addcart:hover, a.mp_link_buynow:hover,
a.mp_cart_direct_checkout_link:hover
{
	 background: none repeat scroll 0 0 <?php echo $subcolor; ?>;
	 border: 1px solid <?php echo $subcolor; ?>;
	 color: <?php echo $subfontcolor; ?>;
}
<?php } ?>
<?php if ($maincolor != "") { ?>
#header-wrapper{
	border-bottom: 1px solid <?php echo $maincolor; ?>;
}
<?php } ?>
<?php if ($maincolor != "") { ?>
#branding-wrapper,
.nav .current a, .nav li:hover > a, .nav li.current_page_item a,
.nav ul li:hover a, .nav li:hover li a {
	background: <?php echo $maincolor; ?>;
	color: <?php echo $mainfontcolor; ?>;
}
<?php } ?>
<?php if ($subcolor != "") { ?>
.nav ul li:hover a
{
	background: <?php echo $subcolor; ?>;
}
<?php } ?>
<?php if ($subbackgroundcolor != "") { ?>
#navigation-wrapper,
body,
#shopping-cart,
#header-wrapper,
#footer-wrapper{
	background: <?php echo $subbackgroundcolor; ?>;
}
<?php } ?>
<?php if ($subbackgroundhovercolor != "") { ?>
#panel {
	background: <?php echo $subbackgroundhovercolor; ?>;
}
<?php } ?>
<?php if ($subbackgroundfontcolor != "") { ?>
#footer a:hover, #header-wrapper a:hover, #panel a:hover,
.footer-widget,
#footer-widgets h3, #panel-inner,
#footer caption {
	color: <?php echo $subbackgroundfontcolor; ?>;
}
<?php } ?>
</style>
<?php
}
?>