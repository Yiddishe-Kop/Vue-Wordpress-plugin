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
    if (strpos($_SERVER['REQUEST_URI'], 'vendor') !== false || is_single()) { // only load vue files if in "vendor" page [checks if 'vendor' in url]
        // wp_enqueue_script('vuejs', 'https://cdn.jsdelivr.net/npm/vue/dist/vue.js', array(), null, false); // development version
        wp_enqueue_script('vuejs', 'https://cdn.jsdelivr.net/npm/vue@2.6.11', array(), null, false);
        wp_enqueue_script('vue_datepicker', 'https://unpkg.com/vuejs-datepicker', array('vuejs'), null, false);
        wp_enqueue_script('vue-mixins', get_template_directory_uri() . '/assets/vue-mixins.js', array('vuejs'), null, false);
        wp_enqueue_script('my_vuecode', plugin_dir_url(__FILE__) . 'vuecode.js', array('vuejs'), false);
        wp_enqueue_style('vue_css', plugin_dir_url(__FILE__) . 'style.css', 'main_style');
    }
}
add_action('wp_enqueue_scripts', 'func_load_vuescripts');

function vue_output_menu_packages($product, $sections) {

    $percentage_hike = get_percentage_hike();

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
            $package_sections_items[$section_name][$wooco_component['name']]['selected_basic'] = array_fill(0, (int) $wooco_component['qty_free'], array('id' => $wooco_component['default'], 'qty' => 1)); // preselects qty free
            $package_sections_items[$section_name][$wooco_component['name']]['selected_deluxe'] = array_fill(0, (int) $wooco_component['qty_free_deluxe'], array('id' => $wooco_component['default'], 'qty' => 1)); // preselects qty free

            $wooco_component_default = isset($wooco_component['default']) ? (int) $wooco_component['default'] : 0;
            $wooco_products = wooco_get_products($wooco_component['type'], $wooco_component[$wooco_component_type], $wooco_component_default);
            foreach ($wooco_products as $wooco_product) {
                $package_sections_items[$section_name][$wooco_component['name']]['items'][$wooco_product['id']] = $wooco_product;
                // add percentage hike for extra items [they only get auto added in get_price_html]
                $original_price = $package_sections_items[$section_name][$wooco_component['name']]['items'][$wooco_product['id']]['price'];
                $package_sections_items[$section_name][$wooco_component['name']]['items'][$wooco_product['id']]['price'] = round($original_price * $percentage_hike);
                $package_sections_items[$section_name][$wooco_component['name']]['items'][$wooco_product['id']]['price_html'] = ''; // delete it - was causing problem for JSON.parse
            }
        }
    }

    $category = get_the_category_by_ID($product->get_category_ids()[0]);
    $vendor = getProductVendor($product);
    $min_people = $category == 'Shabbos' ? $vendor->min_people_shabbos : $vendor->min_people_supper;

    $image_gallery = [];
    if ($attachment_ids = $product->get_gallery_image_ids()) {
        foreach ($attachment_ids as $attachment_id) {
            $image_gallery[] = wp_get_attachment_thumb_url($attachment_id);
        }
    }

    $package_data = [
        'category' => $category,
        'package_name' => $product->get_name(),
        'package_id' => $product_id,
        'discount' => esc_attr(get_post_meta($product_id, 'wooco_discount_percent', true)),
        'qty_min' => esc_attr(get_post_meta($product_id, 'wooco_qty_min', true)),
        'qty_max' => esc_attr(get_post_meta($product_id, 'wooco_qty_max', true)),
        'price' => $product->get_price(),
        'percentage_hike' => $percentage_hike,
        'deluxe_price' => get_post_meta($product_id, '_deluxe_price', true),
        'addToCartUrl' => $product->add_to_cart_url(),
        'sections_items' => $package_sections_items,
        'manage_stock' => 1 == $product->get_manage_stock(),
        'is_in_stock' => $product->is_in_stock(),
        'stock_qty' => $product->get_stock_quantity(),
        'backorders_allowed' => $product->backorders_allowed(),
        'min_people' => $min_people,
        'image_gallery' => $image_gallery,

        'date' => isset($_GET['date']) ? $_GET['date'] : '',
        'people' => isset($_GET['people']) ? $_GET['people'] : '',
    ];

    // echo '<pre>' . print_r($package_sections_items, true) . '</pre>';

    $has_image = true;
    ?>
  <div class="vue-app ui-font" id="vue-app-<?php echo $product_id ?>">
    <pre id="wpData" ref="packageData"><?php echo wp_json_encode($package_data) ?></pre>

      <section class="package-wrapper">
        <?php $image_id = $product->get_image_id();
    if ($image_id) {
        $html = wp_get_attachment_image($image_id, 'large', false, ['class' => 'package-image']);
    } else {
        $has_image = false;
        $html = '';
    }
    echo apply_filters('woocommerce_single_product_image_thumbnail_html', $html, $image_id);?>

      <pill v-if="stock.manage && stock.qty < 5" class="red stock-alert">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" height="1.2em" class="icon"><path class="primary" d="M12 2a10 10 0 1 1 0 20 10 10 0 0 1 0-20z"/><path class="secondary" d="M12 18a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm1-5.9c-.13 1.2-1.88 1.2-2 0l-.5-5a1 1 0 0 1 1-1.1h1a1 1 0 0 1 1 1.1l-.5 5z"/></svg>
        {{stock.inStock ? 'Hurry! only ' + stock.qty + ' available' : 'Sorry, out of stock'}}
      </pill>

      <div class="package-info">
        <h1 class="package-title serif thin smcp<?php echo $has_image ? ' move-up' : '' ?>"><span><?php echo $product->get_name() ?></span></h1>
        <p><?php echo $product->get_description() ?></p>
      </div>

      <div v-if="imageGallery && imageGallery.length" class="image-gallery">
        <img v-for="img in imageGallery" :src="img" alt="">
      </div>

        <deluxe-switch
          v-if="basePrice.basic != basePrice.deluxe"
          @change="onDeluxeChange"
          id="<?php echo $product_id ?>"
          label1="Basic"
          label2="Deluxe"
          :selected-val="selectedPackage"
        ></deluxe-switch>

        <h4 class="top-summary">
          <b class="total b">${{packagePrice}}</b>
        </h4>

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
            v-if="shouldShowComp(component.info.show_in)"
          >
          <div v-if="component.info.custom_qty == 'yes' && component[selectedVarName].length === 0">
            <button-cta @click="vEvent.$emit(`${packageName}addline`, {package: packageName, section: sectionTitle, component: componentName})" class="only-icon">
              <svg xmlns="http://www.w3.org/2000/svg" width="22px" viewBox="0 0 24 24" class="icon-add-circle"><circle cx="12" cy="12" r="10" class="primary"/><path class="secondary" d="M13 11h4a1 1 0 0 1 0 2h-4v4a1 1 0 0 1-2 0v-4H7a1 1 0 0 1 0-2h4V7a1 1 0 0 1 2 0v4z"/></svg>
            </button-cta>
          </div>
            <transition-group name="slide-in">
              <div v-for="(sel, j) in component[selectedVarName]" :key="componentName + j" class="food-line">
                  <food-dropdown
                    empty-text="Please choose..."
                    :in-package="packageName"
                    :in-section="sectionTitle"
                    :in-component="componentName"
                    :component="component"
                    :selection="component[selectedVarName][j]"
                    :index="j"
                    :add-btn="component.info.custom_qty == 'yes' && j == component[selectedVarName].length - 1"
                    :is-deluxe="isDeluxe"
                    :num-of-items="Object.keys(component.items).length"
                    :is-extra="j+1 > component.info[isDeluxe ? 'qty_free_deluxe' : 'qty_free']"
                  >
                    <dropdown-item
                      v-for="(item, i) in component.items"
                      :key="item.id"
                      :item="item"
                      :is-extra="j+1 > component.info[isDeluxe ? 'qty_free_deluxe' : 'qty_free']"
                      :in-package="packageName"
                      :in-section="sectionTitle"
                      :in-component="componentName"
                      :index="j"
                    >
                    </dropdown-item>
                  </food-dropdown>
                </div>
            </transition-group>
          </food-component>
        </meal-section>
        <form @submit.prevent="addToCart" class="summary">
          <i class="icon flaticon-users"></i>
          <input name="wooco_people" type="number" :min="minPeople" v-model="quantity" class="form-control" required >
          <i class="icon flaticon-calendar"></i>
          <vuejs-datepicker
          v-model="datepicker.date"
          :disabled-dates="disabledDates"
          :highlighted="datepicker.highlighted"
          class="vue-datepicker"
          input-class="datepicker"
          :bootstrap-styling="true"
          format="MM/dd/yyyy"
          name="wooco_date"
          :required="true"
          ></vuejs-datepicker>
          <div class="spacer"></div>
          <div v-if="stock.inStock" style="align-self:flex-end;">
            <span class="total">Total: <b>${{(packagePrice * quantity) + extraPrice.sum}}</b></span>
            <button-cta type="submit" :class="[cartBtn.class]" v-html="cartBtn.html"></button-cta>
          </div>
        </form>
  </div>

      <script>
        let app<?php echo $product_id ?> = new Vue({
          el: '#vue-app-<?php echo $product_id ?>',
          mixins: [shabbosPackageMixin, datepickerMixin],
          components: {
            vuejsDatepicker
          },
          data() {
            return {
              id: <?php echo $product_id ?>,
              vEvent: window.vEvent
            }
          }
        })
      </script>
    </section>
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
