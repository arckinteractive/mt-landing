<?php
include(get_stylesheet_directory() . '/options/options-values.php');
?>
<?php
$themename = wp_get_theme();
$themeinput = $themename . '_styleinput';
$maincolor = $themename . '_maincolor';
$subcolor = $themename . '_subcolor';
$mainfontcolor = $themename . '_mainfontcolor';
$subfontcolor = $themename . '_subfontcolor';
$subbackgroundcolor = $themename . '_subbackgroundcolor';
$subbackgroundhovercolor = $themename . '_subbackgroundhovercolor';
$subbackgroundfontcolor = $themename . '_subbackgroundfontcolor';
$fontheaderinput = $themename . '_fontheaderinput';
$fontbodyinput = $themename . '_fontbodyinput';
?>
<div class="postbox">
           <h3 class="hndle"><span><?php _e('Theme specific styling', 'framemarket'); ?></span></h3>
           <div class="inside">
			<span class="description">
				<?php _e('This theme comes with preset colour styles.  Not liking a built in one?  No problem just pick a base style and then use the color pickers to create a new one.', 'framemarket'); ?>
				</span>
			              <table class="form-table">
<tbody>
	<tr valign="top"><th scope="row"><?php _e( 'Select base style', 'framemarket'); ?></th>
		<td>

			<select name="framemarket_theme_options[<?php echo $themeinput; ?>]">
				<?php
					$selected = isset($options[$themeinput]) ? $options[$themeinput] : '';
					$p = '';
					$r = '';

					foreach ( $style_options as $option ) {
						$label = $option['label'];
						if ( $selected == $option['value'] ) // Make default first in list
							$p = "\n\t<option style=\"padding-right: 10px;\" selected='selected' value='" . esc_attr( $option['value'] ) . "'>$label</option>";
						else
							$r .= "\n\t<option style=\"padding-right: 10px;\" value='" . esc_attr( $option['value'] ) . "'>$label</option>";
					}
					echo $p . $r;
				?>
			</select>
			<label class="description" for="framemarket_theme_options[<?php echo $themeinput; ?>]"><?php _e( 'Select a  style', 'framemarket'); ?></label>
		</td>
	</tr>
	<tr valign="top"><th scope="row"><?php _e( 'Pick a main color', 'framemarket'); ?></th>
		<td>


		#<input class="color {required:false,hash:true}" id="<?php echo stripslashes( $maincolor ); ?>" type="text" name="framemarket_theme_options[<?php echo $maincolor; ?>]" value="<?php echo stripslashes( isset($options[$maincolor]) ? $options[$maincolor] : '' ); ?>" />
		</td>
		</tr>
		<tr valign="top"><th scope="row"><?php _e( 'Pick a sub color', 'framemarket'); ?></th>
			<td>
			#<input class="color {required:false,hash:true}" id="<?php echo stripslashes( $subcolor ); ?>" type="text" name="framemarket_theme_options[<?php echo $subcolor; ?>]" value="<?php echo stripslashes( isset($options[$subcolor]) ? $options[$subcolor] : '' ); ?>" />
			</td>
			</tr>
			<tr valign="top"><th scope="row"><?php _e( 'Pick a main font color', 'framemarket'); ?></th>
				<td>
				#<input class="color {required:false,hash:true}" id="<?php echo stripslashes( $mainfontcolor ); ?>" type="text" name="framemarket_theme_options[<?php echo $mainfontcolor; ?>]" value="<?php echo stripslashes( isset($options[$mainfontcolor]) ? $options[$mainfontcolor] : '' ); ?>" />
				</td>
				</tr>
				<tr valign="top"><th scope="row"><?php _e( 'Pick a sub font color', 'framemarket'); ?></th>
					<td>
					#<input class="color {required:false,hash:true}" id="<?php echo stripslashes( $subfontcolor ); ?>" type="text" name="framemarket_theme_options[<?php echo $subfontcolor; ?>]" value="<?php echo stripslashes( isset($options[$subfontcolor]) ? $options[$subfontcolor] : '' ); ?>" />
					</td>
					</tr>
						<tr valign="top"><th scope="row"><?php _e( 'Pick a navigation, body and footer wrapper color', 'framemarket'); ?></th>
							<td>
							#<input class="color {required:false,hash:true}" id="<?php echo stripslashes( $subbackgroundcolor ); ?>" type="text" name="framemarket_theme_options[<?php echo $subbackgroundcolor; ?>]" value="<?php echo stripslashes( isset($options[$subbackgroundcolor]) ? $options[$subbackgroundcolor] : '' ); ?>" />
							</td>
							</tr>
									<tr valign="top"><th scope="row"><?php _e( 'Pick a navigation, body and footer wrapper hover color', 'framemarket'); ?></th>
										<td>
										#<input class="color {required:false,hash:true}" id="<?php echo stripslashes( $subbackgroundhovercolor ); ?>" type="text" name="framemarket_theme_options[<?php echo $subbackgroundhovercolor; ?>]" value="<?php echo stripslashes( isset($options[$subbackgroundhovercolor]) ? $options[$subbackgroundhovercolor] : '' ); ?>" />
										</td>
										</tr>
											<tr valign="top"><th scope="row"><?php _e( 'Pick a navigation, body and footer wrapper font color', 'framemarket'); ?></th>
												<td>
												#<input class="color {required:false,hash:true}" id="<?php echo stripslashes( $subbackgroundfontcolor ); ?>" type="text" name="framemarket_theme_options[<?php echo $subbackgroundfontcolor; ?>]" value="<?php echo stripslashes( isset($options[$subbackgroundfontcolor]) ? $options[$subbackgroundfontcolor] : '' ); ?>" />
												</td>
												</tr>
		<tr valign="top"><th scope="row"><?php _e( 'Pick a header font', 'framemarket'); ?></th>
			<td>
				<select name="framemarket_theme_options[<?php echo $fontheaderinput; ?>]">
					<?php
						$selected = isset($options[$fontheaderinput]) ? $options[$fontheaderinput] : '';
						$p = '';
						$r = '';

						foreach ( $fontheader_options as $option ) {
							$label = $option['label'];
							if ( $selected == $option['value'] ) // Make default first in list
								$p = "\n\t<option style=\"padding-right: 10px;\" selected='selected' value='" . esc_attr( $option['value'] ) . "'>$label</option>";
							else
								$r .= "\n\t<option style=\"padding-right: 10px;\" value='" . esc_attr( $option['value'] ) . "'>$label</option>";
						}
						echo $p . $r;
					?>
				</select>
				<label class="description" for="framemarket_theme_options[<?php echo $fontheaderinput; ?>]"><?php _e( 'Select a font - this theme uses Google fonts and web safe fonts.' , 'framemarket'); ?></label>
			</td>
		</tr>
			<tr valign="top"><th scope="row"><?php _e( 'Pick a body font', 'framemarket'); ?></th>
				<td>
					<select name="framemarket_theme_options[<?php echo $fontbodyinput; ?>]">
						<?php
							$selected = isset($options[$fontbodyinput]) ? $options[$fontbodyinput] : '';
							$p = '';
							$r = '';

							foreach ( $fontbody_options as $option ) {
								$label = $option['label'];
								if ( $selected == $option['value'] ) // Make default first in list
									$p = "\n\t<option style=\"padding-right: 10px;\" selected='selected' value='" . esc_attr( $option['value'] ) . "'>$label</option>";
								else
									$r .= "\n\t<option style=\"padding-right: 10px;\" value='" . esc_attr( $option['value'] ) . "'>$label</option>";
							}
							echo $p . $r;
						?>
					</select>
					<label class="description" for="framemarket_theme_options[<?php echo $fontbodyinput; ?>]"><?php _e( 'Select a font', 'framemarket'); ?></label>
				</td>
			</tr>
			<tr><th scope="row"><?php _e( 'Add a custom background - *save before leaving this page', 'framemarket'); ?></th>
			<td>
				<a class="button" href="themes.php?page=custom-background">
				<?php _e('Add a background', 'framemarket'); ?>
					</a>
			</td>
			</tr>
  </tbody></table>
</div>
</div>