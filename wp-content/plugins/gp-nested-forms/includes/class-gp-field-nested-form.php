<?php

class GP_Field_Nested_Form extends GF_Field {

	public $type = 'form';

	public function get_form_editor_field_title() {
		return esc_attr__( 'Nested Form', 'gp-nested-forms' );
	}

	public function get_form_editor_button() {
		return array(
			'group' => 'advanced_fields',
			'text'  => $this->get_form_editor_field_title()
		);
	}

	public function get_form_editor_field_settings() {
		return array(
			'conditional_logic_field_setting',
			'prepopulate_field_setting',
			'error_message_setting',
			'label_setting',
			'admin_label_setting',
			'visibility_setting',
			'description_setting',
			'css_class_setting',
			'gpnf-setting',
			'gpnf-modal-header-color-setting',
			'gpnf-entry-limits-setting',
			'gpnf-feed-processing-setting',
		);
	}

    public function get_field_input( $form, $value = '', $entry = null ) {

		if( $this->is_entry_detail_edit() ) {
			ob_start();
			?>
			<div class="ginput_container gpnf-no-edit">
				<?php esc_html_e( 'Nested Form fields cannot be edited. Edit the child entry to make changes.', 'gp-nested-forms' ); ?>
				<input name="input_<?php echo $this->id; ?>" type="hidden" value="<?php echo $value; ?>" />
			</div>
			<?php
			return ob_get_clean();
		}

		$nested_form_id   = rgar( $this, 'gpnfForm' );
		$nested_form      = GFAPI::get_form( $nested_form_id );
		$nested_field_ids = rgar( $this, 'gpnfFields' );
		$column_count     = count( $nested_field_ids ) + 1; // + 1 for actions column

		if( ! $nested_field_ids && ! $this->is_form_editor() ) {
			return '<div class="gpnf-nested-entries-container ginput_container">' .
			       __( 'Please select a form and fields to display in this Nested Form field.', 'gp-nested-forms' )
				. '</div>';
		}

		// Get existing entries.
		$entries = array();
		if( ! empty( $value ) ) {
			$entries = gp_nested_forms()->get_entries( $value );
		}

		$tabindex = GFCommon::get_tabindex();
	    $add_button_label = sprintf( __( 'Add %s', 'gp-nested-forms' ), $this->get_item_label() );

		// return parsed template content
		$args = array(
			'template'         => 'nested-entries',
			'field'            => $this,
			'nested_form'      => $nested_form,
			'nested_fields'    => ! empty( $nested_form ) ? gp_nested_forms()->get_fields_by_ids( $nested_field_ids, $nested_form ) : array(),
			'nested_field_ids' => $nested_field_ids,
			'column_count'     => $column_count,
			'value'            => $value,
			'entries'          => $entries,
			'add_button'       => $this->get_add_button( $form['id'], $nested_form['id'], $tabindex, $add_button_label ),
			'tabindex'         => $tabindex,
			'labels' => array(
				'no_entries'   => sprintf( __( 'There are no %s%s.%s', 'gp-nested-forms' ), '<span>', $this->get_items_label(), '</span>' ),
				'add_entry'    => $add_button_label,
				'edit_entry'   => __( 'Edit', 'gp-nested-forms' ),
				'delete_entry' => __( 'Delete', 'gp-nested-forms' ),
			),
		);

	    /**
	     * Filter the arguments that will be used to render the Nested Form field template.
	     *
	     * @since 1.0
	     *
	     * @param array $args {
	     *
	     *     @var string               $template             The template file to be rendered.
	     *     @var GP_Field_Nested_Form $field                The current Nested Form field.
	     *     @var array                $nested_form          The nested form object for the current Nested Form field.
	     *     @var array                $nested_fields        Any array of field objects on the nested form that will be displayed on the parent form.
	     *     @var array                $nested_field_ids     The field IDs from the nested form that will be displayed on the parent form.
	     *     @var string               $value                A comma-delimited list of entry IDs that have been submitted for this Nested Form field and session.
	     *     @var array                $entries              An array of entries that have been submitted for this Nested Form field and session.
	     *     @var string               $add_button           An HTML button that allows users to load the nested form modal and add new entries to the Nested Form field.
	     *     @var int                  $column_count         The number of columns (based on $nested_field_ids count plus one for the actions column).
	     *     @var string               $tabindex             The Gravity Forms tabindex string, if tabindex is enabled.
	     *     @var string               $related_entries_link An HTML link to the Entry List view, filtered by entries for the current Nested Form field and parent entry.
	     *     @var array                $labels               An array of miscellaneous UI labels that will be used when rendering the template ('no_entries', 'add_entry', 'edit_entry', 'delete_entry').
	     *
	     * }
	     *
	     * @param GP_Field_Nested_Form $field The current Nested Form field
	     */
	    $args = gf_apply_filters( array( 'gpnf_template_args', $this->formId, $this->id ), $args, $this );

	    // Update 'add_button' if 'add_entry' label is changed via filter.
	    if( $args['labels']['add_entry'] != $add_button_label ) {
		    $args['add_button'] = $this->get_add_button( $form['id'], $nested_form['id'], $tabindex, $args['labels']['add_entry'] );
	    }

		$template = new GP_Template( gp_nested_forms() );
		$markup = $template->parse_template( array(
			sprintf( '%s-%s-%s.php', $args['template'], $form['id'], $this->id ),
			sprintf( '%s-%s.php', $args['template'], $form['id'] ),
			sprintf( '%s.php', $args['template'] ),
		), true, false, $args );

		// Apppend the input that is actual used to interact with Gravity Forms.
	    $markup .= sprintf(
            '<input type="hidden"
                name="input_%d"
                id="input_%d_%d"
                data-bind="value: entryIds"
                value="%s" />',
		    $this->id, $this->formId, $this->id, $value
	    );

        return $markup;
	}

