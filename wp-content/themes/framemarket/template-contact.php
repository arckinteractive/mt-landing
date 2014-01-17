<?php
/*
Template Name: contact
*/
?>

<?php
if(isset($_POST['submitted'])) {
		if(trim($_POST['contactName']) === '') {
			$nameError =  __('You forgot to enter your name.', 'framemarket');
			$hasError = true;
		} else {
			$name = trim($_POST['contactName']);
		}

		if(trim($_POST['email']) === '')  {
			$emailError = __('You forgot to enter your email address.', 'framemarket');
			$hasError = true;
		} else if ( sanitize_email(trim($_POST['email'])) != trim($_POST['email']) ) {
			$emailError = __('You entered an invalid email address.', 'framemarket');
			$hasError = true;
		} else {
			$email = trim($_POST['email']);
		}

		if(trim($_POST['comments']) === '') {
			$commentError = __('You forgot to enter your comments.', 'framemarket');
			$hasError = true;
		} else {
			if(function_exists('stripslashes')) {
				$comments = stripslashes(trim($_POST['comments']));
			} else {
				$comments = trim($_POST['comments']);
			}
		}

		if(!isset($hasError)) {

			$emailTo = get_option('admin_email');
			$subject = __('Contact Form Submission from ', 'framemarket').$name;
			$sendCopy = trim($_POST['sendCopy']);
			$body = __("Name: $name \n\nEmail: $email \n\nComments: $comments", 'framemarket');
			$headers = __('From: ', 'framemarket') .' <'.$emailTo.'>' . "\r\n" . __('Reply-To: ','framemarket') . $email;

			wp_mail($emailTo, $subject, $body, $headers);

			$emailSent = true;

		}
}
?>
<?php get_header(); ?>
<script type="text/javascript">
<!--//--><![CDATA[//><!--
  jQuery.noConflict();
jQuery(document).ready(function() {
	jQuery('form#contactForm').submit(function() {
		jQuery('form#contactForm .error').remove();
		var hasError = false;
		jQuery('.requiredField').each(function() {
			if(jQuery.trim(jQuery(this).val()) == '') {
				var labelText = jQuery(this).prev('label').text();
				jQuery(this).parent().append('<span class="error"><?php _e('You forgot to enter your', 'framemarket'); ?> '+labelText+'.</span>');
				jQuery(this).addClass('inputError');
				hasError = true;
			} else if(jQuery(this).hasClass('email')) {
				var emailReg = /^([\w-\.]+@([\w-]+\.)+[\w-]{2,4})?$/;
				if(!emailReg.test(jQuery.trim(jQuery(this).val()))) {
					var labelText = jQuery(this).prev('label').text();
					jQuery(this).parent().append('<span class="error"><?php _e('You entered an invalid', 'framemarket'); ?> '+labelText+'.</span>');
					jQuery(this).addClass('inputError');
					hasError = true;
				}
			}
		});
		if(!hasError) {
			var formInput = jQuery(this).serialize();
			jQuery.post(jQuery(this).attr('action'),formInput, function(data){
				jQuery('form#contactForm').slideUp("fast", function() {
					jQuery(this).before('<p class="tick"><?php _e('Your email was successfully sent.', 'framemarket'); ?></p>');
				});
			});
		}
		return false;
	});
});
//-->!]]>
</script>
<div id="content">
				<?php if(isset($emailSent) && $emailSent == true) { ?>

	       			<p class="info"><?php _e('Your email was successfully sent.', 'framemarket'); ?></p>

	   			<?php } else { ?>

			    <?php if (have_posts()) : $count = 0; ?>
				<?php while (have_posts() && $count == 0) : the_post(); $count++; ?>
						<div id="contact-page">
							<h2 class="title"><?php the_title(); ?></h2>
								<div class="entry">
									<?php the_content(); ?>
								</div>

								<?php if(isset($hasError) || isset($captchaError) ) { ?>
		                        <p class="alert"><?php _e('There was an error submitting the form.', 'framemarket'); ?></p>
		                    <?php } ?>

							 <?php if ( get_option('admin_email') == '' ) { ?>
			                        <p class="alert"><?php _e('E-mail has not been setup properly. Please add your contact e-mail!', 'framemarket'); ?></p>
			                    <?php } ?>
								  <form action="<?php the_permalink(); ?>" id="contactForm" method="post">

				                        <ol class="forms">
				                            <li><label for="contactName"><?php _e('Name', 'framemarket'); ?></label>
				                            	<input type="text" name="contactName" id="contactName" value="<?php if(isset($_POST['contactName'])) echo $_POST['contactName'];?>" class="txt requiredField" size="60"/>
				                                <?php if(!empty($nameError)) { ?>
				                                    <span class="error"><?php echo $nameError;?></span>
				                                <?php } ?>
				                            </li>

				                            <li><label for="email"><?php _e('Email', 'framemarket'); ?></label>
				                            	<input type="text" name="email" id="email" value="<?php if(isset($_POST['email']))  echo $_POST['email'];?>" class="txt requiredField email" size="60"/>
				                                <?php if(!empty($emailError)) { ?>
				                                    <span class="error"><?php echo $emailError;?></span>
				                                <?php } ?>
				                            </li>

				                            <li class="textarea"><label for="commentsText"><?php _e('Message', 'framemarket'); ?></label>
				                                <textarea name="comments" id="commentsText" rows="10" cols="35" class="requiredField"><?php if(isset($_POST['comments'])) { if(function_exists('stripslashes')) { echo stripslashes($_POST['comments']); } else { echo $_POST['comments']; } } ?></textarea>
				                                <?php if(!empty($commentError)) { ?>
				                                    <span class="error"><?php echo $commentError;?></span>
				                                <?php } ?>
				                            </li>

				                            <li class="buttons"><input type="hidden" name="submitted" id="submitted" value="true" /><input class="submit button" type="submit" value="<?php _e('Submit', 'framemarket'); ?>" /></li>
				                        </ol>
				                    </form>

									</div>

									<?php endwhile; endif; ?>

									<?php } ?>
</div>
	<?php get_sidebar(); ?>
	<div class="clear"></div>
<?php get_footer() ?>