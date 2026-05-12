<?php
defined('ABSPATH') || exit;

/**
 * Admin Menu — Quản lý Cicafood trong WP Admin
 */
add_action('admin_menu', function () {
    add_menu_page(
        'Cicafood',
        'Cicafood',
        'manage_options',
        'cicafood',
        'cica_admin_dashboard',
        'dashicons-food',
        30
    );
    add_submenu_page('cicafood', 'Dashboard',        '📊 Dashboard',      'manage_options', 'cicafood',             'cica_admin_dashboard');
    add_submenu_page('cicafood', 'Nhà hàng',         '🏪 Nhà hàng',         'manage_options', 'cicafood-restaurants', 'cica_admin_restaurants');
    add_submenu_page('cicafood', 'Đơn hàng',         '📦 Đơn hàng',         'manage_options', 'cicafood-orders',      'cica_admin_orders');
    add_submenu_page('cicafood', 'Voucher',           '🎟️ Voucher',           'manage_options', 'cicafood-vouchers',    'cica_admin_vouchers');
    add_submenu_page('cicafood', 'Danh mục',         '📂 Danh mục',         'manage_options', 'cicafood-categories',  'cica_admin_categories');
    add_submenu_page('cicafood', 'Quản lý Tài khoản','👥 Tài khoản',     'manage_options', 'cicafood-users',       'cica_admin_users');
    add_submenu_page('cicafood', 'Cài đặt',          '⚙️ Cài đặt',       'manage_options', 'cicafood-settings',    'cica_admin_settings');
    add_submenu_page('cicafood', 'Python Analytics',  '🐍 Analytics',     'manage_options', 'cicafood-analytics',   'cica_admin_analytics');
    add_submenu_page('cicafood', 'Phân quyền',       '🔐 Phân quyền',    'manage_options', 'cicafood-users',       'cica_admin_users');
});

// ============================================================
// DASHBOARD
// ============================================================
function cica_admin_dashboard(): void {
    global $wpdb;
    $rest_count  = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}cica_restaurants");
    $order_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}cica_orders");
    $revenue     = $wpdb->get_var("SELECT SUM(total_amount) FROM {$wpdb->prefix}cica_orders WHERE status='completed'");
    $review_count= $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}cica_reviews");
    ?>
    <div class="wrap">
        <h1>🍜 Cicafood Dashboard</h1>
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:20px;margin:20px 0">
            <?php
            $cards = [
                ['🏪', 'Nhà hàng', $rest_count, '#ee2624'],
                ['📦', 'Đơn hàng', $order_count, '#f97316'],
                ['💰', 'Doanh thu', number_format((int)$revenue, 0, ',', '.') . 'đ', '#22c55e'],
                ['⭐', 'Đánh giá', $review_count, '#eab308'],
            ];
            foreach ($cards as [$icon, $label, $value, $color]): ?>
            <div style="background:white;border-radius:12px;padding:24px;box-shadow:0 2px 8px rgba(0,0,0,0.08);border-left:4px solid <?= $color ?>;display:flex;align-items:center;gap:16px">
                <div style="font-size:1.5rem;line-height:1"><?= $icon ?></div>
                <div>
                    <div style="font-size:1.6rem;font-weight:800;color:<?= $color ?>"><?= $value ?></div>
                    <div style="color:#6b7280;font-size:0.875rem"><?= $label ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <h2>Đơn hàng gần đây</h2>
        <?php
        $orders = $wpdb->get_results(
            "SELECT o.*, r.name AS restaurant_name FROM {$wpdb->prefix}cica_orders o
             LEFT JOIN {$wpdb->prefix}cica_restaurants r ON o.restaurant_id = r.id
             ORDER BY o.created_at DESC LIMIT 10",
            ARRAY_A
        );
        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead><tr>
                <th>Mã đơn</th><th>Nhà hàng</th><th>Người nhận</th>
                <th>Tổng tiền</th><th>Trạng thái</th><th>Ngày đặt</th>
            </tr></thead>
            <tbody>
            <?php foreach ($orders as $o):
                $sl = Cicafood_Order::status_label($o['status']);
            ?>
            <tr>
                <td><strong><?= esc_html($o['order_code']) ?></strong></td>
                <td><?= esc_html($o['restaurant_name']) ?></td>
                <td><?= esc_html($o['recipient_name']) ?></td>
                <td><strong style="color:#ee2624"><?= number_format((int)$o['total_amount'], 0, ',', '.') ?>đ</strong></td>
                <td><span style="background:#f3f4f6;padding:3px 10px;border-radius:999px;font-size:12px;font-weight:600"><?= esc_html($sl['label']) ?></span></td>
                <td><?= esc_html(date('d/m/Y H:i', strtotime($o['created_at']))) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

