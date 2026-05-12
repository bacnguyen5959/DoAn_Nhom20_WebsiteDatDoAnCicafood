<?php
defined('ABSPATH') || exit;

/**
 * Cicafood_Cart — Giỏ hàng dùng WP session (qua $_SESSION)
 * Copy logic từ foodbooking/api/cart/add_to_cart.php
 */
class Cicafood_Cart {

    public static function init(): void {
        if (!session_id()) session_start();
    }

    public static function add(int $item_id, int $restaurant_id, int $qty = 1): array {
        global $wpdb;
        $table = $wpdb->prefix . 'cica_menu_items';

        // Conflict check — giỏ hàng chỉ từ 1 nhà hàng
        if (!empty($_SESSION['cica_cart']) && isset($_SESSION['cica_cart_restaurant_id'])) {
            if ((int)$_SESSION['cica_cart_restaurant_id'] !== $restaurant_id) {
                return ['success' => false, 'message' => 'Giỏ hàng đang có món từ nhà hàng khác. Bạn có muốn xoá và thêm mới?', 'conflict' => true];
            }
        }

        $item = $wpdb->get_row($wpdb->prepare(
            "SELECT id, name, price FROM $table WHERE id = %d AND is_available = 1 LIMIT 1",
            $item_id
        ), ARRAY_A);

        if (!$item) return ['success' => false, 'message' => 'Món ăn không tồn tại hoặc đã hết'];

        if (!isset($_SESSION['cica_cart'])) $_SESSION['cica_cart'] = [];
        $_SESSION['cica_cart_restaurant_id'] = $restaurant_id;

        if (isset($_SESSION['cica_cart'][$item_id])) {
            $_SESSION['cica_cart'][$item_id]['quantity'] += $qty;
        } else {
            $_SESSION['cica_cart'][$item_id] = [
                'id'       => $item['id'],
                'name'     => $item['name'],
                'price'    => (int)$item['price'],
                'quantity' => $qty,
            ];
        }

        return [
            'success'    => true,
            'message'    => "Đã thêm \"{$item['name']}\" vào giỏ hàng!",
            'cart_count' => self::count(),
            'cart'       => $_SESSION['cica_cart'],
        ];
    }

    public static function update(int $item_id, int $qty): array {
        if (!isset($_SESSION['cica_cart'][$item_id])) {
            return ['success' => false, 'message' => 'Món không có trong giỏ'];
        }
        if ($qty <= 0) {
            unset($_SESSION['cica_cart'][$item_id]);
            if (empty($_SESSION['cica_cart'])) unset($_SESSION['cica_cart_restaurant_id']);
        } else {
            $_SESSION['cica_cart'][$item_id]['quantity'] = $qty;
        }
        return ['success' => true, 'cart_count' => self::count(), 'cart' => $_SESSION['cica_cart'] ?? []];
    }

    public static function clear(): void {
        $_SESSION['cica_cart'] = [];
        unset($_SESSION['cica_cart_restaurant_id']);
    }

    public static function count(): int {
        if (empty($_SESSION['cica_cart'])) return 0;
        return array_sum(array_column($_SESSION['cica_cart'], 'quantity'));
    }

    public static function subtotal(): int {
        if (empty($_SESSION['cica_cart'])) return 0;
        $total = 0;
        foreach ($_SESSION['cica_cart'] as $item) {
            $total += $item['price'] * $item['quantity'];
        }
        return $total;
    }

    public static function get(): array {
        return $_SESSION['cica_cart'] ?? [];
    }

    public static function restaurant_id(): int {
        return (int)($_SESSION['cica_cart_restaurant_id'] ?? 0);
    }
}
