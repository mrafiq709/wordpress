<?php

/* @author    2codeThemes
*  @package   WPQA/functions
*  @version   1.0
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/* Send mail */
if (!function_exists('wpqa_sendEmail')) :
	function wpqa_sendEmail($fromEmail,$fromEmailName,$toEmail,$toEmailName,$subject,$message) {
		$mail = new PHPMailer\PHPMailer\PHPMailer();
		$mail->isSendmail();
		$mail_smtp = wpqa_options("mail_smtp");
		if ($mail_smtp == "on") {
			$mail_host = wpqa_options("mail_host");
			$mail_username = wpqa_options("mail_username");
			$mail_password = wpqa_options("mail_password");
			$mail_secure = wpqa_options("mail_secure");
			$mail_port = wpqa_options("mail_port");
			$disable_ssl = wpqa_options("disable_ssl");
			$smtp_auth = wpqa_options("smtp_auth");
			if ($mail_host != "" && $mail_port != "" && $mail_username != "" && $mail_password != "" && $mail_secure != "") {
				$mail->isSMTP();
				$mail->Host = $mail_host;
				if ($smtp_auth = "on") {
					$mail->SMTPAuth = true;
				}
				$mail->Username = $mail_username;
				$mail->Password = $mail_password;
				if ($mail_secure != "none") {
					$mail->SMTPSecure = $mail_secure;
				}
				$mail->Port = $mail_port;
			}
			if ($disable_ssl == "on") {
				$mail->SMTPOptions = array(
					'ssl' => array(
						'verify_peer' => false,
						'verify_peer_name' => false,
						'allow_self_signed' => true
					)
				);
			}
		}
		$mail->isHTML(true);
		$mail->setFrom(($mail_smtp == "on"?$mail_username:$fromEmail),htmlspecialchars_decode($fromEmailName));
		$mail->addReplyTo(($mail_smtp == "on"?$mail_username:$fromEmail),htmlspecialchars_decode($fromEmailName));
		$mail->addAddress($toEmail,$toEmailName);
		$mail->CharSet = 'UTF-8';
		$mail->Subject = $subject;
		$mail->msgHTML($message,__DIR__);
		if (!$mail->Send()) {
			add_filter('wp_mail_content_type','wpqa_set_content_type');
			$headers = 'From: '.$fromEmailName.' <'.($mail_smtp == "on"?$mail_username:$fromEmail).'>' . "\r\n";
			wp_mail($toEmail,$subject,$message,$headers);
		}
	}
endif;
if (!function_exists('wpqa_set_content_type')) :
	function wpqa_set_content_type(){
		return "text/html";
	}
