<?php
defined('ABSPATH') || exit;
if (!session_id()) session_start();

// Lấy dữ liệu
$featured_restaurants = Cicafood_Restaurant::get_list(['limit' => 6, 'sort' => 'rating_desc']);
$deal_restaurants     = Cicafood_Restaurant::get_list(['has_deal' => 1, 'limit' => 4]);
$categories           = Cicafood_Restaurant::get_categories();
$public_vouchers      = Cicafood_Voucher::get_available(0, 0);
$public_vouchers      = array_slice(array_filter($public_vouchers, fn($v) => !$v['restaurant_id']), 0, 3);

$user_favorites = [];
if (is_user_logged_in()) {
    global $wpdb;
    $fav_table = $wpdb->prefix . 'cica_favorites';
    $user_favorites = $wpdb->get_col($wpdb->prepare(
        "SELECT restaurant_id FROM $fav_table WHERE wp_user_id = %d", get_current_user_id()
    ));
}

$site_url = get_site_url();
?>

<!-- HERO SECTION — copy từ foodbooking/views/home/index.php -->
<section class="relative overflow-hidden" style="background:linear-gradient(135deg,#1a0a00 0%,#2d0e0e 40%,#ee2624 100%)">
    <div class="max-w-screen-xl mx-auto px-6 py-16 md:py-24 relative z-10">
        <div class="max-w-3xl mx-auto text-center">
            <div class="inline-flex items-center gap-2 bg-white/10 border border-white/20 rounded-full px-4 py-2 text-white/90 text-sm font-medium mb-6">
                <span class="w-2 h-2 bg-green-400 rounded-full" style="animation:pulse 2s infinite"></span>
                Đang giao hàng tại 63+ tỉnh thành
            </div>
            <h1 class="text-4xl md:text-6xl font-black text-white leading-tight mb-6">
                Thèm gì là có,<br>
                <span class="text-yellow-300">Cicafood</span> giao ngay! 🔥
            </h1>
            <p class="text-white/80 text-lg mb-10 font-medium">
                Hơn <strong class="text-yellow-300">500+ quán ngon</strong> · Giao hàng trong <strong class="text-yellow-300">30 phút</strong> · Freeship đơn đầu
            </p>
            <div class="bg-white rounded-2xl md:rounded-full p-2 shadow-2xl flex flex-col md:flex-row gap-2">
                <div class="flex-1 flex items-center bg-gray-50 rounded-xl md:rounded-full px-5 gap-3">
                    <i class="fas fa-search text-gray-400"></i>
                    <input type="text" id="hero-search" placeholder="Tìm món ngon, quán quen..."
                           class="flex-1 py-3.5 bg-transparent outline-none text-gray-700 text-base">
                </div>
                <button onclick="heroSearch()"
                        class="bg-cica-red text-white px-8 py-3.5 rounded-xl md:rounded-full font-bold text-base hover:bg-red-700 transition flex items-center gap-2 justify-center"
                        style="background:#ee2624">
                    <i class="fas fa-search"></i> Tìm kiếm
                </button>
            </div>
        </div>
    </div>
</section>

<!-- CATEGORY PILLS -->
<section class="max-w-screen-xl mx-auto px-4 py-8">
    <div class="flex items-center gap-3 overflow-x-auto pb-2" style="scrollbar-width:none">
        <span class="text-sm font-bold text-gray-700 whitespace-nowrap">Lọc nhanh:</span>
        <?php foreach ($categories as $cat): ?>
        <a href="<?= $site_url ?>/nha-hang/?category=<?= esc_attr($cat['slug']) ?>"
           class="flex-shrink-0 flex items-center gap-2 bg-white border border-gray-200 rounded-full px-4 py-2 text-sm font-semibold text-gray-600 hover:bg-red-50 hover:border-red-300 transition shadow-sm"
           style="text-decoration:none">
            <i class="fas <?= esc_attr($cat['icon']) ?>" style="color:<?= esc_attr($cat['color']) ?>"></i>
            <?= esc_html($cat['name']) ?>
        </a>
        <?php endforeach; ?>
    </div>
