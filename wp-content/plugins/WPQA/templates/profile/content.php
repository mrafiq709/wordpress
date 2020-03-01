<?php

/* @author    2codeThemes
*  @package   WPQA/templates/profile
*  @version   1.0
*/

if ( ! defined( 'ABSPATH' ) ) {
   exit; // Exit if accessed directly
}

$active_points        = wpqa_options("active_points");
$active_moderators    = wpqa_options("active_moderators");
$active_notifications = wpqa_options("active_notifications");
$active_activity_log  = wpqa_options("active_activity_log");
$active_message       = wpqa_options("active_message");
$active_referral      = wpqa_options("active_referral");
$user_profile_pages   = wpqa_options("user_profile_pages");
$ask_me               = wpqa_options("ask_me");
$rows_per_page        = get_option("posts_per_page");
$paged                = (get_query_var("paged") != ""?(int)get_query_var("paged"):(get_query_var("page") != ""?(int)get_query_var("page"):1));
$current              = max(1,$paged);
$show_custom_error    = true;
$last_one             = "";
$wpqa_user_title      = wpqa_user_title();
$is_super_admin       = is_super_admin($user_id);
$first_one            = (isset($first_one) && $first_one != ""?$first_one:"");
$first_one_filter     = apply_filters("wpqa_filter_profile_user_title",false);

