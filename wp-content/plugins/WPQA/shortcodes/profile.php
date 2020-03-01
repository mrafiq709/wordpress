<?php

/* @author    2codeThemes
*  @package   WPQA/shortcodes
*  @version   1.0
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/* Edit profile */
if (!function_exists('wpqa_edit_profile')) :
	function wpqa_edit_profile($atts, $content = null) {
		$a = shortcode_atts( array(
		    'type'  => ''
		), $atts );
		$out = '';
		if (!is_user_logged_in()) {
			if ($a['type'] == "delete") {
				$out .= '<div class="alert-message"><i class="icon-lamp"></i><p>'.esc_html__("Please login to delete account.","wpqa").'</p></div>'.do_shortcode("[wpqa_login]");
			}else {
				$out .= '<div class="alert-message"><i class="icon-lamp"></i><p>'.esc_html__("Please login to edit profile.","wpqa").'</p></div>'.do_shortcode("[wpqa_login]");
			}
		}else {
			if ($a['type'] != "delete") {
				$breadcrumbs = wpqa_options("breadcrumbs");

				$user_meta_avatar = wpqa_avatar_name();
				$user_meta_cover = wpqa_cover_name();

				$edit_profile_items_1 = wpqa_options("edit_profile_items_1");
				$edit_profile_items_2 = wpqa_options("edit_profile_items_2");
				$edit_profile_items_3 = wpqa_options("edit_profile_items_3");
				
				$first_name_required = wpqa_options("first_name_required");
				$last_name_required = wpqa_options("last_name_required");
				$display_name_required = wpqa_options("display_name_required");
				$profile_picture_required = wpqa_options("profile_picture_required");
				$profile_cover_required = wpqa_options("profile_cover_required");
				$country_required = wpqa_options("country_required");
				$city_required = wpqa_options("city_required");
				$phone_required = wpqa_options("phone_required");
				$gender_required = wpqa_options("gender_required");
				$age_required = wpqa_options("age_required");

				$profile_credential_required = wpqa_options("profile_credential_required");
				$question_schedules = wpqa_options("question_schedules");
				$question_schedules_groups = wpqa_options("question_schedules_groups");
				$send_email_new_question = wpqa_options("send_email_new_question");
				$send_email_question_groups = wpqa_options("send_email_question_groups");

				$custom_left_menu = wpqa_options("custom_left_menu");
			}
			$edit_profile_sections = wpqa_options("edit_profile_sections");
			$edit_profile_items_4  = wpqa_options("edit_profile_items_4");
			$delete_account        = wpqa_options("delete_account");
			$out .= '<form class="edit-profile-form wpqa_form wpqa-readonly" method="post" enctype="multipart/form-data">'.apply_filters('wpqa_edit_profile_form','edit_profile');
				$user_id = get_current_user_id();
				$user_info = get_userdata($user_id);
				if ($a['type'] != "delete") {
					$profile_credential = get_the_author_meta('profile_credential',$user_info->ID);
					$url = get_the_author_meta('url',$user_info->ID);
					$twitter = get_the_author_meta('twitter',$user_info->ID);
					$facebook = get_the_author_meta('facebook',$user_info->ID);
					$youtube = get_the_author_meta('youtube',$user_info->ID);
					$vimeo = get_the_author_meta('vimeo',$user_info->ID);
					$linkedin = get_the_author_meta('linkedin',$user_info->ID);
					$follow_email = get_the_author_meta('follow_email',$user_info->ID);
					if (isset($follow_email) && $follow_email != "" && $follow_email != 0 && $follow_email != "on") {
						$follow_email = "on";
						update_user_meta($user_info->ID,'follow_email',$follow_email);
					}
					$display_name = get_the_author_meta('display_name',$user_info->ID);
					$country = get_the_author_meta('country',$user_info->ID);
					$city = get_the_author_meta('city',$user_info->ID);
					$age = get_the_author_meta('age',$user_info->ID);
					$phone = get_the_author_meta('phone',$user_info->ID);
					$gender = get_the_author_meta('gender',$user_info->ID);
					$instagram = get_the_author_meta('instagram',$user_info->ID);
					$pinterest = get_the_author_meta('pinterest',$user_info->ID);
					$show_point_favorite = get_the_author_meta('show_point_favorite',$user_info->ID);
					$question_schedules_user = get_the_author_meta('question_schedules',$user_info->ID);
					$received_email = get_the_author_meta('received_email',$user_info->ID);
					$active_message = wpqa_options("active_message");
					$received_message = get_the_author_meta('received_message',$user_info->ID);
					$unsubscribe_mails = get_the_author_meta('unsubscribe_mails',$user_info->ID);
					$new_payment_mail = get_the_author_meta('new_payment_mail',$user_info->ID);
					$send_message_mail = get_the_author_meta('send_message_mail',$user_info->ID);
					$answer_on_your_question = get_the_author_meta('answer_on_your_question',$user_info->ID);
					$answer_question_follow = get_the_author_meta('answer_question_follow',$user_info->ID);
					$your_avatar = get_the_author_meta($user_meta_avatar,$user_info->ID);
					$your_cover = get_the_author_meta($user_meta_cover,$user_info->ID);

					$categories_left_menu = get_the_author_meta("categories_left_menu",$user_info->ID);
				}
				$rand_e = rand(1,1000);
				if ($a['type'] != "delete" && $breadcrumbs != "on" && wpqa_is_user_profile() && wpqa_is_user_owner() && wpqa_is_user_edit_profile()) {
					$out .= '<div class="profile-setting profile-no-breadcrumbs">
						<a href="#edit-profile" data-type="setting"'.(isset($_POST["profile_type"]) && $_POST["profile_type"] == "setting"?" class='active-tab'":(empty($_POST["profile_type"])?" class='active-tab'":"")).'>'.esc_html__("Edit profile","wpqa").'</a>
						<a href="#change-password" data-type="password"'.(isset($_POST["profile_type"]) && $_POST["profile_type"] == "password"?" class='active-tab'":"").'>'.esc_html__("Change Password","wpqa").'</a>
					</div><div class="clearfix"></div>';
				}
				$out .= '<div class="form-inputs clearfix">
					<div class="page-sections" id="edit-profile"'.(isset($_POST["profile_type"]) && $_POST["profile_type"] == "setting"?" style='display: block'":(isset($_POST["profile_type"]) && $_POST["profile_type"] == "password"?" style='display: none'":"")).'>';
						if ($a['type'] != "delete" && isset($edit_profile_sections) && is_array($edit_profile_sections)) {
							if (isset($edit_profile_items_1["names"]) && isset($edit_profile_items_1["names"]["value"]) && $edit_profile_items_1["names"]["value"] == "names") {
								$edit_profile_items_1["nickname"] = array("sort" => esc_html__("Nickname","wpqa"),"value" => "nickname");
								$edit_profile_items_1["first_name"] = array("sort" => esc_html__("First Name","wpqa"),"value" => "first_name");
								$edit_profile_items_1["last_name"] = array("sort" => esc_html__("Last Name","wpqa"),"value" => "last_name");
								$edit_profile_items_1["display_name"] = array("sort" => esc_html__("Display Name","wpqa"),"value" => "display_name");
							}
							foreach ($edit_profile_sections as $key_sections => $value_sections) {
								if (isset($value_sections["value"]) && $value_sections["value"] == "basic" && isset($edit_profile_items_1) && is_array($edit_profile_items_1)) {
									$out .= '<div class="page-section page-section-'.$value_sections["value"].'">
										<div class="page-wrap-content">
											<h2 class="post-title-2"><i class="icon-vcard"></i>'.esc_html__("Basic Information","wpqa").'</h2>';
											$out .= apply_filters('wpqa_edit_profile_before_email',false,$user_info->ID).
											'<p class="email_field">
												<label for="email_'.$rand_e.'">'.esc_html__("E-Mail","wpqa").'<span class="required">*</span></label>
												<input readonly="readonly" type="text" name="email" id="email_'.$rand_e.'" value="'.(isset($_POST["email"])?esc_attr($_POST["email"]):$user_info->user_email).'">
												<i class="icon-mail"></i>
											</p>';
											foreach ($edit_profile_items_1 as $key_items_1 => $value_items_1) {
												$out = apply_filters("wpqa_edit_profile_sort",$out,"edit_profile_items_1",$edit_profile_items_1,$key_items_1,$value_items_1,"edit",$_POST,$user_info->ID);
												$out .= wpqa_register_edit_fields($key_items_1,$value_items_1,"edit",$rand_e,$user_info);
											}
										$out .= '</div>
										<div class="clearfix"></div>
									</div><!-- End page-section -->';
								}else if (isset($value_sections["value"]) && $value_sections["value"] == "social" && isset($edit_profile_items_2) && !empty($edit_profile_items_2) && is_array($edit_profile_items_2)) {
									$p_count = 0;
									$edit_profile_items_2_keys = array_keys($edit_profile_items_2);
									while ($p_count < count($edit_profile_items_2)) {
										if (isset($edit_profile_items_2[$edit_profile_items_2_keys[$p_count]]["value"]) && $edit_profile_items_2[$edit_profile_items_2_keys[$p_count]]["value"] != "" && $edit_profile_items_2[$edit_profile_items_2_keys[$p_count]]["value"] != "0") {
											$profile_one_2 = $p_count;
											break;
										}
										$p_count++;
									}
									if (isset($profile_one_2)) {
										$out .= '<div class="page-section page-section-'.$value_sections["value"].'">
											<div class="page-wrap-content">
												<h2 class="post-title-2"><i class="icon-globe"></i>'.esc_html__("Social Profiles","wpqa").'</h2>
												<div class="wpqa_form_2">';
													foreach ($edit_profile_items_2 as $key_items_2 => $value_items_2) {
														if ($key_items_2 == "facebook" && isset($value_items_2["value"]) && $value_items_2["value"] == "facebook") {
															$out .= '<p class="facebook_field">
																<label for="facebook_'.$rand_e.'">'.esc_html__("Facebook","wpqa").'  '.esc_html__("(Put the full URL)","wpqa").'</label>
																<input readonly="readonly" type="text" name="facebook" id="facebook_'.$rand_e.'" value="'.esc_url(isset($_POST["facebook"])?$_POST["facebook"]:$facebook).'">
																<i class="icon-facebook"></i>
															</p>';
														}else if ($key_items_2 == "twitter" && isset($value_items_2["value"]) && $value_items_2["value"] == "twitter") {
															$out .= '<p class="twitter_field">
																<label for="twitter_'.$rand_e.'">'.esc_html__("Twitter","wpqa").'  '.esc_html__("(Put the full URL)","wpqa").'</label>
																<input readonly="readonly" type="text" name="twitter" id="twitter_'.$rand_e.'" value="'.esc_url(isset($_POST["twitter"])?$_POST["twitter"]:$twitter).'">
																<i class="icon-twitter"></i>
															</p>';
														}else if ($key_items_2 == "youtube" && isset($value_items_2["value"]) && $value_items_2["value"] == "youtube") {
															$out .= '<p class="youtube_field">
																<label for="youtube_'.$rand_e.'">'.esc_html__("Youtube","wpqa").'  '.esc_html__("(Put the full URL)","wpqa").'</label>
																<input readonly="readonly" type="text" name="youtube" id="youtube_'.$rand_e.'" value="'.esc_url(isset($_POST["youtube"])?$_POST["youtube"]:$youtube).'">
																<i class="icon-play"></i>
															</p>';
														}else if ($key_items_2 == "vimeo" && isset($value_items_2["value"]) && $value_items_2["value"] == "vimeo") {
															$out .= '<p class="vimeo_field">
																<label for="vimeo_'.$rand_e.'">'.esc_html__("Vimeo","wpqa").'  '.esc_html__("(Put the full URL)","wpqa").'</label>
																<input readonly="readonly" type="text" name="vimeo" id="vimeo_'.$rand_e.'" value="'.esc_url(isset($_POST["vimeo"])?$_POST["vimeo"]:$vimeo).'">
																<i class="icon-vimeo"></i>
															</p>';
														}else if ($key_items_2 == "linkedin" && isset($value_items_2["value"]) && $value_items_2["value"] == "linkedin") {
															$out .= '<p class="linkedin_field">
																<label for="linkedin_'.$rand_e.'">'.esc_html__("Linkedin","wpqa").'  '.esc_html__("(Put the full URL)","wpqa").'</label>
																<input readonly="readonly" type="text" name="linkedin" id="linkedin_'.$rand_e.'" value="'.esc_url(isset($_POST["linkedin"])?$_POST["linkedin"]:$linkedin).'">
																<i class="icon-linkedin"></i>
															</p>';
														}else if ($key_items_2 == "instagram" && isset($value_items_2["value"]) && $value_items_2["value"] == "instagram") {
															$out .= '<p class="instagram_field">
																<label for="instagram_'.$rand_e.'">'.esc_html__("Instagram","wpqa").'  '.esc_html__("(Put the full URL)","wpqa").'</label>
																<input readonly="readonly" type="text" name="instagram" id="instagram_'.$rand_e.'" value="'.esc_url(isset($_POST["instagram"])?$_POST["instagram"]:$instagram).'">
																<i class="icon-instagrem"></i>
															</p>';
														}else if ($key_items_2 == "pinterest" && isset($value_items_2["value"]) && $value_items_2["value"] == "pinterest") {
															$out .= '<p class="pinterest_field">
																<label for="pinterest_'.$rand_e.'">'.esc_html__("Pinterest","wpqa").'  '.esc_html__("(Put the full URL)","wpqa").'</label>
																<input readonly="readonly" type="text" name="pinterest" id="pinterest_'.$rand_e.'" value="'.esc_url(isset($_POST["pinterest"])?$_POST["pinterest"]:$pinterest).'">
																<i class="icon-pinterest"></i>
															</p>';
														}
													}
												$out .= '</div>
											</div>
											<div class="clearfix"></div>
										</div><!-- End page-section -->';
									}
								}else if (isset($value_sections["value"]) && $value_sections["value"] == "about" && isset($edit_profile_items_3) && is_array($edit_profile_items_3)) {
									$p_count = 0;
									$edit_profile_items_3_keys = array_keys($edit_profile_items_3);
									while ($p_count < count($edit_profile_items_3)) {
										if (isset($edit_profile_items_3[$edit_profile_items_3_keys[$p_count]]["value"]) && $edit_profile_items_3[$edit_profile_items_3_keys[$p_count]]["value"] != "" && $edit_profile_items_3[$edit_profile_items_3_keys[$p_count]]["value"] != "0") {
											$profile_one_3 = $p_count;
											break;
										}
										$p_count++;
									}
									if (isset($profile_one_3)) {
										$out .= '<div class="page-section page-section-'.$value_sections["value"].'">
											<div class="page-wrap-content">
												<h2 class="post-title-2"><i class="icon-graduation-cap"></i>'.esc_html__("About Me","wpqa").'</h2>';
												foreach ($edit_profile_items_3 as $key_items_3 => $value_items_3) {
													if ($key_items_3 == "profile_credential" && isset($value_items_3["value"]) && $value_items_3["value"] == "profile_credential") {
														$out .= '<p class="profile_credential_field">
															<label for="profile_credential_'.$rand_e.'">'.esc_html__("Add profile credential","wpqa").($profile_credential_required == "on"?'<span class="required">*</span>':'').'</label>
															<input readonly="readonly" type="text" name="profile_credential" id="profile_credential_'.$rand_e.'" value="'.esc_attr(isset($_POST["profile_credential"])?$_POST["profile_credential"]:$profile_credential).'">
															<i class="icon-info"></i>
														</p>';
													}else if ($key_items_3 == "website" && isset($value_items_3["value"]) && $value_items_3["value"] == "website") {
														$out .= '<p class="website_field">
															<label for="url_'.$rand_e.'">'.esc_html__("Website","wpqa").'</label>
															<input readonly="readonly" type="text" name="url" id="url_'.$rand_e.'" value="'.esc_url(isset($_POST["url"])?$_POST["url"]:$url).'">
															<i class="icon-link"></i>
														</p>';
													}else if ($key_items_3 == "bio" && isset($value_items_3["value"]) && $value_items_3["value"] == "bio") {
														$bio_editor = wpqa_options("bio_editor");
														if ($bio_editor == "on") {
															$settings = array("textarea_name" => "description","media_buttons" => true,"textarea_rows" => 10);
															$settings = apply_filters('wpqa_description_editor_setting',$settings);
															ob_start();
															wp_editor((isset($_POST["description"])?wpqa_kses_stip($_POST["description"],"yes"):wpqa_kses_stip($user_info->description,"yes")),"description_".$rand_e,$settings);
															$editor_contents = ob_get_clean();
															$out .= '<div class="the-description wpqa_textarea the-textarea">'.$editor_contents.'</div>';
														}else {
															$out .= '<p class="bio_field">
																<label for="description_'.$rand_e.'">'.esc_html__("Professional Bio","wpqa").'</label>
																<textarea name="description" id="description_'.$rand_e.'" cols="58" rows="8">'.(isset($_POST["description"])?stripslashes(sanitize_textarea_field($_POST["description"])):stripslashes(sanitize_textarea_field($user_info->description))).'</textarea>
																<i class="icon-pencil"></i>
															</p>';
														}
													}else if ($key_items_3 == "private_pages" && isset($value_items_3["value"]) && $value_items_3["value"] == "private_pages") {
														$out .= '<p class="show_point_favorite_field normal_label">
															<label for="show_point_favorite_'.$rand_e.'">
																<span class="wpqa_checkbox"><input type="checkbox" name="show_point_favorite" id="show_point_favorite_'.$rand_e.'" value="on" '.checked(esc_attr(isset($_POST["show_point_favorite"])?$_POST["show_point_favorite"]:(!empty($_POST) && empty($_POST["show_point_favorite"])?"":$show_point_favorite)),"on",false).'></span>
																<span class="wpqa_checkbox_span">'.esc_html__("Show your private pages for all the users?","wpqa").'</span><span> '.esc_html__("(Points, favorite and followed pages).","wpqa").'</span>
															</label>
														</p>';
													}else if ($key_items_3 == "received_message" && isset($value_items_3["value"]) && $value_items_3["value"] == "received_message" && $active_message == "on") {
														$out .= '<p class="received_message_field normal_label">
															<label for="received_message_'.$rand_e.'">
																<span class="wpqa_checkbox"><input type="checkbox" name="received_message" id="received_message_'.$rand_e.'" value="on" '.checked(esc_attr(isset($_POST["received_message"])?$_POST["received_message"]:(!empty($_POST) && empty($_POST["received_message"])?"":$received_message)),"on",false).'></span>
																<span class="wpqa_checkbox_span">'.esc_html__("Do you need to receive message from another users?","wpqa").'</span>
															</label>
														</p>';
													}
												}
											$out .= '</div>
											<div class="clearfix"></div>
										</div><!-- End page-section -->';
									}
								}else if (isset($value_sections["value"]) && $value_sections["value"] == "mails_setting" && isset($edit_profile_items_4) && is_array($edit_profile_items_4)) {
									$unsubscribe_mails_value = (esc_attr(isset($_POST["unsubscribe_mails"])?$_POST["unsubscribe_mails"]:(!empty($_POST) && empty($_POST["unsubscribe_mails"])?"":$unsubscribe_mails)));
									$p_count = 0;
									$edit_profile_items_4_keys = array_keys($edit_profile_items_4);
									while ($p_count < count($edit_profile_items_4)) {
										if (isset($edit_profile_items_4[$edit_profile_items_4_keys[$p_count]]["value"]) && $edit_profile_items_4[$edit_profile_items_4_keys[$p_count]]["value"] != "" && $edit_profile_items_4[$edit_profile_items_4_keys[$p_count]]["value"] != "0") {
											$profile_one_4 = $p_count;
											break;
										}
										$p_count++;
									}
									if (isset($profile_one_4)) {
										$pay_ask = wpqa_options('pay_ask');
										$pay_to_sticky = wpqa_options('pay_to_sticky');
										$buy_points_payment = wpqa_options('buy_points_payment');
										$pay_to_answer = wpqa_options('pay_to_answer');
										$pay_to_anything = apply_filters("wpqa_filter_pay_to_anything",false);
										$out .= '<div class="page-section page-section-'.$value_sections["value"].'">
											<div class="page-wrap-content">
												<h2 class="post-title-2"><i class="icon-mail"></i>'.esc_html__("Mails Setting","wpqa").'</h2>
												<br>';
												foreach ($edit_profile_items_4 as $key_items_4 => $value_items_4) {
													if ($key_items_4 == "follow_email" && isset($value_items_4["value"]) && $value_items_4["value"] == "follow_email") {
														$out .= '<p class="follow_email_field normal_label">
															<label for="follow_email_'.$rand_e.'">
																<span class="wpqa_checkbox"><input type="checkbox" name="follow_email" id="follow_email_'.$rand_e.'" value="on" '.checked(esc_attr(isset($_POST["follow_email"])?$_POST["follow_email"]:(!empty($_POST) && empty($_POST["follow_email"])?"":$follow_email)),"on",false).'></span>
																<span class="wpqa_checkbox_span">'.esc_html__("Show follow up email?","wpqa").'</span>
															</label>
														</p>';
													}else if ($key_items_4 == "question_schedules" && isset($value_items_4["value"]) && $value_items_4["value"] == "question_schedules" && $question_schedules == "on" && is_array($question_schedules_groups) && isset($user_info->roles[0]) && in_array($user_info->roles[0],$question_schedules_groups)) {
														$out .= '<p class="question_schedules_field normal_label'.($unsubscribe_mails_value == "on"?" wpqa_hide":"").'">
															<label for="question_schedules_'.$rand_e.'">
																<span class="wpqa_checkbox"><input type="checkbox" name="question_schedules" id="question_schedules_'.$rand_e.'" value="on" '.checked(esc_attr(isset($_POST["question_schedules"])?$_POST["question_schedules"]:(!empty($_POST) && empty($_POST["question_schedules"])?"":$question_schedules_user)),"on",false).'></span>
																<span class="wpqa_checkbox_span">'.esc_html__("Do you need to get schedules mails for the recent questions?","wpqa").'</span>
															</label>
														</p>';
													}else if ($key_items_4 == "send_emails" && isset($value_items_4["value"]) && $value_items_4["value"] == "send_emails" && $send_email_new_question == "on" && is_array($send_email_question_groups) && isset($user_info->roles[0]) && in_array($user_info->roles[0],$send_email_question_groups)) {
														$out .= '<p class="received_email_field normal_label'.($unsubscribe_mails_value == "on"?" wpqa_hide":"").'">
															<label for="received_email_'.$rand_e.'">
																<span class="wpqa_checkbox"><input type="checkbox" name="received_email" id="received_email_'.$rand_e.'" value="on" '.checked(esc_attr(isset($_POST["received_email"])?$_POST["received_email"]:(!empty($_POST) && empty($_POST["received_email"])?"":$received_email)),"on",false).'></span>
																<span class="wpqa_checkbox_span">'.esc_html__("Do you need to get mails when new question added?","wpqa").'</span>
															</label>
														</p>';
													}else if ($key_items_4 == "unsubscribe_mails" && isset($value_items_4["value"]) && $value_items_4["value"] == "unsubscribe_mails") {
														$out .= '<p class="unsubscribe_mails_field normal_label">
															<label for="unsubscribe_mails_'.$rand_e.'">
																<span class="wpqa_checkbox"><input type="checkbox" name="unsubscribe_mails" class="unsubscribe_mails" id="unsubscribe_mails_'.$rand_e.'" value="on" '.checked($unsubscribe_mails_value,"on",false).'></span>
																<span class="wpqa_checkbox_span">'.esc_html__("Do you need to unsubscribe all the mails?","wpqa").'</span>
															</label>
														</p>';
													}else if ($key_items_4 == "new_payment_mail" && isset($value_items_4["value"]) && $value_items_4["value"] == "new_payment_mail" && ($pay_ask == "on" || $pay_to_sticky == "on" || $buy_points_payment == "on" || $pay_to_answer == "on" || $pay_to_anything == true)) {
														$out .= '<p class="new_payment_mail_field normal_label'.($unsubscribe_mails_value == "on"?" wpqa_hide":"").'">
															<label for="new_payment_mail_'.$rand_e.'">
																<span class="wpqa_checkbox"><input type="checkbox" name="new_payment_mail" class="new_payment_mail" id="new_payment_mail_'.$rand_e.'" value="on" '.checked(esc_attr(isset($_POST["new_payment_mail"])?$_POST["new_payment_mail"]:(!empty($_POST) && empty($_POST["new_payment_mail"])?"":$new_payment_mail)),"on",false).'></span>
																<span class="wpqa_checkbox_span">'.esc_html__("Do you need to get mails for the new payments?","wpqa").'</span>
															</label>
														</p>';
													}else if ($key_items_4 == "send_message_mail" && isset($value_items_4["value"]) && $value_items_4["value"] == "send_message_mail" && $active_message == "on") {
														$out .= '<p class="send_message_mail_field normal_label'.($unsubscribe_mails_value == "on"?" wpqa_hide":"").'">
															<label for="send_message_mail_'.$rand_e.'">
																<span class="wpqa_checkbox"><input type="checkbox" name="send_message_mail" class="send_message_mail" id="send_message_mail_'.$rand_e.'" value="on" '.checked(esc_attr(isset($_POST["send_message_mail"])?$_POST["send_message_mail"]:(!empty($_POST) && empty($_POST["send_message_mail"])?"":$send_message_mail)),"on",false).'></span>
																<span class="wpqa_checkbox_span">'.esc_html__("Do you need to get mails when new message sent?","wpqa").'</span>
															</label>
														</p>';
													}else if ($key_items_4 == "answer_on_your_question" && isset($value_items_4["value"]) && $value_items_4["value"] == "answer_on_your_question") {
														$out .= '<p class="answer_on_your_question_field normal_label'.($unsubscribe_mails_value == "on"?" wpqa_hide":"").'">
															<label for="answer_on_your_question_'.$rand_e.'">
																<span class="wpqa_checkbox"><input type="checkbox" name="answer_on_your_question" class="answer_on_your_question" id="answer_on_your_question_'.$rand_e.'" value="on" '.checked(esc_attr(isset($_POST["answer_on_your_question"])?$_POST["answer_on_your_question"]:(!empty($_POST) && empty($_POST["answer_on_your_question"])?"":$answer_on_your_question)),"on",false).'></span>
																<span class="wpqa_checkbox_span">'.esc_html__("Do you need to get mails when new answer added for your question?","wpqa").'</span>
															</label>
														</p>';
													}else if ($key_items_4 == "answer_question_follow" && isset($value_items_4["value"]) && $value_items_4["value"] == "answer_question_follow") {
														$out .= '<p class="answer_question_follow_field normal_label'.($unsubscribe_mails_value == "on"?" wpqa_hide":"").'">
															<label for="answer_question_follow_'.$rand_e.'">
																<span class="wpqa_checkbox"><input type="checkbox" name="answer_question_follow" class="answer_question_follow" id="answer_question_follow_'.$rand_e.'" value="on" '.checked(esc_attr(isset($_POST["answer_question_follow"])?$_POST["answer_question_follow"]:(!empty($_POST) && empty($_POST["answer_question_follow"])?"":$answer_question_follow)),"on",false).'></span>
																<span class="wpqa_checkbox_span">'.esc_html__("Do you need to get mails when new answer added for your following question?","wpqa").'</span>
															</label>
														</p>'.apply_filters('wpqa_after_answer_question_follow',false,$user_info->ID);
													}
												}
											$out .= '</div>
											<div class="clearfix"></div>
										</div><!-- End page-section -->';
									}
								}else if (isset($value_sections["value"]) && $value_sections["value"] == "categories" && $custom_left_menu == "on") {
									$exclude = apply_filters('wpqa_exclude_question_category',array());
									$out .= '<div class="page-section page-section-'.$value_sections["value"].'">
										<div class="page-wrap-content">
											<h2 class="post-title-2"><i class="icon-folder"></i>'.esc_html__("Custom Categories","wpqa").'</h2>
											<p class="custom_categories_field">
												<span class="styled-select">'.
													wp_dropdown_categories(array_merge($exclude,array(
														'taxonomy'     => 'question-category',
													    'orderby'      => 'name',
													    'echo'         => 0,
													    'hide_empty'   => 0,
													    'hierarchical' => 1,
													    'id'           => "add_categories_left_menu",
													    'name'         => "",
													))).'
												</span>
												<i class="icon-folder"></i>
											</p>
											<div class="clearfix"></div>
											<a data-name="categories_left_menu" data-id="categories_left_menu_items" class="button-default-3 add_categories_left_menu">'.esc_html__("Add category","wpqa").'</a>
											<ul class="profile_items" id="categories_left_menu_items">';
											if (is_array($categories_left_menu) && !empty($categories_left_menu)) {
												foreach ($categories_left_menu as $key => $value) {
													$cat_id = (isset($value["value"]) && $value["value"] != ""?(int)$value["value"]:0);
													if ($cat_id > 0) {
														$get_term = get_term($cat_id,'question-category');
														if (isset($get_term->name)) {
															$out .= '<li class="categories" id="categories_left_menu_items_'.$cat_id.'">
																<label>'.$get_term->name.'</label>
																<input name="categories_left_menu[cat-'.$cat_id.'][value]" value="'.$cat_id.'" type="hidden">
																<div>
																	<div class="del-item-li"><i class="icon-cancel"></i></div>
																	<div class="move-poll-li"><i class="icon-menu"></i></div>
																</div>
															</li>';
														}
													}
												}
											}
											$out .= '</ul>
										</div>
										<div class="clearfix"></div>
									</div><!-- End page-section -->';
								}else if (isset($value_sections["value"]) && $value_sections["value"] == "delete_account" && $delete_account == "on") {
									$out .= '<div class="page-section page-section-'.$value_sections["value"].'">
										<div class="page-wrap-content">
											<h2 class="post-title-2"><i class="icon-trash"></i>'.esc_html__("Delete account","wpqa").'</h2>
											<p class="delete-account"><a href="'.esc_url(wpqa_get_profile_permalink($user_info->ID,"delete")).'">'.esc_html__("Do you need to delete your account?","wpqa").'</a></p>
										</div>
										<div class="clearfix"></div>
									</div><!-- End page-section -->';
								}
							}
						}
						if ($a['type'] == "delete" && isset($edit_profile_sections) && is_array($edit_profile_sections)) {
							foreach ($edit_profile_sections as $key_sections => $value_sections) {
								if (isset($value_sections["value"]) && $value_sections["value"] == "delete_account" && $delete_account == "on") {
									$out .= '<div class="page-section page-section-delete_account">
										<div class="page-wrap-content">
											<h2 class="post-title-2"><i class="icon-trash"></i>'.esc_html__("Delete account","wpqa").'</h2>
											<br>
											<p class="delete_account_field normal_label">
												<label for="delete_account_'.$rand_e.'">
													<span class="wpqa_checkbox"><input type="checkbox" name="delete_account" class="delete_account" id="delete_account_'.$rand_e.'"></span>
													<span class="wpqa_checkbox_span">'.esc_html__("Do you need to delete your account?","wpqa").'</span>
												</label>
											</p>
										</div>
										<div class="clearfix"></div>
									</div><!-- End page-section -->';
								}
							}
						}
					$out .= '</div><!-- End page-sections -->
					
					<div class="page-sections" id="change-password"'.(isset($_POST["profile_type"]) && $_POST["profile_type"] == "password"?" style='display: block'":(isset($_POST["profile_type"]) && $_POST["profile_type"] == "setting"?" style='display: none'":"")).'>
						<div class="page-section">
							<div class="page-wrap-content">
								<h2 class="post-title-2"><i class="icon-lock"></i>'.esc_html__("Change password","wpqa").'</h2>
								<p class="login-password">
									<label for="newpassword_'.$rand_e.'">'.esc_html__("New Password","wpqa").'<span class="required">*</span></label>
									<input readonly="readonly" id="newpassword_'.$rand_e.'" class="required-item" type="password" name="pass1">
									<i class="icon-lock-open"></i>
								</p>
								<p class="login-password">
									<label for="newpassword2_'.$rand_e.'">'.esc_html__("Confirm Password","wpqa").'<span class="required">*</span></label>
									<input readonly="readonly" id="newpassword2_'.$rand_e.'" class="required-item" type="password" name="pass2">
									<i class="icon-lock-open"></i>
								</p>
							</div>
						</div><!-- End page-section -->
					</div><!-- End page-sections -->
				</div>
				
				<p class="form-submit">
					<span class="load_span"><span class="loader_2"></span></span>
					<input type="hidden" name="user_action" value="edit_profile">
					<input type="hidden" name="action" value="update">
					<input type="hidden" name="admin_bar_front" value="1">
					<input type="hidden" name="user_id" id="user_id" value="'.esc_attr($user_info->ID).'">
					<input type="hidden" name="user_login" id="user_login" value="'.esc_attr($user_info->user_login).'">
					<input type="hidden" name="profile_type" id="profile_type" value="'.(isset($_POST["profile_type"]) && $_POST["profile_type"] == "password"?"password":"setting").'">
					'.wp_nonce_field('wpqa_profile_nonce','wpqa_profile_nonce',true,false).'
					<input type="submit" value="'.($a['type'] == "delete"?esc_attr__("Delete","wpqa"):esc_attr__("Save","wpqa")).'" class="button-default button-hide-click login-submit submit">
				</p>
			
			</form>';
		}
		return $out;
	}
