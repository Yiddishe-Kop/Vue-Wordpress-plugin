<?php

/**
 * Plugin Name: WordPress Vue integration
 * Description: Using Vue in WordPress!
 * Plugin URI:        https://yiddishe-kop.com/
 * Version:           0.0.1
 * Author:            Yehuda Neufeld
 * Author URI:        https://yiddishe-kop.com/
 */

//Register scripts to use
function func_load_vuescripts() {
    wp_register_script('wpvue_vuejs', 'https://cdn.jsdelivr.net/npm/vue/dist/vue.js');
    wp_register_script('my_vuecode', plugin_dir_url(__FILE__) . 'vuecode.js', 'wpvue_vuejs', true);
    wp_register_style('vue_css', plugin_dir_url(__FILE__) . 'style.css', 'main_style');
    wp_enqueue_script('wpvue_vuejs');
    wp_enqueue_script('my_vuecode');
    wp_enqueue_style('vue_css');
}
add_action('wp_enqueue_scripts', 'func_load_vuescripts');

//Add shortscode
function vue_output_menu_packages($product, $sections) {

    $product_id = $product->get_id();
    $components = $product->get_components();
    $package_sections_items = [];
    foreach ($sections as $key => $section_name) {
        $package_sections_items[$section_name] = [];
        foreach ($components as $wooco_component) {
            if ($wooco_component['section'] != $key || (($wooco_component_type = $wooco_component['type']) === '') || empty($wooco_component[$wooco_component_type])) {
                continue; // only get from current section
            }
            $package_sections_items[$section_name][$wooco_component['name']]['info'] = $wooco_component;
            $package_sections_items[$section_name][$wooco_component['name']]['selected'] = array_fill(0, (int) $wooco_component['qty_free'], null); // preselects qty free
            $package_sections_items[$section_name][$wooco_component['name']]['selected_basic'] = array_fill(0, (int) $wooco_component['qty_free'], null); // preselects qty free
            $package_sections_items[$section_name][$wooco_component['name']]['selected_deluxe'] = array_fill(0, (int) $wooco_component['qty_free_deluxe'], null); // preselects qty free

            $wooco_component_default = isset($wooco_component['default']) ? (int) $wooco_component['default'] : 0;
            $wooco_products = wooco_get_products($wooco_component['type'], $wooco_component[$wooco_component_type], $wooco_component_default);
            foreach ($wooco_products as $wooco_product) {
                $package_sections_items[$section_name][$wooco_component['name']]['items'][$wooco_product['id']] = $wooco_product;
                $package_sections_items[$section_name][$wooco_component['name']]['items'][$wooco_product['id']]['price_html'] = ''; // delete it - was cusing problem for JSON.parse
            }
        }
    }

    $package_data = [
        'package_name' => $product->get_name(),
        'package_id' => $product_id,
        'discount' => esc_attr(get_post_meta($product_id, 'wooco_discount_percent', true)),
        'qty_min' => esc_attr(get_post_meta($product_id, 'wooco_qty_min', true)),
        'qty_max' => esc_attr(get_post_meta($product_id, 'wooco_qty_max', true)),
        'price' => $product->get_price(),
        'deluxe_price' => get_post_meta($product_id, '_deluxe_price', true),
        'pricing' => $product->get_pricing(),
        'addToCartUrl' => $product->add_to_cart_url(),
        'sections_items' => $package_sections_items,
    ];

    // echo '<pre>' . print_r($package_sections_items, true) . '</pre>';

    ?>
    <div class="vue-app ui-font" id="vue-app-<?php echo $product_id ?>">
      <pre id="wpData" ref="packageData"><?php echo wp_json_encode($package_data) ?></pre>
      <deluxe-switch
        @change="onDeluxeChange"
        id="<?php echo $product_id ?>"
        label1="Basic"
        label2="Deluxe"
        :selected-val="selectedPackage"
      ></deluxe-switch>
      <meal-section v-for="(section, sectionTitle) in packageData" :key="sectionTitle" :title="sectionTitle" :section="section">
        <food-component
          v-for="(component, componentName) in section"
          :key="componentName"
          :title="componentName"
          :desc="component.info.desc"
          :comp="component"
          :in-package="packageName"
          :in-section="sectionTitle"
          :is-deluxe="isDeluxe"
          v-if="component.info.deluxe_only != 'yes' || isDeluxe"
        >
          <transition-group name="slide-in">
            <div v-for="(sel, j) in component.selected" :key="componentName + j" class="food-line">
                <food-dropdown
                  empty-text="Please choose..."
                  :in-package="packageName"
                  :in-section="sectionTitle"
                  :in-component="componentName"
                  :index="j"
                  :add-btn="component.info.custom_qty == 'yes' && j == component.selected.length - 1"
                  :comp="component"
                >
                  <dropdown-item
                    v-for="(item, i) in component.items"
                    :key="item.id"
                    :image="item.image"
                    :name="item.name"
                    :price="j+1 > component.info[isDeluxe ? 'qty_free_deluxe' : 'qty_free'] ? item.price : null"
                    :in-package="packageName"
                    :in-section="sectionTitle"
                    :in-component="componentName"
                    :item-id="item.id"
                    :index="j"
                  >
                  </dropdown-item>
                </food-dropdown>
              </div>
          </transition-group>
        </food-component>
      </meal-section>
      <form :action="addToCartUrl" method="post" enctype="multipart/form-data" class="summary">
        <input name="wooco_ids" type="hidden" :value="wooco_ids">
        <input name="is_deluxe" type="hidden" :value="isDeluxe">
        <input name="wooco_total" type="hidden" :value="totalPrice">
        <div class="spacer"></div>
        <span class="total">Total: <b>${{totalPrice}}</b></span>
        <button-cta type="submit" name="add-to-cart" :value="packageId">Add to Cart</button-cta>
      </form>
    </div>

    <script>
      let app<?php echo $product_id ?> = new Vue({
        el: '#vue-app-<?php echo $product_id ?>',
        mixins: [shabbosPackageMixin]
      })
    </script>
    <?php
}

