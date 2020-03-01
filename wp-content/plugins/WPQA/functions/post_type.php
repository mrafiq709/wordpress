<?php

/* @author    2codeThemes
*  @package   WPQA/functions
*  @version   1.0
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/* Remove question slug */
add_filter('post_type_link','wpqa_remove_slug',10,2);
function wpqa_remove_slug($post_link,$post) {
	$remove_question_slug = wpqa_options("remove_question_slug");
	if ($remove_question_slug == "on") {
		if ('question' != $post->post_type || 'publish' != $post->post_status) {
			return $post_link;
		}
		$question_slug = wpqa_options('question_slug');
		$question_slug = ($question_slug != ""?$question_slug:"question");
		$post_link = str_replace('/'.$question_slug.'/','/',$post_link);
	}
	return $post_link;
}
add_action('pre_get_posts','wpqa_parse_request');
function wpqa_parse_request($query) {
	$remove_question_slug = wpqa_options("remove_question_slug");
	if ($remove_question_slug == "on") {
		if (!$query->is_main_query() || 2 != count($query->query) || !isset($query->query['page'])) {
			return;
		}
		if (!empty($query->query['name'])) {
			$query->set('post_type',array('post','question','page'));
		}
	}
}
/* Question post type */
if (!function_exists('wpqa_question_post_type')) :
	function wpqa_question_post_type() {
		$remove_question_slug   = wpqa_options("remove_question_slug");
		$ask_question_items     = wpqa_options("ask_question_items");
		$thumbnail              = (isset($ask_question_items["featured_image"]["value"]) && $ask_question_items["featured_image"]["value"] == "featured_image"?array("thumbnail"):array());
		
		$archive_question_slug  = wpqa_options('archive_question_slug');
		$archive_question_slug  = ($archive_question_slug != ""?$archive_question_slug:"questions");
		
		$question_slug          = wpqa_options('question_slug');
		$question_slug          = ($question_slug != ""?$question_slug:"question");
		
		$category_question_slug = wpqa_options('category_question_slug');
		$category_question_slug = ($category_question_slug != ""?$category_question_slug:"question-category");
		
		$tag_question_slug      = wpqa_options('tag_question_slug');
		$tag_question_slug      = ($tag_question_slug != ""?$tag_question_slug:"question-tag");
	   
	    register_post_type('question',
	        array(
	        	'label' => esc_html__('Questions','wpqa'),
	            'labels' => array(
					'name'               => esc_html__('Questions','wpqa'),
					'singular_name'      => esc_html__('Questions','wpqa'),
					'menu_name'          => esc_html__('Questions','wpqa'),
					'name_admin_bar'     => esc_html__('Question','wpqa'),
					'add_new'            => esc_html__('Add New','wpqa'),
					'add_new_item'       => esc_html__('Add New Question','wpqa'),
					'new_item'           => esc_html__('New Question','wpqa'),
					'edit_item'          => esc_html__('Edit Question','wpqa'),
					'view_item'          => esc_html__('View Question','wpqa'),
					'view_items'         => esc_html__('View Questions','wpqa'),
					'all_items'          => esc_html__('All Questions','wpqa'),
					'search_items'       => esc_html__('Search Questions','wpqa'),
					'parent_item_colon'  => esc_html__('Parent Question:','wpqa'),
					'not_found'          => esc_html__('No Questions Found.','wpqa'),
					'not_found_in_trash' => esc_html__('No Questions Found in Trash.','wpqa'),
	            ),
	            'description'         => '',
	            'public'              => true,
	            'show_ui'             => true,
	            'capability_type'     => 'post',
	            'publicly_queryable'  => true,
	            'exclude_from_search' => false,
	            'hierarchical'        => false,
	            'rewrite'             => array('slug' => $question_slug,'with_front' => false),
	            'query_var'           => true,
	            'show_in_rest'        => true,
	            'has_archive'         => apply_filters("wpqa_archive_question",$archive_question_slug),
				'menu_position'       => 5,
				'menu_icon'           => "dashicons-editor-help",
	            'supports'            => array_merge($thumbnail,array('title','editor','comments','author')),
	        )
	    );
	    
		$labels = array(
			'name'              => esc_html__('Question Categories','wpqa'),
			'singular_name'     => esc_html__('Question Categories','wpqa'),
			'search_items'      => esc_html__('Search Categories','wpqa'),
			'all_items'         => esc_html__('All Categories','wpqa'),
			'parent_item'       => esc_html__('Question Categories','wpqa'),
			'parent_item_colon' => esc_html__('Question Categories','wpqa'),
			'edit_item'         => esc_html__('Edit Category','wpqa'),
			'update_item'       => esc_html__('Edit','wpqa'),
			'add_new_item'      => esc_html__('Add New Category','wpqa'),
			'new_item_name'     => esc_html__('Add New Category','wpqa')
		);
		
		register_taxonomy('question-category','question',array(
			'hierarchical' => true,
			'labels'       => $labels,
			'show_ui'      => true,
			'query_var'    => true,
			'show_in_rest' => true,
			'rewrite'      => array( 'slug' => $category_question_slug, 'with_front' => false ),
		));
		
		register_taxonomy( 'question_tags',
			array('question'),
			array(
				'hierarchical' => false,
				'labels' => array(
					'name'              => esc_html__('Question Tags','wpqa'),
					'singular_name'     => esc_html__('Question Tags','wpqa'),
					'search_items'      => esc_html__('Search Tags','wpqa'),
					'all_items'         => esc_html__('All Tags','wpqa'),
					'parent_item'       => esc_html__('Question Tags','wpqa'),
					'parent_item_colon' => esc_html__('Question Tags','wpqa'),
					'edit_item'         => esc_html__('Edit Tag','wpqa'),
					'update_item'       => esc_html__('Edit','wpqa'),
					'add_new_item'      => esc_html__('Add New Tag','wpqa'),
					'new_item_name'     => esc_html__('Add New Tag','wpqa')
				),
				'show_ui'      => true,
				'query_var'    => true,
				'show_in_rest' => true,
				'rewrite'      => array( 'slug' => $tag_question_slug ),
			)
		);
	}
