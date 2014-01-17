jQuery(document).ready( function($) {
	jQuery( '.content-section:not(:eq(0))' ).hide();
	jQuery( '.subsubsub a.tab' ).click( function ( e ) {
		// Move the "current" CSS class.
		jQuery( this ).parents( '.subsubsub' ).find( '.current' ).removeClass( 'current' );
		jQuery( this ).addClass( 'current' );

		// If the link is a tab, show only the specified tab.
		var toShow = jQuery( this ).attr( 'href' );

		// Remove the first occurance of # from the selected string (will be added manually below).
		toShow = toShow.replace( '#', '' );

		jQuery( '.content-section:not(#' + toShow + ')' ).hide();
		jQuery( '.content-section#' + toShow ).show();

		return false;
	});
});