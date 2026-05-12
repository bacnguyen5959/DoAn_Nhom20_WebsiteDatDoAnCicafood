<!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php
// Đặt tiêu đề trang theo URL
$request = $_SERVER['REQUEST_URI'] ?? '';
$page_titles = [
    '/nha-hang/'           => 'Nhà hàng',
    '/thanh-toan/'         => 'Thanh toán',
    '/dat-hang-thanh-cong/'=> 'Đặt hàng thành công',
    '/don-hang/'           => 'Đơn hàng của tôi',
    '/voucher/'            => 'Kho Voucher',
    '/ho-so/'              => 'Hồ sơ',
    '/yeu-thich/'          => 'Yêu thích',
    '/quan-ly/'            => 'Quản lý quán',
    '/dang-nhap/'          => 'Đăng nhập',
];
$page_title = 'Cicafood — Đặt đồ ăn trực tuyến';
foreach ($page_titles as $path => $title) {
    if (strpos($request, $path) !== false) {
        $page_title = $title . ' — Cicafood';
        break;
    }
}
// Trang chi tiết nhà hàng
if (preg_match('#/nha-hang/([^/?]+)/#', $request, $m)) {
    global $wpdb;
    $rt   = $wpdb->prefix . 'cica_restaurants';
    $name = $wpdb->get_var($wpdb->prepare("SELECT name FROM $rt WHERE slug=%s LIMIT 1", $m[1]));
    if ($name) $page_title = esc_html($name) . ' — Cicafood';
}
?>
<title><?= esc_html($page_title) ?></title>
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<?php
$site_url    = get_site_url();
$cart_count  = 0;
if (class_exists('Cicafood_Cart')) {
    if (!session_id()) session_start();
    $cart_count = Cicafood_Cart::count();
}
$current_user = wp_get_current_user();
$is_logged    = is_user_logged_in();
?>

<!-- ============================================================
     CICAFOOD HEADER
     ============================================================ -->
