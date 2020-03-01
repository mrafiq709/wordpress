<?php

/* @author    2codeThemes
*  @package   WPQA/functions
*  @version   1.0
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/* Get options */
if (!function_exists('wpqa_options')):
	function wpqa_options( $name, $default = false ) {
		$options = get_option(wpqa_options);
		if ( isset( $options[$name] ) ) {
			return $options[$name];
		}
		return $default;
	}
endif;
add_filter("the_content","do_shortcode");
add_filter("widget_text","do_shortcode");
/* Captcha */
if (!function_exists('wpqa_captcha')) :
	function wpqa_add_captcha($the_captcha,$type,$rand,$comment = "") {
		$captcha_style = wpqa_options("captcha_style");
		$captcha_question = wpqa_options("captcha_question");
		$captcha_answer = wpqa_options("captcha_answer");
		$show_captcha_answer = wpqa_options("show_captcha_answer");
		$out = "";
		$captcha_users = wpqa_options("captcha_users");
		if ($the_captcha == "on" && ($captcha_users == "both" || ($captcha_users == "unlogged" && !is_user_logged_in()))) {
			$out .= "<div class='".($captcha_style == "question_answer" || $captcha_style == "google_recaptcha"?"wpqa_captcha_question":"wpqa_captcha_normal")."'><".($comment == "comment" || $captcha_style == "google_recaptcha"?"div":"p")." class='wpqa_captcha_p".($comment == "comment"?" form-input form-input-full clearfix":"")."'>";
					$out .= ($comment == "comment"?"":"<label for='wpqa_captcha_".$rand."'>".esc_html__('Captcha','wpqa')."<span class='required'>*</span></label>");
				if ($captcha_style != "google_recaptcha") {
					$out .= '<input'.($comment == "comment"?" placeholder='".esc_attr__("Captcha","wpqa")."'":"").' id="wpqa_captcha_'.$rand.'" name="wpqa_captcha" class="wpqa_captcha'.($captcha_style == "google_recaptcha"?" google_recaptcha":"").($captcha_style == "question_answer"?" captcha_answer":"").'" type="text">
					'.($type == 'comment'?'':'<i class="icon-pencil"></i>');
				}
			if ($captcha_style == "google_recaptcha") {
				$out .= "<div class='g-recaptcha' data-sitekey='".wpqa_options("site_key_recaptcha")."'></div><br>";
			}else if ($captcha_style == "question_answer") {
				$out .= "<span class='wpqa_captcha_span'>".$captcha_question.($show_captcha_answer == "on"?" ( ".$captcha_answer." )":"")."</span>";
			}else {
				$out .= "<img class='wpqa_captcha_img' src='".add_query_arg(array("captcha_type" => $type),plugin_dir_url(dirname(__FILE__))."captcha/create_image.php")."' alt='".esc_attr__("Captcha","wpqa")."' title='".esc_attr__("Click here to update the captcha","wpqa")."' onclick=";$out .='"javascript:wpqa_get_captcha';$out .="('".add_query_arg(array("captcha_type" => $type),plugin_dir_url(dirname(__FILE__))."captcha/create_image.php")."', 'wpqa_captcha_img_".$rand."');";$out .='"';$out .=" id='wpqa_captcha_img_".$rand."'>
				<span class='wpqa_captcha_span'>".esc_html__("Click on image to update the captcha.","wpqa")."</span>";
			}
			$out .= "</".($comment == "comment" || $captcha_style == "google_recaptcha"?"div":"p")."></div>";
		}
		return $out;
	}
endif;
/* Check captcha */
if (!function_exists('wpqa_check_captcha')) :
	function wpqa_check_captcha($the_captcha,$type,$posted,$errors) {
		$captcha_users = wpqa_options("captcha_users");
		$captcha_style = wpqa_options("captcha_style");
		$captcha_question = wpqa_options("captcha_question");
		$captcha_answer = wpqa_options("captcha_answer");
		$show_captcha_answer = wpqa_options("show_captcha_answer");

		if ($the_captcha == "on" && ($captcha_users == "both" || ($captcha_users == "unlogged" && !is_user_logged_in()))) {
			if ($captcha_style == "google_recaptcha") {
				if (isset($_POST['g-recaptcha-response']) && !empty($_POST['g-recaptcha-response'])) {
					$secretKey = wpqa_options("secret_key_recaptcha");
					$data = wp_remote_get('https://www.google.com/recaptcha/api/siteverify?secret='.$secretKey.'&response='.$_POST['g-recaptcha-response']);
					if (is_wp_error($data)) {
						$errors->add('required-captcha-error',esc_html__('Robot verification failed, Please try again.','wpqa'));
					}else {
						$json = json_decode($data['body'],true);
					}
					if (isset($json["success"]) && $json["success"] == true) {
						//success
					}else {
						$errors->add('required-captcha-error',esc_html__('Robot verification failed, Please try again.','wpqa'));
					}
				}else {
					$errors->add('required-captcha-error',esc_html__('Please check on the reCAPTCHA box.','wpqa'));
				}
			}else {
				if (empty($posted["wpqa_captcha"])) {
					$errors->add('required-captcha',esc_html__("There are required fields (captcha).","wpqa"));
				}
				else if ($captcha_style == "question_answer") {
					if ($captcha_answer != $posted["wpqa_captcha"]) {
						$errors->add('required-captcha-error',esc_html__('The captcha is incorrect, Please try again.','wpqa'));
					}
				}else {
					if(!session_id()) session_start();
					if (isset($_SESSION["wpqa_code_captcha_".$type]) && $_SESSION["wpqa_code_captcha_".$type] != $posted["wpqa_captcha"]) {
						$errors->add('required-captcha-error',esc_html__('The captcha is incorrect, Please try again.','wpqa'));
					}
				}
			}
		}
		return $errors;
	}
endif;
/* Allow the uploads */
add_action('admin_init','wpqa_allow_uploads');
function wpqa_allow_uploads() {
	if (is_user_logged_in()) {
		$user_info = get_userdata(get_current_user_id());
		$custom_permission = wpqa_options("custom_permission");
		$roles = $user_info->allcaps;
		if (!current_user_can('upload_files') && (($custom_permission != "on") || ($custom_permission == "on" && isset($roles["upload_files"]) && $roles["upload_files"] == 1))) {
			$new_role = get_role(implode(', ', $user_info->roles));
			$new_role->add_cap('upload_files');
		}
	}
}
/* Get images */
if (!function_exists('wpqa_option_images')) :
	function wpqa_option_images($value_id = '',$value_width = '',$value_height = '',$value_options = '',$val = '',$value_class = '',$option_name = '',$name_id = '',$data_attr = '',$add_value_id = '') {
		$output = '';
		$name = $option_name .($add_value_id != 'no'?'['. $value_id .']':'');
		$width = (isset($value_width) && $value_width != ""?" width='".$value_width."' style='box-sizing: border-box;-moz-box-sizing: border-box;-weblit-box-sizing: border-box;'":"");
		$height = (isset($value_height) && $value_height != ""?" height='".$value_height."' style='box-sizing: border-box;-moz-box-sizing: border-box;-weblit-box-sizing: border-box;'":"");
		foreach ( $value_options as $key => $option ) {
			$selected = '';
			if ( $val != '' && ($val == $key) ) {
				$selected = ' of-radio-img-selected';
			}
			$output .= '<div class="of-radio-img-label">' . esc_html( $key ) . '</div>';
			$output .= '<input type="radio" data-attr="' . esc_attr( $data_attr ) . '" class="of-radio-img-radio" value="' . esc_attr( $key ) . '" '.($name_id != "no"?' id="' . esc_attr( $value_id .'_'. $key) . '" name="' . esc_attr( $name ) . '"':'').' '. checked( $val, $key, false ) .'>';
			$output .= '<img'.$width.$height.' src="' . esc_url( $option ) . '" alt="' . $option .'" class="of-radio-img-img '.(isset($value_class)?esc_attr($value_class):'').'' . $selected .'" '.($name_id != "no"?'onclick="document.getElementById(\''. esc_attr($value_id .'_'. $key) .'\').checked=true;"':'').'>';
		}
		return $output;
	}
endif;
/* Sliderui */
if (!function_exists('wpqa_option_sliderui')) :
	function wpqa_option_sliderui($value_min = '',$value_max = '',$value_step = '',$value_edit = '',$val = '',$value_id = '',$option_name = '',$element = '',$bracket = '',$widget = '') {
		$output = $min = $max = $step = $edit = '';
		
		if(!isset($value_min)){ $min  = '0'; }else{ $min = $value_min; }
		if(!isset($value_max)){ $max  = $min + 1; }else{ $max = $value_max; }
		if(!isset($value_step)){ $step  = '1'; }else{ $step = $value_step; }
		
		if (!isset($value_edit)) { 
			$edit  = ' readonly="readonly"'; 
		}else {
			$edit  = '';
		}
		
		if ($val == '') $val = $min;
		
		//values
		$data = 'data-id="'.(isset($element) && $element != ""?$element:$value_id).'" data-val="'.$val.'" data-min="'.$min.'" data-max="'.$max.'" data-step="'.$step.'"';
		
		//html output
		$output .= '<input type="text" name="'.esc_attr( (isset($widget) && $widget == "widget"?$option_name:$option_name . ($bracket != 'remove_it'?'[':'') . $value_id . ']') ).'" id="'.(isset($element) && $element != ""?$element:$value_id).'" value="'. $val .'" class="mini" '. $edit .' />';
		$output .= '<div id="'.(isset($element) && $element != ""?$element:$value_id).'-slider" class="v_sliderui" '. $data .'></div>';
		return $output;
	}
endif;
/* Sessions */
if (!function_exists('wpqa_session')) :
	function wpqa_session ($message = "",$session = "") {
		if(!session_id()) session_start();
		if ($message) {
			$_SESSION[$session] = $message;
		}else {
			if (isset($_SESSION[$session])) {
				$last_message = $_SESSION[$session];
				unset($_SESSION[$session]);
				return $last_message;
			}
		}
	}
endif;
/* Notifications && Activities */
if (!function_exists('wpqa_notifications_activities')) :
	function wpqa_notifications_activities($user_id = "",$another_user_id = "",$username = "",$post_id = "",$comment_id = "",$text = "",$type = "notifications",$more_text = "",$type_of_item = "",$new = true,$array = array()) {
		$active_notifications = wpqa_options("active_notifications");
		$active_activity_log = wpqa_options("active_activity_log");
		if (($type == "notifications" && $active_notifications == "on") || ($type == "activities" && $active_activity_log == "on") || ($type != "notifications" && $type != "activities")) {
			/* Number of my types */
			$_types = get_user_meta($user_id,$user_id."_".$type,true);
			if ($_types == "") {
				$_types = 0;
			}
			$_types++;
			update_user_meta($user_id,$user_id."_".$type,$_types);
			$array = (is_array($array) && !empty($array)?$array:
				array(
					"date_years"      => date_i18n('Y/m/d',current_time('timestamp')),
					"date_hours"      => date_i18n('g:i a',current_time('timestamp')),
					"time"            => current_time('timestamp'),
					"user_id"         => $user_id,
					"another_user_id" => $another_user_id,
					"post_id"         => $post_id,
					"comment_id"      => $comment_id,
					"text"            => $text,
					"username"        => $username,
					"more_text"       => $more_text,
					"type_of_item"    => $type_of_item
				)
			);
			
			add_user_meta($user_id,$user_id."_".$type."_".$_types,$array);
			
			if ($new == true) {
				/* New */
				$_new_types = get_user_meta($user_id,$user_id."_new_".$type,true);
				if (isset($_new_types) && $_new_types != "" && $_new_types > 0) {
					$_new_types++;
				}else {
					$_new_types = 1;
				}
				update_user_meta($user_id,$user_id.'_new_'.$type,$_new_types);
			}
		}
	}
endif;
/* HTML tags */
if (!function_exists('wpqa_html_tags')) :
	function wpqa_html_tags($p_active = "") {
		global $allowedposttags,$allowedtags;
		$allowedposttags['img'] = array('alt' => true, 'class' => true, 'id' => true, 'title' => true, 'src' => true);
		$allowedposttags['a'] = array('href' => true, 'title' => true, 'target' => true, 'class' => true);
		$allowedposttags['br'] = array();
		$allowedposttags['span'] = array('style' => true);
		$allowedtags['img'] = array('alt' => true, 'class' => true, 'id' => true, 'title' => true, 'src' => true);
		$allowedtags['a'] = array('href' => true, 'title' => true, 'target' => true, 'class' => true);
		$allowedtags['blockquote'] = array('class' => true, 'data-secret' => true, 'style' => true);
		$allowedtags['iframe'] = array('title' => true, 'width' => true, 'height' => true, 'src' => true, 'frameborder' => true, 'allow' => true, 'allowfullscreen' => true);
		$allowedtags['span'] = array('style' => true);
		$allowedtags['\\'] = array();
		$allowedtags['pre'] = array('class' => true, 'data-enlighter-language' => true);
		$array = array('hr','br','ul','ol','li','dl','dt','dd','table','td','tr','th','thead','tbody','h1','h2','h3','h4','h5','h6','cite','em','address','big','ins','sub','sup','tt','var');
		foreach ($array as $value) {
			$allowedtags[$value] = array();
		}
		if ($p_active == "yes") {
			$allowedtags['p'] = array('style' => true);
			$allowedposttags['p'] = array('style' => true);
		}
	}
endif;
add_action('wpqa_init','wpqa_html_tags',10);
/* Kses stip */
if (!function_exists('wpqa_kses_stip')) :
	function wpqa_kses_stip($value,$ireplace = "",$p_active = "") {
		return wp_kses(($ireplace == "yes"?str_ireplace(array("<br />","<br>","<br/>","</p>"), "\r\n",$value):$value),wpqa_html_tags(($p_active == "yes"?$p_active:"")));
	}
endif;
/* Kses stip wpautop */
if (!function_exists('wpqa_kses_stip_wpautop')) :
	function wpqa_kses_stip_wpautop($value,$ireplace = "",$p_active = "") {
		return wpautop(wp_kses((($ireplace == "yes"?str_ireplace(array("<br />","<br>","<br/>","</p>"), "\r\n",$value):$value)),wpqa_html_tags(($p_active == "yes"?$p_active:""))));
	}
endif;
/* Count number */
if (!function_exists('wpqa_count_number')) :
	function wpqa_count_number($input) {
		$active_separator = wpqa_options("active_separator");
		$number_separator = wpqa_options("number_separator");
		$input = number_format((int)$input,0,'',($active_separator != 'on'?',':$number_separator));
		$input_count = substr_count($input,',');
		if ($active_separator != 'on' && $input_count != '0') {
			if ($input_count == '1') {
				return (int)substr($input,0,-4).esc_html__('k','wpqa');
			}else if ($input_count == '2') {
				return (int)substr($input,0,-8).esc_html__('mil','wpqa');
			}else if ($input_count == '3') {
				return (int)substr($input,0,-12).esc_html__('bil','wpqa');
			}else {
				return;
			}
		}else {
			return $input;
		}
	}
endif;
/* Get resize img url */
if (!function_exists('wpqa_get_aq_resize_img_url')) :
	function wpqa_get_aq_resize_img_url ($img_width_f,$img_height_f,$img_lightbox = "",$thumbs = "",$gif = "no",$title = "") {
		if (empty($thumbs)) {
			$thumb = get_post_thumbnail_id();
		}else {
			$thumb = $thumbs;
		}
		if ($img_lightbox == "lightbox" || $img_width_f == "" || $img_height_f == "") {
			$full_image = wp_get_attachment_image_src($thumb,"full");
			if ($img_lightbox == "lightbox") {
				$img_url = $full_image[0];
			}
			$img_width_f = ($img_width_f != ""?$img_width_f:$full_image[1]);
			$img_height_f = ($img_height_f != ""?$img_height_f:$full_image[2]);
		}
		$image = wpqa_resize($thumb,'',$img_width_f,$img_height_f,true,$gif);
		if (isset($image['url']) && $image['url'] != "") {
			$last_image = $image['url'];
		}else {
			$last_image = "https://placehold.it/".$img_width_f."x".$img_height_f;
		}
		if (isset($last_image) && $last_image != "") {
			return $last_image;
		}
	}
endif;
/* Get resize img */
if (!function_exists('wpqa_get_aq_resize_img')) :
	function wpqa_get_aq_resize_img ($img_width_f,$img_height_f,$img_lightbox = "",$thumbs = "",$gif = "no",$title = "") {
		$last_image = wpqa_get_aq_resize_img_url($img_width_f,$img_height_f,$img_lightbox,$thumbs,$gif,$title);
		if (isset($last_image) && $last_image != "") {
			return ($img_lightbox == "lightbox"?"<a href='".esc_url($img_url)."'>":"")."<img alt='".(isset($title) && $title != ""?$title:get_the_title())."' width='".$img_width_f."' height='".$img_height_f."' src='".$last_image."'>".($img_lightbox == "lightbox"?"</a>":"");
		}
	}
endif;
/* Get resize image with URL */
if (!function_exists('wpqa_get_aq_resize_url')) :
	function wpqa_get_aq_resize_url ($url,$img_width_f,$img_height_f,$what = "",$gif = "no") {
		$image = wpqa_resize("", $url, $img_width_f, $img_height_f, true,$gif);
		if ((isset($image['url']) && $image['url'] != "") || $what == "on") {
			$last_image = $image['url'];
		}else {
			$last_image = "https://placehold.it/".$img_width_f."x".$img_height_f;
		}
		return $last_image;
	}
endif;
/* Get the attachment ID */
if (!function_exists('wpqa_get_attachment_id')) :
	function wpqa_get_attachment_id ($image_url) {
		global $wpdb;
		$components = parse_url($image_url);
		$attachment = $wpdb->get_col($wpdb->prepare("SELECT ID,guid FROM $wpdb->posts WHERE guid RLIKE '%s';", (isset($components['path']) && $components['path'] != ""?$components['path']:$image_url) ));
		if (isset($attachment[0]) && $attachment[0] != "") {
			return $attachment[0];
		}
	}
endif;
/* Get first image */
if (!function_exists('wpqa_image')) :
	function wpqa_image () {
		global $post;
		ob_start();
		ob_end_clean();
		$output = preg_match_all('/<img.+src=[\'"]([^\'"]+)[\'"].*>/i',$post->post_content,$matches);
		if (isset($matches[1][0])) {
			return $matches[1][0];
		}else {
			return false;
		}
	}
endif;
/* Admin bar */
if (!function_exists('wpqa_admin_bar')) :
	function wpqa_admin_bar() {
		global $wp_admin_bar;
		if (is_super_admin()) {
			if (wpqa_is_user_profile()) {
	    		$wpqa_user_id = (int)get_query_var(apply_filters('wpqa_user_id','wpqa_user_id'));
	    		if ($wpqa_user_id > 0) {
					$wp_admin_bar->add_menu( array(
						'parent' => 0,
						'id' => 'edit_user',
						'title' => '<span class="ab-icon dashicons-before dashicons-edit"></span>'.esc_html__("Edit User","wpqa").'</span></span>' ,
						'href' => admin_url('user-edit.php?user_id='.$wpqa_user_id)
					));
				}
			}
			$count_questions_by_type = wpqa_count_posts_by_type( "question", "draft" );
			if ($count_questions_by_type > 0) {
				$wp_admin_bar->add_menu( array(
					'parent' => 0,
					'id' => 'questions_draft',
					'title' => '<span class="ab-icon dashicons-before dashicons-editor-help"></span><span class="count-'.$count_questions_by_type.'"><span>'.$count_questions_by_type.'</span></span>' ,
					'href' => admin_url('edit.php?post_status=draft&post_type=question')
				));
			}
			$count_posts_by_type = wpqa_count_posts_by_type( "post", "draft" );
			if ($count_posts_by_type > 0) {
				$wp_admin_bar->add_menu( array(
					'parent' => 0,
					'id' => 'posts_draft',
					'title' => '<span class="ab-icon dashicons-before dashicons-media-text"></span><span class="count-'.$count_posts_by_type.'"><span>'.$count_posts_by_type.'</span></span>' ,
					'href' => admin_url('edit.php?post_status=draft&post_type=post')
				));
			}
			$pay_ask = wpqa_options('pay_ask');
			$payment_type_ask = wpqa_options('payment_type_ask');
			$pay_to_sticky = wpqa_options('pay_to_sticky');
			$payment_type_sticky = wpqa_options('payment_type_sticky');
			$buy_points_payment = wpqa_options('buy_points_payment');
			$pay_to_answer = wpqa_options('pay_to_answer');
			$payment_type_answer = wpqa_options('payment_type_answer');
			$pay_to_anything = apply_filters("wpqa_filter_pay_to_anything",false);
			if (($pay_ask == "on" && $payment_type_ask != "points") || ($pay_to_sticky == "on" && $payment_type_sticky != "points") || $buy_points_payment == "on" || ($pay_to_answer == "on" && $payment_type_answer != "points") || $pay_to_anything == true) {
				$new_payments = (int)get_option("new_payments");
				$wp_admin_bar->add_menu( array(
					'parent' => 0,
					'id' => 'new_payments',
					'title' => '<span class="ab-icon dashicons-before dashicons-cart"></span><span class="count-'.$new_payments.'"><span>'.$new_payments.'</span></span>' ,
					'href' => admin_url('admin.php?page=wpqa_payments')
				));
			}
			$active_message = wpqa_options('active_message');
			$count_messages_by_type = wpqa_count_posts_by_type( "message", "draft" );
			if ($active_message == "on" && $count_messages_by_type > 0) {
				$wp_admin_bar->add_menu( array(
					'parent' => 0,
					'id' => 'messages_draft',
					'title' => '<span class="ab-icon dashicons-before dashicons-email-alt"></span><span class="count-'.$count_messages_by_type.'"><span>'.$count_messages_by_type.'</span></span>' ,
					'href' => admin_url('edit.php?post_status=draft&post_type=message')
				));
			}
			$count_users = count_users();
			$count_user_under_review = (isset($count_users["avail_roles"]["wpqa_under_review"])?$count_users["avail_roles"]["wpqa_under_review"]:0);
			if ($count_user_under_review > 0) {
				$wp_admin_bar->add_menu( array(
					'parent' => 0,
					'id' => 'user_under_review',
					'title' => '<span class="ab-icon dashicons-before dashicons-admin-users"></span><span class="count-'.$count_user_under_review.'"><span>'.$count_user_under_review.'</span></span>' ,
					'href' => admin_url('users.php?role=wpqa_under_review')
				));
			}
		}
	}
endif;
add_action( 'wp_before_admin_bar_render', 'wpqa_admin_bar' );
/* Admin bar menu */
add_action('admin_bar_menu', 'wpqa_admin_bar_menu', 70 );
if (!function_exists('wpqa_admin_bar_menu')) :
	function wpqa_admin_bar_menu( $wp_admin_bar ) {
		if (is_super_admin()) {
			$answers_count = count(get_comments(array("post_type" => "question")));
			if ($answers_count > 0) {
				$wp_admin_bar->add_node( array(
					'parent' => 0,
					'id' => 'answers',
					'title' => '<span class="ab-icon dashicons-before dashicons-format-chat"></span><span class="count-'.$answers_count.'"><span>'.$answers_count.'</span></span>' ,
					'href' => admin_url('edit-comments.php?comment_status=all&answers=1')
				));
			}
		}
	}
