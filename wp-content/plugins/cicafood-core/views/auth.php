<?php
defined('ABSPATH') || exit;

// Nếu đã đăng nhập → redirect đúng vai trò (chỉ redirect nếu không phải request AJAX)
if (is_user_logged_in() && !wp_doing_ajax()) {
    if (current_user_can('manage_options') || current_user_can('cica_manage_restaurant')) {
        wp_redirect(get_site_url() . '/quan-ly/');
    } else {
        wp_redirect(get_site_url() . '/');
    }
    exit;
}

$site_url  = get_site_url();
$ajax_url  = site_url('/wp-admin/admin-ajax.php');
$nonce     = wp_create_nonce('cicafood_auth_nonce');
$mode      = sanitize_text_field($_GET['mode'] ?? 'login'); // login | register
$redirect  = esc_url($_GET['redirect_to'] ?? $site_url . '/');
$error_msg = '';

// Xử lý form POST (fallback không dùng AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['cica_login'])) {
        $creds = [
            'user_login'    => sanitize_text_field($_POST['username'] ?? ''),
            'user_password' => $_POST['password'] ?? '',
            'remember'      => !empty($_POST['remember']),
        ];
        $user = wp_signon($creds, false);
        if (is_wp_error($user)) {
            $error_msg = 'Tên đăng nhập hoặc mật khẩu không đúng.';
        } else {
            wp_redirect(cica_is_merchant() ? $site_url . '/quan-ly/' : $site_url . '/');
            exit;
        }
    }
    if (isset($_POST['cica_register'])) {
        $username = sanitize_user($_POST['username'] ?? '');
        $email    = sanitize_email($_POST['email']    ?? '');
        $password = $_POST['password'] ?? '';
        $fullname = sanitize_text_field($_POST['fullname'] ?? '');
        if (!$username || !$email || !$password) {
            $error_msg = 'Vui lòng điền đầy đủ thông tin.';
        } elseif (strlen($password) < 6) {
            $error_msg = 'Mật khẩu tối thiểu 6 ký tự.';
        } elseif (username_exists($username)) {
            $error_msg = 'Tên đăng nhập đã tồn tại.';
        } elseif (email_exists($email)) {
            $error_msg = 'Email đã được sử dụng.';
        } else {
            $user_id = wp_create_user($username, $password, $email);
            if (is_wp_error($user_id)) {
                $error_msg = $user_id->get_error_message();
            } else {
                if ($fullname) wp_update_user(['ID' => $user_id, 'display_name' => $fullname]);
                wp_set_current_user($user_id);
                wp_set_auth_cookie($user_id);
                wp_redirect($site_url . '/');
                exit;
            }
        }
    }
}
?>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Inter', sans-serif; background: #f8f8f8 !important; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2rem 0; }
.auth-wrap { width: 100%; max-width: 440px; padding: 1rem; }
.auth-card { background: white; border-radius: 24px; padding: 2.5rem 2rem; box-shadow: 0 8px 40px rgba(0,0,0,0.1); }
.auth-logo { text-align: center; margin-bottom: 2rem; }
.auth-logo a { display: inline-flex; align-items: center; gap: 0.6rem; text-decoration: none; }
.auth-logo .icon { width: 48px; height: 48px; background: #ee2624; border-radius: 14px; display: flex; align-items: center; justify-content: center; }
.auth-logo span { font-size: 1.6rem; font-weight: 900; color: #111827; }
.auth-tabs { display: flex; background: #f3f4f6; border-radius: 12px; padding: 4px; margin-bottom: 1.75rem; }
.auth-tab { flex: 1; text-align: center; padding: 0.6rem; border-radius: 9px; font-weight: 700; font-size: 0.9rem; cursor: pointer; text-decoration: none; color: #6b7280; transition: all 0.15s; }
.auth-tab.active { background: white; color: #111827; box-shadow: 0 1px 4px rgba(0,0,0,0.1); }
.form-group { margin-bottom: 1rem; }
.form-group label { display: block; font-size: 0.78rem; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.4rem; }
.form-group .input-wrap { position: relative; }
.form-group .input-icon { position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: #9ca3af; font-size: 0.875rem; }
.form-group input { width: 100%; padding: 0.75rem 1rem 0.75rem 2.5rem; border: 1.5px solid #e5e7eb; border-radius: 12px; font-size: 0.9rem; outline: none; transition: border-color 0.2s; font-family: inherit; }
.form-group input:focus { border-color: #ee2624; }
.btn-primary { width: 100%; background: #ee2624; color: white; border: none; border-radius: 12px; padding: 0.875rem; font-weight: 800; font-size: 1rem; cursor: pointer; transition: background 0.2s; font-family: inherit; margin-top: 0.5rem; }
.btn-primary:hover { background: #c41e1c; }
.btn-primary:disabled { background: #fca5a5; cursor: not-allowed; }
.error-box { background: #fef2f2; border: 1px solid #fca5a5; border-radius: 10px; padding: 0.75rem 1rem; font-size: 0.875rem; color: #dc2626; font-weight: 600; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem; }
.success-box { background: #f0fdf4; border: 1px solid #86efac; border-radius: 10px; padding: 0.75rem 1rem; font-size: 0.875rem; color: #16a34a; font-weight: 600; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem; }
.divider { display: flex; align-items: center; gap: 0.75rem; margin: 1.25rem 0; color: #9ca3af; font-size: 0.8rem; }
.divider::before, .divider::after { content: ''; flex: 1; height: 1px; background: #e5e7eb; }
.role-info { background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 12px; padding: 1rem; margin-bottom: 1.25rem; }
.role-info h4 { font-size: 0.8rem; font-weight: 700; color: #0369a1; margin-bottom: 0.5rem; }
.role-item { display: flex; align-items: center; gap: 0.5rem; font-size: 0.8rem; color: #374151; margin-bottom: 0.3rem; }
.pw-toggle { position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #9ca3af; font-size: 0.875rem; }
</style>

<div class="auth-wrap">
    <div class="auth-card">

        <!-- Logo -->
        <div class="auth-logo">
            <a href="<?= esc_url($site_url) ?>">
                <div class="icon"><i class="fas fa-utensils" style="color:white;font-size:1.2rem"></i></div>
                <span>Cicafood</span>
            </a>
            <p style="font-size:0.85rem;color:#9ca3af;margin-top:0.5rem">
                <?= $mode === 'register' ? 'Tạo tài khoản mới' : 'Chào mừng trở lại!' ?>
            </p>
        </div>

        <!-- Tabs -->
        <div class="auth-tabs">
            <a href="?mode=login<?= $redirect !== $site_url . '/' ? '&redirect_to=' . urlencode($redirect) : '' ?>"
               class="auth-tab <?= $mode === 'login' ? 'active' : '' ?>">
                <i class="fas fa-right-to-bracket"></i> Đăng nhập
            </a>
            <a href="?mode=register"
               class="auth-tab <?= $mode === 'register' ? 'active' : '' ?>">
                <i class="fas fa-user-plus"></i> Đăng ký
            </a>
        </div>

        <!-- Error -->
        <?php if ($error_msg): ?>
        <div class="error-box"><i class="fas fa-triangle-exclamation"></i> <?= esc_html($error_msg) ?></div>
        <?php endif; ?>

        <!-- Message box (AJAX) -->
        <div id="auth-msg" style="display:none"></div>

        <?php if ($mode === 'login'): ?>
        <!-- ===== LOGIN FORM ===== -->
        <form id="login-form" method="POST">
            <div class="form-group">
                <label>Tên đăng nhập hoặc Email</label>
                <div class="input-wrap">
                    <i class="fas fa-user input-icon"></i>
                    <input type="text" name="username" id="login-username" placeholder="username hoặc email@..." required autocomplete="username">
                </div>
            </div>
            <div class="form-group">
                <label>Mật khẩu</label>
                <div class="input-wrap">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" name="password" id="login-password" placeholder="••••••••" required autocomplete="current-password">
                    <button type="button" class="pw-toggle" onclick="togglePw('login-password', this)">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem">
                <label style="display:flex;align-items:center;gap:0.4rem;font-size:0.85rem;color:#374151;cursor:pointer;font-weight:500">
                    <input type="checkbox" name="remember" style="accent-color:#ee2624"> Ghi nhớ đăng nhập
                </label>
                <a href="<?= esc_url(wp_lostpassword_url()) ?>" style="font-size:0.8rem;color:#ee2624;text-decoration:none">Quên mật khẩu?</a>
            </div>
            <button type="submit" class="btn-primary" id="login-btn">
                <i class="fas fa-right-to-bracket"></i> Đăng nhập
            </button>
            <input type="hidden" name="cica_login" value="1">
        </form>

        <!-- Role info -->
        <div class="divider">hoặc</div>
        <div class="role-info">
            <h4><i class="fas fa-info-circle"></i> Bạn sẽ được chuyển đến:</h4>
            <div class="role-item"><i class="fas fa-store" style="color:#ee2624"></i> <strong>Chủ nhà hàng</strong> → Trang quản lý quán</div>
            <div class="role-item"><i class="fas fa-user" style="color:#3b82f6"></i> <strong>Khách hàng</strong> → Trang chủ đặt đồ ăn</div>
        </div>

        <?php else: ?>
        <!-- ===== REGISTER FORM ===== -->
        <form id="register-form" method="POST">

            <!-- Chọn vai trò -->
            <div style="margin-bottom:1.25rem">
                <label style="display:block;font-size:0.78rem;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:0.6rem">Bạn muốn đăng ký với vai trò</label>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem">
                    <label style="cursor:pointer">
                        <input type="radio" name="role" value="customer" checked style="display:none" class="role-radio">
                        <div class="role-card" data-role="customer"
                             style="border:2px solid #ee2624;border-radius:14px;padding:1rem;text-align:center;background:#fef2f2;transition:all 0.15s">
                            <i class="fas fa-user" style="font-size:1.5rem;color:#ee2624;margin-bottom:0.4rem;display:block"></i>
                            <p style="font-weight:800;font-size:0.875rem;color:#111827;margin:0">Khách hàng</p>
                            <p style="font-size:0.72rem;color:#6b7280;margin:0.2rem 0 0">Đặt đồ ăn online</p>
                        </div>
                    </label>
                    <label style="cursor:pointer">
                        <input type="radio" name="role" value="merchant_request" style="display:none" class="role-radio">
                        <div class="role-card" data-role="merchant_request"
                             style="border:2px solid #e5e7eb;border-radius:14px;padding:1rem;text-align:center;background:white;transition:all 0.15s">
                            <i class="fas fa-store" style="font-size:1.5rem;color:#9ca3af;margin-bottom:0.4rem;display:block"></i>
                            <p style="font-weight:800;font-size:0.875rem;color:#111827;margin:0">Chủ nhà hàng</p>
                            <p style="font-size:0.72rem;color:#6b7280;margin:0.2rem 0 0">Quản lý quán của bạn</p>
                        </div>
                    </label>
                </div>
                <div id="merchant-note" style="display:none;background:#fff7ed;border:1px solid #fed7aa;border-radius:10px;padding:0.65rem 0.875rem;margin-top:0.75rem;font-size:0.8rem;color:#92400e">
                    <i class="fas fa-info-circle"></i> Tài khoản sẽ được tạo ngay. Admin sẽ liên kết nhà hàng cho bạn sau khi xét duyệt.
                </div>
            </div>

            <div class="form-group">
                <label>Họ và tên</label>
                <div class="input-wrap">
                    <i class="fas fa-id-card input-icon"></i>
                    <input type="text" name="fullname" id="reg-fullname" placeholder="Nguyễn Văn A" required>
                </div>
            </div>
            <div class="form-group">
                <label>Tên đăng nhập <span style="font-size:0.7rem;color:#9ca3af;font-weight:400">(chỉ chữ cái, số, dấu _ - .)</span></label>
                <div class="input-wrap">
                    <i class="fas fa-user input-icon"></i>
                    <input type="text" name="username" id="reg-username"
                           placeholder="vd: nguyen_van_a"
                           pattern="[a-zA-Z0-9_\-\.]+"
                           title="Chỉ dùng chữ cái không dấu, số, dấu _ - ."
                           required autocomplete="username"
                           oninput="this.value=this.value.replace(/[^a-zA-Z0-9_\-\.]/g,'')">
                </div>
                <p style="font-size:0.72rem;color:#9ca3af;margin:0.3rem 0 0"><i class="fas fa-circle-info"></i> Không dùng dấu cách, không dùng tiếng Việt có dấu</p>
            </div>
            <div class="form-group">
                <label>Email</label>
                <div class="input-wrap">
                    <i class="fas fa-envelope input-icon"></i>
                    <input type="email" name="email" id="reg-email" placeholder="email@example.com" required autocomplete="email">
                </div>
            </div>
            <div class="form-group">
                <label>Mật khẩu</label>
                <div class="input-wrap">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" name="password" id="reg-password" placeholder="Tối thiểu 6 ký tự" required autocomplete="new-password">
                    <button type="button" class="pw-toggle" onclick="togglePw('reg-password', this)">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <div id="pw-strength" style="margin-top:0.4rem;height:4px;border-radius:999px;background:#f3f4f6;overflow:hidden">
                    <div id="pw-strength-bar" style="height:100%;width:0%;border-radius:999px;transition:all 0.3s"></div>
                </div>
            </div>
            <button type="submit" class="btn-primary" id="register-btn">
                <i class="fas fa-user-plus"></i> Tạo tài khoản
            </button>
            <input type="hidden" name="cica_register" value="1">
            <p style="font-size:0.75rem;color:#9ca3af;text-align:center;margin-top:0.75rem">
                Bằng cách đăng ký, bạn đồng ý với <a href="#" style="color:#ee2624">Điều khoản sử dụng</a>
            </p>
        </form>
        <?php endif; ?>

    </div>

    <!-- Back to home -->
    <p style="text-align:center;margin-top:1rem;font-size:0.85rem;color:#9ca3af">
        <a href="<?= esc_url($site_url) ?>" style="color:#6b7280;text-decoration:none">
            <i class="fas fa-arrow-left"></i> Về trang chủ
        </a>
    </p>
</div>

<script>
// Ưu tiên dùng cicafoodAjax nếu có (đã được WP localize), fallback về PHP value
const AJAX_URL  = (window.cicafoodAjax && window.cicafoodAjax.ajaxurl) ? window.cicafoodAjax.ajaxurl : '<?= esc_js($ajax_url) ?>';
const NONCE     = '<?= $nonce ?>';
const SITE_URL  = '<?= esc_js($site_url) ?>';
const REDIRECT  = '<?= esc_js($redirect) ?>';

console.log('[Cicafood Auth] AJAX_URL:', AJAX_URL);
console.log('[Cicafood Auth] NONCE:', NONCE);

// ---- Role card selection ----
document.querySelectorAll('.role-radio').forEach(radio => {
    radio.addEventListener('change', function() {
        document.querySelectorAll('.role-card').forEach(card => {
            card.style.borderColor = '#e5e7eb';
            card.style.background  = 'white';
            card.querySelector('i').style.color = '#9ca3af';
        });
        const card = document.querySelector(`.role-card[data-role="${this.value}"]`);
        if (card) {
            card.style.borderColor = '#ee2624';
            card.style.background  = '#fef2f2';
            card.querySelector('i').style.color = '#ee2624';
        }
        const note = document.getElementById('merchant-note');
        if (note) note.style.display = this.value === 'merchant_request' ? 'block' : 'none';
    });
});

// ---- Toggle password visibility ----
function togglePw(id, btn) {
    const input = document.getElementById(id);
    const icon  = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'fas fa-eye';
    }
}

// ---- Password strength ----
const pwInput = document.getElementById('reg-password');
if (pwInput) {
    pwInput.addEventListener('input', function() {
        const val = this.value;
        const bar = document.getElementById('pw-strength-bar');
        let strength = 0;
        if (val.length >= 6)  strength++;
        if (val.length >= 10) strength++;
        if (/[A-Z]/.test(val)) strength++;
        if (/[0-9]/.test(val)) strength++;
        if (/[^A-Za-z0-9]/.test(val)) strength++;
        const colors = ['', '#ef4444', '#f97316', '#eab308', '#22c55e', '#16a34a'];
        const widths = ['0%', '20%', '40%', '60%', '80%', '100%'];
        bar.style.width    = widths[strength] || '0%';
        bar.style.background = colors[strength] || '#f3f4f6';
    });
}

// ---- AJAX Login ----
const loginForm = document.getElementById('login-form');
if (loginForm) {
    loginForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const btn = document.getElementById('login-btn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang đăng nhập...';

        const fd = new FormData();
        fd.append('action',   'cica_ajax_login');
        fd.append('nonce',    NONCE);
        fd.append('username', document.getElementById('login-username').value);
        fd.append('password', document.getElementById('login-password').value);
        fd.append('remember', loginForm.querySelector('[name="remember"]').checked ? '1' : '0');

        fetch(AJAX_URL, { method: 'POST', body: fd })
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    showMsg('success', '✅ Đăng nhập thành công! Đang chuyển hướng...');
                    setTimeout(() => { window.location.href = d.redirect; }, 800);
                } else {
                    showMsg('error', d.message);
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-right-to-bracket"></i> Đăng nhập';
                }
            })
            .catch((err) => {
                console.error('[Cicafood Login Error]', err);
                showMsg('error', 'Lỗi kết nối: ' + (err.message || 'Không xác định') + '. URL: ' + AJAX_URL);
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-right-to-bracket"></i> Đăng nhập';
            });
    });
}

// ---- AJAX Register ----
const regForm = document.getElementById('register-form');
if (regForm) {
    regForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const btn = document.getElementById('register-btn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang tạo tài khoản...';

        const regUsername = document.getElementById('reg-username').value;
        const regPassword = document.getElementById('reg-password').value;

        const fd = new FormData();
        fd.append('action',   'cica_ajax_register');
        fd.append('nonce',    NONCE);
        fd.append('fullname', document.getElementById('reg-fullname').value);
        fd.append('username', regUsername);
        fd.append('email',    document.getElementById('reg-email').value);
        fd.append('password', regPassword);
        fd.append('role',     document.querySelector('input[name="role"]:checked')?.value || 'customer');

        fetch(AJAX_URL, { method: 'POST', body: fd })
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    // Hiện thông báo thành công
                    showMsg('success', '🎉 Tạo tài khoản thành công! Vui lòng đăng nhập để tiếp tục.');

                    // Sau 1.5s: chuyển sang tab đăng nhập và pre-fill thông tin
                    setTimeout(() => {
                        // Chuyển URL sang mode=login
                        const url = new URL(window.location.href);
                        url.searchParams.set('mode', 'login');
                        window.history.pushState({}, '', url);

                        // Ẩn form đăng ký, hiện form đăng nhập
                        document.getElementById('register-form').closest('.auth-card').innerHTML = buildLoginForm(regUsername, regPassword, d.message);

                        // Gắn lại event listener cho login form mới
                        attachLoginHandler();
                    }, 1500);
                } else {
                    showMsg('error', d.message);
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-user-plus"></i> Tạo tài khoản';
                }
            })
            .catch((err) => {
                console.error('[Cicafood Register Error]', err);
                showMsg('error', 'Lỗi kết nối: ' + (err.message || 'Không xác định'));
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-user-plus"></i> Tạo tài khoản';
            });
    });
}

function buildLoginForm(prefillUser, prefillPw, successNote) {
    return `
    <div class="auth-logo">
        <a href="${SITE_URL}">
            <div class="icon"><i class="fas fa-utensils" style="color:white;font-size:1.2rem"></i></div>
            <span>Cicafood</span>
        </a>
        <p style="font-size:0.85rem;color:#9ca3af;margin-top:0.5rem">Chào mừng trở lại!</p>
    </div>
    <div class="auth-tabs">
        <a href="?mode=login" class="auth-tab active"><i class="fas fa-right-to-bracket"></i> Đăng nhập</a>
        <a href="?mode=register" class="auth-tab"><i class="fas fa-user-plus"></i> Đăng ký</a>
    </div>
    <div class="success-box" style="margin-bottom:1rem">
        <i class="fas fa-check-circle"></i> Đăng ký thành công! Hãy đăng nhập để tiếp tục.
    </div>
    <div id="auth-msg" style="display:none"></div>
    <form id="login-form-new">
        <div class="form-group">
            <label>Tên đăng nhập hoặc Email</label>
            <div class="input-wrap">
                <i class="fas fa-user input-icon"></i>
                <input type="text" id="login-username" value="${prefillUser}" placeholder="username hoặc email@..." required autocomplete="username"
                       style="width:100%;padding:0.75rem 1rem 0.75rem 2.5rem;border:1.5px solid #e5e7eb;border-radius:12px;font-size:0.9rem;outline:none;font-family:inherit">
            </div>
        </div>
        <div class="form-group">
            <label>Mật khẩu</label>
            <div class="input-wrap">
                <i class="fas fa-lock input-icon"></i>
                <input type="password" id="login-password" value="${prefillPw}" placeholder="••••••••" required autocomplete="current-password"
                       style="width:100%;padding:0.75rem 1rem 0.75rem 2.5rem;border:1.5px solid #e5e7eb;border-radius:12px;font-size:0.9rem;outline:none;font-family:inherit">
                <button type="button" class="pw-toggle" onclick="togglePw('login-password', this)"><i class="fas fa-eye"></i></button>
            </div>
        </div>
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem">
            <label style="display:flex;align-items:center;gap:0.4rem;font-size:0.85rem;color:#374151;cursor:pointer;font-weight:500">
                <input type="checkbox" id="login-remember" style="accent-color:#ee2624"> Ghi nhớ đăng nhập
            </label>
        </div>
        <button type="submit" class="btn-primary" id="login-btn">
            <i class="fas fa-right-to-bracket"></i> Đăng nhập ngay
        </button>
    </form>`;
}

function attachLoginHandler() {
    const form = document.getElementById('login-form-new');
    if (!form) return;
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const btn = document.getElementById('login-btn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang đăng nhập...';

        const fd = new FormData();
        fd.append('action',   'cica_ajax_login');
        fd.append('nonce',    NONCE);
        fd.append('username', document.getElementById('login-username').value);
        fd.append('password', document.getElementById('login-password').value);
        fd.append('remember', document.getElementById('login-remember')?.checked ? '1' : '0');

        fetch(AJAX_URL, { method: 'POST', body: fd })
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    // Hiện thông báo và redirect
                    const roleLabel = d.role === 'merchant' ? '🏪 Chủ nhà hàng' : '👤 Khách hàng';
                    showMsg('success', `✅ Đăng nhập thành công với vai trò ${roleLabel}! Đang chuyển hướng...`);
                    setTimeout(() => { window.location.href = d.redirect; }, 1200);
                } else {
                    showMsg('error', d.message);
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-right-to-bracket"></i> Đăng nhập ngay';
                }
            })
            .catch(() => {
                showMsg('error', 'Lỗi kết nối. Vui lòng thử lại.');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-right-to-bracket"></i> Đăng nhập ngay';
            });
    });
}

function showMsg(type, msg) {
    const el = document.getElementById('auth-msg');
    if (!el) return;
    el.style.display = 'flex';
    el.className = type === 'success' ? 'success-box' : 'error-box';
    el.innerHTML = (type === 'success' ? '<i class="fas fa-check-circle"></i> ' : '<i class="fas fa-triangle-exclamation"></i> ') + msg;
    el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}
</script>
