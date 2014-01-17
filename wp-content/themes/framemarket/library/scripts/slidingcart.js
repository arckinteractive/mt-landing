jQuery(document).ready(function() {
	
	// Expand Panel
	jQuery("#open").click(function(){
		jQuery("div#panel").slideDown("slow");
		return false;
	});
		
	// Collapse Panel
	jQuery("#close").click(function(){
		jQuery("div#panel").slideUp("slow");		
		return false;	
	});
		
	jQuery("#toggle a").click(function () {
		jQuery("#toggle a").toggle();
		return false;
	});
		
	jQuery("#close").click(function(){
		jQuery("div#panel").slideUp("slow");		
		return false;	
	});
		
	jQuery("#panel").bind("html", function () {
		jQuery("#cart-contents").load(window.location+" #cart-contents span");
	}).initMutation('html');
});
