<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'cicafood_wp' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         '~b_kqvloG9aT8?MQ|Dr{kNXY;Z^ `s>J73j{-Kc.5HU[A4zTqiK}17o@m90#p6q]' );
define( 'SECURE_AUTH_KEY',  'gQ/ZV6[^p[dk4|Afui}$KspG>UgcM 9p2~mFJc/7/~W[jMhd8J>8!qHPvfC/]Ja0' );
define( 'LOGGED_IN_KEY',    'Y,viEwPxJ}am#s9Bw4Byf l:Y(H)[>z,grn/i>_-OP( CSmVI# 4U|Ce4ACaI-&b' );
define( 'NONCE_KEY',        'r!.Vn:gCusFK>|gWK.O6qT9xTuiiY{fW:H_p`twVY7dBLHJHHdHrx@j-f~Oe5]b7' );
define( 'AUTH_SALT',        '{Ab:_V4 }-mSd0Yo9!Ja0*z|KGf7,5|ocnWAn%8V.o*/H=c.x>yt&_@DC.HL8<@g' );
define( 'SECURE_AUTH_SALT', 'Or#2z;UZ8&Pg>/8oXx 6?@(Ie|%4>dq|P Wjw@GJB7)WwB+t@{S<I_e (XR=a!BC' );
define( 'LOGGED_IN_SALT',   'tjYVqNSCJKR>Vpy0H&jFm~_/+}W*Su`>cz}ag_UWaBfJ?YbkFz=ng.XP6^j~rTRb' );
define( 'NONCE_SALT',       '8I&N-kN9_|un$[YhBi!?iR)g${d.@b1YqG}D9S&c|6MZghJ.sNzL:?5PyUE/y!5 ' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */
@ini_set( 'upload_max_filesize', '1024M' );
@ini_set( 'post_max_size', '1024M' );
@ini_set( 'memory_limit', '1024M' );
@ini_set( 'max_execution_time', '300' );
@ini_set( 'max_input_time', '300' );


/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
