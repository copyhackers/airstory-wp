<?php
/**
 * Core functionality for Airstory.
 *
 * @package Airstory.
 */

namespace Airstory\Core;

use Airstory;
use Airstory\Tools as Tools;
use WP_Query;

/**
 * Retrieve an existing post based on Airstory project and document IDs.
 *
 * Since we're tracking the original project and document IDs, we can prevent multiple copies of
 * the same post from being made by cross-referencing these values with post meta.
 *
 * @param string $project_id  The Airstory project UUID.
 * @param string $document_id The Airstory document UUID.
 * @return int Either the ID of a NON-published post with those IDs or 0 if no such post exists.
 */
function get_current_draft( $project_id, $document_id ) {
	$defaults = array(
		'post_type'              => 'post',
		'post_status'            => array( 'draft', 'pending' ),
		'no_found_rows'          => true,
		'update_term_meta_cache' => false,
		'fields'                 => 'ids',
		'posts_per_page'         => 1,
		'orderby'                => 'date',
		'order'                  => 'ASC',
		'meta_query'             => array(
			array(
				'key'   => '_airstory_project_id',
				'value' => $project_id,
			),
			array(
				'key'   => '_airstory_document_id',
				'value' => $document_id,
			),
		),
	);

	/**
	 * Filters the WP_Query arguments used to determine if a post has already been imported (as a
	 * draft) into WordPress.
	 *
	 * @param array $query_args WP_Query arguments to find a matching draft.
	 * @param array $defaults   Default WP_Query arguments, for reference; the $query_args array will
	 *                          be merged with the defaults via wp_parse_args().
	 */
	$query_args = (array) apply_filters( 'airstory_get_current_draft', array(), $defaults );
	$query      = new WP_Query( wp_parse_args( $query_args, $defaults ) );

	return empty( $query->posts ) ? 0 : (int) current( $query->posts );
}

/**
 * Deactivate the plugin and notify the user if the plugin doesn't meet requirements.
 *
 * @global $pagenow
 */
function check_for_missing_requirements() {
	global $pagenow;

	// Only show this warning on the plugin page.
	if ( 'plugins.php' !== $pagenow ) {
		return;
	}

	// No worries if everything checks out.
	$compatibility = Tools\check_compatibility();

	if ( $compatibility['compatible'] ) {
		return;
	}

	// Display a notice, informing the user why the plugin was deactivated.
	add_action( 'admin_notices', __NAMESPACE__ . '\notify_user_of_missing_requirements' );
}
add_action( 'admin_init', __NAMESPACE__ . '\check_for_missing_requirements' );

/**
 * Notify the user of missing plugin requirements and direct them to more detailed information.
 */
function notify_user_of_missing_requirements() {
	// phpcs:disable Generic.WhiteSpace.ScopeIndent
?>

	<div class="notice notice-warning">
		<p><?php esc_html_e( 'The Airstory plugin is missing one or more of its dependencies, so it\'s not yet available to users on this site.', 'airstory' ); ?></p>
		<p>
			<?php
				echo wp_kses_post(
					sprintf(
						/* Translators: %1$s is the URL of the Tools > Airstory page. */
						__( 'For more information, <a href="%1$s" target="_blank">please see the Airstory Tools page</a>.', 'airstory' ),
						esc_url( admin_url( 'tools.php?page=airstory' ) )
					)
				);
			?>
		</p>
	</div>

<?php
	// phpcs:enable Generic.WhiteSpace.ScopeIndent
}

/**
 * Given an Airstory project and document UUIDs, call out to the Airstory API and assemble a
 * WordPress post.
 *
 * @param Airstory\API $api         An instance of the Airstory API class.
 * @param string       $project_id  The Airstory project UUID.
 * @param string       $document_id The Airstory document UUID.
 * @param int          $author_id   Optional. The user ID to attribute the post to. Default is the
 *                                  current user.
 * @return int|WP_Error The ID of the newly-created post or a WP_Error object if anything went
 *                      wrong during the creation of the post.
 */
function create_document( Airstory\API $api, $project_id, $document_id, $author_id = null ) {
	$document = $api->get_document( $project_id, $document_id );

	// Something went wrong getting metadata about the document.
	if ( is_wp_error( $document ) ) {
		return $document;
	}

	// Begin assembling the post.
	$post = array(
		'post_title'   => sanitize_text_field( $document->title ),
		'post_author'  => (int) $author_id,
		'post_status'  => 'draft',
		'post_type'    => 'post',
		'post_content' => '',
	);

	// Next, retrieve the post content.
	$contents = $api->get_document_content( $project_id, $document_id );

	// Unable to retrieve the rendered content.
	if ( is_wp_error( $contents ) ) {
		return $contents;
	}

	/**
	 * Filters the Airstory document content before inserting it into the wp_insert_post() array.
	 *
	 * @param string $document The compiled, HTML response from Airstory.
	 */
	$contents             = apply_filters( 'airstory_before_insert_content', $contents );
	$post['post_content'] = wp_kses_post( $contents );

	/**
	 * Filter arguments for new posts from Airstory before they're inserted into the database.
	 *
	 * @see wp_insert_post()
	 *
	 * @param array $post An array of arguments for wp_insert_post().
	 */
	$post = apply_filters( 'airstory_before_insert_post', $post );

	// Finally, insert the post.
	$post_id = wp_insert_post( (array) $post, true );

	if ( is_wp_error( $post_id ) ) {
		return $post_id;
	}

	// Store the Airstory project and document IDs in post meta.
	add_post_meta( $post_id, '_airstory_project_id', sanitize_text_field( $project_id ), true );
	add_post_meta( $post_id, '_airstory_document_id', sanitize_text_field( $document_id ), true );

	/**
	 * Fires after an Airstory post has been successfully inserted into WordPress.
	 *
	 * @param int $post_id The ID of the newly-created post.
	 */
	do_action( 'airstory_import_post', $post_id );

	return $post_id;
}

/**
 * Given an Airstory project and document UUIDs, call out to the Airstory API and assemble a
 * WordPress post.
 *
 * @param Airstory\API $api         An instance of the Airstory API class.
 * @param string       $project_id  The Airstory project UUID.
 * @param string       $document_id The Airstory document UUID.
 * @param int          $post_id     The ID of the post that already exists within WordPress.
 * @return int|WP_Error The ID of the newly-created post or a WP_Error object if anything went
 *                      wrong during the updating of the post.
 */
function update_document( Airstory\API $api, $project_id, $document_id, $post_id ) {
	$contents = $api->get_document_content( $project_id, $document_id );

	// Unable to retrieve the rendered content.
	if ( is_wp_error( $contents ) ) {
		return $contents;
	}

	/** This filter is defined in includes/core.php. */
	$contents = apply_filters( 'airstory_before_insert_content', $contents );
	$post     = array(
		'ID'           => $post_id,
		'post_content' => wp_kses_post( $contents ),
	);

	/** This filter is defined in includes/core.php. */
	$post = apply_filters( 'airstory_before_insert_post', $post );

	// Finally, insert the post.
	$post_id = wp_update_post( $post );

	if ( is_wp_error( $post_id ) ) {
		return $post_id;
	}

	/**
	 * Fires after an Airstory post has been successfully updated within WordPress.
	 *
	 * @param int $post_id The ID of the updated post.
	 */
	do_action( 'airstory_update_post', $post_id );

	return $post_id;
}
