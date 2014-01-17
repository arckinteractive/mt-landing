<?php global $woocommerce; ?>

<!DOCTYPE html>
<!--[if lt IE 8]>      <html class="no-js lt-ie9 lt-ie8" lang="en"> <![endif]-->
<!--[if IE 8]>         <html class="no-js lt-ie9" lang="en"> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js" lang="en"> <!--<![endif]-->
 
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width" />
  <title><?php wp_title(); ?> | MovableType</title>
 
  <link rel="stylesheet" href="<?php bloginfo( 'template_url' ); ?>/css/app.css" />
 
  <script src="<?php bloginfo( 'template_url' ); ?>/javascripts/vendor/custom.modernizr.js"></script>
 
  <!-- iOS, MS and IE Chrome -->
  <link rel="apple-touch-icon-precomposed" href="/apple-touch-icon.png" />
  <meta http-equiv="cleartype" content="on">
  <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
 
  <!-- IE Fix for HTML5 Tags -->
  <!--[if lt IE 9]>
    <script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
  <![endif]-->

  <!-- Respond.js -->
  <!--[if (lt IE 9) & (!IEMobile)]>
  <script src="<?php bloginfo( 'template_url' ); ?>/js/vendor/respond.min.js"></script>
  <![endif]-->

  <!-- WP Head -->
  <link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>" />
  <?php wp_head(); ?>
  
</head>
<body <?php body_class($class); ?>>
 
  <header class="page-header">
    <div class="row">
      <div class="small-6 large-8 columns">
        <a class="logo" href="/movabletype/">MovableType &amp; ARCKCloud</a>
      </div>
      <div class="small-6 large-4 columns text-right header-nav">
	<a href="/">Hosting</a>&nbsp;&nbsp;|&nbsp;&nbsp;
	<a href="/movabletype/contact">Contact&nbsp;Us</a>&nbsp;&nbsp;|&nbsp;&nbsp;
	<a href="/movabletype/account">Account</a>&nbsp;&nbsp;|&nbsp;&nbsp;
        <a class="cart" href="<?php echo $woocommerce->cart->get_cart_url(); ?>">Cart</a>
	
      </div>
    </div>
  </header>