endif;
/* Send mail template */
if (!function_exists('wpqa_send_email')) :
	function wpqa_send_email($content,$user_id = 0,$post_id = 0,$comment_id = 0,$reset_password = "",$confirm_link_email = "",$item_price = "",$item_currency = "",$payer_email = "",$first_name = "",$last_name = "",$item_transaction = "",$date = "",$time = "",$category = "",$custom = "",$sender_user_id = "",$recieved_user_id = 0,$invitation_link = "") {
		$content = str_ireplace('[%blogname%]', get_bloginfo( 'name' ), $content);
		$content = str_ireplace('[%site_url%]', esc_url(home_url('/')), $content);
		
		if ($user_id > 0) {
			$user = new WP_User($user_id);
			$content = str_ireplace('[%messages_url%]' , esc_url(wpqa_get_profile_permalink($user_id,"messages")), $content);
			$content = str_ireplace('[%user_login%]'   , $user->user_login, $content);
			$content = str_ireplace('[%user_name%]'    , $user->user_login, $content);
			$content = str_ireplace('[%user_nicename%]', ucfirst($user->user_nicename), $content);
			$content = str_ireplace('[%display_name%]' , ucfirst($user->display_name), $content);
			$content = str_ireplace('[%user_email%]'   , $user->user_email, $content);
			$content = str_ireplace('[%user_profile%]' , wpqa_profile_url($user->ID), $content);
			$content = str_ireplace('[%users_link%]'   , admin_url("users.php?role=wpqa_under_review"), $content);
		}
		
		if ($sender_user_id == "anonymous") {
			$content = str_ireplace('[%user_login_sender%]'   , esc_html__("Anonymous","wpqa"), $content);
			$content = str_ireplace('[%user_name_sender%]'    , esc_html__("Anonymous","wpqa"), $content);
			$content = str_ireplace('[%user_nicename_sender%]', esc_html__("Anonymous","wpqa"), $content);
			$content = str_ireplace('[%display_name_sender%]' , esc_html__("Anonymous","wpqa"), $content);
			$content = str_ireplace('[%user_email_sender%]'   , esc_html__("Anonymous","wpqa"), $content);
			$content = str_ireplace('[%user_profile_sender%]' , esc_url(home_url('/')), $content);
		}else if (is_numeric($sender_user_id) && $sender_user_id > 0) {
			$user = new WP_User($sender_user_id);
			$content = str_ireplace('[%user_login_sender%]'   , $user->user_login, $content);
			$content = str_ireplace('[%user_name_sender%]'    , $user->user_login, $content);
			$content = str_ireplace('[%user_nicename_sender%]', ucfirst($user->user_nicename), $content);
			$content = str_ireplace('[%display_name_sender%]' , ucfirst($user->display_name), $content);
			$content = str_ireplace('[%user_email_sender%]'   , $user->user_email, $content);
			$content = str_ireplace('[%user_profile_sender%]' , wpqa_profile_url($user->ID), $content);
		}else {
			if (is_object($sender_user_id)) {
				$content = str_ireplace('[%user_login_sender%]'   , $sender_user_id->comment_author, $content);
				$content = str_ireplace('[%user_name_sender%]'    , $sender_user_id->comment_author, $content);
				$content = str_ireplace('[%user_nicename_sender%]', ucfirst($sender_user_id->comment_author), $content);
				$content = str_ireplace('[%display_name_sender%]' , ucfirst($sender_user_id->comment_author), $content);
				$content = str_ireplace('[%user_email_sender%]'   , $sender_user_id->comment_author_email, $content);
				$content = str_ireplace('[%user_profile_sender%]' , esc_url(($sender_user_id->comment_author_url != ""?$sender_user_id->comment_author_url:home_url('/'))), $content);
			}
		}
		
		if ($recieved_user_id > 0) {
			$user = new WP_User($recieved_user_id);
			$content = str_ireplace('[%user_login%]'   , $user->user_login, $content);
			$content = str_ireplace('[%user_name%]'    , $user->user_login, $content);
			$content = str_ireplace('[%user_nicename%]', ucfirst($user->user_nicename), $content);
			$content = str_ireplace('[%display_name%]' , ucfirst($user->display_name), $content);
			$content = str_ireplace('[%user_email%]'   , $user->user_email, $content);
			$content = str_ireplace('[%user_profile%]' , wpqa_profile_url($user->ID), $content);
		}
		
		if (isset($reset_password) && $reset_password != "") {
			$content = str_ireplace('[%reset_password%]', $reset_password, $content);
		}
		if (isset($confirm_link_email) && $confirm_link_email != "") {
			$content = str_ireplace('[%confirm_link_email%]', $confirm_link_email, $content);
		}
		
		if ($comment_id > 0) {
			$get_comment = get_comment($comment_id);
			$content = str_ireplace('[%comment_link%]', admin_url("edit-comments.php?comment_status=moderated"), $content);
			$content = str_ireplace('[%answer_link%]' , get_permalink($post_id).'#li-comment-'.$comment_id, $content);
			$content = str_ireplace('[%answer_url%]'  , get_permalink($post_id).'#li-comment-'.$comment_id, $content);
			$content = str_ireplace('[%comment_url%]' , get_permalink($post_id).'#li-comment-'.$comment_id, $content);
			$content = str_ireplace('[%the_name%]'    , $get_comment->comment_author, $content);
		}
		
		if ($post_id > 0) {
			$post = get_post($post_id);
			$content = str_ireplace('[%messages_title%]', $post->post_title, $content);
			$content = str_ireplace('[%question_title%]', $post->post_title, $content);
			$content = str_ireplace('[%post_title%]'    , $post->post_title, $content);
			$content = str_ireplace('[%question_link%]' , ($post->post_status == 'publish'?get_permalink($post_id):admin_url('post.php?post='.$post_id.'&action=edit')), $content);
			$content = str_ireplace('[%post_link%]'     , ($post->post_status == 'publish'?get_permalink($post_id):admin_url('post.php?post='.$post_id.'&action=edit')), $content);
			if ($post->post_author > 0) {
				$get_the_author = get_user_by("id",$post->post_author);
				$the_author_post = $get_the_author->display_name;
			}else {
				$the_author_post = get_post_meta($post_id,($post->post_type == 'question'?'question_username':'post_username'),true);
				$the_author_post = ($the_author_post != ""?$the_author_post:esc_html__("Anonymous","wpqa"));
			}
			$content = str_ireplace('[%the_author_question%]', $the_author_post, $content);
			$content = str_ireplace('[%the_author_post%]'    , $the_author_post, $content);
		}
		
		if (isset($item_price) && $item_price != "") {
			$content = str_ireplace('[%item_price%]', $item_price, $content);
		}
		if (isset($item_currency) && $item_currency != "") {
			$content = str_ireplace('[%item_currency%]', $item_currency, $content);
		}
		if (isset($payer_email) && $payer_email != "") {
			$content = str_ireplace('[%payer_email%]', $payer_email, $content);
		}
		if (isset($first_name) && $first_name != "") {
			$content = str_ireplace('[%first_name%]', $first_name, $content);
		}
		if (isset($last_name) && $last_name != "") {
			$content = str_ireplace('[%last_name%]', $last_name, $content);
		}
		if (isset($item_transaction) && $item_transaction != "") {
			$content = str_ireplace('[%item_transaction%]', $item_transaction, $content);
		}
		if (isset($date) && $date != "") {
			$content = str_ireplace('[%date%]', $date, $content);
		}
		if (isset($time) && $time != "") {
			$content = str_ireplace('[%time%]', $time, $content);
		}
		if (isset($category) && $category != "") {
			$content = str_ireplace('[%category_link%]', admin_url('admin.php?page=wpqa_new_categories'), $content);
			$content = str_ireplace('[%category_name%]', $category, $content);
		}
		if (isset($invitation_link) && $invitation_link != "") {
			$content = str_ireplace('[%invitation_link%]', $invitation_link, $content);
		}
		if (isset($custom) && $custom != "") {
			$custom_content = apply_filters('wpqa_filter_send_email',false);
			$content = str_ireplace('[%custom_link%]', $custom_content, $content);
			$content = str_ireplace('[%custom_name%]', $custom, $content);
		}
		return stripslashes($content);
	}
