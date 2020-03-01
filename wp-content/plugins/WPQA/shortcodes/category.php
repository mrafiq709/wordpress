<?php

/* @author    2codeThemes
*  @package   WPQA/shortcodes
*  @version   1.0
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

ob_start();
/* Add category attr */
function wpqa_add_category_attr($atts, $content = null) {
	$allow_user_to_add_category = wpqa_options("allow_user_to_add_category");
	if ($allow_user_to_add_category == "on") {
		$add_category = wpqa_options("add_category");
		$custom_permission = wpqa_options("custom_permission");
		$add_category_no_register = wpqa_options("add_category_no_register");
		if (is_user_logged_in()) {
			$user_get_current_user_id = get_current_user_id();
			$user_is_login = get_userdata($user_get_current_user_id);
			$user_login_group = (is_array($user_is_login->caps)?key($user_is_login->caps):"");
			$roles = $user_is_login->allcaps;
		}
		
		if (($custom_permission != "on" && ((isset($user_login_group) && $user_login_group == "wpqa_under_review") || (isset($user_login_group) && $user_login_group == "activation"))) || ($custom_permission == "on" && (is_user_logged_in() && !is_super_admin($user_get_current_user_id) && empty($roles["add_category"])) || (!is_user_logged_in() && $add_category != "on"))) {
			$out = '<div class="alert-message error"><i class="icon-cancel"></i><p>'.esc_html__("Sorry, you do not have a permission to add a category.","wpqa").'</p></div>';
		}else if (!is_user_logged_in() && $add_category_no_register != "on") {
			$out = '<div class="alert-message error"><i class="icon-cancel"></i></i><p>'.esc_html__("You must login to add category.","wpqa").'</p></div>'.do_shortcode("[wpqa_login]");
		}else {
			$out = wpqa_add_category_from();
		}
	}else {
		$out = '<div class="alert-message warning"><i class="icon-flag"></i><p>'.esc_html__("Sorry, this page is not available.","wpqa").'</p></div>';
	}
	return $out;
}
/* wpqa_add_category */
function wpqa_add_category() {
	if (isset($_POST["form_type"]) && $_POST["form_type"] == "add_category") :
		$return = wpqa_process_add_category();
		if (is_wp_error($return)) :
   			return '<div class="wpqa_error">'.$return->get_error_message().'</div>';
   		else :
			$user_id = get_current_user_id();
			$send_email_add_category = wpqa_options("send_email_add_category");
			if ($send_email_add_category == "on") {
				$send_text = wpqa_send_email(wpqa_options("email_add_category"),"","","","","","","","","","","","","",$return);
				$last_message_email = wpqa_email_code($send_text);
				$email_title = wpqa_options("title_add_category");
				$email_title = ($email_title != ""?$email_title:esc_html__("New category for review","wpqa"));
				$email_template = wpqa_options("email_template");
				$mail_smtp = wpqa_options("mail_smtp");
				$email_template = ($mail_smtp == "on"?wpqa_options("mail_username"):$email_template);
				wpqa_sendEmail($email_template,get_bloginfo('name'),wpqa_options("email_template_to"),get_bloginfo('name'),$email_title,$last_message_email);
			}
			wpqa_session('<div class="alert-message success"><i class="icon-check"></i><p>'.esc_html__("Your category was sent to review.","wpqa").'</p></div>','wpqa_session');
			if ($user_id > 0) {
				wpqa_notifications_activities($user_id,"","","","","approved_category","activities");
			}
			wp_redirect(esc_url(wpqa_add_category_permalink()));
			exit;
		endif;
	endif;
}
add_filter('wpqa_add_category','wpqa_add_category');
/* Process add category */
function wpqa_process_add_category() {
	$form_type = (isset($_POST["form_type"]) && $_POST["form_type"] != ""?$_POST["form_type"]:"");
	if ($form_type == "add_category") {
		global $category_add;
		set_time_limit(0);
		$errors = new WP_Error();
		$category_add = array();
		$fields = array('category','wpqa_captcha');

		$fields = apply_filters('wpqa_add_category_fields',$fields);
		
		foreach ($fields as $field) :
			if (isset($_POST[$field])) $category_add[$field] = $_POST[$field]; else $category_add[$field] = '';
		endforeach;

		if (!isset($_POST['wpqa_add_category_nonce']) || !wp_verify_nonce($_POST['wpqa_add_category_nonce'],'wpqa_add_category_nonce')) {
			$errors->add('required-field','<strong>'.esc_html__("Error","wpqa").' :&nbsp;</strong> '.esc_html__("There is an error, Please try again.","wpqa"));
		}
		
		/* Validate Required Fields */

		if (empty($category_add['category'])) $errors->add('required-field','<strong>'.esc_html__("Error","wpqa").' :&nbsp;</strong> '.esc_html__("There are required fields (category).","wpqa"));
		
		wpqa_check_captcha(wpqa_options("the_captcha_category"),"category",$category_add,$errors);

		$term = term_exists(wpqa_kses_stip($category_add['category']),'question-category');
		if (0 !== $term && null !== $term) {
			$errors->add('required-category-exists','<strong>'.esc_html__("Error","wpqa").' :&nbsp;</strong> '.esc_html__('The category exists!','wpqa'));
		}

		do_action('wpqa_add_category_errors',$errors,$category_add);
		
		if (sizeof($errors->errors)>0) return $errors;
		
		/* Create category */
		wpqa_add_cateory_request($category_add['category']);
		do_action('wpqa_finished_add_category',$category_add);
		
		/* Successful */
		return wpqa_kses_stip($category_add['category']);
	}
}
/* Add category request */
function wpqa_add_cateory_request($category,$post_id = '') {
	$user_id = get_current_user_id();
	$_reuqest_category = (int)get_user_meta($user_id,$user_id."_reuqest_category",true);
	$_reuqest_category++;
	update_user_meta($user_id,$user_id."_reuqest_category",$_reuqest_category);
	add_user_meta($user_id,$user_id."_reuqest_category_".$_reuqest_category,array("the_date" => current_time('timestamp'),"category_status" => 1,"user_id" => $user_id,"category" => wpqa_kses_stip($category),"post_id" => $post_id,"item_id_option" => $_reuqest_category));

	$wpqa_new_category = get_option("wpqa_new_category");
	$wpqa_new_category_array = get_option("wpqa_new_category_array");
	if ($wpqa_new_category_array == "") {
		$wpqa_new_category_array = array();
	}
	if ($wpqa_new_category != "") {
		$wpqa_new_category++;
		update_option("wpqa_new_category",$wpqa_new_category);
		array_push($wpqa_new_category_array,$wpqa_new_category);
		update_option("wpqa_new_category_array",$wpqa_new_category_array);
	}else {
		$wpqa_new_category = 1;
		add_option("wpqa_new_category",$wpqa_new_category);
		add_option("wpqa_new_category_array",array($wpqa_new_category));
	}
	add_option("wpqa_new_category_".$wpqa_new_category,array("the_date" => current_time('timestamp'),"category_status" => 1,"user_id" => $user_id,"category" => wpqa_kses_stip($category),"post_id" => $post_id,"item_id_option" => $wpqa_new_category,"reuqest_category" => $_reuqest_category));
}
/* Add category form */
function wpqa_add_category_from() {
	global $category_add;
	$rand = rand(1,1000);
	$out = '<form class="form-post wpqa_form" method="post">'.(isset($_POST["form_type"]) && $_POST["form_type"] == "add_category"?apply_filters('wpqa_add_category','category'):"").'
		<div class="form-inputs clearfix">';
			$out .= '<p>
				<label for="category-name-'.$rand.'">'.esc_html__("Category name","wpqa").'<span class="required">*</span></label>
				<input type="text" name="category" id="category-name-'.$rand.'" value="'.(isset($category_add['category'])?wpqa_kses_stip($category_add['category']):"").'">
				<i class="icon-chat"></i>
				<span class="form-description">'.esc_html__("Please choose an appropriate category for questions.","wpqa").'</span>
			</p>'.
			apply_filters('wpqa_add_category_from_after_category',false,$category_add).'			
			<div class="form-inputs clearfix">
				'.wpqa_add_captcha(wpqa_options("the_captcha_category"),"category",$rand).'
			</div>
		</div>
		
		<p class="form-submit">
			<input type="hidden" name="form_type" value="add_category">
			<input type="hidden" name="wpqa_add_category_nonce" value="'.wp_create_nonce("wpqa_add_category_nonce").'">
			<input type="submit" value="'.esc_html__("Add new category","wpqa").'" class="button-default button-hide-click">
			<span class="load_span"><span class="loader_2"></span></span>
		</p>
	</form>';
	return $out;
}?>