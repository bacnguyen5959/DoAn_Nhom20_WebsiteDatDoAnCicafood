<?php
/**
 * Template: Trang chi tiết nhà hàng
 * Load đúng WP lifecycle — scripts được enqueue trước khi render
 */
defined('ABSPATH') || exit;
if (!session_id()) session_start();

global $cicafood_restaurant_slug;
$slug = $cicafood_restaurant_slug ?: sanitize_text_field(
    preg_match('#/nha-hang/([^/?]+)#', $_SERVER['REQUEST_URI'], $m) ? $m[1] : ''
);

get_header();
?>
<main id="main-content">
<?php
if ($slug) {
    include CICAFOOD_PLUGIN_DIR . 'views/restaurant-detail.php';
} else {
    echo '<div style="text-align:center;padding:4rem"><p>Không tìm thấy nhà hàng.</p></div>';
}
?>
</main>
<?php
get_footer();
