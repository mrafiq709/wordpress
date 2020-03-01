<?php
/*
    Plugin Name: Sample Shorcode
    Plugin URI:  http://your.url
    Description: Wordpress-plugin for testing Wordpress shortcodes
    Version:     1.0.0
    Author:      Your Name
    Author URI:  http://your.url
    License:     GPL2
    License URI: https://www.gnu.org/licenses/gpl-2.0.html
    Text Domain: wporg
    Domain Path: /languages
 */

// todo: plugin code goes here...

function get_shortcode_this_is_a_shortcode()
{
    return "<p>Hello from <b>Custom Shortcode Plugin</b></p>";
}

add_shortcode('this_is_a_shortcode', 'get_shortcode_this_is_a_shortcode');

?>