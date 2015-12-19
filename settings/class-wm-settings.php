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

		static $revision_limit_key = 'wm_revision_limit_';

		function __construct() {
			add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
			add_action( 'admin_init', array( $this, 'settings_init' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts_styles' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_menu_css' ) );
			add_action( 'admin_notices', array( $this, 'display_admin_notices' ) );

			add_filter( 'plugin_action_links_' . WM_BASE_PATH, array( $this, 'plugin_actions' ), 10, 4 );
		}

		function plugin_actions( $actions, $plugin_file, $plugin_data, $context ) {

			$actions['settings'] = '<a href="' . admin_url( 'admin.php?page=' . self::$page_slug ) . '">' . __( 'Settings', WM_TEXT_DOMAIN ) . '</a>';

			return $actions;
		}

		function show_message( $message, $type = 'info' ) {
			?>
			<div class="<?php echo $type; ?>"><?php echo $message; ?></div>
			<?php
		}

		function display_admin_notices() {
			// check for our settings page - need this in conditional further down
			global $current_screen;
			if ( 'toplevel_page_'.self::$page_slug != $current_screen->id ) {
				return;
			}

			$wm_settings_pg = strpos( $_GET['page'], self::$page_slug );
			// collect setting errors/notices: //http://codex.wordpress.org/Function_Reference/get_settings_errors
			$set_errors = get_settings_errors();

			//display admin message only for the admin to see, only on our settings page and only when setting errors/notices are returned!
			if ( current_user_can( 'manage_options' ) && false !== $wm_settings_pg && ! empty( $set_errors ) ) {

				// have our settings succesfully been updated?
				if ( 'settings_updated' === $set_errors[0]['code'] && isset( $_GET['settings-updated'] ) ) {

					$this->show_message( '<p>' . $set_errors[0]['message'] . '</p>', 'updated' );

					// have errors been found?
				} else {
					// there maybe more than one so run a foreach loop.
					foreach ( $set_errors as $set_error ) {
						// set the title attribute to match the error "setting title" - need this in js file
						$this->show_message( '<p>' . $set_error['message'] . '</p>', 'error' );
					}
				}
			}
		}

		function enqueue_scripts_styles() {
			wp_enqueue_style( 'watchman-icons', trailingslashit( WM_URL ) . 'ui/css/watchman-icons.css', array(), WM_VERSION, 'all' );
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

				.wm-icon {
					margin-top: -8px;
					margin-right: 5px;
					float: left;
				}
				.wm-icon:before {
					font-size: 35px;
				}
				h2.wm-heading {
					line-height: 22px;
				}
			";
			wp_add_inline_style( 'wp-admin', $css );
		}

		function add_admin_menu() {
			add_menu_page( __( 'Watchman', WM_TEXT_DOMAIN ), __( 'Watchman', WM_TEXT_DOMAIN ), 'manage_options', self::$page_slug, array( $this, 'render_settings_page' ), 'div', '3.1234' );
		}

		function settings_init() {

			add_settings_section( self::$revision_limit_key . 'section', __( 'Revision Limits', WM_TEXT_DOMAIN ), array( $this, 'revision_limit_section_callback' ), 'watchman_group' );

			$post_types = get_post_types( array(), 'objects' );
			foreach ( $post_types as $pt ) {
				$does_support = post_type_supports( $pt->name, 'revisions' );
				if ( $does_support ) {
					add_settings_field( self::$revision_limit_key . $pt->name, $pt->labels->name, array( $this, 'revision_limit_callback' ), 'watchman_group', self::$revision_limit_key . 'section', array( 'post_type' => $pt->name ) );
					register_setting( 'watchman_group', self::$revision_limit_key . $pt->name );
					add_filter( 'sanitize_option_' . self::$revision_limit_key . $pt->name, array( $this, 'sanitize_revision_limits' ), 10, 2 );
				}
			}
		}

		function sanitize_revision_limits( $value, $option ) {

			$post_type = str_replace( self::$revision_limit_key, '', $option );
			$post_type_obj = get_post_type_object( $post_type );
			$labels = get_post_type_labels( $post_type_obj );

			if ( ! empty( $value ) && ! is_numeric( $value ) ) {
				add_settings_error( $option, 'watchman_errors', sprintf( __( 'You need to fill numeric value for %s revision limit.', WM_TEXT_DOMAIN ), '<strong>' . $labels->name . '</strong>' ) );
			} else if ( '' !== $value ) {
				$value = absint( $value );
			}

			return $value;
		}

		function revision_limit_section_callback() {
			?>
			<p class="description">
				<?php _e( 'This section lets you control revision limits on your post types. Leave blank in case of unlimited revisions.', WM_TEXT_DOMAIN ); ?><br />
				<?php _e( 'If any post type is registered but missing from the following list, then it means that it does not support revisions.', WM_TEXT_DOMAIN ); ?><br />
				<?php _e( 'Please make sure the post type you want to monitor declares revision support in registration.', WM_TEXT_DOMAIN ); ?>
			</p>
			<?php
		}

		function revision_limit_callback( $args ) {
			?>
			<input type="text" name="<?php echo self::$revision_limit_key . $args['post_type']; ?>" id="<?php echo self::$revision_limit_key . $args['post_type']; ?>" value="<?php echo get_option( self::$revision_limit_key . $args['post_type'] ); ?>" />
			<?php
		}

		function render_settings_page() {
			?>
			<div class="wrap">

				<h2 class="wm-heading"><i class="wm-icon icon-watchman"></i><?php _e( 'Watchman', WM_TEXT_DOMAIN ); ?></h2>

				<form action='options.php' method='post'>

					<?php
					settings_fields( 'watchman_group' );
					do_settings_sections( 'watchman_group' );
					submit_button();
					?>

				</form>
			</div>
			<?php
		}
	}

}
