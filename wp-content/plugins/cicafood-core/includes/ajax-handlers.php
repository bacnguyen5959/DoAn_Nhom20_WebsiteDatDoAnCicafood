<?php
defined('ABSPATH') || exit;

/**
 * AJAX Handlers — Tất cả wp_ajax_ hooks
 * Tương đương toàn bộ folder api/ của foodbooking
 */

// ============================================================
// CART
// ============================================================
add_action('wp_ajax_cica_add_to_cart',        'cica_ajax_add_to_cart');
add_action('wp_ajax_nopriv_cica_add_to_cart', 'cica_ajax_add_to_cart');
function cica_ajax_add_to_cart(): void {
    if (!session_id()) session_start();
    check_ajax_referer('cicafood_nonce', 'nonce');
    $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $result = Cicafood_Cart::add((int)($data['item_id'] ?? 0), (int)($data['restaurant_id'] ?? 0), (int)($data['quantity'] ?? 1));
    wp_send_json($result);
}

add_action('wp_ajax_cica_update_cart',        'cica_ajax_update_cart');
add_action('wp_ajax_nopriv_cica_update_cart', 'cica_ajax_update_cart');
function cica_ajax_update_cart(): void {
    if (!session_id()) session_start();
    check_ajax_referer('cicafood_nonce', 'nonce');
    $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $result = Cicafood_Cart::update((int)($data['item_id'] ?? 0), (int)($data['quantity'] ?? 0));
    wp_send_json($result);
}

add_action('wp_ajax_cica_clear_cart',        'cica_ajax_clear_cart');
add_action('wp_ajax_nopriv_cica_clear_cart', 'cica_ajax_clear_cart');
function cica_ajax_clear_cart(): void {
    if (!session_id()) session_start();
    Cicafood_Cart::clear();
    wp_send_json(['success' => true]);
}

// ============================================================
// VOUCHER
// ============================================================
add_action('wp_ajax_cica_apply_voucher',        'cica_ajax_apply_voucher');
add_action('wp_ajax_nopriv_cica_apply_voucher', 'cica_ajax_apply_voucher');
function cica_ajax_apply_voucher(): void {
    if (!session_id()) session_start();
    $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $result = Cicafood_Voucher::apply(
        $data['code'] ?? '',
        (int)($data['subtotal'] ?? 0),
        (int)($_SESSION['cica_cart_restaurant_id'] ?? 0)
    );
    wp_send_json($result);
}

add_action('wp_ajax_cica_get_vouchers', 'cica_ajax_get_vouchers');
function cica_ajax_get_vouchers(): void {
    if (!session_id()) session_start();
    $subtotal       = (int)($_GET['subtotal'] ?? 0);
    $restaurant_id  = (int)($_GET['restaurant_id'] ?? 0);
    $vouchers = Cicafood_Voucher::get_available($subtotal, $restaurant_id, get_current_user_id());
    wp_send_json(['success' => true, 'vouchers' => $vouchers]);
}

add_action('wp_ajax_cica_save_voucher', 'cica_ajax_save_voucher');
function cica_ajax_save_voucher(): void {
    if (!is_user_logged_in()) { wp_send_json(['success' => false, 'message' => 'Vui lòng đăng nhập']); }
    $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $result = Cicafood_Voucher::save_to_wallet((int)($data['voucher_id'] ?? 0), get_current_user_id());
    wp_send_json($result);
}

// ============================================================
// RESTAURANT
// ============================================================
add_action('wp_ajax_cica_toggle_favorite',        'cica_ajax_toggle_favorite');
add_action('wp_ajax_nopriv_cica_toggle_favorite', 'cica_ajax_toggle_favorite');
function cica_ajax_toggle_favorite(): void {
    if (!is_user_logged_in()) {
        wp_send_json(['success' => false, 'message' => 'Vui lòng đăng nhập', 'login_required' => true]);
    }
    $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $result = Cicafood_Restaurant::toggle_favorite((int)($data['restaurant_id'] ?? 0), get_current_user_id());
    $result['success'] = true;
    wp_send_json($result);
}

// ============================================================
// REVIEW
// ============================================================
add_action('wp_ajax_cica_submit_review', 'cica_ajax_submit_review');
function cica_ajax_submit_review(): void {
    if (!is_user_logged_in()) { wp_send_json(['success' => false, 'message' => 'Vui lòng đăng nhập']); }
    check_ajax_referer('cicafood_nonce', 'nonce');
    $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $result = Cicafood_Review::submit(
        (int)($data['restaurant_id'] ?? 0),
        get_current_user_id(),
        (int)($data['rating'] ?? 0),
        $data['comment'] ?? ''
    );
    wp_send_json($result);
}

// ============================================================
// ORDER
// ============================================================
add_action('wp_ajax_cica_place_order', 'cica_ajax_place_order');
function cica_ajax_place_order(): void {
    if (!session_id()) session_start();
    if (!is_user_logged_in()) { wp_send_json(['success' => false, 'message' => 'Vui lòng đăng nhập']); }
    check_ajax_referer('cicafood_nonce', 'nonce');
    $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $data['wp_user_id'] = get_current_user_id();
    $result = Cicafood_Order::create($data);
    wp_send_json($result);
}

