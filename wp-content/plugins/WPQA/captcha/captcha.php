<?php

/* @author    2codeThemes
*  @package   WPQA/captcha
*  @version   1.0
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if(!session_id()) session_start();
if (wpqa_server('REQUEST_METHOD') <> "POST") 
	die("You can only reach this page by posting from the html form");

if ((isset($_REQUEST["wpqa_captcha"]) && isset($_SESSION["wpqa_code_captcha_register"]) && $_REQUEST["wpqa_captcha"] == $_SESSION["wpqa_code_captcha_register"]) && !empty($_REQUEST["wpqa_captcha"]) && !empty($_SESSION["wpqa_code_captcha_register"])) OR (isset($_REQUEST["wpqa_captcha"]) && isset($_SESSION["wpqa_code_captcha_login"]) && $_REQUEST["wpqa_captcha"] == $_SESSION["wpqa_code_captcha_login"]) && !empty($_REQUEST["wpqa_captcha"]) && !empty($_SESSION["wpqa_code_captcha_login"])) OR (isset($_REQUEST["wpqa_captcha"]) && isset($_SESSION["wpqa_code_captcha_password"]) && $_REQUEST["wpqa_captcha"] == $_SESSION["wpqa_code_captcha_password"]) && !empty($_REQUEST["wpqa_captcha"]) && !empty($_SESSION["wpqa_code_captcha_password"])) OR (isset($_REQUEST["wpqa_captcha"]) && isset($_SESSION["wpqa_code_captcha_post"]) && $_REQUEST["wpqa_captcha"] == $_SESSION["wpqa_code_captcha_post"]) && !empty($_REQUEST["wpqa_captcha"]) && !empty($_SESSION["wpqa_code_captcha_post"])) OR (isset($_REQUEST["wpqa_captcha"]) && isset($_SESSION["wpqa_code_captcha_category"]) && $_REQUEST["wpqa_captcha"] == $_SESSION["wpqa_code_captcha_category"]) && !empty($_REQUEST["wpqa_captcha"]) && !empty($_SESSION["wpqa_code_captcha_category"])) OR (isset($_REQUEST["wpqa_captcha"]) && isset($_SESSION["wpqa_code_captcha_question"]) && $_REQUEST["wpqa_captcha"] == $_SESSION["wpqa_code_captcha_question"]) && !empty($_REQUEST["wpqa_captcha"]) && !empty($_SESSION["wpqa_code_captcha_question"])) OR (isset($_REQUEST["wpqa_captcha"]) && isset($_SESSION["wpqa_code_captcha_message"]) && $_REQUEST["wpqa_captcha"] == $_SESSION["wpqa_code_captcha_message"]) && !empty($_REQUEST["wpqa_captcha"]) && !empty($_SESSION["wpqa_code_captcha_message"])) OR (isset($_REQUEST["wpqa_captcha"]) && isset($_SESSION["wpqa_code_captcha_comment"]) && $_REQUEST["wpqa_captcha"] == $_SESSION["wpqa_code_captcha_comment"]) && !empty($_REQUEST["wpqa_captcha"]) && !empty($_SESSION["wpqa_code_captcha_comment"])) OR (isset($_REQUEST["wpqa_captcha"]) && isset($_SESSION["wpqa_code_captcha_custom"]) && $_REQUEST["wpqa_captcha"] == $_SESSION["wpqa_code_captcha_custom"]) && !empty($_REQUEST["wpqa_captcha"]) && !empty($_SESSION["wpqa_code_captcha_custom"]))) {
	echo "wpqa_captcha_1";
}else {
	echo "wpqa_captcha_0";
}?>