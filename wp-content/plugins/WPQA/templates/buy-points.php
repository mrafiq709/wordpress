<?php

/* @author    2codeThemes
*  @package   WPQA/templates
*  @version   1.0
*/

if ( ! defined( 'ABSPATH' ) ) {
   exit; // Exit if accessed directly
}

do_action("wpqa_before_buy_points");?>

<div class='wpqa-buy-points-template'>
	<div class="page-sections">
		<div class="page-section">
			<?php $buy_points_payment = wpqa_options("buy_points_payment");
			if ($buy_points_payment == "on") {
				$user_id = get_current_user_id();
				$buy_points = wpqa_options("buy_points");
				$currency_code = wpqa_options("currency_code");
				$currency_code = (isset($currency_code) && $currency_code != ""?$currency_code:"USD");
				if (isset($buy_points) && is_array($buy_points)) {
					if ($user_id > 0 && isset($_POST["process"]) && $_POST["process"] == "points" && isset($_POST["package_points"])) {
						wpqa_add_points($user_id,(int)$_POST["package_points"],"+","buy_points");
						wpqa_session('<div class="alert-message success"><i class="icon-check"></i><p>'.esc_html__("You have got a new free points.","wpqa").'</p></div>','wpqa_session');
						wp_safe_redirect(esc_url(wpqa_get_profile_permalink($user_id,"points")));
						die();
					}
					echo '<div class="page-wrap-content">
						<h2 class="post-title-3"><i class="icon-trophy"></i>'.esc_html__("Buy points","wpqa").'</h2>
						<div class="points-section buy-points-section">
							<ul class="row">';
								foreach ($buy_points as $key => $value) {
									if ($value > 0) {
										echo '<li class="col col12">
											<div class="point-section">
												<div class="point-div">
													<i class="icon-bucket"></i>
													<span>'.wpqa_count_number($value["package_points"]).'</span>'.esc_html($value["package_name"]).'
													<span class="points-price">'.floatval($value["package_price"]).' '.$currency_code.'</span>
												</div>
												<p>'.wpqa_kses_stip($value["package_description"]).'</p>
												<div class="buy-points-content">
													<a href="#" class="wpqa-open-click button-default">'.esc_html__("Buy points","wpqa").'</a>
													<div class="clearfix"></div>
													<div class="buy-points-div wpqa-open-div'.(isset($_POST["add_coupon"]) && $_POST["add_coupon"] == "submit" && isset($_POST["package_points"]) && $_POST["package_points"] == $value["package_points"]?"":" wpqa_hide").'">';
														if (isset($value["package_points"]) && $value["package_points"] > 0 && isset($value["package_price"]) && $value["package_price"] > 0) {
															echo wpqa_get_payment_coupons($user_id,false,0,0,"points",$value["package_points"],$value["package_price"],$value["package_name"],esc_html__("Please make a payment to buy %s points %s.","wpqa"),2);
														}
													echo '</div>
													<div class="clearfix"></div>
												</div>
											</div>
										</li>';
									}
								}
							echo '</ul>
						</div><!-- End buy-points-section -->
					</div><!-- End page-wrap-content -->';
				}
			}else {
				echo '<div class="alert-message warning"><i class="icon-flag"></i><p>'.esc_html__("Sorry, this page is not available.","wpqa").'</p></div>';
			}?>
		</div><!-- End page-section -->
	</div><!-- End page-sections -->
</div><!-- End wpqa-buy-points-template -->

<?php do_action("wpqa_after_buy_points");?>