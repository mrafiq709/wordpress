<?php

/* @author    2codeThemes
*  @package   WPQA/templates/profile
*  @version   1.0
*/

if ( ! defined( 'ABSPATH' ) ) {
   exit; // Exit if accessed directly
}?>

<div class="wrap-tabs">
	<div class="menu-tabs">
		<ul class="menu flex">
			<li<?php echo (!wpqa_user_title()?" class='active-tab'":"")?>><a href="<?php echo esc_url(wpqa_profile_url($wpqa_user_id))?>"><?php esc_html_e("About","wpqa")?></a></li>
			<?php $user_profile_pages = apply_filters("discy_user_profile_pages",$user_profile_pages);
			if (isset($user_profile_pages) && is_array($user_profile_pages) && !empty($user_profile_pages)) {
				foreach ($user_profile_pages as $key => $value) {
					do_action("discy_action_user_profile_pages",$user_profile_pages,$key,$value);
					if ($key == "questions" && isset($user_profile_pages["questions"]["value"]) && $user_profile_pages["questions"]["value"] == "questions") {?>
						<li<?php echo (wpqa_is_user_questions() || "questions" == wpqa_user_title()?" class='active-tab'":"")?>><a href="<?php echo esc_url(wpqa_get_profile_permalink($wpqa_user_id,"questions"))?>"><?php esc_html_e("Questions","wpqa")?></a></li>
					<?php }else if ($key == "best-answers" && isset($user_profile_pages["best-answers"]["value"]) && $user_profile_pages["best-answers"]["value"] == "best-answers") {?>
						<li<?php echo (wpqa_is_best_answers() || "best-answers" == wpqa_user_title()?" class='active-tab'":"")?>><a href="<?php echo esc_url(wpqa_get_profile_permalink($wpqa_user_id,"best_answers"))?>"><?php esc_html_e("Best Answers","wpqa")?></a></li>
					<?php }else if ($key == "polls" && isset($user_profile_pages["polls"]["value"]) && $user_profile_pages["polls"]["value"] == "polls") {?>
						<li<?php echo (wpqa_is_user_polls() || "polls" == wpqa_user_title()?" class='active-tab'":"")?>><a href="<?php echo esc_url(wpqa_get_profile_permalink($wpqa_user_id,"polls"))?>"><?php esc_html_e("Polls","wpqa")?></a></li>
					<?php }else if ($key == "answers" && isset($user_profile_pages["answers"]["value"]) && $user_profile_pages["answers"]["value"] == "answers") {?>
						<li<?php echo (wpqa_is_user_answers() || "answers" == wpqa_user_title()?" class='active-tab'":"")?>><a href="<?php echo esc_url(wpqa_get_profile_permalink($wpqa_user_id,"answers"))?>"><?php esc_html_e("Answers","wpqa")?></a></li>
					<?php }else if ($key == "asked" && isset($user_profile_pages["asked"]["value"]) && $user_profile_pages["asked"]["value"] == "asked" && $ask_question_to_users == "on") {?>
						<li<?php echo (wpqa_is_user_asked() || "asked" == wpqa_user_title()?" class='active-tab'":"")?>><a href="<?php echo esc_url(wpqa_get_profile_permalink($wpqa_user_id,"asked"))?>"><?php esc_html_e("Asked Questions","wpqa");?></a></li>
						<?php if (wpqa_is_user_owner() && isset($user_profile_pages["asked-questions"]["value"]) && $user_profile_pages["asked-questions"]["value"] == "asked-questions") {?>
							<li<?php echo (wpqa_is_asked_questions() || "asked-questions" == wpqa_user_title()?" class='active-tab'":"")?>><a href="<?php echo esc_url(wpqa_get_profile_permalink($wpqa_user_id,"asked_questions"))?>"><?php esc_html_e("Waiting Questions","wpqa");echo ($asked_questions > 0 && wpqa_is_user_owner()?"<span class='notifications-number asked-count'>".wpqa_count_number($asked_questions)."</span>":"")?></a></li>
						<?php }
					}else if ($key == "paid-questions" && isset($user_profile_pages["paid-questions"]["value"]) && $user_profile_pages["paid-questions"]["value"] == "paid-questions" && $pay_ask == "on") {?>
						<li<?php echo (wpqa_is_paid_questions() || "paid-questions" == wpqa_user_title()?" class='active-tab'":"")?>><a href="<?php echo esc_url(wpqa_get_profile_permalink($wpqa_user_id,"paid_questions"))?>"><?php esc_html_e("Paid Questions","wpqa")?></a></li>
					<?php }else if ($key == "favorites" && isset($user_profile_pages["favorites"]["value"]) && $user_profile_pages["favorites"]["value"] == "favorites" && ($show_point_favorite == "on" || wpqa_is_user_owner())) {?>
						<li<?php echo (wpqa_is_user_favorites() || "favorites" == wpqa_user_title()?" class='active-tab'":"")?>><a href="<?php echo esc_url(wpqa_get_profile_permalink($wpqa_user_id,"favorites"))?>"><?php esc_html_e("Favorite Questions","wpqa")?></a></li>
					<?php }else if ($key == "followed" && isset($user_profile_pages["followed"]["value"]) && $user_profile_pages["followed"]["value"] == "followed" && ($show_point_favorite == "on" || wpqa_is_user_owner())) {?>
						<li<?php echo (wpqa_is_user_followed() || "followed" == wpqa_user_title()?" class='active-tab'":"")?>><a href="<?php echo esc_url(wpqa_get_profile_permalink($wpqa_user_id,"followed"))?>"><?php esc_html_e("Followed Questions","wpqa")?></a></li>
					<?php }else if ($key == "posts" && isset($user_profile_pages["posts"]["value"]) && $user_profile_pages["posts"]["value"] == "posts") {?>
						<li<?php echo (wpqa_is_user_posts() || "posts" == wpqa_user_title()?" class='active-tab'":"")?>><a href="<?php echo esc_url(wpqa_get_profile_permalink($wpqa_user_id,"posts"))?>"><?php esc_html_e("Posts","wpqa")?></a></li>
					<?php }else if ($key == "comments" && isset($user_profile_pages["comments"]["value"]) && $user_profile_pages["comments"]["value"] == "comments") {?>
						<li<?php echo (wpqa_is_user_comments() || "comments" == wpqa_user_title()?" class='active-tab'":"")?>><a href="<?php echo esc_url(wpqa_get_profile_permalink($wpqa_user_id,"comments"))?>"><?php esc_html_e("Comments","wpqa")?></a></li>
					<?php }else if ($key == "followers-questions" && isset($user_profile_pages["followers-questions"]["value"]) && $user_profile_pages["followers-questions"]["value"] == "followers-questions") {?>
						<li<?php echo (wpqa_is_followers_questions() || "followers-questions" == wpqa_user_title()?" class='active-tab'":"")?>><a href="<?php echo esc_url(wpqa_get_profile_permalink($wpqa_user_id,"followers_questions"))?>"><?php esc_html_e("Followers Questions","wpqa")?></a></li>
					<?php }else if ($key == "followers-answers" && isset($user_profile_pages["followers-answers"]["value"]) && $user_profile_pages["followers-answers"]["value"] == "followers-answers") {?>
						<li<?php echo (wpqa_is_followers_answers() || "followers-answers" == wpqa_user_title()?" class='active-tab'":"")?>><a href="<?php echo esc_url(wpqa_get_profile_permalink($wpqa_user_id,"followers_answers"))?>"><?php esc_html_e("Followers Answers","wpqa")?></a></li>
					<?php }else if ($key == "followers-posts" && isset($user_profile_pages["followers-posts"]["value"]) && $user_profile_pages["followers-posts"]["value"] == "followers-posts") {?>
						<li<?php echo (wpqa_is_followers_posts() || "followers-posts" == wpqa_user_title()?" class='active-tab'":"")?>><a href="<?php echo esc_url(wpqa_get_profile_permalink($wpqa_user_id,"followers_posts"))?>"><?php esc_html_e("Followers Posts","wpqa")?></a></li>
					<?php }else if ($key == "followers-comments" && isset($user_profile_pages["followers-comments"]["value"]) && $user_profile_pages["followers-comments"]["value"] == "followers-comments") {?>
						<li<?php echo (wpqa_is_followers_comments() || "followers-comments" == wpqa_user_title()?" class='active-tab'":"")?>><a href="<?php echo esc_url(wpqa_get_profile_permalink($wpqa_user_id,"followers_comments"))?>"><?php esc_html_e("Followers Comments","wpqa")?></a></li>
					<?php }
				}
			}?>
		</ul>
	</div><!-- End menu-tabs -->
</div><!-- End wrap-tabs -->