<header id="cica-header" style="background:#ee2624;position:sticky;top:0;z-index:1000;box-shadow:0 2px 12px rgba(238,38,36,0.25)">

    <!-- Top bar -->
    <div style="background:rgba(0,0,0,0.15);padding:6px 0;font-size:0.75rem;color:rgba(255,255,255,0.85);text-align:center;letter-spacing:0.02em">
        🚚 Freeship đơn đầu · Giao hàng trong 30 phút · Hotline: <strong>1800-CICA</strong>
    </div>

    <!-- Main header -->
    <div style="max-width:1280px;margin:0 auto;padding:0 1.25rem;display:flex;align-items:center;gap:1rem;height:64px">

        <!-- Logo -->
        <a href="<?= esc_url($site_url) ?>/" style="display:flex;align-items:center;gap:0.6rem;text-decoration:none;flex-shrink:0">
            <div style="width:36px;height:36px;background:white;border-radius:10px;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 8px rgba(0,0,0,0.15)">
                <i class="fas fa-utensils" style="color:#ee2624;font-size:1rem"></i>
            </div>
            <span style="font-size:1.4rem;font-weight:900;color:white;letter-spacing:-0.5px">Cicafood</span>
        </a>

        <!-- Search bar (desktop) -->
        <div style="flex:1;max-width:480px;margin:0 1rem;position:relative" id="header-search-wrap">
            <div style="display:flex;align-items:center;background:rgba(255,255,255,0.15);border:1.5px solid rgba(255,255,255,0.3);border-radius:999px;padding:0 1rem;gap:0.5rem;transition:background 0.2s"
                 onmouseover="this.style.background='rgba(255,255,255,0.22)'" onmouseout="this.style.background='rgba(255,255,255,0.15)'">
                <i class="fas fa-search" style="color:rgba(255,255,255,0.7);font-size:0.85rem"></i>
                <input type="text" id="header-search" placeholder="Tìm món ngon, quán quen..."
                       autocomplete="off"
                       style="flex:1;background:transparent;border:none;outline:none;color:white;font-size:0.875rem;padding:8px 0"
                       onkeydown="if(event.key==='Enter'){window.location.href='<?= esc_url($site_url) ?>/nha-hang/?q='+encodeURIComponent(this.value);document.getElementById('search-dropdown').style.display='none'}"
                       oninput="searchAutocomplete(this.value)"
                       onfocus="this.parentElement.style.background='rgba(255,255,255,0.25)';if(this.value.length>=2)searchAutocomplete(this.value)"
                       onblur="this.parentElement.style.background='rgba(255,255,255,0.15)';setTimeout(()=>{document.getElementById('search-dropdown').style.display='none'},200)">
                <button onclick="window.location.href='<?= esc_url($site_url) ?>/nha-hang/?q='+encodeURIComponent(document.getElementById('header-search').value)"
                        style="background:white;border:none;border-radius:999px;padding:4px 14px;font-size:0.8rem;font-weight:700;color:#ee2624;cursor:pointer;white-space:nowrap">
                    Tìm
                </button>
            </div>
            <!-- Autocomplete dropdown -->
            <div id="search-dropdown"
                 style="display:none;position:absolute;top:calc(100% + 8px);left:0;right:0;background:white;border-radius:16px;box-shadow:0 8px 32px rgba(0,0,0,0.15);z-index:500;overflow:hidden;max-height:400px;overflow-y:auto">
            </div>
        </div>

        <!-- Nav links (desktop) -->
        <nav style="display:flex;align-items:center;gap:0.25rem;margin-left:auto" class="cica-nav-desktop">
            <?php
            $nav_items = [
                ['label' => '<i class="fas fa-ticket"></i> Voucher',   'url' => $site_url . '/voucher/'],
                ['label' => '<i class="fas fa-store"></i> Nhà hàng',   'url' => $site_url . '/nha-hang/'],
                ['label' => '<i class="fas fa-receipt"></i> Đơn hàng', 'url' => $site_url . '/don-hang/'],
            ];
            // Thêm link quản lý quán nếu user có nhà hàng
            if ($is_logged) {
                if (current_user_can('cica_manage_restaurant')) {
                    $nav_items[] = ['label' => '<i class="fas fa-chart-line"></i> Quản lý quán <span id="merchant-order-badge" style="display:none;background:white;color:#ee2624;font-size:0.65rem;font-weight:900;padding:1px 6px;border-radius:999px;margin-left:2px">0</span>', 'url' => $site_url . '/quan-ly/'];
                }
            }
            foreach ($nav_items as $item):
            ?>
            <a href="<?= esc_url($item['url']) ?>"
               style="display:flex;align-items:center;gap:0.4rem;padding:0.45rem 0.85rem;border-radius:999px;font-size:0.85rem;font-weight:600;color:rgba(255,255,255,0.9);text-decoration:none;transition:background 0.2s;white-space:nowrap"
               onmouseover="this.style.background='rgba(255,255,255,0.18)'" onmouseout="this.style.background='transparent'">
                <?= $item['label'] ?>
            </a>
            <?php endforeach; ?>
        </nav>

        <!-- Cart button -->
        <a href="<?= esc_url($site_url) ?>/thanh-toan/"
           style="position:relative;display:flex;align-items:center;gap:0.5rem;background:rgba(255,255,255,0.15);border:1.5px solid rgba(255,255,255,0.3);border-radius:999px;padding:0.45rem 1rem;color:white;text-decoration:none;font-weight:700;font-size:0.85rem;transition:background 0.2s;flex-shrink:0"
           onmouseover="this.style.background='rgba(255,255,255,0.25)'" onmouseout="this.style.background='rgba(255,255,255,0.15)'">
            <i class="fas fa-shopping-bag"></i>
            <span id="cica-cart-label" style="white-space:nowrap">Giỏ hàng</span>
            <span id="cica-cart-badge"
                  style="display:<?= $cart_count > 0 ? 'flex' : 'none' ?>;position:absolute;top:-6px;right:-6px;width:20px;height:20px;background:white;color:#ee2624;border-radius:50%;font-size:0.7rem;font-weight:900;align-items:center;justify-content:center;box-shadow:0 2px 6px rgba(0,0,0,0.2)">
                <?= $cart_count ?>
            </span>
        </a>

        <!-- User menu -->
        <?php if ($is_logged): ?>
        <div style="position:relative" id="cica-user-menu-wrap">
            <button onclick="document.getElementById('cica-user-dropdown').classList.toggle('cica-dd-open')"
                    style="display:flex;align-items:center;gap:0.5rem;background:rgba(255,255,255,0.15);border:1.5px solid rgba(255,255,255,0.3);border-radius:999px;padding:0.4rem 0.85rem;color:white;cursor:pointer;font-weight:600;font-size:0.85rem;transition:background 0.2s"
                    onmouseover="this.style.background='rgba(255,255,255,0.25)'" onmouseout="this.style.background='rgba(255,255,255,0.15)'">
                <div style="width:26px;height:26px;background:white;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:900;font-size:0.75rem;color:#ee2624">
                    <?= strtoupper(mb_substr($current_user->display_name ?: 'U', 0, 1)) ?>
                </div>
                <span style="max-width:80px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= esc_html($current_user->display_name) ?></span>
                <i class="fas fa-chevron-down" style="font-size:0.65rem;opacity:0.8"></i>
            </button>
            <div id="cica-user-dropdown"
                 style="display:none;position:absolute;right:0;top:calc(100% + 8px);background:white;border-radius:16px;box-shadow:0 8px 32px rgba(0,0,0,0.15);min-width:200px;overflow:hidden;z-index:200">
                <div style="padding:1rem;border-bottom:1px solid #f3f4f6;background:#fef2f2">
                    <p style="font-weight:700;font-size:0.875rem;color:#111827;margin:0"><?= esc_html($current_user->display_name) ?></p>
                    <p style="font-size:0.75rem;color:#6b7280;margin:0.2rem 0 0"><?= esc_html($current_user->user_email) ?></p>
                </div>
                <?php
                $dd_items = [
                    ['fa-user',    'Hồ sơ',      $site_url . '/ho-so/'],
                    ['fa-receipt', 'Đơn hàng',   $site_url . '/don-hang/'],
                    ['fa-ticket',  'Voucher',     $site_url . '/voucher/'],
                    ['fa-heart',   'Yêu thích',  $site_url . '/yeu-thich/'],
                ];
                foreach ($dd_items as [$icon, $label, $url]):
                ?>
                <a href="<?= esc_url($url) ?>"
                   style="display:flex;align-items:center;gap:0.75rem;padding:0.75rem 1rem;font-size:0.875rem;font-weight:500;color:#374151;text-decoration:none;transition:background 0.15s"
                   onmouseover="this.style.background='#fef2f2';this.style.color='#ee2624'" onmouseout="this.style.background='';this.style.color='#374151'">
                    <i class="fas <?= $icon ?>" style="width:16px;text-align:center;color:#ee2624"></i> <?= $label ?>
                </a>
                <?php endforeach; ?>
                <div style="border-top:1px solid #f3f4f6">
                    <a href="<?= esc_url(wp_logout_url($site_url)) ?>"
                       style="display:flex;align-items:center;gap:0.75rem;padding:0.75rem 1rem;font-size:0.875rem;font-weight:600;color:#dc2626;text-decoration:none;transition:background 0.15s"
                       onmouseover="this.style.background='#fef2f2'" onmouseout="this.style.background=''">
                        <i class="fas fa-right-from-bracket" style="width:16px;text-align:center"></i> Đăng xuất
                    </a>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div style="display:flex;gap:0.5rem;flex-shrink:0">
            <a href="<?= esc_url(get_site_url() . '/dang-nhap/?mode=login') ?>"
               style="padding:0.45rem 1rem;border-radius:999px;font-size:0.85rem;font-weight:700;color:white;text-decoration:none;border:1.5px solid rgba(255,255,255,0.5);transition:background 0.2s"
               onmouseover="this.style.background='rgba(255,255,255,0.15)'" onmouseout="this.style.background='transparent'">
                Đăng nhập
            </a>
            <a href="<?= esc_url(get_site_url() . '/dang-nhap/?mode=register') ?>"
               style="padding:0.45rem 1rem;border-radius:999px;font-size:0.85rem;font-weight:700;color:#ee2624;text-decoration:none;background:white;transition:opacity 0.2s"
               onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                Đăng ký
            </a>
        </div>
        <?php endif; ?>

        <!-- Mobile menu toggle -->
        <button id="cica-mobile-toggle" onclick="document.getElementById('cica-mobile-menu').classList.toggle('cica-mobile-open')"
                style="display:none;background:rgba(255,255,255,0.15);border:1.5px solid rgba(255,255,255,0.3);border-radius:10px;padding:0.5rem 0.65rem;color:white;cursor:pointer;font-size:1rem">
            <i class="fas fa-bars"></i>
        </button>
    </div>

    <!-- Mobile menu -->
    <div id="cica-mobile-menu" style="display:none;background:rgba(0,0,0,0.2);border-top:1px solid rgba(255,255,255,0.15);padding:0.75rem 1.25rem 1rem">
        <div style="display:flex;flex-direction:column;gap:0.25rem">
            <a href="<?= esc_url($site_url) ?>/nha-hang/" style="padding:0.6rem 0.75rem;color:white;text-decoration:none;font-weight:600;font-size:0.9rem;border-radius:10px;display:flex;align-items:center;gap:0.6rem" onmouseover="this.style.background='rgba(255,255,255,0.15)'" onmouseout="this.style.background=''"><i class="fas fa-store"></i> Nhà hàng</a>
            <a href="<?= esc_url($site_url) ?>/don-hang/" style="padding:0.6rem 0.75rem;color:white;text-decoration:none;font-weight:600;font-size:0.9rem;border-radius:10px;display:flex;align-items:center;gap:0.6rem" onmouseover="this.style.background='rgba(255,255,255,0.15)'" onmouseout="this.style.background=''"><i class="fas fa-receipt"></i> Đơn hàng</a>
            <a href="<?= esc_url($site_url) ?>/voucher/" style="padding:0.6rem 0.75rem;color:white;text-decoration:none;font-weight:600;font-size:0.9rem;border-radius:10px;display:flex;align-items:center;gap:0.6rem" onmouseover="this.style.background='rgba(255,255,255,0.15)'" onmouseout="this.style.background=''"><i class="fas fa-ticket"></i> Voucher</a>
            <?php if ($is_logged): ?>
            <a href="<?= esc_url($site_url) ?>/ho-so/" style="padding:0.6rem 0.75rem;color:white;text-decoration:none;font-weight:600;font-size:0.9rem;border-radius:10px;display:flex;align-items:center;gap:0.6rem" onmouseover="this.style.background='rgba(255,255,255,0.15)'" onmouseout="this.style.background=''"><i class="fas fa-user"></i> Hồ sơ</a>
            <a href="<?= esc_url(wp_logout_url($site_url)) ?>" style="padding:0.6rem 0.75rem;color:#fca5a5;text-decoration:none;font-weight:600;font-size:0.9rem;border-radius:10px;display:flex;align-items:center;gap:0.6rem"><i class="fas fa-right-from-bracket"></i> Đăng xuất</a>
            <?php else: ?>
            <a href="<?= esc_url(wp_login_url()) ?>" style="padding:0.6rem 0.75rem;color:white;text-decoration:none;font-weight:600;font-size:0.9rem;border-radius:10px;display:flex;align-items:center;gap:0.6rem"><i class="fas fa-right-to-bracket"></i> Đăng nhập</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<style>
