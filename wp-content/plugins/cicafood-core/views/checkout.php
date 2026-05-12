<?php
defined('ABSPATH') || exit;
if (!session_id()) session_start();

$cart = Cicafood_Cart::get();
if (empty($cart)) {
    echo '
    <div style="max-width:480px;margin:4rem auto;padding:1rem;text-align:center">
        <div style="background:white;border-radius:20px;border:1px solid #e5e7eb;padding:3rem 2rem;box-shadow:0 2px 12px rgba(0,0,0,0.06)">
            <div style="width:80px;height:80px;background:#f3f4f6;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1.25rem">
                <i class="fas fa-shopping-bag" style="font-size:2rem;color:#d1d5db"></i>
            </div>
            <h2 style="font-size:1.2rem;font-weight:800;color:#374151;margin:0 0 0.5rem">Giỏ hàng trống</h2>
            <p style="color:#9ca3af;font-size:0.9rem;margin:0 0 1.5rem">Bạn chưa có món nào trong giỏ hàng</p>
            <a href="' . esc_url(get_site_url() . '/nha-hang/') . '"
               style="display:inline-flex;align-items:center;gap:0.5rem;background:#ee2624;color:white;padding:0.75rem 1.5rem;border-radius:12px;font-weight:700;font-size:0.9rem;text-decoration:none">
                <i class="fas fa-utensils"></i> Đặt món ngay
            </a>
        </div>
    </div>';
    return;
}

$restaurant_id = Cicafood_Cart::restaurant_id();
$restaurant    = Cicafood_Restaurant::get_by_id($restaurant_id);
if (!$restaurant) { echo '<p>Nha hang khong ton tai.</p>'; return; }

$subtotal     = Cicafood_Cart::subtotal();
$delivery_fee = (int)$restaurant['delivery_fee'];
$service_fee  = (int)round($subtotal * 2 / 100);
$total        = $subtotal + $delivery_fee + $service_fee;

$current_user = wp_get_current_user();
$site_url     = get_site_url();
$ajax_url     = admin_url('admin-ajax.php');
$nonce        = wp_create_nonce('cicafood_nonce');

// Lay voucher cho checkout
$wallet_vouchers = is_user_logged_in() ? Cicafood_Voucher::get_wallet(get_current_user_id()) : [];
$avail_vouchers  = Cicafood_Voucher::get_available($subtotal, $restaurant_id, get_current_user_id());
$wallet_ids      = array_column($wallet_vouchers, 'id');
$extra_vouchers  = array_filter($avail_vouchers, fn($v) => !in_array($v['id'], $wallet_ids));
$checkout_vouchers = array_merge($wallet_vouchers, array_values($extra_vouchers));

// Auto-apply voucher tu URL param
$url_voucher = sanitize_text_field($_GET['voucher'] ?? '');

// Xu ly dat hang
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    check_admin_referer('cica_checkout');
    $result = Cicafood_Order::create([
        'wp_user_id'       => get_current_user_id(),
        'restaurant_id'    => $restaurant_id,
        'delivery_address' => $_POST['delivery_address'] ?? '',
        'recipient_name'   => $_POST['recipient_name'] ?? '',
        'recipient_phone'  => $_POST['recipient_phone'] ?? '',
        'note'             => $_POST['note'] ?? '',
        'delivery_fee'     => $delivery_fee,
        'discount_amount'  => (int)($_POST['discount_amount'] ?? 0),
        'shipping_discount'=> (int)($_POST['shipping_discount'] ?? 0),
        'voucher_code'     => $_POST['voucher_code'] ?? '',
        'payment_method'   => in_array($_POST['payment_method'] ?? '', ['cod', 'bank_transfer']) ? $_POST['payment_method'] : 'cod',
    ]);
    if ($result['success']) {
        wp_redirect($site_url . '/dat-hang-thanh-cong/?order_id=' . $result['order_id']);
        exit;
    }
    $errors[] = $result['message'];
}
?>


