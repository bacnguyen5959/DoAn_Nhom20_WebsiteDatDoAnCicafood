<?php
defined('ABSPATH') || exit;
if (!session_id()) session_start();

$user_id      = get_current_user_id();
$site_url     = get_site_url();
$ajax_url     = admin_url('admin-ajax.php');
$nonce        = wp_create_nonce('cicafood_nonce');

// Lấy subtotal giỏ hàng hiện tại để check điều kiện voucher
$cart_subtotal    = Cicafood_Cart::subtotal();
$cart_rest_id     = Cicafood_Cart::restaurant_id();

// Voucher công khai (toàn hệ thống)
$public_vouchers  = Cicafood_Voucher::get_available($cart_subtotal, 0, $user_id);
// Voucher của nhà hàng trong giỏ (nếu có)
$rest_vouchers    = $cart_rest_id ? Cicafood_Voucher::get_available($cart_subtotal, $cart_rest_id, $user_id) : [];
// Ví voucher của user
$wallet_vouchers  = Cicafood_Voucher::get_wallet($user_id);

// Merge và dedup
$all_vouchers = [];
$seen_ids = [];
foreach (array_merge($rest_vouchers, $public_vouchers) as $v) {
    if (!in_array($v['id'], $seen_ids)) {
        $all_vouchers[] = $v;
        $seen_ids[] = $v['id'];
    }
}

// Tách: dùng được vs không dùng được
$usable   = array_filter($all_vouchers, fn($v) => $v['is_valid']);
$unusable = array_filter($all_vouchers, fn($v) => !$v['is_valid']);

function voucher_type_label(string $type): array {
    return match($type) {
        'freeship' => ['🚚 Freeship',    '#0ea5e9', '#e0f2fe'],
        'percent'  => ['🏷️ Giảm %',     '#f97316', '#fff7ed'],
        'fixed'    => ['💰 Giảm tiền',   '#8b5cf6', '#f5f3ff'],
        default    => ['🎁 Voucher',     '#6b7280', '#f3f4f6'],
    };
}
?>

