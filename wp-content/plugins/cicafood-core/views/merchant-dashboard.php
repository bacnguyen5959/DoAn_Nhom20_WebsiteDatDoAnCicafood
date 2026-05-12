<?php
defined('ABSPATH') || exit;
if (!session_id()) session_start();

$user_id  = get_current_user_id();
$site_url = get_site_url();
$ajax_url = admin_url('admin-ajax.php');
$nonce    = wp_create_nonce('cicafood_nonce');

global $wpdb;
$rt = $wpdb->prefix . 'cica_restaurants';

$is_admin = current_user_can('manage_options');

// Kiểm tra quyền merchant
if (!cica_is_merchant()) {
    echo '<div style="text-align:center;padding:4rem 1rem">
        <i class="fas fa-lock" style="font-size:3rem;color:#d1d5db;margin-bottom:1rem;display:block"></i>
        <h2 style="color:#374151">Bạn không có quyền truy cập</h2>
        <p style="color:#9ca3af">Trang này chỉ dành cho Chủ nhà hàng.</p>
        <a href="' . esc_url(get_site_url()) . '" style="display:inline-block;margin-top:1rem;background:#ee2624;color:white;padding:0.75rem 1.5rem;border-radius:10px;font-weight:700;text-decoration:none">Về trang chủ</a>
    </div>';
    return;
}

// Admin: chọn nhà hàng qua ?rid=X
if ($is_admin) {
    $rid_param = (int)($_GET['rid'] ?? 0);
    if ($rid_param > 0) {
        $restaurant = $wpdb->get_row($wpdb->prepare(
            "SELECT r.*, c.name AS category_name FROM $rt r LEFT JOIN {$wpdb->prefix}cica_categories c ON r.category_id=c.id WHERE r.id=%d LIMIT 1",
            $rid_param
        ), ARRAY_A);
    } else {
        $restaurant = $wpdb->get_row(
            "SELECT r.*, c.name AS category_name FROM $rt r LEFT JOIN {$wpdb->prefix}cica_categories c ON r.category_id=c.id ORDER BY r.id ASC LIMIT 1",
            ARRAY_A
        );
    }
} else {
    // Merchant thường: lấy nhà hàng của mình
    $restaurant = $wpdb->get_row($wpdb->prepare(
        "SELECT r.*, c.name AS category_name FROM $rt r LEFT JOIN {$wpdb->prefix}cica_categories c ON r.category_id=c.id WHERE r.wp_user_id=%d LIMIT 1",
        $user_id
    ), ARRAY_A);
}

if (!$restaurant) {
    if ($is_admin) {
        $all_restaurants = $wpdb->get_results("SELECT id, name FROM $rt ORDER BY name ASC", ARRAY_A);
        echo '<div style="max-width:600px;margin:3rem auto;padding:1rem">';
        echo '<h2 style="font-size:1.2rem;font-weight:800;color:#111827;margin-bottom:1rem"><i class="fas fa-store" style="color:#ee2624"></i> Chọn nhà hàng để quản lý</h2>';
        echo '<div style="display:flex;flex-direction:column;gap:0.5rem">';
        foreach ($all_restaurants as $r) {
            echo '<a href="?rid=' . (int)$r['id'] . '" style="display:block;padding:0.875rem 1rem;background:white;border:1.5px solid #e5e7eb;border-radius:12px;font-weight:600;color:#374151;text-decoration:none" onmouseover="this.style.borderColor=\'#ee2624\'" onmouseout="this.style.borderColor=\'#e5e7eb\'">' . esc_html($r['name']) . '</a>';
        }
        echo '</div></div>';
    } else {
        echo '<div style="text-align:center;padding:4rem 1rem">
            <i class="fas fa-store-slash" style="font-size:3rem;color:#d1d5db;margin-bottom:1rem;display:block"></i>
            <h2 style="color:#374151">Nhà hàng chưa được liên kết</h2>
            <p style="color:#9ca3af">Liên hệ admin để được gán nhà hàng.</p>
        </div>';
    }
    return;
}

$rid = (int)$restaurant['id'];

// Stats
$ot = $wpdb->prefix . 'cica_orders';
$mi = $wpdb->prefix . 'cica_menu_items';
$total_orders    = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $ot WHERE restaurant_id=%d", $rid));
$pending_orders  = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $ot WHERE restaurant_id=%d AND status IN('pending','confirmed','preparing','delivering')", $rid));
$revenue         = (int)$wpdb->get_var($wpdb->prepare("SELECT SUM(total_amount) FROM $ot WHERE restaurant_id=%d AND status='completed'", $rid));
$menu_count      = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $mi WHERE restaurant_id=%d AND is_available=1", $rid));

// Menu data
$categories = Cicafood_Menu::get_categories($rid);
$all_items   = $wpdb->get_results($wpdb->prepare(
    "SELECT i.*, c.name AS cat_name FROM $mi i LEFT JOIN {$wpdb->prefix}cica_menu_categories c ON i.menu_category_id=c.id WHERE i.restaurant_id=%d ORDER BY i.menu_category_id ASC, i.sort_order ASC",
    $rid
), ARRAY_A) ?: [];

// Recent orders
$recent_orders = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM $ot WHERE restaurant_id=%d ORDER BY created_at DESC LIMIT 20",
    $rid
), ARRAY_A) ?: [];

$tab = sanitize_text_field($_GET['tab'] ?? 'orders');

// Chart data: Doanh thu 7 ngay gan nhat
$chart_data = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $day_revenue = (int)$wpdb->get_var($wpdb->prepare(
        "SELECT COALESCE(SUM(total_amount), 0) FROM $ot WHERE restaurant_id=%d AND status='completed' AND DATE(created_at)=%s",
        $rid, $date
    ));
    $day_orders = (int)$wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $ot WHERE restaurant_id=%d AND DATE(created_at)=%s",
        $rid, $date
    ));
    $chart_data[] = [
        'label' => date('d/m', strtotime($date)),
        'revenue' => $day_revenue,
        'orders' => $day_orders,
    ];
}

?>

<div style="max-width:1200px;margin:0 auto;padding:1.5rem 1rem">

<!-- ===== HEADER ===== -->
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem">
    <div style="display:flex;align-items:center;gap:1rem">
        <img src="<?= esc_url($restaurant['image'] ?: 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=80&q=80') ?>"
             alt="" style="width:56px;height:56px;border-radius:14px;object-fit:cover;border:2px solid #e5e7eb">
        <div>
            <h1 style="font-size:1.3rem;font-weight:900;color:#111827;margin:0"><?= esc_html($restaurant['name']) ?></h1>
            <p style="font-size:0.8rem;color:#9ca3af;margin:0"><?= esc_html($restaurant['category_name'] ?? '') ?> · <?= esc_html($restaurant['address']) ?></p>
        </div>
    </div>
    <div style="display:flex;align-items:center;gap:0.75rem">
        <span id="open-badge" style="padding:0.4rem 1rem;border-radius:999px;font-size:0.8rem;font-weight:700;background:<?= $restaurant['is_open'] ? '#dcfce7' : '#fee2e2' ?>;color:<?= $restaurant['is_open'] ? '#16a34a' : '#dc2626' ?>">
            <?= $restaurant['is_open'] ? '● Đang mở cửa' : '○ Đóng cửa' ?>
        </span>
        <button onclick="toggleOpen()" id="toggle-open-btn"
                style="background:<?= $restaurant['is_open'] ? '#fee2e2' : '#dcfce7' ?>;color:<?= $restaurant['is_open'] ? '#dc2626' : '#16a34a' ?>;border:none;border-radius:10px;padding:0.5rem 1rem;font-weight:700;font-size:0.8rem;cursor:pointer">
            <?= $restaurant['is_open'] ? '<i class="fas fa-door-closed"></i> Đóng cửa' : '<i class="fas fa-door-open"></i> Mở cửa' ?>
        </button>
    </div>
</div>

<?php if ($is_admin): ?>
<?php
$all_rests = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}cica_restaurants ORDER BY name ASC", ARRAY_A);
?>
<div style="background:#fef9c3;border:1px solid #fde68a;border-radius:12px;padding:0.75rem 1rem;margin-bottom:1.25rem;display:flex;align-items:center;gap:0.75rem;flex-wrap:wrap">
    <i class="fas fa-shield-halved" style="color:#92400e"></i>
    <span style="font-size:0.8rem;font-weight:700;color:#92400e">Admin — Đang xem:</span>
    <select onchange="window.location.href='?rid='+this.value+'&tab=<?= esc_js($tab) ?>'"
            style="padding:4px 10px;border:1.5px solid #fde68a;border-radius:8px;font-size:0.8rem;font-weight:600;outline:none;cursor:pointer;background:white">
        <?php foreach ($all_rests as $r): ?>
        <option value="<?= (int)$r['id'] ?>" <?= (int)$r['id'] === $rid ? 'selected' : '' ?>><?= esc_html($r['name']) ?></option>
        <?php endforeach; ?>
    </select>
</div>
<?php endif; ?>

<!-- ===== STATS ===== -->
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:1.5rem">
    <?php foreach ([
        ['fas fa-receipt',     'Tổng đơn',       $total_orders,                                '#ee2624','#fef2f2'],
        ['fas fa-clock',       'Đang xử lý',      $pending_orders,                              '#f97316','#fff7ed'],
        ['fas fa-coins',       'Doanh thu',        number_format($revenue,0,',','.').'đ',        '#16a34a','#f0fdf4'],
        ['fas fa-utensils',    'Món đang bán',     $menu_count,                                  '#8b5cf6','#f5f3ff'],
    ] as [$icon,$label,$val,$color,$bg]): ?>
    <div style="background:white;border-radius:14px;border:1px solid #e5e7eb;padding:1rem;display:flex;align-items:center;gap:0.75rem">
        <div style="width:44px;height:44px;border-radius:12px;background:<?= $bg ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <i class="<?= $icon ?>" style="color:<?= $color ?>"></i>
        </div>
        <div>
            <p style="font-size:1.25rem;font-weight:900;color:#111827;margin:0"><?= $val ?></p>
            <p style="font-size:0.72rem;color:#9ca3af;margin:0"><?= $label ?></p>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- ===== CHART: DOANH THU 7 NGÀY ===== -->
