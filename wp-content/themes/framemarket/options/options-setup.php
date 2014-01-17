<div class="postbox">
           <h3 class="hndle"><span><?php _e( 'Set up your theme', 'framemarket'); ?></span></h3>
           <div class="inside">
			<span class="description">
				<?php _e( 'These simple options will allow you control over some areas of your site.', 'framemarket'); ?></span>
			              <table class="form-table">
<tbody><tr><th scope="row"><?php _e( 'Type of header', 'framemarket' ); ?></th>
	<td>
		<fieldset><legend class="screen-reader-text"><span><?php _e( 'Select one' , 'framemarket'); ?></span></legend>
		<?php
			if ( ! isset( $checked ) )
				$checked = '';
			$radio_setting = isset($options['logoinput']) ? $options['logoinput'] : '';
			foreach ( $logo_options as $option ) {

				if ( '' != $radio_setting ) {
					if ( $radio_setting == $option['value'] ) {
						$checked = "checked=\"checked\"";
					} else {
						$checked = '';
					}
				}
				?>
				<label class="description"><input type="radio" name="framemarket_theme_options[logoinput]" value="<?php echo esc_attr( $option['value'] ); ?>" <?php echo $checked; ?> /> <?php echo $option['label']; ?></label><br />
				<?php
			}
		?>
		</fieldset>
	</td>
</tr>
	<tr valign="top"><th scope="row"><?php _e( 'Enter some text for your site name' , 'framemarket'); ?></th>
		<td>
			<input id="framemarket_theme_options[logotext]" class="regular-text" type="text" name="framemarket_theme_options[logotext]" value="<?php echo esc_attr( isset($options['logotext']) ? $options['logotext'] : '' ); ?>" />
			<label class="description" for="framemarket_theme_options[logotext]"><?php _e( 'Site name', 'framemarket'); ?></label>
		</td>
	</tr>
			<tr><th scope="row"><?php _e('Add a custom header - *save before leaving this page', 'framemarket'); ?></th>
			<td>
				<a class="button" href="themes.php?page=custom-header">
				<?php _e("Add a custom header", 'framemarket'); ?>
					</a>
			</td>
			</tr>
			<?php if ( class_exists( 'MarketPress' ) ) {
				?>
					<?php  if ( is_multisite() ) {?>
			<tr><th scope="row"><?php _e( 'Show shop picker?', 'framemarket' ); ?></th>
				<td>
					<fieldset><legend class="screen-reader-text"><span><?php _e( 'Select one' , 'framemarket'); ?></span></legend>
					<?php
						if ( ! isset( $checked ) )
							$checked = '';
						$radio_setting = isset($options['pickerinput']) ? $options['pickerinput'] : '';
						foreach ( $picker_options as $option ) {

							if ( '' != $radio_setting ) {
								if ( $radio_setting == $option['value'] ) {
									$checked = "checked=\"checked\"";
								} else {
									$checked = '';
								}
							}
							?>
							<label class="description"><input type="radio" name="framemarket_theme_options[pickerinput]" value="<?php echo esc_attr( $option['value'] ); ?>" <?php echo $checked; ?> /> <?php echo $option['label']; ?></label><br />
							<?php
						}
					?>
					</fieldset>
				</td>
			</tr>
				<tr><th scope="row"><?php _e( 'Show global or just that main shop products on the front?', 'framemarket' ); ?></th>
					<td>
						<fieldset><legend class="screen-reader-text"><span><?php _e( 'Select one' , 'framemarket'); ?></span></legend>
						<?php
							if ( ! isset( $checked ) )
								$checked = '';
							$radio_setting = isset($options['showinput']) ? $options['showinput'] : '';
							foreach ( $show_options as $option ) {

								if ( '' != $radio_setting ) {
									if ( $radio_setting == $option['value'] ) {
										$checked = "checked=\"checked\"";
									} else {
										$checked = '';
									}
								}
								?>
								<label class="description"><input type="radio" name="framemarket_theme_options[showinput]" value="<?php echo esc_attr( $option['value'] ); ?>" <?php echo $checked; ?> /> <?php echo $option['label']; ?></label><br />
								<?php
							}
						?>
						</fieldset>
					</td>
				</tr>
				<tr><th scope="row"><?php _e( 'What search bar do you want to use?', 'framemarket' ); ?></th>
					<td>
						<fieldset><legend class="screen-reader-text"><span><?php _e( 'Select one' , 'framemarket'); ?></span></legend>
						<?php
							if ( ! isset( $checked ) )
								$checked = '';
							$radio_setting = isset($options['searchinput']) ? $options['searchinput'] : '';
							foreach ( $search_options as $option ) {

								if ( '' != $radio_setting ) {
									if ( $radio_setting == $option['value'] ) {
										$checked = "checked=\"checked\"";
									} else {
										$checked = '';
									}
								}
								?>
								<label class="description"><input type="radio" name="framemarket_theme_options[searchinput]" value="<?php echo esc_attr( $option['value'] ); ?>" <?php echo $checked; ?> /> <?php echo $option['label']; ?></label><br />
								<?php
							}
						?>
						</fieldset>
					</td>
				</tr>
				<?php }?>
						<?php }?>
  </tbody></table>
</div>
</div>