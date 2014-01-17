<?php
function framemarket_listall_shops(){
	global $wpdb;
    $query = "SELECT blog_id FROM " . $wpdb->base_prefix . "blogs WHERE spam != '1' AND archived != '1' AND deleted != '1' AND public = '1' ORDER BY path";

    $blogs = $wpdb->get_results($query);
    ?>
<select name="shoplist" onchange="document.location.href=this.options[this.selectedIndex].value;">
	<option value="">Visit a shop</option>
	<?php
    foreach($blogs as $blog){
        $blog_details = get_blog_details($blog->blog_id);
?>
<option value="<?php echo $blog_details->siteurl; ?>"> <?php echo $blog_details->blogname; ?></option>
<?php
    }
?>
 </select>
<?php
}

// output in a grid of 4 across
function framemarket_grid_mp_list_products( $echo = true, $paginate = '', $paged = '', $per_page = '', $order_by = '', $order = '', $category = '', $tag = '', $search = '' ) {
  global $wp_query, $mp, $grid_home;
  $settings = get_option('mp_settings');

  //setup taxonomy if applicable
  $taxonomy_query = '';
  if ($category) {
    $taxonomy_query = '&product_category=' . sanitize_title($category);
  } else if ($tag) {
    $taxonomy_query = '&product_tag=' . sanitize_title($tag);
  } else if (isset($wp_query->query_vars['taxonomy']) && ($wp_query->query_vars['taxonomy'] == 'product_category' || $wp_query->query_vars['taxonomy'] == 'product_tag')) {
    $taxonomy_query = '&' . $wp_query->query_vars['taxonomy'] . '=' . get_query_var($wp_query->query_vars['taxonomy']);
  }

  //check if it's a search
  $search_query = '';
  if (isset($wp_query->query_vars['s']) && $wp_query->query_vars['s']){
  	$search_query = '&s=' . $wp_query->query_vars['s'];
  }

	if ( $paginate ) {
		// $paged = true;
	} else if ( $paginate === '' ) {
		if ( $settings['paginate'] ) {
			//$paged = true;
		}
		else
			$paginate_query = '&nopaging=true';
	} else {
		$paginate_query = '&nopaging=true';
	}

	if (! $paged) {
		$paged = ( $wp_query->query_vars['paged'] ) ? $wp_query->query_vars['paged'] : 1;
	}

	//get page details
	if ( $paged ) {
		//figure out perpage
		if ( intval( $per_page ) )
		  $paginate_query = '&posts_per_page='.intval($per_page);
		else
		  $paginate_query = '&posts_per_page='.$settings['per_page'];

		//figure out page
		//if ( $wp_query->query_vars['paged'] )
		//	$paginate_query .= '&paged='.intval($wp_query->query_vars['paged']);

		if ( intval( $paged ) )
			$paginate_query .= '&paged='.intval($paged);
		else if ($wp_query->query_vars['paged'])
			$paginate_query .= '&paged='.intval($wp_query->query_vars['paged']);
		/*else if ($paged)
			$paginate_query .= '&paged='.intval($paged);*/
	}

  //get order by
  if (!$order_by) {
    if ($settings['order_by'] == 'price')
      $order_by_query = '&meta_key=mp_price_sort&orderby=meta_value_num';
    else if ($settings['order_by'] == 'sales')
      $order_by_query = '&meta_key=mp_sales_count&orderby=meta_value_num';
    else
      $order_by_query = '&orderby='.$settings['order_by'];
  } else {
  	if ('price' == $order_by)
  		$order_by_query = '&meta_key=mp_price_sort&orderby=meta_value_num';
    else if('sales' == $order_by)
      $order_by_query = '&meta_key=mp_sales_count&orderby=meta_value_num';
    else
    	$order_by_query = '&orderby='.$order_by;
  }

  //get order direction
  if (!$order) {
    $order_query = '&order='.$settings['order'];
  } else {
    $order_query = '&order='.$order;
  }

  //The Query
  $custom_query = new WP_Query('post_type=product&post_status=publish' . $search_query . $taxonomy_query . $paginate_query . $order_by_query . $order_query);
  $wp_query = $custom_query;
  $content = '<div id="mp_product_list">';

  if ($last = count($custom_query->posts)) {

    $count = 1;
    $counter = 0;
    foreach ($custom_query->posts as $post) {

			//add last css class for styling grids
			if (($count%4 == 0))
				 $class = array('mp_product', 'last-product');
			else
				 $class = 'mp_product';

      $content .= '<div '.mp_product_class(false, $class, $post->ID).'>';
      $content .= '<h3 class="mp_product_name"><a href="' . get_permalink( $post->ID ) . '">' . $post->post_title . '</a></h3>';
      $content .= '<div class="mp_product_content">';
      $product_content = mp_product_image( false, 'list', $post->ID );
      //$product_content .= $mp->product_excerpt($post->post_excerpt, $post->post_content, $post->ID);
      $content .= apply_filters( 'mp_product_list_content', $product_content, $post->ID );
      $content .= '</div>';

      $content .= '<div class="mp_product_meta">';
      //price
      $meta = mp_product_price(false, $post->ID, false);
      //button
      $meta .= mp_buy_button(false, 'list', $post->ID);
      $content .= apply_filters( 'mp_product_list_meta', $meta, $post->ID );
      $content .= '</div>';

      $content .= '</div>';

      $count++;
	  $counter++;
		//add last css class for styling grids
	  if (($counter%4 == 0))
			$content .= '<div class="clear"></div>';
    }
  } else {
    $content .= '<div id="mp_no_products">' . apply_filters( 'mp_product_list_none', __('No Products', 'mp') ) . '</div>';
  }

  $content .= '</div>';

 	if (  $wp_query->max_num_pages > 1 ) :
		$content .= '<div id="navigation-bottom" class="navigation">';
		if (  $wp_query->query_vars['paged'] > 1 ) {
			if ($grid_home) {
				$content .= '<div class="nav-previous"><a href="?page='.($wp_query->query_vars['paged']-1).'" >'. __('<span class="meta-nav">&larr;</span> Previous', 'framemarket').'</a></div>';
			} else {
				$content .= '<div class="nav-previous">'. get_previous_posts_link( __('<span class="meta-nav">&larr;</span> Previous', 'framemarket') ).'</a></div>';
			}
		}
		if (  $wp_query->max_num_pages > $wp_query->query_vars['paged'] ) {
			if ($grid_home) {
				$content .= '<div class="nav-next"><a href="?page='.($wp_query->query_vars['paged']+1).'" >'. __('Next <span class="meta-nav">&rarr;</span>', 'framemarket' ).'</a></div>';
			} else {
				$content .= '<div class="nav-next">'. get_next_posts_link(__('Next <span class="meta-nav">&rarr;</span>', 'framemarket' )).'</div>';
			}
		}
		$content .= '</div>';
	endif;

	if ( $echo )
		echo $content;
	else
		return $content;
}