	public function get_add_button( $form_id, $nested_form_id, $tabindex, $label ) {
		return sprintf( '
			<button type="button" class="gpnf-add-entry"
		        data-formid="%s"
		        data-nestedformid="%s"
				data-bind="attr: { disabled: isMaxed }"
				%s>
				%s
			</button>',
			$form_id, $nested_form_id, $tabindex, $label
		);
	}

	public function get_value_entry_list( $value, $entry, $field_id, $columns, $form ) {

		$entry_ids   = array_filter( explode( ',', $value ) );
		$entry_count = count( $entry_ids );
		$items_label = $this->get_items_label();

		return $entry_count . ' ' . strtolower( $items_label );
	}

    public function get_value_entry_detail( $value, $currency = '', $use_text = false, $format = 'html', $media = 'screen' ) {

		$entries = gp_nested_forms()->get_entries( $value );
		if( empty( $entries ) ) {
			return $value;
		}

		$nested_form_id   = rgar( $this, 'gpnfForm' );
		$nested_form      = GFAPI::get_form( $nested_form_id );
		$nested_field_ids = rgar( $this, 'gpnfFields' );
		$column_count     = count( $nested_field_ids ) + 1; // + 1 for actions column

		if( ! $nested_field_ids ) {
			return '';
		}

		$related_entries_link  = sprintf(
			'<a class="gpnf-related-entries-link" href="%s">%s</a>',
			add_query_arg( array(
				'page' => 'gf_entries',
				'id'   => $nested_form['id'],
				GPNF_Entry::ENTRY_PARENT_KEY            => rgget( 'lid' ),
				GPNF_Entry::ENTRY_NESTED_FORM_FIELD_KEY => $this->id,
			), admin_url( 'admin.php' ) ),
			sprintf( __( 'View Expanded %s List', 'gp-nested-forms' ), $this->get_item_label() )
		);

		// return parsed template content
		$args = array(
			'template'             => 'nested-entries-detail',
			'field'                => $this,
			'nested_form'          => $nested_form,
			'nested_fields'        => gp_nested_forms()->get_fields_by_ids( $nested_field_ids, $nested_form ),
			'nested_field_ids'     => $nested_field_ids,
			'value'                => $value,
			'entries'              => $entries,
			'column_count'         => $column_count,
			'related_entries_link' => $related_entries_link,
			'labels' => array(
				'view_entry' => __( 'View Entry', 'gp-nested-forms' )
			)
		);

		/**
		 * Filter the arguments that will be used to render the Nested Form field template.
		 *
		 * @since 1.0
		 *
		 * @param array $args {
		 *
		 *     @var string               $template             The template file to be rendered.
		 *     @var GP_Field_Nested_Form $field                The current Nested Form field.
		 *     @var array                $nested_form          The nested form object for the current Nested Form field.
		 *     @var array                $nested_fields        Any array of field objects on the nested form that will be displayed on the parent entry.
		 *     @var array                $nested_field_ids     The field IDs from the nested form that will be displayed on the parent entry.
		 *     @var string               $value                A comma-delimited list of entry IDs that have been submitted for this Nested Form field and parent entry.
		 *     @var array                $entries              An array of entries that have been submitted for this Nested Form field and parent entry.
		 *     @var int                  $column_count         The number of columns (based on $nested_field_ids count plus one for the actions column).
		 *     @var string               $related_entries_link An HTML link to the Entry List view, filtered by entries for the current Nested Form field and parent entry.
		 *     @var array                $labels               An array of miscellaneous UI labels that will be used when rendering the template.
		 *
		 * }
		 *
		 * @param GP_Field_Nested_Form $field The current Nested Form field
		 */
		$args = gf_apply_filters( array( 'gpnf_template_args', $this->formId, $this->id ), $args, $this );

	    $template = new GP_Template( gp_nested_forms() );
	    $markup = $template->parse_template( array(
		    sprintf( '%s-%s-%s.php', $args['template'], $this->form_id, $this->id ),
		    sprintf( '%s-%s.php', $args['template'], $this->form_id ),
		    sprintf( '%s.php', $args['template'] ),
	    ), true, false, $args );

		// Apppend the input that is actual used to interact with Gravity Forms.
	    $markup .= sprintf(
		    '<input type="hidden"
                name="input_%d"
                id="input_%d_%d"
                value="%s" />',
		    $this->id, $this->formId, $this->id, $value
	    );

		return $markup;
	}

