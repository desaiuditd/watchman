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

		function __construct() {
			add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
			add_action( 'admin_init', array( $this, 'settings_init' ) );
		}

		function add_admin_menu() {
			add_menu_page( __( 'Watchman', WM_TEXT_DOMAIN ), __( 'Watchman', WM_TEXT_DOMAIN ), 'manage_options', 'watchman', array( $this, 'render_settings_page' ), 'dashicons-businessman' );
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
				.wm-icon:before {
					font-size: 30px;
					width: 25px;
					height: 25px;
					margin-right: 10px;
					margin-top: -7px;
				}

				.wm-heading {
					margin-top: 30px;
				}
			</style>
			<div class="wrap">

				<h1 class="wm-heading"><i class="wm-icon dashicons-before dashicons-businessman"></i><?php _e( 'Watchman' ); ?></h1>

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
