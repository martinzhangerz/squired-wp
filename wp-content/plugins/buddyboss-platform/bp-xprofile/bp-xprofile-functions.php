<?php
/**
 * BuddyPress XProfile Filters.
 *
 * Business functions are where all the magic happens in BuddyPress. They will
 * handle the actual saving or manipulation of information. Usually they will
 * hand off to a database class for data access, then return
 * true or false on success or failure.
 *
 * @package BuddyBoss\XProfile
 * @since BuddyPress 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/*** Field Group Management **************************************************/

/**
 * Fetch a set of field groups, populated with fields and field data.
 *
 * Procedural wrapper for BP_XProfile_Group::get() method.
 *
 * @since BuddyPress 2.1.0
 *
 * @param array $args See {@link BP_XProfile_Group::get()} for description of arguments.
 * @return array $groups
 */
function bp_xprofile_get_groups( $args = array() ) {

	/**
	 * For repeaters, automatically set the parameter value
	 * to determine if we should display only the template fields
	 * or only the clone fields
	 */

	if ( ! isset( $args['repeater_show_main_fields_only'] ) ) {
		$repeater_show_main_fields_only = true;

		// If on a user profile
		if ( 'profile' == bp_current_component() ) {
			$repeater_show_main_fields_only = false;
		}

		$args['repeater_show_main_fields_only'] = apply_filters( 'bp_xprofile_get_groups_repeater_show_main_fields_only', $repeater_show_main_fields_only );
	}

	$groups = BP_XProfile_Group::get( $args );

	/**
	 * Filters a set of field groups, populated with fields and field data.
	 *
	 * @since BuddyPress 2.1.0
	 *
	 * @param array $groups Array of field groups and field data.
	 * @param array $args   Array of arguments used to query for groups.
	 */
	return apply_filters( 'bp_xprofile_get_groups', $groups, $args );
}

/**
 * Insert a new profile field group.
 *
 * @since BuddyPress 1.0.0
 *
 * @param array|string $args {
 *    Array of arguments for field group insertion.
 *
 *    @type int|bool    $field_group_id ID of the field group to insert into.
 *    @type string|bool $name           Name of the group.
 *    @type string      $description    Field group description.
 *    @type bool        $can_delete     Whether or not the field group can be deleted.
 * }
 * @return boolean
 */
function xprofile_insert_field_group( $args = '' ) {

	// Parse the arguments.
	$r = bp_parse_args(
		$args,
		array(
			'field_group_id' => false,
			'name'           => false,
			'description'    => '',
			'can_delete'     => true,
		),
		'xprofile_insert_field_group'
	);

	// Bail if no group name.
	if ( empty( $r['name'] ) ) {
		return false;
	}

	// Create new field group object, maybe using an existing ID.
	$field_group              = new BP_XProfile_Group( $r['field_group_id'] );
	$field_group->name        = $r['name'];
	$field_group->description = $r['description'];
	$field_group->can_delete  = $r['can_delete'];

	return $field_group->save();
}

/**
 * Get a specific profile field group.
 *
 * @since BuddyPress 1.0.0
 *
 * @param int $field_group_id Field group ID to fetch.
 * @return false|BP_XProfile_Group
 */
function xprofile_get_field_group( $field_group_id = 0 ) {

	// Try to get a specific field group by ID.
	$field_group = new BP_XProfile_Group( $field_group_id );

	// Bail if group was not found.
	if ( empty( $field_group->id ) ) {
		return false;
	}

	// Return field group.
	return $field_group;
}

/**
 * Delete a specific profile field group.
 *
 * @since BuddyPress 1.0.0
 *
 * @param int $field_group_id Field group ID to delete.
 * @return boolean
 */
function xprofile_delete_field_group( $field_group_id = 0 ) {

	// Try to get a specific field group by ID.
	$field_group = xprofile_get_field_group( $field_group_id );

	// Bail if group was not found.
	if ( false === $field_group ) {
		return false;
	}

	// Return the results of trying to delete the field group.
	return $field_group->delete();
}

/**
 * Update the position of a specific profile field group.
 *
 * @since BuddyPress 1.0.0
 *
 * @param int $field_group_id Field group ID to update.
 * @param int $position       Field group position to update to.
 * @return boolean
 */
function xprofile_update_field_group_position( $field_group_id = 0, $position = 0 ) {
	return BP_XProfile_Group::update_position( $field_group_id, $position );
}

/*** Field Management *********************************************************/

/**
 * Get details of all xprofile field types.
 *
 * @since BuddyPress 2.0.0
 *
 * @return array Key/value pairs (field type => class name).
 */
function bp_xprofile_get_field_types() {
	$fields = array(
		'checkbox'       => 'BP_XProfile_Field_Type_Checkbox',
		'datebox'        => 'BP_XProfile_Field_Type_Datebox',
		'multiselectbox' => 'BP_XProfile_Field_Type_Multiselectbox',
		'number'         => 'BP_XProfile_Field_Type_Number',
		'url'            => 'BP_XProfile_Field_Type_URL',
		'radio'          => 'BP_XProfile_Field_Type_Radiobutton',
		'selectbox'      => 'BP_XProfile_Field_Type_Selectbox',
		'textarea'       => 'BP_XProfile_Field_Type_Textarea',
		'textbox'        => 'BP_XProfile_Field_Type_Textbox',
		'telephone'      => 'BP_XProfile_Field_Type_Telephone',
		'gender'         => 'BP_XProfile_Field_Type_Gender',
		'socialnetworks' => 'BP_XProfile_Field_Type_Social_Networks',
	);

	if ( function_exists( 'bp_member_type_enable_disable' ) && true === bp_member_type_enable_disable() ) {
		$fields['membertypes'] = 'BP_XProfile_Field_Type_Member_Types';
	}

	/**
	 * Filters the list of all xprofile field types.
	 *
	 * If you've added a custom field type in a plugin, register it with this filter.
	 *
	 * @since BuddyPress 2.0.0
	 *
	 * @param array $fields Array of field type/class name pairings.
	 */
	return apply_filters( 'bp_xprofile_get_field_types', $fields );
}

/**
 * Creates the specified field type object; used for validation and templating.
 *
 * @since BuddyPress 2.0.0
 *
 * @param string $type Type of profile field to create. See {@link bp_xprofile_get_field_types()} for default core values.
 * @return object $value If field type unknown, returns BP_XProfile_Field_Type_Textarea.
 *                       Otherwise returns an instance of the relevant child class of BP_XProfile_Field_Type.
 */
function bp_xprofile_create_field_type( $type ) {

	$field = bp_xprofile_get_field_types();
	$class = isset( $field[ $type ] ) ? $field[ $type ] : '';

	/**
	 * To handle (missing) field types, fallback to a placeholder field object if a type is unknown.
	 */
	if ( $class && class_exists( $class ) ) {
		return new $class();
	} else {
		return new BP_XProfile_Field_Type_Placeholder();
	}
}

/**
 * Insert or update an xprofile field.
 *
 * @since BuddyPress 1.1.0
 *
 * @param array|string $args {
 *     Array of arguments.
 *     @type int    $field_id          Optional. Pass the ID of an existing field to edit that field.
 *     @type int    $field_group_id    ID of the associated field group.
 *     @type int    $parent_id         Optional. ID of the parent field.
 *     @type string $type              Field type. Checked against a field_types whitelist.
 *     @type string $name              Name of the new field.
 *     @type string $description       Optional. Descriptive text for the field.
 *     @type bool   $is_required       Optional. Whether users must provide a value for the field. Default: false.
 *     @type bool   $can_delete        Optional. Whether admins can delete this field in the Dashboard interface.
 *                                     Generally this is false only for the Name field, which is required throughout BP.
 *                                     Default: true.
 *     @type string $order_by          Optional. For field types that support options (such as 'radio'), this flag
 *                                     determines whether the sort order of the options will be 'default'
 *                                     (order created) or 'custom'.
 *     @type bool   $is_default_option Optional. For the 'option' field type, setting this value to true means that
 *                                     it'll be the default value for the parent field when the user has not yet
 *                                     overridden. Default: true.
 *     @type int    $option_order      Optional. For the 'option' field type, this determines the order in which the
 *                                     options appear.
 * }
 * @return bool|int False on failure, ID of new field on success.
 */
function xprofile_insert_field( $args = '' ) {

	$r = wp_parse_args(
		$args,
		array(
			'field_id'          => null,
			'field_group_id'    => null,
			'parent_id'         => null,
			'type'              => '',
			'name'              => '',
			'description'       => '',
			'is_required'       => false,
			'can_delete'        => true,
			'order_by'          => '',
			'is_default_option' => false,
			'option_order'      => null,
			'field_order'       => null,
		)
	);

	// Field_group_id is required.
	if ( empty( $r['field_group_id'] ) ) {
		return false;
	}

	// Check this is a non-empty, valid field type.
	if ( ! in_array( $r['type'], (array) buddypress()->profile->field_types ) ) {
		return false;
	}

	// Instantiate a new field object.
	if ( ! empty( $r['field_id'] ) ) {
		$field = xprofile_get_field( $r['field_id'] );
	} else {
		$field = new BP_XProfile_Field();
	}

	$field->group_id = $r['field_group_id'];
	$field->type     = $r['type'];

	// The 'name' field cannot be empty.
	if ( ! empty( $r['name'] ) ) {
		$field->name = $r['name'];
	}

	$field->description       = $r['description'];
	$field->order_by          = $r['order_by'];
	$field->parent_id         = (int) $r['parent_id'];
	$field->field_order       = (int) $r['field_order'];
	$field->option_order      = (int) $r['option_order'];
	$field->is_required       = (bool) $r['is_required'];
	$field->can_delete        = (bool) $r['can_delete'];
	$field->is_default_option = (bool) $r['is_default_option'];

	return $field->save();
}

/**
 * Get a profile field object.
 *
 * @since BuddyPress 1.1.0
 * @since BuddyPress 2.8.0 Added `$user_id` and `$get_data` parameters.
 *
 * @param int|object $field    ID of the field or object representing field data.
 * @param int|null   $user_id  Optional. ID of the user associated with the field.
 *                             Ignored if `$get_data` is false. If `$get_data` is
 *                             true, but no `$user_id` is provided, defaults to
 *                             logged-in user ID.
 * @param bool       $get_data Whether to fetch data for the specified `$user_id`.
 * @return BP_XProfile_Field|null Field object if found, otherwise null.
 */
