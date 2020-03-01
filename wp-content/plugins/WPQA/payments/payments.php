<?php

/* @author    2codeThemes
*  @package   WPQA/payments
*  @version   1.0
*/

if ( ! defined( 'ABSPATH' ) ) {
   exit; // Exit if accessed directly
}

/* Payments */
if (!function_exists('wpqa_get_payment_coupons')) :
	function wpqa_get_payment_coupons($user_id,$popup = false,$item_id = 0,$days_sticky = 0,$kind_of_payment = "ask_question",$points = 0,$price = 0,$package_name = "",$message = "",$number_vars = 1) {
		$output = '';
		$active_coupons = wpqa_options("active_coupons");
		$coupons = wpqa_options("coupons");
		$free_coupons = wpqa_options("free_coupons");
		$currency_code = wpqa_options("currency_code");
		$currency_code = (isset($currency_code) && $currency_code != ""?$currency_code:"USD");
		if ($kind_of_payment == "answer") {
			$payment_option = "pay_answer_payment";
			$payment_by_points = "payment_type_answer";
			$points_price = "answer_payment_points";
			$payment_description = esc_attr__("Pay to add answer","wpqa");
			$item_number = "pay_answer";
			$return_url = get_the_permalink();
			$item_process = "answer";
		}else if ($kind_of_payment == "sticky") {
			$payment_option = "pay_sticky_payment";
			$payment_by_points = "payment_type_sticky";
			$points_price = "sticky_payment_points";
			$payment_description = esc_attr__("Pay to make question sticky","wpqa");
			$item_number = "pay_sticky";
			$return_url = get_the_permalink();
			$item_process = "sticky";
		}else if ($kind_of_payment == "points") {
			$payment_option = $payment_by_points = $points_price = "";
			$payment_description = esc_attr__("Buy points","wpqa").($package_name != ""?" - ".$package_name:"");
			$item_number = "buy_points";
			$return_url = wpqa_buy_points_permalink();
			$item_process = "points";
		}else {
			$payment_option = "pay_ask_payment";
			$payment_by_points = "payment_type_ask";
			$points_price = "ask_payment_points";
			$wpqa_add_question_user = wpqa_add_question_user();
			if ($wpqa_add_question_user != "") {
				$author_display_name = get_the_author_meta("display_name",$wpqa_add_question_user);
			}
			$payment_description = esc_attr__("Ask a new question","wpqa").($wpqa_add_question_user != ""?" ".esc_attr__("for","wpqa")." ".$author_display_name:"");
			$item_number = ($wpqa_add_question_user != ""?$wpqa_add_question_user:"pay_ask");
			$return_url = ($wpqa_add_question_user != ""?wpqa_add_question_permalink("user",$wpqa_add_question_user):wpqa_add_question_permalink());
			$item_process = "ask";
		}

		$payment_option = apply_filters("wpqa_filter_payment_option",$payment_option);
		$payment_by_points = apply_filters("wpqa_filter_payment_by_points",$payment_by_points);
		$points_price = apply_filters("wpqa_filter_points_price",$points_price);
		$payment_description = apply_filters("wpqa_filter_payment_description",$payment_description);
		$item_number = apply_filters("wpqa_filter_item_number",$item_number);
		$return_url = apply_filters("wpqa_filter_return_url",$return_url);
		$item_process = apply_filters("wpqa_filter_item_process",$item_process);

		$payment_by_points = wpqa_options($payment_by_points);
		if ($payment_by_points == "points") {
			$points_price = floatval(wpqa_options($points_price));
			if ($kind_of_payment == "sticky") {
				$output .= '<div class="alert-message success"><i class="icon-check"></i><p>'.sprintf(esc_html__("Please pay by points to allow to be able to sticky the question %s For %s days.","wpqa"),' "'.$points_price." ".esc_html__("points","wpqa").'"',$days_sticky).'</p></div>';
			}else if ($kind_of_payment == "answer") {
				$output .= '<div class="alert-message success"><i class="icon-check"></i><p>'.sprintf(esc_html__("Please pay by points to allow to be able to add a answer %s.","wpqa"),' "'.$points_price." ".esc_html__("points","wpqa").'"').'</p></div>';
			}else {
				$output .= '<div class="alert-message success"><i class="icon-check"></i><p>'.sprintf(esc_html__("Please pay by points to allow to be able to ask a question %s.","wpqa"),' "'.$points_price." ".esc_html__("points","wpqa").'"').'</p></div>';
			}
			if ($user_id > 0) {
				$points_user = (int)get_user_meta($user_id,"points",true);
				if ($points_price <= $points_user) {
					$output .= '<div class="process_area">
						<form method="post" action="'.$return_url.'">
							<input type="submit" class="button" value="'.esc_attr__("Process","wpqa").'">
							<input type="hidden" name="process" value="'.$item_process.'">
							<input type="hidden" name="points" value="'.$points_price.'">';
							if ($item_id > 0) {
								$output .= '<input type="hidden" name="post_id" value="'.$item_id.'">';
							}
						$output .= '</form>
					</div>';
				}else {
					$buy_points_payment = wpqa_options("buy_points_payment");
					$output .= '<div class="alert-message error"><i class="icon-cancel"></i><p>'.esc_html__("Sorry, you haven't enough points","wpqa").($buy_points_payment == "on"?', <a href="'.wpqa_buy_points_permalink().'">'.esc_html__("You can buy the points from here.","wpqa").'</a>':'.').'</p></div>';
				}
			}
		}else {
			$pay_payment = $last_payment = floatval($price > 0?$price:wpqa_options($payment_option));
			if ($active_coupons == "on") {
				if (isset($_POST["add_coupon"]) && $_POST["add_coupon"] == "submit") {
					$coupon_name = (isset($_POST["coupon_name"])?esc_attr($_POST["coupon_name"]):"");
					$coupons_not_exist = "no";
					
					if ($coupon_name == "") {
						$output .= '<div class="alert-message error"><i class="icon-cancel"></i><p>'.esc_html__("Please enter your coupon.","wpqa").'</p></div>';
					}else if (isset($coupons) && is_array($coupons)) {
						foreach ($coupons as $coupons_k => $coupons_v) {
							if (is_array($coupons_v) && in_array($coupon_name,$coupons_v)) {
								$coupons_not_exist = "yes";
								
								if (isset($coupons_v["coupon_date"]) && $coupons_v["coupon_date"] != "") {
									$coupons_v["coupon_date"] = !is_numeric($coupons_v["coupon_date"]) ? strtotime($coupons_v["coupon_date"]):$coupons_v["coupon_date"];
								}
								
								if (isset($coupons_v["coupon_date"]) && $coupons_v["coupon_date"] != "" && current_time( 'timestamp' ) > $coupons_v["coupon_date"]) {
									$output .= '<div class="alert-message error"><i class="icon-cancel"></i><p>'.esc_html__("This coupon has expired.","wpqa").'</p></div>';
								}else if (isset($coupons_v["coupon_type"]) && $coupons_v["coupon_type"] == "percent" && (int)$coupons_v["coupon_amount"] > 100) {
									$output .= '<div class="alert-message error"><i class="icon-cancel"></i><p>'.esc_html__("This coupon is not valid.","wpqa").'</p></div>';
								}else if (isset($coupons_v["coupon_type"]) && $coupons_v["coupon_type"] == "discount" && (int)$coupons_v["coupon_amount"] > $pay_payment) {
									$output .= '<div class="alert-message error"><i class="icon-cancel"></i><p>'.esc_html__("This coupon is not valid.","wpqa").'</p></div>';
								}else {
									if (isset($coupons_v["coupon_type"]) && $coupons_v["coupon_type"] == "percent") {
										$the_discount = ($pay_payment*$coupons_v["coupon_amount"])/100;
										$last_payment = $pay_payment-$the_discount;
									}else if (isset($coupons_v["coupon_type"]) && $coupons_v["coupon_type"] == "discount") {
										$last_payment = $pay_payment-$coupons_v["coupon_amount"];
									}
									$output .= '<div class="alert-message success"><i class="icon-check"></i><p>'.sprintf(esc_html__('Coupon "%s" applied successfully.','wpqa'),$coupon_name).'</p></div>';
									
									update_user_meta($user_id,$user_id."_coupon",esc_attr($coupons_v["coupon_name"]));
									update_user_meta($user_id,$user_id."_coupon_value",($last_payment <= 0?"free":$last_payment));
								}
							}
						}
						
						if ($coupons_not_exist == "no") {
							$output .= '<div class="alert-message error"><i class="icon-cancel"></i><p>'.esc_html__("Coupon does not exist!.","wpqa").'</p></div>';
						}else if ($coupons_not_exist == "no") {
							$output .= '<div class="alert-message error"><i class="icon-cancel"></i><p>'.sprintf(esc_html__('Coupon "%s" does not exist!.','wpqa'),$coupon_name).'</p></div>';
						}
					}
				}else {
					delete_user_meta($user_id,$user_id."_coupon");
					delete_user_meta($user_id,$user_id."_coupon_value");
				}
			}

			$payment_with_currency = ' "'.$last_payment." ".$currency_code.'"';
			if ($number_vars == 2) {
				if ($kind_of_payment == "sticky") {
					$last_message = sprintf($message,$payment_with_currency,$days_sticky);
				}else {
					$last_message = sprintf($message,$points,$payment_with_currency);
				}
			}else {
				$last_message = sprintf($message,$payment_with_currency);
			}
			//$message = sprintf(esc_html__("Please make a payment to allow to be able to add a annswer %s.","wpqa"),' "'.$last_payment." ".$currency_code.'"');
			$output .= '<div class="alert-message success"><i class="icon-check"></i><p>'.$last_message.'</p></div>';
			
			if (isset($coupons) && is_array($coupons) && $free_coupons == "on" && $active_coupons == "on") {
				foreach ($coupons as $coupons_k => $coupons_v) {
					$pay_payments = $last_payments = floatval($price > 0?$price:wpqa_options($payment_option));
					if (isset($coupons_v["coupon_type"]) && $coupons_v["coupon_type"] == "percent") {
						$the_discount = ($pay_payments*$coupons_v["coupon_amount"])/100;
						$last_payments = $pay_payments-$the_discount;
					}else if (isset($coupons_v["coupon_type"]) && $coupons_v["coupon_type"] == "discount") {
						$last_payments = $pay_payments-$coupons_v["coupon_amount"];
					}
					
					if ($last_payments <= 0) {
						if (isset($coupons_v["coupon_date"]) && $coupons_v["coupon_date"] != "") {
							$coupons_v["coupon_date"] = !is_numeric($coupons_v["coupon_date"]) ? strtotime($coupons_v["coupon_date"]):$coupons_v["coupon_date"];
						}
						if (isset($coupons_v["coupon_type"]) && ($coupons_v["coupon_type"] == "percent" && (int)$coupons_v["coupon_amount"] >= 100  && (isset($coupons_v["coupon_date"]) && ($coupons_v["coupon_date"] == "" || ($coupons_v["coupon_date"] != "" && strtotime(date("M j, Y") <= $coupons_v["coupon_date"]))))) || ($coupons_v["coupon_type"] == "discount" && (int)$coupons_v["coupon_amount"] >= $pay_payments && (isset($coupons_v["coupon_date"]) && ($coupons_v["coupon_date"] == "" || ($coupons_v["coupon_date"] != "" && strtotime(date("M j, Y") <= $coupons_v["coupon_date"])))))) {
							$output .= '<div class="alert-message"><i class="icon-lamp"></i><p>'.sprintf(esc_html__("Ask a free question? Add this coupon %s.","wpqa"),' "'.$coupons_v["coupon_name"].'"').'</p></div>';
						}
					}
				}
			}
			
			if ($active_coupons == "on") {
				$output .= '<div class="coupon_area">
					<form method="post">
						<input type="text" name="coupon_name" id="coupon_name" value="" placeholder="'.esc_attr__("Coupon code","wpqa").'">
						<input type="submit" class="button-default" value="'.esc_attr__("Apply Coupon","wpqa").'">';
						if ($kind_of_payment == "points") {
							$output .= '<input type="hidden" name="package_points" value="'.esc_attr($points).'">';
						}
						if ($popup == "popup") {
							$output .= '<input type="hidden" name="form_type" value="add_question">
							<input type="hidden" name="question_popup" value="popup">';
						}
						$output .= '<input type="hidden" name="add_coupon" value="submit">
					</form>
				</div>';
			}
			
			$output .= '<div class="clearfix"></div>';
			if ($last_payment > 0) {
				$output .= '<div class="payment_area">
					<form method="post" action="?action=process">
						'.($kind_of_payment == "answer"?'<input type="hidden" name="question_answer" value="'.$item_id.'">':'').'
						'.($kind_of_payment == "sticky"?'<input type="hidden" name="question_sticky" value="'.$item_id.'">':'').'
						'.($kind_of_payment != "answer" && $kind_of_payment != "sticky" && $item_id != ""?'<input type="hidden" name="payment_item" value="'.$item_id.'">':'').'
						'.($points > 0?'<input type="hidden" name="buy_points" value="'.$points.'">':'').'
						<input type="hidden" name="CatDescription" value="'.$payment_description.'">
						<input type="hidden" name="item_number" value="'.$item_number.'">
						<input type="hidden" name="payment" value="'.$last_payment.'">
						<input type="hidden" name="quantity" value="1" />
						<input type="hidden" name="key" value="'.md5(date("Y-m-d:").rand()).'">
						<input type="hidden" name="go" value="paypal">
						<input type="hidden" name="action" value="process">
						<input type="hidden" name="currency_code" value="'.$currency_code.'">
						'.(isset($coupon_name) && $coupon_name != ''?'<input type="hidden" name="coupon" value="'.$coupon_name.'">':'').'
						<input type="hidden" name="cpp_header_image" value="'.plugin_dir_url(dirname(__FILE__)).'images/payment.png">
						<input type="image" src="'.plugin_dir_url(dirname(__FILE__)).'images/payment.png" border="0" name="submit" alt="'. esc_attr__("Pay now","wpqa").'">
					</form>
				</div>';
			}else {
				$wpqa_find_coupons = wpqa_find_coupons($coupons,(isset($_POST["coupon_name"])?esc_html($_POST["coupon_name"]):""));
				$output .= '<div class="process_area">
					<form method="post" action="'.$return_url.'">
						<input type="submit" class="button" value="'.esc_attr__("Process","wpqa").'">
						<input type="hidden" name="process" value="'.$item_process.'">';
						if ($item_process == "points") {
							$output .= '<input type="hidden" name="package_points" value="'.esc_attr($_POST["package_points"]).'">';
						}
						if ($wpqa_find_coupons != "" && $active_coupons == "on" && isset($_POST["coupon_name"])) {
							$output .= '<input type="hidden" name="coupon" value="'.esc_attr($_POST["coupon_name"]).'">';
						}
					$output .= '</form>
				</div>';
			}
		}
		return $output;
	}
