<?php
/**
 * Created by PhpStorm.
 * User: udit
 * Date: 12/9/14
 * Time: 10:43 AM
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WM_Revision' ) ) {

	/**
	 * Class WM_Revision
	 *
	 * Handles all the functionality to track custom fields
	 *
	 * @since 0.4
	 */
	class WM_Revision {

		/**
		 * @since 0.4
		 */
		function __construct() {

			/**
			 * This hook filters the number of revisions to keep for a specific post.
			 */
			add_filter( 'wp_revisions_to_keep', array( $this, 'filter_revisions_to_keep' ), 999, 2 );

			/**
			 * This hooks gets fired once a revision is stored in WP_Post table in DB.
			 *
			 * This gives us $revision_id. So we can make use of that and store our stuff into post meta for that particular revision.
			 * E.g., Taxonomy diff, meta diff., featured image diff, etc.
			 *
			 */
			add_action( '_wp_put_post_revision', array( $this, 'post_revision_process' ), 10, 1 );

			/**
			 * Filter whether the post has changed since the last revision.
			 *
			 * By default a revision is saved only if one of the revisioned fields has changed.
			 * This filter can override that so a revision is saved even if nothing has changed.
			 *
			 * We will take care of our own fields and pass on the flag.
			 */
			add_filter( 'wp_save_post_revision_post_has_changed', array( $this, 'check_for_changes' ), 10, 3 );

			/**
			 * We may have to call this dynamically within a for loop. depending upon how many custom fields that we are supporting.
			 */
			foreach ( array_keys( $this->get_custom_revision_fields() ) as $field ) {
				add_filter( '_wp_post_revision_field_'.$field, array( $this, 'revision_field_content' ), 10, 4 );
			}

			/**
			 * This adds custom diff ui for custom revision fields
			 */
			add_filter( 'wp_get_revision_ui_diff', array( $this, 'revision_ui_diff' ), 10, 3 );
		}

		/**
		 * @param $num
		 * @param $post
		 *
		 * @return int
		 * @since 0.1
		 */
		function filter_revisions_to_keep( $num, $post ) {

			// Check individual Post Limit
			$revision_limit = get_post_meta( $post->ID, WM_Admin::$wm_revision_limit_meta_key, true );

			if ( '' !== $revision_limit ) {

				if ( ! is_numeric( $revision_limit ) ) {
					$num = - 1;
				} else {
					$num = intval( $revision_limit );
				}
			} else {

				$post_type = get_post_type( $post );

				$revision_limit = get_option( WM_Settings::$revision_limit_key . $post_type, false );

				if ( '' === $revision_limit || ! is_numeric( $revision_limit ) ) {
					$num = - 1;
				} else {
					$num = intval( $revision_limit );
				}
			}

			return $num;
		}

		/**
		 * @return array $revision_fields
		 * @since 0.5
		 */
		function get_custom_revision_fields() {
			$revision_fields = array(
				'post_author' => array(
					'label' => __( 'Post Author', WM_TEXT_DOMAIN ),
					'meta_key' => '_wm_post_author',
					'meta_value' => function( $post ) {
						$author = new WP_User( $post->post_author );
						return $author->display_name . ' (' . $post->post_author . ')';
					},
				),
				'post_status' => array(
					'label' => __( 'Post Status', WM_TEXT_DOMAIN ),
					'meta_key' => '_wm_post_status',
					'meta_value' => function( $post ) {
						$post_status = get_post_status_object( $post->post_status );
						return $post_status->label;
					},
				),
				'post_date' => array(
					'label' => __( 'Post Date', WM_TEXT_DOMAIN ),
					'meta_key' => '_wm_post_date',
					'meta_value' => function( $post ) {
						$datef = 'M j, Y @ H:i';
						return date_i18n( $datef, strtotime( $post->post_date ) );
					},
				),
			);
			return $revision_fields;
		}

		/**
		 * @param $revision_id
		 * @since 0.4
		 */
		function post_revision_process( $revision_id ) {

			$revision = get_post( $revision_id );
			$post = get_post( $revision->post_parent );

			foreach ( $this->get_custom_revision_fields() as $field => $fieldmeta ) {
				update_post_meta( $post->ID, $fieldmeta['meta_key'] . '_' . $revision_id , call_user_func( $fieldmeta['meta_value'], $post ) );
			}

		}

		/**
		 * @param $post_has_changed
		 * @param $last_revision
		 * @param $post
		 *
		 * @return mixed
		 * @since 0.4
		 */
		function check_for_changes( $post_has_changed, $last_revision, $post ) {

			foreach ( $this->get_custom_revision_fields() as $field => $fieldmeta ) {

				$post_value = normalize_whitespace( call_user_func( $fieldmeta['meta_value'], $post ) );
				$revision_value = normalize_whitespace( apply_filters( "_wp_post_revision_field_$field", $last_revision->$field, $field, $last_revision, 'from' ) );

				if ( $post_value != $revision_value ) {

					$post_has_changed = true;
					break;

				}
			}

			return $post_has_changed;
		}

		/**
		 * Contextually filter a post revision field.
		 *
		 * The dynamic portion of the hook name, $field, corresponds to each of the post
		 * fields of the revision object being iterated over in a foreach statement.
		 *
		 * @param string  $value    The current revision field to compare to or from.
		 * @param string  $field    The current revision field.
		 * @param WP_Post $post     The revision post object to compare to or from.
		 * @param string  $context  The context of whether the current revision is the old or the new one. Values are 'to' or 'from'.
		 *
		 * @return string $value
		 * @since 0.4
	     */
		function revision_field_content( $value, $field, $post, $context ) {

			$revision_fields = $this->get_custom_revision_fields();

			if ( array_key_exists( $field, $revision_fields ) ) {

				$value = get_post_meta( $post->post_parent, $revision_fields[ $field ]['meta_key'] . '_' . $post->ID, true );

			}

			return $value;
		}

		/**
		 * Filter the fields displayed in the post revision diff UI.
		 *
		 * @since 4.1.0
		 *
		 * @param array   $return       Revision UI fields. Each item is an array of id, name and diff.
		 * @param WP_Post $compare_from The revision post to compare from.
		 * @param WP_Post $compare_to   The revision post to compare to.
		 *
		 * @return array $return
		 * @since 0.5
		 */
		function revision_ui_diff( $return, $compare_from, $compare_to ) {

			foreach ( $this->get_custom_revision_fields() as $field => $fieldmeta ) {
				/**
				 * Contextually filter a post revision field.
				 *
				 * The dynamic portion of the hook name, `$field`, corresponds to each of the post
				 * fields of the revision object being iterated over in a foreach statement.
				 *
				 * @since 3.6.0
				 *
				 * @param string  $compare_from->$field The current revision field to compare to or from.
				 * @param string  $field                The current revision field.
				 * @param WP_Post $compare_from         The revision post object to compare to or from.
				 * @param string  null                  The context of whether the current revision is the old
				 *                                      or the new one. Values are 'to' or 'from'.
				 */
				$content_from = $compare_from ? apply_filters( "_wp_post_revision_field_$field", $compare_from->$field, $field, $compare_from, 'from' ) : '';

				/** This filter is documented in wp-admin/includes/revision.php */
				$content_to = apply_filters( "_wp_post_revision_field_$field", $compare_to->$field, $field, $compare_to, 'to' );

				$args = array(
					'show_split_view' => true,
				);

				/**
				 * Filter revisions text diff options.
				 *
				 * Filter the options passed to {@see wp_text_diff()} when viewing a post revision.
				 *
				 * @since 4.1.0
				 *
				 * @param array   $args {
				 *     Associative array of options to pass to {@see wp_text_diff()}.
				 *
				 *     @type bool $show_split_view True for split view (two columns), false for
				 *                                 un-split view (single column). Default true.
				 * }
				 * @param string  $field        The current revision field.
				 * @param WP_Post $compare_from The revision post to compare from.
				 * @param WP_Post $compare_to   The revision post to compare to.
				 */
				$args = apply_filters( 'revision_text_diff_options', $args, $field, $compare_from, $compare_to );

				$diff = wp_text_diff( $content_from, $content_to, $args );

				if ( $diff ) {
					$return[] = array(
						'id' => $field,
						'name' => $fieldmeta['label'],
						'diff' => $diff,
					);
				}
			}

			return $return;
		}
	}

}
