<?php
defined('ABSPATH') || exit;

/**
 * Cicafood_Database
 * Tạo và quản lý tất cả custom tables của Cicafood
 */
class Cicafood_Database {

    /**
     * Tạo toàn bộ custom tables khi activate plugin
     */
    public static function create_tables(): void {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // --------------------------------------------------------
        // cica_categories — Danh mục nhà hàng
        // --------------------------------------------------------
        dbDelta("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}cica_categories (
            id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name        VARCHAR(100) NOT NULL,
            slug        VARCHAR(120) NOT NULL UNIQUE,
            icon        VARCHAR(100) DEFAULT 'fa-utensils',
            color       VARCHAR(20)  DEFAULT '#ee2624',
            sort_order  INT          DEFAULT 0
        ) $charset;");

        // --------------------------------------------------------
        // cica_restaurants — Nhà hàng
        // --------------------------------------------------------
        dbDelta("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}cica_restaurants (
            id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            wp_user_id      BIGINT(20) UNSIGNED DEFAULT NULL COMMENT 'WP user ID của chủ quán',
            category_id     INT UNSIGNED DEFAULT NULL,
            name            VARCHAR(200) NOT NULL,
            slug            VARCHAR(220) NOT NULL UNIQUE,
            description     TEXT,
            address         TEXT NOT NULL,
            province        VARCHAR(100) DEFAULT 'TP. Hồ Chí Minh',
            phone           VARCHAR(20),
            image           VARCHAR(255) DEFAULT NULL,
            cover_image     VARCHAR(255) DEFAULT NULL,
            rating          DECIMAL(2,1) DEFAULT 4.5,
            total_reviews   INT          DEFAULT 0,
            min_order       INT          DEFAULT 0,
            delivery_fee    INT          DEFAULT 15000,
            delivery_time   INT          DEFAULT 30,
            distance        DECIMAL(5,2) DEFAULT 1.5,
            is_open         TINYINT(1)   DEFAULT 1,
            is_featured     TINYINT(1)   DEFAULT 0,
            has_freeship    TINYINT(1)   DEFAULT 0,
            has_deal        TINYINT(1)   DEFAULT 0,
            open_time       TIME         DEFAULT '07:00:00',
            close_time      TIME         DEFAULT '22:00:00',
            created_at      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
        ) $charset;");

        // --------------------------------------------------------
        // cica_menu_categories — Nhóm menu trong nhà hàng
        // --------------------------------------------------------
        dbDelta("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}cica_menu_categories (
            id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            restaurant_id   INT UNSIGNED NOT NULL,
            name            VARCHAR(100) NOT NULL,
            sort_order      INT DEFAULT 0
        ) $charset;");

        // --------------------------------------------------------
        // cica_menu_items — Món ăn
        // --------------------------------------------------------
        dbDelta("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}cica_menu_items (
            id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            restaurant_id       INT UNSIGNED NOT NULL,
            menu_category_id    INT UNSIGNED DEFAULT NULL,
            name                VARCHAR(200) NOT NULL,
            description         TEXT,
            price               INT NOT NULL,
            original_price      INT DEFAULT NULL,
            image               VARCHAR(255) DEFAULT NULL,
            is_available        TINYINT(1) DEFAULT 1,
            is_best_seller      TINYINT(1) DEFAULT 0,
            sort_order          INT DEFAULT 0
        ) $charset;");

        // --------------------------------------------------------
        // cica_orders — Đơn hàng
        // --------------------------------------------------------
        dbDelta("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}cica_orders (
            id                      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            order_code              VARCHAR(20) NOT NULL UNIQUE,
            wp_user_id              BIGINT(20) UNSIGNED NOT NULL,
            restaurant_id           INT UNSIGNED NOT NULL,
            delivery_address        TEXT NOT NULL,
            recipient_name          VARCHAR(100) NOT NULL,
            recipient_phone         VARCHAR(20) NOT NULL,
            note                    TEXT DEFAULT NULL,
            subtotal                INT NOT NULL DEFAULT 0,
            delivery_fee            INT NOT NULL DEFAULT 0,
            service_fee             INT NOT NULL DEFAULT 0,
            discount_amount         INT NOT NULL DEFAULT 0,
            shipping_discount       INT NOT NULL DEFAULT 0,
            total_amount            INT NOT NULL DEFAULT 0,
            voucher_code            VARCHAR(50) DEFAULT NULL,
            payment_method          VARCHAR(20) DEFAULT 'cod',
            payment_status          VARCHAR(20) DEFAULT 'pending',
            status                  VARCHAR(20) DEFAULT 'pending',
            created_at              TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at              TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) $charset;");

        // --------------------------------------------------------
        // cica_order_items — Chi tiết đơn hàng
        // --------------------------------------------------------
        dbDelta("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}cica_order_items (
            id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            order_id        INT UNSIGNED NOT NULL,
            menu_item_id    INT UNSIGNED NOT NULL,
            item_name       VARCHAR(200) NOT NULL,
            item_price      INT NOT NULL,
            quantity        INT NOT NULL DEFAULT 1,
            subtotal        INT NOT NULL
        ) $charset;");

        // --------------------------------------------------------
        // cica_vouchers — Voucher
        // --------------------------------------------------------
        dbDelta("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}cica_vouchers (
            id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            restaurant_id   INT UNSIGNED DEFAULT NULL,
            code            VARCHAR(50) NOT NULL UNIQUE,
            name            VARCHAR(200) NOT NULL,
            description     TEXT,
            type            VARCHAR(20) NOT NULL DEFAULT 'percent',
            value           INT NOT NULL DEFAULT 0,
            max_discount    INT DEFAULT NULL,
            min_order       INT DEFAULT 0,
            usage_limit     INT DEFAULT 100,
            used_count      INT DEFAULT 0,
            start_date      DATE DEFAULT NULL,
            end_date        DATE DEFAULT NULL,
            is_active       TINYINT(1) DEFAULT 1
        ) $charset;");

        // --------------------------------------------------------
        // cica_user_vouchers — Ví voucher của user
        // --------------------------------------------------------
        dbDelta("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}cica_user_vouchers (
            id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            wp_user_id  BIGINT(20) UNSIGNED NOT NULL,
            voucher_id  INT UNSIGNED NOT NULL,
            is_used     TINYINT(1) DEFAULT 0,
            used_at     TIMESTAMP NULL DEFAULT NULL,
            created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user_voucher (wp_user_id, voucher_id)
        ) $charset;");

        // --------------------------------------------------------
        // cica_reviews — Đánh giá
        // --------------------------------------------------------
        dbDelta("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}cica_reviews (
            id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            wp_user_id      BIGINT(20) UNSIGNED NOT NULL,
            restaurant_id   INT UNSIGNED NOT NULL,
            order_id        INT UNSIGNED DEFAULT NULL,
            rating          TINYINT NOT NULL DEFAULT 5,
            comment         TEXT DEFAULT NULL,
            reply           TEXT DEFAULT NULL,
            is_visible      TINYINT(1) DEFAULT 1,
            created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) $charset;");

        // --------------------------------------------------------
        // cica_favorites — Nhà hàng yêu thích
        // --------------------------------------------------------
        dbDelta("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}cica_favorites (
            id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            wp_user_id      BIGINT(20) UNSIGNED NOT NULL,
            restaurant_id   INT UNSIGNED NOT NULL,
            created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_favorite (wp_user_id, restaurant_id)
        ) $charset;");
    }

