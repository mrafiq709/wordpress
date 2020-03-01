<?php get_header();
	$discy_sidebar_all = $discy_sidebar = (function_exists('discy_sidebars')?discy_sidebars("sidebar_where"):"");
	wpqa_content();
get_footer();?>