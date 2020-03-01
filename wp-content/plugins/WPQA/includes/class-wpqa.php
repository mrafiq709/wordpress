<?php

/* @author    2codeThemes
*  @package   WPQA/includes
*  @version   1.0
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if (!class_exists('WPQA')) :
	class WPQA {
		/* Name */
		protected $plugin_name;
		public function plugin_name() {
			return $this->plugin_name;
		}
		/* Name capital */
		public function super_plugin_name() {
			return strtoupper($this->plugin_name);
		}
		/* Version */
		protected $plugin_version;
		public function plugin_version() {
			return $this->plugin_version;
		}
		/* Plugin URL */
		protected $plugin_url;
		public function plugin_url() {
			return $this->plugin_url;
		}
		/* URL */
		protected $site_url;
		public function site_url() {
			return $this->site_url;
		}
		/* The php main path */
		protected $wpqa_main_path;
		public function wpqa_main_path() {
			return $this->wpqa_main_path;
		}
		
		/* Define the core functionality of the plugin. */
		public function __construct() {
			$text_domain = get_file_data(plugin_dir_path(dirname(__DIR__))."WPQA/wpqa.php",array('Text Domain'),'plugin');
			$version = get_file_data(plugin_dir_path(dirname(__DIR__))."WPQA/wpqa.php",array('Version'),'plugin');
			$this->plugin_name = $text_domain[0];
			$this->plugin_version = $version[0];
			$this->plugin_url = "https://2code.info/WPQA/";
			$this->site_url = "https://2code.info/";
			$this->wpqa_main_path = plugin_dir_path(dirname(__FILE__));
			
			$this->wpqa_get_functions();
			$this->wpqa_get_shortcodes();
			$this->wpqa_get_widgets();
			$this->wpqa_get_post_type();
			
			add_action('wp_enqueue_scripts',array($this,'wpqa_enqueue_style'));
			add_action('admin_enqueue_scripts',array($this,'wpqa_enqueue_admin'));
		}
		/* The code that runs during plugin activation */
		public static function activate() {
			global $wp_version,$wpdb;
			$wpdb->query($wpdb->prepare("ALTER TABLE ".$wpdb->users." CHANGE `user_nicename` `user_nicename` VARCHAR(255) NOT NULL DEFAULT %s;",''));
			$wp_compatible_version = '4.0';
			if (version_compare($wp_version,$wp_compatible_version,'<')) {
				deactivate_plugins(basename(__FILE__));
				wp_die('<p>'.sprintf(esc_html__('This plugin can not be activated because it requires a WordPress version at least %1$s (or later). Please go to Dashboard &#9656; Updates to get the latest version of WordPress.','wpqa'),$wp_compatible_version).'</p><a href="'.admin_url('plugins.php').'">'.esc_html__('go back','wpqa').'</a>');
			}
			update_option("FlushRewriteRules",true);
		}
		/* The code that runs during plugin deactivation */
		public static function deactivate() {
			flush_rewrite_rules(true);
		}
		/* The code that runs the functions */
		public function wpqa_get_functions() {
			require_once plugin_dir_path(dirname(__FILE__)).'mail/src/SMTP.php';
			require_once plugin_dir_path(dirname(__FILE__)).'mail/src/Exception.php';
			require_once plugin_dir_path(dirname(__FILE__)).'mail/src/PHPMailer.php';
			require_once plugin_dir_path(dirname(__FILE__)).'functions/resizer.php';
			require_once plugin_dir_path(dirname(__FILE__)).'functions/mails.php';
			require_once plugin_dir_path(dirname(__FILE__)).'functions/ajax-action.php';
			require_once plugin_dir_path(dirname(__FILE__)).'payments/payments.php';
			require_once plugin_dir_path(dirname(__FILE__)).'functions/functions.php';
			require_once plugin_dir_path(dirname(__FILE__)).'functions/referrals.php';
			require_once plugin_dir_path(dirname(__FILE__)).'functions/messages.php';
			require_once plugin_dir_path(dirname(__FILE__)).'functions/avatar.php';
			require_once plugin_dir_path(dirname(__FILE__)).'functions/cover.php';
			require_once plugin_dir_path(dirname(__FILE__)).'functions/categories.php';
			require_once plugin_dir_path(dirname(__FILE__)).'functions/rewrite.php';
			require_once plugin_dir_path(dirname(__FILE__)).'functions/menu.php';
			require_once plugin_dir_path(dirname(__FILE__)).'functions/filters.php';
			require_once plugin_dir_path(dirname(__FILE__)).'functions/actions.php';
			require_once plugin_dir_path(dirname(__FILE__)).'functions/comments.php';
			require_once plugin_dir_path(dirname(__FILE__)).'functions/questions.php';
			require_once plugin_dir_path(dirname(__FILE__)).'functions/check-account.php';
		}
		/* The code that runs the shortcodes */
		public function wpqa_get_shortcodes() {
			require_once plugin_dir_path(dirname(__FILE__)).'shortcodes/shortcodes.php';
		}
		/* The code that runs the widgets */
		public function wpqa_get_widgets() {
			require_once plugin_dir_path(dirname(__FILE__)).'widgets/widgets.php';
		}
		/* The code that runs the post type */
		public function wpqa_get_post_type() {
			require_once plugin_dir_path(dirname(__FILE__)).'functions/post_type.php';
		}
		/* The code that runs the enqueue style */
		public function wpqa_enqueue_style() {
			wp_enqueue_style('wpqa-custom-css',plugins_url('assets/css/custom.css',dirname(__FILE__)),array(),$this->plugin_version());
			$require_name_email    = get_option("require_name_email");
			$comment_editor        = wpqa_options((is_singular("question")?"answer_editor":"comment_editor"));
			$captcha_answer        = wpqa_options("captcha_answer");
			$attachment_answer     = wpqa_options("attachment_answer");
			$featured_image_answer = wpqa_options("featured_image_answer");
			$poll_image            = wpqa_options("poll_image");
			$poll_image_title      = wpqa_options("poll_image_title");
			$comment_limit         = (int)wpqa_options((is_singular("question")?"answer_limit":"comment_limit"));
			$comment_min_limit     = (int)wpqa_options((is_singular("question")?"answer_min_limit":"comment_min_limit"));
			$question_bump_points  = (int)wpqa_options("question_bump_points");
			$ajax_file             = wpqa_options("ajax_file");
			wp_enqueue_script("wpqa-scripts-js",plugins_url('assets/js/scripts.js',dirname(__FILE__)),array("jquery"),$this->plugin_version(),true);
			wp_enqueue_script("wpqa-custom-js",plugins_url('assets/js/custom.js',dirname(__FILE__)),array("jquery","jquery-ui-core","jquery-ui-sortable","jquery-ui-datepicker"),$this->plugin_version(),true);
			$ajax_file = ($ajax_file == "theme"?plugins_url('includes/ajax.php',dirname(__FILE__)):admin_url("admin-ajax.php"));
			$wpqa_js = array(
				'wpqa_dir'               => plugin_dir_url(dirname(__FILE__)),
				'admin_url'              => $ajax_file,
				'captcha_answer'         => $captcha_answer,
				'attachment_answer'      => $attachment_answer,
				'featured_image_answer'  => $featured_image_answer,
				'comment_editor'         => $comment_editor,
				'poll_image'             => $poll_image,
				'poll_image_title'       => $poll_image_title,
				'comment_limit'          => $comment_limit,
				'comment_min_limit'      => $comment_min_limit,
				'wpqa_best_answer_nonce' => wp_create_nonce("wpqa_best_answer_nonce"),
				'home_url'               => esc_url(home_url('/')),
				'require_name_email'     => ($require_name_email == 1?"require_name_email":""),
				'wpqa_error_text'        => esc_html__("Please fill the required field.","wpqa"),
				'wpqa_error_min_limit'   => esc_html__("Sorry, The minimum characters is","wpqa"),
				'wpqa_error_limit'       => esc_html__("Sorry, The maximum characters is","wpqa"),
				'wpqa_error_captcha'     => esc_html__("The captcha is incorrect, Please try again.","wpqa"),
				'delete_account'         => esc_html__("Are you sure you want to delete your account?","wpqa"),
				'sure_delete'            => esc_html__("Are you sure you want to delete the question?","wpqa"),
				'sure_delete_post'       => esc_html__("Are you sure you want to delete the post?","wpqa"),
				'sure_delete_comment'    => esc_html__("Are you sure you want to delete the comment?","wpqa"),
				'sure_delete_answer'     => esc_html__("Are you sure you want to delete the answer?","wpqa"),
				'sure_delete_message'    => esc_html__("Are you sure you want to delete the message?","wpqa"),
				'sure_ban'               => esc_html__("Are you sure you want to ban this user?","wpqa"),
				'wpqa_remove_image'      => esc_html__("Are you sure you want to delete the image?","wpqa"),
				'wpqa_remove_attachment' => esc_html__("Are you sure you want to delete the attachment?","wpqa"),
				'no_vote_question'       => esc_html__("Sorry, you cannot vote your question.","wpqa"),
				'no_vote_more'           => esc_html__("Sorry, you cannot vote on the same question more than once.","wpqa"),
				'no_vote_user'           => esc_html__("Voting is available to members only.","wpqa"),
				'no_vote_answer'         => esc_html__("Sorry, you cannot vote your answer.","wpqa"),
				'no_vote_more_answer'    => esc_html__("Sorry, you cannot vote on the same answer more than once.","wpqa"),
				'no_vote_comment'        => esc_html__("Sorry, you cannot vote your comment.","wpqa"),
				'no_vote_more_comment'   => esc_html__("Sorry, you cannot vote on the same comment more than once.","wpqa"),
				'no_poll_more'           => esc_html__("Sorry, you cannot poll on the same question more than once.","wpqa"),
				'choose_best_answer'     => esc_html__("Select as best answer","wpqa"),
				'cancel_best_answer'     => esc_html__("Cancel the best answer","wpqa"),
				'best_answer'            => esc_html__("Best answer","wpqa"),
				'best_answer_selected'   => esc_html__("There is another one select this a best answer","wpqa"),
				'follow_question_attr'   => esc_html__("Follow the question","wpqa"),
				'unfollow_question_attr' => esc_html__("Unfollow the question","wpqa"),
				'follow'                 => esc_html__("Follow","wpqa"),
				'unfollow'               => esc_html__("Unfollow","wpqa"),
				'select_file'            => esc_html__("Select file","wpqa"),
				'browse'                 => esc_html__("Browse","wpqa"),
				"no_questions"           => esc_html__("There are no questions yet.","wpqa"),
				'reported'               => esc_html__("Thank you, your reported will be reviewed shortly.","wpqa"),
				'add_favorite'           => esc_html__("Add this question to favorites","wpqa"),
				'remove_favorite'        => esc_html__("Remove this question of my favorites","wpqa"),
				'wpqa_error_name'        => esc_html__("Please fill the required fields (name).","wpqa"),
				'wpqa_error_email'       => esc_html__("Please fill the required fields (email).","wpqa"),
				'wpqa_error_comment'     => esc_html__("Please type a comment.","wpqa"),
				'wpqa_valid_email'       => esc_html__("Please enter a valid email address.","wpqa"),
				'cancel_reply'           => esc_html__("Cancel reply.","wpqa"),
				'block_message_text'     => esc_html__("Block Message","wpqa"),
				'unblock_message_text'   => esc_html__("Unblock Message","wpqa"),
				'click_continue'         => esc_html__("Click here to continue.","wpqa"),
				'click_not_finish'       => esc_html__("Complete your following to continue.","wpqa"),
				'ban_user'               => esc_html__("Ban user","wpqa"),
				'unban_user'             => esc_html__("Unban user","wpqa"),
				"must_login"             => esc_html__("Please login, Only users can vote and see the results.","wpqa"),
				'question_bump'          => sprintf(esc_html__("Sorry, you are bumping your question with less than %s points.","wpqa"),$question_bump_points),
				'question_bump_points'   => $question_bump_points,
				"get_points"             => esc_html__("You have bumped your question.","wpqa"),
				"email_exist"            => esc_html__("This email is exist already.","wpqa"),
				"sent_invitation"        => esc_html__("The invitation was sent.","wpqa"),
			);
			wp_localize_script('wpqa-custom-js','wpqa_js',$wpqa_js);
		}
		/* The code that runs the enqueue for admin */
		public function wpqa_enqueue_admin() {
			wp_enqueue_script("wpqa-admin-custom-js",plugins_url('assets/js/admin-custom.js',dirname(__FILE__)),array("jquery"),$this->plugin_version(),true);
			$option_js = array(
				'ajax_a'                    => plugins_url('includes/ajax.php',dirname(__FILE__)),
				'confirm_delete'            => esc_html__("Are you sure you want to delete?","wpqa"),
				'confirm_reports'           => esc_html__("If you press will delete report!","wpqa"),
				'no_reports'                => esc_html__("There are no reports yet.","wpqa"),
				'confirm_delete_attachment' => esc_html__("If you press will delete the attachment!","wpqa"),
			);
			wp_localize_script('wpqa-admin-custom-js','option_js',$option_js);
		}
	}
endif;
$wpqa = new WPQA;
add_action('wp_head','wpqa_wp_head');
function wpqa_wp_head() {
	if(!session_id()) session_start();
}