<?php
/**
 * Template: Trang đăng nhập / đăng ký Cicafood
 * Dùng get_header/get_footer để WP load đầy đủ
 */
defined('ABSPATH') || exit;

get_header();
?>
<style>
/* Ẩn header/footer trên trang auth */
#cica-header, footer { display: none !important; }
body { background: #f8f8f8 !important; }
</style>
<?php
include CICAFOOD_PLUGIN_DIR . 'views/auth.php';
get_footer();
