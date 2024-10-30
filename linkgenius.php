<?php
/*
Plugin Name: LinkGenius
Plugin URI: https://all-affiliates.com/linkgenius/
Description: LinkGenius is a powerful (affiliate) link management plugin. With LinkGenius, you can effortlessly organize, optimize, and track your (affiliate) links, unlocking a new level of efficiency.
Version: 1.2.1
Author: all-affiliates.com
Author URI: https://all-affiliates.com
Domain Path: /languages
Text Domain: linkgenius
License: GPL2
*/

use LinkGenius\CPT;
use LinkGenius\Discloser;
use LinkGenius\Editor;
use LinkGenius\Importer;
use LinkGenius\LinkLocator;
use LinkGenius\Redirect;
use LinkGenius\Settings;
use LinkGenius\Shortcode;

if (!defined('ABSPATH')) {
    exit();
}


if (file_exists(dirname(__FILE__) . '/vendor/autoload.php')) {
    require_once dirname(__FILE__) . '/vendor/autoload.php';
}

// Include necessary files and classes
require_once plugin_dir_path(__FILE__). 'vendor/cmb2/cmb2/init.php';
require_once plugin_dir_path(__FILE__). 'vendor/jcchavezs/cmb2-conditionals/cmb2-conditionals.php';


function linkgenius_init() {
    define('LINKGENIUS_PATH', plugin_dir_path(__FILE__));
    define('LINKGENIUS_URL', plugin_dir_url(dirname(__FILE__)));
    define("LINKGENIUS_TYPE_LINK", "linkgenius_link");
    define("LINKGENIUS_TYPE_CATEGORY", "linkgenius_category");
    define("LINKGENIUS_TYPE_TAG", "linkgenius_tag");
    define("LINKGENIUS_POST_TYPE_SLUG", "edit.php?post_type=".LINKGENIUS_TYPE_LINK);
    define("LINKGENIUS_OPTIONS_PREFIX", "linkgenius_options");
    define("LINKGENIUS_OPTIONS_TAB_GROUP", "linkgenius_options");	

    load_plugin_textdomain('linkgenius', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/');
    if ( !is_textdomain_loaded( 'linkgenius' ) ) {
        // Load the fallback PO file
        load_textdomain( 'linkgenius', LINKGENIUS_PATH. 'languages/linkgenius-fallback.mo' );
    }
    Settings::instance();
    CPT::instance();
    Discloser::instance();
    LinkLocator::instance();
    new Importer();
    new Editor();
    new Redirect();
    new Shortcode();
}
add_action( 'plugins_loaded', 'linkgenius_init' );

// Register activation and deactivation hooks
register_activation_hook(__FILE__, 'plugin_activate');
register_deactivation_hook(__FILE__, 'plugin_deactivate');

// Activate the plugin
function plugin_activate() {
    add_option('linkgenius_should_flush', true);
}

// Deactivate the plugin
function plugin_deactivate() {
    // Perform deactivation tasks if needed
}