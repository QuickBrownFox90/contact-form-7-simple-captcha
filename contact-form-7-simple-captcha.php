<?php

/**
 * Plugin Name:       Contact Form 7 Simple Captcha Extension
 * Plugin URI:        http://dev.patrick-mauersberger.de
 * _Update URI:        github link
 * Description:       A simple "I am not a robot" captcha for Contact Form 7. Required plugin: Contact Form 7
 * Requires Plugins:  contact-form-7
 * Requires PHP:      8.1
 * Requires at least: 6.5
 *
 * Author:            Patrick Mauersberger
 * Author URI:        http://dev.patrick-mauersberger.de
 * Text Domain:       wpcf7sc
 * Domain Path:       /languages
 * Version:           1.0.0
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @version 1.0.0
 * @package contact-form-7-simple-captcha
 */

defined( 'ABSPATH' ) or die( 'What did you think, what you doing here?' );

/**
 * Define the global constants
 * @since  Version 1.0.0
 */

define( 'WPCF7SC_PREFIX', 'wpcf7sc_' );
define( 'WPCF7SC_ROOT', __DIR__ );
define( 'WPCF7SC_UPDATE_URI', 'QuickBrownFox90/contact-form-7-simple-captcha' );

/**
 * Requirements
 * – the real action
 * @since  Version 1.0.0
 */

require_once 'inc/main.php';

use WPCF7_SimpleCaptcha\Main;

$SC = new Main();