function xprofile_get_field( $field, $user_id = null, $get_data = true ) {
	if ( $field instanceof BP_XProfile_Field ) {
		$_field = $field;
	} elseif ( is_object( $field ) ) {
		$_field = new BP_XProfile_Field();
		$_field->fill_data( $field );
	} else {
		$_field = BP_XProfile_Field::get_instance( $field, $user_id, $get_data );
	}

	if ( ! $_field ) {
		return null;
	}

	return $_field;
}

/**
 * Delete a profile field object.
 *
 * @since BuddyPress 1.1.0
 *
 * @param int|object $field_id ID of the field or object representing field data.
 * @return bool Whether or not the field was deleted.
 */
function xprofile_delete_field( $field_id ) {
	$field = new BP_XProfile_Field( $field_id );
	return $field->delete();
}

/*** Field Data Management *****************************************************/


/**
 * Fetches profile data for a specific field for the user.
 *
 * When the field value is serialized, this function unserializes and filters
 * each item in the array.
 *
 * @since BuddyPress 1.0.0
 *
 * @param mixed  $field        The ID of the field, or the $name of the field.
 * @param int    $user_id      The ID of the user.
 * @param string $multi_format How should array data be returned? 'comma' if you want a
 *                             comma-separated string; 'array' if you want an array.
 * @return mixed The profile field data.
 */
function xprofile_get_field_data( $field, $user_id = 0, $multi_format = 'array' ) {

	if ( empty( $user_id ) ) {
		$user_id = bp_displayed_user_id();
	}

	if ( empty( $user_id ) ) {
		return false;
	}

	if ( is_numeric( $field ) ) {
		$field_id = $field;
	} else {
		$field_id = xprofile_get_field_id_from_name( $field );
	}

	if ( empty( $field_id ) ) {
		return false;
	}

	$values = maybe_unserialize( BP_XProfile_ProfileData::get_value_byid( $field_id, $user_id ) );

	if ( is_array( $values ) ) {
		$data = array();
		foreach ( (array) $values as $value ) {

			/**
			 * Filters the field data value for a specific field for the user.
			 *
			 * @since BuddyPress 1.0.0
			 *
			 * @param string $value    Value saved for the field.
			 * @param int    $field_id ID of the field being displayed.
			 * @param int    $user_id  ID of the user being displayed.
			 */
			$data[] = apply_filters( 'xprofile_get_field_data', $value, $field_id, $user_id );
		}

		if ( 'comma' == $multi_format ) {
			$data = implode( ', ', $data );
		}
	} else {
		/** This filter is documented in bp-xprofile/bp-xprofile-functions.php */
		$data = apply_filters( 'xprofile_get_field_data', $values, $field_id, $user_id );
	}

	return $data;
}

/**
 * A simple function to set profile data for a specific field for a specific user.
 *
 * @since BuddyPress 1.0.0
 *
 * @param int|string $field       The ID of the field, or the $name of the field.
 * @param int        $user_id     The ID of the user.
 * @param mixed      $value       The value for the field you want to set for the user.
 * @param bool       $is_required Whether or not the field is required.
 * @return bool True on success, false on failure.
 */
function xprofile_set_field_data( $field, $user_id, $value, $is_required = false ) {

	if ( is_numeric( $field ) ) {
		$field_id = $field;
	} else {
		$field_id = xprofile_get_field_id_from_name( $field );
	}

	if ( empty( $field_id ) ) {
		return false;
	}

	$field          = xprofile_get_field( $field_id );
	$field_type     = BP_XProfile_Field::get_type( $field_id );
	$field_type_obj = bp_xprofile_create_field_type( $field_type );

	/**
	 * Filter the raw submitted profile field value.
	 *
	 * Use this filter to modify the values submitted by users before
	 * doing field-type-specific validation.
	 *
	 * @since BuddyPress 2.1.0
	 *
	 * @param mixed                  $value          Value passed to xprofile_set_field_data().
	 * @param BP_XProfile_Field      $field          Field object.
	 * @param BP_XProfile_Field_Type $field_type_obj Field type object.
	 */
	$value = apply_filters( 'bp_xprofile_set_field_data_pre_validate', $value, $field, $field_type_obj );

	// Special-case support for integer 0 for the number field type.
	if ( $is_required && ! is_integer( $value ) && $value !== '0' && ( empty( $value ) || ! is_array( $value ) && ! strlen( trim( $value ) ) ) ) {
		return false;
	}

	/**
	 * Certain types of fields (checkboxes, multiselects) may come through empty.
	 * Save as empty array so this isn't overwritten by the default on next edit.
	 *
	 * Special-case support for integer 0 for the number field type
	 */
	if ( empty( $value ) && ! is_integer( $value ) && $value !== '0' && $field_type_obj->accepts_null_value ) {
		$value = array();
	}

	// If the value is empty, then delete any field data that exists, unless the field is of a type
	// where null values are semantically meaningful.
	if ( empty( $value ) && ! is_integer( $value ) && $value !== '0' && ! $field_type_obj->accepts_null_value ) {
		xprofile_delete_field_data( $field_id, $user_id );
		return true;
	}

	// For certain fields, only certain parameters are acceptable, so add them to the whitelist.
	if ( $field_type_obj->supports_options ) {
		$field_type_obj->set_whitelist_values( wp_list_pluck( $field->get_children(), 'name' ) );
	}

	// Check the value is in an accepted format for this form field.
	if ( ! $field_type_obj->is_valid( $value ) ) {
		return false;
	}

	$field           = new BP_XProfile_ProfileData();
	$field->field_id = $field_id;
	$field->user_id  = $user_id;

	// Gets un/reserialized via xprofile_sanitize_data_value_before_save()
	$field->value = maybe_serialize( $value );

	return $field->save();
}

/**
 * Set the visibility level for this field.
 *
 * @since BuddyPress 1.6.0
 *
 * @param int    $field_id         The ID of the xprofile field.
 * @param int    $user_id          The ID of the user to whom the data belongs.
 * @param string $visibility_level What the visibity setting should be.
 * @return bool True on success
 */
function xprofile_set_field_visibility_level( $field_id = 0, $user_id = 0, $visibility_level = '' ) {
	if ( empty( $field_id ) || empty( $user_id ) || empty( $visibility_level ) ) {
		return false;
	}

	// Check against a whitelist.
	$allowed_values = bp_xprofile_get_visibility_levels();
	if ( ! array_key_exists( $visibility_level, $allowed_values ) ) {
		return false;
	}

	// Stored in an array in usermeta.
	$current_visibility_levels = bp_get_user_meta( $user_id, 'bp_xprofile_visibility_levels', true );

	if ( ! $current_visibility_levels ) {
		$current_visibility_levels = array();
	}

	$current_visibility_levels[ $field_id ] = $visibility_level;

	return bp_update_user_meta( $user_id, 'bp_xprofile_visibility_levels', $current_visibility_levels );
}

/**
 * Get the visibility level for a field.
 *
 * @since BuddyPress 2.0.0
 *
 * @param int $field_id The ID of the xprofile field.
 * @param int $user_id The ID of the user to whom the data belongs.
 * @return string
 */
function xprofile_get_field_visibility_level( $field_id = 0, $user_id = 0 ) {
	$current_level = '';

	if ( empty( $field_id ) || empty( $user_id ) ) {
		return $current_level;
	}

	$current_levels = bp_get_user_meta( $user_id, 'bp_xprofile_visibility_levels', true );
	$current_level  = isset( $current_levels[ $field_id ] ) ? $current_levels[ $field_id ] : '';

	// Use the user's stored level, unless custom visibility is disabled.
	$field = xprofile_get_field( $field_id );
	if ( isset( $field->allow_custom_visibility ) && 'disabled' === $field->allow_custom_visibility ) {
		$current_level = $field->default_visibility;
	}

	// If we're still empty, it means that overrides are permitted, but the
	// user has not provided a value. Use the default value.
	if ( empty( $current_level ) ) {
		$current_level = $field->default_visibility;
	}

	return $current_level;
}

/**
 * Delete XProfile field data.
 *
 * @since BuddyPress 1.1.0
 *
 * @param string $field   Field to delete.
 * @param int    $user_id User ID to delete field from.
 * @return bool Whether or not the field was deleted.
 */
function xprofile_delete_field_data( $field = '', $user_id = 0 ) {

	// Get the field ID.
	if ( is_numeric( $field ) ) {
		$field_id = (int) $field;
	} else {
		$field_id = xprofile_get_field_id_from_name( $field );
	}

	// Bail if field or user ID are empty.
	if ( empty( $field_id ) || empty( $user_id ) ) {
		return false;
	}

	// Get the profile field data to delete.
	$field = new BP_XProfile_ProfileData( $field_id, $user_id );

	// Delete the field data.
	return $field->delete();
}

/**
 * Check if field is a required field.
 *
 * @since BuddyPress 1.1.0
 *
 * @param int $field_id ID of the field to check for.
 * @return bool Whether or not field is required.
 */
function xprofile_check_is_required_field( $field_id ) {
	$field  = new BP_XProfile_Field( $field_id );
	$retval = false;

	if ( isset( $field->is_required ) ) {
		$retval = $field->is_required;
	}

	return (bool) $retval;
}

/**
 * Validate profile field.
 *
 * @since BuddyBoss 1.0.0
 */
function xprofile_validate_field( $field_id, $value, $UserId ) {
	return apply_filters( 'xprofile_validate_field', '', $field_id, $value, $UserId );
}

/**
 * Returns the ID for the field based on the field name.
 *
 * @since BuddyPress 1.0.0
 *
 * @param string $field_name The name of the field to get the ID for.
 * @return int|null $field_id on success, false on failure.
 */
function xprofile_get_field_id_from_name( $field_name ) {
	return BP_XProfile_Field::get_id_from_name( $field_name );
}

/**
 * Fetches a random piece of profile data for the user.
 *
 * @since BuddyPress 1.0.0
 *
 * @global BuddyPress $bp           The one true BuddyPress instance.
 * @global wpdb $wpdb WordPress database abstraction object.
 * @global object     $current_user WordPress global variable containing current logged in user information.
 *
 * @param int  $user_id          User ID of the user to get random data for.
 * @param bool $exclude_fullname Optional; whether or not to exclude the full name field as random data.
 *                               Defaults to true.
 * @return string|bool The fetched random data for the user, or false if no data or no match.
 */
