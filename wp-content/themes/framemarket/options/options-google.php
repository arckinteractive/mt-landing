<div class="postbox">
           <h3 class="hndle"><span><?php _e( 'Add your Google Analytics code', 'framemarket'); ?></span></h3>
                   <div class="inside">
					<span class="description"><?php _e( 'Easily add Google Analytics code just enter it her.', 'framemarket'); ?></span>
					              <table class="form-table">
		<tbody>
			<tr><th scope="row"><?php _e( 'Ad text or code', 'framemarket'); ?></th>
				<td>
					<textarea id="framemarket_theme_options[googletextarea]" class="large-text" cols="50" rows="5" name="framemarket_theme_options[googletextarea]"><?php echo stripslashes( isset($options['googletextarea']) ? $options['googletextarea'] : '' ); ?></textarea>
					<label class="description" for="framemarket_theme_options[googletextarea]"><?php _e( 'Your Google Analytics', 'framemarket'); ?></label>
				</td>
			</tr>
  </tbody></table>
</div>
</div>