if ($wpqa_user_title == "" && isset($user_profile_pages[$first_one]["value"]) && $user_profile_pages[$first_one]["value"] == $first_one) {
	$last_one = $first_one;
}else if ($wpqa_user_title != "" && ($first_one_filter == true || isset($user_profile_pages[$wpqa_user_title]["value"]) && $user_profile_pages[$wpqa_user_title]["value"] == $wpqa_user_title)) {
	$last_one = $wpqa_user_title;
}else if ($first_one == "") {
	$no_pages = true;
}
$profile_last_one = apply_filters("wpqa_content_profile_last_one",false,$last_one);
if (($profile_last_one == true || $last_one == "questions" || $last_one == "posts" || ($last_one == "favorites" && ($show_point_favorite == "on" || wpqa_is_user_owner())) || ($last_one == "asked" && $ask_question_to_users == "on") || ($last_one == "asked-questions" && $ask_question_to_users == "on" && wpqa_is_user_owner()) || ($pay_ask == "on" && $last_one == "paid-questions") || ($last_one == "followed" && ($show_point_favorite == "on" || wpqa_is_user_owner())) || $last_one == "polls" || $last_one == "followers-questions" || $last_one == "followers-posts")) {
	if ($profile_last_one == true || $last_one == "questions" || ($last_one == "favorites" && ($show_point_favorite == "on" || wpqa_is_user_owner())) || ($last_one == "followed" && ($show_point_favorite == "on" || wpqa_is_user_owner())) || $last_one == "polls" || $last_one == "followers-questions" || ($last_one == "asked" && $ask_question_to_users == "on") || ($last_one == "asked-questions" && $ask_question_to_users == "on" && wpqa_is_user_owner()) || ($pay_ask == "on" && $last_one == "paid-questions")) {
		$its_question = "question";
	}
	$is_questions_sticky = false;
	include locate_template("theme-parts/loop.php");
}else if (wpqa_is_user_followers() || wpqa_is_user_following()) {
	include wpqa_get_template("follow.php","profile/");
}else if (wpqa_is_user_referrals() && $active_referral == "on" && wpqa_is_user_owner()) {
	include wpqa_get_template("referrals.php","profile/");
}else if (wpqa_is_user_messages() && $active_message == "on" && wpqa_is_user_owner()) {
	include wpqa_get_template("messages.php","profile/");
}else if (((wpqa_is_user_points() && $active_points == "on" && ($show_point_favorite == "on" || wpqa_is_user_owner())) || (wpqa_is_pending_questions() && ($is_super_admin || $active_moderators == "on") && wpqa_is_user_owner()) || (wpqa_is_user_notifications() && $active_notifications == "on" && wpqa_is_user_owner()) || (wpqa_is_user_activities() && $active_activity_log == "on" && wpqa_is_user_owner()))) {
	if (wpqa_is_pending_questions() && ($is_super_admin || $active_moderators == "on") && wpqa_is_user_owner()) {
		$user_moderator = get_user_meta($user_id,prefix_author."user_moderator",true);
		if ($is_super_admin || $user_moderator == "on") {
			$moderator_categories = get_user_meta($user_id,prefix_author."moderator_categories",true);
			if ($is_super_admin || is_array($moderator_categories) && !empty($moderator_categories)) {
				$its_question = "question";
				include locate_template("theme-parts/loop.php");
			}else if (!$is_super_admin && (!is_array($moderator_categories) || (is_array($moderator_categories) && empty($moderator_categories)))) {
				echo '<div class="alert-message warning"><i class="icon-flag"></i><p>'.esc_html__("Sorry, You don't moderator yet.","wpqa").'</p></div>';
			}
		}else {
			echo '<div class="alert-message warning"><i class="icon-flag"></i><p>'.esc_html__("Sorry, is this private page.","wpqa").'</p></div>';
		}
	}else {
		if (wpqa_is_user_points() && $active_points == "on") {
			$whats_type = "point";
			if ($ask_me == "on") {
				$user_login = get_userdata($wpqa_user_id);
				$old_points = get_user_meta($wpqa_user_id,$user_login->user_login."_points",true);
				if (isset($old_points) && $old_points != "") {
					update_user_meta($wpqa_user_id,$wpqa_user_id."_".$whats_type."s",$old_points);
					delete_user_meta($wpqa_user_id,$user_login->user_login."_points");
				}
			}
			wpqa_get_user_stats($wpqa_user_id,wpqa_options('user_stats'),$active_points,$show_point_favorite);
		}else if (wpqa_is_user_notifications()) {
			$whats_type = "notification";
		}else {
			$whats_type = "activitie";
		}
		
		$_whats_types = get_user_meta($wpqa_user_id,$wpqa_user_id."_".$whats_type."s",true);
		
		if (isset($_whats_types) && $_whats_types > 0) {
			echo '<div id="section-'.$wpqa_user_title.'" class="user-notifications user-profile-area section-page-div"><div><ul>';
				$pagination_args = array(
					'format'    => (get_option('permalink_structure')?'page/%#%/':'?page=%#%'),
					'total'     => ceil($_whats_types/$rows_per_page),
					'current'   => $current,
					'show_all'  => false,
					'prev_text' => '<i class="icon-left-open"></i>',
					'next_text' => '<i class="icon-right-open"></i>',
				);
				
				$start = ($current - 1) * $rows_per_page;
				$end = $start + $rows_per_page;
				$end = ($_whats_types < $end) ? $_whats_types : $end;
				for ($i = $_whats_types-$start; $i > $_whats_types-$end; $i--) {
					if (wpqa_is_user_points() && $active_points == "on" && $ask_me == "on") {
						$points_one = get_user_meta($wpqa_user_id,$user_login->user_login."_points_".$i,true);
						if (isset($points_one) && !empty($points_one)) {
							update_user_meta($wpqa_user_id,$wpqa_user_id."_points_".$i,$points_one);
							delete_user_meta($wpqa_user_id,$user_login->user_login."_points_".$i);
						}
					}
					$whats_type_result = get_user_meta($wpqa_user_id,$wpqa_user_id."_".$whats_type."s_".$i,true);?>
					<li>
						<?php 
						if (wpqa_is_user_points() && $active_points == "on") {
							if (isset($whats_type_result[3]) && $whats_type_result[3] == "+") {
								echo "<i class='icon-thumbs-up'></i>";
							}else {
								echo "<i class='icon-thumbs-down'></i>";
							}
							echo "<div>
								<span class='point-span'>".$whats_type_result[3].$whats_type_result[2]."</span>";
								if (!empty($whats_type_result[5])) {
									$get_the_permalink = get_the_permalink($whats_type_result[5]);
									$get_post_status = get_post_status($whats_type_result[5]);
								}
								if (!empty($whats_type_result[6])) {
									$get_comment = get_comment($whats_type_result[6]);
								}
								if (!empty($whats_type_result["user_id"])) {
									$get_user_url = wpqa_profile_url($whats_type_result["user_id"]);
								}
								
								if (!empty($whats_type_result[5]) && !empty($whats_type_result[6]) && $get_post_status != "trash" && isset($get_comment) && $get_comment->comment_approved != "spam" && $get_comment->comment_approved != "trash") {?>
									<a href="<?php echo get_the_permalink($whats_type_result[5]).(isset($whats_type_result[6])?"#comment-".$whats_type_result[6]:"")?>">
								<?php }else if (!empty($whats_type_result[5]) && (empty($whats_type_result[6])) && $get_post_status != "trash" && isset($get_the_permalink) && $get_the_permalink != "") {?>
									<a href="<?php echo get_the_permalink($whats_type_result[5])?>">
								<?php }else if (!empty($whats_type_result["user_id"]) && isset($get_user_url) && $get_user_url != "") {?>
									<a href="<?php echo esc_url($get_user_url)?>">
								<?php }
									$result_text = apply_filters("wpqa_points_text",false,$whats_type_result[4]);
									if ($result_text != "") {
										$output .= $result_text;
									}else if ($whats_type_result[4] != "add_facebook" && $whats_type_result[4] != "add_twitter" && $whats_type_result[4] != "add_youtube" && $whats_type_result[4] != "add_vimeo" && $whats_type_result[4] != "add_linkedin" && $whats_type_result[4] != "add_instagram" && $whats_type_result[4] != "add_pinterest" && $whats_type_result[4] != "remove_facebook" && $whats_type_result[4] != "remove_twitter" && $whats_type_result[4] != "remove_youtube" && $whats_type_result[4] != "remove_vimeo" && $whats_type_result[4] != "remove_linkedin" && $whats_type_result[4] != "remove_instagram" && $whats_type_result[4] != "remove_pinterest" && $whats_type_result[4] != "sticky_points" && $whats_type_result[4] != "ask_points" && $whats_type_result[4] != "buy_points" && $whats_type_result[4] != "voting_question" && $whats_type_result[4] != "voting_answer" && $whats_type_result[4] != "rating_question" && $whats_type_result[4] != "rating_answer" && $whats_type_result[4] != "user_unfollow" && $whats_type_result[4] != "user_follow" && $whats_type_result[4] != "bump_question" && $whats_type_result[4] != "select_best_answer" && $whats_type_result[4] != "cancel_best_answer" && $whats_type_result[4] != "answer_question" && $whats_type_result[4] != "add_question" && $whats_type_result[4] != "add_post" && $whats_type_result[4] != "question_point" && $whats_type_result[4] != "gift_site" && $whats_type_result[4] != "points_referral" && $whats_type_result[4] != "admin_add_points" && $whats_type_result[4] != "admin_remove_points" && $whats_type_result[4] != "point_back" && $whats_type_result[4] != "point_removed" && $whats_type_result[4] != "delete_answer" && $whats_type_result[4] != "delete_best_answer" && $whats_type_result[4] != "delete_follow_user" && $whats_type_result[4] != "delete_question") {
										echo ($whats_type_result[4]);
									}else if ($whats_type_result[4] == "add_facebook") {
										esc_html_e("You have added your Facebook link.","wpqa");
									}else if ($whats_type_result[4] == "add_twitter") {
										esc_html_e("You have added your Twitter link.","wpqa");
									}else if ($whats_type_result[4] == "add_youtube") {
										esc_html_e("You have added your Youtube link.","wpqa");
									}else if ($whats_type_result[4] == "add_vimeo") {
										esc_html_e("You have added your Vimeo link.","wpqa");
									}else if ($whats_type_result[4] == "add_linkedin") {
										esc_html_e("You have added your Linkedin link.","wpqa");
									}else if ($whats_type_result[4] == "add_instagram") {
										esc_html_e("You have added your Instagram link.","wpqa");
									}else if ($whats_type_result[4] == "add_pinterest") {
										esc_html_e("You have added your Pinterest link.","wpqa");
									}else if ($whats_type_result[4] == "remove_facebook") {
										esc_html_e("You have removed your Facebook link.","wpqa");
									}else if ($whats_type_result[4] == "remove_twitter") {
										esc_html_e("You have removed your Twitter link.","wpqa");
									}else if ($whats_type_result[4] == "remove_youtube") {
										esc_html_e("You have removed your Youtube link.","wpqa");
									}else if ($whats_type_result[4] == "remove_vimeo") {
										esc_html_e("You have removed your Vimeo link.","wpqa");
									}else if ($whats_type_result[4] == "remove_linkedin") {
										esc_html_e("You have removed your Linkedin link.","wpqa");
									}else if ($whats_type_result[4] == "remove_instagram") {
										esc_html_e("You have removed your Instagram link.","wpqa");
									}else if ($whats_type_result[4] == "remove_pinterest") {
										esc_html_e("You have removed your Pinterest link.","wpqa");
									}else if ($whats_type_result[4] == "sticky_points") {
										esc_html_e("You have stickied your question by points.","wpqa");
									}else if ($whats_type_result[4] == "ask_points") {
										esc_html_e("You have bought to ask question by points.","wpqa");
									}else if ($whats_type_result[4] == "buy_points") {
										esc_html_e("You have bought a new points.","wpqa");
									}else if ($whats_type_result[4] == "voting_question" || $whats_type_result[4] == "rating_question") {
										esc_html_e("Voted your question.","wpqa");
									}else if ($whats_type_result[4] == "voting_answer" || $whats_type_result[4] == "rating_answer") {
										esc_html_e("Voted your answer.","wpqa");
									}else if ($whats_type_result[4] == "user_follow") {
										esc_html_e("User followed You.","wpqa");
									}else if ($whats_type_result[4] == "user_unfollow") {
										esc_html_e("User unfollowed You.","wpqa");
									}else if ($whats_type_result[4] == "bump_question") {
										esc_html_e("Discount points to bump question.","wpqa");
									}else if ($whats_type_result[4] == "select_best_answer") {
										esc_html_e("Chosen your answer best answer.","wpqa");
									}else if ($whats_type_result[4] == "cancel_best_answer") {
										esc_html_e("Canceled your answer best answer.","wpqa");
									}else if ($whats_type_result[4] == "answer_question") {
										esc_html_e("You have answered the question.","wpqa");
									}else if ($whats_type_result[4] == "add_question") {
										esc_html_e("Added a new question.","wpqa");
									}else if ($whats_type_result[4] == "delete_question") {
										esc_html_e("Deleted your question.","wpqa");
									}else if ($whats_type_result[4] == "add_post") {
										esc_html_e("Added a new post.","wpqa");
									}else if ($whats_type_result[4] == "gift_site") {
										esc_html_e("Gift of the site.","wpqa");
									}else if ($whats_type_result[4] == "points_referral") {
										esc_html_e("Referral a new user.","wpqa");
									}else if ($whats_type_result[4] == "question_point") {
										esc_html_e("You have charged points for ask a question.","wpqa");
									}else if ($whats_type_result[4] == "admin_add_points") {
										esc_html_e("The administrator added points for you.","wpqa");
									}else if ($whats_type_result[4] == "admin_remove_points") {
										esc_html_e("The administrator removed points from you.","wpqa");
									}else if ($whats_type_result[4] == "point_back") {
										esc_html_e("Your point back because the best answer was selected.","wpqa");
									}else if ($whats_type_result[4] == "point_removed") {
										esc_html_e("Your point removed because the best answer was removed.","wpqa");
									}else if ($whats_type_result[4] == "delete_answer") {
										esc_html_e("Your answer was removed.","wpqa");
									}else if ($whats_type_result[4] == "delete_best_answer") {
										esc_html_e("Deleted your best answer.","wpqa");
									}else if ($whats_type_result[4] == "delete_follow_user") {
										esc_html_e("Deleted your following user.","wpqa");
									}
								
								if ((!empty($whats_type_result[5]) && !empty($whats_type_result[6]) && $get_post_status != "trash" && isset($get_comment) && $get_comment->comment_approved != "spam" && $get_comment->comment_approved != "trash") || (!empty($whats_type_result[5]) && (empty($whats_type_result[6])) && $get_post_status != "trash" && isset($get_the_permalink) && $get_the_permalink != "") || (!empty($whats_type_result["user_id"]) && isset($get_user_url) && $get_user_url != "")) {?>
									</a>
								<?php }
						}else if (wpqa_is_user_notifications()) {
							update_user_meta($wpqa_user_id,$wpqa_user_id.'_new_notifications',0);
							echo wpqa_show_notifications($whats_type_result);
						}else {
							echo wpqa_show_activities($whats_type_result);
						}
						$time_format = wpqa_options("time_format");
						$time_format = ($time_format?$time_format:get_option("time_format"));
						$date_format = wpqa_options("date_format");
						$date_format = ($date_format?$date_format:get_option("date_format"));?>
						<span class='notifications-date'><?php echo (isset($whats_type_result['time'])?sprintf(esc_html__('%1$s at %2$s','wpqa'),mysql2date($date_format,date($date_format,$whats_type_result["time"])),mysql2date($time_format,date($time_format,$whats_type_result["time"]))):$whats_type_result[0]."&nbsp;&nbsp;-&nbsp;&nbsp;".$whats_type_result[1])?></span>
						</div>
					</li>
				<?php }
			echo '</div></ul></div>';
			if (isset($_whats_types) && $_whats_types > 0 && $pagination_args["total"] > 1) {?>
				<div class="main-pagination"><div class='pagination'><?php echo (paginate_links($pagination_args) != ""?paginate_links($pagination_args):"")?></div></div>
			<?php }
		}else {
			echo '<div class="alert-message warning"><i class="icon-flag"></i><p>'.(wpqa_is_user_points()?esc_html__("There are no points yet.","wpqa"):(wpqa_is_user_notifications()?esc_html__("There are no notifications yet.","wpqa"):esc_html__("There are no activities yet.","wpqa"))).'</p></div>';
		}
	}
}else if ($last_one == "answers" || $last_one == "best-answers" || ($last_one == "comments") || $last_one == "followers-answers" || $last_one == "followers-comments") {
	if ($last_one == "answers" || $last_one == "comments" || $last_one == "followers-answers" || $last_one == "followers-comments") {
		if ($last_one == "followers-answers" || $last_one == "followers-comments") {
			$following_me = get_user_meta($wpqa_user_id,"following_me",true);
		}
		$comments_all = get_comments(array(($last_one == "followers-answers" || $last_one == "followers-comments"?"author__in":"user_id") => ($last_one == "followers-answers" || $last_one == "followers-comments"?$following_me:$wpqa_user_id),"status" => "approve",'post_type' => ($last_one == "answers" || $last_one == "followers-answers"?"question":"post"),"meta_query" => array(array("key" => "answer_question_user","compare" => "NOT EXISTS"))));
		if (($last_one == "followers-answers" || $last_one == "followers-comments") && empty($following_me)) {
			$comments_all = array();
		}
	}else {
		$comments_all = get_comments(array('user_id' => $wpqa_user_id,"status" => "approve",'post_type' => 'question',"meta_query" => array('relation' => 'AND',array("key" => "best_answer_comment","compare" => "=","value" => "best_answer_comment"),array("key" => "answer_question_user","compare" => "NOT EXISTS"))));
	}
	if (!empty($comments_all)) {
		$k_ad = -1;
		$pagination_args = array(
			'format'    => (get_option('permalink_structure')?'page/%#%/':'?page=%#%'),
			'total'     => ceil(sizeof($comments_all)/$rows_per_page),
			'current'   => $current,
			'show_all'  => false,
			'prev_text' => '<i class="icon-left-open"></i>',
			'next_text' => '<i class="icon-right-open"></i>',
		);
		
		$start = ($current - 1) * $rows_per_page;
		$end = $start + $rows_per_page;
		?>
		<div<?php echo ($last_one == "answers" || $last_one == "best-answers" || $last_one == "comments" || $last_one == "followers-answers" || $last_one == "followers-comments"?" id='section-".$wpqa_user_title."'":"")?> class="page-content commentslist section-page-div">
			<ol class="commentlist clearfix">
				<?php $end = (sizeof($comments_all) < $end) ? sizeof($comments_all) : $end;
				for ($k = $start;$k < $end ;++$k ) {$k_ad++;
					if ($last_one == "answers" || $last_one == "comments" || $last_one == "followers-answers" || $last_one == "followers-comments") {
						$comment_item = $comments_all[$k];
					}else {
						$comment_item = get_comment($comments_all[$k]);
					}
					if ($last_one == "answers" || $last_one == "followers-answers") {
						$yes_private = wpqa_private($comment_item->comment_post_ID,get_post($comment_item->comment_post_ID)->post_author,get_current_user_id());
					}else {
						$yes_private = 1;
					}
					if ($yes_private == 1) {
							$comment_id = esc_attr($comment_item->comment_ID);
							wpqa_comment($comment_item,"","",($last_one == "answers" || $last_one == "best-answers" || $last_one == "followers-answers"?"answer":"comment"),wpqa_is_user_owner(),$k_ad,($last_one == "best-answers"?"not show":""));?>
						</li>
					<?php }else {?>
						<li class="comment">
							<div class="comment-body clearfix">
								<?php echo '<div class="alert-message warning"><i class="icon-flag"></i><p>'.esc_html__("Sorry it a private answer.","wpqa").'</p></div>';?>
							</div>
						</li>
					<?php }
				}?>
			</ol>
		</div>
	<?php }else {
		echo '<div class="alert-message warning"><i class="icon-flag"></i><p>'.($last_one == "best-answers"?esc_html__("There are no best answers yet.","wpqa"):($last_one == "answers" || $last_one == "followers-answers"?esc_html__("There are no answers yet","wpqa"):esc_html__("There are no comments yet","wpqa"))).'</p></div>';
	}
	if ($comments_all && $pagination_args["total"] > 1) {?>
		<div class="main-pagination"><div class='pagination'><?php echo (paginate_links($pagination_args) != ""?paginate_links($pagination_args):"")?></div></div>
	<?php }
}else {
	if (!is_author()) {
		if (wpqa_is_user_notifications() || wpqa_is_user_referrals() || wpqa_is_user_messages() || wpqa_is_user_activities() || (((wpqa_is_user_points() && $active_points == "on") || $last_one == "followed" || $last_one == "favorites") && $show_point_favorite != "on" && !wpqa_is_user_owner())) {
			echo '<div class="alert-message warning"><i class="icon-flag"></i><p>'.esc_html__("Sorry, is this private page.","wpqa").'</p></div>';
		}else if ($first_one_filter == false && (!isset($no_pages) || ($wpqa_user_title != "" && isset($no_pages)))) {
			echo '<div class="alert-message error"><i class="icon-cancel"></i><p>'.esc_html__("Sorry, this page is not found.","wpqa").'</p></div>';
		}
	}
	do_action("wpqa_action_after_profile_content");
}?>