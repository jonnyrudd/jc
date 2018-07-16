<?php

if( ! class_exists( 'GP_Plugin' ) ) {
	return;
}

class GP_Nested_Forms extends GP_Plugin {

	protected $_version   = GP_NESTED_FORMS_VERSION;
	protected $_path      = 'gp-nested-forms/gp-nested-forms.php';
	protected $_full_path = __FILE__;
	protected $_slug      = 'gp-nested-forms';

	protected $min_gravity_perks_version = '2.0-beta-1';
	protected $min_gravity_forms_version = '2.3-beta-1';
	protected $min_wp_version            = '4.9';

	public $parent_form_id = null;
	public $field_type = 'form';

	public static $nested_forms_markup = array();

	private $_session_queue = array();

	private static $instance = null;

	public static function get_instance() {

		if( self::$instance == null ) {
			self::includes();
			self::$instance = isset ( self::$perk_class ) ? new self ( new self::$perk_class ) : new self();
		}

		return self::$instance;
	}

	public static function includes() { }

	public function minimum_requirements() {
		return array(
			'gravityforms' => array(
				'version' => '2.3-beta-1',
			),
			'wordpress' => array(
				'version' => '4.9'
			),
			'plugins' => array(
				'gravityperks/gravityperks.php' => array(
					'name' => 'Gravity Perks',
					'version' => '2.0',
				),
			),
		);
	}

	public function pre_init() {

		parent::pre_init();

		$this->setup_cron();

		require_once( 'includes/class-gp-template.php' );
		require_once( 'includes/class-gp-field-nested-form.php' );
		require_once( 'includes/class-gpnf-feed-processing.php' );
		require_once( 'includes/class-gpnf-notification-processing.php' );
		require_once( 'includes/class-gpnf-zapier.php' );
		require_once( 'includes/class-gpnf-entry.php' );
		require_once( 'includes/class-gpnf-session.php' );

	}

	public function init() {

		parent::init();

		load_plugin_textdomain( 'gp-nested-forms', false, basename( dirname( __file__ ) ) . '/languages/' );

		// Initialize feed processing.
		gpnf_feed_processing();
		gpnf_notification_processing();
		gpnf_zapier();

		// General Hooks
		add_action( 'gform_pre_validation',      array( $this, 'maybe_load_nested_form_hooks' ) );
		add_action( 'gform_pre_render',          array( $this, 'maybe_load_nested_form_hooks' ) );
		add_filter( 'gform_entry_meta',          array( $this, 'register_entry_meta' ) );
		add_filter( 'gform_merge_tag_filter',    array( $this, 'all_fields_value' ), 11, 5 );
		add_action( 'gform_calculation_formula', array( $this, 'process_merge_tags' ), 10, 4 );
		add_filter( 'gform_custom_merge_tags',   array( $this, 'add_nested_form_field_total_merge_tag' ), 10, 4 );

		// Handle parent form.
		add_filter( 'gform_get_form_filter',       array( $this, 'handle_event_handler' ), 10, 2 );
		add_action( 'gform_register_init_scripts', array( $this, 'register_all_form_init_scripts' ) );
		add_filter( 'gform_get_form_filter',       array( $this, 'queue_session_script' ), 10, 2 );
		add_action( 'gform_entry_created',         array( $this, 'handle_parent_submission' ), 10, 2 );

		// Handle nested form.
		add_action( 'gform_get_form_filter',        array( $this, 'handle_nested_forms_markup' ), 10, 2 );
		add_filter( 'gform_confirmation',           array( $this, 'handle_nested_confirmation' ), 10, 3 );
		add_filter( 'gform_confirmation_anchor',    array( $this, 'handle_nested_confirmation_anchor' ) );
		add_action( 'gform_entry_id_pre_save_lead', array( $this, 'maybe_edit_entry' ), 10, 2 );

		// Administrative hooks.
		// Trash child entries when a parent entry is trashed or deleted.
		add_action( 'gform_update_status', array( $this, 'child_entry_trash_manage' ), 10, 3 );
		// Filter child entries by parent entry ID in the List View.
		add_filter( 'gform_get_entries_args_entry_list', array( $this, 'filter_entry_list' ) );
		// Add support for processing nested forms in Gravity Forms preview.
		add_action( 'wp', array( $this, 'handle_core_preview_ajax' ), 9 );

		// Integrations.
		add_filter( 'gform_webhooks_request_data', array( $this, 'add_full_child_entry_data_for_webhooks' ), 10, 4 );

	}

	public function init_admin() {

		parent::init_admin();

		add_filter( 'gform_admin_pre_render', array( $this, 'cleanup_form_meta' ) );

		// Field Settings
		add_action( 'gform_field_standard_settings_1430',  array( $this, 'editor_field_standard_settings' ) );
		add_action( 'gform_field_appearance_settings_500', array( $this, 'editor_field_appearance_settings' ) );
		add_action( 'gform_field_advanced_settings_400',   array( $this, 'editor_field_advanced_settings' ) );

	}

	public function init_ajax() {

		parent::init_ajax();

		// AJAX
		add_action( 'wp_ajax_gpnf_get_form_fields',        array( $this, 'ajax_get_form_fields' ) );
		add_action( 'wp_ajax_nopriv_gpnf_get_form_fields', array( $this, 'ajax_get_form_fields' ) );
		add_action( 'wp_ajax_gpnf_delete_entry',           array( $this, 'ajax_delete_entry' ) );
		add_action( 'wp_ajax_nopriv_gpnf_delete_entry',    array( $this, 'ajax_delete_entry' ) );
		add_action( 'wp_ajax_gpnf_edit_entry',             array( $this, 'ajax_edit_entry' ) );
		add_action( 'wp_ajax_nopriv_gpnf_edit_entry',      array( $this, 'ajax_edit_entry' ) );
		add_action( 'wp_ajax_gpnf_refresh_markup',         array( $this, 'ajax_refresh_markup' ) );
		add_action( 'wp_ajax_nopriv_gpnf_refresh_markup',  array( $this, 'ajax_refresh_markup' ) );
		add_action( 'wp_ajax_gpnf_session',                array( $this, 'ajax_session' ) );
		add_action( 'wp_ajax_nopriv_gpnf_session',         array( $this, 'ajax_session' ) );

	}

	public function upgrade( $previous_version ) {
		global $wpdb;

		if( version_compare( $previous_version, '1.0-beta-5', '<' ) ) {

			// Delete expiration meta key from entries that have a valid parent entry ID.
			$sql = $wpdb->prepare( "
				DELETE em1 FROM {$wpdb->prefix}gf_entry_meta em1
				INNER JOIN {$wpdb->prefix}gf_entry_meta em2 ON em2.entry_id = em1.entry_id
				WHERE em1.meta_key = '%s'
				AND em2.meta_key = '%s' 
				AND concat( '', em2.meta_value * 1 ) = em2.meta_value",
				GPNF_Entry::ENTRY_EXP_KEY, GPNF_Entry::ENTRY_PARENT_KEY
			);

			$wpdb->query( $sql );

		}

	}

	public function tooltips( $tooltips ) {

		$template = '<h6>%s</h6> %s';

		$tooltips['gpnf_form']               = sprintf( $template, __( 'Nested Form', 'gp-nested-forms' ),        __( 'Select the form that should be used to create nested entries for this form.', 'gp-nested-forms' ) );
		$tooltips['gpnf_fields']             = sprintf( $template, __( 'Display Fields', 'gp-nested-forms' ),     __( 'Select which fields should be displayed on the current form from the nested entry.', 'gp-nested-forms' ) );
		$tooltips['gpnf_entry_labels']       = sprintf( $template, __( 'Entry Labels', 'gp-nested-forms' ),       __( 'Specify a singular and plural label with which entries submitted via this field will be labeled (i.e. "employee", "employees").', 'gp-nested-forms' ) );
		$tooltips['gpnf_entry_limits']       = sprintf( $template, __( 'Entry Limits', 'gp-nested-forms' ),       __( 'Specify the minimum and maximum number of entries that can be submitted for this field.', 'gp-nested-forms' ) );
		$tooltips['gpnf_feed_processing']    = sprintf( $template, __( 'Feed Processing', 'gp-nested-forms' ),    __( 'By default, any Gravity Forms add-on feeds will be processed immediately when the nested form is submitted. Use this option to delay feed processing for entries submitted via the nested form until after the parent form is submitted. <br><br>For example, if you have a User Registration feed configured for the nested form, you may not want the users to actually be registered until the parent form is submitted.', 'gp-nested-forms' ) );
		$tooltips['gpnf_modal_header_color'] = sprintf( $template, __( 'Modal Header Color', 'gp-nested-forms' ), __( 'Select a color which will be used to set the background color of the nested form modal header.', 'gp-nested-forms' ) );

		return $tooltips;
	}