endif;
/* Do payments */
add_action("wpqa_do_payments","wpqa_do_payments");
if (!function_exists('wpqa_do_payments')):
	function wpqa_do_payments() {
		$pay_ask = wpqa_options('pay_ask');
		$pay_to_sticky = wpqa_options('pay_to_sticky');
		$buy_points_payment = wpqa_options('buy_points_payment');
		$pay_to_answer = wpqa_options('pay_to_answer');
		$pay_to_anything = apply_filters("wpqa_filter_pay_to_anything",false);
		if ($pay_ask == "on" || $pay_to_sticky == "on" || $buy_points_payment == "on" || $pay_to_answer == "on" || $pay_to_anything == true) {
			require_once plugin_dir_path(dirname(__FILE__)).'payments/paypal.class.php';
			$p = new paypal_class;
			$paypal_sandbox = wpqa_options('paypal_sandbox');
			if ($paypal_sandbox == "on") {
				$p->paypal_url = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
			}else {
				$p->paypal_url = 'https://www.paypal.com/cgi-bin/webscr';
			}
			$protocol    = is_ssl() ? 'https' : 'http';
			$this_script = $protocol.'://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];
			$user_id     = get_current_user_id();
			switch ((isset($_REQUEST['action'])?$_REQUEST['action']:"")) {
				case 'process':
					if (isset($_POST["go"]) && $_POST["go"] == "paypal") {
						$payment_item    = (isset($_REQUEST['payment_item']) && $_REQUEST['payment_item'] != ""?(int)$_REQUEST['payment_item']:"");
						$question_answer = (isset($_REQUEST['question_answer']) && $_REQUEST['question_answer'] != ""?(int)$_REQUEST['question_answer']:"");
						$question_sticky = (isset($_REQUEST['question_sticky']) && $_REQUEST['question_sticky'] != ""?(int)$_REQUEST['question_sticky']:"");
						$buy_points      = (isset($_REQUEST['buy_points']) && $_REQUEST['buy_points'] != ""?(int)$_REQUEST['buy_points']:"");
						$CatDescription  = (isset($_REQUEST['CatDescription']) && $_REQUEST['CatDescription'] != ""?esc_attr($_REQUEST['CatDescription']):"");
						$item_no         = (isset($_REQUEST['item_number']) && $_REQUEST['item_number'] != ""?esc_attr($_REQUEST['item_number']):"");
						$payment         = (isset($_REQUEST['payment']) && $_REQUEST['payment'] != ""?esc_attr($_REQUEST['payment']):"");
						$key             = (isset($_REQUEST['key']) && $_REQUEST['key'] != ""?esc_attr($_REQUEST['key']):"");
						$quantity        = (isset($_REQUEST['quantity']) && $_REQUEST['quantity'] != ""?esc_attr($_REQUEST['quantity']):"");
						$coupon          = (isset($_REQUEST['coupon']) && $_REQUEST['coupon'] != ""?esc_attr($_REQUEST['coupon']):"");
						$currency_code   = (isset($_REQUEST['currency_code']) && $_REQUEST['currency_code'] != ""?esc_attr($_REQUEST['currency_code']):"");
						
						echo '<div class="alert-message success"><i class="icon-check"></i><p>'.esc_html__("Please wait will go to the PayPal now to pay a new payment.","wpqa").'</p></div>';
						
						if ($payment_item != "") {
							update_user_meta($user_id,"payment_item",$payment_item);
						}

						if ($question_answer != "") {
							update_user_meta($user_id,"question_answer",$question_answer);
						}

						if ($question_sticky != "") {
							update_user_meta($user_id,"question_sticky",$question_sticky);
						}

						if ($buy_points != "") {
							update_user_meta($user_id,"buy_points",$buy_points);
						}
						
						$p->add_field('business', wpqa_options('paypal_email'));
						$p->add_field('return', $this_script.'?action=success');
						$p->add_field('cancel_return', $this_script.'?action=cancel');
						$p->add_field('notify_url', $this_script.'?action=ipn');
						$p->add_field('item_name', $CatDescription);
						$p->add_field('item_number', $item_no);
						$p->add_field('amount', $payment);
						$p->add_field('key', $key);
						$p->add_field('quantity', $quantity);
						$p->add_field('currency_code', $currency_code);
						
						$p->submit_paypal_post();
					}else {
						wp_safe_redirect(esc_url(home_url('/')));
					}
					get_footer();
					die();
				break;
				case 'success':
					if ((isset($_REQUEST['txn_id']) && $_REQUEST['txn_id'] != "") || isset($_REQUEST['tx']) && $_REQUEST['tx'] != "") {
						$data = wp_remote_post($p->paypal_url.'?cmd=_notify-synch&tx='.(isset($_REQUEST['tx'])?$_REQUEST['tx']:(isset($_REQUEST['txn_id'])?$_REQUEST['txn_id']:'')).'&at='.wpqa_options("identity_token"));
						if (!is_wp_error($data)) {
							$data = $data['body'];
							$response = substr($data, 7);
							$response = urldecode($response);
							
							preg_match_all('/^([^=\s]++)=(.*+)/m', $response, $m, PREG_PATTERN_ORDER);
							$response = array_combine($m[1], $m[2]);
							
							if (isset($response['charset']) && strtoupper($response['charset']) !== 'UTF-8') {
								foreach ($response as $key => &$value) {
									$value = mb_convert_encoding($value, 'UTF-8', $response['charset']);
								}
								$response['charset_original'] = $response['charset'];
								$response['charset'] = 'UTF-8';
							}
							
							ksort($response);
						}else {
							wp_safe_redirect(esc_url(home_url('/')));
							die();
						}
						
						$item_transaction = (isset($response['txn_id'])?esc_attr($response['txn_id']):"");
						$last_payments    = get_user_meta($user_id,$user_id."_last_payments",true);
						if ($item_transaction != "") {
							if (isset($last_payments) && $last_payments == $item_transaction) {
								wp_safe_redirect(esc_url(home_url('/')));
								die();
							}else {
								$item_no       = (isset($response['item_number'])?esc_attr($response['item_number']):"");
								$item_price    = (isset($response['mc_gross'])?esc_attr($response['mc_gross']):"");
								$item_currency = (isset($response['mc_currency'])?esc_attr($response['mc_currency']):"");
								$payer_email   = (isset($response['payer_email'])?esc_attr($response['payer_email']):"");
								$first_name    = (isset($response['first_name'])?esc_attr($response['first_name']):"");
								$last_name     = (isset($response['last_name'])?esc_attr($response['last_name']):"");
								$item_name     = (isset($response['item_name'])?esc_attr($response['item_name']):"");
								
								/* Coupon */
								$_coupon = get_user_meta($user_id,$user_id."_coupon",true);
								$_coupon_value = get_user_meta($user_id,$user_id."_coupon_value",true);
								
								/* Number of my payments */
								$_payments = get_user_meta($user_id,$user_id."_payments",true);
								if ($_payments == "" || !$_payments) {
									$_payments = 0;
								}
								$_payments++;
								update_user_meta($user_id,$user_id."_payments",$_payments);
								
								add_user_meta($user_id,$user_id."_payments_".$_payments,
									array(
										"date_years" => date_i18n('Y/m/d',current_time('timestamp')),
										"date_hours" => date_i18n('g:i a',current_time('timestamp')),
										"item_number" => $item_no,
										"item_name" => $item_name,
										"item_price" => $item_price,
										"item_currency" => $item_currency,
										"item_transaction" => $item_transaction,
										"payer_email" => $payer_email,
										"first_name" => $first_name,
										"last_name" => $last_name,
										"user_id" => $user_id,
										"sandbox" => ($paypal_sandbox == "on"?"sandbox":""),
										"time" => current_time('timestamp'),
										"coupon" => $_coupon,
										"coupon_value" => $_coupon_value
									)
								);
								
								/* New */
								$new_payments = get_option("new_payments");
								if ($new_payments == "" || !$new_payments) {
									$new_payments = 0;
								}
								$new_payments++;
								$update = update_option('new_payments',$new_payments);
								
								/* Money i'm paid */
								$_all_my_payment = get_user_meta($user_id,$user_id."_all_my_payment_".$item_currency,true);
								if($_all_my_payment == "" || $_all_my_payment == 0 || !$_all_my_payment) {
									$_all_my_payment = 0;
								}
								update_user_meta($user_id,$user_id."_all_my_payment_".$item_currency,$_all_my_payment+$item_price);
								
								update_user_meta($user_id,$user_id."_last_payments",$item_transaction);
								
								/* All the payments */
								$payments_option = get_option("payments_option");
								if ($payments_option == "" && !$payments_option) {
									$payments_option = 0;
								}
								$payments_option++;
								update_option("payments_option",$payments_option);
								
								add_option("payments_".$payments_option,
									array(
										"date_years" => date_i18n('Y/m/d',current_time('timestamp')),
										"date_hours" => date_i18n('g:i a',current_time('timestamp')),
										"item_number" => $item_no,
										"item_name" => $item_name,
										"item_price" => $item_price,
										"item_currency" => $item_currency,
										"item_transaction" => $item_transaction,
										"payer_email" => $payer_email,
										"first_name" => $first_name,
										"last_name" => $last_name,
										"user_id" => $user_id,
										"sandbox" => ($paypal_sandbox == "on"?"sandbox":""),
										"time" => current_time('timestamp'),
										"coupon" => $_coupon,
										"coupon_value" => $_coupon_value,
										"payment_new" => 1,
										"payment_item" => $payments_option
									)
								);
								
								delete_user_meta($user_id,$user_id."_coupon",true);
								delete_user_meta($user_id,$user_id."_coupon_value",true);
								
								/* All money */
								$all_money = get_option("all_money_".$item_currency);
								if($all_money == "" || !$all_money || $all_money == 0) {
									$all_money = 0;
								}
								update_option("all_money_".$item_currency,$all_money+$item_price);
								
								/* The currency */
								$the_currency = get_option("the_currency");
								if (is_string($the_currency) || (is_array($the_currency) && empty($the_currency))) {
									delete_option("the_currency");
									add_option("the_currency",array("USD"));
									$the_currency = get_option("the_currency");
								}
								$the_currency = (is_array($the_currency)?$the_currency:array());
								if (!in_array($item_currency,$the_currency)) {
									array_push($the_currency,$item_currency);
								}
								update_option("the_currency",$the_currency);
								
								$another_way_payment_filter = apply_filters("wpqa_another_way_payment_filter",true,array("user_id" => $user_id,"item_transaction" => $item_transaction,"item_price" => $item_price,"item_currency" => $item_currency,"payer_email" => $payer_email,"first_name" => $first_name,"last_name" => $last_name));
								if ($another_way_payment_filter == true) {
									if ($item_no == "pay_answer") {
										$_question_answer = get_user_meta($user_id,"question_answer",true);
										/* Number allow to add answer */
										$_allow_to_answer = (int)get_user_meta($user_id,$user_id."_allow_to_answer",true);
										if ($_allow_to_answer == "" || $_allow_to_answer < 0) {
											$_allow_to_answer = 0;
										}
										$_allow_to_answer++;
										update_user_meta($user_id,$user_id."_allow_to_answer",$_allow_to_answer);
										
										/* Paid answer */
										update_user_meta($user_id,"_paid_answer","paid");
										delete_user_meta($user_id,"question_answer");
									}else if ($item_no == "pay_sticky") {
										$_question_sticky = get_user_meta($user_id,"question_sticky",true);
										update_post_meta($_question_sticky,"sticky",1);
										$sticky_questions = get_option('sticky_questions');
										if (is_array($sticky_questions)) {
											if (!in_array($_question_sticky,$sticky_questions)) {
												$array_merge = array_merge($sticky_questions,array($_question_sticky));
												update_option("sticky_questions",$array_merge);
											}
										}else {
											update_option("sticky_questions",array($_question_sticky));
										}
										$sticky_posts = get_option('sticky_posts');
										if (is_array($sticky_posts)) {
											if (!in_array($_question_sticky,$sticky_posts)) {
												$array_merge = array_merge($sticky_posts,array($_question_sticky));
												update_option("sticky_posts",$array_merge);
											}
										}else {
											update_option("sticky_posts",array($_question_sticky));
										}
										$days_sticky = (int)wpqa_options("days_sticky");
										$days_sticky = ($days_sticky > 0?$days_sticky:7);
										update_post_meta($_question_sticky,"start_sticky_time",strtotime(date("Y-m-d")));
										update_post_meta($_question_sticky,"end_sticky_time",strtotime(date("Y-m-d",strtotime(date("Y-m-d")." +$days_sticky days"))));
										delete_user_meta($user_id,"question_sticky");
									}else if ($item_no == "buy_points") {
										$buy_points = (int)get_user_meta($user_id,"buy_points",true);
										wpqa_add_points($user_id,$buy_points,"+","buy_points");
										delete_user_meta($user_id,"buy_points");
									}else {
										/* Number allow to ask question */
										$_allow_to_ask = (int)get_user_meta($user_id,$user_id."_allow_to_ask",true);
										if ($_allow_to_ask == "" || $_allow_to_ask < 0) {
											$_allow_to_ask = 0;
										}
										$_allow_to_ask++;
										update_user_meta($user_id,$user_id."_allow_to_ask",$_allow_to_ask);
										
										/* Paid question */
										update_user_meta($user_id,"_paid_question","paid");
									}
									
									if ($item_no == "pay_sticky") {
										update_post_meta($_question_sticky, 'item_transaction_sticky', $item_transaction);
										if ($paypal_sandbox == "on") {
											update_post_meta($_question_sticky, 'paypal_sandbox_sticky', 'sandbox');
										}
									}else {
										update_user_meta($user_id,"item_transaction",$item_transaction);
										if ($paypal_sandbox == "on") {
											update_user_meta($user_id,"paypal_sandbox","sandbox");
										}
									}

									if ($item_no == "pay_answer") {
										$payment_success = esc_html__("Thank you for your payment you can now add a new answer","wpqa");
									}else if ($item_no == "pay_sticky") {
										$payment_success = esc_html__("Thank you for your payment, Your question now is sticky","wpqa");
									}else if ($item_no == "buy_points") {
										$payment_success = esc_html__("Thank you for your payment, Your points was added now","wpqa");
									}else {
										$payment_success = esc_html__("Thank you for your payment you can now add a new question","wpqa");
									}
									
									echo '<div class="alert-message success"><i class="icon-check"></i><p>'.$payment_success.'.</p></div>';
									$send_text = wpqa_send_email(wpqa_options("email_new_payment"),"","","","","",$item_price,$item_currency,$payer_email,$first_name,$last_name,$item_transaction,date('m/d/Y'),date('g:i A'));
									$last_message_email = wpqa_email_code($send_text);
									$email_title = wpqa_options("title_new_payment");
									$email_title = ($email_title != ""?$email_title:esc_html__("Instant Payment Notification - Received Payment","wpqa"));
									$email_template = wpqa_options("email_template");
									$mail_smtp = wpqa_options("mail_smtp");
									$email_template = ($mail_smtp == "on"?wpqa_options("mail_username"):$email_template);
									wpqa_sendEmail($email_template,get_bloginfo('name'),wpqa_options("email_template_to"),$first_name,$email_title,$last_message_email);
									if ($payer_email != $email_template) {
										$unsubscribe_mails = get_the_author_meta('unsubscribe_mails',$user_id);
										$new_payment_mail = get_the_author_meta('new_payment_mail',$user_id);
										if ($unsubscribe_mails != "on" || ($new_payment_mail == "on" || $new_payment_mail == "")) {
											wpqa_sendEmail($email_template,get_bloginfo('name'),$payer_email,$first_name,$email_title,$last_message_email);
										}
									}
									wpqa_session('<div class="alert-message success"><i class="icon-check"></i><p>'.$payment_success.', '.sprintf(esc_html__("Your transaction id %s, Please copy it.","wpqa"),$item_transaction).'</p></div>','wpqa_session');
									if ($item_no == "" || $item_no == "pay_ask" || is_numeric($item_no)) {
										if (is_numeric($item_no)) {
											wp_safe_redirect(esc_url(wpqa_add_question_permalink("user",$item_no)));
										}else {
											wp_safe_redirect(esc_url(wpqa_add_question_permalink()));
										}
									}else if (isset($_question_answer) && $_question_answer != "") {
										wp_safe_redirect(esc_url(get_the_permalink($_question_answer)));
									}else if (isset($_question_sticky) && $_question_sticky != "") {
										wp_safe_redirect(esc_url(get_the_permalink($_question_sticky)));
									}else if ($item_no == "buy_points") {
										wp_safe_redirect(esc_url(wpqa_get_profile_permalink($user_id,"points")));
									}else {
										wp_safe_redirect(esc_url(home_url('/')));
									}
								}
								die();
							}
						}else {
							echo '<div class="alert-message error"><i class="icon-cancel"></i><p>'.esc_html__("The payment was failed!","wpqa").'</p></div>';
						}
					}else {
						wp_safe_redirect(esc_url(home_url('/')));
						die();
					}
				break;
				case 'cancel':
					echo '<div class="alert-message error"><i class="icon-cancel"></i><p>'.esc_html__("The payment was canceled!","wpqa").'</p></div>';
				break;
				case 'ipn':
					if ($p->validate_ipn()) { 
						$dated = date("D, d M Y H:i:s", time()); 
						
						$subject  = 'Instant Payment Notification - Received Payment';
						$body     =  "An instant payment notification was successfully recieved\n";
						$body    .= "from ".esc_attr($p->ipn_data['payer_email'])." on ".date('m/d/Y');
						$body    .= " at ".date('g:i A')."\n\nDetails:\n";
						$headers  = "";
						$headers .= "From: Paypal \r\n";
						$headers .= "Date: $dated \r\n";
						
						$PaymentStatus =  esc_attr($p->ipn_data['payment_status']);
						$Email         =  esc_attr($p->ipn_data['payer_email']);
						$id            =  esc_attr($p->ipn_data['item_number']);
						
						if($PaymentStatus == 'Completed' or $PaymentStatus == 'Pending') {
							$PaymentStatus = '2';
						}else {
							$PaymentStatus = '1';
						}
						mail(get_bloginfo("admin_email"), $subject, $body, $headers);
					}
				break;
			}
		}
	}
