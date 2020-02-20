<?php
/**
 * Plugin Name: We Tuts
 * Description: A simple description of our plguin
 * Plugin URI: http://tareq.weDevs.com
 * Author: Tareq Hasan
 * Author URI: http://tareq.wedevs.com
 * Version: 0.1
 * License: GPL2
 * Text Domain: wetuts
 */

/**
 * Copyright (c) 2014 Tareq Hasan (email: tareq@wedevs.com). All rights reserved.
 *
 * Released under the GPL license
 * http://www.opensource.org/licenses/gpl-license.php
 *
 * This is an add-on for WordPress
 * http://wordpress.org/
 *
 * **********************************************************************
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 * **********************************************************************
 */

// don't call the file directly
if ( !defined( 'ABSPATH' ) ) exit;

if ( is_admin() ) {
    require_once dirname( __FILE__ ) . '/includes/admin/profile.php';
}

/**
 * Print SEO tags in the header
 *
 * @return void
 */
function wetuts_seo_tags() {
    ?>
    <!-- weTuts SEO Plugin -->
    <meta name="description" content="Tareq Hasan | Web Application Developer" />
    <meta name="keywords" content="php, ajax, jQuery, php5, php4, wordpress, twitter" />
    <!-- weTuts SEO Plugin -->
    <?php
}

add_action( 'wp_head', 'wetuts_seo_tags' );

function wptuts_wp_footer() {
    echo '<h1>Hello Sunshine</h1>';
}
//add_action( 'wp_footer', 'wptuts_wp_footer', 99999 );

function wetuts_author_bio( $content ) {
    global $post;

    $author   = get_user_by( 'id', $post->post_author );

    $bio      = get_user_meta( $author->ID, 'description', true );
    $twitter  = get_user_meta( $author->ID, 'twitter', true );
    $facebook = get_user_meta( $author->ID, 'facebook', true );
    $linkedin = get_user_meta( $author->ID, 'linkedin', true );

    // ob_start(): This will store the htnl part as a stream
    ob_start();
    ?>
    <div class="wetuts-bio-wrap">

        <div class="avatar-image">
            <?php echo get_avatar( $author->ID, 64 ); ?>
        </div>

        <div class="wetuts-bio-content">
            <div class="author-name"><?php echo $author->display_name; ?></div>

            <div class="wetuts-author-bio">
            <!-- escape function -->
            <!-- wp_kses_post: If there is an ambigiouse or unexpected tag or attributes -->
            <!-- then it will remove them. And returb a plain text.-->
            <!-- wpautop: If there is line break, or spaces, then it will remove them and -->
            <!-- return as a paragraph text -->
                <?php echo wpautop( wp_kses_post( $bio ) ); ?>
            </div>

            <ul class="wetuts-socials">
                <?php if ( $twitter ) { ?>
                    <li><a href="<?php echo esc_url( $twitter ); ?>"><?php _e( 'Twitter', 'wetuts' ); ?></a></li>
                <?php } ?>

                <?php if ( $facebook ) { ?>
                    <li><a href="<?php echo esc_url( $facebook ); ?>"><?php _e( 'Facebook', 'wetuts' ); ?></a></li>
                <?php } ?>

                <?php if ( $linkedin ) { ?>
                    <li><a href="<?php echo esc_url( $linkedin ); ?>"><?php _e( 'LinkedIn', 'wetuts' ); ?></a></li>
                <?php } ?>
            </ul>
        </div>
    </div>
    <?php
    $bio_content = ob_get_clean(); // get the stored html by: ob_get_clean()

    return $content . $bio_content;
}

add_filter( 'the_content', 'wetuts_author_bio' );

function wetuts_enqueue_scripts() {
    wp_enqueue_style( 'wetuts-style', plugins_url( 'assets/css/style.css', __FILE__ ) );
}

add_action( 'wp_enqueue_scripts', 'wetuts_enqueue_scripts' );

function wetuts_page_content_top( $a, $b ) {
    echo '<h1>I am at top</h1>';

    var_dump($a, $b);
}

add_action( 'wetuts_page_content_top', 'wetuts_page_content_top', 10, 2 );

function wetuts_page_content_bottom( $a, $b ) {
    echo '<h1>I am at bottom</h1>';

    var_dump($a, $b);
}

add_action( 'wetuts_page_content_bottom', 'wetuts_page_content_bottom', 10, 2 );

function wetuts_my_welcome_message( $message, $a, $b ) {

    return 'Hello Sunshine, welcome back!' . $a . $b;
}

add_filter( 'wetuts_welcome_message', 'wetuts_my_welcome_message', 10, 3 );

/**
 * =========================
 * Short Code
 * =========================
 */
function wetuts_contact_form($atts, $content){
    $atts = shortcode_atts(array(
        'email' => get_option('admin_email'),
        'submit' => __('Send Email', 'wetuts')
    ), $atts);

    $submit = false;
    if(isset($_POST['wetuts_submit'])){
        $subject = $_POST['wetuts_subject'];
        $name = $_POST['wetuts_name'];
        $email = $_POST['wetuts_email'];
        $message = $_POST['wetuts_message'];

        $submit = true;
    }

    ob_start();
    ?>

    <?php if($submit) {?>
    <h1><?php _e('Email Sent Successfull', 'wetuts') ?></h1>
    <?php } ?>

    <form action="" id="wetuts_contact" method="POST">
        <p>
            <label for="name">Name</label>
            <input type="text" name="wetuts_name", value="">
        </p>
        <p>
            <label for="email">Email</label>
            <input type="text" name="wetuts_email", value="">
        </p>
        <p>
            <label for="subject">Subject</label>
            <input type="text" name="wetuts_subject", value="">
        </p>
        <p>
            <label for="message">Message</label>
            <input type="text" name="wetuts_message", value="">
        </p>
        <input type="submit" name="wetuts_submit" value="<?php echo esc_attr($atts['submit']); ?>">
    </form>
    <?php
    return ob_get_clean();
}

add_shortcode('wetuts_contact', 'wetuts_contact_form');