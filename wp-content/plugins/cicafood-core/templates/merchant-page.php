<?php
/**
 * Template: Merchant Dashboard
 */
defined('ABSPATH') || exit;

get_header();

if (!is_user_logged_in()) {
    wp_redirect(wp_login_url(get_site_url() . '/quan-ly/'));
    exit;
}
?>
<main id="main-content">
<?php include CICAFOOD_PLUGIN_DIR . 'views/merchant-dashboard.php'; ?>
</main>
<?php
get_footer();
