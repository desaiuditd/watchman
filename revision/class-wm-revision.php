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

	class WM_Revision {

		function __construct() {

			/**
			 * This hooks gets fired once a revision is stored in WP_Post table in DB.
			 *
			 * This gives us $revision_id. So we can make use of that and store our stuff into post meta for that particular revision.
			 * E.g., Taxonomy diff, meta diff., featured image diff, etc.
			 *
			 */
			add_action( '_wp_put_post_revision', array( $this, 'post_revision_process' ) );

			/**
			 * Filter whether the post has changed since the last revision.
			 *
			 * By default a revision is saved only if one of the revisioned fields has changed.
			 * This filter can override that so a revision is saved even if nothing has changed.
			 *
			 * We will take care of our own fields and pass on the flag.
			 */
			add_filter( 'wp_save_post_revision_check_for_changes', array( $this, 'check_for_changes' ), 10, 3 );

			/**
			 * We may have to call this dynamically within a for loop. depending upon how many custom fields that we are supporting.
			 *
			 * TODO Check for this.
			 */
			$field = '';
			add_filter( '_wp_post_revision_field_'.$field, array( $this, 'revision_field_content' ) );
		}

		/**
		 * @param $revision_id
		 */
		function post_revision_process( $revision_id ) {

		}

		/**
		 * @param $check_flag
		 * @param $last_revision
		 * @param $post
		 *
		 * @return mixed
		 */
		function check_for_changes( $check_flag, $last_revision, $post ) {
			return $check_flag;
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
	     */
		function revision_field_content( $value, $field, $post, $context ) {
			return $value;
		}

	}

}
