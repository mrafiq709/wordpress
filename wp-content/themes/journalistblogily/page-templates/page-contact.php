<?php
/* 
 * Template Name: Contact
 */

get_header(); ?>

<div id="primary" class="content-area small-12 medium-8 medium-push-4 columns sidebar-left">

    <main id="main" class="site-main" role="main">
        <div class="row col-sm-12">
            <div class="col-sm-12">
                <?php
                echo do_shortcode(
                    '[contact-form-7 title="Contact us now"]'
                );
                ?>
            </div>
        </div>

    </main><!-- #main -->
</div><!-- #primary -->

<?php
get_sidebar();
get_footer();
