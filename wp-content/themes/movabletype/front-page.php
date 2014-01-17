<?php 

    get_header();

    global $woocommerce;

    // Get the license type passed from MT. The license will either be 5users or unlimited
    $lic = $_GET['lic'];

    // If we did not receive a license possibly because a user returned here 
    // from the cart then check to see if we stored the license in the session.
    if ($lic) {
        $_SESSION['mt_lic'] = $lic;
    } else if (!$lic && $_SESSION['mt_lic']) {
        $lic = $_SESSION['mt_lic'];
    } else if (!$lic) {
        $lic = '5users';
    }

    if ($lic == '5users') {
        $attribute_license = urlencode('5 Users');
    } else if (strtolower($lic) == 'unlimited') {
        $attribute_license = 'Unlimited';
    }

    // Used for switching licenses
    if ($lic == '5users') { $l = 'unlimited'; } else { $l = '5users'; }
    
    // If we don't recognize the license value, default it to 5users
    if ($lic != '5users' && $lic !='unlimited') { $lic = '5users'; }

    $products = array(
        'ce'   => 43,
        'vps1' => 44,
        'vps2' => 45,
        'vps3' => 46,
        'vps4' => 47,
    ); 

    $variations = array(
        '5users' => array(
            'ce'   => 56,
            'vps1' => 58,
            'vps2' => 63,
            'vps3' => 65,
            'vps4' => 67,
        ),
        'unlimited' => array(
            'ce'   => 57,
            'vps1' => 59,
            'vps2' => 64,
            'vps3' => 66,
            'vps4' => 69,
        )
    );


    // Initialize each hosting product

    $ce   = get_product($variations[$lic]['ce']); 
    $ce->set_price($ce->subscription_price);

    $vps1 = get_product($variations[$lic]['vps1']);
    $vps1->set_price($vps1->subscription_price);

    $vps2 = get_product($variations[$lic]['vps2']);
    $vps2->set_price($vps2->subscription_price);

    $vps3 = get_product($variations[$lic]['vps3']);
    $vps3->set_price($vps3->subscription_price);

    $vps4 = get_product($variations[$lic]['vps4']);
    $vps4->set_price($vps4->subscription_price);

    // Get the lowest VPS price
    $starting = min(array($vps1->get_price(), $vps2->get_price(), $vps3->get_price(), $vps4->get_price()));

    // Clear the cart when loading this page
    $woocommerce->cart->empty_cart();

    // ?add-to-cart=43&attribute_license=5-users&variation_id=56&quantity=1&product_id=43
?>

<section class="hero">
  <div class="row">
    <div class="large-10 columns large-centered text-center">
      <!--h1>Never worry about your MovableType hosting. We'll <strong>host</strong> and <strong>support</strong> your content for you.</h1-->
	<h1><strong>Fully</strong> managed<br/> <strong>high</strong> performance hosting for Moveable Type sites.</h1>
    </div>
  </div>
  
</section>

<section class="bg-secondary">
  <div class="row">
    <div class="large-4 columns">
      <div class="panel callout panel-intro">
        <figure class="icon-1"></figure>
        <p><strong>We’ll manage your entire Movable Type application infrastructure</strong> for a flat monthly fee, including OS/Application updates, patches, support for critical application issues, infrastructure consulting, performance reporting and automagic application scaling as your site grows.</p>
        <p>You worry about growing your audience, we worry about keeping your site stable, secure and fast.</p>
      </div>
    </div>

<!--script type="text/javascript">(function() { var phplive_e_1349885922 = document.createElement("script") ; phplive_e_1349885922.type = "text/javascript" ; phplive_e_1349885922.async = true ; phplive_e_1349885922.src = "//www.arckinteractive.com/phplive/js/phplive_v2.js.php?q=0|1349885922|0|Chat" ; document.getElementById("phplive_btn_1349885922").appendChild( phplive_e_1349885922 ) ; })() ;</script>


