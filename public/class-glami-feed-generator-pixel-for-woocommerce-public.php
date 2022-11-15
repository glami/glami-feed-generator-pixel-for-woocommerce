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

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->glami_settings = get_option('woocommerce_glami-feed-generator-pixel-for-woocommerce_settings',[]);
	}

    function glami_preload_basic_script($content=null) {
	    $parse = explode('.',$this->glami_settings['glami_engine']);
	    if (is_array($parse))
	        $parse = end($parse);
        echo "<!-- Glami piXel -->
		<script>
            (function(f, a, s, h, i, o, n) {f['GlamiTrackerObject'] = i;
                f[i]=f[i]||function(){(f[i].q=f[i].q||[]).push(arguments)};o=a.createElement(s),
                    n=a.getElementsByTagName(s)[0];o.async=1;o.src=h;n.parentNode.insertBefore(o,n)
            })(window, document, 'script', '//www.".$this->glami_settings['glami_engine']."/js/compiled/pt.js', 'glami');
            glami('create', '".$this->glami_settings['glami_pixel_key']."', '".$parse."');
            $content
		</script>
		<!-- End Glami piXel -->";
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
                    foreach ($this->glami_settings['glami_color'] as $color) {
                        $product_title_rest=" ".$product->get_attribute($color);
                    }
                    $children_ids = $product->get_children();
                    $item_ids[]= $product->get_id();
                    foreach ($children_ids as $children_id) {
                        $variation=wc_get_product($children_id);
                        if ($variation)
                            array_push($item_ids,$variation);
                    }
                }else {
                    array_push($item_ids,$product->get_id());
                }
                $pixel.="glami('track', 'ViewContent', {
                content_type: 'product',
                item_ids: ".json_encode($item_ids).",
                product_names: ['".esc_html($product->get_name())."']
                });";
            }
        }

        if (is_product_category()) {
            $queried=get_queried_object();
            if( get_class( $queried ) === 'WP_Term' ) {
                $args = array(
                    'status' => 'publish',
                    'category' => array( $queried->slug ),
                );

                $categories_list = array();
                $ancestors = get_ancestors($queried->term_id, 'product_cat', 'taxonomy');

                $ancestors=array_reverse($ancestors);
                foreach ($ancestors as $parent) {
                    $term = get_term_by('id', $parent, 'product_cat');
                    array_push($categories_list, $term->name);
                }
                array_push($categories_list, $queried->name);
                $categories = implode('|', $categories_list);

                $products = wc_get_products( $args );

                $product_ids=[];
                $product_names=[];
                foreach ($products as $product) {

                    $product_title_rest="";
                    if ($product->is_type('variation')) {
                        foreach ($this->glami_settings['glami_color'] as $color) {
                            $product_title_rest=" ".$product->get_attribute($color);
                        }
                    }

                    array_push($product_ids,$product->get_id());
                    array_push($product_names,esc_html($product->get_name()));
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
        if ($this->glami_settings['glami_pixel_key']==null || empty($this->glami_settings['glami_pixel_key'])) {
            return;
        }

        $order=wc_get_order($order_id);
        if ($order && get_option('glami_feed_generator_pixel_report_purchase_'.$order_id,null)!=1) {
            $items_ids=[];
            $product_names=[];
            foreach( $order->get_items() as $item_id => $item ){
                array_push($items_ids,$item->get_variation_id() ? $item->get_variation_id() : $item->get_product_id());
                array_push($product_names,$item->get_name());
            }

            $pixel="glami('track', 'Purchase', {
item_ids: ['".implode(",",$items_ids)."'],
product_names: ['".implode(",",$product_names)."'],
value: {$order->get_total()},
currency: '{$order->get_currency()}',
transaction_id: '$order_id'
});";
            $this->glami_preload_basic_script($pixel);
            update_option('we_glami_xml_pixel_report_purchase_'.$order_id,1);
        }
    }

    function glami_load_add_to_cart_analytics() {
        if ($this->glami_settings['glami_pixel_key']==null || empty($this->glami_settings['glami_pixel_key'])) {
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
