<?php
defined('ABSPATH') || exit;
if (!session_id()) session_start();

$user_id     = get_current_user_id();
$user        = wp_get_current_user();
$site_url    = get_site_url();
$ajax_url    = admin_url('admin-ajax.php');
$nonce       = wp_create_nonce('cicafood_nonce');

// Meta hien tai
$phone   = get_user_meta($user_id, 'phone',   true);
$address = get_user_meta($user_id, 'address', true);
$avatar  = get_user_meta($user_id, 'cica_avatar', true);

// Thong ke
global $wpdb;
$order_count    = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}cica_orders WHERE wp_user_id=%d", $user_id));
$fav_count      = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}cica_favorites WHERE wp_user_id=%d", $user_id));
$voucher_count  = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}cica_user_vouchers WHERE wp_user_id=%d AND is_used=0", $user_id));
$total_spent    = (int)$wpdb->get_var($wpdb->prepare("SELECT SUM(total_amount) FROM {$wpdb->prefix}cica_orders WHERE wp_user_id=%d AND status='completed'", $user_id));

$avatar_url = $avatar ?: 'https://ui-avatars.com/api/?name=' . urlencode($user->display_name) . '&background=ee2624&color=fff&size=200&bold=true';
?>

<div style="max-width:800px;margin:0 auto;padding:1.5rem 1rem">

    <!-- Page header -->
    <h1 style="font-size:1.4rem;font-weight:900;color:#111827;margin:0 0 1.5rem;display:flex;align-items:center;gap:0.5rem">
        <i class="fas fa-user-circle" style="color:#ee2624"></i> Hồ Sơ Của Tôi
    </h1>

    <!-- Stats row -->
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:1.5rem">
        <?php foreach ([
            ['fas fa-receipt',    'Đơn hàng',    $order_count,                                    '#ee2624', '#fef2f2'],
            ['fas fa-heart',      'Yêu thích',   $fav_count,                                      '#ec4899', '#fdf2f8'],
            ['fas fa-ticket',     'Voucher',      $voucher_count,                                  '#f97316', '#fff7ed'],
            ['fas fa-coins',      'Đã chi',       number_format($total_spent,0,',','.') . 'đ',    '#16a34a', '#f0fdf4'],
        ] as [$icon,$label,$val,$color,$bg]): ?>
        <div style="background:white;border-radius:14px;border:1px solid #e5e7eb;padding:1rem;text-align:center">
            <div style="width:40px;height:40px;border-radius:12px;background:<?= $bg ?>;display:flex;align-items:center;justify-content:center;margin:0 auto 0.5rem">
                <i class="<?= $icon ?>" style="color:<?= $color ?>"></i>
            </div>
            <p style="font-size:1.1rem;font-weight:900;color:#111827;margin:0"><?= $val ?></p>
            <p style="font-size:0.72rem;color:#9ca3af;margin:0"><?= $label ?></p>
        </div>
        <?php endforeach; ?>
    </div>

    <div style="display:grid;grid-template-columns:260px 1fr;gap:1.5rem;align-items:start">

        <!-- LEFT: Avatar card -->
        <div style="background:white;border-radius:20px;border:1px solid #e5e7eb;padding:1.5rem;text-align:center">
            <!-- Avatar -->
            <div style="position:relative;display:inline-block;margin-bottom:1rem">
                <img id="avatar-preview" src="<?= esc_url($avatar_url) ?>" alt="Avatar"
                     style="width:120px;height:120px;border-radius:50%;object-fit:cover;border:4px solid white;box-shadow:0 4px 16px rgba(238,38,36,0.2)">
                <label for="avatar-file"
                       style="position:absolute;bottom:4px;right:4px;width:32px;height:32px;background:#ee2624;border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;box-shadow:0 2px 8px rgba(0,0,0,0.2)">
                    <i class="fas fa-camera" style="color:white;font-size:0.75rem"></i>
                </label>
                <input type="file" id="avatar-file" accept="image/*" style="display:none" onchange="previewAvatar(this)">
            </div>

            <h2 style="font-size:1.1rem;font-weight:800;color:#111827;margin:0 0 0.25rem"><?= esc_html($user->display_name) ?></h2>
            <p style="font-size:0.8rem;color:#9ca3af;margin:0 0 1.25rem"><?= esc_html($user->user_email) ?></p>

            <!-- Upload progress -->
            <div id="avatar-upload-area" style="display:none">
                <div id="avatar-progress-bar" style="height:4px;background:#f3f4f6;border-radius:999px;margin-bottom:0.75rem;overflow:hidden">
                    <div id="avatar-progress-fill" style="height:100%;width:0%;background:#ee2624;border-radius:999px;transition:width 0.3s"></div>
                </div>
                <button type="button" onclick="uploadAvatar()"
                        style="width:100%;background:#ee2624;color:white;border:none;border-radius:10px;padding:0.6rem;font-weight:700;font-size:0.875rem;cursor:pointer">
                    <i class="fas fa-upload"></i> Lưu ảnh mới
                </button>
                <button type="button" onclick="cancelAvatar()"
                        style="width:100%;background:#f3f4f6;color:#6b7280;border:none;border-radius:10px;padding:0.5rem;font-weight:600;font-size:0.8rem;cursor:pointer;margin-top:0.4rem">
                    Huỷ
                </button>
            </div>

            <!-- Quick links -->
            <div style="border-top:1px solid #f3f4f6;padding-top:1rem;margin-top:0.5rem;display:flex;flex-direction:column;gap:0.25rem">
                <?php foreach ([
                    ['fas fa-receipt', 'Đơn hàng của tôi', $site_url . '/don-hang/'],
                    ['fas fa-ticket',  'Ví voucher',        $site_url . '/voucher/'],
                    ['fas fa-heart',   'Yêu thích',         $site_url . '/yeu-thich/'],
                ] as [$icon, $label, $url]): ?>
                <a href="<?= esc_url($url) ?>"
                   style="display:flex;align-items:center;gap:0.6rem;padding:0.6rem 0.75rem;border-radius:10px;font-size:0.875rem;font-weight:600;color:#374151;text-decoration:none;transition:background 0.15s"
                   onmouseover="this.style.background='#fef2f2';this.style.color='#ee2624'" onmouseout="this.style.background='';this.style.color='#374151'">
                    <i class="<?= $icon ?>" style="color:#ee2624;width:16px;text-align:center"></i> <?= $label ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- RIGHT: Edit form -->
        <div style="display:flex;flex-direction:column;gap:1.25rem">

            <!-- Thong tin ca nhan -->
            <div style="background:white;border-radius:20px;border:1px solid #e5e7eb;padding:1.5rem">
                <h3 style="font-size:1rem;font-weight:800;color:#111827;margin:0 0 1.25rem;display:flex;align-items:center;gap:0.5rem">
                    <i class="fas fa-id-card" style="color:#ee2624"></i> Thông Tin Cá Nhân
                </h3>

                <div style="display:flex;flex-direction:column;gap:1rem">
                    <!-- Display name -->
                    <div>
                        <label style="display:block;font-size:0.75rem;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:0.4rem">
                            Họ và tên
                        </label>
                        <input type="text" id="f-display-name" value="<?= esc_attr($user->display_name) ?>"
                               placeholder="Nhập họ và tên..."
                               style="width:100%;padding:0.7rem 1rem;border:1.5px solid #e5e7eb;border-radius:12px;font-size:0.9rem;outline:none;box-sizing:border-box;transition:border-color 0.2s"
                               onfocus="this.style.borderColor='#ee2624'" onblur="this.style.borderColor='#e5e7eb'">
                    </div>

                    <!-- Phone -->
                    <div>
                        <label style="display:block;font-size:0.75rem;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:0.4rem">
                            Số điện thoại
                        </label>
                        <div style="position:relative">
                            <span style="position:absolute;left:1rem;top:50%;transform:translateY(-50%);color:#9ca3af;font-size:0.875rem">
                                <i class="fas fa-phone"></i>
                            </span>
                            <input type="tel" id="f-phone" value="<?= esc_attr($phone) ?>"
                                   placeholder="0901 234 567"
                                   style="width:100%;padding:0.7rem 1rem 0.7rem 2.5rem;border:1.5px solid #e5e7eb;border-radius:12px;font-size:0.9rem;outline:none;box-sizing:border-box;transition:border-color 0.2s"
                                   onfocus="this.style.borderColor='#ee2624'" onblur="this.style.borderColor='#e5e7eb'">
                        </div>
                    </div>

                    <!-- Address -->
                    <div>
                        <label style="display:block;font-size:0.75rem;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:0.4rem">
                            Địa chỉ giao hàng mặc định
                        </label>
                        <div style="position:relative">
                            <span style="position:absolute;left:1rem;top:0.85rem;color:#9ca3af;font-size:0.875rem">
                                <i class="fas fa-map-marker-alt"></i>
                            </span>
                            <textarea id="f-address" rows="2" placeholder="Số nhà, tên đường, phường/xã, quận/huyện..."
                                      style="width:100%;padding:0.7rem 1rem 0.7rem 2.5rem;border:1.5px solid #e5e7eb;border-radius:12px;font-size:0.9rem;outline:none;resize:none;box-sizing:border-box;transition:border-color 0.2s"
                                      onfocus="this.style.borderColor='#ee2624'" onblur="this.style.borderColor='#e5e7eb'"><?= esc_textarea($address) ?></textarea>
                        </div>
                    </div>

                    <!-- Email (readonly) -->
                    <div>
                        <label style="display:block;font-size:0.75rem;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:0.4rem">
                            Email <span style="font-size:0.65rem;color:#9ca3af;font-weight:500;text-transform:none">(không thể thay đổi)</span>
                        </label>
                        <div style="position:relative">
                            <span style="position:absolute;left:1rem;top:50%;transform:translateY(-50%);color:#d1d5db;font-size:0.875rem">
                                <i class="fas fa-envelope"></i>
                            </span>
                            <input type="email" value="<?= esc_attr($user->user_email) ?>" readonly
                                   style="width:100%;padding:0.7rem 1rem 0.7rem 2.5rem;border:1.5px solid #f3f4f6;border-radius:12px;font-size:0.9rem;outline:none;box-sizing:border-box;background:#f9fafb;color:#9ca3af;cursor:not-allowed">
                        </div>
                    </div>

                    <!-- Error/success -->
                    <div id="profile-msg" style="display:none;border-radius:10px;padding:0.65rem 1rem;font-size:0.875rem;font-weight:600"></div>

                    <!-- Save button -->
                    <button type="button" onclick="saveProfile()"
                            id="save-profile-btn"
                            style="background:#ee2624;color:white;border:none;border-radius:12px;padding:0.875rem;font-weight:800;font-size:0.95rem;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:0.5rem;transition:background 0.2s"
                            onmouseover="this.style.background='#c41e1c'" onmouseout="this.style.background='#ee2624'">
                        <i class="fas fa-save"></i> Lưu thay đổi
                    </button>
                </div>
            </div>

            <!-- Doi mat khau -->
            <div style="background:white;border-radius:20px;border:1px solid #e5e7eb;padding:1.5rem">
                <h3 style="font-size:1rem;font-weight:800;color:#111827;margin:0 0 1.25rem;display:flex;align-items:center;gap:0.5rem">
                    <i class="fas fa-lock" style="color:#8b5cf6"></i> Đổi Mật Khẩu
                </h3>
                <div style="display:flex;flex-direction:column;gap:1rem">
                    <div>
                        <label style="display:block;font-size:0.75rem;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:0.4rem">Mật khẩu hiện tại</label>
                        <input type="password" id="f-current-pw" placeholder="••••••••"
                               style="width:100%;padding:0.7rem 1rem;border:1.5px solid #e5e7eb;border-radius:12px;font-size:0.9rem;outline:none;box-sizing:border-box"
                               onfocus="this.style.borderColor='#8b5cf6'" onblur="this.style.borderColor='#e5e7eb'">
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
                        <div>
                            <label style="display:block;font-size:0.75rem;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:0.4rem">Mật khẩu mới</label>
                            <input type="password" id="f-new-pw" placeholder="••••••••"
                                   style="width:100%;padding:0.7rem 1rem;border:1.5px solid #e5e7eb;border-radius:12px;font-size:0.9rem;outline:none;box-sizing:border-box"
                                   onfocus="this.style.borderColor='#8b5cf6'" onblur="this.style.borderColor='#e5e7eb'">
                        </div>
                        <div>
                            <label style="display:block;font-size:0.75rem;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:0.4rem">Xác nhận lại</label>
                            <input type="password" id="f-confirm-pw" placeholder="••••••••"
                                   style="width:100%;padding:0.7rem 1rem;border:1.5px solid #e5e7eb;border-radius:12px;font-size:0.9rem;outline:none;box-sizing:border-box"
                                   onfocus="this.style.borderColor='#8b5cf6'" onblur="this.style.borderColor='#e5e7eb'">
                        </div>
                    </div>
                    <div id="pw-msg" style="display:none;border-radius:10px;padding:0.65rem 1rem;font-size:0.875rem;font-weight:600"></div>
                    <button type="button" onclick="changePassword()"
                            style="background:#8b5cf6;color:white;border:none;border-radius:12px;padding:0.75rem;font-weight:700;font-size:0.875rem;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:0.5rem;transition:background 0.2s"
                            onmouseover="this.style.background='#7c3aed'" onmouseout="this.style.background='#8b5cf6'">
                        <i class="fas fa-key"></i> Đổi mật khẩu
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>

