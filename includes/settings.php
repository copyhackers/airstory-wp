<?php
/**
 * User settings for connecting to Airstory.
 *
 * @package Airstory
 */

namespace Airstory\Settings;

use Airstory\Connection as Connection;
use Airstory\Credentials as Credentials;

/**
 * Render the "Airstory" settings section on the user profile page.
 *
 * @param WP_User $user The current user object.
 */
function render_profile_settings( $user ) {
	$profile = get_user_option( '_airstory_profile', $user->ID );
	$blogs   = get_available_blogs( $user->ID );
?>

	<h2><?php esc_html_e( 'Airstory Configuration', 'airstory' ); ?></h2>
	<table class="form-table">
		<tbody>
			<tr>
				<th scope="row"><label for="airstory-token"><?php esc_html_e( 'User Token', 'airstory' ); ?></label></th>
				<td>
					<?php if ( ! empty( $profile['email'] ) ) : ?>

						<input name="airstory-disconnect" type="submit" class="button" value="<?php esc_attr_e( 'Disconnect from Airstory', '' ); ?>" />
						<p class="description">
							<?php echo wp_kses_post( sprintf( __( 'Currently authenticated as <strong>%s</strong>', 'airstory' ), $profile['email'] ) ); ?>
						</p>

					<?php else : ?>

						<input name="airstory-token" id="airstory-token" type="password" class="regular-text" />
						<p class="description"><?php echo wp_kses_post( __( 'You can retrieve your user token from <a href="https://app.airstory.co/projects?overlay=account" target="_blank">your Airstory account settings</a>.', 'airstory' ) ); ?></p>

					<?php endif; ?>
				</td>
			</tr>

			<?php if ( 1 < count( $blogs ) ) : ?>

				<tr>
					<th scope="row"><?php echo esc_html( _x( 'Connected Sites', 'label for list of WordPress blogs (multisite only)', 'airstory' ) ); ?></label></th>
					<td>
						<fieldset>
							<legend class="screen-reader-text"><?php esc_html_e( 'Sites connected to Airstory', 'airstory' ); ?></legend>
							<?php foreach ( $blogs as $blog ) : ?>

								<p>
									<label>
										<input name="airstory-sites[]" type="checkbox" value="<?php echo esc_attr( $blog['id'] ); ?>" <?php checked( true, $blog['connected'] ); ?> />
										<?php echo esc_html( $blog['title'] ); ?>
									</label>
								</p>

							<?php endforeach; ?>
					</td>
				</tr>

			<?php endif; ?>
		</tbody>
	</table>

<?php
	wp_nonce_field( 'airstory-profile', '_airstory_nonce' );
}
add_action( 'show_user_profile', __NAMESPACE__ . '\render_profile_settings' );

/**
 * Save the user's profile settings.
 *
 * @param int $user_id The user ID.
 * @return bool Whether or not the user meta was updated successfully.
 */
function save_profile_settings( $user_id ) {
	if ( ! isset( $_POST['_airstory_nonce'] )
		|| ! wp_verify_nonce( $_POST['_airstory_nonce'], 'airstory-profile' )
		|| ! current_user_can( 'edit_user', $user_id )
	) {
		return false;
	}

	$token = get_user_option( '_airstory_token', $user_id );

	// The user is disconnecting.
	if ( $token && isset( $_POST['airstory-disconnect'] ) ) {

		// Clear out all connections.
		Connection\set_connected_sites( $user_id, array() );

		/**
		 * A user has disconnected their account from Airstory.
		 *
		 * @param int $user_id The ID of the user that just disconnected.
		 */
		do_action( 'airstory_user_disconnect', $user_id );

		delete_user_option( $user_id, '_airstory_profile', true );

		return Credentials\clear_token( $user_id );

	} elseif ( ! empty( $_POST['airstory-token'] ) ) {
		Credentials\set_token( $user_id, sanitize_text_field( $_POST['airstory-token'] ) );
	}

	if ( is_multisite() ) {
		$site_ids = array_map( 'absint', (array) $_POST['airstory-sites'] );
		Connection\set_connected_sites( $user_id, $site_ids );
	}

	/**
	 * A user has connected their account to Airstory.
	 *
	 * @param int $user_id The ID of the user that just connected.
	 */
	do_action( 'airstory_user_connect', $user_id );

	return true;
}
add_action( 'personal_options_update', __NAMESPACE__ . '\save_profile_settings' );

/**
 * Generate a list of blogs that $user_id is a member of *and* can publish to.
 *
 * This is only used in WordPress Multisite, but will allow users to manage their connections with
 * each site in a network from their profile page.
 *
 * @param int $user_id The WordPress user ID.
 * @return array {
 *   An array of blogs the user is able to publish to. This will be an array of arrays.
 *
 *   @var int    $id    The WordPress blog ID.
 *   @var string $title The WordPress blog name.
 *   @var bool   $connected Whether or not there's an active connection with the blog.
 * }
 */
function get_available_blogs( $user_id ) {
	if ( ! is_multisite() ) {
		return array();
	}

	$all_blogs = get_blogs_of_user( $user_id );
	$blogs     = array();

	/*
	 * Go through each blog this user is a member of and determine if the user:
	 * - Can at least create (if not publish) new posts, making them at least a Contributor.
	 * - Already has an active Airstory connection for the blog.
	 *
	 * This is a rather intensive process, and may take a while for users that are members of many
	 * blogs in the network.
	 */
	foreach ( $all_blogs as $blog_id => $blog ) {
		switch_to_blog( $blog_id );

		// Don't bother checking tokens if the user can't publish.
		if ( user_can( $user_id, 'edit_posts' ) ) {

			$blogs[] = array(
				'id'        => (int) $blog_id,
				'title'     => $blog->blogname,
				'connected' => Connection\has_connection( $user_id ),
			);
		}

		restore_current_blog();
	}

	return $blogs;
}
