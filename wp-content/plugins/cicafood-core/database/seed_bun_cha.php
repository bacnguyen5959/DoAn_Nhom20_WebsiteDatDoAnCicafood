<?php
/**
 * Seed menu cho nhà hàng Bún Chả Hà Nội Cô Lan
 * Chạy 1 lần: truy cập /wp-content/plugins/cicafood-core/database/seed_bun_cha.php
 * Sau khi chạy xong hãy xoá file này.
 */

// Bootstrap WordPress
$wp_load = dirname(__FILE__, 5) . '/wp-load.php';
if (!file_exists($wp_load)) {
    die('Không tìm thấy wp-load.php. Kiểm tra lại đường dẫn.');
}
require_once $wp_load;

global $wpdb;
$mc_table = $wpdb->prefix . 'cica_menu_categories';
$mi_table = $wpdb->prefix . 'cica_menu_items';
$rest_table = $wpdb->prefix . 'cica_restaurants';

// Lấy restaurant_id
$rest_id = (int)$wpdb->get_var(
    $wpdb->prepare("SELECT id FROM $rest_table WHERE slug = %s LIMIT 1", 'bun-cha-ha-noi')
);

if (!$rest_id) {
    die('Không tìm thấy nhà hàng bun-cha-ha-noi trong database.');
}

// Xoá menu cũ nếu có
$wpdb->delete($mi_table, ['restaurant_id' => $rest_id]);
$wpdb->delete($mc_table, ['restaurant_id' => $rest_id]);

// ---- Menu Categories ----
$wpdb->insert($mc_table, ['restaurant_id' => $rest_id, 'name' => 'Combo Tiết Kiệm', 'sort_order' => 1]);
$cat_combo = $wpdb->insert_id;

$wpdb->insert($mc_table, ['restaurant_id' => $rest_id, 'name' => 'Bún Chả', 'sort_order' => 2]);
$cat_bun_cha = $wpdb->insert_id;

$wpdb->insert($mc_table, ['restaurant_id' => $rest_id, 'name' => 'Bún & Phở', 'sort_order' => 3]);
$cat_bun = $wpdb->insert_id;

$wpdb->insert($mc_table, ['restaurant_id' => $rest_id, 'name' => 'Thức Uống', 'sort_order' => 4]);
$cat_drink = $wpdb->insert_id;

