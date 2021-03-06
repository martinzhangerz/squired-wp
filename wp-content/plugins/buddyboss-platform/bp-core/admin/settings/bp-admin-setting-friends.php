<?php
/**
 * Add admin Connections settings page in Dashboard->BuddyBoss->Settings
 *
 * @package BuddyBoss\Core
 *
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Main Connection settings class.
 *
 * @since BuddyBoss 1.0.0
 */
class BP_Admin_Setting_Friends extends BP_Admin_Setting_tab {

	public function initialize() {
		$this->tab_label = __( 'Connections', 'buddyboss' );
		$this->tab_name  = 'bp-friends';
		$this->tab_order = 60;
	}

	public function is_active() {
		return bp_is_active( 'friends' );
	}

	public function register_fields() {
		$this->add_section( 'bp_friends', __( 'Connection Settings', 'buddyboss' ) );

		if ( bp_is_active( 'messages' ) ) {
			$this->add_field( 'bp-force-friendship-to-message', __( 'Messaging', 'buddyboss' ), array( $this, 'bp_admin_setting_callback_force_friendship_to_message' ), array( $this, 'bp_admin_sanitize_callback_force_friendship_to_message' ) );
		}

		// Connection Settings tutorial
		$this->add_field( 'bp-connection-settings-tutorial', '', array( $this, 'bp_connection_settings_tutorial' ) );
	}

	/**
	 * Force users to be connected before sending a message to each other.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function bp_admin_setting_callback_force_friendship_to_message() {
		?>
		<input id="bp-force-friendship-to-message" name="bp-force-friendship-to-message" type="checkbox" value="1" <?php checked( bp_force_friendship_to_message( false ) ); ?> />
		<label for="bp-force-friendship-to-message"><?php _e( 'Require users to be connected before they can message each other', 'buddyboss' ); ?></label>
		<?php
	}

	/**
	 * Sanitization for bp-force-friendship-to-message setting.
	 *
	 * In the UI, a checkbox asks whether you'd like to *enable* forceing users to be friends for messaging. For
	 * legacy reasons, the option that we store is 1 if these friends or messaging is *disabled*. So we use this
	 * function to flip the boolean before saving the intval.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param bool $value Whether or not to sanitize.
	 * @return bool
	 */
	public function bp_admin_sanitize_callback_force_friendship_to_message( $value = false ) {
		return $value ? 1 : 0;
	}

	/**
	 * Link to Connection Settings tutorial
	 *
	 * @since BuddyBoss 1.0.0
	 */
	function bp_connection_settings_tutorial() {
		?>

		<p>
			<a class="button" href="<?php echo bp_core_help_docs_link( 'components/connections/connection-settings.md' ); ?>"><?php _e( 'View Tutorial', 'buddyboss' ); ?></a>
		</p>

		<?php
	}
}

return new BP_Admin_Setting_Friends();
