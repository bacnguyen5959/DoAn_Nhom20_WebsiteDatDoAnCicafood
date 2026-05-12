<?php
defined('ABSPATH') || exit;
if (!session_id()) session_start();

// Lấy slug từ URL
$slug = sanitize_text_field($_GET['slug'] ?? '');

// Nếu không có slug trong GET, lấy từ URL path
if (!$slug) {
    $request_uri = $_SERVER['REQUEST_URI'];
    // URL pattern: /nha-hang/{slug}/
    if (preg_match('#/nha-hang/([^/?]+)#', $request_uri, $matches)) {
        $slug = $matches[1];
    }
}

if (!$slug) {
    echo '<div class="max-w-screen-xl mx-auto px-4 py-20 text-center"><p class="text-gray-400">Không tìm thấy nhà hàng.</p></div>';
    return;
}

$restaurant = Cicafood_Restaurant::get_by_slug($slug);

if (!$restaurant) {
    echo '<div class="max-w-screen-xl mx-auto px-4 py-20 text-center"><i class="fas fa-store-slash text-6xl text-gray-200 mb-4"></i><p class="text-gray-400 text-lg">Nhà hàng không tồn tại.</p></div>';
    return;
}

$menu_grouped = Cicafood_Menu::get_items_grouped((int)$restaurant['id']);
$reviews      = Cicafood_Review::get_by_restaurant((int)$restaurant['id'], 5);
$site_url     = get_site_url();
$current_cart = Cicafood_Cart::get();

// Truyền dữ liệu cho restaurant.js qua wp_localize_script
wp_localize_script('cicafood-restaurant', 'cicaRestaurant', [
    'deliveryFee'  => (int)$restaurant['delivery_fee'],
    'restaurantId' => (int)$restaurant['id'],
    'currentCart'  => $current_cart ?: new stdClass(),
    'loginUrl'     => wp_login_url(get_permalink()),
]);

// Check favorite
$is_favorite = false;
if (is_user_logged_in()) {
    global $wpdb;
    $fav_table = $wpdb->prefix . 'cica_favorites';
    $is_favorite = (bool)$wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $fav_table WHERE wp_user_id = %d AND restaurant_id = %d",
        get_current_user_id(), $restaurant['id']
    ));
}
?>

<!-- Cover Image -->
<div style="width:100%;height:280px;overflow:hidden;position:relative;background:linear-gradient(135deg,#ee2624,#f97316)">
    <img src="<?= esc_url($restaurant['cover_image'] ?: $restaurant['image'] ?: '') ?>"
         alt="<?= esc_attr($restaurant['name']) ?>"
         style="width:100%;height:100%;object-fit:cover"
         onerror="this.style.display='none'">
</div>

<!-- Restaurant Info Bar -->
<div style="background:white;padding:1.5rem 1.5rem 1.25rem;border-bottom:1px solid #e5e7eb;box-shadow:0 2px 8px rgba(0,0,0,0.04)">
    <div class="max-w-screen-xl mx-auto" style="display:flex;align-items:flex-start;gap:1.25rem">
        <!-- Avatar nhà hàng — to hơn, nổi bật hơn -->
        <div style="width:80px;height:80px;border-radius:14px;overflow:hidden;border:3px solid #f3f4f6;box-shadow:0 2px 8px rgba(0,0,0,0.12);flex-shrink:0;background:#f3f4f6">
            <img src="<?= esc_url($restaurant['image'] ?: '') ?>"
                 alt="<?= esc_attr($restaurant['name']) ?>"
                 style="width:100%;height:100%;object-fit:cover"
                 onerror="this.style.display='none';this.parentElement.innerHTML='<div style=\'width:100%;height:100%;background:linear-gradient(135deg,#ee2624,#f97316);display:flex;align-items:center;justify-content:center;font-size:2.5rem\'>🍜</div>'">
        </div>
        <div style="flex:1;padding-top:0.25rem">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:1rem">
                <div>
                    <h1 style="font-size:1.5rem;font-weight:900;color:#111827;margin:0 0 0.25rem"><?= esc_html($restaurant['name']) ?></h1>
                    <p style="color:#6b7280;font-size:0.875rem;margin:0 0 0.5rem"><?= esc_html($restaurant['category_name'] ?? '') ?> · <?= esc_html($restaurant['address']) ?></p>
                    <div style="display:flex;align-items:center;gap:1rem;font-size:0.875rem;color:#6b7280">
                        <span style="color:#eab308;font-weight:700"><i class="fas fa-star"></i> <?= number_format((float)$restaurant['rating'], 1) ?> (<?= (int)$restaurant['total_reviews'] ?> đánh giá)</span>
                        <span><i class="far fa-clock" style="color:#ee2624"></i> <?= (int)$restaurant['delivery_time'] ?> phút</span>
                        <span><?= (int)$restaurant['delivery_fee'] === 0 ? '<span style="color:#22c55e;font-weight:600">🚚 Freeship</span>' : cica_format_price((int)$restaurant['delivery_fee']) ?></span>
                        <span style="background:<?= $restaurant['is_open'] ? '#dcfce7' : '#fee2e2' ?>;color:<?= $restaurant['is_open'] ? '#16a34a' : '#dc2626' ?>;padding:2px 10px;border-radius:999px;font-weight:600">
                            <?= $restaurant['is_open'] ? 'Đang mở' : 'Đóng cửa' ?>
                        </span>
                    </div>
                </div>
                <button onclick="toggleFavorite(event, <?= (int)$restaurant['id'] ?>, this)"
                        style="width:40px;height:40px;border-radius:50%;background:white;border:2px solid #e5e7eb;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:1.1rem;transition:all 0.2s">
                    <i class="<?= $is_favorite ? 'fas' : 'far' ?> fa-heart" style="color:<?= $is_favorite ? '#ee2624' : '#9ca3af' ?>"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Main Layout -->