endif;
/* Process edit profile form */
if (!function_exists('wpqa_process_edit_profile_form')) :
	function wpqa_process_edit_profile_form() {
		if (isset($_POST['wpqa_profile_nonce']) && wp_verify_nonce($_POST['wpqa_profile_nonce'],'wpqa_profile_nonce')) {
			$user_meta_avatar = wpqa_avatar_name();
			$user_meta_cover = wpqa_cover_name();

			$edit_profile_items_1 = wpqa_options("edit_profile_items_1");
			$edit_profile_items_3 = wpqa_options("edit_profile_items_3");
			
			$profile_credential_register = (isset($edit_profile_items_3["profile_credential"]["value"]) && $edit_profile_items_3["profile_credential"]["value"] == "profile_credential"?"on":0);
			$nickname = (isset($edit_profile_items_1["nickname"]["value"]) && $edit_profile_items_1["nickname"]["value"] == "nickname"?"on":0);
			
			$profile_credential_required = wpqa_options("profile_credential_required");
			$profile_credential_maximum = wpqa_options("profile_credential_maximum");
			$user_id = get_current_user_id();
			$get_your_avatar = get_user_meta($user_id,$user_meta_avatar,true);
			$get_your_cover = get_user_meta($user_id,$user_meta_cover,true);
			
			require_once(ABSPATH . 'wp-admin/includes/user.php');
			
			$errors = new WP_Error();
			$posted = array(
				'email'                   => esc_html($_POST['email']),
				'pass1'                   => (isset($_POST['profile_type']) && $_POST['profile_type'] == "password" && isset($_POST['pass1']) && $_POST['pass1'] != ""?esc_html($_POST['pass1']):""),
				'pass2'                   => (isset($_POST['profile_type']) && $_POST['profile_type'] == "password" && isset($_POST['pass2']) && $_POST['pass2'] != ""?esc_html($_POST['pass2']):""),
				'first_name'              => (isset($_POST['first_name']) && $_POST['first_name'] != ""?esc_html($_POST['first_name']):""),
				'last_name'               => (isset($_POST['last_name']) && $_POST['last_name'] != ""?esc_html($_POST['last_name']):""),
				'nickname'                => (isset($_POST['nickname']) && $_POST['nickname'] != ""?esc_html($_POST['nickname']):""),
				'display_name'            => (isset($_POST['display_name']) && $_POST['display_name'] != ""?esc_html($_POST['display_name']):""),
				'country'                 => (isset($_POST['country']) && $_POST['country'] != ""?esc_html($_POST['country']):""),
				'city'                    => (isset($_POST['city']) && $_POST['city'] != ""?esc_html($_POST['city']):""),
				'phone'                   => (isset($_POST['phone']) && $_POST['phone'] != ""?esc_html($_POST['phone']):""),
				'gender'                  => (isset($_POST['gender']) && $_POST['gender'] != ""?esc_html($_POST['gender']):""),
				'age'                     => (isset($_POST['age']) && $_POST['age'] != ""?esc_html($_POST['age']):""),
				'profile_credential'      => (isset($_POST['profile_credential']) && $_POST['profile_credential'] != ""?esc_html($_POST['profile_credential']):""),
				'facebook'                => (isset($_POST['facebook']) && $_POST['facebook'] != ""?esc_url($_POST['facebook']):""),
				'twitter'                 => (isset($_POST['twitter']) && $_POST['twitter'] != ""?esc_url($_POST['twitter']):""),
				'youtube'                 => (isset($_POST['youtube']) && $_POST['youtube'] != ""?esc_url($_POST['youtube']):""),
				'vimeo'                   => (isset($_POST['vimeo']) && $_POST['vimeo'] != ""?esc_url($_POST['vimeo']):""),
				'linkedin'                => (isset($_POST['linkedin']) && $_POST['linkedin'] != ""?esc_url($_POST['linkedin']):""),
				'instagram'               => (isset($_POST['instagram']) && $_POST['instagram'] != ""?esc_url($_POST['instagram']):""),
				'pinterest'               => (isset($_POST['pinterest']) && $_POST['pinterest'] != ""?esc_url($_POST['pinterest']):""),
				'follow_email'            => (isset($_POST['follow_email']) && $_POST['follow_email'] != ""?esc_html($_POST['follow_email']):""),
				'show_point_favorite'     => (isset($_POST['show_point_favorite']) && $_POST['show_point_favorite'] != ""?esc_html($_POST['show_point_favorite']):""),
				'question_schedules'      => (isset($_POST['question_schedules']) && $_POST['question_schedules'] != ""?esc_html($_POST['question_schedules']):""),
				'received_email'          => (isset($_POST['received_email']) && $_POST['received_email'] != ""?esc_html($_POST['received_email']):""),
				'received_message'        => (isset($_POST['received_message']) && $_POST['received_message'] != ""?esc_html($_POST['received_message']):""),
				'unsubscribe_mails'       => (isset($_POST['unsubscribe_mails']) && $_POST['unsubscribe_mails'] != ""?esc_html($_POST['unsubscribe_mails']):""),
				'new_payment_mail'        => (isset($_POST['new_payment_mail']) && $_POST['new_payment_mail'] != ""?esc_html($_POST['new_payment_mail']):""),
				'send_message_mail'       => (isset($_POST['send_message_mail']) && $_POST['send_message_mail'] != ""?esc_html($_POST['send_message_mail']):""),
				'answer_on_your_question' => (isset($_POST['answer_on_your_question']) && $_POST['answer_on_your_question'] != ""?esc_html($_POST['answer_on_your_question']):""),
				'answer_question_follow'  => (isset($_POST['answer_question_follow']) && $_POST['answer_question_follow'] != ""?esc_html($_POST['answer_question_follow']):""),
				'delete_account'          => (isset($_POST['delete_account']) && $_POST['delete_account'] != ""?esc_html($_POST['delete_account']):""),
				'url'                     => (isset($_POST['url']) && $_POST['url'] != ""?esc_url($_POST['url']):""),
				'description'             => (isset($_POST['description']) && $_POST['description'] != ""?esc_html($_POST['description']):""),
				'categories_left_menu'    => (isset($_POST['categories_left_menu']) && $_POST['categories_left_menu'] != ""?$_POST['categories_left_menu']:""),
			);
			$posted = apply_filters("wpqa_edit_profile_posted",$posted);

			if (isset($_POST['delete_account']) && $_POST['delete_account'] == "on") {
				wp_delete_user($user_id,0);
				wpqa_session('<div class="alert-message success"><i class="icon-check"></i><p>'.esc_html__("Profile has been deleted.","wpqa").'</p></div>','wpqa_session');
				wp_safe_redirect(esc_url(home_url('/')));
				exit;
			}

			if (isset($_POST['profile_type']) && $_POST['profile_type'] == "setting") {
				unset($_POST['pass1']);
				unset($_POST['pass2']);
			}
			
			if (empty($_POST['email'])) {
				$errors->add('required-field','<strong>'.esc_html__("Error","wpqa").' :&nbsp;</strong> '.esc_html__("There are required fields (Email).","wpqa"));
			}
			if (isset($_POST['profile_type']) && $_POST['profile_type'] == "password" && $_POST['pass1'] !== $_POST['pass2']) {
				$errors->add('required-field','<strong>'.esc_html__("Error","wpqa").' :&nbsp;</strong> '.esc_html__("Password does not match.","wpqa"));
			}

			do_action('wpqa_edit_profile_errors_main',$errors,$posted,$edit_profile_items_1,"edit",$user_id);

			if (empty($_POST['profile_credential']) && $profile_credential_register === "on" && $profile_credential_required == "on") {
				$errors->add('required-field','<strong>'.esc_html__("Error","wpqa").' :&nbsp;</strong> '.esc_html__("There are required fields (Profile credential).","wpqa"));
			}
			if (isset($_POST['profile_credential']) && $profile_credential_maximum > 0 && strlen($_POST['profile_credential']) > $profile_credential_maximum) {
				$errors->add('required-field','<strong>'.esc_html__("Error","wpqa").' :&nbsp;</strong> '.esc_html__("Sorry, The maximum characters for the profile credential is","wpqa")." ".$profile_credential_maximum);
			}
			if (isset($_POST['facebook']) && $_POST['facebook'] != "" && filter_var($_POST['facebook'],FILTER_VALIDATE_URL) === FALSE) {
				$errors->add('required-field','<strong>'.esc_html__("Error","wpqa").' :&nbsp;</strong> '.esc_html__("Not a valid URL (Facebook).","wpqa"));
			}
			if (isset($_POST['twitter']) && $_POST['twitter'] != "" && filter_var($_POST['twitter'],FILTER_VALIDATE_URL) === FALSE) {
				$errors->add('required-field','<strong>'.esc_html__("Error","wpqa").' :&nbsp;</strong> '.esc_html__("Not a valid URL (Twitter).","wpqa"));
			}
			if (isset($_POST['youtube']) && $_POST['youtube'] != "" && filter_var($_POST['youtube'],FILTER_VALIDATE_URL) === FALSE) {
				$errors->add('required-field','<strong>'.esc_html__("Error","wpqa").' :&nbsp;</strong> '.esc_html__("Not a valid URL (Youtube).","wpqa"));
			}
			if (isset($_POST['vimeo']) && $_POST['vimeo'] != "" && filter_var($_POST['vimeo'],FILTER_VALIDATE_URL) === FALSE) {
				$errors->add('required-field','<strong>'.esc_html__("Error","wpqa").' :&nbsp;</strong> '.esc_html__("Not a valid URL (Vimeo).","wpqa"));
			}
			if (isset($_POST['linkedin']) && $_POST['linkedin'] != "" && filter_var($_POST['linkedin'],FILTER_VALIDATE_URL) === FALSE) {
				$errors->add('required-field','<strong>'.esc_html__("Error","wpqa").' :&nbsp;</strong> '.esc_html__("Not a valid URL (Linkedin).","wpqa"));
			}
			if (isset($_POST['instagram']) && $_POST['instagram'] != "" && filter_var($_POST['instagram'],FILTER_VALIDATE_URL) === FALSE) {
				$errors->add('required-field','<strong>'.esc_html__("Error","wpqa").' :&nbsp;</strong> '.esc_html__("Not a valid URL (Instagram).","wpqa"));
			}
			if (isset($_POST['pinterest']) && $_POST['pinterest'] != "" && filter_var($_POST['pinterest'],FILTER_VALIDATE_URL) === FALSE) {
				$errors->add('required-field','<strong>'.esc_html__("Error","wpqa").' :&nbsp;</strong> '.esc_html__("Not a valid URL (Pinterest).","wpqa"));
			}

			do_action('wpqa_edit_profile_errors',$errors,$posted,$edit_profile_items_1,"edit",$user_id);
			
			if ($errors->get_error_code()) {
				return $errors;
			}
			
			isset($_POST['admin_bar_front']) ? 'true' : 'false';
			if ($nickname !== 'on' && isset($_POST['nickname'])) {
				$_POST['nickname'] = get_the_author_meta("user_login",$user_id);
			}
			$errors_user = edit_user($user_id);
			if (is_wp_error($errors_user)) {
				return $errors_user;
			}

			do_action('wpqa_personal_update_profile',$user_id,$posted,isset($_FILES)?$_FILES:array(),"edit");

			if (sizeof($errors->errors) > 0) {
				return $errors;
			}

			$update_profile = get_user_meta($user_id,"update_profile",true);
			if ($update_profile == "yes") {
				delete_user_meta($user_id,"update_profile");
	  			wpqa_session('<div class="alert-message success"><i class="icon-check"></i><p>'.esc_html__("Profile has been updated.","wpqa").'</p></div>','wpqa_session');
				wp_safe_redirect(esc_url(home_url('/')));
				exit;
			}
			return;
		}
	}