function framemarket_mp_list_global_products($args = '') {
  global $wpdb, $mp, $grid_home;

  $defaults = array(
		'echo' => true,
    'paginate' => true,
		'page' => 1,
    'per_page' => 20,
		'order_by' => 'date',
    'order' => 'DESC',
		'category' => '',
    'tag' => '',
    	's' => '',
    	'sentence' => '',
    	'exact' => '',
		'show_thumbnail' => true,
		'thumbnail_size' => 150,
		'context' => 'list',
		'show_price' => true,
		'text' => 'excerpt',
		'as_list' => false
	);

  $r = wp_parse_args( $args, $defaults );
  extract( $r );

  //setup taxonomy if applicable
  if ($category) {
    $category = $wpdb->escape( sanitize_title( $category ) );
    $query = "SELECT blog_id, p.post_id, post_permalink, post_title, post_content FROM {$wpdb->base_prefix}mp_products p INNER JOIN {$wpdb->base_prefix}mp_term_relationships r ON p.id = r.post_id INNER JOIN {$wpdb->base_prefix}mp_terms t ON r.term_id = t.term_id WHERE p.blog_public = 1 AND t.type = 'product_category' AND t.slug = '$category'";
	$query_count = "SELECT COUNT(*) FROM {$wpdb->base_prefix}mp_products p INNER JOIN {$wpdb->base_prefix}mp_term_relationships r ON p.id = r.post_id INNER JOIN {$wpdb->base_prefix}mp_terms t ON r.term_id = t.term_id WHERE p.blog_public = 1 AND t.type = 'product_category' AND t.slug = '$category'";
  } else if ($tag) {
    $tag = $wpdb->escape( sanitize_title( $tag ) );
    $query = "SELECT blog_id, p.post_id, post_permalink, post_title, post_content FROM {$wpdb->base_prefix}mp_products p INNER JOIN {$wpdb->base_prefix}mp_term_relationships r ON p.id = r.post_id INNER JOIN {$wpdb->base_prefix}mp_terms t ON r.term_id = t.term_id WHERE p.blog_public = 1 AND t.type = 'product_tag' AND t.slug = '$tag'";
    $query_count = "SELECT COUNT(*) FROM {$wpdb->base_prefix}mp_products p INNER JOIN {$wpdb->base_prefix}mp_term_relationships r ON p.id = r.post_id INNER JOIN {$wpdb->base_prefix}mp_terms t ON r.term_id = t.term_id WHERE p.blog_public = 1 AND t.type = 'product_tag' AND t.slug = '$tag'";
  } else {
    $query = "SELECT blog_id, p.post_id, post_permalink, post_title, post_content FROM {$wpdb->base_prefix}mp_products p WHERE p.blog_public = 1";
    $query_count = "SELECT COUNT(*) FROM {$wpdb->base_prefix}mp_products p WHERE p.blog_public = 1";
  }

  	// get search
	if ( !empty($s) ) {
		// added slashes screw with quote grouping when done early, so done later
		$s = stripslashes($s);
		if ( !empty($sentence) ) {
			$search_terms = array($s);
		} else {
			preg_match_all('/".*?("|$)|((?<=[\r\n\t ",+])|^)[^\r\n\t ",+]+/', $s, $matches);
			$search_terms = array_map('_search_terms_tidy', $matches[0]);
		}
		$n = !empty($exact) ? '' : '%';
		$search = '';
		$searchand = '';
		foreach( (array) $search_terms as $term ) {
			$term = esc_sql( like_escape( $term ) );
			$search .= "{$searchand}((p.post_title LIKE '{$n}{$term}{$n}') OR (p.post_content LIKE '{$n}{$term}{$n}'))";
			$searchand = ' AND ';
		}
		if ( !empty($search) ) {
			$query .= " AND ({$search}) ";
		}
	}

  //get order by
  switch ($order_by) {

    case 'title':
      $query .= " ORDER BY p.post_title";
      break;

    case 'price':
      $query .= " ORDER BY p.price";
      break;

    case 'sales':
      $query .= " ORDER BY p.sales_count";
      break;

    case 'rand':
      $query .= " ORDER BY RAND()";
      break;

    case 'date':
    default:
      $query .= " ORDER BY p.post_date";
      break;
  }

  //get order direction
  if ($order == 'ASC') {
    $query .= " ASC";
  } else {
    $query .= " DESC";
  }

  //get page details
  if ($paginate)
    $query .= " LIMIT " . intval(($page-1)*$per_page) . ", " . intval($per_page);

  //The Query
  $results = $wpdb->get_results( $query );
  if ($paginate){
	  $total_results = $wpdb->get_var( $query_count );
	  $total_pages = ceil($total_results/$per_page);
  }

  // Make sure show thumbnail setting follow main site
  $show_thumbnail = ( !$mp->get_setting('show_thumbnail') ) ? false : $show_thumbnail;

   $content = '<div id="mp_product_list">';

  if ($results) {
	$count = 1;
	$counter = 0;
    foreach ($results as $product) {

 			//add last css class for styling grids
			if (($count%4 == 0))
				 $content .= '<div class="product mp_product last-product">';
			else
				  $content .= '<div class="product mp_product">';


      global $current_blog;
      switch_to_blog($product->blog_id);

      //grab permalink
      $permalink = get_permalink( $product->post_id );

      //grab thumbnail
      if ($show_thumbnail)
        $thumbnail = mp_product_image( false, $context, $product->post_id, $thumbnail_size );

      //price
      if ($show_price) {
        if ($context == 'widget')
          $price = mp_product_price(false, $product->post_id, ''); //no price label in widgets
        else
          $price = mp_product_price(false, $product->post_id, '');
      }

      restore_current_blog();

      $content .= '<h3 class="mp_product_name"><a href="' . $permalink . '">' . esc_attr($product->post_title) . '</a></h3>';
      $content .= '<div class="mp_product_content">';

      $content .= $thumbnail;

      $content .= '</div>';

      $content .= '<div class="mp_product_meta">';

      //price
      $content .= $price;

      //button
      $content .= '<a class="mp_link_buynow" href="' . $permalink . '">' .  __('Buy Now &raquo;', 'mp') . '</a>';
      $content .= '</div>';

        $content .= '</div>';
     	 $count++;
		  $counter++;
			//add last css class for styling grids
		  if (($counter%4 == 0))
				$content .= '<div class="clear"></div>';
    }
  } else {
    $content .= '<div id="mp_no_products">' . apply_filters( 'mp_product_list_none', __('No Products', 'mp') ) . '</div>';
  }

	// Pagination
	if ( $paginate ){
		if ( $total_pages > 0 ){
			$content .= '<div id="navigation-bottom" class="navigation">';
			if (  $page > 1 ) {
				if ($grid_home) {
					$content .= '<div class="nav-previous"><a href="?page='.($page-1).'" >'. __('<span class="meta-nav">&larr;</span> Previous', 'framemarket').'</a></div>';
				} else {
					$content .= '<div class="nav-previous">'. get_previous_posts_link( __('<span class="meta-nav">&larr;</span> Previous', 'framemarket'), $total_pages ).'</a></div>';
				}
			}
			if (  $total_pages > $page ) {
				if ($grid_home) {
					$content .= '<div class="nav-next"><a href="?page='.($page+1).'" >'. __('Next <span class="meta-nav">&rarr;</span>', 'framemarket' ).'</a></div>';
				} else {
					$content .= '<div class="nav-next">'. get_next_posts_link(__('Next <span class="meta-nav">&rarr;</span>', 'framemarket' ), $total_pages).'</div>';
				}
			}
			$content .= '</div>';
		}
	}

    $content .= '</div>';

  if ($echo)
    echo $content;
  else
    return $content;
}