	public function scripts() {

		$scripts = array(
			array(
				'handle'  => 'select2',
				'src'     => $this->get_base_url() . '/js/select2.min.js',
				'version' => '4.0.3',
				'enqueue' => null,
			),
			array(
				'handle'  => 'gp-nested-forms-admin',
				'src'     => $this->get_base_url() . '/js/gp-nested-forms-admin.js',
				'version' => $this->_version,
				'deps'    => array( 'jquery', 'select2' ),
				'enqueue' => array(
					array( 'admin_page' => array( 'form_editor' ) ),
				),
				'callback' => array( $this, 'localize_scripts' ),
			),
			array(
				'handle'  => 'knockout',
				'src'     => $this->get_base_url() . '/js/knockout-3.4.2.js',
				'version' => $this->_version,
				'enqueue' => null,
			),
			array(
				'handle'  => 'gp-nested-forms',
				'src'     => $this->get_base_url() . '/js/gp-nested-forms.js',
				'version' => $this->_version,
				'deps'    => array( 'jquery', 'knockout', 'jquery-ui-dialog', 'gform_gravityforms' ),
				'enqueue' => array(
					array( $this, 'should_enqueue_frontend_script' ),
				),
				'callback' => array( $this, 'localize_scripts' ),
			),
		);

		return array_merge( parent::scripts(), $scripts );
	}

	public function styles() {

		$styles = array(
			array(
				'handle'  => 'select2',
				'src'     => $this->get_base_url() . '/css/select2.min.css',
				'version' => '4.0.3',
				'enqueue' => array(
					array( 'admin_page' => array( 'form_editor' ) ),
				)
			),
			array(
				'handle'  => 'gp-nested-forms-admin',
				'src'     => $this->get_base_url() . '/css/gp-nested-forms-admin.css',
				'version' => $this->_version,
				'deps'    => array( 'select2' ),
				'enqueue' => array(
					array( 'admin_page' => array( 'form_editor', 'entry_view', 'entry_edit' ) ),
				)
			),
			// Dependency on this has been removed; however, it is still useful for testing to make sure custom styles
			// will override any 3rd-party inclusion of a jQuery UI theme.
//			array(
//				'handle'  => 'gp-nested-forms-jquery-ui',
//				'src'     => $this->get_base_url() . '/css/gp-nested-forms-jquery-ui.css',
//				'version' => $this->_version,
//				'enqueue' => array(
//					array( $this, 'should_enqueue_frontend_script' ),
//				)
//			),
			array(
				'handle'  => 'gp-nested-forms',
				'src'     => $this->get_base_url() . '/css/gp-nested-forms.css',
				'version' => $this->_version,
				'enqueue' => array(
					array( $this, 'should_enqueue_frontend_script' ),
				)
			)
		);

		return array_merge( parent::styles(), $styles );
	}

	public function should_enqueue_frontend_script( $form ) {
		return ! GFForms::get_page() && ! rgempty( GFFormsModel::get_fields_by_type( $form, array( 'form' ) ) );
	}

	public function localize_scripts() {

		wp_localize_script( 'gp-nested-forms-admin', 'GPNFAdminData', array(
			'nonces' => array(
				'getFormFields' => wp_create_nonce( 'gpnf_get_form_fields' ),
			),
			'strings' => array(
				'getFormFieldsError'       => esc_html__( 'There was an error retrieving the fields for this form. Please try again or contact support.', 'gp-nested-forms' ),
				'displayFieldsPlaceholder' => esc_html__( 'Select your fields', 'gp-nested-forms' ),
			),
		) );

		wp_localize_script( 'gp-nested-forms', 'GPNFData', array(
			'nonces' => array(
				'editEntry'   => wp_create_nonce( 'gpnf_edit_entry' ),
				'refreshMarkup'   => wp_create_nonce( 'gpnf_refresh_markup' ),
				'deleteEntry' => wp_create_nonce( 'gpnf_delete_entry' ),
			),
			'strings' => array(),
		) );

	}

	public function setup_cron() {

		if ( ! wp_next_scheduled( 'gpnf_daily_cron' ) ) {
			wp_schedule_event( time(), 'daily', 'gpnf_daily_cron' );
		}

		add_action( 'gpnf_daily_cron', array( $this, 'daily_cron' ) );

	}

	public function daily_cron() {

		$expired = $this->get_expired_entries();

		foreach( $expired as $entry_id ) {

			// Move expired entries to the trash. Gravity Forms will handle deleting them from there.
			GFFormsModel::update_lead_property( $entry_id, 'status', 'trash' );

			// Remove expiration meta so this entry will never "expire" again.
			$entry = new GPNF_Entry( $entry_id );
			$entry->delete_expiration();

		}

	}

	public function get_expired_entries() {
		global $wpdb;

		$sql = $wpdb->prepare( "SELECT entry_id FROM {$wpdb->prefix}gf_entry_meta WHERE meta_key = %s and meta_value < %d", GPNF_Entry::ENTRY_EXP_KEY, strtotime( sprintf( '-%d seconds', GPNF_Entry::get_expiration_modifier() ) ) );
		$entry_ids = wp_list_pluck( $wpdb->get_results( $sql ), 'entry_id' );

		return $entry_ids;
	}

	public function handle_core_preview_ajax() {
		if( rgget( 'gf_page' ) == 'preview' && $this->is_nested_form_submission() && class_exists( 'GFFormDisplay' ) && ! empty( GFFormDisplay::$submission ) ) {
			echo GFForms::get_form( rgpost( 'gform_submit' ), true, true, true, null, true );
			exit;
		}
	}

	public function filter_entry_list( $args ) {

		$parent_entry_id      = rgget( GPNF_Entry::ENTRY_PARENT_KEY );
		$nested_form_field_id = rgget( GPNF_Entry::ENTRY_NESTED_FORM_FIELD_KEY );

		if( ! $parent_entry_id || ! $nested_form_field_id) {
			return $args;
		}

		// set field filters if not already set
		if( ! isset( $args['search_criteria']['field_filters'] ) ) {
			$args['search_criteria']['field_filters'] = array();
		}

		$args['search_criteria']['field_filters'][] = array(
			'key'   => GPNF_Entry::ENTRY_PARENT_KEY,
			'value' => $parent_entry_id
		);

		$args['search_criteria']['field_filters'][] = array(
			'key'   => GPNF_Entry::ENTRY_NESTED_FORM_FIELD_KEY,
			'value' => $nested_form_field_id
		);

		return $args;
	}

	public function queue_session_script( $form_markup, $form ) {

		if( ! $this->has_nested_form_field( $form, true ) ) {
			return $form_markup;
		}

		$this->_session_queue[ $form['id'] ] = $form['id'];

		if( ! has_action( 'wp_footer', array( $this, 'output_session_scripts' ) ) ) {
			add_action( 'wp_footer', array( $this, 'output_session_scripts' ), 11 );
			add_action( 'gform_preview_footer', array( $this, 'output_session_scripts' ), 11 );
		}

		return $form_markup;
	}

	public function output_session_scripts() {
		foreach( $this->_session_queue as $form_id ) {
			echo GPNF_Session::get_session_script( $form_id );
		}
	}

