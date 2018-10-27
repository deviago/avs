<?php
ob_start();
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
define('DB_NAME', 'avs');

/** MySQL database username */
define('DB_USER', 'root');

/** MySQL database password */
define('DB_PASSWORD', '');

/** MySQL hostname */
define('DB_HOST', 'localhost');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8mb4');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         ' dt^}ib$%SajoUUSL?<5FViO9D`GD.-0O7>bohg!~M+<%sE1]*T@-tG1~`FGRL])');
define('SECURE_AUTH_KEY',  '>lF(/g:E{/}F!8c#sNW4,*+c]+sW]yV`.j2qts:9#KNS%>X5F%0]}l|<5K-GEfZ`');
define('LOGGED_IN_KEY',    '9DnE*5Hh-NkKi}Syms?k)6iP=_qM}@u~Ac4sCJ0d:LGfg8$Ap$4F0d_JwtY~)(^$');
define('NONCE_KEY',        'w!qaqi:KJ6<wSG=|F5?/OyIA@oO!64&!s!Gwj4gS?^*iWk5LnQ^dRS:yw7B{VrR8');
define('AUTH_SALT',        '-hv!God%qfJ-9TUr{?,i5ZffsI6h49g4P#W]Z&V0SPJC6&qQjNP0dX:,THo2-xj>');
define('SECURE_AUTH_SALT', 'DLw:{#;Me[LknZ[zJCYg&EUOv^2&*@9WdHPRB;,@h1T{?@JO[K7001E:$*y=EQu^');
define('LOGGED_IN_SALT',   '@yB1=kq<P.YnWv4gO5@+PqLiTRDu}8e@T`@20^=GXd*g*9ORr Eh tG}XGQ!{Vsf');
define('NONCE_SALT',       '~Xh;H2EDPjR(ul-=AP0VGf$P9:o23>R9Sdgu01vzs6O00}>X2uNl5U+RVt !X7l,');

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
define('WP_DEBUG', true);

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');