function framemarket_mp_list_categories( $echo = true, $args = '' ) {
  $args['taxonomy'] = 'product_category';
  $args['echo'] = false;
  $args['title_li'] = '';

  $list = '<ul id="mp_category_list">' . wp_list_categories( $args ) . '</ul>';

  if ($echo)
    echo $list;
  else
    return $list;
}

function framemarket_global_categories_theme($content) {
  global $wp_query;

  if ( $slug = get_query_var('global_taxonomy') ) {
    $args = array();
    $args['echo'] = false;
    $args['category'] = $slug;

    //check for paging
    if (get_query_var('paged'))
      $args['page'] = intval(get_query_var('paged'));

    $content = framemarket_mp_list_global_products( $args );
    $content .= get_posts_nav_link();

  } else { //no category set, so show list
    $content .= mp_global_categories_list( array( 'echo' => false ) );
  }

  return $content;
}

function framemarket_product_meta() {
   global $post;

   //don't filter outside of the loop
 	if ( !in_the_loop() )
		  return $content;

   $content = '<div class="product-meta-details">';
   $content .= mp_category_list($post->ID, '<span class="grid_mp_product_categories">' . __( 'Categorized in ', 'mp' ), ', ', '</span>');
   $content .= '&nbsp;&nbsp;';
   $content .= mp_tag_list($post->ID, '<span class="grid_mp_product_tags">', ', ', '</span>');
   $content .= '</div><hr/><div class="product-meta-details">';
   $content .= mp_product_price(false);
   $content .= mp_buy_button(false, 'single');
   $content .= '</div><hr />';

   return $content;
 }