endif;
/* Edit profile form */
if (!function_exists('wpqa_edit_profile_form')) :
	function wpqa_edit_profile_form($edit) {
		if (isset($_POST["user_action"]) && $_POST["user_action"] == $edit) :
			$return = wpqa_process_edit_profile_form();
			if (is_wp_error($return)) :
	   			return '<div class="wpqa_error">'.$return->get_error_message().'</div>';
	   		else :
	   			return '<div class="wpqa_success">'.esc_html__("Profile has been updated.","wpqa").'</div>';
	   		endif;
		endif;
	}
endif;
add_filter('wpqa_edit_profile_form','wpqa_edit_profile_form');
/* Show profile fields */
add_action( 'show_user_profile', 'wpqa_show_extra_profile_fields' );
add_action( 'edit_user_profile', 'wpqa_show_extra_profile_fields' );
if (!function_exists('wpqa_show_extra_profile_fields')) :
	function wpqa_show_extra_profile_fields( $user ) {?>
		<table class="form-table">
			<tr class="form-terms">
				<th colspan="2" scope="row" valign="top">
					<div class="discy_framework">
						<?php if (class_exists('discy_admin_fields_class')) {
							discy_admin_fields_class::discy_admin_fields("author",discy_author,"author",$user->ID,discy_admin_author($user->ID));
						}?>
					</div>
				</th>
			</tr>
		</table>
	<?php }
