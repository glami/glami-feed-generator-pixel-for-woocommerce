<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://www.glami.eco/
 * @since      1.0.0
 *
 * @package    Glami_Feed_Generator_Pixel_For_Woocommerce
 * @subpackage Glami_Feed_Generator_Pixel_For_Woocommerce/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Glami_Feed_Generator_Pixel_For_Woocommerce
 * @subpackage Glami_Feed_Generator_Pixel_For_Woocommerce/includes
 * @author     GLAMI <info@glami.cz>
 */
class Glami_Feed_Generator_Pixel_For_Woocommerce_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'glami-feed-generator-pixel-for-woocommerce',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
