<?php

/* @author    2codeThemes
*  @package   WPQA/shortcodes
*  @version   1.0
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/* Signup attr */
if (!function_exists('wpqa_signup_attr')) :
	function wpqa_signup_attr($atts, $content = null) {
		global $posted;
		$a = shortcode_atts( array(
		    'dark_button' => '',
		), $atts );
		$out = '';
		if (is_user_logged_in()) {
			$out .= wpqa_login_already();
		}else {
			$protocol = is_ssl() ? 'https' : 'http';
			$rand_r = rand(1,1000);
			$register_items = wpqa_options("register_items");
			$filter_social_login = apply_filters("wpqa_filter_social_login",false);
			if ($filter_social_login != "" || shortcode_exists('wpqa_social_login') || shortcode_exists('rdp-linkedin-login') || shortcode_exists('oa_social_login') || shortcode_exists('xs_social_login') || shortcode_exists('wordpress_social_login') || shortcode_exists('apsl-login') || shortcode_exists('apsl-login-lite') || shortcode_exists('nextend_social_login')) {
				$out .= '<div class="wpqa_login_social">';
					$out .= ($filter_social_login != ""?$filter_social_login:"").
					(shortcode_exists('wpqa_social_login')?do_shortcode("[wpqa_social_login]"):"").
					(shortcode_exists('rdp-linkedin-login')?do_shortcode("[rdp-linkedin-login]"):"").
					(shortcode_exists('oa_social_login')?do_shortcode("[oa_social_login]"):"").
					(shortcode_exists('xs_social_login')?do_shortcode("[xs_social_login]"):"").
					(shortcode_exists('wordpress_social_login')?do_shortcode("[wordpress_social_login]"):"").
					(shortcode_exists('apsl-login')?do_shortcode("[apsl-login]"):"").
					(shortcode_exists('apsl-login-lite')?do_shortcode("[apsl-login-lite]"):"").
					(shortcode_exists('nextend_social_login')?do_shortcode("[nextend_social_login]"):"");
					$out .= '<div class="wpqa_login_social_div"><span>'.esc_html__("or use","wpqa").'</span></div>
				</div>';
			}
			$out .= '<form method="post" class="signup_form wpqa_form'.((isset($register_items["image_profile"]) && isset($register_items["image_profile"]["value"]) && $register_items["image_profile"]["value"] == "image_profile") || (isset($register_items["cover"]) && isset($register_items["cover"]["value"]) && $register_items["cover"]["value"] == "cover")?' wpqa-no-ajax':'').apply_filters("wpqa_filter_form_class",false).'" enctype="multipart/form-data">'.apply_filters('wpqa_register_form',false).'
				<div class="wpqa_error_desktop wpqa_hide"><div class="wpqa_error"></div></div>
				<div class="wpqa_success"></div>
				<div class="form-inputs clearfix">'.
					apply_filters('wpqa_register_before_username',false,$posted);
					if (empty($register_items)) {
						$register_items["username"] = array("sort" => esc_html__("Username","wpqa"),"value" => "username");
						$register_items["email"] = array("sort" => esc_html__("E-Mail","wpqa"),"value" => "email");
						$register_items["password"] = array("sort" => esc_html__("Password","wpqa"),"value" => "password");
					}
					if (isset($register_items) && is_array($register_items) && !empty($register_items)) {
						foreach ($register_items as $sort_key => $sort_value) {
							$out = apply_filters("wpqa_register_sort",$out,"register_items",$register_items,$sort_key,$sort_value,"register",$posted);
							if ($sort_key == "username" && isset($sort_value["value"]) && $sort_value["value"] == "username") {
								$out .= '<p class="'.$sort_key.'_field">
									<label for="user_name_'.$rand_r.'">'.esc_html__("Username","wpqa").'<span class="required">*</span></label>
									<input type="text" class="required-item" name="user_name" id="user_name_'.$rand_r.'" value="'.(isset($posted["user_name"])?$posted["user_name"]:"").'">
									<i class="icon-user"></i>
								</p>'.apply_filters('wpqa_register_after_username',false,$posted);
							}else if ($sort_key == "email" && isset($sort_value["value"]) && $sort_value["value"] == "email") {
								$out .= '<p class="'.$sort_key.'_field">
									<label for="email_'.$rand_r.'">'.esc_html__("E-Mail","wpqa").'<span class="required">*</span></label>
									<input type="email" class="required-item" name="email" id="email_'.$rand_r.'" value="'.(isset($posted["email"])?$posted["email"]:"").'">
									<i class="icon-mail"></i>
								</p>';
							}else if ($sort_key == "password" && isset($sort_value["value"]) && $sort_value["value"] == "password") {
								$out .= '<p class="'.$sort_key.'_field">
									<label for="pass1_'.$rand_r.'">'.esc_html__("Password","wpqa").'<span class="required">*</span></label>
									<input type="password" class="required-item" name="pass1" id="pass1_'.$rand_r.'" autocomplete="off">
									<i class="icon-lock-open"></i>
								</p>
								<p class="'.$sort_key.'_2_field">
									<label for="pass2_'.$rand_r.'">'.esc_html__("Confirm Password","wpqa").'<span class="required">*</span></label>
									<input type="password" class="required-item" name="pass2" id="pass2_'.$rand_r.'" autocomplete="off">
									<i class="icon-lock"></i>
								</p>'.apply_filters('wpqa_register_after_password',false,$posted);
							}
							$out .= wpqa_register_edit_fields($sort_key,$sort_value,"register",$rand_r);
						}
					}
					
					$out .= wpqa_add_captcha(wpqa_options("the_captcha_register"),"register",$rand_r);
					
					$terms_active_register = wpqa_options("terms_active_register");
					if ($terms_active_register == "on") {
						$terms_checked_register = wpqa_options("terms_checked_register");
						if ((isset($posted['agree_terms']) && $posted['agree_terms'] == "on") || ($terms_checked_register == "on" && empty($posted))) {
							$active_terms = true;
						}
						$terms_link_register = wpqa_options("terms_link_register");
						$terms_page_register = wpqa_options('terms_page_register');
						$terms_active_target_register = wpqa_options('terms_active_target_register');
						$privacy_policy_register = wpqa_options('privacy_policy_register');
						$privacy_active_target_register = wpqa_options('privacy_active_target_register');
						$privacy_page_register = wpqa_options('privacy_page_register');
						$privacy_link_register = wpqa_options('privacy_link_register');
						$out .= '<p class="wpqa_checkbox_p">
							<label for="agree_terms-'.$rand_r.'">
								<span class="wpqa_checkbox"><input type="checkbox" id="agree_terms-'.$rand_r.'" name="agree_terms" value="on" '.(isset($active_terms) == "on"?"checked='checked'":"").'></span>
								<span class="wpqa_checkbox_span">'.sprintf(esc_html__('By registering, you agreed to the %1$s Terms of Service %2$s %3$s.','wpqa'),'<a target="'.($terms_active_target_register == "same_page"?"_self":"_blank").'" href="'.esc_url(isset($terms_link_register) && $terms_link_register != ""?$terms_link_register:(isset($terms_page_register) && $terms_page_register != ""?get_page_link($terms_page_register):"#")).'">','</a>',($privacy_policy_register == "on"?" ".sprintf(esc_html__('and %1$s Privacy Policy %2$s','wpqa'),'<a target="'.($privacy_active_target_register == "same_page"?"_self":"_blank").'" href="'.esc_url(isset($privacy_link_register) && $privacy_link_register != ""?$privacy_link_register:(isset($privacy_page_register) && $privacy_page_register != ""?get_page_link($privacy_page_register):"#")).'">','</a>'):"")).'<span class="required">*</span></span>
							</label>
						</p>';
					}
				$out .= '</div>

				<div class="clearfix"></div>
				<div class="wpqa_error_mobile wpqa_hide"><div class="wpqa_error"></div></div>

				<p class="form-submit">
					<span class="load_span"><span class="loader_2"></span></span>
					<input type="hidden" name="form_type" value="wpqa-signup">
					<input type="hidden" name="action" value="wpqa_ajax_signup_process">
					<input type="hidden" name="redirect_to" value="'.esc_url(wp_unslash($protocol.'://'.wpqa_server('HTTP_HOST').wpqa_server('REQUEST_URI'))).'">
					'.wp_referer_field().'
					<input type="hidden" name="wpqa_signup_nonce" value="'.wp_create_nonce("wpqa_signup_nonce").'">
					<input type="submit" name="register" value="'.esc_attr__("Signup","wpqa").'" class="button-default button-hide-click'.(isset($a["dark_button"]) && $a["dark_button"] == "dark_button"?" dark_button":"").'">
				</p>
			</form>';
		}
		return $out;
	}
