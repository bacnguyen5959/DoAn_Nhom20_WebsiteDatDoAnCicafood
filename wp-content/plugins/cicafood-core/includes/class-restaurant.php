<?php
defined('ABSPATH') || exit;

/**
 * Cicafood_Restaurant — CRUD và query nhà hàng
 */
class Cicafood_Restaurant {

    private static string $table;

    public static function init(): void {
        global $wpdb;
        self::$table = $wpdb->prefix . 'cica_restaurants';
    }

    /**
     * Lấy danh sách nhà hàng với filter
     */
    public static function get_list(array $args = []): array {
        global $wpdb;
        $table = $wpdb->prefix . 'cica_restaurants';
        $cat   = $wpdb->prefix . 'cica_categories';

        $defaults = [
            'category_slug' => '',
            'province'      => '',
            'search'        => '',
            'is_featured'   => null,
            'has_freeship'  => null,
            'has_deal'      => null,
            'is_open'       => null,
            'sort'          => 'rating_desc',
            'limit'         => 12,
            'offset'        => 0,
        ];
        $args = wp_parse_args($args, $defaults);

        $where  = ['1=1'];
        $params = [];

        if (!empty($args['category_slug'])) {
            $where[]  = "c.slug = %s";
            $params[] = $args['category_slug'];
        }
        if (!empty($args['province'])) {
            $where[]  = "r.province = %s";
            $params[] = $args['province'];
        }
        if (!empty($args['search'])) {
            $where[]  = "(r.name LIKE %s OR r.address LIKE %s)";
            $params[] = '%' . $wpdb->esc_like($args['search']) . '%';
            $params[] = '%' . $wpdb->esc_like($args['search']) . '%';
        }
        if ($args['is_featured'] !== null) {
            $where[]  = "r.is_featured = %d";
            $params[] = (int) $args['is_featured'];
        }
        if ($args['has_freeship'] !== null) {
            $where[]  = "r.has_freeship = %d";
            $params[] = (int) $args['has_freeship'];
        }
        if ($args['has_deal'] !== null) {
            $where[]  = "r.has_deal = %d";
            $params[] = (int) $args['has_deal'];
        }
        if ($args['is_open'] !== null) {
            $where[]  = "r.is_open = %d";
            $params[] = (int) $args['is_open'];
        }

        $order = match ($args['sort']) {
            'rating_desc'   => 'r.rating DESC',
            'delivery_asc'  => 'r.delivery_fee ASC',
            'time_asc'      => 'r.delivery_time ASC',
            'newest'        => 'r.created_at DESC',
            default         => 'r.is_featured DESC, r.rating DESC',
        };

        $where_sql = implode(' AND ', $where);
        $sql = "SELECT r.*, c.name AS category_name, c.icon AS category_icon
                FROM $table r
                LEFT JOIN $cat c ON r.category_id = c.id
                WHERE $where_sql
                ORDER BY $order
                LIMIT %d OFFSET %d";

        $params[] = (int) $args['limit'];
        $params[] = (int) $args['offset'];

        return $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A) ?: [];
    }

    /**
     * Lấy 1 nhà hàng theo slug
     */
    public static function get_by_slug(string $slug): ?array {
        global $wpdb;
        $table = $wpdb->prefix . 'cica_restaurants';
        $cat   = $wpdb->prefix . 'cica_categories';

        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT r.*, c.name AS category_name, c.icon AS category_icon
             FROM $table r
             LEFT JOIN $cat c ON r.category_id = c.id
             WHERE r.slug = %s LIMIT 1",
            $slug
        ), ARRAY_A);

        return $row ?: null;
    }

    /**
     * Lấy 1 nhà hàng theo ID
     */
    public static function get_by_id(int $id): ?array {
        global $wpdb;
        $table = $wpdb->prefix . 'cica_restaurants';
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d LIMIT 1", $id
        ), ARRAY_A);
        return $row ?: null;
    }

    /**
     * Đếm tổng số nhà hàng (dùng cho pagination)
     */
    public static function count(array $args = []): int {
        global $wpdb;
        $table = $wpdb->prefix . 'cica_restaurants';
        $cat   = $wpdb->prefix . 'cica_categories';

        $where  = ['1=1'];
        $params = [];

        if (!empty($args['category_slug'])) {
            $where[]  = "c.slug = %s";
            $params[] = $args['category_slug'];
        }
        if (!empty($args['search'])) {
            $where[]  = "r.name LIKE %s";
            $params[] = '%' . $wpdb->esc_like($args['search']) . '%';
        }

        $where_sql = implode(' AND ', $where);
        $sql = "SELECT COUNT(*) FROM $table r LEFT JOIN $cat c ON r.category_id = c.id WHERE $where_sql";

        return (int) ($params
            ? $wpdb->get_var($wpdb->prepare($sql, $params))
            : $wpdb->get_var($sql));
    }

    /**
     * Lấy tất cả categories
     */
    public static function get_categories(): array {
        global $wpdb;
        $table = $wpdb->prefix . 'cica_categories';
        return $wpdb->get_results("SELECT * FROM $table ORDER BY sort_order ASC", ARRAY_A) ?: [];
    }

    /**
     * Toggle yêu thích
     */
    public static function toggle_favorite(int $restaurant_id, int $wp_user_id): array {
        global $wpdb;
        $table = $wpdb->prefix . 'cica_favorites';

        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE wp_user_id = %d AND restaurant_id = %d",
            $wp_user_id, $restaurant_id
        ));

        if ($exists) {
            $wpdb->delete($table, ['wp_user_id' => $wp_user_id, 'restaurant_id' => $restaurant_id]);
            return ['favorited' => false];
        } else {
            $wpdb->insert($table, ['wp_user_id' => $wp_user_id, 'restaurant_id' => $restaurant_id]);
            return ['favorited' => true];
        }
    }

    /**
     * Lấy danh sách yêu thích của user
     */
    public static function get_favorites(int $wp_user_id): array {
        global $wpdb;
        $fav   = $wpdb->prefix . 'cica_favorites';
        $rest  = $wpdb->prefix . 'cica_restaurants';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT r.* FROM $rest r
             INNER JOIN $fav f ON r.id = f.restaurant_id
             WHERE f.wp_user_id = %d
             ORDER BY f.created_at DESC",
            $wp_user_id
        ), ARRAY_A) ?: [];
    }
}
