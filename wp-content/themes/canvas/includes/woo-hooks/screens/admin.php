<div class="wrap" id="woothemes-hooks">
<?php screen_icon( 'themes' ); ?><h2><?php _e( 'Hooks', 'woothemes' ); ?></h2>
<?php if ( isset( $_GET['updated'] ) && $_GET['updated'] == 'true' ) { echo '<div class="updated fade"><p><strong>' . __( 'Hooks Settings Updated.', 'woothemes' ) . '</strong></p></div>'; } ?>
<?php echo $this->_generate_sections_menu(); ?>
<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=woo-hook-manager&updated=true' ) ); ?>">
<?php
wp_nonce_field( 'woohooks-options-update' );
$this->_generate_sections_html();
submit_button();
?>
</form>
</div><!--.wrap #woothemes-filters-->