endif;
/* Coupon valid */
if (!function_exists('wpqa_coupon_valid')) :
	function wpqa_coupon_valid ($coupons,$coupon_name,$coupons_not_exist,$pay_ask_payment,$what_return = '') {
		if (isset($coupons) && is_array($coupons)) {
			foreach ($coupons as $coupons_k => $coupons_v) {
				if (is_array($coupons_v) && in_array($coupon_name,$coupons_v)) {
					if ($what_return == "coupons_not_exist") {
						return "yes";
					}
					if (isset($coupons_v["coupon_date"]) && $coupons_v["coupon_date"] !="" && $coupons_v["coupon_date"] < date_i18n('m/d/Y',current_time('timestamp'))) {
						return '<div class="alert-message error"><i class="icon-cancel"></i><p>'.esc_html__("This coupon has expired.","wpqa").'</p></div>';
					}else if (isset($coupons_v["coupon_type"]) && $coupons_v["coupon_type"] == "percent") {
						if ((int)$coupons_v["coupon_amount"] > 100) {
							return '<div class="alert-message error"><i class="icon-cancel"></i><p>'.esc_html__("This coupon is not valid.","wpqa").'</p></div>';
						}else {
							$the_discount = ($pay_ask_payment*$coupons_v["coupon_amount"])/100;
							$last_payment = $pay_ask_payment-$the_discount;
							if ($what_return == "last_payment") {
								return $last_payment;
							}
						}
					}else if (isset($coupons_v["coupon_type"]) && $coupons_v["coupon_type"] == "discount") {
						if ((int)$coupons_v["coupon_amount"] > $pay_ask_payment) {
							return '<div class="alert-message error"><i class="icon-cancel"></i><p>'.esc_html__("This coupon is not valid.","wpqa").'</p></div>';
						}else {
							$last_payment = $pay_ask_payment-$coupons_v["coupon_amount"];
							if ($what_return == "last_payment") {
								return $last_payment;
							}
						}
					}else {
						return '<div class="alert-message success"><i class="icon-check"></i><p>'.esc_html__("Coupon code applied successfully.","wpqa").'</p></div>';
					}
				}
			}
		}
	}