<div class="max-w-screen-xl mx-auto" style="padding:1.5rem;display:grid;grid-template-columns:1fr 360px;gap:1.5rem;align-items:start">

    <!-- LEFT: Menu -->
    <div>
        <?php if (empty($menu_grouped)): ?>
        <div style="background:white;border-radius:12px;padding:3rem;text-align:center;color:#9ca3af">
            <i class="fas fa-utensils" style="font-size:3rem;margin-bottom:1rem;display:block"></i>
            <p>Chưa có menu. Vui lòng quay lại sau.</p>
        </div>
        <?php else: ?>
        <?php foreach ($menu_grouped as $cat): ?>
        <div style="margin-bottom:2rem">
            <h2 style="font-size:1.1rem;font-weight:700;color:#111827;padding:0.75rem 0;border-bottom:2px solid #ee2624;margin-bottom:1rem"><?= esc_html($cat['name']) ?></h2>
            <?php foreach ($cat['items'] as $item): ?>
            <div style="display:flex;gap:1rem;padding:1rem 0;border-bottom:1px solid #f3f4f6;align-items:center">
                <img src="<?= esc_url($item['image'] ?: '') ?>"
                     alt="<?= esc_attr($item['name']) ?>"
                     style="width:90px;height:90px;border-radius:10px;object-fit:cover;flex-shrink:0;background:#f3f4f6"
                     onerror="this.style.display='none';this.nextElementSibling.style.display='flex'"
                     >
                <?php
                $colors = ['#ee2624','#f97316','#eab308','#22c55e','#0ea5e9','#8b5cf6','#ec4899','#14b8a6'];
                $color  = $colors[abs(crc32($item['name'])) % count($colors)];
                $letter = mb_strtoupper(mb_substr($item['name'], 0, 1));
                ?>
                <div style="display:none;width:90px;height:90px;border-radius:10px;flex-shrink:0;background:<?= $color ?>22;align-items:center;justify-content:center;font-size:2rem;font-weight:900;color:<?= $color ?>;border:2px solid <?= $color ?>33">
                    <?= esc_html($letter) ?>
                </div>
                <div style="flex:1">
                    <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.25rem">
                        <h3 style="font-weight:700;font-size:0.95rem;margin:0"><?= esc_html($item['name']) ?></h3>
                        <?php if ($item['is_best_seller']): ?>
                        <span style="background:#fef3c7;color:#d97706;font-size:0.65rem;font-weight:700;padding:1px 6px;border-radius:999px">Bán chạy</span>
                        <?php endif; ?>
                    </div>
                    <?php if ($item['description']): ?>
                    <p style="font-size:0.8rem;color:#6b7280;margin:0 0 0.5rem;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden"><?= esc_html($item['description']) ?></p>
                    <?php endif; ?>
                    <div style="display:flex;align-items:center;justify-content:space-between">
                        <div>
                            <span style="font-size:1rem;font-weight:700;color:#ee2624"><?= cica_format_price((int)$item['price']) ?></span>
                            <?php if ($item['original_price'] && (int)$item['original_price'] > (int)$item['price']): ?>
                            <span style="font-size:0.8rem;color:#9ca3af;text-decoration:line-through;margin-left:0.4rem"><?= cica_format_price((int)$item['original_price']) ?></span>
                            <?php endif; ?>
                        </div>
                        <button data-item-id="<?= (int)$item['id'] ?>" data-restaurant-id="<?= (int)$restaurant['id'] ?>"
                                class="cica-add-btn"
                                style="width:36px;height:36px;border-radius:50%;background:#ee2624;color:white;border:none;font-size:1.2rem;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all 0.2s"
                                onmouseover="this.style.background='#c41e1c';this.style.transform='scale(1.1)'"
                                onmouseout="this.style.background='#ee2624';this.style.transform=''">
                            <i class="fas fa-plus" style="font-size:0.8rem"></i>
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>

        <!-- Reviews -->
        <?php if (!empty($reviews)): ?>
        <div style="margin-top:2rem">
            <h2 style="font-size:1.1rem;font-weight:700;color:#111827;margin-bottom:1rem">⭐ Đánh giá khách hàng</h2>
            <?php foreach ($reviews as $r): ?>
            <div style="background:white;border-radius:12px;padding:1rem;margin-bottom:0.75rem;border:1px solid #f3f4f6">
                <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:0.5rem">
                    <div style="width:36px;height:36px;background:#ee2624;border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;font-weight:700;font-size:0.875rem">
                        <?= strtoupper(mb_substr($r['user_name'] ?? 'U', 0, 1)) ?>
                    </div>
                    <div>
                        <p style="font-weight:600;font-size:0.875rem;margin:0"><?= esc_html($r['user_name'] ?? 'Người dùng') ?></p>
                        <div style="color:#eab308;font-size:0.75rem">
                            <?php for ($i = 0; $i < (int)$r['rating']; $i++): ?><i class="fas fa-star"></i><?php endfor; ?>
                        </div>
                    </div>
                    <span style="margin-left:auto;font-size:0.75rem;color:#9ca3af"><?= date('d/m/Y', strtotime($r['created_at'])) ?></span>
                </div>
                <?php if ($r['comment']): ?>
                <p style="font-size:0.875rem;color:#374151;margin:0"><?= esc_html($r['comment']) ?></p>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- RIGHT: Cart Sidebar -->
    <div style="background:white;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,0.08);padding:1.25rem;position:sticky;top:80px" id="cart-sidebar">
        <h3 style="font-size:1.1rem;font-weight:700;margin:0 0 1rem;padding-bottom:0.75rem;border-bottom:1px solid #e5e7eb">
            🛒 Giỏ hàng
        </h3>
        <div id="cart-items-list">
            <div style="text-align:center;padding:2rem 0;color:#9ca3af;font-size:0.9rem">
                <i class="fas fa-shopping-bag" style="font-size:2rem;margin-bottom:0.5rem;display:block"></i>
                Chưa có món nào
            </div>
        </div>
        <div id="cart-summary" style="display:none">
            <div style="border-top:1px solid #e5e7eb;padding-top:0.75rem;margin-top:0.75rem">
                <div style="display:flex;justify-content:space-between;font-size:0.875rem;color:#374151;margin-bottom:0.4rem">
                    <span>Tạm tính</span><span id="cart-subtotal">0đ</span>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:0.875rem;color:#374151;margin-bottom:0.4rem">
                    <span>Phí giao hàng</span>
                    <span><?= (int)$restaurant['delivery_fee'] === 0 ? '<span style="color:#22c55e;font-weight:600">Miễn phí</span>' : cica_format_price((int)$restaurant['delivery_fee']) ?></span>
                </div>
                <div style="display:flex;justify-content:space-between;font-weight:800;font-size:1rem;border-top:1px solid #e5e7eb;padding-top:0.6rem;margin-top:0.4rem">
                    <span>Tổng</span><span id="cart-total" style="color:#ee2624">0đ</span>
                </div>
            </div>
            <a href="<?= $site_url ?>/thanh-toan/" id="checkout-btn"
               style="display:block;width:100%;background:#ee2624;color:white;text-align:center;padding:1rem;border-radius:12px;font-weight:700;font-size:1rem;text-decoration:none;margin-top:1rem;transition:background 0.2s;box-sizing:border-box"
               onmouseover="this.style.background='#c41e1c'" onmouseout="this.style.background='#ee2624'">
                Đặt hàng ngay
            </a>
        </div>
    </div>
</div>

<style>
@media (max-width: 768px) {
    .max-w-screen-xl { grid-template-columns: 1fr !important; }
    #cart-sidebar { position: fixed !important; bottom: 0; left: 0; right: 0; border-radius: 12px 12px 0 0 !important; z-index: 50; }
}
</style>
