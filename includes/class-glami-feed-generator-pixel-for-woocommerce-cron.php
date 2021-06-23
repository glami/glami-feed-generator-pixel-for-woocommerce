<?php
/**
 * Class responsible for scheduling and un-scheduling events (cron jobs).
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Plugin_Name
 * @subpackage Plugin_Name/includes
 */

/**
 * Class responsible for scheduling and un-scheduling events (cron jobs).
 *
 * This class defines all code necessary to schedule and un-schedule cron jobs.
 *
 * @since      1.0.0
 * @package    Plugin_Name
 * @subpackage Plugin_Name/includes
 * @author     Your Name <email@example.com>
 */
class Glami_Feed_Generator_Pixel_For_Woocommerce_Cron {

	const GLAMI_FEED_GENERATOR_FOR_WOOCOMMERCE_CRON_HOOK = 'glami_feed_generator_for_wc_generate_feed';

	/**
	 * Check if already scheduled, and schedule if not.
	 */
	public static function schedule() {
		if ( ! self::next_scheduled_hourly() ) {
			self::hourly_schedule();
		}
	}

	/**
	 * Unschedule.
	 */
	public static function unschedule() {
		wp_clear_scheduled_hook( self::GLAMI_FEED_GENERATOR_FOR_WOOCOMMERCE_CRON_HOOK );
	}
	private static function next_scheduled_hourly() {
		return wp_next_scheduled( self::GLAMI_FEED_GENERATOR_FOR_WOOCOMMERCE_CRON_HOOK );
	}
	private static function hourly_schedule() {
		if(version_compare(get_bloginfo('version'),'5.3', '>=') )
			$datetime=new DateTime('now',wp_timezone());
		else
			$datetime=new DateTime('now');

		wp_schedule_event( $datetime->getTimestamp(), 'hourly', self::GLAMI_FEED_GENERATOR_FOR_WOOCOMMERCE_CRON_HOOK );
	}
}