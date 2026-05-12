<?php
defined('ABSPATH') || exit;

if (!is_user_logged_in()) {
    echo '<p>Vui lòng <a href="' . esc_url(wp_login_url(get_permalink())) . '">đăng nhập</a> để quản lý voucher.</p>';
    return;
}

global $wpdb;
$rt  = $wpdb->prefix . 'cica_restaurants';
$vt  = $wpdb->prefix . 'cica_vouchers';
$uv  = $wpdb->prefix . 'cica_user_vouchers';

$restaurant = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM $rt WHERE wp_user_id = %d LIMIT 1", get_current_user_id()
), ARRAY_A);

$is_admin = current_user_can('manage_options');

if (!$restaurant && !$is_admin) {
    echo '<div style="text-align:center;padding:3rem;color:#6b7280"><i class="fas fa-store-slash" style="font-size:3rem;margin-bottom:1rem;display:block"></i><p>Bạn chưa có nhà hàng nào được liên kết.</p></div>';
    return;
}

$rid = $restaurant ? (int)$restaurant['id'] : 0;
$where_sql = $is_admin ? '1=1' : $wpdb->prepare('v.restaurant_id = %d', $rid);

$vouchers = $wpdb->get_results(
    "SELECT v.*, (SELECT COUNT(*) FROM $uv uv WHERE uv.voucher_id = v.id AND uv.is_used = 1) AS used_count
     FROM $vt v WHERE $where_sql ORDER BY v.id DESC",
    ARRAY_A
) ?: [];

$ajax_url = admin_url('admin-ajax.php');
$nonce    = wp_create_nonce('cicafood_nonce');
?>