endif;
/* Emails */
if (!function_exists('wpqa_email_code')) :
	function wpqa_email_code($content,$mail = "",$schedule = "",$user_id = "") {
		$active_footer_email = wpqa_options("active_footer_email");
		$social_footer_email = wpqa_options("social_footer_email");
		$copyrights_for_email = wpqa_options("copyrights_for_email");
		$logo_email_template = wpqa_image_url_id(wpqa_options("logo_email_template"));
		$custom_image_mail = wpqa_image_url_id(wpqa_options("custom_image_mail"));
		$background_email = wpqa_options("background_email");
		$background_email = ($background_email != ""?$background_email:"#272930");
		$social_td = '';
		if ($social_footer_email == "on") {
			$sort_social = wpqa_options("sort_social");
			$social = array(
				array("name" => "Facebook",   "value" => "facebook",   "icon" => "facebook"),
				array("name" => "Twitter",    "value" => "twitter",    "icon" => "twitter"),
				array("name" => "Linkedin",   "value" => "linkedin",   "icon" => "linkedin"),
				array("name" => "Dribbble",   "value" => "dribbble",   "icon" => "dribbble"),
				array("name" => "Youtube",    "value" => "youtube",    "icon" => "play"),
				array("name" => "Vimeo",      "value" => "vimeo",      "icon" => "vimeo"),
				array("name" => "Skype",      "value" => "skype",      "icon" => "skype"),
				array("name" => "WhatsApp",   "value" => "whatsapp",   "icon" => "whatsapp"),
				array("name" => "Flickr",     "value" => "flickr",     "icon" => "flickr"),
				array("name" => "Soundcloud", "value" => "soundcloud", "icon" => "soundcloud"),
				array("name" => "Instagram",  "value" => "instagram",  "icon" => "instagrem"),
				array("name" => "Pinterest",  "value" => "pinterest",  "icon" => "pinterest")
			);
			if (isset($sort_social) && is_array($sort_social)) {
				$k = 0;
				foreach ($sort_social as $key_r => $value_r) {$k++;
					if (isset($sort_social[$key_r]["value"])) {
						$sort_social_value = $sort_social[$key_r]["value"];
						$social_icon_h = wpqa_options($sort_social_value."_icon_h");
						if ($sort_social_value != "rss" && $social_icon_h != "") {
							$social_td .= '<td width="32" align="center" valign="top" style="border-collapse:collapse;font-family:&quot;Helvetica Neue&quot;,Helvetica,Arial,sans-serif;font-size:14px;color:#7a7a7a;padding:0 2px">
								<a href="'.($sort_social_value == "skype"?"skype:":"").($sort_social_value == "whatsapp"?"whatsapp://send?abid=":"").($sort_social_value != "skype" && $sort_social_value != "whatsapp"?esc_url($social_icon_h):$social_icon_h).($sort_social_value == "skype"?"?call":"").($sort_social_value == "whatsapp"?"&text=".esc_html__("Hello","wpqa"):"").'" style="display:block;border:none" target="_blank">
									<img alt="'.$value_r["name"].'" width="32" height="32" src="'.get_template_directory_uri().'/images/social/'.$value_r["value"].'.png" style="line-height:100%;outline:none;text-decoration:none;border:none">
								</a>
							</td>';
						}
					}
				}
			}
		}

		if ($schedule != "") {
			$schedule_content = wpqa_options("schedule_content");
			if ($schedule == "daily") {
				$specific_date = "24 hours";
			}else if ($schedule == "weekly") {
				$specific_date = "1 week";
			}else if ($schedule == "monthly") {
				$specific_date = "1 month";
			}
			$recent_questions_query = new WP_Query(array('author' => -$user_id,'date_query' => array(array('after' => $specific_date.' ago')),'post_type' => 'question','ignore_sticky_posts' => 1,'cache_results' => false,'no_found_rows' => true,'posts_per_page' => 10));
			if ($recent_questions_query->have_posts()) :
		    	while ( $recent_questions_query->have_posts() ) : $recent_questions_query->the_post();
			        $recent_questions .= '<tr>
			            <td>
			                <p style="font-size:16px;color:#222222;line-height:120%;margin-top:0;margin-bottom:15px;"><a style="text-decoration:none;color:#26333b" href="'.get_permalink($post->ID).'">'.get_the_title($post->ID).'</a></p>
			            </td>
			        </tr>';
			       endwhile;
			else :
				return 'no_question';
			endif;
			wp_reset_query();
		}
		return '<div style="word-wrap:break-word;'.(is_rtl()?"direction:rtl;":"").'">
			<div>
				<div>
					<div style="margin:0;background-color:#f4f3f4;font-family:Helvetica,Arial,sans-serif;font-size:12px" text="#444" bgcolor="#F4F3F4" link="#21759B" alink="#21759B" vlink="#21759B" marginheight="0" marginwidth="0">
						<table border="0" width="100%" cellspacing="0" cellpadding="0" bgcolor="#F4F3F4">
							<tbody>
								<tr>
									<td style="padding:15px">
										<center>
											<table width="550" cellspacing="0" cellpadding="0" align="center" bgcolor="#FFF">
												<tbody>
													<tr>
														<td align="center">
															<div style="border:solid 1px #d9d9d9;padding-right:30px;padding-left:30px">
																<table style="line-height:1.6;font-size:12px;font-family:Helvetica,Arial,sans-serif;border:solid 1px #FFF;color:#444;" border="0" width="100%" cellspacing="0" cellpadding="0" bgcolor="#ffffff">
																	<tbody>
																		<tr>
																			<td style="color:#FFF" colspan="2" valign="bottom" height="30"></td>
																		</tr>
																		<tr>
																			<td style="line-height:32px;padding:30px 30px 20px;text-align:center;background-color:'.$background_email.';" valign="baseline"><a href="'.esc_url(home_url('/')).'" target="_blank">'.($logo_email_template != ''?'<img alt="'.esc_attr(get_option('blogname')).'" src="'.$logo_email_template.'">':'').'</a></td>
																		</tr>
																		'.($mail == 'email_custom_mail'?'<tr>
																			<td style="line-height:32px;padding:30px 30px 20px;text-align:center;" valign="baseline"><a href="'.esc_url(home_url('/')).'" target="_blank">'.($custom_image_mail != ''?'<img alt="'.esc_attr(get_option('blogname')).'" src="'.$custom_image_mail.'">':'').'</a></td>
																		</tr>':'').'
																		<tr>
																			<td colspan="2">
																				<div style="padding-top:10px;color:#444;">'.$content.'</div>
																			</td>
																		</tr>
																		'.(isset($recent_questions) && $recent_questions != ''?$recent_questions:'').'
																		'.($schedule != ''?(isset($schedule_content) && $schedule_content != ''?$schedule_content:''):'').'
																	</tbody>
																</table>
															</div>
														</td>
													</tr>
												</tbody>
											</table>
											'.($active_footer_email == 'on'?'<table width="594" border="0" align="center" cellpadding="0" cellspacing="0">
												<tbody>
													<tr>
														<td align="center" valign="top" style="border-collapse:collapse;font-family:&quot;Helvetica Neue&quot;,Helvetica,Arial,sans-serif;font-size:14px;color:#7a7a7a;padding:15px 0 10px">
															<table width="98" border="0" cellspacing="0" cellpadding="0">
																<tbody>
																	<tr>'.(isset($social_td) && $social_td != ''?$social_td:'').'</tr>
																</tbody>
															</table>
														</td>
													</tr>
													'.($copyrights_for_email != ''?'<tr>
														<td align="center" valign="top" style="border-collapse:collapse;font-family:&quot;Helvetica Neue&quot;,Helvetica,Arial,sans-serif;font-size:14px;color:#999;padding-top:10px">
															'.$copyrights_for_email.'
														</td>
													</tr>':'').'
												</tbody>
											</table>':'').'
										</center>
									</td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>';
	}