add_action('wp_ajax_cica_cancel_order', 'cica_ajax_cancel_order');
function cica_ajax_cancel_order(): void {
    if (!is_user_logged_in()) { wp_send_json(['success' => false, 'message' => 'Vui lòng đăng nhập']); }
    $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $result = Cicafood_Order::cancel((int)($data['order_id'] ?? 0), get_current_user_id());
    wp_send_json($result);
}

// ============================================================
// MERCHANT VOUCHER MANAGEMENT
// ============================================================
add_action('wp_ajax_cica_merchant_save_voucher',   'cica_ajax_merchant_save_voucher');
function cica_ajax_merchant_save_voucher(): void {
    if (!is_user_logged_in()) { wp_send_json(['success' => false, 'message' => 'Vui lòng đăng nhập']); }
    check_ajax_referer('cicafood_nonce', 'nonce');

    global $wpdb;
    $rt = $wpdb->prefix . 'cica_restaurants';
    $vt = $wpdb->prefix . 'cica_vouchers';

    // Lấy restaurant của merchant
    $restaurant = $wpdb->get_row($wpdb->prepare(
        "SELECT id FROM $rt WHERE wp_user_id = %d LIMIT 1", get_current_user_id()
    ), ARRAY_A);
    if (!$restaurant && !current_user_can('manage_options')) {
        wp_send_json(['success' => false, 'message' => 'Bạn không có nhà hàng nào']);
    }
    $rid = $restaurant ? (int)$restaurant['id'] : 0;

    $data = $_POST;
    $code      = strtoupper(sanitize_text_field($data['code'] ?? ''));
    $name      = sanitize_text_field($data['name'] ?? '');
    $desc      = sanitize_textarea_field($data['description'] ?? '');
    $type      = in_array($data['type'] ?? '', ['fixed','percent','freeship']) ? $data['type'] : 'fixed';
    $value     = max(1, (int)($data['value'] ?? 0));
    $max_disc  = (int)($data['max_discount'] ?? $value);
    $min_order = max(0, (int)($data['min_order'] ?? 0));
    $limit     = max(1, (int)($data['usage_limit'] ?? 100));
    $end_date  = sanitize_text_field($data['end_date'] ?? '');
    $vid       = (int)($data['voucher_id'] ?? 0);

    if (!$code || !$name || !$end_date) {
        wp_send_json(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin bắt buộc']);
    }

    $row = [
        'restaurant_id' => $rid ?: null,
        'code'          => $code,
        'name'          => $name,
        'description'   => $desc,
        'type'          => $type,
        'value'         => $value,
        'max_discount'  => $max_disc,
        'min_order'     => $min_order,
        'usage_limit'   => $limit,
        'end_date'      => $end_date,
        'is_active'     => 1,
    ];

    if ($vid > 0) {
        // Edit — chỉ cho sửa voucher của mình
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $vt WHERE id = %d AND (restaurant_id = %d OR %d = 1)",
            $vid, $rid, current_user_can('manage_options') ? 1 : 0
        ));
        if (!$existing) { wp_send_json(['success' => false, 'message' => 'Không tìm thấy voucher']); }
        $wpdb->update($vt, $row, ['id' => $vid]);
        wp_send_json(['success' => true, 'message' => 'Đã cập nhật voucher!']);
    } else {
        // Add — check trùng code
        $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $vt WHERE code = %s", $code));
        if ($exists) { wp_send_json(['success' => false, 'message' => "Mã \"$code\" đã tồn tại"]); }
        $wpdb->insert($vt, $row);
        wp_send_json(['success' => true, 'message' => 'Đã tạo voucher!', 'voucher_id' => $wpdb->insert_id]);
    }
}

add_action('wp_ajax_cica_merchant_delete_voucher', 'cica_ajax_merchant_delete_voucher');
function cica_ajax_merchant_delete_voucher(): void {
    if (!is_user_logged_in()) { wp_send_json(['success' => false, 'message' => 'Vui lòng đăng nhập']); }
    check_ajax_referer('cicafood_nonce', 'nonce');

    global $wpdb;
    $rt = $wpdb->prefix . 'cica_restaurants';
    $vt = $wpdb->prefix . 'cica_vouchers';

    $vid = (int)($_POST['voucher_id'] ?? 0);
    $restaurant = $wpdb->get_row($wpdb->prepare(
        "SELECT id FROM $rt WHERE wp_user_id = %d LIMIT 1", get_current_user_id()
    ), ARRAY_A);
    $rid = $restaurant ? (int)$restaurant['id'] : 0;

    $where = current_user_can('manage_options')
        ? ['id' => $vid]
        : ['id' => $vid, 'restaurant_id' => $rid];

    $deleted = $wpdb->delete($vt, $where);
    if ($deleted) {
        wp_send_json(['success' => true, 'message' => 'Đã xoá voucher']);
    } else {
        wp_send_json(['success' => false, 'message' => 'Không thể xoá voucher này']);
    }
}

