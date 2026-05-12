<?php
defined('ABSPATH') || exit;

/**
 * Cicafood_Order — Tạo và quản lý đơn hàng
 * Copy logic từ foodbooking/views/order/checkout.php
 */
class Cicafood_Order {

    /**
     * Tạo đơn hàng mới
     */
    public static function create(array $data): array {
        global $wpdb;
        $orders_table = $wpdb->prefix . 'cica_orders';
        $items_table  = $wpdb->prefix . 'cica_order_items';

        $required = ['wp_user_id', 'restaurant_id', 'delivery_address', 'recipient_name', 'recipient_phone'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return ['success' => false, 'message' => "Thiếu thông tin: $field"];
            }
        }

        $cart = Cicafood_Cart::get();
        if (empty($cart)) return ['success' => false, 'message' => 'Giỏ hàng trống'];

        $subtotal      = Cicafood_Cart::subtotal();
        $delivery_fee  = (int)($data['delivery_fee'] ?? 0);
        $service_fee   = (int)round($subtotal * 2 / 100); // 2%
        $discount      = (int)($data['discount_amount'] ?? 0);
        $ship_discount = (int)($data['shipping_discount'] ?? 0);
        $final_ship    = max(0, $delivery_fee - $ship_discount);
        $total         = max(0, $subtotal + $final_ship + $service_fee - $discount);
        $order_code    = self::generate_code();

        $wpdb->query('START TRANSACTION');
        try {
            $wpdb->insert($orders_table, [
                'order_code'       => $order_code,
                'wp_user_id'       => (int)$data['wp_user_id'],
                'restaurant_id'    => (int)$data['restaurant_id'],
                'delivery_address' => sanitize_textarea_field($data['delivery_address']),
                'recipient_name'   => sanitize_text_field($data['recipient_name']),
                'recipient_phone'  => sanitize_text_field($data['recipient_phone']),
                'note'             => sanitize_textarea_field($data['note'] ?? ''),
                'subtotal'         => $subtotal,
                'delivery_fee'     => $final_ship,
                'service_fee'      => $service_fee,
                'discount_amount'  => $discount,
                'shipping_discount'=> $ship_discount,
                'total_amount'     => $total,
                'voucher_code'     => sanitize_text_field($data['voucher_code'] ?? ''),
                'payment_method'   => 'cod',
                'payment_status'   => 'pending',
                'status'           => 'pending',
            ]);
            $order_id = $wpdb->insert_id;

            // Lưu chi tiết đơn
            foreach ($cart as $item_id => $item) {
                $wpdb->insert($items_table, [
                    'order_id'     => $order_id,
                    'menu_item_id' => (int)$item_id,
                    'item_name'    => $item['name'],
                    'item_price'   => (int)$item['price'],
                    'quantity'     => (int)$item['quantity'],
                    'subtotal'     => (int)$item['price'] * (int)$item['quantity'],
                ]);
            }

            $wpdb->query('COMMIT');
            Cicafood_Cart::clear();

            return ['success' => true, 'order_id' => $order_id, 'order_code' => $order_code, 'total' => $total];

        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            return ['success' => false, 'message' => 'Lỗi tạo đơn hàng: ' . $e->getMessage()];
        }
    }

    /**
     * Lấy đơn hàng theo ID
     */
    public static function get_by_id(int $id): ?array {
        global $wpdb;
        $table = $wpdb->prefix . 'cica_orders';
        $rest  = $wpdb->prefix . 'cica_restaurants';
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT o.*, r.name AS restaurant_name, r.image AS restaurant_image
             FROM $table o LEFT JOIN $rest r ON o.restaurant_id = r.id
             WHERE o.id = %d LIMIT 1",
            $id
        ), ARRAY_A);
        return $row ?: null;
    }

    /**
     * Lấy danh sách đơn hàng của user
     */
    public static function get_by_user(int $wp_user_id, int $limit = 10, int $offset = 0): array {
        global $wpdb;
        $table = $wpdb->prefix . 'cica_orders';
        $rest  = $wpdb->prefix . 'cica_restaurants';
        return $wpdb->get_results($wpdb->prepare(
            "SELECT o.*, r.name AS restaurant_name, r.image AS restaurant_image
             FROM $table o LEFT JOIN $rest r ON o.restaurant_id = r.id
             WHERE o.wp_user_id = %d
             ORDER BY o.created_at DESC LIMIT %d OFFSET %d",
            $wp_user_id, $limit, $offset
        ), ARRAY_A) ?: [];
    }

    /**
     * Lấy items của đơn hàng
     */
    public static function get_items(int $order_id): array {
        global $wpdb;
        $table = $wpdb->prefix . 'cica_order_items';
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE order_id = %d", $order_id
        ), ARRAY_A) ?: [];
    }

    /**
     * Huỷ đơn hàng
     */
    public static function cancel(int $order_id, int $wp_user_id): array {
        global $wpdb;
        $table = $wpdb->prefix . 'cica_orders';
        $order = self::get_by_id($order_id);
        if (!$order) return ['success' => false, 'message' => 'Đơn hàng không tồn tại'];
        if ((int)$order['wp_user_id'] !== $wp_user_id) return ['success' => false, 'message' => 'Không có quyền'];
        if (!in_array($order['status'], ['pending', 'confirmed'])) {
            return ['success' => false, 'message' => 'Không thể huỷ đơn ở trạng thái này'];
        }
        $wpdb->update($table, ['status' => 'cancelled'], ['id' => $order_id]);
        return ['success' => true, 'message' => 'Đã huỷ đơn hàng'];
    }

    /**
     * Label trạng thái đơn hàng
     */
    public static function status_label(string $status): array {
        $map = [
            'pending'    => ['label' => 'Chờ xác nhận', 'color' => 'yellow', 'icon' => 'fa-clock'],
            'confirmed'  => ['label' => 'Đã xác nhận',  'color' => 'blue',   'icon' => 'fa-check-circle'],
            'preparing'  => ['label' => 'Đang chuẩn bị','color' => 'orange', 'icon' => 'fa-fire-flame-curved'],
            'delivering' => ['label' => 'Đang giao',     'color' => 'purple', 'icon' => 'fa-motorcycle'],
            'completed'  => ['label' => 'Hoàn thành',    'color' => 'green',  'icon' => 'fa-circle-check'],
            'cancelled'  => ['label' => 'Đã huỷ',        'color' => 'red',    'icon' => 'fa-circle-xmark'],
        ];
        return $map[$status] ?? ['label' => $status, 'color' => 'gray', 'icon' => 'fa-question'];
    }

    private static function generate_code(): string {
        return 'CF' . strtoupper(substr(uniqid(), -6)) . rand(10, 99);
    }
}