function xprofile_get_random_profile_data( $user_id, $exclude_fullname = true ) {
	$field_data = BP_XProfile_ProfileData::get_random( $user_id, $exclude_fullname );

	if ( empty( $field_data ) ) {
		return false;
	}

	$field_data[0]->value = xprofile_format_profile_field( $field_data[0]->type, $field_data[0]->value );

	if ( empty( $field_data[0]->value ) ) {
		return false;
	}

	/**
	 * Filters a random piece of profile data for the user.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param array $field_data Array holding random profile data.
	 */
	return apply_filters( 'xprofile_get_random_profile_data', $field_data );
}

/**
 * Formats a profile field according to its type. [ TODO: Should really be moved to filters ]
 *
 * @since BuddyPress 1.0.0
 *
 * @param string $field_type  The type of field: datebox, selectbox, textbox etc.
 * @param string $field_value The actual value.
 * @return string|bool The formatted value, or false if value is empty.
 */
function xprofile_format_profile_field( $field_type, $field_value ) {

	if ( empty( $field_value ) ) {
		return false;
	}

	$field_value = bp_unserialize_profile_field( $field_value );

	if ( 'datebox' != $field_type ) {
		$content     = $field_value;
		$field_value = str_replace( ']]>', ']]&gt;', $content );
	}

	return xprofile_filter_format_field_value_by_type( stripslashes_deep( $field_value ), $field_type );
}

/**
 * Update the field position for a provided field.
 *
 * @since BuddyPress 1.1.0
 *
 * @param int $field_id       ID of the field to update.
 * @param int $position       Position to update the field to.
 * @param int $field_group_id Group ID for group the field is in.
 * @return bool
 */
function xprofile_update_field_position( $field_id, $position, $field_group_id ) {
	return BP_XProfile_Field::update_position( $field_id, $position, $field_group_id );
}

/**
 * Replace the displayed and logged-in users fullnames with the xprofile name, if required.
 *
 * The Members component uses the logged-in user's display_name to set the
 * value of buddypress()->loggedin_user->fullname. However, in cases where
 * profile sync is disabled, display_name may diverge from the xprofile
 * fullname field value, and the xprofile field should take precedence.
 *
 * Runs at bp_setup_globals:100 to ensure that all components have loaded their
 * globals before attempting any overrides.
 *
 * @since BuddyPress 2.0.0
 */
function xprofile_override_user_fullnames() {
	// If sync is enabled, the two names will match. No need to continue.
	if ( ! bp_disable_profile_sync() ) {
		return;
	}

	if ( bp_loggedin_user_id() ) {
		buddypress()->loggedin_user->fullname = bp_core_get_user_displayname( bp_loggedin_user_id() );
	}

	if ( bp_displayed_user_id() ) {
		buddypress()->displayed_user->fullname = bp_core_get_user_displayname( bp_displayed_user_id() );
	}
}
add_action( 'bp_setup_globals', 'xprofile_override_user_fullnames', 100 );

/**
 * Setup the avatar upload directory for a user.
 *
 * @since BuddyPress 1.0.0
 *
 * @package BuddyBoss Core
 *
 * @param string $directory The root directory name. Optional.
 * @param int    $user_id   The user ID. Optional.
 * @return array Array containing the path, URL, and other helpful settings.
 */
function xprofile_avatar_upload_dir( $directory = 'avatars', $user_id = 0 ) {

	// Use displayed user if no user ID was passed.
	if ( empty( $user_id ) ) {
		$user_id = bp_displayed_user_id();
	}

	// Failsafe against accidentally nooped $directory parameter.
	if ( empty( $directory ) ) {
		$directory = 'avatars';
	}

	$path      = bp_core_avatar_upload_path() . '/' . $directory . '/' . $user_id;
	$newbdir   = $path;
	$newurl    = bp_core_avatar_url() . '/' . $directory . '/' . $user_id;
	$newburl   = $newurl;
	$newsubdir = '/' . $directory . '/' . $user_id;

	/**
	 * Filters the avatar upload directory for a user.
	 *
	 * @since BuddyPress 1.1.0
	 *
	 * @param array $value Array containing the path, URL, and other helpful settings.
	 */
	return apply_filters(
		'xprofile_avatar_upload_dir',
		array(
			'path'    => $path,
			'url'     => $newurl,
			'subdir'  => $newsubdir,
			'basedir' => $newbdir,
			'baseurl' => $newburl,
			'error'   => false,
		)
	);
}

/**
 * When search_terms are passed to BP_User_Query, search against xprofile fields.
 *
 * @since BuddyPress 2.0.0
 *
 * @param array         $sql   Clauses in the user_id SQL query.
 * @param BP_User_Query $query User query object.
 * @return array
 */
function bp_xprofile_bp_user_query_search( $sql, BP_User_Query $query ) {
	global $wpdb;

	if ( empty( $query->query_vars['search_terms'] ) || empty( $sql['where']['search'] ) ) {
		return $sql;
	}

	$bp = buddypress();

	$search_terms_clean = bp_esc_like( wp_kses_normalize_entities( $query->query_vars['search_terms'] ) );

	if ( $query->query_vars['search_wildcard'] === 'left' ) {
		$search_terms_nospace = '%' . $search_terms_clean;
		$search_terms_space   = '%' . $search_terms_clean . ' %';
	} elseif ( $query->query_vars['search_wildcard'] === 'right' ) {
		$search_terms_nospace = $search_terms_clean . '%';
		$search_terms_space   = '% ' . $search_terms_clean . '%';
	} else {
		$search_terms_nospace = '%' . $search_terms_clean . '%';
		$search_terms_space   = '%' . $search_terms_clean . '%';
	}

	// Combine the core search (against wp_users) into a single OR clause
	// with the xprofile_data search.
	$matched_user_ids = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT user_id FROM {$bp->profile->table_name_data} WHERE value LIKE %s OR value LIKE %s",
			$search_terms_nospace,
			$search_terms_space
		)
	);

	// Checked profile fields based on privacy settings of particular user while searching
	if ( ! empty( $matched_user_ids ) ) {
		$matched_user_data = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$bp->profile->table_name_data} WHERE value LIKE %s OR value LIKE %s",
				$search_terms_nospace,
				$search_terms_space
			)
		);

		foreach ( $matched_user_data as $key => $user ) {
			$field_visibility = xprofile_get_field_visibility_level( $user->field_id, $user->user_id );
			if ( 'adminsonly' === $field_visibility && ! current_user_can( 'administrator' ) ) {
				if ( ( $key = array_search( $user->user_id, $matched_user_ids ) ) !== false ) {
					unset( $matched_user_ids[ $key ] );
				}
			}
			if ( 'friends' === $field_visibility && ! current_user_can( 'administrator' ) && false === friends_check_friendship( intval( $user->user_id ), bp_loggedin_user_id() ) ) {
				if ( ( $key = array_search( $user->user_id, $matched_user_ids ) ) !== false ) {
					unset( $matched_user_ids[ $key ] );
				}
			}
		}
	}

	if ( ! empty( $matched_user_ids ) ) {
		$search_core            = $sql['where']['search'];
		$search_combined        = " ( u.{$query->uid_name} IN (" . implode( ',', $matched_user_ids ) . ") OR {$search_core} )";
		$sql['where']['search'] = $search_combined;
	}

	return $sql;
}
add_action( 'bp_user_query_uid_clauses', 'bp_xprofile_bp_user_query_search', 10, 2 );

/**
 * Syncs Xprofile data to the standard built in WordPress profile data.
 *
 * @since BuddyPress 1.0.0
 *
 * @param int $user_id ID of the user to sync.
 * @return bool
 */
function xprofile_sync_wp_profile( $user_id = 0, $field_id = null ) {

	// Bail if profile syncing is disabled.
	if ( bp_disable_profile_sync() ) {
		return true;
	}

	if ( empty( $user_id ) ) {
		$user_id = bp_loggedin_user_id();
	}

	if ( empty( $user_id ) ) {
		return false;
	}

	// Get First, Last and Nickname field id from DB.
	$firstname_id = bp_xprofile_firstname_field_id();
	$lastname_id  = bp_xprofile_lastname_field_id();
	$nickname_id  = bp_xprofile_nickname_field_id();

	if ( ! $field_id || $field_id == $firstname_id ) {
		$firstname = xprofile_get_field_data( bp_xprofile_firstname_field_id(), $user_id );
		bp_update_user_meta( $user_id, 'first_name', $firstname );
	}

	if ( ! $field_id || $field_id == $lastname_id ) {
		$lastname = xprofile_get_field_data( bp_xprofile_lastname_field_id(), $user_id );
		bp_update_user_meta( $user_id, 'last_name', $lastname );
	}

	if ( ! $field_id || $field_id == $nickname_id ) {
		$nickname = xprofile_get_field_data( bp_xprofile_nickname_field_id(), $user_id );
		bp_update_user_meta( $user_id, 'nickname', $nickname );
	}

	bp_xprofile_update_display_name( $user_id );
}
add_action( 'bp_core_signup_user', 'xprofile_sync_wp_profile' );
add_action( 'bp_core_activated_user', 'xprofile_sync_wp_profile' );

/**
 * Syncs the standard built in WordPress profile data to XProfile.
 *
 * @since BuddyPress 1.2.4
 *
 * @param object $errors Array of errors. Passed by reference.
 * @param bool   $update Whether or not being upated.
 * @param object $user   User object whose profile is being synced. Passed by reference.
 */
function xprofile_sync_bp_profile( &$errors, $update, &$user ) {

	// Bail if profile syncing is disabled.
	if ( bp_disable_profile_sync() || ! $update || $errors->get_error_codes() ) {
		return;
	}

	if ( isset( $user->first_name ) ) {
		xprofile_set_field_data( bp_xprofile_firstname_field_id(), $user->ID, $user->first_name );
	}

	if ( isset( $user->last_name ) ) {
		xprofile_set_field_data( bp_xprofile_lastname_field_id(), $user->ID, $user->last_name );
	}

	if ( isset( $user->nickname ) ) {
		xprofile_set_field_data( bp_xprofile_nickname_field_id(), $user->ID, $user->nickname );
	}

	$user->display_name = bp_core_get_member_display_name( $user->display_name, $user->ID );
}
add_action( 'user_profile_update_errors', 'xprofile_sync_bp_profile', 20, 3 );

