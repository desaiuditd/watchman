<?php
/**
 * Created by PhpStorm.
 * User: udit
 * Date: 12/10/14
 * Time: 1:25 AM
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WM_Settings' ) ) {

	class WM_Settings {

		static $page_slug = 'watchman';

		function __construct() {
			add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
			add_action( 'admin_init', array( $this, 'settings_init' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts_styles' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_menu_css' ) );
		}

		function enqueue_scripts_styles() {
			wp_enqueue_style( 'watchman-icons', trailingslashit( WM_URL ) . 'assets/css/watchman-icons.css', array(), WM_VERSION, 'all' );
		}

		function admin_menu_css() {
			$page_slug = self::$page_slug;
			$css = "
				#toplevel_page_{$page_slug} .wp-menu-image:before {
					font-family: 'watchman' !important;
					content: 'a' !important;
				}
				#toplevel_page_{$page_slug} .wp-menu-image {
					background-repeat: no-repeat;
				}
				body.{$page_slug} #wpbody-content .wrap h2:nth-child(1):before {
					font-family: 'watchman' !important;
					content: 'a';
					padding: 0 8px 0 0;
				}
			";
			wp_add_inline_style( 'wp-admin', $css );
		}

		function add_admin_menu() {
			add_menu_page( __( 'Watchman', WM_TEXT_DOMAIN ), __( 'Watchman', WM_TEXT_DOMAIN ), 'manage_options', self::$page_slug, array( $this, 'render_settings_page' ), 'div', '3.1234' );
		}

		function settings_init() {
			register_setting( 'watchman_group', 'watchman_settings' );

			add_settings_section( 'wm_revision_limit_section', __( 'Revision Limits', WM_TEXT_DOMAIN ), array( $this, 'revision_limit_section_callback' ), 'watchman_group' );
		}

		function revision_limit_section_callback() {
			_e( 'This section lets you control revision limits on your post types.', WM_TEXT_DOMAIN );
		}

		function render_settings_page() { ?>
			<style>
				.wm-icon {
					margin-top: -8px;
					margin-right: 5px;
					float: left;
				}

				.wm-icon:before {
					font-size: 35px;
				}

				.wm-heading {
					margin-top: 30px;
				}
			</style>
			<div class="wrap">

				<h1 class="wm-heading"><i class="wm-icon icon-watchman"></i><?php _e( 'Watchman' ); ?></h1>

				<form action='options.php' method='post'>

					<?php
					settings_fields( 'watchman_group' );
					do_settings_sections( 'watchman_group' );
					submit_button();
					?>

				</form>
			</div>
		<?php }

	}

}