endif;
/* All comments by post type */
if (!function_exists('wpqa_all_comments_of_post_type')) :
	function wpqa_all_comments_of_post_type($post_type) {
		global $wpdb;
		$comments = $wpdb->get_var("SELECT COUNT(comment_ID)
			FROM $wpdb->comments
			WHERE comment_post_ID in (
			SELECT ID 
			FROM $wpdb->posts 
			WHERE post_type = '$post_type' 
			AND post_status = 'publish')
			AND comment_approved = '1'
		");
		return $comments;
	}
endif;
/* Show messages li */
if (!function_exists('wpqa_get_messages')) :
	function wpqa_get_messages($user_id,$item_number,$more_button,$count = false) {
		global $post;
		$output = '';
		$time_format = wpqa_options("time_format");
		$time_format = ($time_format?$time_format:get_option("time_format"));
		$date_format = wpqa_options("date_format");
		$date_format = ($date_format?$date_format:get_option("date_format"));
		if ($count == true) {
			$num = wpqa_count_new_message((isset($user_id)?$user_id:0));
			$num = (isset($num) && $num != "" && $num > 0?$num:0);
			if (isset($num) && $num != "" && $num > 0) {
				$num = ($num <= 99?$num:"99+");
				$output .= '<span class="notifications-number">'.$num.'</span>';
			}
		}
		$output .= '<div>
		<ul>';
		$args = array('post_type' => 'message','posts_per_page' => $item_number,"meta_query" => array('relation' => 'AND',array("key" => "delete_inbox_message","compare" => "NOT EXISTS"),array("key" => "message_user_id","compare" => "=","value" => $user_id)));
		$messages_query = new WP_Query( $args );
		if ($messages_query->have_posts()) {
			while ( $messages_query->have_posts() ) { $messages_query->the_post();
				$message_new = get_post_meta($post->ID,'message_new',true);
				$output .= '<li>
					<i class="message_new'.($message_new == 1 || $message_new == "on"?" message-new":"").' icon-mail"></i>
					<div>';
						$display_name = get_the_author_meta('display_name',$post->post_author);
						if ($post->post_author > 0) {
							$output .= '<a href="'.get_author_posts_url($post->post_author).'">'.$display_name.'</a>';
						}else {
							$output .= get_post_meta($post->ID,'message_username',true);
						}
						$output .= ' '.esc_html__("has","wpqa").' <a href="'.esc_url(wpqa_get_profile_permalink($user_id,"messages")).'">'.esc_html__("sent a message for you.","wpqa").'</a>
						<span class="notifications-date">'.sprintf(esc_html__('%1$s at %2$s','wpqa'),get_the_time($date_format),get_the_time($time_format)).'</span>
					</div>
				</li>';
			}
				
			$output .= '</ul>';
			if ($more_button == "on") {
				$output .= '<a href="'.esc_url(wpqa_get_profile_permalink($user_id,"messages")).'">'.esc_html__("Show all messages.","wpqa").'</a>';
			}
		}else {
			$output .= '<li><div>'.esc_html__("There are no messages yet.","wpqa").'</div></li></ul>';
		}
		$output .= '</div>';
		wp_reset_postdata();
		return $output;
	}
endif;
/* Show notifications li */
if (!function_exists('wpqa_get_notifications')) :
	function wpqa_get_notifications($user_id,$item_number,$more_button,$count = false,$more_button_ul = false) {
		$output = '';
		if ($count == true) {
			$num = get_user_meta($user_id,$user_id.'_new_notifications',true);
			$num = (isset($num) && $num != "" && $num > 0?$num:0);
			if (isset($num) && $num != "" && $num > 0) {
				$num = ($num <= 99?$num:"99+");
				$output .= '<span class="notifications-number">'.$num.'</span>';
			}
		}
		if ($more_button_ul == false) {
			$output .= '<div>';
		}
		$output .= '<ul>';
		$_notifications = get_user_meta($user_id,$user_id."_notifications",true);
		
		for ($notifications = 1; $notifications <= $_notifications; $notifications++) {
			$notification_one[] = get_user_meta($user_id,$user_id."_notifications_".$notifications);
		}
		if (isset($notification_one) && is_array($notification_one)) {
			$notification = array_reverse($notification_one);
			$end = (sizeof($notification) < $item_number) ? sizeof($notification) : $item_number;
			for ($i=0;$i < $end ;++$i) {
				$notification_result = $notification[$i][0];
				if (!empty($notification_result["another_user_id"])) {
					$wpqa_profile_url = wpqa_profile_url($notification_result["another_user_id"]);
					$display_name = get_the_author_meta('display_name',$notification_result["another_user_id"]);
				}
				$output .= wpqa_show_notifications($notification_result,"on");
			}
			if ($more_button == "on" && $more_button_ul == true) {
				$output .= "<li><a href='".esc_url(wpqa_get_profile_permalink($user_id,"notifications"))."'>".esc_html__("Show all notifications.","wpqa")."</a></li>";
			}
			$output .= "</ul>";
			if ($more_button == "on" && $more_button_ul == false) {
				$output .= "<a href='".esc_url(wpqa_get_profile_permalink($user_id,"notifications"))."'>".esc_html__("Show all notifications.","wpqa")."</a>";
			}
		}else {
			$output .= "<li><div>".esc_html__("There are no notifications yet.","wpqa")."</div></li></ul>";
		}
		if ($more_button_ul == false) {
			$output .= '</div>';
		}
		return $output;
	}
endif;
/* Show notifications */
if (!function_exists('wpqa_show_notifications')) :
	function wpqa_show_notifications($whats_type_result,$show_date = "") {
		$output = "";
		if ($show_date == "on") {
			$output .= "<li>";
		}
		
		$result_icon = apply_filters("wpqa_notifications_icon",false,$whats_type_result["text"]);
		if ($result_icon != "") {
			$output .= "<i class='".$result_icon."'></i>";
		}else if ($whats_type_result["text"] == "accepted_category" || $whats_type_result["text"] == "canceled_category") {
			$output .= "<i class='icon-folder'></i>";
		}else if ($whats_type_result["text"] == "question_vote_up" || $whats_type_result["text"] == "answer_vote_up") {
			$output .= "<i class='icon-up-dir'></i>";
		}else if ($whats_type_result["text"] == "question_vote_down" || $whats_type_result["text"] == "answer_vote_down") {
			$output .= "<i class='icon-down-dir'></i>";
		}else if ($whats_type_result["text"] == "gift_site" || $whats_type_result["text"] == "points_referral" || $whats_type_result["text"] == "admin_add_points") {
			$output .= "<i class='icon-bucket'></i>";
		}else if ($whats_type_result["text"] == "admin_remove_points" || $whats_type_result["text"] == "question_remove_favorites") {
			$output .= "<i class='icon-star-empty'></i>";
		}else if ($whats_type_result["text"] == "add_message_user" || $whats_type_result["text"] == "seen_message") {
			$output .= "<i class='icon-mail'></i>";
		}else if ($whats_type_result["text"] == "question_favorites") {
			$output .= "<i class='icon-star'></i>";
		}else if ($whats_type_result["text"] == "follow_question" || $whats_type_result["text"] == "user_follow") {
			$output .= "<i class='icon-plus'></i>";
		}else if ($whats_type_result["text"] == "unfollow_question" || $whats_type_result["text"] == "user_unfollow") {
			$output .= "<i class='icon-minus'></i>";
		}else if ($whats_type_result["text"] == "answer_asked_question" || $whats_type_result["text"] == "select_best_answer" || $whats_type_result["text"] == "cancel_best_answer" || $whats_type_result["text"] == "answer_question" || $whats_type_result["text"] == "answer_question_follow" || $whats_type_result["text"] == "approved_answer" || $whats_type_result["text"] == "approved_comment") {
			$output .= "<i class='icon-comment'></i>";
		}else if (!empty($whats_type_result["post_id"])) {
			$output .= "<i class='icon-sound'></i>";
		}else if (!empty($whats_type_result["comment_id"])) {
			$output .= "<i class='icon-comment'></i>";
		}else if ((!empty($whats_type_result["another_user_id"]) || !empty($whats_type_result["username"])) && $whats_type_result["text"] != "admin_add_points" && $whats_type_result["text"] != "admin_remove_points") {
			$output .= "<i class='icon-user'></i>";
		}else if ($whats_type_result["text"] == "action_comment" || $whats_type_result["text"] == "action_post" || $whats_type_result["text"] == "delete_reason" || $whats_type_result["text"] == "delete_question" || $whats_type_result["text"] == "delete_post" || $whats_type_result["text"] == "delete_answer" || $whats_type_result["text"] == "delete_comment") {
			$output .= "<i class='icon-cancel'></i>";
		}else {
			$output .= "<i class='icon-check'></i>";
		}
		
		$output .= "<div>";
		if (!empty($whats_type_result["another_user_id"])) {
			$wpqa_profile_url = wpqa_profile_url($whats_type_result["another_user_id"]);
			$display_name = get_the_author_meta('display_name',$whats_type_result["another_user_id"]);
		}
		
		if ((($whats_type_result["text"] == "add_question_user" || $whats_type_result["text"] == "add_question" || $whats_type_result["text"] == "poll_question") && empty($whats_type_result["username"]) && isset($whats_type_result["another_user_id"]) && $whats_type_result["another_user_id"] == 0) || (!empty($whats_type_result["another_user_id"]) || !empty($whats_type_result["username"])) && $whats_type_result["text"] != "admin_add_points" && $whats_type_result["text"] != "admin_remove_points") {
			
			if ((($whats_type_result["text"] == "add_question_user" || $whats_type_result["text"] == "add_question" || $whats_type_result["text"] == "poll_question") && isset($whats_type_result["another_user_id"]) && $whats_type_result["another_user_id"] == 0) || (isset($display_name) && $display_name != "")) {
				if (!empty($whats_type_result["another_user_id"])) {
					$output .= '<a href="'.esc_url($wpqa_profile_url).'">'.esc_html($display_name).'</a> ';
				}
				if (!empty($whats_type_result["username"])) {
					$output .= esc_attr($whats_type_result["username"])." ";
				}
				if (($whats_type_result["text"] == "add_question_user" || $whats_type_result["text"] == "add_question") && empty($whats_type_result["username"]) && isset($whats_type_result["another_user_id"]) && $whats_type_result["another_user_id"] == 0) {
					$output .= esc_html__("Anonymous","wpqa")." ";
				}
				if (($whats_type_result["text"] == "poll_question") && empty($whats_type_result["username"]) && isset($whats_type_result["another_user_id"]) && $whats_type_result["another_user_id"] == 0) {
					$output .= esc_html__("A non-registered user","wpqa")." ";
				}
				$output .= esc_html__("has","wpqa");
			}else if (!empty($whats_type_result["username"])) {
				$output .= esc_attr($whats_type_result["username"])." ";
			}else {
				$output .= esc_html__("Deleted user","wpqa")." -";
			}
		}
		
		$output .= " ";
		if (!empty($whats_type_result["post_id"])) {
			$get_the_permalink = get_the_permalink($whats_type_result["post_id"]);
			$get_post_status = get_post_status($whats_type_result["post_id"]);
		}
		if (!empty($whats_type_result["comment_id"])) {
			$get_comment = get_comment($whats_type_result["comment_id"]);
		}
		if (!empty($whats_type_result["post_id"]) && !empty($whats_type_result["comment_id"]) && $get_post_status != "trash" && isset($get_comment) && $get_comment->comment_approved != "spam" && $get_comment->comment_approved != "trash") {
			$output .= '<a href="'.esc_url($get_the_permalink.(isset($whats_type_result["comment_id"])?"#comment-".$whats_type_result["comment_id"]:"")).'">';
		}
		if (!empty($whats_type_result["post_id"]) && empty($whats_type_result["comment_id"]) && $get_post_status != "trash" && isset($get_the_permalink) && $get_the_permalink != "") {
			$output .= '<a href="'.esc_url($get_the_permalink).'">';
		}
			$result_text = apply_filters("wpqa_notifications_text",false,$whats_type_result["text"]);
			if ($result_text != "") {
				$output .= $result_text;
			}else if ($whats_type_result["text"] == "accepted_category") {
				$output .= esc_html__("The administrator approved your category.","wpqa");
			}else if ($whats_type_result["text"] == "canceled_category") {
				$output .= esc_html__("The administrator rejected your category.","wpqa");
			}else if ($whats_type_result["text"] == "poll_question") {
				$output .= esc_html__("polled at your question","wpqa");
			}else if ($whats_type_result["text"] == "gift_site") {
				$output .= esc_html__("Gift of the site","wpqa");
			}else if ($whats_type_result["text"] == "points_referral") {
				$output .= esc_html__("referred a new user","wpqa");
			}else if ($whats_type_result["text"] == "admin_add_points") {
				$output .= esc_html__("The administrator added points for you.","wpqa");
			}else if ($whats_type_result["text"] == "admin_remove_points") {
				$output .= esc_html__("The administrator removed points from you.","wpqa");
			}else if ($whats_type_result["text"] == "question_vote_up") {
				$output .= esc_html__("voted up your question.","wpqa");
			}else if ($whats_type_result["text"] == "question_vote_down") {
				$output .= esc_html__("voted down your question.","wpqa");
			}else if ($whats_type_result["text"] == "answer_vote_up") {
				$output .= esc_html__("voted up your answer.","wpqa");
			}else if ($whats_type_result["text"] == "answer_vote_down") {
				$output .= esc_html__("voted down your answer.","wpqa");
			}else if ($whats_type_result["text"] == "user_follow") {
				$output .= esc_html__("followed you.","wpqa");
			}else if ($whats_type_result["text"] == "user_unfollow") {
				$output .= esc_html__("unfollowed you.","wpqa");
			}else if ($whats_type_result["text"] == "point_back") {
				$output .= esc_html__("Your point back because the best answer selected.","wpqa");
			}else if ($whats_type_result["text"] == "select_best_answer") {
				$output .= esc_html__("chosen your answer best answer.","wpqa");
			}else if ($whats_type_result["text"] == "point_removed") {
				$output .= esc_html__("Your point removed because the best answer removed.","wpqa");
			}else if ($whats_type_result["text"] == "cancel_best_answer") {
				$output .= esc_html__("canceled your answer best answer.","wpqa");
			}else if ($whats_type_result["text"] == "answer_asked_question") {
				$output .= esc_html__("answered at your asked question.","wpqa");
			}else if ($whats_type_result["text"] == "answer_question") {
				$output .= esc_html__("answered your question.","wpqa");
			}else if ($whats_type_result["text"] == "answer_question_follow") {
				$output .= esc_html__("answered your question you followed.","wpqa");
			}else if ($whats_type_result["text"] == "add_question") {
				$output .= esc_html__("added a new question.","wpqa");
			}else if ($whats_type_result["text"] == "add_question_user") {
				$output .= esc_html__("asked you a question.","wpqa");
			}else if ($whats_type_result["text"] == "question_favorites") {
				$output .= esc_html__("added your question to favorites.","wpqa");
			}else if ($whats_type_result["text"] == "question_remove_favorites") {
				$output .= esc_html__("removed your question from favorites.","wpqa");
			}else if ($whats_type_result["text"] == "follow_question") {
				$output .= esc_html__("followed your question.","wpqa");
			}else if ($whats_type_result["text"] == "unfollow_question") {
				$output .= esc_html__("unfollowed your question.","wpqa");
			}else if ($whats_type_result["text"] == "approved_answer") {
				$output .= esc_html__("The administrator approved your answer.","wpqa");
			}else if ($whats_type_result["text"] == "approved_comment") {
				$output .= esc_html__("The administrator approved your comment.","wpqa");
			}else if ($whats_type_result["text"] == "approved_question") {
				if (!empty($whats_type_result["another_user_id"])) {
					$output .= esc_html__("approved your question.","wpqa");
				}else {
					$output .= esc_html__("The administrator approved your question.","wpqa");
				}
			}else if ($whats_type_result["text"] == "approved_message") {
				$output .= "<a href='".esc_url(wpqa_get_profile_permalink(get_current_user_id(),"messages"))."'>".esc_html__("The administrator approved your message.","wpqa")."</a>";
			}else if ($whats_type_result["text"] == "answer_review") {
				$output .= esc_html__("The administrator review your answer.","wpqa");
			}else if ($whats_type_result["text"] == "question_review") {
				$output .= esc_html__("The administrator review your question.","wpqa");
			}else if ($whats_type_result["text"] == "approved_post") {
				$output .= esc_html__("The administrator approved your post.","wpqa");
			}else if ($whats_type_result["text"] == "action_comment") {
				$output .= sprintf(esc_html__("The administrator %s your %s.","wpqa"),$whats_type_result["more_text"],(isset($whats_type_result["type_of_item"]) && $whats_type_result["type_of_item"] == "answer"?esc_html__("answer","wpqa"):esc_html__("comment","wpqa")));
			}else if ($whats_type_result["text"] == "action_post") {
				$output .= sprintf(esc_html__("The administrator %s your %s.","wpqa"),$whats_type_result["more_text"],(isset($whats_type_result["type_of_item"]) && $whats_type_result["type_of_item"] == "question"?esc_html__("question","wpqa"):esc_html__("post","wpqa")));
			}else if ($whats_type_result["text"] == "delete_reason") {
				$output .= sprintf(esc_html__("The administrator reason: %s.","wpqa"),$whats_type_result["more_text"]);
			}else if ($whats_type_result["text"] == "delete_question" || $whats_type_result["text"] == "delete_post") {
				if (isset($whats_type_result["type_of_item"]) && $whats_type_result["type_of_item"] == "question") {
					if (!empty($whats_type_result["another_user_id"])) {
						$output .= esc_html__("deleted your question.","wpqa");
					}else {
						$output .= esc_html__("Your question was deleted.","wpqa");
					}
				}else {
					$output .= esc_html__("Your post was deleted.","wpqa");
				}
			}else if ($whats_type_result["text"] == "delete_answer" || $whats_type_result["text"] == "delete_comment") {
				if (isset($whats_type_result["type_of_item"]) && $whats_type_result["type_of_item"] == "answer") {
					$output .= esc_html__("Your answer was deleted.","wpqa");
				}else {
					$output .= esc_html__("Your comment was deleted.","wpqa");
				}
			}else if ($whats_type_result["text"] == "add_message_user") {
				$output .= "<a href='".esc_url(wpqa_get_profile_permalink(get_current_user_id(),"messages"))."'>".esc_html__("sent a message for you.","wpqa")."</a>";
			}else if ($whats_type_result["text"] == "seen_message") {
				$output .= esc_html__("seen your message.","wpqa");
			}else {
				$output .= $whats_type_result["text"];
			}
			if ((!empty($whats_type_result["post_id"]) && !empty($whats_type_result["comment_id"]) && $get_post_status != "trash" && isset($get_comment) && $get_comment->comment_approved != "spam" && $get_comment->comment_approved != "trash") || (!empty($whats_type_result["post_id"]) && empty($whats_type_result["comment_id"]) && $get_post_status != "trash" && isset($get_the_permalink) && $get_the_permalink != "")) {
			$output .= '</a>';
		}
		if (is_super_admin($whats_type_result["user_id"]) && !empty($whats_type_result["post_id"]) && !empty($whats_type_result["comment_id"])) {
			if (isset($get_comment) && $get_comment->comment_approved == "spam") {
				$output .= " ".esc_html__('( Spam )','wpqa');
			}else if ($get_post_status == "trash" || (isset($get_comment) && $get_comment->comment_approved == "trash")) {
				$output .= " ".esc_html__('( Trashed )','wpqa');
			}else if (empty($get_comment)) {
				$output .= " ".esc_html__('( Deleted )','wpqa');
			}
			if ($whats_type_result["text"] == "delete_reason") {
				$output .= " - ".(isset($whats_type_result["type_of_item"]) && $whats_type_result["type_of_item"] == "answer"?esc_html__("answer","wpqa"):esc_html__("comment","wpqa"));
			}
		}
		if (is_super_admin($whats_type_result["user_id"]) && !empty($whats_type_result["post_id"]) && empty($whats_type_result["comment_id"])) {
			if ($get_post_status == "trash") {
				$output .= " ".esc_html__('( Trashed )','wpqa');
			}else if (empty($get_the_permalink)) {
				$output .= " ".esc_html__('( Deleted )','wpqa');
			}
		}
		if (!empty($whats_type_result["more_text"]) && $whats_type_result["text"] != "action_post" && $whats_type_result["text"] != "action_comment" && $whats_type_result["text"] != "delete_reason") {
			$output .= " - ".esc_attr($whats_type_result["more_text"]).".";
		}
		
		if ($show_date == "on") {
			$time_format = wpqa_options("time_format");
			$time_format = ($time_format?$time_format:get_option("time_format"));
			$date_format = wpqa_options("date_format");
			$date_format = ($date_format?$date_format:get_option("date_format"));
			$output .= "<span class='notifications-date'>".sprintf(esc_html__('%1$s at %2$s','wpqa'),mysql2date($date_format,date($date_format,$whats_type_result["time"])),mysql2date($time_format,date($time_format,$whats_type_result["time"])))."</span>
			</div></li>";
		}
		return $output;	
	}
endif;
/* Show activities */
if (!function_exists('wpqa_show_activities')) :
	function wpqa_show_activities($whats_type_result,$show_date = "") {
		$output = "";
		if ($show_date == "on") {
			$output .= "<li>";
		}
		
		$result_icon = apply_filters("wpqa_activities_icon",false,$whats_type_result["text"]);
		if ($result_icon != "") {
			$output .= "<i class='".$result_icon."'></i>";
		}else if ($whats_type_result["text"] == "approved_category") {
			$output .= "<i class='icon-folder'></i>";
		}else if ($whats_type_result["text"] == "question_vote_up" || $whats_type_result["text"] == "answer_vote_up") {
			$output .= "<i class='icon-up-dir'></i>";
		}else if ($whats_type_result["text"] == "question_vote_down" || $whats_type_result["text"] == "answer_vote_down") {
			$output .= "<i class='icon-down-dir'></i>";
		}else if ($whats_type_result["text"] == "select_best_answer" || $whats_type_result["text"] == "cancel_best_answer" || $whats_type_result["text"] == "add_answer" || $whats_type_result["text"] == "add_comment" || $whats_type_result["text"] == "report_answer" || $whats_type_result["text"] == "approved_answer" || $whats_type_result["text"] == "approved_comment") {
			$output .= "<i class='icon-comment'></i>";
		}else if (!empty($whats_type_result["post_id"])) {
			$output .= "<i class='icon-sound'></i>";
		}else if (!empty($whats_type_result["comment_id"])) {
			$output .= "<i class='icon-comment'></i>";
		}else if ($whats_type_result["text"] == "add_message") {
			$output .= "<i class='icon-mail'></i>";
		}else if ((!empty($whats_type_result["another_user_id"]) || !empty($whats_type_result["username"])) && $whats_type_result["text"] != "admin_add_points" && $whats_type_result["text"] != "admin_remove_points") {
			$output .= "<i class='icon-user'></i>";
		}else if ($whats_type_result["text"] == "gift_site" || $whats_type_result["text"] == "admin_add_points") {
			$output .= "<i class='icon-bucket'></i>";
		}else if ($whats_type_result["text"] == "admin_remove_points") {
			$output .= "<i class='icon-star-empty'></i>";
		}else if ($whats_type_result["text"] == "delete_inbox_message" || $whats_type_result["text"] == "delete_send_message" || $whats_type_result["text"] == "action_comment" || $whats_type_result["text"] == "action_post" || $whats_type_result["text"] == "delete_reason" || $whats_type_result["text"] == "delete_question" || $whats_type_result["text"] == "delete_post" || $whats_type_result["text"] == "delete_answer" || $whats_type_result["text"] == "delete_comment") {
			$output .= "<i class='icon-cancel'></i>";
		}else {
			$output .= "<i class='icon-check'></i>";
		}
		
		$output .= "<div>";
		if (!empty($whats_type_result["another_user_id"])) {
			$wpqa_profile_url = wpqa_profile_url($whats_type_result["another_user_id"]);
			$display_name = get_the_author_meta('display_name',$whats_type_result["another_user_id"]);
		}
		
		if ((!empty($whats_type_result["another_user_id"]) || !empty($whats_type_result["username"])) && $whats_type_result["text"] != "add_message" && $whats_type_result["text"] != "admin_add_points" && $whats_type_result["text"] != "admin_remove_points" && $whats_type_result["text"] != "user_follow" && $whats_type_result["text"] != "ban_user" && $whats_type_result["text"] != "unban_user" && $whats_type_result["text"] != "user_unfollow") {
			if (isset($display_name) && $display_name != "") {
				if (!empty($whats_type_result["another_user_id"])) {
					$output .= '<a href="'.esc_url($wpqa_profile_url).'">'.esc_html($display_name).'</a>';
				}
				if (!empty($whats_type_result["username"])) {
					$output .= esc_attr($whats_type_result["username"])." ";
				}
				$output .= esc_html__("has","wpqa")." ";
			}else {
				$output .= esc_html__("Deleted user","wpqa")." - ";
			}
		}
		
		if (!empty($whats_type_result["post_id"])) {
			$get_the_permalink = get_the_permalink($whats_type_result["post_id"]);
			$get_post_status = get_post_status($whats_type_result["post_id"]);
		}
		if (!empty($whats_type_result["comment_id"])) {
			$get_comment = get_comment($whats_type_result["comment_id"]);
		}
		if (!empty($whats_type_result["post_id"]) && !empty($whats_type_result["comment_id"]) && $get_post_status != "trash" && isset($get_comment) && $get_comment->comment_approved != "spam" && $get_comment->comment_approved != "trash") {
			$output .= '<a href="'.esc_url($get_the_permalink.(isset($whats_type_result["comment_id"])?"#comment-".$whats_type_result["comment_id"]:"")).'">';
		}
		if (!empty($whats_type_result["post_id"]) && empty($whats_type_result["comment_id"]) && $get_post_status != "trash" && isset($get_the_permalink) && $get_the_permalink != "") {
			$output .= '<a href="'.esc_url($get_the_permalink).'">';
		}
			
			$result_text = apply_filters("wpqa_activities_text",false,$whats_type_result["text"]);
			if ($result_text != "") {
				$output .= $result_text;
			}else if ($whats_type_result["text"] == "poll_question") {
				$output .= esc_html__("Poll at question","wpqa");
			}else if ($whats_type_result["text"] == "question_vote_up") {
				$output .= esc_html__("Voted up question.","wpqa");
			}else if ($whats_type_result["text"] == "question_vote_down") {
				$output .= esc_html__("Voted down question.","wpqa");
			}else if ($whats_type_result["text"] == "answer_vote_up") {
				$output .= esc_html__("Voted up answer.","wpqa");
			}else if ($whats_type_result["text"] == "answer_vote_down") {
				$output .= esc_html__("Voted down answer.","wpqa");
			}else if ($whats_type_result["text"] == "user_follow") {
				$output .= esc_html__("You have followed","wpqa");
			}else if ($whats_type_result["text"] == "ban_user") {
				$output .= esc_html__("You have banned user","wpqa");
			}else if ($whats_type_result["text"] == "unban_user") {
				$output .= esc_html__("You have unbanned user","wpqa");
			}else if ($whats_type_result["text"] == "user_unfollow") {
				$output .= esc_html__("You have unfollowed","wpqa");
			}else if ($whats_type_result["text"] == "bump_question") {
				$output .= esc_html__("You have bumped your question.","wpqa");
			}else if ($whats_type_result["text"] == "report_question") {
				$output .= esc_html__("You have reported a question.","wpqa");
			}else if ($whats_type_result["text"] == "report_answer") {
				$output .= esc_html__("You have reported an answer.","wpqa");
			}else if ($whats_type_result["text"] == "select_best_answer") {
				$output .= esc_html__("You have chosen the best answer.","wpqa");
			}else if ($whats_type_result["text"] == "cancel_best_answer") {
				$output .= esc_html__("You have canceled the best answer.","wpqa");
			}else if ($whats_type_result["text"] == "closed_question") {
				$output .= esc_html__("You have closed the question.","wpqa");
			}else if ($whats_type_result["text"] == "opend_question") {
				$output .= esc_html__("You have opend the question.","wpqa");
			}else if ($whats_type_result["text"] == "follow_question") {
				$output .= esc_html__("You have followed the question.","wpqa");
			}else if ($whats_type_result["text"] == "unfollow_question") {
				$output .= esc_html__("You have unfollowed the question.","wpqa");
			}else if ($whats_type_result["text"] == "question_favorites") {
				$output .= esc_html__("You have added a question at favorites.","wpqa");
			}else if ($whats_type_result["text"] == "question_remove_favorites") {
				$output .= esc_html__("You have removed a question from favorites.","wpqa");
			}else if ($whats_type_result["text"] == "add_answer") {
				$output .= esc_html__("You have added an answer.","wpqa");
			}else if ($whats_type_result["text"] == "add_comment") {
				$output .= esc_html__("You have added a comment.","wpqa");
			}else if ($whats_type_result["text"] == "approved_answer") {
				$output .= esc_html__("Your answere is pending for review.","wpqa");
			}else if ($whats_type_result["text"] == "approved_comment") {
				$output .= esc_html__("Your comment is pending for review.","wpqa");
			}else if ($whats_type_result["text"] == "add_question") {
				$output .= esc_html__("Added a new question.","wpqa");
			}else if ($whats_type_result["text"] == "add_post") {
				$output .= esc_html__("Add a new post.","wpqa");
			}else if ($whats_type_result["text"] == "approved_question") {
				$output .= esc_html__("Your question is pending for review.","wpqa");
			}else if ($whats_type_result["text"] == "approved_message") {
				$output .= esc_html__("Your message is pending for review.","wpqa");
			}else if ($whats_type_result["text"] == "approved_post") {
				$output .= esc_html__("Your post is pending for review.","wpqa");
			}else if ($whats_type_result["text"] == "approved_category") {
				$output .= esc_html__("Your category is pending for review.","wpqa");
			}else if ($whats_type_result["text"] == "delete_question") {
				$output .= esc_html__("You have deleted a question.","wpqa");
			}else if ($whats_type_result["text"] == "delete_post") {
				$output .= esc_html__("You have deleted a post.","wpqa");
			}else if ($whats_type_result["text"] == "delete_answer") {
				$output .= esc_html__("You have deleted an answer.","wpqa");
			}else if ($whats_type_result["text"] == "delete_comment") {
				$output .= esc_html__("You have deleted a comment.","wpqa");
			}else if ($whats_type_result["text"] == "add_message") {
				$output .= esc_html__("You have sent a message for","wpqa");
			
				if (!empty($whats_type_result["another_user_id"]) || !empty($whats_type_result["username"])) {
					if (isset($display_name) && $display_name != "") {
						if (!empty($whats_type_result["another_user_id"])) {
							$output .= ' <a href="'.esc_url($wpqa_profile_url).'">'.esc_html($display_name).'</a>.';
						}
						if (!empty($whats_type_result["username"])) {
							$output .= esc_html($whats_type_result["username"]).".";
						}
					}else {
						$output .= esc_html__("Delete user","wpqa").".";
					}
				}
			}else if ($whats_type_result["text"] == "delete_inbox_message") {
				$output .= esc_html__("You have deleted your inbox message","wpqa");
			}else if ($whats_type_result["text"] == "delete_send_message") {
				$output .= esc_html__("You have deleted your sent message","wpqa");
			}else {
				$output .= $whats_type_result["text"];
			}
		if ((!empty($whats_type_result["post_id"]) && !empty($whats_type_result["comment_id"]) && $get_post_status != "trash" && isset($get_comment) && $get_comment->comment_approved != "spam" && $get_comment->comment_approved != "trash") || (!empty($whats_type_result["post_id"]) && empty($whats_type_result["comment_id"]) && $get_post_status != "trash" && isset($get_the_permalink) && $get_the_permalink != "")) {
			$output .= '</a>';
		}
		if (is_super_admin($whats_type_result["user_id"]) && !empty($whats_type_result["post_id"]) && !empty($whats_type_result["comment_id"])) {
			if (isset($get_comment) && $get_comment->comment_approved == "spam") {
				$output .= " ".esc_html__('( Spam )','wpqa');
			}else if ($get_post_status == "trash" || (isset($get_comment) && $get_comment->comment_approved == "trash")) {
				$output .= " ".esc_html__('( Trashed )','wpqa');
			}else if (empty($get_comment)) {
				$output .= " ".esc_html__('( Deleted )','wpqa');
			}
		}
		if (is_super_admin($whats_type_result["user_id"]) && !empty($whats_type_result["post_id"]) && empty($whats_type_result["comment_id"])) {
			if ($get_post_status == "trash") {
				$output .= " ".esc_html__('( Trashed )','wpqa');
			}else if (empty($get_the_permalink)) {
				$output .= " ".esc_html__('( Deleted )','wpqa');
			}
		}
		if (!empty($whats_type_result["more_text"])) {
			$output .= " - ".esc_attr($whats_type_result["more_text"]).".";
		}
		if (($whats_type_result["text"] == "ban_user" || $whats_type_result["text"] == "unban_user" || $whats_type_result["text"] == "user_follow" || $whats_type_result["text"] == "user_unfollow") && !empty($whats_type_result["another_user_id"])) {
			$output .= ' <a href="'.wpqa_profile_url($whats_type_result["another_user_id"]).'">'.get_the_author_meta('display_name',$whats_type_result["another_user_id"]).'</a>.';
		}
		
		if ($show_date == "on") {
			$date_format = wpqa_options("date_format");
			$date_format = ($date_format?$date_format:get_option("date_format"));
			$time_format = wpqa_options("time_format");
			$time_format = ($time_format?$time_format:get_option("time_format"));
			$output .= "<span class='notifications-date'>".sprintf(esc_html__('%1$s at %2$s','wpqa'),mysql2date($date_format,date($date_format,$whats_type_result["time"])),mysql2date($time_format,date($time_format,$whats_type_result["time"])))."</span>
			</div></li>";
		}
		return $output;	
	}
endif;
/* Show activities li */
if (!function_exists('wpqa_get_activities')) :
	function wpqa_get_activities($user_id,$item_number,$more_button) {
		$output = '<div>
		<ul>';
		$_activities = get_user_meta($user_id,$user_id."_activities",true);
		
		for ($activities = 1; $activities <= $_activities; $activities++) {
			$notification_one[] = get_user_meta($user_id,$user_id."_activities_".$activities);
		}
		if (isset($notification_one) && is_array($notification_one)) {
			$notification = array_reverse($notification_one);
			$end = (sizeof($notification) < $item_number) ? sizeof($notification) : $item_number;
			for ($i=0;$i < $end ;++$i) {
				$notification_result = $notification[$i][0];
				if (!empty($notification_result["another_user_id"])) {
					$wpqa_profile_url = wpqa_profile_url($notification_result["another_user_id"]);
					$display_name = get_the_author_meta('display_name',$notification_result["another_user_id"]);
				}
				$output .= wpqa_show_activities($notification_result,"on");
			}
			$output .= "</ul>";
			if (isset($more_button) && $more_button == "on") {
				$output .= "<a href='".esc_url(wpqa_get_profile_permalink($user_id,"activities"))."'>".esc_html__("Show all activities.","wpqa")."</a>";
			}
		}else {
			$output .= "<li><div>".esc_html__("There are no activities yet.","wpqa")."</div></li></ul>";
		}
		$output .= '</div>';
		return $output;
	}
endif;
/* Comments */
if (!function_exists('wpqa_comments')) :
	function wpqa_comments($args = array()) {
		$defaults = array(
			'post_or_question' => 'post',
			'comments_number'  => '5',
			'comment_excerpt'  => '20',
			'show_images'      => 'on',
			'display_date'     => 'on',
			'specific_date'    => '',
		);
		
		$args = wp_parse_args($args,$defaults);
		
		$post_or_question = $args['post_or_question'];
		$comments_number  = $args['comments_number'];
		$comment_excerpt  = $args['comment_excerpt'];
		$show_images      = $args['show_images'];
		$display_date     = $args['display_date'];
		$specific_date    = $args['specific_date'];

		if (isset($specific_date)) {
			if ($specific_date == "24" || $specific_date == "48" || $specific_date == "72" || $specific_date == "96" || $specific_date == "120" || $specific_date == "144") {
				$specific_date = $specific_date." hours";
			}else if ($specific_date == "week" || $specific_date == "month" || $specific_date == "year") {
				$specific_date = "1 ".$specific_date;
			}
		}
		$specific_date_array = (isset($specific_date) && $specific_date != "" && $specific_date != "all"?array('date_query' => array(array('after' => $specific_date.' ago'))):array());

		$comments = get_comments(array_merge($specific_date_array,array("post_type" => $post_or_question,"status" => "approve","number" => $comments_number,"meta_query" => array(array("key" => "answer_question_user","compare" => "NOT EXISTS")))));?>
		<div class="user-notifications user-profile-area">
			<div>
				<ul>
					<?php foreach ($comments as $comment) {
						$comment_user_id = $comment->user_id;
						$user = get_user_by('id',$comment_user_id);
						$anonymously_user = get_comment_meta($comment->comment_ID,'anonymously_user',true);
						$deleted_user = ($comment_user_id > 0 && isset($user->display_name)?$user->display_name:($comment_user_id == 0?$comment->comment_author:"delete"));
						$comment_author_name = ($deleted_user == "delete"?esc_html__("[Deleted User]","wpqa"):($anonymously_user > 0 || $anonymously_user == "anonymously"?esc_html__('Anonymous','wpqa'):$deleted_user));
						$user_profile_page = wpqa_profile_url($comment_user_id);?>
						<li>
							<?php if ($show_images == "on") {?>
								<span class="span-icon">
									<?php if ($comment_user_id > 0) {?>
										<a href="<?php echo esc_url($user_profile_page)?>">
									<?php }
										echo wpqa_get_user_avatar(array("user_id" => ($comment_user_id > 0?$comment_user_id:$comment->comment_author_email),"size" => 25,"user_name" => $comment->comment_author));
									if ($comment_user_id > 0) {?>
										</a>
									<?php }?>
								</span>
							<?php }?>
							<div>
								<?php echo ($comment_user_id > 0?"<a href='".esc_url($user_profile_page)."'>":"").$comment_author_name.($comment_user_id > 0?"</a>":"") ." ". ($post_or_question == "question"?esc_html__("added an answer","wpqa"):esc_html__("added a comment","wpqa")) ?> <span class="question-title"><a href="<?php echo get_permalink($comment->comment_post_ID);?>#comment-<?php echo (int)$comment->comment_ID;?>"><?php echo wp_trim_words($comment->comment_content,$comment_excerpt);?></a></span>
								<?php if ($display_date == "on") {
									$time_format = wpqa_options("time_format");
									$time_format = ($time_format?$time_format:get_option("time_format"));
									$date_format = wpqa_options("date_format");
									$date_format = ($date_format?$date_format:get_option("date_format"));?>
									<span class="notifications-date"><?php printf(esc_html__('%1$s at %2$s','wpqa'),get_comment_date($date_format,$comment->comment_ID),get_comment_date($time_format,$comment->comment_ID))?></span>
								<?php }?>
							</div>
						</li>
					    <?php
					}?>
				</ul>
			</div>
		</div>
	<?php }
endif;
/* Posts */
if (!function_exists('wpqa_posts')) :
	function wpqa_posts($args = array()) {
		$defaults = array(
			'posts_per_page'      => '5',
			'orderby'             => '',
			'excerpt_title'       => '5',
			'show_images'         => 'on',
			'post_or_question'    => 'post',
			'display_comment'     => 'on',
			'display'             => '',
			'category'            => '',
			'categories'          => array(),
			'e_categories'        => array(),
			'custom_posts'        => '',
			'display_question'    => '',
			'category_question'   => '',
			'categories_question' => array(),
			'e_cats_question'     => array(),
			'custom_questions'    => '',
			'custom_args'         => array(),
			'no_query'            => '',
			'display_image'       => 'on',
			'display_video'       => 'on',
			'display_date'        => 'on',
			'blog_h_button'       => '',
			'blog_h_button_text'  => esc_html__('Explore Our Blog','wpqa'),
			'blog_h_page'         => '',
			'blog_h_link'         => '',
			'post_style'          => 'style_1',
			'excerpt_post'        => '40',
			'specific_date'       => '',
		);
		
		$args = wp_parse_args($args,$defaults);
		
		$posts_per_page      = $args['posts_per_page'];
		$orderby             = $args['orderby'];
		$excerpt_title       = $args['excerpt_title'];
		$show_images         = $args['show_images'];
		$post_or_question    = $args['post_or_question'];
		$display_comment     = $args['display_comment'];
		$display             = $args['display'];
		$category            = $args['category'];
		$categories          = $args['categories'];
		$e_categories        = $args['e_categories'];
		$custom_posts        = $args['custom_posts'];
		$display_question    = $args['display_question'];
		$category_question   = $args['category_question'];
		$categories_question = $args['categories_question'];
		$e_cats_question     = $args['e_cats_question'];
		$custom_questions    = $args['custom_questions'];
		$custom_args         = $args['custom_args'];
		$no_query            = $args['no_query'];
		$display_image       = $args['display_image'];
		$display_video       = $args['display_video'];
		$display_date        = $args['display_date'];
		$blog_h_button       = $args['blog_h_button'];
		$blog_h_button_text  = $args['blog_h_button_text'];
		$blog_h_page         = $args['blog_h_page'];
		$blog_h_link         = $args['blog_h_link'];
		$post_style          = $args['post_style'];
		$excerpt_post        = $args['excerpt_post'];
		$specific_date       = $args['specific_date'];
		
		global $post;
		if (empty($custom_args)) {
			$get_current_user_id = get_current_user_id();
			$question_meta_query = array("key" => "user_id","compare" => "NOT EXISTS");
			$advanced_queries = wpqa_options("advanced_queries");
			if ($advanced_queries == "on" && !is_super_admin($get_current_user_id)) {
				$question_meta_query = array(
					$question_meta_query,array(
						'relation' => 'OR',
						array("key" => "private_question","compare" => "NOT EXISTS"),
						array("key" => "private_question","compare" => "=","value" => 0),
						array(
							'relation' => 'AND',
							array("key" => "private_question","compare" => "EXISTS"),
							array("key" => "private_question_author","compare" => "=","value" => $get_current_user_id),
						)
					)
				);
			}
			
			$user_array_question = array("meta_query" => array($question_meta_query));
			if ($orderby == "popular") {
				$orderby_array = array_merge($user_array_question,array('orderby' => 'comment_count'));
			}else if ($orderby == "random") {
				$orderby_array = array_merge($user_array_question,array('orderby' => 'rand'));
			}else if ($orderby == "most_visited") {
				$post_meta_stats = wpqa_options("post_meta_stats");
				$post_meta_stats = ($post_meta_stats != ""?$post_meta_stats:"post_stats");
				$orderby_array = array('orderby' => array('post_stats_order' => "DESC"),"meta_query" => array('relation' => 'AND','user_ques_slug_order' => $question_meta_query,'post_stats_order' => array('type' => 'numeric',"key" => $post_meta_stats,"value" => 0,"compare" => ">=")));
			}else if ($orderby == "most_voted") {
				$orderby_array = array('orderby' => array('question_vote_order' => "DESC"),"meta_query" => array('relation' => 'AND','user_ques_slug_order' => $question_meta_query,'question_vote_order' => array('type' => 'numeric',"key" => "question_vote","value" => 0,"compare" => ">=")));
			}else if ($orderby == "most_rated") {
				$orderby_array = array("orderby" => "meta_value_num","meta_key" => "final_review","meta_query" => array(array('type' => 'numeric',"key" => "final_review","value" => 0,"compare" => ">=")));
			}else {
				$orderby_array = $user_array_question;
			}
			
			if ($post_or_question == "post") {
				$display      = $display;
				$category     = $category;
				$categories   = $categories;
				$e_categories = $e_categories;
				$custom_posts = $custom_posts;
				$taxonomy     = "category";
			}else if ($post_or_question == "question") {
				$display      = $display_question;
				$category     = $category_question;
				$categories   = $categories_question;
				$e_categories = $e_cats_question;
				$custom_posts = $custom_questions;
				$taxonomy     = "question-category";
			}
			
			$categories_a = $exclude_categories_a = array();
			if (isset($categories) && is_array($categories)) {
				$categories_a = $categories;
			}
			
			if (isset($e_categories) && is_array($e_categories)) {
				$exclude_categories_a = $e_categories;
			}
			
			if ($display == "category") {
				$cat_query = array('tax_query' => array(array('taxonomy' => $taxonomy,'field' => 'id','terms' => $category,'operator' => 'IN')));
			}else if ($display == "categories") {
				$cat_query = array('tax_query' => array(array('taxonomy' => $taxonomy,'field' => 'id','terms' => $categories_a,'operator' => 'IN')));
			}else if ($display == "exclude_categories") {
				$cat_query = array('tax_query' => array(array('taxonomy' => $taxonomy,'field' => 'id','terms' => $exclude_categories_a,'operator' => 'NOT IN')));
			}else if ($display == "custom_posts") {
				$custom_posts = explode(",",$custom_posts);
				$cat_query = array('post__in' => $custom_posts);
			}else {
				$cat_query = array();
			}
		}

		if ($specific_date == "24" || $specific_date == "48" || $specific_date == "72" || $specific_date == "96" || $specific_date == "120" || $specific_date == "144") {
			$specific_date = $specific_date." hours";
		}else if ($specific_date == "week" || $specific_date == "month" || $specific_date == "year") {
			$specific_date = "1 ".$specific_date;
		}

		$specific_date_array = ($specific_date != "" && $specific_date != "all"?array('date_query' => array(array('after' => $specific_date.' ago'))):array());
		$comment_count = ($orderby == "no_response"?array("comment_count" => "0"):array());
		
		$args = (empty($custom_args)?array_merge($comment_count,$specific_date_array,$orderby_array,$cat_query,array('post_type' => $post_or_question,'ignore_sticky_posts' => 1,'cache_results' => false,'no_found_rows' => true,'posts_per_page' => $posts_per_page)):$custom_args);

		$related_query = new WP_Query( $args );
		$out = '';
		if ($related_query->have_posts()) :
			$out .= '<div class="user-notifications user-profile-area'.($post_style == "style_2"?" widget-post-style-2".($display_image === "on"?" post-style-2-image":""):"").'">
				<div>
					<ul>';
						while ( $related_query->have_posts() ) : $related_query->the_post();
							$what_post = get_post_meta($post->ID,'what_post',true);
							$video_type = get_post_meta($post->ID,'video_post_type',true);
							$out .= '<li class="widget-posts-';if (is_sticky()) {$out .= 'sticky';}else if ($what_post == "google") {$out .= 'google';}else if ($what_post == "audio") {$out .= 'volume-up';}else if ($what_post == "video") {if ($video_type == 'youtube') {$out .= 'youtube';}else if ($video_type == 'vimeo') {$out .= 'vimeo';}else if ($video_type == 'daily' || $video_type == 'embed' || $video_type == 'html5' || $video_type == 'facebook') {$out .= 'daily';}}else if ($what_post == "slideshow") {$out .= 'slideshow';}else if ($what_post == "quote") {$out .= 'quote';}else if ($what_post == "link") {$out .= 'link';}else if ($what_post == "soundcloud") {$out .= 'soundcloud';}else if ($what_post == "twitter") {$out .= 'twitter';}else if ($what_post == "facebook") {$out .= 'facebook';}else if ($what_post == "instagram") {$out .= 'instagram';}else {if (has_post_thumbnail()) {$out .= 'image';}else {$out .= 'text';}}$out .= (has_post_thumbnail()?'':' widget-no-img').($display_comment === "on" || ($post_style == "style_2" && $display_date === "on")?'':' widget-no-meta').'">';
								$video_description = get_post_meta($post->ID,"video_description",true);
								if ($post_style == "style_2" && $display_video === "on" && ($what_post == "video" || $video_description == "on")) {
									if ($post_or_question == "question") {
										$ask_question_items = wpqa_options("ask_question_items");
										$video_desc_active = (isset($ask_question_items["video_desc_active"]["value"]) && $ask_question_items["video_desc_active"]["value"] == "video_desc_active"?"on":"");
										if ($video_desc_active == "on" && $video_description == "on") {
											$video_desc = get_post_meta($post->ID,'video_desc',true);
											$video_id = get_post_meta($post->ID,"video_id",true);
											$video_type = get_post_meta($post->ID,"video_type",true);
											if ($video_id != "") {
												if ($video_type == 'youtube') {
													preg_match("/^(?:http(?:s)?:\/\/)?(?:www\.)?(?:m\.)?(?:youtu\.be\/|youtube\.com\/(?:(?:watch)?\?(?:.*&)?v(?:i)?=|(?:embed|v|vi|user)\/))([^\?&\"'>]+)/",$video_id,$matches);
													if (isset($matches[1])) {
														$video_id = $matches[1];
													}
													$type = "https://www.youtube.com/embed/".$video_id;
												}else if ($video_type == 'vimeo') {
													preg_match("/^(?:http(?:s)?:\/\/)?(?:www\.)?(player\.)?vimeo\.com\/([a-z]*\/)*([0-9]{6,11})[?]?.*/",$video_id,$matches);
													if (isset($matches[3])) {
														$video_id = $matches[3];
													}
													$type = "https://player.vimeo.com/video/".$video_id;
												}else if ($video_type == 'daily') {
													preg_match("!^.+dailymotion\.com/(video|hub)/([^_]+)[^#]*(#video=([^_&]+))?|(dai\.ly/([^_]+))!",$video_id,$matches);
													if (isset($matches[2])) {
														$video_id = $matches[2];
													}
													$type = "https://www.dailymotion.com/embed/video/".$video_id;
												}else if ($video_type == 'facebook') {
													$type = "https://www.facebook.com/video/embed?video_id=".$video_id;
												}
												$las_video = '<iframe frameborder="0" allowfullscreen height="155" src="'.$type.'"></iframe>';
												$out .= '<div class="question-video-widget">'.$las_video.'</div>';
											}
										}
									}else if ($what_post == "video") {
										$video_id = get_post_meta($post->ID,wpqa_meta.'video_post_id',true);
										if ($video_id != "") {
											if ($video_type == 'youtube') {
												preg_match("/^(?:http(?:s)?:\/\/)?(?:www\.)?(?:m\.)?(?:youtu\.be\/|youtube\.com\/(?:(?:watch)?\?(?:.*&)?v(?:i)?=|(?:embed|v|vi|user)\/))([^\?&\"'>]+)/",$video_id,$matches);
												if (isset($matches[1])) {
													$video_id = $matches[1];
												}
												$type = "https://www.youtube.com/embed/".$video_id;
											}else if ($video_type == 'vimeo') {
												preg_match("/^(?:http(?:s)?:\/\/)?(?:www\.)?(player\.)?vimeo\.com\/([a-z]*\/)*([0-9]{6,11})[?]?.*/",$video_id,$matches);
												if (isset($matches[3])) {
													$video_id = $matches[3];
												}
												$type = "https://player.vimeo.com/video/".$video_id;
											}else if ($video_type == 'daily') {
												preg_match("!^.+dailymotion\.com/(video|hub)/([^_]+)[^#]*(#video=([^_&]+))?|(dai\.ly/([^_]+))!",$video_id,$matches);
												if (isset($matches[2])) {
													$video_id = $matches[2];
												}
												$type = "https://www.dailymotion.com/embed/video/".$video_id;
											}else if ($video_type == 'facebook') {
												$type = "https://www.facebook.com/video/embed?video_id=".$video_id;
											}
										}
										$video_mp4 = get_post_meta($post->ID,wpqa_meta."video_mp4",true);
										$video_m4v = get_post_meta($post->ID,wpqa_meta."video_m4v",true);
										$video_webm = get_post_meta($post->ID,wpqa_meta."video_webm",true);
										$video_ogv = get_post_meta($post->ID,wpqa_meta."video_ogv",true);
										$video_wmv = get_post_meta($post->ID,wpqa_meta."video_wmv",true);
										$video_flv = get_post_meta($post->ID,wpqa_meta."video_flv",true);
										$video_image = get_post_meta($post->ID,wpqa_meta."video_image",true);
										$video_mp4 = (isset($video_mp4) && $video_mp4 != ""?" mp4='".$video_mp4."'":"");
										$video_m4v = (isset($video_m4v) && $video_m4v != ""?" m4v='".$video_m4v."'":"");
										$video_webm = (isset($video_webm) && $video_webm != ""?" webm='".$video_webm."'":"");
										$video_ogv = (isset($video_ogv) && $video_ogv != ""?" ogv='".$video_ogv."'":"");
										$video_wmv = (isset($video_wmv) && $video_wmv != ""?" wmv='".$video_wmv."'":"");
										$video_flv = (isset($video_flv) && $video_flv != ""?" flv='".$video_flv."'":"");
										$video_image = (isset($video_image) && $video_image != ""?" poster='".wpqa_image_url_id($video_image)."'":"");
										if ($video_type == "html5") {
											$out .= do_shortcode('[video'.$video_mp4.$video_m4v.$video_webm.$video_ogv.$video_wmv.$video_flv.$video_image.']');
										}else if ($video_type == "embed") {
											$out .= get_post_meta($post->ID,"custom_embed",true);
										}else if (isset($type) && $type != "") {
											$las_video = '<iframe frameborder="0" allowfullscreen height="155" src="'.$type.'"></iframe>';
											$out .= '<div class="question-video-widget">'.$las_video.'</div>';
										}
									}
								}else if ($post_style == "style_2" && $display_image === "on") {
									$out .= '<div class="widget-post-image"><a href="'.get_permalink().'" title="'.sprintf('%s', the_title_attribute('echo=0')).'" rel="bookmark">';
									$img_width = "229";
									$img_height = "155";
									if (has_post_thumbnail()) {
										$out .= apply_filters("wpqa_filter_image_widget",wpqa_get_aq_resize_img($img_width,$img_height),$post,$img_width,$img_height);
									}else {
										$wpqa_image = wpqa_image();
										if (!is_single() && !empty($wpqa_image)) {
											$out .= "<img alt='".get_the_title()."' src='".wpqa_get_aq_resize_url(wpqa_image(),$img_width,$img_height)."'>";
										}
									}
									$out .= '</a></div>';
								}
								if ($post_style != "style_2") {
									if ($post->post_author > 0) {
										$user_name = get_the_author_meta("display_name",$post->post_author);
										$user_id = $post->post_author;
									}else {
										$user_id = get_post_meta($post->ID,$post_or_question.'_email',true);
										$anonymously_user     = get_post_meta($post->ID,"anonymously_user",true);
										$anonymously_question = get_post_meta($post->ID,"anonymously_question",true);
										if (($anonymously_question == "on" || $anonymously_question == 1) && $anonymously_user != "") {
											$user_name = esc_html__('Anonymous','wpqa');
										}else {
											$user_name = get_post_meta($post->ID,$post_or_question."_username",true);
											$user_name = ($user_name != ""?$user_name:esc_html__('Anonymous','wpqa'));
										}
									}
									$user_profile_page = wpqa_profile_url($user_id);
									if ($show_images === "on") {
										$out .= '<span class="span-icon">';
											if ($user_id > 0) {
												$out .= '<a href="'.esc_url($user_profile_page).'">';
											}
											$out .= wpqa_get_user_avatar(array("user_id" => $user_id,"size" => 20,"user_name" => $user_name));
											if ($user_id > 0) {
												$out .= '</a>';
											}
										$out .= '</span>';
									}
								}
								$out .= '<div>';
									if ($post_style == "style_2") {
										$sort_title_meta = array("meta","title");
									}else {
										$sort_title_meta = array("title","meta");
									}
									foreach ($sort_title_meta as $key => $value) {
										if ($value == "title") {
											$out .= '<h3><a href="'.get_permalink().'" title="'.sprintf('%s', the_title_attribute('echo=0')).'" rel="bookmark">'.wpqa_excerpt_title($excerpt_title,wpqa_excerpt_type,"return").'</a></h3>';
											if ($post_style == "style_2" && $excerpt_post > 0) {
												$out .= '<p>'.wpqa_excerpt($excerpt_post,wpqa_excerpt_type,"return").'</p>';
											}
										}else if ($value == "meta") {
											if ($display_comment === "on" || ($post_style == "style_2" && $display_date === "on")) {
												$out .= '<ul class="widget-post-meta">';
												if ($post_style == "style_2" && $display_date === "on") {
													$out .= '<li><span class="post-meta-date">';
														$time_string = '<time class="entry-date published" datetime="%1$s">%2$s</time>';
														$date_format = wpqa_options("date_format");
														$date_format = ($date_format?$date_format:get_option("date_format"));
														$time_string = sprintf($time_string,esc_attr(get_the_date('c')),esc_html(get_the_time($date_format)));
														$data_string = esc_html__("On","wpqa");
														$posted_on   = $data_string.': '.$time_string;
														$out .= $posted_on;
													$out .= '</span></li>';
												}
												if ($display_comment === "on") {
													$num_comments = (int)get_comments_number();
													$out .= '<li><a class="post-meta-comment" href="'.get_comments_link().'">';
														if ($post_style == "style_2") {
															$comment_string = ($post_or_question == "question"?_n("Answer","Answers",$num_comments,"wpqa"):_n("Comment","Comments",$num_comments,"wpqa"));
															$comments = $comment_string.': '.$num_comments;
														}else if ($post_style != "style_2") {
															$comment_string = ($post_or_question == "question"?sprintf(_n("%s Answer","%s Answers",$num_comments,"wpqa"),$num_comments):sprintf(_n("%s Comment","%s Comments",$num_comments,"wpqa"),$num_comments));
															$out .= '<i class="icon-comment"></i>';
															$comments = $comment_string;
														}
														$out .= $comments;
													$out .= '</a></li>';
												}
												$out .= '</ul>';
											}
										}
									}
								$out .= '</div>
							</li>';
						endwhile;
					$out .= '</ul>';
					if ($post_or_question == "post" && $post_style == "style_2" && $blog_h_button === "on") {
						$out .= '<div class="blog-post-button"><a href="'.esc_url(($blog_h_link != ""?$blog_h_link:($blog_h_page != "" && $blog_h_page > 0?get_page_link($blog_h_page):""))).'" class="button-default">'.($blog_h_button_text != ""?$blog_h_button_text:esc_html__("Explore Our Blog","wpqa")).'</a></div>';
					}
				$out .= '</div>
			</div>';
		else : $out .= (isset($no_query) && $no_query == "no_query"?"no_query":"");endif;
		wp_reset_postdata();
		return $out;
	}
endif;
/* Author */
if (!function_exists('wpqa_author')) :
	function wpqa_author($author_id,$author_page = "",$owner = "",$type_post = "",$widget = "",$class = "",$cover = "",$category = "",$show_icon = "",$specific_time = "") {
		$type_post = ($category !== ""?"points":$type_post);
		if (isset($author_id) && $author_id > 0) {
			$follow_email = get_the_author_meta('follow_email',$author_id);
			if (isset($follow_email) && $follow_email != "" && $follow_email != 0 && $follow_email != "on") {
				update_user_meta($author_id,'follow_email',"on");
				$follow_email = get_the_author_meta('follow_email',$author_id);
			}
			if ($cover == "") {
				$active_points = wpqa_options("active_points");
				
				if ($author_page == "grid" || $author_page == "grid_pop" || $author_page == "small" || $author_page == "simple_follow" || $author_page == "columns" || $author_page == "columns_pop") {
					/* questions */
					$questions_count = wpqa_count_posts_by_user($author_id,"question","publish",($category !== ""?$category:0));
					/* answers */
					$answers_count = count(get_comments(array("post_type" => "question","status" => "approve","user_id" => $author_id)));
					
					/* the_best_answer */
					$the_best_answer = count(get_comments(array('user_id' => $author_id,"status" => "approve",'post_type' => 'question',"meta_query" => array('relation' => 'AND',array("key" => "best_answer_comment","compare" => "=","value" => "best_answer_comment"),array("key" => "answer_question_user","compare" => "NOT EXISTS")))));
					
					/* points */
					$points_type = "points";
					if ($specific_time == "day") {
						$points_type = "points_date_".date("j_n_Y");
					}else if ($specific_time == "week") {
						$points_type = "points_date_".date("W_j_Y");
					}else if ($specific_time == "month") {
						$points_type = "points_date_".date("n_Y");
					}else if ($specific_time == "year") {
						$points_type = "points_date_".date("Y");
					}
					$points = (int)get_user_meta($author_id,$points_type,true);
					if ($category !== "") {
						$points_type = "points_category".$category;
						if ($specific_time == "day") {
							$points_type = "points_category".$category."_date_".date("j_n_Y");
						}else if ($specific_time == "week") {
							$points_type = "points_category".$category."_date_".date("W_j_Y");
						}else if ($specific_time == "month") {
							$points_type = "points_category".$category."_date_".date("n_Y");
						}else if ($specific_time == "year") {
							$points_type = "points_category".$category."_date_".date("Y");
						}
						$points_category_user = ($category !== ""?(int)get_user_meta($author_id,$points_type,true):"");
						$points = $points_category_user;
					}
					
					/* posts */
					$posts_count = wpqa_count_posts_by_user($author_id,"post");
					
					/* comments */
					$comments_count = count(get_comments(array("post_type" => "post","status" => "approve","user_id" => $author_id)));
				}
			}
			$author_display_name = get_the_author_meta("display_name",$author_id);
			$out = '<div class="post-section user-area user-area-'.$author_page.($author_page == "advanced"?" user-advanced":"").($class != ""?" ".$class:"").'">
				<div class="post-inner">';
					if ($cover == "") {
						if ($author_page == "advanced") {
							$out .= '<div class="user-head-area">';
						}
						if ($author_page == "advanced") {
							$message_button = wpqa_message_button($author_id,$cover,$owner);
							$out .= $message_button;
						}
						
						$out .= wpqa_get_avatar_link(array("user_id" => $author_id,"user_name" => $author_display_name,"size" => apply_filters("wpqa_filter_avatar_size",($author_page == "small"?42:($author_page == "columns" || $author_page == "columns_pop"?70:84)),$author_page,$class),"span" => "span"));
						if ($author_page == "advanced" && (isset($message_button) && $message_button != "") && $owner == false) {
							$out .= wpqa_following($author_id,"",$owner,"login");
						}
						if ($author_page == "advanced") {
							$out .= '</div>';
						}
					}
					$credential = get_the_author_meta('profile_credential',$author_id);
					$out .= '<div class="user-content">
						<div class="user-inner">';
							if ($author_page == "columns" || $author_page == "columns_pop") {
								$out .= '<div class="user-data-columns">';
							}
							
							if ($cover == "") {
								$out .= '<h4><a href="'.esc_url(wpqa_profile_url($author_id)).'">'.$author_display_name.'</a>'.wpqa_verified_user($author_id).'</h4>';
								
								if ($credential != "" && $author_page != "small_grid" && $author_page != "grid" && $author_page != "grid_pop" && $author_page != "small" && $author_page != "simple_follow" && $author_page != "columns" && $author_page != "columns_pop") {
									$out .= '<span class="profile-credential">'.esc_html($credential).'</span>';
								}
								
								if ($author_page != "grid_pop" && $author_page != "small" && $author_page != "columns_pop") {
									$active_points_category = wpqa_options("active_points_category");
									if ($active_points_category != "on") {
										$out .= wpqa_get_badge($author_id);
									}
								}
								
								if ($author_page == "columns_pop") {
									$country = get_the_author_meta('country',$author_id);
									$city    = get_the_author_meta('city',$author_id);
									$get_countries = apply_filters('wpqa_get_countries',false);
									if ((isset($credential) && $credential != "") || (isset($city) && $city != "") || (isset($country) && $country != "" && isset($get_countries[$country]))) {
										$out .= '<div class="user-data">
											<ul>';
												if (isset($credential) && $credential != "") {
													$out .= '<li class="profile-credential">
														'.esc_html($credential).'
													</li>';
												}else if ((isset($city) && $city != "") || (isset($country) && $country != "" && isset($get_countries[$country]))) {
													$out .= '<li class="city-country">
														<i class="icon-location"></i>
														'.(isset($city) && $city != ""?esc_html($city).", ":"").(isset($country) && $country != "" && isset($get_countries[$country])?$get_countries[$country]:"").'
													</li>';
												}
											$out .= '</ul>
										</div>';
									}
								}
								
								if ($author_page == "columns" || $author_page == "columns_pop") {
									$out .= '</div>';
								}
							}
							
							if ($author_page != "small_grid" && $author_page != "grid" && $author_page != "grid_pop" && $author_page != "small" && $author_page != "simple_follow" && $author_page != "columns" && $author_page != "columns_pop") {
								$meta_description = get_user_meta($author_id,"description",true);
								if ($meta_description != "") {
									$bio_editor = wpqa_options("bio_editor");
									if ($bio_editor == "on") {
										$out .= '<div class="bio_editor">'.wpqa_kses_stip(nl2br($meta_description,"yes")).'</div>';
									}else {
										$out .= '<p>'.nl2br($meta_description).'</p>';
									}
								}
							}
							
							if ($author_page == "advanced") {
								/* user data */
								$country    = get_the_author_meta('country',$author_id);
								$city       = get_the_author_meta('city',$author_id);
								$credential = get_the_author_meta('profile_credential',$author_id);
								$age        = get_the_author_meta('age',$author_id);
								$phone      = get_the_author_meta('phone',$author_id);
								$url        = get_the_author_meta('url',$author_id);
								$gender     = get_the_author_meta('gender',$author_id);
								if ((isset($credential) && $credential != "") || (isset($city) && $city != "") || (isset($country) && $country != "") || (isset($phone) && $phone != "") || (isset($url) && $url != "") || (isset($gender) && $gender != "") || (isset($age) && $age != "")) {
									$out .= '<div class="user-data">
										<ul>';
											$get_countries = apply_filters('wpqa_get_countries',false);
											if ((isset($city) && $city != "") || (isset($country) && $country != "" && isset($get_countries[$country]))) {
												$out .= '<li class="city-country">
													<i class="icon-location"></i>
													'.(isset($city) && $city != ""?$city.", ":"").(isset($country) && $country != "" && isset($get_countries[$country])?$get_countries[$country]:"").'
												</li>';
											}
											if (isset($phone) && $phone != "") {
												$out .= '<li class="user-phone">
													<i class="icon-phone"></i>
													'.esc_attr($phone).'
												</li>';
											}
											if (isset($url) && $url != "") {
												$out .= '<li class="user-url">
													<a href="'.esc_url($url).'">
														<i class="icon-link"></i>
														'.esc_html__("Visit site","wpqa").'
													</a>
												</li>';
											}
											if (isset($gender) && $gender != "") {
												$out .= '<li class="user-gender">
													<i class="icon-heart"></i>
													'.($gender == "male" || $gender == 1?esc_html__("Male","wpqa"):"").($gender == "female" || $gender == 2?esc_html__("Female","wpqa"):"").($gender == "other" || $gender == 3?esc_html__("Other","wpqa"):"").'
												</li>';
											}
											if (isset($age) && $age != "") {
												$age = (date_create($age)?date_diff(date_create($age),date_create('today'))->y:"");
												$out .= '<li class="user-age">
													<i class="icon-globe"></i>
													'.esc_attr($age)." ".esc_html__("years old","wpqa").'
												</li>';
											}
										$out .= '</ul>
									</div><!-- End user-data -->';
								}
								$out .= apply_filters("wpqa_profile_advanced_filter",false,$author_id);
							}
							
							if ($author_page == "grid" || $author_page == "grid_pop" || $author_page == "small" || $author_page == "simple_follow") {
								$out .= '<div class="user-data">
									<ul>';
										if ($type_post == "post" || $type_post == "comments") {
											if ((($show_icon == 'on' && $widget == 'widget' && $type_post == "post") || $show_icon != "on")) {
												$out .= '<li class="user-posts">
													<a href="'.esc_url(wpqa_get_profile_permalink($author_id,"posts")).'">
														'.($widget == 'widget' && $show_icon != 'on'?'':'<i class="icon-book-open"></i>').'
														'.wpqa_count_number($posts_count).' '._n("Post","Posts",$questions_count,"wpqa").'
													</a>
												</li>';
											}
											if ((($show_icon == 'on' && $widget == 'widget' && $type_post == "comments") || $show_icon != "on")) {
												$out .= '<li class="user-comments">
													<a href="'.esc_url(wpqa_get_profile_permalink($author_id,"comments")).'">
														'.($widget == 'widget' && $show_icon != 'on'?'':'<i class="icon-comment"></i>').'
														'.wpqa_count_number($comments_count).' '._n("Comment","Comments",$comments_count,"wpqa").'
													</a>
												</li>';
											}
										}else {
											if ((($show_icon == 'on' && $widget == 'widget' && $type_post == "question_count") || $show_icon != "on")) {
												$out .= '<li class="user-questions">
													<a href="'.esc_url(wpqa_get_profile_permalink($author_id,"questions")).'">
														'.($widget == 'widget' && $show_icon != 'on'?'':'<i class="icon-book-open"></i>').'
														'.wpqa_count_number($questions_count).' '._n("Question","Questions",$questions_count,"wpqa").'
													</a>
												</li>';
											}
											if ($type_post == "the_best_answer" && (($show_icon == 'on' && $widget == 'widget') || $show_icon != "on")) {
												$out .= '<li class="user-best-answers">
													<a href="'.esc_url(wpqa_get_profile_permalink($author_id,"best_answers")).'">
														'.($widget == 'widget' && $show_icon != 'on'?'':'<i class="icon-graduation-cap"></i>').'
														'.($the_best_answer == ""?0:wpqa_count_number($the_best_answer)).' '._n("Best Answer","Best Answers",$the_best_answer,"wpqa").'
													</a>
												</li>';
											}else if ($type_post == "points" && $active_points == "on" && (($show_icon == 'on' && $widget == 'widget') || $show_icon != "on")) {
												$out .= '<li class="user-points">
													<a href="'.esc_url(wpqa_get_profile_permalink($author_id,"points")).'">
														'.($widget == 'widget' && $show_icon != 'on'?'':'<i class="icon-bucket"></i>').'
														'.($points == ""?0:wpqa_count_number($points)).' '._n("Point","Points",$points,"wpqa").'
													</a>
												</li>';
											}else if ((($show_icon == 'on' && $widget == 'widget' && $type_post == "answers") || $show_icon != "on")) {
												$out .= '<li class="user-answers">
													<a href="'.esc_url(wpqa_get_profile_permalink($author_id,"answers")).'">
														'.($widget == 'widget' && $show_icon != 'on'?'':'<i class="icon-comment"></i>').'
														'.wpqa_count_number($answers_count).' '._n("Answer","Answers",$answers_count,"wpqa").'
													</a>
												</li>';
											}
										}
									$out .= '</ul>
								</div><!-- End user-data -->';
								
								if ($widget == "widget") {
									if ($category !== "") {
										$out .= apply_filters("wpqa_widget_before_badge",false,$category);
									}
									$out .= wpqa_get_badge($author_id,"",(isset($points_category_user) && $points_category_user !== ""?$points_category_user:""));
								}
							}
							
							if (($author_page == "small_grid" || $author_page == "simple_follow") && $owner == false) {
								$out .= wpqa_following($author_id,($author_page == "small_grid"?"style_4":""),$owner);
							}
						$out .= '</div>';
						
						if ($author_page != "small_grid" && $author_page != "grid" && $author_page != "grid_pop" && $author_page != "small" && $author_page != "simple_follow" && $author_page != "columns" && $author_page != "columns_pop") {
							$twitter    = get_the_author_meta('twitter',$author_id);
							$facebook   = get_the_author_meta('facebook',$author_id);
							$linkedin   = get_the_author_meta('linkedin',$author_id);
							$youtube    = get_the_author_meta('youtube',$author_id);
							$vimeo      = get_the_author_meta('vimeo',$author_id);
							$pinterest  = get_the_author_meta('pinterest',$author_id);
							$instagram  = get_the_author_meta('instagram',$author_id);
							$user_email = get_the_author_meta('email',$author_id);

							$get_current_user_id = get_current_user_id();
							$is_super_admin      = is_super_admin($get_current_user_id);
							$active_moderators   = wpqa_options("active_moderators");
							if ($active_moderators == "on") {
								$moderator_categories = get_user_meta($get_current_user_id,prefix_author."moderator_categories",true);
								$moderator_categories = (is_array($moderator_categories) && !empty($moderator_categories)?$moderator_categories:array());
								$pending_questions    = (($is_super_admin || $active_moderators == "on") && ($is_super_admin || (isset($moderator_categories) && is_array($moderator_categories) && !empty($moderator_categories)))?true:false);
								$moderators_permissions = wpqa_user_moderator($get_current_user_id);
								$if_user_id = get_user_by("id",$author_id);
							}
							if (($active_moderators == "on" && isset($if_user_id->roles[0]) && $if_user_id->roles[0] != "administrator" && $pending_questions == true && ($is_super_admin || (isset($moderators_permissions['ban']) && $moderators_permissions['ban'] == "ban"))) || ($user_email && isset($follow_email) && $follow_email == "on") || $facebook || $twitter || $linkedin || $youtube || $vimeo || $pinterest || $instagram || ($cover == "" && (!isset($message_button) || (isset($message_button) && $message_button == "")))) {
								$out .= '<div class="social-ul">
									<ul>';
										if ($author_page != "single-author" && ($cover == "" && (!isset($message_button) || (isset($message_button) && $message_button == ""))) && $owner == false) {
											$out .= '<li class="social-follow">'.wpqa_following($author_id,"style_3",$owner).'</li>';
											
										}
										if ($author_page != "single-author" && $cover == "" && $active_moderators == "on" && isset($if_user_id->roles[0]) && $if_user_id->roles[0] != "administrator" && $pending_questions == true && ($is_super_admin || (isset($moderators_permissions['ban']) && $moderators_permissions['ban'] == "ban"))) {
											$out .= "<li class='ban-unban-user'><span class='small_loader loader_2'></span><a class='".(isset($if_user_id->caps["ban_group"]) && $if_user_id->caps["ban_group"] == 1?"unban-user":"ban-user")."' data-nonce='".wp_create_nonce("ban_nonce")."' href='#' data-id='".$author_id."'><span>".(isset($if_user_id->caps["ban_group"]) && $if_user_id->caps["ban_group"] == 1?esc_html__("Unban user","wpqa"):esc_html__("Ban user","wpqa"))."</span></a></li>";
										}
										if ($facebook) {
											$out .= '<li class="social-facebook"><a title="Facebook" class="tooltip-n" href="'.esc_url($facebook).'" target="_blank"><i class="icon-facebook"></i></a></li>';
										}
										if ($twitter) {
											$out .= '<li class="social-twitter"><a title="Twitter" class="tooltip-n" href="'.esc_url($twitter).'" target="_blank"><i class="icon-twitter"></i></a></li>';
										}
										if ($linkedin) {
											$out .= '<li class="social-linkedin"><a title="Linkedin" class="tooltip-n" href="'.esc_url($linkedin).'" target="_blank"><i class="icon-linkedin"></i></a></li>';
										}
										if ($pinterest) {
											$out .= '<li class="social-pinterest"><a title="Pinterest" class="tooltip-n" href="'.esc_url($pinterest).'" target="_blank"><i class="icon-pinterest"></i></a></li>';
										}
										if ($instagram) {
											$out .= '<li class="social-instagram"><a title="Instagram" class="tooltip-n" href="'.esc_url($instagram).'" target="_blank"><i class="icon-instagrem"></i></a></li>';
										}
										if ($youtube) {
											$out .= '<li class="social-youtube"><a title="Youtube" class="tooltip-n" href="'.esc_url($youtube).'" target="_blank"><i class="icon-play"></i></a></li>';
										}
										if ($vimeo) {
											$out .= '<li class="social-vimeo"><a title="Vimeo" class="tooltip-n" href="'.esc_url($vimeo).'" target="_blank"><i class="icon-vimeo"></i></a></li>';
										}
										if ($user_email && isset($follow_email) && $follow_email == "on") {
											$out .= '<li class="social-email"><a title="'.esc_html__("Email","wpqa").'" class="tooltip-n" href="mailto:'.esc_attr($user_email).'" target="_blank" rel="nofollow"><i class="icon-mail"></i></a></li>';
										}
									$out .= '</ul>
								</div><!-- End social-ul -->';
							}
						}
					$out .= '</div><!-- End user-content -->';
					
					if ($author_page == "grid_pop" && $owner == false) {
						$out .= wpqa_following($author_id,"",$owner);
					}
					
					if ($cover == "" && $owner == false && $author_page != "single-author" && $author_page != "small_grid" && $author_page != "grid" && $author_page != "grid_pop" && $author_page != "small" && $author_page != "simple_follow" && $author_page != "columns" && $author_page != "columns_pop") {
						$ask_question_to_users = wpqa_options("ask_question_to_users");
						$breadcrumbs = wpqa_options("breadcrumbs");
						if ($ask_question_to_users == "on" && $breadcrumbs != "on") {
							$out .= '<div class="ask-question ask-user-after-social"><a href="'.esc_url(wpqa_add_question_permalink("user")).'" class="button-default ask-question-user">'.esc_html__("Ask","wpqa")." ".$author_display_name.'</a></div>';
						}
					}
					
					if ($author_page == "columns" || $author_page == "columns_pop") {
						$out .= '<div class="user-columns-data">
							<ul>';
								if ($type_post == "post" || $type_post == "comments") {
									$out .= '<li class="user-columns-posts">
										<a href="'.esc_url(wpqa_get_profile_permalink($author_id,"posts")).'">
											<i class="icon-book-open"></i>'.($posts_count == ""?0:wpqa_count_number($posts_count)).' '._n("Post","Posts",$posts_count,"wpqa").'
										</a>
									</li>
									<li class="user-columns-comments">
										<a href="'.esc_url(wpqa_get_profile_permalink($author_id,"comments")).'">
											<i class="icon-comment"></i>'.($comments_count == ""?0:wpqa_count_number($comments_count)).' '._n("Comment","Comments",$comments_count,"wpqa").'
										</a>
									</li>';
								}else {
									$out .= '<li class="user-columns-questions">
										<a href="'.esc_url(wpqa_get_profile_permalink($author_id,"questions")).'">
											<i class="icon-book-open"></i>'.($questions_count == ""?0:wpqa_count_number($questions_count)).' '._n("Question","Questions",$questions_count,"wpqa").'
										</a>
									</li>
									<li class="user-columns-answers">
										<a href="'.esc_url(wpqa_get_profile_permalink($author_id,"answers")).'">
											<i class="icon-comment"></i>'.($answers_count == ""?0:wpqa_count_number($answers_count)).' '._n("Answer","Answers",$answers_count,"wpqa").'
										</a>
									</li>';
								}
								$out .= '<li class="user-columns-best-answers">
									<a href="'.esc_url(wpqa_get_profile_permalink($author_id,"best_answers")).'">
										<i class="icon-graduation-cap"></i>'.($the_best_answer == ""?0:wpqa_count_number($the_best_answer)).' '._n("Best Answer","Best Answers",$the_best_answer,"wpqa").'
									</a>
								</li>';
								if ($active_points == "on") {
									$out .= '<li class="user-columns-points">
										<a href="'.esc_url(wpqa_get_profile_permalink($author_id,"points")).'">
											<i class="icon-bucket"></i>'.($points == ""?0:wpqa_count_number($points)).' '._n("Point","Points",$points,"wpqa").'
										</a>
									</li>';
								}
							$out .= '</ul>
						</div><!-- End user-columns-data -->';

						$out .= '<div class="user-follow-profile">';
							if ($owner == false) {
								$out .= wpqa_following($author_id,"style_2",$owner);
							}
							$out .= '<a href="'.wpqa_profile_url($author_id).'">'.esc_html__("View Profile","wpqa").'</a>
						</div><!-- End user-follow-profile -->';
					}
					
					$out .= '<div class="clearfix"></div>
				</div><!-- End post-inner -->
			</div><!-- End post -->';
			
			if ($author_page == "grid_pop") {
				$out .= '<div class="user-data">
					<ul>
						<li class="user-best-answers">
							<a href="'.esc_url(wpqa_get_profile_permalink($author_id,"best_answers")).'">
								<i class="icon-graduation-cap"></i>
								'.($the_best_answer == ""?0:wpqa_count_number($the_best_answer)).' '._n("Best Answer","Best Answers",$the_best_answer,"wpqa").'
							</a>
						</li>';
						if ($active_points == "on") {
							$out .= '<li class="user-points">
								<a href="'.esc_url(wpqa_get_profile_permalink($author_id,"points")).'">
									<i class="icon-bucket"></i>
									'.($points == ""?0:wpqa_count_number($points)).' '._n("Point","Points",$points,"wpqa").'
								</a>
							</li>';
						}
					$out .= '</ul>
				</div><!-- End user-data -->';
			}
			
			return $out;
		}
	}
endif;
/* Message button */
if (!function_exists('wpqa_message_button')) :
	function wpqa_message_button($author_id,$text = "",$owner = "",$return = "") {
		$out = "";
		$active_message = wpqa_options("active_message");
		if ($active_message == "on" && $owner == false) {
			$send_message_no_register = wpqa_options("send_message_no_register");
			$received_message = esc_attr(get_the_author_meta('received_message',$author_id));
			$get_current_user_id = get_current_user_id();
			$block_message = esc_attr(get_the_author_meta('block_message',$get_current_user_id));
			$user_block_message = array();
			if (is_user_logged_in()) {
				$user_block_message = get_user_meta($author_id,"user_block_message",true);
			}
			if (((!is_user_logged_in() && $send_message_no_register == "on") || (is_user_logged_in() && (empty($user_block_message) || (isset($user_block_message) && is_array($user_block_message) && !in_array($get_current_user_id,$user_block_message))) && ($block_message != "on" || is_super_admin($get_current_user_id)) && ($received_message == "" || $received_message == "on")))) {
				$out .= '<div class="'.($text != ""?'send_message_text':'send_message_icon').'"><a href="#" title="'.esc_html__("Send Message","wpqa").'" class="wpqa-message tooltip-n'.($text != ""?' button-default':'').'">'.($text != ""?esc_html__("Message","wpqa"):'<i class="icon-mail"></i>').'</a></div>';
			}
		}
		return $out;
	}
endif;
/* Following */
if (!function_exists('wpqa_following')) :
	function wpqa_following($author_id,$follow_style = "",$owner = "",$login = "") {
		$out = "";
		if ((is_user_logged_in() && $owner == false) || (!is_user_logged_in() && $login == "login")) {
			if (!is_user_logged_in() && $login == "login") {
				$out .= '<div class="user_follow"><a href="#" class="login-panel tooltip-n" title="'.esc_attr__("Login","wpqa").'"><i class="icon-plus"></i></a></div>';
			}else {
				$following_me = get_user_meta(get_current_user_id(),"following_me",true);
				if (isset($following_me)) {
					if ($follow_style == "style_2") {
						$following_you = get_user_meta($author_id,"following_you",true);
					}
					$out .= '<div class="user_follow'.($follow_style == "style_2"?"_2":"").($follow_style == "style_3"?"_3":"").($follow_style == "style_4"?"_4":"").(!empty($following_me) && in_array($author_id,$following_me)?($follow_style == "style_4"?" user_follow_done":" user_follow_yes"):"").'">
						<div class="small_loader loader_2'.($follow_style == "style_2"?" user_follow_loader":"").'"></div>';
						if (!empty($following_me) && in_array($author_id,$following_me)) {
							$out .= '<a href="#" class="following_not'.($follow_style == "style_2" || $follow_style == "style_3" || $follow_style == "style_4"?"":" tooltip-n").($follow_style == "style_4"?" button-default":"").'" data-rel="'.(int)$author_id.'" title="'.esc_attr__("Unfollow","wpqa").'">';
								if ($follow_style == "style_2" || $follow_style == "style_3" || $follow_style == "style_4") {
									$out .= '<span class="follow-value">'.esc_html__("Unfollow","wpqa").'</span>';
									if ($follow_style == "style_2") {
										$out .= '<span class="follow-count">'.($following_you == ""?0:wpqa_count_number($following_you)).'</span>';
									}
								}else {
									$out .= '<i class="icon-minus"></i>';
								}
							$out .= '</a>';
						}else {
							$out .= '<a href="#" class="following_you'.($follow_style == "style_2" || $follow_style == "style_3" || $follow_style == "style_4"?"":" tooltip-n").($follow_style == "style_4"?" button-default":"").'" data-rel="'.(int)$author_id.'" title="'.esc_attr__("Follow","wpqa").'">';
							if ($follow_style == "style_2" || $follow_style == "style_3" || $follow_style == "style_4") {
								$out .= '<span class="follow-value">'.esc_html__("Follow","wpqa").'</span>';
								if ($follow_style == "style_2") {
									$out .= '<span class="follow-count">'.($following_you == ""?0:wpqa_count_number($following_you)).'</span>';
								}
							}else {
								$out .= '<i class="icon-plus"></i>';
							}
							$out .= '</a>';
						}
					$out .= '</div>';
				}
			}
		}
		return $out;
	}
endif;
/* Get verified user */
if (!function_exists('wpqa_verified_user')) :
	function wpqa_verified_user($author_id,$return = "") {
		if ($author_id > 0) {
			$verified_user = get_the_author_meta('verified_user',$author_id);
			if ($verified_user == 1 || $verified_user == "on") {
				return '<span class="verified_user tooltip-n" title="'.esc_html__("Verified","wpqa").'"><i class="icon-check"></i></span>';
			}
		}
	}
endif;
/* Get badge */
if (!function_exists('wpqa_get_badge')) :
	function wpqa_get_badge($author_id,$return = "",$points = "") {
		$badges_style = wpqa_options("badges_style");
		$author_id = (int)$author_id;
		if ($badges_style == "by_groups_points") {
			if ($author_id > 0) {
				$points = (int)($points !== ""?$points:get_user_meta($author_id,"points",true));
				$badges_groups_points = wpqa_options("badges_groups_points");
				$user_info = get_userdata($author_id);
				$group_key = (is_array($user_info->caps)?key($user_info->caps):"");
				if (isset($badges_groups_points) && is_array($badges_groups_points)) {
					$badges_groups_points = array_values($badges_groups_points);
					foreach ($badges_groups_points as $badges_k => $badges_v) {
						if ($badges_v["badge_group"] == $group_key) {
							$badges_points[] = $badges_v;
						}
						
					}
					if (isset($badges_points) && is_array($badges_points)) {
						foreach ($badges_points as $key => $badge_point) {
							if ($points >= $badge_point["badge_points"]) {
								$last_key = $key;
							}
						}
					}
					if (isset($last_key)) {
						$badge_key = $last_key;
						if ($return == "points") {
							return (isset($badges_points[$badge_key]["badge_points"])?$badges_points[$badge_key]["badge_points"]:"");
						}else if ($return == "color") {
							return (isset($badges_points[$badge_key]["badge_color"])?$badges_points[$badge_key]["badge_color"]:"");
						}else if ($return == "name") {
							return (isset($badges_points[$badge_key]["badge_name"])?strip_tags(stripslashes($badges_points[$badge_key]["badge_name"]),"<i>"):"");
						}else if ($return == "key") {
							return $badge_key;
						}else if ($return == "first_key") {
							$first_badge = (isset($badges_points) && is_array($badges_points)?reset($badges_points):array());
							return (isset($first_badge['badge_points']) && $first_badge['badge_points'] == $badges_points[$badge_key]["badge_points"]?$badge_key:"");
						}else {
							return '<span class="badge-span" style="background-color: '.(isset($badges_points[$badge_key]["badge_color"])?$badges_points[$badge_key]["badge_color"]:"").'">'.(isset($badges_points[$badge_key]["badge_name"])?strip_tags(stripslashes($badges_points[$badge_key]["badge_name"]),"<i>"):"").'</span>';
						}
					}
				}
			}
		}else if ($badges_style == "by_groups") {
			if ($author_id > 0) {
				$badges_groups = wpqa_options("badges_groups");
				$user_info = get_userdata($author_id);
				$group_key = (is_array($user_info->caps)?key($user_info->caps):"");
				if (isset($badges_groups) && is_array($badges_groups)) {
					global $wp_roles;
					$badges_groups = array_values($badges_groups);
					$found_key = array_search($group_key,array_column($badges_groups,'badge_name'));
					$user_group = $wp_roles->roles[$user_group]["name"];
					if ($return == "color") {
						return $badges_groups[$found_key]["badge_color"];
					}else if ($return == "name") {
						return $user_group;
					}else if ($return == "key") {
						return $found_key;
					}else if ($return == "first_key") {
						$first_badge = (isset($badges_groups) && is_array($badges_groups)?reset($badges_groups):array());
						return (isset($first_badge['badge_points']) && $first_badge['badge_points'] == $badges_groups[$found_key]["badge_points"]?$found_key:"");
					}else {
						return '<span class="badge-span" style="background-color: '.$badges_groups[$found_key]["badge_color"].'">'.$user_group.'</span>';
					}
				}
			}
		}else {
			$active_points = wpqa_options("active_points");
			if ($author_id > 0 && $active_points == "on") {
				$points = (int)($points !== ""?$points:get_user_meta($author_id,"points",true));
				$badges = wpqa_options("badges");
				if (isset($badges) && is_array($badges)) {
					$badges = (!empty($badges)?array_values($badges):array());
					foreach ($badges as $badges_k => $badges_v) {
						$badges_points[] = $badges_v["badge_points"];
					}
					if (isset($badges_points) && is_array($badges_points)) {
						foreach ($badges_points as $key => $badge_point) {
							if ($points >= $badge_point) {
								$last_key = $key;
							}
						}
					}
					if (isset($last_key)) {
						$badge_key = $last_key;
						if ($return == "points") {
							return $badges[$badge_key]["badge_points"];
						}else if ($return == "color") {
							return $badges[$badge_key]["badge_color"];
						}else if ($return == "name") {
							return strip_tags(stripslashes($badges[$badge_key]["badge_name"]),"<i>");
						}else if ($return == "key") {
							return $badge_key;
						}else if ($return == "first_key") {
							$first_badge = (isset($badges) && is_array($badges)?reset($badges):array());
							return (isset($first_badge['badge_points']) && $first_badge['badge_points'] == $badges[$badge_key]["badge_points"]?$badge_key:"");
						}else {
							return '<span class="badge-span" style="background-color: '.$badges[$badge_key]["badge_color"].'">'.strip_tags(stripslashes($badges[$badge_key]["badge_name"]),"<i>").'</span>';
						}
					}
				}
			}
		}
	}
endif;
/* Post tag callback */
if (!function_exists('wpqa_post_tag_callback')) :
	function wpqa_post_tag_callback($count) {
		return sprintf(_n(esc_html__('%s post','wpqa'),esc_html__('%s posts','wpqa'),$count),number_format_i18n($count));
	}
endif;
/* Question tag callback */
if (!function_exists('wpqa_question_tags_callback')) :
	function wpqa_question_tags_callback($count) {
		return sprintf(_n(esc_html__('%s question','wpqa'),esc_html__('%s questions','wpqa'),$count),number_format_i18n($count));
	}
endif;
/* Count posts by type */
if (!function_exists('wpqa_count_posts_by_type')) :
	function wpqa_count_posts_by_type( $post_type = 'post', $post_status = "publish", $asked = "asked" ) {
		$question_meta_query = ($asked == "asked"?array():array("meta_query" => array(array("key" => "user_id","compare" => "NOT EXISTS"))));
		$args = array(
			'post_type'   => $post_type,
			'post_status' => $post_status
		);
		$the_query = new WP_Query(array_merge($question_meta_query,$args));
		return $the_query->found_posts;
		wp_reset_postdata();
	}
endif;
/* Count posts by user */
if (!function_exists('wpqa_count_posts_by_user')) :
	function wpqa_count_posts_by_user( $user_id, $post_type = "post", $post_status = "publish", $category = 0 ) {
		$author = ($user_id > 0?array("author" => $user_id):array());
		$tax = ($category > 0?array("tax_query" => array(array("taxonomy" => "question-category","field" => "id","terms" => $category,'operator' => 'IN'))):array());
		$args = array(
			"post_type"   => $post_type,
			"post_status" => $post_status,
			"meta_query" => array(
				"relation" => "OR",
				array("key" => "private_question","compare" => "NOT EXISTS"),
				array("key" => "private_question","compare" => "=","value" => 0)
			)
		);
		$the_query = new WP_Query(array_merge($author,$tax,$args));
		return $the_query->found_posts;
		wp_reset_postdata();
	}
endif;
/* Count new message */
if (!function_exists('wpqa_count_new_message')) :
	function wpqa_count_new_message( $user_id = "", $post_status = "publish" ) {
		$args = array(
			"post_type"   => "message",
			"post_status" => $post_status,
			"meta_query" => array(
				"relation" => "AND",
				array("key" => "delete_inbox_message","compare" => "NOT EXISTS"),
				array("key" => "message_user_id","compare" => "=","value" => $user_id),
				array(
					"relation" => "OR",
					array("key" => "message_new","compare" => "=","value" => 1),
					array("key" => "message_new","compare" => "=","value" => "on")
				)
			)
		);
		$the_query = new WP_Query($args);
		return $the_query->found_posts;
		wp_reset_postdata();
	}
endif;
/* User table */
if (!function_exists('wpqa_user_table')) :
	function wpqa_user_table( $column ) {
		$user_meta_admin = wpqa_options("user_meta_admin");
		if (isset ($user_meta_admin) && is_array($user_meta_admin)) {
			$column['question'] = esc_html__('Questions','wpqa');
			if (isset($user_meta_admin["points"]) && $user_meta_admin["points"] == "points") {
				$column['points'] = esc_html__('Points','wpqa');
			}
			if (isset($user_meta_admin["phone"]) && $user_meta_admin["phone"] == "phone") {
				$column['phone'] = esc_html__('Phone','wpqa');
			}
			if (isset($user_meta_admin["country"]) && $user_meta_admin["country"] == "country") {
				$column['country'] = esc_html__('Country','wpqa');
			}
			if (isset($user_meta_admin["age"]) && $user_meta_admin["age"] == "age") {
				$column['age'] = esc_html__('Age','wpqa');
			}
			if (isset($user_meta_admin["invitation"]) && $user_meta_admin["invitation"] == "invitation") {
				$column['invitation'] = esc_html__('Invitation','wpqa');
			}
		}
		return $column;
	}
endif;
add_filter( 'manage_users_columns', 'wpqa_user_table' );
if (!function_exists('wpqa_user_table_row')) :
	function wpqa_user_table_row( $val, $column_name, $user_id ) {
		$user = get_userdata( $user_id );
		switch ($column_name) {
			case 'question' :
				$count_user_ques_slugs = wpqa_count_posts_by_user($user_id,"question");
				return ($count_user_ques_slugs > 0?'<a href="'.admin_url('edit.php?post_type=question&author='.$user_id).'">':'').$count_user_ques_slugs.($count_user_ques_slugs > 0?'</a>':'');
				break;
			case 'points' :
				$points = (int)get_the_author_meta( 'points', $user_id );
				return $points;
				break;
			case 'phone' :
				$phone = get_the_author_meta( 'phone', $user_id );
				return ($phone != ""?$phone:" - ");
				break;
			case 'country' :
				$get_countries = apply_filters('wpqa_get_countries',false);
				$country = get_the_author_meta( 'country', $user_id );
				if ($country && isset($get_countries[$country])) {
					return $get_countries[$country];
				}else {
					return ' - ';
				}
				break;
			case 'age' :
				$age = get_the_author_meta( 'age', $user_id );
				return (date_create($age)?date_diff(date_create($age),date_create('today'))->y:"");
				break;
			case 'invitation' :
				$invitation = (int)get_the_author_meta( 'wpqa_invitations', $user_id );
				return ($invitation > 0?'<a href="'.get_author_posts_url($invitation).'" target="_blank">'.get_the_author_meta('display_name',$invitation).'</a>':' - ');
				break;
			default:
		}
	}
endif;
add_filter( 'manage_users_custom_column', 'wpqa_user_table_row', 10, 3 );
/* Media library */
add_action('pre_get_posts','wpqa_media_library');
if (!function_exists('wpqa_media_library')) :
	function wpqa_media_library($wp_query_obj) {
		global $current_user,$pagenow;
		if (!is_a($current_user,'WP_User') || is_super_admin($current_user->ID))
			return;
		if ('admin-ajax.php' != $pagenow || $_REQUEST['action'] != 'query-attachments')
			return;
		if (!current_user_can('manage_media_library'))
			$wp_query_obj->set('author',$current_user->ID);
		return;
	}
endif;
/* Remove item by value */
if (!function_exists('wpqa_remove_item_by_value')) :
	function wpqa_remove_item_by_value($array,$val = '',$preserve_keys = true) {
		if (empty($array) || !is_array($array)) {
			return false;
		}
		if (!in_array($val,$array)) {
			return $array;
		}
		foreach ($array as $key => $value) {
			if ($value == $val) unset($array[$key]);
		}
		return ($preserve_keys === true)?$array:array_values($array);
	}
endif;
/* Insert after key in array */
if (!function_exists('wpqa_array_insert_after')) :
function wpqa_array_insert_after( array $array, $key, array $new ) {
	$keys = array_keys( $array );
	$index = array_search( $key, $keys );
	$pos = false === $index ? count( $array ) : $index + 1;
	return array_merge( array_slice( $array, 0, $pos ), $new, array_slice( $array, $pos ) );
}
endif;
/* Excerpt row */
if (!function_exists('wpqa_excerpt_row')) :
	function wpqa_excerpt_row($excerpt_length,$content) {
		$words = explode(' ',$content,$excerpt_length + 1);
		if (count($words) > $excerpt_length) :
			array_pop($words);
			array_push($words,'...');
			$content = implode(' ',$words).'...';
		endif;
			$content = strip_tags($content);
		echo ($content);
	}
endif;
/* Excerpt title row */
if (!function_exists('wpqa_excerpt_title_row')) :
	function wpqa_excerpt_title_row($excerpt_length,$title) {
		$words = explode(' ',$title,$excerpt_length + 1);
		if (count($words) > $excerpt_length) :
			array_pop($words);
			array_push($words,'');
			$title = implode(' ',$words).'...';
		endif;
			$title = strip_tags($title);
		echo ($title);
	}
endif;
/* Excerpts */
if (!defined("wpqa_excerpt_type")) {
	define("wpqa_excerpt_type",wpqa_options("excerpt_type"));
}
if (!function_exists('wpqa_excerpt_title')) :
	function wpqa_excerpt_title ($excerpt_length,$excerpt_type = wpqa_excerpt_type,$return = "") {
		global $post;
		$title = "";
		$excerpt_length = ((isset($excerpt_length) && $excerpt_length != "") || $excerpt_length == 0?$excerpt_length:5);
		if ($excerpt_length > 0) {
			$title = $post->post_title;
		}
		if ($excerpt_type == "characters") {
			$title = mb_substr($title,0,$excerpt_length,"UTF-8");
		}else {
			$words = explode(' ',$title,$excerpt_length + 1);
			if (count($words) > $excerpt_length) :
				array_pop($words);
				array_push($words,'');
				$title = implode(' ',$words);
			endif;
		}
		$title = strip_tags($title);
		if ($return == "return") {
			return esc_attr($title);
		}else {
			echo esc_attr($title);
		}
	}
endif;
if (!function_exists('wpqa_excerpt')) :
	function wpqa_excerpt ($excerpt_length,$excerpt_type = wpqa_excerpt_type,$return = "") {
		global $post;
		$content = "";
		$excerpt_length = ((isset($excerpt_length) && $excerpt_length != "") || $excerpt_length == 0?$excerpt_length:5);
		if ($excerpt_length > 0) {
			$content = $post->post_content;
		}
		if ($excerpt_type == "characters") {
			$content = mb_substr($content,0,$excerpt_length,"UTF-8");
		}else {
			$words = explode(' ',$content,$excerpt_length + 1);
			if (count($words) > $excerpt_length) :
				array_pop($words);
				array_push($words,'');
				$content = implode(' ',$words);
			endif;
		}
		$content = strip_tags($content);
		if ($return == "return") {
			return esc_attr($content);
		}else {
			echo esc_attr($content);
		}
	}
endif;
if (!function_exists('wpqa_excerpt_any')) :
	function wpqa_excerpt_any ($excerpt_length,$title,$excerpt_type = wpqa_excerpt_type) {
		$excerpt_length = (isset($excerpt_length) && $excerpt_length != ""?$excerpt_length:5);
		if ($excerpt_type == "characters") {
			$title = mb_substr($title,0,$excerpt_length,"UTF-8");
		}else {
			$words = explode(' ',$title,$excerpt_length + 1);
			if (count($words) > $excerpt_length) :
				array_pop($words);
				array_push($words,'');
				$title = implode(' ',$words).'...';
			endif;
				$title = strip_tags($title);
		}
		return $title;
	}
endif;
/* Before delete user */
add_action('delete_user','wpqa_before_delete_user');
if (!function_exists('wpqa_before_delete_user')) :
	function wpqa_before_delete_user($user_id) {
		$active_points = wpqa_options("active_points");
		$point_following_me = wpqa_options("point_following_me");
		
		$following_me = get_user_meta($user_id,"following_me",true);
		if (isset($following_me) && is_array($following_me)) {
			foreach ($following_me as $key => $value) {
				$following_me = get_user_meta($value,"following_me",true);
				$remove_following_me = wpqa_remove_item_by_value($following_me,$user_id);
				update_user_meta($value,"following_me",$remove_following_me);
				if ($active_points == "on" && $point_following_me > 0) {
					wpqa_add_points($value,$point_following_me,"-","delete_follow_user");
				}
				
				$following_you = get_user_meta($value,"following_you",true);
				$remove_following_you = wpqa_remove_item_by_value($following_you,$user_id);
				update_user_meta($value,"following_you",$remove_following_you);
			}
		}
	}
endif;
/* Action delete post */
if (!function_exists('wpqa_delete_post')) :
	function wpqa_delete_post() {
		if (isset($_GET['wpqa_delete_nonce']) && wp_verify_nonce($_GET['wpqa_delete_nonce'],'wpqa_delete_nonce') && !is_admin() && isset($_GET["delete"]) && $_GET["delete"] != "") {
			$post_id   = (int)$_GET["delete"];
			$get_post  = get_post($post_id);
			$post_type = $get_post->post_type;
			$filter_post_type = apply_filters("wpqa_filter_delete_post_type",false,$post_type);
			$filter_delete_post = apply_filters("wpqa_filter_delete_post",true,$post_id);
			if ($filter_delete_post == true && isset($post_id) && $post_id != "" && isset($get_post->post_status) && $get_post->post_status == "publish" && ($post_type == "post" || $post_type == "question" || $filter_post_type == true)) {
				$post_author  = $get_post->post_author;
				$user_id      = get_current_user_id();
				$delete_post  = wpqa_options($post_type."_delete");
				$delete_trush = wpqa_options("delete_".$post_type);
				$moderators_permissions = wpqa_user_moderator($user_id);
				if (($post_author != 0 && $post_author == $user_id && $delete_post == "on") || (isset($moderators_permissions['delete']) && $moderators_permissions['delete'] == "delete") || is_super_admin($user_id)) {
					if ($user_id > 0) {
						wpqa_notifications_activities($user_id,"","","","","delete_".$post_type,"activities","",$post_type);
					}
					wp_delete_post($post_id,($delete_trush == "trash"?false:true));
					wpqa_session('<div class="alert-message success"><i class="icon-check"></i><p>'.esc_html__("Has been deleted successfully.","wpqa").'</p></div>','wpqa_session');
					$protocol    = is_ssl() ? 'https' : 'http';
					$redirect_to = wp_unslash($protocol.'://'.wpqa_server('HTTP_HOST').wpqa_server('REQUEST_URI'));
					$redirect_to = (isset($_GET["page"]) && esc_attr($_GET["page"]) != ""?esc_attr($_GET["page"]):$redirect_to);
					wp_redirect(((isset($_GET["page"]) && esc_attr($_GET["page"]) != "") || is_page()?site_url("/").$redirect_to:esc_url(home_url('/'))));
					exit;
				}
			}
		}
	}
endif;
add_action('wpqa_init','wpqa_delete_post');
/* Before delete post */
add_action('before_delete_post','wpqa_before_delete_post');
if (!function_exists('wpqa_before_delete_post')) :
	function wpqa_before_delete_post($post_id) {
		$get_post = get_post($post_id);
		$post_type = $get_post->post_type;
		if ($post_type == "question") {
			$active_points = wpqa_options("active_points");
			$active_points_category = wpqa_options("active_points_category");
			if ($active_points == "on" && $active_points_category == "on") {
				$categories = wp_get_post_terms($post_id,'question-category',array('fields' => 'ids'));
				update_post_meta($post_id,"question_category",$categories);
			}
			$remove_best_answer_stats = wpqa_options("remove_best_answer_stats");
			if ($remove_best_answer_stats == "on" && $active_points == "on") {
				$user_id = $get_post->post_author;
				if ($user_id > 0) {
					$point_add_question = (int)wpqa_options("point_add_question");
					if ($point_add_question > 0) {
						wpqa_add_points($user_id,$point_add_question,"-","delete_question",$post_id);
					}
				}
			}
		}
		
		if (isset($post_id) && $post_id != "" && ($post_type == "post" || $post_type == "question")) { 
			$favorites_questions = get_post_meta($post_id,"favorites_questions",true);
			if (isset($favorites_questions) && is_array($favorites_questions) && count($favorites_questions) > 0) {
				foreach ($favorites_questions as $user_id) {
					$favorites_questions_user = get_user_meta($user_id,$user_id."_favorites",true);
					$remove_favorites_questions = wpqa_remove_item_by_value($favorites_questions_user,$post_id);
					update_user_meta($user_id,$user_id."_favorites",$remove_favorites_questions);
				}
			}
			
			$following_questions = get_post_meta($post_id,"following_questions",true);
			$following_questions = (is_array($following_questions) && !empty($following_questions)?get_users(array('fields' => 'ID','include' => $following_questions,'orderby' => 'registered')):array());
			if (isset($following_questions) && is_array($following_questions) && count($following_questions) > 0) {
				foreach ($following_questions as $user_id) {
					$following_questions_user = get_user_meta($user_id,"following_questions",true);
					$remove_following_questions = wpqa_remove_item_by_value($following_questions_user,$post_id);
					update_user_meta($user_id,"following_questions",$remove_following_questions);
				}
			}
		}
	}
endif;
/* Transition the post status */
add_action('transition_post_status','wpqa_run_on_update_post',10,3);
if (!function_exists('wpqa_run_on_update_post')) :
	function wpqa_run_on_update_post($new_status,$old_status,$post) {
		if (is_admin()) {
			if ($post->post_type == "question" || $post->post_type == "post" || $post->post_type == "message") {
				$post_from_front = get_post_meta($post->ID,'post_from_front',true);
				if ($post->post_type == "question") {
					$user_id = get_post_meta($post->ID,"user_id",true);
					$anonymously_user = get_post_meta($post->ID,"anonymously_user",true);
					$question_username = get_post_meta($post->ID,'question_username',true);
					$question_email = get_post_meta($post->ID,'question_email',true);
					if ($question_username == "") {
						$question_no_username = get_post_meta($post->ID,'question_no_username',true);
					}
				}
				if ($post->post_type == "post") {
					$post_username = get_post_meta($post->ID,'post_username',true);
					$post_email = get_post_meta($post->ID,'post_email',true);
				}
				if ($post->post_type == "message") {
					$message_username = get_post_meta($post->ID,'message_username',true);
					$message_email = get_post_meta($post->ID,'message_email',true);
				}
				
				if ((isset($anonymously_user) && $anonymously_user > 0) || (isset($question_no_username) && $question_no_username == "no_user") || (isset($question_username) && $question_username != "" && isset($question_email) && $question_email != "") || (isset($post_username) && $post_username != "" && isset($post_email) && $post_email != "") || (isset($message_username) && $message_username != "" && isset($message_email) && $message_email != "")) {
					$not_user = 0;
				}else {
					$not_user = $post->post_author;
				}
				
				$post_approved_before = get_post_meta($post->ID,'post_approved_before',true);
				if ($post_approved_before != "yes") {
					if ('publish' == $new_status && $post->post_type == "message") {
						$message_user_array = get_post_meta($post->ID,'message_user_array',true);
						if (is_array($message_user_array) && !empty($message_user_array)) {
							update_post_meta($post->ID,'post_approved_before',"yes");
							$get_message_user = get_post_meta($post->ID,'message_user_id',true);
							$send_email_message = wpqa_options("send_email_message");
							if ($post->post_author != $get_message_user && $get_message_user > 0) {
								$header_messages = wpqa_options("header_messages");
								$header_style = wpqa_options("header_style");
								$show_message_area = ($header_messages == "on" && $header_style == "simple"?"on":0);
								wpqa_notifications_activities($get_message_user,$post->post_author,($post->post_author == 0?$get_message_user:""),"","","add_message_user","notifications","","message",($show_message_area === "on"?false:true));
							}
							if ($not_user > 0) {
								wpqa_notifications_activities($not_user,"","","","","approved_message","notifications");
								wpqa_notifications_activities($not_user,$get_message_user,"","","","add_message","activities","","message");
							}
							
							if ($send_email_message == "on" && $get_message_user > 0) {
								$user = get_userdata($get_message_user);
								$send_text = wpqa_send_email(wpqa_options("email_new_message"),$get_message_user,$post->ID,"","","","","","","","","","","","","",$post->post_author,$user->ID);
								$last_message_email = wpqa_email_code($send_text);
								$email_title = wpqa_options("title_new_message");
								$email_title = ($email_title != ""?$email_title:esc_html__("New message","wpqa"));
								$email_template = wpqa_options("email_template");
								$mail_smtp = wpqa_options("mail_smtp");
								$email_template = ($mail_smtp == "on"?wpqa_options("mail_username"):$email_template);
								$unsubscribe_mails = get_the_author_meta('unsubscribe_mails',$user->ID);
								$send_message_mail = get_the_author_meta('send_message_mail',$user->ID);
								if ($unsubscribe_mails != "on" || ($send_message_mail == "on" || $send_message_mail == "")) {
									wpqa_sendEmail($email_template,get_bloginfo('name'),esc_html($user->user_email),esc_html($user->display_name),$email_title,$last_message_email);
								}
							}
						}
					}
					if ('publish' == $new_status && isset($post_from_front) && $post_from_front == "from_front" && ($post->post_type == "question" || $post->post_type == "post")) {
						if ($not_user > 0 || $anonymously_user > 0) {
							if ($post->post_type == "question") {
								wpqa_notifications_activities(($anonymously_user > 0?$anonymously_user:$not_user),"","",$post->ID,"","approved_question","notifications","","question");
								if ($post->post_author != $user_id && $user_id > 0) {
									wpqa_notifications_activities($user_id,($anonymously_user > 0?0:$not_user),"",$post->ID,"","add_question_user","notifications","","question");
								}
							}else if ($not_user > 0) {
								wpqa_notifications_activities($not_user,"","",$post->ID,"","approved_post","notifications");
							}
						}
						
						if ($post->post_type == "question") {
							wpqa_notifications_ask_question($post->ID,$question_username,$user_id,$not_user,$anonymously_user,"admin");
						}
						
						if ($post->post_type == "question" || $post->post_type == "post") {
							wpqa_post_publish($post,$not_user,"admin");
						}
						update_post_meta($post->ID,'post_approved_before',"yes");
					}
					do_action("wpqa_update_post",$post);
				}
			}
		}
	}
endif;
/* Save post */
add_action('save_post','wpqa_save_post',10,3);
if (!function_exists('wpqa_save_post')) :
	function wpqa_save_post($post_id) {
		$post_data = get_post($post_id);
		if (is_admin()) {
			if ($post_data->post_type == "question" || $post_data->post_type == "post" || $post_data->post_type == "message") {
				if ($post_data->post_type == "question") {
					$question_username = get_post_meta($post_id,'question_username',true);
					$question_email = get_post_meta($post_id,'question_email',true);
					$anonymously_user = get_post_meta($post_id,'anonymously_user',true);
					if ($question_username == "") {
						$question_no_username = get_post_meta($post_id,'question_no_username',true);
					}
				}
				if ($post_data->post_type == "post") {
					$post_username = get_post_meta($post_id,'post_username',true);
					$post_email = get_post_meta($post_id,'post_email',true);
				}
				if ($post_data->post_type == "message") {
					$message_username = get_post_meta($post_id,'message_username',true);
					$message_email = get_post_meta($post_id,'message_email',true);
				}
				
				if ((isset($anonymously_user) && $anonymously_user != "") || (isset($question_no_username) && $question_no_username == "no_user") || (isset($question_username) && $question_username != "" && isset($question_email) && $question_email != "") || (isset($post_username) && $post_username != "" && isset($post_email) && $post_email != "") || (isset($message_username) && $message_username != "" && isset($message_email) && $message_email != "")) {
					$data = array(
						'ID' => $post_id,
						'post_author' => 0,
					);
					remove_action('save_post','wpqa_save_post');
					$post_id = wp_update_post($data);
					add_action('save_post','wpqa_save_post');
				}
			}
		}else {
			if ($post_data->post_type == "question") {
				$question_status = wpqa_get_question_status($post_id,get_current_user_id());
				if ($question_status == "draft") {
					$data = array(
						'ID' => $post_id,
						'post_status' => $question_status,
					);
					remove_action('save_post','wpqa_save_post');
					$post_id = wp_update_post($data);
					add_action('save_post','wpqa_save_post');
				}
			}
		}
	}
endif;
/* Get comment */
if (!function_exists('wpqa_comment')) :
	function wpqa_comment($comment,$args,$depth,$answer = "",$owner = "",$k_ad = "",$best_answer = "",$answer_args = array()) {
		if ($answer != "answer") {
			global $post;
			if ($k_ad == "") {
				global $k_ad;
			}
			if ($comment->comment_parent == 0) {
				$k_ad++;
			}
		}
		$show_replies = wpqa_options("show_replies");
	    $user_get_current_user_id = get_current_user_id();
	    $comment_id = (int)$comment->comment_ID;
	    $comment_user_id = (int)$comment->user_id;
	    $post_id = (int)$comment->comment_post_ID;
    	$post_type = get_post_type($post_id);
	    $user = get_user_by('id',$comment_user_id);
	    $deleted_user = ($comment_user_id > 0 && isset($user->display_name)?$user->display_name:($comment_user_id == 0?$comment->comment_author:"delete"));
		$like_answer = apply_filters("wpqa_comment_like_answer",false,(isset($post->post_type)?$post->post_type:""));
	    if ($like_answer == true || ($answer != "answer" && isset($post->post_type) && $post->post_type == 'question') || ($answer == "answer" && $post_type == "question")) {
	    	$its_question = (isset($post->post_type)?$post->post_type:$post_type);
	    	$the_best_answer = get_post_meta(($answer == "answer"?$post_id:(isset($post->ID)?$post->ID:0)),"the_best_answer",true);
	    	$best_answer_comment = get_comment_meta($comment_id,"best_answer_comment",true);
	    	$comment_best_answer = ($best_answer_comment == "best_answer_comment" || $the_best_answer == $comment_id?"comment-best-answer":"");
	    	$active_reports = wpqa_options("active_reports");
	    	$active_logged_reports = wpqa_options("active_logged_reports");
	    	$active_vote = wpqa_options("active_vote");
	    	$active_vote_unlogged = wpqa_options("active_vote_unlogged");
			$active_best_answer = wpqa_options("active_best_answer");
	    }
	    
	    if ($like_answer == true || (isset($its_question) && $its_question == "question")) {
	    	$comment_vote = get_comment_meta($comment_id,'comment_vote',true);
	    	if (isset($comment_vote) && is_array($comment_vote) && isset($comment_vote["vote"])) {
	    		update_comment_meta($comment_id,'comment_vote',$comment_vote["vote"]);
	    		$comment_vote = get_comment_meta($comment_id,'comment_vote',true);
	    	}else if ($comment_vote == "") {
	    		update_comment_meta($comment_id,'comment_vote',0);
	    		$comment_vote = get_comment_meta($comment_id,'comment_vote',true);
	    	}
	    }
	    $can_delete_comment = wpqa_options("can_delete_comment");
	    $can_edit_comment = wpqa_options("can_edit_comment");
	    $can_edit_comment_after = (int)wpqa_options("can_edit_comment_after");
	    $can_edit_comment_after = (isset($can_edit_comment_after) && $can_edit_comment_after > 0?$can_edit_comment_after:0);
	    if (version_compare(phpversion(), '5.3.0', '>')) {
	    	$time_now = strtotime(current_time('mysql'),date_create_from_format('Y-m-d H:i',current_time('mysql')));
	    }else {
	    	list($year, $month, $day, $hour, $minute, $second) = sscanf(current_time('mysql'),'%04d-%02d-%02d %02d:%02d:%02d');
	    	$datetime = new DateTime("$year-$month-$day $hour:$minute:$second");
	    	$time_now = strtotime($datetime->format('r'));
	    }
	    $time_edit_comment = strtotime('+'.$can_edit_comment_after.' hour',strtotime($comment->comment_date));
	    $time_end = ($time_now-$time_edit_comment)/60/60;
	    $edit_comment = get_comment_meta($comment_id,"edit_comment",true);
	    $between_comments_position = (int)wpqa_options("between_comments_position");
	    $adv_type_repeat = wpqa_options("between_comments_adv_type_repeat");
	    $count_adv = ($between_comments_position > 0 && isset($k_ad) && $k_ad > 0?$k_ad % $between_comments_position:0);
	    if (isset($k_ad) && (($k_ad == $between_comments_position) || ($adv_type_repeat == "on" && $k_ad != 0 && $count_adv == 0))) {
	    	if (function_exists('discy_ads')) {
	    		echo discy_ads("between_comments_adv_type","between_comments_adv_code","between_comments_adv_href","between_comments_adv_img","li","","","on");
	    	}
	    }
	    if ($answer == "answer") {
	    	$k_ad++;
	    }
	    $answer_question_style = wpqa_options("answer_question_style");
	    $profile_credential = ($comment_user_id > 0?get_the_author_meta('profile_credential',$comment_user_id):"");
	    if (isset($answer_args['custom_home_answer']) && $answer_args['custom_home_answer'] == "on") {
        	$answer_image         = get_post_meta($answer_args['answer_question_id'],prefix_meta.'answers_image_h',true);
        	$active_vote_answer   = get_post_meta($answer_args['answer_question_id'],prefix_meta.'active_vote_answer_h',true);
        	$show_dislike_answers = get_post_meta($answer_args['answer_question_id'],prefix_meta.'show_dislike_answers_h',true);
        }else if (isset($answer_args['custom_answers']) && $answer_args['custom_answers'] == "on") {
        	$answer_image         = get_post_meta($answer_args['answer_question_id'],prefix_meta.'answers_image_a',true);
        	$active_vote_answer   = get_post_meta($answer_args['answer_question_id'],prefix_meta.'active_vote_answer_a',true);
        	$show_dislike_answers = get_post_meta($answer_args['answer_question_id'],prefix_meta.'show_dislike_answers_a',true);
        }else {
        	$answer_image         = wpqa_options("answer_image");
        	$active_vote_answer   = wpqa_options("active_vote_answer");
        	$show_dislike_answers = wpqa_options("show_dislike_answers");
        }?>
	    <li <?php comment_class(($answer_image == "on"?"":"comment-without-image ").($show_replies == "on"?"comment-show-replies ":"").(isset($answer_args["comment_read_more"])?"comment-read-more ":"").(isset($answer_args["comment_with_title"])?"comment-with-title ".($answer_question_style != ""?"comment-with-title-".str_replace('style_','',$answer_question_style)." ":""):"").($profile_credential != ""?"comment-credential ":"").(isset($its_question) && $its_question == "question"?$comment_best_answer." ":"").($comment->comment_type == "pingback"?"comment":""),$comment_id,$post_id);echo (isset($its_question) && $its_question == 'question' && is_single()?' itemscope itemtype="https://schema.org/'.($comment->comment_parent > 0?"Comment":"Answer").'"'.($comment->comment_parent == 0?' itemprop="'.($best_answer_comment == 'best_answer_comment' || $the_best_answer == $comment_id?'acceptedAnswer':'suggestedAnswer').'"':''):'');?> id="li-comment-<?php echo esc_attr($comment_id);?>">
	    	<div id="comment-<?php echo esc_attr($comment_id);?>" class="comment-body clearfix">
	            <?php if (isset($answer_args["comment_with_title"])) {
		            echo '<div class="comment-question-title"><header class="article-header"><div class="question-header"><div class="post-meta">';
		            	if (function_exists('discy_meta')) {
		            		discy_meta("on","on","","","","",$post_id);
		            	}
		        	echo '</div></div></header>
		        	<div class="clearfix"></div>
		            <h2 class="post-title"><a class="post-title" href="' . esc_url( get_permalink($post_id) ) . '" rel="bookmark">'.get_the_title($post_id).'</a></h2></div>';
		        }?>
	            <div class="comment-text">
	            	<?php if ($answer_image == "on") {
		            	do_action("wpqa_action_avatar_link",array("user_id" => ($comment_user_id > 0?$comment_user_id:0),"size" => 42,"span" => "span","pop" => "pop","comment" => $comment,"email" => ($comment_user_id > 0?"":$comment->comment_author_email)));
		            }?>
	                <div class="author clearfix">
	                	<?php if ($best_answer == "" && isset($its_question) && $its_question == "question" && ($best_answer_comment == "best_answer_comment" || $the_best_answer == $comment_id)) {?>
	                		<div class="best-answer"><?php esc_html_e("Best Answer","wpqa")?></div>
	                	<?php }?>
	                	<div class="comment-meta">
	                    	<div class="comment-author">
	                    		<?php $wpqa_activate_comment_author = apply_filters('wpqa_activate_comment_author',true,$comment_id);
	                    		if ($wpqa_activate_comment_author == true) {
	                    			if ($comment_user_id > 0) {
		                    			$wpqa_profile_url = wpqa_profile_url($comment_user_id);
		                    		}else {
		                    			$wpqa_profile_url = ($comment->comment_author_url != ""?$comment->comment_author_url:"wpqa_No_site");
		                    		}
		                    		if ($wpqa_profile_url != "" && $wpqa_profile_url != "wpqa_No_site") {?>
		                    			<a href="<?php echo esc_url($wpqa_profile_url)?>">
		                    		<?php }
			                    		$anonymously_user = get_comment_meta($comment_id,"anonymously_user",true);
		                        		echo ($deleted_user == "delete"?esc_html__("[Deleted User]","wpqa"):($anonymously_user != ""?esc_html__("Anonymous","wpqa"):get_comment_author($comment_id)));
		                        	if ($wpqa_profile_url != "" && $wpqa_profile_url != "wpqa_No_site") {?>
		                        		</a>
		                        	<?php }
		                        }
	                        	if ($comment_user_id != 0) {
	                        		do_action("wpqa_verified_user",$comment_user_id);
	                        		$active_points_category = wpqa_options("active_points_category");
									if ($active_points_category == "on") {
										$get_terms = wp_get_post_terms($post_id,'question-category',array('fields' => 'ids'));
										if (!empty($get_terms) && is_array($get_terms) && isset($get_terms[0])) {
											$points_category_user = (int)get_user_meta($comment_user_id,"points_category".$get_terms[0],true);
											echo apply_filters("wpqa_comments_before_badge",false,$get_terms[0]);
										}
									}
	                        		do_action("wpqa_get_badge",$comment_user_id,"",(isset($points_category_user)?$points_category_user:""));
	                        	}
	                        	if ($profile_credential != "") {?>
	                        		<span class="profile-credential"><?php echo esc_html($profile_credential)?></span>
	                        	<?php }
	                        	do_action("wpqa_action_after_credential",(isset($post->ID)?$post->ID:0),$comment_id,$comment_user_id);?>
	                    	</div>
							<?php $date_format = wpqa_options("date_format");
							$date_format = ($date_format?$date_format:get_option("date_format"));?>
	                        <a href="<?php echo get_comment_link($comment_id); ?>" class="comment-date"<?php echo(isset($its_question) && $its_question == "question"?" itemprop='url'":"")?>>
	                        	<?php $date_format = wpqa_options("date_format");
								$date_format = ($date_format?$date_format:get_option("date_format"));
								echo (is_single()?'<span itemprop="dateCreated" datetime="'.get_comment_date("F j, Y",$comment_id).'"></span>':'');
	                        	if ((isset($its_question) && $its_question == "question") || ($answer == "answer")) {
	                        		echo ($comment->comment_parent > 0?esc_html__("Replied to","wpqa"):esc_html__("Added an","wpqa"))." ";
	                        		printf(esc_html__('answer on %1$s at %2$s','wpqa'),get_comment_date($date_format,$comment_id),wpqa_get_comment_time(false,false,false,$comment));
	                        	}else {
	                        		echo ($comment->comment_parent > 0?esc_html__("Replied to","wpqa"):esc_html__("Added a","wpqa"))." ";
	                        		printf(esc_html__('comment on %1$s at %2$s','wpqa'),get_comment_date($date_format,$comment_id),wpqa_get_comment_time(false,false,false,$comment));
	                        	}?>
	                        </a> 
	                    </div><!-- End comment-meta -->
	                </div><!-- End author -->
	                <div class="text">
	                	<?php if ($edit_comment == "edited") {?>
	                		<em class="comment-edited">
	                			<?php if (isset($its_question) && $its_question == "question") {
	                				esc_html_e('This answer was edited.','wpqa');
	                			}else {
	                				esc_html_e('This comment was edited.','wpqa');
	                			}?>
	                		</em>
	                	<?php }
	                	if ($comment->comment_approved == '0') : ?>
	                	    <em class="comment-awaiting">
		                	    <?php if (isset($its_question) && $its_question == "question") {
		                	    	esc_html_e('Your answer is awaiting moderation.','wpqa');
		                	    }else {
		                	    	esc_html_e('Your comment is awaiting moderation.','wpqa');
		                	    }?>
	                	    </em><br>
	                	<?php endif;
	                	
	                	if (is_singular("question")) {
	                		$featured_image_in_answers = wpqa_options("featured_image_question_answers");
	                	}else {
	                		$featured_image_in_answers = wpqa_options("featured_image_in_answers");
	                	}
	                	if ($featured_image_in_answers == "on") {
	                		$featured_image = get_comment_meta($comment_id,'featured_image',true);
	                		if ($featured_image != "") {
	                			$img_url = wp_get_attachment_url($featured_image,"full");
	                			$featured_image_answers_lightbox = wpqa_options("featured_image_answers_lightbox");
	                			$featured_image_answer_width = wpqa_options("featured_image_answer_width");
	                			$featured_image_answer_height = wpqa_options("featured_image_answer_height");
	                			$featured_image_answer_width = ($featured_image_answer_width != ""?$featured_image_answer_width:260);
	                			$featured_image_answer_height = ($featured_image_answer_height != ""?$featured_image_answer_height:185);
	                			$link_url = ($featured_image_answers_lightbox == "on"?$img_url:get_permalink($post_id)."#comment-".$comment_id);
	                			$last_image = wpqa_get_aq_resize_img($featured_image_answer_width,$featured_image_answer_height,"",$featured_image);
	                			$featured_answer_position = wpqa_options("featured_answer_position");
	                			if ($featured_answer_position != "after" && isset($last_image) && $last_image != "") {
	                				echo "<div class='featured_image_answer'><a href='".$link_url."'>".$last_image."</a></div>
	                				<div class='clearfix'></div>";
	                			}
	                		}
	                	}

	                	$answer_video = wpqa_options("answer_video");
	                	$video_answer_position = wpqa_options("video_answer_position");
	                	$video_answer_width = wpqa_options("video_answer_width");
						$video_answer_100 = wpqa_options("video_answer_100");
						$video_answer_height = wpqa_options("video_answer_height");
	                	$video_answer_description = get_comment_meta($comment_id,"video_answer_description",true);
						if ($answer_video == "on" && $video_answer_description == "on") {
							$video_answer_type = get_comment_meta($comment_id,"video_answer_type",true);
							$video_answer_id = get_comment_meta($comment_id,"video_answer_id",true);
							if ($video_answer_id != "") {
								if ($video_answer_type == 'youtube') {
									preg_match("/^(?:http(?:s)?:\/\/)?(?:www\.)?(?:m\.)?(?:youtu\.be\/|youtube\.com\/(?:(?:watch)?\?(?:.*&)?v(?:i)?=|(?:embed|v|vi|user)\/))([^\?&\"'>]+)/",$video_answer_id,$matches);
									if (isset($matches[1])) {
										$video_answer_id = $matches[1];
									}
									$type = "https://www.youtube.com/embed/".$video_answer_id;
								}else if ($video_answer_type == 'vimeo') {
									preg_match("/^(?:http(?:s)?:\/\/)?(?:www\.)?(player\.)?vimeo\.com\/([a-z]*\/)*([0-9]{6,11})[?]?.*/",$video_answer_id,$matches);
									if (isset($matches[3])) {
										$video_answer_id = $matches[3];
									}
									$type = "https://player.vimeo.com/video/".$video_answer_id;
								}else if ($video_answer_type == 'daily') {
									preg_match("!^.+dailymotion\.com/(video|hub)/([^_]+)[^#]*(#video=([^_&]+))?|(dai\.ly/([^_]+))!",$video_answer_id,$matches);
									if (isset($matches[2])) {
										$video_answer_id = $matches[2];
									}
									$type = "https://www.dailymotion.com/embed/video/".$video_answer_id;
								}else if ($video_answer_type == 'facebook') {
									$type = "https://www.facebook.com/video/embed?video_id=".$video_answer_id;
								}
								
								$las_video = '<div class="question-video-loop answer-video'.($video_answer_100 == "on"?' question-video-loop-100':'').($video_answer_position == "after"?' question-video-loop-after':'').'"><iframe frameborder="0" allowfullscreen width="'.$video_answer_width.'" height="'.$video_answer_height.'" src="'.$type.'"></iframe></div>';
								
								if ($video_answer_position == "before" && $answer_video == "on" && isset($video_answer_id) && $video_answer_id != "" && $video_answer_description == "on") {
									echo ($las_video);
								}
							}
						}?>

	                	<div<?php echo (isset($its_question) && $its_question == "question" && is_single()?" itemprop='text'":"")?>>
	                		<?php if (isset($answer_args["comment_with_title"]) || isset($answer_args["comment_read_more"])) {
	                			echo '<p>'.wp_html_excerpt($comment->comment_content,apply_filters('wpqa_answer_number',300),'<a class="post-read-more comment-read-more" href="'.get_permalink($post_id).'#comment-'.esc_attr($comment_id).'" rel="bookmark" title="'.esc_attr__('Read more','wpqa').'">'.esc_html__('Read more','wpqa').'</a>').'</p>';
	                		}else {
				            	comment_text($comment_id);
					        }?>
	                	</div>
	                	<div class="clearfix"></div>
	                	<?php if ($video_answer_position == "after" && $answer_video == "on" && isset($video_answer_id) && $video_answer_id != "" && $video_answer_description == "on") {
							echo ($las_video);
						}?>
						<div class="clearfix"></div>
	                	<?php if ($featured_image_in_answers == "on" && isset($featured_answer_position) && $featured_answer_position == "after" && isset($last_image) && $last_image != "") {
	                		echo "<div class='featured_image_answer featured_image_after'><a href='".$link_url."'>".$last_image."</a></div>
	                		<div class='clearfix'></div>";
	                	}
	                	
	                	if (isset($its_question) && $its_question == "question") {
	                		$added_file = get_comment_meta($comment_id,'added_file', true);
	                		if ($added_file != "") {
	                			echo "<a href='".wp_get_attachment_url($added_file)."'>".esc_html__("Attachment","wpqa")."</a><div class='clearfix'></div><br>";
	                		}
	                	}?>
	                	<div class="wpqa_error"></div>
	                	<?php if ($like_answer == true || (isset($its_question) && $its_question == "question")) {
	                		if ($active_vote == "on" && $active_vote_answer == "on") {
	                			if ($owner == false) {
	                				$comment_vote_type = apply_filters("wpqa_comment_vote_type","comment",(isset($post->post_type)?$post->post_type:""));?>
		                			<ul class="question-vote answer-vote<?php echo ($show_dislike_answers != "on"?" answer-vote-dislike":"")?>">
		                				<li><a href="#"<?php echo ((is_user_logged_in() && $comment_user_id != $user_get_current_user_id) || (!is_user_logged_in() && $active_vote_unlogged == "on")?' id="'.$comment_vote_type.'_vote_up-'.$comment_id.'"':'')?> data-type="<?php echo esc_html($comment_vote_type)?>" data-vote-type="up" class="wpqa_vote comment_vote_up<?php echo (is_user_logged_in() && $comment_user_id != $user_get_current_user_id?"":(is_user_logged_in() && $comment_user_id == $user_get_current_user_id?" vote_not_allow":($active_vote_unlogged == "on"?"":" vote_not_user"))).((is_user_logged_in() && $comment_user_id != $user_get_current_user_id) || (!is_user_logged_in() && $active_vote_unlogged == "on")?' vote_allow':'')?>" title="<?php esc_attr_e("Like","wpqa");?>"><i class="<?php echo apply_filters('wpqa_vote_up_icon','icon-up-dir');?>"></i></a></li>
		                				<li class="vote_result"<?php echo (is_single()?' itemprop="upvoteCount"':'')?>><?php echo ($comment_vote != ""?wpqa_count_number($comment_vote):0)?></li>
		                				<li class="li_loader"><span class="loader_3 fa-spin"></span></li>
		                				<?php if ($show_dislike_answers != "on") {?>
		                					<li class="dislike_answers"><a href="#"<?php echo ((is_user_logged_in() && $comment_user_id != $user_get_current_user_id) || (!is_user_logged_in() && $active_vote_unlogged == "on")?' id="'.$comment_vote_type.'_vote_down-'.$comment_id.'"':'')?> data-type="<?php echo esc_html($comment_vote_type)?>" data-vote-type="down" class="wpqa_vote comment_vote_down<?php echo (is_user_logged_in() && $comment_user_id != $user_get_current_user_id?"":(is_user_logged_in() && $comment_user_id == $user_get_current_user_id?" vote_not_allow":($active_vote_unlogged == "on"?"":" vote_not_user"))).((is_user_logged_in() && $comment_user_id != $user_get_current_user_id) || (!is_user_logged_in() && $active_vote_unlogged == "on")?' vote_allow':'')?>" title="<?php esc_attr_e("Dislike","wpqa");?>"><i class="<?php echo apply_filters('wpqa_vote_down_icon','icon-down-dir');?>"></i></a></li>
		                				<?php }?>
		                			</ul>
	                			<?php }
	                		}
	                	}?>
	                	<ul class="comment-reply comment-reply-main">
	                	    <?php if ($answer != "answer" && $answer != "comment") {
	                	    	comment_reply_link( array_merge( $args, array( 'reply_text' => '<i class="icon-reply"></i>'.esc_html__( 'Reply', 'wpqa' ),'login_text' => '<i class="icon-lock"></i>'.esc_html__( 'Login to Reply', 'wpqa' ), 'before' => '<li>', 'after' => '</li>', 'depth' => $depth, 'max_depth' => $args['max_depth'] ) ) );
	                	    }
	                	    do_action("wpqa_action_after_reply_comment",$comment,($like_answer == true || (isset($its_question) && $its_question == "question")?"answer":"comment"));
	                	    $comment_share  = wpqa_options("comment_share");
	                	    $share_facebook = (isset($comment_share["share_facebook"]["value"])?$comment_share["share_facebook"]["value"]:"");
	                	    $share_twitter  = (isset($comment_share["share_twitter"]["value"])?$comment_share["share_twitter"]["value"]:"");
	                	    $share_linkedin = (isset($comment_share["share_linkedin"]["value"])?$comment_share["share_linkedin"]["value"]:"");
	                	    $share_whatsapp = (isset($comment_share["share_whatsapp"]["value"])?$comment_share["share_whatsapp"]["value"]:"");
	                	    if ($share_facebook == "share_facebook" || $share_twitter == "share_twitter" || $share_linkedin == "share_linkedin" || $share_whatsapp == "share_whatsapp") {?>
	                	    	<li class="comment-share question-share question-share-2">
	                	    		<i class="icon-share"></i>
	                	    		<?php esc_html_e("Share","wpqa");
	                	    		wpqa_share($comment_share,$share_facebook,$share_twitter,$share_linkedin,$share_whatsapp,"style_2",$comment_id);?>
	                	    	</li>
	                	    <?php }
	                	    do_action("wpqa_action_after_share_comment",$comment,(isset($its_question) && $its_question == "question"?"answer":"comment"));
	                	    if (isset($its_question) && $its_question == "question" && $answer != "answer") {
	                	    	$best_answer_userself = wpqa_options("best_answer_userself");
		                	    $user_best_answer = esc_attr(get_the_author_meta('user_best_answer',$user_get_current_user_id));
		                	    if ((is_user_logged_in() && $active_best_answer == "on" && $user_get_current_user_id > 0 && (($comment_user_id != $user_get_current_user_id && $user_get_current_user_id == $post->post_author) || ($best_answer_userself == "on" && $comment_user_id == $user_get_current_user_id && $user_get_current_user_id == $post->post_author))) || $user_best_answer == "on" || is_super_admin($user_get_current_user_id)) {
		                	    	if ($the_best_answer != 0 && ($best_answer_comment == "best_answer_comment" || $the_best_answer == $comment_id)) {
		                	        	echo '<li><a class="best_answer_re" data-nonce="'.wp_create_nonce("wpqa_best_answer_nonce").'" title="'.esc_attr__("Cancel the best answer","wpqa").'" href="#"><i class="icon-cancel"></i>'.esc_html__("Cancel the best answer","wpqa").'</a></li>';
		                	    	}
			                	    if ($the_best_answer == 0 || $the_best_answer == "") {?>
			                	    	<li><a class="best_answer_a" data-nonce="<?php echo wp_create_nonce("wpqa_best_answer_nonce")?>" title="<?php esc_attr_e("Select as best answer","wpqa");?>" href="#"><i class="icon-check"></i><?php esc_html_e("Select as best answer","wpqa");?></a></li>
			                	    <?php }
		                	    }
	                	    }?>
	                	    <li class="clearfix last-item-answers"></li>
	                	</ul>
	                	<?php do_action("wpqa_after_answer_links",$comment,$can_edit_comment,$can_edit_comment_after,$time_end,$can_delete_comment,$like_answer,(isset($its_question)?$its_question:false),(isset($active_reports)?$active_reports:false),(isset($active_logged_reports)?$active_logged_reports:false),$owner);
	                	if ((current_user_can('edit_comment',$comment_id) || ($can_edit_comment == "on" && $comment_user_id == $user_get_current_user_id && $comment_user_id != 0 && $user_get_current_user_id != 0 && ($can_edit_comment_after == 0 || $time_end <= $can_edit_comment_after))) || (($can_delete_comment == "on" && $comment_user_id == $user_get_current_user_id && $comment_user_id > 0 && $user_get_current_user_id > 0) || current_user_can('edit_comment',$comment_id) || is_super_admin($user_get_current_user_id)) || (($like_answer == true || (isset($its_question) && $its_question == "question")) && $active_reports == "on" && ((is_user_logged_in() && $comment_user_id != $user_get_current_user_id && $user_get_current_user_id != 0) || (!is_user_logged_in() && $active_logged_reports != "on")))) {?>
		                	<ul class="comment-reply comment-list-links">
		                	    <li class="question-list-details comment-list-details">
									<i class="icon-dot-3"></i>
									<ul>
				                	    <?php if (current_user_can('edit_comment',$comment_id) || ($can_edit_comment == "on" && $comment_user_id == $user_get_current_user_id && $comment_user_id != 0 && $user_get_current_user_id != 0 && ($can_edit_comment_after == 0 || $time_end <= $can_edit_comment_after))) {
				                	    	echo "<li><a class='comment-edit-link edit-comment' href='".esc_url(wpqa_edit_permalink($comment_id,"comment"))."'><i class='icon-pencil'></i>".esc_html__("Edit","wpqa")."</a></li>";
				                	    }
				                	    if (($can_delete_comment == "on" && $comment_user_id == $user_get_current_user_id && $comment_user_id > 0 && $user_get_current_user_id > 0) || current_user_can('edit_comment',$comment_id) || is_super_admin($user_get_current_user_id)) {
				                	    	echo "<li><a class='delete-comment".(isset($its_question) && $its_question == "question"?' delete-answer':'')."' href='".esc_url_raw(add_query_arg(array('delete_comment' => $comment_id,"wpqa_delete_nonce" => wp_create_nonce("wpqa_delete_nonce")),get_permalink($post_id)))."'><i class='icon-trash'></i>".esc_html__("Delete","wpqa")."</a></li>";
				                	    }
			                	    	if (($like_answer == true || (isset($its_question) && $its_question == "question")) && $active_reports == "on" && ((is_user_logged_in() && $comment_user_id != $user_get_current_user_id && $user_get_current_user_id != 0) || (!is_user_logged_in() && $active_logged_reports != "on"))) {
			                	    		if ($owner == false) {?>
				                	    		<li class="report_activated"><a class="report_c" href="<?php echo esc_attr($comment_id)?>"><i class="icon-attention"></i><?php esc_html_e("Report","wpqa")?></a></li>
				                	    	<?php }
				                	    }?>
				               		</ul>
				               	</li>
		                	    <li class="clearfix last-item-answers"></li>
		                	</ul>
		                <?php }
		                do_action("wpqa_action_after_list_comment",$comment,(isset($its_question) && $its_question == "question"?"answer":"comment"));?>
	                </div><!-- End text -->
	                <div class="clearfix"></div>
	            </div><!-- End comment-text -->
	        </div><!-- End comment-body -->
	<?php }
endif;
/* Breadcrumbs */
if (!function_exists('wpqa_breadcrumbs')) :
	function wpqa_breadcrumbs ($text = "",$breadcrumb_right = true) {
		global $post,$wp_query;
		$active_points         = wpqa_options("active_points");
		$breadcrumbs_separator = wpqa_options("breadcrumbs_separator");
		$breadcrumbs_separator = ($breadcrumbs_separator != ""?$breadcrumbs_separator:"/");
		$active_cover_category = wpqa_options("active_cover_category");
		$post_type             = get_post_type();
	    $home                  = '<i class="icon-home"></i>'.esc_html__('Home','wpqa');
	    $before_schema         = '<span class="crumbs-span">'.$breadcrumbs_separator.'</span>';
	    $before                = $before_schema.'<span class="current">';
	    $after                 = '</span>';
	    $user_id               = get_current_user_id();
	    
		echo '<div class="breadcrumbs">
			<span class="crumbs">
				<span itemscope itemtype="https://schema.org/BreadcrumbList">
					'.wpqa_breadcrumbs_schema(esc_url(home_url('/')),$home,1);
				    $text_filter = apply_filters("wpqa_breadcrumbs_text",false);
				    if (isset($text) && $text != "") {
				    	echo ($before . $text . $after);
				    }else if ($text_filter != "") {
				    	echo ($before . $text_filter . $after);
				    }else if (wpqa_is_user_profile()) {
				    	$wpqa_user_id = (int)get_query_var(apply_filters('wpqa_user_id','wpqa_user_id'));
			    		$user_name = get_the_author_meta("display_name",$wpqa_user_id);
			    		echo ($before_schema.(wpqa_is_home_profile()?$user_name:wpqa_breadcrumbs_schema(wpqa_profile_url($wpqa_user_id),$user_name,2,"yes")));
			    		if (wpqa_user_title()) {
			    			echo ($before.wpqa_profile_title().$after);
			    		}
				    }else if (is_search() || wpqa_is_search()) {
				    	$search_value = wpqa_search();
				    	if ($search_value != "") {
				    		$out_data = esc_html__('Search results for ', 'wpqa') . '"' . $search_value . '"';
				    	}else {
				    		$out_data = esc_html__('Search', 'wpqa');
				    	}
				    	echo ($before . $out_data . $after);
				    }else if (wpqa_is_buy_points()) {
				    	echo ($before . esc_html__('Buy points', 'wpqa') . $after);
				    }else if (wpqa_is_login()) {
				    	echo ($before . esc_html__('Login', 'wpqa') . $after);
				    }else if (wpqa_is_signup()) {
				    	echo ($before . esc_html__('Signup', 'wpqa') . $after);
				    }else if (wpqa_is_lost_password()) {
				    	echo ($before . esc_html__('Lost password', 'wpqa') . $after);
				    }else if (wpqa_is_add_questions()) {
				    	$wpqa_add_question_user = wpqa_add_question_user();
				    	if ($wpqa_add_question_user > 0) {
				    		$display_name = get_the_author_meta('display_name',$wpqa_add_question_user);
				    	}
				    	echo ($before . esc_html__('Ask question', 'wpqa') . ($wpqa_add_question_user > 0?" ".esc_html__("to","wpqa")." ".$display_name:"") . $after);
				    }else if (wpqa_is_add_category()) {
				    	echo ($before . esc_html__('Add category', 'wpqa') . $after);
				    }else if (wpqa_is_edit_questions()) {
				    	echo ($before . esc_html__('Edit question', 'wpqa') . $after);
				    }else if (wpqa_is_add_posts()) {
				    	echo ($before . esc_html__('Add post', 'wpqa') . $after);
				    }else if (wpqa_is_edit_posts()) {
				    	echo ($before . esc_html__('Edit post', 'wpqa') . $after);
				    }else if (wpqa_is_edit_comments()) {
				    	echo ($before . esc_html__('Edit comment', 'wpqa') . $after);
				    }else if (wpqa_is_edit_tags()) {
				    	echo ($before . esc_html__('Edit tags', 'wpqa') . $after);
				    }else if (is_category() || is_tag() || is_tax()) {
				        $term = $wp_query->get_queried_object();
				    	$taxonomy = get_taxonomy( $term->taxonomy );
				    	if ( isset($item) && is_array($item) && ( is_taxonomy_hierarchical( $term->taxonomy ) && $term->parent ) && $parents = wpqa_breadcrumbs_get_term_parents( $term->parent, $term->taxonomy ) )
				    		$item = array_merge( $item, $parents );
				    	$item['last'] = $term->name;
				    	if (isset($term->term_id)) {
				    		echo wpqa_get_taxonomy_parents($term->term_id,$taxonomy->name,true,$term->term_id,array(),$before_schema);
				    	}
				        echo ($before.single_cat_title('',false).$after);
				    }else if (is_day()) {
				        echo ($before_schema.wpqa_breadcrumbs_schema(esc_url(get_year_link(get_the_time('Y'))),get_the_time('Y'),2,"yes")).
				        ($before_schema.wpqa_breadcrumbs_schema(esc_url(get_month_link(get_the_time('Y'),get_the_time('m'))),get_the_time('F'),3,"yes")).
				        ($before . get_the_time('d') . $after);
				    }else if (is_month()) {
				        echo ($before_schema.wpqa_breadcrumbs_schema(esc_url(get_year_link(get_the_time('Y'))),get_the_time('Y'),2,"yes")).
				        ($before . get_the_time('F') . $after);
				    }else if (is_year()) {
				        echo ($before . get_the_time('Y') . $after);
				    }else if (is_single() && !is_attachment()) {
				        if ($post_type != 'post') {
				        	if ($post_type == 'question') {
				    			echo ($before_schema.wpqa_breadcrumbs_schema(get_post_type_archive_link("question"),esc_html__("Questions","wpqa"),2,'yes')).
				    			($before . esc_html__("Q","wpqa")." ". $post->ID . $after);
				        	}else {
				        		$post_type = get_post_type_object($post_type);
				        		$slug = $post_type->rewrite;
				        		echo ($before_schema.wpqa_breadcrumbs_schema(esc_url($homeLink.'/'.$slug['slug']).'/',$post_type->labels->singular_name,2,'yes')).
				        		($before.get_the_title().$after);
				        	}
				        }else {
				            $cat = get_the_category();
				            if (isset($cat) && is_array($cat) && isset($cat[0])) {
								$term_id = $cat[0];
								$taxonomy = 'category';
								$list = '';
								$term = get_term( $term_id, $taxonomy );
								if ( is_wp_error( $term ) ) {
									return $term;
								}
								if ( ! $term ) {
									return $list;
								}
								$term_id = $term->term_id;
								$parents = get_ancestors( $term_id, $taxonomy, 'taxonomy' );
								array_unshift( $parents, $term_id );
								$counter = 1;
								foreach ( array_reverse( $parents ) as $term_id ) {
									$counter++;
									$parent = get_term( $term_id, $taxonomy );
									$name   = $parent->name;
									$list  .= $before_schema.wpqa_breadcrumbs_schema(esc_url(get_term_link($parent->term_id,$taxonomy)),$name,$counter,"yes");
								}
				            	echo ($list);
				            }
				            echo ($before.get_the_title().$after);
				        }
				    }else if (!is_single() && !is_page() && $post_type != 'post') {
				        $post_type = get_post_type_object($post_type);
				    	echo ($before . (isset($post_type->labels->singular_name) && !is_404()?$post_type->labels->singular_name:esc_html__("Error 404","wpqa")) . $after);
				    }else if (is_attachment()) {
				        $parent = get_post($post->post_parent);
				        $cat = get_the_category($parent->ID);
				        echo ($before . get_the_title() . $after);
				    }else if (is_page() && !$post->post_parent) {
				        echo ($before . get_the_title() . $after);
				    }else if (is_page() && $post->post_parent) {
				        $parent_id  = $post->post_parent;
				        $breadcrumbs = array();
				        $counter = 1;
				        while ($parent_id) {
				        	$counter++;
				            $page = get_page($parent_id);
				            $breadcrumbs[] = wpqa_breadcrumbs_schema(esc_url(get_permalink($page->ID)),get_the_title($page->ID),$counter,'yes');
				            $parent_id  = $page->post_parent;
				        }
				        $breadcrumbs = array_reverse($breadcrumbs);
				        foreach ($breadcrumbs as $crumb) echo ($before_schema.$crumb);
				        echo ($before . get_the_title() . $after);
				    }else if (is_tag()) {
				        echo ($before . esc_html__('Posts tagged ', 'wpqa') . '"' . single_tag_title('', false) . '"' . $after);
				    }else if (is_404()) {
				        echo ($before . esc_html__('Error 404 ', 'wpqa') . $after);
				    }
				    do_action("wpqa_filter_breadcrumb",$before,$after);
				    if (get_query_var('paged')) {
				        echo ($before . esc_html__('Page', 'wpqa') . ' ' . esc_attr(get_query_var('paged')) . $after);
				    }
				echo '</span>
			</span>';
			if ($breadcrumb_right == true) {
				$live_search = wpqa_options('live_search');
				$category_filter = wpqa_options('category_filter');
				echo '<div class="breadcrumb-right">';
					$tax_archive = apply_filters('wpqa_tax_archive',false);
					$tax_filter = apply_filters("wpqa_before_question_category",false);
					$tax_question = apply_filters("wpqa_question_category","question-category");
					if (wpqa_is_user_profile()) {
						if (wpqa_is_user_owner()) {
							if (wpqa_is_user_edit_profile()) {
								echo '<div class="profile-setting">
									<a href="#edit-profile" data-type="setting"'.(isset($_POST["profile_type"]) && $_POST["profile_type"] == "setting"?" class='active-tab'":(empty($_POST["profile_type"])?" class='active-tab'":"")).'>'.esc_html__("Edit profile","wpqa").'</a>
									<a href="#change-password" data-type="password"'.(isset($_POST["profile_type"]) && $_POST["profile_type"] == "password"?" class='active-tab'":"").'>'.esc_html__("Change Password","wpqa").'</a>
								</div><div class="clearfix"></div>';
							}else {?>
								<div class="question-navigation edit-profile"><a href="<?php echo esc_url(wpqa_get_profile_permalink($user_id,"edit"))?>"><i class="icon-pencil"></i><?php esc_html_e("Edit profile","wpqa")?></a></div>
							<?php }
						}else {
							$ask_question_to_users = wpqa_options("ask_question_to_users");
							if ($ask_question_to_users == "on") {
								$display_name = get_the_author_meta("display_name",$wpqa_user_id);?>
								<div class="ask-question"><a href="<?php echo esc_url(wpqa_add_question_permalink("user"))?>" class="button-default ask-question-user"><?php echo esc_html__("Ask","wpqa")." ".$display_name?></a></div>
							<?php }
						}
					}else if (!is_tag() && !is_tax("question_tags") && ((is_category() || (!is_post_type_archive() && is_archive()) || is_tax("question-category") || $tax_filter == true || $tax_archive == true || is_page_template("template-categories.php") || is_post_type_archive("question")) && $category_filter == "on")) {
						if (is_page_template("template-categories.php")) {
							$cat_search = get_post_meta($post->ID,prefix_meta.'cat_search',true);
							$cat_filter = get_post_meta($post->ID,prefix_meta.'cat_filter',true);
						}else {
							$cat_search = wpqa_options("cat_search");
							$cat_filter = "";
						}
						if ($tax_archive != true || ($tax_filter == true)) {
							$cats_search = (is_tax("question-category") || $tax_filter == true || is_post_type_archive("question")?$tax_question:"category");
							$exclude = apply_filters('wpqa_exclude_question_category',array());
							$args = array_merge($exclude,array(
							'child_of'     => 0,
							'parent'       => '',
							'orderby'      => 'name',
							'order'        => 'ASC',
							'hide_empty'   => 1,
							'hierarchical' => 1,
							'exclude'      => '',
							'include'      => '',
							'number'       => '',
							'taxonomy'     => $cats_search,
							'pad_counts'   => false ));
							$options_categories = get_categories($args);
							if ((!is_page_template("template-categories.php") && isset($options_categories) && is_array($options_categories)) || $cat_search == "on" || ($cat_filter == "on" && is_page_template("template-categories.php"))) {?>
								<div class="search-form">
									<?php do_action("wpqa_before_select_filter");
									if ($cat_filter == "on" || is_page_template("template-categories.php")) {
										$cat_sort = get_post_meta($post->ID,prefix_meta.'cat_sort',true);
										$cat_sort = ($cat_sort != ""?$cat_sort:"name");
										$g_cat_filter = (isset($_GET["cat_filter"]) && $_GET["cat_filter"] != ""?esc_html($_GET["cat_filter"]):$cat_sort);
										echo '<form method="get" class="search-filter-form">
											<span class="styled-select cat-filter">
												<select name="cat_filter" onchange="this.form.submit()">
													<option value="count" '.selected($g_cat_filter,"count",false).'>'.esc_html__('Popular','wpqa').'</option>
													<option value="followers" '.selected($g_cat_filter,"followers",false).'>'.esc_html__('Followers','wpqa').'</option>
													<option value="name" '.selected($g_cat_filter,"name",false).'>'.esc_html__('Name','wpqa').'</option>
												</select>
											</span>
										</form>';
									}
									if (!is_page_template("template-categories.php") && isset($options_categories) && is_array($options_categories)) {?>
										<div class="search-filter-form">
											<span class="styled-select cat-filter">
												<select class="home_categories">
													<option<?php echo (is_post_type_archive("question")?' selected="selected"':'')?> value="<?php echo (is_tax("question-category") || $tax_filter == true || is_post_type_archive("question")?get_post_type_archive_link("question"):"")?>"><?php esc_html_e('All Categories','wpqa')?></option>
													<?php foreach ($options_categories as $category) {
														echo apply_filters("wpqa_select_filter_categories",'<option '.(is_category() || is_tax("question-category") || $tax_filter == true?selected(esc_attr(get_query_var((is_category()?'cat':'term'))),(is_category()?$category->term_id:$category->slug),false):"").' value="'.get_term_link($category->slug,is_tax($tax_question) || $tax_filter == true || is_post_type_archive("question")?$tax_question:"category").'">'.esc_html($category->name).'</option>',$tax_filter,$category,$tax_question);
													}?>
												</select>
											</span>
										</div>
									<?php }
									if (is_tax("question-category") && $active_cover_category != "on") {
										$tax_id = (int)get_query_var('wpqa_term_id');
										echo wpqa_follow_cat_button($tax_id,$user_id);
									}
									if ($cat_search == "on") {
										if (is_page_template("template-categories.php")) {
											$cats_tax = get_post_meta($post->ID,prefix_meta.'cats_tax',true);
											$cats_tax = ($cats_tax != ""?$cats_tax:"question");
										}else {
											$cats_tax = (is_tax("question-category") || $tax_filter == true || is_post_type_archive("question")?"question":"post");
										}
										echo '<form method="get" action="'.esc_url(wpqa_get_search_permalink()).'" class="search-input-form main-search-form">
											<input class="search-input'.($live_search == "on"?" live-search live-search-icon":"").'"'.($live_search == "on"?" autocomplete='off'":"").' type="search" name="search" placeholder="'.esc_attr__('Type to find...','wpqa').'">';
											if ($live_search == "on") {
												echo '<div class="loader_2 search_loader"></div>
												<div class="search-results results-empty"></div>';
											}
											echo '<button class="button-search"><i class="icon-search"></i></button>
											<input type="hidden" name="search_type" class="search_type" value="'.($cats_tax == "post"?"category":$tax_question).'">
										</form>';
									}
									do_action("wpqa_after_select_filter");?>
								</div><!-- End search-form -->
							<?php }
						}
					}else if (is_page_template("template-tags.php") || is_tag() || is_tax("question_tags")) {
						if (is_page_template("template-tags.php")) {
							$tag_search = get_post_meta($post->ID,prefix_meta.'tag_search',true);
							$tag_filter = get_post_meta($post->ID,prefix_meta.'tag_filter',true);
						}else {
							$tag_search = wpqa_options("tag_search");
							$tag_filter = "";
						}
						if ($tag_search == "on" || $tag_filter == "on") {
							echo '<div class="search-form">';
								if (is_page_template("template-tags.php") && $tag_filter == "on") {
									$tag_sort = get_post_meta($post->ID,prefix_meta.'tag_sort',true);
									$tag_sort = ($tag_sort != ""?$tag_sort:"name");
									$g_tag_filter = (isset($_GET["tag_filter"]) && $_GET["tag_filter"] != ""?esc_html($_GET["tag_filter"]):$tag_sort);
									echo '<form method="get" class="search-filter-form">
										<span class="styled-select tag-filter">
											<select name="tag_filter" onchange="this.form.submit()">
												<option value="count" '.selected($g_tag_filter,"count",false).'>'.esc_html__('Popular','wpqa').'</option>
												<option value="followers" '.selected($g_tag_filter,"followers",false).'>'.esc_html__('Followers','wpqa').'</option>
												<option value="name" '.selected($g_tag_filter,"name",false).'>'.esc_html__('Name','wpqa').'</option>
											</select>
										</span>
									</form>';
								}
								if (is_tax("question_tags")) {
									$tax_id = (int)get_query_var('wpqa_term_id');
									echo wpqa_follow_cat_button($tax_id,$user_id,'tag');
								}
								if ($tag_search == "on") {
									if (is_page_template("template-tags.php")) {
										$tags_tax = get_post_meta($post->ID,prefix_meta.'tags_tax',true);
										$tags_tax = ($tags_tax != ""?$tags_tax:"question");
									}else {
										$tags_tax = (is_tax("question_tags")?"question":"post");
									}
									echo '<form method="get" action="'.esc_url(wpqa_get_search_permalink()).'" class="search-input-form main-search-form">
										<input class="search-input'.($live_search == "on"?" live-search live-search-icon":"").'"'.($live_search == "on"?" autocomplete='off'":"").' type="search" name="search" placeholder="'.esc_attr__('Type to find...','wpqa').'">';
										if ($live_search == "on") {
											echo '<div class="loader_2 search_loader"></div>
											<div class="search-results results-empty"></div>';
										}
										echo '<button class="button-search"><i class="icon-search"></i></button>
										<input type="hidden" name="search_type" class="search_type" value="'.($tags_tax == "post"?"post_tag":"question_tags").'">
									</form>';
								}
							echo '</div>';
						}
					}else if (is_page_template("template-users.php")) {
						$user_search = get_post_meta($post->ID,prefix_meta.'user_search',true);
						$user_filter = get_post_meta($post->ID,prefix_meta.'user_filter',true);
						if ($user_search == "on" || $user_filter == "on") {
							echo '<div class="search-form">';
								if ($user_filter == "on") {
									$user_sort = get_post_meta($post->ID,prefix_meta.'user_sort',true);
									$user_sort = ($user_sort != ""?$user_sort:"user_registered");
									$g_user_filter = (isset($_GET["user_filter"]) && $_GET["user_filter"] != ""?esc_html($_GET["user_filter"]):$user_sort);
									echo '<form method="get" class="search-filter-form">
										<span class="styled-select user-filter">
											<select name="user_filter" onchange="this.form.submit()">
												<option value="user_registered" '.selected($g_user_filter,"user_registered",false).'>'.esc_html__('Date Register','wpqa').'</option>
												<option value="display_name" '.selected($g_user_filter,"display_name",false).'>'.esc_html__('Name','wpqa').'</option>
												<option value="ID" '.selected($g_user_filter,"ID",false).'>'.esc_html__('ID','wpqa').'</option>
												<option value="question_count" '.selected($g_user_filter,"question_count",false).'>'.esc_html__('Questions','wpqa').'</option>
												<option value="answers" '.selected($g_user_filter,"answers",false).'>'.esc_html__('Answers','wpqa').'</option>
												<option value="the_best_answer" '.selected($g_user_filter,"the_best_answer",false).'>'.esc_html__('Best Answers','wpqa').'</option>';
												if ($active_points == "on") {
													echo '<option value="points" '.selected($g_user_filter,"points",false).'>'.esc_html__('Points','wpqa').'</option>';
												}
												echo '<option value="followers" '.selected($g_user_filter,"followers",false).'>'.esc_html__('Followers','wpqa').'</option>
												<option value="post_count" '.selected($g_user_filter,"post_count",false).'>'.esc_html__('Posts','wpqa').'</option>
												<option value="comments" '.selected($g_user_filter,"comments",false).'>'.esc_html__('Comments','wpqa').'</option>
											</select>
										</span>
									</form>';
								}
								if ($user_search == "on") {
									echo '<form method="get" action="'.esc_url(wpqa_get_search_permalink()).'" class="search-input-form main-search-form">
										<input class="search-input'.($live_search == "on"?" live-search live-search-icon":"").'"'.($live_search == "on"?" autocomplete='off'":"").' type="search" name="search" placeholder="'.esc_attr__('Type to find...','wpqa').'">';
										if ($live_search == "on") {
											echo '<div class="loader_2 search_loader"></div>
											<div class="search-results results-empty"></div>';
										}
										echo '<button class="button-search"><i class="icon-search"></i></button>
										<input type="hidden" name="search_type" class="search_type" value="users">
									</form>';
								}
							echo '</div>';
						}
					}else if (is_singular("question")) {
						$question_navigation = wpqa_options("question_navigation");
						$question_nav_category = wpqa_options("question_nav_category");
						$custom_page_setting = get_post_meta($post->ID,prefix_meta.'custom_page_setting',true);
						if ($custom_page_setting == "on") {
							$question_navigation = get_post_meta($post->ID,prefix_meta.'post_navigation',true);
							$question_nav_category = get_post_meta($post->ID,prefix_meta.'question_nav_category',true);
						}
						if ($question_navigation == "on") {
							if ($question_nav_category == "on") {
								$previous_post = get_previous_post(true,'','question-category');
								$next_post = get_next_post(true,'','question-category');
							}else {
								$previous_post = get_previous_post();
								$next_post = get_next_post();
							}?>
							<div class="question-navigation">
								<?php if (isset($next_post) && is_object($next_post)) {?>
									<a class="nav-next" href="<?php echo get_permalink($next_post->ID)?>"><?php esc_html_e("Next","wpqa")?><i class="icon-right-open"></i></a>
								<?php }
								if (isset($previous_post) && is_object($previous_post)) {?>
									<a class="nav-previous" href="<?php echo get_permalink($previous_post->ID)?>"><i class="icon-left-open"></i></a>
								<?php }?>
							</div><!-- End page-navigation -->
						<?php }
						$question_stats = apply_filters('wpqa_question_stats',true);
						$the_best_answer = get_post_meta($post->ID,"the_best_answer",true);
						$comments = get_comments('post_id='.$post->ID);
						$closed_question = get_post_meta($post->ID,"closed_question",true);
						if ($question_stats == true && ($closed_question == 1 || (isset($the_best_answer) && $the_best_answer != "" && $comments) || ($the_best_answer == "" && $comments))) {?>
							<div class="question-stats">
								<?php if ($closed_question == 1) {?>
									<span class="question-stats-closed question-closed"><i class="icon-cancel"></i><?php esc_html_e("Closed","wpqa")?></span>
								<?php }else if (isset($the_best_answer) && $the_best_answer != "" && $comments) {?>
									<span class="question-stats-answered question-answered-done"><i class="icon-check"></i><?php esc_html_e("Answered","wpqa")?></span>
								<?php }else if ($the_best_answer == "" && $comments) {?>
									<span class="question-stats-process"><i class="icon-flash"></i><?php esc_html_e("In Process","wpqa")?></span>
								<?php }?>
							</div><!-- End question-stats -->
						<?php }
					}
					do_action("wpqa_right_breadcrumb");
					echo '<div class="clearfix"></div>
				</div><!-- End breadcrumb-right -->';
			}
		echo '</div><!-- End breadcrumbs -->';
	}
endif;
/* Get taxonomy parents */
if (!function_exists('wpqa_get_taxonomy_parents')) :
	function wpqa_get_taxonomy_parents( $id, $taxonomy = 'category', $link = false,$main_id = '', $visited = array(), $before = "" ) {
		$out = '';
		$parent = get_term( $id, $taxonomy );
		if ( is_wp_error( $parent ) ) {
			return $parent;
		}
		$name = $parent->name;
		if ( $parent->parent && ( $parent->parent != $parent->term_id ) && is_array($visited) && !in_array( $parent->parent, $visited ) ) {
			$visited[] = $parent->parent;
			$out .= $before.wpqa_get_taxonomy_parents( $parent->parent, $taxonomy, $link, $visited, $before );
		}
		if ( $link ) {
			if ($parent->term_id != $main_id) {
				$out .= '<a href="' . esc_url( get_term_link( $parent,$taxonomy ) ) . '" title="' . esc_attr( $parent->name ) . '">'.$name.'</a>';
			}
		}else {
			$out .= $name;
		}
		return $out;
	}
endif;
/* Get term parents */
if (!function_exists('wpqa_breadcrumbs_get_term_parents')) :
	function wpqa_breadcrumbs_get_term_parents( $parent_id = '', $taxonomy = '' ) {
		$html = array();
		$parents = array();
		if ( empty( $parent_id ) || empty( $taxonomy ) )
			return $parents;
		$counter = 1;
		while ( $parent_id ) {
			$counter++;
			$parent = get_term( $parent_id, $taxonomy );
			$parents[] = wpqa_breadcrumbs_schema(esc_url(get_term_link($parent,$taxonomy)),$parent->name,$counter,"yes");
			$parent_id = $parent->parent;
		}
		if ( $parents )
			$parents = array_reverse( $parents );
		return $parents;
	}
endif;
/* Breadcrumbs schema */
if (!function_exists('wpqa_breadcrumbs_schema')) :
	function wpqa_breadcrumbs_schema($link,$name,$position,$current = '') {
		return '<span'.($current != ''?' class="current"':'').' itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
			<meta itemprop="position" content="'.$position.'">
			<a itemprop="item" href="'.$link.'" title="'.esc_attr(str_replace('<i class="icon-home"></i>','',$name)).'">
				<span itemprop="name">'.$name.'</span>
			</a>
		</span>';
	}
endif;
/* Set post & question stats */
if (!is_admin()) {
	$active_post_stats = wpqa_options("active_post_stats");
	if ($active_post_stats == "on") {
		add_action('wp_head','wpqa_set_post_stats',1000);
	}
	if (!function_exists('wpqa_set_post_stats')) :
		function wpqa_set_post_stats() {
		    $post_id = get_the_ID();
		    if (is_single($post_id) || is_page($post_id)) {
		    	$active_stats = true;
		    	if (is_single($post_id)) {
		    		global $post;
		    		$user_id     = get_current_user_id();
		    		$yes_private = (is_singular("question")?wpqa_private($post_id,$post->post_author,$user_id):1);
		    		
		    		if (!is_super_admin($user_id) && $yes_private != 1) {
		    			$active_stats = false;
		    		}
		    	}
		    	if ($active_stats == true) {
		    		$post_meta_stats = wpqa_options("post_meta_stats");
		    		$cache_post_stats = wpqa_options("cache_post_stats");
					$post_meta_stats = ($post_meta_stats != ""?$post_meta_stats:"post_stats");
			        $current_stats = get_post_meta($post_id,$post_meta_stats,true);
			        $visit_cookie = wpqa_options("visit_cookie");
			        if ($visit_cookie != "on" || ($visit_cookie == "on" && !isset($_COOKIE[wpqa_options("uniqid_cookie").'wpqa_post_stats'.$post_id]))) {
			        	global $post;
				        if (!isset($current_stats)) {
				            add_post_meta($post_id,$post_meta_stats,1);
				            if ($cache_post_stats == "on") {
					            set_transient($post_meta_stats.$post->ID,(int)$current_stats+1,60*60*24);
					        }
				        }else {
				            update_post_meta($post_id,$post_meta_stats,(int)$current_stats+1);
				            if ($cache_post_stats == "on") {
				            	$post_stats = get_transient($post_meta_stats.$post->ID);
				            	if ($post_stats == false) {
						            set_transient($post_meta_stats.$post->ID,(int)$current_stats+1,60*60*24);
						        }
					        }
				        }
			        }
			        if ($visit_cookie == "on") {
			        	setcookie(wpqa_options("uniqid_cookie").'wpqa_post_stats'.$post_id,"wpqa_post_stats",time()+3600*24*365,'/');
			        }
		        }
		    }
		}
	endif;
}
/* Update profile */
if (!function_exists('wpqa_update_profile')) :
	function wpqa_update_profile($user_id) {
		$update_profile = "";
		if (is_user_logged_in()) {
			$update_profile = get_user_meta($user_id,"update_profile",true);
			if (is_page()) {
				global $post;
				$login_only = get_post_meta($post->ID,prefix_meta."login_only",true);
				$update_profile = ($update_profile == "yes" && $login_only != "on"?"yes":"no");
			}
		}
		return $update_profile;
	}
endif;
/* Get edit profile page */
if (!function_exists('wpqa_update_edit_profiel')) :
	function wpqa_update_edit_profiel($user_id,$update_profile) {
		if ($update_profile == "yes") {
			echo '<div class="alert-message"><i class="icon-lamp"></i><p>'.esc_html__("Kindly fill the required fields, You need to fill all the required fields.","wpqa").'</p></div>';
			$nicename = wpqa_get_user_nicename($user_id);
			$templates = array(
				'edit-'.$nicename.'.php',
				'edit-'.$user_id.'.php',
				'edit.php',
				'profile.php',
			);
			if (isset($templates) && is_array($templates) && !empty($templates)) {
				$wpqa_get_template = wpqa_get_template($templates,"profile/");
				if ($wpqa_get_template) {
					include $wpqa_get_template;
				}
			}
			get_footer();
			die();
		}
	}
endif;
/* Head content */
add_action('wpqa_head_content','wpqa_head_content');
if (!function_exists('wpqa_head_content')) :
	function wpqa_head_content($login = "",$its_not_login = false) {
		$user_id = get_current_user_id();
		if (is_user_logged_in()) {
			$user_is_login = get_userdata($user_id);
			$user_login_group = (is_array($user_is_login->caps)?key($user_is_login->caps):"");
			$roles = $user_is_login->allcaps;
			if ($login == "") {
				$active_points_category = wpqa_options("active_points_category");
				if ($active_points_category == "on") {
					$categories_user_points = get_user_meta($user_id,"categories_user_points",true);
					if (is_array($categories_user_points) && !empty($categories_user_points)) {
						$category_with_points = array();
						foreach ($categories_user_points as $category) {
							$category_with_points[$category] = (int)get_user_meta($user_id,"points_category".$category,true);
						}
						arsort($category_with_points);
						foreach ($category_with_points as $category => $points) {
							$show_badge_today = get_user_meta($user_id,"show_badge_today",true);
							if ($show_badge_today != date("j_n_Y")) {
								$get_badge = wpqa_get_badge($user_id,"name",$points);
								$badge_category = wpqa_get_badge($user_id,"key",$points)."_".$category;
								$wpqa_get_badge = get_user_meta($user_id,$badge_category,true);
								update_user_meta($user_id,$badge_category,$badge_category);
								if ($wpqa_get_badge == "" || ($badge_category != $wpqa_get_badge)) {
									$get_term = get_term($category,'question-category');
									$new_badge = $get_badge;
									$get_badge_color = wpqa_get_badge($user_id,"color",$points);
									update_user_meta($user_id,"show_badge_today",date("j_n_Y"));
									break;
								}
							}
						}
					}
				}else {
					$first_key = wpqa_get_badge($user_id,"first_key");
					if ($first_key === "") {
						$get_badge = wpqa_get_badge($user_id,"name");
						$wpqa_get_badge = get_user_meta($user_id,$get_badge,true);
						if ($wpqa_get_badge == "" || ($get_badge != $wpqa_get_badge)) {
							$new_badge = $get_badge;
							$get_badge_color = wpqa_get_badge($user_id,"color");
							update_user_meta($user_id,$get_badge,$get_badge);
						}
					}
				}
				if (isset($new_badge) && $new_badge != "") {
					do_action("wpqa_action_get_new_badge",$user_id,$new_badge,(isset($category) && $category > 0?$category:0),(isset($points) && $points > 0?$points:0));
				}
				$_pop_notification = (int)get_user_meta($user_id,$user_id."_pop_notification",true);
				if ($_pop_notification > 0) {
					global $post;
					$pop_notification = get_user_meta($user_id,$user_id."_pop_notification_".$_pop_notification,true);
					$pop_notification_pages = explode(",",$pop_notification["pages"]);
					if (!isset($pop_notification["end"]) || (isset($pop_notification["end"]) && $pop_notification["end"] != "end")) {
						if ((isset($pop_notification["post_id"]) && $pop_notification["post_id"] != "" && (is_page() || is_single()) && $pop_notification["post_id"] == $post->ID) || (((is_front_page() || is_home()) && $pop_notification["home_pages"] == "home_page") || $pop_notification["home_pages"] == "all_pages" || ($pop_notification["home_pages"] == "custom_pages" && is_page() && isset($pop_notification_pages) && is_array($pop_notification_pages) && isset($post->ID) && in_array($post->ID,$pop_notification_pages)))) {
							$show_pop_notification = true;
							if (isset($pop_notification["notification_time"]) && $pop_notification["notification_time"] == "one_time") {
								$pop_notification["end"] = "end";
								update_user_meta($user_id,$user_id."_pop_notification_".$_pop_notification,$pop_notification);
							}
						}
					}
				}
			}
		}
		if ($login != "login" || $login == "") {
			$confirm_email = wpqa_options("confirm_email");
			if ($login != "login" && is_user_logged_in() && $confirm_email == "on") {
				$if_user_id = get_user_by("id",$user_id);
				if (isset($if_user_id->caps["activation"]) && $if_user_id->caps["activation"] == 1) {
					$site_users_only = "yes";
				}
			}
			
			if (($login == "" && isset($show_pop_notification)) || ($login == "" && isset($new_badge) && $new_badge != "") || ($login != "login" && isset($_POST["form_type"]) && (($_POST["form_type"] == "add_question" && isset($_POST["question_popup"]) && $_POST["question_popup"] == "popup") || ($_POST["form_type"] == "add_post" && isset($_POST["post_popup"]) && $_POST["post_popup"] == "popup") || ($_POST["form_type"] == "add_message" && isset($_POST["message_popup"]) && $_POST["message_popup"] == "popup") || $_POST["form_type"] == "wpqa-signup" || $_POST["form_type"] == "wpqa-login" || $_POST["form_type"] == "wpqa-forget"))) {?>
				<script type="text/javascript">
					jQuery(document).ready(function($) {
						function wrap_pop() {
							jQuery(".wrap-pop").on("click",function () {
								jQuery.when(jQuery(".panel-pop").fadeOut(200)).done(function() {
									jQuery(this).css({"top":"-100%","display":"none"});
									jQuery(".wrap-pop").remove();
								});
							});
						}
						
						<?php if ($login == "" && isset($show_pop_notification)) {
							$pop_up = $form_type = "wpqa-notification";
						}else if ($login == "" && isset($new_badge) && $new_badge != "") {
							$pop_up = $form_type = "wpqa-badge";
						}else if ($login != "login") {
							$form_type = (isset($_POST["form_type"])?esc_html($_POST["form_type"]):"");
							if ($form_type == "wpqa-signup") {
								$pop_up = "signup-panel";
							}else if ($form_type == "wpqa-forget") {
								$pop_up = "lost-password";
							}else if ($form_type == "wpqa-login") {
								$pop_up = "login-panel";
							}else if ($form_type == "add_question" && isset($_POST["user_id"]) && esc_html($_POST["user_id"]) != "" && isset($_POST["question_popup"]) && $_POST["question_popup"] == "popup") {
								$pop_up = "wpqa-question-user";
							}else if ($form_type == "add_question" && isset($_POST["question_popup"]) && $_POST["question_popup"] == "popup") {
								$pop_up = "wpqa-question";
							}else if ($form_type == "add_post" && isset($_POST["post_popup"]) && $_POST["post_popup"] == "popup") {
								$pop_up = "wpqa-post";
							}else if ($form_type == "add_message" && isset($_POST["message_popup"]) && $_POST["message_popup"] == "popup") {
								$pop_up = "wpqa-message";
							}
						}
						if (isset($pop_up) && $pop_up != "") {?>
							panel_pop("#<?php echo esc_js($pop_up)?>","<?php echo esc_js($form_type)?>");
							
							function panel_pop(whatId,fromType) {
								var data_width = jQuery(whatId).attr("data-width");
								jQuery(".panel-pop").css({"top":"-100%","display":"none"});
								jQuery(".wrap-pop").remove();
								var cssMargin = (jQuery("body.rtl").length?"margin-right":"margin-left");
								var cssValue = "-"+(data_width !== undefined && data_width !== false?data_width/2:"")+"px";
								if (jQuery(whatId).length) {
									jQuery(whatId).css("width",(data_width !== undefined && data_width !== false?data_width:"")+"px").css(cssMargin,cssValue).show().animate({"top":"7%"},200);
									jQuery("html,body").animate({scrollTop:0},200);
									jQuery("body").prepend("<div class='wrap-pop'></div>");
								}
								wrap_pop();
							}
						<?php }?>
					});
				</script>
			<?php }
		}
		
		if (is_user_logged_in()) {
			if (isset($show_pop_notification)) {?>
				<div class="panel-pop" id="wpqa-notification" data-width="690">
					<i class="icon-cancel"></i>
					<div class="panel-pop-content">
						<?php echo ($pop_notification["text"]);
						if ((isset($pop_notification["button_text"]) && $pop_notification["button_text"] != "") && (isset($pop_notification["button_url"]) && $pop_notification["button_url"] != "")) {?>
							<a target="<?php echo esc_attr($pop_notification["button_target"])?>" class="button-default" href="<?php echo esc_url($pop_notification["button_url"])?>"><?php echo esc_html($pop_notification["button_text"])?></a>
						<?php }?>
					</div><!-- End panel-pop-content -->
				</div><!-- End wpqa-notification -->
			<?php }
			if (isset($new_badge) && $new_badge != "") {
				$site_users_only = wpqa_site_users_only();
				$under_construction = wpqa_under_construction();
				if ($site_users_only != "yes" && $under_construction != "on" && !is_page_template("template-landing.php")) {?>
					<div class="panel-pop" id="wpqa-badge" data-width="690">
						<i class="icon-cancel"></i>
						<div class="panel-pop-content">
							<div class="new_badge" style='color: <?php echo esc_attr($get_badge_color)?>'><span class="wings-shape"><i class="icon-bucket"></i></span></div>
							<h3><?php esc_html_e("You just unlocked a new badge!","wpqa")?></h3>
							<p><?php echo sprintf(esc_html__("Woohoo! You've earned the %s badge%s, here's a new badge to celebrate! Looking for more? Browse the complete list of questions, or popular tags. Help us answer unanswered questions.","wpqa"),"\"<span style='color: ".$get_badge_color."'>".$new_badge."</span>\"",(isset($category) && $category > 0 && isset($get_term) && isset($get_term->slug)?" ".esc_html__("in the","wpqa")." <a href='".get_term_link($get_term->slug,'question-category')."'>".$get_term->name."</a>"." ".esc_html__("category","wpqa"):""))?></p>
							<?php $pages = get_pages(array('meta_key' => '_wp_page_template','meta_value' => 'template-badges.php'));
							if (isset($pages) && isset($pages[0]) && isset($pages[0]->ID)) {?>
								<a class="button-default" href="<?php echo get_the_permalink($pages[0]->ID)?>"><?php esc_html_e("Earn More Points!","wpqa")?></a>
							<?php }?>
						</div><!-- End panel-pop-content -->
					</div><!-- End wpqa-badge -->
				<?php }
			}
		}else {
			$signup_style = wpqa_options("signup_style");?>
			<div class="<?php echo ($its_not_login == true?'panel-signup panel-un-login'.(wpqa_is_signup()?"":""):'panel-pop'.($its_not_login != true && $signup_style == "style_2"?" panel-pop-image":""))?>"<?php echo($its_not_login != true && $signup_style == "style_2"?' data-width="770"':'')?> id="signup-panel">
				<?php echo ($its_not_login == true?'':'<i class="icon-cancel"></i>');
				$signup_details = wpqa_options("signup_details");
				if ($its_not_login == true || $signup_style != "style_2") {
					$logo_signup = wpqa_image_url_id(wpqa_options("logo_signup"));
					$logo_signup_retina = wpqa_image_url_id(wpqa_options("logo_signup_retina"));
					$logo_signup_height = wpqa_options("logo_signup_height");
					$logo_signup_width = wpqa_options("logo_signup_width");
					$text_signup = wpqa_options("text_signup");
				}?>
				<div class="pop-border-radius">
					<?php if ($its_not_login == true || $signup_style != "style_2") {?>
						<div class="pop-header">
							<h3>
								<?php if ($logo_signup != "" || $logo_signup_retina != "") {
									if ($logo_signup != "" || ($logo_signup_retina == "" && $logo_signup != "")) {?>
										<img width="<?php echo esc_attr($logo_signup_width)?>" height="<?php echo esc_attr($logo_signup_height)?>" class="signup-logo <?php echo ($logo_signup_retina == "" && $logo_signup != ""?"retina_screen":"default_screen")?>" alt="<?php esc_attr_e("Sign Up","wpqa")?>" src="<?php echo esc_url($logo_signup)?>">
									<?php }
									if ($logo_signup_retina != "") {?>
										<img width="<?php echo esc_attr($logo_signup_width)?>" height="<?php echo esc_attr($logo_signup_height)?>" class="signup-logo retina_screen" alt="<?php esc_attr_e("Sign Up","wpqa")?>" src="<?php echo esc_url($logo_signup_retina)?>">
									<?php }
								}else {
									esc_html_e("Sign Up","wpqa");
								}?>
							</h3>
							<?php if ($text_signup != "") {?>
								<p><?php echo wpqa_kses_stip($text_signup)?></p>
							<?php }?>
						</div><!-- End pop-header -->
					<?php }
					if ($its_not_login != true && $signup_style == "style_2") {?>
						<div class="panel-image-content">
							<div class="panel-image-opacity"></div>
							<div class="panel-image-inner">
								<h3><?php esc_html_e("Sign Up","wpqa");?></h3>
								<?php if ($signup_details != "") {?>
									<p><?php echo wpqa_kses_stip($signup_details)?></p>
								<?php }?>
							</div><!-- End panel-image-inner -->
							<?php echo ' <a href="#" class="login-panel button-default">'.esc_html__( 'Have an account?', 'wpqa' ).' '.esc_html__( 'Sign In', 'wpqa' ).'</a>';?>
						</div><!-- End panel-image-content -->
					<?php }?>
					<div class="panel-pop-content">
						<?php echo do_shortcode("[wpqa_signup]");?>
					</div><!-- End pop-border-radius -->
				</div><!-- End pop-border-radius -->
				<?php if ($its_not_login == true || $signup_style != "") {?>
					<div class="pop-footer<?php echo($signup_style != "style_2"?"":" wpqa_hide")?>">
						<?php echo esc_html__( 'Have an account?', 'wpqa' ).' <a href="#" class="'.($its_not_login == true?'login-panel-un':'login-panel').'">'.esc_html__( 'Sign In Now', 'wpqa' ).'</a>';?>
					</div><!-- End pop-footer -->
				<?php }?>
			</div><!-- End signup -->
			
			<?php $login_style = wpqa_options("login_style");?>
			<div class="<?php echo ($its_not_login == true?'panel-login panel-un-login'.(wpqa_is_signup()?" wpqa_hide":""):'panel-pop'.($its_not_login != true && $login_style == "style_2"?" panel-pop-image":""))?>"<?php echo($its_not_login != true && $login_style == "style_2"?' data-width="770"':'')?> id="login-panel">
				<?php echo ($its_not_login == true?'':'<i class="icon-cancel"></i>');
				$login_details = wpqa_options("login_details");
				if ($its_not_login == true || $login_style != "style_2") {
					$logo_login = wpqa_image_url_id(wpqa_options("logo_login"));
					$logo_login_retina = wpqa_image_url_id(wpqa_options("logo_login_retina"));
					$logo_login_height = wpqa_options("logo_login_height");
					$logo_login_width = wpqa_options("logo_login_width");
					$text_login = wpqa_options("text_login");
				}?>
				<div class="pop-border-radius">
					<?php if ($its_not_login == true || $login_style != "style_2") {?>
						<div class="pop-header">
							<h3>
								<?php if ($logo_login != "" || $logo_login_retina != "") {
									if ($logo_login != "" || ($logo_login_retina == "" && $logo_login != "")) {?>
										<img width="<?php echo esc_attr($logo_login_width)?>" height="<?php echo esc_attr($logo_login_height)?>" class="login-logo <?php echo ($logo_login_retina == "" && $logo_login != ""?"retina_screen":"default_screen")?>" alt="<?php esc_attr_e("Sign In","wpqa")?>" src="<?php echo esc_url($logo_login)?>">
									<?php }
									if ($logo_login_retina != "") {?>
										<img width="<?php echo esc_attr($logo_login_width)?>" height="<?php echo esc_attr($logo_login_height)?>" class="login-logo retina_screen" alt="<?php esc_attr_e("Sign In","wpqa")?>" src="<?php echo esc_url($logo_login_retina)?>">
									<?php }
								}else {
									esc_html_e("Sign In","wpqa");
								}?>
							</h3>
							<?php if ($text_login != "") {?>
								<p><?php echo wpqa_kses_stip($text_login)?></p>
							<?php }?>
						</div><!-- End pop-header -->
					<?php }
					if ($its_not_login != true && $login_style == "style_2") {?>
						<div class="panel-image-content">
							<div class="panel-image-opacity"></div>
							<div class="panel-image-inner">
								<h3><?php esc_html_e("Sign In","wpqa");?></h3>
								<?php if ($login_details != "") {?>
									<p><?php echo wpqa_kses_stip($login_details)?></p>
								<?php }?>
							</div><!-- End panel-image-inner -->
							<?php echo ' <a href="#" class="signup-panel button-default">'.esc_html__( 'Sign Up Here', 'wpqa' ).'</a>';?>
						</div><!-- End panel-image-content -->
					<?php }?>
					<div class="panel-pop-content">
						<?php echo do_shortcode("[wpqa_login".($its_not_login == true?" un-login='true'":"")."]");?>
					</div><!-- End panel-pop-content -->
				</div><!-- End pop-border-radius -->
				<?php if ($its_not_login == true || $login_style != "") {?>
					<div class="pop-footer<?php echo($login_style != "style_2"?"":" wpqa_hide")?>">
						<?php echo esc_html__( "Don't have account,", "wpqa" ).' <a href="#" class="'.($its_not_login == true?'signup-panel-un':'signup-panel').'">'.esc_html__( 'Sign Up Here', 'wpqa' ).'</a>';?>
					</div><!-- End pop-footer -->
				<?php }?>
			</div><!-- End login-panel -->
			
			<?php $pass_style = wpqa_options("pass_style");?>
			<div class="<?php echo ($its_not_login == true?'panel-password panel-un-login':'panel-pop'.($its_not_login != true && $pass_style == "style_2"?" panel-pop-image":""))?>"<?php echo($its_not_login != true && $pass_style == "style_2"?' data-width="770"':'')?> id="lost-password">
				<?php echo ($its_not_login == true?'':'<i class="icon-cancel"></i>');
				$pass_details = wpqa_options("pass_details");
				if ($its_not_login == true || $pass_style != "style_2") {
					$logo_pass = wpqa_image_url_id(wpqa_options("logo_pass"));
					$logo_pass_retina = wpqa_image_url_id(wpqa_options("logo_pass_retina"));
					$logo_pass_height = wpqa_options("logo_pass_height");
					$logo_pass_width = wpqa_options("logo_pass_width");
					$text_pass = wpqa_options("text_pass");
				}?>
				<div class="pop-border-radius">
					<?php if ($its_not_login == true || $pass_style != "style_2") {?>
						<div class="pop-header">
							<h3>
								<?php if ($logo_pass != "" || $logo_pass_retina != "") {
									if ($logo_pass != "" || ($logo_pass_retina == "" && $logo_pass != "")) {?>
										<img width="<?php echo esc_attr($logo_pass_width)?>" height="<?php echo esc_attr($logo_pass_height)?>" class="pass-logo <?php echo ($logo_pass_retina == "" && $logo_pass != ""?"retina_screen":"default_screen")?>" alt="<?php esc_attr_e("Forgot Password","wpqa")?>" src="<?php echo esc_url($logo_pass)?>">
									<?php }
									if ($logo_pass_retina != "") {?>
										<img width="<?php echo esc_attr($logo_pass_width)?>" height="<?php echo esc_attr($logo_pass_height)?>" class="pass-logo retina_screen" alt="<?php esc_attr_e("Forgot Password","wpqa")?>" src="<?php echo esc_url($logo_pass_retina)?>">
									<?php }
								}else {
									esc_html_e("Forgot Password","wpqa");
								}?>
							</h3>
							<?php if ($text_pass != "") {?>
								<p><?php echo wpqa_kses_stip($text_pass)?></p>
							<?php }?>
						</div><!-- End pop-header -->
					<?php }
					if ($its_not_login != true && $pass_style == "style_2") {?>
						<div class="panel-image-content">
							<div class="panel-image-opacity"></div>
							<div class="panel-image-inner">
								<h3><?php esc_html_e("Forgot Password","wpqa");?></h3>
								<?php if ($pass_details != "") {?>
									<p><?php echo wpqa_kses_stip($pass_details)?></p>
								<?php }?>
							</div><!-- End panel-image-inner -->
						</div><!-- End panel-image-content -->
					<?php }?>
					<div class="panel-pop-content">
						<?php echo do_shortcode("[wpqa_lost_pass".($its_not_login != true && $pass_style == "style_2"?" text='true'":"")."]");?>
					</div><!-- End panel-pop-content -->
				</div><!-- End pop-border-radius -->
				<?php if ($its_not_login == true || $pass_style != "") {?>
					<div class="pop-footer<?php echo($pass_style != "style_2"?"":" wpqa_hide")?>">
						<?php echo esc_html__( 'Have an account?', 'wpqa' ).' <a href="#" class="'.($its_not_login == true?'login-panel-un':'login-panel').'">'.esc_html__( 'Sign In Now', 'wpqa' ).'</a>';?>
					</div><!-- End pop-footer -->
				<?php }?>
			</div><!-- End lost-password -->
		<?php }
		
		$confirm_email = wpqa_users_confirm_mail(false);
		if ($confirm_email != "yes" && $login != "login") {
			$pay_ask = wpqa_options("pay_ask");
			$custom_permission = wpqa_options("custom_permission");
			$ask_question_no_register = wpqa_options("ask_question_no_register");
			$ask_question = wpqa_options("ask_question");
			if (($custom_permission == "on" && is_user_logged_in() && !is_super_admin($user_id) && empty($roles["ask_question"])) || ($custom_permission == "on" && !is_user_logged_in() && $ask_question != "on")) {
				if (!is_user_logged_in()) {
					$register = true;
				}
			}else if (!is_user_logged_in() && $ask_question_no_register != "on") {
				$register = true;
			}else {
				if (!is_user_logged_in() && $pay_ask == "on") {
					$register = true;
				}
			}?>
			<div class="panel-pop" id="wpqa-question"<?php echo (isset($register) && $register == true?"":' data-width="690"')?>>
				<i class="icon-cancel"></i>
				<div class="panel-pop-content">
					<?php echo do_shortcode("[wpqa_question popup='popup']");?>
				</div><!-- End panel-pop-content -->
				<?php if (isset($register) && $register == true) {?>
					<div class="pop-footer">
						<?php echo esc_html__( 'Need An Account,', 'wpqa' ).' <a href="#" class="signup-panel">'.esc_html__( 'Sign Up Here', 'wpqa' ).'</a>';?>
					</div><!-- End pop-footer -->
				<?php }?>
			</div><!-- End wpqa-question -->
			
			<?php $ask_question_to_users = wpqa_options("ask_question_to_users");
			if ($ask_question_to_users == "on" && wpqa_is_user_profile()) {?>
				<div class="panel-pop" id="wpqa-question-user"<?php echo (isset($register) && $register == true?"":' data-width="690"')?>>
					<i class="icon-cancel"></i>
					<div class="panel-pop-content">
						<?php echo do_shortcode("[wpqa_question type='user' popup='popup']");?>
					</div><!-- End panel-pop-content -->
					<?php if (isset($register) && $register == true) {?>
						<div class="pop-footer">
							<?php echo esc_html__( 'Need An Account,', 'wpqa' ).' <a href="#" class="signup-panel">'.esc_html__( 'Sign Up Here', 'wpqa' ).'</a>';?>
						</div><!-- End pop-footer -->
					<?php }?>
				</div><!-- End wpqa-question-user -->
			<?php }
			
			$active_post_popup = wpqa_options("active_post_popup");
			if ($active_post_popup == "on") {
				$custom_permission = wpqa_options("custom_permission");
				$add_post_no_register = wpqa_options("add_post_no_register");
				$add_post = wpqa_options("add_post");
				if (($custom_permission == "on" && is_user_logged_in() && !is_super_admin($user_id) && empty($roles["add_post"])) || ($custom_permission == "on" && !is_user_logged_in() && $add_post != "on")) {
					if (!is_user_logged_in()) {
						$register = true;
					}
				}else if (!is_user_logged_in() && $add_post_no_register != "on") {
					$register = true;
				}?>
				<div class="panel-pop" id="wpqa-post"<?php echo (isset($register) && $register == true?"":' data-width="690"')?>>
					<i class="icon-cancel"></i>
					<div class="panel-pop-content">
						<?php echo do_shortcode("[wpqa_add_post popup='popup']");?>
					</div><!-- End panel-pop-content -->
					<?php if (isset($register) && $register == true) {?>
						<div class="pop-footer">
							<?php echo esc_html__( 'Need An Account,', 'wpqa' ).' <a href="#" class="signup-panel">'.esc_html__( 'Sign Up Here', 'wpqa' ).'</a>';?>
						</div><!-- End pop-footer -->
					<?php }?>
				</div><!-- End wpqa-post -->
			<?php }
			
			$active_message = wpqa_options("active_message");
			$active_message_filter = apply_filters('wpqa_active_message_filter',false);
			if ($active_message == "on" && (wpqa_is_user_profile() || $active_message_filter == true)) {
				$received_message = "";
				$user_block_message = array();
				$send_message_no_register = wpqa_options("send_message_no_register");
				$wpqa_user_id = (int)get_query_var(apply_filters('wpqa_user_id','wpqa_user_id'));
				
				if (isset($wpqa_user_id) && $wpqa_user_id > 0) {
					$received_message = esc_attr(get_the_author_meta('received_message',$wpqa_user_id));
					$user_block_message = get_user_meta($wpqa_user_id,"user_block_message",true);
				}
				$block_message = esc_attr(get_the_author_meta('block_message',$wpqa_user_id));
				
				if (((!is_user_logged_in() && $send_message_no_register == "on") || (is_user_logged_in() && (empty($user_block_message) || (isset($user_block_message) && is_array($user_block_message) && !in_array(get_current_user_id(),$user_block_message))) && ($block_message != "on" || is_super_admin($wpqa_user_id)) && ($received_message == "" || $received_message == "on")))) {
					$custom_permission = wpqa_options("custom_permission");
					$send_message_no_register = wpqa_options("send_message_no_register");
					$send_message = wpqa_options("send_message");
					if (($custom_permission != "on" && ((isset($user_login_group) && $user_login_group == "wpqa_under_review") || (isset($user_login_group) && $user_login_group == "activation"))) || ($custom_permission == "on" && (is_user_logged_in() && !is_super_admin($user_id) && empty($roles["send_message"])) || (!is_user_logged_in() && $send_message != "on"))) {
						if (!is_user_logged_in()) {
							$register = true;
						}
					}else if (!is_user_logged_in() && $send_message_no_register != "on") {
						$register = true;
					}?>
					<div class="panel-pop" id="wpqa-message"<?php echo (isset($register) && $register == true?"":' data-width="690"')?>>
						<i class="icon-cancel"></i>
						<div class="panel-pop-content">
							<?php echo do_shortcode("[wpqa_send_message popup='popup']");?>
						</div><!-- End panel-pop-content -->
						<?php if (isset($register) && $register == true) {?>
							<div class="pop-footer">
								<?php echo esc_html__( 'Need An Account,', 'wpqa' ).' <a href="#" class="signup-panel">'.esc_html__( 'Sign Up Here', 'wpqa' ).'</a>';?>
							</div><!-- End pop-footer -->
						<?php }?>
					</div><!-- End wpqa-message -->
				<?php }
			}
			
			$active_reports = wpqa_options("active_reports");
			$active_logged_reports = wpqa_options("active_logged_reports");
			if ($active_reports == "on" && (is_user_logged_in() || (!is_user_logged_in() && $active_logged_reports != "on"))) {
				global $post;?>
				<div class="panel-pop" id="wpqa-report">
					<i class="icon-cancel"></i>
					<div class="panel-pop-content">
						<p><?php esc_html_e("Please briefly explain why you feel this answer should be reported.","wpqa")?></p>
						<form class="wpqa_form submit-report" method="post">
							<div class="wpqa_error"></div>
							<div class="wpqa_success"></div>
							<div class="form-inputs clearfix">
								<p class="login-text">
									<label for="explain-reported"><?php esc_html_e("Explain","wpqa")?><span class="required">*</span></label>
									<textarea id="explain-reported" name="explain"></textarea>
									<i class="icon-pencil"></i>
								</p>
							</div>
							<p class="form-submit">
								<span class="load_span"><span class="loader_2"></span></span>
								<?php wp_nonce_field('wpqa_report_nonce','wpqa_report_nonce',false)?>
								<input type="submit" value="<?php esc_attr_e("Report","wpqa")?>" class="button-default button-hide-click">
							</p>
							<input type="hidden" name="form_type" value="wpqa-report">
							<input type="hidden" name="post_id" value="<?php echo (isset($post->ID)?esc_attr($post->ID):"")?>">
						</form>
					</div><!-- End panel-pop-content -->
				</div><!-- End wpqa-report -->
			<?php }
		}
	}
endif;
/* Check if site for the users only */
if (!function_exists('wpqa_site_users_only')) :
	function wpqa_site_users_only() {
		$site_users_only = $active_confirm_email = $login_only = "no";
		$site_users_option = wpqa_options("site_users_only");
		
		if (is_user_logged_in()) {
			$if_user_id = get_user_by("id",get_current_user_id());
			if (isset($if_user_id->caps["activation"]) && $if_user_id->caps["activation"] == 1) {
				$active_confirm_email = "yes";
			}
		}
		
		if ((!is_user_logged_in() && $site_users_option == "on") || (is_user_logged_in() && $active_confirm_email == "yes" && $site_users_option == "on")) {
			if (is_page()) {
				global $post;
				$login_only = get_post_meta($post->ID,prefix_meta.'login_only',true);
			}
			if ($login_only != "on") {
				$site_users_only = "yes";
			}else {
				$site_users_only = "no";
			}
		}
		
		return $site_users_only;
	}
endif;
/* Check if the site under construction */
if (!function_exists('wpqa_under_construction')) :
	function wpqa_under_construction() {
		$under_construction = wpqa_options("under_construction");
		if (!is_super_admin() && $under_construction == "on") {
			return $under_construction;
		}
	}
endif;
/* Check if confirm mail active */
if (!function_exists('wpqa_users_confirm_mail')) :
	function wpqa_users_confirm_mail($make_pages_work = true) {
		$site_users_only = $login_only = "no";
		if (is_user_logged_in()) {
			$if_user_id = get_user_by("id",get_current_user_id());
			if (isset($if_user_id->caps["ban_group"]) && $if_user_id->caps["ban_group"] == 1) {
				$site_users_only = "yes";
				if (is_page()) {
					global $post;
					$banned_only = get_post_meta($post->ID,prefix_meta.'banned_only',true);
					if ($banned_only == "on") {
						$site_users_only = "no";
					}
				}
			}else {
				if ($make_pages_work == true && $site_users_only == "yes") {
					if (is_page()) {
						global $post;
						$login_only = get_post_meta($post->ID,prefix_meta.'login_only',true);
					}
					
					if ($login_only != "on") {
						$site_users_only = "yes";
					}else {
						$site_users_only = "no";
					}
				}
			}
		}
		return $site_users_only;
	}
endif;
/* Header content */
add_action('wpqa_header_content','wpqa_header_content');
if (!function_exists('wpqa_header_content')) :
	function wpqa_header_content($args = array()) {
		/* Session */
		do_action("wpqa_show_session");
		/* Update */
		if (is_user_logged_in()) {
			wpqa_update_edit_profiel($args["user_id"],$args["update_profile"]);
		}
		do_action("wpqa_do_payments");
	}
endif;
/* Top bar wordpress */
add_filter('show_admin_bar','wpqa_disable_admin_bar',20,1);
if (!function_exists('wpqa_disable_admin_bar')) :
	function wpqa_disable_admin_bar( $show_admin_bar ) {
		$top_bar_wordpress = wpqa_options("top_bar_wordpress");
		$user_info = get_userdata(get_current_user_id());
		$user_group = (isset($user_info->roles) && isset($user_info->roles[0])?$user_info->roles[0]:"");
		if ($user_group == "ban_group" || ($top_bar_wordpress == "on" && !(current_user_can('administrator')))) {
			$top_bar_groups = wpqa_options("top_bar_groups");
			if ($user_group == "ban_group" || (is_array($top_bar_groups) && in_array($user_group,$top_bar_groups))) {
				$show_admin_bar = false;
			}
		}
		return $show_admin_bar;
	}
endif;
/* Pagination */
if (!function_exists('wpqa_pagination')) :
	function wpqa_pagination($args = array(),$max_num_pages = '',$query = '') {
		global $wp_rewrite,$wp_query;
		do_action('wpqa_pagination_start');
		if ($query) {
			$wp_query = $query;
		}
		if ($max_num_pages == "") {
			$max_num_pages = $wp_query->max_num_pages;
		}
		/* If there's not more than one page,return nothing. */
		if (1 >= $max_num_pages)
			return;
		/* Get the current page. */
		$paged = $current = (get_query_var("paged") != ""?(int)get_query_var("paged"):(get_query_var("page") != ""?(int)get_query_var("page"):1));
		$page_paged = (get_query_var("paged") != ""?"paged":(get_query_var("page") != ""?"page":"paged"));
		$current = $paged;
		/* Get the max number of pages. */
		$max_num_pages = ($max_num_pages != ""?$max_num_pages:intval($wp_query->max_num_pages));
		/* Set up some default arguments for the paginate_links() function. */
		$defaults = array(
			'base'         => (esc_url_raw(add_query_arg($page_paged,'%#%'))),
			'format'       => '',
			'total'        => $max_num_pages,
			'current'      => esc_attr($current),
			'prev_next'    => true,
			'prev_text'    => '<i class="icon-left-open"></i>',
			'next_text'    => '<i class="icon-right-open"></i>',
			'show_all'     => false,
			'end_size'     => 1,
			'mid_size'     => 1,
			'add_fragment' => '',
			'type'         => 'plain',
			'before'       => '<div class="main-pagination"><div class="pagination">',
			'after'        => '</div></div>',
			'echo'         => true,
		);
		/* Add the $base argument to the array if the user is using permalinks. */
		if (!wpqa_is_search() && $wp_rewrite->using_permalinks()) {
			$defaults['base'] = user_trailingslashit(trailingslashit(get_pagenum_link()) . 'page/%#%');
		}
		/* If we're on a search results page,we need to change this up a bit. */
		if (!wpqa_is_search() && is_search()) {
		/* If we're in BuddyPress,use the default "unpretty" URL structure. */
			if (class_exists('BP_Core_User')) {
				$search_query = esc_attr(get_query_var('s'));
				$base = user_trailingslashit(esc_url(home_url('/'))) . '?s=' . $search_query . '&paged=%#%';
				$defaults['base'] = $base;
			}else {
				$search_permastruct = $wp_rewrite->get_search_permastruct();
				if (!empty($search_permastruct))
					$defaults['base'] = esc_url_raw(add_query_arg('page','%#%'));
			}
		}
		/* Merge the arguments input with the defaults. */
		$args = wp_parse_args($args,$defaults);
		/* Allow developers to overwrite the arguments with a filter. */
		$args = apply_filters('wpqa_pagination_args',$args);
		/* Don't allow the user to set this to an array. */
		if ('array' == $args['type'])
			$args['type'] = 'plain';
		/* Make sure raw querystrings are displayed at the end of the URL,if using pretty permalinks. */
		$pattern = '/\?(.*?)\//i';
		preg_match($pattern,$args['base'],$raw_querystring);
		if ($wp_rewrite->using_permalinks() && $raw_querystring)
			$raw_querystring[0] = str_replace('','',$raw_querystring[0]);
			if (!empty($raw_querystring)) {
				$args['base'] = str_replace($raw_querystring[0],'',$args['base']);
				$args['base'] .= substr($raw_querystring[0],0,-1);
			}
		/* Get the paginated links. */
		$page_links = paginate_links($args);
		/* Remove 'page/1' from the entire output since it's not needed. */
		if (!wpqa_is_search()) {
			$page_links = str_replace(array('&#038;paged=1\'','/page/1\''),'\'',$page_links);
		}
		/* Wrap the paginated links with the $before and $after elements. */
		$page_links = $args['before'] . $page_links . $args['after'];
		/* Allow devs to completely overwrite the output. */
		$page_links = apply_filters('wpqa_pagination',$page_links);
		do_action('wpqa_pagination_end');
		/* Return the paginated links for use in themes. */
		if ($args['echo']) {
			echo ($page_links);
		}else {
			return $page_links;
		}
	}
endif;
/* Pagination load */
if (!function_exists('wpqa_pagination_load')) :
	function wpqa_pagination_load($post_pagination = "pagination",$max_num_pages = "",$it_answer_pagination = false,$its_question = false,$wpqa_query = null,$it_comment_pagination = false) {
		if ($post_pagination != "none") {
			if ($post_pagination == "infinite_scroll" || $post_pagination == "load_more") {
				if (isset($it_answer_pagination) && $it_answer_pagination == true) {
					if (isset($it_comment_pagination) && $it_comment_pagination == true) {
						$nomore_text = esc_html__("No more comments","wpqa");
						$load_text = esc_html__("Load More Comments","wpqa");
					}else {
						$nomore_text = esc_html__("No more answers","wpqa");
						$load_text = esc_html__("Load More Answers","wpqa");
					}
				}else if (isset($its_question) && $its_question == "question") {
					$nomore_text = esc_html__("No more questions","wpqa");
					$load_text = esc_html__("Load More Questions","wpqa");
				}else {
					$nomore_text = esc_html__("No more posts","wpqa");
					$load_text = esc_html__("Load More Posts","wpqa");
				}
				
				$more_link = get_next_posts_link(' ',$max_num_pages);
				if (!empty($more_link)) :?>
					<script type="text/javascript">
						(function($) {
							jQuery(document).ready(function() {
								var ajax_new_count = 0;
								/* Load more */
								function wpqa_load_more(load_type,j_this,ajax_new_count) {
									var main_content = ".discy-main-inner";
									if (load_type == "infinite-scroll") {
										var $link = jQuery('.posts-infinite-scroll a');
									}else {
										var $link = j_this;
									}
									var page_url = $link.attr("href");
									if (page_url != undefined) {
										if (load_type == "infinite-scroll") {
											$link.parent().parent().animate({ opacity: 1}, 300).css('padding', '10px');
										}else {
											$link.closest(main_content).find(".posts-"+load_type+" a").hide();
										}
										$link.closest(main_content).find(".posts-"+load_type+" .load_span").show();
										jQuery("<div>").load(page_url, function() {
											var n = ajax_new_count.toString();
											var $wrap = $link.closest(main_content).find('.<?php echo (isset($it_answer_pagination) && $it_answer_pagination == true?"commentlist":"post-articles")?>');
											<?php if ('post' === get_post_type()) {?>
												var $share = $link.closest(main_content).find('.post-articles article.post .post-share > ul').attr("style");
											<?php }?>
											var $new = jQuery(this).find('.<?php echo (isset($it_answer_pagination) && $it_answer_pagination == true?"commentlist > li.comment":"post-articles article.".esc_js(get_post_type()))?>').addClass('<?php echo (isset($it_answer_pagination) && $it_answer_pagination == true?"comment":"post-section")?>-new-'+n);
											<?php if ('post' === get_post_type()) {?>
												$new.find('.post-share > ul').attr("style",$share);
											<?php }?>
											var $this_div = jQuery(this);
											$new.imagesLoaded( function() {
												$new.hide().appendTo($wrap).fadeIn(400);
												/* Lightbox */
												var lightboxArgs = {			
													animation_speed: "fast",
													overlay_gallery: true,
													autoplay_slideshow: false,
													slideshow: 5000,
													theme: "pp_default",
													opacity: 0.8,
													show_title: false,
													social_tools: "",
													deeplinking: false,
													allow_resize: true,
													counter_separator_label: "/",
													default_width: 940,
													default_height: 529
												};
												jQuery("a[href$=jpg],a[href$=JPG],a[href$=jpeg],a[href$=JPEG],a[href$=png],a[href$=gif],a[href$=bmp]:has(img)").prettyPhoto(lightboxArgs);
												jQuery("a[class^='prettyPhoto'],a[rel^='prettyPhoto']").prettyPhoto(lightboxArgs);
												/* Facebook */
												jQuery(".facebook-remove").remove();
												/* Owl */
												jQuery(".post-section-new-"+n+" .slider-owl").each(function () {
													var $slider = jQuery(this);
													var $slider_item = $slider.find('.slider-item').length;
													$slider.find('.slider-item').css({"height":"auto"});
													if ($slider.find('img').length) {
														var $slider = jQuery(this).imagesLoaded(function() {
															$slider.owlCarousel({
																autoPlay: 5000,
																margin: 10,
																responsive: {
																	0: {
																		items: 1
																	}
																},
																stopOnHover: true,
																navText : ["", ""],
																nav: ($slider_item > 1)?true:false,
																rtl: jQuery('body.rtl').length?true:false,
																loop: ($slider_item > 1)?true:false,
															});
														});
													}else {
														$slider.owlCarousel({
															autoPlay: 5000,
															margin: 10,
															responsive: {
																0: {
																	items: 1
																}
															},
															stopOnHover: true,
															navText : ["", ""],
															nav: ($slider_item > 1)?true:false,
															rtl: jQuery('body.rtl').length?true:false,
															loop: ($slider_item > 1)?true:false,
														});
													}
												});
												/* Question masonry */
												if (jQuery(".post-section-new-"+n+".post-with-columns").length) {
													if ($new.eq(0).is('.question-masonry')) {
														var newItems = jQuery('.post-section-new-'+n);
														jQuery('.question-articles').isotope( 'insert', newItems );
														jQuery('.question-articles').isotope({
															filter: "*",
															animationOptions: {
																duration: 750,
																itemSelector: '.question-masonry',
																easing: "linear",
																queue: false,
															}
														});
														setTimeout(function() {
															if ($new.eq(0).is('.post-masonry')) {
																jQuery('.question-articles').isotope({
																	filter: "*",
																	animationOptions: {
																		duration: 750,
																		itemSelector: '.question-masonry',
																		easing: "linear",
																		queue: false,
																	}
																});
															}
														}, 1000);
													}else {
														jQuery(".post-section-new-"+n+".post-with-columns").matchHeight();
														jQuery(".post-section-new-"+n+".post-with-columns > .article-question").matchHeight();
													}
												}
												/* Poll */
												if (jQuery(".post-section-new-"+n+" .progressbar-percent").length) {
													jQuery(".post-section-new-"+n+" .progressbar-percent").each(function(){
														var $this = jQuery(this);
														var percent = $this.attr("attr-percent");
														$this.bind("inview", function(event, isInView, visiblePartX, visiblePartY) {
															if (isInView) {
																$this.animate({ "width" : percent + "%"}, 700);
															}
														});
													});
												}
												/* Audio */
												if ($new.eq(0).find('.wp-audio-shortcode')) {
													<?php wp_enqueue_style( 'wp-mediaelement' );
													wp_enqueue_script( 'wp-playlist' );?>
													mejs.plugins.silverlight[0].types.push('video/x-ms-wmv');
													mejs.plugins.silverlight[0].types.push('audio/x-ms-wma');
													jQuery(function () {
														var settings = {};
														if ( typeof _wpmejsSettings !== 'undefined' ) {
															settings = _wpmejsSettings;
														}
														settings.success = settings.success || function (mejs) {
															var autoplay, loop;
															if ( 'flash' === mejs.pluginType ) {
																autoplay = mejs.attributes.autoplay && 'false' !== mejs.attributes.autoplay;
																loop = mejs.attributes.loop && 'false' !== mejs.attributes.loop;
																autoplay && mejs.addEventListener( 'canplay', function () {
																	mejs.play();
																}, false );
																loop && mejs.addEventListener( 'ended', function () {
																	mejs.play();
																}, false );
															}
														};
														jQuery('.post-section-new-'+n+' .wp-audio-shortcode').mediaelementplayer( settings );
													});
												}
												$link.closest(main_content).find(".posts-"+load_type+" .load_span").hide();
												if (load_type == "load-more") {
													$link.closest(main_content).find(".posts-"+load_type+" a").show();
												}
												/* Content */
												jQuery(".discy-main-wrap,.fixed-sidebar,.fixed_nav_menu").css({"height":"auto"});
												/* load more */
												if ($this_div.find(".posts-"+load_type).length) {
													if (load_type == "infinite-scroll") {
														$link.closest(main_content).find(".posts-infinite-scroll").html($this_div.find(".posts-infinite-scroll").html()).animate({opacity: 0}, 300).css("padding","0");
													}else {
														$link.closest(main_content).find(".posts-"+load_type).html($this_div.find(".posts-"+load_type).html());
													}
												}else {
													$link.closest(main_content).find(".pagination-wrap").html('<p class="no-comments"><?php echo esc_js($nomore_text)?></p>');
													$link.closest(main_content).find(".posts-"+load_type).fadeOut("fast").remove();
												}
												jQuery("<?php echo (isset($it_answer_pagination) && $it_answer_pagination == true?"comment":"post-section")?>-new-"+n).removeClass("<?php echo (isset($it_answer_pagination) && $it_answer_pagination == true?"comment":"post-section")?>-new-"+n);
												return false;
											});
										});
									}
								}
								/* infinite scroll */
								jQuery(".posts-infinite-scroll").each (function () {
									jQuery(this).bind("inview",function(event,isInView,visiblePartX,visiblePartY) {
										if  (jQuery(".posts-infinite-scroll").length && isInView) {
											/* wpqa_load_more */
											ajax_new_count++;
											wpqa_load_more("infinite-scroll","",ajax_new_count);
										}
									});
								});
								/* load more */
								jQuery("body").on("click",".posts-load-more a",function(e) {
									e.preventDefault();
									/* wpqa_load_more */
									ajax_new_count++;
									wpqa_load_more("load-more",jQuery(this),ajax_new_count);
								});
							});
						})(jQuery);
					</script>
				<?php endif;
			}?>
			<div class="clearfix"></div>
			<div class="pagination-wrap<?php echo (isset($it_answer_pagination) && $it_answer_pagination == true?" pagination-answer":"").(isset($its_question) && $its_question == "question"?" pagination-question":"").(empty($more_link)?" no-pagination-wrap":"")?>">
				<?php if ($post_pagination == "load_more" || $post_pagination == "infinite_scroll") {
					$more_link = ($post_pagination == "load_more"?get_next_posts_link($load_text,$max_num_pages):$more_link);
					if (!empty($more_link)) {?>
						<div class="pagination-nav <?php echo ($post_pagination == "infinite_scroll"?"posts-infinite-scroll":"posts-load-more")?>">
							<span class="load_span"><span class="loader_2"></span></span>
							<div class="load-more"><?php echo ($more_link)?></div>
						</div><!-- End pagination-nav -->
					<?php }
				}else if ($post_pagination == "pagination") {
					wpqa_pagination(array(),$max_num_pages,(isset($wpqa_query)?$wpqa_query:null));
				}else {?>
					<div class="page-navigation page-navigation-before clearfix">
						<div class="row">
							<div class="col col6">
								<div class="nav-next"><?php next_posts_link('<i class="icon-left-thin"></i><span>'.(isset($its_question) && $its_question == "question"?esc_html__('Old Questions','wpqa'):esc_html__('Old Entries','wpqa')).'</span>',$max_num_pages)?></div>
							</div>
							<div class="col col6">
								<div class="nav-previous"><?php previous_posts_link('<span>'.(isset($its_question) && $its_question == "question"?esc_html__('New Questions','wpqa'):esc_html__('New Entries','wpqa')).'</span><i class="icon-right-thin"></i>',$max_num_pages)?></div>
							</div>
						</div>
					</div>
				<?php }?>
			</div>
		<?php }
	}
endif;
/* Share links */
if (!function_exists('wpqa_share')) :
	function wpqa_share($post_share,$share_facebook,$share_twitter,$share_linkedin,$share_whatsapp,$share_style = "style_1",$comment_id = "",$tax_id = "",$url = "",$title = "") {
		global $post;
		if ($share_facebook == "share_facebook" || $share_twitter == "share_twitter" || $share_linkedin == "share_linkedin" || $share_whatsapp == "share_whatsapp") {
			if ($url == "" && $title == "") {
				if ($tax_id > 0 && is_tax("question-category")) {
					$url = get_term_link($tax_id,"question-category");
					$title = esc_html(get_query_var('wpqa_term_name'));
				}else {
					$url = urlencode(get_permalink().($comment_id > 0?"#comment-".$comment_id:""));
					$title = urlencode($comment_id > 0?wp_html_excerpt(strip_shortcodes(get_comment_text($comment_id)),160):get_the_title());
				}
			}
			if (isset($post->ID) && "post" == get_post_type($post->ID)) {
				$window_title = esc_html__("Share This Article","wpqa");
			}else {
				$window_title = esc_html__("Share","wpqa");
			}?>
			<div class="post-share">
				<span><i class="icon-share"></i><span><?php echo ($window_title)?></span></span>
				<ul>
					<?php foreach ($post_share as $key => $value) {
						if ($share_facebook == "share_facebook" && isset($value["value"]) && $value["value"] == "share_facebook") {?>
							<li class="share-facebook"><a target="_blank" href="http://www.facebook.com/sharer.php?u=<?php echo ($url)?>&amp;t=<?php echo ($title);?>"><i class="icon-facebook"></i><?php echo ($share_style == "style_2"?esc_html__("Share on","wpqa")." ":"")."<span>".esc_html__("Facebook","wpqa")."</span>"?></a></li>
						<?php }else if ($share_twitter == "share_twitter" && isset($value["value"]) && $value["value"] == "share_twitter") {?>
							<li class="share-twitter"><a target="_blank" href="http://twitter.com/share?text=<?php echo ($title);?>&amp;url=<?php echo ($url);?>"><i class="icon-twitter"></i><?php echo ($share_style == "style_2"?esc_html__("Share on","wpqa")." ".esc_html__("Twitter","wpqa"):"")?></a></li>
						<?php }else if ($share_linkedin == "share_linkedin" && isset($value["value"]) && $value["value"] == "share_linkedin") {?>
							<li class="share-linkedin"><a target="_blank" href="http://www.linkedin.com/shareArticle?mini=true&amp;url=<?php echo ($url);?>&amp;title=<?php echo ($title);?>"><i class="icon-linkedin"></i><?php echo ($share_style == "style_2"?esc_html__("Share on","wpqa")." ".esc_html__("LinkedIn","wpqa"):"")?></a></li>
						<?php }else if ($share_whatsapp == "share_whatsapp" && isset($value["value"]) && $value["value"] == "share_whatsapp") {?>
							<li class="share-whatsapp"><a target="_blank" href="https://api.whatsapp.com/send?text=<?php echo ($title)?> - <?php echo ($url);?>"><i class="fab fa-whatsapp"></i><?php echo ($share_style == "style_2"?esc_html__("Share on","wpqa")." ".esc_html__("WhatsApp","wpqa"):"")?></a></li>
						<?php }
					}?>
				</ul>
			</div><!-- End post-share -->
		<?php }
	}
endif;
/* Get image for share */
if (!function_exists('wpqa_image_for_share')) :
	function wpqa_image_for_share() {
		global $post;
		$post_thumb = wpqa_get_the_image($post->ID,apply_filters("wpqa_image_share_size","large"));
		if (!empty($post_thumb)) {
			$post_thumb = $post_thumb;
		}else {
			$fb_share_image = wpqa_image_url_id(wpqa_options("fb_share_image"));
			if (is_single() || is_page()) {
				$custom_header = get_post_meta($post->ID,prefix_meta.'custom_header',true);
			}
			if ((is_single() || is_page()) && isset($custom_header) && $custom_header == "on") {
				$logo_display = get_post_meta($post->ID,prefix_meta.'logo_display',true);
				$logo_img = get_post_meta($post->ID,prefix_meta.'logo_img',true);
			}else {
				$logo_display = wpqa_options("logo_display");
				$logo_img = wpqa_image_url_id(wpqa_options("logo_img"));
			}
			if (!empty($fb_share_image)) {
				$post_thumb = $fb_share_image;
			}else if ($logo_display == "custom_image" && isset($logo_img) && $logo_img != "") {
				$post_thumb = $logo_img;
			}
		}
		return $post_thumb;
	}
endif;
/* Check image id or URL */
if (!function_exists('wpqa_image_url_id')) :
	function wpqa_image_url_id($url_id) {
		if (is_numeric($url_id)) {
			$image = wp_get_attachment_url($url_id);
		}
		
		if (!isset($image)) {
			if (is_array($url_id)) {
				if (isset($url_id['id']) && $url_id['id'] != '' && $url_id['id'] != 0) {
					$image = wp_get_attachment_url($url_id['id']);
				}else if (isset($url_id['url']) && $url_id['url'] != '') {
					$id    = wpqa_get_attachment_id($url_id['url']);
					$image = ($id?wp_get_attachment_url($id):'');
				}
				$image = (isset($image) && $image != ''?$image:$url_id['url']);
			}else {
				if (isset($url_id) && $url_id != '') {
					$id    = wpqa_get_attachment_id($url_id);
					$image = ($id?wp_get_attachment_url($id):'');
				}
				$image = (isset($image) && $image != ''?$image:$url_id);
			}
		}
		if (isset($image) && $image != "") {
			return $image;
		}
	}
endif;
/* Get the image */
if (!function_exists('wpqa_get_the_image')) :
	function wpqa_get_the_image ($post_id,$imge_size = "large",$aq_resize = "") {
		$what_post = get_post_meta($post_id,'what_post',true);
		$post_thumb = "";
		if ($what_post == "video") {
			$protocol = is_ssl() ? 'https' : 'http';
			$video_id = get_post_meta($post_id,prefix_meta.'video_post_id',true);
			$video_type = get_post_meta($post_id,prefix_meta.'video_post_type',true);
			if (!empty($video_id)) {
				if ($video_type == 'youtube') {
					$post_thumb = $protocol.'://img.youtube.com/vi/'.$video_id.'/0.jpg';
				}else if ($video_type == 'vimeo') {
					$url = $protocol.'://vimeo.com/api/v2/video/'.$video_id.'.php';
					$data = wp_remote_get($url);
					if (!is_wp_error($data)) {
						$thumb = @unserialize(trim($data['body']));
						$post_thumb = $thumb[0]['thumbnail_large'];
					}
				}else if ($video_type == 'daily') {
					$post_thumb = $protocol.'://www.dailymotion.com/thumbnail/video/'.$video_id;
				}else if ($video_type == 'facebook') {
					$post_thumb = $protocol.'://graph.facebook.com/'.$video_id.'/picture';
				}
			}else if (has_post_thumbnail($post_id)) {
				$post_thumb = wpqa_get_aq_resize_img_url(600,315,"on",get_post_thumbnail_id($post_id));
			}else {
				$post_thumb = wpqa_get_aq_resize_url(wpqa_image(),600,315,"on");
			}
		}else if ($what_post == "slideshow") {
			$slideshow_type = get_post_meta($post_id,prefix_meta.'slideshow_type',true);
			if ($slideshow_type == "custom_slide") {
				$slideshow_post = get_post_meta($post_id,prefix_meta.'slideshow_post',true);
				if (isset($slideshow_post[1]['image_url']['id'])) {
					$post_thumb = wpqa_get_aq_resize_img_url(600,315,"on",$slideshow_post[1]['image_url']['id']);
				}
			}else if ($slideshow_type == "upload_images") {
				$upload_images = get_post_meta($post_id,prefix_meta.'upload_images',true);
				if (isset($upload_images[1])) {
					$post_thumb = wpqa_get_aq_resize_img_url(600,315,"on",$upload_images[1]);
				}
			}
		}else {
			if (has_post_thumbnail($post_id)) {
				$post_thumb = wpqa_get_aq_resize_img_url(600,315,"on",get_post_thumbnail_id($post_id));
			}else {
				$post_thumb = wpqa_get_aq_resize_url(wpqa_image(),600,315,"on");
			}
		}
		return $post_thumb;
	}
endif;
/* Get the user stats */
if (!function_exists('wpqa_get_user_stats')) :
	function wpqa_get_user_stats ($wpqa_user_id,$user_stats,$active_points,$show_point_favorite) {
		do_action("wpqa_action_before_user_stats",$wpqa_user_id);
		/* questions */
		$add_questions = wpqa_count_posts_by_user($wpqa_user_id,"question");

		/* answers */
		$add_answer = count(get_comments(array("post_type" => "question","status" => "approve","user_id" => $wpqa_user_id)));

		/* the_best_answer */
		$the_best_answer = count(get_comments(array('user_id' => $wpqa_user_id,"status" => "approve",'post_type' => 'question',"meta_query" => array('relation' => 'AND',array("key" => "best_answer_comment","compare" => "=","value" => "best_answer_comment"),array("key" => "answer_question_user","compare" => "NOT EXISTS")))));

		/* points */
		$points = (int)get_user_meta($wpqa_user_id,"points",true);
		if ($active_points != "on" && isset($user_stats["points"]) && $user_stats["points"] == "points") {
			unset($user_stats["points"]);
		}
		
		if ((isset($user_stats["questions"]) && $user_stats["questions"] == "questions") || (isset($user_stats["answers"]) && $user_stats["answers"] == "answers") || (isset($user_stats["best_answers"]) && $user_stats["best_answers"] == "best_answers") || (isset($user_stats["points"]) && $user_stats["points"] == "points")) {
			if (count($user_stats) == 1) {
				$column_user = "col12";
			}else if (count($user_stats) == 2) {
				$column_user = "col6";
			}else if (count($user_stats) == 3) {
				$column_user = "col4";
			}else {
				$column_user = "col3";
			}?>
			<div class="user-stats">
				<ul class="row">
					<?php if (isset($user_stats["questions"]) && $user_stats["questions"] == "questions") {?>
						<li class="col <?php echo esc_attr($column_user)?> user-questions">
							<div>
								<a href="<?php echo esc_url(wpqa_get_profile_permalink($wpqa_user_id,"questions"))?>"></a>
								<i class="icon-book-open"></i>
								<div>
									<span><?php echo ($add_questions == ""?0:wpqa_count_number($add_questions))?></span>
									<h4><?php echo _n("Question","Questions",$add_questions,"wpqa")?></h4>
								</div>
							</div>
						</li>
					<?php }
					if (isset($user_stats["answers"]) && $user_stats["answers"] == "answers") {?>
						<li class="col <?php echo esc_attr($column_user)?> user-answers">
							<div>
								<a href="<?php echo esc_url(wpqa_get_profile_permalink($wpqa_user_id,"answers"))?>"></a>
								<i class="icon-comment"></i>
								<div>
									<span><?php echo ($add_answer == ""?0:wpqa_count_number($add_answer))?></span>
									<h4><?php echo _n("Answer","Answers",$add_answer,"wpqa")?></h4>
								</div>
							</div>
						</li>
					<?php }
					if (isset($user_stats["best_answers"]) && $user_stats["best_answers"] == "best_answers") {?>
						<li class="col <?php echo esc_attr($column_user)?> user-best-answers">
							<div>
								<a href="<?php echo esc_url(wpqa_get_profile_permalink($wpqa_user_id,"best_answers"))?>"></a>
								<i class="icon-graduation-cap"></i>
								<div>
									<span><?php echo ($the_best_answer == ""?0:wpqa_count_number($the_best_answer))?></span>
									<h4><?php echo _n("Best Answer","Best Answers",$the_best_answer,"wpqa")?></h4>
								</div>
							</div>
						</li>
					<?php }
					if (isset($user_stats["points"]) && $user_stats["points"] == "points") {?>
						<li class="col <?php echo esc_attr($column_user)?> user-points">
							<div>
								<?php if ($show_point_favorite == "on" || wpqa_is_user_owner()) {?>
									<a href="<?php echo esc_url(wpqa_get_profile_permalink($wpqa_user_id,"points"))?>"></a>
								<?php }?>
								<i class="icon-bucket"></i>
								<div>
									<span><?php echo ($points == ""?0:wpqa_count_number($points))?></span>
									<h4><?php echo _n("Point","Points",$points,"wpqa")?></h4>
								</div>
							</div>
						</li>
					<?php }?>
				</ul>
				<?php do_action("wpqa_after_user_stats",$wpqa_user_id);
				$active_points_category = wpqa_options("active_points_category");
				if ($active_points_category == "on") {
					$categories_user_points = get_user_meta($wpqa_user_id,"categories_user_points",true);
					if (is_array($categories_user_points) && !empty($categories_user_points)) {
						$display_name = get_the_author_meta('display_name',$wpqa_user_id);
						echo "<ul class='row user-points-categories'>
							<li class='col'>
								<div>
									<h5><i class='icon-graduation-cap'></i>".$display_name." ".(count($categories_user_points) > 1?esc_html__("has been qualified at the following categories","wpqa"):esc_html__("has been qualified at the following category","wpqa"))."</h5>
									<ul>";
										$category_with_points = array();
										foreach ($categories_user_points as $category) {
											$category_with_points[$category] = (int)get_user_meta($wpqa_user_id,"points_category".$category,true);
										}
										arsort($category_with_points);
										foreach ($category_with_points as $category => $points) {
											$get_term = get_term($category,'question-category');
											if (isset($get_term->slug)) {
												echo "<li>
													<i class='icon-bucket'></i>
													".apply_filters("wpqa_filter_categories_points","<a href='".get_term_link($get_term->slug,'question-category')."'>".$get_term->name."</a> (".$points." "._n("point","points",$points,"wpqa").") ".wpqa_get_badge($wpqa_user_id,"",$points),$get_term,$points,$wpqa_user_id)."
												</li>";
											}
										}
									echo "</ul>
								</div>
							</li>
						</ul>";
					}
				}?>
			</div><!-- End user-stats -->
		<?php }
	}
endif;
/* Post publish */
if (!function_exists('wpqa_post_publish')) :
	function wpqa_post_publish($post,$post_author,$area = '') {
		$post_id = $post->ID;
		$post_type = $post->post_type;
		$point_add_post = (int)wpqa_options("point_add_".$post_type);
		$active_points = wpqa_options("active_points");
		if ($post_author > 0 && $point_add_post > 0 && $active_points == "on") {
			wpqa_add_points($post_author,$point_add_post,"+","add_".$post_type,$post_id);
		}
		do_action("wpqa_after_post_publish",$post,$post_author,$area);
	}
endif;
/* Add points for the user */
if (!function_exists('wpqa_add_points')) :
	function wpqa_add_points($user_id,$points,$relation,$message,$post_id = 0,$comment_id = 0,$another_user_id = 0,$points_type = "points",$items = true) {
		$active_points_specific = wpqa_options("active_points_specific");
		if ($active_points_specific == "on" && $points_type == "points") {
			$points_type_specific = "points_date_".date("j_n_Y");
			$points_user = (int)get_user_meta($user_id,$points_type_specific,true);
			$points_user = (int)($relation == "+"?$points_user+$points:$points_user-$points);
			update_user_meta($user_id,$points_type_specific,($points_user > 0?$points_user:0));

			$points_type_specific = "points_date_".date("W_j_Y");
			$points_user = (int)get_user_meta($user_id,$points_type_specific,true);
			$points_user = (int)($relation == "+"?$points_user+$points:$points_user-$points);
			update_user_meta($user_id,$points_type_specific,($points_user > 0?$points_user:0));

			$points_type_specific = "points_date_".date("n_Y");
			$points_user = (int)get_user_meta($user_id,$points_type_specific,true);
			$points_user = (int)($relation == "+"?$points_user+$points:$points_user-$points);
			update_user_meta($user_id,$points_type_specific,($points_user > 0?$points_user:0));

			$points_type_specific = "points_date_".date("Y");
			$points_user = (int)get_user_meta($user_id,$points_type_specific,true);
			$points_user = (int)($relation == "+"?$points_user+$points:$points_user-$points);
			update_user_meta($user_id,$points_type_specific,($points_user > 0?$points_user:0));
		}
		if ($items == true) {
			$_points = (int)get_user_meta($user_id,$user_id."_".$points_type,true);
			$_points++;
			update_user_meta($user_id,$user_id."_".$points_type,$_points);
			add_user_meta($user_id,$user_id."_".$points_type."_".$_points,array(date_i18n('Y/m/d',current_time('timestamp')),date_i18n('g:i a',current_time('timestamp')),$points,$relation,$message,($post_id > 0?$post_id:""),($comment_id > 0?$comment_id:""),"time" => current_time('timestamp'),"user_id" => ($another_user_id > 0?$another_user_id:"")));
		}
		$points_user = (int)get_user_meta($user_id,$points_type,true);
		$points_user = (int)($relation == "+"?$points_user+$points:$points_user-$points);
		update_user_meta($user_id,$points_type,($points_user > 0?$points_user:0));

		$active_points_category = wpqa_options("active_points_category");
		if ($active_points_category == "on" && $points_type == "points") {
			$categories = wp_get_post_terms($post_id,'question-category',array('fields' => 'ids'));
			$categories = (is_array($categories) && !empty($categories)?$categories:get_post_meta($post_id,"question_category",true));
			if (isset($categories) && is_array($categories) && !empty($categories)) {
				foreach ($categories as $category) {
					$categories_user_points = get_user_meta($user_id,"categories_user_points",true);
					if (empty($categories_user_points)) {
						update_user_meta($user_id,"categories_user_points",array($category));
					}else if (is_array($categories_user_points) && !in_array($category,$categories_user_points)) {
						update_user_meta($user_id,"categories_user_points",array_merge($categories_user_points,array($category)));
					}
					$_points_category = (int)get_user_meta($user_id,$user_id."_points_category".$category,true);
					$_points_category++;

					update_user_meta($user_id,$user_id."_points_category".$category,($_points_category > 0?$_points_category:0));
					add_user_meta($user_id,$user_id."_points_category_".$category.$_points_category,array(date_i18n('Y/m/d',current_time('timestamp')),date_i18n('g:i a',current_time('timestamp')),$points,$relation,$message,($post_id > 0?$post_id:""),($comment_id > 0?$comment_id:""),"time" => current_time('timestamp'),"user_id" => ($another_user_id > 0?$another_user_id:"")));

					$points_category_user = (int)get_user_meta($user_id,"points_category".$category,true);
					$points_category_user = (int)($relation == "+"?$points_category_user+$points:$points_category_user-$points);
					update_user_meta($user_id,"points_category".$category,($points_category_user > 0?$points_category_user:0));

					if ($active_points_specific == "on") {
						$points_type_specific = "points_category".$category."_date_".date("j_n_Y");
						$points_user = (int)get_user_meta($user_id,$points_type_specific,true);
						$points_user = (int)($relation == "+"?$points_user+$points:$points_user-$points);
						update_user_meta($user_id,$points_type_specific,($points_user > 0?$points_user:0));

						$points_type_specific = "points_category".$category."_date_".date("W_j_Y");
						$points_user = (int)get_user_meta($user_id,$points_type_specific,true);
						$points_user = (int)($relation == "+"?$points_user+$points:$points_user-$points);
						update_user_meta($user_id,$points_type_specific,($points_user > 0?$points_user:0));

						$points_type_specific = "points_category".$category."_date_".date("n_Y");
						$points_user = (int)get_user_meta($user_id,$points_type_specific,true);
						$points_user = (int)($relation == "+"?$points_user+$points:$points_user-$points);
						update_user_meta($user_id,$points_type_specific,($points_user > 0?$points_user:0));

						$points_type_specific = "points_category".$category."_date_".date("Y");
						$points_user = (int)get_user_meta($user_id,$points_type_specific,true);
						$points_user = (int)($relation == "+"?$points_user+$points:$points_user-$points);
						update_user_meta($user_id,$points_type_specific,($points_user > 0?$points_user:0));
					}
				}
			}
		}
	}
endif;
/* hex2rgb */
if (!function_exists('wpqa_hex2rgb')) :
	function wpqa_hex2rgb ($hex) {
	   $hex = str_replace("#","",$hex);
	   if (strlen($hex) == 3) {
	      $r = hexdec(substr($hex,0,1).substr($hex,0,1));
	      $g = hexdec(substr($hex,1,1).substr($hex,1,1));
	      $b = hexdec(substr($hex,2,1).substr($hex,2,1));
	   }else {
	      $r = hexdec(substr($hex,0,2));
	      $g = hexdec(substr($hex,2,2));
	      $b = hexdec(substr($hex,4,2));
	   }
	   $rgb = array($r, $g, $b);
	   return $rgb;
	}
endif;
/* Mention */
add_filter("the_content","wpqa_mention");
add_filter("comment_text","wpqa_mention");
if (!function_exists('wpqa_mention')) :
	function wpqa_mention($content) {
		$active_mention = wpqa_options("active_mention");
		if ($active_mention == "on") {
			if (preg_match_all('/[a-z0-9_\-\+\.]+@[a-z0-9\-]+\.([a-z]{2,4})(?:\.[a-z]{2})?/i',$content, $matches_email)) {
				if (isset($matches[0])) {}
			}else if (preg_match_all('/@[\w\s+]+/',$content,$matches)) {
				if (isset($matches[0])) {
					foreach ($matches[0] as $key => $match) {
						$first_match = str_ireplace("@","",$match);
						$middle_match = preg_split("/[\s,]+/",$first_match);
						$match = trim((isset($middle_match[0]) && $middle_match[0] !== ""?$middle_match[0]:"")." ".(isset($middle_match[1]) && $middle_match[1] !== ""?$middle_match[1]:""));
						$last_match = trim(preg_replace('/\s+/', ' ', $match));
						if ($last_match != "") {
							$first_name = (isset($middle_match[0]) && $middle_match[0] !== ""?$middle_match[0]:$last_match);
							add_action('pre_user_query','wpqa_custom_search_mention');
							$args = array(
								'meta_query' => array('relation' => 'OR',array("key" => "first_name","value" => $first_name,"compare" => "RLIKE")),
								'orderby'    => "user_registered",
								'order'      => "ASC",
								'search'     => '*'.$last_match.'*',
								'number'     => 1,
								'fields'     => 'ID',
							);
							$query = new WP_User_Query($args);
							$query = $query->get_results();
							if (isset($query[0])) {
								$user_id = $query[0];
								$content = str_ireplace('@'.$last_match,'<a target="_blank" href="'.wpqa_profile_url($user_id).'">'.get_the_author_meta('display_name',$user_id).'</a>',$content);
							}else {
								$args = array(
									'meta_query' => array('relation' => 'OR',array("key" => "first_name","value" => $first_name,"compare" => "RLIKE")),
									'orderby'    => "user_registered",
									'order'      => "ASC",
									'search'     => '*'.$first_name.'*',
									'number'     => 1,
									'fields'     => 'ID',
								);
								$query = new WP_User_Query($args);
								$query = $query->get_results();
								if (isset($query[0])) {
									$user_id = $query[0];
									$content = str_ireplace('@'.$first_name,'<a target="_blank" href="'.wpqa_profile_url($user_id).'">'.get_the_author_meta('display_name',$user_id).'</a>',$content);
								}
							}
							remove_action('pre_user_query','wpqa_custom_search_mention');
						}
					}
				}
			}
		}
		return $content;
	}
endif;
/* Custom search for mention */
if (!function_exists('wpqa_custom_search_mention')) :
	function wpqa_custom_search_mention($user_query) {
		global $wpdb;
		$search_value = $user_query->query_vars;
		$search_value = str_replace("*","",$search_value['search']);
		$search_value = apply_filters("wpqa_search_value_filter",$search_value);
		$user_query->query_where .= " 
		OR ($wpdb->users.ID LIKE '".$search_value."' OR $wpdb->users.ID RLIKE '".$search_value."') 
		OR ($wpdb->users.display_name LIKE '".$search_value."' OR $wpdb->users.display_name RLIKE '".$search_value."') 
		OR ($wpdb->users.user_login LIKE '".$search_value."' OR $wpdb->users.user_login RLIKE '".$search_value."') 
		OR ($wpdb->users.user_nicename LIKE '".$search_value."' OR $wpdb->users.user_nicename RLIKE '".$search_value."') 
		OR ($wpdb->usermeta.meta_key = 'nickname' AND ($wpdb->usermeta.meta_value LIKE '".$search_value."' OR $wpdb->usermeta.meta_value RLIKE '".$search_value."')) 
		OR ($wpdb->usermeta.meta_key = 'first_name' AND ($wpdb->usermeta.meta_value LIKE '".$search_value."' OR $wpdb->usermeta.meta_value RLIKE '".$search_value."'))
		OR ($wpdb->usermeta.meta_key = 'last_name' AND ($wpdb->usermeta.meta_value LIKE '".$search_value."' OR $wpdb->usermeta.meta_value RLIKE '".$search_value."')) ";
	}
endif;
/* Random token */
if (!function_exists('wpqa_token')) :
	function wpqa_token($length){
		$token = "";
		$codeAlphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
		$codeAlphabet.= "abcdefghijklmnopqrstuvwxyz";
		$codeAlphabet.= "0123456789";
		$max = strlen($codeAlphabet);
		for ($i=0; $i < $length; $i++) {
			$token .= $codeAlphabet[random_int(0, $max-1)];
		}
		return $token;
	}
endif;?>