endif;
/* Save user's meta */
add_action( 'wpqa_personal_update_profile', 'wpqa_save_extra_profile_fields' );
add_action( 'edit_user_profile_update', 'wpqa_save_extra_profile_fields' );
if (!function_exists('wpqa_save_extra_profile_fields')) :
	function wpqa_save_extra_profile_fields( $user_id ) {
		if ( !current_user_can( 'edit_user', $user_id ) ) return false;

		$options = (function_exists('discy_admin_author')?discy_admin_author($user_id):array());
		$get_current_user_id = get_current_user_id();
		$points_social = (int)wpqa_options("points_social");
		
		if (isset($_POST['admin']) && $_POST['admin'] == "save") {
			do_action("wpqa_user_register",$user_id);
			
			if (isset($_POST['user_best_answer'])) {
				$user_best_answer = sanitize_text_field($_POST['user_best_answer']);
				update_user_meta( $user_id, 'user_best_answer', $user_best_answer );
			}
		}

		$user_data = get_userdata($user_id);
		$default_group = $user_data->roles;
		if (is_array($default_group)) {
			$default_group = $default_group[0];
		}
		if (isset($_POST['role']) && $_POST['role'] != "" && $default_group != $_POST['role']) {
			$default_group = esc_html($_POST['role']);
		}

		if (is_super_admin($get_current_user_id) && ((isset($_POST['activate_user']) && $_POST['activate_user'] == "on") || (isset($_POST['approve_user']) && $_POST['approve_user'] == "on"))) {
			$default_group = wpqa_options("default_group");
			$default_group = (isset($default_group) && $default_group != ""?$default_group:"subscriber");
			$activate_user_meta = (isset($_POST['activate_user']) && $_POST['activate_user'] == "on"?"activate_user":"approve_user");
			$activate_user = get_user_meta($user_id,$activate_user_meta,true);
			if ($activate_user == "") {
				$send_text = wpqa_send_email(wpqa_options("email_approve_user"),$user_id);
				$last_message_email = wpqa_email_code($send_text);
				$email_title = wpqa_options("title_approve_user");
				$email_title = ($email_title != ""?$email_title:esc_html__("Confirm account","wpqa"));
				$email_template = wpqa_options("email_template");
				$mail_smtp = wpqa_options("mail_smtp");
				$email_template = ($mail_smtp == "on"?wpqa_options("mail_username"):$email_template);
				wpqa_sendEmail($email_template,get_bloginfo('name'),esc_html($user_data->user_email),esc_html($user_data->display_name),$email_title,$last_message_email);
				update_user_meta($user_id,$activate_user_meta,"on");
				if (isset($_POST['activate_user']) && $_POST['activate_user'] == "on") {
					delete_user_meta($user_id,"activation");
				}
			}
		}
		
		if (isset($_POST['from_admin']) && $_POST['from_admin'] == "yes") {
			$active_points = wpqa_options("active_points");
			if (is_super_admin($get_current_user_id) && $active_points == "on") {
				$add_remove_point = "";
				$the_points = "";
				$the_reason = "";
				if (isset($_POST['add_remove_point'])) {
					$add_remove_point = esc_html($_POST['add_remove_point']);
				}
				if (isset($_POST['the_points'])) {
					$the_points = (int)esc_html($_POST['the_points']);
				}
				if (isset($_POST['the_reason'])) {
					$the_reason = esc_html($_POST['the_reason']);
				}
				if ($the_points > 0) {
					if ($add_remove_point == "remove") {
						$add_remove_point_last = "-";
						$the_reason_last = "admin_remove_points";
					}else {
						$add_remove_point_last = "+";
						$the_reason_last = "admin_add_points";
					}
					$the_reason = (isset($the_reason) && $the_reason != ""?$the_reason:$the_reason_last);
					wpqa_add_points($user_id,$the_points,$add_remove_point_last,$the_reason_last);
					if ($get_current_user_id > 0 && $user_id > 0) {
						wpqa_notifications_activities($user_id,$get_current_user_id,"","","",$the_reason_last,"notifications");
					}
				}
			}

			$new_moderator_categories = array();
			$moderator_categories = (isset($_POST[prefix_author."moderator_categories"])?$_POST[prefix_author."moderator_categories"]:array());
			$moderator_categories = (is_array($moderator_categories) && !empty($moderator_categories)?$moderator_categories:array());
			foreach ($moderator_categories as $key => $value) {
				$key = str_replace("cat-","",$key);
				$new_moderator_categories[] = $key;
			}
			update_user_meta($user_id,prefix_author."moderator_categories",$new_moderator_categories);
			
			foreach ($options as $value) {
				if (!isset($value['unset']) && $value['type'] != 'heading' && $value['type'] != "heading-2" && $value['type'] != "heading-3" && $value['type'] != "html" && $value['type'] != 'info' && $value['type'] != 'content') {
					$val = '';
					if (isset($value['std'])) {
						$val = $value['std'];
					}
					
					$field_name = $value['id'];
					if (isset($_POST[$field_name])) {
						$val = $_POST[$field_name];
					}
					
					if (!isset($_POST[$field_name]) && $value['type'] == "checkbox") {
						$val = 0;
					}
					
					if ('' === $val || array() === $val) {
						delete_user_meta($user_id,$field_name);
					}else {
						update_user_meta($user_id,$field_name,$val);
					}
				}
			}
		}else {
			$post_array = array('country','city','phone','gender','age','facebook','twitter','youtube','vimeo','linkedin','instagram','pinterest','follow_email','show_point_favorite','question_schedules','received_email','received_message','unsubscribe_mails','new_payment_mail','send_message_mail','answer_on_your_question','answer_question_follow','profile_credential','categories_left_menu');
			$post_array = apply_filters("wpqa_edit_profile_post_array",$post_array);

			if (isset($_POST["categories_left_menu"]) && is_array($_POST["categories_left_menu"]) && !empty($_POST["categories_left_menu"])) {
				foreach ($_POST["categories_left_menu"] as $key => $value) {
					$_POST["categories_left_menu"][$key]["value"] = (int)$value["value"];
				}
			}

			foreach ($post_array as $field_name) {
				$val = '';
				
				if (isset($_POST[$field_name])) {
					$val = $_POST[$field_name];
				}
				
				if ('' === $val || array() === $val) {
					if ($field_name == "facebook" || $field_name == "twitter" || $field_name == "youtube" || $field_name == "vimeo" || $field_name == "linkedin" || $field_name == "instagram" || $field_name == "pinterest") {
						if ($points_social > 0) {
							delete_user_meta($user_id,"add_".$field_name);
							if (get_user_meta($user_id,$field_name,true) != "" && get_user_meta($user_id,"remove_".$field_name,true) != true) {
								wpqa_add_points($user_id,$points_social,"-","remove_".$field_name);
							}
							update_user_meta($user_id,"remove_".$field_name,true);
						}
					}
					delete_user_meta($user_id,$field_name);
				}else {
					update_user_meta($user_id,$field_name,$val);
					if ($field_name == "facebook" || $field_name == "twitter" || $field_name == "youtube" || $field_name == "vimeo" || $field_name == "linkedin" || $field_name == "instagram" || $field_name == "pinterest") {
						if ($points_social > 0) {
							delete_user_meta($user_id,"remove_".$field_name);
							if (get_user_meta($user_id,"add_".$field_name,true) != true) {
								wpqa_add_points($user_id,$points_social,"+","add_".$field_name);
							}
							update_user_meta($user_id,"add_".$field_name,true);
						}
					}
				}
			}
		}
		$nicename_nickname = (isset($_POST['nickname']) && $_POST['nickname'] != ""?sanitize_text_field($_POST['nickname']):sanitize_text_field($_POST['user_name']));
		edit_user($user_id);
		wp_update_user(array('ID' => $user_id,'user_nicename' => $nicename_nickname,'nickname' => $nicename_nickname,'role' => $default_group));
		if (isset($_POST["redirect_to"]) && $_POST["redirect_to"] != "") {
			wp_redirect(esc_url($_POST["redirect_to"]));
			die();
		}
	}