// ---- Menu Items ----
$items = [
    // Combo
    [
        'menu_category_id' => $cat_combo,
        'name'             => 'Combo Bún Chả Đặc Biệt + Nước',
        'description'      => 'Bún chả đặc biệt (chả miếng + chả viên) + 1 trà đá',
        'price'            => 75000,
        'original_price'   => 90000,
        'image'            => 'https://images.unsplash.com/photo-1559847844-5315695dadae?auto=format&fit=crop&w=400&q=80',
        'is_best_seller'   => 1,
        'sort_order'       => 1,
    ],
    [
        'menu_category_id' => $cat_combo,
        'name'             => 'Combo Bún Chả + Nem Rán',
        'description'      => 'Bún chả thường + 3 nem rán giòn + nước chấm',
        'price'            => 85000,
        'original_price'   => 100000,
        'image'            => 'https://images.unsplash.com/photo-1569050467447-ce54b3bbc37d?auto=format&fit=crop&w=400&q=80',
        'is_best_seller'   => 1,
        'sort_order'       => 2,
    ],

    // Bún Chả
    [
        'menu_category_id' => $cat_bun_cha,
        'name'             => 'Bún Chả Đặc Biệt',
        'description'      => 'Chả miếng + chả viên nướng than hoa, nước chấm chua ngọt đậm đà',
        'price'            => 65000,
        'original_price'   => null,
        'image'            => 'https://images.unsplash.com/photo-1559847844-5315695dadae?auto=format&fit=crop&w=400&q=80',
        'is_best_seller'   => 1,
        'sort_order'       => 1,
    ],
    [
        'menu_category_id' => $cat_bun_cha,
        'name'             => 'Bún Chả Thường',
        'description'      => 'Chả miếng nướng than hoa thơm lừng, bún tươi',
        'price'            => 55000,
        'original_price'   => null,
        'image'            => 'https://images.unsplash.com/photo-1565557623262-b51c2513a641?auto=format&fit=crop&w=400&q=80',
        'is_best_seller'   => 0,
        'sort_order'       => 2,
    ],
    [
        'menu_category_id' => $cat_bun_cha,
        'name'             => 'Bún Chả + Nem Rán (3 cái)',
        'description'      => 'Bún chả thường kèm 3 nem rán giòn rụm',
        'price'            => 75000,
        'original_price'   => null,
        'image'            => 'https://images.unsplash.com/photo-1547592166-23ac45744acd?auto=format&fit=crop&w=400&q=80',
        'is_best_seller'   => 0,
        'sort_order'       => 3,
    ],
    [
        'menu_category_id' => $cat_bun_cha,
        'name'             => 'Nem Rán (5 cái)',
        'description'      => 'Nem rán nhân thịt + miến + mộc nhĩ, vỏ giòn vàng',
        'price'            => 35000,
        'original_price'   => null,
        'image'            => 'https://images.unsplash.com/photo-1512058564366-18510be2db19?auto=format&fit=crop&w=400&q=80',
        'is_best_seller'   => 0,
        'sort_order'       => 4,
    ],

    // Bún & Phở
    [
        'menu_category_id' => $cat_bun,
        'name'             => 'Bún Bò Huế',
        'description'      => 'Bún bò Huế cay nồng, chả cua, giò heo',
        'price'            => 60000,
        'original_price'   => null,
        'image'            => 'https://images.unsplash.com/photo-1582878826629-29b7ad1cdc43?auto=format&fit=crop&w=400&q=80',
        'is_best_seller'   => 0,
        'sort_order'       => 1,
    ],
    [
        'menu_category_id' => $cat_bun,
        'name'             => 'Bún Riêu Cua',
        'description'      => 'Bún riêu cua đồng, cà chua, đậu phụ chiên',
        'price'            => 58000,
        'original_price'   => null,
        'image'            => 'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?auto=format&fit=crop&w=400&q=80',
        'is_best_seller'   => 0,
        'sort_order'       => 2,
    ],
    [
        'menu_category_id' => $cat_bun,
        'name'             => 'Phở Gà',
        'description'      => 'Phở gà ta nước trong, thịt gà xé mềm',
        'price'            => 55000,
        'original_price'   => null,
        'image'            => 'https://images.unsplash.com/photo-1617196034183-421b4040ed20?auto=format&fit=crop&w=400&q=80',
        'is_best_seller'   => 0,
        'sort_order'       => 3,
    ],

    // Thức Uống
    [
        'menu_category_id' => $cat_drink,
        'name'             => 'Trà Đá',
        'description'      => 'Trà xanh pha lạnh giải khát',
        'price'            => 10000,
        'original_price'   => null,
        'image'            => 'https://images.unsplash.com/photo-1556679343-c7306c1976bc?auto=format&fit=crop&w=400&q=80',
        'is_best_seller'   => 0,
        'sort_order'       => 1,
    ],
    [
        'menu_category_id' => $cat_drink,
        'name'             => 'Nước Chanh Tươi',
        'description'      => 'Chanh vắt tươi pha đường, thêm đá',
        'price'            => 20000,
        'original_price'   => null,
        'image'            => 'https://images.unsplash.com/photo-1621263764928-df1444c5e859?auto=format&fit=crop&w=400&q=80',
        'is_best_seller'   => 0,
        'sort_order'       => 2,
    ],
    [
        'menu_category_id' => $cat_drink,
        'name'             => 'Bia Hà Nội (lon)',
        'description'      => 'Bia Hà Nội lon 330ml ướp lạnh',
        'price'            => 25000,
        'original_price'   => null,
        'image'            => 'https://images.unsplash.com/photo-1608270586620-248524c67de9?auto=format&fit=crop&w=400&q=80',
        'is_best_seller'   => 0,
        'sort_order'       => 3,
    ],
];

$count = 0;
foreach ($items as $item) {
    $wpdb->insert($mi_table, array_merge(['restaurant_id' => $rest_id, 'is_available' => 1], $item));
    $count++;
}

echo "<h2 style='font-family:sans-serif;color:green'>✅ Đã thêm $count món cho nhà hàng Bún Chả Hà Nội Cô Lan (ID: $rest_id)</h2>";
echo "<p style='font-family:sans-serif'><a href='/cicafood/wordpress/nha-hang/bun-cha-ha-noi/'>Xem nhà hàng →</a></p>";
echo "<p style='font-family:sans-serif;color:red'><strong>Hãy xoá file này sau khi chạy xong!</strong></p>";
