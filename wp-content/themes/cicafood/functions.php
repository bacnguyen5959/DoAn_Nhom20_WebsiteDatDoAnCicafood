<?php
/**
 * Cicafood Child Theme — functions.php
 * Child theme của Storefront (WooCommerce official theme)
 */

// ============================================================
// 1. Enqueue styles & scripts
// ============================================================
add_action('wp_enqueue_scripts', function () {

    // CSS theme cha (Storefront)
    wp_enqueue_style(
        'storefront-style',
        get_template_directory_uri() . '/style.css'
    );

    // CSS child theme (brand override)
    wp_enqueue_style(
        'cicafood-style',
        get_stylesheet_uri(),
        ['storefront-style'],
        '1.0.1'
    );

    // Google Fonts — Inter
    wp_enqueue_style(
        'cicafood-fonts',
        'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap',
        [],
        null
    );

    // Font Awesome 6
    wp_enqueue_style(
        'font-awesome',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css',
        [],
        '6.5.0'
    );

    // Tailwind CSS — Play CDN cho development
    wp_enqueue_script(
        'tailwindcss',
        'https://cdn.tailwindcss.com?plugins=forms,typography,aspect-ratio',
        [],
        null,
        false
    );

    // Child theme JS
    if (file_exists(get_stylesheet_directory() . '/assets/js/main.js')) {
        wp_enqueue_script(
            'cicafood-main',
            get_stylesheet_directory_uri() . '/assets/js/main.js',
            [],
            '1.0.1',
            true
        );
    }

    // Luôn localize cicafoodAjax (dùng cho cả main.js và inline scripts)
    wp_add_inline_script('cicafood-main', '', 'before');
    wp_localize_script('cicafood-main', 'cicafoodAjax', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('cicafood_nonce'),
        'siteurl' => get_site_url(),
    ]);
});

// ============================================================
// 1b. Ẩn Storefront header/footer hooks mặc định
// ============================================================
add_action('after_setup_theme', function () {
    remove_action('storefront_header', 'storefront_header_container',                 0);
    remove_action('storefront_header', 'storefront_skip_links',                       5);
    remove_action('storefront_header', 'storefront_social_icons',                    10);
    remove_action('storefront_header', 'storefront_site_branding',                   20);
    remove_action('storefront_header', 'storefront_secondary_navigation',            30);
    remove_action('storefront_header', 'storefront_product_search',                  40);
    remove_action('storefront_header', 'storefront_header_container_close',          41);
    remove_action('storefront_header', 'storefront_primary_navigation_wrapper',      42);
    remove_action('storefront_header', 'storefront_primary_navigation',              50);
    remove_action('storefront_header', 'storefront_header_cart',                     60);
    remove_action('storefront_header', 'storefront_primary_navigation_wrapper_close',68);
    remove_action('storefront_before_content', 'storefront_header_widget_region',   10);
    remove_action('storefront_before_content', 'woocommerce_breadcrumb',            10);
    remove_action('storefront_footer',  'storefront_footer_widgets',                10);
    remove_action('storefront_footer',  'storefront_credit',                        20);
    // Xóa chữ "Sửa" (edit_post_link) của Storefront
    remove_action('storefront_page',             'storefront_edit_post_link', 30);
    remove_action('storefront_single_post_bottom','storefront_edit_post_link',  5);
}, 20);

// Xóa breadcrumb WooCommerce (chữ "Nhà hàng", "Voucher"...)
add_action('init', function () {
    remove_action('storefront_before_content', 'woocommerce_breadcrumb', 10);
    add_filter('storefront_show_page_title', '__return_false');
    add_filter('woocommerce_show_page_title', '__return_false');
});

// An page title (chu Nha hang, Voucher, Don hang...)
add_filter('the_title', function($title, $id = null) {
    if (is_admin() || !in_the_loop() || !is_main_query()) return $title;
    if (is_page()) return '';
    return $title;
}, 10, 2);

// An .entry-header cua Storefront
add_action('storefront_page', function() {
    remove_action('storefront_page', 'storefront_page_header', 10);
}, 1);



// ============================================================
// 2. Theme supports
// ============================================================
add_action('after_setup_theme', function () {
    add_theme_support('woocommerce');
    add_theme_support('wc-product-gallery-zoom');
    add_theme_support('wc-product-gallery-lightbox');
    add_theme_support('wc-product-gallery-slider');
    add_image_size('cicafood-card',   800, 450, true);
    add_image_size('cicafood-cover', 1200, 400, true);
    add_image_size('cicafood-thumb',  400, 300, true);
});

// ============================================================
// 3. Load text domain
// ============================================================
add_action('init', function () {
    load_child_theme_textdomain('cicafood', get_stylesheet_directory() . '/languages');
});

// ============================================================
// 4. Admin footer branding
// ============================================================
add_filter('admin_footer_text', function () {
    return 'Cicafood &copy; ' . date('Y') . ' — Child theme của <strong>Storefront</strong> + WooCommerce';
});

// ============================================================
// 5. Helper functions
// ============================================================
if (!function_exists('cica_format_price')) {
    function cica_format_price(int $amount): string {
        return number_format($amount, 0, ',', '.') . 'đ';
    }
}

// ============================================================
// 6. Tắt jQuery Migrate trên frontend (không cần thiết)
// ============================================================
add_filter('wp_default_scripts', function ($scripts) {
    if (!is_admin() && isset($scripts->registered['jquery'])) {
        $scripts->registered['jquery']->deps = array_diff(
            $scripts->registered['jquery']->deps,
            ['jquery-migrate']
        );
    }
});