endif;
add_action('wpqa_init','wpqa_question_post_type',0);
/* Remove meta boxes */
if (!function_exists('wpqa_remove_meta_boxes')) :
	function wpqa_remove_meta_boxes() {
		global $post;
		$category_single_multi = wpqa_options("category_single_multi");
		if ($category_single_multi != "multi") {
			remove_meta_box( 'question-categorydiv', 'question', 'side' );
		}
		if (isset($post->ID) && $post->ID > 0) {
			$get_question_user_id = get_post_meta($post->ID,"user_id",true);
			if ($get_question_user_id != "") {
				remove_meta_box( 'tagsdiv-question_tags', 'question', 'side' );
			}
		}
	}
endif;
add_action( 'do_meta_boxes' , 'wpqa_remove_meta_boxes' );
/* Admin columns for post types */
if (!function_exists('wpqa_question_columns')) :
	function wpqa_question_columns($old_columns){
		$columns = array();
		$columns["cb"]       = "<input type=\"checkbox\">";
		$columns["title"]    = esc_html__("Title","wpqa");
		$columns["type"]     = esc_html__("Type","wpqa");
		$columns["author-q"] = esc_html__("Author","wpqa");
		$columns["category"] = esc_html__("Category","wpqa");
		$columns["tag"]      = esc_html__("Tags","wpqa");
		$columns["comments"] = "<span class='vers comment-grey-bubble' title='".esc_attr__("Answers","wpqa")."'><span class='screen-reader-text'>".esc_html__("Answers","wpqa")."</span></span>";
		$columns["date"]     = esc_html__("Date","wpqa");
		return $columns;
	}