add_action('wp_ajax_cica_merchant_get_vouchers', 'cica_ajax_merchant_get_vouchers');
function cica_ajax_merchant_get_vouchers(): void {
    if (!is_user_logged_in()) { wp_send_json(['success' => false]); }

    global $wpdb;
    $rt = $wpdb->prefix . 'cica_restaurants';
    $vt = $wpdb->prefix . 'cica_vouchers';
    $uv = $wpdb->prefix . 'cica_user_vouchers';

    $restaurant = $wpdb->get_row($wpdb->prepare(
        "SELECT id FROM $rt WHERE wp_user_id = %d LIMIT 1", get_current_user_id()
    ), ARRAY_A);
    $rid = $restaurant ? (int)$restaurant['id'] : 0;

    $where = current_user_can('manage_options') ? '1=1' : $wpdb->prepare('v.restaurant_id = %d', $rid);
    $vouchers = $wpdb->get_results(
        "SELECT v.*, (SELECT COUNT(*) FROM $uv uv WHERE uv.voucher_id = v.id AND uv.is_used = 1) AS used_count
         FROM $vt v WHERE $where ORDER BY v.id DESC",
        ARRAY_A
    ) ?: [];

    wp_send_json(['success' => true, 'vouchers' => $vouchers]);
}

// ============================================================
// PROFILE
// ============================================================
add_action('wp_ajax_cica_update_profile', 'cica_ajax_update_profile');
function cica_ajax_update_profile(): void {
    if (!is_user_logged_in()) { wp_send_json(['success' => false, 'message' => 'Vui lòng đăng nhập']); }
    check_ajax_referer('cicafood_nonce', 'nonce');

    $user_id     = get_current_user_id();
    $display_name = sanitize_text_field($_POST['display_name'] ?? '');
    $phone        = sanitize_text_field($_POST['phone']        ?? '');
    $address      = sanitize_textarea_field($_POST['address']  ?? '');

    if (empty($display_name)) {
        wp_send_json(['success' => false, 'message' => 'Họ tên không được để trống']);
    }

    // Validate phone
    if ($phone && !preg_match('/^[0-9+\-\s]{8,15}$/', $phone)) {
        wp_send_json(['success' => false, 'message' => 'Số điện thoại không hợp lệ']);
    }

    wp_update_user(['ID' => $user_id, 'display_name' => $display_name]);
    update_user_meta($user_id, 'phone',   $phone);
    update_user_meta($user_id, 'address', $address);

    wp_send_json(['success' => true, 'message' => 'Đã cập nhật hồ sơ thành công!']);
}

add_action('wp_ajax_cica_change_password', 'cica_ajax_change_password');
function cica_ajax_change_password(): void {
    if (!is_user_logged_in()) { wp_send_json(['success' => false, 'message' => 'Vui lòng đăng nhập']); }
    check_ajax_referer('cicafood_nonce', 'nonce');

    $user_id    = get_current_user_id();
    $current_pw = $_POST['current_pw'] ?? '';
    $new_pw     = $_POST['new_pw']     ?? '';

    if (strlen($new_pw) < 6) {
        wp_send_json(['success' => false, 'message' => 'Mật khẩu mới tối thiểu 6 ký tự']);
    }

    $user = get_user_by('id', $user_id);
    if (!wp_check_password($current_pw, $user->user_pass, $user_id)) {
        wp_send_json(['success' => false, 'message' => 'Mật khẩu hiện tại không đúng']);
    }

    wp_set_password($new_pw, $user_id);
    wp_send_json(['success' => true, 'message' => 'Đã đổi mật khẩu thành công! Vui lòng đăng nhập lại.']);
}

add_action('wp_ajax_cica_update_avatar', 'cica_ajax_update_avatar');
function cica_ajax_update_avatar(): void {
    if (!is_user_logged_in()) { wp_send_json(['success' => false, 'message' => 'Vui lòng đăng nhập']); }
    check_ajax_referer('cicafood_nonce', 'nonce');

    if (empty($_FILES['avatar'])) {
        wp_send_json(['success' => false, 'message' => 'Không có file nào được gửi']);
    }

    $file = $_FILES['avatar'];

    // Validate type
    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowed)) {
        wp_send_json(['success' => false, 'message' => 'Chỉ chấp nhận ảnh JPG, PNG, GIF, WEBP']);
    }

    // Validate size (2MB)
    if ($file['size'] > 2 * 1024 * 1024) {
        wp_send_json(['success' => false, 'message' => 'Ảnh không được vượt quá 2MB']);
    }

    // Upload via WP media
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $attachment_id = media_handle_upload('avatar', 0);
    if (is_wp_error($attachment_id)) {
        wp_send_json(['success' => false, 'message' => 'Lỗi upload: ' . $attachment_id->get_error_message()]);
    }

    $avatar_url = wp_get_attachment_url($attachment_id);
    update_user_meta(get_current_user_id(), 'cica_avatar', $avatar_url);

    wp_send_json(['success' => true, 'message' => 'Đã cập nhật ảnh đại diện!', 'avatar_url' => $avatar_url]);
}


// ============================================================
// MERCHANT MENU & ORDER MANAGEMENT
// ============================================================

function cica_get_merchant_rid(): int {
    global $wpdb;
    if (current_user_can('manage_options')) return -1; // admin: không giới hạn
    if (!current_user_can('cica_manage_restaurant')) return 0; // không có quyền merchant
    $rt  = $wpdb->prefix . 'cica_restaurants';
    $row = $wpdb->get_row($wpdb->prepare(
        "SELECT id FROM $rt WHERE wp_user_id = %d LIMIT 1",
        get_current_user_id()
    ), ARRAY_A);
    return $row ? (int)$row['id'] : 0;
}