</section>

<!-- PROMOTIONAL BANNERS -->
<section class="max-w-screen-xl mx-auto px-4 mb-10">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="md:col-span-2 relative rounded-3xl p-8 overflow-hidden shadow-lg" style="background:linear-gradient(135deg,#dc2626,#f97316)">
            <div class="relative z-10">
                <span class="bg-white/20 text-white text-xs font-bold px-3 py-1 rounded-full uppercase">Chào Giao Hàng</span>
                <h2 class="text-white text-4xl font-black mt-3 mb-2">FREESHIP<br>Đơn Đầu Tiên!</h2>
                <p class="text-white/80 text-sm mb-5">Nhập <strong class="text-yellow-300">FREESHIP</strong> khi thanh toán</p>
                <a href="<?= $site_url ?>/nha-hang/" class="inline-flex items-center gap-2 bg-white px-6 py-3 rounded-xl font-black text-sm shadow-lg" style="color:#ee2624;text-decoration:none">
                    Đặt ngay <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            <i class="fas fa-motorcycle absolute bottom-0 right-6 text-white/10" style="font-size:140px"></i>
        </div>
        <div class="flex flex-col gap-4">
            <?php
            $colors = [['#f97316','#eab308'],['#8b5cf6','#6366f1'],['#22c55e','#14b8a6']];
            $ci = 0;
            foreach (array_values($public_vouchers) as $pv):
                [$c1,$c2] = $colors[$ci % 3]; $ci++;
            ?>
            <div class="rounded-2xl p-5 flex items-center justify-between hover:shadow-lg transition" style="background:linear-gradient(135deg,<?= $c1 ?>,<?= $c2 ?>)">
                <div>
                    <p class="text-white/80 font-bold text-xs uppercase mb-1"><?= $pv['type'] === 'freeship' ? 'Freeship' : 'Giảm giá' ?></p>
                    <h3 class="text-white font-black text-lg"><?= esc_html($pv['name']) ?></h3>
                    <p class="text-white/70 text-xs font-mono">Mã: <?= esc_html($pv['code']) ?></p>
                </div>
                <?php if (is_user_logged_in()): ?>
                <button onclick="claimVoucher(<?= (int)$pv['id'] ?>, this)"
                        class="text-xs font-black bg-white/90 hover:bg-white px-3 py-1.5 rounded-full transition"
                        style="color:#374151;border:none;cursor:pointer">
                    🎁 Lấy mã
                </button>
                <?php else: ?>
                <a href="<?= wp_login_url(get_permalink()) ?>" class="text-xs font-black bg-white/30 text-white px-3 py-1.5 rounded-full" style="text-decoration:none">Đăng nhập</a>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- QUÁN NGON NỔI BẬT -->
