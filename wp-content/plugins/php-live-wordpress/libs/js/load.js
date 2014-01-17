unixtime = function() { return parseInt(new Date().getTime().toString().substring(0, 10)) ; }

function phplive_wp_sethtml()
{
	var htmlcode = jQuery('#phplive_html_code').val() ;
	var htmlcode = escape( htmlcode.replace( /\+/g, "%plus%" ) ) ;
	//var showhide = ( jQuery('#phplive_url_show').attr( "checked" ) ) ? "show" : "hide" ;
	var showhide = "show" ;

	if ( htmlcode.indexOf( "phplive" ) != -1 )
	{
		var data = {
			action: 'my_action',
			action_sub: 'set_html',
			phplive_html_code: htmlcode,
			phplive_url_showhide: showhide
		} ;

		jQuery.post( ajaxurl, data, function(response) {
			eval( response ) ;

			if ( json_data.status )
				jQuery('#div_alert').show().fadeOut("slow").fadeIn("fast").delay(3000).fadeOut("slow").hide() ;
			else
				alert( json_data.error ) ;
		});
	}
	else
	{
		if ( htmlcode )
			alert( "Invalid PHP HTML Code format.  Please copy the code exactly as it is displayed from the PHP Live! Setup area." ) ;
		else
			alert( "Blank HTML Code is invalid." ) ;
	}
}

function phplive_wp_reset()
{
	var unique = unixtime() ;
	var data = {
		action: 'my_action',
		action_sub: 'reset',
	} ;

	if ( confirm( "Reset the PHP Live! HTML Code?" ) )
	{
		jQuery.post( ajaxurl, data, function(response) {
			eval( response ) ;

			if ( json_data.status )
				location.href = location.href+"&"+unique ;
			else
				alert( json_data.error ) ;
		});
	}
}


function phplive_wp_launch( thediv )
{
	var divs = Array( "html", "settings" ) ;
	for ( c = 0; c < divs.length; ++c )
		jQuery('#menu_'+divs[c]).removeClass('phplive_wp_menu_focus').addClass('phplive_wp_menu') ;

	jQuery('#menu_'+thediv).removeClass('phplive_wp_menu').addClass('phplive_wp_menu_focus') ;

	if ( thediv == "html" )
	{
		
		jQuery('#phplive_setup_body_settings').hide() ;
		jQuery('#phplive_setup_body_html').show() ;
	}
	else if ( thediv == "settings" )
	{
		jQuery('#phplive_setup_body_html').hide() ;
		jQuery('#phplive_setup_body_settings').show() ;
	}
}

(function() {
	phplive_wp_launch( "html" ) ;
})();