endif;
/* Signup jQuery */
if (!function_exists('wpqa_signup_jquery')) :
	function wpqa_signup_jquery() {
		global $posted;
		$allow_spaces = wpqa_options("allow_spaces");
		$register_items = wpqa_options("register_items");
		$errors = new WP_Error();
		if ( isset( $_REQUEST['redirect_to'] ) ) $redirect_to = $_REQUEST['redirect_to']; else $redirect_to = esc_url(home_url('/'));
		// Process signup form
		$posted = array(
			'user_name'    => esc_html($_POST['user_name']),
			'email'        => esc_html($_POST['email']),
			'pass1'        => esc_html($_POST['pass1']),
			'pass2'        => esc_html($_POST['pass2']),
			'agree_terms'  => (isset($_POST['agree_terms']) && $_POST['agree_terms'] != ""?esc_html($_POST['agree_terms']):""),
			'wpqa_captcha' => (isset($_POST['wpqa_captcha']) && $_POST['wpqa_captcha'] != ""?esc_html($_POST['wpqa_captcha']):""),
			'nickname'     => (isset($_POST['nickname']) && $_POST['nickname'] != ""?esc_html($_POST['nickname']):""),
			'first_name'   => (isset($_POST['first_name']) && $_POST['first_name'] != ""?esc_html($_POST['first_name']):""),
			'last_name'    => (isset($_POST['last_name']) && $_POST['last_name'] != ""?esc_html($_POST['last_name']):""),
			'display_name' => (isset($_POST['display_name']) && $_POST['display_name'] != ""?esc_html($_POST['display_name']):""),
			'country'      => (isset($_POST['country']) && $_POST['country'] != ""?esc_html($_POST['country']):""),
			'city'         => (isset($_POST['city']) && $_POST['city'] != ""?esc_html($_POST['city']):""),
			'phone'        => (isset($_POST['phone']) && $_POST['phone'] != ""?esc_html($_POST['phone']):""),
			'gender'       => (isset($_POST['gender']) && $_POST['gender'] != ""?esc_html($_POST['gender']):""),
			'age'          => (isset($_POST['age']) && $_POST['age'] != ""?esc_html($_POST['age']):""),
			'redirect_to'  => $_POST['redirect_to'],
		);

		$posted = apply_filters('wpqa_register_posted',$posted);

		$posted = array_map('stripslashes', $posted);
		$posted['username'] = sanitize_user((isset($posted['username'])?$posted['username']:""));
		// Validation
		if ( empty($posted['user_name']) ) {
			$errors->add('required-username',esc_html__("Please enter your name.","wpqa"));
		}
		if ( $allow_spaces != "on" && $posted['user_name'] == trim($posted['user_name']) && strpos($posted['user_name'], ' ') !== false ) {
			$errors->add('error-username',esc_html__("Please enter your name without any spaces.","wpqa"));
		}
		if ( empty($posted['email']) ) {
			$errors->add('required-email',esc_html__("Please enter your email.","wpqa"));
		}
		if ( empty($posted['pass1']) ) {
			$errors->add('required-pass1',esc_html__("Please enter your password.","wpqa"));
		}
		if ( empty($posted['pass2']) ) {
			$errors->add('required-pass2',esc_html__("Please rewrite password.","wpqa"));
		}
		if ( $posted['pass1'] !== $posted['pass2'] ) {
			$errors->add('required-pass1',esc_html__("Password does not match.","wpqa"));
		}

		do_action('wpqa_register_errors_main',$errors,$posted,$register_items,"register");
		
		wpqa_check_captcha(wpqa_options("the_captcha_register"),"register",$posted,$errors);
		
		$terms_active_register = wpqa_options("terms_active_register");
		if ($terms_active_register == "on" && $posted['agree_terms'] != "on") {
			$errors->add('required-terms', esc_html__("There are required fields (Agree of the terms).","wpqa"));
		}
		// Check the username
		if ( username_exists( $posted['user_name'] ) ) :
			$errors->add('registered-username',esc_html__("This username is already registered.","wpqa"));
		endif;
		// Check the e-mail address
		if ( !is_email( $posted['email'] ) ) :
			$errors->add('right-email',esc_html__("Please write correctly email.","wpqa"));
		elseif ( email_exists( $posted['email'] ) ) :
			$errors->add('registered-email',esc_html__("This email is already registered.","wpqa"));
		endif;

		$black_list_emails = wpqa_options("black_list_emails");
		if (is_array($black_list_emails) && !empty($black_list_emails)) {
			foreach ($black_list_emails as $value) {
				if (strpos($posted['email'],$value["email"]) !== false) {
					$errors->add('wrong-email',esc_html__("Sorry, This email or domain are not allowing to register, Please try another email.","wpqa"));
				}
			}
		}
		
		do_action('wpqa_register_errors',$errors,$posted,$register_items,"register");
		
		// Result
		$result = array();
		if ( !$errors->get_error_code() ) :
			do_action('register_post', $posted['user_name'], $posted['email'], $errors);
			$errors = apply_filters( 'registration_errors', $errors, $posted['user_name'], $posted['email'] );
			// if there are no errors, let's create the user account
			if ( !$errors->get_error_code() ) :
				$user_id = wp_create_user($posted['user_name'],$posted['pass1'],$posted['email']);
				if (is_wp_error($user_id)) {
					$errors->add('error', sprintf('<strong>'.esc_html__('Error:','wpqa').'</strong> '.esc_html__('Sorry, You can not register, Please contact the webmaster','wpqa').': ',get_option('admin_email')));
					$result['success'] = 0;
					foreach ($errors->errors as $error) {
						$result['error'] = $error[0];
						break;
					}
				}else {
					$user_review = wpqa_options("user_review");
					$confirm_email = wpqa_options("confirm_email");
					if (($user_review == "on" && $confirm_email != "on") || ($user_review == "on" && $confirm_email == "on")) {
						$ask_under_review = get_role("wpqa_under_review");
						if (!isset($ask_under_review)) {
							add_role("wpqa_under_review",esc_html__("Under review","wpqa"),array('read' => false));
						}
					}
					if ($confirm_email == "on") {
						$activation = get_role("activation");
						if (!isset($activation)) {
							add_role("activation",esc_html__("Activation","wpqa"),array('read' => false));
						}
						$rand_a = wpqa_token(15);
						update_user_meta($user_id,"activation",$rand_a);
						$confirm_link = esc_url_raw(add_query_arg(array("u" => $user_id,"activate" => $rand_a),esc_url(home_url('/'))));
						$send_text = wpqa_send_email(wpqa_options("email_confirm_link_2"),$user_id,"","","",$confirm_link);
						$last_message_email = wpqa_email_code($send_text);
						$email_title = wpqa_options("title_confirm_link_2");
						$email_title = ($email_title != ""?$email_title:esc_html__("Confirm account","wpqa"));
						$email_template = wpqa_options("email_template");
						$mail_smtp = wpqa_options("mail_smtp");
						$email_template = ($mail_smtp == "on"?wpqa_options("mail_username"):$email_template);
						wpqa_sendEmail($email_template,get_bloginfo('name'),esc_html($posted['email']),esc_html($posted['user_name']),$email_title,$last_message_email);
						wpqa_session('<div class="alert-message success"><i class="icon-check"></i><p>'.esc_html__("Check your email please to activate your membership.","wpqa").'</p></div>','wpqa_session');
					}else {
						$default_group = wpqa_options("default_group");
						$default_group = (isset($default_group) && $default_group != ""?$default_group:"subscriber");
						$default_group = ($user_review == "on" && $confirm_email != "on"?"wpqa_under_review":$default_group);
					}
					if ($user_review == "on" && $confirm_email != "on") {
						$send_email_users_review = wpqa_options("send_email_users_review");
						if ($send_email_users_review == "on") {
							$send_text = wpqa_send_email(wpqa_options("email_review_user"),$user_id);
							$last_message_email = wpqa_email_code($send_text);
							$email_title = wpqa_options("title_review_user");
							$email_title = ($email_title != ""?$email_title:esc_html__("New user for review","wpqa"));
							$email_template = wpqa_options("email_template");
							$mail_smtp = wpqa_options("mail_smtp");
							$email_template = ($mail_smtp == "on"?wpqa_options("mail_username"):$email_template);
							wpqa_sendEmail($email_template,get_bloginfo('name'),wpqa_options("email_template_to"),get_bloginfo('name'),$email_title,$last_message_email);
						}
					}
					$nickname = ($posted['nickname'] != ""?$posted['nickname']:$posted['user_name']);
					$display_name = ($posted['display_name'] != ""?$posted['display_name']:$posted['user_name']);
					wp_update_user(array('ID' => $user_id,'role' => ($confirm_email == "on"?'activation':$default_group),'user_nicename' => $nickname,'nickname' => $nickname,'display_name' => $display_name));

					do_action('wpqa_after_register',$user_id,$posted,isset($_FILES)?$_FILES:array(),"register");
					
					$secure_cookie = is_ssl() ? true : false;
					wp_set_auth_cookie($user_id, true, $secure_cookie);
					
					$after_register = wpqa_options("after_register");
					$after_register_link = wpqa_options("after_register_link");
					
					if (isset($posted['redirect_to']) && $after_register == "same_page") {
						$redirect_to = $posted['redirect_to'];
					}else if (isset($user_id) && $user_id > 0 && $after_register == "profile") {
						$redirect_to = wpqa_profile_url($user_id);
					}else if ($after_register == "custom_link" && $after_register_link != "") {
						$redirect_to = esc_url($after_register_link);
					}else {
						$redirect_to = esc_url(home_url('/'));
					}
					if (wpqa_is_ajax()) {
						$result['success'] = 1;
						$result['redirect'] = $redirect_to;
					}else {
						wp_safe_redirect($redirect_to);
						die();
					}
				}
			else :
				if (wpqa_is_ajax()) {
					$result['success'] = 0;
					foreach ($errors->errors as $error) {
						$result['error'] = $error[0];
						break;
					}
				}
			endif;
		else :
			if (wpqa_is_ajax()) {
				$result['success'] = 0;
				foreach ($errors->errors as $error) {
					$result['error'] = $error[0];
					break;
				}
			}
		endif;
		if (wpqa_is_ajax()) {
			echo json_encode($result);
			die();
		}
		return $errors;
	}
