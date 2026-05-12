<?php
defined('ABSPATH') || exit;

/**
 * Cicafood_Menu — Menu items và categories
 */
class Cicafood_Menu {

    public static function get_categories(int $restaurant_id): array {
        global $wpdb;
        $table = $wpdb->prefix . 'cica_menu_categories';
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE restaurant_id = %d ORDER BY sort_order ASC",
            $restaurant_id
        ), ARRAY_A) ?: [];
    }

    public static function get_items(int $restaurant_id, ?int $category_id = null): array {
        global $wpdb;
        $table = $wpdb->prefix . 'cica_menu_items';
        if ($category_id) {
            return $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table WHERE restaurant_id = %d AND menu_category_id = %d AND is_available = 1 ORDER BY sort_order ASC",
                $restaurant_id, $category_id
            ), ARRAY_A) ?: [];
        }
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE restaurant_id = %d AND is_available = 1 ORDER BY menu_category_id ASC, sort_order ASC",
            $restaurant_id
        ), ARRAY_A) ?: [];
    }

    public static function get_items_grouped(int $restaurant_id): array {
        $categories = self::get_categories($restaurant_id);
        $all_items  = self::get_items($restaurant_id);

        $grouped = [];
        foreach ($categories as $cat) {
            $cat['items'] = array_filter($all_items, fn($i) => (int)$i['menu_category_id'] === (int)$cat['id']);
            $cat['items'] = array_values($cat['items']);
            if (!empty($cat['items'])) $grouped[] = $cat;
        }

        // Items không có category
        $uncategorized = array_filter($all_items, fn($i) => empty($i['menu_category_id']));
        if (!empty($uncategorized)) {
            $grouped[] = ['id' => 0, 'name' => 'Khác', 'items' => array_values($uncategorized)];
        }

        return $grouped;
    }
}