endif;
/* Find coupons */
if (!function_exists('wpqa_find_coupons')) :
	function wpqa_find_coupons($coupons,$coupon_name) {
		if (isset($coupons) && is_array($coupons)) {
			foreach ($coupons as $coupons_k => $coupons_v) {
				if (is_array($coupons_v) && ((isset($coupons_v["coupon_name"]) && $coupons_v["coupon_name"] == $coupon_name)) || in_array($coupon_name,$coupons_v)) {
					return $coupons_k;
				}
			}
		}
		return false;
	}
endif;
/* Payments */
if (!function_exists('wpqa_payments')) :
	function wpqa_payments () {?>
		<div class="wrap">
			<h1><?php esc_html_e("Payments","wpqa")?></h1>
			<?php $the_currency = get_option("the_currency");
			if (isset($the_currency) && is_array($the_currency)) {
				echo "<br>".esc_html__("All my money","wpqa")."<br>";
				foreach ($the_currency as $key => $currency) {
					if (isset($currency) && $currency != "") {
						$all_money = get_option("all_money_".$currency);
						echo "<br>".(isset($all_money) && $all_money != ""?$all_money:0)." ".$currency."<br>";
						//$_all_my_payment = get_user_meta(get_current_user_id(),get_current_user_id()."_all_my_payment_".$currency,true);
						//echo " all my payment ".(isset($_all_my_payment) && $_all_my_payment != ""?$_all_my_payment:0)." ".$currency."<br>";
					}
				}
				echo "<br>";
			}
			do_action("wpqa_action_after_all_money")?>
			
			<div class="payments-table-items">
				<?php $_payments = get_option("payments_option");
				$rows_per_page = get_option("posts_per_page");
				for ($payments = 1; $payments <= $_payments; $payments++) {
					$payment_one[] = get_option("payments_".$payments);
				}
				if (isset($payment_one)) {
					update_option("new_payments",0);
					$payment = array_reverse($payment_one);
					$paged = (isset($_GET["paged"])?(int)$_GET["paged"]:1);
					$current = max(1,$paged);
					$pagination_args = array(
						'base'      => @esc_url_raw(add_query_arg('paged','%#%')),
						'total'     => ceil(sizeof($payment)/$rows_per_page),
						'current'   => $current,
						'show_all'  => false,
						'prev_text' => '&laquo; Previous',
						'next_text' => 'Next &raquo;',
					);
						
					$start = ($current - 1) * $rows_per_page;
					$end = $start + $rows_per_page;
					$end = (sizeof($payment) < $end) ? sizeof($payment) : $end;
				}
				
				if (isset($payment_one) && is_array($payment_one) && isset($pagination_args["total"]) && $pagination_args["total"] > 1) {?>
					<div class="tablenav top">
						<div class="tablenav-pages">
							<span class="displaying-num"><?php echo count($payment_one)?> <?php esc_html_e("Payments","wpqa")?></span>
							<span class="pagination-links">
								<?php echo (paginate_links($pagination_args) != ""?paginate_links($pagination_args):"")?>
							</span>
						</div>
						<br class="clear">
					</div>
				<?php }else {
					echo "<br>";
				}?>
				
				<table class="wp-list-table widefat fixed striped ">
					<thead>
						<tr>
							<th class="manage-column" scope="col"><span><?php esc_html_e("Price","wpqa")?> - (<?php esc_html_e("coupon","wpqa")?>)</span></th>
							<th class="manage-column" scope="col"><span><?php esc_html_e("Author","wpqa")?></span></th>
							<th class="manage-column" scope="col"><span><?php esc_html_e("Item","wpqa")?></span></th>
							<th class="manage-column" scope="col"><span><?php esc_html_e("Date","wpqa")?></span></th>
							<th class="manage-column" scope="col"><span><?php esc_html_e("Transaction","wpqa")?></span></th>
							<th class="manage-column" scope="col"><span><?php esc_html_e("Payer email","wpqa")?> - (<?php esc_html_e("sandbox","wpqa")?>)</span></th>
						</tr>
					</thead>
					
					<tbody class="payment-table">
						<?php if (isset($payment_one) && is_array($payment_one) && !empty($payment_one)) {
							for ($i = $start; $i < $end; ++$i) {
								$payment_result = $payment[$i];
								$date_years = (isset($payment_result["date_years"]) && $payment_result["date_years"] != ""?$payment_result["date_years"]:(isset($payment_result[0]) && $payment_result[0] != ""?$payment_result[0]:""));
								$date_hours = (isset($payment_result["date_hours"]) && $payment_result["date_hours"] != ""?$payment_result["date_hours"]:(isset($payment_result[1]) && $payment_result[1] != ""?$payment_result[1]:""));
								$item_number = (isset($payment_result["item_number"]) && $payment_result["item_number"] != ""?$payment_result["item_number"]:(isset($payment_result[2]) && $payment_result[2] != ""?$payment_result[2]:""));
								$item_price = (isset($payment_result["item_price"]) && $payment_result["item_price"] != ""?$payment_result["item_price"]:(isset($payment_result[3]) && $payment_result[3] != ""?$payment_result[3]:""));
								$item_currency = (isset($payment_result["item_currency"]) && $payment_result["item_currency"] != ""?$payment_result["item_currency"]:(isset($payment_result[4]) && $payment_result[4] != ""?$payment_result[4]:""));
								$item_transaction = (isset($payment_result["item_transaction"]) && $payment_result["item_transaction"] != ""?$payment_result["item_transaction"]:(isset($payment_result[5]) && $payment_result[5] != ""?$payment_result[5]:""));
								$payer_email = (isset($payment_result["payer_email"]) && $payment_result["payer_email"] != ""?$payment_result["payer_email"]:(isset($payment_result[6]) && $payment_result[6] != ""?$payment_result[6]:""));
								$first_name = (isset($payment_result["first_name"]) && $payment_result["first_name"] != ""?$payment_result["first_name"]:(isset($payment_result[7]) && $payment_result[7] != ""?$payment_result[7]:""));
								$last_name = (isset($payment_result["last_name"]) && $payment_result["last_name"] != ""?$payment_result["last_name"]:(isset($payment_result[8]) && $payment_result[8] != ""?$payment_result[8]:""));
								$user_id = (isset($payment_result["user_id"]) && $payment_result["user_id"] != ""?$payment_result["user_id"]:(isset($payment_result[9]) && $payment_result[9] != ""?$payment_result[9]:""));
								$sandbox = (isset($payment_result["sandbox"]) && $payment_result["sandbox"] != ""?$payment_result["sandbox"]:(isset($payment_result[10]) && $payment_result[10] != ""?$payment_result[10]:""));
								$time = (isset($payment_result["time"]) && $payment_result["time"] != ""?human_time_diff($payment_result["time"],current_time('timestamp'))." ago":(isset($payment_result[11]) && $payment_result[11] != ""?human_time_diff($payment_result[11],current_time('timestamp'))." ago":$date_years." ".$date_hours));
								$coupon = (isset($payment_result["coupon"]) && $payment_result["coupon"] != ""?$payment_result["coupon"]:(isset($payment_result[12]) && $payment_result[12] != ""?$payment_result[12]:""));
								$coupon_value = (isset($payment_result["coupon_value"]) && $payment_result["coupon_value"] != ""?$payment_result["coupon_value"]:(isset($payment_result[13]) && $payment_result[13] != ""?$payment_result[13]:""));
								$item_name = (isset($payment_result["item_name"]) && $payment_result["item_name"] != ""?$payment_result["item_name"]:"---");?>
								
								<tr<?php echo (isset($payment_result["payment_new"]) && $payment_result["payment_new"] == 1?' class="unapproved"':'')?>>
									<td><?php echo esc_html($item_price)." ".$item_currency.(isset($coupon) && $coupon != ""?" - (".$coupon.")":"")?></td>
									<td><a href="<?php echo wpqa_profile_url((int)$user_id);?>"><strong><?php echo get_the_author_meta("display_name",(int)$user_id)?></strong></a></td>
									<td><?php echo esc_html($item_name)?></td>
									<td><?php echo esc_html($time)?></td>
									<td><?php echo esc_html($item_transaction)?></td>
									<td><?php echo esc_html($payer_email).(isset($sandbox) && $sandbox != ""?" - (".$sandbox.")":"")?></td>
								</tr>
								<?php if (isset($payment_result["payment_new"]) && $payment_result["payment_new"] == 1 && isset($payment_result["payment_item"])) {
									$payment_result["payment_new"] = 0;
									update_option("payments_".$payment_result["payment_item"],$payment_result);
								}
							}
						}else {
							echo '<tr class="no-items"><td class="colspanchange" colspan="6">There are no payments yet.</td></tr>';
						}?>
					</tbody>
				
					<tfoot>
						<tr>
							<th class="manage-column" scope="col"><span><?php esc_html_e("Price","wpqa")?> - (<?php esc_html_e("coupon","wpqa")?>)</span></th>
							<th class="manage-column" scope="col"><span><?php esc_html_e("Author","wpqa")?></span></th>
							<th class="manage-column" scope="col"><span><?php esc_html_e("Item","wpqa")?></span></th>
							<th class="manage-column" scope="col"><span><?php esc_html_e("Date","wpqa")?></span></th>
							<th class="manage-column" scope="col"><span><?php esc_html_e("Transaction","wpqa")?></span></th>
							<th class="manage-column" scope="col"><span><?php esc_html_e("Payer email","wpqa")?> - (<?php esc_html_e("sandbox","wpqa")?>)</span></th>
						</tr>
					</tfoot>
				</table>
					
				<?php if (isset($payment_one) && is_array($payment_one) && isset($pagination_args["total"]) && $pagination_args["total"] > 1) {?>
					<div class="tablenav bottom">
						<div class="tablenav-pages">
							<span class="displaying-num"><?php echo count($payment_one)?> <?php esc_html_e("Payments","wpqa")?></span>
							<span class="pagination-links">
								<?php echo (paginate_links($pagination_args) != ""?paginate_links($pagination_args):"")?>
							</span>
						</div>
						<br class="clear">
					</div>
				<?php }?>
			</div>
		</div>
	<?php }
endif;
?>