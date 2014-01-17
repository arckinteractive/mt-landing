<?php
$id = md5(microtime() . rand());
$admin_url = admin_url('admin-ajax.php');

if ('after' == $mailchimp_position) echo apply_filters('wdsi_content', $message->post_content);

echo '<form id="wdsi-mailchimp-' . $id . '" class="wdsi-mailchimp-root wdsi-clearfix">';
echo '<input type="hidden" class="wdsi-mailchimp-post_id" value="' . esc_attr($message->ID) . '" />';
echo '<label for="wdsi-mailchimp-' . $id . '-email" class="wdsi-mailchimp-label">' . __('Email:', 'wdsi') . '</label>';
echo '<input type="text" id="wdsi-mailchimp-' . $id . '-email" class="wdsi-mailchimp-email" placeholder="' . esc_attr($mailchimp_placeholder) . '" />';
echo '<button class="wdsi-mailchimp-subscribe" type="button">' . __('Subscribe', 'wdsi') . '</button>';
echo '<div class="wdsi-mailchimp-result"></div>';
echo '</form>';

if ('before' == $mailchimp_position) echo apply_filters('wdsi_content', $message->post_content);

?>

<script>
(function ($) {

function mailchimp_subscribe (root) {
	var $email = root.find(".wdsi-mailchimp-email"),
		$post_id = root.find(".wdsi-mailchimp-post_id"),
		$result = root.find(".wdsi-mailchimp-result")
	;
	if (!$email.length || !$email.val()) return false;
	$.post("<?php echo $admin_url; ?>", {
		"action": "wdsi_mailchimp_subscribe",
		'post_id': $post_id.val(),
		"email": $email.val()
	}, function (data) {
		var is_error = data && data.is_error && parseInt(data.is_error, 10),
			msg = (data && data.message ? data.message : "<?php echo esc_js(__('Error', 'wdsi')); ?>"),
			error_class = 'wdsi-mailchimp_error',
			success_class = 'wdsi-mailchimp_success',
			wrapper = (is_error ? error_class : success_class)
		;
		$result
			.removeClass(error_class).removeClass(success_class)
			.addClass(wrapper)
			.html(msg)
		;
	}, 'json');
	return false;
}

$(function () {
$(".wdsi-mailchimp-subscribe").click(function () {
	mailchimp_subscribe($(this).parents(".wdsi-mailchimp-root"));
	return false;
});
});
})(jQuery);
</script>