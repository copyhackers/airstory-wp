<?php
/**
 * User settings for connecting to Airstory.
 *
 * @package Airstory
 */

namespace Airstory\Settings;

use Airstory\Connection as Connection;
use Airstory\Credentials as Credentials;
use Airstory\Settings as Settings;
use Airstory\Tools as Tools;
use WP_Error;

/**
 * Retrieve a value from the _airstory_data user meta key.
 *
 * @param int    $user_id The user ID to update.
 * @param string $key     The key within the _airstory_data array.
 * @param mixed  $default Optional. The value to return if no value is found in the array. Default
 *                        is null.
 * @return mixed The value assigned to $key, or $default if a corresponding value wasn't found.
 */
function get_user_data( $user_id, $key, $default = null ) {
	$key  = sanitize_title( $key );
	$data = (array) get_user_option( '_airstory_data', $user_id );

	return isset( $data[ $key ] ) ? $data[ $key ] : $default;
}

/**
 * Set a value for a key in the _airstory_data user meta key.
 *
 * @param int    $user_id The user ID to update.
 * @param string $key     The key within the _airstory_data array.
 * @param mixed  $value   Optional. The value to assign to $key. Default is null.
 * @return bool True if the _airstory_data user meta was updated, false otherwise.
 */
function set_user_data( $user_id, $key, $value = null ) {
	$key          = sanitize_title( $key );
	$data         = (array) get_user_option( '_airstory_data', $user_id );
	$data[ $key ] = $value;

	return update_user_option( $user_id, '_airstory_data', $data, true );
}

/**
 * Display a notification to the user following plugin activation, guiding them to the settings page.
 */
function show_user_connection_notice() {
	$user_id = wp_get_current_user()->ID;

	// Ensure we only ever show this to each user once.
	if ( get_user_data( $user_id, 'welcome_message_seen', false ) ) {
		return;
	}

	$message = sprintf(
		/* Translators: %1s$ is the WordPress user edit link. */
		__( 'To get started, please connect WordPress to your Airstory account <a href="%1$s#airstory">on your profile page</a>.', 'airstory' ),
		esc_url( get_edit_user_link() )
	);

	// phpcs:disable Generic.WhiteSpace.ScopeIndent
?>

	<div class="notice notice-success is-dismissible">
		<p><strong><?php esc_html_e( 'Welcome to Airstory!', 'airstory' ); ?></strong></p>
		<p><?php echo wp_kses_post( $message ); ?></p>
		<button type="button" class="notice-dismiss">
			<span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice', 'airstory' ); ?></span>
		</button>
	</div>

<?php
	// phpcs:enable Generic.WhiteSpace.ScopeIndent

	// Indicate that the user has seen the welcome message.
	set_user_data( $user_id, 'welcome_message_seen', true );
}
add_action( 'admin_notices', __NAMESPACE__ . '\show_user_connection_notice' );
add_action( 'network_admin_notices', __NAMESPACE__ . '\show_user_connection_notice' );

/**
 * Render the "Airstory" settings section on the user profile page.
 *
 * @param WP_User $user The current user object.
 */
function render_profile_settings( $user ) {
	$compatibility = Tools\check_compatibility();

	if ( ! $compatibility['compatible'] ) {
		return;
	}

	$profile = Settings\get_user_data( $user->ID, 'profile', array() );
	$blogs   = get_available_blogs( $user->ID );

	// phpcs:disable Generic.WhiteSpace.ScopeIndent
?>

	<h2 id="airstory"><?php esc_html_e( 'Airstory Configuration', 'airstory' ); ?></h2>
	<table class="form-table">
		<tbody>
			<tr>
				<th scope="row"><label for="airstory-token"><?php esc_html_e( 'User Token', 'airstory' ); ?></label></th>
				<td>
					<?php if ( ! empty( $profile['email'] ) ) : ?>

						<input name="airstory-disconnect" type="submit" class="button" value="<?php esc_attr_e( 'Disconnect from Airstory', 'airstory' ); ?>" />
						<p class="description">
							<?php
								echo wp_kses_post(
									sprintf(
										/* Translators: %1$s is the user's Airstory email address. */
										__( 'Currently authenticated as <strong>%1$s</strong>', 'airstory' ),
										$profile['email']
									)
								);
							?>
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
	// phpcs:enable Generic.WhiteSpace.ScopeIndent

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

	// No token is set, but we aren't disconnecting.
	if ( empty( $_POST['airstory-token'] ) && ! isset( $_POST['airstory-disconnect'] ) ) {
		return false;
	}

	// The user is attempting to disconnect.
	if ( isset( $_POST['airstory-disconnect'] ) ) {
		// Clear out all connections.
		Connection\set_connected_blogs( $user_id, array() );

		/**
		 * A user has disconnected their account from Airstory.
		 *
		 * @param int $user_id The ID of the user that just disconnected.
		 */
		do_action( 'airstory_user_disconnect', $user_id );

		Credentials\clear_token( $user_id );

		return delete_user_option( $user_id, '_airstory_data', true );
	}

	// The user is setting their token.
	if ( ! empty( $_POST['airstory-token'] ) ) {
		$token_set = Credentials\set_token( $user_id, sanitize_text_field( $_POST['airstory-token'] ) );

		if ( is_wp_error( $token_set ) ) {
			add_action( 'user_profile_update_errors', __NAMESPACE__ . '\profile_error_save_token' );

			return $token_set;
		}
	}

	// If the user has access to more than one site, update them accordingly.
	if ( is_multisite() && ! empty( $_POST['airstory-sites'] ) ) {
		$site_ids = array_map( 'absint', (array) $_POST['airstory-sites'] );
		Connection\set_connected_blogs( $user_id, $site_ids );
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
 * Add an error message to the user profile screen if the token could not be saved.
 *
 * @param WP_Error $errors WP_Error object, passed by reference.
 */
function profile_error_save_token( $errors ) {
	$errors->add(
		'airstory-save-token',
		__( 'WordPress was unable to establish a connection with Airstory using the token provided', 'airstory' )
	);
}


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
