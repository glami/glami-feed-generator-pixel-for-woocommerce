<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://www.glami.eco/
 * @since             1.0.0
 * @package           Glami_Feed_Generator_Pixel_For_Woocommerce
 *
 * @wordpress-plugin
 * Plugin Name:       GLAMI feed generator + PiXel for WooCommerce
 * Plugin URI:        https://www.glami.eco/
 * Description:       GLAMI feed generator + PiXel is an extension built for GLAMI, an engine that focuses on all styles of fashion, apparel and accessories.
 * Version:           1.0.0
 * Author:            GLAMI
 * Author URI:        https://www.glami.eco/
 * Text Domain:       glami-feed-generator-pixel-for-woocommerce
 * Domain Path:       /languages
 * WC requires at least: 3.0
 * WC tested up to:   5.4.1
 * License:           GNU General Public License v3.0
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.html
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

require 'includes/update/plugin-update-checker.php';
$myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
	'https://github.com/glami/woocommerce-module/',
	__FILE__,
	'glami-feed-generator-pixel-for-woocommerce'
);

//Set the branch that contains the stable release.
$myUpdateChecker->setBranch('stable-branch-name');

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'GLAMI_FEED_GENERATOR_PIXEL_FOR_WOOCOMMERCE_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-glami-feed-generator-pixel-for-woocommerce-activator.php
 */
function activate_glami_feed_generator_pixel_for_woocommerce() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-glami-feed-generator-pixel-for-woocommerce-activator.php';
	Glami_Feed_Generator_Pixel_For_Woocommerce_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-glami-feed-generator-pixel-for-woocommerce-deactivator.php
 */
function deactivate_glami_feed_generator_pixel_for_woocommerce() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-glami-feed-generator-pixel-for-woocommerce-deactivator.php';
	Glami_Feed_Generator_Pixel_For_Woocommerce_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_glami_feed_generator_pixel_for_woocommerce' );
register_deactivation_hook( __FILE__, 'deactivate_glami_feed_generator_pixel_for_woocommerce' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-glami-feed-generator-pixel-for-woocommerce.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_glami_feed_generator_pixel_for_woocommerce() {
	if( ! class_exists( 'WC_Integration' )) {
		add_action( 'admin_notices', 'woocommerce_missing_noticea' );
		return false;
	}
	$plugin = new Glami_Feed_Generator_Pixel_For_Woocommerce();
	$plugin->run();
}

add_action( 'plugins_loaded', 'run_glami_feed_generator_pixel' );
function run_glami_feed_generator_pixel() {
	run_glami_feed_generator_pixel_for_woocommerce();
}

function woocommerce_missing_notices() {
	$class = 'notice error';
	$message = 'GLAMI feed generator + PiXel for WooCommerce requires the WooCommerce plugin';

	printf( '<div class="%s"><p>%s</p></div>', $class, $message );
}