	public function child_entry_trash_manage( $entry_id, $new_status, $old_status ) {

		$entry = new GPNF_Entry( $entry_id );

		if( ! $entry->has_children() ) {
			return;
		}

		// Entry is trashed, send children to trash.
		if( $new_status == 'trash' ) {
			$entry->trash_children();
		}

		// Entry is untrashed, set children to active.
		if( $old_status == 'trash' && $new_status == 'active' ) {
			$entry->untrash_children();
		}

	}

	public function child_entry_delete( $entry_id ) {

		$entry = new GPNF_Entry( $entry_id );

		if( $entry->has_children() ) {
			$entry->delete_children();
		}

	}



	// # ADMIN

	public function editor_field_standard_settings( $form_id ) {

		$forms = GFFormsModel::get_forms();

		?>

		<li class="gpnf-setting field_setting">

			<div class="gpnf-sub-setting">

				<label for="gpnf-form" class="section_label">
					<?php _e( 'Nested Form', 'gp-nested-forms' ); ?>
					<?php gform_tooltip( 'gpnf_form' ); ?>
				</label>

				<select id="gpnf-form" onchange="SetFieldProperty( 'gpnfForm', this.value ); window.gpGlobals.GPNFAdmin.toggleNestedFormFields();" class="fieldwidth-3">
					<option value=""><?php _e( 'Select a Form', 'gp-nested-forms' ); ?></option>
					<?php foreach( $forms as $form ):
						if( $form->id == $form_id )
							continue;
						?>
						<option value="<?php echo $form->id; ?>"><?php echo $form->title; ?></option>
					<?php endforeach; ?>
				</select>

			</div>

			<div id="gpnf-form-settings" class="gpnf-sub-setting" style="display:none;">

				<label for="gpnf-fields" class="section_label">
					<?php _e( 'Display Fields', 'gp-nested-forms' ); ?>
					<?php gform_tooltip( 'gpnf_fields' ); ?>
				</label>
				<select id="gpnf-fields" multiple="" class="fieldwidth-3" onchange="SetFieldProperty( 'gpnfFields', jQuery( this ).val() );">
					<!-- dynamically populated based on selection in 'form' select -->
				</select>

			</div>

			<!-- Entry Labels -->
			<div id="gpnf-entry-labels" class="gpnf-sub-setting">
				<label for="gpnf-entry-label-singular" class="section_label">
					<?php esc_html_e( 'Entry Labels', 'gp-nested-forms' ); ?>
					<?php gform_tooltip( 'gpnf_entry_labels' ); ?></label>
				<div>
					<label for="gpnf-entry-label-singular">
						<?php _e( 'Singular', 'gp-nested-forms' ); ?>
					</label>
					<input type="text" id="gpnf-entry-label-singular" placeholder="<?php esc_html_e( 'e.g. Entry', 'gp-nested-forms' ); ?>" onchange="SetFieldProperty( 'gpnfEntryLabelSingular', jQuery( this ).val() );" />
				</div>
				<div>
					<label for="gpnf-entry-label-plural">
						<?php _e( 'Plural', 'gp-nested-forms' ); ?>
					</label>
					<input type="text" id="gpnf-entry-label-plural" placeholder="<?php esc_html_e( 'e.g. Entries', 'gp-nested-forms' ); ?>" onchange="SetFieldProperty( 'gpnfEntryLabelPlural', jQuery( this ).val() );" />
				</div>
			</div>

		</li>

		<?php
	}

	public function editor_field_appearance_settings() {
		?>

		<li class="gpnf-modal-header-color-setting field_setting" style="display:none;">

			<label for="gpnf-modal-header-color" class="section_label">
				<?php _e( 'Modal Header Color', 'gp-nested-forms' ); ?>
				<?php gform_tooltip( 'gpnf_modal_header_color' ); ?>
			</label>

			<input type='text' class="iColorPicker" onchange="SetFieldProperty( 'gpnfModalHeaderColor', this.value );" id="gpnf-modal-header-color" />
			<img id="chip_gpnf-modal-header-color" height="24" width="24" src="<?php echo GFCommon::get_base_url() ?>/images/blankspace.png" />
			<img id="chooser_gpnf-modal-header-color" src="<?php echo GFCommon::get_base_url() ?>/images/color.png" />

		</li>

		<?php
	}

	public function editor_field_advanced_settings() {
		?>

		<li class="gpnf-entry-limits-setting field_setting" id="gpnf-entry-limits" style="display:none;">

			<label for="gpnf-entry-limit-min" class="section_label">
				<?php esc_html_e( 'Entry Limits', 'gp-nested-forms' ); ?>
				<?php gform_tooltip( 'gpnf_entry_limits' ); ?>
			</label>

			<div>
				<label for="gpnf-entry-limit-min">
					<?php _e( 'Minimum', 'gp-nested-forms' ); ?>
				</label>
				<input type="number" id="gpnf-entry-limit-min" placeholder="<?php esc_html_e( 'e.g. 2', 'gp-nested-forms' ); ?>" onchange="SetFieldProperty( 'gpnfEntryLimitMin', jQuery( this ).val() );" />
			</div>

			<div>
				<label for="gpnf-entry-limit-max">
					<?php _e( 'Maximum', 'gp-nested-forms' ); ?>
				</label>
				<input type="number" id="gpnf-entry-limit-max" placeholder="<?php esc_html_e( 'e.g. 5', 'gp-nested-forms' ); ?>" onchange="SetFieldProperty( 'gpnfEntryLimitMax', jQuery( this ).val() );" />
			</div>

		</li>

		<li class="gpnf-feed-processing-setting field_setting" id="gpnf-feed-processing-setting" style="display:none;">

			<label for="gpnf-feed-processing" class="section_label">
				<?php esc_html_e( 'Feed Processing', 'gp-nested-forms' ); ?>
				<?php gform_tooltip( 'gpnf_feed_processing' ); ?>
			</label>

			<span><?php esc_html_e( 'Process nested feeds when the', 'gp-nested-forms' ); ?></span>
			<select id="gpnf-feed-processing" onchange="SetFieldProperty( 'gpnfFeedProcessing', jQuery( this ).val() );">
				<option value="parent"><?php esc_html_e( 'parent form', 'gp-nested-forms' ); ?></option>
				<option value="child"><?php esc_html_e( 'nested form', 'gp-nested-forms' ); ?></option>
			</select>
			<span><?php esc_html_e( 'is submitted.', 'gp-nested-forms' ); ?></span>

		</li>

		<?php
	}



	// # GENERAL FUNCTIONALITY

	public function get_nested_forms_markup( $form ) {

		ob_start();

		foreach( $form['fields'] as $field ):

			if( $field->type != 'form' ) {
				continue;
			}

			$nested_form_id = rgar( $field, 'gpnfForm' );
			$nested_form    = GFAPI::get_form( $nested_form_id );

			if( ! $nested_form ) {
				$data = array( 'nested_field_id' => $field->id, 'nested_form_id' => $nested_form_id );
				$this->log( sprintf( $nested_form_id ? 'No nested form ID is configured for this field: %s' : 'Nested form does not exist: %s', print_r( $data, true ) ) );
				continue;
			}

			GFFormDisplay::enqueue_form_scripts( $nested_form , true );

			?>

			<div class="gpnf-nested-form gpnf-nested-form-<?php echo $form['id']; ?>-<?php echo $field['id']; ?>" style="display:none;">
				<?php

				$this->load_nested_form_hooks( $nested_form_id, $form['id'] );

				gravity_form( $nested_form_id, false, true, true, array(), true, 99999 );

				$this->unload_nested_form_hooks( $nested_form_id );

				?>
			</div>

			<div class="gpnf-edit-form gpnf-edit-form-<?php echo $form['id']; ?>-<?php echo $field['id']; ?>" style="display:none;">
				<!-- Loaded dynamically via AJAX -->
			</div>

		<?php endforeach;

		return ob_get_clean();
	}

	/**
	 * Output all queued nested forms markup.
	 */
	public static function output_nested_forms_markup() {
		foreach( self::$nested_forms_markup as $markup ) {
			echo $markup;
		}
	}