endif;
/* Exporter data */
add_filter('wp_privacy_personal_data_exporters','wpqa_register_exporter');
if (!function_exists('wpqa_register_exporter')) :
	function wpqa_register_exporter($exporters) {
		$exporters['my-plugin-data'] = array(
			'exporter_friendly_name' => esc_html__('Custom fields','wpqa'),
			'callback' => 'wpqa_exporter_data',
		);
	    return $exporters;
	}
endif;
if (!function_exists('wpqa_exporter_data')) :
	function wpqa_exporter_data($email_address,$page = 1) {
		$export_items = array();
		$user         = get_user_by('email',$email_address);
		$user_id      = $user->ID;

		$profile_credential = get_the_author_meta('profile_credential',$user_id);
		$twitter = get_the_author_meta('twitter',$user_id);
		$facebook = get_the_author_meta('facebook',$user_id);
		$youtube = get_the_author_meta('youtube',$user_id);
		$vimeo = get_the_author_meta('vimeo',$user_id);
		$linkedin = get_the_author_meta('linkedin',$user_id);
		$instagram = get_the_author_meta('instagram',$user_id);
		$pinterest = get_the_author_meta('pinterest',$user_id);
		$country = get_the_author_meta('country',$user_id);
		$city = get_the_author_meta('city',$user_id);
		$age = get_the_author_meta('age',$user_id);
		$phone = get_the_author_meta('phone',$user_id);
		$gender = get_the_author_meta('gender',$user_id);

		$data = array(
			array(
				'name'  => esc_html__('Profile credential','wpqa'),
				'value' => $profile_credential !== ''?esc_html($profile_credential):'',
			),
			array(
				'name'  => esc_html__('Twitter','wpqa'),
				'value' => $twitter !== ''?esc_url($twitter):'',
			),
			array(
				'name'  => esc_html__('Facebook','wpqa'),
				'value' => $facebook !== ''?esc_url($facebook):'',
			),
			array(
				'name'  => esc_html__('Youtube','wpqa'),
				'value' => $youtube !== ''?esc_url($youtube):'',
			),
			array(
				'name'  => esc_html__('Vimeo','wpqa'),
				'value' => $vimeo !== ''?esc_url($vimeo):'',
			),
			array(
				'name'  => esc_html__('Linkedin','wpqa'),
				'value' => $linkedin !== ''?esc_url($linkedin):'',
			),
			array(
				'name'  => esc_html__('Instagram','wpqa'),
				'value' => $instagram !== ''?esc_url($instagram):'',
			),
			array(
				'name'  => esc_html__('Pinterest','wpqa'),
				'value' => $pinterest !== ''?esc_url($pinterest):'',
			),
			array(
				'name'  => esc_html__('Country','wpqa'),
				'value' => $country !== ''?esc_html($country):'',
			),
			array(
				'name'  => esc_html__('City','wpqa'),
				'value' => $city !== ''?esc_html($city):'',
			),
			array(
				'name'  => esc_html__('Age','wpqa'),
				'value' => $age !== ''?esc_html($age):'',
			),
			array(
				'name'  => esc_html__('Phone','wpqa'),
				'value' => $phone !== ''?esc_html($phone):'',
			),
			array(
				'name'  => esc_html__('Gender','wpqa'),
				'value' => $gender !== ''?($gender == "male" || $gender == 1?esc_html__("Male","wpqa"):"").($gender == "female" || $gender == 2?esc_html__("Female","wpqa"):"").($gender == "other" || $gender == 3?esc_html__("Other","wpqa"):""):'',
			),
		);

		$export_items[] = array(
			'group_id'    => 'custom_fields',
			'group_label' => esc_html__('Custom fields','wpqa'),
			'item_id'     => $user_id,
			'data'        => $data,
		);

		return array(
			'data' => $export_items,
			'done' => true,
		);
	}
endif;?>