<?php
/**
 * Plugin Name:       Cicafood Core
 * Plugin URI:        https://cicafood.online
 * Description:       Plugin lõi cho hệ thống đặt đồ ăn Cicafood. Tạo custom tables, REST API, shortcodes và tích hợp WooCommerce.
 * Version:           1.0.0
 * Author:            Bùi Trung Dũng
 * Author URI:        https://cicafood.online
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       cicafood-core
 * Requires at least: 6.0
 * Requires PHP:      8.0
 */

defined('ABSPATH') || exit;

// ============================================================
// CONSTANTS
// ============================================================
define('CICAFOOD_VERSION',     '1.0.0');
define('CICAFOOD_PLUGIN_DIR',  plugin_dir_path(__FILE__));
define('CICAFOOD_PLUGIN_URL',  plugin_dir_url(__FILE__));
define('CICAFOOD_DB_VERSION',  '1.0');

// ============================================================
// AUTOLOAD INCLUDES
// ============================================================
require_once CICAFOOD_PLUGIN_DIR . 'includes/class-database.php';
require_once CICAFOOD_PLUGIN_DIR . 'includes/class-restaurant.php';
require_once CICAFOOD_PLUGIN_DIR . 'includes/class-menu.php';
require_once CICAFOOD_PLUGIN_DIR . 'includes/class-cart.php';
require_once CICAFOOD_PLUGIN_DIR . 'includes/class-order.php';
require_once CICAFOOD_PLUGIN_DIR . 'includes/class-voucher.php';
require_once CICAFOOD_PLUGIN_DIR . 'includes/class-review.php';
require_once CICAFOOD_PLUGIN_DIR . 'includes/shortcodes.php';
require_once CICAFOOD_PLUGIN_DIR . 'includes/ajax-handlers.php';
require_once CICAFOOD_PLUGIN_DIR . 'admin/admin-menu.php';

// ============================================================
// ACTIVATION HOOK — Tạo custom tables + roles
// ============================================================
register_activation_hook(__FILE__, function () {
    Cicafood_Database::create_tables();
    Cicafood_Database::insert_sample_data();
    update_option('cicafood_db_version', CICAFOOD_DB_VERSION);
    cica_register_roles();
    flush_rewrite_rules();
});

// ============================================================
// DEACTIVATION HOOK
// ============================================================
register_deactivation_hook(__FILE__, function () {
    remove_role('cicafood_merchant');
    flush_rewrite_rules();
});

// ============================================================
// ROLES & CAPABILITIES
// ============================================================
function cica_register_roles(): void {
    // Xóa role cũ nếu có để tạo lại sạch
    remove_role('cicafood_merchant');

    add_role('cicafood_merchant', 'Chủ nhà hàng', [
        'read'                    => true,  // Truy cập WP Admin cơ bản
        'cica_manage_restaurant'  => true,  // Quản lý nhà hàng của mình
        'cica_manage_menu'        => true,  // Quản lý menu
        'cica_manage_orders'      => true,  // Xem & cập nhật đơn hàng
        'cica_manage_vouchers'    => true,  // Tạo voucher
    ]);

    // Cấp thêm capability cho admin
    $admin = get_role('administrator');
    if ($admin) {
        $admin->add_cap('cica_manage_restaurant');
        $admin->add_cap('cica_manage_menu');
        $admin->add_cap('cica_manage_orders');
        $admin->add_cap('cica_manage_vouchers');
    }
}

// Đăng ký roles mỗi lần plugin load (phòng trường hợp chưa activate)
add_action('init', function () {
    if (!get_role('cicafood_merchant')) {
        cica_register_roles();
    }
}, 1);

// ============================================================
// AUTH REDIRECTS — Chuẩn WordPress
// ============================================================

// 1. Override URL trang login → dùng trang custom /dang-nhap/
add_filter('login_url', function ($login_url, $redirect, $force_reauth) {
    $custom = get_site_url() . '/dang-nhap/';
    if ($redirect) $custom .= '?redirect_to=' . urlencode($redirect);
    return $custom;
}, 10, 3);

// 2. Redirect sau khi đăng nhập thành công (form POST fallback)
add_filter('login_redirect', function ($redirect_to, $requested_redirect_to, $user) {
    if (is_wp_error($user)) return $redirect_to;
    if (user_can($user, 'manage_options'))          return admin_url();
    if (user_can($user, 'cica_manage_restaurant'))  return get_site_url() . '/quan-ly/';
    return get_site_url() . '/';
}, 10, 3);

// 3. Redirect sau khi đăng xuất → về trang login custom
add_action('wp_logout', function () {
    wp_redirect(get_site_url() . '/dang-nhap/?mode=login');
    exit;
});