<div style="background:white;border-radius:16px;border:1px solid #e5e7eb;padding:1.25rem;margin-bottom:1.5rem">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem">
        <h2 style="font-size:1rem;font-weight:800;color:#111827;margin:0"><i class="fas fa-chart-bar" style="color:#8b5cf6"></i> Thống kê 7 ngày gần nhất</h2>
        <a href="<?= esc_url(admin_url('admin-ajax.php') . '?action=cica_export_orders_excel&nonce=' . $nonce . '&restaurant_id=' . $rid) ?>" 
           style="display:inline-flex;align-items:center;gap:0.4rem;background:#16a34a;color:white;padding:0.5rem 1rem;border-radius:10px;font-size:0.8rem;font-weight:700;text-decoration:none;border:none;cursor:pointer"
           title="Xuất báo cáo đơn hàng ra file Excel">
            <i class="fas fa-file-excel"></i> Xuất Excel
        </a>
    </div>
    <div style="display:grid;grid-template-columns:2fr 1fr;gap:1rem;align-items:center">
        <div style="position:relative;height:220px">
            <canvas id="revenueChart"></canvas>
        </div>
        <div style="display:flex;flex-direction:column;gap:0.75rem">
            <?php 
            $total_7d_rev = array_sum(array_column($chart_data, 'revenue'));
            $total_7d_orders = array_sum(array_column($chart_data, 'orders'));
            $avg_order_val = $total_7d_orders > 0 ? round($total_7d_rev / $total_7d_orders) : 0;
            ?>
            <div style="background:#f0fdf4;border-radius:12px;padding:0.875rem;text-align:center">
                <p style="font-size:0.7rem;color:#16a34a;font-weight:700;margin:0 0 2px">Doanh thu 7 ngày</p>
                <p style="font-size:1.1rem;font-weight:900;color:#111827;margin:0"><?= number_format($total_7d_rev, 0, ',', '.') ?>đ</p>
            </div>
            <div style="background:#eff6ff;border-radius:12px;padding:0.875rem;text-align:center">
                <p style="font-size:0.7rem;color:#2563eb;font-weight:700;margin:0 0 2px">Tổng đơn 7 ngày</p>
                <p style="font-size:1.1rem;font-weight:900;color:#111827;margin:0"><?= $total_7d_orders ?></p>
            </div>
            <div style="background:#fef3c7;border-radius:12px;padding:0.875rem;text-align:center">
                <p style="font-size:0.7rem;color:#d97706;font-weight:700;margin:0 0 2px">Giá trị TB/đơn</p>
                <p style="font-size:1.1rem;font-weight:900;color:#111827;margin:0"><?= number_format($avg_order_val, 0, ',', '.') ?>đ</p>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
(function() {
    const labels = <?= json_encode(array_column($chart_data, 'label')) ?>;
    const revenues = <?= json_encode(array_column($chart_data, 'revenue')) ?>;
    const orders = <?= json_encode(array_column($chart_data, 'orders')) ?>;
    const ctx = document.getElementById('revenueChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Doanh thu (VND)',
                data: revenues,
                backgroundColor: 'rgba(139, 92, 246, 0.7)',
                borderColor: '#8b5cf6',
                borderWidth: 2,
                borderRadius: 8,
                yAxisID: 'y',
            }, {
                label: 'Số đơn',
                data: orders,
                type: 'line',
                borderColor: '#ee2624',
                backgroundColor: 'rgba(238, 38, 36, 0.1)',
                borderWidth: 3,
                pointRadius: 5,
                pointBackgroundColor: '#ee2624',
                fill: true,
                tension: 0.3,
                yAxisID: 'y1',
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { position: 'bottom', labels: { usePointStyle: true, padding: 15, font: { size: 11, weight: '600' } } },
                tooltip: {
                    backgroundColor: '#1f2937',
                    titleFont: { size: 12, weight: '700' },
                    bodyFont: { size: 11 },
                    padding: 10,
                    cornerRadius: 8,
                    callbacks: {
                        label: function(ctx) {
                            if (ctx.datasetIndex === 0) return ' ' + new Intl.NumberFormat('vi-VN').format(ctx.raw) + 'đ';
                            return ' ' + ctx.raw + ' đơn';
                        }
                    }
                }
            },
            scales: {
                x: { grid: { display: false }, ticks: { font: { size: 11, weight: '600' } } },
                y: {
                    position: 'left',
                    grid: { color: '#f3f4f6' },
                    ticks: { font: { size: 10 }, callback: v => (v/1000) + 'K' }
                },
                y1: {
                    position: 'right',
                    grid: { drawOnChartArea: false },
                    ticks: { font: { size: 10 }, stepSize: 1 },
                    min: 0
                }
            }
        }
    });
})();
</script>

<!-- ===== TABS ===== -->
<?php
// Lay vouchers cua nha hang nay
$vt = $wpdb->prefix . 'cica_vouchers';
$uv = $wpdb->prefix . 'cica_user_vouchers';
$vouchers = $wpdb->get_results($wpdb->prepare(
    "SELECT v.*, (SELECT COUNT(*) FROM $uv uv WHERE uv.voucher_id = v.id AND uv.is_used = 1) AS used_count
     FROM $vt v WHERE v.restaurant_id = %d ORDER BY v.id DESC",
    $rid
), ARRAY_A) ?: [];
?>
<div style="display:flex;gap:0.25rem;background:#f3f4f6;border-radius:12px;padding:4px;margin-bottom:1.5rem;width:fit-content">
    <?php foreach ([
        ['orders',  'fas fa-receipt',  'Đơn hàng',     $pending_orders > 0 ? $pending_orders : null],
        ['menu',    'fas fa-utensils', 'Quản lý Menu',  null],
        ['voucher', 'fas fa-ticket',   'Voucher',        null],
        ['settings','fas fa-cog',      'Cài đặt',        null],
    ] as [$t,$icon,$label,$badge]): ?>
    <button onclick="switchTab('<?= $t ?>')" id="tab-btn-<?= $t ?>"
            style="display:flex;align-items:center;gap:0.4rem;padding:0.5rem 1.1rem;border-radius:9px;border:none;font-weight:700;font-size:0.875rem;cursor:pointer;transition:all 0.15s;position:relative;<?= $tab===$t ? 'background:white;color:#111827;box-shadow:0 1px 4px rgba(0,0,0,0.1)' : 'background:transparent;color:#6b7280' ?>">
        <i class="<?= $icon ?>"></i> <?= $label ?>
        <?php if ($badge): ?>
        <span style="position:absolute;top:-4px;right:-4px;background:#ee2624;color:white;font-size:0.6rem;font-weight:900;width:18px;height:18px;border-radius:50%;display:flex;align-items:center;justify-content:center"><?= $badge ?></span>
        <?php endif; ?>
    </button>
    <?php endforeach; ?>
</div>