endif;
/* Send admin notification */
if (!function_exists('wpqa_send_admin_notification')) :
	function wpqa_send_admin_notification($post_id,$post_title) {
		$blogname = get_option('blogname');
		$email = get_option('admin_email');
		$headers = "MIME-Version: 1.0\r\n" . "From: ".$blogname." "."<".$email.">\n" . "Content-Type: text/HTML; charset=\"" . get_option('blog_charset') . "\"\r\n";
		$message = esc_html__('Hello there,','wpqa').'<br/><br/>'. 
		__('A new post has been submitted in ','wpqa').$blogname.' site.'.esc_html__(' Please find details below:','wpqa').'<br/><br/>'.
		
		'Post title: '.$post_title.'<br/><br/>';
		$post_author_name = get_post_meta($post_id,'ap_author_name',true);
		$post_author_email = get_post_meta($post_id,'ap_author_email',true);
		$post_author_url = get_post_meta($post_id,'ap_author_url',true);
		if ($post_author_name != ''){
			$message .= 'Post Author Name: '.$post_author_name.'<br/><br/>';
		}
		if ($post_author_email != ''){
			$message .= 'Post Author Email: '.$post_author_email.'<br/><br/>';
		}
		if ($post_author_url != ''){
			$message .= 'Post Author URL: '.$post_author_url.'<br/><br/>';
		}
		
		$message .= '____<br/><br/>
		'.esc_html__('To take action (approve/reject)- please go here:','wpqa').'<br/>'
		.admin_url().'post.php?post='.$post_id.'&action=edit <br/><br/>
		
		'.esc_html__('Thank You','wpqa');
		$subject = esc_html__('New Post Submission','wpqa');
		wp_mail($email,$subject,$message,$headers);
	}
