$(function() {

  // Home VPS toggle
  $('.button-vps').click(function() {
    $('.panel-vps').toggleClass('open');
    $('.vps-options').toggleClass('closed');
    return false;
  });

  // Home Q/A
  $('.question').click(function(){
    $(this).parent().parent().find('.answer').toggleClass('open');
    return false;
  });

  // WooCommerce Product List
  $('ul.products').addClass('large-block-grid-4');

  // WooCommerce Single Product
  $('.single-product .entry-summary').wrap('<div class="row product-row"><div class="large-6 columns info-column"></div></div>');
  $('.single-product .images').wrap('<div class="large-6 columns images-column"></div>');
  $('.images-column').appendTo('.product-row');
  $('.woocommerce-tabs').appendTo('.info-column');
  $('.woocommerce-tabs .panel').removeClass('panel');


});