<div style="max-width:900px;margin:0 auto;padding:1.5rem 1rem">

    <!-- Page header -->
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:0.75rem">
        <div>
            <h1 style="font-size:1.4rem;font-weight:900;color:#111827;margin:0;display:flex;align-items:center;gap:0.5rem">
                <i class="fas fa-ticket" style="color:#ee2624"></i> Kho Voucher
            </h1>
            <p style="font-size:0.85rem;color:#6b7280;margin:0.3rem 0 0">
                <?php if ($cart_subtotal > 0): ?>
                    Đang xem voucher cho đơn hàng <strong style="color:#ee2624"><?= cica_format_price($cart_subtotal) ?></strong>
                <?php else: ?>
                    Thêm món vào giỏ để xem voucher áp dụng được
                <?php endif; ?>
            </p>
        </div>
        <?php if ($cart_subtotal > 0): ?>
        <a href="<?= esc_url($site_url . '/thanh-toan/') ?>"
           style="display:inline-flex;align-items:center;gap:0.5rem;background:#ee2624;color:white;padding:0.6rem 1.25rem;border-radius:10px;font-weight:700;font-size:0.875rem;text-decoration:none">
            <i class="fas fa-shopping-bag"></i> Thanh toán ngay
        </a>
        <?php endif; ?>
    </div>

    <!-- Nhập mã thủ công -->
    <div style="background:white;border-radius:16px;border:1px solid #e5e7eb;padding:1.25rem;margin-bottom:1.5rem">
        <p style="font-size:0.875rem;font-weight:700;color:#374151;margin:0 0 0.75rem">Nhập mã voucher</p>
        <div style="display:flex;gap:0.5rem">
            <input type="text" id="manual-voucher-input" placeholder="Nhập mã voucher..."
                   style="flex:1;padding:0.65rem 1rem;border:1.5px solid #e5e7eb;border-radius:10px;font-size:0.875rem;outline:none;font-family:monospace;text-transform:uppercase"
                   onfocus="this.style.borderColor='#ee2624'" onblur="this.style.borderColor='#e5e7eb'">
            <button onclick="applyManualVoucher()"
                    style="background:#ee2624;color:white;border:none;border-radius:10px;padding:0.65rem 1.25rem;font-weight:700;font-size:0.875rem;cursor:pointer;white-space:nowrap">
                Áp dụng
            </button>
        </div>
        <div id="manual-voucher-result" style="margin-top:0.5rem"></div>
    </div>

    <!-- VOUCHER DÙNG ĐƯỢC -->
    <?php if (!empty($usable)): ?>
    <div style="margin-bottom:2rem">
        <h2 style="font-size:1rem;font-weight:700;color:#16a34a;margin:0 0 0.75rem;display:flex;align-items:center;gap:0.5rem">
            <i class="fas fa-circle-check"></i> Có thể áp dụng (<?= count($usable) ?>)
        </h2>
        <div style="display:flex;flex-direction:column;gap:0.75rem">
            <?php foreach ($usable as $v):
                [$type_label, $type_color, $type_bg] = voucher_type_label($v['type']);
            ?>
            <div style="background:white;border-radius:16px;border:2px solid #bbf7d0;overflow:hidden;display:flex;position:relative"
                 class="voucher-card-usable">
                <!-- Left accent -->
                <div style="width:8px;background:linear-gradient(180deg,#16a34a,#22c55e);flex-shrink:0"></div>
                <!-- Dashed divider -->
                <div style="width:1px;background:repeating-linear-gradient(180deg,#d1fae5 0,#d1fae5 6px,transparent 6px,transparent 12px);flex-shrink:0;margin:12px 0"></div>

                <div style="flex:1;padding:1rem 1.25rem;display:flex;align-items:center;gap:1rem;flex-wrap:wrap">
                    <!-- Icon + type -->
                    <div style="width:52px;height:52px;border-radius:14px;background:<?= $type_bg ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:1.4rem">
                        <?= explode(' ', $type_label)[0] ?>
                    </div>
                    <!-- Info -->
                    <div style="flex:1;min-width:0">
                        <div style="display:flex;align-items:center;gap:0.5rem;flex-wrap:wrap;margin-bottom:0.2rem">
                            <span style="font-family:monospace;font-weight:900;font-size:1rem;color:#111827;background:#f0fdf4;padding:2px 10px;border-radius:6px;border:1px dashed #86efac">
                                <?= esc_html($v['code']) ?>
                            </span>
                            <span style="font-size:0.7rem;font-weight:700;padding:2px 8px;border-radius:999px;background:<?= $type_bg ?>;color:<?= $type_color ?>">
                                <?= $type_label ?>
                            </span>
                        </div>
                        <p style="font-weight:700;font-size:0.9rem;color:#111827;margin:0 0 0.2rem"><?= esc_html($v['name']) ?></p>
                        <div style="display:flex;gap:1rem;font-size:0.75rem;color:#6b7280;flex-wrap:wrap">
                            <?php if ($v['type'] === 'freeship'): ?>
                            <span><i class="fas fa-motorcycle" style="color:#0ea5e9"></i> Miễn phí vận chuyển</span>
                            <?php elseif ($v['type'] === 'percent'): ?>
                            <span><i class="fas fa-percent" style="color:#f97316"></i> Giảm <?= $v['value'] ?>% (tối đa <?= cica_format_price((int)$v['max_discount']) ?>)</span>
                            <?php else: ?>
                            <span><i class="fas fa-tag" style="color:#8b5cf6"></i> Giảm <?= cica_format_price((int)$v['value']) ?></span>
                            <?php endif; ?>
                            <span><i class="fas fa-receipt" style="color:#6b7280"></i> Đơn từ <?= cica_format_price((int)$v['min_order']) ?></span>
                            <?php if ($v['end_date']): ?>
                            <span><i class="fas fa-calendar" style="color:#6b7280"></i> HSD: <?= date('d/m/Y', strtotime($v['end_date'])) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <!-- Discount preview + action -->
                    <div style="text-align:right;flex-shrink:0">
                        <?php if ($v['discount_amount'] > 0): ?>
                        <p style="font-size:1.1rem;font-weight:900;color:#16a34a;margin:0 0 0.4rem">
                            -<?= cica_format_price($v['discount_amount']) ?>
                        </p>
                        <?php endif; ?>
                        <div style="display:flex;gap:0.4rem;justify-content:flex-end">
                            <?php if ($cart_subtotal > 0): ?>
                            <button onclick="applyVoucherCode('<?= esc_js($v['code']) ?>')"
                                    style="background:#16a34a;color:white;border:none;border-radius:8px;padding:0.45rem 0.875rem;font-weight:700;font-size:0.8rem;cursor:pointer">
                                Dùng ngay
                            </button>
                            <?php endif; ?>
                            <button onclick="saveVoucher(<?= (int)$v['id'] ?>, this)"
                                    style="background:#f0fdf4;color:#16a34a;border:1px solid #86efac;border-radius:8px;padding:0.45rem 0.875rem;font-weight:700;font-size:0.8rem;cursor:pointer">
                                <i class="fas fa-bookmark"></i> Lưu
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- VOUCHER KHÔNG DÙNG ĐƯỢC -->
    <?php if (!empty($unusable)): ?>
    <div style="margin-bottom:2rem">
        <h2 style="font-size:1rem;font-weight:700;color:#9ca3af;margin:0 0 0.75rem;display:flex;align-items:center;gap:0.5rem">
            <i class="fas fa-lock"></i> Chưa đủ điều kiện (<?= count($unusable) ?>)
        </h2>
        <div style="display:flex;flex-direction:column;gap:0.75rem">
            <?php foreach ($unusable as $v):
                [$type_label, $type_color, $type_bg] = voucher_type_label($v['type']);
            ?>
            <div style="background:#f9fafb;border-radius:16px;border:1.5px solid #e5e7eb;overflow:hidden;display:flex;opacity:0.75">
                <div style="width:8px;background:#d1d5db;flex-shrink:0"></div>
                <div style="width:1px;background:repeating-linear-gradient(180deg,#e5e7eb 0,#e5e7eb 6px,transparent 6px,transparent 12px);flex-shrink:0;margin:12px 0"></div>
                <div style="flex:1;padding:1rem 1.25rem;display:flex;align-items:center;gap:1rem;flex-wrap:wrap">
                    <div style="width:52px;height:52px;border-radius:14px;background:#f3f4f6;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:1.4rem;filter:grayscale(1)">
                        <?= explode(' ', $type_label)[0] ?>
                    </div>
                    <div style="flex:1;min-width:0">
                        <div style="display:flex;align-items:center;gap:0.5rem;flex-wrap:wrap;margin-bottom:0.2rem">
                            <span style="font-family:monospace;font-weight:900;font-size:1rem;color:#9ca3af;background:#f3f4f6;padding:2px 10px;border-radius:6px;border:1px dashed #d1d5db">
                                <?= esc_html($v['code']) ?>
                            </span>
                            <span style="font-size:0.7rem;font-weight:700;padding:2px 8px;border-radius:999px;background:#f3f4f6;color:#9ca3af">
                                <?= $type_label ?>
                            </span>
                        </div>
                        <p style="font-weight:700;font-size:0.9rem;color:#9ca3af;margin:0 0 0.2rem"><?= esc_html($v['name']) ?></p>
                        <div style="display:flex;gap:1rem;font-size:0.75rem;color:#9ca3af;flex-wrap:wrap">
                            <span><i class="fas fa-receipt"></i> Đơn từ <?= cica_format_price((int)$v['min_order']) ?></span>
                            <?php if ($v['end_date']): ?>
                            <span><i class="fas fa-calendar"></i> HSD: <?= date('d/m/Y', strtotime($v['end_date'])) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div style="text-align:right;flex-shrink:0">
                        <p style="font-size:0.75rem;color:#ef4444;font-weight:600;margin:0 0 0.4rem">
                            <i class="fas fa-triangle-exclamation"></i> <?= esc_html($v['reason']) ?>
                        </p>
                        <button onclick="saveVoucher(<?= (int)$v['id'] ?>, this)"
                                style="background:#f3f4f6;color:#9ca3af;border:1px solid #e5e7eb;border-radius:8px;padding:0.45rem 0.875rem;font-weight:700;font-size:0.8rem;cursor:pointer">
                            <i class="fas fa-bookmark"></i> Lưu
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- VÍ VOUCHER ĐÃ LƯU -->
    <?php if (!empty($wallet_vouchers)): ?>
    <div style="margin-bottom:2rem">
        <h2 style="font-size:1rem;font-weight:700;color:#374151;margin:0 0 0.75rem;display:flex;align-items:center;gap:0.5rem">
            <i class="fas fa-wallet" style="color:#8b5cf6"></i> Ví voucher của tôi (<?= count($wallet_vouchers) ?>)
        </h2>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:0.75rem">
            <?php foreach ($wallet_vouchers as $v):
                $expired = $v['end_date'] && strtotime($v['end_date']) < time();
                $used    = (int)$v['is_used'];
                [$type_label, $type_color, $type_bg] = voucher_type_label($v['type']);
            ?>
            <div style="background:white;border-radius:14px;border:1.5px solid <?= ($expired || $used) ? '#e5e7eb' : '#ddd6fe' ?>;padding:1rem;<?= ($expired || $used) ? 'opacity:0.6' : '' ?>">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.5rem">
                    <span style="font-family:monospace;font-weight:900;font-size:0.9rem;color:<?= ($expired || $used) ? '#9ca3af' : '#7c3aed' ?>;background:<?= ($expired || $used) ? '#f3f4f6' : '#f5f3ff' ?>;padding:2px 8px;border-radius:6px">
                        <?= esc_html($v['code']) ?>
                    </span>
                    <?php if ($used): ?>
                    <span style="font-size:0.7rem;font-weight:700;background:#f3f4f6;color:#9ca3af;padding:2px 8px;border-radius:999px">Đã dùng</span>
                    <?php elseif ($expired): ?>
                    <span style="font-size:0.7rem;font-weight:700;background:#fee2e2;color:#dc2626;padding:2px 8px;border-radius:999px">Hết hạn</span>
                    <?php else: ?>
                    <span style="font-size:0.7rem;font-weight:700;background:#f5f3ff;color:#7c3aed;padding:2px 8px;border-radius:999px">Còn hạn</span>
                    <?php endif; ?>
                </div>
                <p style="font-weight:700;font-size:0.875rem;color:#374151;margin:0 0 0.25rem"><?= esc_html($v['name']) ?></p>
                <p style="font-size:0.75rem;color:#9ca3af;margin:0">
                    <?php if ($v['end_date']): ?>HSD: <?= date('d/m/Y', strtotime($v['end_date'])) ?><?php endif; ?>
                </p>
                <?php if (!$used && !$expired && $cart_subtotal > 0): ?>
                <button onclick="applyVoucherCode('<?= esc_js($v['code']) ?>')"
                        style="width:100%;margin-top:0.75rem;background:#7c3aed;color:white;border:none;border-radius:8px;padding:0.5rem;font-weight:700;font-size:0.8rem;cursor:pointer">
                    Dùng ngay
                </button>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (empty($all_vouchers) && empty($wallet_vouchers)): ?>
    <div style="text-align:center;padding:4rem 1rem;color:#9ca3af">
        <i class="fas fa-ticket" style="font-size:3rem;margin-bottom:1rem;display:block"></i>
        <p style="font-size:1rem;font-weight:600;margin:0 0 0.5rem">Chưa có voucher nào</p>
        <p style="font-size:0.875rem;margin:0">Quay lại sau để xem ưu đãi mới nhất!</p>
    </div>
    <?php endif; ?>
