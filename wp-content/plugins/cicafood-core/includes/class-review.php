<?php
defined('ABSPATH') || exit;

/**
 * Cicafood_Review — Đánh giá nhà hàng
 * Copy logic từ foodbooking/api/review/submit_rating.php
 */
class Cicafood_Review {

    public static function submit(int $restaurant_id, int $wp_user_id, int $rating, string $comment): array {
        global $wpdb;
        $reviews_table = $wpdb->prefix . 'cica_reviews';
        $orders_table  = $wpdb->prefix . 'cica_orders';
        $rest_table    = $wpdb->prefix . 'cica_restaurants';

        if ($rating < 1 || $rating > 5) return ['success' => false, 'message' => 'Rating không hợp lệ'];

        // Kiểm tra có đơn hoàn thành chưa được đánh giá
        $unreviewed = $wpdb->get_var($wpdb->prepare(
            "SELECT o.id FROM $orders_table o
             LEFT JOIN $reviews_table r ON r.order_id = o.id
             WHERE o.wp_user_id = %d AND o.restaurant_id = %d AND o.status = 'completed' AND r.id IS NULL
             LIMIT 1",
            $wp_user_id, $restaurant_id
        ));

        if (!$unreviewed) {
            return ['success' => false, 'message' => 'Bạn cần hoàn thành đơn hàng tại quán này để đánh giá.'];
        }

        $wpdb->insert($reviews_table, [
            'wp_user_id'    => $wp_user_id,
            'restaurant_id' => $restaurant_id,
            'order_id'      => (int)$unreviewed,
            'rating'        => $rating,
            'comment'       => sanitize_textarea_field($comment),
        ]);

        // Cập nhật rating trung bình
        $avg = $wpdb->get_row($wpdb->prepare(
            "SELECT AVG(rating) AS avg_r, COUNT(*) AS total FROM $reviews_table WHERE restaurant_id = %d AND is_visible = 1",
            $restaurant_id
        ));
        $wpdb->update($rest_table, [
            'rating'       => round((float)$avg->avg_r, 1),
            'total_reviews'=> (int)$avg->total,
        ], ['id' => $restaurant_id]);

        $user = get_userdata($wp_user_id);
        return [
            'success'       => true,
            'message'       => 'Cảm ơn bạn đã đánh giá!',
            'new_rating'    => round((float)$avg->avg_r, 1),
            'total_reviews' => (int)$avg->total,
            'review'        => [
                'user_name'  => $user ? $user->display_name : 'Người dùng',
                'rating'     => $rating,
                'comment'    => esc_html($comment),
                'created_at' => current_time('d/m/Y H:i'),
            ],
        ];
    }

    public static function get_by_restaurant(int $restaurant_id, int $limit = 10): array {
        global $wpdb;
        $table = $wpdb->prefix . 'cica_reviews';
        return $wpdb->get_results($wpdb->prepare(
            "SELECT r.*, u.display_name AS user_name
             FROM $table r LEFT JOIN {$wpdb->users} u ON r.wp_user_id = u.ID
             WHERE r.restaurant_id = %d AND r.is_visible = 1
             ORDER BY r.created_at DESC LIMIT %d",
            $restaurant_id, $limit
        ), ARRAY_A) ?: [];
    }
}