/* Dropdown toggle */
.cica-dd-open { display:block !important; }

/* Mobile responsive */
@media (max-width: 768px) {
    .cica-nav-desktop { display:none !important; }
    #cica-mobile-toggle { display:block !important; }
    #header-search { display:none; }
    #header-search + button { display:none; }
}
@media (max-width: 900px) {
    .cica-nav-desktop { display:none !important; }
    #cica-mobile-toggle { display:block !important; }
}
.cica-mobile-open { display:block !important; }

/* Hide Storefront default header elements */
.site-header .col-full,
.storefront-handheld-footer-bar,
.storefront-sticky-add-to-cart { display:none !important; }
#masthead { display:none !important; }
</style>

<script>
// Close dropdown when clicking outside
document.addEventListener('click', function(e) {
    const wrap = document.getElementById('cica-user-menu-wrap');
    if (wrap && !wrap.contains(e.target)) {
        const dd = document.getElementById('cica-user-dropdown');
        if (dd) dd.classList.remove('cica-dd-open');
    }
});

// Autocomplete search
let searchTimer = null;
function searchAutocomplete(q) {
    const dd = document.getElementById('search-dropdown');
    if (!q || q.length < 2) { dd.style.display = 'none'; return; }
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        const ajaxUrl = (window.cicafoodAjax && window.cicafoodAjax.ajaxurl)
            ? window.cicafoodAjax.ajaxurl
            : '<?= esc_js(site_url('/wp-admin/admin-ajax.php')) ?>';
        fetch(ajaxUrl + '?action=cica_search&q=' + encodeURIComponent(q))
            .then(r => r.json())
            .then(d => {
                const { items = [], restaurants = [] } = d.results || {};
                if (!items.length && !restaurants.length) { dd.style.display = 'none'; return; }
                let html = '';
                if (items.length) {
                    html += '<div style="padding:8px 12px;font-size:11px;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:0.05em;border-bottom:1px solid #f3f4f6">Món ăn</div>';
                    items.forEach(item => {
                        html += `<a href="<?= esc_js($site_url) ?>/nha-hang/${item.restaurant_slug}/"
                            style="display:flex;align-items:center;gap:10px;padding:10px 14px;text-decoration:none;color:#374151;transition:background 0.15s"
                            onmouseover="this.style.background='#fef2f2'" onmouseout="this.style.background=''">
                            <img src="${item.image || 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=60&q=80'}"
                                 style="width:40px;height:40px;border-radius:8px;object-fit:cover;flex-shrink:0">
                            <div style="flex:1;min-width:0">
                                <p style="font-weight:600;font-size:13px;margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${item.name}</p>
                                <p style="font-size:11px;color:#9ca3af;margin:0">${item.restaurant_name}</p>
                            </div>
                            <span style="font-size:12px;font-weight:700;color:#ee2624;flex-shrink:0">${parseInt(item.price).toLocaleString('vi-VN')}đ</span>
                        </a>`;
                    });
                }
                if (restaurants.length) {
                    html += '<div style="padding:8px 12px;font-size:11px;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:0.05em;border-top:1px solid #f3f4f6;border-bottom:1px solid #f3f4f6">Nhà hàng</div>';
                    restaurants.forEach(r => {
                        html += `<a href="<?= esc_js($site_url) ?>/nha-hang/${r.slug}/"
                            style="display:flex;align-items:center;gap:10px;padding:10px 14px;text-decoration:none;color:#374151;transition:background 0.15s"
                            onmouseover="this.style.background='#fef2f2'" onmouseout="this.style.background=''">
                            <div style="width:40px;height:40px;border-radius:8px;background:#fef2f2;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                                <i class="fas fa-store" style="color:#ee2624;font-size:14px"></i>
                            </div>
                            <p style="font-weight:600;font-size:13px;margin:0">${r.name}</p>
                        </a>`;
                    });
                }
                html += `<a href="<?= esc_js($site_url) ?>/nha-hang/?q=${encodeURIComponent(q)}"
                    style="display:block;padding:10px 14px;text-align:center;font-size:12px;font-weight:700;color:#ee2624;text-decoration:none;border-top:1px solid #f3f4f6;background:#fafafa">
                    Xem tất cả kết quả cho "${q}" →
                </a>`;
                dd.innerHTML = html;
                dd.style.display = 'block';
            })
            .catch(() => { dd.style.display = 'none'; });
    }, 250);
}