	public function register_entry_meta( $meta ) {

		$meta[ GPNF_Entry::ENTRY_PARENT_KEY ] = array(
			'label'      => esc_html__( 'Parent Entry ID', 'gp-nested-forms' ),
			'is_numeric' => true,
		);

		$meta[ GPNF_Entry::ENTRY_PARENT_FORM_KEY ] = array(
			'label'      => esc_html__( 'Parent Entry Form ID', 'gp-nested-forms' ),
			'is_numeric' => true,
		);

		$meta[ GPNF_Entry::ENTRY_NESTED_FORM_FIELD_KEY ] = array(
			'label'      => esc_html__( 'Child Form Field ID', 'gp-nested-forms' ),
			'is_numeric' => true,
		);

		return $meta;
	}

	public function process_merge_tags( $formula, $field, $form, $entry ) {

		preg_match_all( '/{[^{]*?:([0-9]+):(sum|total|count)=?([0-9]*)}/', $formula, $matches, PREG_SET_ORDER );
		foreach( $matches as $match ) {

			list( $search, $nested_form_field_id, $func, $target_field_id ) = $match;

			$nested_form_field = GFFormsModel::get_field( $form, $nested_form_field_id );
			if( ! $nested_form_field ) {
				continue;
			}

			$nested_form = GFAPI::get_form( $nested_form_field->gpnfForm );
			$replace     = '';

			$_entry = new GPNF_Entry( $entry );
			$child_entries = $_entry->get_child_entries( $nested_form_field_id );

			switch( $func ) {
				case 'sum':
					$total = 0;
					foreach( $child_entries as $child_entry ) {
						$total += (float) rgar( $child_entry, $target_field_id );
					}
					$replace = $total;
					break;
				case 'total':
					$total = 0;
					foreach( $child_entries as $child_entry ) {
						$total += (float) GFCommon::get_order_total( $nested_form, $child_entry );
					}
					$replace = $total;
					break;
				case 'count':
					$replace = count( $child_entries );
					break;
			}

			$formula = str_replace( $search, $replace, $formula );

		}

		return $formula;
	}

	public function add_nested_form_field_total_merge_tag( $merge_tags ) {
		return $merge_tags;
	}



	// # AJAX

	public function ajax_get_form_fields() {

		if( ! wp_verify_nonce( rgpost( 'nonce' ), 'gpnf_get_form_fields' ) ) {
			die( __( 'Oops! You don\'t have permission to get fields for this form.', 'gp-nested-forms' ) );
		}

		$form_id = rgpost( 'form_id' );
		$form = GFAPI::get_form( $form_id );

		wp_send_json( $form['fields'] );

	}

	public function ajax_delete_entry() {

		usleep( 500000 ); // @todo Remove!

		if( ! wp_verify_nonce( rgpost( 'nonce' ), 'gpnf_delete_entry' ) ) {
			wp_send_json_error( __( 'Oops! You don\'t have permission to delete this entry.', 'gp-nested-forms' ) );
		}

		$entry_id = $this->get_posted_entry_id();
		$result   = GFAPI::delete_entry( $entry_id );

		if( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		} else {
			wp_send_json_success();
		}

	}

	/**
	 * Fetch the form with the entry pre-populated, ready for editing.
	 */
	public function ajax_edit_entry() {

		//usleep( 500000 ); // @todo Remove!

		if( ! wp_verify_nonce( rgpost( 'nonce' ), 'gpnf_edit_entry' ) ) {
			die( __( 'Oops! You don\'t have permission to edit this entry.', 'gp-nested-forms' ) );
		}

		$entry_id = $this->get_posted_entry_id();
		$entry    = GFAPI::get_entry( $entry_id );
		$form_id  = $entry['form_id'];

		if( ! $entry ) {
			die( __( 'Oops! We can\'t locate that entry.', 'gp-nested-forms' ) );
		}

		ob_start();

		add_filter( 'gform_pre_render_' . $form_id, array( $this, 'prepare_form_for_population' ) );
		add_filter( 'gform_form_tag', array( $this, 'set_edit_form_action' ) );
		add_filter( 'gwlc_is_edit_view', '__return_true' );
		add_filter( 'gwlc_selected_values', array( $this, 'set_gwlc_selected_values' ), 20, 2 );

		$this->parent_form_id = rgpost( 'parent_form_id' );
		add_filter( 'gform_form_tag', array( $this, 'add_nested_inputs' ), 10, 2 );

		gravity_form( $form_id, false, false, false, $this->prepare_entry_for_population( $entry ), true, 9999 );

		$markup = trim( ob_get_clean() );
		wp_send_json( $markup );

	}

	public function ajax_refresh_markup() {

		if( ! wp_verify_nonce( rgpost( 'nonce' ), 'gpnf_refresh_markup' ) ) {
			die( __( 'Oops! You don\'t have permission to do this.', 'gp-nested-forms' ) );
		}

		$form_id  = rgpost( 'gpnf_parent_form_id' );
		$form   = GFAPI::get_form( $form_id );

		$nested_form_field_id  = rgpost( 'gpnf_nested_form_field_id' );
		$nested_form_field = GFFormsModel::get_field( $form, $nested_form_field_id );
		$nested_form_id = rgar( $nested_form_field, 'gpnfForm' );

		ob_start();

		$this->load_nested_form_hooks( $nested_form_id, $form_id );
		add_filter( 'gform_form_tag', array( $this, 'set_edit_form_action' ) );

		// Clear the post so Gravity Forms will use isSelected property on choice-based fields and not try to determine
		// isSelected based on posted values. I'm betting this will resolve many other unknown issues as well.
		$_POST = array();

		gravity_form( $nested_form_id, false, true, true, array(), true, 99999 );

		$this->unload_nested_form_hooks( $nested_form_id );

		$markup = trim( ob_get_clean() );
		wp_send_json( $markup );

    }

	public function ajax_session() {

		$form_id = rgpost( 'form_id' );

		$session = new GPNF_Session( $form_id );
		$session
			->set_session_data()
			->set_cookie();

		die();

	}



	// # VALUES

	public function get_entries( $entry_ids ) {

		$entries = array();

		if( empty( $entry_ids ) )
			return $entries;

		if( is_string( $entry_ids ) ) {
			$entry_ids = array_map( 'trim', explode( ',', $entry_ids ) );
		} else if( ! is_array( $entry_ids ) ) {
			$entry_ids = array( $entry_ids );
		}

		foreach( $entry_ids as $entry_id ) {
			$entry = GFAPI::get_entry( (int) $entry_id );
			if( ! is_wp_error( $entry ) ) {
				$entries[] = GFAPI::get_entry( $entry_id );
			}
		}

		return $entries;
	}

	public function get_entry_url( $entry_id, $form_id ) {
		/**
		 * Filter the URL for entry detail view per entry.
		 *
		 * @since 1.0-beta-4.16
		 *
		 * @param string $entry_url The URL to a specific entry's detail view.
		 * @param int    $entry_id  The current entry ID.
		 * @param int    $form_id   The current form ID.
		 */
		return gf_apply_filters( array( 'gpnf_entry_url', $form_id ), admin_url( "admin.php?page=gf_entries&view=entry&id={$form_id}&lid={$entry_id}" ), $entry_id, $form_id );
	}