/**
 * Update display_name in user database.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_xprofile_update_display_name( $user_id ) {
	wp_update_user(
		array(
			'ID'           => $user_id,
			'display_name' => bp_core_get_member_display_name( get_user_by( 'ID', $user_id )->display_name, $user_id ),
		)
	);
}

/**
 * Validate nickname when updated and return error if invalid.
 *
 * @since BuddyBoss 1.0.0
 */
function user_profile_update_validate_nickname( &$errors, $update, &$user ) {
	// Bail if not updating or already has error
	if ( ! $update || $errors->get_error_codes() ) {
		return;
	}

	$invalid = bp_xprofile_validate_nickname_value( '', bp_xprofile_nickname_field_id(), $user->nickname, $user->ID );

	if ( $invalid ) {
		$errors->add(
			'nickname_invalid',
			$invalid,
			array( 'form-field' => 'nickname' )
		);
	}
}
add_action( 'user_profile_update_errors', 'user_profile_update_validate_nickname', 10, 3 );

/**
 * Update the WP display, last, and first name fields when the xprofile display name field is updated.
 *
 * @since BuddyPress 3.0.0
 *
 * @param BP_XProfile_ProfileData $data Current instance of the profile data being saved.
 */
function xprofile_sync_wp_profile_on_single_field_set( $data ) {
	$synced_fields = array_filter(
		array(
			bp_xprofile_firstname_field_id(),
			bp_xprofile_lastname_field_id(),
			bp_xprofile_nickname_field_id(),
		)
	);

	if ( ! in_array( $data->field_id, $synced_fields ) ) {
		return;
	}

	xprofile_sync_wp_profile( $data->user_id, $data->field_id );
}
add_action( 'xprofile_data_after_save', 'xprofile_sync_wp_profile_on_single_field_set' );

/**
 * When a user is deleted, we need to clean up the database and remove all the
 * profile data from each table. Also we need to clean anything up in the
 * usermeta table that this component uses.
 *
 * @since BuddyPress 1.0.0
 *
 * @param int $user_id The ID of the deleted user.
 */
function xprofile_remove_data( $user_id ) {
	BP_XProfile_ProfileData::delete_data_for_user( $user_id );
}
add_action( 'wpmu_delete_user', 'xprofile_remove_data' );
add_action( 'delete_user', 'xprofile_remove_data' );
add_action( 'bp_make_spam_user', 'xprofile_remove_data' );

/*** XProfile Meta ****************************************************/

/**
 * Delete a piece of xprofile metadata.
 *
 * @since BuddyPress 1.5.0
 *
 * @param int         $object_id   ID of the object the metadata belongs to.
 * @param string      $object_type Type of object. 'group', 'field', or 'data'.
 * @param string|bool $meta_key    Key of the metadata being deleted. If omitted, all
 *                                 metadata for the object will be deleted.
 * @param mixed       $meta_value  Optional. If provided, only metadata that matches
 *                                 the value will be permitted.
 * @param bool        $delete_all  Optional. If true, delete matching metadata entries
 *                                 for all objects, ignoring the specified object_id. Otherwise, only
 *                                 delete matching metadata entries for the specified object.
 *                                 Default: false.
 * @return bool True on success, false on failure.
 */
function bp_xprofile_delete_meta( $object_id, $object_type, $meta_key = false, $meta_value = false, $delete_all = false ) {
	global $wpdb;

	// Sanitize object type.
	if ( ! in_array( $object_type, array( 'group', 'field', 'data' ) ) ) {
		return false;
	}

	// Legacy - if no meta_key is passed, delete all for the item.
	if ( empty( $meta_key ) ) {
		$table_key  = 'xprofile_' . $object_type . 'meta';
		$table_name = $wpdb->{$table_key};
		$keys       = $wpdb->get_col( $wpdb->prepare( "SELECT meta_key FROM {$table_name} WHERE object_type = %s AND object_id = %d", $object_type, $object_id ) );

		// Force delete_all to false if deleting all for object.
		$delete_all = false;
	} else {
		$keys = array( $meta_key );
	}

	add_filter( 'query', 'bp_filter_metaid_column_name' );
	add_filter( 'query', 'bp_xprofile_filter_meta_query' );

	$retval = false;
	foreach ( $keys as $key ) {
		$retval = delete_metadata( 'xprofile_' . $object_type, $object_id, $key, $meta_value, $delete_all );
	}

	remove_filter( 'query', 'bp_xprofile_filter_meta_query' );
	remove_filter( 'query', 'bp_filter_metaid_column_name' );

	return $retval;
}

/**
 * Get a piece of xprofile metadata.
 *
 * Note that the default value of $single is true, unlike in the case of the
 * underlying get_metadata() function. This is for backward compatibility.
 *
 * @since BuddyPress 1.5.0
 *
 * @param int    $object_id   ID of the object the metadata belongs to.
 * @param string $object_type Type of object. 'group', 'field', or 'data'.
 * @param string $meta_key    Key of the metadata being fetched. If omitted, all
 *                            metadata for the object will be retrieved.
 * @param bool   $single      Optional. If true, return only the first value of the
 *                            specified meta_key. This parameter has no effect if meta_key is not
 *                            specified. Default: true.
 * @return mixed Meta value if found. False on failure.
 */
function bp_xprofile_get_meta( $object_id, $object_type, $meta_key = '', $single = true ) {
	// Sanitize object type.
	if ( ! in_array( $object_type, array( 'group', 'field', 'data' ) ) ) {
		return false;
	}

	add_filter( 'query', 'bp_filter_metaid_column_name' );
	add_filter( 'query', 'bp_xprofile_filter_meta_query' );
	$retval = get_metadata( 'xprofile_' . $object_type, $object_id, $meta_key, $single );
	remove_filter( 'query', 'bp_filter_metaid_column_name' );
	remove_filter( 'query', 'bp_xprofile_filter_meta_query' );

	return $retval;
}

/**
 * Update a piece of xprofile metadata.
 *
 * @since BuddyPress 1.5.0
 *
 * @param int    $object_id   ID of the object the metadata belongs to.
 * @param string $object_type Type of object. 'group', 'field', or 'data'.
 * @param string $meta_key    Key of the metadata being updated.
 * @param string $meta_value  Value of the metadata being updated.
 * @param mixed  $prev_value  Optional. If specified, only update existing
 *                            metadata entries with the specified value.
 *                            Otherwise update all entries.
 * @return bool|int Returns false on failure. On successful update of existing
 *                  metadata, returns true. On successful creation of new metadata,
 *                  returns the integer ID of the new metadata row.
 */
function bp_xprofile_update_meta( $object_id, $object_type, $meta_key, $meta_value, $prev_value = '' ) {
	add_filter( 'query', 'bp_filter_metaid_column_name' );
	add_filter( 'query', 'bp_xprofile_filter_meta_query' );
	$retval = update_metadata( 'xprofile_' . $object_type, $object_id, $meta_key, $meta_value, $prev_value );
	remove_filter( 'query', 'bp_xprofile_filter_meta_query' );
	remove_filter( 'query', 'bp_filter_metaid_column_name' );

	return $retval;
}

/**
 * Add a piece of xprofile metadata.
 *
 * @since BuddyPress 2.0.0
 *
 * @param int    $object_id   ID of the object the metadata belongs to.
 * @param string $object_type Type of object. 'group', 'field', or 'data'.
 * @param string $meta_key    Metadata key.
 * @param mixed  $meta_value  Metadata value.
 * @param bool   $unique      Optional. Whether to enforce a single metadata value
 *                            for the given key. If true, and the object already
 *                            has a value for the key, no change will be made.
 *                            Default false.
 * @return int|bool The meta ID on successful update, false on failure.
 */
function bp_xprofile_add_meta( $object_id, $object_type, $meta_key, $meta_value, $unique = false ) {
	add_filter( 'query', 'bp_filter_metaid_column_name' );
	add_filter( 'query', 'bp_xprofile_filter_meta_query' );
	$retval = add_metadata( 'xprofile_' . $object_type, $object_id, $meta_key, $meta_value, $unique );
	remove_filter( 'query', 'bp_filter_metaid_column_name' );
	remove_filter( 'query', 'bp_xprofile_filter_meta_query' );

	return $retval;
}

/**
 * Updates the fieldgroup metadata.
 *
 * @since BuddyPress 1.5.0
 *
 * @param int    $field_group_id Group ID for the group field belongs to.
 * @param string $meta_key       Meta key to update.
 * @param string $meta_value     Meta value to update to.
 * @return bool|int
 */
function bp_xprofile_update_fieldgroup_meta( $field_group_id, $meta_key, $meta_value ) {
	return bp_xprofile_update_meta( $field_group_id, 'group', $meta_key, $meta_value );
}

/**
 * Updates the field metadata.
 *
 * @since BuddyPress 1.5.0
 *
 * @param int    $field_id   Field ID to update.
 * @param string $meta_key   Meta key to update.
 * @param string $meta_value Meta value to update to.
 * @return bool|int
 */
function bp_xprofile_update_field_meta( $field_id, $meta_key, $meta_value ) {
	return bp_xprofile_update_meta( $field_id, 'field', $meta_key, $meta_value );
}

/**
 * Updates the fielddata metadata.
 *
 * @since BuddyPress 1.5.0
 *
 * @param int    $field_data_id Field ID to update.
 * @param string $meta_key      Meta key to update.
 * @param string $meta_value    Meta value to update to.
 * @return bool|int
 */
function bp_xprofile_update_fielddata_meta( $field_data_id, $meta_key, $meta_value ) {
	return bp_xprofile_update_meta( $field_data_id, 'data', $meta_key, $meta_value );
}

/**
 * Return the field ID for the Full Name xprofile field.
 *
 * @since BuddyPress 2.0.0
 *
 * @return int Field ID.
 */
