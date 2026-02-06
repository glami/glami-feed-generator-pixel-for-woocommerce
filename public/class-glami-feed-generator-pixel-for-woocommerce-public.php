<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://www.glami.eco/
 * @since      1.0.0
 *
 * @package    Glami_Feed_Generator_Pixel_For_Woocommerce
 * @subpackage Glami_Feed_Generator_Pixel_For_Woocommerce/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Glami_Feed_Generator_Pixel_For_Woocommerce
 * @subpackage Glami_Feed_Generator_Pixel_For_Woocommerce/public
 * @author     GLAMI <info@glami.cz>
 */
class Glami_Feed_Generator_Pixel_For_Woocommerce_Public {
	private $glami_settings;
	private $plugin_name;
	private $version;
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->glami_settings = get_option('woocommerce_glami-feed-generator-pixel-for-woocommerce_settings',[]);
	}
    function glami_top_integration_guide($order_id){
        if ($this->glami_settings['glami_top']!="yes") {
            return;
        }

        $parse = explode('.',$this->glami_settings['glami_engine']);
        if (is_array($parse))
            $parse = end($parse);

	    $order = new WC_Order( $order_id );
        ?>
	    <!-- GLAMI Reviews -->
        <script>
	    (function (f, a, s, h, i, o, n) {
        f['GlamiOrderReview'] = i;
        f[i] = f[i] || function () {(f[i].q = f[i].q || []).push(arguments);};
        o = a.createElement(s), n = a.getElementsByTagName(s)[0];
        o.async = 1; o.src = h; n.parentNode.insertBefore(o, n);
        })(window, document, 'script', '//www.<?php echo $this->glami_settings['glami_engine'];?>/js/compiled/or.js', 'glami_or');
        glami_or('addParameter', 'merchant_id',<?php echo $this->glami_settings['glami_pixel_key'];?>, '<?php echo $parse;?>');
        glami_or('addParameter', 'order_id', '<?php echo $order->get_id(); ?>');
        glami_or('addParameter', 'email', '<?php echo $order->get_billing_email(); ?>');
        glami_or('addParameter', 'language', '<?php echo get_locale();?>');
        glami_or('addParameter', 'items', [
	    <?php
	    $keysX = array_keys($order->get_items());
	    $last_key = end($keysX);
	    foreach ( $order->get_items() as $key => $item ) :
		    $product = wc_get_product($item->get_product_id());
            if ($product) {
                ?>
                {
                id: '<?php echo $product->get_id(); ?>',
                name: '<?php echo $product->get_name(); ?>'
                }
                <?php
                if ($key != $last_key) {
                    echo ', ';
                }
	        }
	    endforeach; ?>
        ]);
        glami_or('create');
	    <?php
	    echo "</script>
<!-- End of GLAMI TOP Tracking -->";
    }

    function glami_preload_basic_script($content=null) {
        if (empty($this->glami_settings['glami_pixel_key'])) {
            return;
        }

        $parse = explode('.', $this->glami_settings['glami_engine']);
        if (is_array($parse)) {
            $parse = end($parse); // GR / CZ / SK etc
        }

        $engine     = esc_js($this->glami_settings['glami_engine']);
        $pixel_key  = esc_js($this->glami_settings['glami_pixel_key']);
        $country    = esc_js($parse);
        $wc_version = esc_js(WC()->version);

        echo "<!-- Glami Pixel -->
    <script>
        (function(f,a,s,h,i,o,n){
            f['GlamiTrackerObject']=i;
            f[i]=f[i]||function(){(f[i].q=f[i].q||[]).push(arguments)};
            o=a.createElement(s),n=a.getElementsByTagName(s)[0];
            o.async=1;o.src=h;n.parentNode.insertBefore(o,n);
        })(window, document, 'script', '//www.{$engine}/js/compiled/pt.js', 'glami');

        glami('create', '{$pixel_key}', '{$country}', {
            source: 'WooCommerce_{$wc_version}'
        });

        ".($content ?? "")."
    </script>
    <!-- End Glami Pixel -->";
    }

    function glami_output_analytics_tracking_script() {
        global $post;
        if (empty($this->glami_settings) || $this->glami_settings['glami_pixel_key']==null || empty($this->glami_settings['glami_pixel_key'])) {
            return;
        }

        $pixel="";
        if (is_product()) {
            $product=wc_get_product($post->ID);
            if ($product) {
                $product_title_rest="";
                $item_ids=[];
                if ($product->is_type('variable')) {
                    $children_ids = $product->get_children();
                    if (!empty($children_ids))
                        $item_ids=array_merge([$product->get_id()],$children_ids);
                }else {
                    $item_ids[]=$product->get_id();
                }
                $pixel.="glami('track', 'ViewContent', {
                content_type: 'product',
                item_ids: ".json_encode($item_ids).",
                product_names: ['".esc_html($product->get_name())."']
                });";
            }
        }

        if (is_product_category()) {
	        global $wp_query;
	        $queried=get_queried_object();
	        if( get_class( $queried ) === 'WP_Term' ) {
                $categories_list = array();
                $ancestors = get_ancestors($queried->term_id, 'product_cat', 'taxonomy');

                $ancestors=array_reverse($ancestors);
                foreach ($ancestors as $parent) {
                    $term              = get_term_by('id', $parent, 'product_cat');
                    $categories_list[] = $term->name;
                }
                $categories_list[] = $queried->name;
                $categories        = implode('|', $categories_list);

                $product_ids=[];
                $product_names=[];
		        if (!empty($wp_query->posts)) {
			        foreach ( $wp_query->posts as $post ) {
				        $product = wc_get_product( $post->ID );
				        if ($product) {
					        $product_ids[]   = $product->get_id();
					        $product_names[] = esc_html( $product->get_name() );
				        }
			        }
		        }

                $pixel.="glami('track', 'ViewContent', {
content_type: 'category',
item_ids: ".json_encode($product_ids,JSON_UNESCAPED_UNICODE).",
product_names: ".json_encode($product_names,JSON_UNESCAPED_UNICODE).",
category_text: '".$categories."'
});
";
            }
        }

        $pixel.="glami('track', 'PageView');";
        $this->glami_preload_basic_script($pixel);
    }

    function glami_load_ecommerce_analytics($order_id) {
        if (empty($this->glami_settings['glami_pixel_key'])) {
            return;
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        if ($order->get_meta('_glami_purchase_tracked') == 1) {
            return;
        }

        $items_ids=[];
        $product_names=[];
        foreach( $order->get_items() as $item_id => $item ){
            $items_ids[]     = $item->get_variation_id() ? $item->get_variation_id() : $item->get_product_id();
            $product_names[] = $item->get_name();
        }

        $item_ids_js      = "'" . implode("','", array_map('esc_js', $items_ids)) . "'";
        $product_names_js = "'" . implode("','", array_map('esc_js', $product_names)) . "'";

        $total    = (float) $order->get_total();
        $currency = esc_js($order->get_currency());
        $order_id = esc_js($order_id);

        $pixel = "
        glami('track', 'Purchase', {
            item_ids: [$item_ids_js],
            product_names: [$product_names_js],
            value: $total,
            currency: '$currency',
            transaction_id: '$order_id'
        });
    ";
        $this->glami_preload_basic_script($pixel);
        $order->update_meta_data('_glami_purchase_tracked', 1);
        $order->save();
    }

    function glami_load_add_to_cart_analytics() {
        if (empty($this->glami_settings['glami_pixel_key'])) {
            return;
        }

        global $product;
        if ( $product->is_type('variable') ) {
            $variations_data = [];
            foreach ($product->get_available_variations() as $variation) {
                $variations_data[$variation['variation_id']] = $variation['display_price'];
            }
            ?>
            <script>
                jQuery(function ($) {
                    var jsonData = <?php echo json_encode($variations_data); ?>,
                        inputVID = 'input.variation_id';

                    $('input').change(function () {
                        if ('' != $(inputVID).val()) {
                            var vid = $(inputVID).val(),vprice = '';
                            $.each(jsonData, function (index, price) {
                                if (index == $(inputVID).val()) {
                                    vprice = price;
                                }
                            });

                            $('#glami_item_id').val(vid);
                            $('#glami_item_price').val(vprice);
                        }
                    });
                });
            </script>
            <?php
        }
        ?>
        <input type="hidden" name="glami_item_id" id="glami_item_id" value="<?php echo $product->get_id(); ?>">
        <input type="hidden" name="glami_item_price" id="glami_item_price" value="<?php echo $product->get_price(); ?>">
        <input type="hidden" name="glami_item_name" id="glami_item_name" value="<?php echo $product->get_name(); ?>">

        <script type="text/javascript">
            jQuery(document).ready(function($) {
                $('.button.single_add_to_cart_button').click(function() {
                    glami('track', 'AddToCart', {
                        item_ids: [$('#glami_item_id').val()],
                        product_names: [$('#glami_item_name').val()],
                        value: $('#glami_item_price').val(),
                        currency: '<?php echo get_woocommerce_currency();?>'
                    });
                });
            });
        </script>
        <?php
    }
}