// ---- Save menu item (add / edit) ----
add_action('wp_ajax_cica_save_menu_item', 'cica_ajax_save_menu_item');
function cica_ajax_save_menu_item(): void {
    if (!is_user_logged_in()) { wp_send_json(['success' => false, 'message' => 'Chưa đăng nhập']); }
    check_ajax_referer('cicafood_nonce', 'nonce');
    global $wpdb;
    $mi  = $wpdb->prefix . 'cica_menu_items';
    $rid = cica_get_merchant_rid();
    if ($rid === 0) { wp_send_json(['success' => false, 'message' => 'Bạn chưa có nhà hàng']); }

    $item_id = (int)($_POST['item_id']          ?? 0);
    $cat_id  = (int)($_POST['menu_category_id'] ?? 0);
    $name    = sanitize_text_field($_POST['name']        ?? '');
    $desc    = sanitize_textarea_field($_POST['description'] ?? '');
    $price   = max(0, (int)($_POST['price']     ?? 0));
    $orig    = (int)($_POST['original_price']   ?? 0) ?: null;
    $avail   = (int)($_POST['is_available']     ?? 1);
    $best    = (int)($_POST['is_best_seller']   ?? 0);
    $image   = esc_url_raw($_POST['image']      ?? '');

    if (!$name || $price <= 0) {
        wp_send_json(['success' => false, 'message' => 'Tên và giá không được để trống']);
    }

    $data = [
        'menu_category_id' => $cat_id ?: null,
        'name'             => $name,
        'description'      => $desc,
        'price'            => $price,
        'original_price'   => $orig,
        'image'            => $image,
        'is_available'     => $avail,
        'is_best_seller'   => $best,
    ];

    if ($item_id > 0) {
        $where = ($rid === -1) ? ['id' => $item_id] : ['id' => $item_id, 'restaurant_id' => $rid];
        $wpdb->update($mi, $data, $where);
        wp_send_json(['success' => true, 'message' => 'Đã cập nhật món ăn!', 'item_id' => $item_id]);
    } else {
        $data['restaurant_id'] = ($rid === -1) ? (int)($_POST['restaurant_id'] ?? 0) : $rid;
        $wpdb->insert($mi, $data);
        wp_send_json(['success' => true, 'message' => 'Đã thêm món ăn!', 'item_id' => $wpdb->insert_id]);
    }
}

// ---- Delete menu item ----
add_action('wp_ajax_cica_delete_menu_item', 'cica_ajax_delete_menu_item');
function cica_ajax_delete_menu_item(): void {
    if (!is_user_logged_in()) { wp_send_json(['success' => false, 'message' => 'Chưa đăng nhập']); }
    check_ajax_referer('cicafood_nonce', 'nonce');
    global $wpdb;
    $mi      = $wpdb->prefix . 'cica_menu_items';
    $rid     = cica_get_merchant_rid();
    $item_id = (int)($_POST['item_id'] ?? 0);
    $where   = ($rid === -1) ? ['id' => $item_id] : ['id' => $item_id, 'restaurant_id' => $rid];
    $deleted = $wpdb->delete($mi, $where);
    wp_send_json($deleted
        ? ['success' => true,  'message' => 'Đã xóa món ăn']
        : ['success' => false, 'message' => 'Không thể xóa']
    );
}

// ---- Toggle item availability ----
add_action('wp_ajax_cica_toggle_item_avail', 'cica_ajax_toggle_item_avail');
function cica_ajax_toggle_item_avail(): void {
    if (!is_user_logged_in()) { wp_send_json(['success' => false]); }
    check_ajax_referer('cicafood_nonce', 'nonce');
    global $wpdb;
    $mi      = $wpdb->prefix . 'cica_menu_items';
    $rid     = cica_get_merchant_rid();
    $item_id = (int)($_POST['item_id'] ?? 0);
    $current = (int)$wpdb->get_var($wpdb->prepare("SELECT is_available FROM $mi WHERE id = %d", $item_id));
    $new_val = $current ? 0 : 1;
    $where   = ($rid === -1) ? ['id' => $item_id] : ['id' => $item_id, 'restaurant_id' => $rid];
    $wpdb->update($mi, ['is_available' => $new_val], $where);
    wp_send_json(['success' => true, 'is_available' => $new_val]);
}

// ---- Save menu category ----
add_action('wp_ajax_cica_save_menu_category', 'cica_ajax_save_menu_category');
function cica_ajax_save_menu_category(): void {
    if (!is_user_logged_in()) { wp_send_json(['success' => false]); }
    check_ajax_referer('cicafood_nonce', 'nonce');
    global $wpdb;
    $mc      = $wpdb->prefix . 'cica_menu_categories';
    $rid     = cica_get_merchant_rid();
    if ($rid === 0) { wp_send_json(['success' => false, 'message' => 'Bạn chưa có nhà hàng']); }
    $cat_id  = (int)($_POST['cat_id']  ?? 0);
    $name    = sanitize_text_field($_POST['name'] ?? '');
    if (!$name) { wp_send_json(['success' => false, 'message' => 'Tên danh mục không được để trống']); }
    $rest_id = ($rid === -1) ? (int)($_POST['restaurant_id'] ?? 0) : $rid;
    if ($cat_id > 0) {
        $wpdb->update($mc, ['name' => $name], ['id' => $cat_id, 'restaurant_id' => $rest_id]);
        wp_send_json(['success' => true, 'message' => 'Đã cập nhật danh mục', 'cat_id' => $cat_id]);
    } else {
        $wpdb->insert($mc, ['restaurant_id' => $rest_id, 'name' => $name, 'sort_order' => 99]);
        wp_send_json(['success' => true, 'message' => 'Đã thêm danh mục', 'cat_id' => $wpdb->insert_id]);
    }
}

