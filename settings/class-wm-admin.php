<?php
/**
 * Created by PhpStorm.
 * User: udit
 * Date: 18/12/15
 * Time: 18:33
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WM_Admin' ) ) {

	/**
	 * Class WM_Admin
	 *
	 * Handles all admin side functionality
	 *
	 * @since 0.7
	 */
	class WM_Admin {

		static $wm_error_code_key = 'wm-error-code';

		static $wm_error_codes = array(
			'1' => 'You need to fill numeric value for revision limit.',
		);

		static $wm_revision_limit_meta_key = '_wm_revision_limit';

		/**
		 * @since 0.7
		 */
		function __construct() {

			add_action( 'add_meta_boxes', array( $this, 'add_revision_meta_box' ), 10, 2 );
			add_action( 'save_post', array( $this, 'save_revision_meta_box' ), 10, 3 );
			add_action( 'admin_notices', array( $this, 'show_admin_notices' ), 10 );
			add_filter( 'removable_query_args', array( $this, 'remove_error_query_arg' ), 10, 1 );

		}

		function add_revision_meta_box( $post_type, $post ) {

			add_meta_box( 'revision-meta-box', __( 'Revision', WM_TEXT_DOMAIN ), array( $this, 'revision_meta_box_markup' ), $post_type, 'side', 'high', null );

		}

		function revision_meta_box_markup( $post ) {

			wp_nonce_field( basename( __FILE__ ), 'revision-meta-box-nonce' );

			?>
			<p class="description">
				<?php _e( 'You can set revision limit on individual post from here.', WM_TEXT_DOMAIN ); ?>
				<?php printf( __( 'This will take precedence over post type limit from %s.', WM_TEXT_DOMAIN ), '<a target="_blank" href="' . add_query_arg( 'page', WM_Settings::$page_slug, admin_url( 'admin.php' ) ) . '">' . __( 'global settings', WM_TEXT_DOMAIN ) . '</a>' );; ?><br />
				<?php _e( 'Leave this blank to use global settings.', WM_TEXT_DOMAIN ); ?><br />
				<?php _e( 'Put negative number to set unlimited revisions for this post.', WM_TEXT_DOMAIN ); ?>
			</p>
			<label for="revision-limit"><?php _e( 'Revision Limit', WM_TEXT_DOMAIN ); ?></label>
			<input name="revision-limit" type="text" value="<?php echo get_post_meta( $post->ID, self::$wm_revision_limit_meta_key, true ); ?>" />
			<?php

		}

		function remove_error_query_arg( $args ) {
			$args[] = self::$wm_error_code_key;
			return $args;
		}

		function show_admin_notices() {
			if ( isset( $_GET[ self::$wm_error_code_key ] ) ) {
				?>
				<div class="error">
					<p><?php echo self::$wm_error_codes[ $_GET[ self::$wm_error_code_key ] ]; ?></p>
				</div>
				<?php
			}
		}

		function save_revision_meta_box( $post_id, $post, $update ) {

			if ( ! isset( $_POST['revision-meta-box-nonce'] ) || ! wp_verify_nonce( $_POST['revision-meta-box-nonce'], basename( __FILE__ ) ) ) {
				return $post_id;
			}

			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return $post_id;
			}

			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return $post_id;
			}

			$revision_limit = '';
			if ( isset( $_POST['revision-limit'] ) ) {
				$revision_limit = $_POST['revision-limit'];
			}

			if ( ! empty( $revision_limit ) && ! is_numeric( $revision_limit ) ) {
				add_filter( 'redirect_post_location', function( $location ) {
					return add_query_arg( self::$wm_error_code_key, '1', $location );
				} );
				return $post_id;
			}

			update_post_meta( $post_id, self::$wm_revision_limit_meta_key, $revision_limit );

		}
	}

}
