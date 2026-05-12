<?php
/**
 * Seed menu + fix anh cho TAT CA nha hang
 * Truy cap: /wp-content/plugins/cicafood-core/database/seed_all_menus.php
 * Xoa file sau khi chay xong.
 */
$wp_load = dirname(__FILE__, 5) . '/wp-load.php';
if (!file_exists($wp_load)) die('Khong tim thay wp-load.php');
require_once $wp_load;

global $wpdb;
$mc_t = $wpdb->prefix . 'cica_menu_categories';
$mi_t = $wpdb->prefix . 'cica_menu_items';
$rt_t = $wpdb->prefix . 'cica_restaurants';

function do_seed(string $slug, array $cats, string $cover, string $thumb): void {
    global $wpdb, $mc_t, $mi_t, $rt_t;
    $rid = (int)$wpdb->get_var($wpdb->prepare("SELECT id FROM $rt_t WHERE slug=%s LIMIT 1", $slug));
    if (!$rid) { echo "<p style='color:red;font-family:sans-serif'>Khong tim thay: $slug</p>"; return; }

    // Cap nhat anh
    $wpdb->update($rt_t, ['cover_image' => $cover, 'image' => $thumb], ['id' => $rid]);

    // Xoa menu cu
    $wpdb->delete($mi_t, ['restaurant_id' => $rid]);
    $wpdb->delete($mc_t, ['restaurant_id' => $rid]);

    $sort = 1; $total = 0;
    foreach ($cats as $cat_name => $items) {
        $wpdb->insert($mc_t, ['restaurant_id' => $rid, 'name' => $cat_name, 'sort_order' => $sort++]);
        $cid = $wpdb->insert_id;
        $isort = 1;
        foreach ($items as $it) {
            $wpdb->insert($mi_t, [
                'restaurant_id'   => $rid,
                'menu_category_id'=> $cid,
                'is_available'    => 1,
                'sort_order'      => $isort++,
                'name'            => $it[0],
                'description'     => $it[1],
                'price'           => $it[2],
                'original_price'  => $it[3] ?? null,
                'image'           => $it[4],
                'is_best_seller'  => $it[5] ?? 0,
            ]);
            $total++;
        }
    }
    echo "<p style='font-family:sans-serif;color:#16a34a'>&#10003; <b>$slug</b> &mdash; $total mon</p>";
}

// ============================================================
// ANH DO AN (Unsplash co dinh, khong bi block)
// ============================================================
$I = [
    'pho'     => 'https://images.unsplash.com/photo-1582878826629-29b7ad1cdc43?w=400&q=80',
    'pho2'    => 'https://images.unsplash.com/photo-1565557623262-b51c2513a641?w=400&q=80',
    'com'     => 'https://images.unsplash.com/photo-1512058564366-18510be2db19?w=400&q=80',
    'com2'    => 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=400&q=80',
    'burger'  => 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=400&q=80',
    'burger2' => 'https://images.unsplash.com/photo-1550317138-10000687a72b?w=400&q=80',
    'sushi'   => 'https://images.unsplash.com/photo-1579871494447-9811cf80d66c?w=400&q=80',
    'sushi2'  => 'https://images.unsplash.com/photo-1617196034183-421b4040ed20?w=400&q=80',
    'tra'     => 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=400&q=80',
    'tra2'    => 'https://images.unsplash.com/photo-1556679343-c7306c1976bc?w=400&q=80',
    'pizza'   => 'https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?w=400&q=80',
    'pizza2'  => 'https://images.unsplash.com/photo-1571997478779-2adcbbe9ab2f?w=400&q=80',
    'bun'     => 'https://images.unsplash.com/photo-1559847844-5315695dadae?w=400&q=80',
    'nem'     => 'https://images.unsplash.com/photo-1512058564366-18510be2db19?w=400&q=80',
    'lau'     => 'https://images.unsplash.com/photo-1547592166-23ac45744acd?w=400&q=80',
    'lau2'    => 'https://images.unsplash.com/photo-1569050467447-ce54b3bbc37d?w=400&q=80',
    'drink'   => 'https://images.unsplash.com/photo-1621263764928-df1444c5e859?w=400&q=80',
    'drink2'  => 'https://images.unsplash.com/photo-1556679343-c7306c1976bc?w=400&q=80',
    'coffee'  => 'https://images.unsplash.com/photo-1509042239860-f550ce710b93?w=400&q=80',
    'cake'    => 'https://images.unsplash.com/photo-1578985545062-69928b1d9587?w=400&q=80',
];

