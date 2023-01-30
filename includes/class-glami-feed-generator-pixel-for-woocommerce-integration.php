<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://www.glami.eco/
 * @since      1.0.0
 *
 * @package    Glami_Feed_Generator_Pixel_For_Woocommerce
 * @subpackage Glami_Feed_Generator_Pixel_For_Woocommerce/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Glami_Feed_Generator_Pixel_For_Woocommerce
 * @subpackage Glami_Feed_Generator_Pixel_For_Woocommerce/includes
 * @author     GLAMI <info@glami.cz>
 */

if ( ! class_exists( 'Glami_Feed_Generator_Pixel_For_Woocommerce_Integration' ) ) :

class Glami_Feed_Generator_Pixel_For_Woocommerce_Integration extends WC_Integration{
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

	public function __construct() {
		$this->id = "glami-feed-generator-pixel-for-woocommerce";
		$this->method_title = __( 'GLAMI feed generator + PiXel', 'glami-feed-generator-pixel-for-woocommerce' );
		$this->method_description    = __( 'GLAMI feed generator + PiXel.', 'glami-feed-generator-pixel-for-woocommerce' );
		$this->init_form_fields();
		$this->init_settings();
		add_action( 'woocommerce_update_options_integration_' . $this->id, array( $this, 'process_admin_options' ) );
		add_filter( 'woocommerce_settings_api_sanitized_fields_' . $this->id, array( $this, 'sanitize_settings' ) );
	}

	/**
	 * Santize our settings
	 * @see process_admin_options()
	 */
	public function sanitize_settings( $settings ) {
		foreach ($settings as $k=>$option) {
		    if (!in_array($k,['pixel_key','engine','size_system',]))
			    $settings[$k]=!empty($option) ? $option : [];
        }
		return $settings;
	}

	/**
	 * Add settings action link to plugins listing
	 *
	 * @param array links The existing action links
	 * @return array The final action links to be displayed
	 *
	 * @since    1.0.0
	 */
	public function add_action_links ( $links ) {
		$action_links = array(
			'settings' => sprintf(
				'<a href="%s" title="%s"> %s </a>',
				admin_url( 'admin.php?page=wc-settings&tab=integration&section=glami-feed-generator-pixel-for-woocommerce'),
				__( 'View GLAMI Feed Generator & Pixel Settings', 'glami-feed-generator-pixel-for-woocommerce'),
				__( 'Settings', 'glami-feed-generator-pixel-for-woocommerce' )
			),
		);

		return array_merge( $links, $action_links );
	}