    /**
     * Insert dữ liệu mẫu (categories + restaurants + menu + vouchers)
     */
    public static function insert_sample_data(): void {
        global $wpdb;
        $cat_table  = $wpdb->prefix . 'cica_categories';
        $rest_table = $wpdb->prefix . 'cica_restaurants';
        $mc_table   = $wpdb->prefix . 'cica_menu_categories';
        $mi_table   = $wpdb->prefix . 'cica_menu_items';
        $vou_table  = $wpdb->prefix . 'cica_vouchers';

        // Chỉ insert nếu chưa có dữ liệu
        if ($wpdb->get_var("SELECT COUNT(*) FROM $cat_table") > 0) return;

        // Categories
        $categories = [
            ['Cơm',       'com',      'fa-bowl-rice',          '#f97316', 1],
            ['Phở & Bún', 'pho-bun',  'fa-fire-flame-curved',  '#ee2624', 2],
            ['Burger',    'burger',   'fa-burger',             '#eab308', 3],
            ['Pizza',     'pizza',    'fa-pizza-slice',        '#f97316', 4],
            ['Sushi',     'sushi',    'fa-fish',               '#06b6d4', 5],
            ['Trà Sữa',   'tra-sua',  'fa-mug-hot',            '#8b5cf6', 6],
            ['Bánh Mì',   'banh-mi',  'fa-bread-slice',        '#d97706', 7],
            ['Lẩu',       'lau',      'fa-pot-food',           '#ef4444', 8],
        ];
        foreach ($categories as $c) {
            $wpdb->insert($cat_table, [
                'name' => $c[0], 'slug' => $c[1], 'icon' => $c[2],
                'color' => $c[3], 'sort_order' => $c[4],
            ]);
        }

        // Restaurants
        $restaurants = [
            [2, 'Phở Gánh Hà Nội', 'pho-ganh-ha-noi',
             'Phở bò truyền thống Hà Nội với nước dùng hầm 12 tiếng.',
             '12 Võ Văn Tần, Q3, TP.HCM', 'TP. Hồ Chí Minh', '0901111222',
             'https://images.unsplash.com/photo-1582878826629-29b7ad1cdc43?auto=format&fit=crop&w=800&q=80',
             'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?auto=format&fit=crop&w=1200&q=80',
             4.9, 1250, 50000, 0, 15, 0.8, 1, 1, 1, 0],
            [1, 'Cơm Tấm Ba Ghiền', 'com-tam-ba-ghien',
             'Cơm tấm sườn bì chả chuẩn vị Sài Gòn.',
             '45 Đinh Tiên Hoàng, Q Bình Thạnh, TP.HCM', 'TP. Hồ Chí Minh', '0902222333',
             'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?auto=format&fit=crop&w=800&q=80',
             'https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=1200&q=80',
             4.8, 980, 40000, 12000, 20, 1.2, 1, 1, 0, 1],
            [3, 'Burger Bò Wagyu House', 'burger-bo-wagyu',
             'Burger bò Wagyu nhập khẩu, pho mát chảy.',
             '78 Nguyễn Đình Chiểu, Q3, TP.HCM', 'TP. Hồ Chí Minh', '0903333444',
             'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?auto=format&fit=crop&w=800&q=80',
             'https://images.unsplash.com/photo-1550317138-10000687a72b?auto=format&fit=crop&w=1200&q=80',
             4.6, 760, 80000, 15000, 25, 2.1, 1, 0, 0, 1],
            [5, 'Sushi Hokkaido Authentic', 'sushi-hokkaido',
             'Sushi tươi sống nhập khẩu từ Nhật.',
             '156 Hai Bà Trưng, Q1, TP.HCM', 'TP. Hồ Chí Minh', '0904444555',
             'https://images.unsplash.com/photo-1579871494447-9811cf80d66c?auto=format&fit=crop&w=800&q=80',
             'https://images.unsplash.com/photo-1617196034183-421b4040ed20?auto=format&fit=crop&w=1200&q=80',
             4.9, 1680, 100000, 20000, 30, 3.5, 1, 1, 1, 0],
            [6, 'Phúc Long Tea & Coffee', 'phuc-long-tea-coffee',
             'Trà sữa, trà đào, cà phê đặc sắc.',
             '23 Lê Văn Sỹ, Q Phú Nhuận, TP.HCM', 'TP. Hồ Chí Minh', '0905555666',
             'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?auto=format&fit=crop&w=800&q=80',
             'https://images.unsplash.com/photo-1453614512568-c4024d13c247?auto=format&fit=crop&w=1200&q=80',
             4.7, 2100, 30000, 10000, 18, 1.5, 1, 0, 1, 1],
            [4, 'Pizza Napoli Express', 'pizza-napoli',
             'Pizza kiểu Napoli truyền thống, lò củi 900°C.',
             '67 Cách Mạng Tháng 8, Q10, TP.HCM', 'TP. Hồ Chí Minh', '0906666777',
             'https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?auto=format&fit=crop&w=800&q=80',
             'https://images.unsplash.com/photo-1571997478779-2adcbbe9ab2f?auto=format&fit=crop&w=1200&q=80',
             4.5, 520, 60000, 15000, 35, 4.2, 1, 0, 0, 0],
            [2, 'Bún Chả Hà Nội Cô Lan', 'bun-cha-ha-noi',
             'Bún chả đặc sản Hà Nội, chả nướng than hoa.',
             '34 Phan Xích Long, Q Phú Nhuận, TP.HCM', 'TP. Hồ Chí Minh', '0907777888',
             'https://images.unsplash.com/photo-1559847844-5315695dadae?auto=format&fit=crop&w=800&q=80',
             'https://images.unsplash.com/photo-1482049016688-2d3e1b311543?auto=format&fit=crop&w=1200&q=80',
             4.7, 890, 45000, 12000, 22, 1.8, 1, 1, 0, 1],
            [8, 'Lẩu Thái KungFu', 'lau-thai-kungfu',
             'Lẩu Thái chua cay nồng nàn, hải sản tươi sống.',
             '89 Võ Thị Sáu, Q3, TP.HCM', 'TP. Hồ Chí Minh', '0908888999',
             'https://images.unsplash.com/photo-1547592166-23ac45744acd?auto=format&fit=crop&w=800&q=80',
             'https://images.unsplash.com/photo-1569050467447-ce54b3bbc37d?auto=format&fit=crop&w=1200&q=80',
             4.8, 1450, 150000, 0, 40, 2.7, 1, 0, 1, 0],
        ];

        foreach ($restaurants as $r) {
            $wpdb->insert($rest_table, [
                'category_id'  => $r[0],  'name'         => $r[1],
                'slug'         => $r[2],  'description'  => $r[3],
                'address'      => $r[4],  'province'     => $r[5],
                'phone'        => $r[6],  'image'        => $r[7],
                'cover_image'  => $r[8],  'rating'       => $r[9],
                'total_reviews'=> $r[10], 'min_order'    => $r[11],
                'delivery_fee' => $r[12], 'delivery_time'=> $r[13],
                'distance'     => $r[14], 'is_open'      => $r[15],
                'is_featured'  => $r[16], 'has_freeship' => $r[17],
                'has_deal'     => $r[18],
            ]);
        }

        // Menu categories & items cho nhà hàng 1 (Phở Gánh)
        $rest1 = $wpdb->get_var("SELECT id FROM $rest_table WHERE slug='pho-ganh-ha-noi'");
        if ($rest1) {
            $wpdb->insert($mc_table, ['restaurant_id' => $rest1, 'name' => 'Combo Tiết Kiệm', 'sort_order' => 1]);
            $mc1 = $wpdb->insert_id;
            $wpdb->insert($mc_table, ['restaurant_id' => $rest1, 'name' => 'Phở Bò', 'sort_order' => 2]);
            $mc2 = $wpdb->insert_id;
            $wpdb->insert($mc_table, ['restaurant_id' => $rest1, 'name' => 'Thức Uống', 'sort_order' => 3]);
            $mc3 = $wpdb->insert_id;

            $items = [
                [$rest1, $mc1, 'Combo Phở Bò Đặc Biệt + Nước', 'Phở bò tái chín đặc biệt + 1 Trà đá', 75000, 90000, 'https://images.unsplash.com/photo-1582878826629-29b7ad1cdc43?auto=format&fit=crop&w=400&q=80', 1],
                [$rest1, $mc2, 'Phở Bò Tái Chín Đặc Biệt', 'Tô lớn đầy ắp: tái, chín, gầu, nạm, gân', 75000, null, 'https://images.unsplash.com/photo-1582878826629-29b7ad1cdc43?auto=format&fit=crop&w=400&q=80', 1],
                [$rest1, $mc2, 'Phở Bò Tái', 'Thịt tái mỏng hồng, mềm mượt', 65000, null, 'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?auto=format&fit=crop&w=400&q=80', 0],
                [$rest1, $mc2, 'Phở Bò Viên', 'Bò viên dai giòn sần sật', 60000, null, 'https://images.unsplash.com/photo-1565557623262-b51c2513a641?auto=format&fit=crop&w=400&q=80', 0],
                [$rest1, $mc3, 'Trà Đá', 'Trà xanh pha lạnh giải khát', 10000, null, 'https://images.unsplash.com/photo-1556679343-c7306c1976bc?auto=format&fit=crop&w=400&q=80', 0],
                [$rest1, $mc3, 'Nước Chanh', 'Chanh tươi vắt pha đường', 20000, null, 'https://images.unsplash.com/photo-1621263764928-df1444c5e859?auto=format&fit=crop&w=400&q=80', 0],
            ];
            foreach ($items as $item) {
                $wpdb->insert($mi_table, [
                    'restaurant_id'    => $item[0], 'menu_category_id' => $item[1],
                    'name'             => $item[2], 'description'      => $item[3],
                    'price'            => $item[4], 'original_price'   => $item[5],
                    'image'            => $item[6], 'is_best_seller'   => $item[7],
                ]);
            }
        }

        // Menu cho nhà hàng 2 (Cơm Tấm)
        $rest2 = $wpdb->get_var("SELECT id FROM $rest_table WHERE slug='com-tam-ba-ghien'");
        if ($rest2) {
            $wpdb->insert($mc_table, ['restaurant_id' => $rest2, 'name' => 'Combo Đầy Đủ', 'sort_order' => 1]);
            $mc1 = $wpdb->insert_id;
            $wpdb->insert($mc_table, ['restaurant_id' => $rest2, 'name' => 'Cơm Tấm', 'sort_order' => 2]);
            $mc2 = $wpdb->insert_id;

            $items = [
                [$rest2, $mc1, 'Combo Cơm Tấm Sườn Bì Chả', 'Cơm + Sườn nướng + Bì + Chả Trứng + Súp', 68000, 80000, 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?auto=format&fit=crop&w=400&q=80', 1],
                [$rest2, $mc2, 'Cơm Tấm Sườn Bì Chả', 'Sườn cốt lết nướng lửa than, bì da heo sợi giòn', 65000, null, 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?auto=format&fit=crop&w=400&q=80', 1],
                [$rest2, $mc2, 'Cơm Tấm Chỉ Sườn', 'Sườn nướng to bản, đậm vị kho lạc', 55000, null, 'https://images.unsplash.com/photo-1621252179027-94459d278660?auto=format&fit=crop&w=400&q=80', 0],
                [$rest2, $mc2, 'Cơm Tấm Bì Chả', 'Bì da và chả trứng', 50000, null, 'https://images.unsplash.com/photo-1512058564366-18510be2db19?auto=format&fit=crop&w=400&q=80', 0],
            ];
            foreach ($items as $item) {
                $wpdb->insert($mi_table, [
                    'restaurant_id'    => $item[0], 'menu_category_id' => $item[1],
                    'name'             => $item[2], 'description'      => $item[3],
                    'price'            => $item[4], 'original_price'   => $item[5],
                    'image'            => $item[6], 'is_best_seller'   => $item[7],
                ]);
            }
        }

        // Vouchers
        $vouchers = [
            [null, 'CICA20',    'Chào bạn mới - Giảm 20%',    'Giảm 20% cho đơn đầu tiên, tối đa 50.000đ', 'percent', 20,    50000, 50000,  500, '2026-12-31'],
            [null, 'FREESHIP',  'Miễn phí vận chuyển',         'Freeship cho mọi đơn từ 50.000đ',           'freeship', 100,  30000, 50000, 1000, '2026-12-31'],
            [null, 'CICASAVE50','Giảm 50.000đ',                'Giảm thẳng 50.000đ cho đơn từ 150.000đ',   'fixed',  50000, 50000, 150000, 200, '2026-12-31'],
            [null, 'WEEKEND30', 'Cuối tuần vui - Giảm 30%',   'Giảm 30% mỗi cuối tuần, tối đa 70.000đ',   'percent', 30,    70000, 80000,  150, '2026-12-31'],
        ];
        foreach ($vouchers as $v) {
            $wpdb->insert($vou_table, [
                'restaurant_id' => $v[0], 'code'        => $v[1],
                'name'          => $v[2], 'description' => $v[3],
                'type'          => $v[4], 'value'       => $v[5],
                'max_discount'  => $v[6], 'min_order'   => $v[7],
                'usage_limit'   => $v[8], 'end_date'    => $v[9],
            ]);
        }
    }
}
