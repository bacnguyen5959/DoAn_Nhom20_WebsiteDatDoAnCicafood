<?php
/**
 * Template: Trang nhà hàng yêu thích
 */
defined('ABSPATH') || exit;

get_header();

if (!is_user_logged_in()) {
    wp_redirect(wp_login_url(get_site_url() . '/yeu-thich/'));
    exit;
}
?>
<main id="main-content">
<?php include CICAFOOD_PLUGIN_DIR . 'views/favorites.php'; ?>
</main>
<?php
get_footer();
