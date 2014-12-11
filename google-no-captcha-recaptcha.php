<?php

/**
 *
 * @package   Plugin_Name
 * @author    Narayan Prusty <arsenal.narayan@gmail.com>
 * @license   GPL-2.0+
 * @link      http://qnimate.com
 * @copyright 2014 QNimate
 *
 * @wordpress-plugin
 * Plugin Name:       Google's No Captcha reCaptcha
 * Description:       This plugin adds Google's No Captcha reCaptcha to WordPress comment, login, registration and lost password forms.
 * Version:           1.0
 * Author:            @narayanprusy
 * Author URI:        http://qnimate.com
 * Text Domain:       no-captcha-recaptcha
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

    function add_new_menu_items()
    {
        add_menu_page(
            "reCapatcha Options",
            "reCaptcha Options",
            "manage_options",
            "recaptcha-options",
            "recaptcha_options_page",
            "",
            100
        );

    }

    function recaptcha_options_page()
    {
        ?>
            <div class="wrap">
            <h1>reCaptcha Options</h1>
            <form method="post" action="options.php">
                <?php
                    settings_fields("header_section");
                    do_settings_sections("recaptcha-options");
                    submit_button();                     
                ?>          
            </form>
        </div>
        <?php
    }

    add_action("admin_menu", "add_new_menu_items");

    function display_options()
    {
        add_settings_section("header_section", "Keys", "display_options_content", "recaptcha-options");

        add_settings_field("captcha_site_key", __("Site Key"), "display_captcha_site_key_element", "recaptcha-options", "header_section");
        add_settings_field("captcha_secret_key", __("Secret Key"), "display_captcha_secret_key_element", "recaptcha-options", "header_section");

        register_setting("header_section", "captcha_site_key");
        register_setting("header_section", "captcha_secret_key");
    }

    function display_options_content()
    {
        echo __('<p>You need to <a href="https://www.google.com/recaptcha/admin" rel="external">register you domain</a> and get keys to make this plugin work.</p>');
        echo __("Enter the key details below");
    }
    function display_captcha_site_key_element()
    {
        ?>
            <input type="text" name="captcha_site_key" id="captcha_site_key" value="<?php echo get_option('captcha_site_key'); ?>" />
        <?php
    }
    function display_captcha_secret_key_element()
    {
        ?>
            <input type="text" name="captcha_secret_key" id="captcha_secret_key" value="<?php echo get_option('captcha_secret_key'); ?>" />
        <?php
    }

    add_action("admin_init", "display_options");

add_action("wp_enqueue_scripts", "frontend_recaptcha_script");

function frontend_recaptcha_script()
{
	wp_register_script("recaptcha", "https://www.google.com/recaptcha/api.js");
   	wp_enqueue_script("recaptcha");
}

add_action("comment_form", "display_comment_recaptcha");

function display_comment_recaptcha()
{
	?>
		<style>
			#commentform #submit
			{
				display: none;
			}
		</style>
		<div class="g-recaptcha" data-sitekey="<?php echo get_option('captcha_site_key'); ?>"></div>
		<input name="submit" type="submit" value="Submit Comment">
	<?php
}

add_filter("preprocess_comment", "verify_comment_captcha");

function verify_comment_captcha($commentdata)
{
    if(defined('XMLRPC_REQUEST')) 
    { 
        // don't verify captcha at XMLRPC_REQUESTS
        return $commentdata;
    } 
    else 
    {
        if(isset($_POST['g-recaptcha-response']))
        {
            $recaptcha_secret = get_option('captcha_secret_key');
            $response = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=".$recaptcha_secret."&response=".$_POST['g-recaptcha-response']);
            $response = json_decode($response, true);
            if($response["success"] == true)
            {
                return $commentdata;
            }
            else
            {
                echo __("Bots are not allowed to submit comments.");
                return null;
            }
        }
        else
        {
            echo __("Bots are not allowed to submit comments. If you are not a bot then please enable JavaScript in browser.");
            return null;
        }
    }
}


add_action("login_enqueue_scripts", "login_recaptcha_script");

function login_recaptcha_script()
{
	wp_register_script("recaptcha_login", "https://www.google.com/recaptcha/api.js");
   	wp_enqueue_script("recaptcha_login");
}

add_action("login_form", "display_login_captcha");

function display_login_captcha()
{
    echo '<style type="text/css">
                    #lostpasswordform, #loginform {
                    width: 300px !important;
                    }
                </style>';
	?>
        <br>
		<div class="g-recaptcha" data-sitekey="<?php echo get_option('captcha_site_key'); ?>"></div>
	<?php
}

add_filter("wp_authenticate_user", "verify_login_captcha", 10, 2);

function verify_login_captcha($user, $password)
{
    if(defined('XMLRPC_REQUEST')) 
    {
        return $user;
    } 
    else 
    {
        if(isset($_POST['g-recaptcha-response']))
        {
            $recaptcha_secret = get_option('captcha_secret_key');
            $response = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=".$recaptcha_secret."&response=".$_POST['g-recaptcha-response']);
            $response = json_decode($response, true);
            if($response["success"] == true)
            {
                return $user;
            }
            else
            {
                return new WP_Error("Captcha Invalid", __("<strong>ERROR</strong>: You are a bot"));
            }
        }
        else
        {
            return new WP_Error("Captcha Invalid", __("<strong>ERROR</strong>: You are a bot. If not then enable JavaScript"));
        }
    }	
}

add_action("register_form", "display_register_captcha");

function display_register_captcha()
{
        echo '<style type="text/css">
                    #registerform {
                    width: 300px !important;
                    }
                </style>';
	?>
        <br>
		<div class="g-recaptcha" data-sitekey="<?php echo get_option('captcha_site_key'); ?>"></div>
	<?php
}

add_filter("registration_errors", "verify_registration_captcha", 10, 3);

function verify_registration_captcha($errors, $sanitized_user_login, $user_email)
{
    if ( defined('XMLRPC_REQUEST') ) 
    { 
        // don't verify captcha at XMLRPC_REQUESTS
        return $errors;
    } 
    else 
    {
            // do your code here
        if(isset($_POST['g-recaptcha-response']))
        {
            $recaptcha_secret = get_option('captcha_secret_key');
            $response = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=".$recaptcha_secret."&response=".$_POST['g-recaptcha-response']);
            $response = json_decode($response, true);
            if($response["success"] == true)
            {
                return $errors;
            }
            else
            {
                $errors->add("Captcha Invalid", __("<strong>ERROR</strong>: You are a bot"));
            }
        }
        else
        {
            $errors->add("Captcha Invalid", __("<strong>ERROR</strong>: You are a bot. If not then enable JavaScript"));
        }
    }
		

    return $errors;
}

add_action("lostpassword_form", "display_login_captcha");
add_action("lostpassword_post", "verify_lostpassword_captcha");

function verify_lostpassword_captcha()
{
    if ( defined('XMLRPC_REQUEST') ) 
    {
        return;
    } 
    else 
    {
            // do your code here
        if(isset($_POST['g-recaptcha-response']))
        {
            $recaptcha_secret = get_option('captcha_secret_key');
            $response = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=".$recaptcha_secret."&response=".$_POST['g-recaptcha-response']);
            $response = json_decode($response, true);
            if($response["success"] == true)
            {
                return;
            }
            else
            {
                wp_die(__("<strong>ERROR</strong>: You are a bot"));
            }
        }
        else
        {
            wp_die(__("<strong>ERROR</strong>: You are a bot. If not then enable JavaScript"));
        }
    }	

    return;	
}