endif;
/* Signup process */
if (!function_exists('wpqa_signup_process')) :
	function wpqa_signup_process() {
		if (isset($_POST['form_type']) && $_POST['form_type'] == "wpqa-signup") :
			$return = wpqa_signup_jquery();
			if (is_wp_error($return)) :
	   			return '<div class="wpqa_error wpqa_error_register">'.$return->get_error_message().'</div>';
	   		endif;
		endif;
	}
endif;
add_filter('wpqa_register_form','wpqa_signup_process');
/* Registration save */
add_action('user_register','wpqa_registration_save',10,1);
add_action('wpqa_user_register','wpqa_registration_save',10,1);
if (!function_exists('wpqa_registration_save')) :
	function wpqa_registration_save($user_id) {
		$register_default_options = wpqa_options("register_default_options");
		$default_options = array("follow_email","show_point_favorite","received_email","received_message","unsubscribe_mails","new_payment_mail","send_message_mail","answer_on_your_question","answer_question_follow","question_schedules");
		foreach ($default_options as $key) {
			if (is_array($register_default_options) && in_array($key,$register_default_options)) {
				update_user_meta($user_id,$key,"on");
			}
		}
		$send_welcome_mail = wpqa_options("send_welcome_mail");
		if ($send_welcome_mail == "on") {
			$welcome_mail = get_user_meta($user_id,"welcome_mail",true);
			if ($welcome_mail == "") {
				$send_text = wpqa_send_email(wpqa_options("email_welcome_mail"),$user_id);
				$last_message_email = wpqa_email_code($send_text);
				$email_title = wpqa_options("title_welcome_mail");
				$email_title = ($email_title != ""?$email_title:esc_html__("Welcome","wpqa"));
				$user_email = get_the_author_meta("user_email",$user_id);
				$display_name = get_the_author_meta("display_name",$user_id);
				$email_template = wpqa_options("email_template");
				$mail_smtp = wpqa_options("mail_smtp");
				$email_template = ($mail_smtp == "on"?wpqa_options("mail_username"):$email_template);
				wpqa_sendEmail($email_template,get_bloginfo('name'),esc_html($user_email),esc_html($display_name),$email_title,$last_message_email);
				update_user_meta($user_id,"welcome_mail","done");
			}
		}
		$if_user_id = get_user_by("id",$user_id);
		if ((isset($if_user_id->caps["activation"]) && $if_user_id->caps["activation"] == 1) || (isset($if_user_id->caps["wpqa_under_review"]) && $if_user_id->caps["wpqa_under_review"] == 1)) {
			// Not activated or under review
		}else {
			$point_new_user = (int)wpqa_options("point_new_user");
			$active_points = wpqa_options("active_points");
			if ($user_id > 0 && $active_points == "on" && $point_new_user > 0) {
				$gift_site = get_user_meta($user_id,"gift_site",true);
				if ($gift_site == "") {
					wpqa_add_points($user_id,$point_new_user,"+","gift_site");
					wpqa_notifications_activities($user_id,"","","","","gift_site","notifications",$point_new_user." "._n("Point","Points",$point_new_user,"wpqa"));
					update_user_meta($user_id,"gift_site","done");
				}
			}

			$active_referral = wpqa_options("active_referral");
			$points_referral = (int)wpqa_options("points_referral");
			if ($user_id > 0 && $points_referral > 0 && $active_points == "on" && $active_referral == "on" && isset($_COOKIE[wpqa_options("uniqid_cookie").'wpqa_referral'])) {
				$invite = esc_html($_COOKIE[wpqa_options("uniqid_cookie").'wpqa_referral']);
				$user = get_users(array("number" => 1,"count_total" => false,"meta_query" => array("relation" => "OR",array("key" => "wpqa_referral","value" => $invite),array("key" => $invite))));
				$user_id_invite = (int)(isset($user[0]) && isset($user[0]->ID)?$user[0]->ID:0);
				if ($user_id_invite > 0) {
					wpqa_add_points($user_id_invite,$points_referral,"+","points_referral");
					wpqa_add_points($user_id_invite,$points_referral,"+","points_referral",0,0,0,"points_referral");
					wpqa_notifications_activities($user_id_invite,$user_id,"","","","points_referral","notifications",$points_referral." "._n("Point","Points",$points_referral,"wpqa"));
					$invite_meta = get_user_meta($user_id_invite,$invite,true);
					if (is_array($invite_meta) && !empty($invite_meta) && ((isset($invite_meta["status"]) && $invite_meta["status"] == "pending") || (!isset($invite_meta["status"])))) {
						$invite_meta["status"] = "completed";
						$invite_meta["points"] = $points_referral;
						update_user_meta($user_id_invite,$invite,$invite_meta);
						wpqa_add_points($user_id_invite,1,"+","",0,0,0,"invitations_completed",false);
						wpqa_add_points($user_id_invite,1,"-","",0,0,0,"invitations_pending",false);
						update_user_meta($user_id,"wpqa_invitations",$user_id_invite);
					}else {
						$rand = wpqa_token(15);
						$user_email = get_the_author_meta("user_email",$user_id);
						update_user_meta($user_id_invite,$rand,array("email" => $user_email,"status" => "completed","points" => $points_referral,"resend" => 0));
						$points_referrals_meta = get_user_meta($user_id_invite,"points_referrals",true);
						if (empty($points_referrals_meta)) {
							$update = update_user_meta($user_id_invite,"points_referrals",array($rand));
						}else if (is_array($points_referrals_meta) && !in_array($rand,$points_referrals_meta)) {
							$update = update_user_meta($user_id_invite,"points_referrals",array_merge($points_referrals_meta,array($rand)));
						}
						wpqa_add_points($user_id_invite,1,"+","",0,0,0,"invitations_completed",false);
						update_user_meta($user_id,"wpqa_invitations",$user_id_invite);
					}
					unset($_COOKIE[wpqa_options("uniqid_cookie").'wpqa_referral']);
					setcookie(wpqa_options("uniqid_cookie").'wpqa_referral',null,-1,'/');
				}
			}
		}
	}
