<?php

/* @author    2codeThemes
*  @package   WPQA/shortcodes
*  @version   1.0
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/* Password shortcode */
if (!function_exists('wpqa_lost_pass')) :
	function wpqa_lost_pass($atts, $content = null) {
		$protocol = is_ssl() ? 'https' : 'http';
		$a = shortcode_atts( array(
		    'dark_button' => '',
		    'text' => '',
		), $atts );
		$out = '';
		if (is_user_logged_in()) {
			$out .= wpqa_login_already();
		}else {
			$rand_w = rand(1,1000);
			if ($a["text"] == "") {
				$out .= '<p>'.esc_html__("Lost your password? Please enter your email address. You will receive a link and will create a new password via email.","wpqa").'</p>';
			}
			$out .= '<form method="post" class="wpqa-lost-password wpqa_form">
				<div class="wpqa_error_desktop wpqa_hide"><div class="wpqa_error"></div></div>
				<div class="wpqa_success"></div>
				<div class="form-inputs clearfix">
					<p>
						<label for="user_mail_'.$rand_w.'">'.esc_html__("E-Mail","wpqa").'<span class="required">*</span></label>
						<input type="email" class="required-item" name="user_mail" id="user_mail_'.$rand_w.'">
						<i class="icon-mail"></i>
					</p>'.
					wpqa_add_captcha(wpqa_options("the_captcha_password"),"password",$rand_w).'
				</div>

				<div class="clearfix"></div>
				<div class="wpqa_error_mobile wpqa_hide"><div class="wpqa_error"></div></div>

				<p class="form-submit">
					<span class="load_span"><span class="loader_2"></span></span>
					<input type="submit" value="'.esc_attr__("Reset","wpqa").'" class="button-default'.(isset($a["dark_button"]) && $a["dark_button"] == "dark_button"?" dark_button":"").'">
					<input type="hidden" name="form_type" value="wpqa-forget">
					<input type="hidden" name="action" value="wpqa_ajax_password_process">
					<input type="hidden" name="redirect_to" value="'.esc_url(wp_unslash($protocol.'://'.wpqa_server('HTTP_HOST').wpqa_server('REQUEST_URI'))).'">
					<input type="hidden" name="wpqa_pass_nonce" value="'.wp_create_nonce("wpqa_pass_nonce").'">
				</p>
			</form>';
		}
		return $out;
	}
endif;
/* Password jQuery */
if (!function_exists('wpqa_pass_jquery')) :
	function wpqa_pass_jquery() {
		$errors = new WP_Error();
		if ( isset( $_REQUEST['redirect_to'] ) ) $redirect_to = $_REQUEST['redirect_to']; else $redirect_to = esc_url(home_url('/'));
		// Errors
		if ( is_user_logged_in() ) :
			$user_id = get_current_user_id();
			$errors->add('already_logged', sprintf(esc_html__('You are already logged in, If you want to change your password go to %1$s edit profile %2$s.','wpqa'),'<a href="'.esc_url(wpqa_get_profile_permalink($user_id,"edit")).'">','</a>'));
		elseif ( !isset($_POST['user_mail']) ) :
			$errors->add('empty_email', sprintf(esc_html__('%1$s ERROR %2$s: please insert your email.','wpqa'),'<strong>','</strong>'));
		elseif ( !email_exists($_POST['user_mail']) ) :
			$errors->add('invalid_email', sprintf(esc_html__('%1$s ERROR %2$s: there is no user registered with that email address.','wpqa'),'<strong>','</strong>'));
		elseif (isset($_POST['user_mail']) && $_POST['user_mail'] != "") :
			$get_user_by_mail = get_user_by('email',esc_html($_POST['user_mail']));
			if (!isset($get_user_by_mail->ID)) :
				$errors->add('invalid_email', sprintf(esc_html__('%1$s ERROR %2$s: there is no user registered with that email address.','wpqa'),'<strong>','</strong>'));
			endif;
		endif;

		wpqa_check_captcha(wpqa_options("the_captcha_password"),"password",$_POST,$errors);

		if (wpqa_is_ajax()) :
			// Result
			$result = array();
			if ( !$errors->get_error_code() ) :
				$result['success'] = 1;
				if ($_POST['form_type']) :
					unset($_POST["form_type"]);
				endif;
				$rand_a = wpqa_token(15);
				$get_reset_password = get_user_meta($get_user_by_mail->ID,"reset_password",true);
				if ($get_reset_password == "") :
					update_user_meta($get_user_by_mail->ID,"reset_password",$rand_a);
					$get_reset_password = $rand_a;
				endif;
				$confirm_link_email = esc_url_raw(add_query_arg(array("u" => $get_user_by_mail->ID,"reset_password" => $get_reset_password),esc_url(home_url('/'))));
				$send_text = wpqa_send_email(wpqa_options("email_new_password"),$get_user_by_mail->ID,"","","",$confirm_link_email);
				$last_message_email = wpqa_email_code($send_text);
				$email_title = wpqa_options("title_new_password");
				$email_title = ($email_title != ""?$email_title:esc_html__("Reset your password","wpqa"));
				$email_template = wpqa_options("email_template");
				$mail_smtp = wpqa_options("mail_smtp");
				$email_template = ($mail_smtp == "on"?wpqa_options("mail_username"):$email_template);
				wpqa_sendEmail($email_template,get_bloginfo('name'),esc_html($_POST['user_mail']),esc_html($get_user_by_mail->display_name),$email_title,$last_message_email);
				$result['redirect'] = $redirect_to;
				$result['done'] = esc_html__('Check your email please.','wpqa');
			else :
				$result['success'] = 0;
				foreach ($errors->errors as $error) {
					$result['error'] = $error[0];
					break;
				}
			endif;
			echo json_encode($result);
			die();
		else :
			wpqa_pass_jquery();
		endif;
		return $errors;
	}
