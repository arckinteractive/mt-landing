<div class="panel">

  <?php if ( is_page() ) {
    if($post->post_parent)
      $children = wp_list_pages('sort_column=menu_order&title_li=&child_of='.$post->post_parent.'&echo=0');
    else
      $children = wp_list_pages('sort_column=menu_order&title_li=&child_of='.$post->ID.'&echo=0');
    if ($children) {
  ?>

  <div class="sidebar">
    <h2>Sub-pages of Current Page</h2>
    <ul>
      <?php echo $children; ?>
    </ul>
  </div>
  
  <?php
    } // End If Post
    } // End if is page
  ?>

</div>