<div class="gsfn-widget-tab gsfn-left" style="background-color: rgb(222, 116, 59); color: rgb(255, 255, 255); border-color: rgb(255, 255, 255); font-family: Arial, Helvetica, sans-serif; top: 524.5px;" onclick="phplive_launch_chat_0(0)">
<a href="/movabletype/contact/" style="color: white; a: white;">Ask a Question</a>
</div>
</span-->
    <div class="large-4 columns">
      <div class="panel panel-cloud">
        <div class="text-center">
          <figure class="icon-2"></figure>
          <h3>Movable Type<br /><strong>Cloud Edition</strong></h3>
          <p><strong>Fully managed, low fixed monthly cost.</strong><br />
            ArckCloud will scale your service based on your site's performance.</p>
          </div>
        <ul class="disc">
          <li>20GB Disk Space*</li>
          <li>1TB of Bandwidth**</li>
          <li>Fully Managed</li>
          <li><?php echo trim(urldecode($attribute_license)); ?> MT Pro License (<a href="/movabletype/?lic=<?php echo $l; ?>">change</a>)</li>
        </ul>
        <div class="panel-convert">
          <div class="row">
            <div class="large-6 small-6 columns">
              <p>All for one price*</p>
              <h3>$<?php echo $ce->get_price(); ?><span>/month</span></h3>
            </div>
            <div class="large-6 small-6 columns">
              <a class="button expand" href="<?php echo get_site_url(); ?>?add-to-cart=<?php echo $products['ce']; ?>&attribute_license=<?php echo $attribute_license; ?>&variation_id=<?php echo $variations[$lic]['ce']; ?>&quantity=1&product_id=<?php echo $products['ce']; ?>">Choose Plan</a>
            </div>
          </div>
        </div>
      </div>
      <small> * 20¢/GB/mo for additional disk space.<br />** 16¢/GB/mo for additional bandwidth.</small>
    </div>

    <div class="large-4 columns">
      <div class="panel panel-vps">
        <div class="text-center">
          <figure class="icon-3"></figure>
          <h3>Movable Type<br /><strong>VPS Edition</strong></h3>
          <p><strong>Dedicated virtual machine power.</strong><br />
            Scale your own resources up or down, depending on your needs.</p>
          </div>
        <ul class="disc">
          <li>Dedicated VPS</li>
          <li>Fully Managed</li>
          <li>Scale CPU, Memory &amp; Storage</li>
          <li><?php echo trim(urldecode($attribute_license)); ?> MT Pro License (<a href="/movabletype/?lic=<?php echo $l; ?>">change</a>)</li>
        </ul>
        <div class="panel-convert">
          <div class="row">
            <div class="large-6 small-6 columns">
              <p>Starting at*</p>
              <h3>$<?php echo $starting; ?><span>/month</span></h3>
            </div>
            <div class="large-6 small-6 columns">
              <a class="button expand button-vps" href="#">Choose VPS</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="vps-options closed">
    <div class="row">
      <div class="large-12 columns bg-tertiary">
        <div class="row">
          <div class="large-3 columns">
            <div class="panel">
              <h4 class="text-center">VPS #1</h4>
              <table>
                <tr>
                  <td>RAM</td>
                  <td>512MB</td>
                </tr>
                <tr>
                  <td>Disk Space</td>
                  <td>20GB</td>
                </tr>
                <tr>
                  <td>vCPUs</td>
                  <td>1</td>
                </tr>
                <tr>
                  <td>Price</td>
                  <td>$<?php echo $vps1->get_price(); ?>/mo</td>
                </tr>
              </table>
              <a class="button expand" href="<?php echo get_site_url(); ?>?add-to-cart=<?php echo $products['vps1']; ?>&attribute_license=<?php echo $attribute_license; ?>&variation_id=<?php echo $variations[$lic]['vps1']; ?>&quantity=1&product_id=<?php echo $products['vps1']; ?>">Choose Plan</a>
            </div>
          </div>

          <div class="large-3 columns">
            <div class="panel">
              <h4 class="text-center">VPS #2</h4>
              <table>
                <tr>
                  <td>RAM</td>
                  <td>1GB</td>
                </tr>
                <tr>
                  <td>Disk Space</td>
                  <td>40GB</td>
                </tr>
                <tr>
                  <td>vCPUs</td>
                  <td>1</td>
                </tr>
                <tr>
                  <td>Price</td>
                  <td>$<?php echo $vps2->get_price(); ?>/mo</td>
                </tr>
              </table>
              <a class="button expand" href="<?php echo get_site_url(); ?>?add-to-cart=<?php echo $products['vps2']; ?>&attribute_license=<?php echo $attribute_license; ?>&variation_id=<?php echo $variations[$lic]['vps2']; ?>&quantity=1&product_id=<?php echo $products['vps2']; ?>">Choose Plan</a>
            </div>
          </div>

          <div class="large-3 columns">
            <div class="panel">
              <h4 class="text-center">VPS #3</h4>
              <table>
                <tr>
                  <td>RAM</td>
                  <td>2GB</td>
                </tr>
                <tr>
                  <td>Disk Space</td>
                  <td>80GB</td>
                </tr>
                <tr>
                  <td>vCPUs</td>
                  <td>2</td>
                </tr>
                <tr>
                  <td>Price</td>
                  <td>$<?php echo $vps3->get_price(); ?>/mo</td>
                </tr>
              </table>
              <a class="button expand" href="<?php echo get_site_url(); ?>?add-to-cart=<?php echo $products['vps3']; ?>&attribute_license=<?php echo $attribute_license; ?>&variation_id=<?php echo $variations[$lic]['vps3']; ?>&quantity=1&product_id=<?php echo $products['vps3']; ?>">Choose Plan</a>
            </div>
          </div>

          <div class="large-3 columns">
            <div class="panel">
              <h4 class="text-center">VPS #4</h4>
              <table>
                <tr>
                  <td>RAM</td>
                  <td>4GB</td>
                </tr>
                <tr>
                  <td>Disk Space</td>
                  <td>160GB</td>
                </tr>
                <tr>
                  <td>vCPUs</td>
                  <td>2</td>
                </tr>
                <tr>
                  <td>Price</td>
                  <td>$<?php echo $vps4->get_price(); ?>/mo</td>
                </tr>
              </table>
              <a class="button expand" href="<?php echo get_site_url(); ?>?add-to-cart=<?php echo $products['vps4']; ?>&attribute_license=<?php echo $attribute_license; ?>&variation_id=<?php echo $variations[$lic]['vps4']; ?>&quantity=1&product_id=<?php echo $products['vps4']; ?>">Choose Plan</a>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>

