var ga_data_ready = 0;
var ga_width;

google.load("visualization", "1", {packages:["corechart"]});

google.setOnLoadCallback(function() {
	if( jQuery('#google-analytics-statistics').length > 0 ) {
		if(typeof ga != 'undefined' && typeof ga.data != 'undefined') {
			ga_data_ready = ga.data;
			load_chart(ga_data_ready);
		}
		else
			load_dashboard_display();
	}
});
jQuery(document).ready(function() {
	setInterval(check_width, 200);
});
function check_width() {
	var current_width = jQuery("#google_analytics_chart").width();
	if(current_width != 100 && ga_width != current_width && ga_data_ready)
		load_chart(ga_data_ready);
}

function load_dashboard_display() {
	if(typeof ga != 'undefined' && typeof ga.post != 'undefined')
		post = ga.post;
	else
		post = 0;
	if(typeof ga != 'undefined' && typeof ga.network_admin != 'undefined')
		network_admin = ga.network_admin;
	else
		network_admin = 0;

	jQuery.post(ajaxurl, {action: 'load_google_analtics', post: post, network_admin: network_admin }, function(data){ //post data to specified action trough special WP ajax page
		var data = jQuery.parseJSON(data);
		jQuery('#google-analytics-statistics').hide();
		jQuery('#google-analytics-statistics').html(data['html']);
		jQuery('#google-analytics-statistics').slideDown('normal');

		ga_data_ready = data['data'];
		load_chart(ga_data_ready)
	});
}
function load_chart(data) {
	var data = google.visualization.arrayToDataTable(data);

	var options = {
		hAxis: {title: 'Year',  titleTextStyle: {color: '#333'}},
		vAxis: {minValue: 0},
		backgroundColor: 'none',
		legend: {position: 'bottom'},
		chartArea: {top: 10, left: 40, width: '95%'},
		focusTarget: 'category'
	};

	var chart = new google.visualization.AreaChart(document.getElementById('google_analytics_chart'));
	chart.draw(data, options);
	ga_width = jQuery("#google_analytics_chart").width();
}
