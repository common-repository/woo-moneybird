<?php
/**
 * Plugin Name: Integration of Moneybird for WooCommerce 
 * Plugin URI: https://Woocommerce-moneybird.techastha.com
 * Description: Generate invoice using Moneybird API form WooCommerce Order.
 * Author: techastha
 * Author URI: https://techastha.com
 * Text Domain: wcmb
 * Version: 2.1.2
 * Requires at least: 5.2.0
 * Requires PHP: 5.6
 * Domain Path: /languages
 * WC requires at least: 3.6.0
 * WC tested up to: 5.6.0
 *
 * 
 */
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

// Defines the current version of the plugin.
define( 'WCMB_VERSION', '2.1.2' );

// Defines the name of the plugin.
define( 'WCMB_NAME', 'Integration of Moneybird for WooCommerce' );

// Defines the path to the main plugin file.
define( 'WCMB_FILE', __FILE__ );

define( 'WCMB_PLUGIN_BASENAME', dirname( plugin_basename( WCMB_FILE ) ) );

// Defines the path to be used for includes.
define( 'WCMB_DIR_PATH', plugin_dir_path( WCMB_FILE ) );

// Defines the path for plugin directory.
define( 'WCMB_PLUGINS_DIR_PATH', plugin_dir_path( __DIR__ ) );

// Defines the URL to the plugin.
define( 'WCMB_URL', plugin_dir_url( WCMB_FILE ) );

// Defines the path to be used for css/js/images include .
define( 'WCMB_ASSETS_URL', WCMB_URL.'assets/' );

/**
 * Include core plugin classes.
 */
require WCMB_DIR_PATH . 'classes/class-wcmb.php';
$WCMB = new WCMB();   

