<?php

// Security Check
if ( ! defined( 'WPINC' ) || ! defined( 'ABSPATH' ) ) {
	die;
}

if ( ! class_exists( 'Webflow_Pages_Admin' ) ) {


	class Webflow_Pages_Admin {

		/**
	 * The unique instance of the plugin.
	 *
	 * @var Webflow_Pages_Admin
	 */
		private static $instance;

		/**
		 * Gets an instance of our plugin.
		 *
		 * @return Webflow_Pages_Admin
		 */
		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		// Adds actions for the Admin Side
		public function init() {

			add_action( 'admin_menu', array( $this, 'register_menu_page' ) );

			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts'));

		}

		/**
		 * Register a custom menu page.
		 */
		function register_menu_page() {
			add_menu_page(
				__( 'Webflow Pages', WEBFLOW_PAGES_TEXT_DOMAIN ),
				__( 'Webflow Pages', WEBFLOW_PAGES_TEXT_DOMAIN ),
				'manage_options',
				'webflow-settings',
				"",
				'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz4KPCEtLSBHZW5lcmF0b3I6IEFkb2JlIElsbHVzdHJhdG9yIDIzLjAuNCwgU1ZHIEV4cG9ydCBQbHVnLUluIC4gU1ZHIFZlcnNpb246IDYuMDAgQnVpbGQgMCkgIC0tPgo8c3ZnIHZlcnNpb249IjEuMSIgaWQ9IkxheWVyXzEiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiIHg9IjBweCIgeT0iMHB4IgoJIHZpZXdCb3g9IjAgMCAxNiAxNiIgc3R5bGU9ImVuYWJsZS1iYWNrZ3JvdW5kOm5ldyAwIDAgMTYgMTY7IiB4bWw6c3BhY2U9InByZXNlcnZlIj4KPHN0eWxlIHR5cGU9InRleHQvY3NzIj4KCS5zdDB7ZmlsbDojRkZGRkZGO30KPC9zdHlsZT4KPHBhdGggY2xhc3M9InN0MCIgZD0iTTExLjYsNS44YzAsMC0xLjIsMy44LTEuMyw0LjFjMC0wLjMtMC45LTcuMi0wLjktNy4yYy0yLjEsMC0zLjIsMS41LTMuOCwzYzAsMC0xLjUsMy44LTEuNiw0LjIKCWMwLTAuMy0wLjItNC4xLTAuMi00LjFDMy43LDQsMS45LDIuOCwwLjUsMi44bDEuNywxMC40YzIuMiwwLDMuMy0xLjUsNC0zYzAsMCwxLjMtMy40LDEuNC0zLjVjMCwwLjEsMC45LDYuNiwwLjksNi42CgljMi4yLDAsMy40LTEuNCw0LTIuOWwzLTcuNUMxMy4zLDIuOCwxMi4yLDQuMywxMS42LDUuOHoiLz4KPC9zdmc+Cg==',
				6
			);

			add_submenu_page(
				'webflow-settings',
				__( 'Welcome', WEBFLOW_PAGES_TEXT_DOMAIN ),
				__( 'Welcome', WEBFLOW_PAGES_TEXT_DOMAIN ),
				'manage_options',
				'webflow-settings',
				array($this, 'display_welcome_page')
			);

			add_submenu_page(
				'webflow-settings',
				__( 'Webflow Pages Settings', WEBFLOW_PAGES_TEXT_DOMAIN ),
				__( 'Settings', WEBFLOW_PAGES_TEXT_DOMAIN ),
				'manage_options',
				'webflow-pages-settings',
				array($this, 'display_settings_page')
			);
		}

		/**
		 *
		 * Displays main page
		 *
		 */
		function display_welcome_page() {
			include_once __DIR__ . '/views/admin-welcome-page.php';
		}

		/**
		 * Displays settings page
		 */
		function display_settings_page() {
			include_once __DIR__ . '/views/admin-settings-page.php';
		}

		/**
		 * Adds Admin scripts and styles
		 *
		 * @param $hook
		 */
		function enqueue_admin_scripts($hook) {
			if( "webflow-pages_page_webflow-pages-settings" === $hook ) {
                // enques scripts and styles of the Svelte app
                wp_enqueue_script( 'webflow-dashboard',  WEBFLOW_PAGES_PLUGIN_DIRECTORY_URL . '/externals/dashboard/public/bundle.js' , array(), WEBFLOW_PAGES_PLUGIN_VERSION , true);
                wp_enqueue_style( 'webflow-dashboard',  WEBFLOW_PAGES_PLUGIN_DIRECTORY_URL . '/externals/dashboard/public/bundle.css' , array(), WEBFLOW_PAGES_PLUGIN_VERSION );

                // sets data that is necessary for the frontend
                wp_localize_script("webflow-dashboard", "_wfAjaxData", Webflow_API::get_instance()->get_ajax_data());
			}

            if( "toplevel_page_webflow-settings" === $hook ) {

                wp_enqueue_style( 'webflow-pages-toplevel',  WEBFLOW_PAGES_PLUGIN_DIRECTORY_URL . '/includes/admin/views/assets/style.css' , array(), WEBFLOW_PAGES_PLUGIN_VERSION );

            }
		}



	}

}