<div style="max-width:960px;margin:0 auto;padding:1.5rem 1rem">

    <!-- Breadcrumb -->
    <div style="display:flex;align-items:center;gap:0.5rem;font-size:0.8rem;color:#9ca3af;margin-bottom:1.5rem">
        <a href="<?= $site_url ?>" style="color:inherit;text-decoration:none">Trang chủ</a>
        <i class="fas fa-chevron-right" style="font-size:0.6rem"></i>
        <span style="color:#374151;font-weight:600">Thanh Toán</span>
    </div>

    <?php if (!empty($errors)): ?>
    <div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:14px;padding:1rem;margin-bottom:1rem">
        <?php foreach ($errors as $err): ?>
        <p style="color:#dc2626;font-size:0.875rem;margin:0"><i class="fas fa-triangle-exclamation"></i> <?= esc_html($err) ?></p>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <form method="POST">
        <?php wp_nonce_field('cica_checkout'); ?>
        <div style="display:grid;grid-template-columns:1fr 360px;gap:1.5rem;align-items:start">

            <!-- LEFT -->
            <div style="display:flex;flex-direction:column;gap:1.25rem">

                <!-- Dia chi -->
                <div style="background:white;border-radius:20px;border:1px solid #e5e7eb;padding:1.5rem">
                    <h2 style="font-size:1rem;font-weight:800;color:#111827;margin:0 0 1.25rem;display:flex;align-items:center;gap:0.5rem">
                        <i class="fas fa-map-marker-alt" style="color:#ee2624"></i> Địa Chỉ Nhận Hàng
                    </h2>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem">
                        <div>
                            <label style="display:block;font-size:0.75rem;font-weight:700;color:#6b7280;text-transform:uppercase;margin-bottom:0.4rem">Họ Tên *</label>
                            <input type="text" name="recipient_name" value="<?= esc_attr($current_user->display_name) ?>" required
                                   style="width:100%;padding:0.65rem 0.875rem;border:1.5px solid #e5e7eb;border-radius:10px;font-size:0.875rem;outline:none;box-sizing:border-box"
                                   onfocus="this.style.borderColor='#ee2624'" onblur="this.style.borderColor='#e5e7eb'">
                        </div>
                        <div>
                            <label style="display:block;font-size:0.75rem;font-weight:700;color:#6b7280;text-transform:uppercase;margin-bottom:0.4rem">Số Điện Thoại *</label>
                            <input type="tel" name="recipient_phone" value="<?= esc_attr(get_user_meta(get_current_user_id(), 'phone', true)) ?>" required
                                   style="width:100%;padding:0.65rem 0.875rem;border:1.5px solid #e5e7eb;border-radius:10px;font-size:0.875rem;outline:none;box-sizing:border-box"
                                   onfocus="this.style.borderColor='#ee2624'" onblur="this.style.borderColor='#e5e7eb'">
                        </div>
                    </div>
                    <div style="margin-bottom:1rem">
                        <label style="display:block;font-size:0.75rem;font-weight:700;color:#6b7280;text-transform:uppercase;margin-bottom:0.4rem">Địa Chỉ Giao Hàng *</label>
                        <textarea name="delivery_address" rows="2" required placeholder="Số nhà, tên đường, phường/xã, quận/huyện..."
                                  style="width:100%;padding:0.65rem 0.875rem;border:1.5px solid #e5e7eb;border-radius:10px;font-size:0.875rem;outline:none;resize:none;box-sizing:border-box"
                                  onfocus="this.style.borderColor='#ee2624'" onblur="this.style.borderColor='#e5e7eb'"><?= esc_textarea(get_user_meta(get_current_user_id(), 'address', true)) ?></textarea>
                    </div>
                    <div>
                        <label style="display:block;font-size:0.75rem;font-weight:700;color:#6b7280;text-transform:uppercase;margin-bottom:0.4rem">Ghi Chú Cho Quán</label>
                        <textarea name="note" rows="2" placeholder="Ít đường, nhiều đá, không hành..."
                                  style="width:100%;padding:0.65rem 0.875rem;border:1.5px solid #e5e7eb;border-radius:10px;font-size:0.875rem;outline:none;resize:none;box-sizing:border-box"
                                  onfocus="this.style.borderColor='#ee2624'" onblur="this.style.borderColor='#e5e7eb'"></textarea>
                    </div>
                </div>

                <!-- VOUCHER SECTION -->
                <div style="background:white;border-radius:20px;border:1px solid #e5e7eb;padding:1.5rem">
                    <h2 style="font-size:1rem;font-weight:800;color:#111827;margin:0 0 1rem;display:flex;align-items:center;gap:0.5rem">
                        <i class="fas fa-ticket" style="color:#f97316"></i> Mã Giảm Giá
                        <?php if (!empty($checkout_vouchers)): ?>
                        <span style="font-size:0.7rem;font-weight:700;background:#fef3c7;color:#92400e;padding:2px 8px;border-radius:999px"><?= count(array_filter($checkout_vouchers, fn($v) => !$v['end_date'] || strtotime($v['end_date']) >= time())) ?> voucher</span>
                        <?php endif; ?>
                    </h2>

                    <?php if (!empty($checkout_vouchers)): ?>
                    <!-- Danh sach voucher -->
                    <div style="display:flex;flex-direction:column;gap:0.5rem;margin-bottom:1rem;max-height:260px;overflow-y:auto" id="voucher-list">
                        <?php foreach ($checkout_vouchers as $v):
                            $expired  = $v['end_date'] && strtotime($v['end_date']) < time();
                            $is_used  = !empty($v['is_used']);
                            $can_use  = !$expired && !$is_used && $subtotal >= (int)$v['min_order'];
                            $reason   = $expired ? 'Hết hạn' : ($is_used ? 'Đã dùng' : ($subtotal < (int)$v['min_order'] ? 'Đơn từ ' . cica_format_price((int)$v['min_order']) : ''));
                            $t_icon   = $v['type'] === 'freeship' ? '🚚' : ($v['type'] === 'percent' ? '🏷️' : '💰');
                            $t_color  = $v['type'] === 'freeship' ? '#0ea5e9' : ($v['type'] === 'percent' ? '#f97316' : '#8b5cf6');
                            // Tinh discount preview
                            $d = 0;
                            if ($v['type'] === 'percent') { $d = (int)($subtotal * $v['value'] / 100); if ($v['max_discount']) $d = min($d, (int)$v['max_discount']); }
                            elseif ($v['type'] === 'fixed') { $d = min((int)$v['value'], $subtotal); }
                            elseif ($v['type'] === 'freeship') { $d = (int)($v['max_discount'] ?? $delivery_fee); }
                        ?>
                        <div onclick="<?= $can_use ? "pickVoucher('" . esc_js($v['code']) . "')" : '' ?>"
                             style="border:2px solid <?= $can_use ? '#e5e7eb' : '#f3f4f6' ?>;border-radius:14px;padding:0.75rem 1rem;display:flex;align-items:center;gap:0.75rem;cursor:<?= $can_use ? 'pointer' : 'not-allowed' ?>;transition:all 0.15s;<?= !$can_use ? 'opacity:0.5' : '' ?>"
                             class="voucher-card" id="vc-<?= esc_attr($v['code']) ?>">
                            <!-- Icon -->
                            <div style="width:38px;height:38px;border-radius:10px;background:<?= $t_color ?>18;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0"><?= $t_icon ?></div>
                            <!-- Info -->
                            <div style="flex:1;min-width:0">
                                <div style="display:flex;align-items:center;gap:0.4rem;margin-bottom:0.15rem">
                                    <span style="font-family:monospace;font-weight:900;font-size:0.875rem;color:<?= $can_use ? '#ee2624' : '#9ca3af' ?>"><?= esc_html($v['code']) ?></span>
                                    <?php if ($can_use): ?>
                                    <span style="font-size:0.65rem;font-weight:700;background:<?= $t_color ?>18;color:<?= $t_color ?>;padding:1px 6px;border-radius:999px">
                                        <?= $v['type'] === 'freeship' ? 'Freeship' : ($v['type'] === 'percent' ? 'Giảm ' . $v['value'] . '%' : 'Giảm ' . cica_format_price((int)$v['value'])) ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                                <p style="font-size:0.78rem;color:#6b7280;margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= esc_html($v['name']) ?></p>
                            </div>
                            <!-- Right -->
                            <div style="text-align:right;flex-shrink:0">
                                <?php if ($can_use && $d > 0): ?>
                                <span style="font-weight:800;font-size:0.9rem;color:#16a34a">-<?= cica_format_price($d) ?></span>
                                <?php elseif (!$can_use): ?>
                                <span style="font-size:0.72rem;color:#ef4444;font-weight:600"><?= esc_html($reason) ?></span>
                                <?php endif; ?>
                            </div>
                            <!-- Check indicator -->
                            <div class="vc-check" style="width:20px;height:20px;border-radius:50%;border:2px solid #e5e7eb;flex-shrink:0;display:flex;align-items:center;justify-content:center;transition:all 0.15s"></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Applied bar -->
                    <div id="applied-bar" style="display:none;background:#f0fdf4;border:1px solid #86efac;border-radius:12px;padding:0.65rem 1rem;margin-bottom:0.75rem;align-items:center;justify-content:space-between">
                        <span style="font-size:0.875rem;font-weight:700;color:#16a34a">
                            <i class="fas fa-check-circle"></i> <span id="applied-text"></span>
                        </span>
                        <button type="button" onclick="removeVoucher()" style="background:none;border:none;color:#9ca3af;cursor:pointer;font-size:0.8rem;font-weight:600;padding:0">
                            <i class="fas fa-times"></i> Bỏ
                        </button>
                    </div>

                    <!-- Manual input -->
                    <div style="display:flex;gap:0.5rem">
                        <input type="text" id="voucher-input" placeholder="Nhập mã khác..."
                               style="flex:1;padding:0.65rem 1rem;border:1.5px solid #e5e7eb;border-radius:12px;font-size:0.875rem;outline:none;font-family:monospace;text-transform:uppercase;box-sizing:border-box"
                               onfocus="this.style.borderColor='#ee2624'" onblur="this.style.borderColor='#e5e7eb'"
                               onkeydown="if(event.key==='Enter'){event.preventDefault();applyVoucherManual()}">
                        <button type="button" onclick="applyVoucherManual()"
                                style="background:#ee2624;color:white;border:none;border-radius:12px;padding:0.65rem 1.1rem;font-weight:700;font-size:0.875rem;cursor:pointer;white-space:nowrap">
                            Áp dụng
                        </button>
                    </div>
                    <div id="voucher-result" style="margin-top:0.5rem"></div>

                    <input type="hidden" name="voucher_code" id="voucher-code-input">
                    <input type="hidden" name="discount_amount" id="discount-amount-input" value="0">
                    <input type="hidden" name="shipping_discount" id="shipping-discount-input" value="0">
                </div>

                <!-- Phuong thuc thanh toan -->
                <div style="background:white;border-radius:20px;border:1px solid #e5e7eb;padding:1.5rem">
                    <h2 style="font-size:1rem;font-weight:800;color:#111827;margin:0 0 1rem;display:flex;align-items:center;gap:0.5rem">
                        <i class="fas fa-credit-card" style="color:#3b82f6"></i> Phương Thức Thanh Toán
                    </h2>
                    <div style="display:flex;flex-direction:column;gap:0.75rem">
                        <!-- COD -->
                        <label style="display:flex;align-items:center;gap:0.875rem;border:2px solid #e5e7eb;border-radius:16px;padding:1rem;cursor:pointer;transition:all 0.15s" id="pay-cod-label"
                               onclick="selectPayment('cod')">
                            <input type="radio" name="payment_method" value="cod" checked style="accent-color:#ee2624" onchange="selectPayment('cod')">
                            <div style="width:36px;height:36px;background:#f0fdf4;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                                <i class="fas fa-money-bill-wave" style="color:#22c55e"></i>
                            </div>
                            <div>
                                <span style="font-size:0.875rem;font-weight:700;color:#374151;display:block">Tiền mặt COD</span>
                                <span style="font-size:0.75rem;color:#9ca3af">Thanh toán khi nhận hàng</span>
                            </div>
                        </label>

                        <!-- QR Transfer -->
                        <label style="display:flex;align-items:center;gap:0.875rem;border:2px solid #e5e7eb;border-radius:16px;padding:1rem;cursor:pointer;transition:all 0.15s" id="pay-qr-label"
                               onclick="selectPayment('bank_transfer')">
                            <input type="radio" name="payment_method" value="bank_transfer" style="accent-color:#0ea5e9" onchange="selectPayment('bank_transfer')">
                            <div style="width:36px;height:36px;background:#e0f2fe;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                                <i class="fas fa-qrcode" style="color:#0ea5e9"></i>
                            </div>
                            <div>
                                <span style="font-size:0.875rem;font-weight:700;color:#374151;display:block">Chuyển khoản QR</span>
                                <span style="font-size:0.75rem;color:#9ca3af">Quét mã QR — xác nhận ngay</span>
                            </div>
                        </label>
                    </div>

                    <!-- QR Panel (hiện khi chọn bank_transfer) -->
                    <div id="qr-panel" style="display:none;margin-top:1rem;background:#f0f9ff;border:1px solid #bae6fd;border-radius:14px;padding:1.25rem;text-align:center">
                        <p style="font-size:0.8rem;font-weight:700;color:#0369a1;margin:0 0 0.75rem">
                            <i class="fas fa-info-circle"></i> Quét mã QR để chuyển khoản — đơn hàng sẽ được xác nhận sau khi thanh toán
                        </p>
                        <!-- QR từ VietQR API -->
                        <img id="qr-img"
                             src="https://img.vietqr.io/image/<?= esc_attr(get_option('cica_bank_id','MB')) ?>-<?= esc_attr(get_option('cica_bank_account','0123456789')) ?>-compact2.png?amount=<?= $total ?>&addInfo=CICAFOOD+<?= get_current_user_id() ?>&accountName=<?= urlencode(get_option('cica_bank_name','CICAFOOD')) ?>"
                             alt="QR Chuyển khoản"
                             style="width:220px;height:220px;border-radius:12px;border:3px solid white;box-shadow:0 4px 16px rgba(0,0,0,0.1);margin:0 auto 0.75rem;display:block"
                             id="qr-img-static">
                        <div style="background:white;border-radius:10px;padding:0.75rem;text-align:left;font-size:0.8rem;margin-top:0.5rem">
                            <div style="display:flex;justify-content:space-between;margin-bottom:0.4rem">
                                <span style="color:#6b7280">Ngân hàng</span>
                                <strong><?= esc_html(get_option('cica_bank_id', 'MB')) ?></strong>
                            </div>
                            <div style="display:flex;justify-content:space-between;margin-bottom:0.4rem">
                                <span style="color:#6b7280">Số tài khoản</span>
                                <strong style="font-family:monospace"><?= esc_html(get_option('cica_bank_account', '0123456789')) ?></strong>
                            </div>
                            <div style="display:flex;justify-content:space-between;margin-bottom:0.4rem">
                                <span style="color:#6b7280">Chủ tài khoản</span>
                                <strong><?= esc_html(get_option('cica_bank_name', 'CICAFOOD')) ?></strong>
                            </div>
                            <div style="display:flex;justify-content:space-between;margin-bottom:0.4rem">
                                <span style="color:#6b7280">Số tiền</span>
                                <strong style="color:#ee2624" id="qr-amount"><?= cica_format_price($total) ?></strong>
                            </div>
                            <div style="display:flex;justify-content:space-between">
                                <span style="color:#6b7280">Nội dung CK</span>
                                <strong style="font-family:monospace;color:#0369a1">CICAFOOD <?= get_current_user_id() ?></strong>
                            </div>
                        </div>
                        <p style="font-size:0.75rem;color:#6b7280;margin:0.75rem 0 0">
                            <i class="fas fa-shield-halved" style="color:#16a34a"></i> Đơn hàng sẽ được xử lý sau khi merchant xác nhận thanh toán
                        </p>
                    </div>
                </div>
            </div>

            <!-- RIGHT: Order Summary -->
            <div style="position:sticky;top:80px">
                <div style="background:white;border-radius:20px;border:1px solid #e5e7eb;padding:1.5rem">
                    <!-- Restaurant info -->
                    <div style="display:flex;align-items:center;gap:0.75rem;padding-bottom:1rem;border-bottom:1px solid #f3f4f6;margin-bottom:1rem">
                        <div style="width:48px;height:48px;border-radius:12px;overflow:hidden;flex-shrink:0">
                            <img src="<?= esc_url($restaurant['image'] ?: '') ?>" alt="" style="width:100%;height:100%;object-fit:cover">
                        </div>
                        <div>
                            <h3 style="font-weight:700;font-size:0.9rem;color:#111827;margin:0"><?= esc_html($restaurant['name']) ?></h3>
                            <p style="font-size:0.75rem;color:#9ca3af;margin:0"><?= count($cart) ?> loại món</p>
                        </div>
                    </div>

                    <!-- Cart items -->
                    <div style="display:flex;flex-direction:column;gap:0.5rem;margin-bottom:1rem;max-height:200px;overflow-y:auto">
                        <?php foreach ($cart as $item_id => $item): ?>
                        <div style="display:flex;align-items:center;justify-content:space-between;font-size:0.875rem">
                            <div style="display:flex;align-items:center;gap:0.5rem">
                                <span style="background:#fef2f2;color:#ee2624;font-weight:700;font-size:0.75rem;padding:2px 6px;border-radius:6px"><?= (int)$item['quantity'] ?>x</span>
                                <span style="color:#374151;max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= esc_html($item['name']) ?></span>
                            </div>
                            <span style="font-weight:600;color:#374151;flex-shrink:0"><?= cica_format_price((int)$item['price'] * (int)$item['quantity']) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Totals -->
                    <div style="border-top:1px dashed #e5e7eb;padding-top:0.875rem;display:flex;flex-direction:column;gap:0.5rem">
                        <div style="display:flex;justify-content:space-between;font-size:0.875rem;color:#6b7280">
                            <span>Tạm tính</span><span><?= cica_format_price($subtotal) ?></span>
                        </div>
                        <div style="display:flex;justify-content:space-between;font-size:0.875rem;color:#6b7280">
                            <span>Phí giao hàng</span>
                            <span id="delivery-display" <?= $delivery_fee === 0 ? 'style="color:#22c55e;font-weight:600"' : '' ?>>
                                <?= $delivery_fee === 0 ? 'Miễn phí' : cica_format_price($delivery_fee) ?>
                            </span>
                        </div>
                        <div style="display:flex;justify-content:space-between;font-size:0.875rem;color:#6b7280">
                            <span>Phí dịch vụ (2%)</span><span><?= cica_format_price($service_fee) ?></span>
                        </div>
                        <div id="discount-row" style="display:none;justify-content:space-between;font-size:0.875rem;color:#16a34a;font-weight:600">
                            <span><i class="fas fa-tag"></i> Voucher</span>
                            <span id="discount-display">-0đ</span>
                        </div>
                        <div style="display:flex;justify-content:space-between;font-weight:900;font-size:1.1rem;border-top:1px solid #e5e7eb;padding-top:0.75rem;margin-top:0.25rem">
                            <span style="color:#111827">Tổng thanh toán</span>
                            <span id="total-final" style="color:#ee2624"><?= cica_format_price($total) ?></span>
                        </div>
                    </div>

                    <button type="submit" name="place_order"
                            style="width:100%;background:#ee2624;color:white;border:none;border-radius:14px;padding:1rem;font-weight:900;font-size:1rem;cursor:pointer;margin-top:1.25rem;display:flex;align-items:center;justify-content:center;gap:0.5rem;transition:background 0.2s"
                            onmouseover="this.style.background='#c41e1c'" onmouseout="this.style.background='#ee2624'">
                        <i class="fas fa-check-circle"></i> Đặt Hàng Ngay
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
.voucher-card:hover { border-color: #ee2624 !important; background: #fef9f9; }
.voucher-card.selected { border-color: #16a34a !important; background: #f0fdf4; }
.voucher-card.selected .vc-check { background: #16a34a; border-color: #16a34a; }
@media (max-width: 768px) {
    form > div[style*="grid-template-columns"] { grid-template-columns: 1fr !important; }
}
</style>

<script>
const SUBTOTAL  = <?= $subtotal ?>;
const SHIP_FEE  = <?= $delivery_fee ?>;
const SVC_FEE   = <?= $service_fee ?>;
const AJAX_URL  = '<?= $ajax_url ?>';
const NONCE     = '<?= $nonce ?>';
let currentDiscount    = 0;
let currentShipDiscount= 0;
let selectedCode       = '';

function pickVoucher(code) {
    // Deselect all
    document.querySelectorAll('.voucher-card').forEach(c => {
        c.classList.remove('selected');
        const chk = c.querySelector('.vc-check');
        if (chk) { chk.innerHTML = ''; chk.style.background = ''; chk.style.borderColor = '#e5e7eb'; }
    });

    if (selectedCode === code) {
        // Toggle off
        removeVoucher();
        return;
    }

    // Select
    const card = document.getElementById('vc-' + code);
    if (card) {
        card.classList.add('selected');
        const chk = card.querySelector('.vc-check');
        if (chk) { chk.innerHTML = '<i class="fas fa-check" style="color:white;font-size:0.6rem"></i>'; }
    }

    // Apply via AJAX
    fetch(AJAX_URL, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=cica_apply_voucher&code=${encodeURIComponent(code)}&subtotal=${SUBTOTAL}&nonce=${NONCE}`
    }).then(r => r.json()).then(d => {
        if (d.success) {
            selectedCode = code;
            document.getElementById('voucher-code-input').value = code;
            if (d.voucher.type === 'freeship') {
                currentShipDiscount = d.discount_amount;
                currentDiscount = 0;
                document.getElementById('shipping-discount-input').value = currentShipDiscount;
                document.getElementById('discount-amount-input').value = 0;
            } else {
                currentDiscount = d.discount_amount;
                currentShipDiscount = 0;
                document.getElementById('discount-amount-input').value = currentDiscount;
                document.getElementById('shipping-discount-input').value = 0;
            }
            showAppliedBar(code, d.discount_formatted);
            recalcTotal();
        } else {
            if (typeof showCicaToast === 'function') showCicaToast('error', d.message);
        }
    });
}

function applyVoucherManual() {
    const code = document.getElementById('voucher-input').value.trim().toUpperCase();
    if (!code) return;
    const res = document.getElementById('voucher-result');
    res.innerHTML = '<span style="font-size:0.8rem;color:#6b7280"><i class="fas fa-spinner fa-spin"></i> Đang kiểm tra...</span>';

    fetch(AJAX_URL, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=cica_apply_voucher&code=${encodeURIComponent(code)}&subtotal=${SUBTOTAL}&nonce=${NONCE}`
    }).then(r => r.json()).then(d => {
        if (d.success) {
            res.innerHTML = '';
            selectedCode = code;
            document.getElementById('voucher-code-input').value = code;
            if (d.voucher.type === 'freeship') {
                currentShipDiscount = d.discount_amount;
                currentDiscount = 0;
                document.getElementById('shipping-discount-input').value = currentShipDiscount;
                document.getElementById('discount-amount-input').value = 0;
            } else {
                currentDiscount = d.discount_amount;
                currentShipDiscount = 0;
                document.getElementById('discount-amount-input').value = currentDiscount;
                document.getElementById('shipping-discount-input').value = 0;
            }
            showAppliedBar(code, d.discount_formatted);
            recalcTotal();
            // Deselect all cards
            document.querySelectorAll('.voucher-card').forEach(c => c.classList.remove('selected'));
        } else {
            res.innerHTML = `<div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:8px;padding:0.5rem 0.75rem;font-size:0.8rem;color:#dc2626;font-weight:600;margin-top:0.25rem">
                <i class="fas fa-times-circle"></i> ${d.message}
            </div>`;
        }
    });
}

function showAppliedBar(code, formatted) {
    const bar = document.getElementById('applied-bar');
    document.getElementById('applied-text').textContent = 'Đã áp dụng "' + code + '" — Tiết kiệm ' + formatted;
    bar.style.display = 'flex';
    const discRow = document.getElementById('discount-row');
    discRow.style.display = 'flex';
    document.getElementById('discount-display').textContent = '-' + formatted;
}

function removeVoucher() {
    selectedCode = '';
    currentDiscount = 0;
    currentShipDiscount = 0;
    document.getElementById('voucher-code-input').value = '';
    document.getElementById('discount-amount-input').value = 0;
    document.getElementById('shipping-discount-input').value = 0;
    document.getElementById('applied-bar').style.display = 'none';
    document.getElementById('discount-row').style.display = 'none';
    document.getElementById('voucher-input').value = '';
    document.getElementById('voucher-result').innerHTML = '';
    document.querySelectorAll('.voucher-card').forEach(c => {
        c.classList.remove('selected');
        const chk = c.querySelector('.vc-check');
        if (chk) { chk.innerHTML = ''; chk.style.background = ''; chk.style.borderColor = '#e5e7eb'; }
    });
    recalcTotal();
}

function recalcTotal() {
    const newShip = Math.max(0, SHIP_FEE - currentShipDiscount);
    const total   = Math.max(0, SUBTOTAL + newShip + SVC_FEE - currentDiscount);
    document.getElementById('total-final').textContent = total.toLocaleString('vi-VN') + 'đ';
    const dd = document.getElementById('delivery-display');
    dd.textContent = newShip === 0 ? 'Miễn phí' : newShip.toLocaleString('vi-VN') + 'đ';
    dd.style.color = newShip === 0 ? '#22c55e' : '';
    dd.style.fontWeight = newShip === 0 ? '600' : '';
    // Cập nhật QR khi tổng tiền thay đổi
    updateQRAmount(total);
}

function selectPayment(method) {
    const codLabel = document.getElementById('pay-cod-label');
    const qrLabel  = document.getElementById('pay-qr-label');
    const qrPanel  = document.getElementById('qr-panel');
    if (method === 'cod') {
        codLabel.style.borderColor = '#ee2624';
        codLabel.style.background  = '#fef2f2';
        qrLabel.style.borderColor  = '#e5e7eb';
        qrLabel.style.background   = '';
        qrPanel.style.display      = 'none';
        document.querySelector('input[value="cod"]').checked = true;
    } else {
        qrLabel.style.borderColor  = '#0ea5e9';
        qrLabel.style.background   = '#f0f9ff';
        codLabel.style.borderColor = '#e5e7eb';
        codLabel.style.background  = '';
        qrPanel.style.display      = 'block';
        document.querySelector('input[value="bank_transfer"]').checked = true;
        // Cập nhật QR với tổng tiền hiện tại
        const newShip = Math.max(0, SHIP_FEE - currentShipDiscount);
        const total   = Math.max(0, SUBTOTAL + newShip + SVC_FEE - currentDiscount);
        updateQRAmount(total);
    }
}

function updateQRAmount(amount) {
    const img = document.getElementById('qr-img');
    if (!img) return;
    const bankId  = '<?= esc_js(get_option('cica_bank_id','MB')) ?>';
    const account = '<?= esc_js(get_option('cica_bank_account','0123456789')) ?>';
    const name    = encodeURIComponent('<?= esc_js(get_option('cica_bank_name','CICAFOOD')) ?>');
    const userId  = <?= get_current_user_id() ?>;
    img.src = `https://img.vietqr.io/image/${bankId}-${account}-compact2.png?amount=${amount}&addInfo=CICAFOOD+${userId}&accountName=${name}`;
    const amountEl = document.getElementById('qr-amount');
    if (amountEl) amountEl.textContent = amount.toLocaleString('vi-VN') + 'đ';
}

// Init: set COD selected style
document.addEventListener('DOMContentLoaded', function() {
    selectPayment('cod');
});

// Auto-apply voucher tu URL
<?php if ($url_voucher): ?>
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('voucher-input').value = '<?= esc_js($url_voucher) ?>';
    applyVoucherManual();
});
<?php endif; ?>
</script>
