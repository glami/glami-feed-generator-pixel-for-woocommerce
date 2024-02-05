<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://www.glami.eco/
 * @since      1.0.0
 *
 * @package    Glami_Feed_Generator_Pixel_For_Woocommerce
 * @subpackage Glami_Feed_Generator_Pixel_For_Woocommerce/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Glami_Feed_Generator_Pixel_For_Woocommerce
 * @subpackage Glami_Feed_Generator_Pixel_For_Woocommerce/admin
 * @author     GLAMI <info@glami.cz>
 */
class Glami_Feed_Generator_Pixel_For_Woocommerce_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Glami_Feed_Generator_Pixel_For_Woocommerce_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Glami_Feed_Generator_Pixel_For_Woocommerce_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/glami-feed-generator-pixel-for-woocommerce-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Glami_Feed_Generator_Pixel_For_Woocommerce_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Glami_Feed_Generator_Pixel_For_Woocommerce_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_register_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/glami-feed-generator-pixel-for-woocommerce-admin.js', array( 'jquery' ), $this->version, false );
		wp_enqueue_script($this->plugin_name);
		wp_localize_script($this->plugin_name, 'glami_ajax_object', array(
			'ajax_url' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce('glami-ajax-nonce')
		));
	}

	public function add_integration( $integrations ) {
		$integrations[] = 'Glami_Feed_Generator_Pixel_For_Woocommerce_Integration';
		return $integrations;
	}

	function glami_category_mapping_taxonomy_add_new_meta_field() {
	    $glami_settings = get_option('woocommerce_glami-feed-generator-pixel-for-woocommerce_settings');
	    $engine=!empty($glami_settings['glami_engine']) ? $glami_settings['glami_engine'] : 'glami.eco';
		$xml=simplexml_load_file(plugin_dir_path( __DIR__ )."includes/categories/{$engine}.xml");
		$categories=[];
		function printChildren($node,$children,&$categories) {
			if (!empty($node->CATEGORY_ID) && !array_key_exists((string)$node->CATEGORY_ID,$categories)) {
				$categories["{$node->CATEGORY_ID}"]="{$node->CATEGORY_FULLNAME}";
			}
			if ($children->count()>0) {
				foreach ($children as $child) {
					printChildren($child, $child->children(), $categories);
				}
			}
		}
		printChildren($xml,$xml->CATEGORY,$categories);
		?>
        <div class="form-field">
            <label for="glami_categories_map"><?php _e('GLAMI product category', $this->plugin_name); ?></label>
            <select id="glami_categories_map" name="glami_categories_map">
                <?php foreach ($categories as $category_id=>$category_label) : ?>
                <option value="<?php echo $category_id;?>" <?php selected( null, $category_id );?>><?php echo $category_label;?></option>
                <?php endforeach; ?>
            </select>
            <p class="description"><?php _e('GLAMI product category mapping', $this->plugin_name); ?></p>
        </div>
		<?php
	}

    function printChildren($node,$children,&$categories) {
        if (!empty($node->CATEGORY_ID) && !array_key_exists((string)$node->CATEGORY_ID,$categories)) {
            $categories["{$node->CATEGORY_ID}"]="{$node->CATEGORY_FULLNAME}";
        }
        if ($children->count()>0) {
            foreach ($children as $child) {
                $this->printChildren($child, $child->children(), $categories);
            }
        }
    }

	function glami_category_mapping_taxonomy_edit_meta_field($term) {
		$glami_settings = get_option('woocommerce_glami-feed-generator-pixel-for-woocommerce_settings');
		$engine=!empty($glami_settings['glami_engine']) ? $glami_settings['glami_engine'] : 'glami.eco';
		$xml=simplexml_load_file(plugin_dir_path( __DIR__ )."includes/categories/{$engine}.xml");
		$categories=[];
        $this->printChildren($xml,$xml->CATEGORY,$categories);
		$value= get_term_meta($term->term_id, 'glami_categories_map', true);
		?>
        <tr class="form-field">
            <th scope="row" valign="top"><label for="glami_categories_map"><?php _e('GLAMI categories', $this->plugin_name); ?></label></th>
            <td>
                <select id="glami_categories_map" name="glami_categories_map">
		            <?php foreach ($categories as $category_id=>$category_label) : ?>
                        <option value="<?php echo $category_id;?>" <?php selected( $value, $category_id );?>><?php echo $category_label;?></option>
		            <?php endforeach; ?>
                </select>
                <p class="description"><?php _e('GLAMI category mapping', $this->plugin_name); ?></p>
            </td>
        </tr>
		<?php
	}

	function save_taxonomy_custom_meta($term_id) {
		$glami_categories_map = filter_input(INPUT_POST, 'glami_categories_map');
		update_term_meta($term_id, 'glami_categories_map', $glami_categories_map);
	}

	function glami_feed_run_cron_event() {
        $feed=new Glami_Feed_Generator_Pixel_For_Woocommerce_Engine();
        $feed->generate();
	}

	function glami_feed_run_ajax_event() {
		$feed=new Glami_Feed_Generator_Pixel_For_Woocommerce_Engine();
		$generator=$feed->generate();
		wp_send_json($generator);
	}

	function plugin_action_links($links, $file)
	{
		static $this_plugin;
		if (!$this_plugin) {
			$this_plugin = ( dirname(plugin_basename(__FILE__), 2) . '/' . $this->plugin_name . '.php' );
		}
		if ($file == $this_plugin) {
			$settings_link = '<a href="' . admin_url("admin.php?page=wc-settings&tab=integration&section=glami-feed-generator-pixel-for-woocommerce").'">'.__('Settings').'</a>';
			array_unshift($links, $settings_link);
		}
		return $links;
	}
}