<!-- ===== TAB: ORDERS ===== -->
<div id="tab-orders" style="display:<?= $tab==='orders' ? 'block' : 'none' ?>">
    <div style="background:white;border-radius:16px;border:1px solid #e5e7eb;overflow:hidden">
        <div style="padding:1rem 1.25rem;border-bottom:1px solid #f3f4f6;display:flex;align-items:center;justify-content:space-between">
            <h2 style="font-size:1rem;font-weight:800;color:#111827;margin:0">Đơn hàng gần đây</h2>
            <div style="display:flex;gap:0.4rem" id="order-filter-btns">
                <?php foreach ([
                    ['all','Tất cả','#6b7280','#f3f4f6'],
                    ['pending','Chờ xác nhận','#92400e','#fef9c3'],
                    ['confirmed','Đã xác nhận','#1e40af','#dbeafe'],
                    ['preparing','Đang làm','#9a3412','#ffedd5'],
                    ['delivering','Đang giao','#6b21a8','#f3e8ff'],
                    ['completed','Hoàn thành','#166534','#dcfce7'],
                ] as [$s,$l,$tc,$bg]): ?>
                <button onclick="filterOrders('<?= $s ?>', this)"
                        style="padding:3px 10px;border-radius:999px;border:none;font-size:0.72rem;font-weight:700;cursor:pointer;background:<?= $s==='all' ? '#ee2624' : $bg ?>;color:<?= $s==='all' ? 'white' : $tc ?>"
                        class="order-filter-btn" data-filter="<?= $s ?>">
                    <?= $l ?>
                </button>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if (empty($recent_orders)): ?>
        <div style="padding:3rem;text-align:center;color:#9ca3af">
            <i class="fas fa-inbox" style="font-size:2.5rem;margin-bottom:0.75rem;display:block"></i>
            <p style="font-weight:600;margin:0">Chưa có đơn hàng nào</p>
        </div>
        <?php else: ?>
        <div style="overflow-x:auto">
        <table style="width:100%;border-collapse:collapse;font-size:0.875rem">
            <thead>
                <tr style="background:#f9fafb">
                    <th style="padding:0.75rem 1rem;text-align:left;font-weight:700;color:#6b7280;font-size:0.75rem;text-transform:uppercase">Mã đơn</th>
                    <th style="padding:0.75rem 1rem;text-align:left;font-weight:700;color:#6b7280;font-size:0.75rem;text-transform:uppercase">Khách hàng</th>
                    <th style="padding:0.75rem 1rem;text-align:left;font-weight:700;color:#6b7280;font-size:0.75rem;text-transform:uppercase">Tổng tiền</th>
                    <th style="padding:0.75rem 1rem;text-align:left;font-weight:700;color:#6b7280;font-size:0.75rem;text-transform:uppercase">Trạng thái</th>
                    <th style="padding:0.75rem 1rem;text-align:left;font-weight:700;color:#6b7280;font-size:0.75rem;text-transform:uppercase">Thời gian</th>
                    <th style="padding:0.75rem 1rem;text-align:left;font-weight:700;color:#6b7280;font-size:0.75rem;text-transform:uppercase">Cập nhật</th>
                </tr>
            </thead>
            <tbody id="orders-tbody">
            <?php foreach ($recent_orders as $o):
                $sl = Cicafood_Order::status_label($o['status']);
                $status_styles = [
                    'pending'    => ['#fef9c3','#92400e'],
                    'confirmed'  => ['#dbeafe','#1e40af'],
                    'preparing'  => ['#ffedd5','#9a3412'],
                    'delivering' => ['#f3e8ff','#6b21a8'],
                    'completed'  => ['#dcfce7','#166534'],
                    'cancelled'  => ['#fee2e2','#991b1b'],
                ];
                [$sbg,$stc] = $status_styles[$o['status']] ?? ['#f3f4f6','#374151'];
            ?>
            <tr style="border-top:1px solid #f3f4f6" class="order-row" data-status="<?= esc_attr($o['status']) ?>">
                <td style="padding:0.875rem 1rem">
                    <span style="font-weight:700;color:#111827"><?= esc_html($o['order_code']) ?></span>
                </td>
                <td style="padding:0.875rem 1rem">
                    <p style="font-weight:600;color:#374151;margin:0"><?= esc_html($o['recipient_name']) ?></p>
                    <p style="font-size:0.75rem;color:#9ca3af;margin:0"><?= esc_html($o['recipient_phone']) ?></p>
                </td>
                <td style="padding:0.875rem 1rem">
                    <span style="font-weight:700;color:#ee2624"><?= cica_format_price((int)$o['total_amount']) ?></span>
                </td>
                <td style="padding:0.875rem 1rem">
                    <span style="background:<?= $sbg ?>;color:<?= $stc ?>;padding:3px 10px;border-radius:999px;font-size:0.72rem;font-weight:700">
                        <?= esc_html($sl['label']) ?>
                    </span>
                </td>
                <td style="padding:0.875rem 1rem;color:#9ca3af;font-size:0.8rem">
                    <?= date('d/m H:i', strtotime($o['created_at'])) ?>
                </td>
                <td style="padding:0.875rem 1rem">
                    <?php if (!in_array($o['status'], ['completed','cancelled'])): ?>
                    <div style="display:flex;gap:0.4rem;align-items:center">
                        <select onchange="updateOrderStatus(<?= (int)$o['id'] ?>, this.value, this)"
                                style="padding:4px 8px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:0.78rem;font-weight:600;outline:none;cursor:pointer">
                            <?php foreach (['confirmed'=>'Xác nhận','preparing'=>'Đang làm','delivering'=>'Đang giao','completed'=>'Hoàn thành','cancelled'=>'Huỷ'] as $sv=>$sl2): ?>
                            <option value="<?= $sv ?>" <?= $o['status']===$sv ? 'selected' : '' ?>><?= $sl2 ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php else: ?>
                    <span style="font-size:0.75rem;color:#9ca3af">—</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ===== TAB: MENU ===== -->
<div id="tab-menu" style="display:<?= $tab==='menu' ? 'block' : 'none' ?>">

    <!-- Toolbar -->
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;flex-wrap:wrap;gap:0.75rem">
        <div style="display:flex;gap:0.5rem;flex-wrap:wrap">
            <button onclick="openCatModal()"
                    style="display:flex;align-items:center;gap:0.4rem;background:#f3f4f6;color:#374151;border:none;border-radius:10px;padding:0.55rem 1rem;font-weight:700;font-size:0.8rem;cursor:pointer">
                <i class="fas fa-folder-plus"></i> Thêm danh mục
            </button>
            <button onclick="openItemModal()"
                    style="display:flex;align-items:center;gap:0.4rem;background:#ee2624;color:white;border:none;border-radius:10px;padding:0.55rem 1rem;font-weight:700;font-size:0.8rem;cursor:pointer">
                <i class="fas fa-plus"></i> Thêm món mới
            </button>
        </div>
        <input type="text" id="menu-search" placeholder="Tìm món ăn..."
               oninput="searchMenu(this.value)"
               style="padding:0.55rem 1rem;border:1.5px solid #e5e7eb;border-radius:10px;font-size:0.875rem;outline:none;width:220px"
               onfocus="this.style.borderColor='#ee2624'" onblur="this.style.borderColor='#e5e7eb'">
    </div>

    <!-- Menu grouped by category -->
    <?php if (empty($all_items) && empty($categories)): ?>
    <div style="background:white;border-radius:16px;border:1px solid #e5e7eb;padding:3rem;text-align:center;color:#9ca3af">
        <i class="fas fa-utensils" style="font-size:3rem;margin-bottom:1rem;display:block"></i>
        <p style="font-weight:600;margin:0 0 0.5rem">Chưa có món ăn nào</p>
        <p style="font-size:0.875rem;margin:0 0 1.25rem">Bắt đầu thêm danh mục và món ăn cho nhà hàng của bạn</p>
        <button onclick="openItemModal()"
                style="background:#ee2624;color:white;border:none;border-radius:10px;padding:0.65rem 1.5rem;font-weight:700;cursor:pointer">
            <i class="fas fa-plus"></i> Thêm món đầu tiên
        </button>
    </div>
    <?php else: ?>

    <?php
    // Group items by category
    $grouped = [];
    foreach ($all_items as $item) {
        $cid = $item['menu_category_id'] ?? 0;
        $cname = $item['cat_name'] ?? 'Chưa phân loại';
        if (!isset($grouped[$cid])) $grouped[$cid] = ['name' => $cname, 'items' => []];
        $grouped[$cid]['items'][] = $item;
    }
    ?>

    <div id="menu-container" style="display:flex;flex-direction:column;gap:1.25rem">
        <?php foreach ($grouped as $cid => $group): ?>
        <div class="menu-category-block" data-cat-id="<?= (int)$cid ?>">
            <!-- Category header -->
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.75rem">
                <h3 style="font-size:0.95rem;font-weight:800;color:#111827;margin:0;display:flex;align-items:center;gap:0.5rem">
                    <i class="fas fa-layer-group" style="color:#ee2624;font-size:0.8rem"></i>
                    <?= esc_html($group['name']) ?>
                    <span style="font-size:0.72rem;font-weight:600;color:#9ca3af">(<?= count($group['items']) ?> món)</span>
                </h3>
                <?php if ($cid > 0): ?>
                <div style="display:flex;gap:0.4rem">
                    <button onclick="openCatModal(<?= (int)$cid ?>, '<?= esc_js($group['name']) ?>')"
                            style="background:#f3f4f6;color:#6b7280;border:none;border-radius:8px;padding:4px 10px;font-size:0.75rem;font-weight:600;cursor:pointer">
                        <i class="fas fa-edit"></i> Sửa
                    </button>
                    <button onclick="deleteCat(<?= (int)$cid ?>)"
                            style="background:#fef2f2;color:#dc2626;border:none;border-radius:8px;padding:4px 10px;font-size:0.75rem;font-weight:600;cursor:pointer">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
                <?php endif; ?>
            </div>

            <!-- Items grid -->
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:0.75rem">
                <?php foreach ($group['items'] as $item): ?>
                <div class="menu-item-card" data-name="<?= esc_attr(strtolower($item['name'])) ?>"
                     style="background:white;border-radius:14px;border:1.5px solid <?= $item['is_available'] ? '#e5e7eb' : '#f3f4f6' ?>;overflow:hidden;display:flex;<?= !$item['is_available'] ? 'opacity:0.65' : '' ?>">
                    <!-- Image -->
                    <div style="width:90px;height:90px;flex-shrink:0;overflow:hidden;background:#f9fafb">
                        <img src="<?= esc_url($item['image'] ?: 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=200&q=80') ?>"
                             alt="<?= esc_attr($item['name']) ?>"
                             style="width:100%;height:100%;object-fit:cover">
                    </div>
                    <!-- Info -->
                    <div style="flex:1;padding:0.65rem 0.75rem;min-width:0">
                        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:0.4rem">
                            <p style="font-weight:700;font-size:0.875rem;color:#111827;margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= esc_html($item['name']) ?></p>
                            <?php if ($item['is_best_seller']): ?>
                            <span style="font-size:0.6rem;font-weight:700;background:#fef3c7;color:#92400e;padding:1px 5px;border-radius:4px;flex-shrink:0">Hot</span>
                            <?php endif; ?>
                        </div>
                        <p style="font-size:0.75rem;color:#9ca3af;margin:0.15rem 0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= esc_html($item['description'] ?: '—') ?></p>
                        <p style="font-weight:800;font-size:0.9rem;color:#ee2624;margin:0.2rem 0 0"><?= cica_format_price((int)$item['price']) ?></p>
                    </div>
                    <!-- Actions -->
                    <div style="display:flex;flex-direction:column;justify-content:center;gap:0.3rem;padding:0.5rem;border-left:1px solid #f3f4f6">
                        <button onclick="openItemModal(<?= htmlspecialchars(json_encode($item), ENT_QUOTES) ?>)"
                                title="Sửa" style="width:30px;height:30px;background:#f3f4f6;border:none;border-radius:8px;cursor:pointer;display:flex;align-items:center;justify-content:center">
                            <i class="fas fa-edit" style="font-size:0.75rem;color:#374151"></i>
                        </button>
                        <button onclick="toggleAvail(<?= (int)$item['id'] ?>, this)"
                                title="<?= $item['is_available'] ? 'Ẩn món' : 'Hiện món' ?>"
                                style="width:30px;height:30px;background:<?= $item['is_available'] ? '#f0fdf4' : '#fef2f2' ?>;border:none;border-radius:8px;cursor:pointer;display:flex;align-items:center;justify-content:center">
                            <i class="fas <?= $item['is_available'] ? 'fa-eye' : 'fa-eye-slash' ?>" style="font-size:0.75rem;color:<?= $item['is_available'] ? '#16a34a' : '#9ca3af' ?>"></i>
                        </button>
                        <button onclick="deleteItem(<?= (int)$item['id'] ?>, this)"
                                title="Xóa" style="width:30px;height:30px;background:#fef2f2;border:none;border-radius:8px;cursor:pointer;display:flex;align-items:center;justify-content:center">
                            <i class="fas fa-trash" style="font-size:0.75rem;color:#dc2626"></i>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