<div style="max-width:1000px;margin:0 auto;padding:1.5rem 1rem">

    <!-- Header -->
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:0.75rem">
        <div>
            <h1 style="font-size:1.4rem;font-weight:900;color:#111827;margin:0;display:flex;align-items:center;gap:0.5rem">
                <i class="fas fa-ticket" style="color:#ee2624"></i> Quản lý Voucher
                <?php if ($restaurant): ?>
                <span style="font-size:0.8rem;font-weight:600;background:#fef2f2;color:#ee2624;padding:2px 10px;border-radius:999px"><?= esc_html($restaurant['name']) ?></span>
                <?php endif; ?>
            </h1>
            <p style="font-size:0.85rem;color:#6b7280;margin:0.3rem 0 0">Tạo và quản lý mã giảm giá cho khách hàng</p>
        </div>
        <button onclick="openVoucherModal()"
                style="display:flex;align-items:center;gap:0.5rem;background:#ee2624;color:white;border:none;border-radius:10px;padding:0.65rem 1.25rem;font-weight:700;font-size:0.875rem;cursor:pointer">
            <i class="fas fa-plus"></i> Tạo voucher mới
        </button>
    </div>

    <!-- Stats -->
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:1.5rem">
        <?php
        $total   = count($vouchers);
        $active  = count(array_filter($vouchers, fn($v) => $v['is_active'] && (!$v['end_date'] || strtotime($v['end_date']) >= time())));
        $used    = array_sum(array_column($vouchers, 'used_count'));
        foreach ([
            ['fas fa-ticket',      'Tổng voucher',   $total,  '#ee2624', '#fef2f2'],
            ['fas fa-circle-check','Đang hoạt động', $active, '#16a34a', '#f0fdf4'],
            ['fas fa-users',       'Lượt đã dùng',   $used,   '#8b5cf6', '#f5f3ff'],
        ] as [$icon, $label, $val, $color, $bg]):
        ?>
        <div style="background:white;border-radius:14px;border:1px solid #e5e7eb;padding:1rem;display:flex;align-items:center;gap:0.75rem">
            <div style="width:44px;height:44px;border-radius:12px;background:<?= $bg ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                <i class="<?= $icon ?>" style="color:<?= $color ?>;font-size:1.1rem"></i>
            </div>
            <div>
                <p style="font-size:1.4rem;font-weight:900;color:#111827;margin:0"><?= $val ?></p>
                <p style="font-size:0.75rem;color:#6b7280;margin:0"><?= $label ?></p>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Voucher list -->
    <?php if (empty($vouchers)): ?>
    <div style="background:white;border-radius:16px;border:1px solid #e5e7eb;padding:3rem;text-align:center;color:#9ca3af">
        <i class="fas fa-ticket" style="font-size:3rem;margin-bottom:1rem;display:block"></i>
        <p style="font-weight:600;margin:0 0 0.5rem">Chưa có voucher nào</p>
        <p style="font-size:0.875rem;margin:0 0 1.25rem">Tạo voucher để thu hút thêm khách hàng!</p>
        <button onclick="openVoucherModal()"
                style="background:#ee2624;color:white;border:none;border-radius:10px;padding:0.65rem 1.5rem;font-weight:700;cursor:pointer">
            <i class="fas fa-plus"></i> Tạo ngay
        </button>
    </div>
    <?php else: ?>
    <div style="display:flex;flex-direction:column;gap:0.75rem" id="voucher-list">
        <?php foreach ($vouchers as $v):
            $is_active = $v['is_active'] && (!$v['end_date'] || strtotime($v['end_date']) >= time());
            $usage_pct = $v['usage_limit'] > 0 ? min(100, round($v['used_count'] / $v['usage_limit'] * 100)) : 0;
        ?>
        <div style="background:white;border-radius:14px;border:1.5px solid <?= $is_active ? '#e5e7eb' : '#f3f4f6' ?>;padding:1.25rem;display:flex;align-items:center;gap:1rem;flex-wrap:wrap;<?= !$is_active ? 'opacity:0.65' : '' ?>"
             id="voucher-row-<?= $v['id'] ?>">

            <!-- Code + type -->
            <div style="flex-shrink:0;min-width:140px">
                <span style="font-family:monospace;font-weight:900;font-size:1rem;color:#ee2624;background:#fef2f2;padding:4px 12px;border-radius:8px;border:1px dashed #fca5a5;display:block;text-align:center;margin-bottom:0.4rem">
                    <?= esc_html($v['code']) ?>
                </span>
                <span style="display:block;text-align:center;font-size:0.7rem;font-weight:700;padding:2px 8px;border-radius:999px;background:<?= $is_active ? '#dcfce7' : '#f3f4f6' ?>;color:<?= $is_active ? '#16a34a' : '#9ca3af' ?>">
                    <?= $is_active ? '● Đang chạy' : '○ Hết hạn' ?>
                </span>
            </div>

            <!-- Info -->
            <div style="flex:1;min-width:200px">
                <p style="font-weight:700;font-size:0.95rem;color:#111827;margin:0 0 0.2rem"><?= esc_html($v['name']) ?></p>
                <div style="display:flex;gap:1rem;font-size:0.75rem;color:#6b7280;flex-wrap:wrap">
                    <?php if ($v['type'] === 'freeship'): ?>
                    <span><i class="fas fa-motorcycle" style="color:#0ea5e9"></i> Freeship</span>
                    <?php elseif ($v['type'] === 'percent'): ?>
                    <span><i class="fas fa-percent" style="color:#f97316"></i> Giảm <?= $v['value'] ?>% (tối đa <?= cica_format_price((int)$v['max_discount']) ?>)</span>
                    <?php else: ?>
                    <span><i class="fas fa-tag" style="color:#8b5cf6"></i> Giảm <?= cica_format_price((int)$v['value']) ?></span>
                    <?php endif; ?>
                    <span><i class="fas fa-receipt"></i> Đơn từ <?= cica_format_price((int)$v['min_order']) ?></span>
                    <?php if ($v['end_date']): ?>
                    <span><i class="fas fa-calendar"></i> HSD: <?= date('d/m/Y', strtotime($v['end_date'])) ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Usage bar -->
            <div style="min-width:120px;flex-shrink:0">
                <div style="display:flex;justify-content:space-between;font-size:0.75rem;color:#6b7280;margin-bottom:0.3rem">
                    <span>Đã dùng</span>
                    <span style="font-weight:700;color:#374151"><?= $v['used_count'] ?>/<?= $v['usage_limit'] ?></span>
                </div>
                <div style="height:6px;background:#f3f4f6;border-radius:999px;overflow:hidden">
                    <div style="height:100%;width:<?= $usage_pct ?>%;background:<?= $usage_pct >= 80 ? '#ef4444' : '#ee2624' ?>;border-radius:999px;transition:width 0.3s"></div>
                </div>
            </div>

            <!-- Actions -->
            <div style="display:flex;gap:0.5rem;flex-shrink:0">
                <button onclick='openEditModal(<?= json_encode($v) ?>)'
                        style="background:#f3f4f6;color:#374151;border:none;border-radius:8px;padding:0.5rem 0.875rem;font-weight:600;font-size:0.8rem;cursor:pointer;display:flex;align-items:center;gap:0.4rem">
                    <i class="fas fa-edit"></i> Sửa
                </button>
                <button onclick="deleteVoucher(<?= (int)$v['id'] ?>)"
                        style="background:#fef2f2;color:#dc2626;border:none;border-radius:8px;padding:0.5rem 0.875rem;font-weight:600;font-size:0.8rem;cursor:pointer;display:flex;align-items:center;gap:0.4rem">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- ============================================================
     MODAL: TẠO / SỬA VOUCHER
     ============================================================ -->
