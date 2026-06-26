<?php
/*
Plugin Name: Avilio
Plugin URI: soon
Description: A WordPress helper class plugin
Version: 1.2.0
Author: Tio27
Author URI: https://github.com/setioko27
License: GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.txt
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Use Composer autoloader
require_once plugin_dir_path(__FILE__) . '../vendor/autoload.php';

// Initialize your plugin
add_action('plugins_loaded', function() {
    // Your initialization code here
    new \Avilio\Theme();
});