</div><!-- end max-w -->

<!-- ===== TAB: VOUCHER ===== -->
<div id="tab-voucher" style="max-width:1200px;margin:0 auto;padding:0 1rem 1.5rem;display:<?= $tab==='voucher' ? 'block' : 'none' ?>">

    <!-- Toolbar -->
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem">
        <h2 style="font-size:1rem;font-weight:800;color:#111827;margin:0">
            <i class="fas fa-ticket" style="color:#f97316"></i> Voucher của quán
        </h2>
        <button onclick="openMerchantVoucherModal()"
                style="display:flex;align-items:center;gap:0.5rem;background:#ee2624;color:white;border:none;border-radius:10px;padding:0.6rem 1.25rem;font-weight:700;font-size:0.875rem;cursor:pointer">
            <i class="fas fa-plus"></i> Tạo voucher mới
        </button>
    </div>

    <!-- Danh sách voucher -->
    <?php if (empty($vouchers)): ?>
    <div style="background:white;border-radius:16px;border:1px solid #e5e7eb;padding:3rem;text-align:center;color:#9ca3af">
        <i class="fas fa-ticket" style="font-size:3rem;margin-bottom:1rem;display:block"></i>
        <p style="font-weight:600;margin:0 0 0.5rem">Chưa có voucher nào</p>
        <p style="font-size:0.875rem;margin:0 0 1.25rem">Tạo voucher để thu hút thêm khách hàng!</p>
        <button onclick="openMerchantVoucherModal()"
                style="background:#ee2624;color:white;border:none;border-radius:10px;padding:0.65rem 1.5rem;font-weight:700;cursor:pointer">
            <i class="fas fa-plus"></i> Tạo ngay
        </button>
    </div>
    <?php else: ?>
    <div style="display:flex;flex-direction:column;gap:0.75rem" id="merchant-voucher-list">
        <?php foreach ($vouchers as $v):
            $is_active = $v['is_active'] && (!$v['end_date'] || strtotime($v['end_date']) >= time());
            $usage_pct = $v['usage_limit'] > 0 ? min(100, round($v['used_count'] / $v['usage_limit'] * 100)) : 0;
        ?>
        <div style="background:white;border-radius:14px;border:1.5px solid <?= $is_active ? '#e5e7eb' : '#f3f4f6' ?>;padding:1.25rem;display:flex;align-items:center;gap:1rem;flex-wrap:wrap;<?= !$is_active ? 'opacity:0.65' : '' ?>"
             id="mv-row-<?= $v['id'] ?>">
            <!-- Code -->
            <div style="flex-shrink:0;min-width:130px">
                <span style="font-family:monospace;font-weight:900;font-size:1rem;color:#ee2624;background:#fef2f2;padding:4px 12px;border-radius:8px;border:1px dashed #fca5a5;display:block;text-align:center;margin-bottom:0.4rem">
                    <?= esc_html($v['code']) ?>
                </span>
                <span style="display:block;text-align:center;font-size:0.7rem;font-weight:700;padding:2px 8px;border-radius:999px;background:<?= $is_active ? '#dcfce7' : '#f3f4f6' ?>;color:<?= $is_active ? '#16a34a' : '#9ca3af' ?>">
                    <?= $is_active ? '● Đang chạy' : '○ Hết hạn' ?>
                </span>
            </div>
            <!-- Info -->
            <div style="flex:1;min-width:180px">
                <p style="font-weight:700;font-size:0.95rem;color:#111827;margin:0 0 0.25rem"><?= esc_html($v['name']) ?></p>
                <div style="display:flex;gap:1rem;font-size:0.75rem;color:#6b7280;flex-wrap:wrap">
                    <?php if ($v['type'] === 'freeship'): ?>
                    <span><i class="fas fa-motorcycle" style="color:#0ea5e9"></i> Freeship</span>
                    <?php elseif ($v['type'] === 'percent'): ?>
                    <span><i class="fas fa-percent" style="color:#f97316"></i> Giảm <?= $v['value'] ?>%</span>
                    <?php else: ?>
                    <span><i class="fas fa-tag" style="color:#8b5cf6"></i> Giảm <?= cica_format_price((int)$v['value']) ?></span>
                    <?php endif; ?>
                    <span><i class="fas fa-receipt"></i> Đơn từ <?= cica_format_price((int)$v['min_order']) ?></span>
                    <?php if ($v['end_date']): ?>
                    <span><i class="fas fa-calendar"></i> HSD: <?= date('d/m/Y', strtotime($v['end_date'])) ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <!-- Usage -->
            <div style="min-width:110px;flex-shrink:0">
                <div style="display:flex;justify-content:space-between;font-size:0.75rem;color:#6b7280;margin-bottom:0.3rem">
                    <span>Đã dùng</span>
                    <span style="font-weight:700;color:#374151"><?= $v['used_count'] ?>/<?= $v['usage_limit'] ?></span>
                </div>
                <div style="height:6px;background:#f3f4f6;border-radius:999px;overflow:hidden">
                    <div style="height:100%;width:<?= $usage_pct ?>%;background:<?= $usage_pct >= 80 ? '#ef4444' : '#ee2624' ?>;border-radius:999px"></div>
                </div>
            </div>
            <!-- Actions -->
            <div style="display:flex;gap:0.5rem;flex-shrink:0">
                <button onclick='openMerchantVoucherModal(<?= json_encode($v) ?>)'
                        style="background:#f3f4f6;color:#374151;border:none;border-radius:8px;padding:0.5rem 0.875rem;font-weight:600;font-size:0.8rem;cursor:pointer">
                    <i class="fas fa-edit"></i> Sửa
                </button>
                <button onclick="deleteMerchantVoucher(<?= (int)$v['id'] ?>)"
                        style="background:#fef2f2;color:#dc2626;border:none;border-radius:8px;padding:0.5rem 0.875rem;font-weight:600;font-size:0.8rem;cursor:pointer">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- ===== TAB: SETTINGS ===== -->
<div id="tab-settings" style="display:<?= $tab==='settings' ? 'block' : 'none' ?>;max-width:800px;margin:0 auto">
    <div style="background:white;border-radius:16px;border:1px solid #e5e7eb;padding:2rem">
        <h2 style="font-size:1.25rem;font-weight:900;color:#111827;margin:0 0 1.5rem"><i class="fas fa-cog" style="color:#6b7280"></i> Cài đặt nhà hàng</h2>
        <form id="merchant-settings-form" onsubmit="saveMerchantSettings(event)">
            <input type="hidden" id="setting-rid" value="<?= (int)$rid ?>">
            
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:1.5rem">
                <div>
                    <label style="display:block;font-size:0.8rem;font-weight:700;color:#374151;margin-bottom:0.5rem">Tên nhà hàng *</label>
                    <input type="text" id="setting-name" value="<?= esc_attr($restaurant['name']) ?>" required
                           style="width:100%;padding:0.75rem 1rem;border:1.5px solid #e5e7eb;border-radius:10px;font-size:0.9rem;outline:none"
                           onfocus="this.style.borderColor='#ee2624'" onblur="this.style.borderColor='#e5e7eb'">
                </div>
                <div>
                    <label style="display:block;font-size:0.8rem;font-weight:700;color:#374151;margin-bottom:0.5rem">Số điện thoại *</label>
                    <input type="text" id="setting-phone" value="<?= esc_attr($restaurant['phone']) ?>" required
                           style="width:100%;padding:0.75rem 1rem;border:1.5px solid #e5e7eb;border-radius:10px;font-size:0.9rem;outline:none"
                           onfocus="this.style.borderColor='#ee2624'" onblur="this.style.borderColor='#e5e7eb'">
                </div>
            </div>

            <div style="margin-bottom:1.5rem">
                <label style="display:block;font-size:0.8rem;font-weight:700;color:#374151;margin-bottom:0.5rem">Địa chỉ chi tiết *</label>
                <input type="text" id="setting-address" value="<?= esc_attr($restaurant['address']) ?>" required
                       style="width:100%;padding:0.75rem 1rem;border:1.5px solid #e5e7eb;border-radius:10px;font-size:0.9rem;outline:none"
                       onfocus="this.style.borderColor='#ee2624'" onblur="this.style.borderColor='#e5e7eb'">
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:1.5rem">
                <div>
                    <label style="display:block;font-size:0.8rem;font-weight:700;color:#374151;margin-bottom:0.5rem">Tỉnh/Thành phố *</label>
                    <select id="setting-province" style="width:100%;padding:0.75rem 1rem;border:1.5px solid #e5e7eb;border-radius:10px;font-size:0.9rem;outline:none;cursor:pointer"
                            onfocus="this.style.borderColor='#ee2624'" onblur="this.style.borderColor='#e5e7eb'">
                        <?php foreach (['Hà Nội', 'TP.HCM', 'Đà Nẵng', 'Hải Phòng', 'Cần Thơ'] as $p): ?>
                        <option value="<?= esc_attr($p) ?>" <?= $restaurant['province'] === $p ? 'selected' : '' ?>><?= esc_html($p) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label style="display:block;font-size:0.8rem;font-weight:700;color:#374151;margin-bottom:0.5rem">Phí giao hàng (VND) *</label>
                    <input type="number" id="setting-fee" value="<?= (int)$restaurant['delivery_fee'] ?>" required min="0" step="1000"
                           style="width:100%;padding:0.75rem 1rem;border:1.5px solid #e5e7eb;border-radius:10px;font-size:0.9rem;outline:none"
                           onfocus="this.style.borderColor='#ee2624'" onblur="this.style.borderColor='#e5e7eb'">
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:2rem">
                <div>
                    <label style="display:block;font-size:0.8rem;font-weight:700;color:#374151;margin-bottom:0.5rem">Logo nhà hàng</label>
                    <div style="display:flex;align-items:center;gap:1rem">
                        <img id="setting-logo-preview" src="<?= esc_url($restaurant['image'] ?: 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=100&q=80') ?>" 
                             style="width:60px;height:60px;border-radius:12px;object-fit:cover;border:2px solid #e5e7eb">
                        <label style="background:#f3f4f6;color:#374151;padding:0.5rem 1rem;border-radius:8px;font-size:0.8rem;font-weight:700;cursor:pointer">
                            <i class="fas fa-camera"></i> Chọn ảnh
                            <input type="file" id="setting-logo-file" accept="image/*" style="display:none" onchange="previewSettingImg(this, 'setting-logo-preview')">
                        </label>
                    </div>
                </div>
                <div>
                    <label style="display:block;font-size:0.8rem;font-weight:700;color:#374151;margin-bottom:0.5rem">Ảnh bìa (Cover)</label>
                    <div style="display:flex;align-items:center;gap:1rem">
                        <img id="setting-cover-preview" src="<?= esc_url($restaurant['cover_image'] ?: 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=200&q=80') ?>" 
                             style="width:100px;height:60px;border-radius:12px;object-fit:cover;border:2px solid #e5e7eb">
                        <label style="background:#f3f4f6;color:#374151;padding:0.5rem 1rem;border-radius:8px;font-size:0.8rem;font-weight:700;cursor:pointer">
                            <i class="fas fa-camera"></i> Chọn ảnh
                            <input type="file" id="setting-cover-file" accept="image/*" style="display:none" onchange="previewSettingImg(this, 'setting-cover-preview')">
                        </label>
                    </div>
                </div>
            </div>

            <button type="submit" id="btn-save-settings" style="width:100%;background:#ee2624;color:white;border:none;border-radius:12px;padding:1rem;font-size:1rem;font-weight:800;cursor:pointer">
                Lưu cài đặt
            </button>
        </form>
    </div>