<div id="voucher-modal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.5);align-items:center;justify-content:center;padding:1rem">
    <div style="background:white;border-radius:20px;width:100%;max-width:560px;max-height:90vh;overflow-y:auto;box-shadow:0 24px 60px rgba(0,0,0,0.2)">
        <!-- Modal header -->
        <div style="padding:1.25rem 1.5rem;border-bottom:1px solid #f3f4f6;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;background:white;z-index:1">
            <h2 id="modal-title" style="font-size:1.1rem;font-weight:900;color:#111827;margin:0">Tạo Voucher Mới</h2>
            <button onclick="closeVoucherModal()" style="background:none;border:none;font-size:1.2rem;color:#9ca3af;cursor:pointer;padding:0.25rem">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Form -->
        <form id="voucher-form" style="padding:1.5rem;display:flex;flex-direction:column;gap:1rem">
            <input type="hidden" id="f-voucher-id" value="0">

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
                <div>
                    <label style="display:block;font-size:0.8rem;font-weight:700;color:#374151;margin-bottom:0.4rem;text-transform:uppercase;letter-spacing:0.05em">Mã Code *</label>
                    <input type="text" id="f-code" placeholder="VD: SUMMER20" required
                           style="width:100%;padding:0.65rem 0.875rem;border:1.5px solid #e5e7eb;border-radius:10px;font-size:0.875rem;font-family:monospace;text-transform:uppercase;outline:none;box-sizing:border-box"
                           onfocus="this.style.borderColor='#ee2624'" onblur="this.style.borderColor='#e5e7eb'">
                </div>
                <div>
                    <label style="display:block;font-size:0.8rem;font-weight:700;color:#374151;margin-bottom:0.4rem;text-transform:uppercase;letter-spacing:0.05em">Tên Voucher *</label>
                    <input type="text" id="f-name" placeholder="Giảm 20% cuối tuần" required
                           style="width:100%;padding:0.65rem 0.875rem;border:1.5px solid #e5e7eb;border-radius:10px;font-size:0.875rem;outline:none;box-sizing:border-box"
                           onfocus="this.style.borderColor='#ee2624'" onblur="this.style.borderColor='#e5e7eb'">
                </div>
            </div>

            <div>
                <label style="display:block;font-size:0.8rem;font-weight:700;color:#374151;margin-bottom:0.4rem;text-transform:uppercase;letter-spacing:0.05em">Mô tả</label>
                <textarea id="f-desc" rows="2" placeholder="Mô tả chi tiết điều kiện áp dụng..."
                          style="width:100%;padding:0.65rem 0.875rem;border:1.5px solid #e5e7eb;border-radius:10px;font-size:0.875rem;outline:none;resize:none;box-sizing:border-box"
                          onfocus="this.style.borderColor='#ee2624'" onblur="this.style.borderColor='#e5e7eb'"></textarea>
            </div>

            <!-- Type selector -->
            <div>
                <label style="display:block;font-size:0.8rem;font-weight:700;color:#374151;margin-bottom:0.6rem;text-transform:uppercase;letter-spacing:0.05em">Loại Voucher *</label>
                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:0.5rem">
                    <?php foreach ([
                        ['fixed',    '💰', 'Giảm tiền',  '#8b5cf6'],
                        ['percent',  '🏷️', 'Giảm %',     '#f97316'],
                        ['freeship', '🚚', 'Freeship',   '#0ea5e9'],
                    ] as [$val, $emoji, $label, $color]): ?>
                    <label style="cursor:pointer">
                        <input type="radio" name="voucher-type" value="<?= $val ?>" <?= $val === 'fixed' ? 'checked' : '' ?>
                               onchange="onTypeChange()" style="display:none">
                        <div class="type-btn" data-type="<?= $val ?>"
                             style="border:2px solid #e5e7eb;border-radius:12px;padding:0.75rem;text-align:center;transition:all 0.2s;<?= $val === 'fixed' ? "border-color:$color;background:{$color}10" : '' ?>">
                            <div style="font-size:1.3rem;margin-bottom:0.2rem"><?= $emoji ?></div>
                            <div style="font-size:0.8rem;font-weight:700;color:#374151"><?= $label ?></div>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
                <div>
                    <label id="f-value-label" style="display:block;font-size:0.8rem;font-weight:700;color:#374151;margin-bottom:0.4rem;text-transform:uppercase;letter-spacing:0.05em">Số tiền giảm (VND) *</label>
                    <input type="number" id="f-value" min="1" required
                           style="width:100%;padding:0.65rem 0.875rem;border:1.5px solid #e5e7eb;border-radius:10px;font-size:0.875rem;outline:none;box-sizing:border-box"
                           onfocus="this.style.borderColor='#ee2624'" onblur="this.style.borderColor='#e5e7eb'">
                </div>
                <div id="f-max-wrap">
                    <label style="display:block;font-size:0.8rem;font-weight:700;color:#374151;margin-bottom:0.4rem;text-transform:uppercase;letter-spacing:0.05em">Giảm tối đa (VND)</label>
                    <input type="number" id="f-max" min="0"
                           style="width:100%;padding:0.65rem 0.875rem;border:1.5px solid #e5e7eb;border-radius:10px;font-size:0.875rem;outline:none;box-sizing:border-box"
                           onfocus="this.style.borderColor='#ee2624'" onblur="this.style.borderColor='#e5e7eb'">
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem">
                <div>
                    <label style="display:block;font-size:0.8rem;font-weight:700;color:#374151;margin-bottom:0.4rem;text-transform:uppercase;letter-spacing:0.05em">Đơn tối thiểu *</label>
                    <input type="number" id="f-min" min="0" value="0" required
                           style="width:100%;padding:0.65rem 0.875rem;border:1.5px solid #e5e7eb;border-radius:10px;font-size:0.875rem;outline:none;box-sizing:border-box"
                           onfocus="this.style.borderColor='#ee2624'" onblur="this.style.borderColor='#e5e7eb'">
                </div>
                <div>
                    <label style="display:block;font-size:0.8rem;font-weight:700;color:#374151;margin-bottom:0.4rem;text-transform:uppercase;letter-spacing:0.05em">Giới hạn dùng *</label>
                    <input type="number" id="f-limit" min="1" value="100" required
                           style="width:100%;padding:0.65rem 0.875rem;border:1.5px solid #e5e7eb;border-radius:10px;font-size:0.875rem;outline:none;box-sizing:border-box"
                           onfocus="this.style.borderColor='#ee2624'" onblur="this.style.borderColor='#e5e7eb'">
                </div>
                <div>
                    <label style="display:block;font-size:0.8rem;font-weight:700;color:#374151;margin-bottom:0.4rem;text-transform:uppercase;letter-spacing:0.05em">Ngày hết hạn *</label>
                    <input type="date" id="f-end" required
                           style="width:100%;padding:0.65rem 0.875rem;border:1.5px solid #e5e7eb;border-radius:10px;font-size:0.875rem;outline:none;box-sizing:border-box"
                           onfocus="this.style.borderColor='#ee2624'" onblur="this.style.borderColor='#e5e7eb'">
                </div>
            </div>

            <div id="form-error" style="display:none;background:#fef2f2;border:1px solid #fca5a5;border-radius:10px;padding:0.75rem;font-size:0.875rem;color:#dc2626;font-weight:600"></div>

            <div style="display:flex;gap:0.75rem;padding-top:0.5rem">
                <button type="button" onclick="closeVoucherModal()"
                        style="flex:1;background:#f3f4f6;color:#374151;border:none;border-radius:12px;padding:0.875rem;font-weight:700;cursor:pointer;font-size:0.9rem">
                    Huỷ
                </button>
                <button type="submit" id="form-submit-btn"
                        style="flex:2;background:#ee2624;color:white;border:none;border-radius:12px;padding:0.875rem;font-weight:700;cursor:pointer;font-size:0.9rem">
                    <i class="fas fa-save"></i> Lưu Voucher
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const AJAX_URL = '<?= $ajax_url ?>';
const NONCE    = '<?= $nonce ?>';