<section class="max-w-screen-xl mx-auto px-4 mb-12">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-black text-gray-900">🔥 Quán Ngon Nổi Bật</h2>
            <p class="text-sm text-gray-500 mt-1">Được yêu thích nhất</p>
        </div>
        <a href="<?= $site_url ?>/nha-hang/" class="text-sm font-bold hover:underline" style="color:#ee2624;text-decoration:none">Xem tất cả <i class="fas fa-arrow-right"></i></a>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
        <?php foreach ($featured_restaurants as $r): ?>
        <div class="bg-white rounded-2xl overflow-hidden shadow-sm border border-gray-100 group" style="transition:transform 0.2s,box-shadow 0.2s" onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='0 20px 40px rgba(0,0,0,0.1)'" onmouseout="this.style.transform='';this.style.boxShadow=''">
            <a href="<?= $site_url ?>/nha-hang/<?= esc_attr($r['slug']) ?>/" style="text-decoration:none;color:inherit;display:block">
                <div class="relative overflow-hidden" style="height:192px">
                    <img src="<?= esc_url($r['image'] ?: 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?auto=format&fit=crop&w=800&q=80') ?>"
                         alt="<?= esc_attr($r['name']) ?>"
                         style="width:100%;height:100%;object-fit:cover;transition:transform 0.7s"
                         onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform=''"
                         loading="lazy">
                    <div class="absolute top-3 left-3 flex gap-1.5">
                        <?php if ($r['has_freeship']): ?><span class="text-white text-xs font-bold px-2 py-0.5 rounded-md" style="background:#22c55e">Freeship</span><?php endif; ?>
                        <?php if ($r['has_deal']): ?><span class="text-white text-xs font-bold px-2 py-0.5 rounded-md" style="background:#f97316">Deal</span><?php endif; ?>
                    </div>
                    <button onclick="toggleFavorite(event,<?= (int)$r['id'] ?>,this)"
                            class="absolute top-3 right-3 w-8 h-8 bg-white/90 rounded-full flex items-center justify-center shadow hover:scale-110 transition"
                            style="border:none;cursor:pointer">
                        <i class="<?= in_array($r['id'], $user_favorites) ? 'fas' : 'far' ?> fa-heart" style="color:<?= in_array($r['id'], $user_favorites) ? '#ee2624' : '#9ca3af' ?>"></i>
                    </button>
                </div>
                <div class="p-4">
                    <div class="flex items-start justify-between gap-2">
                        <h3 class="font-bold text-gray-800 text-base leading-tight"><?= esc_html($r['name']) ?></h3>
                        <span class="flex items-center gap-1 font-bold text-sm flex-shrink-0" style="color:#eab308">
                            <i class="fas fa-star text-xs"></i><?= number_format((float)$r['rating'], 1) ?>
                        </span>
                    </div>
                    <p class="text-xs text-gray-400 mt-1 mb-2"><?= esc_html($r['category_name'] ?? '') ?></p>
                    <div class="flex items-center justify-between text-xs text-gray-500 border-t border-gray-50 pt-3">
                        <span><i class="far fa-clock mr-1" style="color:#ee2624"></i><?= (int)$r['delivery_time'] ?> phút</span>
                        <span><i class="fas fa-location-dot mr-1" style="color:#ee2624"></i><?= (float)$r['distance'] ?> km</span>
                        <span class="<?= (int)$r['delivery_fee'] === 0 ? 'font-semibold' : '' ?>" style="<?= (int)$r['delivery_fee'] === 0 ? 'color:#22c55e' : '' ?>">
                            <?= (int)$r['delivery_fee'] === 0 ? '🚚 Freeship' : cica_format_price((int)$r['delivery_fee']) ?>
                        </span>
                    </div>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- WHY CICAFOOD -->
<section class="bg-white border-y border-gray-100 py-14 mb-12">
    <div class="max-w-screen-xl mx-auto px-4">
        <h2 class="text-2xl font-black text-center text-gray-900 mb-10">Tại Sao Chọn <span style="color:#ee2624">Cicafood</span>?</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            <?php foreach ([
                ['fa-bolt','Giao Nhanh 30\'','Đội ngũ shipper tận tâm, giao hàng siêu tốc'],
                ['fa-shield-halved','An Toàn Thực Phẩm','Tất cả đối tác đều qua kiểm duyệt nghiêm ngặt'],
                ['fa-tag','Deal Tốt Nhất','Hàng trăm voucher và ưu đãi mỗi ngày'],
                ['fa-headset','Hỗ Trợ 24/7','Đội hỗ trợ luôn sẵn sàng giải quyết vấn đề'],
            ] as [$icon, $title, $desc]): ?>
            <div class="text-center group">
                <div class="w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-4" style="background:#fef2f2">
                    <i class="fas <?= $icon ?> text-2xl" style="color:#ee2624"></i>
                </div>
                <h3 class="font-bold text-gray-800 mb-1"><?= $title ?></h3>
                <p class="text-xs text-gray-500"><?= $desc ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php if (!is_user_logged_in()): ?>