endif;
/* Schedule mail */
function wpqa_schedule_mails($schedule) {
	$question_schedules = wpqa_options("question_schedules");
	if ($question_schedules == "on") {
		$question_schedules_groups = wpqa_options("question_schedules_groups");
		$email_title = wpqa_options("title_question_schedules");
		$email_title = ($email_title != ""?$email_title:esc_html__("Recent questions","wpqa"));
		$email_template = wpqa_options("email_template");
		$mail_smtp = wpqa_options("mail_smtp");
		$email_template = ($mail_smtp == "on"?wpqa_options("mail_username"):$email_template);
		$users = get_users(array("meta_query" => array("relation" => "AND",array("key" => "question_schedules","compare" => "=","value" => "on"),array('relation' => 'OR',array("key" => "unsubscribe_mails","compare" => "NOT EXISTS"),array("key" => "unsubscribe_mails","compare" => "!=","value" => "on"))),"role__not_in" => array("wpqa_under_review","activation","ban_group"),"role__in" => (isset($question_schedules_groups) && is_array($question_schedules_groups)?$question_schedules_groups:array()),"fields" => array("ID","user_email","display_name")));
		if (isset($users) && is_array($users) && !empty($users)) {
			foreach ($users as $key => $value) {
				$user_id = $value->ID;
				$send_text = wpqa_send_email(wpqa_options("email_question_schedules"),"","","","","","","","","","","","","","","","",$user_id);
				$last_message_email = wpqa_email_code($send_text,"email_question_schedules",$schedule,$user_id);
				if ($last_message_email != "no_question") {
					wpqa_sendEmail($email_template,get_bloginfo('name'),esc_html($value->user_email),esc_html($value->display_name),$email_title,$last_message_email);
				}
			}
		}
	}
}
/* Cron schedules */
add_filter("cron_schedules","wpqa_cron_schedules");
if (!function_exists('wpqa_cron_schedules')) :
	function wpqa_cron_schedules($schedules) {
		$schedules['weekly'] = array(
			'interval' => WEEK_IN_SECONDS,
			'display'  => esc_html__('Once Weekly','wpqa'),
		);
		$schedules['monthly'] = array(
			'interval' => MONTH_IN_SECONDS,
			'display'  => esc_html__('Once Monthly','wpqa'),
		);
		return $schedules;
	}
