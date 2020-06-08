<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'website1' );

/** MySQL database username */
define( 'DB_USER', 'root' );

/** MySQL database password */
define( 'DB_PASSWORD', '' );

/** MySQL hostname */
define( 'DB_HOST', 'localhost' );

/** Database Charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The Database Collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         ')_KE5VJW8JE6Fj&0t^5Z7@oVhTp+SvBW4Uxor+C1+/%0}3m&(.]t@[5MQ*k~ Fwa' );
define( 'SECURE_AUTH_KEY',  '2}&WvRAF(SgHU5y^05_]kI]R!WSfsuK+_Mqq*2ExLSoG3r1Aa/kf2p/=:YhY+*[g' );
define( 'LOGGED_IN_KEY',    'WeKbH<%MB!JEYJ^f;__ektVA-g6#S@:G[9Xy^ ;c^<MOX A;L8nR7-K`nx/m6=%;' );
define( 'NONCE_KEY',        ' hrW2 k/i!Z$EEhF(=Vdu#%;?d4O:I:BY+:78Rc-(yq5l3t@v$q~!rPbcO!}~(<-' );
define( 'AUTH_SALT',        '$cZ}A)folH!-Ce+c)hUn)yc8XG7F+cYycuFu)[DL$qw1HZbb=b0UDb8>i?.$} 7|' );
define( 'SECURE_AUTH_SALT', 'cc6GZPtDW)7>R2q*EyR}!zu $]8(;m]c$i,C%45[S/EeK#z4=HIw/*Id8D&#fMqa' );
define( 'LOGGED_IN_SALT',   '1&pT?//.;1#|y@v!?N=(L]_!BMbE+pR9ZYLG;zCdb!)4xF4#6L5M=CGIXe!P@/z ' );
define( 'NONCE_SALT',       'T@Ae^$5_Kg`P-pYQy7}K_[d!3G?>%`( _SEXa|sT,Ql18SIc[A%4-ufmBHxIQET%' );

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
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
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', false );

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