endif;
add_filter('manage_edit-question_columns', 'wpqa_question_columns');
if (!function_exists('wpqa_question_custom_columns')) :
	function wpqa_question_custom_columns($column) {
		global $post;
		$question_details = wpqa_question_get_question_details( $post->ID );
		switch ( $column ) {
			case 'type' :
				$question_poll = get_post_meta($post->ID,'question_poll',true);
				if ($question_poll == "on") {
					echo '<a href="'.admin_url('edit.php?post_type=question&types=poll').'">'.esc_html__("Poll","wpqa").'</a>';
				}else {
					echo '<a href="'.admin_url('edit.php?post_type=question&types=question').'">'.esc_html__("Question","wpqa").'</a>';
				}
			break;
			case 'author-q' :
				$display_name = get_the_author_meta('display_name',$post->post_author);
				if ($post->post_author > 0) {
					echo '<a href="'.admin_url('edit.php?post_type=question&question_author='.$post->post_author.'').'">'.$display_name.'</a>';
				}else {
					$anonymously_question = get_post_meta($post->ID,'anonymously_question',true);
					$anonymously_user = get_post_meta($post->ID,'anonymously_user',true);
					if (($anonymously_question == "on" || $anonymously_question == 1) && $anonymously_user != "") {
						$display_name_anonymous = get_the_author_meta('display_name',$anonymously_user);
						echo esc_html__("Anonymous","wpqa")." - <a href='".wpqa_profile_url($anonymously_user)."' target='_blank'>".$display_name_anonymous."</a>";
					}else {
						$question_username = get_post_meta($post->ID,'question_username',true);
						$question_username = ($question_username != ""?$question_username:esc_html__("Anonymous","wpqa"));
						echo esc_attr($question_username);
					}
				}
				$user_id = get_post_meta($post->ID,'user_id',true);
				if ($user_id != "") {
					$display_name = get_the_author_meta('display_name',$user_id);
					echo "<br>".esc_html__("Asked to","wpqa")." <a href='".wpqa_profile_url($user_id)."' target='_blank'>".$display_name."</a>";
				}
			break;
			case 'category' :
				$question_category = wp_get_post_terms($post->ID,'question-category',array("fields" => "all"));
				if (isset($question_category[0])) {?>
					<a href="<?php echo admin_url('edit.php?question-category='.$question_category[0]->slug.'&post_type=question');?>"><?php echo esc_attr($question_category[0]->name)?></a>
				<?php }else {
					echo '<span aria-hidden="true">-</span><span class="screen-reader-text">'.esc_html__("No category","wpqa").'</span>';
				}
			break;
			case 'tag' :
				$terms = wp_get_object_terms($post->ID,'question_tags');
				if ($terms) :
					$terms_array = array();
					foreach ($terms as $term) :
						$terms_array[] = '<a href="'.admin_url('edit.php?question_tags='.$term->slug.'&post_type=question').'">'.$term->name.'</a>';
					endforeach;
					echo implode(', ',$terms_array);
				else:
					echo '<span aria-hidden="true">-</span><span class="screen-reader-text">'.esc_html__("No tags","wpqa").'</span>';
				endif;
			break;
		}
	}
endif;
add_action('manage_question_posts_custom_column', 'wpqa_question_custom_columns', 2);
/* Get question details */
if (!function_exists('wpqa_question_get_question_details')) :
	function wpqa_question_get_question_details( $post_id ) { 
		$status = current(wp_get_object_terms( $post_id, 'site_status' ));
		return $post_id;
	}
endif;
/* Message update */
if (!function_exists('wpqa_question_updated_messages')) :
	function wpqa_question_updated_messages($messages) {
	  global $post,$post_ID;
	  $messages['question'] = array(
	    0 => '',
	    1 => sprintf(esc_html__('Updated. %1$s View question %2$s','wpqa'),'<a href="'.esc_url(get_permalink($post_ID)).'">','</a>'),
	  );
	  return $messages;
	}