function bp_xprofile_fullname_field_id() {
	$id = wp_cache_get( 'fullname_field_id', 'bp_xprofile' );

	if ( false === $id ) {
		global $wpdb;

		$bp = buddypress();

		if ( isset( $bp->profile->table_name_fields ) ) {
			$id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$bp->profile->table_name_fields} WHERE name = %s", addslashes( bp_xprofile_fullname_field_name() ) ) );
		} else {
			$table = bp_core_get_table_prefix() . 'bp_xprofile_fields';
			$id    = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE name = %s", addslashes( bp_xprofile_fullname_field_name() ) ) );
		}

		wp_cache_set( 'fullname_field_id', $id, 'bp_xprofile' );
	}

	return absint( $id );
}

/**
 * Get the group id of the base name field
 *
 * @since BuddyBoss 1.0.0
 */
function bp_xprofile_base_group_id( $defalut = 1, $get_option = true ) {
	if ( is_multisite() ) {
		$field_id = get_site_option( 'bp-xprofile-base-group-id' );
	}

	if ( empty( $field_id ) && $get_option ) {
		$field_id = bp_get_option( 'bp-xprofile-base-group-id', $defalut );
	}

	return (int) apply_filters( 'bp_xprofile_base_group_id', $field_id );
}

/**
 * Get the field id of the first name field
 *
 * @since BuddyBoss 1.0.0
 */
function bp_xprofile_firstname_field_id( $defalut = 1, $get_option = true ) {
	$field_id = 0;

	if ( is_multisite() ) {
		$field_id = get_site_option( 'bp-xprofile-firstname-field-id' );
	}

	if ( empty( $field_id ) && $get_option ) {
		$field_id = bp_get_option( 'bp-xprofile-firstname-field-id', $defalut );
	}

	return (int) apply_filters( 'bp_xprofile_firstname_field_id', $field_id );
}

/**
 * Get the field id of the last name field
 *
 * @since BuddyBoss 1.0.0
 */
function bp_xprofile_lastname_field_id( $defalut = 0, $get_option = true ) {
	$field_id = 0;

	if ( is_multisite() ) {
		$field_id = get_site_option( 'bp-xprofile-lastname-field-id' );
	}

	if ( empty( $field_id ) && $get_option ) {
		$field_id = bp_get_option( 'bp-xprofile-lastname-field-id', $defalut );
	}

	return (int) apply_filters( 'bp_xprofile_lastname_field_id', $field_id );
}

/**
 * Get the field id of the nick name field, fallback to default fullname field
 *
 * @since BuddyBoss 1.0.0
 */
function bp_xprofile_nickname_field_id( $no_fallback = false, $get_option = true ) {
	$field_id = 0;

	if ( is_multisite() ) {
		$field_id = get_site_option( 'bp-xprofile-nickname-field-id', $no_fallback ? 0 : 0 );
	}

	if ( empty( $field_id ) && $get_option ) {
		$field_id = bp_get_option( 'bp-xprofile-nickname-field-id', $no_fallback ? 0 : 0 );
	}

	// Set nickname field id to 0(zero) if first name and nickname both are same.
	$first_name_id = bp_xprofile_firstname_field_id();
	if ( $first_name_id === (int) $field_id ) {
		$field_id = 0;
	}

	return (int) apply_filters( 'bp_xprofile_nickname_field_id', $field_id );
}

/**
 * Return the field name for the Full Name xprofile field.
 *
 * @since BuddyPress 1.5.0
 *
 * @return string The field name.
 */
function bp_xprofile_fullname_field_name() {
	$field_name = BP_XPROFILE_FULLNAME_FIELD_NAME;

	/**
	 * Get the nickname field if is set
	 *
	 * @since BuddyBoss 1.0.0
	 */
	if ( $nickname_field_id = bp_xprofile_nickname_field_id( true ) ) {
		$field_name = xprofile_get_field( $nickname_field_id )->name;
	}

	/**
	 * Filters the field name for the Full Name xprofile field.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param string $value BP_XPROFILE_FULLNAME_FIELD_NAME Full name field constant.
	 */
	return apply_filters( 'bp_xprofile_fullname_field_name', $field_name );
}

/**
 * Is rich text enabled for this profile field?
 *
 * By default, rich text is enabled for textarea fields and disabled for all other field types.
 *
 * @since BuddyPress 2.4.0
 *
 * @param int|null $field_id Optional. Default current field ID.
 * @return bool
 */
function bp_xprofile_is_richtext_enabled_for_field( $field_id = null ) {
	if ( ! $field_id ) {
		$field_id = bp_get_the_profile_field_id();
	}

	$field = xprofile_get_field( $field_id );

	$enabled = false;
	if ( $field instanceof BP_XProfile_Field ) {
		$enabled = (bool) $field->type_obj->supports_richtext;
	}

	/**
	 * Filters whether richtext is enabled for the given field.
	 *
	 * @since BuddyPress 2.4.0
	 *
	 * @param bool $enabled  True if richtext is enabled for the field, otherwise false.
	 * @param int  $field_id ID of the field.
	 */
	return apply_filters( 'bp_xprofile_is_richtext_enabled_for_field', $enabled, $field_id );
}

/**
 * Get visibility levels out of the $bp global.
 *
 * @since BuddyPress 1.6.0
 *
 * @return array
 */
function bp_xprofile_get_visibility_levels() {

	/**
	 * Filters the visibility levels out of the $bp global.
	 *
	 * @since BuddyPress 1.6.0
	 *
	 * @param array $visibility_levels Array of visibility levels.
	 */
	return apply_filters( 'bp_xprofile_get_visibility_levels', buddypress()->profile->visibility_levels );
}

/**
 * Get the ids of fields that are hidden for this displayed/loggedin user pair.
 *
 * This is the function primarily responsible for profile field visibility. It works by determining
 * the relationship between the displayed_user (ie the profile owner) and the current_user (ie the
 * profile viewer). Then, based on that relationship, we query for the set of fields that should
 * be excluded from the profile loop.
 *
 * @since BuddyPress 1.6.0
 *
 * @see BP_XProfile_Group::get()
 *   or if you have added your own custom levels.
 *
 * @param int $displayed_user_id The id of the user the profile fields belong to.
 * @param int $current_user_id   The id of the user viewing the profile.
 * @return array An array of field ids that should be excluded from the profile query
 */
function bp_xprofile_get_hidden_fields_for_user( $displayed_user_id = 0, $current_user_id = 0 ) {
	if ( ! $displayed_user_id ) {
		$displayed_user_id = bp_displayed_user_id();
	}

	if ( ! $displayed_user_id ) {
		return array();
	}

	if ( ! $current_user_id ) {
		$current_user_id = bp_loggedin_user_id();
	}

	// @todo - This is where you'd swap out for current_user_can() checks
	$hidden_levels = bp_xprofile_get_hidden_field_types_for_user( $displayed_user_id, $current_user_id );
	$hidden_fields = bp_xprofile_get_fields_by_visibility_levels( $displayed_user_id, $hidden_levels );

	/**
	 * Filters the ids of fields that are hidden for this displayed/loggedin user pair.
	 *
	 * @since BuddyPress 1.6.0
	 *
	 * @param array $hidden_fields     Array of hidden fields for the displayed/logged in user.
	 * @param int   $displayed_user_id ID of the displayed user.
	 * @param int   $current_user_id   ID of the current user.
	 */
	return apply_filters( 'bp_xprofile_get_hidden_fields_for_user', $hidden_fields, $displayed_user_id, $current_user_id );
}

/**
 * Get the visibility levels that should be hidden for this user pair.
 *
 * Field visibility is determined based on the relationship between the
 * logged-in user, the displayed user, and the visibility setting for the
 * current field. (See bp_xprofile_get_hidden_fields_for_user().) This
 * utility function speeds up this matching by fetching the visibility levels
 * that should be hidden for the current user pair.
 *
 * @since BuddyPress 1.8.2
 *
 * @see bp_xprofile_get_hidden_fields_for_user()
 *
 * @param int $displayed_user_id The id of the user the profile fields belong to.
 * @param int $current_user_id   The id of the user viewing the profile.
 * @return array An array of visibility levels hidden to the current user.
 */
function bp_xprofile_get_hidden_field_types_for_user( $displayed_user_id = 0, $current_user_id = 0 ) {

	// Current user is logged in.
	if ( ! empty( $current_user_id ) ) {

		// Nothing's private when viewing your own profile, or when the
		// current user is an admin.
		if ( $displayed_user_id == $current_user_id || bp_current_user_can( 'bp_moderate' ) ) {
			$hidden_levels = array();

			// If the current user and displayed user are friends, show all.
		} elseif ( bp_is_active( 'friends' ) && friends_check_friendship( $displayed_user_id, $current_user_id ) ) {
			$hidden_levels = array( 'adminsonly' );

			// Current user is logged in but not friends, so exclude friends-only.
		} else {
			$hidden_levels = array( 'friends', 'adminsonly' );
		}

		// Current user is not logged in, so exclude friends-only, loggedin, and adminsonly.
	} else {
		$hidden_levels = array( 'friends', 'loggedin', 'adminsonly' );
	}

	/**
	 * Filters the visibility levels that should be hidden for this user pair.
	 *
	 * @since BuddyPress 2.0.0
	 *
	 * @param array $hidden_fields     Array of hidden fields for the displayed/logged in user.
	 * @param int   $displayed_user_id ID of the displayed user.
	 * @param int   $current_user_id   ID of the current user.
	 */
	return apply_filters( 'bp_xprofile_get_hidden_field_types_for_user', $hidden_levels, $displayed_user_id, $current_user_id );
}

/**
 * Fetch an array of the xprofile fields that a given user has marked with certain visibility levels.
 *
 * @since BuddyPress 1.6.0
 *
 * @see bp_xprofile_get_hidden_fields_for_user()
 *
 * @param int   $user_id The id of the profile owner.
 * @param array $levels  An array of visibility levels ('public', 'friends', 'loggedin', 'adminsonly' etc) to be
 *                       checked against.
 * @return array $field_ids The fields that match the requested visibility levels for the given user.
 */