	public function all_fields_value( $value, $merge_tag, $modifiers, $field, $raw_value ) {

		// Only process for Nested Form fields - and - if All Fields template has not filtered this field out (i.e. false).
		if( $field->type != 'form' || $value === false ) {
			return $value;
		}

		$entry_ids = array_map( 'trim', explode( ',', $raw_value ) );
		$nested_form_id = rgar( $field, 'gpnfForm' );
		$nested_form = GFAPI::get_form( $nested_form_id );

		if( ! $nested_form ) {
			$data = array( 'nested_field_id' => $field->id, 'nested_form_id' => $nested_form_id );
			$this->log( sprintf( $nested_form_id ? 'No nested form ID is configured for this field: %s' : 'Nested form does not exist: %s', print_r( $data, true ) ) );
			return $value;
		}

		//$value = gf_apply_filters( array( 'gpnf_pre_all_fields_value', $field->formId, $field->id ), '', $field, $nested_form, array(), $entry_ids, $raw_value, $modifiers );

		$values        = array();
		$is_all_fields = $merge_tag == 'all_fields';
		// Renabled for now, see bit below... no idea why I changed it...
		$modifiers     = $is_all_fields ? "context[nested],parent[{$field->id}]," . $modifiers : $modifiers;
		// This might impact how this works with All Fields Template... let's revisit when it comes up.
		//$modifiers     = "context[nested],parent[{$field->id}]";
		$template      = new GP_Template( gp_nested_forms() );

		if( $this->is_filtered_single( $modifiers, $field, $is_all_fields ) ) {
			$args = array(
				'template'    => 'nested-entries-simple-list',
				'field'       => $field,
				'nested_form' => $nested_form,
				'modifiers'   => $modifiers,
				'items'       => $this->get_simple_list_items( $entry_ids, $nested_form, $modifiers ),
			);
			$value = $template->parse_template( array(
				sprintf( '%s-%s-%s.php', $args['template'], $nested_form['id'], $field->id ),
				sprintf( '%s-%s.php', $args['template'], $nested_form['id'] ),
				sprintf( '%s.php', $args['template'] ),
			), true, false, $args );
			return $value;
		}

		$args = array(
			'template'      => 'nested-entry',
			'field'         => $field,
			'nested_form'   => $nested_form,
			'modifiers'     => $modifiers,
			'is_all_fields' => $is_all_fields,
		);

		foreach( $entry_ids as $entry_id ) {

			$entry = GFAPI::get_entry( $entry_id );
			if( is_wp_error( $entry ) ) {
				continue;
			}

			$args['entry'] = $entry;
			// Pass entry for integration with GP Preview Submission.
			$args['modifiers'] = $modifiers . ",entry[{$entry_id}]";

			$values[] = $template->parse_template( array(
				sprintf( '%s-%s-%s.php', $args['template'], $nested_form['id'], $field->id ),
				sprintf( '%s-%s.php', $args['template'], $nested_form['id'] ),
				sprintf( '%s.php', $args['template'] ),
			), true, false, $args );

		}

		$hr = '<hr class="gpnf-nested-entries-hr" style="height:12px;visibility:hidden;margin:0;">';

		if( $is_all_fields ) {
			foreach( $values as &$_value ) {
				$_value = preg_replace( '/bgcolor/', 'style="border-top:5px solid #faebd2;" bgcolor', $_value, 1 );
				$_value = str_replace( 'EAF2FA', 'FAF4EA', $_value );
			}
			$value = sprintf( '%s%s%s', $hr, implode( $hr, $values ), $hr );
		} else {
			foreach( $values as &$_value ) {
				$_value = preg_replace( '/bgcolor/', 'style="border-top:5px solid #d2e6fa;" bgcolor', $_value, 1 );
			}
			$value = implode( $hr, $values );
		}

		return $value;
	}

	public function is_filtered_single( $modifiers, $nested_form_field, $is_all_fields ) {

		if( ! is_callable( 'gw_all_fields_template' ) ) {
			return false;
		}

		$modifiers = gw_all_fields_template()->parse_modifiers( $modifiers );
		$filter = rgar( $modifiers, 'filter' );
		if( ! $filter ) {
			return false;
		}

		if( $is_all_fields ) {

			if( ! is_array( $filter ) ) {
				$filter = array( $filter );
			}

			$field_ids = array();
			foreach( $filter as $field_id ) {
				// Convert "1.1" to "1" and make sure we're only doing field-specific NF (e.g. "1.1" vs "1").
				if( intval( $field_id ) == $nested_form_field->id && $field_id !== intval( $field_id ) ) {
					$field_id_bits = explode( '.', $field_id );
					$field_ids[] = array_pop( $field_id_bits );
					if( count( $field_ids ) > 1 ) {
						return false;
					}
				}
			}

		}
		// If it's not the {all_fields} merge tag and the filter is an array, we know it's more than one field.
		else if( is_array( $filter ) ) {
			return false;
		}


		return true;
	}

	public function get_simple_list_items( $entry_ids, $nested_form, $modifiers ) {

		if( ! is_callable( 'gw_all_fields_template' ) ) {
			return array();
		}

		$items = array();

		foreach( $entry_ids as $entry_id ) {

			$entry = GFAPI::get_entry( $entry_id );
			if( is_wp_error( $entry ) ) {
				continue;
			}

			$items = array_merge( $items, gw_all_fields_template()->get_items( $nested_form, $entry, false, true, 'html', false, '', $modifiers ) );

		}

		return $items;
	}

	public function handle_nested_confirmation( $confirmation, $submitted_form, $entry ) {

		if( ! $this->is_nested_form_submission() ) {
			return $confirmation;
		}

		$parent_form       = GFAPI::get_form( $this->get_parent_form_id() );
		$nested_form_field = $this->get_posted_nested_form_field( $parent_form );
		$display_fields    = $nested_form_field->gpnfFields;
		$field_values      = $this->get_entry_display_values( $entry, $submitted_form );
		$mode              = rgpost( 'gpnf_mode' ) ? rgpost( 'gpnf_mode' ) : 'add';

		// Attach session meta to child entry.
		$entry = new GPNF_Entry( $entry );
		$entry->set_parent_form( $parent_form['id'] );
		$entry->set_nested_form_field( $nested_form_field->id );
		$entry->set_expiration();

		$session = new GPNF_Session( $parent_form['id'] );
		$session->add_child_entry( $entry->id );

		// set args passed back to entry list on front-end
		$args = array(
			'formId'      => $parent_form['id'],
			'fieldId'     => $nested_form_field['id'],
			'entryId'     => $entry->id,
			'entry'       => $entry,
			'fieldValues' => $field_values,
			'mode'        => $mode,
		);

		return '<script type="text/javascript"> if( typeof GPNestedForms != "undefined" ) { GPNestedForms.loadEntry( ' . json_encode( $args ) . ' ); } </script>';

	}

	public function handle_nested_confirmation_anchor( $anchor ) {
		return $this->is_nested_form_submission() ? false : $anchor;
	}

	public function handle_parent_submission( $parent_entry, $form ) {

		if( ! $this->has_nested_form_field( $form ) ) {
			return;
		}

		// Clear the session when the parent form is submitted.
		$session = new GPNF_Session( $form['id'] );
		$session->delete_cookie();

		$parent_entry = new GPNF_Entry( $parent_entry );
		if( ! $parent_entry->has_children() ) {
			return;
		}

		$child_entries = $parent_entry->get_child_entries();
		if( ! $child_entries ) {
			return;
		}

		foreach( $child_entries as $child_entry ) {

			// Create posts for child entries; the func handles determining if the entry has post fields.
			GFCommon::create_post( GFAPI::get_form( $child_entry['form_id'] ), $child_entry );

			$child_entry = new GPNF_Entry( $child_entry );
			$child_entry->set_parent_form( $form['id'], $parent_entry->id );
			$child_entry->delete_expiration();

		}

	}

	public function get_posted_nested_form_field( $form ) {
		foreach( $form['fields'] as $field ) {
			if( $field->id == $this->get_posted_nested_form_field_id() ) {
				return $field;
			}
		}
		return false;
	}

	public function get_entry_display_values( $entry, $form, $display_fields = array() ) {

		if( ! is_array( $entry ) ) {
			$entry = GFAPI::get_entry( $entry );
		}

		if( is_wp_error( $entry ) ) {
			return false;
		}

		$field_values = array();
		if( empty( $display_fields ) ) {
			$display_fields = wp_list_pluck( $form['fields'], 'id' );
		}

		foreach( $display_fields as $display_field_id ) {

			$field = GFFormsModel::get_field( $form, $display_field_id );

			// This can happen if the field is deleted from the child form but is still set as a Display Field on the Nested Form field.
			if( ! $field ) {
				continue;
			}

			$raw_value = GFFormsModel::get_lead_field_value( $entry, $field );
			$value = GFCommon::get_lead_field_display( $field, $raw_value, $entry['currency'], true );

			if( is_array( $value ) ) {
				ksort( $value );
				$value = implode( ' ', $value );
			}

			$value = array(
				'label' => $value,
				'value' => $raw_value,
			);

			/**
			 * Filter the value to be displayed in the Nested Form entries view (per field).
			 *
			 * @since 1.0
			 *
			 * @param mixed    $value The field value to be displayed.
			 * @param GF_Field $field The current field object.
			 * @param array    $form  The current form.
			 * @param array    $entry The current entry.
			 */
			$value = gf_apply_filters( array( 'gpnf_display_value', $form['id'], $field->id ), $value, $field, $form, $entry );
			$value = gf_apply_filters( array( "gpnf_{$field->get_input_type()}_display_value", $form['id'] ), $value, $field, $form, $entry );

			$field_values[ $display_field_id ] = $value;

		}

		$field_values['id'] = $entry['id'];

		$entry = new GPNF_Entry( $entry );
		$entry->set_total();
		$field_values['total'] = $entry->_total;

		return $field_values;
	}



