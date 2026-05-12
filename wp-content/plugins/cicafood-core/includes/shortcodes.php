<?php
defined('ABSPATH') || exit;

/**
 * Shortcodes — Nhúng các trang Cicafood vào WP pages
 * Dùng: [cicafood_home], [cicafood_restaurants], [cicafood_restaurant], [cicafood_checkout], v.v.
 */

// ============================================================
// TRANG CHỦ
// ============================================================
add_shortcode('cicafood_home', function () {
    ob_start();
    include CICAFOOD_PLUGIN_DIR . 'views/home.php';
    return ob_get_clean();
});

// ============================================================
// DANH SÁCH NHÀ HÀNG
// ============================================================
add_shortcode('cicafood_restaurants', function () {
    ob_start();
    include CICAFOOD_PLUGIN_DIR . 'views/restaurants.php';
    return ob_get_clean();
});

// ============================================================
// CHI TIẾT NHÀ HÀNG
// ============================================================
add_shortcode('cicafood_restaurant', function () {
    ob_start();
    include CICAFOOD_PLUGIN_DIR . 'views/restaurant-detail.php';
    return ob_get_clean();
});

// ============================================================
// CHECKOUT
// ============================================================
add_shortcode('cicafood_checkout', function () {
    if (!is_user_logged_in()) {
        return '<p>Vui lòng <a href="' . wp_login_url(get_permalink()) . '">đăng nhập</a> để thanh toán.</p>';
    }
    ob_start();
    include CICAFOOD_PLUGIN_DIR . 'views/checkout.php';
    return ob_get_clean();
});

// ============================================================
// LỊCH SỬ ĐƠN HÀNG
// ============================================================
add_shortcode('cicafood_orders', function () {
    if (!is_user_logged_in()) {
        return '<p>Vui lòng <a href="' . wp_login_url(get_permalink()) . '">đăng nhập</a> để xem đơn hàng.</p>';
    }
    ob_start();
    include CICAFOOD_PLUGIN_DIR . 'views/order-history.php';
    return ob_get_clean();
});

// ============================================================
// HỒ SƠ NGƯỜI DÙNG
// ============================================================
add_shortcode('cicafood_profile', function () {
    if (!is_user_logged_in()) {
        return '<p>Vui lòng <a href="' . wp_login_url(get_permalink()) . '">đăng nhập</a> để xem hồ sơ.</p>';
    }
    ob_start();
    include CICAFOOD_PLUGIN_DIR . 'views/profile.php';
    return ob_get_clean();
});

// ============================================================
// VÍ VOUCHER
// ============================================================
add_shortcode('cicafood_vouchers', function () {
    if (!is_user_logged_in()) {
        return '<p>Vui lòng <a href="' . wp_login_url(get_permalink()) . '">đăng nhập</a> để xem voucher.</p>';
    }
    ob_start();
    include CICAFOOD_PLUGIN_DIR . 'views/vouchers.php';
    return ob_get_clean();
});

// ============================================================
// YÊU THÍCH
// ============================================================
add_shortcode('cicafood_favorites', function () {
    if (!is_user_logged_in()) {
        return '<p>Vui lòng <a href="' . wp_login_url(get_permalink()) . '">đăng nhập</a> để xem yêu thích.</p>';
    }
    ob_start();
    include CICAFOOD_PLUGIN_DIR . 'views/favorites.php';
    return ob_get_clean();
});

// ============================================================
// MERCHANT VOUCHER MANAGEMENT
// ============================================================
add_shortcode('cicafood_merchant_vouchers', function () {
    if (!is_user_logged_in()) {
        return '<p>Vui lòng <a href="' . wp_login_url(get_permalink()) . '">đăng nhập</a> để quản lý voucher.</p>';
    }
    ob_start();
    include CICAFOOD_PLUGIN_DIR . 'views/merchant-vouchers.php';
    return ob_get_clean();
});