// ---- Delete menu category ----
add_action('wp_ajax_cica_delete_menu_category', 'cica_ajax_delete_menu_category');
function cica_ajax_delete_menu_category(): void {
    if (!is_user_logged_in()) { wp_send_json(['success' => false]); }
    check_ajax_referer('cicafood_nonce', 'nonce');
    global $wpdb;
    $mc      = $wpdb->prefix . 'cica_menu_categories';
    $mi      = $wpdb->prefix . 'cica_menu_items';
    $rid     = cica_get_merchant_rid();
    $cat_id  = (int)($_POST['cat_id'] ?? 0);
    $rest_id = ($rid === -1) ? (int)($_POST['restaurant_id'] ?? 0) : $rid;
    $wpdb->update($mi, ['menu_category_id' => null], ['menu_category_id' => $cat_id, 'restaurant_id' => $rest_id]);
    $wpdb->delete($mc, ['id' => $cat_id, 'restaurant_id' => $rest_id]);
    wp_send_json(['success' => true, 'message' => 'Đã xóa danh mục']);
}

// ---- Update order status (merchant) ----
add_action('wp_ajax_cica_merchant_update_order', 'cica_ajax_merchant_update_order');
function cica_ajax_merchant_update_order(): void {
    if (!is_user_logged_in()) { wp_send_json(['success' => false]); }
    check_ajax_referer('cicafood_nonce', 'nonce');
    global $wpdb;
    $ot     = $wpdb->prefix . 'cica_orders';
    $rid    = cica_get_merchant_rid();
    $oid    = (int)($_POST['order_id'] ?? 0);
    $status = sanitize_text_field($_POST['status'] ?? '');
    $valid  = ['confirmed', 'preparing', 'delivering', 'completed', 'cancelled'];
    if (!in_array($status, $valid)) { wp_send_json(['success' => false, 'message' => 'Trạng thái không hợp lệ']); }
    $where = ($rid === -1) ? ['id' => $oid] : ['id' => $oid, 'restaurant_id' => $rid];
    $wpdb->update($ot, ['status' => $status], $where);
    wp_send_json(['success' => true, 'message' => 'Đã cập nhật trạng thái', 'status' => $status]);
}

// ---- Toggle restaurant open/close ----
add_action('wp_ajax_cica_merchant_toggle_open', 'cica_ajax_merchant_toggle_open');
function cica_ajax_merchant_toggle_open(): void {
    if (!is_user_logged_in()) { wp_send_json(['success' => false]); }
    check_ajax_referer('cicafood_nonce', 'nonce');
    global $wpdb;
    $rt      = $wpdb->prefix . 'cica_restaurants';
    $rid     = cica_get_merchant_rid();
    if ($rid === 0) { wp_send_json(['success' => false, 'message' => 'Bạn chưa có nhà hàng']); }
    $rest_id = ($rid === -1) ? (int)($_POST['restaurant_id'] ?? 0) : $rid;
    $current = (int)$wpdb->get_var($wpdb->prepare("SELECT is_open FROM $rt WHERE id = %d", $rest_id));
    $new_val = $current ? 0 : 1;
    $wpdb->update($rt, ['is_open' => $new_val], ['id' => $rest_id]);
    wp_send_json([
        'success' => true,
        'is_open' => $new_val,
        'message' => $new_val ? 'Nhà hàng đã mở cửa' : 'Nhà hàng đã đóng cửa',
    ]);
}

// ---- Upload menu item image ----
add_action('wp_ajax_cica_upload_item_image', 'cica_ajax_upload_item_image');
function cica_ajax_upload_item_image(): void {
    if (!is_user_logged_in()) { wp_send_json(['success' => false]); }
    check_ajax_referer('cicafood_nonce', 'nonce');
    if (empty($_FILES['image'])) { wp_send_json(['success' => false, 'message' => 'Không có file']); }
    $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    if (!in_array($_FILES['image']['type'], $allowed)) {
        wp_send_json(['success' => false, 'message' => 'Chỉ chấp nhận ảnh JPG/PNG/WEBP']);
    }
    if ($_FILES['image']['size'] > 3 * 1024 * 1024) {
        wp_send_json(['success' => false, 'message' => 'Ảnh không quá 3MB']);
    }
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';
    $id = media_handle_upload('image', 0);
    if (is_wp_error($id)) {
        wp_send_json(['success' => false, 'message' => $id->get_error_message()]);
    }
    wp_send_json(['success' => true, 'url' => wp_get_attachment_url($id)]);
}