<?php if ($is_logged && current_user_can('cica_manage_restaurant')): ?>
// ============================================================
// MERCHANT — Auto-refresh badge đơn hàng chờ xử lý
// ============================================================
(function() {
    const AJAX  = (window.cicafoodAjax && window.cicafoodAjax.ajaxurl)
                ? window.cicafoodAjax.ajaxurl
                : '<?= esc_js(site_url('/wp-admin/admin-ajax.php')) ?>';
    const badge = document.getElementById('merchant-order-badge');
    let lastCount = 0;

    function fetchPendingOrders() {
        fetch(AJAX + '?action=cica_get_pending_orders')
            .then(r => r.json())
            .then(d => {
                const count = d.count || 0;
                if (!badge) return;
                if (count > 0) {
                    badge.textContent = count;
                    badge.style.display = 'inline-block';
                    // Thong bao khi co don moi
                    if (count > lastCount && lastCount >= 0 && document.hidden === false) {
                        if (typeof showCicaToast === 'function') {
                            showCicaToast('info', '🔔 Có ' + count + ' đơn hàng đang chờ xác nhận!');
                        }
                        // Doi mau badge nhay
                        badge.style.animation = 'cicaBadgePulse 0.5s ease 3';
                    }
                    lastCount = count;
                } else {
                    badge.style.display = 'none';
                    lastCount = 0;
                }
            })
            .catch(() => {});
    }

    // Chay ngay khi load
    fetchPendingOrders();
    // Refresh moi 30 giay
    setInterval(fetchPendingOrders, 30000);
})();
<?php endif; ?>