<style>
@media (max-width: 700px) {
    div[style*="grid-template-columns:260px"] { grid-template-columns: 1fr !important; }
    div[style*="grid-template-columns:repeat(4"] { grid-template-columns: repeat(2,1fr) !important; }
}
</style>

<script>
const AJAX_URL = '<?= $ajax_url ?>';
const NONCE    = '<?= $nonce ?>';
let avatarFile = null;

// ---- Avatar preview ----
function previewAvatar(input) {
    if (!input.files || !input.files[0]) return;
    avatarFile = input.files[0];
    const reader = new FileReader();
    reader.onload = e => {
        document.getElementById('avatar-preview').src = e.target.result;
        document.getElementById('avatar-upload-area').style.display = 'block';
    };
    reader.readAsDataURL(avatarFile);
}

function cancelAvatar() {
    avatarFile = null;
    document.getElementById('avatar-file').value = '';
    document.getElementById('avatar-upload-area').style.display = 'none';
    document.getElementById('avatar-preview').src = '<?= esc_js($avatar_url) ?>';
}

function uploadAvatar() {
    if (!avatarFile) return;
    const fill = document.getElementById('avatar-progress-fill');
    fill.style.width = '30%';

    const fd = new FormData();
    fd.append('action', 'cica_update_avatar');
    fd.append('nonce',  NONCE);
    fd.append('avatar', avatarFile);

    const xhr = new XMLHttpRequest();
    xhr.upload.onprogress = e => {
        if (e.lengthComputable) fill.style.width = Math.round(e.loaded / e.total * 90) + '%';
    };
    xhr.onload = () => {
        fill.style.width = '100%';
        const d = JSON.parse(xhr.responseText);
        if (d.success) {
            document.getElementById('avatar-preview').src = d.avatar_url;
            document.getElementById('avatar-upload-area').style.display = 'none';
            avatarFile = null;
            showMsg('profile-msg', 'success', 'Đã cập nhật ảnh đại diện!');
        } else {
            showMsg('profile-msg', 'error', d.message || 'Lỗi upload ảnh');
        }
        setTimeout(() => fill.style.width = '0%', 600);
    };
    xhr.open('POST', AJAX_URL);
    xhr.send(fd);
}

