<?php

class GPNF_Feed_Processing {

    private static $instance = null;

    public static function get_instance() {
        if( null == self::$instance ) {
	        self::$instance = new self;
        }
        return self::$instance;
    }

	private function __construct() {

		add_filter( 'gform_addon_pre_process_feeds', array( $this, 'pre_process_feeds' ), 10, 3 );
		add_filter( 'gform_entry_created', array( $this, 'process_feeds' ), 10, 2 );

	}

	public function pre_process_feeds( $feeds, $entry, $form ) {

		$addon_slug = rgars( $feeds, '0/addon_slug' );
		if( ! $addon_slug ) {
			return $feeds;
		}

		$is_filtered = false;
		$filtered = array();

		// Check if this is a nested form submission.
		if( gp_nested_forms()->is_nested_form_submission() ) {

			$is_filtered       = true;
			$parent_form       = GFAPI::get_form( gp_nested_forms()->get_parent_form_id() );
			$nested_form_field = gp_nested_forms()->get_posted_nested_form_field( $parent_form );

			// Allow each feed's processing to be determined individually.
			foreach( $feeds as $feed ) {
				if( $this->should_process_feed( 'child', $feed, $parent_form, $nested_form_field, $addon_slug, $entry, $form ) ) {
					$filtered[] = $feed;
				}
			}

		}
		// Check if we are pre-processing feeds from a parent form submission for a nested entry.
		else if( ! empty( $this->_parent_form_data ) ) {

			$is_filtered         = true;
			$parent_form         = $this->_parent_form_data['form'];
			$nested_form_field   = $this->_parent_form_data['field'];

			// Allow each feed's processing to be determined individually.
			foreach( $feeds as $feed ) {
				if( $this->should_process_feed( 'parent', $feed, $parent_form, $nested_form_field, $addon_slug, $entry, $form ) ) {
					$filtered[] = $feed;
				}
			}

		}

		return $is_filtered ? $filtered : $feeds;
	}

	public function process_feeds( $entry, $form ) {

		// No need to process feeds for nested form submissions; GF will handle that for us.
		if( gp_nested_forms()->is_nested_form_submission() ) {
			return;
		}

		// If this form has any Nested Form fields, let's find them and *maybe* process feeds for each entry in each field.
		if( gp_nested_forms()->has_nested_form_field( $form ) ) {

			foreach( $form['fields'] as $field ) {

				if( $field->type != 'form' ) {
					continue;
				}

				$nested_entry_ids = gp_nested_forms()->get_field_value( $form, $entry, $field->id );
				if( empty( $nested_entry_ids ) ) {
					continue;
				}

				$addons         = GFAddon::get_registered_addons();
				$nested_entries = gp_nested_forms()->get_entries( $nested_entry_ids );
				$nested_form    = GFAPI::get_form( $field->gpnfForm );

				foreach( $addons as $addon ) {
					$addon = call_user_func( array( $addon, 'get_instance' ) );
					if( $addon instanceof GFFeedAddOn ) {
						foreach( $nested_entries as $nested_entry ) {
							$this->_parent_form_data = compact( 'form', 'field' );
							$addon->maybe_process_feed( $nested_entry, $nested_form );
							$this->_parent_form_data = false;
						}
					}
				}

			}

		}

	}

	public function should_process_feed( $context, $feed, $parent_form, $nested_form_field, $addon_slug, $entry, $form ) {

		// Use the field setting by default (passed as $context); allow overriding with the filter below.
		$field_setting       = empty( $nested_form_field->gpnfFeedProcessing ) ? 'parent' : $nested_form_field->gpnfFeedProcessing;
		$should_process_feed = $field_setting == $context;

    	/**
		 * Indicate whether a feed should be processed by context (parent or child submission).
	     *
	     * @since 1.0
	     *
	     * @param bool   $should_process_feed Whether the feed should processed for the given context. Compares the context with the $field->gpnfFeedProcessing setting for default evaluation.
	     * @param array  $feed                The current feed.
	     * @param string $context             The current context for which feeds are being processed; 'parent' is a parent form submission; 'child' is a nested form submission.
	     * @param array  $parent_form         The parent form object.
	     * @param array  $nested_form_field   The field object of the Nested Form field.
	     * @param array  $entry               The current entry for which feeds are being processed.
		 */
		$should_process_feed = gf_apply_filters( array( 'gpnf_should_process_feed',               $parent_form['id'], $nested_form_field->id ), $should_process_feed, $feed, $context, $parent_form, $nested_form_field, $entry );
		$should_process_feed = gf_apply_filters( array( "gpnf_should_process_{$addon_slug}_feed", $parent_form['id'], $nested_form_field->id ), $should_process_feed, $feed, $context, $parent_form, $nested_form_field, $entry );

		return $should_process_feed;
	}

}

function gpnf_feed_processing() {
    return GPNF_Feed_Processing::get_instance();
}
