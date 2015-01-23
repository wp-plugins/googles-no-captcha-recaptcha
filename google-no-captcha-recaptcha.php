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

        function no_captcha_recaptcha_menu()
    {
        add_menu_page( "reCapatcha Options", "reCaptcha Options", "manage_options", "recaptcha-options", "recaptcha_options_page", "", 100 );
    }

    function recaptcha_options_page()
    {
        ?>
            <div class="wrap">
            <h1>reCaptcha Options</h1>
            <form method="post" action="options.php">
                <?php
                    settings_fields( "header_section" );
                    do_settings_sections( "recaptcha-options" );
                    submit_button();                     
                ?>          
            </form>
        </div>
        <?php
    }

    add_action( "admin_menu", "no_captcha_recaptcha_menu" );

    function display_recaptcha_options()
    {
        add_settings_section( "header_section", "Keys", "display_recaptcha_content", "recaptcha-options" );

        add_settings_field( "captcha_site_key", __("Site Key"), "display_captcha_site_key_element", "recaptcha-options", "header_section" );
        add_settings_field( "captcha_secret_key", __("Secret Key"), "display_captcha_secret_key_element", "recaptcha-options", "header_section" );

        register_setting( "header_section", "captcha_site_key" );
        register_setting( "header_section", "captcha_secret_key" );
    }

    function display_recaptcha_content()
    {
        echo __( '<p>You need to <a href="https://www.google.com/recaptcha/admin" rel="external">register you domain</a> and get keys to make this plugin work.</p>' );
        echo __( "Enter the key details below" );
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

    add_action( "admin_init", "display_recaptcha_options" );

    add_action( "wp_enqueue_scripts", "frontend_recaptcha_script" );

    function frontend_recaptcha_script()
    {
        if( get_option( 'captcha_site_key' ) && get_option( 'captcha_secret_key' ) )
        {
            wp_register_script( "recaptcha", "https://www.google.com/recaptcha/api.js" );
            wp_enqueue_script( "recaptcha" );

            $plugin_url = plugin_dir_url( __FILE__ );
            
            wp_enqueue_style( "no-captcha-recaptcha", $plugin_url . "style.css" );
        }   
    }

    add_action( "comment_form", "display_comment_recaptcha" );

    function display_comment_recaptcha()
    {
        if( get_option( 'captcha_site_key' ) && get_option( 'captcha_secret_key' ) )
        {
            ?>
                <div class="g-recaptcha" data-sitekey="<?php echo get_option( 'captcha_site_key' ); ?>"></div>
                <input name="submit" type="submit" value="Submit Comment">
            <?php   
        }
        
    }

    add_filter( "preprocess_comment", "verify_comment_captcha" );

    function verify_comment_captcha( $commentdata )
    {
        if( isset( $_POST['g-recaptcha-response'] ) )
        {
            $recaptcha_secret = get_option( 'captcha_secret_key' );
            $response = file_get_contents( "https://www.google.com/recaptcha/api/siteverify?secret=" . $recaptcha_secret . "&response=" .$_POST['g-recaptcha-response'] );
            $response = json_decode( $response, true );
            if( true == $response["success"] )
            {
                return $commentdata;
            }
            else
            {
                echo __( "Bots are not allowed to submit comments." );
                return null;
            }
        }
        else
        {
            if( get_option( 'captcha_site_key' ) && get_option( 'captcha_secret_key' ) )
            {
                echo __( "Bots are not allowed to submit comments. If you are not a bot then please enable JavaScript in browser." );
                return null;    
            }   
            else
            {
                return $commentdata;
            }
        }
    }

    add_action( "login_enqueue_scripts", "login_recaptcha_script" );

    function login_recaptcha_script()
    {
        if( get_option( 'captcha_site_key' ) && get_option( 'captcha_secret_key' ) )
        {
            wp_register_script( "recaptcha_login", "https://www.google.com/recaptcha/api.js" );
            wp_enqueue_script( "recaptcha_login" );
        }
    }

    add_action( "login_form", "display_login_captcha" );

    function display_login_captcha()
    {
        if( get_option( 'captcha_site_key' ) && get_option( 'captcha_secret_key' ) )
        {
            ?>
                <div class="g-recaptcha" data-sitekey="<?php echo get_option('captcha_site_key' ); ?>"></div>
            <?php
        }   
    }

    add_filter( "wp_authenticate_user", "verify_login_captcha", 10, 2 );

    function verify_login_captcha( $user, $password )
    {
        if( isset( $_POST['g-recaptcha-response'] ) )
        {
            $recaptcha_secret = get_option( 'captcha_secret_key' );
            $response = file_get_contents( "https://www.google.com/recaptcha/api/siteverify?secret=" . $recaptcha_secret . "&response=" . $_POST['g-recaptcha-response'] );
            $response = json_decode( $response, true );
            if( true == $response["success"] )
            {
                return $user;
            }
            else
            {
                return new WP_Error( "Captcha Invalid", __( "<strong>ERROR</strong>: You are a bot" ) );
            } 
        }
        else
        {
            if( get_option( 'captcha_site_key' ) && get_option( 'captcha_secret_key' ) )
            {
                return new WP_Error( "Captcha Invalid", __( "<strong>ERROR</strong>: You are a bot. If not then enable JavaScript" ) );
            }
            else
            {
                return $user;
            }
        }   
    }

    add_action( "register_form", "display_register_captcha" );


    function display_register_captcha()
    {
        if( get_option( 'captcha_site_key' ) && get_option( 'captcha_secret_key' ) )
        {
            ?>
                <div class="g-recaptcha" data-sitekey="<?php echo get_option( 'captcha_site_key' ); ?>"></div>
            <?php   
        }       
    }

    add_filter( "registration_errors", "verify_registration_captcha", 10, 3 );

    function verify_registration_captcha( $errors, $sanitized_user_login, $user_email )
    {
        if( isset( $_POST['g-recaptcha-response'] ) )
        {
            $recaptcha_secret = get_option( 'captcha_secret_key' );
            $response = file_get_contents( "https://www.google.com/recaptcha/api/siteverify?secret=" . $recaptcha_secret . "&response=" . $_POST['g-recaptcha-response'] );
            $response = json_decode( $response, true );
            if( true == $response["success"] )
            {
                return $errors;
            }
            else
            {
                $errors->add( "Captcha Invalid", __( "<strong>ERROR</strong>: You are a bot" ) );
            }
        }
        else
        {   
            if( get_option( 'captcha_site_key' ) && get_option( 'captcha_secret_key' ) )
            {
                $errors->add( "Captcha Invalid", __( "<strong>ERROR</strong>: You are a bot. If not then enable JavaScript" ) );
            }
            else
            {
                return $errors;
            }
            
        }   

        return $errors;
    }


    add_action( "lostpassword_form", "display_login_captcha" );
    add_action( "lostpassword_post", "verify_lostpassword_captcha" );

    function verify_lostpassword_captcha()
    {
        if( isset( $_POST['g-recaptcha-response'] ) )
        {
            $recaptcha_secret = get_option( 'captcha_secret_key' );
            $response = file_get_contents( "https://www.google.com/recaptcha/api/siteverify?secret=" . $recaptcha_secret . "&response=" . $_POST['g-recaptcha-response'] );
            $response = json_decode( $response, true );
            if( true == $response["success"] )
            {
                return;
            }
            else
            {
                wp_die( __( "<strong>ERROR</strong>: You are a bot" ) );
            }
        }
        else
        {
            if( get_option( 'captcha_site_key' ) && get_option( 'captcha_secret_key' ) )
            {
                wp_die( __( "<strong>ERROR</strong>: You are a bot. If not then enable JavaScript" ) ); 
            }
            else
            {
                return;
            }
            
        }   

        return $errors; 
    }