</div>

<!-- ===== MODAL: TẠO/SỬA VOUCHER ===== -->
<div id="merchant-voucher-modal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.5);align-items:center;justify-content:center;padding:1rem">
    <div style="background:white;border-radius:20px;width:100%;max-width:520px;max-height:90vh;overflow-y:auto;box-shadow:0 24px 60px rgba(0,0,0,0.2)">
        <div style="padding:1.25rem 1.5rem;border-bottom:1px solid #f3f4f6;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;background:white;z-index:1">
            <h3 id="mv-modal-title" style="font-size:1rem;font-weight:800;color:#111827;margin:0">Tạo Voucher Mới</h3>
            <button onclick="closeMerchantVoucherModal()" style="background:none;border:none;font-size:1.2rem;color:#9ca3af;cursor:pointer"><i class="fas fa-times"></i></button>
        </div>
        <div style="padding:1.5rem;display:flex;flex-direction:column;gap:1rem">
            <input type="hidden" id="mv-id" value="0">

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
                <div>
                    <label style="display:block;font-size:0.75rem;font-weight:700;color:#6b7280;text-transform:uppercase;margin-bottom:0.4rem">Mã Code *</label>
                    <input type="text" id="mv-code" placeholder="VD: SALE20" required
                           style="width:100%;padding:0.65rem 0.875rem;border:1.5px solid #e5e7eb;border-radius:10px;font-size:0.875rem;font-family:monospace;text-transform:uppercase;outline:none;box-sizing:border-box"
                           onfocus="this.style.borderColor='#ee2624'" onblur="this.style.borderColor='#e5e7eb'">
                </div>
                <div>
                    <label style="display:block;font-size:0.75rem;font-weight:700;color:#6b7280;text-transform:uppercase;margin-bottom:0.4rem">Tên Voucher *</label>
                    <input type="text" id="mv-name" placeholder="Giảm 20% cuối tuần" required
                           style="width:100%;padding:0.65rem 0.875rem;border:1.5px solid #e5e7eb;border-radius:10px;font-size:0.875rem;outline:none;box-sizing:border-box"
                           onfocus="this.style.borderColor='#ee2624'" onblur="this.style.borderColor='#e5e7eb'">
                </div>
            </div>

            <!-- Loại voucher -->
            <div>
                <label style="display:block;font-size:0.75rem;font-weight:700;color:#6b7280;text-transform:uppercase;margin-bottom:0.6rem">Loại Voucher *</label>
                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:0.5rem">
                    <?php foreach ([['fixed','💰','Giảm tiền','#8b5cf6'],['percent','🏷️','Giảm %','#f97316'],['freeship','🚚','Freeship','#0ea5e9']] as [$val,$emoji,$lbl,$color]): ?>
                    <label style="cursor:pointer">
                        <input type="radio" name="mv-type" value="<?= $val ?>" <?= $val==='fixed'?'checked':'' ?> onchange="mvOnTypeChange()" style="display:none">
                        <div class="mv-type-btn" data-type="<?= $val ?>"
                             style="border:2px solid <?= $val==='fixed' ? $color : '#e5e7eb' ?>;border-radius:12px;padding:0.75rem;text-align:center;transition:all 0.2s;background:<?= $val==='fixed' ? $color.'18' : '' ?>">
                            <div style="font-size:1.3rem;margin-bottom:0.2rem"><?= $emoji ?></div>
                            <div style="font-size:0.8rem;font-weight:700;color:#374151"><?= $lbl ?></div>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
                <div>
                    <label id="mv-value-label" style="display:block;font-size:0.75rem;font-weight:700;color:#6b7280;text-transform:uppercase;margin-bottom:0.4rem">Số tiền giảm (VND) *</label>
                    <input type="number" id="mv-value" min="1" required
                           style="width:100%;padding:0.65rem 0.875rem;border:1.5px solid #e5e7eb;border-radius:10px;font-size:0.875rem;outline:none;box-sizing:border-box"
                           onfocus="this.style.borderColor='#ee2624'" onblur="this.style.borderColor='#e5e7eb'">
                </div>
                <div id="mv-max-wrap">
                    <label style="display:block;font-size:0.75rem;font-weight:700;color:#6b7280;text-transform:uppercase;margin-bottom:0.4rem">Giảm tối đa (VND)</label>
                    <input type="number" id="mv-max" min="0"
                           style="width:100%;padding:0.65rem 0.875rem;border:1.5px solid #e5e7eb;border-radius:10px;font-size:0.875rem;outline:none;box-sizing:border-box"
                           onfocus="this.style.borderColor='#ee2624'" onblur="this.style.borderColor='#e5e7eb'">
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem">
                <div>
                    <label style="display:block;font-size:0.75rem;font-weight:700;color:#6b7280;text-transform:uppercase;margin-bottom:0.4rem">Đơn tối thiểu *</label>
                    <input type="number" id="mv-min" min="0" value="0" required
                           style="width:100%;padding:0.65rem 0.875rem;border:1.5px solid #e5e7eb;border-radius:10px;font-size:0.875rem;outline:none;box-sizing:border-box"
                           onfocus="this.style.borderColor='#ee2624'" onblur="this.style.borderColor='#e5e7eb'">
                </div>
                <div>
                    <label style="display:block;font-size:0.75rem;font-weight:700;color:#6b7280;text-transform:uppercase;margin-bottom:0.4rem">Giới hạn dùng *</label>
                    <input type="number" id="mv-limit" min="1" value="100" required
                           style="width:100%;padding:0.65rem 0.875rem;border:1.5px solid #e5e7eb;border-radius:10px;font-size:0.875rem;outline:none;box-sizing:border-box"
                           onfocus="this.style.borderColor='#ee2624'" onblur="this.style.borderColor='#e5e7eb'">
                </div>
                <div>
                    <label style="display:block;font-size:0.75rem;font-weight:700;color:#6b7280;text-transform:uppercase;margin-bottom:0.4rem">Ngày hết hạn *</label>
                    <input type="date" id="mv-end" required
                           style="width:100%;padding:0.65rem 0.875rem;border:1.5px solid #e5e7eb;border-radius:10px;font-size:0.875rem;outline:none;box-sizing:border-box"
                           onfocus="this.style.borderColor='#ee2624'" onblur="this.style.borderColor='#e5e7eb'">
                </div>
            </div>

            <div id="mv-error" style="display:none;background:#fef2f2;border:1px solid #fca5a5;border-radius:10px;padding:0.75rem;font-size:0.875rem;color:#dc2626;font-weight:600"></div>

            <div style="display:flex;gap:0.75rem;padding-top:0.25rem">
                <button type="button" onclick="closeMerchantVoucherModal()"
                        style="flex:1;background:#f3f4f6;color:#374151;border:none;border-radius:12px;padding:0.875rem;font-weight:700;cursor:pointer">Huỷ</button>
                <button type="button" onclick="saveMerchantVoucher()" id="mv-save-btn"
                        style="flex:2;background:#ee2624;color:white;border:none;border-radius:12px;padding:0.875rem;font-weight:700;cursor:pointer">
                    <i class="fas fa-save"></i> Lưu Voucher
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ===== MODAL: CATEGORY ===== -->
<div id="cat-modal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.5);align-items:center;justify-content:center;padding:1rem">
    <div style="background:white;border-radius:20px;width:100%;max-width:420px;padding:1.5rem;box-shadow:0 24px 60px rgba(0,0,0,0.2)">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem">
            <h3 id="cat-modal-title" style="font-size:1rem;font-weight:800;color:#111827;margin:0">Thêm danh mục</h3>
            <button onclick="closeCatModal()" style="background:none;border:none;font-size:1.2rem;color:#9ca3af;cursor:pointer"><i class="fas fa-times"></i></button>
        </div>
        <input type="hidden" id="cat-id" value="0">
        <div style="margin-bottom:1rem">
            <label style="display:block;font-size:0.75rem;font-weight:700;color:#6b7280;text-transform:uppercase;margin-bottom:0.4rem">Tên danh mục *</label>
            <input type="text" id="cat-name" placeholder="VD: Món chính, Đồ uống..."
                   style="width:100%;padding:0.7rem 1rem;border:1.5px solid #e5e7eb;border-radius:12px;font-size:0.9rem;outline:none;box-sizing:border-box"
                   onfocus="this.style.borderColor='#ee2624'" onblur="this.style.borderColor='#e5e7eb'">
        </div>
        <div id="cat-error" style="display:none;background:#fef2f2;border:1px solid #fca5a5;border-radius:8px;padding:0.5rem 0.75rem;font-size:0.8rem;color:#dc2626;margin-bottom:0.75rem"></div>
        <div style="display:flex;gap:0.75rem">
            <button onclick="closeCatModal()" style="flex:1;background:#f3f4f6;color:#374151;border:none;border-radius:12px;padding:0.75rem;font-weight:700;cursor:pointer">Huỷ</button>
            <button onclick="saveCat()" style="flex:2;background:#ee2624;color:white;border:none;border-radius:12px;padding:0.75rem;font-weight:700;cursor:pointer"><i class="fas fa-save"></i> Lưu</button>
        </div>
    </div>
