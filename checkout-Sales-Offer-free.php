<?php
/*
Plugin Name: Checkout Sales Offer Free
Plugin URI: https://saranhosting.com/checkout-sales-offer/
Description: افزونه‌ای برای نمایش محصولات در صفحه پرداخت نسخه رایگان.
Version: 1.1
Author: SaranHosting.Com
Author URI: https://saranhosting.com
Text Domain: checkout-sales-offer-free
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}


add_action('init', function () {
    error_log('Plugin code is running!');
});





add_action('woocommerce_review_order_before_payment', 'ccs_test_product_display');



function ccs_test_product_display()
{
    // Retrieve settings
    $product_id = get_option('ccs_free_product_id', 200); // Default: 200
    $threshold = get_option('ccs_cart_threshold', ''); // Default: empty (not set)
    $suffix = get_option('ccs_title_suffix', 'را به سفارش خود اضافه کنید');


    // Ensure threshold is valid
    if ($threshold === '' || !is_numeric($threshold)) {
        $threshold = null; // Treat as not set
    }

    $enable_display = get_option('ccs_enable_item_display', 1); // پیش‌فرض فعال
    if (!$enable_display) {
        return; // اگر غیرفعال است، نمایش داده نشود
    }

    $product = wc_get_product($product_id);
    $cart_total = WC()->cart ? WC()->cart->subtotal : 0;

    if ($product) {
        $product_title = $product->get_name(); // Get the product title
        $product_title_with_suffix = $product_title . ' ' . esc_html($suffix); // Append suffix
        $product_description = $product->get_description(); // Get the full product description

        // Wrapper
        echo '<div class="ccs-product-box reyhoon-shipping-container"  id="free-version"  >';

        // Determine price or free based on threshold
        $is_free = ($threshold !== null && $cart_total >= $threshold);

        // Toggle Button with Text
        $product_in_cart = false;
        foreach (WC()->cart->get_cart() as $cart_item) {
            if ($cart_item['product_id'] == $product_id) {
                $product_in_cart = true;
                break;
            }
        }
        $checked = $product_in_cart ? 'checked' : '';
        $toggle_text = $product_in_cart ? 'محصول را حذف کن' : 'محصول را اضافه کن';
        $action = $is_free ? 'ccs_add_free_product' : 'ccs_add_to_cart';



        // Image
        echo '<div style="margin-left: 20px;">';
        echo '<img src="' . esc_url(wp_get_attachment_url($product->get_image_id())) . '" alt="' . esc_attr($product->get_name()) . '" style="width: 80px; height: 80px; object-fit: cover;">';
        echo '</div>';







        // Title, Caption, and Price
        echo '<div style="flex: 1;">';
        echo '<h3 style="margin: 0; font-size: 16px;">' . esc_html($product_title_with_suffix) . '</h3>';
        echo '<p style="margin: 5px 0; font-size: 14px; color: #555;">' . esc_html($product_description) . '</p>';




        // Price or Free
        if ($is_free) {
            echo '<p style="color: green; font-weight: bold;">' . __('رایگان', 'checkout-cross-sell') . '</p>';
        } else {
            echo '<p class="ccs-product-price" style="font-weight: bold;">' . wc_price($product->get_price()) . '</p>';
        }
        echo '</div>';


        // Toggle Button with Loader
        echo '<div style="margin-right: 20px; text-align: center; position: relative;">';
        echo '<label class="ccs-toggle">';
        echo '<input type="checkbox" class="ccs-toggle-input" data-action="' . esc_attr($action) . '" data-product-id="' . esc_attr($product_id) . '" ' . $checked . '>';
        echo '<span class="ccs-toggle-slider"></span>';
        echo '</label>';
        echo '<div class="loader" style="display: none;"></div>'; // Loader element
        echo '<p style="margin-top: 5px; font-size: 14px;">' . esc_html($toggle_text) . '</p>';
        echo '</div>';


        // Close Wrappe
        echo '</div>';
    }
}






// Enqueue JavaScript for AJAX add-to-cart
add_action('wp_enqueue_scripts', 'ccs_enqueue_scripts');

function ccs_enqueue_scripts()
{
    if (is_checkout()) {
        wp_enqueue_script('ccs-script', plugin_dir_url(__FILE__) . 'assets/js/ccs-script.js', ['jquery'], '1.0', true);
        wp_localize_script('ccs-script', 'ccs_ajax_free', ['ajax_url' => admin_url('admin-ajax.php')]);
    }
}
function ccs_make_free_product_in_cart($cart)
{
    if (is_admin() && !defined('DOING_AJAX')) {
        return;
    }

    static $processed = false; // Prevent recursion
    if ($processed) {
        return;
    }
    $processed = true;

    $free_product_id = 200; // Free product ID
    $threshold = 500; // Cart subtotal threshold for free product

    // Check cart total
    if ($cart->subtotal >= $threshold) {
        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            // If the product is marked as free, set its price to 0
            if ($cart_item['product_id'] == $free_product_id && isset($cart_item['free_item']) && $cart_item['free_item'] === true) {
                $cart_item['data']->set_price(0); // Set the price to 0
            }
        }
    }
}
add_action('woocommerce_before_calculate_totals', 'ccs_make_free_product_in_cart', 10, 1);

add_filter('woocommerce_calculated_total', 'ccs_adjust_cart_total', 10, 2);

function ccs_adjust_cart_total($total, $cart)
{
    $free_product_id = 200; // Free product ID

    foreach ($cart->get_cart() as $cart_item) {
        if ($cart_item['product_id'] == $free_product_id && isset($cart_item['free_item']) && $cart_item['free_item'] === true) {
            $total -= $cart_item['data']->get_regular_price(); // Subtract the original price of the free product
        }
    }

    return $total;
}



add_filter('woocommerce_cart_item_subtotal', 'ccs_force_free_product_subtotal', 10, 3);

function ccs_force_free_product_subtotal($subtotal, $cart_item, $cart_item_key)
{
    if (isset($cart_item['free_item']) && $cart_item['free_item'] === true) {
        return wc_price(0); // Display 0 for free products
    }
    return $subtotal;
}





add_filter('woocommerce_cart_item_price', 'ccs_force_free_product_price', 10, 3);

function ccs_force_free_product_price($price, $cart_item, $cart_item_key)
{
    if (isset($cart_item['free_item']) && $cart_item['free_item'] === true) {
        return wc_price(0); // نمایش قیمت ۰
    }
    return $price;
}


// Handle AJAX add-to-cart
add_action('wp_ajax_ccs_add_to_cart', 'ccs_add_to_cart');
add_action('wp_ajax_nopriv_ccs_add_to_cart', 'ccs_add_to_cart');

function ccs_add_to_cart()
{
    $product_id = intval($_POST['product_id']);

    if ($product_id && WC()->cart) {
        WC()->cart->add_to_cart($product_id);
        wp_send_json_success(['message' => __('محصول به سبد خرید اضافه شد!', 'checkout-cross-sell')]);
    }

    wp_send_json_error(['message' => __('اضافه کردن محصول به سبد خرید شکست خورد.', 'checkout-cross-sell')]);
}

add_action('wp_ajax_ccs_add_free_product', 'ccs_add_free_product');
add_action('wp_ajax_nopriv_ccs_add_free_product', 'ccs_add_free_product');

function ccs_add_free_product()
{
    $product_id = intval($_POST['product_id']);
    $threshold = 500; // آستانه قیمت سبد خرید

    // بررسی مجموع قیمت سبد خرید
    if (WC()->cart->subtotal >= $threshold) {
        // بررسی آیا محصول قبلاً اضافه شده است
        foreach (WC()->cart->get_cart() as $cart_item) {
            if ($cart_item['product_id'] == $product_id && isset($cart_item['free_item']) && $cart_item['free_item'] === true) {
                wp_send_json_error(['message' => __('این محصول رایگان قبلاً به سبد خرید اضافه شده است.', 'checkout-cross-sell')]);
            }
        }

        // اضافه کردن محصول به سبد به صورت رایگان
        if ($product_id && WC()->cart) {
            WC()->cart->add_to_cart($product_id, 1, '', '', ['free_item' => true]);
            wp_send_json_success(['message' => __('محصول رایگان به سبد خرید اضافه شد!', 'checkout-cross-sell')]);
        }
    } else {
        wp_send_json_error(['message' => __('شما واجد شرایط دریافت این محصول نیستید.', 'checkout-cross-sell')]);
    }

    wp_die();
}


add_filter('woocommerce_cart_item_name', 'ccs_add_free_item_label', 10, 3);

function ccs_add_free_item_label($item_name, $cart_item, $cart_item_key)
{
    if (isset($cart_item['free_item']) && $cart_item['free_item'] === true) {
        // Check if 'رایگان' is already appended to avoid duplication
        if (strpos($item_name, __('رایگان', 'checkout-cross-sell')) === false) {
            $item_name .= ' <span style="color: green;">(' . __('رایگان', 'checkout-cross-sell') . ')</span>';
        }
    }
    return $item_name;
}


add_action('wp_ajax_ccs_remove_from_cart', 'ccs_remove_from_cart');
add_action('wp_ajax_nopriv_ccs_remove_from_cart', 'ccs_remove_from_cart');

function ccs_remove_from_cart()
{
    $product_id = intval($_POST['product_id']);

    if (WC()->cart) {
        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
            if ($cart_item['product_id'] == $product_id) {
                WC()->cart->remove_cart_item($cart_item_key);
                wp_send_json_success(['message' => __('محصول از سبد خرید حذف شد!', 'checkout-cross-sell')]);
            }
        }
    }

    wp_send_json_error(['message' => __('محصول در سبد خرید پیدا نشد.', 'checkout-cross-sell')]);
}


add_action('wp_enqueue_scripts', 'my_plugin_enqueue_styles');

function my_plugin_enqueue_styles()
{
    // Check if we're on the checkout page
    if (is_checkout()) {
        wp_enqueue_style(
            'my-plugin-styles-free', // Handle for the stylesheet
            plugin_dir_url(__FILE__) . '/assets/css/style.css', // Path to the CSS file
            array(), // Dependencies (leave empty if none)
            '1.0', // Version number
            'all' // Media type (e.g., 'all', 'screen', 'print')
        );
    }
}


// Add the settings page to the admin menu
add_action('admin_menu', 'ccs_add_admin_menu');
function ccs_add_admin_menu()
{
    add_menu_page(
        __('تنظیمات پیشنهاد محصول', 'checkout-cross-sell'), // Page title
        __('پیشنهاد محصول', 'checkout-cross-sell'),          // Menu title
        'manage_options',                                    // Capability
        'ccs_settings',                                      // Menu slug
        'ccs_settings_page',                                 // Callback function
        'dashicons-cart',                                    // Icon
        20                                                   // Position
    );
}

add_action('admin_init', 'ccs_register_settings');
function ccs_register_settings()
{
    register_setting('ccs_settings_group', 'ccs_free_product_id');
    register_setting('ccs_settings_group', 'ccs_cart_threshold');
    register_setting('ccs_settings_group', 'ccs_enable_item_display');
    register_setting('ccs_settings_group', 'ccs_title_suffix');

}

// Render the settings page
function ccs_settings_page()
{
    ?>
    <div class="wrap">
        <h1><?php _e('تنظیمات پیشنهاد فروش در صفحه پرداخت', 'checkout-cross-sell'); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('ccs_settings_group');
            do_settings_sections('ccs_settings_group');
            ?>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label
                            for="ccs_enable_item_display"><?php _e('فعال کردن نمایش آیتم در صفحه پرداخت', 'checkout-cross-sell'); ?></label>
                    </th>
                    <td>
                        <!-- Toggle Button -->
                        <label class="ccs-toggle">
                            <input type="checkbox" name="ccs_enable_item_display" id="ccs_enable_item_display" value="1"
                                <?php checked(1, get_option('ccs_enable_item_display'), true); ?>>
                            <span class="ccs-toggle-slider"></span>
                        </label>
                        <p class="description">
                            <?php _e('با فعال کردن این گزینه، آیتم مورد نظر در صفحه پرداخت نمایش داده می‌شود.', 'checkout-cross-sell'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label
                            for="ccs_free_product_id"><?php _e('انتخاب محصول پیشنهادی', 'checkout-cross-sell'); ?></label>
                    </th>
                    <td>

                        <?php
                        // Get the current product ID and name
                        $current_product_id = get_option('ccs_free_product_id', '');
                        $current_product_name = '';
                        if (!empty($current_product_id)) {
                            $current_product = wc_get_product($current_product_id);
                            $current_product_name = $current_product ? $current_product->get_name() : '';
                        }
                        ?>

                        <input type="hidden" name="ccs_free_product_id" id="ccs_free_product_id"
                            value="<?php echo esc_attr($current_product_id); ?>" />
                        <input type="text" id="ccs_free_product_search"
                            value="<?php echo $current_product_name ? esc_attr($current_product_name . ' (انتخاب شده)') : ''; ?>"
                            placeholder="<?php _e('جستجوی محصول...', 'checkout-cross-sell'); ?>" class="regular-text" />
                        <p class="description">
                            <?php _e('محصول مورد نظر را جستجو کنید و انتخاب نمایید.', 'checkout-cross-sell'); ?>
                        </p>
                        <!-- <div id="ccs_free_product_results"
                            style="border: 1px solid #ccc; max-height: 200px; overflow-y: auto; display: none;"></div> -->
                        <!-- Results container for displaying search results -->
                        <div id="ccs_free_product_results"
                            class="ccs-product-results"
                            style="border: 1px solid #ccc; max-height: 200px; overflow-y: auto; display: none;">
                        </div>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="ccs_title_suffix"><?php _e('پسوند عنوان محصول', 'checkout-cross-sell'); ?></label>
                    </th>
                    <td>
                        <input type="text" name="ccs_title_suffix" id="ccs_title_suffix"
                            value="<?php echo esc_attr(get_option('ccs_title_suffix', 'را به سفارش خود اضافه کنید')); ?>"
                            class="regular-text">
                        <p class="description">
                            <?php _e('متنی که به انتهای نام محصول در عنوان نمایش داده می‌شود.', 'checkout-cross-sell'); ?>
                        </p>
                    </td>
                </tr>


                <tr>
                    <th scope="row">
                        <label for="ccs_cart_threshold"><?php _e('حداقل مبلغ سبد خرید', 'checkout-cross-sell'); ?></label>
                    </th>
                    <td>
                        <input type="number" name="ccs_cart_threshold" id="ccs_cart_threshold"
                            value="<?php echo esc_attr(get_option('ccs_cart_threshold', '')); ?>" class="regular-text"
                            placeholder="500">
                        <p class="description">
                            <?php _e('حداقل مبلغ سبد خرید برای دریافت محصول پیشنهادی به صورت رایگان را تنظیم کنید. در صورت خالی ماندن، محصول با قیمت اصلی نمایش داده می‌شود.', 'checkout-cross-sell'); ?>
                        </p>

                    </td>
                </tr>
            </table>
            <?php submit_button('ذخیره تنظیمات'); ?>
        </form>
    </div>
    <?php
}


add_action('admin_enqueue_scripts', 'ccs_admin_styles');

function ccs_admin_styles()
{
    wp_enqueue_style('ccs-admin-styles', plugin_dir_url(__FILE__) . 'assets/css/admin-style.css', [], '1.0', 'all');
}

add_action('admin_enqueue_scripts', 'ccs_enqueue_admin_scripts');

function ccs_enqueue_admin_scripts()
{
    wp_enqueue_script('ccs-free-admin-script', plugin_dir_url(__FILE__) . 'assets/js/admin-script.js', ['jquery'], '1.0', true);
    wp_localize_script('ccs-free-admin-script', 'ccs_ajax_free', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('ccs_search_products_free'),
    ]);

}

add_action('admin_enqueue_scripts', function () {
    wp_enqueue_script('ccs-free-admin-script', plugin_dir_url(__FILE__) . 'assets/js/admin-script.js', ['jquery'], '1.0', true);
}, 20); // Higher priority ensures this runs later



add_action('wp_ajax_ccs_search_products_free', 'ccs_search_products_free');
add_action('wp_ajax_nopriv_ccs_search_products_free', 'ccs_search_products_free');

function ccs_search_products_free()
{
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ccs_search_products_free')) {
        wp_send_json_error(['message' => 'Invalid nonce']);
        return;
    }

    // Get search term
    $search_term = isset($_POST['search_term']) ? sanitize_text_field($_POST['search_term']) : '';

    if (empty($search_term)) {
        wp_send_json_error(['message' => 'No search term provided']);
        return;
    }

    // Log the search term for debugging
    error_log("Search Term: " . $search_term);

    // Query products
    $args = [
        'post_type' => 'product',
        'posts_per_page' => 10,
        's' => $search_term, // WordPress search parameter
        'post_status' => 'publish', // Ensure only published products are returned
    ];

    $query = new WP_Query($args);

    // Log query results
    error_log("Query results found: " . $query->found_posts);

    if (!$query->have_posts()) {
        wp_send_json_success(['products' => []]); // Return an empty array if no products found
        return;
    }

    // Build response
    $products = [];
    while ($query->have_posts()) {
        $query->the_post();
        $product = wc_get_product(get_the_ID());
        $products[] = [
            'id' => $product->get_id(),
            'name' => $product->get_name(),
        ];
    }

    // Reset post data
    wp_reset_postdata();

    // Log the products array
    error_log("Products: " . print_r($products, true));

    wp_send_json_success(['products' => $products]); // Return the products
}