// Cover images (1200x400) - anh do an thuc su
$C = [
    'pho'    => 'https://images.unsplash.com/photo-1582878826629-29b7ad1cdc43?w=1200&h=400&fit=crop&q=80',
    'com'    => 'https://images.unsplash.com/photo-1512058564366-18510be2db19?w=1200&h=400&fit=crop&q=80',
    'burger' => 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=1200&h=400&fit=crop&q=80',
    'sushi'  => 'https://images.unsplash.com/photo-1579871494447-9811cf80d66c?w=1200&h=400&fit=crop&q=80',
    'tra'    => 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=1200&h=400&fit=crop&q=80',
    'pizza'  => 'https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?w=1200&h=400&fit=crop&q=80',
    'bun'    => 'https://images.unsplash.com/photo-1559847844-5315695dadae?w=1200&h=400&fit=crop&q=80',
    'lau'    => 'https://images.unsplash.com/photo-1547592166-23ac45744acd?w=1200&h=400&fit=crop&q=80',
];
$T = [
    'pho'    => 'https://images.unsplash.com/photo-1582878826629-29b7ad1cdc43?w=200&h=200&fit=crop&q=80',
    'com'    => 'https://images.unsplash.com/photo-1512058564366-18510be2db19?w=200&h=200&fit=crop&q=80',
    'burger' => 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=200&h=200&fit=crop&q=80',
    'sushi'  => 'https://images.unsplash.com/photo-1579871494447-9811cf80d66c?w=200&h=200&fit=crop&q=80',
    'tra'    => 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=200&h=200&fit=crop&q=80',
    'pizza'  => 'https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?w=200&h=200&fit=crop&q=80',
    'bun'    => 'https://images.unsplash.com/photo-1559847844-5315695dadae?w=200&h=200&fit=crop&q=80',
    'lau'    => 'https://images.unsplash.com/photo-1547592166-23ac45744acd?w=200&h=200&fit=crop&q=80',
];

// ============================================================
// PHO GANH
// ============================================================
do_seed('pho-ganh-ha-noi', [
    'Combo Tiet Kiem' => [
        ['Combo Pho Bo Dac Biet + Tra Da', 'Pho bo tai chin dac biet + 1 tra da', 75000, 90000, $I['pho'], 1],
        ['Combo 2 To Pho', '2 to pho bo tai + 2 tra da', 130000, 150000, $I['pho2'], 0],
    ],
    'Pho Bo' => [
        ['Pho Bo Tai Chin Dac Biet', 'To lon: tai, chin, gau, nam, gan, nuoc dung ham 12 tieng', 75000, null, $I['pho'], 1],
        ['Pho Bo Tai', 'Thit tai mong hong, mem muot, nuoc trong', 65000, null, $I['pho2'], 0],
        ['Pho Bo Chin', 'Thit chin mem, nuoc dung ngot thanh', 65000, null, $I['pho'], 0],
        ['Pho Bo Vien', 'Bo vien dai gion san sat', 60000, null, $I['pho2'], 0],
        ['Pho Ga', 'Ga ta nuoc trong, thit xe mem', 60000, null, $I['pho'], 0],
    ],
    'Thuc Uong' => [
        ['Tra Da', 'Tra xanh pha lanh', 10000, null, $I['drink2'], 0],
        ['Nuoc Chanh Tuoi', 'Chanh vat tuoi pha duong', 20000, null, $I['drink'], 0],
    ],
], $C['pho'], $T['pho']);