</div>

<!-- Modal xác nhận áp dụng voucher -->
<div id="voucher-apply-modal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.5);align-items:center;justify-content:center">
    <div style="background:white;border-radius:20px;padding:1.5rem;max-width:400px;width:90%;text-align:center">
        <div style="width:56px;height:56px;background:#f0fdf4;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;font-size:1.5rem">✅</div>
        <h3 style="font-weight:900;font-size:1.1rem;margin:0 0 0.5rem">Áp dụng voucher?</h3>
        <p style="color:#6b7280;font-size:0.875rem;margin:0 0 1.25rem">Mã <strong id="modal-voucher-code" style="color:#ee2624;font-family:monospace"></strong> sẽ được áp dụng khi thanh toán</p>
        <div style="display:flex;gap:0.75rem">
            <button onclick="document.getElementById('voucher-apply-modal').style.display='none'"
                    style="flex:1;background:#f3f4f6;color:#374151;border:none;border-radius:10px;padding:0.75rem;font-weight:700;cursor:pointer">Huỷ</button>
            <button id="modal-confirm-btn"
                    style="flex:1;background:#ee2624;color:white;border:none;border-radius:10px;padding:0.75rem;font-weight:700;cursor:pointer">Đến thanh toán</button>
        </div>
    </div>
</div>

