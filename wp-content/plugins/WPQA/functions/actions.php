<?php

/* @author    2codeThemes
*  @package   WPQA/functions
*  @version   1.0
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/* Core init */
add_action('init','wpqa_init',0);
if (!function_exists('wpqa_init')) :
	function wpqa_init() {
		do_action('wpqa_init');
	}
endif;
/* Action author */
add_action("wpqa_author","wpqa_action_author");
if (!function_exists('wpqa_action_author')) :
	function wpqa_action_author ($args = array()) {
		if (isset($args["user_id"])) {
			echo wpqa_author($args["user_id"],(isset($args["author_page"])?$args["author_page"]:""),(isset($args["owner"])?$args["owner"]:""),(isset($args["type_post"])?$args["type_post"]:""),(isset($args["widget"])?$args["widget"]:""),(isset($args["class"])?$args["class"]:""));
		}
	}
endif;
/* Get badge action */
add_action("wpqa_get_badge","wpqa_action_get_badge",1,3);
if (!function_exists('wpqa_action_get_badge')) :
	function wpqa_action_get_badge($user_id,$return = "",$points = "") {
		echo wpqa_get_badge($user_id,$return,$points);
	}
endif;
/* Get badge action */
add_action("wpqa_verified_user","wpqa_action_verified_user",1,2);
if (!function_exists('wpqa_action_verified_user')) :
	function wpqa_action_verified_user($user_id,$return = "") {
		echo wpqa_verified_user($user_id,$return = "");
	}
endif;
/* Show session */
add_action('wpqa_show_session','wpqa_show_session');
if (!function_exists('wpqa_show_session')) :
	function wpqa_show_session() {
		echo wpqa_session('','wpqa_session').wpqa_session('','wpqa_session_2');
	}
endif;
/* Search link */
add_action('wpqa_search_permalink','wpqa_search_permalink');
if (!function_exists('wpqa_search_permalink')) :
	function wpqa_search_permalink() {
		echo wpqa_get_search_permalink();
	}
endif;
/* Search content */
add_action('wpqa_get_search','wpqa_get_search');
if (!function_exists('wpqa_get_search')) :
	function wpqa_get_search() {
		echo wpqa_search_terms();
	}
endif;
/* Search content */
add_action('wpqa_search_type','wpqa_get_search_type');
if (!function_exists('wpqa_get_search_type')) :
	function wpqa_get_search_type() {
		echo wpqa_search_type();
	}
endif;
/* Profile content */
add_action('wpqa_get_profile','wpqa_get_profile',1,2);
if (!function_exists('wpqa_get_profile')) :
	function wpqa_get_profile($user_id = 0,$type = 'questions') {
		echo wpqa_get_profile_permalink($user_id,$type);
	}
endif;
/* Profile logout */
add_action('wpqa_action_get_logout','wpqa_action_get_logout');
if (!function_exists('wpqa_action_get_logout')) :
	function wpqa_action_get_logout() {
		echo wpqa_get_logout();
	}
endif;?>