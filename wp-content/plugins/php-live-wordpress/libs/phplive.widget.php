<?php
	/* (c) OSI Codes Inc. */
	/* http://www.osicodesinc.com */
	/* Dev team: 615 */
	CLASS phplive_widget EXTENDS WP_Widget
	{
		function phplive_widget()
		{
			parent::WP_Widget( 'phplive_widget', 'PHP Live!', array( 'classname' => 'phplive_widget', 'description' => 'Integrate PHP Live! with WordPress' ) ) ;
		}
		function form( $instance ){ }
		function widget( $args, $instance ) { phplive::get_instance()->widget_fetch_phplive_html_code() ; }
	}
?>