	/**
	 * Define the plugin form fields and settings
	 *
	 * @since    1.0.0
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'customize_button' => array(
				'title'             => __( 'Generate Feed', 'glami-feed-generator-pixel-for-woocommerce' ),
				'type'              => 'button',
				'custom_attributes' => array(
					'onclick' => "generateGlamiFeed()",
				),
				'description'       => __( 'Once the XML is generated, the XML feed URL will be displayed here.', 'glami-feed-generator-pixel-for-woocommerce' ),
			),
			'glami_pixel_key' => array(
				'title'       => __( 'Pixel Key', 'glami-feed-generator-pixel-for-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Paste your GLAMI pixel key. You can find it in your GLAMI dashboard under GLAMI pixel - instructions for developers.', 'glami-feed-generator-pixel-for-woocommerce' ),
				'default'     => '',
			),
			'glami_engine' => array(
				'title'       => __( 'Engine', 'glami-feed-generator-pixel-for-woocommerce' ),
				'type'        => 'select',
				'description' => __( 'Choose the GLAMI fashion search engine you want to appear in.', 'glami-feed-generator-pixel-for-woocommerce' ),
				'options'     => $this->glami_engines(),
				'default'     => 'glami.eco',
				'desc_tip'          => true,
			),
			'glami_description_field' => array(
				'title'       => __( 'Description', 'glami-feed-generator-pixel-for-woocommerce' ),
				'type'        => 'select',
				'description' => __( 'Choose the attributes that will be used for description attribute in XML.', 'glami-feed-generator-pixel-for-woocommerce' ),
				'options'     => [
					'short_description'=>__( 'Short description', 'glami-feed-generator-pixel-for-woocommerce' ),
					'description'=>__( 'Description', 'glami-feed-generator-pixel-for-woocommerce' )
				],
				'default'     => 'short_description',
				'desc_tip'          => true,
			),
			'glami_cron_schedule' => array(
				'title'       => __( 'Cron schedule', 'glami-feed-generator-pixel-for-woocommerce' ),
				'type'        => 'select',
				'description' => __( 'Choose the frequency of the feed update.', 'glami-feed-generator-pixel-for-woocommerce' ),
				'options'     => [
					'hourly'=>__( 'Hourly', 'glami-feed-generator-pixel-for-woocommerce' ),
					'twice_dailydaily'=>__( 'Twice Daily', 'glami-feed-generator-pixel-for-woocommerce' ),
					'daily'=>__( 'Daily', 'glami-feed-generator-pixel-for-woocommerce' )
				],
				'default'     => 'hourly',
				'desc_tip'          => true,
			),
            'glami_exclude_out_of_stock' => array(
                'title'       => __( 'Out of Stock', 'glami-feed-generator-pixel-for-woocommerce' ),
                'label'       => __( 'Exclude out of Stock from XML feed', 'glami-feed-generator-pixel-for-woocommerce' ),
                'type'        => 'checkbox',
                'description' => __( 'Excludes out of stock products from XML if selected.', 'glami-feed-generator-pixel-for-woocommerce' ),
                'options'     => $this->attribute_taxonomies(),
                'default'     => '',
                'desc_tip'          => true,
            ),
			'glami_manufacturer' => array(
				'title'       => __( 'Manufacturer', 'glami-feed-generator-pixel-for-woocommerce' ),
				'type'        => 'multiselect',
				'description' => __( 'Choose the attributes that will be used for manufacturer attribute in XML.', 'glami-feed-generator-pixel-for-woocommerce' ),
				'options'     => $this->attribute_taxonomies(),
				'class'       => 'glami-select',
				'default'     => '',
				'desc_tip'          => true,
			),
			'glami_size' => array(
				'title'       => __( 'Size', 'glami-feed-generator-pixel-for-woocommerce' ),
				'type'        => 'multiselect',
				'description' => __( 'Choose the attributes that will be used for size attribute in XML.', 'glami-feed-generator-pixel-for-woocommerce' ),
				'options'     => $this->attribute_taxonomies(),
				'class'       => 'glami-select',
				'default'     => '',
				'desc_tip'          => true,
			),
			'glami_size_system' => array(
				'title'       => __( 'Size system', 'glami-feed-generator-pixel-for-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Choose the attributes that will be used for size system attribute in XML.', 'glami-feed-generator-pixel-for-woocommerce' ),
				'default'     => 'EU',
				'desc_tip'          => true,
			),
			'glami_color' => array(
				'title'       => __( 'Colour', 'glami-feed-generator-pixel-for-woocommerce' ),
				'type'        => 'multiselect',
				'description' => __( 'Choose the attributes that will be used for colour attribute in XML.', 'glami-feed-generator-pixel-for-woocommerce' ),
				'options'     => $this->attribute_taxonomies(),
				'class'       => 'glami-select',
				'default'     => '',
				'desc_tip'          => true,
			),
			'glami_cut' => array(
				'title'       => __( 'Cut', 'glami-feed-generator-pixel-for-woocommerce' ),
				'type'        => 'multiselect',
				'description' => __( 'Choose the attributes that will be used for cut attribute in XML.', 'glami-feed-generator-pixel-for-woocommerce' ),
				'options'     => $this->attribute_taxonomies(),
				'class'       => 'glami-select',
				'default'     => '',
				'desc_tip'          => true,
			),
			'glami_heel_type' => array(
				'title'       => __( 'Heel Type', 'glami-feed-generator-pixel-for-woocommerce' ),
				'type'        => 'multiselect',
				'description' => __( 'Choose the attributes that will be used for heel type attribute in XML.', 'glami-feed-generator-pixel-for-woocommerce' ),
				'options'     => $this->attribute_taxonomies(),
				'class'       => 'glami-select',
				'default'     => '',
				'desc_tip'          => true,
			),
			'glami_length' => array(
				'title'       => __( 'Length', 'glami-feed-generator-pixel-for-woocommerce' ),
				'type'        => 'multiselect',
				'description' => __( 'Choose the attributes that will be used for length attribute in XML.', 'glami-feed-generator-pixel-for-woocommerce' ),
				'options'     => $this->attribute_taxonomies(),
				'class'       => 'glami-select',
				'default'     => '',
				'desc_tip'          => true,
			),
			'glami_occasion' => array(
				'title'       => __( 'Occasion', 'glami-feed-generator-pixel-for-woocommerce' ),
				'type'        => 'multiselect',
				'description' => __( 'Choose the attributes that will be used for occasion attribute in XML.', 'glami-feed-generator-pixel-for-woocommerce' ),
				'options'     => $this->attribute_taxonomies(),
				'class'       => 'glami-select',
				'default'     => '',
				'desc_tip'          => true,
			),
			'glami_pattern' => array(
				'title'       => __( 'Pattern', 'glami-feed-generator-pixel-for-woocommerce' ),
				'type'        => 'multiselect',
				'description' => __( 'Choose the attributes that will be used for pattern attribute in XML.', 'glami-feed-generator-pixel-for-woocommerce' ),
				'options'     => $this->attribute_taxonomies(),
				'class'       => 'glami-select',
				'default'     => '',
				'desc_tip'          => true,
			),
			'glami_gender' => array(
				'title'       => __( 'Gender', 'glami-feed-generator-pixel-for-woocommerce' ),
				'type'        => 'multiselect',
				'description' => __( 'Choose the attributes that will be used for gender attribute in XML.', 'glami-feed-generator-pixel-for-woocommerce' ),
				'options'     => $this->attribute_taxonomies(),
				'class'       => 'glami-select',
				'default'     => '',
				'desc_tip'          => true,
			),
			'glami_strap' => array(
				'title'       => __( 'Strap', 'glami-feed-generator-pixel-for-woocommerce' ),
				'type'        => 'multiselect',
				'description' => __( 'Choose the attributes that will be used for strap attribute in XML.', 'glami-feed-generator-pixel-for-woocommerce' ),
				'options'     => $this->attribute_taxonomies(),
				'class'       => 'glami-select',
				'default'     => '',
				'desc_tip'          => true,
			),
			'glami_style' => array(
				'title'       => __( 'Style', 'glami-feed-generator-pixel-for-woocommerce' ),
				'type'        => 'multiselect',
				'description' => __( 'Choose the attributes that will be used for style attribute in XML.', 'glami-feed-generator-pixel-for-woocommerce' ),
				'options'     => $this->attribute_taxonomies(),
				'class'       => 'glami-select',
				'default'     => '',
				'desc_tip'          => true,
			),
			'glami_sport' => array(
				'title'       => __( 'Sport', 'glami-feed-generator-pixel-for-woocommerce' ),
				'type'        => 'multiselect',
				'description' => __( 'Choose the attributes that will be used for sport attribute in XML.', 'glami-feed-generator-pixel-for-woocommerce' ),
				'options'     => $this->attribute_taxonomies(),
				'class'       => 'glami-select',
				'default'     => '',
				'desc_tip'          => true,
			),
			'glami_sleeve_length' => array(
				'title'       => __( 'Sleeve length', 'glami-feed-generator-pixel-for-woocommerce' ),
				'type'        => 'multiselect',
				'description' => __( 'Choose the attributes that will be used for sleeve length attribute in XML.', 'glami-feed-generator-pixel-for-woocommerce' ),
				'options'     => $this->attribute_taxonomies(),
				'class'       => 'glami-select',
				'default'     => '',
				'desc_tip'          => true,
			),
			'glami_material' => array(
				'title'       => __( 'Material', 'glami-feed-generator-pixel-for-woocommerce' ),
				'type'        => 'multiselect',
				'description' => __( 'Choose the attributes that will be used for material attribute in XML.', 'glami-feed-generator-pixel-for-woocommerce' ),
				'options'     => $this->attribute_taxonomies(),
				'class'       => 'glami-select',
				'default'     => '',
				'desc_tip'          => true,
			),
			'glami_season' => array(
				'title'       => __( 'Season', 'glami-feed-generator-pixel-for-woocommerce' ),
				'type'        => 'multiselect',
				'description' => __( 'Choose the attributes that will be used for season attribute in XML.', 'glami-feed-generator-pixel-for-woocommerce' ),
				'options'     => $this->attribute_taxonomies(),
				'class'       => 'glami-select',
				'default'     => '',
				'desc_tip'          => true,
			),
			'glami_trend' => array(
				'title'       => __( 'Trend', 'glami-feed-generator-pixel-for-woocommerce' ),
				'type'        => 'multiselect',
				'description' => __( 'Choose the attributes that will be used for trend attribute in XML.', 'glami-feed-generator-pixel-for-woocommerce' ),
				'options'     => $this->attribute_taxonomies(),
				'class'       => 'glami-select',
				'default'     => '',
				'desc_tip'          => true,
			),
			'glami_certification' => array(
				'title'       => __( 'Certification', 'glami-feed-generator-pixel-for-woocommerce' ),
				'type'        => 'multiselect',
				'description' => __( 'Choose the attributes that will be used for certification attribute in XML.', 'glami-feed-generator-pixel-for-woocommerce' ),
				'options'     => $this->attribute_taxonomies(),
				'class'       => 'glami-select',
				'default'     => '',
				'desc_tip'          => true,
			),
			'glami_fit' => array(
				'title'       => __( 'Fit', 'glami-feed-generator-pixel-for-woocommerce' ),
				'type'        => 'multiselect',
				'description' => __( 'Choose the attributes that will be used for fit attribute in XML.', 'glami-feed-generator-pixel-for-woocommerce' ),
				'options'     => $this->attribute_taxonomies(),
				'class'       => 'glami-select',
				'default'     => '',
				'desc_tip'          => true,
			),
			'glami_sustainability' => array(
				'title'       => __( 'Sustainability', 'glami-feed-generator-pixel-for-woocommerce' ),
				'type'        => 'multiselect',
				'description' => __( 'Choose the attributes that will be used for sustainability attribute in XML.', 'glami-feed-generator-pixel-for-woocommerce' ),
				'options'     => $this->attribute_taxonomies(),
				'class'       => 'glami-select',
				'default'     => '',
				'desc_tip'          => true,
			),
		);
	}

	/**
	 * Fetches IDs and names of WooCommerce attribute taxonomies.
	 * @return array [attribute_id => attribute_name] mapping
	 *
	 * @since  1.5.0
	 * @access private
	 */
	private function glami_engines() {
		return [
			'glami.eco'=>__('English','glami-feed-generator-pixel-for-woocommerce'),
			'glami.ro'=>__('Romania','glami-feed-generator-pixel-for-woocommerce'),
			'glami.gr'=>__('Greece','glami-feed-generator-pixel-for-woocommerce'),
			'glami.hr'=>__('Croatia','glami-feed-generator-pixel-for-woocommerce'),
			'glami.hu'=>__('Hungary','glami-feed-generator-pixel-for-woocommerce'),
			'glami.cz'=>__('Czech Republic','glami-feed-generator-pixel-for-woocommerce'),
			'glami.sk'=>__('Slovakia','glami-feed-generator-pixel-for-woocommerce'),
			'glami.si'=>__('Slovenia','glami-feed-generator-pixel-for-woocommerce'),
			'glami.com.tr'=>__('Turkey','glami-feed-generator-pixel-for-woocommerce'),
			'glami.ee'=>__('Estonia','glami-feed-generator-pixel-for-woocommerce'),
			'glami.lv'=>__('Lithuania','glami-feed-generator-pixel-for-woocommerce'),
			'glami.lt'=>__('Litva','glami-feed-generator-pixel-for-woocommerce'),
			'glami.es'=>__('Spain','glami-feed-generator-pixel-for-woocommerce'),
			'glami.com.br'=>__('Brazil','glami-feed-generator-pixel-for-woocommerce'),
			'glami.bg'=>__('Bulgaria','glami-feed-generator-pixel-for-woocommerce'),
		];
	}
	private function attribute_taxonomies() {
		return wp_list_pluck( wc_get_attribute_taxonomies(), 'attribute_label', 'attribute_name' );
	}

