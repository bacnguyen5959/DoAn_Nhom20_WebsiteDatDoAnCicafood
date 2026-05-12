<?php
defined('ABSPATH') || exit;

/**
 * Cicafood_Voucher — Logic voucher
 * Copy từ foodbooking/api/order/apply_voucher.php + api/voucher/
 */
class Cicafood_Voucher {

    /**
     * Áp dụng voucher — trả về discount amount
     */
    public static function apply(string $code, int $subtotal, int $restaurant_id = 0): array {
        global $wpdb;
        $table = $wpdb->prefix . 'cica_vouchers';

        $voucher = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE code = %s AND (end_date IS NULL OR end_date >= CURDATE()) AND is_active = 1 AND (restaurant_id IS NULL OR restaurant_id = %d) LIMIT 1",
            strtoupper(trim($code)), $restaurant_id
        ), ARRAY_A);

        if (!$voucher) return ['success' => false, 'message' => 'Mã voucher không hợp lệ hoặc không áp dụng cho quán này'];

        if ($subtotal < (int)$voucher['min_order']) {
            return ['success' => false, 'message' => 'Đơn hàng tối thiểu ' . cica_format_price((int)$voucher['min_order']) . ' để dùng voucher này'];
        }

        $discount = 0;
        if ($voucher['type'] === 'percent') {
            $discount = (int)($subtotal * (int)$voucher['value'] / 100);
            if ($voucher['max_discount']) $discount = min($discount, (int)$voucher['max_discount']);
        } elseif ($voucher['type'] === 'fixed') {
            $discount = min((int)$voucher['value'], $subtotal);
        } elseif ($voucher['type'] === 'freeship') {
            $discount = (int)($voucher['max_discount'] ?? 30000);
        }

        return [
            'success'            => true,
            'message'            => "Áp dụng mã \"{$voucher['code']}\" thành công!",
            'voucher'            => $voucher,
            'discount_amount'    => $discount,
            'discount_formatted' => cica_format_price($discount),
        ];
    }

    /**
     * Lấy danh sách voucher khả dụng cho user
     */
    public static function get_available(int $subtotal, int $restaurant_id = 0, int $wp_user_id = 0): array {
        global $wpdb;
        $table = $wpdb->prefix . 'cica_vouchers';

        $vouchers = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE (end_date IS NULL OR end_date >= CURDATE()) AND is_active = 1 AND (restaurant_id IS NULL OR restaurant_id = %d) ORDER BY value DESC",
            $restaurant_id
        ), ARRAY_A) ?: [];

        foreach ($vouchers as &$v) {
            $v['is_valid']         = $subtotal >= (int)$v['min_order'];
            $v['reason']           = $v['is_valid'] ? '' : 'Đơn tối thiểu ' . cica_format_price((int)$v['min_order']);
            $v['min_order_formatted'] = cica_format_price((int)$v['min_order']);

            // Tính discount preview
            $d = 0;
            if ($v['type'] === 'percent') {
                $d = (int)($subtotal * (int)$v['value'] / 100);
                if ($v['max_discount']) $d = min($d, (int)$v['max_discount']);
            } elseif ($v['type'] === 'fixed') {
                $d = min((int)$v['value'], $subtotal);
            } elseif ($v['type'] === 'freeship') {
                $d = (int)($v['max_discount'] ?? 30000);
            }
            $v['discount_amount']    = $d;
            $v['discount_formatted'] = cica_format_price($d);
        }

        return $vouchers;
    }

    /**
     * Lưu voucher vào ví user
     */
    public static function save_to_wallet(int $voucher_id, int $wp_user_id): array {
        global $wpdb;
        $uv_table = $wpdb->prefix . 'cica_user_vouchers';
        $v_table  = $wpdb->prefix . 'cica_vouchers';

        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $uv_table WHERE wp_user_id = %d AND voucher_id = %d",
            $wp_user_id, $voucher_id
        ));
        if ($exists) return ['success' => false, 'message' => 'Bạn đã lưu voucher này rồi.'];

        $v = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM $v_table WHERE id = %d AND (end_date IS NULL OR end_date >= CURDATE())",
            $voucher_id
        ));
        if (!$v) return ['success' => false, 'message' => 'Voucher không tồn tại hoặc đã hết hạn.'];

        $wpdb->insert($uv_table, ['wp_user_id' => $wp_user_id, 'voucher_id' => $voucher_id]);
        return ['success' => true, 'message' => 'Đã lưu voucher vào Ví!'];
    }

    /**
     * Lấy voucher trong ví của user
     */
    public static function get_wallet(int $wp_user_id): array {
        global $wpdb;
        $uv = $wpdb->prefix . 'cica_user_vouchers';
        $v  = $wpdb->prefix . 'cica_vouchers';
        return $wpdb->get_results($wpdb->prepare(
            "SELECT v.*, uv.is_used, uv.used_at FROM $v v
             INNER JOIN $uv uv ON v.id = uv.voucher_id
             WHERE uv.wp_user_id = %d
             ORDER BY uv.created_at DESC",
            $wp_user_id
        ), ARRAY_A) ?: [];
    }
}
