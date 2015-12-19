<?php
/**
 * Plugin Name: Watchman
 * Plugin URI: http://blog.incognitech.in/watchman
 * Description: A WordPress plugin to track revisions of your posts, pages and custom posts
 * Version: 0.7.1
 * Author: desaiuditd
 * Author URI: http://blog.incognitech.in
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Watchman' ) ) {

	class Watchman {

		/** Singleton *************************************************************/

		/**
		 * @var Watchman The one true Watchman
		 * @since 0.1
		 */
		private static $instance;

		/**
		 * Main Watchman Instance
		 *
		 * Insures that only one instance of Watchman exists in memory at any one
		 * time. Also prevents needing to define globals all over the place.
		 *
		 * @since 0.1
		 * @static
		 * @static var array $instance
		 * @return Watchman The one true Watchman
		 */
		public static function instance() {
			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Watchman ) ) {
				self::$instance = new Watchman;
				self::$instance->setup_constants();
				self::$instance->includes();
				self::$instance->load_textdomain();
				self::$instance->hooks();
			}
			return self::$instance;
		}

		/**
		 * Throw error on object clone
		 *
		 * The whole idea of the singleton design pattern is that there is a single
		 * object therefore, we don't want the object to be cloned.
		 *
		 * @since 0.1
		 * @access protected
		 * @return void
		 */
		public function __clone() {
			// Cloning instances of the class is forbidden
			_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', WM_TEXT_DOMAIN ), '1.6' );
		}

		/**
		 * Disable unserializing of the class
		 *
		 * @since 0.1
		 * @access protected
		 * @return void
		 */
		public function __wakeup() {
			// Unserializing instances of the class is forbidden
			_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', WM_TEXT_DOMAIN ), '1.6' );
		}

		/**
		 * Setup plugin constants
		 *
		 * @access private
		 * @since 0.1
		 * @return void
		 */
		private function setup_constants() {

			// Defines WM_VERSION if it does not exits.
			if ( ! defined( 'WM_VERSION' ) ) {
				define( 'WM_VERSION', '0.7.1' );
			}

			// Defines WM_TEXT_DOMAIN if it does not exits.
			if ( ! defined( 'WM_TEXT_DOMAIN' ) ) {
				define( 'WM_TEXT_DOMAIN', 'watchman' );
			}

			// Defines WM_PATH if it does not exits.
			if ( ! defined( 'WM_PATH' ) ) {
				define( 'WM_PATH', plugin_dir_path( __FILE__ ) );
			}

			// Defines WM_URL if it does not exits.
			if ( ! defined( 'WM_URL' ) ) {
				define( 'WM_URL', plugin_dir_url( __FILE__ ) );
			}

			// Defines WM_BASE_PATH if it does not exits.
			if ( ! defined( 'WM_BASE_PATH' ) ) {
				define( 'WM_BASE_PATH', plugin_basename( __FILE__ ) );
			}
		}

		/**
		 * Include required files
		 *
		 * @access private
		 * @since 0.1
		 * @return void
		 */
		private function includes() {
			include_once trailingslashit( WM_PATH ) . 'lib/class-wm-autoload.php';
			new WM_Autoload( trailingslashit( WM_PATH ) . 'revision/' );
			new WM_Autoload( trailingslashit( WM_PATH ) . 'settings/' );

			new WM_Settings();
			new WM_Admin();
			new WM_Revision();
		}

		/**
		 * Loads the plugin language files
		 *
		 * @access public
		 * @since 0.1
		 * @return void
		 */
		public function load_textdomain() {
			// Set filter for plugin's languages directory
			$lang_dir = dirname( plugin_basename( WM_PATH ) ) . '/languages/';
			$lang_dir = apply_filters( 'wm_languages_directory', $lang_dir );

			// Traditional WordPress plugin locale filter
			$locale        = apply_filters( 'plugin_locale',  get_locale(), WM_TEXT_DOMAIN );
			$mofile        = sprintf( '%1$s-%2$s.mo', WM_TEXT_DOMAIN, $locale );

			// Setup paths to current locale file
			$mofile_local  = $lang_dir . $mofile;
			$mofile_global = WP_LANG_DIR . '/' . WM_TEXT_DOMAIN . '/' . $mofile;

			if ( file_exists( $mofile_global ) ) {
				// Look in global /wp-content/languages/wp_ti folder
				load_textdomain( WM_TEXT_DOMAIN, $mofile_global );
			} elseif ( file_exists( $mofile_local ) ) {
				// Look in local /wp-content/plugins/wp-time-is/languages/ folder
				load_textdomain( WM_TEXT_DOMAIN, $mofile_local );
			} else {
				// Load the default language files
				load_plugin_textdomain( WM_TEXT_DOMAIN, false, $lang_dir );
			}
		}

		function hooks() {

		}
	}

}

/**
 * The main function responsible for returning the one true Watchman
 * Instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $watchman = watchman(); ?>
 *
 * @since 1.4
 * @return object The one true Watchman Instance
 */
function watchman() {
	return Watchman::instance();
}

// Get Watchman Running
watchman();

/**
 * Look Maa! A Singleton Class Design Pattern! I'm sure you would be <3 ing design patterns.
 */