// 4. Redirect trang chủ về /dang-nhap/ nếu chưa đăng nhập
add_action('wp_loaded', function () {
    if (wp_doing_ajax() || is_admin() || wp_doing_cron()) return;
    if (defined('REST_REQUEST') && REST_REQUEST) return;

    $request = $_SERVER['REQUEST_URI'] ?? '';

    $allowed = ['/dang-nhap/', '/wp-admin/', '/wp-login.php', 'admin-ajax.php', '/wp-cron.php', '/wp-json/', 'check_user.php'];
    foreach ($allowed as $path) {
        if (strpos($request, $path) !== false) return;
    }

    if (!is_user_logged_in()) {
        wp_redirect(get_site_url() . '/dang-nhap/');
        exit;
    }
}, 99);
// 4. Bật đăng ký user (nếu chưa bật)
add_action('init', function () {
    if (!get_option('users_can_register')) {
        update_option('users_can_register', 1);
    }
}, 5);

// Helper: kiểm tra user có phải merchant không
function cica_is_merchant(): bool {
    return current_user_can('cica_manage_restaurant');
}

// Helper: lấy restaurant_id của user hiện tại
function cica_get_user_restaurant_id(int $user_id = 0): int {
    global $wpdb;
    if (!$user_id) $user_id = get_current_user_id();
    $rt  = $wpdb->prefix . 'cica_restaurants';
    $row = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $rt WHERE wp_user_id = %d LIMIT 1", $user_id
    ));
    return $row ? (int)$row : 0;
}

// ============================================================
// SHORTCODE: chi tiết nhà hàng
// ============================================================
add_shortcode('cicafood_restaurant', function () {
    ob_start();
    include CICAFOOD_PLUGIN_DIR . 'views/restaurant-detail.php';
    return ob_get_clean();
});

// ============================================================
// FILTER: Bắt URL /nha-hang/{slug}/ và load view chi tiết
// ============================================================

// Lưu slug vào global để dùng trong template
global $cicafood_restaurant_slug;
$cicafood_restaurant_slug = '';

add_action('parse_request', function ($wp) {
    $request = $_SERVER['REQUEST_URI'];

    // ---- Trang xác nhận đặt hàng ----
    if (preg_match('#/dat-hang-thanh-cong/?(\?.*)?$#', $request)) {
        add_filter('template_include', function () {
            return CICAFOOD_PLUGIN_DIR . 'templates/order-confirmation-page.php';
        });
        status_header(200);
        return;
    }

    // ---- Trang đăng nhập / đăng ký custom ----
    if (preg_match('#/dang-nhap/?(\?.*)?$#', $request)) {
        add_filter('template_include', function () {
            return CICAFOOD_PLUGIN_DIR . 'templates/auth-page.php';
        });
        status_header(200);
        return;
    }

    // ---- Merchant dashboard ----
    if (preg_match('#/quan-ly/?(\?.*)?$#', $request)) {
        add_filter('template_include', function () {
            return CICAFOOD_PLUGIN_DIR . 'templates/merchant-page.php';
        });
        status_header(200);
        return;
    }

    // ---- Yeu thich ----
    if (preg_match('#/yeu-thich/?(\?.*)?$#', $request)) {
        add_filter('template_include', function () {
            return CICAFOOD_PLUGIN_DIR . 'templates/favorites-page.php';
        });
        status_header(200);
        return;
    }

    // ---- Ho so nguoi dung ----
    if (preg_match('#/ho-so/?(\?.*)?$#', $request)) {
        add_filter('template_include', function () {
            return CICAFOOD_PLUGIN_DIR . 'templates/profile-page.php';
        });
        status_header(200);
        return;
    }

    // ---- Chi tiết nhà hàng ----
    if (preg_match('#/nha-hang/([^/?]+)/?(\?.*)?$#', $request, $matches)) {
        $slug = sanitize_text_field($matches[1]);
        if ($slug && $slug !== 'nha-hang') {
            global $cicafood_restaurant_slug;
            $cicafood_restaurant_slug = $slug;
            add_filter('template_include', function ($template) {
                return CICAFOOD_PLUGIN_DIR . 'templates/restaurant-single.php';
            });
            status_header(200);
        }
    }
}, 1);

// ============================================================
// ENQUEUE ASSETS cho plugin views
// ============================================================
add_action('wp_enqueue_scripts', function () {
    // Chỉ enqueue restaurant JS trên trang nhà hàng
    global $cicafood_restaurant_slug;
    if (empty($cicafood_restaurant_slug)) return;

    wp_enqueue_script(
        'cicafood-restaurant',
        CICAFOOD_PLUGIN_URL . 'assets/js/restaurant.js',
        [],
        CICAFOOD_VERSION,
        true
    );
    wp_localize_script('cicafood-restaurant', 'cicafoodAjax', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('cicafood_nonce'),
        'siteurl' => get_site_url(),
    ]);
});
add_action('plugins_loaded', function () {
    load_plugin_textdomain('cicafood-core', false, dirname(plugin_basename(__FILE__)) . '/languages');
});

// ============================================================
// GLOBAL HELPER FUNCTIONS
// ============================================================

/**
 * Format giá tiền VND
 */
if (!function_exists('cica_format_price')) {
    function cica_format_price(int $amount): string {
        return number_format($amount, 0, ',', '.') . 'đ';
    }
}

// Start session sớm
add_action('init', function () {
    if (!session_id() && !headers_sent()) {
        session_start();
    }
}, 1);