// ============================================================
// COM TAM
// ============================================================
do_seed('com-tam-ba-ghien', [
    'Combo Day Du' => [
        ['Combo Com Tam Suon Bi Cha', 'Com + Suon nuong + Bi + Cha Trung + Sup', 68000, 80000, $I['com'], 1],
        ['Combo 2 Phan Com Tam', '2 phan com tam suon bi cha + 2 tra da', 120000, 140000, $I['com2'], 0],
    ],
    'Com Tam' => [
        ['Com Tam Suon Bi Cha', 'Suon cot let nuong lua than, bi da heo soi gion, cha trung', 65000, null, $I['com'], 1],
        ['Com Tam Chi Suon', 'Suon nuong to ban, dam vi', 55000, null, $I['com2'], 0],
        ['Com Tam Bi Cha', 'Bi da va cha trung hap mem', 50000, null, $I['com'], 0],
        ['Com Tam Suon Nuong', 'Suon nuong than hoa thom lung', 58000, null, $I['com2'], 0],
    ],
    'Thuc Uong' => [
        ['Tra Da', 'Tra xanh pha lanh', 10000, null, $I['drink2'], 0],
        ['Nuoc Mia', 'Nuoc mia ep tuoi mat lanh', 20000, null, $I['drink'], 0],
    ],
], $C['com'], $T['com']);

// ============================================================
// BURGER
// ============================================================
do_seed('burger-bo-wagyu', [
    'Combo Burger' => [
        ['Combo Wagyu Double + Khoai Tay + Nuoc', 'Burger bo Wagyu 2 tang + khoai tay chien + Coca', 185000, 220000, $I['burger'], 1],
        ['Combo Burger Co Ban', 'Burger bo + khoai tay chien nho + nuoc ngot', 130000, 155000, $I['burger2'], 0],
    ],
    'Burger' => [
        ['Wagyu Double Smash Burger', '2 lop thit bo Wagyu xay, pho mat chay, sot dac biet', 155000, null, $I['burger'], 1],
        ['Wagyu Classic Burger', 'Thit bo Wagyu 150g, rau tuoi, sot mayo', 125000, null, $I['burger2'], 0],
        ['Crispy Chicken Burger', 'Ga ran gion, sot cay Sriracha', 95000, null, $I['burger'], 0],
        ['Mushroom Swiss Burger', 'Bo + nam xao + pho mat Thuy Si', 115000, null, $I['burger2'], 0],
    ],
    'Mon Phu' => [
        ['Khoai Tay Chien (L)', 'Khoai tay chien gion co lon', 45000, null, $I['com2'], 0],
        ['Onion Rings', 'Hanh tay tam bot chien gion', 40000, null, $I['com'], 0],
    ],
    'Thuc Uong' => [
        ['Coca Cola (lon)', 'Coca Cola 330ml uop lanh', 20000, null, $I['drink2'], 0],
        ['Milkshake Vani', 'Milkshake vani beo ngay', 55000, null, $I['drink'], 0],
    ],
], $C['burger'], $T['burger']);

// ============================================================
// SUSHI
// ============================================================
do_seed('sushi-hokkaido', [
    'Set Sushi' => [
        ['Set Sushi 12 Mieng Dac Biet', '12 mieng sushi cao cap: ca hoi, ca ngu, tom, bach tuoc', 280000, 320000, $I['sushi'], 1],
        ['Set Sushi 8 Mieng Co Ban', '8 mieng sushi tuoi ngon', 180000, 210000, $I['sushi2'], 0],
    ],
    'Sushi' => [
        ['Sushi Ca Hoi (6 mieng)', 'Ca hoi Na Uy tuoi nhap khau, beo ngay', 120000, null, $I['sushi'], 1],
        ['Sushi Ca Ngu (6 mieng)', 'Ca ngu dai duong do tuoi', 110000, null, $I['sushi2'], 0],
        ['Sushi Tom (6 mieng)', 'Tom hum dat luoc chin', 100000, null, $I['sushi'], 0],
        ['Sushi Trung Ca (6 mieng)', 'Trung ca hoi muoi vang ong', 130000, null, $I['sushi2'], 0],
    ],
    'Maki & Roll' => [
        ['California Roll (8 mieng)', 'Cua, bo, dua leo, trung ca', 95000, null, $I['sushi'], 1],
        ['Dragon Roll (8 mieng)', 'Tom tempura, bo, sot unagi', 125000, null, $I['sushi2'], 0],
        ['Spicy Tuna Roll (8 mieng)', 'Ca ngu cay, dua leo, sot sriracha', 105000, null, $I['sushi'], 0],
    ],
    'Thuc Uong' => [
        ['Tra Xanh Nhat (nong)', 'Matcha truyen thong Nhat Ban', 35000, null, $I['drink2'], 0],
        ['Sake (nho)', 'Ruou sake Nhat 180ml', 85000, null, $I['drink'], 0],
    ],
], $C['sushi'], $T['sushi']);