function bp_xprofile_get_fields_by_visibility_levels( $user_id, $levels = array() ) {
	if ( ! is_array( $levels ) ) {
		$levels = (array) $levels;
	}

	$user_visibility_levels = bp_get_user_meta( $user_id, 'bp_xprofile_visibility_levels', true );

	// Parse the user-provided visibility levels with the default levels, which may take
	// precedence.
	$default_visibility_levels = BP_XProfile_Group::fetch_default_visibility_levels();

	foreach ( (array) $default_visibility_levels as $d_field_id => $defaults ) {
		// If the admin has forbidden custom visibility levels for this field, replace
		// the user-provided setting with the default specified by the admin.
		if ( isset( $defaults['allow_custom'] ) && isset( $defaults['default'] ) && 'disabled' == $defaults['allow_custom'] ) {
			$user_visibility_levels[ $d_field_id ] = $defaults['default'];
		}
	}

	$field_ids = array();
	foreach ( (array) $user_visibility_levels as $field_id => $field_visibility ) {
		if ( in_array( $field_visibility, $levels ) ) {
			$field_ids[] = $field_id;
		}
	}

	// Never allow the fullname field to be excluded.
	if ( in_array( 1, $field_ids ) ) {
		$key = array_search( 1, $field_ids );
		unset( $field_ids[ $key ] );
	}

	return $field_ids;
}

/**
 * Formats datebox field values passed through a POST request.
 *
 * @since BuddyPress 2.8.0
 *
 * @param int $field_id The id of the current field being looped through.
 * @return void This function only changes the global $_POST that should contain
 *              the datebox data.
 */
function bp_xprofile_maybe_format_datebox_post_data( $field_id ) {
	if ( ! isset( $_POST[ 'field_' . $field_id ] ) ) {
		if ( ! empty( $_POST[ 'field_' . $field_id . '_day' ] ) && ! empty( $_POST[ 'field_' . $field_id . '_month' ] ) && ! empty( $_POST[ 'field_' . $field_id . '_year' ] ) ) {
			// Concatenate the values.
			$date_value = $_POST[ 'field_' . $field_id . '_day' ] . ' ' . $_POST[ 'field_' . $field_id . '_month' ] . ' ' . $_POST[ 'field_' . $field_id . '_year' ];

			// Check that the concatenated value can be turned into a timestamp.
			if ( $timestamp = strtotime( $date_value ) ) {
				// Add the timestamp to the global $_POST that should contain the datebox data.
				$_POST[ 'field_' . $field_id ] = date( 'Y-m-d H:i:s', $timestamp );
			}
		}
	}
}

/**
 * Determine a user's "mentionname", the name used for that user in @-mentions.
 *
 * @since BuddyPress 1.9.0
 *
 * @param int|string $user_id ID of the user to get @-mention name for.
 * @return string $mentionname User name appropriate for @-mentions.
 */
function bp_activity_get_user_mentionname( $user_id ) {
	$mentionname = '';

	$userdata = bp_core_get_core_userdata( $user_id );

	if ( $userdata ) {
		if ( bp_is_username_compatibility_mode() ) {
			$mentionname = str_replace( ' ', '-', $userdata->user_login );
		} else {
			$mentionname = get_user_meta( $userdata->ID, 'nickname', true );
		}
	}

	return $mentionname;
}

/**
 * Options for at mention js script
 *
 * @since BuddyBoss 1.0.0
 */
function bp_at_mention_default_options() {
	return apply_filters(
		'bp_at_mention_js_options',
		array(
			'selectors'   => array( '.bp-suggestions', '#comments form textarea', '.wp-editor-area' ),
			'insert_tpl'  => '@${ID}',
			'display_tpl' => '<li data-value="@${ID}"><img src="${image}" /><span class="username">@${ID}</span><small>${name}</small></li>',
			'extra_options' => array()
		)
	);
}

/**
 * Social Networks xprofile field provider.
 *
 * @since BuddyBoss 1.0.0
 *
 * @return array
 */