// ============================================================
// AUTH — Login & Register AJAX
// ============================================================
add_action('wp_ajax_nopriv_cica_ajax_login', 'cica_ajax_login_handler');
add_action('wp_ajax_cica_ajax_login',        'cica_ajax_login_handler');
function cica_ajax_login_handler(): void {
    // Verify nonce — không dùng check_ajax_referer để tránh 400 trên trang standalone
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'cicafood_auth_nonce')) {
        wp_send_json(['success' => false, 'message' => 'Phiên làm việc hết hạn, vui lòng tải lại trang']);
    }

    $username = sanitize_text_field($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = !empty($_POST['remember']);

    if (!$username || !$password) {
        wp_send_json(['success' => false, 'message' => 'Vui lòng nhập đầy đủ thông tin']);
    }

    $creds = [
        'user_login'    => $username,
        'user_password' => $password,
        'remember'      => $remember,
    ];

    $user = wp_signon($creds, false);

    if (is_wp_error($user)) {
        wp_send_json(['success' => false, 'message' => 'Tên đăng nhập hoặc mật khẩu không đúng']);
    }

    // Xác định redirect theo role
    $redirect = get_site_url() . '/';
    if (user_can($user->ID, 'cica_manage_restaurant') || user_can($user->ID, 'manage_options')) {
        $redirect = get_site_url() . '/quan-ly/';
    }

    $role_label = (user_can($user->ID, 'cica_manage_restaurant') || user_can($user->ID, 'manage_options'))
        ? 'merchant' : 'customer';

    wp_send_json([
        'success'  => true,
        'message'  => 'Đăng nhập thành công!',
        'redirect' => $redirect,
        'role'     => $role_label,
    ]);
}

add_action('wp_ajax_nopriv_cica_ajax_register', 'cica_ajax_register_handler');
function cica_ajax_register_handler(): void {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'cicafood_auth_nonce')) {
        wp_send_json(['success' => false, 'message' => 'Phiên làm việc hết hạn, vui lòng tải lại trang']);
    }

    $fullname = sanitize_text_field($_POST['fullname'] ?? '');
    $username = sanitize_user(trim($_POST['username'] ?? ''), true);
    $email    = sanitize_email($_POST['email']         ?? '');
    $password = $_POST['password']                     ?? '';
    $role     = sanitize_text_field($_POST['role']     ?? 'customer'); // customer | merchant_request

    if (!$username || !$email || !$password) {
        wp_send_json(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin']);
    }
    if (strlen($username) < 3) {
        wp_send_json(['success' => false, 'message' => 'Tên đăng nhập tối thiểu 3 ký tự']);
    }
    if (!preg_match('/^[a-zA-Z0-9_\-\.]+$/', $username)) {
        wp_send_json(['success' => false, 'message' => 'Tên đăng nhập chỉ được dùng chữ cái, số, dấu _ - .']);
    }
    if (strlen($password) < 6) {
        wp_send_json(['success' => false, 'message' => 'Mật khẩu tối thiểu 6 ký tự']);
    }
    if (!is_email($email)) {
        wp_send_json(['success' => false, 'message' => 'Email không hợp lệ']);
    }
    if (username_exists($username)) {
        wp_send_json(['success' => false, 'message' => 'Tên đăng nhập đã tồn tại']);
    }
    if (email_exists($email)) {
        wp_send_json(['success' => false, 'message' => 'Email đã được sử dụng']);
    }

    $user_id = wp_create_user($username, $password, $email);
    if (is_wp_error($user_id)) {
        wp_send_json(['success' => false, 'message' => $user_id->get_error_message()]);
    }

    if ($fullname) {
        wp_update_user(['ID' => $user_id, 'display_name' => $fullname]);
    }

    // Lưu role request vào user meta để admin biết
    if ($role === 'merchant_request') {
        update_user_meta($user_id, 'cica_role_request', 'merchant');
    }

    // KHÔNG tự đăng nhập — user sẽ đăng nhập thủ công theo luồng
    // wp_set_current_user($user_id);
    // wp_set_auth_cookie($user_id, false);

    $msg_extra = '';
    if ($role === 'merchant_request') {
        $msg_extra = ' Yêu cầu làm chủ nhà hàng đã được ghi nhận.';
    }

    wp_send_json([
        'success' => true,
        'message' => 'Tạo tài khoản thành công!' . $msg_extra,
        'role'    => $role,
    ]);
}

// ============================================================
// SEARCH AUTOCOMPLETE
// ============================================================
add_action('wp_ajax_cica_search',        'cica_ajax_search');
add_action('wp_ajax_nopriv_cica_search', 'cica_ajax_search');
function cica_ajax_search(): void {
    global $wpdb;
    $q  = sanitize_text_field($_GET['q'] ?? '');
    if (strlen($q) < 2) { wp_send_json(['results' => []]); }

    $like = '%' . $wpdb->esc_like($q) . '%';
    $mi   = $wpdb->prefix . 'cica_menu_items';
    $rt   = $wpdb->prefix . 'cica_restaurants';

    // Tìm món ăn
    $items = $wpdb->get_results($wpdb->prepare(
        "SELECT i.id, i.name, i.price, i.image, r.name AS restaurant_name, r.slug AS restaurant_slug
         FROM $mi i JOIN $rt r ON i.restaurant_id = r.id
         WHERE i.name LIKE %s AND i.is_available = 1 AND r.is_open = 1
         ORDER BY i.is_best_seller DESC, i.name ASC LIMIT 6",
        $like
    ), ARRAY_A) ?: [];

    // Tìm nhà hàng
    $rests = $wpdb->get_results($wpdb->prepare(
        "SELECT id, name, slug, image, category_id FROM $rt WHERE name LIKE %s LIMIT 4",
        $like
    ), ARRAY_A) ?: [];

    wp_send_json([
        'results' => [
            'items'       => $items,
            'restaurants' => $rests,
        ]
    ]);
}

