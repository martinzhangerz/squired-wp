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
define( 'DB_NAME', 'squireddemo' );

/** MySQL database username */
define( 'DB_USER', 'root' );

/** MySQL database password */
define( 'DB_PASSWORD', 'root' );

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
define( 'AUTH_KEY',         'YI17~4=XZfRQ5{!46Z!QQeamW#[;#j,w E]v%`,+|47:c As-8XhBM_S%/z$?zdR' );
define( 'SECURE_AUTH_KEY',  'b7i_/uZf)Tg*[*2M;Zs2nwlArG_c[Ho45-(k#i/_pca#)`e5y00R-u@OHrk:YJTZ' );
define( 'LOGGED_IN_KEY',    'W_!]ZPRE_=|xu(z@QJ[c,x4383!hq#bkA&!Ag,{]}W`:@qN?li4&}F^OTx.}poT3' );
define( 'NONCE_KEY',        'i[GRM%Cc|(Ny&-i`7/@gLIq[(1yy(,JAgWe!#i^>J+Y*IAdFIg@p@O< |w;-NJA=' );
define( 'AUTH_SALT',        '[8Pi-%,SPNJyzYjJ=Z9OP=lpXsSb61xp4e!@y5qu4t[Nf@3,D|V0/nO7T+*&8e/2' );
define( 'SECURE_AUTH_SALT', ' Svb/dGMFIee1,NVXEr^H1K={u=D(7xT^84FW,#J{!UAdef#SL4?2i?nf.,4K3VJ' );
define( 'LOGGED_IN_SALT',   'puN 2#9gd3MO^|.!G YnlS$(n.y#;rUVP*~JW0*x(ExHne.v5BJu:01c~5Lgc`m&' );
define( 'NONCE_SALT',       'llca.E.LrSdDsn64P8Icc3 k,N@IJ<Un6-LhHB%0 [Ef3kQs%Nf$g@8 tSK*fw*B' );

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
 * visit the Codex.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define( 'WP_DEBUG', false );

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __FILE__ ) . '/' );
}

/** Sets up WordPress vars and included files. */
require_once( ABSPATH . 'wp-settings.php' );