</div>

<!-- ===== MODAL: MENU ITEM ===== -->
<div id="item-modal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.5);align-items:center;justify-content:center;padding:1rem">
    <div style="background:white;border-radius:20px;width:100%;max-width:600px;max-height:90vh;overflow-y:auto;box-shadow:0 24px 60px rgba(0,0,0,0.2)">
        <!-- Modal header -->
        <div style="padding:1.25rem 1.5rem;border-bottom:1px solid #f3f4f6;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;background:white;z-index:1">
            <h3 id="item-modal-title" style="font-size:1rem;font-weight:800;color:#111827;margin:0">Thêm món mới</h3>
            <button onclick="closeItemModal()" style="background:none;border:none;font-size:1.2rem;color:#9ca3af;cursor:pointer"><i class="fas fa-times"></i></button>
        </div>
        <div style="padding:1.5rem;display:flex;flex-direction:column;gap:1rem">
            <input type="hidden" id="item-id" value="0">

            <!-- Image upload -->
            <div style="text-align:center">
                <div style="position:relative;display:inline-block">
                    <img id="item-img-preview" src="https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=200&q=80"
                         style="width:120px;height:120px;border-radius:16px;object-fit:cover;border:2px solid #e5e7eb">
                    <label for="item-img-file" style="position:absolute;bottom:4px;right:4px;width:28px;height:28px;background:#ee2624;border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer">
                        <i class="fas fa-camera" style="color:white;font-size:0.65rem"></i>
                    </label>
                    <input type="file" id="item-img-file" accept="image/*" style="display:none" onchange="previewItemImg(this)">
                </div>
                <input type="hidden" id="item-image-url" value="">
                <div id="item-img-uploading" style="display:none;font-size:0.75rem;color:#6b7280;margin-top:0.4rem"><i class="fas fa-spinner fa-spin"></i> Đang upload...</div>
            </div>

            <!-- Name -->
            <div>
                <label style="display:block;font-size:0.75rem;font-weight:700;color:#6b7280;text-transform:uppercase;margin-bottom:0.4rem">Tên món *</label>
                <input type="text" id="item-name" placeholder="VD: Phở bò tái chín đặc biệt"
                       style="width:100%;padding:0.7rem 1rem;border:1.5px solid #e5e7eb;border-radius:12px;font-size:0.9rem;outline:none;box-sizing:border-box"
                       onfocus="this.style.borderColor='#ee2624'" onblur="this.style.borderColor='#e5e7eb'">
            </div>

            <!-- Description -->
            <div>
                <label style="display:block;font-size:0.75rem;font-weight:700;color:#6b7280;text-transform:uppercase;margin-bottom:0.4rem">Mô tả</label>
                <textarea id="item-desc" rows="2" placeholder="Mô tả ngắn về món ăn..."
                          style="width:100%;padding:0.7rem 1rem;border:1.5px solid #e5e7eb;border-radius:12px;font-size:0.875rem;outline:none;resize:none;box-sizing:border-box"
                          onfocus="this.style.borderColor='#ee2624'" onblur="this.style.borderColor='#e5e7eb'"></textarea>
            </div>

            <!-- Price row -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
                <div>
                    <label style="display:block;font-size:0.75rem;font-weight:700;color:#6b7280;text-transform:uppercase;margin-bottom:0.4rem">Giá bán (VND) *</label>
                    <input type="number" id="item-price" min="0" placeholder="50000"
                           style="width:100%;padding:0.7rem 1rem;border:1.5px solid #e5e7eb;border-radius:12px;font-size:0.9rem;outline:none;box-sizing:border-box"
                           onfocus="this.style.borderColor='#ee2624'" onblur="this.style.borderColor='#e5e7eb'">
                </div>
                <div>
                    <label style="display:block;font-size:0.75rem;font-weight:700;color:#6b7280;text-transform:uppercase;margin-bottom:0.4rem">Giá gốc (VND)</label>
                    <input type="number" id="item-orig-price" min="0" placeholder="Để trống nếu không giảm"
                           style="width:100%;padding:0.7rem 1rem;border:1.5px solid #e5e7eb;border-radius:12px;font-size:0.9rem;outline:none;box-sizing:border-box"
                           onfocus="this.style.borderColor='#ee2624'" onblur="this.style.borderColor='#e5e7eb'">
                </div>
            </div>

            <!-- Category -->
            <div>
                <label style="display:block;font-size:0.75rem;font-weight:700;color:#6b7280;text-transform:uppercase;margin-bottom:0.4rem">Danh mục</label>
                <select id="item-cat" style="width:100%;padding:0.7rem 1rem;border:1.5px solid #e5e7eb;border-radius:12px;font-size:0.9rem;outline:none;box-sizing:border-box;cursor:pointer"
                        onfocus="this.style.borderColor='#ee2624'" onblur="this.style.borderColor='#e5e7eb'">
                    <option value="">-- Chưa phân loại --</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?= (int)$cat['id'] ?>"><?= esc_html($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Toggles -->
            <div style="display:flex;gap:1.5rem">
                <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer;font-size:0.875rem;font-weight:600;color:#374151">
                    <input type="checkbox" id="item-available" checked style="width:16px;height:16px;accent-color:#ee2624">
                    Đang bán
                </label>
                <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer;font-size:0.875rem;font-weight:600;color:#374151">
                    <input type="checkbox" id="item-bestseller" style="width:16px;height:16px;accent-color:#f97316">
                    Bán chạy 🔥
                </label>
            </div>

            <div id="item-error" style="display:none;background:#fef2f2;border:1px solid #fca5a5;border-radius:10px;padding:0.65rem 1rem;font-size:0.875rem;color:#dc2626;font-weight:600"></div>

            <div style="display:flex;gap:0.75rem;padding-top:0.25rem">
                <button onclick="closeItemModal()" style="flex:1;background:#f3f4f6;color:#374151;border:none;border-radius:12px;padding:0.875rem;font-weight:700;cursor:pointer">Huỷ</button>
                <button onclick="saveItem()" id="item-save-btn" style="flex:2;background:#ee2624;color:white;border:none;border-radius:12px;padding:0.875rem;font-weight:700;cursor:pointer"><i class="fas fa-save"></i> Lưu món</button>
            </div>
        </div>
    </div>
</div>

<style>
@media(max-width:700px){
    div[style*="grid-template-columns:repeat(4"]{grid-template-columns:repeat(2,1fr)!important}
    div[style*="grid-template-columns:repeat(auto-fill,minmax(280px"]{grid-template-columns:1fr!important}
}
</style>

<script>
const AJAX_URL = '<?= $ajax_url ?>';
const NONCE    = '<?= $nonce ?>';
const RID      = <?= $rid ?>;

// ===== TABS =====
function switchTab(tab) {
    ['orders','menu','voucher','settings'].forEach(t => {
        const el = document.getElementById('tab-'+t);
        if (el) el.style.display = t===tab ? 'block' : 'none';
        const btn = document.getElementById('tab-btn-'+t);
        if(btn) {
            if (t===tab) { btn.style.background='white'; btn.style.color='#111827'; btn.style.boxShadow='0 1px 4px rgba(0,0,0,0.1)'; }
            else         { btn.style.background='transparent'; btn.style.color='#6b7280'; btn.style.boxShadow=''; }
        }
    });
    history.replaceState(null,'','?tab='+tab);
}

// ===== TOGGLE OPEN =====
function toggleOpen() {
    const fd = new FormData();
    fd.append('action','cica_merchant_toggle_open');
    fd.append('nonce', NONCE);
    fd.append('restaurant_id', RID);
    fetch(AJAX_URL,{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
        if (d.success) {
            const badge = document.getElementById('open-badge');
            const btn   = document.getElementById('toggle-open-btn');
            if (d.is_open) {
                badge.textContent='● Đang mở cửa'; badge.style.background='#dcfce7'; badge.style.color='#16a34a';
                btn.innerHTML='<i class="fas fa-door-closed"></i> Đóng cửa'; btn.style.background='#fee2e2'; btn.style.color='#dc2626';
            } else {
                badge.textContent='○ Đóng cửa'; badge.style.background='#fee2e2'; badge.style.color='#dc2626';
                btn.innerHTML='<i class="fas fa-door-open"></i> Mở cửa'; btn.style.background='#dcfce7'; btn.style.color='#16a34a';
            }
            if(typeof showCicaToast==='function') showCicaToast('success', d.message);
        }
    });
}