// ============================================================
// RESTAURANTS
// ============================================================
function cica_admin_restaurants(): void {
    global $wpdb;
    $table = $wpdb->prefix . 'cica_restaurants';

    // Handle toggle open
    if (isset($_POST['toggle_open']) && isset($_POST['restaurant_id'])) {
        check_admin_referer('cica_admin_rest');
        $id = (int)$_POST['restaurant_id'];
        $current = $wpdb->get_var($wpdb->prepare("SELECT is_open FROM $table WHERE id = %d", $id));
        $wpdb->update($table, ['is_open' => $current ? 0 : 1], ['id' => $id]);
        echo '<div class="notice notice-success is-dismissible"><p>Đã cập nhật trạng thái nhà hàng.</p></div>';
    }

    // Handle add new
    if (isset($_POST['add_restaurant'])) {
        check_admin_referer('cica_admin_rest');
        $wpdb->insert($table, [
            'name' => sanitize_text_field($_POST['name']),
            'address' => sanitize_text_field($_POST['address']),
            'province' => sanitize_text_field($_POST['province']),
            'phone' => sanitize_text_field($_POST['phone']),
            'category_id' => (int)$_POST['category_id'],
            'delivery_fee' => (int)$_POST['delivery_fee'],
            'is_open' => 1,
            'rating' => 5.0,
            'total_reviews' => 0
        ]);
        echo '<div class="notice notice-success is-dismissible"><p>Đã thêm nhà hàng mới thành công!</p></div>';
    }

    // Handle delete
    if (isset($_POST['delete_restaurant'])) {
        check_admin_referer('cica_admin_rest');
        $id = (int)$_POST['restaurant_id'];
        $wpdb->delete($table, ['id' => $id]);
        $wpdb->delete($wpdb->prefix . 'cica_menu_items', ['restaurant_id' => $id]);
        $wpdb->delete($wpdb->prefix . 'cica_menu_categories', ['restaurant_id' => $id]);
        $wpdb->update($wpdb->prefix . 'cica_restaurants', ['wp_user_id' => null], ['id' => $id]); // Unassign users just in case
        echo '<div class="notice notice-success is-dismissible"><p>Đã xóa nhà hàng.</p></div>';
    }

    $restaurants = $wpdb->get_results("SELECT r.*, c.name AS cat_name FROM $table r LEFT JOIN {$wpdb->prefix}cica_categories c ON r.category_id = c.id ORDER BY r.created_at DESC", ARRAY_A);
    $categories = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}cica_categories ORDER BY sort_order", ARRAY_A);
    ?>
    <div class="wrap">
        <h1>🏪 Quản lý Nhà hàng</h1>

        <h2>Thêm nhà hàng mới</h2>
        <form method="POST" style="background:white;padding:20px;border-radius:8px;margin-bottom:20px;display:grid;grid-template-columns:repeat(3,1fr);gap:12px;box-shadow:0 1px 3px rgba(0,0,0,0.1)">
            <?php wp_nonce_field('cica_admin_rest'); ?>
            <div><label>Tên quán *</label><br><input type="text" name="name" class="regular-text" required></div>
            <div><label>Số điện thoại *</label><br><input type="text" name="phone" class="regular-text" required></div>
            <div><label>Danh mục</label><br>
                <select name="category_id" class="regular-text">
                    <option value="0">-- Chọn --</option>
                    <?php foreach ($categories as $c): ?>
                    <option value="<?= (int)$c['id'] ?>"><?= esc_html($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div><label>Tỉnh/Thành phố *</label><br>
                <select name="province" class="regular-text">
                    <option value="Hà Nội">Hà Nội</option>
                    <option value="TP.HCM">TP.HCM</option>
                    <option value="Đà Nẵng">Đà Nẵng</option>
                    <option value="Hải Phòng">Hải Phòng</option>
                    <option value="Cần Thơ">Cần Thơ</option>
                </select>
            </div>
            <div><label>Địa chỉ chi tiết *</label><br><input type="text" name="address" class="regular-text" required></div>
            <div><label>Phí giao hàng (VND)</label><br><input type="number" name="delivery_fee" value="15000" class="regular-text"></div>
            <div style="grid-column:1/-1"><button type="submit" name="add_restaurant" class="button button-primary">Thêm nhà hàng</button></div>
        </form>

        <h2>Danh sách nhà hàng</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead><tr>
                <th>Tên quán</th><th>Danh mục</th><th>Tỉnh/TP</th>
                <th>Rating</th><th>Trạng thái</th><th>Thao tác</th>
            </tr></thead>
            <tbody>
            <?php foreach ($restaurants as $r): ?>
            <tr>
                <td><strong><?= esc_html($r['name']) ?></strong><br><small style="color:#9ca3af"><?= esc_html($r['address']) ?></small></td>
                <td><?= esc_html($r['cat_name'] ?? '—') ?></td>
                <td><?= esc_html($r['province']) ?></td>
                <td>⭐ <?= esc_html($r['rating']) ?> (<?= (int)$r['total_reviews'] ?>)</td>
                <td>
                    <span style="background:<?= $r['is_open'] ? '#dcfce7' : '#fee2e2' ?>;color:<?= $r['is_open'] ? '#16a34a' : '#dc2626' ?>;padding:3px 10px;border-radius:999px;font-size:12px;font-weight:600">
                        <?= $r['is_open'] ? 'Đang mở' : 'Đóng cửa' ?>
                    </span>
                </td>
                <td>
                    <form method="POST" style="display:inline;margin-right:4px">
                        <?php wp_nonce_field('cica_admin_rest'); ?>
                        <input type="hidden" name="restaurant_id" value="<?= (int)$r['id'] ?>">
                        <button type="submit" name="toggle_open" class="button button-small">
                            <?= $r['is_open'] ? 'Đóng cửa' : 'Mở cửa' ?>
                        </button>
                    </form>
                    <form method="POST" style="display:inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa nhà hàng này? Tất cả món ăn của nhà hàng sẽ bị xóa!')">
                        <?php wp_nonce_field('cica_admin_rest'); ?>
                        <input type="hidden" name="restaurant_id" value="<?= (int)$r['id'] ?>">
                        <button type="submit" name="delete_restaurant" class="button button-small" style="color:#dc2626;border-color:#dc2626">Xóa</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

// ============================================================
// ORDERS
// ============================================================
function cica_admin_orders(): void {
    global $wpdb;
    $table = $wpdb->prefix . 'cica_orders';

    // Handle status update
    if (isset($_POST['update_status']) && isset($_POST['order_id'])) {
        check_admin_referer('cica_update_order');
        $valid_statuses = ['pending','confirmed','preparing','delivering','completed','cancelled'];
        $new_status = $_POST['new_status'] ?? '';
        if (in_array($new_status, $valid_statuses)) {
            $wpdb->update($table, ['status' => $new_status], ['id' => (int)$_POST['order_id']]);
            echo '<div class="notice notice-success"><p>Đã cập nhật trạng thái đơn hàng.</p></div>';
        }
    }

    $orders = $wpdb->get_results(
        "SELECT o.*, r.name AS restaurant_name FROM $table o
         LEFT JOIN {$wpdb->prefix}cica_restaurants r ON o.restaurant_id = r.id
         ORDER BY o.created_at DESC LIMIT 50",
        ARRAY_A
    );
    $statuses = ['pending','confirmed','preparing','delivering','completed','cancelled'];
    ?>
    <div class="wrap">
        <h1>📦 Quản lý Đơn hàng</h1>
        <table class="wp-list-table widefat fixed striped">
            <thead><tr>
                <th>Mã đơn</th><th>Nhà hàng</th><th>Người nhận</th>
                <th>Tổng tiền</th><th>Trạng thái</th><th>Cập nhật</th><th>Ngày đặt</th>
            </tr></thead>
            <tbody>
            <?php foreach ($orders as $o):
                $sl = Cicafood_Order::status_label($o['status']);
            ?>
            <tr>
                <td><strong><?= esc_html($o['order_code']) ?></strong></td>
                <td><?= esc_html($o['restaurant_name']) ?></td>
                <td><?= esc_html($o['recipient_name']) ?><br><small><?= esc_html($o['recipient_phone']) ?></small></td>
                <td><strong style="color:#ee2624"><?= number_format((int)$o['total_amount'], 0, ',', '.') ?>đ</strong></td>
                <td><span style="font-size:12px;font-weight:600"><?= esc_html($sl['label']) ?></span></td>
                <td>
                    <form method="POST" style="display:flex;gap:4px">
                        <?php wp_nonce_field('cica_update_order'); ?>
                        <input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>">
                        <select name="new_status" style="font-size:12px">
                            <?php foreach ($statuses as $s): ?>
                            <option value="<?= $s ?>" <?= $o['status'] === $s ? 'selected' : '' ?>><?= Cicafood_Order::status_label($s)['label'] ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" name="update_status" class="button button-small">Lưu</button>
                    </form>
                </td>
                <td><?= esc_html(date('d/m/Y H:i', strtotime($o['created_at']))) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

// ============================================================
// VOUCHERS
// ============================================================
function cica_admin_vouchers(): void {
    global $wpdb;
    $table = $wpdb->prefix . 'cica_vouchers';

    // Handle add voucher
    if (isset($_POST['add_voucher'])) {
        check_admin_referer('cica_add_voucher');
        $wpdb->insert($table, [
            'code'        => strtoupper(sanitize_text_field($_POST['code'])),
            'name'        => sanitize_text_field($_POST['name']),
            'description' => sanitize_textarea_field($_POST['description']),
            'type'        => sanitize_text_field($_POST['type']),
            'value'       => (int)$_POST['value'],
            'max_discount'=> (int)$_POST['max_discount'] ?: null,
            'min_order'   => (int)$_POST['min_order'],
            'usage_limit' => (int)$_POST['usage_limit'],
            'end_date'    => sanitize_text_field($_POST['end_date']),
        ]);
        echo '<div class="notice notice-success"><p>Đã thêm voucher mới.</p></div>';
    }

    $vouchers = $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC", ARRAY_A);
    ?>
    <div class="wrap">
        <h1>🎟️ Quản lý Voucher</h1>

        <h2>Thêm voucher mới</h2>
        <form method="POST" style="background:white;padding:20px;border-radius:8px;margin-bottom:20px;display:grid;grid-template-columns:repeat(3,1fr);gap:12px">
            <?php wp_nonce_field('cica_add_voucher'); ?>
            <div><label>Mã voucher *</label><br><input type="text" name="code" class="regular-text" required style="text-transform:uppercase"></div>
            <div><label>Tên voucher *</label><br><input type="text" name="name" class="regular-text" required></div>
            <div><label>Loại *</label><br>
                <select name="type" class="regular-text">
                    <option value="percent">Phần trăm (%)</option>
                    <option value="fixed">Số tiền cố định</option>
                    <option value="freeship">Freeship</option>
                </select>
            </div>
            <div><label>Giá trị *</label><br><input type="number" name="value" class="regular-text" required></div>
            <div><label>Giảm tối đa (VND)</label><br><input type="number" name="max_discount" class="regular-text"></div>
            <div><label>Đơn tối thiểu (VND)</label><br><input type="number" name="min_order" class="regular-text" value="0"></div>
            <div><label>Giới hạn sử dụng</label><br><input type="number" name="usage_limit" class="regular-text" value="100"></div>
            <div><label>Ngày hết hạn</label><br><input type="date" name="end_date" class="regular-text"></div>
            <div><label>Mô tả</label><br><input type="text" name="description" class="regular-text"></div>
            <div style="grid-column:1/-1"><button type="submit" name="add_voucher" class="button button-primary">Thêm Voucher</button></div>
        </form>

        <h2>Danh sách voucher</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead><tr><th>Mã</th><th>Tên</th><th>Loại</th><th>Giá trị</th><th>Đơn tối thiểu</th><th>Hết hạn</th><th>Đã dùng</th></tr></thead>
            <tbody>
            <?php foreach ($vouchers as $v): ?>
            <tr>
                <td><strong style="color:#ee2624"><?= esc_html($v['code']) ?></strong></td>
                <td><?= esc_html($v['name']) ?></td>
                <td><?= esc_html($v['type']) ?></td>
                <td><?= $v['type'] === 'percent' ? $v['value'].'%' : number_format((int)$v['value'], 0, ',', '.').'đ' ?></td>
                <td><?= number_format((int)$v['min_order'], 0, ',', '.') ?>đ</td>
                <td><?= esc_html($v['end_date'] ?? '—') ?></td>
                <td><?= (int)$v['used_count'] ?>/<?= (int)$v['usage_limit'] ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

// ============================================================
// CATEGORIES
// ============================================================
function cica_admin_categories(): void {
    global $wpdb;
    $table = $wpdb->prefix . 'cica_categories';

    if (isset($_POST['add_category'])) {
        check_admin_referer('cica_add_category');
        $name = sanitize_text_field($_POST['name']);
        $wpdb->insert($table, [
            'name'       => $name,
            'slug'       => sanitize_title($name),
            'icon'       => sanitize_text_field($_POST['icon']),
            'color'      => sanitize_hex_color($_POST['color']),
            'sort_order' => (int)$_POST['sort_order'],
        ]);
        echo '<div class="notice notice-success"><p>Đã thêm danh mục.</p></div>';
    }

    $cats = $wpdb->get_results("SELECT * FROM $table ORDER BY sort_order ASC", ARRAY_A);
    ?>
    <div class="wrap">
        <h1>📂 Danh mục nhà hàng</h1>
        <form method="POST" style="background:white;padding:20px;border-radius:8px;margin-bottom:20px;display:flex;gap:12px;flex-wrap:wrap">
            <?php wp_nonce_field('cica_add_category'); ?>
            <div><label>Tên *</label><br><input type="text" name="name" class="regular-text" required></div>
            <div><label>Icon (FontAwesome)</label><br><input type="text" name="icon" class="regular-text" value="fa-utensils" placeholder="fa-utensils"></div>
            <div><label>Màu</label><br><input type="color" name="color" value="#ee2624"></div>
            <div><label>Thứ tự</label><br><input type="number" name="sort_order" class="small-text" value="0"></div>
            <div style="align-self:flex-end"><button type="submit" name="add_category" class="button button-primary">Thêm</button></div>
        </form>
        <table class="wp-list-table widefat fixed striped">
            <thead><tr><th>ID</th><th>Tên</th><th>Slug</th><th>Icon</th><th>Màu</th><th>Thứ tự</th></tr></thead>
            <tbody>
            <?php foreach ($cats as $c): ?>
            <tr>
                <td><?= (int)$c['id'] ?></td>
                <td><strong><?= esc_html($c['name']) ?></strong></td>
                <td><?= esc_html($c['slug']) ?></td>
                <td><i class="fas <?= esc_attr($c['icon']) ?>"></i> <?= esc_html($c['icon']) ?></td>
                <td><span style="background:<?= esc_attr($c['color']) ?>;color:white;padding:2px 8px;border-radius:4px"><?= esc_html($c['color']) ?></span></td>
                <td><?= (int)$c['sort_order'] ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

// ============================================================
// PHÂN QUYỀN — Gán nhà hàng cho user

// ============================================================
// QUẢN LÝ TÀI KHOẢN
// ============================================================
function cica_admin_users(): void {
    global $wpdb;
    $rt = $wpdb->prefix . 'cica_restaurants';
    $notice = '';
    $tab = sanitize_text_field($_GET['subtab'] ?? 'pending');

    // ---- Xử lý form ----
    if (isset($_POST['cica_users_nonce']) && wp_verify_nonce($_POST['cica_users_nonce'], 'cica_users_action')) {
        $action = sanitize_text_field($_POST['user_action'] ?? '');

        // Duyệt yêu cầu merchant
        if ($action === 'approve_merchant') {
            $uid  = (int)$_POST['user_id'];
            $rid  = (int)$_POST['restaurant_id'];
            if ($uid && $rid) {
                // Gỡ nhà hàng cũ
                $wpdb->update($rt, ['wp_user_id' => null], ['wp_user_id' => $uid]);
                $wpdb->update($rt, ['wp_user_id' => null], ['id' => $rid]);
                // Gán mới
                $wpdb->update($rt, ['wp_user_id' => $uid], ['id' => $rid]);
                // Cấp role
                $u = new WP_User($uid);
                $u->set_role('cicafood_merchant');
                // Xóa meta request
                delete_user_meta($uid, 'cica_role_request');
                $notice = '<div class="notice notice-success is-dismissible"><p>✅ Đã duyệt và cấp quyền Chủ nhà hàng!</p></div>';
            }
        }

        // Từ chối yêu cầu
        if ($action === 'reject_merchant') {
            $uid = (int)$_POST['user_id'];
            delete_user_meta($uid, 'cica_role_request');
            $notice = '<div class="notice notice-warning is-dismissible"><p>❌ Đã từ chối yêu cầu.</p></div>';
        }

        // Gán nhà hàng thủ công
        if ($action === 'assign') {
            $uid = (int)$_POST['user_id'];
            $rid = (int)$_POST['restaurant_id'];
            if ($uid && $rid) {
                $wpdb->update($rt, ['wp_user_id' => null], ['wp_user_id' => $uid]);
                $wpdb->update($rt, ['wp_user_id' => null], ['id' => $rid]);
                $wpdb->update($rt, ['wp_user_id' => $uid], ['id' => $rid]);
                $u = new WP_User($uid);
                $u->set_role('cicafood_merchant');
                $notice = '<div class="notice notice-success is-dismissible"><p>✅ Đã gán nhà hàng và cấp quyền!</p></div>';
            }
        }

        // Gỡ quyền merchant
        if ($action === 'revoke') {
            $uid = (int)$_POST['user_id'];
            $wpdb->update($rt, ['wp_user_id' => null], ['wp_user_id' => $uid]);
            $u = new WP_User($uid);
            $u->set_role('subscriber');
            $notice = '<div class="notice notice-warning is-dismissible"><p>🔄 Đã gỡ quyền Chủ nhà hàng.</p></div>';
        }

        // Xóa tài khoản
        if ($action === 'delete_user') {
            $uid = (int)$_POST['user_id'];
            if ($uid !== get_current_user_id()) {
                require_once ABSPATH . 'wp-admin/includes/user.php';
                wp_delete_user($uid);
                $notice = '<div class="notice notice-success is-dismissible"><p>🗑️ Đã xóa tài khoản.</p></div>';
            }
        }
    }

    // ---- Lấy dữ liệu ----
    $pending_users = get_users(['meta_key' => 'cica_role_request', 'meta_value' => 'merchant']);
    $all_users     = get_users(['orderby' => 'registered', 'order' => 'DESC', 'number' => 50]);
    $restaurants   = $wpdb->get_results("SELECT r.*, u.display_name AS owner_name FROM $rt r LEFT JOIN {$wpdb->users} u ON r.wp_user_id = u.ID ORDER BY r.name", ARRAY_A);
    $free_rests    = array_filter($restaurants, fn($r) => !$r['wp_user_id']);

    $pending_count = count($pending_users);
    ?>
    <div class="wrap">
        <h1 style="display:flex;align-items:center;gap:10px">
            👥 Quản lý Tài khoản
            <?php if ($pending_count > 0): ?>
            <span style="background:#ee2624;color:white;font-size:12px;font-weight:700;padding:2px 10px;border-radius:999px"><?= $pending_count ?> chờ duyệt</span>
            <?php endif; ?>
        </h1>

        <?= $notice ?>

        <!-- Tabs -->
        <nav class="nav-tab-wrapper" style="margin-bottom:20px">
            <a href="?page=cicafood-users&subtab=pending" class="nav-tab <?= $tab==='pending' ? 'nav-tab-active' : '' ?>">
                ⏳ Chờ duyệt <?= $pending_count > 0 ? "($pending_count)" : '' ?>
            </a>
            <a href="?page=cicafood-users&subtab=assign" class="nav-tab <?= $tab==='assign' ? 'nav-tab-active' : '' ?>">
                🔗 Phân quyền
            </a>
            <a href="?page=cicafood-users&subtab=list" class="nav-tab <?= $tab==='list' ? 'nav-tab-active' : '' ?>">
                📋 Danh sách tài khoản
            </a>
        </nav>

        <?php if ($tab === 'pending'): ?>
        <!-- ===== TAB: CHỜ DUYỆT ===== -->
        <?php if (empty($pending_users)): ?>
        <div style="background:white;border-radius:12px;padding:40px;text-align:center;box-shadow:0 2px 8px rgba(0,0,0,0.06)">
            <div style="font-size:1.5rem;margin-bottom:8px">✅</div>
            <h3 style="color:#374151;margin:0 0 8px">Không có yêu cầu nào đang chờ</h3>
            <p style="color:#9ca3af;margin:0">Khi có user đăng ký vai trò Chủ nhà hàng, họ sẽ xuất hiện ở đây.</p>
        </div>
        <?php else: ?>
        <table class="wp-list-table widefat fixed striped">
            <thead><tr>
                <th>Người dùng</th><th>Email</th><th>Ngày đăng ký</th><th>Gán nhà hàng</th><th>Thao tác</th>
            </tr></thead>
            <tbody>
            <?php foreach ($pending_users as $u): ?>
            <tr>
                <td>
                    <strong><?= esc_html($u->display_name) ?></strong><br>
                    <small style="color:#9ca3af">@<?= esc_html($u->user_login) ?></small>
                </td>
                <td><?= esc_html($u->user_email) ?></td>
                <td><?= date('d/m/Y', strtotime($u->user_registered)) ?></td>
                <td>
                    <form method="POST" style="display:flex;gap:6px;align-items:center;flex-wrap:wrap">
                        <?php wp_nonce_field('cica_users_action', 'cica_users_nonce'); ?>
                        <input type="hidden" name="user_action" value="approve_merchant">
                        <input type="hidden" name="user_id" value="<?= (int)$u->ID ?>">
                        <select name="restaurant_id" required style="font-size:13px;padding:4px 8px;border-radius:6px;border:1px solid #ddd;height:32px">
                            <option value="">-- Chọn nhà hàng --</option>
                            <?php foreach ($free_rests as $r): ?>
                            <option value="<?= (int)$r['id'] ?>"><?= esc_html($r['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="button button-primary" style="background:#16a34a;border-color:#16a34a;height:32px;line-height:30px;padding:0 12px;white-space:nowrap">
                            ✅ Duyệt &amp; Gán
                        </button>
                    </form>
                </td>
                <td style="vertical-align:middle">
                    <form method="POST" style="display:inline" onsubmit="return confirm('Từ chối yêu cầu này?')">
                        <?php wp_nonce_field('cica_users_action', 'cica_users_nonce'); ?>
                        <input type="hidden" name="user_action" value="reject_merchant">
                        <input type="hidden" name="user_id" value="<?= (int)$u->ID ?>">
                        <button type="submit" class="button" style="color:#dc2626;border-color:#dc2626;height:32px;line-height:30px;padding:0 12px">❌ Từ chối</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>

        <?php elseif ($tab === 'assign'): ?>
        <!-- ===== TAB: PHÂN QUYỀN ===== -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px">
            <!-- Form gán -->
            <div style="background:white;border-radius:12px;padding:24px;box-shadow:0 2px 8px rgba(0,0,0,0.06)">
                <h3 style="margin-top:0">Gán nhà hàng cho user</h3>
                <form method="POST">
                    <?php wp_nonce_field('cica_users_action', 'cica_users_nonce'); ?>
                    <input type="hidden" name="user_action" value="assign">
                    <table class="form-table">
                        <tr>
                            <th><label>Chọn User</label></th>
                            <td>
                                <select name="user_id" class="regular-text" required>
                                    <option value="">-- Chọn user --</option>
                                    <?php foreach ($all_users as $u):
                                        if (in_array('administrator', $u->roles)) continue;
                                    ?>
                                    <option value="<?= (int)$u->ID ?>">
                                        <?= esc_html($u->display_name) ?> (@<?= esc_html($u->user_login) ?>)
                                        — <?= in_array('cicafood_merchant', $u->roles) ? '🏪 Merchant' : '👤 Khách' ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label>Nhà hàng</label></th>
                            <td>
                                <select name="restaurant_id" class="regular-text" required>
                                    <option value="">-- Chọn nhà hàng --</option>
                                    <?php foreach ($restaurants as $r): ?>
                                    <option value="<?= (int)$r['id'] ?>">
                                        <?= esc_html($r['name']) ?>
                                        <?= $r['owner_name'] ? ' (đang do ' . esc_html($r['owner_name']) . ' quản lý)' : ' (chưa có chủ)' ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                    </table>
                    <button type="submit" class="button button-primary button-large">✅ Gán & Cấp quyền</button>
                </form>
            </div>

            <!-- Danh sách hiện tại -->
            <div style="background:white;border-radius:12px;padding:24px;box-shadow:0 2px 8px rgba(0,0,0,0.06)">
                <h3 style="margin-top:0">Phân quyền hiện tại</h3>
                <table class="wp-list-table widefat fixed striped" style="font-size:13px">
                    <thead><tr><th>Nhà hàng</th><th>Chủ quản lý</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($restaurants as $r): ?>
                    <tr>
                        <td><strong><?= esc_html($r['name']) ?></strong></td>
                        <td>
                            <?php if ($r['owner_name']): ?>
                            <span style="color:#16a34a">✅ <?= esc_html($r['owner_name']) ?></span>
                            <?php else: ?>
                            <span style="color:#9ca3af">— Chưa có</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($r['wp_user_id']): ?>
                            <form method="POST" style="display:inline" onsubmit="return confirm('Gỡ quyền?')">
                                <?php wp_nonce_field('cica_users_action', 'cica_users_nonce'); ?>
                                <input type="hidden" name="user_action" value="revoke">
                                <input type="hidden" name="user_id" value="<?= (int)$r['wp_user_id'] ?>">
                                <button type="submit" class="button button-small" style="color:#dc2626">Gỡ</button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php else: ?>
        <!-- ===== TAB: DANH SÁCH TÀI KHOẢN ===== -->
        <div style="background:white;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,0.06);overflow:hidden">
            <table class="wp-list-table widefat fixed striped">
                <thead><tr>
                    <th>Tài khoản</th><th>Email</th><th>Vai trò</th>
                    <th>Nhà hàng</th><th>Ngày tạo</th><th>Thao tác</th>
                </tr></thead>
                <tbody>
                <?php foreach ($all_users as $u):
                    $is_admin    = in_array('administrator', $u->roles);
                    $is_merchant = in_array('cicafood_merchant', $u->roles);
                    $rest_name   = $wpdb->get_var($wpdb->prepare("SELECT name FROM $rt WHERE wp_user_id=%d", $u->ID));
                    $role_label  = $is_admin ? '🔧 Admin' : ($is_merchant ? '🏪 Chủ nhà hàng' : '👤 Khách hàng');
                    $role_color  = $is_admin ? '#7c3aed' : ($is_merchant ? '#ee2624' : '#374151');
                    $role_bg     = $is_admin ? '#f5f3ff' : ($is_merchant ? '#fef2f2' : '#f3f4f6');
                ?>
                <tr>
                    <td>
                        <strong><?= esc_html($u->display_name) ?></strong><br>
                        <small style="color:#9ca3af">@<?= esc_html($u->user_login) ?></small>
                    </td>
                    <td><?= esc_html($u->user_email) ?></td>
                    <td>
                        <span style="background:<?= $role_bg ?>;color:<?= $role_color ?>;padding:3px 10px;border-radius:999px;font-size:12px;font-weight:700">
                            <?= $role_label ?>
                        </span>
                    </td>
                    <td><?= $rest_name ? esc_html($rest_name) : '<span style="color:#9ca3af">—</span>' ?></td>
                    <td><?= date('d/m/Y', strtotime($u->user_registered)) ?></td>
                    <td>
                        <?php if (!$is_admin): ?>
                        <div style="display:flex;gap:4px;flex-wrap:wrap">
                            <?php if ($is_merchant): ?>
                            <form method="POST" style="display:inline" onsubmit="return confirm('Gỡ quyền merchant?')">
                                <?php wp_nonce_field('cica_users_action', 'cica_users_nonce'); ?>
                                <input type="hidden" name="user_action" value="revoke">
                                <input type="hidden" name="user_id" value="<?= (int)$u->ID ?>">
                                <button type="submit" class="button button-small">🔄 Gỡ quyền</button>
                            </form>
                            <?php endif; ?>
                            <?php if ($u->ID !== get_current_user_id()): ?>
                            <form method="POST" style="display:inline" onsubmit="return confirm('Xóa tài khoản này? Không thể hoàn tác!')">
                                <?php wp_nonce_field('cica_users_action', 'cica_users_nonce'); ?>
                                <input type="hidden" name="user_action" value="delete_user">
                                <input type="hidden" name="user_id" value="<?= (int)$u->ID ?>">
                                <button type="submit" class="button button-small" style="color:#dc2626;border-color:#dc2626">🗑️ Xóa</button>
                            </form>
                            <?php endif; ?>
                        </div>
                        <?php else: ?>
                        <span style="color:#9ca3af;font-size:12px">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Hướng dẫn -->
        <div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:8px;padding:16px;margin-top:20px">
            <h4 style="margin:0 0 8px;color:#0369a1">📋 Hướng dẫn tạo tài khoản Chủ nhà hàng</h4>
            <ol style="margin:0;color:#374151;font-size:13px;padding-left:20px">
                <li>User đăng ký tại <strong>/dang-nhap/?mode=register</strong>, chọn vai trò <strong>"Chủ nhà hàng"</strong></li>
                <li>Yêu cầu xuất hiện ở tab <strong>"Chờ duyệt"</strong></li>
                <li>Admin chọn nhà hàng → nhấn <strong>"Duyệt & Gán"</strong></li>
                <li>User đăng nhập lại → tự redirect về <strong>/quan-ly/</strong></li>
            </ol>
        </div>
        <?php endif; ?>
    </div>
    <?php
}

// ============================================================
// CÀI ĐẶT — Thông tin ngân hàng QR
// ============================================================
function cica_admin_settings(): void {
    $notice = '';
    if (isset($_POST['cica_settings_nonce']) && wp_verify_nonce($_POST['cica_settings_nonce'], 'cica_save_settings')) {
        update_option('cica_bank_id',      sanitize_text_field($_POST['bank_id']      ?? 'MB'));
        update_option('cica_bank_account', sanitize_text_field($_POST['bank_account'] ?? ''));
        update_option('cica_bank_name',    sanitize_text_field($_POST['bank_name']    ?? ''));
        $notice = '<div class="notice notice-success is-dismissible"><p>✅ Đã lưu cài đặt!</p></div>';
    }

    $bank_id      = get_option('cica_bank_id',      'MB');
    $bank_account = get_option('cica_bank_account', '');
    $bank_name    = get_option('cica_bank_name',    '');

    // Danh sách ngân hàng VN phổ biến
    $banks = [
        'MB'        => 'MB Bank (Quân đội)',
        'VCB'       => 'Vietcombank',
        'TCB'       => 'Techcombank',
        'ACB'       => 'ACB',
        'VPB'       => 'VPBank',
        'BIDV'      => 'BIDV',
        'VTB'       => 'Vietinbank',
        'STB'       => 'Sacombank',
        'TPB'       => 'TPBank',
        'MSB'       => 'MSB',
        'OCB'       => 'OCB',
        'SHB'       => 'SHB',
        'HDB'       => 'HDBank',
        'MOMO'      => 'MoMo',
        'ZALOPAY'   => 'ZaloPay',
    ];
    ?>
    <div class="wrap">
        <h1>⚙️ Cài đặt Cicafood</h1>
        <?= $notice ?>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-top:20px">
            <!-- Form cài đặt -->
            <div style="background:white;border-radius:12px;padding:24px;box-shadow:0 2px 8px rgba(0,0,0,0.06)">
                <h2 style="margin-top:0;font-size:1.1rem;display:flex;align-items:center;gap:8px">
                    <i class="fas fa-qrcode" style="color:#0ea5e9"></i> Thông tin thanh toán QR
                </h2>
                <p style="color:#6b7280;font-size:13px;margin-bottom:20px">
                    Thông tin này sẽ hiển thị trên trang thanh toán khi khách chọn "Chuyển khoản QR".
                    Sử dụng <a href="https://vietqr.io" target="_blank">VietQR</a> — miễn phí, không cần đăng ký.
                </p>
                <form method="POST">
                    <?php wp_nonce_field('cica_save_settings', 'cica_settings_nonce'); ?>
                    <table class="form-table">
                        <tr>
                            <th><label for="bank_id">Ngân hàng *</label></th>
                            <td>
                                <select name="bank_id" id="bank_id" class="regular-text" onchange="updatePreview()">
                                    <?php foreach ($banks as $code => $name): ?>
                                    <option value="<?= esc_attr($code) ?>" <?= $bank_id === $code ? 'selected' : '' ?>>
                                        <?= esc_html($name) ?> (<?= esc_html($code) ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="bank_account">Số tài khoản *</label></th>
                            <td>
                                <input type="text" name="bank_account" id="bank_account" class="regular-text"
                                       value="<?= esc_attr($bank_account) ?>"
                                       placeholder="VD: 0123456789"
                                       oninput="updatePreview()">
                                <p class="description">Số tài khoản ngân hàng hoặc số điện thoại (MoMo/ZaloPay)</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="bank_name">Tên chủ tài khoản *</label></th>
                            <td>
                                <input type="text" name="bank_name" id="bank_name" class="regular-text"
                                       value="<?= esc_attr($bank_name) ?>"
                                       placeholder="VD: NGUYEN VAN A"
                                       oninput="updatePreview()">
                                <p class="description">Viết IN HOA, không dấu</p>
                            </td>
                        </tr>
                    </table>
                    <button type="submit" class="button button-primary button-large" style="margin-top:10px">
                        💾 Lưu cài đặt
                    </button>
                </form>
            </div>

            <!-- Preview QR -->
            <div style="background:white;border-radius:12px;padding:24px;box-shadow:0 2px 8px rgba(0,0,0,0.06);text-align:center">
                <h2 style="margin-top:0;font-size:1.1rem">Preview QR Code</h2>
                <p style="color:#6b7280;font-size:13px;margin-bottom:8px">Nhập số tiền để xem QR mẫu:</p>
                <div style="display:flex;gap:8px;margin-bottom:12px;justify-content:center">
                    <input type="number" id="preview-amount" value="100000" min="1000" step="1000"
                           style="width:130px;padding:6px 10px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;text-align:center;outline:none"
                           oninput="updatePreview()" onfocus="this.style.borderColor='#ee2624'" onblur="this.style.borderColor='#e5e7eb'">
                    <span style="line-height:32px;font-size:13px;color:#6b7280">VND</span>
                </div>
                <img id="qr-preview-img"
                     src="https://img.vietqr.io/image/<?= esc_attr($bank_id) ?>-<?= esc_attr($bank_account ?: '0000000000') ?>-compact2.png?amount=100000&addInfo=CICAFOOD+TEST&accountName=<?= urlencode($bank_name ?: 'CICAFOOD') ?>"
                     alt="QR Preview"
                     style="width:200px;height:200px;border-radius:12px;border:2px solid #e5e7eb;margin:0 auto 16px;display:block">
                <div style="background:#f0f9ff;border-radius:10px;padding:12px;text-align:left;font-size:13px">
                    <div style="display:flex;justify-content:space-between;margin-bottom:6px">
                        <span style="color:#6b7280">Ngân hàng</span>
                        <strong id="prev-bank"><?= esc_html($bank_id) ?></strong>
                    </div>
                    <div style="display:flex;justify-content:space-between;margin-bottom:6px">
                        <span style="color:#6b7280">Số TK</span>
                        <strong id="prev-account" style="font-family:monospace"><?= esc_html($bank_account ?: '—') ?></strong>
                    </div>
                    <div style="display:flex;justify-content:space-between">
                        <span style="color:#6b7280">Chủ TK</span>
                        <strong id="prev-name"><?= esc_html($bank_name ?: '—') ?></strong>
                    </div>
                </div>
                <?php if (!$bank_account): ?>
                <p style="color:#f97316;font-size:12px;margin-top:12px;font-weight:600">
                    ⚠️ Chưa có số tài khoản — hãy điền và lưu để QR hoạt động
                </p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Hướng dẫn -->
        <div style="background:#f0fdf4;border:1px solid #86efac;border-radius:8px;padding:16px;margin-top:20px">
            <h4 style="margin:0 0 8px;color:#166534">📋 Hướng dẫn sử dụng QR thanh toán</h4>
            <ol style="margin:0;color:#374151;font-size:13px;padding-left:20px">
                <li>Điền thông tin ngân hàng của bạn ở trên và nhấn <strong>Lưu cài đặt</strong></li>
                <li>Khách hàng vào trang thanh toán, chọn <strong>"Chuyển khoản QR"</strong></li>
                <li>QR code tự động tạo với số tiền đúng bằng tổng đơn hàng</li>
                <li>Khách quét QR bằng app ngân hàng → chuyển khoản</li>
                <li>Merchant vào <strong>Quản lý quán → Tab Đơn hàng</strong> → xác nhận đơn sau khi nhận tiền</li>
            </ol>
        </div>
    </div>

    <script>
    function updatePreview() {
        const bankId  = document.getElementById('bank_id').value;
        const account = document.getElementById('bank_account').value || '0000000000';
        const name    = encodeURIComponent(document.getElementById('bank_name').value || 'CICAFOOD');
        const amount  = parseInt(document.getElementById('preview-amount')?.value) || 100000;
        document.getElementById('qr-preview-img').src =
            `https://img.vietqr.io/image/${bankId}-${account}-compact2.png?amount=${amount}&addInfo=CICAFOOD+TEST&accountName=${name}`;
        document.getElementById('prev-bank').textContent    = bankId;
        document.getElementById('prev-account').textContent = document.getElementById('bank_account').value || '—';
        document.getElementById('prev-name').textContent    = document.getElementById('bank_name').value || '—';
    }
    </script>
    <?php
}

/**
 * Lấy lệnh Python phù hợp trên hệ thống (hosting)
 */
function cica_get_python_command(): ?string {
    // Ưu tiên các bản cao để tránh lỗi SyntaxError trên Python 3.6 (do thư viện mới yêu cầu 3.7+)
    $commands = ['python3.11', 'python3.10', 'python3.9', 'python3.8', 'python3.7', 'python3', 'python'];
    foreach ($commands as $cmd) {
        $check = @shell_exec("$cmd --version 2>&1");
        // Kiểm tra xem output có thực sự bắt đầu bằng "Python " và theo sau là chữ số không
        if ($check && preg_match('/^Python\s+\d/i', trim($check))) {
            return $cmd;
        }
    }
    return null;
}

/**
 * Trang Analytics gọi Python script (Tiêu chí e5: Đa ngôn ngữ)
 */
function cica_admin_analytics() {
    global $table_prefix;
    $python_path = plugin_dir_path(__FILE__) . '../analytics.py';
    $output = '';
    $message = '';

    $python_cmd = cica_get_python_command();
    
    if (!$python_cmd) {
        $message = '<div class="notice notice-error"><p>❌ <strong>Lỗi:</strong> Không tìm thấy môi trường Python trên server này. Vui lòng liên hệ quản trị viên hosting.</p></div>';
    }

    // Xây dựng các tham số database
    $db_args = sprintf(
        ' --host %s --user %s --password %s --db %s --prefix %s',
        escapeshellarg(DB_HOST),
        escapeshellarg(DB_USER),
        escapeshellarg(DB_PASSWORD),
        escapeshellarg(DB_NAME),
        escapeshellarg($table_prefix)
    );

    // Xử lý nút bấm
    if ($python_cmd && isset($_POST['run_analytics'])) {
        // Chạy script Python lấy output text
        $command = "$python_cmd " . escapeshellarg($python_path) . $db_args . " 2>&1";
        $output = shell_exec($command);
    }

    if ($python_cmd && isset($_POST['export_excel_python'])) {
        // Chạy script Python xuất Excel
        $command = "$python_cmd " . escapeshellarg($python_path) . " --export report_admin" . $db_args . " 2>&1";
        $result = shell_exec($command);
        $message = '<div class="updated"><p>' . esc_html($result) . '</p></div>';
    }


    ?>
    <div class="wrap">
        <h1 style="display:flex;align-items:center;gap:10px;">
            <span style="font-size: 30px;">🐍</span> Python Data Analytics
        </h1>
        <p>Tích hợp đa ngôn ngữ: WordPress (PHP) gọi Python script để phân tích dữ liệu trực tiếp từ Database.</p>
        
        <?= $message ?>

        <div style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-top: 20px;">
            <form method="post" style="margin-bottom: 20px; display: flex; gap: 10px;">
                <button type="submit" name="run_analytics" class="button button-primary">
                    <i class="dashicons dashicons-chart-area" style="margin-top: 4px;"></i> Chạy Phân Tích Dữ Liệu
                </button>
                <button type="submit" name="export_excel_python" class="button">
                    <i class="dashicons dashicons-media-spreadsheet" style="margin-top: 4px;"></i> Xuất Excel bằng Python
                </button>
                <?php if (file_exists(plugin_dir_path(__FILE__) . '../report_admin.xlsx')): ?>
                    <a href="<?= esc_url(add_query_arg('v', filemtime(plugin_dir_path(__FILE__) . '../report_admin.xlsx'), plugins_url('../report_admin.xlsx', __FILE__))) ?>" class="button button-link" download>
                        Tải file Excel vừa tạo
                    </a>
                <?php endif; ?>
            </form>

            <?php if ($output): ?>
                <div style="background: #1e1e1e; color: #d4d4d4; padding: 20px; border-radius: 8px; font-family: 'Consolas', monospace; white-space: pre-wrap; overflow-x: auto; line-height: 1.5; border-left: 5px solid #ee2624;">
                    <?= esc_html($output) ?>
                </div>
            <?php else: ?>
                <div style="padding: 40px; text-align: center; color: #9ca3af; border: 2px dashed #e5e7eb; border-radius: 8px;">
                    Nhấn nút "Chạy Phân Tích Dữ Liệu" để bắt đầu xử lý bằng Python.
                </div>
            <?php endif; ?>
        </div>

        <div style="margin-top: 30px; background: #f0fdf4; border-left: 4px solid #16a34a; padding: 15px; border-radius: 4px;">
            <p style="margin: 0; font-weight: 600; color: #166534;">
                <i class="dashicons dashicons-info"></i> Giải thích cho Giảng viên:
            </p>
            <ul style="margin: 10px 0 0 20px; color: #166534; list-style: disc;">
                <li><strong>Đa ngôn ngữ:</strong> Hệ thống sử dụng PHP (WordPress) để điều khiển, nhưng toàn bộ logic tính toán phức tạp và xuất báo cáo Excel nâng cao được thực hiện bởi <strong>Python</strong> thông qua <code>shell_exec</code>.</li>
                <li><strong>Thư viện mã mở:</strong> Sử dụng <code>mysql-connector-python</code> để truy vấn DB và <code>openpyxl</code> để xử lý file Excel.</li>
                <li><strong>Tính thông minh:</strong> Script Python tự động tính toán tỷ lệ, xếp hạng và vẽ biểu đồ ASCII trong console.</li>
            </ul>
        </div>
    </div>
    <?php
}