// ============================================================
// PHUC LONG
// ============================================================
do_seed('phuc-long-tea-coffee', [
    'Tra Sua' => [
        ['Tra Sua Truyen Thong', 'Tra den pha sua dac, tran chau den deo', 45000, null, $I['tra'], 1],
        ['Tra Sua Matcha', 'Matcha Nhat pha sua tuoi, tran chau trang', 55000, null, $I['tra2'], 1],
        ['Tra Sua Khoai Mon', 'Khoai mon tim pha sua beo ngay', 50000, null, $I['tra'], 0],
        ['Tra Sua Dau Tay', 'Dau tay tuoi pha sua, thach dau', 52000, null, $I['tra2'], 0],
    ],
    'Tra Trai Cay' => [
        ['Tra Dao Cam Sa', 'Tra dao thom, cam tuoi, sa tuoi mat', 48000, null, $I['tra'], 1],
        ['Tra Vai Nhan', 'Vai thieu + nhan long, thanh mat', 45000, null, $I['tra2'], 0],
        ['Tra Chanh Leo', 'Chanh leo chua ngot, thom nong', 45000, null, $I['tra'], 0],
    ],
    'Ca Phe' => [
        ['Ca Phe Sua Da', 'Ca phe Phuc Long dac trung, sua dac', 40000, null, $I['coffee'], 1],
        ['Bac Xiu', 'Ca phe nhat, nhieu sua, da bao', 38000, null, $I['coffee'], 0],
        ['Americano', 'Espresso pha loang, uong nong hoac da', 42000, null, $I['coffee'], 0],
    ],
], $C['tra'], $T['tra']);

// ============================================================
// PIZZA
// ============================================================
do_seed('pizza-napoli', [
    'Combo Pizza' => [
        ['Combo Pizza L + Salad + Nuoc', '1 pizza co L + salad Caesar + 2 nuoc ngot', 280000, 330000, $I['pizza'], 1],
        ['Combo 2 Pizza M', '2 pizza co M tuy chon', 240000, 280000, $I['pizza2'], 0],
    ],
    'Pizza' => [
        ['Margherita (M)', 'Sot ca chua San Marzano, mozzarella tuoi, hung que', 120000, null, $I['pizza'], 1],
        ['Pepperoni (M)', 'Pepperoni My, mozzarella keo soi', 145000, null, $I['pizza2'], 1],
        ['BBQ Chicken (M)', 'Ga nuong BBQ, hanh tay, ot chuong', 140000, null, $I['pizza'], 0],
        ['4 Loai Pho Mat (M)', 'Mozzarella, Cheddar, Parmesan, Gorgonzola', 155000, null, $I['pizza2'], 0],
        ['Hai San (M)', 'Tom, muc, ngheu, sot trang', 160000, null, $I['pizza'], 0],
    ],
    'Mon Phu' => [
        ['Garlic Bread (4 lat)', 'Banh mi nuong bo toi thom lung', 45000, null, $I['com2'], 0],
        ['Salad Caesar', 'Rau romaine, crouton, sot Caesar', 65000, null, $I['com'], 0],
    ],
    'Thuc Uong' => [
        ['Pepsi (lon)', 'Pepsi 330ml uop lanh', 20000, null, $I['drink2'], 0],
        ['Nuoc Cam Ep', 'Cam tuoi ep nguyen chat', 40000, null, $I['drink'], 0],
    ],
], $C['pizza'], $T['pizza']);

