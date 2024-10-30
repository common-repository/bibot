<?php

/**
* The plugin bootstrap file
*
* This file is read by WordPress to generate the plugin information in the plugin
* admin area. This file also includes all of the dependencies used by the plugin,
* registers the activation and deactivation functions, and defines a function
* that starts the plugin.
*
* @link              https://bibot.ir
* @since             1.0.0
* @package           Bibot
*
* @wordpress-plugin
* Plugin Name:       bibot
* Plugin URI:        https://github.com/abreza/wp-bibot
* Description:       Simply protect your WordPress against spam comments and brute-force attacks!
* Version:           1.0.0
* Author:            Morteza Abolghasemi
* Author URI:        https://bibot.ir
* License:           GPL-2.0+
* License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
* Text Domain:       bibot
* Domain Path:       /languages
*/

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if (!defined('ABSPATH')) {
	die( 'Direct access not allowed!' );
}










function bib_add_plugin_action_links($links) {
	return array_merge(array("settings" => "<a href=\"options-general.php?page=bib-options\">".__("Settings", "bibot")."</a>"), $links);
}
add_filter("plugin_action_links_".plugin_basename(__FILE__), "bib_add_plugin_action_links");

function bib_activation($plugin) {
	if ($plugin == plugin_basename(__FILE__) && (!get_option("bib_site_key") || !get_option("bib_secret_key"))) {
		exit(wp_redirect(admin_url("options-general.php?page=bib-options")));
	}
}
add_action("activated_plugin", "bib_activation");








function bib_options_page() {
	echo "<div class=\"wrap\">
	<h1>".__("Bibot Options", "bibot")."</h1>
	<form method=\"post\" action=\"options.php\">";
	settings_fields("bib_header_section");
	do_settings_sections("bib-options");
	submit_button();
	echo "</form>
	</div>";
}

function bib_menu() {
	add_submenu_page("options-general.php", "bibot", "bibot", "manage_options", "bib-options", "bib_options_page");
}
add_action("admin_menu", "bib_menu");


function bib_display_content() {
	echo "<p>".__("You have to <a href=\"https://bibot.ir/panel/user/sign_up/\" rel=\"external\">register your domain</a> first, get required keys from Bibot and save them bellow.", "bibot")."</p>";
}

function bib_display_site_key_element() {
	$bib_site_key = filter_var(get_option("bib_site_key"), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
	echo "<input type=\"text\" name=\"bib_site_key\" class=\"regular-text\" id=\"bib_site_key\" value=\"{$bib_site_key}\" />";
}

function bib_display_secret_key_element() {
	$bib_secret_key = filter_var(get_option("bib_secret_key"), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
	echo "<input type=\"text\" name=\"bib_secret_key\" class=\"regular-text\" id=\"bib_secret_key\" value=\"{$bib_secret_key}\" />";
}

function bib_display_login_check_disable() {
	echo "<input type=\"checkbox\" name=\"bib_login_check_disable\" id=\"bib_login_check_disable\" value=\"1\" ".checked(1, get_option("bib_login_check_disable"), false)." />";
}


function bib_display_options() {
	add_settings_section("bib_header_section", __("What first?", "bibot"), "bib_display_content", "bib-options");

	add_settings_field("bib_site_key", __("Site Key", "bibot"), "bib_display_site_key_element", "bib-options", "bib_header_section");
	add_settings_field("bib_secret_key", __("Secret Key", "bibot"), "bib_display_secret_key_element", "bib-options", "bib_header_section");
	add_settings_field("bib_login_check_disable", __("Disable bibot for login", "bibot"), "bib_display_login_check_disable", "bib-options", "bib_header_section");

	register_setting("bib_header_section", "bib_site_key");
	register_setting("bib_header_section", "bib_secret_key");
	register_setting("bib_header_section", "bib_login_check_disable");
}
add_action("admin_init", "bib_display_options");








function load_language_bib() {
	load_plugin_textdomain("bibot", false, dirname(plugin_basename(__FILE__))."/languages/");
}
add_action("plugins_loaded", "load_language_bib");







function frontend_bib_script() {
	$bib_site_key = filter_var(get_option("bib_site_key"), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
	$bib_display_list = array("comment_form_after_fields", "register_form", "lost_password", "lostpassword_form", "retrieve_password", "resetpass_form", "woocommerce_register_form", "woocommerce_lostpassword_form", "woocommerce_after_order_notes", "bp_after_signup_profile_fields");

	if (!get_option("bib_login_check_disable")) {
		array_push($bib_display_list, "login_form", "woocommerce_login_form");
	}

	foreach($bib_display_list as $bib_display) {
		add_action($bib_display, "bib_display");
	}


	function add_defer_attribute($tag, $handle) {
		if ( 'bibot-captcha' == $handle )
		$tag = str_replace( ' src', ' defer="defer" src', $tag );
		return $tag;

	}
	add_filter('script_loader_tag', 'add_defer_attribute', 10, 2);

	wp_register_script("bibot-captcha", "https://cdn.bibot.ir/bibot.min.js", array(), false, true);
	wp_enqueue_script("bibot-captcha");

	wp_enqueue_style("style", plugin_dir_url(__FILE__)."style.css");
}

function bib_display() {
	$bib_site_key = filter_var(get_option("bib_site_key"), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
	echo "<div class=\"bibot-captcha\" data-sitekey=\"{$bib_site_key}\"></div>";
}

function bib_verify($input) {
	if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['bibot-response']) && !empty($_POST['bibot-response'])){
		$bib_secret_key = filter_var(get_option("bib_secret_key"), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$data = array('response' => $_POST['bibot-response'], 'secretkey' => $bib_secret_key);
		$options = array(
			'body' => $data,
			'timeout' => '5',
			'redirection' => '5',
			'httpversion' => '1.0',
			'blocking' => true,
			'headers' => array(),
			'cookies' => array()
		);
		$response = wp_remote_post('https://api.bibot.ir/api1/siteverify/', $options);
		if(is_wp_error($response)) {
			$error_message = $response->get_error_message();
			return new WP_Error("bibot", "Something went wrong: $error_message");
		}
		$responseData = json_decode(wp_remote_retrieve_body($response));
		if(!empty($responseData) && $responseData->success){
			return $input;
		}
		else {
			return new WP_Error("bibot", "<strong>".__("ERROR:", "bibot")."</strong> ".__("Bibot verification failed.", "bibot"));
		}
	}
	else {
		wp_die("<p><strong>".__("ERROR:", "bibot")."</strong> ".__("Bibot verification failed.", "bibot")." ".__("Do you have JavaScript enabled?", "bibot")."</p>", "bibot", array("response" => 403, "back_link" => 1));
	}
}

function bib_check() {
	if (get_option("bib_site_key") && get_option("bib_secret_key") && !is_user_logged_in() && !function_exists("wpcf7_contact_form_shortcode")) {
		add_action("login_enqueue_scripts", "frontend_bib_script");
		add_action("wp_enqueue_scripts", "frontend_bib_script");

		$bib_verify_list = array("preprocess_comment", "registration_errors", "lostpassword_post", "resetpass_post", "woocommerce_register_post");

		if (!get_option("bib_login_check_disable")) {
			array_push($bib_verify_list, "wp_authenticate_user", "bp_signup_validate");
		}

		foreach($bib_verify_list as $bib_verify) {
			add_action($bib_verify, "bib_verify");
		}
	}
}

add_action("init", "bib_check");
