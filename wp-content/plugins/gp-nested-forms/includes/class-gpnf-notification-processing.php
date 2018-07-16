<?php

class GPNF_Notification_Processing {

	private static $instance = null;

	public static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	private function __construct() {

		add_filter( 'gform_disable_notification', array( $this, 'should_disable_notification' ), 10, 4 );
		add_action( 'gform_entry_created', array( $this, 'maybe_send_child_notifications' ), 10, 2 );

	}

	public function should_disable_notification( $value, $notification, $form, $entry ) {

		if ( gp_nested_forms()->is_nested_form_submission() ) {
			$parent_form       = GFAPI::get_form( gp_nested_forms()->get_parent_form_id() );
			$nested_form_field = gp_nested_forms()->get_posted_nested_form_field( $parent_form );

			return ! $this->should_send_notification( 'child', $notification, $parent_form, $nested_form_field, $entry, $form );
		} else if ( $parent_form_id = rgar( $entry, GPNF_Entry::ENTRY_PARENT_FORM_KEY ) ) {
			$parent_form       = GFAPI::get_form( $parent_form_id );
			$nested_form_field = GFFormsModel::get_field( $parent_form, rgar( $entry, GPNF_Entry::ENTRY_NESTED_FORM_FIELD_KEY ) );

			return ! $this->should_send_notification( 'parent', $notification, $parent_form, $nested_form_field, $entry, $form );
		}

		return $value;

	}

	public function maybe_send_child_notifications( $parent_entry, $form ) {

		if ( ! gp_nested_forms()->has_nested_form_field( $form ) ) {
			return;
		}

		$parent_entry = new GPNF_Entry( $parent_entry );
		if ( ! $parent_entry->has_children() ) {
			return;
		}

		$child_entries = $parent_entry->get_child_entries();
		if ( ! $child_entries ) {
			return;
		}

		foreach ( $child_entries as $child_entry ) {
			$child_form = GFAPI::get_form( $child_entry['form_id'] );

			GFCommon::send_form_submission_notifications( $child_form, $child_entry );
		}

	}

	public function should_send_notification( $context, $notification, $parent_form, $nested_form_field, $entry, $child_form ) {

		$should_send_notification = $context === 'parent';

		/**
		 * Indicate whether a notification should be sent by context (parent or child submission).
		 *
		 * @since 1.0-beta-4.10
		 *
		 * @param bool   $should_send_notification Whether the notification should be sent for the given context.
		 * @param array  $notification             The notification object.
		 * @param string $context                  The current context for which notifications are being processed; 'parent' is a parent form submission; 'child' is a nested form submission.
		 * @param array  $parent_form              The parent form object.
		 * @param array  $nested_form_field        The field object of the Nested Form field.
		 * @param array  $entry                    The current entry for which feeds are being processed.
		 * @param array  $child_form               The child form object.
		 */
		$should_send_notification = gf_apply_filters( array(
			'gpnf_should_send_notification',
			$parent_form['id'],
			$nested_form_field->id
		), $should_send_notification, $notification, $context, $parent_form, $nested_form_field, $entry, $child_form );

		return $should_send_notification;
	}

}

function gpnf_notification_processing() {
	return GPNF_Notification_Processing::get_instance();
}