function bp_xprofile_social_network_provider() {

	$options = array();

	$options[] = (object) array(
		'id'                => 1,
		'is_default_option' => false,
		'name'              => __( 'Facebook', 'buddyboss' ),
		'value'             => 'facebook',
		'svg'               => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><path fill="#333" d="M16 0c-8.8 0-16 7.2-16 16s7.2 16 16 16c8.8 0 16-7.2 16-16s-7.2-16-16-16v0zM20.192 10.688h-1.504c-1.184 0-1.376 0.608-1.376 1.408v1.792h2.784l-0.384 2.816h-2.4v7.296h-2.912v-7.296h-2.496v-2.816h2.496v-2.080c-0.096-2.496 1.408-3.808 3.616-3.808 0.992 0 1.888 0.096 2.176 0.096v2.592z"></path></svg>',
	);
	$options[] = (object) array(
		'id'                => 2,
		'is_default_option' => false,
		'name'              => __( 'Flickr', 'buddyboss' ),
		'value'             => 'flickr',
		'svg'               => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><path fill="#333" d="M16 0c-8.837 0-16 7.212-16 16.109s7.163 16.109 16 16.109 16-7.212 16-16.109-7.163-16.109-16-16.109zM9 21c-2.761 0-5-2.239-5-5s2.239-5 5-5 5 2.239 5 5c0 2.761-2.239 5-5 5zM23 21c-2.761 0-5-2.239-5-5s2.239-5 5-5 5 2.239 5 5c0 2.761-2.239 5-5 5z"></path></svg>',
	);
	$options[] = (object) array(
		'id'                => 3,
		'is_default_option' => false,
		'name'              => __( 'Google+', 'buddyboss' ),
		'value'             => 'google',
		'svg'               => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><path fill="#333" d="M16 0c-8.838 0-16 7.162-16 16s7.162 16 16 16 16-7.163 16-16-7.163-16-16-16zM12 24c-4.425 0-8-3.575-8-8s3.575-8 8-8c2.162 0 3.969 0.787 5.363 2.094l-2.175 2.088c-0.594-0.569-1.631-1.231-3.188-1.231-2.731 0-4.963 2.263-4.963 5.050s2.231 5.050 4.963 5.050c3.169 0 4.356-2.275 4.538-3.45h-4.537v-2.744h7.556c0.069 0.4 0.125 0.8 0.125 1.325 0 4.575-3.063 7.819-7.681 7.819zM26 16v2h-2v-2h-2v-2h2v-2h2v2h2v2h-2z"></path></svg>',
	);
	$options[] = (object) array(
		'id'                => 4,
		'is_default_option' => false,
		'name'              => __( 'Instagram', 'buddyboss' ),
		'value'             => 'instagram',
		'svg'               => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><path fill="#333" d="M16 19.104c-1.696 0-3.104-1.408-3.104-3.104 0-1.728 1.408-3.104 3.104-3.104 1.728 0 3.104 1.376 3.104 3.104 0 1.696-1.376 3.104-3.104 3.104zM19.616 12.896c-0.32 0-0.512-0.192-0.416-0.384v-2.208c0-0.192 0.192-0.416 0.416-0.416h2.176c0.224 0 0.416 0.224 0.416 0.416v2.208c0 0.192-0.192 0.384-0.416 0.384h-2.176zM16 0c-8.8 0-16 7.2-16 16s7.2 16 16 16c8.8 0 16-7.2 16-16s-7.2-16-16-16v0zM24 22.112c0 0.992-0.896 1.888-1.888 1.888h-12.224c-0.992 0-1.888-0.8-1.888-1.888v-12.224c0-1.088 0.896-1.888 1.888-1.888h12.224c0.992 0 1.888 0.8 1.888 1.888v12.224zM20.896 16c0 2.688-2.208 4.896-4.896 4.896s-4.896-2.208-4.896-4.896c0-0.416 0.096-0.896 0.192-1.312h-1.504v7.008c0 0.192 0.224 0.416 0.416 0.416h11.488c0.192 0 0.416-0.224 0.416-0.416v-7.008h-1.504c0.192 0.416 0.288 0.896 0.288 1.312z"></path></svg>',
	);
	$options[] = (object) array(
		'id'                => 5,
		'is_default_option' => false,
		'name'              => __( 'LinkedIn', 'buddyboss' ),
		'value'             => 'linkedIn',
		'svg'               => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill="#333" d="M10 0.4c-5.302 0-9.6 4.298-9.6 9.6s4.298 9.6 9.6 9.6 9.6-4.298 9.6-9.6-4.298-9.6-9.6-9.6zM7.65 13.979h-1.944v-6.256h1.944v6.256zM6.666 6.955c-0.614 0-1.011-0.435-1.011-0.973 0-0.549 0.409-0.971 1.036-0.971s1.011 0.422 1.023 0.971c0 0.538-0.396 0.973-1.048 0.973zM14.75 13.979h-1.944v-3.467c0-0.807-0.282-1.355-0.985-1.355-0.537 0-0.856 0.371-0.997 0.728-0.052 0.127-0.065 0.307-0.065 0.486v3.607h-1.945v-4.26c0-0.781-0.025-1.434-0.051-1.996h1.689l0.089 0.869h0.039c0.256-0.408 0.883-1.010 1.932-1.010 1.279 0 2.238 0.857 2.238 2.699v3.699z"></path></svg>',
	);
	$options[] = (object) array(
		'id'                => 6,
		'is_default_option' => false,
		'name'              => __( 'Medium', 'buddyboss' ),
		'value'             => 'medium',
		'svg'               => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 28 28"><path fill="#333" d="M9.328 6.578v18.328c0 0.484-0.234 0.938-0.766 0.938-0.187 0-0.359-0.047-0.516-0.125l-7.266-3.641c-0.438-0.219-0.781-0.781-0.781-1.25v-17.813c0-0.391 0.187-0.75 0.609-0.75 0.25 0 0.469 0.125 0.688 0.234l7.984 4c0.016 0.016 0.047 0.063 0.047 0.078zM10.328 8.156l8.344 13.531-8.344-4.156v-9.375zM28 8.437v16.469c0 0.516-0.297 0.875-0.812 0.875-0.266 0-0.516-0.078-0.734-0.203l-6.891-3.437zM27.953 6.563c0 0.063-8.078 13.172-8.703 14.172l-6.094-9.906 5.063-8.234c0.172-0.281 0.484-0.438 0.812-0.438 0.141 0 0.281 0.031 0.406 0.094l8.453 4.219c0.031 0.016 0.063 0.047 0.063 0.094z"></path></svg>',
	);
	$options[] = (object) array(
		'id'                => 7,
		'is_default_option' => false,
		'name'              => __( 'Meetup', 'buddyboss' ),
		'value'             => 'meetup',
		'svg'               => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><path fill="#333" d="M31.971 26.984c-0.405-2.575-5.165-0.592-5.461-3.412-0.417-3.997 5.533-12.612 5.063-15.963-0.417-3.007-2.455-3.64-4.22-3.675-1.712-0.027-2.164 0.243-2.744 0.58-0.337 0.195-0.816 0.58-1.483-0.055-0.445-0.424-0.743-0.715-1.207-1.092-0.243-0.189-0.621-0.432-1.26-0.527-0.635-0.095-1.464 0-1.989 0.223-0.527 0.229-0.936 0.621-1.368 0.999-0.431 0.377-1.529 1.597-2.548 1.145-0.447-0.193-1.944-0.941-3.029-1.407-2.084-0.903-5.096 0.56-6.181 2.488-1.617 2.865-4.8 14.137-5.285 15.62-1.079 3.336 1.376 6.053 4.679 5.899 1.403-0.068 2.333-0.573 3.216-2.184 0.512-0.924 5.305-13.449 5.664-14.057 0.263-0.431 1.125-1.004 1.853-0.633 0.735 0.377 0.883 1.159 0.775 1.895-0.181 1.193-3.559 8.839-3.695 9.7-0.216 1.471 0.479 2.285 2.009 2.365 1.045 0.055 2.089-0.316 2.912-1.88 0.465-0.869 5.799-11.555 6.269-12.269 0.52-0.781 0.937-1.039 1.471-1.011 0.412 0.020 1.065 0.128 0.904 1.355-0.163 1.207-4.457 9.040-4.901 10.961-0.608 2.569 0.803 5.165 3.121 6.304 1.483 0.728 7.96 1.968 7.436-1.369z"></path></svg>',
	);
	$options[] = (object) array(
		'id'                => 8,
		'is_default_option' => false,
		'name'              => __( 'Pinterest', 'buddyboss' ),
		'value'             => 'pinterest',
		'svg'               => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 28"><path fill="#333" d="M24 14c0 6.625-5.375 12-12 12-1.188 0-2.312-0.172-3.406-0.5 0.453-0.719 0.969-1.641 1.219-2.562 0 0 0.141-0.531 0.844-3.297 0.406 0.797 1.625 1.5 2.922 1.5 3.859 0 6.484-3.516 6.484-8.234 0-3.547-3.016-6.875-7.609-6.875-5.688 0-8.563 4.094-8.563 7.5 0 2.063 0.781 3.906 2.453 4.594 0.266 0.109 0.516 0 0.594-0.313 0.063-0.203 0.187-0.734 0.25-0.953 0.078-0.313 0.047-0.406-0.172-0.672-0.484-0.578-0.797-1.313-0.797-2.359 0-3.031 2.266-5.75 5.906-5.75 3.219 0 5 1.969 5 4.609 0 3.453-1.531 6.375-3.813 6.375-1.25 0-2.188-1.031-1.891-2.312 0.359-1.516 1.062-3.156 1.062-4.25 0-0.984-0.531-1.813-1.625-1.813-1.281 0-2.312 1.328-2.312 3.109 0 0 0 1.141 0.391 1.906-1.313 5.563-1.547 6.531-1.547 6.531-0.219 0.906-0.234 1.922-0.203 2.766-4.234-1.859-7.187-6.078-7.187-11 0-6.625 5.375-12 12-12s12 5.375 12 12z"></path></svg>',
	);
	$options[] = (object) array(
		'id'                => 9,
		'is_default_option' => false,
		'name'              => __( 'Quora', 'buddyboss' ),
		'value'             => 'quora',
		'svg'               => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 28 28"><path fill="#333" d="M19.609 12.297c0-6.516-2.031-9.859-6.797-9.859-4.688 0-6.719 3.344-6.719 9.859 0 6.484 2.031 9.797 6.719 9.797 0.75 0 1.422-0.078 2.047-0.266v0c-0.969-1.906-2.109-3.828-4.328-3.828-0.422 0-0.844 0.063-1.234 0.25l-0.766-1.516c0.922-0.797 2.406-1.422 4.312-1.422 2.984 0 4.5 1.437 5.719 3.266 0.703-1.563 1.047-3.672 1.047-6.281zM25.703 22.172h1.828c0.109 1.125-0.453 5.828-5.563 5.828-3.094 0-4.719-1.797-5.953-3.891v0c-1.016 0.281-2.109 0.422-3.203 0.422-6.25 0-12.359-4.984-12.359-12.234 0-7.313 6.125-12.297 12.359-12.297 6.359 0 12.406 4.953 12.406 12.297 0 4.094-1.906 7.422-4.672 9.562 0.891 1.344 1.813 2.234 3.094 2.234 1.406 0 1.969-1.078 2.063-1.922z"></path></svg>',
	);
	$options[] = (object) array(
		'id'                => 10,
		'is_default_option' => false,
		'name'              => __( 'Reddit', 'buddyboss' ),
		'value'             => 'reddit',
		'svg'               => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 28 28"><path fill="#333" d="M17.109 18.234c0.141 0.141 0.141 0.359 0 0.484-0.891 0.891-2.609 0.969-3.109 0.969s-2.219-0.078-3.109-0.969c-0.141-0.125-0.141-0.344 0-0.484 0.125-0.125 0.344-0.125 0.469 0 0.562 0.578 1.781 0.766 2.641 0.766s2.063-0.187 2.641-0.766c0.125-0.125 0.344-0.125 0.469 0zM12.313 15.406c0 0.766-0.625 1.391-1.391 1.391-0.781 0-1.406-0.625-1.406-1.391 0-0.781 0.625-1.406 1.406-1.406 0.766 0 1.391 0.625 1.391 1.406zM18.484 15.406c0 0.766-0.625 1.391-1.406 1.391-0.766 0-1.391-0.625-1.391-1.391 0-0.781 0.625-1.406 1.391-1.406 0.781 0 1.406 0.625 1.406 1.406zM22.406 13.531c0-1.031-0.844-1.859-1.875-1.859-0.531 0-1 0.219-1.344 0.562-1.266-0.875-2.969-1.437-4.859-1.5l0.984-4.422 3.125 0.703c0 0.766 0.625 1.391 1.391 1.391 0.781 0 1.406-0.641 1.406-1.406s-0.625-1.406-1.406-1.406c-0.547 0-1.016 0.328-1.25 0.781l-3.453-0.766c-0.172-0.047-0.344 0.078-0.391 0.25l-1.078 4.875c-1.875 0.078-3.563 0.641-4.828 1.516-0.344-0.359-0.828-0.578-1.359-0.578-1.031 0-1.875 0.828-1.875 1.859 0 0.75 0.438 1.375 1.062 1.687-0.063 0.281-0.094 0.578-0.094 0.875 0 2.969 3.344 5.375 7.453 5.375 4.125 0 7.469-2.406 7.469-5.375 0-0.297-0.031-0.609-0.109-0.891 0.609-0.313 1.031-0.938 1.031-1.672zM28 14c0 7.734-6.266 14-14 14s-14-6.266-14-14 6.266-14 14-14 14 6.266 14 14z"></path></svg>',
	);
	$options[] = (object) array(
		'id'                => 14,
		'is_default_option' => false,
		'name'              => __( 'Snapchat', 'buddyboss' ),
		'value'             => 'snapchat',
		'svg'               => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M12 0c-6.627 0-12 5.373-12 12s5.373 12 12 12 12-5.373 12-12-5.373-12-12-12zm5.126 16.475c-.057.077-.103.4-.178.655-.086.295-.356.262-.656.203-.437-.085-.827-.109-1.281-.034-.785.131-1.601 1.292-2.969 1.292-1.472 0-2.238-1.156-3.054-1.292-.832-.138-1.31.084-1.597.084-.221 0-.307-.135-.34-.247-.074-.251-.12-.581-.178-.66-.565-.087-1.84-.309-1.873-.878-.008-.148.096-.279.243-.303 1.872-.308 3.063-2.419 2.869-2.877-.138-.325-.735-.442-.986-.541-.648-.256-.739-.55-.7-.752.053-.28.395-.468.68-.468.275 0 .76.367 1.138.158-.055-.982-.194-2.387.156-3.171.667-1.496 2.129-2.236 3.592-2.236 1.473 0 2.946.75 3.608 2.235.349.783.212 2.181.156 3.172.357.197.799-.167 1.107-.167.302 0 .712.204.719.545.005.267-.233.497-.708.684-.255.101-.848.217-.986.541-.198.468 1.03 2.573 2.869 2.876.146.024.251.154.243.303-.033.569-1.314.791-1.874.878z"/></svg>',
	);
	$options[] = (object) array(
		'id'                => 11,
		'is_default_option' => false,
		'name'              => __( 'Tumblr', 'buddyboss' ),
		'value'             => 'tumblr',
		'svg'               => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill="#333" d="M10 0.4c-5.302 0-9.6 4.298-9.6 9.6s4.298 9.6 9.6 9.6 9.6-4.298 9.6-9.6-4.298-9.6-9.6-9.6zM12.577 14.141c-0.393 0.188-0.748 0.318-1.066 0.395-0.318 0.074-0.662 0.113-1.031 0.113-0.42 0-0.791-0.055-1.114-0.162s-0.598-0.26-0.826-0.459c-0.228-0.197-0.386-0.41-0.474-0.633-0.088-0.225-0.132-0.549-0.132-0.973v-3.262h-1.016v-1.314c0.359-0.119 0.67-0.289 0.927-0.512 0.257-0.221 0.464-0.486 0.619-0.797s0.263-0.707 0.322-1.185h1.307v2.35h2.18v1.458h-2.18v2.385c0 0.539 0.028 0.885 0.085 1.037 0.056 0.154 0.161 0.275 0.315 0.367 0.204 0.123 0.437 0.185 0.697 0.185 0.466 0 0.928-0.154 1.388-0.461v1.468z"></path></svg>',
	);
	$options[] = (object) array(
		'id'                => 12,
		'is_default_option' => false,
		'name'              => __( 'Twitter', 'buddyboss' ),
		'value'             => 'twitter',
		'svg'               => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><path fill="#333" d="M16 0c-8.8 0-16 7.2-16 16s7.2 16 16 16c8.8 0 16-7.2 16-16s-7.2-16-16-16v0zM22.4 12.704v0.384c0 4.32-3.296 9.312-9.312 9.312-1.888 0-3.584-0.512-4.992-1.504h0.8c1.504 0 3.008-0.512 4.096-1.408-1.376 0-2.592-0.992-3.104-2.304 0.224 0 0.416 0.128 0.608 0.128 0.32 0 0.608 0 0.896-0.128-1.504-0.288-2.592-1.6-2.592-3.2v0c0.416 0.224 0.896 0.416 1.504 0.416-0.896-0.608-1.504-1.6-1.504-2.688 0-0.608 0.192-1.216 0.416-1.728 1.6 2.016 4 3.328 6.784 3.424-0.096-0.224-0.096-0.512-0.096-0.704 0-1.792 1.504-3.296 3.296-3.296 0.896 0 1.792 0.384 2.4 0.992 0.704-0.096 1.504-0.416 2.112-0.8-0.224 0.8-0.8 1.408-1.408 1.792 0.704-0.096 1.312-0.288 1.888-0.48-0.576 0.8-1.184 1.376-1.792 1.792v0z"></path></svg>',
	);
	$options[] = (object) array(
		'id'                => 13,
		'is_default_option' => false,
		'name'              => __( 'YouTube', 'buddyboss' ),
		'value'             => 'youTube',
		'svg'               => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><path fill="#333" d="M16 0c-8.8 0-16 7.2-16 16s7.2 16 16 16c8.8 0 16-7.2 16-16s-7.2-16-16-16v0zM24 16.608c0 1.28-0.192 2.592-0.192 2.592s-0.192 1.088-0.608 1.6c-0.608 0.608-1.312 0.608-1.6 0.704-2.208 0.192-5.6 0.192-5.6 0.192s-4.192 0-5.408-0.192c-0.384-0.096-1.184 0-1.792-0.704-0.512-0.512-0.608-1.6-0.608-1.6s-0.192-1.312-0.192-2.592v-1.216c0-1.28 0.192-2.592 0.192-2.592s0.224-1.088 0.608-1.6c0.608-0.608 1.312-0.608 1.6-0.704 2.208-0.192 5.6-0.192 5.6-0.192s3.392 0 5.6 0.192c0.288 0 0.992 0 1.6 0.704 0.512 0.512 0.608 1.6 0.608 1.6s0.192 1.312 0.192 2.592v1.216zM14.304 18.112l4.384-2.304-4.384-2.208v4.512z"></path></svg>',
	);

	return apply_filters( 'bp_xprofile_fields_social_networks_provider', $options );
}

