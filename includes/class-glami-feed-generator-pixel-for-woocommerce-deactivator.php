<?php

/**
 * Fired during plugin deactivation
 *
 * @link       https://www.glami.eco/
 * @since      1.0.0
 *
 * @package    Glami_Feed_Generator_Pixel_For_Woocommerce
 * @subpackage Glami_Feed_Generator_Pixel_For_Woocommerce/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Glami_Feed_Generator_Pixel_For_Woocommerce
 * @subpackage Glami_Feed_Generator_Pixel_For_Woocommerce/includes
 * @author     GLAMI <info@glami.cz>
 */
class Glami_Feed_Generator_Pixel_For_Woocommerce_Deactivator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
		require_once plugin_dir_path( __FILE__ ) . 'class-glami-feed-generator-pixel-for-woocommerce-cron.php';
		Glami_Feed_Generator_Pixel_For_Woocommerce_Cron::unschedule();
	}

}
