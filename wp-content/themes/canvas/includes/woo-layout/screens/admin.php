<div class="wrap" id="woothemes-layouts">
<?php screen_icon( 'themes' ); ?><h2><?php _e( 'Layouts', 'woothemes' ); ?></h2>
<?php if ( isset( $_GET['updated'] ) && $_GET['updated'] == 'true' ) { echo '<div class="updated fade"><p><strong>' . __( 'Layouts Settings Updated.', 'woothemes' ) . '</strong></p></div>'; } ?>
<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=woo-layout-manager&updated=true' ) ); ?>">
<?php
wp_nonce_field( 'woolayout-options-update' );
$this->_generate_settings_html();
$this->_generate_layout_note();
$this->_generate_sections_html();
submit_button();
?>
</form>
</div><!--.wrap #woothemes-layouts-->