endif;
/* Sanitize user */
add_filter('sanitize_user','wpqa_sanitize_user',10,3);
if (!function_exists('wpqa_sanitize_user')) :
	function wpqa_sanitize_user($username,$raw_username,$strict) {
		if (!$strict) {
			return $username;
		}
		return sanitize_user(stripslashes($raw_username),false);
	}
endif;
/* Register URL */
add_filter('register_url','wpqa_register_url',10,1);
function wpqa_register_url() {
	return wpqa_signup_permalink();
}
/* Stop sent WordPress mail */
add_action('wpqa_init','wpqa_send_new_user_notifications');
function wpqa_send_new_user_notifications() {
	remove_action('register_new_user','wp_send_new_user_notifications');
}
/* Register and edit profile fields */
function wpqa_register_edit_fields($key_items,$value_items,$type,$rand,$user = object) {
	$type_name = ($type == "register"?"_register":"");
	$out = '';
	$key_id = $key_items;
	if ($key_items == "image_profile") {
		$user_meta_avatar = wpqa_avatar_name();
		$key_id = "profile_picture";
	}else if ($key_items == "cover") {
		$user_meta_cover = wpqa_cover_name();
		$key_id = "profile_cover";
	}
	$key_required = wpqa_options($key_id."_required".$type_name);
	if ($type == "edit") {
		if ($key_items == "image_profile") {
			$key_id = $user_meta_avatar;
		}else if ($key_items == "cover") {
			$key_id = $user_meta_cover;
		}
		$user_meta = get_user_meta($user->ID,$key_id,true);
	}
	$readonly = ($type == "edit"?" readonly='readonly'":"");
	if ($key_items == "nickname" && isset($value_items["value"]) && $value_items["value"] == "nickname") {
		$out .= '<p class="'.$key_items.'_field">
			<label for="nickname_'.$rand.'">'.esc_html__("Nickname","wpqa").'<span class="required">*</span></label>
			<input'.$readonly.' name="nickname" id="nickname_'.$rand.'" type="text" value="'.(isset($_POST["nickname"])?esc_attr($_POST["nickname"]):($type == "edit"?esc_attr($user->nickname):"")).'">
			<i class="icon-vcard"></i>
		</p>';
	}else if ($key_items == "first_name" && isset($value_items["value"]) && $value_items["value"] == "first_name") {
		$out .= '<p class="'.$key_items.'_field">
			<label for="first_name_'.$rand.'">'.esc_html__("First Name","wpqa").($key_required == "on"?'<span class="required">*</span>':'').'</label>
			<input'.($key_required == "on"?' class="required-item"':'').$readonly.' name="first_name" id="first_name_'.$rand.'" type="text" value="'.(isset($_POST["first_name"])?esc_attr($_POST["first_name"]):($type == "edit"?esc_attr($user->first_name):"")).'">
			<i class="icon-user"></i>
		</p>';
	}else if ($key_items == "last_name" && isset($value_items["value"]) && $value_items["value"] == "last_name") {
		$out .= '<p class="'.$key_items.'_field">
			<label for="last_name_'.$rand.'">'.esc_html__("Last Name","wpqa").($key_required == "on"?'<span class="required">*</span>':'').'</label>
			<input'.($key_required == "on"?' class="required-item"':'').$readonly.' name="last_name" id="last_name_'.$rand.'" type="text" value="'.(isset($_POST["last_name"])?esc_attr($_POST["last_name"]):($type == "edit"?esc_attr($user->last_name):"")).'">
			<i class="icon-users"></i>
		</p>';
	}else if ($key_items == "display_name" && isset($value_items["value"]) && $value_items["value"] == "display_name") {
		$out .= '<p class="'.$key_items.'_field">
			<label for="display_name_'.$rand.'">'.esc_html__("Display Name","wpqa").($key_required == "on"?'<span class="required">*</span>':'').'</label>
			<input'.($key_required == "on"?' class="required-item"':'').$readonly.' name="display_name" id="display_name_'.$rand.'" type="text" value="'.(isset($_POST["display_name"])?esc_attr($_POST["display_name"]):($type == "edit"?esc_attr($user->display_name):"")).'">
			<i class="icon-user"></i>
		</p>';
		$out .= apply_filters('wpqa_edit_profile_after_names',false,$user->ID);
	}else if ($key_items == "image_profile" && isset($value_items["value"]) && $value_items["value"] == "image_profile") {
		if ($user->ID > 0) {
			$out .= '<div class="clearfix"></div>
			<div class="author-image profile-image">
				<span class="author-image-span wpqa-delete-image-span">'.wpqa_get_user_avatar(array("user_id" => $user->ID,"size" => 100,"user_name" => $user->display_name)).'</span>';
				if (((!is_array($user_meta) && $user_meta != "") || (is_array($user_meta) && isset($user_meta["id"]) && $user_meta["id"] != 0))) {
					$out .= '<div class="clearfix"></div>
					<div class="button-default wpqa-remove-image" data-name="'.$user_meta_avatar.'" data-type="user_meta" data-id="'.$user->ID.'" data-nonce="'.wp_create_nonce("wpqa_remove_image").'">'.esc_html__("Delete","wpqa").'</div>
					<div class="loader_2 loader_4"></div>';
				}
			$out .= '</div>';
		}
		$out .= '<label for="your_avatar_'.$rand.'">'.esc_html__('Profile Picture','wpqa').($key_required == "on"?'<span class="required">*</span>':'').'</label>
		<div class="fileinputs">
			<input type="file" name="'.$user_meta_avatar.'" id="your_avatar_'.$rand.'">
			<div class="fakefile">
				<button type="button">'.esc_html__('Select file','wpqa').'</button>
				<span>'.esc_html__('Browse','wpqa').'</span>
			</div>
			<i class="icon-camera"></i>
		</div>
		<div class="clearfix"></div>';
	}else if ($key_items == "cover" && isset($value_items["value"]) && $value_items["value"] == "cover") {
		if ($user->ID > 0 && ((!is_array($user_meta) && $user_meta != "") || (is_array($user_meta) && isset($user_meta["id"]) && $user_meta["id"] != 0))) {
			$out .= '<div class="clearfix"></div>
			<div class="author-image profile-image">
				<span class="author-image-span wpqa-delete-image-span">'.wpqa_get_user_cover(array("user_id" => $user->ID,"size" => 100,"user_name" => $user->display_name)).'</span>
					<div class="clearfix"></div>
					<div class="button-default wpqa-remove-image" data-name="'.$user_meta_cover.'" data-type="user_meta" data-id="'.$user->ID.'" data-nonce="'.wp_create_nonce("wpqa_remove_image").'">'.esc_html__("Delete","wpqa").'</div>
					<div class="loader_2 loader_4"></div>
			</div>';
		}
		$out .= '<label for="your_cover_'.$rand.'">'.esc_html__('Cover Picture','wpqa').($key_required == "on"?'<span class="required">*</span>':'').'</label>
		<div class="fileinputs">
			<input type="file" name="'.$user_meta_cover.'" id="your_cover_'.$rand.'">
			<div class="fakefile">
				<button type="button">'.esc_html__('Select file','wpqa').'</button>
				<span>'.esc_html__('Browse','wpqa').'</span>
			</div>
			<i class="icon-camera"></i>
		</div>
		<div class="clearfix"></div>';
	}else if ($key_items == "country" && isset($value_items["value"]) && $value_items["value"] == "country") {
		$get_countries = apply_filters('wpqa_get_countries',false);
		$out .= '<p class="'.$key_items.'_field">
			<label for="country_'.$rand.'">'.esc_html__("Country","wpqa").($key_required == "on"?'<span class="required">*</span>':'').'</label>
			<span class="styled-select">
				<select name="country" id="country_'.$rand.'" '.($key_required == "on"?'class="required-item"':'').'>
					<option value="">'.esc_html__( 'Select a country&hellip;', 'wpqa' ).'</option>';
						foreach( $get_countries as $key => $value ) {
							$out .= '<option value="' . esc_attr( $key ) . '"' . selected( (isset($_POST["country"])?esc_attr($_POST["country"]):($type == "edit"?esc_attr($user_meta):"")), esc_attr( $key ), false ) . '>' . esc_attr( $value ) . '</option>';
						}
				$out .= '</select>
			</span>
			<i class="icon-location"></i>
		</p>';
	}else if ($key_items == "city" && isset($value_items["value"]) && $value_items["value"] == "city") {
		$out .= '<p class="'.$key_items.'_field">
			<label for="city_'.$rand.'">'.esc_html__("City","wpqa").($key_required == "on"?'<span class="required">*</span>':'').'</label>
			<input'.($key_required == "on"?' class="required-item"':'').$readonly.' type="text" name="city" id="city_'.$rand.'" value="'.(isset($_POST["city"])?esc_attr($_POST["city"]):($type == "edit"?esc_attr($user_meta):"")).'">
			<i class="icon-address"></i>
		</p>';
	}else if ($key_items == "phone" && isset($value_items["value"]) && $value_items["value"] == "phone") {
		$out .= '<p class="'.$key_items.'_field">
			<label for="phone_'.$rand.'">'.esc_html__("Phone","wpqa").($key_required == "on"?'<span class="required">*</span>':'').'</label>
			<input'.($key_required == "on"?' class="required-item"':'').$readonly.' type="text" name="phone" id="phone_'.$rand.'" value="'.(isset($_POST["phone"])?esc_attr($_POST["phone"]):($type == "edit"?esc_attr($user_meta):"")).'">
			<i class="icon-phone"></i>
		</p>';
	}else if ($key_items == "gender" && isset($value_items["value"]) && $value_items["value"] == "gender") {
		$last_gender = (isset($_POST["gender"]) && $_POST["gender"]?esc_attr($_POST["gender"]):($type == "edit"?esc_attr($user_meta):""));
		$gender_other = wpqa_options("gender_other");
		$out .= '<p class="'.$key_items.'_field wpqa_radio_p"><label>'.esc_html__("Gender","wpqa").($key_required == "on"?'<span class="required">*</span>':'').'</label></p>
		<div class="wpqa_radio_div">
			<p>
				<span class="wpqa_radio"><input id="gender_male_'.$rand.'" name="gender" type="radio" value="1"'.($last_gender == "male" || $last_gender == "1"?' checked="checked"':'').'></span>
				<label for="gender_male_'.$rand.'">'.esc_html__("Male","wpqa").'</label>
			</p>
			<p>
				<span class="wpqa_radio"><input id="gender_female_'.$rand.'" name="gender" type="radio" value="2"'.($last_gender == "female" || $last_gender == "2"?' checked="checked"':'').'></span>
				<label for="gender_female_'.$rand.'">'.esc_html__("Female","wpqa").'</label>
			</p>';
			if ($gender_other == "on") {
				$out .= '<p>
					<span class="wpqa_radio"><input id="gender_other_'.$rand.'" name="gender" type="radio" value="3"'.($last_gender == "other" || $last_gender == "3"?' checked="checked"':'').'></span>
					<label for="gender_other_'.$rand.'">'.esc_html__("Other","wpqa").'</label>
				</p>';
			}
			$out .= '<div class="clearfix"></div>
		</div>';
	}else if ($key_items == "age" && isset($value_items["value"]) && $value_items["value"] == "age") {
		$out .= '<p class="'.$key_items.'_field">
			<label for="age_'.$rand.'">'.esc_html__("Age","wpqa").($key_required == "on"?'<span class="required">*</span>':'').'</label>
			<input'.$readonly.' type="text" class="age-datepicker'.($key_required == "on"?' required-item':'').'" name="age" id="age_'.$rand.'" value="'.(isset($_POST["age"])?esc_attr($_POST["age"]):($type == "edit"?esc_attr($user_meta):"")).'">
			<i class="icon-globe"></i>
		</p>';
	}
	return $out;
}
/* Register and edit profile errors */
add_action("wpqa_register_errors_main","wpqa_register_edit_profile_errors",1,5);
add_action("wpqa_edit_profile_errors_main","wpqa_register_edit_profile_errors",1,5);
function wpqa_register_edit_profile_errors($errors,$posted,$sort,$type,$user_id = 0) {
	$nickname = (isset($sort["nickname"]["value"]) && $sort["nickname"]["value"] == "nickname"?"on":0);
	$first_name = (isset($sort["first_name"]["value"]) && $sort["first_name"]["value"] == "first_name"?"on":0);
	$last_name = (isset($sort["last_name"]["value"]) && $sort["last_name"]["value"] == "last_name"?"on":0);
	$display_name = (isset($sort["display_name"]["value"]) && $sort["display_name"]["value"] == "display_name"?"on":0);
	$profile_picture = (isset($sort["image_profile"]["value"]) && $sort["image_profile"]["value"] == "image_profile"?"on":0);
	$profile_cover = (isset($sort["cover"]["value"]) && $sort["cover"]["value"] == "cover"?"on":0);
	$country = (isset($sort["country"]["value"]) && $sort["country"]["value"] == "country"?"on":0);
	$city = (isset($sort["city"]["value"]) && $sort["city"]["value"] == "city"?"on":0);
	$phone = (isset($sort["phone"]["value"]) && $sort["phone"]["value"] == "phone"?"on":0);
	$gender = (isset($sort["gender"]["value"]) && $sort["gender"]["value"] == "gender"?"on":0);
	$age = (isset($sort["age"]["value"]) && $sort["age"]["value"] == "age"?"on":0);
	$type_name = ($type == "register"?"_register":"");

	$first_name_required = wpqa_options("first_name_required".$type_name);
	$last_name_required = wpqa_options("last_name_required".$type_name);
	$display_name_required = wpqa_options("display_name_required".$type_name);
	$profile_picture_required = wpqa_options("profile_picture_required".$type_name);
	$profile_cover_required = wpqa_options("profile_cover_required".$type_name);
	$country_required = wpqa_options("country_required".$type_name);
	$city_required = wpqa_options("city_required".$type_name);
	$phone_required = wpqa_options("phone_required".$type_name);
	$gender_required = wpqa_options("gender_required".$type_name);
	$age_required = wpqa_options("age_required".$type_name);

	$user_meta_avatar = wpqa_avatar_name();
	$user_meta_cover = wpqa_cover_name();
	$profile_picture_size = (int)wpqa_options("profile_picture_size");
	$profile_cover_size = (int)wpqa_options("profile_cover_size");

	if ($type == "edit") { 
		$get_your_avatar = get_user_meta($user_id,$user_meta_avatar,true);
		$get_your_cover = get_user_meta($user_id,$user_meta_cover,true);
	}
	if (empty($_POST['nickname']) && $nickname === "on") {
		$errors->add('required-field','<strong>'.esc_html__("Error","wpqa").' :&nbsp;</strong> '.esc_html__("There are required fields (Nickname).","wpqa"));
	}
	if (empty($_POST['first_name']) && $first_name === "on" && $first_name_required == "on") {
		$errors->add('required-field','<strong>'.esc_html__("Error","wpqa").' :&nbsp;</strong> '.esc_html__("There are required fields (First name).","wpqa"));
	}
	if (empty($_POST['last_name']) && $last_name === "on" && $last_name_required == "on") {
		$errors->add('required-field','<strong>'.esc_html__("Error","wpqa").' :&nbsp;</strong> '.esc_html__("There are required fields (Last name).","wpqa"));
	}
	if (empty($_POST['display_name']) && $display_name === "on" && $display_name_required == "on") {
		$errors->add('required-field','<strong>'.esc_html__("Error","wpqa").' :&nbsp;</strong> '.esc_html__("There are required fields (Display name).","wpqa"));
	}

	if (empty($_FILES[$user_meta_avatar]['name']) && ((empty($get_your_avatar) && $type == "edit") || $type == "register") && $profile_picture === "on" && $profile_picture_required == "on") {
		$errors->add('required-field','<strong>'.esc_html__("Error","wpqa").' :&nbsp;</strong> '.esc_html__("There are required fields (Profile picture).","wpqa"));
	}
	if (empty($_FILES[$user_meta_cover]['name']) && ((empty($get_your_cover) && $type == "edit") || $type == "register") && $profile_cover === "on" && $profile_cover_required == "on") {
		$errors->add('required-field','<strong>'.esc_html__("Error","wpqa").' :&nbsp;</strong> '.esc_html__("There are required fields (Cover picture).","wpqa"));
	}
	if (isset($_FILES[$user_meta_avatar]) && !empty($_FILES[$user_meta_avatar]['name'])) :
		$mime = $_FILES[$user_meta_avatar]["type"];
		$file_tmp = $_FILES[$user_meta_avatar]['tmp_name'];
		$size = filesize($file_tmp);
		if ($mime != 'image/jpeg' && $mime != 'image/jpg' && $mime != 'image/png') {
			$errors->add('upload-error', esc_html__('Error type, Please upload: jpg, jpeg or png','wpqa'));
		}else if ($profile_picture_size > 0 && $size > ($profile_picture_size*1000)) {
			$errors->add('upload-error', sprintf(esc_html__('Error size, The maximum size is %s MB.','wpqa'),floor($profile_picture_size/1000)));
		}
	endif;

	if (isset($_FILES[$user_meta_cover]) && !empty($_FILES[$user_meta_cover]['name'])) :
		$mime = $_FILES[$user_meta_cover]["type"];
		$file_tmp = $_FILES[$user_meta_cover]['tmp_name'];
		$size = filesize($file_tmp);
		if ($mime != 'image/jpeg' && $mime != 'image/jpg' && $mime != 'image/png') {
			$errors->add('upload-error', esc_html__('Error type, Please upload: jpg, jpeg or png','wpqa'));
		}else if ($profile_cover_size > 0 && $size > ($profile_cover_size*1000)) {
			$errors->add('upload-error', sprintf(esc_html__('Error size, The maximum size is %s MB.','wpqa'),floor($profile_cover_size/1000)));
		}
	endif;

	if (empty($_POST['country']) && $country === "on" && $country_required == "on") {
		$errors->add('required-field','<strong>'.esc_html__("Error","wpqa").' :&nbsp;</strong> '.esc_html__("There are required fields (Country).","wpqa"));
	}
	if (empty($_POST['city']) && $city === "on" && $city_required == "on") {
		$errors->add('required-field','<strong>'.esc_html__("Error","wpqa").' :&nbsp;</strong> '.esc_html__("There are required fields (City).","wpqa"));
	}
	if (empty($_POST['phone']) && $phone === "on" && $phone_required == "on") {
		$errors->add('required-field','<strong>'.esc_html__("Error","wpqa").' :&nbsp;</strong> '.esc_html__("There are required fields (Phone).","wpqa"));
	}
	if (empty($_POST['gender']) && $gender === "on" && $gender_required == "on") {
		$errors->add('required-field','<strong>'.esc_html__("Error","wpqa").' :&nbsp;</strong> '.esc_html__("There are required fields (Gender).","wpqa"));
	}
	if (empty($_POST['age']) && $age === "on" && $age_required == "on") {
		$errors->add('required-field','<strong>'.esc_html__("Error","wpqa").' :&nbsp;</strong> '.esc_html__("There are required fields (Age).","wpqa"));
	}
	return $errors;
}
/* Register and edit profile updated */
add_action("wpqa_personal_update_profile","wpqa_register_edit_profile_updated",1,4);
add_action("wpqa_after_register","wpqa_register_edit_profile_updated",1,4);
function wpqa_register_edit_profile_updated($user_id,$posted,$files,$type) {
	$user_meta_avatar = wpqa_avatar_name();
	$user_meta_cover = wpqa_cover_name();
	if (isset($files[$user_meta_avatar]) || isset($files[$user_meta_cover])) {
		require_once(ABSPATH.'wp-admin/includes/image.php');
		require_once(ABSPATH.'wp-admin/includes/file.php');
	}

	if (isset($files[$user_meta_avatar]) && !empty($files[$user_meta_avatar]['name'])) :
		$your_avatar = wp_handle_upload($files[$user_meta_avatar],array('test_form' => false),current_time('mysql'));
		if ($your_avatar && isset($your_avatar["url"])) :
			$filename = $your_avatar["file"];
			$filetype = wp_check_filetype( basename( $filename ), null );
			$wp_upload_dir = wp_upload_dir();
			
			$attachment = array(
				'guid'           => $wp_upload_dir['url'] . '/' . basename( $filename ), 
				'post_mime_type' => $filetype['type'],
				'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
				'post_content'   => '',
				'post_status'    => 'inherit'
			);
			$attach_id = wp_insert_attachment($attachment,$filename);
			$attach_data = wp_generate_attachment_metadata($attach_id,$filename);
			wp_update_attachment_metadata($attach_id,$attach_data);
			$meta_for_avatar = $attach_id;
		endif;
		if (isset($your_avatar['error']) && $your_avatar) :
			if (isset($errors->add)) {
				$errors->add('upload-error', esc_html__('Error in upload the image : ','wpqa') . $your_avatar['error']);
				if ($errors->get_error_code()) return $errors;
			}
			return $errors;
		endif;
	elseif ($type == "edit") :
		$get_your_avatar = get_user_meta($user_id,$user_meta_avatar,true);
		$meta_for_avatar = $get_your_avatar;
	endif;
	if (isset($meta_for_avatar)) {
		update_user_meta($user_id,$user_meta_avatar,$meta_for_avatar);
	}

	if (isset($files[$user_meta_cover]) && !empty($files[$user_meta_cover]['name'])) :
		$your_cover = wp_handle_upload($files[$user_meta_cover],array('test_form' => false),current_time('mysql'));
		if ($your_cover && isset($your_cover["url"])) :
			$filename = $your_cover["file"];
			$filetype = wp_check_filetype( basename( $filename ), null );
			$wp_upload_dir = wp_upload_dir();
			
			$attachment = array(
				'guid'           => $wp_upload_dir['url'] . '/' . basename( $filename ), 
				'post_mime_type' => $filetype['type'],
				'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
				'post_content'   => '',
				'post_status'    => 'inherit'
			);
			$attach_id = wp_insert_attachment($attachment,$filename);
			$attach_data = wp_generate_attachment_metadata($attach_id,$filename);
			wp_update_attachment_metadata($attach_id,$attach_data);
			$meta_for_cover = $attach_id;
		endif;
		if (isset($your_cover['error']) && $your_cover) :
			if (isset($errors->add)) {
				$errors->add('upload-error', esc_html__('Error in upload the image : ','wpqa') . $your_cover['error']);
				if ($errors->get_error_code()) return $errors;
			}
			return $errors;
		endif;
	elseif ($type == "edit") :
		$get_your_cover = get_user_meta($user_id,$user_meta_cover,true);
		$meta_for_cover = $get_your_cover;
	endif;
	if (isset($meta_for_cover)) {
		update_user_meta($user_id,$user_meta_cover,$meta_for_cover);
	}

	if ($type == "register") {
		$array_posts = array("first_name","last_name","country","city","phone","gender","age");
		foreach ($array_posts as $key => $value) {
			if (isset($posted[$value]) && $posted[$value] != "") {
				update_user_meta($user_id,$value,sanitize_text_field($posted[$value]));
			}
		}
	}
}?>