	// # FORM RENDERING

	public function handle_nested_forms_markup( $form_html, $form ) {

		if( ! $this->has_nested_form_field( $form, true ) ) {
			return $form_html;
		}

		$is_ajax_submission = rgpost( 'gform_submit' ) && rgpost( 'gform_ajax' );

		if( $is_ajax_submission ) {
			$nested_entries = $this->get_submitted_nested_entries( $form );
			$form_html      = sprintf( '<script type="text/javascript"> parent.gpnfNestedEntries[%d] = %s; </script>', $form['id'], json_encode( $nested_entries ) ) . $form_html;
			return $form_html;
		}

		$nested_forms_markup = $this->get_nested_forms_markup( $form );

		/**
		 * This hook is deprecated.
		 */
		if( apply_filters( 'gpnf_append_nested_forms_to_footer', true, $form ) ) {
			self::$nested_forms_markup[ $form['id'] ] = $nested_forms_markup;
			if( ! has_action( 'wp_footer', array( $this, 'output_nested_forms_markup' ) ) ) {
				add_action( 'wp_footer',            array( $this, 'output_nested_forms_markup' ) );
				add_action( 'gform_preview_footer', array( $this, 'output_nested_forms_markup' ) );
			}

		} else {
			$form_html .= $nested_forms_markup;
		}

		return $form_html;
	}

	public function maybe_load_nested_form_hooks( $form ) {

		if( ! $this->is_nested_form_submission() || did_action( 'gform_pre_validation' ) ) {
			return $form;
		}

		$this->load_nested_form_hooks( $form['id'], $this->get_parent_form_id() );

		return $form;
	}

	public function load_nested_form_hooks( $form_id, $parent_form_id ) {

		$this->parent_form_id = $parent_form_id;

		add_filter( 'gform_form_tag', array( $this, 'add_nested_inputs' ), 10, 2 );

		// Force scripts to load in the footer so that they are not reincluded in the fetched form markup.
		add_filter( 'gform_init_scripts_footer',                    '__return_true', 11 );
		add_filter( 'gform_get_form_filter_' . $form_id,            array( $this, 'replace_post_render_trigger' ), 10, 2 );
		add_filter( 'gform_footer_init_scripts_filter_' . $form_id, array( $this, 'replace_post_render_trigger' ), 10, 2 );

		// Prevent posts from being generated.
		add_filter( 'gform_disable_post_creation_' . $form_id, '__return_true', 11 );

		add_filter( 'gform_validation', array( $this, 'override_no_duplicates_validation' ) );

		// Setup unload to remove hooks after form has been generated.
		add_filter( 'gform_get_form_filter_' . $form_id, array( $this, 'unload_nested_form_hooks' ), 99 );

	}

	/**
	 * When editing a child entry via a Nested Form, override the no duplicates validation if the value of the child entry
	 * has not changed.
	 *
	 * @param $result
	 *
	 * @return mixed
	 */
	public function override_no_duplicates_validation( $result ) {

		if( $result['is_valid'] ) {
			return $result;
		}

		$edit_entry_id = $this->get_posted_entry_id();
		if( ! $edit_entry_id ) {
			return $result;
		}

		/** @var GF_Field $field */
		foreach( $result['form']['fields'] as &$field ) {

			if( ! $field->noDuplicates || ! $field->failed_validation ) {
				continue;
			}

			$submitted_value = $field->get_value_submission( array() );
			if( ! GFFormsModel::is_duplicate( $result['form']['id'], $field, $submitted_value ) ) {
				continue;
			}

			$entry = GFAPI::get_entry( $edit_entry_id );
			$existing_value = rgar( $entry, $field->id );

			if( $submitted_value == $existing_value ) {
				$field->failed_validation = false;
			}

		}

		$result['is_valid'] = true;
		foreach( $result['form']['fields'] as &$field ) {
			if( $field->failed_validation ) {
				$result['is_valid'] = false;
			}
		}

		return $result;
	}

	public function unload_nested_form_hooks( $form_string ) {

		$this->parent_form_id = null;

		remove_filter( 'gform_form_tag', array( $this, 'add_nested_inputs' ) );
		remove_filter( 'gform_init_scripts_footer', '__return_true', 11 );

		return $form_string;
	}

	public function replace_post_render_trigger( $form_html, $form ) {
		$form_html = preg_replace( '/trigger\([ ]*[\'"]gform_post_render[\'"]/', "trigger('gpnf_post_render'", $form_html );
		// Used by event handler functionality to target nested form post render events and prioritize them.
		$form_html = preg_replace( '/bind\([ ]*[\'"]gform_post_render[\'"]/', "bind('gform_post_render.gpnf'", $form_html );
		return $form_html;
	}

	public function handle_event_handler( $markup, $form ) {

		if( ! $this->has_nested_form_field( $form, true ) ) {
			return $markup;
		}

		if( apply_filters( 'gform_init_scripts_footer', false ) && ! has_action( 'wp_footer', array( __CLASS__, 'output_event_handler' ) ) ) {
			add_action( 'wp_footer', array( __CLASS__, 'output_event_handler' ) );
			add_action( 'gform_preview_footer', array( __CLASS__, 'output_event_handler' ) );
		} else {
			$script = self::output_event_handler( false );
			$markup = $script . $markup;

		}

		return $markup;
	}

	public static function output_event_handler( $echo = true ) {

		$script = '<script> jQuery( document ).on( "gform_post_render", function( event, formId, currentPage ) { gpnfEventHandler( event, formId, currentPage ); } ); </script>';

		if( $echo ) {
			echo $script;
		}

		return $script;
	}

