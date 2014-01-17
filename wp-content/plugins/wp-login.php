<!DOCTYPE html>
	<html xmlns="http://www.w3.org/1999/xhtml" lang="en-US">
	<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<title>WPMU DEV &#8211; The WordPress Experts &rsaquo; Log In</title>
	<link rel='stylesheet' id='wp-admin-css'  href='http://premium.wpmudev.org/wp-admin/css/wp-admin.min.css?ver=3.5.2' type='text/css' media='all' />
<link rel='stylesheet' id='buttons-css'  href='http://premium.wpmudev.org/wp-includes/css/buttons.min.css?ver=3.5.2' type='text/css' media='all' />
<link rel='stylesheet' id='colors-fresh-css'  href='http://premium.wpmudev.org/wp-admin/css/colors-fresh.min.css?ver=3.5.2' type='text/css' media='all' />
<script type='text/javascript' src='http://premium.wpmudev.org/wp-includes/js/jquery/jquery.js?ver=1.8.3'></script>

    <style>
    body.login #login h1 a {
        background: url('http://premium.wpmudev.org/wp-content/themes/wpmudev-new/i/logo-login.png') no-repeat scroll left center transparent;
        height: 90px;
        width: 325px;
        margin-left: 5px;
    }
    </style>
    <meta name='robots' content='noindex,nofollow' />
	</head>
	<body class="login login-action-login wp-core-ui">
	<div id="login">
		<h1><a href="http://premium.wpmudev.org" title="Powered by WordPress">WPMU DEV &#8211; The WordPress Experts</a></h1>
	
<form name="loginform" id="loginform" action="http://premium.wpmudev.org/wp-login.php" method="post">
	<p>
		<label for="user_login">Username<br />
		<input type="text" name="log" id="user_login" class="input" value="" size="20" /></label>
	</p>
	<p>
		<label for="user_pass">Password<br />
		<input type="password" name="pwd" id="user_pass" class="input" value="" size="20" /></label>
	</p>
<script type="text/javascript">
	// Form Label
	if ( document.getElementById('loginform') )
		document.getElementById('loginform').childNodes[1].childNodes[1].childNodes[0].nodeValue = 'Username or Email';

	// Error Messages
	if ( document.getElementById('login_error') )
		document.getElementById('login_error').innerHTML = document.getElementById('login_error').innerHTML.replace( 'username', 'Username or Email' );
	</script><p><a class="wpmu_dev_login-button wpmu_dev_login-facebook" href="#"><span class="fb-icon"></span>Facebook</a> <a class="wpmu_dev_login-button wpmu_dev_login-google" href="#" data-auth_url="https://www.google.com/accounts/o8/ud?openid.ns=http%3A%2F%2Fspecs.openid.net%2Fauth%2F2.0&openid.mode=checkid_setup&openid.return_to=http%3A%2F%2Fpremium.wpmudev.org%2Fwp-admin%2Fadmin-ajax.php%3Faction%3Dwpmudev_social_apis_auth_google&openid.realm=http%3A%2F%2Fpremium.wpmudev.org&openid.ns.ax=http%3A%2F%2Fopenid.net%2Fsrv%2Fax%2F1.0&openid.ax.mode=fetch_request&openid.ax.type.namePerson_first=http%3A%2F%2Faxschema.org%2FnamePerson%2Ffirst&openid.ax.type.namePerson_last=http%3A%2F%2Faxschema.org%2FnamePerson%2Flast&openid.ax.type.contact_email=http%3A%2F%2Faxschema.org%2Fcontact%2Femail&openid.ax.required=namePerson_first%2CnamePerson_last%2Ccontact_email&openid.claimed_id=http%3A%2F%2Fspecs.openid.net%2Fauth%2F2.0%2Fidentifier_select&openid.identity=http%3A%2F%2Fspecs.openid.net%2Fauth%2F2.0%2Fidentifier_select"><span class="g-icon"></span>Google</a> </p>	<p class="forgetmenot"><label for="rememberme"><input name="rememberme" type="checkbox" id="rememberme" value="forever"  /> Remember Me</label></p>
	<p class="submit">
		<input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large" value="Log In" />
		<input type="hidden" name="redirect_to" value="http://premium.wpmudev.org/wp-admin/" />
		<input type="hidden" name="testcookie" value="1" />
	</p>
