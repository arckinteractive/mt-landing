<div class="postbox">
           <h3 class="hndle"><span><?php _e( 'Do you want to have a header ad?', 'framemarket' ); ?></span></h3>
           <div class="inside">
			<span class="description"><?php _e( 'You can have an ad in the header - html code works here.', 'framemarket' ); ?></span>
			              <table class="form-table">
<tbody>
	<tr><th scope="row"><?php _e( 'Ad text or code', 'framemarket' ); ?></th>
		<td>
			<textarea id="framemarket_theme_options[adverttextarea]" class="large-text" cols="50" rows="5" name="framemarket_theme_options[adverttextarea]"><?php echo stripslashes( isset($options['adverttextarea']) ? $options['adverttextarea'] : '' ); ?></textarea>
			<label class="description" for="framemarket_theme_options[adverttextarea]"><?php _e( 'Your Advert', 'framemarket'); ?></label>
		</td>
	</tr>
  </tbody></table>
</div>
</div>