</section>

<!--section class="bg-white text-center">
  <h2 class="text-light">Still not sure?<br />Try it first!</h2>
  <a class="free-trial" href="#">Free Trial</a>
</section-->

<section class="bg-primary">
  <div class="row">
    <div class="large-8 large-centered columns">
      <h2>Common Questions</h2>
      <ul class="large-block-grid-3">
        <li>
          <div class="panel">
            <p><a href="#" class="question">Is the license fee refunded if I cancel the ArckCloud managed hosting?</a></p>
            <div class="answer">
              <p>If you cancel at any time your Movable Type license key will be sent to you
              via email. This key permits you to download your own copy of Movable Type from movabletype.com.</p>
            </div>
          </div>
        </li>
        <li>
          <div class="panel">
            <p><a href="#" class="question">What does fully managed mean?</a></p>
            <div class="answer">
              <p>Fully managed means that ArckCloud will make sure your Movable Type site is always up and blazing fast.
              We will handle installing upgrades and patches for Movable Type and the underlying operating system.</p>
            </div>
          </div>
        </li>
        <li>
          <div class="panel">
            <p><a href="#" class="question">What is the difference between Movable Type Cloud Edition and VPS Edition?</a></p>
            <div class="answer">
              <p>In most cases cloud edition will be all that is needed for running Movable Type however, some user may have additional 
              requirements like running other applications or databases. With a VPS you have a virtual server completely dedicated to your needs.
              ArckCloud will install and manage Movable Type on your behalf however you are free to use the servers resources as you need. Additionally,
              VPS edition includes a management console to manage your VPS, remote console access,and  SSH access.</p>
            </div>
          </div>
        </li>
        <li>
          <div class="panel">
            <p><a href="#" class="question">What if I need a larger VPS than what is listed?</a></p>
            <div class="answer">
              <p>We listed 4 common options but absolutley understand that you may have custom requirements. 
              Simply choose the VPS that most closely matches your needs and add a comment in the note field 
              on the checkout page specifying your actual needs. If there is any cost difference we will contact
              you for approval before configuring the VPS.</p>
            </div>
          </div>
        </li>
        <li>
          <div class="panel">
            <p><a href="#" class="question">Does ArckCloud support issues related to using Movable Type?</a></p>
            <div class="answer">
              <p>With your purchase of a Movable Type license you will receive 12-months of Movable Type Pro Support at no charge. 
              Visit http://www.movabletype.com for additional details.</p>
            </div>
          </div>
        </li>
        <li>
          <div class="panel">
            <p><a href="#" class="question">Does ArckCloud monitor my Movable Type site?</a></p>
            <div class="answer">
              <p>At ArckCloud we love monitoring things and this applies to our hosted Movable Type services as well. 
              ArckCloud will constantly monitor your sites uptime and performance. Any issues that arise will be dealt with
              by our support staff 24x7x365.</p>
            </div>
          </div>
        </li>
      </ul>
    </div>
  </div>
</section>

<?php get_footer(); ?>