<!-- CTA REGISTER -->
<section class="max-w-screen-xl mx-auto px-4 mb-12">
    <div class="rounded-3xl p-10 md:p-14 flex flex-col md:flex-row items-center justify-between gap-8 relative overflow-hidden" style="background:linear-gradient(to right,#111827,#1f2937)">
        <div>
            <h2 class="text-3xl font-black text-white mb-3">Tham gia Cicafood<br><span style="color:#fbbf24">Nhận ngay 20% giảm!</span></h2>
            <p class="text-gray-400">Đăng ký tài khoản miễn phí và nhận voucher chào mừng</p>
        </div>
        <div class="flex gap-4 flex-shrink-0">
            <a href="<?= wp_login_url() ?>" class="border border-white/30 text-white px-6 py-3.5 rounded-xl font-bold hover:bg-white/10 transition" style="text-decoration:none">Đăng nhập</a>
            <a href="<?= wp_registration_url() ?>" class="text-white px-8 py-3.5 rounded-xl font-bold transition" style="background:#ee2624;text-decoration:none">Đăng ký ngay</a>
        </div>
    </div>
</section>
<?php endif; ?>

<script>
function heroSearch() {
    const q = document.getElementById('hero-search').value.trim();
    window.location.href = '<?= $site_url ?>/nha-hang/' + (q ? '?q=' + encodeURIComponent(q) : '');
}
document.getElementById('hero-search').addEventListener('keydown', e => { if (e.key === 'Enter') heroSearch(); });

function claimVoucher(id, btn) {
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    fetch('<?= admin_url('admin-ajax.php') ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=cica_save_voucher&voucher_id=${id}&nonce=<?= wp_create_nonce('cicafood_nonce') ?>`
    }).then(r => r.json()).then(d => {
        if (d.success) { showCicaToast('success', '🎉 ' + d.message); btn.innerHTML = '✓ Đã lưu'; }
        else { showCicaToast('error', d.message); btn.disabled = false; btn.innerHTML = '🎁 Lấy mã'; }
    });
}

function toggleFavorite(e, id, btn) {
    e.preventDefault(); e.stopPropagation();
    fetch('<?= admin_url('admin-ajax.php') ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=cica_toggle_favorite&restaurant_id=${id}&nonce=<?= wp_create_nonce('cicafood_nonce') ?>`
    }).then(r => r.json()).then(d => {
        if (d.success) {
            const icon = btn.querySelector('i');
            if (d.favorited) { icon.className = 'fas fa-heart'; icon.style.color = '#ee2624'; showCicaToast('success', 'Đã thêm vào yêu thích!'); }
            else { icon.className = 'far fa-heart'; icon.style.color = '#9ca3af'; showCicaToast('info', 'Đã bỏ yêu thích'); }
        } else if (d.login_required) { window.location.href = '<?= wp_login_url(get_permalink()) ?>'; }
    });
}

function showCicaToast(type, msg) {
    let container = document.getElementById('cica-toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'cica-toast-container';
        container.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:9999;display:flex;flex-direction:column;gap:8px';
        document.body.appendChild(container);
    }
    const colors = {success:'#16a34a', error:'#dc2626', info:'#2563eb'};
    const toast = document.createElement('div');
    toast.style.cssText = `background:${colors[type]||'#374151'};color:white;padding:12px 20px;border-radius:12px;font-weight:600;font-size:14px;box-shadow:0 8px 30px rgba(0,0,0,0.15);animation:slideIn 0.3s ease`;
    toast.textContent = msg;
    container.appendChild(toast);
    setTimeout(() => toast.remove(), 3500);
}
</script>
<style>
@keyframes slideIn { from{opacity:0;transform:translateX(50px)} to{opacity:1;transform:translateX(0)} }
</style>