<script>
const AJAX_URL = '<?= $ajax_url ?>';
const NONCE    = '<?= $nonce ?>';
const SITE_URL = '<?= $site_url ?>';

function applyVoucherCode(code) {
    document.getElementById('modal-voucher-code').textContent = code;
    document.getElementById('modal-confirm-btn').onclick = function() {
        window.location.href = SITE_URL + '/thanh-toan/?voucher=' + encodeURIComponent(code);
    };
    const modal = document.getElementById('voucher-apply-modal');
    modal.style.display = 'flex';
}

function applyManualVoucher() {
    const code = document.getElementById('manual-voucher-input').value.trim().toUpperCase();
    if (!code) return;
    const res = document.getElementById('manual-voucher-result');
    res.innerHTML = '<span style="font-size:0.8rem;color:#6b7280"><i class="fas fa-spinner fa-spin"></i> Đang kiểm tra...</span>';

    fetch(AJAX_URL, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=cica_apply_voucher&code=${encodeURIComponent(code)}&subtotal=<?= $cart_subtotal ?>&nonce=${NONCE}`
    }).then(r => r.json()).then(d => {
        if (d.success) {
            res.innerHTML = `<div style="background:#f0fdf4;border:1px solid #86efac;border-radius:8px;padding:0.5rem 0.75rem;font-size:0.8rem;color:#16a34a;font-weight:600;display:flex;align-items:center;gap:0.5rem">
                <i class="fas fa-check-circle"></i> ${d.message} — Tiết kiệm <strong>${d.discount_formatted}</strong>
                <button onclick="applyVoucherCode('${code}')" style="margin-left:auto;background:#16a34a;color:white;border:none;border-radius:6px;padding:3px 10px;font-weight:700;font-size:0.75rem;cursor:pointer">Dùng ngay</button>
            </div>`;
        } else {
            res.innerHTML = `<div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:8px;padding:0.5rem 0.75rem;font-size:0.8rem;color:#dc2626;font-weight:600">
                <i class="fas fa-times-circle"></i> ${d.message}
            </div>`;
        }
    });
}

function saveVoucher(id, btn) {
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    fetch(AJAX_URL, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=cica_save_voucher&voucher_id=${id}&nonce=${NONCE}`
    }).then(r => r.json()).then(d => {
        if (d.success) {
            btn.innerHTML = '<i class="fas fa-check"></i> Đã lưu';
            btn.style.background = '#f0fdf4';
            btn.style.color = '#16a34a';
        } else {
            btn.innerHTML = '<i class="fas fa-bookmark"></i> Lưu';
            btn.disabled = false;
            if (typeof showCicaToast === 'function') showCicaToast('info', d.message);
        }
    });
}

// Nhập mã bằng Enter
document.getElementById('manual-voucher-input').addEventListener('keydown', e => {
    if (e.key === 'Enter') applyManualVoucher();
});

// Đóng modal khi click ngoài
document.getElementById('voucher-apply-modal').addEventListener('click', function(e) {
    if (e.target === this) this.style.display = 'none';
});

// Auto-fill voucher từ URL param
const urlParams = new URLSearchParams(window.location.search);
const vParam = urlParams.get('code');
if (vParam) {
    document.getElementById('manual-voucher-input').value = vParam.toUpperCase();
    applyManualVoucher();
}
</script>