// ===== ORDER STATUS =====
function updateOrderStatus(orderId, status, sel) {
    const fd = new FormData();
    fd.append('action','cica_merchant_update_order');
    fd.append('nonce', NONCE);
    fd.append('order_id', orderId);
    fd.append('status', status);
    sel.disabled = true;
    fetch(AJAX_URL,{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
        sel.disabled = false;
        if (d.success) {
            const row = sel.closest('.order-row');
            if (row) row.dataset.status = status;
            if(typeof showCicaToast==='function') showCicaToast('success', d.message);
            if (status==='completed'||status==='cancelled') {
                setTimeout(()=>{ row.style.opacity='0.5'; }, 500);
            }
        } else {
            if(typeof showCicaToast==='function') showCicaToast('error', d.message);
        }
    });
}

function filterOrders(filter, btn) {
    document.querySelectorAll('.order-filter-btn').forEach(b=>{
        b.style.background='#f3f4f6'; b.style.color='#374151';
    });
    btn.style.background='#ee2624'; btn.style.color='white';
    document.querySelectorAll('.order-row').forEach(row=>{
        row.style.display = (filter==='all'||row.dataset.status===filter) ? '' : 'none';
    });
}

// ===== CATEGORY MODAL =====
function openCatModal(id=0, name='') {
    document.getElementById('cat-id').value = id;
    document.getElementById('cat-name').value = name;
    document.getElementById('cat-modal-title').textContent = id ? 'Sửa danh mục' : 'Thêm danh mục';
    document.getElementById('cat-error').style.display = 'none';
    document.getElementById('cat-modal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
    setTimeout(()=>document.getElementById('cat-name').focus(), 100);
}
function closeCatModal() {
    document.getElementById('cat-modal').style.display = 'none';
    document.body.style.overflow = '';
}
function saveCat() {
    const name = document.getElementById('cat-name').value.trim();
    const id   = parseInt(document.getElementById('cat-id').value);
    if (!name) { showModalError('cat-error','Ten danh muc khong duoc de trong'); return; }
    const fd = new FormData();
    fd.append('action','cica_save_menu_category');
    fd.append('nonce', NONCE);
    fd.append('name', name);
    fd.append('cat_id', id);
    fd.append('restaurant_id', RID);
    fetch(AJAX_URL,{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
        if (d.success) { closeCatModal(); if(typeof showCicaToast==='function') showCicaToast('success',d.message); setTimeout(()=>location.reload(),600); }
        else showModalError('cat-error', d.message);
    });
}
function deleteCat(id) {
    if (!confirm('Xoa danh muc nay? Cac mon se chuyen sang "Chua phan loai".')) return;
    const fd = new FormData();
    fd.append('action','cica_delete_menu_category');
    fd.append('nonce', NONCE);
    fd.append('cat_id', id);
    fd.append('restaurant_id', RID);
    fetch(AJAX_URL,{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
        if (d.success) { if(typeof showCicaToast==='function') showCicaToast('success',d.message); setTimeout(()=>location.reload(),600); }
    });
}

// ===== ITEM MODAL =====
let currentItemImgUrl = '';
function openItemModal(item=null) {
    document.getElementById('item-id').value       = item ? item.id : 0;
    document.getElementById('item-name').value     = item ? item.name : '';
    document.getElementById('item-desc').value     = item ? (item.description||'') : '';
    document.getElementById('item-price').value    = item ? item.price : '';
    document.getElementById('item-orig-price').value = item ? (item.original_price||'') : '';
    document.getElementById('item-cat').value      = item ? (item.menu_category_id||'') : '';
    document.getElementById('item-available').checked  = item ? !!parseInt(item.is_available) : true;
    document.getElementById('item-bestseller').checked = item ? !!parseInt(item.is_best_seller) : false;
    currentItemImgUrl = item ? (item.image||'') : '';
    document.getElementById('item-image-url').value = currentItemImgUrl;
    document.getElementById('item-img-preview').src = currentItemImgUrl || 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=200&q=80';
    document.getElementById('item-modal-title').textContent = item ? 'Sua mon an' : 'Them mon moi';
    document.getElementById('item-error').style.display = 'none';
    document.getElementById('item-modal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function closeItemModal() {
    document.getElementById('item-modal').style.display = 'none';
    document.body.style.overflow = '';
    document.getElementById('item-img-file').value = '';
}
function previewItemImg(input) {
    if (!input.files||!input.files[0]) return;
    const reader = new FileReader();
    reader.onload = e => document.getElementById('item-img-preview').src = e.target.result;
    reader.readAsDataURL(input.files[0]);
    // Upload immediately
    const uploading = document.getElementById('item-img-uploading');
    uploading.style.display = 'block';
    const fd = new FormData();
    fd.append('action','cica_upload_item_image');
    fd.append('nonce', NONCE);
    fd.append('image', input.files[0]);
    fetch(AJAX_URL,{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
        uploading.style.display = 'none';
        if (d.success) { currentItemImgUrl = d.url; document.getElementById('item-image-url').value = d.url; }
        else if(typeof showCicaToast==='function') showCicaToast('error', d.message);
    });
}
function saveItem() {
    const name  = document.getElementById('item-name').value.trim();
    const price = parseInt(document.getElementById('item-price').value);
    if (!name)       { showModalError('item-error','Ten mon khong duoc de trong'); return; }
    if (!price||price<=0) { showModalError('item-error','Gia ban phai lon hon 0'); return; }
    const btn = document.getElementById('item-save-btn');
    btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Dang luu...';
    const fd = new FormData();
    fd.append('action','cica_save_menu_item');
    fd.append('nonce', NONCE);
    fd.append('item_id',          document.getElementById('item-id').value);
    fd.append('name',             name);
    fd.append('description',      document.getElementById('item-desc').value);
    fd.append('price',            price);
    fd.append('original_price',   document.getElementById('item-orig-price').value||0);
    fd.append('menu_category_id', document.getElementById('item-cat').value||0);
    fd.append('is_available',     document.getElementById('item-available').checked?1:0);
    fd.append('is_best_seller',   document.getElementById('item-bestseller').checked?1:0);
    fd.append('image',            document.getElementById('item-image-url').value||currentItemImgUrl);
    fd.append('restaurant_id',    RID);
    fetch(AJAX_URL,{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
        btn.disabled=false; btn.innerHTML='<i class="fas fa-save"></i> Luu mon';
        if (d.success) { closeItemModal(); if(typeof showCicaToast==='function') showCicaToast('success',d.message); setTimeout(()=>location.reload(),600); }
        else showModalError('item-error', d.message);
    }).catch(()=>{ btn.disabled=false; btn.innerHTML='<i class="fas fa-save"></i> Luu mon'; });
}

// ===== TOGGLE AVAILABILITY =====
function toggleAvail(itemId, btn) {
    const fd = new FormData();
    fd.append('action','cica_toggle_item_avail');
    fd.append('nonce', NONCE);
    fd.append('item_id', itemId);
    fetch(AJAX_URL,{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
        if (d.success) {
            const card = btn.closest('.menu-item-card');
            if (d.is_available) {
                card.style.opacity='1'; card.style.borderColor='#e5e7eb';
                btn.style.background='#f0fdf4';
                btn.querySelector('i').className='fas fa-eye'; btn.querySelector('i').style.color='#16a34a';
                btn.title='An mon';
            } else {
                card.style.opacity='0.65'; card.style.borderColor='#f3f4f6';
                btn.style.background='#fef2f2';
                btn.querySelector('i').className='fas fa-eye-slash'; btn.querySelector('i').style.color='#9ca3af';
                btn.title='Hien mon';
            }
        }
    });
}

// ===== DELETE ITEM =====
function deleteItem(itemId, btn) {
    if (!confirm('Xoa mon an nay?')) return;
    const card = btn.closest('.menu-item-card');
    const fd = new FormData();
    fd.append('action','cica_delete_menu_item');
    fd.append('nonce', NONCE);
    fd.append('item_id', itemId);
    fetch(AJAX_URL,{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
        if (d.success) {
            card.style.transition='all 0.3s'; card.style.opacity='0'; card.style.transform='scale(0.9)';
            setTimeout(()=>card.remove(), 300);
            if(typeof showCicaToast==='function') showCicaToast('success', d.message);
        }
    });
}

// ===== SEARCH MENU =====
function searchMenu(q) {
    q = q.toLowerCase().trim();
    document.querySelectorAll('.menu-item-card').forEach(card=>{
        card.style.display = (!q || card.dataset.name.includes(q)) ? '' : 'none';
    });
}

// ===== HELPERS =====
function showModalError(id, msg) {
    const el = document.getElementById(id);
    el.textContent = msg; el.style.display = 'block';
}

// Close modals on backdrop click / ESC
['cat-modal','item-modal'].forEach(id=>{
    document.getElementById(id).addEventListener('click', e=>{ if(e.target===document.getElementById(id)) { closeCatModal(); closeItemModal(); } });
});
document.addEventListener('keydown', e=>{ if(e.key==='Escape'){ closeCatModal(); closeItemModal(); } });

// ============================================================
// POLLING — Tự động kiểm tra đơn hàng mới mỗi 30 giây
// ============================================================
(function() {
    const AJAX    = (window.cicafoodAjax && window.cicafoodAjax.ajaxurl) ? window.cicafoodAjax.ajaxurl : '<?= esc_js(admin_url('admin-ajax.php')) ?>';
    const NONCE   = window.cicafoodAjax ? window.cicafoodAjax.nonce : '';
    let lastCount = <?= $pending_orders ?>;
    let audio     = null;

    // Tao am thanh thong bao (beep don gian)
    function playBeep() {
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.frequency.value = 880;
            gain.gain.setValueAtTime(0.3, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.4);
            osc.start(ctx.currentTime);
            osc.stop(ctx.currentTime + 0.4);
        } catch(e) {}
    }

    // Cap nhat badge tren tab Orders
    function updateBadge(count) {
        const tabBtn = document.getElementById('tab-btn-orders');
        if (!tabBtn) return;
        // Xoa badge cu
        const oldBadge = tabBtn.querySelector('.order-badge');
        if (oldBadge) oldBadge.remove();
        if (count > 0) {
            const badge = document.createElement('span');
            badge.className = 'order-badge';
            badge.style.cssText = 'position:absolute;top:-6px;right:-6px;background:#ee2624;color:white;font-size:0.65rem;font-weight:900;min-width:18px;height:18px;border-radius:999px;display:flex;align-items:center;justify-content:center;padding:0 4px;animation:pulse 1.5s infinite';
            badge.textContent = count;
            tabBtn.style.position = 'relative';
            tabBtn.appendChild(badge);
        }
    }

    // Hien thong bao toast
    function showNewOrderToast(count) {
        if (typeof showCicaToast === 'function') {
            showCicaToast('success', '🔔 Có ' + count + ' đơn hàng mới đang chờ xác nhận!');
        }
        // Doi title tab
        document.title = '(' + count + ') Đơn mới — Quản lý quán';
        playBeep();
    }

    // Poll API
    function pollOrders() {
        fetch(AJAX + '?action=cica_get_pending_orders&nonce=' + NONCE)
            .then(r => r.json())
            .then(d => {
                const newCount = d.count || 0;
                updateBadge(newCount);

                // Chi thong bao khi so don tang
                if (newCount > lastCount) {
                    showNewOrderToast(newCount);
                }
                lastCount = newCount;
            })
            .catch(() => {}); // Im lang neu loi mang
    }

    // Khoi dong: cap nhat badge ngay
    updateBadge(lastCount);

    // Poll moi 30 giay
    setInterval(pollOrders, 30000);

    // Them CSS animation pulse
    const style = document.createElement('style');
    style.textContent = '@keyframes pulse { 0%,100%{transform:scale(1)} 50%{transform:scale(1.2)} }';
    document.head.appendChild(style);
})();

// ===== MERCHANT VOUCHER =====
const MV_COLORS = { fixed:'#8b5cf6', percent:'#f97316', freeship:'#0ea5e9' };

function openMerchantVoucherModal(v) {
    document.getElementById('mv-modal-title').textContent = v ? 'Sửa Voucher' : 'Tạo Voucher Mới';
    document.getElementById('mv-id').value    = v ? v.id : 0;
    document.getElementById('mv-code').value  = v ? v.code : '';
    document.getElementById('mv-name').value  = v ? v.name : '';
    document.getElementById('mv-value').value = v ? v.value : '';
    document.getElementById('mv-max').value   = v ? (v.max_discount || '') : '';
    document.getElementById('mv-min').value   = v ? v.min_order : 0;
    document.getElementById('mv-limit').value = v ? v.usage_limit : 100;
    document.getElementById('mv-end').value   = v ? (v.end_date || '').substring(0,10) : '';
    const type = v ? v.type : 'fixed';
    const radio = document.querySelector(`input[name="mv-type"][value="${type}"]`);
    if (radio) radio.checked = true;
    mvOnTypeChange();
    document.getElementById('mv-error').style.display = 'none';
    document.getElementById('merchant-voucher-modal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeMerchantVoucherModal() {
    document.getElementById('merchant-voucher-modal').style.display = 'none';
    document.body.style.overflow = '';
}

function mvOnTypeChange() {
    const type  = document.querySelector('input[name="mv-type"]:checked')?.value || 'fixed';
    const color = MV_COLORS[type] || '#ee2624';
    document.querySelectorAll('.mv-type-btn').forEach(btn => {
        const t = btn.dataset.type;
        btn.style.borderColor = t===type ? color : '#e5e7eb';
        btn.style.background  = t===type ? color+'18' : '';
    });
    const lbl = document.getElementById('mv-value-label');
    const maxW = document.getElementById('mv-max-wrap');
    if (type==='percent')   { lbl.textContent='Phần trăm giảm (%) *'; maxW.style.display='block'; }
    else if (type==='freeship') { lbl.textContent='Phí ship hỗ trợ tối đa (VND) *'; maxW.style.display='block'; }
    else { lbl.textContent='Số tiền giảm (VND) *'; maxW.style.display='none'; }
}

function saveMerchantVoucher() {
    const btn = document.getElementById('mv-save-btn');
    const err = document.getElementById('mv-error');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang lưu...';
    err.style.display = 'none';

    const type = document.querySelector('input[name="mv-type"]:checked')?.value || 'fixed';
    const fd = new FormData();
    fd.append('action',       'cica_merchant_save_voucher');
    fd.append('nonce',        NONCE);
    fd.append('voucher_id',   document.getElementById('mv-id').value);
    fd.append('code',         document.getElementById('mv-code').value.toUpperCase());
    fd.append('name',         document.getElementById('mv-name').value);
    fd.append('type',         type);
    fd.append('value',        document.getElementById('mv-value').value);
    fd.append('max_discount', document.getElementById('mv-max').value || document.getElementById('mv-value').value);
    fd.append('min_order',    document.getElementById('mv-min').value);
    fd.append('usage_limit',  document.getElementById('mv-limit').value);
    fd.append('end_date',     document.getElementById('mv-end').value);
    fd.append('restaurant_id', RID);

    fetch(AJAX_URL, { method:'POST', body:fd })
        .then(r=>r.json())
        .then(d => {
            if (d.success) {
                closeMerchantVoucherModal();
                if (typeof showCicaToast==='function') showCicaToast('success', d.message);
                setTimeout(()=>location.reload(), 600);
            } else {
                err.textContent = d.message;
                err.style.display = 'block';
            }
        })
        .catch(()=>{ err.textContent='Lỗi kết nối'; err.style.display='block'; })
        .finally(()=>{ btn.disabled=false; btn.innerHTML='<i class="fas fa-save"></i> Lưu Voucher'; });
}

function deleteMerchantVoucher(id) {
    if (!confirm('Xoá voucher này?')) return;
    const fd = new FormData();
    fd.append('action',     'cica_merchant_delete_voucher');
    fd.append('nonce',      NONCE);
    fd.append('voucher_id', id);
    fetch(AJAX_URL, { method:'POST', body:fd })
        .then(r=>r.json())
        .then(d => {
            if (d.success) {
                const row = document.getElementById('mv-row-'+id);
                if (row) { row.style.opacity='0'; row.style.transition='opacity 0.3s'; setTimeout(()=>row.remove(),300); }
                if (typeof showCicaToast==='function') showCicaToast('success', d.message);
            }
        });
}

// Close modal on backdrop/ESC
document.getElementById('merchant-voucher-modal').addEventListener('click', e => {
    if (e.target===document.getElementById('merchant-voucher-modal')) closeMerchantVoucherModal();
});
document.addEventListener('keydown', e => { if (e.key==='Escape') closeMerchantVoucherModal(); });

// ===== MERCHANT SETTINGS =====
let settingLogoUrl = '<?= esc_url($restaurant['image']) ?>';
let settingCoverUrl = '<?= esc_url($restaurant['cover_image'] ?? '') ?>';

function previewSettingImg(input, previewId) {
    if (!input.files || !input.files[0]) return;
    const reader = new FileReader();
    reader.onload = e => document.getElementById(previewId).src = e.target.result;
    reader.readAsDataURL(input.files[0]);

    // Upload immediately
    const fd = new FormData();
    fd.append('action', 'cica_upload_item_image'); // Reusing existing upload handler
    fd.append('nonce', NONCE);
    fd.append('image', input.files[0]);
    
    // Disable save button while uploading
    const saveBtn = document.getElementById('btn-save-settings');
    if (saveBtn) { saveBtn.disabled = true; saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang tải ảnh...'; }

    fetch(AJAX_URL, {method:'POST', body:fd}).then(r=>r.json()).then(d=>{
        if (saveBtn) { saveBtn.disabled = false; saveBtn.textContent = 'Lưu cài đặt'; }
        if (d.success) {
            if (previewId === 'setting-logo-preview') settingLogoUrl = d.url;
            if (previewId === 'setting-cover-preview') settingCoverUrl = d.url;
            if(typeof showCicaToast==='function') showCicaToast('success', 'Đã tải ảnh lên thành công');
        } else {
            if(typeof showCicaToast==='function') showCicaToast('error', d.message || 'Lỗi tải ảnh');
        }
    }).catch(e => {
        if (saveBtn) { saveBtn.disabled = false; saveBtn.textContent = 'Lưu cài đặt'; }
    });
}

function saveMerchantSettings(e) {
    e.preventDefault();
    const btn = document.getElementById('btn-save-settings');
    btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang lưu...';

    const fd = new FormData();
    fd.append('action', 'cica_save_merchant_settings');
    fd.append('nonce', NONCE);
    fd.append('restaurant_id', document.getElementById('setting-rid').value);
    fd.append('name', document.getElementById('setting-name').value);
    fd.append('phone', document.getElementById('setting-phone').value);
    fd.append('address', document.getElementById('setting-address').value);
    fd.append('province', document.getElementById('setting-province').value);
    fd.append('delivery_fee', document.getElementById('setting-fee').value);
    fd.append('image', settingLogoUrl);
    fd.append('cover_image', settingCoverUrl);

    fetch(AJAX_URL, {method:'POST', body:fd}).then(r=>r.json()).then(d=>{
        btn.disabled = false; btn.textContent = 'Lưu cài đặt';
        if (d.success) {
            if(typeof showCicaToast==='function') showCicaToast('success', d.message);
        } else {
            if(typeof showCicaToast==='function') showCicaToast('error', d.message);
        }
    }).catch(()=>{
        btn.disabled = false; btn.textContent = 'Lưu cài đặt';
    });
}
</script>