	public function register_all_form_init_scripts( $form ) {

		$script = '';

		foreach( $form['fields'] as &$field ) {

			if( $field->type != 'form' || $field->visibility == 'administrative' ) {
				continue;
			}

			$nested_form    = GFAPI::get_form( rgar( $field, 'gpnfForm' ) );
			$display_fields = rgar( $field, 'gpnfFields' );
			$entries        = $this->get_submitted_nested_entries( $form, $field['id'] );

			$args = array(
				'formId'         => $form['id'],
				'fieldId'        => $field['id'],
				'nestedFormId'   => $nested_form['id'],
				'displayFields'  => $display_fields,
				'entries'        => $entries,
				'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
				'modalWidth'          => 700,
				'modalHeight'         => 'auto',
				'modalClass'          => 'gpnf-dialog',
				'modalTitle'          => sprintf( __( 'Add %s', 'gp-nested-forms' ), $field->get_item_label() ),
				'editModalTitle'      => sprintf( __( 'Edit %s', 'gp-nested-forms' ), $field->get_item_label() ),
				'modalHeaderColor'    => $field->gpnfModalHeaderColor,
				'hasConditionalLogic' => GFFormDisplay::has_conditional_logic( $nested_form ),
				'entryLimitMin'       => $field->gpnfEntryLimitMin,
				'entryLimitMax'       => $field->gpnfEntryLimitMax,
			);

			/**
			 * Filter the arguments that will be used to initialized the nested forms frontend script.
			 *
			 * @since 1.0
			 *
			 * @param array $args {
			 *
			 *     @var int    $formId              The current form ID.
			 *     @var int    $fieldId             The field ID of the Nested Form field.
			 *     @var int    $nestedFormId        The form ID of the nested form.
			 *     @var string $modalTitle          The title to be displayed in the modal header.
			 *     @var string $editModalTitle      The title to be displayed in the modal header when editing an existing entry.
			 *     @var array  $displayFields       The fields which will be displayed in the Nested Forms entries view.
			 *     @var array  $entries             An array of modified entries, including only their display values.
			 *     @var string $ajaxUrl             The URL to which AJAX requests will be posted.
			 *     @var int    $modalWidth          The default width of the modal; defaults to 700.
			 *     @var mixed  $modalHeight         The default height of the modal; defaults to 'auto' which will automatically size the modal based on it's contents.
			 *     @var string $modalClass          The class that will be attached to the modal for styling.
			 *     @var string $modalHeaderColor    A HEX color that will be set as the default background color of the modal header.
			 *     @var bool   $hasConditionalLogic Indicate whether the current form has conditional logic enabled.
			 *
			 * }
			 * @param GF_Field $field The current Nested Form field.
			 * @param array    $form  The current form.
			 */
			$args = gf_apply_filters( array( 'gpnf_init_script_args', $form['id'], $field->id, ), $args, $field, $form );

			$script .= 'if( typeof window.gpnfNestedEntries == "undefined" ) { window.gpnfNestedEntries = {}; }';
			$script .= 'new GPNestedForms( ' . json_encode( $args ) . ' );';

		}

		if( $script ) {
			GFFormDisplay::add_init_script( $form['id'], 'gpnf_init_script', GFFormDisplay::ON_PAGE_RENDER, $script );
		}

	}

	public function get_submitted_nested_entries( $form, $field_id = false, $display_values = true ) {

		$all_entries = array();

		foreach( $form['fields'] as $field ) {

			if( $field['type'] != 'form' ) {
				continue;
			}

			$nested_form    = GFAPI::get_form( $field->gpnfForm );
			$display_fields = $field->gpnfFields;

			$entries   = array();
			$entry_ids = rgpost( 'input_' . $field['id'] );

			if( empty( $entry_ids ) ) {
				$entry_ids = $field->get_value_submission( array() /* @todo this might get us in trouble; should pass real $field_values */ );
			}

			if( empty( $entry_ids ) || ! is_string( $entry_ids ) ) {
				$entry_ids = array();
			} else {
				$entry_ids = explode( ',', $entry_ids );
			}

			// if no posted $entry_ids check if we are resuming a saved entry
			if ( isset( $_GET['gf_token'] ) && empty( $entry_ids ) ) {
				$incomplete_submission_info = GFFormsModel::get_incomplete_submission_values( $_GET['gf_token'] );
				if ( $incomplete_submission_info['form_id'] == $form['id'] ) {
					$submission_details_json                  = $incomplete_submission_info['submission'];
					$submission_details                       = json_decode( $submission_details_json, true );
					$submitted_values                         = $submission_details['submitted_values'];
					$entry_ids = explode( ',', rgar( $submitted_values, $field->id ) );
				}
			}

			// Load entries from session.
			if( empty( $entry_ids ) ) {

				$session = new GPNF_Session( $form['id'] );
				$_entries = $session->get( 'nested_entries' );
				if( ! empty( $_entries[ $field['id'] ] ) ) {
					$entry_ids = $_entries[ $field['id'] ];
				}

			}

			if( ! empty( $entry_ids ) ) {

				foreach( $entry_ids as $entry_id ) {

					$entry = GFAPI::get_entry( $entry_id );
					if( is_wp_error( $entry ) ) {
						continue;
					}

					if( $display_values ) {
						$entries[] = $this->get_entry_display_values( $entry, $nested_form );
					} else {
						$entries[] = $entry;
					}

				}

			}

			$all_entries[ $field->id ] = $entries;

		}

		return $field_id ? rgar( $all_entries, $field_id ) : $all_entries;
	}



	// # EDIT POPULATION AND SUBMISSION

	public function prepare_form_for_population( $form ) {

		foreach( $form['fields'] as &$field ) {

			$field['allowsPrepopulate'] = true;

			if( is_array( $field['inputs'] ) ) {
				$inputs = $field['inputs'];
				foreach( $inputs as &$input ) {
					$input['name'] = (string) $input['id'];
				}
				$field['inputs'] = $inputs;
			}

			$field['inputName'] = $field['id'];

		}

		return $form;
	}

	public function set_edit_form_action( $form_tag ) {
		return preg_replace( "|action='(.*?)'|", "action=''", $form_tag );
	}

	public function set_gwlc_selected_values($values, $field) {

		$entry_id = $this->get_posted_entry_id();
		$entry    = GFAPI::get_entry( $entry_id );

		return GFFormsModel::get_lead_field_value( $entry, $field );

	}

	public function prepare_entry_for_population( $entry ) {

		$form = GFFormsModel::get_form_meta( $entry['form_id'] );

		foreach( $form['fields'] as $field ) {

			switch( GFFormsModel::get_input_type( $field ) ) {

				case 'checkbox':

					$values = $this->get_field_values_from_entry( $field, $entry );
					if( is_array( $values ) ) {
						$value = implode( ',', array_filter( $values ) );
					} else {
						$value = $values;
					}
					$entry[$field['id']] = $value;

					break;

				case 'list':

					$value = maybe_unserialize( rgar( $entry, $field->id ) );
					$list_values = array();

					if( is_array( $value ) ) {
						foreach( $value as $vals ) {
							if( is_array( $vals ) ) {
								$vals = implode( '|', array_values( $vals ) );
							}
							array_push( $list_values, $vals );
						}
						$entry[ $field->id ] = implode( ',', $list_values );
					}

					break;

				case 'multiselect':
					$value = self::maybe_decode_json( rgar( $entry, $field->id ) );
					$entry[ $field['id'] ] = $value;
					break;

				case 'fileupload':

					$is_multiple = $field->multipleFiles;
					$value       = rgar( $entry, $field->id );
					$return      = array();

					if( $is_multiple ) {
						$files = json_decode( $value );
					} else {
						$files = array( $value );
					}

					foreach( $files as $file ) {

						$path_info = pathinfo( $file );

						// Check if file has been "deleted" via form UI.
						$upload_files = json_decode( rgpost( 'gform_uploaded_files' ), ARRAY_A );
						$input_name   = "input_{$field->id}";

						if ( is_array( $upload_files ) && array_key_exists( $input_name, $upload_files ) && ! $upload_files[ $input_name ] ) {
							continue;
						}

						if( $is_multiple ) {
							$return[] = array(
								'temp_filename'     => 'GPNF_DOES_NOT_EXIT.png',
								'uploaded_filename' => $path_info['basename'],
							);
						} else {
							$return[] = $path_info['basename'];
						}


					}


					// if $uploaded_files array is not set for this form at all, init as array
					if ( ! isset( GFFormsModel::$uploaded_files[ $form['id'] ] ) ) {
						GFFormsModel::$uploaded_files[ $form['id'] ] = array();
					}

					// check if this field's key has been set in the $uploaded_files array, if not add this file (otherwise, a new image may have been uploaded so don't overwrite)
					if ( ! isset( GFFormsModel::$uploaded_files[ $form['id'] ]["input_{$field->id}"] ) ) {
						GFFormsModel::$uploaded_files[ $form['id'] ]["input_{$field->id}"] = $is_multiple ? $return : reset( $return );
					}

			}

		}

		return $entry;
	}

	public function get_field_values_from_entry( $field, $entry ) {

		$values = array();

		foreach( $entry as $input_id => $value ) {
			$fid = intval( $input_id );
			if( $fid == $field['id'] )
				$values[] = $value;
		}

		return count( $values ) <= 1 ? $values[0] : $values;
	}

	public function maybe_edit_entry( $entry_id, $form ) {

		if( $this->is_nested_form_edit_submission() ) {
			$entry_id = $this->get_posted_entry_id();
			$this->handle_existing_images_submission( $form, $entry_id );
			add_filter( 'gform_entry_post_save', array( $this, 'update_total_hack' ), 10, 2 );
		}

		return $entry_id;
	}