endif;
/* Password process */
if (!function_exists('wpqa_pass_process')) :
	function wpqa_pass_process() {
		if (isset($_POST['form_type']) && $_POST['form_type'] == "wpqa-forget") :
			global $wpqa_pass_errors;
			$wpqa_pass_errors = wpqa_pass_jquery();
		endif;
	}
endif;
add_action('wpqa_init','wpqa_pass_process');
/* Lostpassword URL */
add_filter('lostpassword_url','wpqa_lostpassword_url',10,0);
if (!function_exists('wpqa_lostpassword_url')) :
	function wpqa_lostpassword_url() {
		if (isset($GLOBALS['pagenow']) && $GLOBALS['pagenow'] === 'wp-login.php') {
			return wpqa_lost_password_permalink();
		}else {
			return '#" class="lost-passwords';
		}
	}
endif;
/* Remove text */
add_filter('gettext','wpqa_remove_text');
if (!function_exists('wpqa_remove_text')) :
	function wpqa_remove_text($text) {
		return $text;
	}
endif;
/* Stop change password email */
add_filter("send_password_change_email","__return_false");
/* Reset password */
add_filter('wpqa_init','wpqa_reset_password');
if (!function_exists('wpqa_reset_password')) :
	function wpqa_reset_password() {
		if (isset($_GET['reset_password']) && isset($_GET['u'])) {
			$user_reset = (int)esc_attr($_GET['u']);
			if (!is_user_logged_in()) :
				$reset_password = get_user_meta($user_reset,"reset_password",true);
				if ($reset_password == (int)$_GET['reset_password']) :
					$pw = wpqa_token(15);
					wp_set_password($pw,$user_reset);
					$author_user_email = get_the_author_meta("user_email",$user_reset);
					$author_display_name = get_the_author_meta("display_name",$user_reset);
					delete_user_meta($user_reset,"reset_password");
					$send_text = wpqa_send_email(wpqa_options("email_new_password_2"),$user_reset,"","",$pw);
					$last_message_email = wpqa_email_code($send_text);
					$email_title = wpqa_options("title_new_password_2");
					$email_title = ($email_title != ""?$email_title:esc_html__("Reset your password","wpqa"));
					$email_template = wpqa_options("email_template");
					$mail_smtp = wpqa_options("mail_smtp");
					$email_template = ($mail_smtp == "on"?wpqa_options("mail_username"):$email_template);
					wpqa_sendEmail($email_template,get_bloginfo('name'),$author_user_email,$author_display_name,$email_title,$last_message_email);
					wpqa_session('<div class="alert-message success"><i class="icon-check"></i><p>'.esc_html__("Check your email, Your password has been reset.","wpqa").'</p></div>','wpqa_session');
					wp_safe_redirect(esc_url(home_url('/')));
					die();
				else :
					wpqa_session('<div class="alert-message error"><i class="icon-cancel"></i><p>'.esc_html__("Your password reset link has expired or not valid, Please request a new link","wpqa").' <a href="#" class="reset-password">'.esc_html__( 'Reset Password', 'wpqa' ).'</a>.</p></div>','wpqa_session');
					wp_safe_redirect(esc_url(home_url('/')));
					die();
				endif;
			else :
				$user_id = get_current_user_id();
				$if_user_id = get_user_by("id",$user_id);
				if (isset($if_user_id->caps["activation"]) && $if_user_id->caps["activation"] == 1) :
					// Not activation!
				else :
					wpqa_session('<div class="alert-message success"><i class="icon-check"></i><p>'.sprintf(esc_html__('You are already logged in, If you want to change your password go to %1$s edit profile %2$s.','wpqa'),'<a href="'.esc_url(wpqa_get_profile_permalink($user_id,"edit")).'"">','</a>').'</p></div>','wpqa_session');
					wp_safe_redirect(esc_url(home_url('/')));
					die();
				endif;
			endif;
		}
	}
endif;?>