// ---- Save profile ----
function saveProfile() {
    const btn  = document.getElementById('save-profile-btn');
    const name = document.getElementById('f-display-name').value.trim();
    const phone= document.getElementById('f-phone').value.trim();
    const addr = document.getElementById('f-address').value.trim();

    if (!name) { showMsg('profile-msg', 'error', 'Vui lòng nhập họ tên'); return; }

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang lưu...';

    const fd = new FormData();
    fd.append('action',       'cica_update_profile');
    fd.append('nonce',        NONCE);
    fd.append('display_name', name);
    fd.append('phone',        phone);
    fd.append('address',      addr);

    fetch(AJAX_URL, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                showMsg('profile-msg', 'success', d.message);
                // Update header display name if possible
                const nameEl = document.querySelector('#cica-user-menu-wrap button span');
                if (nameEl) nameEl.textContent = name;
            } else {
                showMsg('profile-msg', 'error', d.message);
            }
        })
        .catch(() => showMsg('profile-msg', 'error', 'Lỗi kết nối'))
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save"></i> Lưu thay đổi';
        });
}

// ---- Change password ----
function changePassword() {
    const cur  = document.getElementById('f-current-pw').value;
    const nw   = document.getElementById('f-new-pw').value;
    const conf = document.getElementById('f-confirm-pw').value;

    if (!cur || !nw || !conf) { showMsg('pw-msg', 'error', 'Vui lòng điền đầy đủ'); return; }
    if (nw.length < 6)        { showMsg('pw-msg', 'error', 'Mật khẩu mới tối thiểu 6 ký tự'); return; }
    if (nw !== conf)           { showMsg('pw-msg', 'error', 'Mật khẩu xác nhận không khớp'); return; }

    const fd = new FormData();
    fd.append('action',       'cica_change_password');
    fd.append('nonce',        NONCE);
    fd.append('current_pw',   cur);
    fd.append('new_pw',       nw);

    fetch(AJAX_URL, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                showMsg('pw-msg', 'success', d.message);
                document.getElementById('f-current-pw').value = '';
                document.getElementById('f-new-pw').value = '';
                document.getElementById('f-confirm-pw').value = '';
            } else {
                showMsg('pw-msg', 'error', d.message);
            }
        })
        .catch(() => showMsg('pw-msg', 'error', 'Lỗi kết nối'));
}

// ---- Helper ----
function showMsg(id, type, msg) {
    const el = document.getElementById(id);
    el.style.display = 'block';
    el.style.background = type === 'success' ? '#f0fdf4' : '#fef2f2';
    el.style.border     = '1px solid ' + (type === 'success' ? '#86efac' : '#fca5a5');
    el.style.color      = type === 'success' ? '#16a34a' : '#dc2626';
    el.innerHTML = (type === 'success' ? '<i class="fas fa-check-circle"></i> ' : '<i class="fas fa-triangle-exclamation"></i> ') + msg;
    setTimeout(() => { el.style.display = 'none'; }, 4000);
}
</script>