	public function get_item_labels() {

		$item_labels = wp_parse_args( array_filter( array(
			'singular' => $this->gpnfEntryLabelSingular,
			'plural'   => $this->gpnfEntryLabelPlural,
		) ), array(
			'singular' => __( 'Entry', 'gp-nested-forms' ),
			'plural'   => __( 'Entries', 'gp-nested-forms' ),
		) );

		/**
		 * Filter the label used to identify entries in a Nested Form field.
		 *
		 * @since 1.0
		 *
		 * @param $item_labels {
		 *
		 *     @var string $singular Label used to identify a single entry (e.g. Car).
		 *     @var string $plural   Label used to identify more than one entry (e.g. Cars).
		 *
		 * }
		 */
		$item_labels = gf_apply_filters( array( 'gpnf_item_labels', $this->formId, $this->id ), $item_labels );

		return $item_labels;
	}

	public function get_item_label() {
		return rgar( $this->get_item_labels(), 'singular' );
	}

	public function get_items_label() {
		return rgar( $this->get_item_labels(), 'plural' );
	}

	public function validate( $value, $form ) {

		if( empty( $value ) ) {
			$entry_count = 0;
		} else {
			$entry_ids   = explode(',', $value);
			$entry_count = count( $entry_ids );
		}

		$minimum = $this->gpnfEntryLimitMin;
		if( ! rgblank( $minimum ) && $entry_count < $minimum ) {
			$this->failed_validation  = true;
			$this->validation_message = sprintf( __( 'Please enter a minimum of %d %s', 'gp-nested-forms' ), $minimum, $minimum > 1 ? $this->get_items_label() : $this->get_item_label() );
		}

		$maximum = $this->gpnfEntryLimitMax;
		if( ! rgblank( $maximum ) && $entry_count > $maximum ) {
			$this->failed_validation  = true;
			$this->validation_message = sprintf( __( 'Please enter a maximum of %d %s', 'gp-nested-forms' ), $maximum, $maximum > 1 ? $this->get_items_label() : $this->get_item_label() );
		}

	}

}

class GP_Nested_Form_Field extends GP_Field_Nested_Form { }

GF_Fields::register( new GP_Field_Nested_Form() );
