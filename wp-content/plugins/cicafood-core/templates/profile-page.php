<?php
/**
 * Template: Trang hồ sơ người dùng
 */
defined('ABSPATH') || exit;

get_header();

if (!is_user_logged_in()) {
    wp_redirect(wp_login_url(get_site_url() . '/ho-so/'));
    exit;
}
?>
<main id="main-content">
<?php include CICAFOOD_PLUGIN_DIR . 'views/profile.php'; ?>
</main>
<?php
get_footer();
