<div class="panel conversion">

  <?php if (get_field('conversion_text')) {
      echo '<h3>' . get_field('conversion_text') . '</h3>';
  } ?>

  <?php if ( get_field('conversion_link_1') && get_field('conversion_link_1_text') ) {
      echo '<a class="button" href="' . get_field('conversion_link_1') . '">' . get_field('conversion_link_1_text') . '</a>';
  } ?>

  <?php if ( get_field('conversion_link_2') && get_field('conversion_link_2_text') ) {
      echo '<a class="button" href="' . get_field('conversion_link_2') . '">' . get_field('conversion_link_2_text') . '</a>';
  } ?>

</div>