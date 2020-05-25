<?php
 defined( 'ABSPATH' ) || exit;

 if ( ! function_exists( 'udesly_setup' ) ) :

	function udesly_setup() {

		load_theme_textdomain( 'squired', get_template_directory() . '/languages' );
		add_theme_support( 'title-tag' );
		add_theme_support( 'menus' );
		add_theme_support( 'post-thumbnails' );

		add_theme_support( 'html5', array(
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
		) );

		// Add theme support for selective refresh for widgets.
		add_theme_support( 'customize-selective-refresh-widgets' );

		add_theme_support( 'custom-logo', array(
			'height'      => 250,
			'width'       => 250,
			'flex-width'  => true,
			'flex-height' => true,
		) );
		add_theme_support( 'woocommerce' );
		add_theme_support( 'align-wide' );
	}
endif;
add_action( 'after_setup_theme', 'udesly_setup' );

function udesly_customize_register( $wp_customize ) {
	$wp_customize->get_setting( 'blogname' )->transport         = 'postMessage';
	$wp_customize->get_setting( 'blogdescription' )->transport  = 'postMessage';

	if ( isset( $wp_customize->selective_refresh ) ) {
		$wp_customize->selective_refresh->add_partial( 'blogname', array(
			'selector'        => '.site-title a',
			'render_callback' => 'udesly_customize_partial_blogname',
		) );
		$wp_customize->selective_refresh->add_partial( 'blogdescription', array(
			'selector'        => '.site-description',
			'render_callback' => 'udesly_customize_partial_blogdescription',
		) );
	}
}
add_action( 'customize_register', 'udesly_customize_register' );

function udesly_customize_partial_blogname() {
	bloginfo( 'name' );
}

function udesly_customize_partial_blogdescription() {
	bloginfo( 'description' );
}

remove_action('wp_head', 'rsd_link');
remove_action('wp_head', 'wlwmanifest_link');
remove_action('wp_head', 'wp_generator');
remove_action('wp_head', 'start_post_rel_link');
remove_action('wp_head', 'index_rel_link');
remove_action('wp_head', 'adjacent_posts_rel_link');

if ( ! current_user_can( 'manage_options' ) ) {
    show_admin_bar( false );
}

require_once dirname( __FILE__ ) . '/includes/class-tgm-plugin-activation.php';

add_action( 'tgmpa_register', 'udesly_theme_register_required_plugins' );

function udesly_theme_register_required_plugins() {

	$plugins = array(

		array(
			'name'      => 'Udesly Adapter',
			'slug'      => 'udesly-adapter-plugin',
			'source'    => 'https://github.com/eclipsesrl/udesly-adapter-plugin/archive/master.zip',
		),
	);

	$config = array(
		'id'           => 'tgmpa',                 
		'default_path' => '',                      
		'menu'         => 'tgmpa-install-plugins', 
		'parent_slug'  => 'themes.php',            
		'capability'   => 'edit_theme_options',    
		'has_notices'  => true,                    
		'dismissable'  => false,                   
		'dismiss_msg'  => '',                      
		'is_automatic' => true,                   
		'message'      => '',                     
	);

	tgmpa( $plugins, $config );
}

add_action( 'wp_enqueue_scripts', 'udesly_theme_enqueue_styles' );
function udesly_theme_enqueue_styles() {
    wp_enqueue_style( 'udesly-theme', get_stylesheet_directory_uri() .'/style.css' );
}

if (!defined('UDESLY_ENABLE_FRONTEND_EDITOR')) {
  define('UDESLY_ENABLE_FRONTEND_EDITOR', true);
}