function framemarket_mp_order_status() {
  global $mp, $wp_query;
	$settings = get_option('mp_settings');
  echo $settings['msg']['order_status'];

  $order_id = ($wp_query->query_vars['order_id']) ? $wp_query->query_vars['order_id'] : $_GET['order_id'];

  if (!empty($order_id)) {
    //get order
    $order = $mp->get_order($order_id);

    if ($order) { //valid order
      echo '<hr/><h2><em>' . sprintf( __('Order Details (%s):', 'mp'), htmlentities($order_id)) . '</em></h2>';
      ?>
      <h3><?php _e('Current Status', 'mp'); ?></h3>
      <ul>
      <?php
      //get times
      $received = date(get_option('date_format') . ' - ' . get_option('time_format'), $order->mp_received_time);
      if ($order->mp_paid_time)
        $paid = date(get_option('date_format') . ' - ' . get_option('time_format'), $order->mp_paid_time);
      if ($order->mp_shipped_time)
        $shipped = date(get_option('date_format') . ' - ' . get_option('time_format'), $order->mp_shipped_time);

      if ($order->post_status == 'order_received') {
        echo '<li>' . __('Received:', 'mp') . ' <strong>' . $received . '</strong></li>';
      } else if ($order->post_status == 'order_paid') {
        echo '<li>' . __('Paid:', 'mp') . ' <strong>' . $paid . '</strong></li>';
        echo '<li>' . __('Received:', 'mp') . ' <strong>' . $received . '</strong></li>';
      } else if ($order->post_status == 'order_shipped' || $order->post_status == 'order_closed') {
        echo '<li>' . __('Shipped:', 'mp') . ' <strong>' . $shipped . '</strong></li>';
        echo '<li>' . __('Paid:', 'mp') . ' <strong>' . $paid . '</strong></li>';
        echo '<li>' . __('Received:', 'mp') . ' <strong>' . $received . '</strong></li>';
      }

      $order_paid = $order->post_status != 'order_received';
      $max_downloads = intval($settings['max_downloads']) ? intval($settings['max_downloads']) : 5;
      ?>
      </ul>
<hr/>
      <h3><?php _e('Payment Information:', 'mp'); ?></h3>
      <ul>
        <li>
          <?php _e('Payment Method:', 'mp'); ?>
          <strong><?php echo $order->mp_payment_info['gateway_public_name']; ?></strong>
        </li>
        <li>
          <?php _e('Payment Type:', 'mp'); ?>
          <strong><?php echo $order->mp_payment_info['method']; ?></strong>
        </li>
        <li>
          <?php _e('Transaction ID:', 'mp'); ?>
          <strong><?php echo $order->mp_payment_info['transaction_id']; ?></strong>
        </li>
        <li>
          <?php _e('Payment Total:', 'mp'); ?>
          <strong><?php echo $mp->format_currency($order->mp_payment_info['currency'], $order->mp_payment_info['total']) . ' ' . $order->mp_payment_info['currency']; ?></strong>
        </li>
      </ul>

<hr/>
      <h3><?php _e('Order Information:', 'mp'); ?></h3>
      <table id="mp-order-product-table" class="mp_cart_contents">
        <thead><tr>
          <th class="mp_cart_col_thumb">&nbsp;</th>
          <th class="mp_cart_col_product"><?php _e('Item', 'mp'); ?></th>
          <th class="mp_cart_col_quant"><?php _e('Quantity', 'mp'); ?></th>
          <th class="mp_cart_col_price"><?php _e('Price', 'mp'); ?></th>
          <th class="mp_cart_col_subtotal"><?php _e('Subtotal', 'mp'); ?></th>
          <th class="mp_cart_col_downloads"><?php _e('Download', 'mp'); ?></th>
        </tr></thead>
        <tbody>
        <?php
          if (is_array($order->mp_cart_info) && count($order->mp_cart_info)) {
						foreach ($order->mp_cart_info as $product_id => $variations) {
							//for compatibility for old orders from MP 1.x
							if (isset($variations['name'])) {
              	$data = $variations;
                echo '<tr>';
	              echo '  <td class="mp_cart_col_thumb">' . mp_product_image( false, 'widget', $product_id ) . '</td>';
	              echo '  <td class="mp_cart_col_product"><a href="' . get_permalink($product_id) . '">' . esc_attr($data['name']) . '</a></td>';
	              echo '  <td class="mp_cart_col_quant">' . number_format_i18n($data['quantity']) . '</td>';
	              echo '  <td class="mp_cart_col_price">' . $mp->format_currency('', $data['price']) . '</td>';
	              echo '  <td class="mp_cart_col_subtotal">' . $mp->format_currency('', $data['price'] * $data['quantity']) . '</td>';
	              echo '  <td class="mp_cart_col_downloads"></td>';
	              echo '</tr>';
							} else {
								foreach ($variations as $variation => $data) {
		              echo '<tr>';
		              echo '  <td class="mp_cart_col_thumb">' . mp_product_image( false, 'widget', $product_id ) . '</td>';
		              echo '  <td class="mp_cart_col_product"><a href="' . get_permalink($product_id) . '">' . esc_attr($data['name']) . '</a></td>';
		              echo '  <td class="mp_cart_col_quant">' . number_format_i18n($data['quantity']) . '</td>';
		              echo '  <td class="mp_cart_col_price">' . $mp->format_currency('', $data['price']) . '</td>';
		              echo '  <td class="mp_cart_col_subtotal">' . $mp->format_currency('', $data['price'] * $data['quantity']) . '</td>';
									if (is_array($data['download']) && $download_url = $mp->get_download_url($product_id, $order->post_title)) {
                    if ($order_paid) {
                      //check for too many downloads
											if (intval($data['download']['downloaded']) < $max_downloads)
												echo '  <td class="mp_cart_col_downloads"><a href="' . $download_url . '">' . __('Download&raquo;', 'mp') . '</a></td>';
											else
											  echo '  <td class="mp_cart_col_downloads">' . __('Limit Reached', 'mp') . '</td>';
										} else {
										  echo '  <td class="mp_cart_col_downloads">' . __('Awaiting Payment', 'mp') . '</td>';
										}
									} else {
										echo '  <td class="mp_cart_col_downloads"></td>';
									}
		              echo '</tr>';
								}
							}
            }
          } else {
            echo '<tr><td colspan="6">' . __('No products could be found for this order', 'mp') . '</td></tr>';
          }
          ?>
        </tbody>
      </table>
      <ul>
        <?php //coupon line
        if ( $order->mp_discount_info ) { ?>
        <li><?php _e('Coupon Discount:', 'mp'); ?> <strong><?php echo $order->mp_discount_info['discount']; ?></strong></li>
        <?php } ?>

        <?php //shipping line
        if ( $order->mp_shipping_total ) { ?>
        <li><?php _e('Shipping:', 'mp'); ?> <strong><?php echo $mp->format_currency('', $order->mp_shipping_total); ?></strong></li>
        <?php } ?>

        <?php //tax line
        if ( $order->mp_tax_total ) { ?>
        <li><?php _e('Taxes:', 'mp'); ?> <strong><?php echo $mp->format_currency('', $order->mp_tax_total); ?></strong></li>
        <?php } ?>

        <li><?php _e('Order Total:', 'mp'); ?> <strong><?php echo $mp->format_currency('', $order->mp_order_total); ?></strong></li>
      </ul>
<hr/>
      <h3><?php _e('Shipping Information:', 'mp'); ?></h3>
      <table>
        <tr>
      	<td align="right"><?php _e('Full Name:', 'mp'); ?></td><td>
        <?php echo esc_attr($order->mp_shipping_info['name']); ?></td>
      	</tr>

      	<tr>
      	<td align="right"><?php _e('Address:', 'mp'); ?></td>
        <td><?php echo esc_attr($order->mp_shipping_info['address1']); ?></td>
      	</tr>

        <?php if ($order->mp_shipping_info['address2']) { ?>
      	<tr>
      	<td align="right"><?php _e('Address 2:', 'mp'); ?></td>
        <td><?php echo esc_attr($order->mp_shipping_info['address2']); ?></td>
      	</tr>
        <?php } ?>

      	<tr>
      	<td align="right"><?php _e('City:', 'mp'); ?></td>
        <td><?php echo esc_attr($order->mp_shipping_info['city']); ?></td>
      	</tr>

      	<?php if ($order->mp_shipping_info['state']) { ?>
      	<tr>
      	<td align="right"><?php _e('State/Province/Region:', 'mp'); ?></td>
        <td><?php echo esc_attr($order->mp_shipping_info['state']); ?></td>
      	</tr>
        <?php } ?>

      	<tr>
      	<td align="right"><?php _e('Postal/Zip Code:', 'mp'); ?></td>
        <td><?php echo esc_attr($order->mp_shipping_info['zip']); ?></td>
      	</tr>

      	<tr>
      	<td align="right"><?php _e('Country:', 'mp'); ?></td>
        <td><?php echo $mp->countries[$order->mp_shipping_info['country']]; ?></td>
      	</tr>

        <?php if ($order->mp_shipping_info['phone']) { ?>
      	<tr>
      	<td align="right"><?php _e('Phone Number:', 'mp'); ?></td>
        <td><?php echo esc_attr($order->mp_shipping_info['phone']); ?></td>
      	</tr>
        <?php } ?>
      </table>
      <?php mp_orderstatus_link(true, false, __('&laquo; Back', 'mp')); ?>
      <?php

    } else { //not valid order id
      echo '<hr/><h3>' . __('Invalid Order ID. Please try again:', 'mp') . '</h3>';
      ?>
      <form action="<?php mp_orderstatus_link(true, true); ?>" method="get">
    		<label><?php _e('Enter your 12-digit Order ID number:', 'mp'); ?><br />
    		<input type="text" name="order_id" id="order_id" class="input" value="" size="20" /></label>
    		<input type="submit" id="order-id-submit" value="<?php _e('Look Up &raquo;', 'mp'); ?>" />
      </form>
      <?php
    }

  } else {

    //get from usermeta
    $user_id = get_current_user_id();
    if ($user_id) {
      if (is_multisite()) {
        global $blog_id;
        $meta_id = 'mp_order_history_' . $blog_id;
      } else {
        $meta_id = 'mp_order_history';
      }
      $orders = get_user_meta($user_id, $meta_id, true);
    } else {
      //get from cookie
      if (is_multisite()) {
        global $blog_id;
        $cookie_id = 'mp_order_history_' . $blog_id . '_' . COOKIEHASH;
      } else {
        $cookie_id = 'mp_order_history_' . COOKIEHASH;
      }

      if (isset($_COOKIE[$cookie_id]))
        $orders = unserialize($_COOKIE[$cookie_id]);
    }

    if (is_array($orders) && count($orders)) {
      krsort($orders);
      //list orders
      echo '<hr/><h3>' . __('Your Recent Orders:', 'mp') . '</h3>';
      echo '<ul id="mp-order-list">';
      foreach ($orders as $timestamp => $order)
        echo '  <li><strong>' . date(get_option('date_format') . ' - ' . get_option('time_format'), $timestamp) . ':</strong> <a href="./' . trailingslashit($order['id']) . '">' . $order['id'] . '</a> - ' . $mp->format_currency('', $order['total']) . '</li>';
      echo '</ul>';

      ?>
      <form action="<?php mp_orderstatus_link(true, true); ?>" method="get">
    		<label><?php _e('Or enter your 12-digit Order ID number:', 'mp'); ?><br />
    		<input type="text" name="order_id" id="order_id" class="input" value="" size="20" /></label>
    		<input type="submit" id="order-id-submit" value="<?php _e('Look Up &raquo;', 'mp'); ?>" />
      </form>
      <?php

    } else {

      if (!is_user_logged_in()) {
        ?>
        <table class="mp_cart_login">
          <thead><tr>
            <th class="mp_cart_login" colspan="2"><?php _e('Have a User Account? Login To View Your Order History:', 'mp'); ?></th>
            <th>&nbsp;</th>
          </tr></thead>
          <tbody>
          <tr>
            <td class="mp_cart_login">
              <form name="loginform" id="loginform" action="<?php echo wp_login_url(); ?>" method="post">
            		<label><?php _e('Username', 'mp'); ?><br />
            		<input type="text" name="log" id="user_login" class="input" value="" size="20" /></label>
                <br />
            		<label><?php _e('Password', 'mp'); ?><br />
            		<input type="password" name="pwd" id="user_pass" class="input" value="" size="20" /></label>
                <br />
            		<input type="submit" name="wp-submit" id="mp_login_submit" value="<?php _e('Login &raquo;', 'mp'); ?>" />
            		<input type="hidden" name="redirect_to" value="<?php mp_orderstatus_link(true, true); ?>" />
              </form>
            </td>
            <td class="mp_cart_or_label"><?php _e('or', 'mp'); ?></td>
            <td class="mp_cart_checkout">
              <form action="<?php mp_orderstatus_link(true, true); ?>" method="get">
            		<label><?php _e('Enter your 12-digit Order ID number:', 'mp'); ?><br />
            		<input type="text" name="order_id" id="order_id" class="input" value="" size="20" /></label>
            		<input type="submit" id="order-id-submit" value="<?php _e('Look Up &raquo;', 'mp'); ?>" />
              </form>
            </td>
          </tr>
          </tbody>
        </table>
        <?php
      } else {
        ?>
        <form action="<?php mp_orderstatus_link(true, true); ?>" method="get">
      		<label><?php _e('Enter your 12-digit Order ID number:', 'mp'); ?><br />
      		<input type="text" name="order_id" id="order_id" class="input" value="" size="20" /></label>
      		<input type="submit" id="order-id-submit" value="<?php _e('Look Up &raquo;', 'mp'); ?>" />
        </form>
        <?php
      }

    }
  }
}

function framemarket_page_title_output() {
    global $wp_query, $wpdb;

    //filter out nav titles
    if (!empty($title) && $id === false)
      return $title;

    if ( $slug = get_query_var('global_taxonomy') ) {
      $slug = $wpdb->escape( $slug );
      $name = $wpdb->get_var( "SELECT name FROM {$wpdb->base_prefix}mp_terms WHERE slug = '$slug'" );
    }

    switch ($wp_query->query_vars['pagename']) {
      case 'mp_global_products':
        return __('Marketplace Products', 'mp');
        break;

      case 'mp_global_categories':
        if ($name)
          return sprintf( __('Product Category: %s', 'mp'), esc_attr($name) );
        else
          return __('Marketplace Product Categories', 'mp');
        break;

      case 'mp_global_tags':
        if ($name)
          return sprintf( __('Product Tag: %s', 'mp'), esc_attr($name) );
        else
          return __('Marketplace Product Tags', 'mp');
        break;

      default:
       return '';
    }
  }
?>