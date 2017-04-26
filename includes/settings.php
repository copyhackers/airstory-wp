<?php
/**
 * User settings for connecting to Airstory.
 *
 * @package Airstory
 */

namespace Airstory\Settings;

/**
 * Render the "Airstory" settings section on the user profile page.
 *
 * @param WP_User $user The current user object.
 */
function render_profile_settings( $user ) {
	$token = get_user_meta( $user->ID, '_airstory_token', true );
	$email = 'test@example.com'; // @todo Get the stored user email.
?>

	<h2><?php esc_html_e( 'Airstory Configuration', 'airstory' ); ?></h2>
	<table class="form-table">
		<tbody>
			<tr>
				<th><label for="airstory-token"><?php esc_html_e( 'User Token', 'airstory' ); ?></label></th>
				<td>
					<?php if ( ! empty( $token ) ) : ?>

						<input name="airstory-disconnect" type="submit" class="button" value="<?php esc_attr_e( 'Disconnect from Airstory', '' ); ?>" />
						<p class="description">
							<?php echo wp_kses_post( sprintf( __( 'Currently authenticated as <strong>%s</strong>', 'airstory' ), $email ) ); ?>
						</p>

					<?php else : ?>

						<input name="airstory-token" id="airstory-token" type="password" class="regular-text" />
						<p class="description"><?php echo wp_kses_post( __( 'You can retrieve your user token from <a href="https://app.airstory.co/projects?overlay=account" target="_blank">your Airstory account settings</a>.', 'airstory' ) ); ?></p>

					<?php endif; ?>
				</td>
			</tr>
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
 *
 * @todo If this ships without encrypting the token, punch me.
 */
function save_profile_settings( $user_id ) {
	if ( ! isset( $_POST['_airstory_nonce'] )
		|| ! wp_verify_nonce( $_POST['_airstory_nonce'], 'airstory-profile' )
		|| ! current_user_can( 'edit_user', $user_id )
	) {
		return false;
	}

	$token = get_user_meta( $user_id, '_airstory_token', true );

	// The user is disconnecting.
	if ( $token && isset( $_POST['airstory-disconnect'] ) ) {

		/**
		 * A user has disconnected their account from Airstory.
		 *
		 * @param int $user_id The ID of the user that just disconnected.
		 */
		do_action( 'airstory_user_disconnect', $user_id );

		return delete_user_meta( $user_id, '_airstory_token', $token );

	} elseif ( empty( $_POST['airstory-token'] ) ) {

		// No disconnection, but no token value, either.
		return false;
	}

	// Store the user meta. Casting, since update_user_meta() can return an int or boolean.
	$new_token = sanitize_text_field( $_POST['airstory-token'] );
	$result    = (bool) update_user_meta( $user_id, '_airstory_token', $new_token, $token );

	// @todo Seriously, this is only a proof-of-concept.
	trigger_error( 'This is not production-ready code, and I would rather not get punched.', E_USER_WARNING );

	/**
	 * A user has connected their account to Airstory.
	 *
	 * @param int $user_id The ID of the user that just connected.
	 */
	do_action( 'airstory_user_connect', $user_id );

	return (bool) $result;
}
add_action( 'personal_options_update', __NAMESPACE__ . '\save_profile_settings' );