function wooco_get_products($type, $data, $default = 0) {
    $wooco_products = $wooco_args = array();
    $ids = explode(',', $data);
    switch ($type) {
        case 'products':
            if (!in_array($default, $ids)) {
                //check default value
                array_unshift($ids, $default);
            }

            foreach ($ids as $id) {
                $wooco_product = wc_get_product($id);

                if (!$wooco_product) {
                    continue;
                }

                if ($wooco_product->is_type('simple') || $wooco_product->is_type('variation')) {
                    $wooco_product_img = wp_get_attachment_image_src($wooco_product->get_image_id(), 'thumbnail');
                    $wooco_products[] = array(
                        'id' => $wooco_product->get_id(),
                        'name' => $wooco_product->get_name(),
                        'price' => $wooco_product->get_price(),
                        'link' => get_permalink($wooco_product->get_id()),
                        'price_html' => htmlentities($wooco_product->get_price_html()),
                        'image' => $wooco_product_img[0],
                        'purchasable' => $wooco_product->is_in_stock() && $wooco_product->is_purchasable() ? 'yes' : 'no',
                    );
                }

                if ($wooco_product->is_type('variable')) {
                    $childs = $wooco_product->get_children();
                    if (!empty($childs)) {
                        foreach ($childs as $child) {
                            $wooco_product_child = wc_get_product($child);
                            if (!$wooco_product_child) {
                                continue;
                            }
                            $wooco_product_child_img = wp_get_attachment_image_src($wooco_product_child->get_image_id(), 'thumbnail');
                            $wooco_products[] = array(
                                'id' => $wooco_product_child->get_id(),
                                'name' => $wooco_product_child->get_name(),
                                'price' => $wooco_product_child->get_price(),
                                'link' => get_permalink($wooco_product_child->get_id()),
                                'price_html' => htmlentities($wooco_product_child->get_price_html()),
                                'image' => $wooco_product_child_img[0],
                                'purchasable' => $wooco_product_child->is_in_stock() && $wooco_product_child->is_purchasable() ? 'yes' : 'no',
                            );
                        }
                    }
                }
            }
            break;
        case 'categories':
            $has_default = false;

            $wooco_args = array(
                'post_type' => 'product',
                'post_status' => 'publish',
                'ignore_sticky_posts' => 1,
                'posts_per_page' => '100',
                'tax_query' => array(
                    array(
                        'taxonomy' => 'product_cat',
                        'field' => 'term_id',
                        'terms' => $ids,
                        'operator' => 'IN',
                    ),
                ),
            );

            $wooco_loop = new WP_Query($wooco_args);
            if ($wooco_loop->have_posts()) {
                while ($wooco_loop->have_posts()) {
                    $wooco_loop->the_post();
                    $wooco_id = get_the_ID();
                    $wooco_product = wc_get_product($wooco_id);

                    if (!$wooco_product) {
                        continue;
                    }

                    if ($wooco_product->is_type('simple')) {
                        $wooco_product_img = wp_get_attachment_image_src($wooco_product->get_image_id(), 'thumbnail');
                        $wooco_products[] = array(
                            'id' => $wooco_product->get_id(),
                            'name' => $wooco_product->get_name(),
                            'price' => $wooco_product->get_price(),
                            'link' => get_permalink($wooco_product->get_id()),
                            'price_html' => htmlentities($wooco_product->get_price_html()),
                            'image' => $wooco_product_img[0],
                            'purchasable' => $wooco_product->is_in_stock() && $wooco_product->is_purchasable() ? 'yes' : 'no',
                        );
                        if ($wooco_product->get_id() == $default) {
                            $has_default = true;
                        }
                    }

                    if ($wooco_product->is_type('variable')) {
                        $childs = $wooco_product->get_children();
                        if (!empty($childs)) {
                            foreach ($childs as $child) {
                                $wooco_product_child = wc_get_product($child);
                                if (!$wooco_product_child) {
                                    continue;
                                }
                                $wooco_product_child_img = wp_get_attachment_image_src($wooco_product_child->get_image_id(), 'thumbnail');
                                $wooco_products[] = array(
                                    'id' => $wooco_product_child->get_id(),
                                    'name' => $wooco_product_child->get_name(),
                                    'price' => $wooco_product_child->get_price(),
                                    'link' => get_permalink($wooco_product_child->get_id()),
                                    'price_html' => htmlentities($wooco_product_child->get_price_html()),
                                    'image' => $wooco_product_child_img[0],
                                    'purchasable' => $wooco_product_child->is_in_stock() && $wooco_product_child->is_purchasable() ? 'yes' : 'no',
                                );
                                if ($wooco_product_child->get_id() == $default) {
                                    $has_default = true;
                                }
                            }
                        }
                    }
                }
                wp_reset_postdata();
            }

            if (!$has_default) {
                //add default product
                $wooco_product_default = wc_get_product($default);
                if ($wooco_product_default) {
                    $wooco_product_default_img = wp_get_attachment_image_src($wooco_product_default->get_image_id(), 'thumbnail');
                    array_unshift($wooco_products, array(
                        'id' => $wooco_product_default->get_id(),
                        'name' => $wooco_product_default->get_name(),
                        'price' => $wooco_product_default->get_price(),
                        'link' => get_permalink($wooco_product_default->get_id()),
                        'price_html' => htmlentities($wooco_product_default->get_price_html()),
                        'image' => $wooco_product_default_img[0],
                        'purchasable' => $wooco_product_default->is_in_stock() && $wooco_product_default->is_purchasable() ? 'yes' : 'no',
                    ));
                }
            }

            break;
    }

    if (count($wooco_products) > 0) {
        return $wooco_products;
    }

    return false;
}