// ============================================================
// REVIEW — Submit sau khi đơn hoàn thành
// ============================================================
add_action('wp_ajax_cica_submit_order_review', 'cica_ajax_submit_order_review');
function cica_ajax_submit_order_review(): void {
    if (!is_user_logged_in()) {
        wp_send_json(['success' => false, 'message' => 'Vui lòng đăng nhập']);
    }
    // Dùng wp_verify_nonce thay check_ajax_referer để tránh die() khi nonce sai
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'cicafood_nonce')) {
        wp_send_json(['success' => false, 'message' => 'Phiên làm việc hết hạn, vui lòng tải lại trang']);
    }

    global $wpdb;
    $rv = $wpdb->prefix . 'cica_reviews';
    $ot = $wpdb->prefix . 'cica_orders';
    $rt = $wpdb->prefix . 'cica_restaurants';

    $order_id     = (int)($_POST['order_id']     ?? 0);
    $rating       = max(1, min(5, (int)($_POST['rating'] ?? 5)));
    $comment      = sanitize_textarea_field($_POST['comment'] ?? '');
    $user_id      = get_current_user_id();

    // Kiểm tra đơn hàng thuộc về user và đã hoàn thành
    $order = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $ot WHERE id = %d AND wp_user_id = %d AND status = 'completed'",
        $order_id, $user_id
    ), ARRAY_A);

    if (!$order) {
        wp_send_json(['success' => false, 'message' => 'Đơn hàng không hợp lệ hoặc chưa hoàn thành']);
    }

    // Kiểm tra đã đánh giá chưa
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $rv WHERE order_id = %d AND wp_user_id = %d",
        $order_id, $user_id
    ));
    if ($existing) {
        wp_send_json(['success' => false, 'message' => 'Bạn đã đánh giá đơn hàng này rồi']);
    }

    // Lưu review
    $wpdb->insert($rv, [
        'wp_user_id'    => $user_id,
        'restaurant_id' => (int)$order['restaurant_id'],
        'order_id'      => $order_id,
        'rating'        => $rating,
        'comment'       => $comment,
        'is_visible'    => 1,
    ]);

    // Cập nhật rating trung bình của nhà hàng
    $avg = $wpdb->get_var($wpdb->prepare(
        "SELECT AVG(rating) FROM $rv WHERE restaurant_id = %d AND is_visible = 1",
        (int)$order['restaurant_id']
    ));
    $count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $rv WHERE restaurant_id = %d AND is_visible = 1",
        (int)$order['restaurant_id']
    ));
    $wpdb->update($rt, [
        'rating'       => round((float)$avg, 1),
        'total_reviews'=> (int)$count,
    ], ['id' => (int)$order['restaurant_id']]);

    wp_send_json(['success' => true, 'message' => 'Cảm ơn bạn đã đánh giá!']);
}

// ============================================================
// MERCHANT — Lấy số đơn hàng đang chờ (polling)
// ============================================================
add_action('wp_ajax_cica_get_pending_orders', 'cica_ajax_get_pending_orders');
function cica_ajax_get_pending_orders(): void {
    if (!is_user_logged_in()) { wp_send_json(['count' => 0]); }

    global $wpdb;
    $ot  = $wpdb->prefix . 'cica_orders';
    $rt  = $wpdb->prefix . 'cica_restaurants';

    if (current_user_can('manage_options')) {
        // Admin: đếm tất cả đơn pending
        $count = (int)$wpdb->get_var(
            "SELECT COUNT(*) FROM $ot WHERE status = 'pending'"
        );
    } else {
        // Merchant: chỉ đếm đơn của quán mình
        $rid = (int)$wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $rt WHERE wp_user_id = %d LIMIT 1",
            get_current_user_id()
        ));
        if (!$rid) { wp_send_json(['count' => 0]); }
        $count = (int)$wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $ot WHERE restaurant_id = %d AND status = 'pending'",
            $rid
        ));
    }

    wp_send_json(['count' => $count]);
}

