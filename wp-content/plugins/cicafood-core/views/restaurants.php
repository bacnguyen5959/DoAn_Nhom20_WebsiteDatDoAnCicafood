<?php
defined('ABSPATH') || exit;

$category  = sanitize_text_field($_GET['category'] ?? '');
$sort      = sanitize_text_field($_GET['sort'] ?? 'default');
$search    = sanitize_text_field($_GET['q'] ?? '');
$freeship  = (int)($_GET['freeship'] ?? 0);
$deal      = (int)($_GET['deal'] ?? 0);

$restaurants = Cicafood_Restaurant::get_list([
    'category_slug' => $category,
    'sort'          => $sort,
    'search'        => $search,
    'has_freeship'  => $freeship ?: null,
    'has_deal'      => $deal ?: null,
    'limit'         => 24,
]);

$categories = Cicafood_Restaurant::get_categories();
$site_url   = get_site_url();

$user_favorites = [];
if (is_user_logged_in()) {
    global $wpdb;
    $fav_table = $wpdb->prefix . 'cica_favorites';
    $user_favorites = $wpdb->get_col($wpdb->prepare(
        "SELECT restaurant_id FROM $fav_table WHERE wp_user_id = %d", get_current_user_id()
    ));
}
?>

<div class="max-w-screen-xl mx-auto px-4 py-6">

    <!-- Page Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-black text-gray-900">
            <?= $search ? "Kết quả: \"" . esc_html($search) . "\"" : '🍽️ Tất Cả Nhà Hàng' ?>
        </h1>
        <p class="text-sm text-gray-500 mt-1"><?= count($restaurants) ?> kết quả tìm được</p>
    </div>

    <!-- Category Pills -->
    <div class="flex gap-2 overflow-x-auto pb-2 mb-4" style="scrollbar-width:none">
        <a href="<?= $site_url ?>/nha-hang/" class="flex-shrink-0 px-4 py-2 rounded-full text-sm font-semibold transition <?= !$category ? 'text-white' : 'bg-gray-100 text-gray-600 hover:bg-red-50' ?>" style="<?= !$category ? 'background:#ee2624;text-decoration:none' : 'text-decoration:none' ?>">Tất cả</a>
        <?php foreach ($categories as $cat): ?>
        <a href="<?= $site_url ?>/nha-hang/?category=<?= esc_attr($cat['slug']) ?>"
           class="flex-shrink-0 flex items-center gap-1.5 px-4 py-2 rounded-full text-sm font-semibold transition"
           style="<?= $category === $cat['slug'] ? 'background:#ee2624;color:white;text-decoration:none' : 'background:#f3f4f6;color:#374151;text-decoration:none' ?>">
            <i class="fas <?= esc_attr($cat['icon']) ?>"></i>
            <?= esc_html($cat['name']) ?>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-6">
        <div class="flex flex-wrap items-center gap-3">
            <span class="text-sm font-semibold text-gray-600">Sắp xếp:</span>
            <?php foreach (['default' => 'Phổ biến', 'rating_desc' => 'Đánh giá', 'delivery_asc' => 'Phí ship', 'time_asc' => 'Nhanh nhất'] as $val => $label): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['sort' => $val])) ?>"
               class="px-3 py-1.5 rounded-full text-xs font-semibold transition"
               style="<?= $sort === $val ? 'background:#ee2624;color:white;text-decoration:none' : 'background:#f3f4f6;color:#374151;text-decoration:none' ?>">
                <?= $label ?>
            </a>
            <?php endforeach; ?>
            <div class="w-px h-6 bg-gray-200 hidden md:block"></div>
            <a href="?<?= http_build_query(array_merge($_GET, ['freeship' => $freeship ? 0 : 1])) ?>"
               class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold transition"
               style="<?= $freeship ? 'background:#22c55e;color:white;text-decoration:none' : 'background:#f3f4f6;color:#374151;text-decoration:none' ?>">
                <i class="fas fa-motorcycle"></i> Freeship
            </a>
            <a href="?<?= http_build_query(array_merge($_GET, ['deal' => $deal ? 0 : 1])) ?>"
               class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold transition"
               style="<?= $deal ? 'background:#f97316;color:white;text-decoration:none' : 'background:#f3f4f6;color:#374151;text-decoration:none' ?>">
                <i class="fas fa-tag"></i> Deal hời
            </a>
        </div>
    </div>

    <!-- Restaurant Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
        <?php foreach ($restaurants as $r): ?>
        <div class="bg-white rounded-2xl overflow-hidden shadow-sm border border-gray-100 group" style="transition:transform 0.2s,box-shadow 0.2s" onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='0 20px 40px rgba(0,0,0,0.1)'" onmouseout="this.style.transform='';this.style.boxShadow=''">
            <a href="<?= $site_url ?>/nha-hang/<?= esc_attr($r['slug']) ?>/" style="text-decoration:none;color:inherit;display:block">
                <div class="relative overflow-hidden" style="height:192px">
                    <img src="<?= esc_url($r['image'] ?: 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?auto=format&fit=crop&w=800&q=80') ?>"
                         alt="<?= esc_attr($r['name']) ?>"
                         style="width:100%;height:100%;object-fit:cover;transition:transform 0.7s"
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
                    <h3 class="font-bold text-base text-gray-800 mb-1 truncate"><?= esc_html($r['name']) ?></h3>
                    <p class="text-xs text-gray-400 mb-3"><?= esc_html($r['category_name'] ?? 'Ẩm thực') ?></p>
                    <div class="flex items-center justify-between text-xs text-gray-500 pt-3 border-t border-gray-50">
                        <div class="flex items-center gap-2">
                            <span class="flex items-center gap-1 font-bold" style="color:#eab308">
                                <i class="fas fa-star"></i><?= number_format((float)$r['rating'], 1) ?>
                            </span>
                            <span class="text-gray-300">|</span>
                            <span><i class="far fa-clock mr-1"></i><?= (int)$r['delivery_time'] ?>'</span>
                        </div>
                        <span class="<?= (int)$r['delivery_fee'] === 0 ? 'font-bold' : '' ?>" style="<?= (int)$r['delivery_fee'] === 0 ? 'color:#22c55e' : '' ?>">
                            <?= (int)$r['delivery_fee'] === 0 ? '🚚 Free' : cica_format_price((int)$r['delivery_fee']) ?>
                        </span>
                    </div>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if (empty($restaurants)): ?>
    <div class="text-center py-20">
        <i class="fas fa-store-slash text-6xl text-gray-200 mb-6"></i>
        <h2 class="text-2xl font-black text-gray-400 mb-2">Không tìm thấy kết quả</h2>
        <a href="<?= $site_url ?>/nha-hang/" class="inline-block text-white px-8 py-3.5 rounded-2xl font-bold hover:bg-red-700 transition mt-4" style="background:#ee2624;text-decoration:none">
            Xem tất cả nhà hàng
        </a>
    </div>
    <?php endif; ?>
</div>

<script>
function toggleFavorite(e, id, btn) {
    e.preventDefault(); e.stopPropagation();
    fetch('<?= admin_url('admin-ajax.php') ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=cica_toggle_favorite&restaurant_id=${id}&nonce=<?= wp_create_nonce('cicafood_nonce') ?>`
    }).then(r => r.json()).then(d => {
        if (d.login_required) { window.location.href = '<?= wp_login_url(get_permalink()) ?>'; return; }
        if (d.success) {
            const icon = btn.querySelector('i');
            if (d.favorited) { icon.className = 'fas fa-heart'; icon.style.color = '#ee2624'; }
            else { icon.className = 'far fa-heart'; icon.style.color = '#9ca3af'; }
        }
    });
}
</script>