</form>

<p id="nav">
<a href="http://premium.wpmudev.org/wp-login.php?action=register">Register</a> |
<a href="http://premium.wpmudev.org/wp-login.php?action=lostpassword" title="Password Lost and Found">Lost your password?</a>
</p>

<script type="text/javascript">
function wp_attempt_focus(){
setTimeout( function(){ try{
d = document.getElementById('user_login');
d.focus();
d.select();
} catch(e){}
}, 200);
}

wp_attempt_focus();
if(typeof wpOnload=='function')wpOnload();
</script>

	<p id="backtoblog"><a href="http://premium.wpmudev.org/" title="Are you lost?">&larr; Back to WPMU DEV &#8211; The WordPress Experts</a></p>
	
	</div>

	
	
<style>
a {
	-webkit-transition: all 0.25s linear;
	-moz-transition: all 0.25s linear;
	transition: all 0.25s linear;
}
.wpmu_dev_login-button {
	display: inline-block;
    color: #41B3DD;
    font-size: 20px;
    letter-spacing: 0;
	margin: 0 25px 20px 0;
    text-align: left;
	text-decoration: none;
}
.wpmu_dev_login-button:hover {
	color: #e8da42;
}
.wpmu_dev_login-google {
	margin-right: 0;
}
.wpmu_dev_login-button .g-icon, 
.wpmu_dev_login-button .fb-icon
{
	background: url("http://premium.wpmudev.org/wp-content/themes/wpmudev-new/i/google-fb-sprite.png") no-repeat scroll 0 0 transparent;
	/*float: left;*/
    height: 22px;
    margin-right: 10px;
    position: relative;
    top: 3px;
    width: 22px;
    display: inline-block;
}
.wpmu_dev_login-button .fb-icon {
	background-position: 0 -22px;
}
</style>
	<div id="fb-root"></div><script type="text/javascript">
		window.fbAsyncInit = function() {
			FB.init({
			  appId: "208345015953410",
			  status: true,
			  cookie: true,
			  xfbml: true
			});
		};
		// Load the FB SDK Asynchronously
		(function(d){
			var js, id = "facebook-jssdk"; if (d.getElementById(id)) {return;}
			js = d.createElement("script"); js.id = id; js.async = true;
			js.src = "//connect.facebook.net/en_US/all.js";
			d.getElementsByTagName("head")[0].appendChild(js);
		}(document));
		</script>
	<script type="text/javascript">
		(function ($) {
		$(function () {
			$(".wpmu_dev_login-facebook").off("click").on("click", function () {
				FB.login(function (resp) {
					if (resp.authResponse && resp.authResponse.userID) {
						// change UI
						// ...
						$.post("http://premium.wpmudev.org/wp-admin/admin-ajax.php", {
							"action": "wpmudev_social_apis_login_facebook",
							"user_id": resp.authResponse.userID,
							"token": FB.getAccessToken()
						}, function (data) {
							window.location = "http://premium.wpmudev.org";
						}, "json");
					}
				}, {scope: "email"});
				return false;
			});
		});
		})(jQuery);
		</script>
	
	<script type="text/javascript">
	(function ($) {
	$(function () {
		$(".wpmu_dev_login-google").off("click").on("click", function () {
			var url = $(this).attr("data-auth_url");
			if (!url) return false;
			var googleLogin = window.open(url, "google_login", "scrollbars=no,resizable=no,toolbar=no,location=no,directories=no,status=no,menubar=no,copyhistory=no,height=400,width=600");
			var gTimer = setInterval(function () {
				try {
					if (googleLogin.location.hostname == window.location.hostname) {
						clearInterval(gTimer);
						googleLogin.close();
						$.post("http://premium.wpmudev.org/wp-admin/admin-ajax.php", {
							"action": "wpmudev_social_apis_login_google"
						}, function (data) {
							window.location = "http://premium.wpmudev.org";
						}, "json");
					}
				} catch (e) {}
			}, 300);
			return false;
		});
	});
	})(jQuery);
	</script>
		<div class="clear"></div>
	</body>
	</html>
	