	public function generate_button_html( $key, $data ) {
		$filename = "glami.xml";
		if (defined('WP_ALLOW_MULTISITE') && isset($blog_id)) {
			$filename = "glami_$blog_id.xml";
		}
		$upload_dir = wp_upload_dir();
		$upload_base_dir = $upload_dir['basedir'] . '/glami-feed-generator/';
		$upload_base_url = $upload_dir['baseurl'] . '/glami-feed-generator/';

		$field    = $this->plugin_id . $this->id . '_' . $key;
		$defaults = array(
			'class'             => 'button-secondary',
			'css'               => '',
			'custom_attributes' => array(),
			'desc_tip'          => false,
			'description'       => '',
			'title'             => '',
		);

		$data = wp_parse_args( $data, $defaults );

		ob_start();
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $field ); ?>"><?php _e( 'Feed', 'glami-feed-generator-pixel-for-woocommerce' ); ?></label>
				<?php echo $this->get_tooltip_html( $data ); ?>
			</th>
			<td class="forminp">
				<fieldset>
					<legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span></legend>
                    <div class="glami-spinner"><span class="spinner"></span></div>
                    <button class="<?php echo esc_attr( $data['class'] ); ?>" type="button" name="<?php echo esc_attr( $field ); ?>" id="<?php echo esc_attr( $field ); ?>" style="<?php echo esc_attr( $data['css'] ); ?>" <?php echo $this->get_custom_attribute_html( $data ); ?>><?php echo wp_kses_post( $data['title'] ); ?></button>
					<?php echo $this->get_description_html( $data ); ?>
                    <div class="glami-xml-ul"><code>
                            <?php if (file_exists($upload_base_dir . $filename)) {
                                echo $upload_base_url .  $filename;
                            }?>
                        </code></div>
				</fieldset>
			</td>
		</tr>
		<?php
		return ob_get_clean();
	}
}

endif;