endif;
add_filter('post_updated_messages','wpqa_question_updated_messages');
/* Questions status */
add_filter( "views_edit-question", "wpqa_questions_status" );
if (!function_exists('wpqa_questions_status')) :
	function wpqa_questions_status($status) {
		global $wpdb;
		$prepare = $wpdb->prepare("SELECT $wpdb->posts.* FROM $wpdb->posts INNER JOIN $wpdb->postmeta ON ( $wpdb->posts.ID = $wpdb->postmeta.post_id ) WHERE 1=1 AND ( ( $wpdb->postmeta.meta_key = 'question_poll' AND $wpdb->postmeta.meta_value = %s ) ) AND $wpdb->posts.post_type = 'question' AND (($wpdb->posts.post_status = 'publish')) GROUP BY $wpdb->posts.ID ORDER BY $wpdb->posts.post_date DESC","on");
		$result = $wpdb->get_results($prepare);
		$query_poll_count = count($result);
		$query_question_count = wp_count_posts("question")->publish-$query_poll_count;
		$get_status = ((isset($_GET['types']))?esc_attr($_GET['types']):'');
		
		$prepare_sticky = $wpdb->prepare("SELECT $wpdb->posts.* FROM $wpdb->posts INNER JOIN $wpdb->postmeta ON ( $wpdb->posts.ID = $wpdb->postmeta.post_id ) WHERE 1=1 AND ( ( $wpdb->postmeta.meta_key = 'sticky' AND $wpdb->postmeta.meta_value = %s ) ) AND $wpdb->posts.post_type = 'question' AND (($wpdb->posts.post_status = 'publish')) GROUP BY $wpdb->posts.ID ORDER BY $wpdb->posts.post_date DESC",1);
		$result_sticky = $wpdb->get_results($prepare_sticky);
		$query_sticky_count = count($result_sticky);
		
		return array_merge( $status, array(
			'question' => '<a href="'.admin_url('edit.php?post_type=question&types=question').'"'.(isset($get_status) && $get_status == "question"?' class="current"':'').'>'.esc_html__('Question','wpqa').' ('.$query_question_count.')</a>',
			'poll' => '<a href="'.admin_url('edit.php?post_type=question&types=poll').'"'.(isset($get_status) && $get_status == "poll"?' class="current"':'').'>'.esc_html__('Poll','wpqa').' ('.$query_poll_count.')</a>',
			'sticky' => '<a href="'.admin_url('edit.php?post_type=question&types=sticky').'"'.(isset($get_status) && $get_status == "sticky"?' class="current"':'').'>'.esc_html__('Sticky','wpqa').' ('.$query_sticky_count.')</a>',
		));
	}
endif;
add_action('current_screen','wpqa_questions_exclude',10,2);
if (!function_exists('wpqa_questions_exclude')) :
	function wpqa_questions_exclude($screen) {
		if ($screen->id != 'edit-question')
			return;
		$get_author = (int)((isset($_GET['question_author']))?esc_attr($_GET['question_author']):0);
		if ($get_author > 0) {
			add_filter('parse_query','wpqa_list_questions_author');
		}
		$get_status = ((isset($_GET['types']))?esc_attr($_GET['types']):'');
		if ($get_status == "question" || $get_status == "poll") {
			add_filter('parse_query','wpqa_list_questions');
		}
		if ($get_status == "sticky") {
			add_filter('parse_query','wpqa_list_questions_sticky');
		}
	}
endif;
if (!function_exists('wpqa_list_questions_author')) :
	function wpqa_list_questions_author($clauses) {
		$get_author = (int)((isset($_GET['question_author']))?esc_attr($_GET['question_author']):0);
		if ($get_author > 0) {
			$clauses->query_vars['author'] = $get_author;
		}
	}