// ===== PAGE LOADING BAR =====
(function() {
    const bar = document.getElementById('cica-loading-bar');
    if (!bar) return;

    function start() {
        bar.className = '';
        bar.style.width = '0%';
        bar.style.opacity = '1';
        bar.style.transition = '';
        bar.offsetWidth; // trigger reflow
        bar.className = 'loading';
    }

    function done() {
        bar.className = 'done';
        setTimeout(() => {
            bar.style.opacity = '0';
            bar.style.transition = 'opacity 0.3s';
            setTimeout(() => {
                bar.style.width = '0%';
                bar.style.opacity = '1';
                bar.style.transition = '';
                bar.className = '';
            }, 300);
        }, 200);
    }

    document.addEventListener('click', function(e) {
        const a = e.target.closest('a');
        if (!a) return;
        const href = a.getAttribute('href');
        if (!href || href.startsWith('#') || href.startsWith('javascript') || a.target === '_blank' || a.hasAttribute('download')) return;
        try {
            const url = new URL(href, window.location.origin);
            if (url.origin !== window.location.origin) return;
        } catch(err) { return; }
        start();
    });

    window.addEventListener('pageshow', done);
    if (document.readyState === 'complete') done();
    else window.addEventListener('load', done);
})();
</script>

<style>
@keyframes cicaBadgePulse {
    0%   { transform: scale(1); }
    50%  { transform: scale(1.3); background: #fbbf24; color: #111; }
    100% { transform: scale(1); }
}

/* ===== PAGE LOADING BAR ===== */
#cica-loading-bar {
    position: fixed;
    top: 0; left: 0;
    height: 3px;
    background: linear-gradient(90deg, #fff, rgba(255,255,255,0.8));
    z-index: 99999;
    width: 0%;
    transition: width 0.3s ease;
    box-shadow: 0 0 8px rgba(255,255,255,0.6);
    pointer-events: none;
}
#cica-loading-bar.loading { width: 85%; transition: width 8s cubic-bezier(0.1,0.05,0,1); }
#cica-loading-bar.done    { width: 100%; transition: width 0.2s ease; }
</style>

<div id="page" class="hfeed site" style="padding-top:0">
<div id="cica-loading-bar"></div>
<div id="content" class="site-content">
<div class="col-full" style="padding:0">