// ============================================================
// BUN CHA
// ============================================================
do_seed('bun-cha-ha-noi', [
    'Combo Tiet Kiem' => [
        ['Combo Bun Cha Dac Biet + Tra Da', 'Bun cha dac biet (cha mieng + cha vien) + 1 tra da', 75000, 90000, $I['bun'], 1],
        ['Combo Bun Cha + Nem Ran', 'Bun cha thuong + 3 nem ran gion', 85000, 100000, $I['nem'], 1],
    ],
    'Bun Cha' => [
        ['Bun Cha Dac Biet', 'Cha mieng + cha vien nuong than hoa, nuoc cham chua ngot', 65000, null, $I['bun'], 1],
        ['Bun Cha Thuong', 'Cha mieng nuong than hoa thom lung, bun tuoi', 55000, null, $I['bun'], 0],
        ['Bun Cha + Nem Ran (3 cai)', 'Bun cha thuong kem 3 nem ran gion rum', 75000, null, $I['nem'], 0],
        ['Nem Ran (5 cai)', 'Nem ran nhan thit + mien + moc nhi, vo gion vang', 35000, null, $I['nem'], 0],
    ],
    'Bun & Pho' => [
        ['Bun Bo Hue', 'Bun bo Hue cay nong, cha cua, gio heo', 60000, null, $I['pho'], 0],
        ['Bun Rieu Cua', 'Bun rieu cua dong, ca chua, dau phu chien', 58000, null, $I['pho2'], 0],
        ['Pho Ga', 'Pho ga ta nuoc trong, thit ga xe mem', 55000, null, $I['pho'], 0],
    ],
    'Thuc Uong' => [
        ['Tra Da', 'Tra xanh pha lanh giai khat', 10000, null, $I['drink2'], 0],
        ['Nuoc Chanh Tuoi', 'Chanh vat tuoi pha duong, them da', 20000, null, $I['drink'], 0],
        ['Bia Ha Noi (lon)', 'Bia Ha Noi lon 330ml uop lanh', 25000, null, $I['drink2'], 0],
    ],
], $C['bun'], $T['bun']);

// ============================================================
// LAU THAI
// ============================================================
do_seed('lau-thai-kungfu', [
    'Lau' => [
        ['Lau Thai Hai San (2 nguoi)', 'Nuoc lau Thai chua cay, tom + muc + ngheu + ca', 280000, 320000, $I['lau'], 1],
        ['Lau Thai Bo (2 nguoi)', 'Nuoc lau Thai + thit bo My thai lat', 260000, 300000, $I['lau2'], 1],
        ['Lau Thai Ga (2 nguoi)', 'Nuoc lau Thai + ga ta chat mieng', 240000, null, $I['lau'], 0],
        ['Lau Thai Chay (2 nguoi)', 'Nuoc lau Thai chay + nam + dau hu + rau', 200000, null, $I['lau2'], 0],
    ],
    'Nhung Them' => [
        ['Tom Su (200g)', 'Tom su tuoi song 200g', 120000, null, $I['lau'], 0],
        ['Bo My Thai Lat (200g)', 'Thit bo My thai mong 200g', 110000, null, $I['lau2'], 0],
        ['Nam Tong Hop', 'Kim cham + nam rom + nam dui ga', 55000, null, $I['com2'], 0],
        ['Rau Tong Hop', 'Rau muong + cai thao + bap chuoi', 40000, null, $I['com'], 0],
    ],
    'Thuc Uong' => [
        ['Bia Tiger (lon)', 'Bia Tiger 330ml uop lanh', 30000, null, $I['drink2'], 0],
        ['Nuoc Dua Tuoi', 'Dua xiem tuoi nguyen trai', 35000, null, $I['drink'], 0],
        ['Tra Da', 'Tra xanh pha lanh', 10000, null, $I['drink2'], 0],
    ],
], $C['lau'], $T['lau']);

echo "<h2 style='font-family:sans-serif;color:#16a34a;margin-top:2rem;padding:1rem;background:#f0fdf4;border-radius:8px'>&#10003; Hoan tat! Da seed menu + cap nhat anh cho tat ca nha hang.</h2>";
echo "<p style='font-family:sans-serif;color:#dc2626'><b>Hay xoa file nay ngay!</b></p>";
echo "<p style='font-family:sans-serif'><a href='/cicafood/wordpress/'>Ve trang chu</a></p>";
