<?php
define('WP_AUTO_UPDATE_CORE', 'minor');// This setting is required to make sure that WordPress updates can be properly managed in WordPress Toolkit. Remove this line if this WordPress website is not managed by WordPress Toolkit anymore.
define( 'WP_CACHE', true ); // Added by WP Rocket

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

// ** MySQL settings ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'pet-hamper' );

/** MySQL database username */
define( 'DB_USER', 'root' );

/** MySQL database password */
define( 'DB_PASSWORD', 'revolution' );

/** MySQL hostname */
define( 'DB_HOST', '127.0.0.1' );

/** Database Charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The Database Collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',          '+XBBja#Wd@TGPR%0KUu<miQKy3:7AgVRa)n#?AHXE)35#9@1u-k4yQKem4b<_(HH' );
define( 'SECURE_AUTH_KEY',   'DGaX=V4nWf-R}a6i)b(|>7uRYMJrCrv=0kLv:T++VwO+VK&,g8E=#[%1xUQhHzLk' );
define( 'LOGGED_IN_KEY',     'tHRo~glUo)6Fw_Tv5&eRV#H&M1*`w)1p7W +yE;_{@_}}t(r!=-l;Gj&rCw_R2{G' );
define( 'NONCE_KEY',         '>h2W<X 0@s`W]ptYAY.:<0mJpA:llA4VFDZ`r6J%|^5&G-=)^a*WA_`!Ip]$r]-X' );
define( 'AUTH_SALT',         'M)VB=Sp=Aa}A<Mbg0HOv#[Y!#?o;OsA*HWny K37d0]ZI3@Z41:w$hln*b9IAhmR' );
define( 'SECURE_AUTH_SALT',  '#^nzNThPIHI5R/u/WcG;M]Ws3cM~ 46@;fu*7f2z#quS!%El2~[0Oq5P.X%](GY_' );
define( 'LOGGED_IN_SALT',    'Hf>r/is>f-)O_[2`Kjc:2B<=t7t10>;xo7Q!ualWJ[{8,]0U IvFsRer3%4z(bQd' );
define( 'NONCE_SALT',        'Y(>^U{+O-gVJPNss!vM36 ;$D:$,zL4tYOfPryFc]*+*:?FfH$8O#nZYl31Fo)Q3' );
define( 'WP_CACHE_KEY_SALT', '=:5a*dmTs/O9::O,3EOcLFOYS_nj+*&SG_4<Am57q|k`$Ml^x.0_sWi*-xkyGh`{' );

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';




/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) )
	define( 'ABSPATH', dirname( __FILE__ ) . '/' );

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