// ============================================================
// MERCHANT — Save Settings
// ============================================================
add_action('wp_ajax_cica_save_merchant_settings', 'cica_ajax_save_merchant_settings');
function cica_ajax_save_merchant_settings(): void {
    if (!is_user_logged_in()) { wp_send_json(['success' => false, 'message' => 'Chưa đăng nhập']); }
    check_ajax_referer('cicafood_nonce', 'nonce');
    
    global $wpdb;
    $rt = $wpdb->prefix . 'cica_restaurants';
    
    $rid = cica_get_merchant_rid();
    if ($rid === 0) { wp_send_json(['success' => false, 'message' => 'Bạn chưa có nhà hàng']); }
    
    $rest_id = ($rid === -1) ? (int)($_POST['restaurant_id'] ?? 0) : $rid;
    
    $name         = sanitize_text_field($_POST['name'] ?? '');
    $phone        = sanitize_text_field($_POST['phone'] ?? '');
    $address      = sanitize_text_field($_POST['address'] ?? '');
    $province     = sanitize_text_field($_POST['province'] ?? '');
    $delivery_fee = (int)($_POST['delivery_fee'] ?? 0);
    $image        = esc_url_raw($_POST['image'] ?? '');
    $cover_image  = esc_url_raw($_POST['cover_image'] ?? '');
    
    if (!$name || !$phone || !$address || !$province) {
        wp_send_json(['success' => false, 'message' => 'Vui lòng điền đầy đủ các trường bắt buộc']);
    }
    
    $data = [
        'name'         => $name,
        'phone'        => $phone,
        'address'      => $address,
        'province'     => $province,
        'delivery_fee' => $delivery_fee,
    ];
    
    if ($image) $data['image'] = $image;
    if ($cover_image) $data['cover_image'] = $cover_image;
    
    $updated = $wpdb->update($rt, $data, ['id' => $rest_id]);
    
    if ($updated !== false) {
        wp_send_json(['success' => true, 'message' => 'Cài đặt đã được lưu thành công!']);
    } else {
        wp_send_json(['success' => false, 'message' => 'Lỗi khi lưu cài đặt']);
    }
}

// ============================================================
// EXPORT — Xuất đơn hàng ra Excel (CSV UTF-8)
// ============================================================
add_action('wp_ajax_cica_export_orders_excel', 'cica_ajax_export_orders_excel');
function cica_ajax_export_orders_excel(): void {
    if (!is_user_logged_in()) { wp_die('Chưa đăng nhập'); }
    if (!wp_verify_nonce($_GET['nonce'] ?? '', 'cicafood_nonce')) { wp_die('Nonce không hợp lệ'); }

    global $wpdb;
    $ot = $wpdb->prefix . 'cica_orders';
    $rt = $wpdb->prefix . 'cica_restaurants';

    $rid = cica_get_merchant_rid();
    if ($rid === 0) { wp_die('Bạn chưa có nhà hàng'); }
    $rest_id = ($rid === -1) ? (int)($_GET['restaurant_id'] ?? 0) : $rid;

    // Lấy tên nhà hàng
    $rest_name = $wpdb->get_var($wpdb->prepare("SELECT name FROM $rt WHERE id=%d", $rest_id)) ?: 'NhaHang';
    $safe_name = sanitize_file_name($rest_name);

    // Lấy đơn hàng
    $orders = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $ot WHERE restaurant_id=%d ORDER BY created_at DESC",
        $rest_id
    ), ARRAY_A) ?: [];

    // Status labels
    $statuses = [
        'pending'    => 'Chờ xác nhận',
        'confirmed'  => 'Đã xác nhận',
        'preparing'  => 'Đang chuẩn bị',
        'delivering' => 'Đang giao',
        'completed'  => 'Hoàn thành',
        'cancelled'  => 'Đã hủy',
    ];

    // Payment labels
    $payments = [
        'cod'           => 'Thanh toán khi nhận',
        'bank_transfer' => 'Chuyển khoản QR',
    ];

    // Output CSV with UTF-8 BOM for Excel compatibility
    $filename = 'DonHang_' . $safe_name . '_' . date('Y-m-d_His') . '.csv';
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');
    // UTF-8 BOM
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    // Header row — Tiêu đề báo cáo
    fputcsv($output, ['BÁO CÁO ĐƠN HÀNG — ' . strtoupper($rest_name)]);
    fputcsv($output, ['Ngày xuất: ' . date('d/m/Y H:i:s')]);
    fputcsv($output, []); // Empty row

    // Column headers
    fputcsv($output, [
        'STT',
        'Mã đơn hàng',
        'Tên người nhận',
        'Số điện thoại',
        'Địa chỉ giao',
        'Tổng tiền (VND)',
        'Phí giao hàng',
        'Giảm giá',
        'Thanh toán',
        'Phương thức TT',
        'Trạng thái',
        'Ghi chú',
        'Ngày đặt',
    ]);

    // Data rows
    $stt = 0;
    $total_revenue = 0;
    $total_completed = 0;
    foreach ($orders as $o) {
        $stt++;
        if ($o['status'] === 'completed') {
            $total_revenue += (int)$o['total_amount'];
            $total_completed++;
        }
        fputcsv($output, [
            $stt,
            $o['order_code'],
            $o['recipient_name'],
            $o['recipient_phone'],
            $o['delivery_address'],
            number_format((int)$o['total_amount'], 0, ',', '.'),
            number_format((int)$o['delivery_fee'], 0, ',', '.'),
            number_format((int)$o['discount_amount'], 0, ',', '.'),
            number_format((int)$o['total_amount'], 0, ',', '.'),
            $payments[$o['payment_method']] ?? $o['payment_method'],
            $statuses[$o['status']] ?? $o['status'],
            $o['note'] ?? '',
            date('d/m/Y H:i', strtotime($o['created_at'])),
        ]);
    }

    // Summary rows
    fputcsv($output, []);
    fputcsv($output, ['', '', '', '', 'TỔNG ĐƠN HÀNG:', count($orders)]);
    fputcsv($output, ['', '', '', '', 'ĐƠN HOÀN THÀNH:', $total_completed]);
    fputcsv($output, ['', '', '', '', 'TỔNG DOANH THU:', number_format($total_revenue, 0, ',', '.') . 'đ']);

    fclose($output);
    exit;
}