	public function add_nested_inputs( $form_tag, $form ) {

		// makes it easier to show/hide these fields for debugging
		$type = 'hidden';

		// append parent form ID input
		$form_tag .= '<input type="' . $type. '" name="gpnf_parent_form_id" value="' . $this->get_parent_form_id() . '" />';

		// append nested form field ID input
		$form_tag .= '<input type="' . $type. '" name="gpnf_nested_form_field_id" value="' . $this->get_posted_nested_form_field_id() . '" />';

		// append entry ID and mode inputs
		if( $entry_id = $this->get_posted_entry_id() ) {
			$form_tag .= '<input type="' . $type. '" value="' . $entry_id . '" name="gpnf_entry_id" />';
			$form_tag .= '<input type="' . $type. '" value="edit" name="gpnf_mode" />';
		}

		// append has_validation_error bool input
		$is_valid = ! isset( GFFormDisplay::$submission[$form['id']] ) || rgar( GFFormDisplay::$submission[$form['id']], 'is_valid' );
		$form_tag .= '<input type="' . $type. '" value="' . $is_valid . '" id="gpnf_is_valid_' . $form['id'] . '" />';

		return $form_tag;
	}

	public function update_total_hack( $entry, $form ) {

		foreach( $form['fields'] as $field ) {

			if( $field['type'] != 'total' ) {
				continue;
			}

			GFFormsModel::refresh_product_cache( $form, $entry );

			$entry[$field['id']] = GFCommon::get_order_total( $form, $entry );

			GFAPI::update_entry( $entry );

		}

		return $entry;
	}

	public function handle_existing_images_submission( $form, $entry_id ) {
		global $_gf_uploaded_files;

		$entry = GFAPI::get_entry( $entry_id );
		if( ! $entry ) {
			return;
		}

		// get all fileupload fields
		// loop through and see if the image has been:
		//  - resubmitted:         populate the existing image data into the $_gf_uploaded_files
		//  - deleted:             do nothing
		//  - new image submitted: do nothing

		if( empty( $_gf_uploaded_files ) ) {
			$_gf_uploaded_files = array();
		}

		foreach( $entry as $input_id => $value ) {

			if( ! is_numeric( $input_id ) ) {
				continue;
			}

			$field = GFFormsModel::get_field( $form, $input_id );
			$input_name = "input_{$field['id']}";

			if( $field->get_input_type() != 'fileupload' ) {
				continue;
			}

			// Handle multi-file uploads.
			if( $field->multipleFiles ) {

				$value  = json_decode( $value, true );
				$posted = wp_list_pluck( rgar( json_decode( rgpost( 'gform_uploaded_files' ), true ), $input_name ), 'uploaded_filename' );
				$count  = count( $value );

				// Remove any files that have been removed via the UI.
				for( $i = $count - 1; $i >= 0; $i-- ) {
					$path = pathinfo( $value[ $i ] );
					if( ! in_array( $path['basename'], $posted ) ) {
						unset( $value[ $i ] );
					}
				}

				// Populate existing images into post where GF will be looking for them.
				$_POST[ "input_{$field->id}" ] = json_encode( $value );

			}
			// Handle single file uploads.
			else if( self::is_prepopulated_file_upload( $form['id'], $input_name ) ) {
				$_gf_uploaded_files[ $input_name ] = $value;
			}

		}

	}

	/**
	 * Check for newly updated file. Only applies to single file uploads.
	 *
	 * @param $form_id
	 * @param $input_name
	 *
	 * @return bool
	 */
	public function is_new_file_upload( $form_id, $input_name ) {

		$file_info     = GFFormsModel::get_temp_filename( $form_id, $input_name );
		$temp_filepath = GFFormsModel::get_upload_path( $form_id ) . "/tmp/" . $file_info["temp_filename"];

		// check if file has already been uploaded by previous step
		if( $file_info && file_exists( $temp_filepath ) ) {
			return true;
		}
		// check if file is uploaded on current step
		else if ( ! empty( $_FILES[ $input_name ][ 'name' ] ) ) {
			return true;
		}

		return false;
	}

	public function is_prepopulated_file_upload( $form_id, $input_name, $is_multiple = false ) {

		// prepopulated files will be stored in the 'gform_uploaded_files' field
		$uploaded_files = json_decode( rgpost( 'gform_uploaded_files' ), ARRAY_A );

		// file is prepopulated if it is present in the 'gform_uploaded_files' field AND is not a new file upload
		$in_uploaded_files = is_array( $uploaded_files ) && array_key_exists( $input_name, $uploaded_files ) && ! empty( $uploaded_files[ $input_name ] );
		$is_prepopulated   = $in_uploaded_files && ! $this->is_new_file_upload( $form_id, $input_name );

		return $is_prepopulated;
	}



	// # VALIDATION

	public function has_nested_form_field( $form, $check_visibility = false ) {
		$fields = GFCommon::get_fields_by_type( $form, $this->field_type );
		$count = 0;
		foreach( $fields as $field ) {
			if( ! $check_visibility || $field->visibility != 'administrative' ) {
				$count++;
			}
		}
		return $count > 0;
	}



	// # INTEGRATIONS

	public function add_full_child_entry_data_for_webhooks( $data, $feed, $entry, $form ) {

		// This should be structed like an Entry Object; if not, we don't want to mess with it.
		if( ! is_array( $data ) ) {
			return $data;
		}

		foreach( $form['fields'] as $field ) {

			if( $field->get_input_type() != $this->field_type || ! array_key_exists( $field->id, $data ) ) {
				continue;
			}

			$_entry = new GPNF_Entry( $entry );
			$data[ $field->id ] = $_entry->get_child_entries( $field->id );

		}

		return $data;
	}



	// # HELPERS

	public function is_nested_form_submission() {
		$parent_form_id = $this->get_parent_form_id();
		return $parent_form_id > 0;
	}

	public function is_nested_form_edit_submission() {
		return $this->is_nested_form_submission() && rgpost( 'gpnf_mode' ) == 'edit';
	}

	public function get_parent_form_id() {

		if( ! $this->parent_form_id )
			$this->parent_form_id = $this->get_posted_parent_form_id();

		return $this->parent_form_id;
	}

	public function get_posted_parent_form_id() {
		return rgpost( 'gpnf_parent_form_id' );
	}

	public function get_posted_nested_form_field_id() {
		return rgpost( 'gpnf_nested_form_field_id' );
	}

	public function get_posted_entry_id() {
		return rgpost( 'gpnf_entry_id' );
	}

	public function get_fields_by_ids( $ids, $form ) {
		$fields = array();
		foreach( $form['fields'] as $field ) {
			if( in_array( $field->id, $ids ) ) {
				$fields[] = $field;
			}
		}
		return $fields;
	}

	/**
	 * Due to our __call() method, is_callable() checks will result in fatal errors. We don't need this function but
	 * let's define it to avoid unpleasantries.
	 */
	public function get_form_field_value( $entry, $field_id, $field ) {
		return $field->get_value_export( $entry, $field_id );
	}

	/**
	 * Remove/replace old settings with their newer counterparts.
	 *
	 * @param $form
	 *
	 * @return mixed
	 */
	public function cleanup_form_meta( $form ) {

		$settings_map = array(
			'gp-nested-forms_fields' => 'gpnfFields',
			'gp-nested-forms_form'   => 'gpnfForm',
		);

		foreach( $form['fields'] as &$field ) {

			if( $field->type != 'form' ) {
				continue;
			}

			foreach( $settings_map as $old => $new ) {
				if( $field->$old ) {
					if( ! $field->$new ) {
						$field->$new = $field->$old;
					}
					unset( $field->{$old} );
				}
			}

		}

		return $form;
	}

	public function documentation() {
		return array(
			'type'  => 'url',
			'value' => 'http://gravitywiz.com/documenation/gravity-forms-nested-forms/'
		);
	}

}

function gp_nested_forms() {
	return GP_Nested_Forms::get_instance();
}

GFAddOn::register( 'GP_Nested_Forms' );
