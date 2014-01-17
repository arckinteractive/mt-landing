jQuery(document).ready(function($){

	$.extend({
		getParameterByName: function(name) {
			name = name.replace(/[\[]/, '\\\[').replace(/[\]]/, '\\\]');
			var regexS = '[\\?&]' + name + '=([^&#]*)';
			var regex = new RegExp(regexS);
			var results = regex.exec(window.location.search);
			if(results == null) {
				return '';
			} else {
				return decodeURIComponent(results[1].replace(/\+/g, ' '));
			}
		},
		showHideSubscriptionMeta: function(){
			if ($('select#product-type').val()==WCSubscriptions.productType) {
				$('.show_if_simple').show();
				$('.show_if_subscription').show();
				$('.grouping_options').hide();
				$('.options_group.pricing ._regular_price_field').hide();
				$('#sale-price-period').show();
			} else {
				$('.show_if_subscription').hide();
				$('.options_group.pricing ._regular_price_field').show();
				$('#sale-price-period').hide();
			}
		},
		showHideVariableSubscriptionMeta: function(){
			if ($('select#product-type').val()=='variable-subscription') {
				$('.show_if_variable').show();
				$('.show_if_variable-subscription').show();
				$('.hide_if_variable-subscription').hide();
			} else {
				$('.show_if_variable-subscription').hide();
				$('.hide_if_variable-subscription').show();
			}
			if ($('select#product-type').val()==WCSubscriptions.productType || $('select#product-type').val()=='variable-subscription') {
				$('._sold_individually_field').hide();
				$('.limit_subscription').show();
			} else {
				$('._sold_individually_field').show();
				$('.limit_subscription').hide();
			}
		},
		setSubscriptionLengths: function(){
			$('[name^="_subscription_length"], [name^="variable_subscription_length"]').each(function(){
				var $lengthElement = $(this),
					selectedLength = $lengthElement.val(),
					matches = $lengthElement.attr('name').match(/\[(.*?)\]/),
					periodSelector,
					interval;

				if (matches) { // Variation
					periodSelector = '[name="variable_subscription_period['+matches[1]+']"]';
					billingInterval = parseInt($('[name="variable_subscription_period_interval['+matches[1]+']"]').val());
				} else {
					periodSelector = '#_subscription_period';
					billingInterval = parseInt($('#_subscription_period_interval').val());
				}

				$lengthElement.empty();

				$.each(WCSubscriptions.subscriptionLengths[$(periodSelector).val()], function(length,description) {
					if(parseInt(length) == 0 || 0 == (parseInt(length) % billingInterval))
						$lengthElement.append($('<option></option>').attr('value',length).text(description));
				});

				$lengthElement.val(selectedLength);

			});
		},
		setTrialPeriods: function(){
			$('[name^="_subscription_trial_length"], [name^="variable_subscription_trial_length"]').each(function(){
				var $trialLengthElement = $(this),
					trialLength = $trialLengthElement.val(),
					matches = $trialLengthElement.attr('name').match(/\[(.*?)\]/),
					periodStrings;

				if (matches) { // Variation
					$trialPeriodElement = $('[name="variable_subscription_trial_period['+matches[1]+']"]');
				} else {
					$trialPeriodElement = $('#_subscription_trial_period');
				}

				selectedTrialPeriod = $trialPeriodElement.val();

				$trialPeriodElement.empty();

				if( parseInt(trialLength) == 1 ) {
					periodStrings = WCSubscriptions.trialPeriodSingular;
				} else {
					periodStrings = WCSubscriptions.trialPeriodPlurals;
				}

				$.each(periodStrings, function(key,description) {
					$trialPeriodElement.append($('<option></option>').attr('value',key).text(description));
				});

				$trialPeriodElement.val(selectedTrialPeriod);
			});
		},
		setSalePeriod: function(){
			$('#sale-price-period').fadeOut(80,function(){
				$('#sale-price-period').text($('#_subscription_period_interval option:selected').text()+' '+$('#_subscription_period option:selected').text());
				$('#sale-price-period').fadeIn(180);
			});
		},
		hideAddItemButton: function(){
			$('#woocommerce-order-items #add_item_id').hide();
			$('#woocommerce-order-items #add_item_id_chzn').hide();
			$('#woocommerce-order-items .add_shop_order_item').hide(); // WC 1.x
			$('#woocommerce-order-items .add_order_item').hide(); // WC 2.0+
		},
		showAddItemButton: function(){
			$('#woocommerce-order-items #add_item_id_chzn').show();
			$('#woocommerce-order-items .add_shop_order_item').show(); // WC 1.x
			$('#woocommerce-order-items .add_order_item').show(); // WC 2.0+
		},
		moveSubscriptionVariationFields: function(){
			$('#variable_product_options tr.variable_subscription_pricing').not('wcs_moved').each(function(){
				var $regularPriceRow = $(this).siblings('tr.sale_price_dates_fields').prev('tr'),
					$firstRow = $regularPriceRow.parent().children('tr:first'),
					$trialPeriodCell = $(this).siblings('tr.variable_subscription_trial').children('td');

				$(this).insertBefore($regularPriceRow);
				$firstRow.children('td:last').replaceWith($(this).children('.sign-up-fee-cell'));

				$regularPriceRow.children(':first').addClass(function(){
					$trialPeriodCell.insertAfter($(this));
					return 'hide_if_variable-subscription';
				});

				$(this).addClass('wcs_moved');
			});
		},
		subscriptionMetaChanged: function(){
			var isChanged = false;

			// Check if any order meta has changed
			$.each(['discount_cart','discount_total','tax_total','total'], function(index,selector){
				selector = '#_order_recurring_' + selector;
				if ($(selector).val() != $(selector).prop('defaultValue')) {
					isChanged = true;
				}
			});

			// Then check the item meta for changes (if necessary)
			if(!isChanged){
				var $currentEl;

				$('#order_items_list .item').each(function(){

					// Check the recurring values
					$(this).find('[value^="_recurring_"]').each(function(){
						$currentEl = $(this).parent().next().children('input[name^="meta_value"]');
						if ($currentEl.val() != $currentEl.prop('defaultValue')){
							isChanged = true;
							return false;
						}
					});

					// Check the subscription values (if necessary)
					if(!isChanged){
						$(this).find('[value^="_subscription_"]').each(function(){
							$currentEl = $(this).parent().next().children('input[name^="meta_value"]');
							if ($currentEl.val() != $currentEl.prop('defaultValue')){
								isChanged = true;
								return false;
							}
						});
					}

				});
			}

			return isChanged;
		}
	});

	$('.options_group.pricing ._sale_price_field .description').prepend('<span id="sale-price-period" style="display: none;"></span>');

	// Move the subscription pricing section to the same location as the normal pricing section
	$('.options_group.subscription_pricing').not('.variable_subscription_pricing .options_group.subscription_pricing').insertBefore($('.options_group.pricing'));
	$('.show_if_subscription.clear').insertAfter($('.options_group.subscription_pricing'));

	// Move the subscription variation pricing section to a better location in the DOM on load
	if($('#variable_product_options tr.variable_subscription_pricing').length > 0) {
		$.moveSubscriptionVariationFields();
	}
	// When a variation is added
	$('#variable_product_options').on('woocommerce_variations_added',function(){
		$.moveSubscriptionVariationFields();
		$.showHideVariableSubscriptionMeta();
	});

	if($('.options_group.pricing').length > 0) {
		$.setSalePeriod();
		$.showHideSubscriptionMeta();
		$.showHideVariableSubscriptionMeta();
		$.setSubscriptionLengths();
		$.setTrialPeriods();
	}

	// Update subscription ranges when subscription period or interval is changed
	$('#woocommerce-product-data').on('change','[name^="_subscription_period"], [name^="_subscription_period_interval"], [name^="variable_subscription_period"], [name^="variable_subscription_period_interval"]',function(){
		$.setSubscriptionLengths();
		$.setSalePeriod();
	});

	$('#woocommerce-product-data').on('propertychange keyup input paste change','[name^="_subscription_trial_length"], [name^="variable_subscription_trial_length"]',function(){
		$.setTrialPeriods();
	});

	$('body').bind('woocommerce-product-type-change',function(){
		$.showHideSubscriptionMeta();
		$.showHideVariableSubscriptionMeta();
	});

	$('input#_downloadable, input#_virtual').change(function(){
		$.showHideSubscriptionMeta();
		$.showHideVariableSubscriptionMeta();
	});

	if($.getParameterByName('select_subscription')=='true'){
		$('select#product-type option[value="'+WCSubscriptions.productType+'"]').attr('selected', 'selected');
		$('select#product-type').trigger('woocommerce-product-type-change');
		$('select#product-type').select();
	}

	// Before saving a subscription product, validate the trial period
	$('#post').submit(function(){
		if ( WCSubscriptions.subscriptionLengths !== undefined ){
			var trialLength = $('#_subscription_trial_length').val(),
				selectedTrialPeriod = $('#_subscription_trial_period').val();

			if ( parseInt(trialLength) >= WCSubscriptions.subscriptionLengths[selectedTrialPeriod].length ) {
				alert(WCSubscriptions.trialTooLongMessages[selectedTrialPeriod]);
				$('#ajax-loading').hide();
				$('#publish').removeClass('button-primary-disabled');
				return false;
			}
		}
	});

	// On "Manage Subscriptions" page, handle editing a date
	$('.date-picker-div').siblings('a.edit-timestamp').click(function() {
		var $pickerDiv = $(this).siblings('.date-picker-div'),
			$editDiv = $(this).parents('.edit-date-div');

		if ($pickerDiv.is(":hidden")) {
			$editDiv.css({visibility:'visible'});
			$pickerDiv.slideDown('fast');
			$(this).hide();
		} else {
			$editDiv.removeAttr( 'style' );
			$pickerDiv.slideUp('fast');
		}

		return false;
	});

	$('.cancel-timestamp', '.date-picker-div').click(function() {
		var $pickerDiv = $(this).parents('.date-picker-div'),
			$editDiv = $(this).parents('.edit-date-div');

		$editDiv.removeAttr( 'style' );
		$pickerDiv.slideUp('fast');
		$pickerDiv.siblings('a.edit-timestamp').show();
		return false;
	});

	$('.save-timestamp', '.date-picker-div').click(function () {
		var $pickerDiv = $(this).parents('.date-picker-div'),
			$editDiv = $pickerDiv.parents('.edit-date-div');
			$timeDiv = $editDiv.siblings('.next-payment-date');
			$subscriptionRow = $pickerDiv.parents('tr');

		$pickerDiv.slideUp('fast');
		$pickerDiv.parents('.row-actions').css({'background-image': 'url('+WCSubscriptions.ajaxLoaderImage+')'});

		$.ajax({
			type: 'POST',
			url: ajaxurl,
			data: {
				action: 'wcs_update_next_payment_date',
				wcs_subscription_key: $('.subscription_key',$subscriptionRow).val(),
				wcs_user_id: $('.user_id',$subscriptionRow).val(),
				wcs_day: $('[name="edit-day"]', $pickerDiv).val(),
				wcs_month: $('[name="edit-month"]', $pickerDiv).val(),
				wcs_year: $('[name="edit-year"]', $pickerDiv).val(),
				wcs_nonce: WCSubscriptions.ajaxDateChangeNonce
			},
			success: function(response){
				response = $.parseJSON(response);
				if('error'==response.status){ // Output error message
					$editDiv.css({'background-image':''});
					$(response.message).hide().prependTo($timeDiv.parent()).slideDown('fast').fadeIn('fast');
					$pickerDiv.slideDown('fast');
					setTimeout(function() {
						$('.error',$timeDiv.parent()).slideUp();
					}, 4000);
				} else { // Update displayed payment date
					$editDiv.removeAttr( 'style' );
					$timeDiv.fadeOut('fast',function(){
						$timeDiv.html(response.dateToDisplay);
						$timeDiv.attr('title',response.timestamp);
						$timeDiv.fadeIn('fast');
						$pickerDiv.siblings('a.edit-timestamp').fadeIn('fast');
						$(response.message).hide().prependTo($timeDiv.parent()).slideDown('fast').fadeIn('fast');
						setTimeout(function() {
							$('.updated',$timeDiv.parent()).slideUp();
						}, 3000);
					});
				}
			}
		});

		return false;
	});

	// Prefill recurring values for a subscription when a subscription product is added to an order
	$('#woocommerce-order-items button.add_shop_order_item').click(function(){

		var add_item_ids = $('select#add_item_id').val();

		if ( add_item_ids ) {
			var count = add_item_ids.length,
				size = $('table.woocommerce_order_items tbody tr.item').size();

			$.each( add_item_ids, function( index, value ) {

				var data = {
					action:      'woocommerce_subscriptions_prefill_order_item_meta',
					item_to_add: value,
					index:       size,
					security:    WCSubscriptions.EditOrderNonce
				};

				$.post( WCSubscriptions.ajaxUrl, data, function(response) {
					var $item_row;

					response = $.parseJSON(response);

					// Item is a subscription
					if ( response.html.length > 0 ) {
						$.hideAddItemButton();
						$('#recurring_order_totals, #recurring_shipping_description').slideUp(1,function(){
							$(this).show(200,function(){
								$(this).slideDown();
							});
						});
					}

					var interval = setInterval(function() { // Can only insert the item row once it is available
						$item_row = $('#order_items_list .item[rel="'+response.item_index+'"]');

						if ( $item_row.length > 0 ) {

							$('tbody.meta_items',$item_row).append(response.html);

							if (! $.isEmptyObject(response.line_totals)) {
								$('input[name="line_subtotal\\['+response.item_index+'\\]"]').val(response.line_totals.line_subtotal);
								$('input[name="line_total\\['+response.item_index+'\\]"]').val(response.line_totals.line_total);
							}

							clearInterval(interval);
						}
					},200);
				});

				size++;
			});
		}

	});

	// Show recurring totals & hide add item button
	$('#woocommerce-order-items button.add_order_item').click(function(){
		var interval = setInterval(function() { // Can only insert the item row once it is available
			$item_row = $('#order_items_list .item:last');

			if( $item_row.length > 0 ) {
				if( $('input[value^="_recurring_"]',$item_row).size() > 0){
					$.hideAddItemButton();
					$('#recurring_order_totals, #recurring_shipping_description').slideUp(1,function(){
						$(this).show(200,function(){
							$(this).slideDown();
						});
					});
				}

				clearInterval(interval);
			}
		},200);
	});

	// Calculate subscription line item taxes when line taxes are calculated
	$('button.calc_line_taxes').on('click', function(){

		$('.woocommerce_order_items_wrapper').block({ message: null, overlayCSS: { background: '#fff url(' + woocommerce_writepanel_params.plugin_url + '/assets/images/ajax-loader.gif) no-repeat center', opacity: 0.6 } });

		var $items = $('#order_items_list tr.item');

		var country = $('#_shipping_country').val();

		if (country) {
			var state = $('#_shipping_state').val(),
				postcode = $('#_shipping_postcode').val(),
				city = $('#_shipping_city').val();
		} else {
			country = $('#_billing_country').val();
			var state = $('#_billing_state').val(),
				postcode = $('#_billing_postcode').val(),
				city = $('#_billing_city').val();
		}

		$items.each(function(idx){

			var $row = $(this);
			var itemID;

			var data = {
				action: 		'woocommerce_subscriptions_calculate_line_taxes',
				order_id: 		WCSubscriptions.postId,
				order_item_id:	$row.find('input.order_item_id').val(),
				product_id:		$row.find('input.item_id').val(),
				line_subtotal:	$row.find('[value="_recurring_line_subtotal"]').parent().next().children('input[name^="meta_value"]').val(),
				line_total:		$row.find('[value="_recurring_line_total"]').parent().next().children('input[name^="meta_value"]').val(),
				tax_class:		$row.find('select.tax_class').val(),
				country:		country,
				state:			state,
				postcode:		postcode,
				city:			city,
				shipping:		accounting.unformat( $('#_order_shipping').val() ),
				security: 		WCSubscriptions.EditOrderNonce
			};

			$.post( WCSubscriptions.ajaxUrl, data, function(response) {

				result = $.parseJSON(response);

				if (! $.isEmptyObject(result)) {
					$row.find('[value="_recurring_line_subtotal_tax"]').parent().next().children('input[name^="meta_value"]').val( result.recurring_line_subtotal_tax );
					$row.find('[value="_recurring_line_tax"]').parent().next().children('input[name^="meta_value"]').val( result.recurring_line_tax );
				}

				if ( typeof result.tax_row_html != 'undefined' ){ // WC 2.0+ so returning tax rows
					$('#recurring_tax_rows:not(.wcs-updated)').empty().append(result.tax_row_html).addClass('wcs-updated');
				}

				if (idx == ($items.size() - 1)) {
					$('.woocommerce_order_items_wrapper').unblock();
				}

				$('#_order_recurring_tax_total').val(result.recurring_line_tax).change();

			});

		});

		$items.promise().done(function() {
			$('#recurring_tax_rows').removeClass('wcs-updated');
		});

		return false;
	}).hover(function() {
		$('.meta_items [value="_recurring_line_subtotal_tax"]').parent().next().children('input[name^="meta_value"]').css('background-color', '#d8c8d2');
		$('.meta_items [value="_recurring_line_tax"]').parent().next().children('input[name^="meta_value"]').css('background-color', '#d8c8d2');
		$('#_order_recurring_tax_total').css('background-color', '#d8c8d2');
	}, function() {
		$('.meta_items [value="_recurring_line_subtotal_tax"]').parent().next().children('input[name^="meta_value"]').css('background-color', '');
		$('.meta_items [value="_recurring_line_tax"]').parent().next().children('input[name^="meta_value"]').css('background-color', '');
		$('#_order_recurring_tax_total').css('background-color', '');
	});

	// Calculate recurring order totals when order totals are calculated
	$('button.calc_totals').on('click', function(){
		// Block write panel
		$('#woocommerce-order-totals').block({ message: null, overlayCSS: { background: '#fff url(' + woocommerce_writepanel_params.plugin_url + '/assets/images/ajax-loader.gif) no-repeat center', opacity: 0.6 } });

		// Get row totals
		var line_subtotals 		= 0;
		var line_subtotal_taxes = 0;
		var line_totals 		= 0;
		var cart_discount 		= 0;
		var cart_tax 			= 0;
		var order_shipping 		= parseFloat( $('#_order_shipping').val() );
		var order_shipping_tax 	= parseFloat( $('#_order_shipping_tax').val() );
		var order_discount		= parseFloat( $('#_order_recurring_discount_total').val() );

		if ( ! order_shipping ) order_shipping = 0;
		if ( ! order_shipping_tax ) order_shipping_tax = 0;
		if ( ! order_discount ) order_discount = 0;

		$('#order_items_list tr.item').each(function(){

			var line_subtotal		= parseFloat( $(this).find('[value="_recurring_line_subtotal"]').parent().next().children('input[name^="meta_value"]').val() );
			var line_subtotal_tax	= parseFloat( $(this).find('[value="_recurring_line_subtotal_tax"]').parent().next().children('input[name^="meta_value"]').val() );
			var line_total			= parseFloat( $(this).find('[value="_recurring_line_total"]').parent().next().children('input[name^="meta_value"]').val() );
			var line_tax			= parseFloat( $(this).find('[value="_recurring_line_tax"]').parent().next().children('input[name^="meta_value"]').val() );

			if ( ! line_subtotal ) line_subtotal = 0;
			if ( ! line_subtotal_tax ) line_subtotal_tax = 0;
			if ( ! line_total ) line_total = 0;
			if ( ! line_tax ) line_tax = 0;

			line_subtotals = parseFloat( line_subtotals + line_subtotal );
			line_subtotal_taxes = parseFloat( line_subtotal_taxes + line_subtotal_tax );
			line_totals = parseFloat( line_totals + line_total );

			if (woocommerce_writepanel_params.round_at_subtotal=='no') {
				line_tax = parseFloat( line_tax.toFixed( 2 ) );
			}

			cart_tax = parseFloat( cart_tax + line_tax );

		});

		// Tax
		if (woocommerce_writepanel_params.round_at_subtotal=='yes') {
			cart_tax = parseFloat( cart_tax.toFixed( 2 ) );
		}

		// Cart discount
		var cart_discount = ( (line_subtotals + line_subtotal_taxes) - (line_totals + cart_tax) );
		if (cart_discount<0) cart_discount = 0;
		cart_discount = cart_discount.toFixed( 2 );

		// Total
		var order_total = line_totals + cart_tax + order_shipping + order_shipping_tax - order_discount;
		order_total = order_total.toFixed( 2 );

		// Set fields
		$('#_order_recurring_discount_total').val( cart_discount ).change();
		$('#_order_recurring_tax_total').val( cart_tax ).change();
		$('#_order_recurring_total').val( order_total ).change();

		// Since we currently cannot calc shipping from the backend, ditch the rows. They must be manually calculated.
		if ( $('.delete_recurring_tax_row').length == 0 ) // WC 1.x so remove tax rows
			$('#recurring_tax_rows').empty();

		$('#woocommerce-order-totals').unblock();

		return false;
	});

	// Move recurring order totals to end of order totals meta box
	$('#recurring_order_totals').remove().insertAfter($("#woocommerce-order-totals .totals_group:last"));

	// If there are changes to any subscription related meta and the order's payment gateway doesn't support it, throw a confirmation.
	$('#post').on('submit', function(){
		if($.subscriptionMetaChanged() && $('[name="gateway_supports_subscription_changes"]').val() == 'false')
			return confirm(WCSubscriptions.changeMetaWarning);
	});

	// Notify store manager that deleting an order also deletes subscriptions
	$('#posts-filter').submit(function(){
		if($('[name="post_type"]').val()=='shop_order' && $('[name="action"]').val()=='trash'){
			var containsSubscription = false;
			$('[name="post[]"]:checked').each(function(){
				if($('[name="contains_subscription"]',$('#post-'+$(this).val())).val()=='true'){
					containsSubscription = true;
					return false;
				}
			});
			if(containsSubscription)
				return confirm(WCSubscriptions.bulkTrashWarning);
		}
	});

	$('.order_actions .submitdelete').click(function(){
		if($('[name="contains_subscription"]').val()=='true')
			return confirm(WCSubscriptions.bulkTrashWarning);
	});

	$(window).load(function(){
		if($('[name="contains_subscription"]').length > 0 && $('[name="contains_subscription"]').val()=='true'){
			$.hideAddItemButton();
		}
	});

	$('.remove_row').on('click',function(){
		var $itemRow = $(this).parents('tr.item');

		// If we're removing the last item, show the add item button
		if($('#order_items_list:visible').size() == 1){
			$.showAddItemButton();
		}

		// If we're removing a subscription, throw notice that subscription will need to be removed
		if($('[value^="_recurring_"]',$itemRow.html).size() > 0 || $('[value^="_subscription_"]',$itemRow.html).size() > 0){
			return confirm(WCSubscriptions.removeItemWarning);
		}
	});

	// Add a tax row
	$('a.add_recurring_tax_row').click(function(){

		var data = {
			action: 	'woocommerce_subscriptions_add_line_tax',
			order_id: 	WCSubscriptions.postId,
			size:		$('#recurring_tax_rows .tax_row').size(),
			security: 	WCSubscriptions.EditOrderNonce
		};

		$('#tax_rows').closest('.totals_group').block({ message: null, overlayCSS: { background: '#fff url(' + woocommerce_writepanel_params.plugin_url + '/assets/images/ajax-loader.gif) no-repeat center', opacity: 0.6 } });

		$.ajax({
			url: woocommerce_writepanel_params.ajax_url,
			data: data,
			type: 'POST',
			success: function( response ) {
				$('#recurring_tax_rows').append( response ).closest('.totals_group').unblock();
			}
		});

		return false;
	});

	// Delete a tax row in WC 2.0+
	$('#recurring_tax_rows').on('click','a.delete_recurring_tax_row',function(){
		var $tax_row = $(this).closest('.tax_row'),
			tax_row_id = $tax_row.attr( 'data-order_item_id' );

		var data = {
			tax_row_id: tax_row_id,
			action: 	'woocommerce_subscriptions_remove_line_tax',
			security: 	WCSubscriptions.EditOrderNonce
		};

		$('#recurring_tax_rows').closest('.totals_group').block({ message: null, overlayCSS: { background: '#fff url(' + woocommerce_writepanel_params.plugin_url + '/assets/images/ajax-loader.gif) no-repeat center', opacity: 0.6 } });

		$.ajax({
			url: WCSubscriptions.ajaxUrl,
			data: data,
			type: 'POST',
			success: function( response ) {
				$tax_row.remove();
				$('#recurring_tax_rows').closest('.totals_group').unblock();
			}
		});

		return false;
	});

	// Editing a variable product
	$('#variable_product_options').on('change','[name^="variable_regular_price"]',function(){
		var matches = $(this).attr('name').match(/\[(.*?)\]/);

		if (matches) {
			var loopIndex = matches[1];
			$('[name="variable_subscription_price['+loopIndex+']"]').val($(this).val());
		}
	});

	// Editing a variable product
	$('#variable_product_options').on('change','[name^="variable_subscription_price"]',function(){
		var matches = $(this).attr('name').match(/\[(.*?)\]/);

		if (matches) {
			var loopIndex = matches[1];
			$('[name="variable_regular_price['+loopIndex+']"]').val($(this).val());
		}
	});

	/* Manage Subscriptions filters */
	$('#subscriptions-filter select#dropdown_customers').css('width', '250px').ajaxChosen({
		method: 		'GET',
		url: 			WCSubscriptions.ajaxUrl,
		dataType:      'json',
		afterTypeDelay: 350,
		minTermLength:  1,
		data: {
			action:   'woocommerce_json_search_customers',
			security: WCSubscriptions.searchCustomersNonce,
			default:  WCSubscriptions.searchCustomersLabel
		}
	}, function (data) {

		var terms = {};

		$.each(data, function (i, val) {
			terms[i] = val;
		});

		return terms;
	});

	$('#subscriptions-filter select#dropdown_products_and_variations').ajaxChosen({
		method: 	'GET',
		url: 		WCSubscriptions.ajaxUrl,
		dataType: 	'json',
		afterTypeDelay: 350,
		data: {
			action:   'woocommerce_json_search_products_and_variations',
			security: WCSubscriptions.searchProductsNonce
		}
	}, function (data) {

		var terms = {};

		$.each(data, function (i, val) {
			terms[i] = val;
		});

		return terms;
	});

});
