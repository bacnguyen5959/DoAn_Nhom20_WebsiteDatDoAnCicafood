<?php
defined('ABSPATH') || exit;

$order_id = (int)($_GET['order_id'] ?? 0);
$site_url  = get_site_url();

if (!$order_id) {
    echo '<div style="text-align:center;padding:4rem"><p>Không tìm thấy đơn hàng.</p><a href="' . esc_url($site_url) . '">Về trang chủ</a></div>';
    return;
}

$order = Cicafood_Order::get_by_id($order_id);

// Bảo mật: chỉ cho xem đơn của chính mình (hoặc admin)
if (!$order || (!current_user_can('manage_options') && (int)$order['wp_user_id'] !== get_current_user_id())) {
    echo '<div style="text-align:center;padding:4rem"><p>Không tìm thấy đơn hàng.</p><a href="' . esc_url($site_url) . '">Về trang chủ</a></div>';
    return;
}

$items       = Cicafood_Order::get_items($order_id);
$status_info = Cicafood_Order::status_label($order['status']);
?>

<div style="max-width:680px;margin:2rem auto;padding:0 1rem">

    <!-- Success Banner -->
    <div style="background:linear-gradient(135deg,#16a34a,#22c55e);border-radius:20px;padding:2rem;text-align:center;color:white;margin-bottom:1.5rem">
        <div style="width:64px;height:64px;background:rgba(255,255,255,0.2);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;font-size:1.75rem">
            <i class="fas fa-check"></i>
        </div>
        <h1 style="font-size:1.5rem;font-weight:900;margin:0 0 0.5rem">Đặt hàng thành công!</h1>
        <p style="margin:0;opacity:0.9;font-size:0.95rem">Mã đơn hàng: <strong><?= esc_html($order['order_code']) ?></strong></p>
    </div>

    <!-- Order Info -->
    <div style="background:white;border-radius:16px;border:1px solid #e5e7eb;padding:1.5rem;margin-bottom:1rem">
        <h2 style="font-size:1rem;font-weight:700;color:#111827;margin:0 0 1rem;padding-bottom:0.75rem;border-bottom:1px solid #f3f4f6">
            <i class="fas fa-info-circle" style="color:#ee2624;margin-right:0.5rem"></i>Thông tin đơn hàng
        </h2>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;font-size:0.875rem">
            <div>
                <span style="color:#6b7280">Nhà hàng</span>
                <p style="font-weight:600;color:#111827;margin:0.2rem 0 0"><?= esc_html($order['restaurant_name']) ?></p>
            </div>
            <div>
                <span style="color:#6b7280">Trạng thái</span>
                <p style="margin:0.2rem 0 0">
                    <span style="background:#fef9c3;color:#854d0e;padding:2px 10px;border-radius:999px;font-weight:700;font-size:0.8rem">
                        <i class="fas <?= esc_attr($status_info['icon']) ?>"></i> <?= esc_html($status_info['label']) ?>
                    </span>
                </p>
            </div>
            <div>
                <span style="color:#6b7280">Người nhận</span>
                <p style="font-weight:600;color:#111827;margin:0.2rem 0 0"><?= esc_html($order['recipient_name']) ?></p>
            </div>
            <div>
                <span style="color:#6b7280">Số điện thoại</span>
                <p style="font-weight:600;color:#111827;margin:0.2rem 0 0"><?= esc_html($order['recipient_phone']) ?></p>
            </div>
            <div style="grid-column:1/-1">
                <span style="color:#6b7280">Địa chỉ giao hàng</span>
                <p style="font-weight:600;color:#111827;margin:0.2rem 0 0"><?= esc_html($order['delivery_address']) ?></p>
            </div>
            <?php if ($order['note']): ?>
            <div style="grid-column:1/-1">
                <span style="color:#6b7280">Ghi chú</span>
                <p style="font-weight:600;color:#111827;margin:0.2rem 0 0"><?= esc_html($order['note']) ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Order Items -->
    <div style="background:white;border-radius:16px;border:1px solid #e5e7eb;padding:1.5rem;margin-bottom:1rem">
        <h2 style="font-size:1rem;font-weight:700;color:#111827;margin:0 0 1rem;padding-bottom:0.75rem;border-bottom:1px solid #f3f4f6">
            <i class="fas fa-utensils" style="color:#ee2624;margin-right:0.5rem"></i>Món đã đặt
        </h2>
        <?php foreach ($items as $item): ?>
        <div style="display:flex;justify-content:space-between;align-items:center;padding:0.5rem 0;border-bottom:1px solid #f9fafb;font-size:0.875rem">
            <div style="display:flex;align-items:center;gap:0.5rem">
                <span style="background:#fef2f2;color:#ee2624;font-weight:700;font-size:0.75rem;padding:2px 8px;border-radius:6px"><?= (int)$item['quantity'] ?>x</span>
                <span style="color:#374151;font-weight:500"><?= esc_html($item['item_name']) ?></span>
            </div>
            <span style="font-weight:600;color:#374151"><?= cica_format_price((int)$item['subtotal']) ?></span>
        </div>
        <?php endforeach; ?>

        <!-- Totals -->
        <div style="margin-top:1rem;padding-top:0.75rem;border-top:1px dashed #e5e7eb;font-size:0.875rem">
            <div style="display:flex;justify-content:space-between;color:#6b7280;margin-bottom:0.4rem">
                <span>Tạm tính</span><span><?= cica_format_price((int)$order['subtotal']) ?></span>
            </div>
            <div style="display:flex;justify-content:space-between;color:#6b7280;margin-bottom:0.4rem">
                <span>Phí giao hàng</span>
                <span><?= (int)$order['delivery_fee'] === 0 ? '<span style="color:#22c55e;font-weight:600">Miễn phí</span>' : cica_format_price((int)$order['delivery_fee']) ?></span>
            </div>
            <div style="display:flex;justify-content:space-between;color:#6b7280;margin-bottom:0.4rem">
                <span>Phí dịch vụ</span><span><?= cica_format_price((int)$order['service_fee']) ?></span>
            </div>
            <?php if ((int)$order['discount_amount'] > 0): ?>
            <div style="display:flex;justify-content:space-between;color:#22c55e;margin-bottom:0.4rem">
                <span>Giảm giá</span><span>-<?= cica_format_price((int)$order['discount_amount']) ?></span>
            </div>
            <?php endif; ?>
            <div style="display:flex;justify-content:space-between;font-weight:800;font-size:1rem;padding-top:0.6rem;border-top:1px solid #e5e7eb;margin-top:0.4rem">
                <span style="color:#111827">Tổng thanh toán</span>
                <span style="color:#ee2624"><?= cica_format_price((int)$order['total_amount']) ?></span>
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div style="display:flex;gap:0.75rem">
        <a href="<?= esc_url($site_url . '/don-hang/') ?>"
           style="flex:1;display:block;text-align:center;padding:0.875rem;border-radius:12px;font-weight:700;font-size:0.9rem;background:#f3f4f6;color:#374151;text-decoration:none">
            <i class="fas fa-list"></i> Xem đơn hàng
        </a>
        <a href="<?= esc_url($site_url) ?>"
           style="flex:1;display:block;text-align:center;padding:0.875rem;border-radius:12px;font-weight:700;font-size:0.9rem;background:#ee2624;color:white;text-decoration:none">
            <i class="fas fa-home"></i> Về trang chủ
        </a>
    </div>
</div>
