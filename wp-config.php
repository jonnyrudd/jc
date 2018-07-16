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
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'runningcloud');

/** MySQL database username */
define('DB_USER', 'root');

/** MySQL database password */
define('DB_PASSWORD', 'HON123well@');

/** MySQL hostname */
define('DB_HOST', 'localhost');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

define('WP_HOME','http://192.168.159.100/jc');
define('WP_SITEURL','http://192.168.159.100/jc');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         'XL-8d{&?[.B$zq:t-!510<!K^:ul7V([7k-b_FY)LebFj^G;6-rOj(;T4Rk&JGg6');
define('SECURE_AUTH_KEY',  '4Qxg5n?f#}^$fqj]jP,D{s0kB&}Ht0/Nu~HQWSAgU!K49Z1zp|.Ap+$WURk&j.5I');
define('LOGGED_IN_KEY',    '~t/I`00SO@FP<v~f]MC=J&v{|Ujg~*yx$6GA6>{1`n>5<JpuJ^7R!O[)lp#jc(HV');
define('NONCE_KEY',        '4W6IXpB##m>J*4.oP-?O |I *>8BnHSX39F*/m91AT/1`<xe70OaI;s#CX[V|VJf');
define('AUTH_SALT',        'S-2xEO/+@3KV3X =g;z^@w*d5g3&r>>jS:AelW}T<6sYJ6YwVLA]R;cc%jGlnT<7');
define('SECURE_AUTH_SALT', 'D<lm%T.3oxm5[ZKD7+wH-O.%pI`/p0[#I8B81s{fy?/rz2>(@.3?%SD,LXU!SeiM');
define('LOGGED_IN_SALT',   'oj!8;OvVO>26:P<AOYfcMRMYTP:`ZQ27_~/xpVq]:lyw1=M}<,qA0,8[j7##h;iF');
define('NONCE_SALT',       'uy.$5mVS(.<}tC#=w~$6|i/6GQ~-ipj_$-i&9r*0d>XZ9`d_#&q<XsH8B<gyF)Ct');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the Codex.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', false );
define( 'WP_DEBUG_DISPLAY', false );
@ini_set( 'display_errors', 0 );

define('FS_METHOD','direct');

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
