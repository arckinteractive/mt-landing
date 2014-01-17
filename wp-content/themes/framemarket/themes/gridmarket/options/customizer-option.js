jQuery(document).ready( function($){
	var settings = [
		{
			setting: 'framemarket_theme_options['+themename+'_styleinput]',
			callback: function(to){
				$('#gridmarket_style-css').attr('href', style_url+'/styles/'+to+'.css');
			}
		},
		{
			setting: 'framemarket_theme_options['+themename+'_maincolor]',
			callback: function(to){
				theme_queue_style('a, a:link, a:visited, #header, #mp-storepicker, .nav ul a:hover', 'color', to, '');
				theme_queue_style('.reply a, input[type="submit"], a.button, a:visited.button, a:link.button, .mp_cart_actions_widget a, .mp_cart_actions_widget a:link, .mp_cart_actions_widget a:visited, .mp_button_addcart, .mp_button_buynow, .mp_cart_col_updatecart input[type="submit"],  #mp_shipping_submit, #mp_payment_confirm, a.mp_link_addcart, a.mp_link_buynow, form.mp_buy_form .mp_adding_to_cart, a.mp_cart_direct_checkout_link', 'background-color', to, '');
				theme_queue_style('.reply a, input[type="submit"], a.button, a:visited.button, a:link.button, .mp_cart_actions_widget a, .mp_cart_actions_widget a:link, .mp_cart_actions_widget a:visited, .mp_button_addcart, .mp_button_buynow, .mp_cart_col_updatecart input[type="submit"],  #mp_shipping_submit, #mp_payment_confirm, a.mp_link_addcart, a.mp_link_buynow, form.mp_buy_form .mp_adding_to_cart, a.mp_cart_direct_checkout_link', 'border-color', to, '');
				theme_queue_style('.reply a, input[type="submit"], a.button, a:visited.button, a:link.button, .mp_cart_actions_widget a, .mp_cart_actions_widget a:link, .mp_cart_actions_widget a:visited, .mp_button_addcart, .mp_button_buynow, .mp_cart_col_updatecart input[type="submit"],  #mp_shipping_submit, #mp_payment_confirm, a.mp_link_addcart, a.mp_link_buynow, form.mp_buy_form .mp_adding_to_cart, a.mp_cart_direct_checkout_link', 'color', wp.customize( 'framemarket_theme_options['+themename+'_mainfontcolor]' ).get(), '');
				theme_queue_style('#header-wrapper', 'border-bottom-color', to, '');
				theme_queue_style('#branding-wrapper, .nav .current a, .nav li:hover > a, .nav li.current_page_item a, .nav ul li:hover a, .nav li:hover li a', 'background-color', to, '');
				theme_update_css();
			}
		},
		{
			setting: 'framemarket_theme_options['+themename+'_subcolor]',
			callback: function(to){
				theme_queue_style('a:hover', 'color', to, '');
				theme_queue_style('#site-logo', 'text-shadow', '-2px -2px 0px '+to, '');
				theme_queue_style('a.mp_cart_direct_checkout_link:hover', 'border-color', to, '');
				theme_queue_style('.reply a:hover, input[type="submit"]:hover, a:hover.button, .mp_cart_actions_widget a:hover, .mp_button_addcart:hover, .mp_button_buynow:hover, .mp_cart_col_updatecart input[type="submit"]:hover,  #mp_shipping_submit:hover, #mp_payment_confirm:hover, a.mp_link_addcart:hover, a.mp_link_buynow:hover, a.mp_cart_direct_checkout_link:hover', 'background-color', to, '');
				theme_queue_style('.reply a:hover, input[type="submit"]:hover, a:hover.button, .mp_cart_actions_widget a:hover, .mp_button_addcart:hover, .mp_button_buynow:hover, .mp_cart_col_updatecart input[type="submit"]:hover,  #mp_shipping_submit:hover, #mp_payment_confirm:hover, a.mp_link_addcart:hover, a.mp_link_buynow:hover, a.mp_cart_direct_checkout_link:hover', 'border-color', to, '');
				theme_queue_style('.nav ul li:hover a', 'background-color', to, '');
				theme_update_css();
			}
		},
		{
			setting: 'framemarket_theme_options['+themename+'_mainfontcolor]',
			callback: function(to){
				theme_queue_style('.reply a, input[type="submit"], a.button, a:visited.button, a:link.button, .mp_cart_actions_widget a, .mp_cart_actions_widget a:link, .mp_cart_actions_widget a:visited, .mp_button_addcart, .mp_button_buynow, .mp_cart_col_updatecart input[type="submit"],  #mp_shipping_submit, #mp_payment_confirm, a.mp_link_addcart, a.mp_link_buynow, form.mp_buy_form .mp_adding_to_cart, a.mp_cart_direct_checkout_link', 'color', to, '');
				theme_queue_style('.reply a:hover, .reply a, a.mp_cart_direct_checkout_link', 'color', to, '!important');
				theme_queue_style('#site-logo', 'color', to, '');
				theme_queue_style('#branding-wrapper, .nav .current a, .nav li:hover > a, .nav li.current_page_item a, .nav ul li:hover a, .nav li:hover li a', 'color', to, '');
				theme_update_css();
			}
		},
		{
			setting: 'framemarket_theme_options['+themename+'_subfontcolor]',
			callback: function(to){
				theme_change_style('.reply a:hover, input[type="submit"]:hover, a:hover.button, .mp_cart_actions_widget a:hover, .mp_button_addcart:hover, .mp_button_buynow:hover, .mp_cart_col_updatecart input[type="submit"]:hover,  #mp_shipping_submit:hover, #mp_payment_confirm:hover, a.mp_link_addcart:hover, a.mp_link_buynow:hover, a.mp_cart_direct_checkout_link:hover', 'color', to, '');
			}
		},
		{
			setting: 'framemarket_theme_options['+themename+'_subbackgroundcolor]',
			callback: function(to){
				theme_change_style('#navigation-wrapper, body, #shopping-cart, #header-wrapper, #footer-wrapper', 'background-color', to, '');
			}
		},
		{
			setting: 'framemarket_theme_options['+themename+'_subbackgroundhovercolor]',
			callback: function(to){
				theme_change_style('#panel', 'background-color', to, '');
			}
		},
		{
			setting: 'framemarket_theme_options['+themename+'_subbackgroundfontcolor]',
			callback: function(to){
				theme_change_style('#footer a:hover, #header-wrapper a:hover, #panel a:hover, .footer-widget, #footer-widgets h3, #panel-inner, #footer caption', 'color', to, '');
			}
		},
		{
			setting: 'framemarket_theme_options['+themename+'_fontheaderinput]',
			callback: function(to){
				if ( to == 'Choose a font' )
					theme_change_font_family('h1, h2, h3, h4, h5, h6, #site-logo', '', '');
				else
					theme_change_font_family('h1, h2, h3, h4, h5, h6, #site-logo', to, '');
			}
		},
		{
			setting: 'framemarket_theme_options['+themename+'_fontbodyinput]',
			callback: function(to){
				if ( to == 'Choose a font' )
					theme_change_font_family('body', '', '');
				else
					theme_change_font_family('body', to, '');
			}
		},
	];
	
	theme_bind_customize( settings );
} );