<div class="postbox">
           <h3 class="hndle"><span>	<?php _e('Add your own links to the footer or leave blank and use the default links', 'framemarket'); ?></span></h3>
           <div class="inside">
			<span class="description"><?php _e( 'Override the default footer links here.', 'framemarket'); ?></span>
			              <table class="form-table">
<tbody>
	<tr><th scope="row"><?php _e( 'Footer text or code', 'framemarket'); ?></th>
		<td>
			<textarea id="framemarket_theme_options[footertextarea]" class="large-text" cols="50" rows="5" name="framemarket_theme_options[footertextarea]"><?php echo stripslashes( isset($options['footertextarea']) ? $options['footertextarea'] : '' ); ?></textarea>
			<label class="description" for="framemarket_theme_options[footertextarea]"><?php _e( 'Your Footer Links', 'framemarket'); ?></label>
		</td>
	</tr>
  </tbody></table>
</div>
</div>