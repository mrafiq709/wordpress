<?php
/*
Plugin Name: Basic Filter And Security
Plugin URI:  http://link to your plugin homepage
Description: This plugin replaces words with your own choice of words.
Version:     1.0
Author:      Author Name
Author URI:  link to your website
License:     GPL2 etc
License URI: link to your plugin license

Copyright YEAR PLUGIN_AUTHOR_NAME (email : your email address)
(Plugin Name) is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.

(Plugin Name) is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with (Plugin Name). If not, see (link to your plugin license).
 */

/*Use this function to replace a single word*/
function renym_wordpress_typo_fix($text)
{
    return str_replace('content', 'WordPress', $text);
}
add_filter('the_content', 'renym_wordpress_typo_fix');

/*Or use this function to replace multiple words or phrases at once*/
function renym_content_replace($content)
{
    $search = array('wordpress', 'dfgdfgdfgdf', 'Easter', '70', 'sensational');
    $replace = array('WordPress', 'coffee', 'Easter holidays', 'seventy', 'extraordinary');
    return str_replace($search, $replace, $content);
}
add_filter('the_content', 'renym_content_replace');

/*Use this function to add a note at the end of your content*/
function renym_content_footer_note($content)
{
    $content .= '<footer class="renym-content-footer">Thank you for reading this tutorial. Maybe next time I will let you buy me a coffee! For more WordPress tutorials visit our <a href="http://github.com" title="WPExplorer Blog">Blog</a></footer>';
    return $content;
}
add_filter('the_content', 'renym_content_footer_note');

/**
=============================
Adding Administrations Menu
=============================
 */
add_action('admin_menu', 'test_plugin_setup_menu');

// Called When Test Plugin Clicked
function test_plugin_setup_menu()
{
    add_menu_page('Test Plugin Page', 'Test Plugin', 'manage_options', 'test-plugin', 'test_init');
}

function test_init()
{
    echo "<h1>Hello World!</h1>";
}



/**
=============================
Security
=============================
 */

/**
 * generate a Delete link based on the homepage url
 */
function wporg_generate_delete_link($content)
{
    // run only for single post page
    if (is_single() && in_the_loop() && is_main_query()) {
        // add query arguments: action, post, nonce
        $url = add_query_arg(
            [
                'action' => 'wporg_frontend_delete',
                'post'   => get_the_ID(),
                'nonce'  => wp_create_nonce('wporg_frontend_delete'),
            ],
            home_url()
        );
        return $content . ' <a href="' . esc_url($url) . '">' . esc_html__('Delete Post', 'wporg') . '</a>';
    }
    return null;
}
 
/**
 * request handler
 */
function wporg_delete_post()
{
    if (
        isset($_GET['action']) &&
        isset($_GET['nonce']) &&
        $_GET['action'] === 'wporg_frontend_delete' &&
        wp_verify_nonce($_GET['nonce'], 'wporg_frontend_delete')
    ) {
 
        // verify we have a post id
        $post_id = (isset($_GET['post'])) ? ($_GET['post']) : (null);
 
        // verify there is a post with such a number
        $post = get_post((int)$post_id);
        if (empty($post)) {
            return;
        }
 
        // delete the post
        wp_trash_post($post_id);
 
        // redirect to admin page
        $redirect = admin_url('edit.php');
        wp_safe_redirect($redirect);
 
        // we are done
        die;
    }
}

if(!function_exists('wp_get_current_user')) { include(ABSPATH . "wp-includes/pluggable.php"); }

if (current_user_can('edit_others_posts')) {
    /**
     * add the delete link to the end of the post content
     */
    add_filter('the_content', 'wporg_generate_delete_link');
 
    /**
     * register our request handler with the init hook
     */
    add_action('init', 'wporg_delete_post');
}


?>