const TYPE_COLORS = { fixed:'#8b5cf6', percent:'#f97316', freeship:'#0ea5e9' };

function openVoucherModal() {
    document.getElementById('modal-title').textContent = 'Tạo Voucher Mới';
    document.getElementById('f-voucher-id').value = '0';
    document.getElementById('voucher-form').reset();
    document.getElementById('f-limit').value = '100';
    document.getElementById('f-min').value = '0';
    document.querySelector('input[name="voucher-type"][value="fixed"]').checked = true;
    onTypeChange();
    document.getElementById('form-error').style.display = 'none';
    document.getElementById('voucher-modal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function openEditModal(v) {
    document.getElementById('modal-title').textContent = 'Chỉnh sửa Voucher';
    document.getElementById('f-voucher-id').value = v.id;
    document.getElementById('f-code').value  = v.code;
    document.getElementById('f-name').value  = v.name;
    document.getElementById('f-desc').value  = v.description || '';
    document.getElementById('f-value').value = v.value;
    document.getElementById('f-max').value   = v.max_discount || '';
    document.getElementById('f-min').value   = v.min_order;
    document.getElementById('f-limit').value = v.usage_limit;
    document.getElementById('f-end').value   = (v.end_date || '').substring(0, 10);
    const radio = document.querySelector(`input[name="voucher-type"][value="${v.type}"]`);
    if (radio) { radio.checked = true; }
    onTypeChange();
    document.getElementById('form-error').style.display = 'none';
    document.getElementById('voucher-modal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeVoucherModal() {
    document.getElementById('voucher-modal').style.display = 'none';
    document.body.style.overflow = '';
}

function onTypeChange() {
    const type = document.querySelector('input[name="voucher-type"]:checked')?.value || 'fixed';
    const color = TYPE_COLORS[type] || '#ee2624';

    // Update type button styles
    document.querySelectorAll('.type-btn').forEach(btn => {
        const t = btn.dataset.type;
        if (t === type) {
            btn.style.borderColor = color;
            btn.style.background  = color + '15';
        } else {
            btn.style.borderColor = '#e5e7eb';
            btn.style.background  = '';
        }
    });

    const lbl = document.getElementById('f-value-label');
    const maxWrap = document.getElementById('f-max-wrap');
    if (type === 'percent') {
        lbl.textContent = 'Phần trăm giảm (%) *';
        maxWrap.style.display = 'block';
    } else if (type === 'freeship') {
        lbl.textContent = 'Phí ship hỗ trợ tối đa (VND) *';
        maxWrap.style.display = 'block';
    } else {
        lbl.textContent = 'Số tiền giảm (VND) *';
        maxWrap.style.display = 'none';
    }
}

// Form submit
document.getElementById('voucher-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('form-submit-btn');
    const errEl = document.getElementById('form-error');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang lưu...';
    errEl.style.display = 'none';

    const type = document.querySelector('input[name="voucher-type"]:checked')?.value || 'fixed';
    const fd = new FormData();
    fd.append('action',       'cica_merchant_save_voucher');
    fd.append('nonce',        NONCE);
    fd.append('voucher_id',   document.getElementById('f-voucher-id').value);
    fd.append('code',         document.getElementById('f-code').value.toUpperCase());
    fd.append('name',         document.getElementById('f-name').value);
    fd.append('description',  document.getElementById('f-desc').value);
    fd.append('type',         type);
    fd.append('value',        document.getElementById('f-value').value);
    fd.append('max_discount', document.getElementById('f-max').value || document.getElementById('f-value').value);
    fd.append('min_order',    document.getElementById('f-min').value);
    fd.append('usage_limit',  document.getElementById('f-limit').value);
    fd.append('end_date',     document.getElementById('f-end').value);

    fetch(AJAX_URL, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                closeVoucherModal();
                if (typeof showCicaToast === 'function') showCicaToast('success', d.message);
                setTimeout(() => location.reload(), 800);
            } else {
                errEl.textContent = d.message;
                errEl.style.display = 'block';
            }
        })
        .catch(() => { errEl.textContent = 'Lỗi kết nối'; errEl.style.display = 'block'; })
        .finally(() => { btn.disabled = false; btn.innerHTML = '<i class="fas fa-save"></i> Lưu Voucher'; });
});

function deleteVoucher(id) {
    if (!confirm('Xoá voucher này? Hành động không thể hoàn tác.')) return;
    const row = document.getElementById('voucher-row-' + id);
    if (row) row.style.opacity = '0.4';

    const fd = new FormData();
    fd.append('action',     'cica_merchant_delete_voucher');
    fd.append('nonce',      NONCE);
    fd.append('voucher_id', id);

    fetch(AJAX_URL, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                if (row) row.remove();
                if (typeof showCicaToast === 'function') showCicaToast('success', d.message);
            } else {
                if (row) row.style.opacity = '1';
                if (typeof showCicaToast === 'function') showCicaToast('error', d.message);
            }
        });
}

// Close modal on backdrop click / ESC
document.getElementById('voucher-modal').addEventListener('click', function(e) {
    if (e.target === this) closeVoucherModal();
});
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeVoucherModal(); });
</script>