endif;
if (!function_exists('wpqa_list_questions')) :
	function wpqa_list_questions($clauses) {
		$get_status = ((isset($_GET['types']))?esc_attr($_GET['types']):'');
		if ($get_status == "poll") {
			$clauses->query_vars['meta_key'] = "question_poll";
			$clauses->query_vars['meta_value'] = "on";
			$clauses->query_vars['post_type'] = "question";
		}else {
			$clauses->query_vars['meta_key'] = "question_poll";
			$clauses->query_vars['meta_value'] = 2;
			$clauses->query_vars['post_type'] = "question";
		}
	}
endif;
if (!function_exists('wpqa_list_questions_sticky')) :
	function wpqa_list_questions_sticky($clauses) {
		if (isset($_GET['types'])) {
			$get_status = esc_attr($_GET['types']);
		}
		if (isset($get_status) && $get_status == "sticky") {
			$clauses->query_vars['meta_key'] = "sticky";
			$clauses->query_vars['meta_value'] = 1;
			$clauses->query_vars['post_type'] = "question";
		}
	}
endif;
/* Add meta boxes */
add_action ('add_meta_boxes','wpqa_builder_meta_boxes');
if (!function_exists('wpqa_builder_meta_boxes')) :
	function wpqa_builder_meta_boxes($post_type) {
		if (in_array($post_type,array('post','question'))) {
			add_meta_box ('wpqa_delete_post_meta',($post_type == "post"?esc_html__('Delete post','wpqa'):esc_html__('Delete question','wpqa')),'wpqa_delete_post_meta',$post_type,'side');
		}
	}
endif;
/* Delete post questions */
if (!function_exists('wpqa_delete_post_meta')) :
	function wpqa_delete_post_meta() {
		global $post;?>
		<div class="minor-publishing">
			<div class="rwmb-field">
				<div class="rwmb-label">
					<label for="wpqa_delete_reason"><?php esc_html_e('Reason if you need to remove it.',"wpqa")?></label>
				</div>
				<div class="rwmb-input wpqa_checkbox_input">
					<input type="text" class="rwmb-input" name="wpqa_delete_reason" id="wpqa_delete_reason" value="<?php echo esc_attr(get_post_meta($post->ID,"wpqa_delete_reason",true));?>">
				</div>
				<div class="clear"></div><br>
				<div class="submitbox"><a href="#" class="submitdelete delete-question-post" data-div-id="wpqa_delete_reason" data-nonce="<?php echo wp_create_nonce("wpqa_delete_nonce")?>" data-id="<?php echo esc_attr($post->ID);?>" data-action="wpqa_delete_question_post" data-location="<?php echo esc_url(($post->post_type == "question"?admin_url( 'edit.php?post_type=question'):admin_url('edit.php')))?>"><?php esc_html_e('Delete?',"wpqa")?></a></div>
			</div>
		</div>
		<?php
	}
endif;
/* Add sticky question widget */
if (!function_exists('wpqa_sticky_add_meta_box')) :
	function wpqa_sticky_add_meta_box() {
		if (!current_user_can('edit_others_posts'))
			return;
		add_meta_box('wpqa_sticky_question',esc_html__('Sticky','wpqa'),'wpqa_sticky_question','question','side','high');
	}
endif;
/* Sticky question */
if (!function_exists('wpqa_sticky_question')) :
	function wpqa_sticky_question() {?>
		<input name="sticky_question" type="hidden" value="sticky">
		<label class="switch" for="sticky-question">
			<input id="sticky-question" name="sticky" type="checkbox" value="sticky" <?php checked( is_sticky() ); ?>>
			<label for="sticky-question" data-on="<?php esc_html_e("ON","wpqa")?>" data-off="<?php esc_html_e("OFF","wpqa")?>"></label>
		</label>
		<label for="sticky-question" class="selectit"><?php esc_html_e("Stick this question","wpqa") ?></label>
		<?php
	}
endif;
add_action('admin_init','wpqa_sticky_add_meta_box');?>