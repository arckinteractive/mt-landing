/*-----------------------------------------------------------------------------------

FILE INFORMATION

Description: JavaScript in the admin for the Woo_Layout extension.
Date Created: 2011-04-11.
Author: Matty.
Since: 4.0.0


TABLE OF CONTENTS

- Setup Layout Selector.
- Layout Selector Selection Event.
- Layout Toggle and Show Active Layout.
- Setup Layout Managers.
- Select Box Logic.
- Image Selector Logic.

- function update_dimensions() - Update dimensions.

-----------------------------------------------------------------------------------*/

jQuery(function($) {

	var logicType = '';

/*----------------------------------------
Setup Layout Selector.
----------------------------------------*/

var layoutSelector = '';
var currentLayoutWidth = jQuery( '.layout-width-value' ).text();
var currentLayoutType = jQuery( '#layout-type' ).attr( 'class' );

var imageDir = jQuery( 'input[name="woo-framework-image-dir"]' ).val();
jQuery( 'input[name="woo-framework-image-dir"]' ).remove();

var gutterSpacing = parseInt( jQuery( 'input[name="woo-gutter"]' ).val() );
jQuery( 'input[name="woo-gutter"]' ).remove();

if ( jQuery( '.section' ).length ) {

	// Create layout selector container.
	layoutSelector += '<h3>' + 'Select Layout' + '</h3><div class="layout-selector">';

	jQuery( '.layout.section' ).each( function () {

		var currentId = jQuery( this ).attr( 'id' );
		var selectedClass = '';
		var imageSelect = '1c';
		if ( currentId == currentLayoutType ) { selectedClass = ' active'; }

		switch ( currentId ) {

			case 'two-col-left':
				imageSelect = '2cl';
			break;

			case 'two-col-right':
				imageSelect = '2cr';
			break;

			case 'three-col-left':
				imageSelect = '3cl';
			break;

			case 'three-col-middle':
				imageSelect = '3cm';
			break;

			case 'three-col-right':
				imageSelect = '3cr';
			break;

		}

		layoutSelector += '<span class="' + currentId + '"><a href="#" id="' + currentId + '" class="layout-option ' + currentId + selectedClass + '">' + '<img src="' + imageDir + imageSelect + '.png" alt="' + jQuery( this ).find( '.heading' ).text() + '" />' + '</a></span>';

	});

	// Close layout selector container.
	layoutSelector += '<div class="clear"></div></div>';

	// Insert the layout selector code.
	jQuery( '#layout-width-notice' ).before( layoutSelector );

}

/*----------------------------------------
Layout Selector Selection Event.
----------------------------------------*/

if ( jQuery( '.layout-selector a.layout-option' ).length ) {
	jQuery( '.layout-selector a.layout-option' ).click( function () {
		jQuery( '.layout-selector a.layout-option.active' ).removeClass( 'active' );
		jQuery( this ).addClass( 'active' );

		var activeLayout = jQuery( this ).attr( 'id' );
		jQuery( '.layout.section:not(#' + activeLayout + ')' ).hide();
		jQuery( '.layout.section#' + activeLayout ).show();

		return false;
	});
}

/*----------------------------------------
Layout Toggle and Show Active Layout.
----------------------------------------*/

if ( jQuery( '.layout-selector a.layout-option.active' ).length && jQuery( '.layout.section' ).length ) {
	var activeLayout = jQuery( '.layout-selector a.layout-option.active' ).attr( 'id' );

	jQuery( '.layout.section:not(#' + activeLayout + ')' ).hide();
	jQuery( '.layout.section#' + activeLayout ).show();
}

/*----------------------------------------
Setup Layout Managers.
----------------------------------------*/

if ( jQuery( '.layout.section' ).length ) {
	jQuery( '.layout.section' ).each( function ( index, element ) {

		// Setup our layout DIV.
		var layoutDiv = jQuery( '<div />' ).addClass( 'layout-ui' ).css( 'height', '300' ).css( 'width', '596' );

		var divHtml = '';

		// Create the layout column DIVs dynamically.
		jQuery( this ).find( 'input.woo-input' ).each( function ( index, element ) {

			var divId = jQuery( this ).attr( 'id' );
			divId += '-column';
			var divClass = '';

			var columnName = jQuery( this ).prev( 'label' ).text();

			switch ( index ) {

				case 0:
					divClass += ' ui-layout-west';
				break;

				case 1:
					divClass += ' ui-layout-center';
				break;

				case 2:
					divClass += ' ui-layout-east';
				break;

			}

			divHtml += '<div id="' + divId + '" class="' + divClass + '"><span class="content">' + columnName + '<small>(approx. <span class="pixel-width">' + '' + '</span>%)</small></span></div>';

		});

		// Add the XHTML for display.
		layoutDiv.html( divHtml );

		if ( jQuery( layoutDiv ).find( 'div' ).length >= 1 ) {

			// Get the initial West and East dimensions.
			var westWidthPercent = jQuery( this ).find( '.controls input.woo-input:eq(0)' ).val();
			var centerWidthPercent = jQuery( this ).find( '.controls input.woo-input:eq(1)' ).val();
			var eastWidthPercent = jQuery( this ).find( '.controls input.woo-input:eq(2)' ).val();

			// Work out the pixel widths for the various columns.
			var onePercent = parseInt( layoutDiv.width() ) / 100;

			var westWidth = Math.ceil( onePercent * westWidthPercent );
			var eastWidth = Math.ceil( onePercent * eastWidthPercent );
			var centerWidth = parseInt( currentLayoutWidth ) - westWidthPercent - eastWidthPercent;

			centerWidth = Math.ceil( centerWidth );

			var layoutObj = layoutDiv.layout({
								closable:				false,
								resizable:				true,
								slidable:				false,
								resizeWhileDragging: 	true,
								west__resizable:		true, // Set to TRUE to activate dynamic margin
								east__resizable:		true, // Set to TRUE to activate dynamic margin
								east__resizerClass: 	'woo-resizer-east',
								west__resizerClass: 	'woo-resizer-west',
								east__size:				eastWidth,
								west__size:				westWidth,
								east__minSize:			10,
								west__minSize:			10,
								onresize: function ( name, element, state, options, layoutname ) {
											update_dimensions( element );
										  }
							});

			setup_dimensions();

		}

		// Add the layout DIV after the heading.
		jQuery( this ).find( '.heading' ).after( layoutDiv );

		// Hide the input fields and the explanation DIV.
		jQuery( this ).find( '.controls, .description' ).hide();

		// Set the dimensions display in the content area.
		jQuery( this ).find( 'div:eq(0) .pixel-width' ).text( westWidthPercent );
		jQuery( this ).find( 'div:eq(1) .pixel-width' ).text( centerWidthPercent );
		jQuery( this ).find( 'div:eq(2) .pixel-width' ).text( eastWidthPercent );

	});

}

/*----------------------------------------
Select Box Logic.
----------------------------------------*/

	if ( jQuery( 'select.woo-input' ).length ) {

		// Load the first item into a <span> tag above the select box.
		jQuery( 'select.woo-input' ).each( function () {

			var currentItemDisplay = jQuery( '<span></span>' );
			var currentItem = jQuery( this ).find( 'option:selected' );
			if ( ! currentItem ) {
				currentItem = jQuery( this ).find( 'option:eq(0)' );
			}

			currentItemDisplay.text( currentItem.text() );

			jQuery( this ).before( currentItemDisplay );

		});

		// Adjust the main item if the select box changes.
		jQuery( 'select.woo-input' ).change( function () {

			var selectedItem = jQuery( this ).find( 'option:selected' ).text();
			if ( selectedItem ) {
				jQuery( this ).prev( 'span' ).text( selectedItem );
			}

		});
	}

/*----------------------------------------
Image Selector Logic.
----------------------------------------*/

jQuery( '.woo-radio-img-img' ).click( function(){
	jQuery( this ).parent().parent().find( '.woo-radio-img-selected' ).removeClass( 'woo-radio-img-selected' );
	jQuery(this).addClass( 'woo-radio-img-selected' );

});
jQuery('.woo-radio-img-label').hide();
jQuery('.woo-radio-img-img').show();
jQuery('.woo-radio-img-radio').hide();

/*----------------------------------------
update_dimensions() - Update dimensions.
----------------------------------------*/

function update_dimensions ( element ) {

	var layoutParent = element.parents( '.layout-ui' );
	var layoutWidth = parseInt( layoutParent.width() );

	// Factor out the resizer.
	var resizerWidth = layoutParent.find( 'span.ui-draggable' ).width();
	var resizerPercentage = Math.ceil( ( resizerWidth / layoutWidth ) * 100 );

	var currentLayoutWidth = parseInt( jQuery( '.layout-width-value' ).text() );

	layoutParent.children( 'div' ).each( function () {

		var columnId = jQuery( this ).attr( 'id' );
		var inputId = columnId.replace( '-column', '' );
		var columnWidth = parseInt( jQuery( this ).width() );
		var newPercentage = ( columnWidth / layoutWidth ) * 100;

		newPercentage = Math.ceil( newPercentage );

		var onePercent = parseInt( currentLayoutWidth ) / 100;

		onePercent = Math.ceil( onePercent );

		var newPixelWidth = onePercent * newPercentage;

		jQuery( this ).find( '.pixel-width' ).text( newPercentage );

		jQuery( 'input#' + inputId ).val( newPercentage );

	});

} // End update_dimensions()

/*----------------------------------------
setup_dimensions() - Setup dimensions.
----------------------------------------*/

function setup_dimensions () {
	var layoutWidth = parseInt( jQuery( '.layout-ui' ).width() );

	var currentLayoutWidth = parseInt( jQuery( '.layout-width-value' ).text() );

	jQuery( '.layout-ui' ).each( function () {
		jQuery( this ).children( 'div' ).each( function () {

			// Factor out the resizer.
			// var resizerWidth = layoutParent.find( 'span.ui-draggable' ).width();
			// var resizerPercentage = Math.ceil( ( resizerWidth / layoutWidth ) * 100 );

			var fullWidth = currentLayoutWidth;
			var adjustedLayoutWidth = layoutWidth;

			jQuery( this ).parent( 'div' ).find( 'span' ).each( function ( i ) {
				fullWidth -= jQuery( this ).width();
				adjustedLayoutWidth -= jQuery( this ).width();
			});

			var columnId = jQuery( this ).attr( 'id' );
			var inputId = columnId.replace( '-column', '' );
			var columnWidth = parseInt( jQuery( this ).width() );

			var newPercentage = ( columnWidth / layoutWidth ) * 100;
			newPercentage = Math.ceil( newPercentage );

			var onePercent = parseInt( fullWidth ) / 100;
			onePercent = Math.ceil( onePercent );

			var newPixelWidth = onePercent * newPercentage;

			jQuery( this ).find( '.pixel-width' ).text( newPercentage );

		});

	});
} // End setup_dimensions()
}); // End jQuery()