<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * Localized language
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'local' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', 'root' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

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
define( 'AUTH_KEY',          '2a&B$_eyvt%on51o83>*0L2[as` Z-yiC7k0`j{H^1~wA[p!Dt~23]97KWhFgrJ[' );
define( 'SECURE_AUTH_KEY',   'tiPQ6=_I#i&8I8&6]h</(q5`/y)-W#dH#`c$K0~mqpiIM>=S+;2hd{#mcCZV 8]U' );
define( 'LOGGED_IN_KEY',     'EM Pi]+4iBo+n:TZn^k>J3rc=RPg?69PgI~5/j*y^/Y#]6v%Ei!&:%{foDI)Y93*' );
define( 'NONCE_KEY',         '3:_Z E 6 mB;J5JHX9jw@W*/Fo(;[s%AJ?]-s=zvz.(Ga!=d*<{wc)C[ELu!#I)B' );
define( 'AUTH_SALT',         '-yn%tGZLo4W<Y8?L6kdWm8+;:!R8`;a6}*f&RA]D,P9Q8DIbA<vM_5ZDU_iSw;w$' );
define( 'SECURE_AUTH_SALT',  '{oMV@+ZKXxtN(Tc <sV/&fwVCn~y3(J^4te-jt*d;*Ms{,J0_+AC 7T2eJ?fd~xh' );
define( 'LOGGED_IN_SALT',    'bJ`L0[D%,kk~e`5@&cd|J2dLrN=sNk@B?);zSXi%KUB8Aj}fyq!84el&Walm&Db1' );
define( 'NONCE_SALT',        '[~cEG6.}Nl/Tuu{jxVeaSkcTk~+VqDC|Ids/~D5a@*BaFb/zfu``}q|zL(ETN_m[' );
define( 'WP_CACHE_KEY_SALT', '_2!{bumJLLJ@wV(@k9s4#nI#ou>oeVS1lCUxt8e2KiXN6}%@)5gBIB wBIvdtFk6' );


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'para_';


/* Add any custom values between this line and the "stop editing" line. */



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
if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', true );
}

define( 'WP_ENVIRONMENT_TYPE', 'local' );
/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