endif;
/* Daily mails */
if (!wp_next_scheduled('wpqa_scheduled_mails_daily')) {
	wp_schedule_event(time(),'daily','wpqa_scheduled_mails_daily');
}
add_action('wpqa_scheduled_mails_daily','wpqa_scheduled_mails_daily');
function wpqa_scheduled_mails_daily() {
	$question_schedules = wpqa_options("question_schedules");
	$question_schedules_time = wpqa_options("question_schedules_time");
	if ($question_schedules == "on" && is_array($question_schedules_time) && in_array("daily",$question_schedules_time)) {
		wpqa_schedule_mails("daily");
	}
}
/* Weekly mails */
if (!wp_next_scheduled('wpqa_scheduled_mails_weekly')) {
	wp_schedule_event(time(),'weekly','wpqa_scheduled_mails_weekly');
}
add_action('wpqa_scheduled_mails_weekly','wpqa_scheduled_mails_weekly');
function wpqa_scheduled_mails_weekly() {
	$question_schedules = wpqa_options("question_schedules");
	$question_schedules_time = wpqa_options("question_schedules_time");
	if ($question_schedules == "on" && is_array($question_schedules_time) && in_array("weekly",$question_schedules_time)) {
		wpqa_schedule_mails("weekly");
	}
}
/* Monthly mails */
if (!wp_next_scheduled('wpqa_scheduled_mails_monthly')) {
	wp_schedule_event(time(),'monthly','wpqa_scheduled_mails_monthly');
}
add_action('wpqa_scheduled_mails_monthly','wpqa_scheduled_mails_monthly');
function wpqa_scheduled_mails_monthly() {
	$question_schedules = wpqa_options("question_schedules");
	$question_schedules_time = wpqa_options("question_schedules_time");
	if ($question_schedules == "on" && is_array($question_schedules_time) && in_array("monthly",$question_schedules_time)) {
		wpqa_schedule_mails("monthly");
	}
}?>