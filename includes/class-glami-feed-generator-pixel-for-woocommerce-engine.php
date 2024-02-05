<?php

/**
 * Fired during plugin activation
 *
 * @link       https://www.glami.eco/
 * @since      1.0.0
 *
 * @package    Glami_Feed_Generator_Pixel_For_Woocommerce
 * @subpackage Glami_Feed_Generator_Pixel_For_Woocommerce/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Glami_Feed_Generator_Pixel_For_Woocommerce
 * @subpackage Glami_Feed_Generator_Pixel_For_Woocommerce/includes
 * @author     GLAMI <info@glami.cz>
 */
class Glami_Feed_Generator_Pixel_For_Woocommerce_Engine {
	function glami_get_attribute($product, $attribute, $options = array()) {
		$attributes = array();
		if (isset($options["glami_".$attribute]) && is_array($options["glami_".$attribute])) {
			foreach ($options["glami_".$attribute] as $attr) {
				if ($product->is_type('variation') && !empty($product->get_attribute($attr))) {
					return str_replace(',', ';', $product->get_attribute($attr));
				}

				$pa_terms = get_the_terms(($product->is_type('variation') ? $product->get_parent_id() : $product->get_id()), 'pa_' . $attr);

				if ($pa_terms && !is_wp_error($pa_terms)) {
					$array_terms = array_map(
						function ($e) {
							return is_object($e) ? $e->name : $e['name'];
						}, $pa_terms
					);
					$attributes = array_merge($attributes, $array_terms);
				}

				$pa_terms = get_the_terms(($product->is_type('variation') ? $product->get_parent_id() : $product->get_id()), $attr);
				if ($pa_terms && !is_wp_error($pa_terms)) {
					$array_terms = array_map(
						function ($e) {
							return is_object($e) ? $e->name : $e['name'];
						}, $pa_terms
					);
					$attributes = array_merge($attributes, $array_terms);
				}

				if (is_array($attributes) && sizeof($attributes) > 0) {
					return implode(";", array_unique($attributes));
				}
			}
		}
		return implode(";", array_unique($attributes));
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

	function generate_xml_part($product, $options, $shop, $domtree) {
        if (isset($options['glami_exclude_out_of_stock']) && $options['glami_exclude_out_of_stock']=='yes' && !$product->is_in_stock()) {
            return ;
        }

		$p_parent = null;
		if ($product->is_type('variation')) {
			$p_parent = wc_get_product($product->get_parent_id());
		}

		$shopitem=$domtree->createElement('SHOPITEM');
		$shopitem->appendChild($domtree->createElement('ITEM_ID',apply_filters('glami_feed_generator_pixel_custom_id', $product->get_id(),$product)));
		$shopitem->appendChild($domtree->createElement('ITEMGROUP_ID',apply_filters('glami_feed_generator_pixel_custom_group_id', ($product->is_type('variation') ? $product->get_parent_id() : $product->get_id()),$product)));

		$element = $domtree->createElement('PRODUCTNAME');
		$textNode = $domtree->createTextNode(apply_filters('glami_feed_generator_pixel_custom_product_name', $product->get_name(),$product));
		$element->appendChild($textNode);
		$shopitem->appendChild($element);

		if ($options['glami_description_field'] == "short_description") {
			$description = $product->get_short_description();
			if ($product->is_type('variation') && $p_parent) {
				$description = $p_parent->get_short_description();
			}
		} else {
			$description = $product->get_description();
			if ($product->is_type('variation') && $p_parent) {
				$description = $p_parent->get_description();
			}
		}
		$description=strip_tags(html_entity_decode($description));
		$shopitem->appendChild($domtree->createElement('DESCRIPTION',apply_filters('glami_feed_generator_pixel_custom_description', $description,$product)));
		$shopitem->appendChild($domtree->createElement('URL',htmlspecialchars($product->get_permalink())));
		$shopitem->appendChild($domtree->createElement('IMGURL',apply_filters('glami_feed_generator_pixel_custom_imgurl',wp_get_attachment_url($product->get_image_id()),$product)));
		$attachment_ids=apply_filters('glami_feed_generator_pixel_custom_gallery', $product->get_gallery_image_ids(),$product);
		foreach ($attachment_ids as $attachment_id) {
			$shopitem->appendChild($domtree->createElement('IMGURL_ALTERNATIVE',wp_get_attachment_image_url($attachment_id,'full')));
		}

		if ($product->is_type('simple')) {
			$price = wc_get_price_including_tax($product, ['qty' => 1, 'price' => $product->get_price()]);
		} elseif ($product->is_type('variable')) {
			$min_var_reg_price = $product->get_variation_regular_price('min', true);
			$min_var_sale_price = $product->get_variation_sale_price('min', true);
			if ($min_var_sale_price != $min_var_reg_price) {
				$price = $min_var_sale_price;
			} else {
				$price = $min_var_reg_price;
			}
		} else {
			$price = wc_get_price_including_tax($product, ['qty' => 1, 'price' => $product->get_price()]);
		}
		$shopitem->appendChild($domtree->createElement('PRICE_VAT',apply_filters('glami_feed_generator_pixel_custom_price_vat',$price,$product)));

		$categories = "";
		$categories_list = get_the_terms(($product->is_type('variation') || $product->is_type('variable') ? $product->get_parent_id() : $product->get_id()), 'product_cat');
		if ($categories_list) {
			$parent = null;
			if ($product->is_type('variation')) {
				$parent=wc_get_product($product->get_parent_id());
			}
            $last_category = end($categories_list);
			// Yoast SEO
			if ( class_exists( 'WPSEO_Primary_Term' ) ) {
				if ($product->is_type('variation')) {
					$primary_term_object = new WPSEO_Primary_Term('product_cat', $product->get_parent_id());
				}else {
					$primary_term_object = new WPSEO_Primary_Term('product_cat', $product->get_id());
				}
				if (!empty($primary_term_object->get_primary_term())) {
					$possible_term = get_term( $primary_term_object->get_primary_term(), 'product_cat' );
					if ($possible_term && !is_wp_error($possible_term))
						$last_category = get_term( $possible_term->term_id, 'product_cat' );
				}
			}
			// Rankmath
			$rank_math_primary_product_cat=$parent ? $parent->get_meta('rank_math_primary_product_cat') : $product->get_meta('rank_math_primary_product_cat');
			if (!empty($rank_math_primary_product_cat) && in_array($rank_math_primary_product_cat,$product->get_category_ids())) {
				$possible_term = get_term( $rank_math_primary_product_cat, 'product_cat' );
				if ($possible_term && !is_wp_error($possible_term))
					$last_category = get_term( $possible_term->term_id, 'product_cat' );
			}
			// The SEO Framework
			$tsf_primary_product_cat=$parent ? $parent->get_meta('_primary_term_product_cat') : $product->get_meta('_primary_term_product_cat');
			if (!empty($tsf_primary_product_cat)) {
				$possible_term = get_term( $tsf_primary_product_cat, 'product_cat' );
				if ($possible_term && !is_wp_error($possible_term))
					$last_category = get_term( $possible_term->term_id, 'product_cat' );
			}
            $glami_category_mapping = get_term_meta($last_category->term_id, 'glami_categories_map', true);
			if (!empty($glami_category_mapping)) {
                $engine=!empty($options['glami_engine']) ? $options['glami_engine'] : 'glami.eco';
                $xml=simplexml_load_file(plugin_dir_path( __DIR__ )."includes/categories/{$engine}.xml");
                $csv_categories=[];
                $this->printChildren($xml,$xml->CATEGORY,$csv_categories);
                $categories = isset($csv_categories[$glami_category_mapping]) ? $csv_categories[$glami_category_mapping] : null;
            }else {
                $categories_list = array();
                $ancestors = get_ancestors($last_category->term_id, 'product_cat', 'taxonomy');
                $ancestors=array_reverse($ancestors);
                foreach ($ancestors as $parent) {
                    $term = get_term_by('id', $parent, 'product_cat');
                    if ($term && !is_wp_error($term))
                        array_push($categories_list, $term->name);
                }
                array_push($categories_list, $last_category->name);
                $categories = implode(' | ', apply_filters('glami_feed_generator_pixel_custom_categories',$categories_list));
            }
		}
		$shopitem->appendChild($domtree->createElement('CATEGORYTEXT',$categories));

        $glami_feed_generator_pixel_cpc=$p_parent ? $p_parent->get_meta('glami_feed_generator_pixel_cpc') : $product->get_meta('glami_feed_generator_pixel_cpc');
        $glami_feed_generator_pixel_promotion_id= $p_parent ? $p_parent->get_meta('glami_feed_generator_pixel_promotion_id') : $product->get_meta('glami_feed_generator_pixel_promotion_id');
        if (!empty($glami_feed_generator_pixel_cpc)) {
            $shopitem->appendChild($domtree->createElement('GLAMI_CPC',apply_filters('glami_feed_generator_pixel_custom_glami_cpc',$glami_feed_generator_pixel_cpc,$product)));
        }
        if (!empty($glami_feed_generator_pixel_promotion_id)) {
            $shopitem->appendChild($domtree->createElement('PROMOTION_ID',apply_filters('glami_feed_generator_pixel_custom_promotion_id',$glami_feed_generator_pixel_promotion_id,$product)));
        }

		if ($product->is_in_stock() || $product->backorders_allowed()) {
			$delivery_date=0;
            $shopitem->appendChild($domtree->createElement('DELIVERY_DATE',apply_filters('glami_feed_generator_pixel_custom_delivery_date',$delivery_date,$product)));
		}

		if (is_array($options['glami_manufacturer']) && sizeof($options['glami_manufacturer']) > 0 && !empty($this->glami_get_attribute($product, 'manufacturer', $options))) {
			$shopitem->appendChild($domtree->createElement('MANUFACTURER',apply_filters('glami_feed_generator_pixel_custom_manufacturer',$this->glami_get_attribute($product, 'manufacturer', $options),$product)));
		}

		if (is_array($options['glami_size']) && sizeof($options['glami_size']) > 0 && !empty($this->glami_get_attribute($product, 'size', $options))) {
			$param=$domtree->createElement('PARAM');
			$param->appendChild($domtree->createElement('PARAM_NAME',__('size','glami-feed-generator-pixel-for-woocommerce')));
			$param->appendChild($domtree->createElement('VAL',$this->glami_get_attribute($product, 'size', $options)));
			$shopitem->appendChild($param);

			if ($options['glami_size_system']) {
				$param=$domtree->createElement('PARAM');
				$param->appendChild($domtree->createElement('PARAM_NAME','size_system'));
				$param->appendChild($domtree->createElement('VAL',$options['glami_size_system']));
				$shopitem->appendChild($param);
			}
		}

		if (is_array($options['glami_material']) && sizeof($options['glami_material']) > 0 && !empty($this->glami_get_attribute($product, 'material', $options))) {
			$materials_as_string = $this->glami_get_attribute($product, 'material', $options);
			$materials = explode(";", $materials_as_string);
			foreach ($materials as $material) {
				$param=$domtree->createElement('PARAM');
				$param->appendChild($domtree->createElement('PARAM_NAME',__('material','glami-feed-generator-pixel-for-woocommerce')));
				$param->appendChild($domtree->createElement('VAL',$material));
				if (strpos($materials_as_string, ";") === false) {
					$param->appendChild($domtree->createElement('PERCENTAGE',"100%"));
				}
				$shopitem->appendChild($param);
			}
		}

		if (isset($options['glami_color']) && is_array($options['glami_color']) && sizeof($options['glami_color']) > 0 && !empty(apply_filters('glami_feed_generator_pixel_custom_colour',$this->glami_get_attribute($product, 'colour', $options),$product))) {
			$param=$domtree->createElement('PARAM');
			$param->appendChild($domtree->createElement('PARAM_NAME',__('colour','glami-feed-generator-pixel-for-woocommerce')));
			$param->appendChild($domtree->createElement('VAL',apply_filters('glami_feed_generator_pixel_custom_colour',$this->glami_get_attribute($product, 'colour', $options),$product)));
			$shopitem->appendChild($param);
		}

		if (isset($options['glami_gender']) && is_array($options['glami_gender']) && sizeof($options['glami_gender']) > 0 && !empty(apply_filters('glami_feed_generator_pixel_custom_gender',$this->glami_get_attribute($product, 'gender', $options),$product))) {
			$param=$domtree->createElement('PARAM');
			$param->appendChild($domtree->createElement('PARAM_NAME',__('gender','glami-feed-generator-pixel-for-woocommerce')));
			$param->appendChild($domtree->createElement('VAL',apply_filters('glami_feed_generator_pixel_custom_gender',$this->glami_get_attribute($product, 'gender', $options),$product)));
			$shopitem->appendChild($param);
		}

		if (isset($options['glami_length']) && is_array($options['glami_length']) && sizeof($options['glami_length']) > 0 && !empty(apply_filters('glami_feed_generator_pixel_custom_length',$this->glami_get_attribute($product, 'length', $options),$product))) {
			$param=$domtree->createElement('PARAM');
			$param->appendChild($domtree->createElement('PARAM_NAME',__('length','glami-feed-generator-pixel-for-woocommerce')));
			$param->appendChild($domtree->createElement('VAL',apply_filters('glami_feed_generator_pixel_custom_length',$this->glami_get_attribute($product, 'length', $options),$product)));
			$shopitem->appendChild($param);
		}

		if (isset($options['glami_occasion']) && is_array($options['glami_occasion']) && sizeof($options['glami_occasion']) > 0 && !empty(apply_filters('glami_feed_generator_pixel_custom_occasion',$this->glami_get_attribute($product, 'occasion', $options),$product))) {
			$param=$domtree->createElement('PARAM');
			$param->appendChild($domtree->createElement('PARAM_NAME',__('occasion','glami-feed-generator-pixel-for-woocommerce')));
			$param->appendChild($domtree->createElement('VAL',apply_filters('glami_feed_generator_pixel_custom_occasion',$this->glami_get_attribute($product, 'occasion', $options),$product)));
			$shopitem->appendChild($param);
		}

		if (isset($options['glami_season']) && is_array($options['glami_season']) && sizeof($options['glami_season']) > 0 && !empty(apply_filters('glami_feed_generator_pixel_custom_season',$this->glami_get_attribute($product, 'occasion', $options),$product))) {
			$param=$domtree->createElement('PARAM');
			$param->appendChild($domtree->createElement('PARAM_NAME',__('season','glami-feed-generator-pixel-for-woocommerce')));
			$param->appendChild($domtree->createElement('VAL',apply_filters('glami_feed_generator_pixel_custom_season',$this->glami_get_attribute($product, 'occasion', $options),$product)));
			$shopitem->appendChild($param);
		}

		if (isset($options['glami_pattern']) && is_array($options['glami_pattern']) && sizeof($options['glami_pattern']) > 0 && !empty(apply_filters('glami_feed_generator_pixel_custom_pattern',$this->glami_get_attribute($product, 'pattern', $options),$product))) {
			$param=$domtree->createElement('PARAM');
			$param->appendChild($domtree->createElement('PARAM_NAME',__('pattern','glami-feed-generator-pixel-for-woocommerce')));
			$param->appendChild($domtree->createElement('VAL',apply_filters('glami_feed_generator_pixel_custom_pattern',$this->glami_get_attribute($product, 'pattern', $options),$product)));
			$shopitem->appendChild($param);
		}

		if (isset($options['glami_style']) && is_array($options['glami_style']) && sizeof($options['glami_style']) > 0 && !empty(apply_filters('glami_feed_generator_pixel_custom_style',$this->glami_get_attribute($product, 'style', $options),$product))) {
			$param=$domtree->createElement('PARAM');
			$param->appendChild($domtree->createElement('PARAM_NAME',__('style','glami-feed-generator-pixel-for-woocommerce')));
			$param->appendChild($domtree->createElement('VAL',apply_filters('glami_feed_generator_pixel_custom_style',$this->glami_get_attribute($product, 'style', $options),$product)));
			$shopitem->appendChild($param);
		}

		if (isset($options['glami_fit']) && is_array($options['glami_fit']) && sizeof($options['glami_fit']) > 0 && !empty(apply_filters('glami_feed_generator_pixel_custom_fit',$this->glami_get_attribute($product, 'fit', $options),$product))) {
			$param=$domtree->createElement('PARAM');
			$param->appendChild($domtree->createElement('PARAM_NAME',__('fit','glami-feed-generator-pixel-for-woocommerce')));
			$param->appendChild($domtree->createElement('VAL',apply_filters('glami_feed_generator_pixel_custom_fit',$this->glami_get_attribute($product, 'fit', $options),$product)));
			$shopitem->appendChild($param);
		}

		if (isset($options['glami_heel_type']) && is_array($options['glami_heel_type']) && sizeof($options['glami_heel_type']) > 0 && !empty(apply_filters('glami_feed_generator_pixel_custom_gender',$this->glami_get_attribute($product, 'heel_type', $options),$product))) {
			$param=$domtree->createElement('PARAM');
			$param->appendChild($domtree->createElement('PARAM_NAME',__('heel type','glami-feed-generator-pixel-for-woocommerce')));
			$param->appendChild($domtree->createElement('VAL',apply_filters('glami_feed_generator_pixel_custom_gender',$this->glami_get_attribute($product, 'heel_type', $options),$product)));
			$shopitem->appendChild($param);
		}

		if (isset($options['glami_cut']) && is_array($options['glami_cut']) && sizeof($options['glami_cut']) > 0 && !empty(apply_filters('glami_feed_generator_pixel_custom_gender',$this->glami_get_attribute($product, 'cut',  $options),$product))) {
			$param=$domtree->createElement('PARAM');
			$param->appendChild($domtree->createElement('PARAM_NAME',__('cut','glami-feed-generator-pixel-for-woocommerce')));
			$param->appendChild($domtree->createElement('VAL',apply_filters('glami_feed_generator_pixel_custom_gender',$this->glami_get_attribute($product, 'cut',  $options),$product)));
			$shopitem->appendChild($param);
		}

		if (isset($options['glami_strap']) && is_array($options['glami_strap']) && sizeof($options['glami_strap']) > 0 && !empty(apply_filters('glami_feed_generator_pixel_custom_strap',$this->glami_get_attribute($product, 'strap', $options),$product))) {
			$param=$domtree->createElement('PARAM');
			$param->appendChild($domtree->createElement('PARAM_NAME',__('strap','glami-feed-generator-pixel-for-woocommerce')));
			$param->appendChild($domtree->createElement('VAL',apply_filters('glami_feed_generator_pixel_custom_strap',$this->glami_get_attribute($product, 'strap', $options),$product)));
			$shopitem->appendChild($param);
		}

		if (isset($options['glami_sport']) && is_array($options['glami_sport']) && sizeof($options['glami_sport']) > 0 && !empty(apply_filters('glami_feed_generator_pixel_custom_sport',$this->glami_get_attribute($product, 'sport',  $options),$product))) {
			$param=$domtree->createElement('PARAM');
			$param->appendChild($domtree->createElement('PARAM_NAME',__('sport','glami-feed-generator-pixel-for-woocommerce')));
			$param->appendChild($domtree->createElement('VAL',apply_filters('glami_feed_generator_pixel_custom_sport',$this->glami_get_attribute($product, 'sport',  $options),$product)));
			$shopitem->appendChild($param);
		}

		if (isset($options['glami_sleeve_length']) && is_array($options['glami_sleeve_length']) && sizeof($options['glami_sleeve_length']) > 0 && !empty(apply_filters('glami_feed_generator_pixel_custom_sleeve_length',$this->glami_get_attribute($product, 'sleeve_length',  $options),$product))) {
			$param=$domtree->createElement('PARAM');
			$param->appendChild($domtree->createElement('PARAM_NAME',__('sleeve length','glami-feed-generator-pixel-for-woocommerce')));
			$param->appendChild($domtree->createElement('VAL',apply_filters('glami_feed_generator_pixel_custom_sleeve_length',$this->glami_get_attribute($product, 'sleeve_length',  $options),$product)));
			$shopitem->appendChild($param);
		}

		if (isset($options['glami_trend']) && is_array($options['glami_trend']) && sizeof($options['glami_trend']) > 0 && !empty(apply_filters('glami_feed_generator_pixel_custom_trend',$this->glami_get_attribute($product, 'trend', $options),$product))) {
			$param=$domtree->createElement('PARAM');
			$param->appendChild($domtree->createElement('PARAM_NAME',__('trend','glami-feed-generator-pixel-for-woocommerce')));
			$param->appendChild($domtree->createElement('VAL',apply_filters('glami_feed_generator_pixel_custom_trend',$this->glami_get_attribute($product, 'trend', $options),$product)));
			$shopitem->appendChild($param);
		}

		if (isset($options['glami_certification']) && is_array($options['glami_certification']) && sizeof($options['glami_certification']) > 0 && !empty(apply_filters('glami_feed_generator_pixel_custom_certification',$this->glami_get_attribute($product, 'certification',  $options),$product))) {
			$param=$domtree->createElement('PARAM');
			$param->appendChild($domtree->createElement('PARAM_NAME',__('certification','glami-feed-generator-pixel-for-woocommerce')));
			$param->appendChild($domtree->createElement('VAL',apply_filters('glami_feed_generator_pixel_custom_certification',$this->glami_get_attribute($product, 'certification',  $options),$product)));
			$shopitem->appendChild($param);
		}

		if (isset($options['glami_sustainability']) && is_array($options['glami_sustainability']) && sizeof($options['glami_sustainability']) > 0 && !empty(apply_filters('glami_feed_generator_pixel_custom_sustainability',$this->glami_get_attribute($product, 'sustainability',  $options),$product))) {
			$param=$domtree->createElement('PARAM');
			$param->appendChild($domtree->createElement('PARAM_NAME',__('sustainability','glami-feed-generator-pixel-for-woocommerce')));
			$param->appendChild($domtree->createElement('VAL',apply_filters('glami_feed_generator_pixel_custom_sustainability',$this->glami_get_attribute($product, 'sustainability',  $options),$product)));
			$shopitem->appendChild($param);
		}

		$shop->appendChild($shopitem);
	}

	public function generate() {
		global $wpdb, $blog_id;
		$filename = "glami.xml";
		$tmp_filename="glami_".time().".xml";
		if (defined('WP_ALLOW_MULTISITE') && isset($blog_id)) {
			$filename = "glami_$blog_id.xml";
			$tmp_filename = "glami_".time()."$blog_id.xml";
		}

		$upload_dir = wp_upload_dir();
		$upload_base_dir = $upload_dir['basedir'] . '/glami-feed-generator/';
		$upload_base_url = $upload_dir['baseurl'] . '/glami-feed-generator/';
		$dirname = dirname($upload_base_dir . $tmp_filename);
		if (!is_dir($dirname)) {
			mkdir($dirname, 0755, true);
		}
		$tmp_file = fopen($upload_base_dir . $tmp_filename, 'w');

		$domtree = new DOMDocument('1.0', 'UTF-8');
		$domtree->preserveWhiteSpace = false;
		$domtree->formatOutput = true;

		$xmlRoot = $domtree->createElement("SHOP");
		$shop = $domtree->appendChild($xmlRoot);

		$args = array('post_type' => 'product', 'posts_per_page' => -1, 'post_status' => 'publish');
		$glami_settings = get_option('woocommerce_glami-feed-generator-pixel-for-woocommerce_settings');

		$loop = new WP_Query(apply_filters('glami_feed_generator_pixel_custom_args', $args));
		if ($loop->last_error) :
			print_r($loop->last_error);
			exit;
		endif;
		while ($loop->have_posts()) : $loop->the_post();
			$product = wc_get_product(get_the_ID());
			if (floatval($product->get_price()) > 0) {
				if ($product->is_type('simple')) {
					$this->generate_xml_part($product, $glami_settings, $shop, $domtree);
				} elseif ($product->is_type('variable')) {
					$variations_ids = $product->get_children();
					foreach ($variations_ids as $variation_id) {
						$product = wc_get_product($variation_id);
						if ($product) {
							$this->generate_xml_part($product, $glami_settings, $shop, $domtree);
						}
					}
				}
			}
		endwhile;
		wp_reset_query();
		fwrite($tmp_file,$domtree->saveXML());

		$doc = new DOMDocument;
		if (@$doc->load($upload_base_dir . $tmp_filename) === false) {
			return false;
		}else {
			copy($upload_base_dir . $tmp_filename,$upload_base_dir . $filename);
			update_option('glami_feed_generator_last_run', date_i18n('d-m-Y H:i:s'));
			unlink($upload_base_dir .  $tmp_filename);
			return [
				'url' =>  $upload_base_url  . $filename
			];
		}
	}
}