/**
 * Add social networks button to the member header area.
 *
 * @return string
 * @since BuddyBoss 1.0.0
 */
function bp_get_user_social_networks_urls( $user_id = null ) {

	global $wpdb;
	global $bp;

	$social_networks_id = (int) $wpdb->get_var( "SELECT a.id FROM {$bp->table_prefix}bp_xprofile_fields a WHERE parent_id = 0 AND type = 'socialnetworks' " );

	$html = '';

	$original_option_values = array();

	$user = ( $user_id !== null && $user_id > 0 ) ? $user_id : bp_displayed_user_id();

	if ( $social_networks_id > 0 ) {
		$providers = bp_xprofile_social_network_provider();

		$original_option_values = maybe_unserialize( BP_XProfile_ProfileData::get_value_byid( $social_networks_id, $user ) );

		if ( isset( $original_option_values ) && ! empty( $original_option_values ) ) {
			foreach ( $original_option_values as $key => $original_option_value ) {
				if ( '' !== $original_option_value ) {
					$key = bp_social_network_search_key( $key, $providers );

					$html .= '<span class="social ' . $providers[ $key ]->value . '"><a target="_blank" data-balloon-pos="up" data-balloon="' . $providers[ $key ]->name . '" href="' . esc_url( $original_option_value ) . '">' . $providers[ $key ]->svg . '</a></span>';
				}
			}
		}
	}

	if ( $html !== '' ) {
		$level = xprofile_get_field_visibility_level( $social_networks_id, bp_displayed_user_id() );

		if ( bp_displayed_user_id() === bp_loggedin_user_id() ) {
			$html = '<div class="social-networks-wrap">' . $html . '</div>';
		} elseif ( 'public' === $level ) {
			$html = '<div class="social-networks-wrap">' . $html . '</div>';
		} elseif ( 'loggedin' === $level && is_user_logged_in() ) {
			$html = '<div class="social-networks-wrap">' . $html . '</div>';
		} elseif ( 'friends' === $level && is_user_logged_in() ) {
			$member_friend_status = friends_check_friendship_status( bp_loggedin_user_id(), bp_displayed_user_id() );

			if ( 'is_friend' === $member_friend_status ) {
				$html = '<div class="social-networks-wrap">' . $html . '</div>';
			} else {
				$html = '';
			}
		}
	}

	return apply_filters( 'bp_get_user_social_networks_urls', $html, $original_option_values, $social_networks_id );
}

/**
 * Decide need to add profile field select box or not.
 *
 * @since BuddyBoss 1.1.3
 *
 * @return bool
 */
function bp_check_member_type_field_have_options() {

	$arr = array();

	// Get posts of custom post type selected.
	$posts = new \WP_Query(
		array(
			'posts_per_page' => - 1,
			'post_type'      => bp_get_member_type_post_type(),
			'orderby'        => 'title',
			'order'          => 'ASC',
		)
	);
	if ( $posts ) {
		foreach ( $posts->posts as $post ) {
			$enabled = get_post_meta( $post->ID, '_bp_member_type_enable_profile_field', true );
			if ( '' === $enabled || '1' === $enabled ) {
				$arr[] = $post->ID;
			}
		}
	}

	if ( empty( $arr ) ) {
		return false;
	} else {
		return true;
	}

}

/**
 * Get the display_name for member based on user_id
 *
 * @since BuddyBoss 1.0.0
 *
 * @param string $display_name
 * @param int    $user_id
 *
 * @return string
 */
function bp_xprofile_get_member_display_name( $user_id = null ) {
	// some cases it calls the filter directly, therefore no user id is passed
	if ( ! $user_id ) {
		return false;
	}

	$format = bp_get_option( 'bp-display-name-format' );

	switch ( $format ) {
		case 'first_name':
			// Get First Name Field Id.
			$first_name_id = (int) bp_get_option( 'bp-xprofile-firstname-field-id' );

			$display_name = xprofile_get_field_data( $first_name_id, $user_id );

			if ( '' === $display_name ) {
				$display_name = get_user_meta( $user_id, 'first_name', true );
				if ( empty( $display_name ) ) {
					$display_name = get_user_meta( $user_id, 'nickname', true );
				}
				xprofile_set_field_data( $first_name_id, $user_id, $display_name );
			}

			// Get Nick Name Field Id.
			$nickname_id = (int) bp_get_option( 'bp-xprofile-nickname-field-id' );
			$nick_name   = xprofile_get_field_data( $nickname_id, $user_id );

			if ( '' === trim( $nick_name ) ) {
				$user = get_userdata( $user_id );
				// make sure nickname is valid
				$nickname = get_user_meta( $user_id, 'nickname', true );
				$nickname = sanitize_title( $nickname );
				$invalid  = bp_xprofile_validate_nickname_value( '', $nickname_id, $nickname, $user_id );

				// or use the user_nicename
				if ( ! $nickname || $invalid ) {
					$nickname = $user->user_nicename;
				}
				xprofile_set_field_data( $nickname_id, $user_id, $nickname );
			}

			break;
		case 'first_last_name':
			// Get First Name Field Id.
			$first_name_id = (int) bp_get_option( 'bp-xprofile-firstname-field-id' );
			// Get Last Name Field Id.
			$last_name_id      = (int) bp_get_option( 'bp-xprofile-lastname-field-id' );
			$result_first_name = xprofile_get_field_data( $first_name_id, $user_id );
			$result_last_name  = xprofile_get_field_data( $last_name_id, $user_id );

			if ( '' === $result_first_name ) {
				$result_first_name = get_user_meta( $user_id, 'first_name', true );
				if ( empty( $result_first_name ) ) {
					$result_first_name = get_user_meta( $user_id, 'nickname', true );
				}
				xprofile_set_field_data( $first_name_id, $user_id, $result_first_name );
			}

			if ( '' === $result_last_name ) {
				$result_last_name = get_user_meta( $user_id, 'last_name', true );
				xprofile_set_field_data( $last_name_id, $user_id, $result_last_name );
			}

			$display_name = implode(
				' ',
				array_filter(
					array(
						isset( $result_first_name ) ? $result_first_name : '',
						isset( $result_last_name ) ? $result_last_name : '',
					)
				)
			);

			// Get Nick Name Field Id.
			$nickname_id = (int) bp_get_option( 'bp-xprofile-nickname-field-id' );
			$nick_name   = xprofile_get_field_data( $nickname_id, $user_id );

			if ( '' === trim( $nick_name ) ) {
				$user = get_userdata( $user_id );
				// make sure nickname is valid
				$nickname = get_user_meta( $user_id, 'nickname', true );
				$nickname = sanitize_title( $nickname );
				$invalid  = bp_xprofile_validate_nickname_value( '', $nickname_id, $nickname, $user_id );

				// or use the user_nicename
				if ( ! $nickname || $invalid ) {
					$nickname = $user->user_nicename;
				}
				xprofile_set_field_data( $nickname_id, $user_id, $nickname );
			}

			break;
		case 'nickname':
			// Get Nick Name Field Id.
			$nickname_id  = (int) bp_get_option( 'bp-xprofile-nickname-field-id' );
			$display_name = xprofile_get_field_data( $nickname_id, $user_id );

			if ( '' === trim( $display_name ) ) {
				$user = get_userdata( $user_id );
				// make sure nickname is valid
				$nickname = get_user_meta( $user_id, 'nickname', true );
				$nickname = sanitize_title( $nickname );
				$invalid  = bp_xprofile_validate_nickname_value( '', $nickname_id, $nickname, $user_id );

				// or use the user_nicename
				if ( ! $nickname || $invalid ) {
					$nickname = $user->user_nicename;
				}
				xprofile_set_field_data( $nickname_id, $user_id, $nickname );
				$display_name = $nickname;

			}
			break;
	}

	return apply_filters( 'bp_xprofile_get_member_display_name', trim( $display_name ), $user_id );
}

