<?php
/**
 * Gravity Flow Extension Base
 *
 * @package     GravityFlow
 * @subpackage  Classes/ExtensionBase
 * @copyright   Copyright (c) 2015-2018, Steven Henty S.L.
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

if ( ! class_exists( 'GFForms' ) ) {
	die();
}
GFForms::include_feed_addon_framework();

/**
 * Class Gravity_Flow_Feed_Extension
 *
 * @since 1.0
 */
abstract class Gravity_Flow_Feed_Extension extends GFFeedAddOn {

	/**
	 * The item name used by Easy Digital Downloads.
	 *
	 * @var string
	 */
	public $edd_item_name = '';

	/**
	 * If the extensions minimum requirements are met add the general hooks.
	 */
	public function init() {
		parent::init();

		$meets_requirements = $this->meets_minimum_requirements();
		if ( ! $meets_requirements['meets_requirements'] ) {
			return;
		}

		add_filter( 'gravityflow_menu_items', array( $this, 'menu_items' ) );
		add_filter( 'gravityflow_toolbar_menu_items', array( $this, 'toolbar_menu_items' ) );
	}

	/**
	 * If the extensions minimum requirements are met add the admin hooks.
	 */
	public function init_admin() {
		parent::init_admin();

		$meets_requirements = $this->meets_minimum_requirements();
		if ( ! $meets_requirements['meets_requirements'] ) {
			return;
		}

		add_filter( 'gravityflow_settings_menu_tabs', array( $this, 'app_settings_tabs' ) );
		add_filter( 'plugin_action_links', array( $this, 'plugin_settings_link' ), 10, 2 );

		// Members 2.0+ Integration.
		if ( function_exists( 'members_register_cap_group' ) ) {
			remove_filter( 'members_get_capabilities', array( $this, 'members_get_capabilities' ) );
			add_filter( 'gravityflow_members_capabilities', array( $this, 'get_members_capabilities' ) );
		}
	}

	/**
	 * Add the extension capabilities to the Gravity Flow group in Members.
	 *
	 * Override to provide human readable labels.
	 *
	 * @since 1.8.1-dev
	 *
	 * @param array $caps The capabilities and their human readable labels.
	 *
	 * @return array
	 */
	public function get_members_capabilities( $caps ) {
		foreach ( $this->_capabilities as $capability ) {
			$caps[ $capability ] = $capability;
		}

		return $caps;
	}

	/**
	 * Add a tab to the app settings page for this extension.
	 *
	 * @param array $settings_tabs The app settings tabs.
	 *
	 * @return array
	 */
	public function app_settings_tabs( $settings_tabs ) {

		$settings_tabs[] = array(
			'name'     => $this->_slug,
			'label'    => $this->get_short_title(),
			'callback' => array( $this, 'app_settings_tab' ),
		);

		return $settings_tabs;
	}

	/**
	 * The callback for this extensions app settings tab.
	 */
	public function app_settings_tab() {

		require_once( GFCommon::get_base_path() . '/tooltips.php' );

		$icon = $this->app_settings_icon();
		if ( empty( $icon ) ) {
			$icon = '<i class="fa fa-cogs"></i>';
		}
		?>

		<h3><span><?php echo $icon ?><?php echo $this->app_settings_title() ?></span></h3>

		<?php

		if ( $this->maybe_uninstall() ) {
			?>
			<div class="push-alert-gold" style="border-left: 1px solid #E6DB55; border-right: 1px solid #E6DB55;">
				<?php printf( esc_html_x( '%s has been successfully uninstalled. It can be re-activated from the %splugins page%s.', 'Displayed on the settings page after uninstalling a Gravity Flow extension.', 'gravityflow' ), esc_html( $this->_title ), "<a href='plugins.php'>", '</a>' ); ?>
			</div>
			<?php
		} else {
			// Saves settings page if save button was pressed.
			$this->maybe_save_app_settings();

			// Reads main add-on settings.
			$settings = $this->get_app_settings();
			$this->set_settings( $settings );

			// Reading add-on fields.
			$sections = $this->app_settings_fields();

			GFCommon::display_admin_message();

			// Rendering settings based on fields and current settings.
			$this->render_settings( $sections );

			$this->render_uninstall();

		}
	}

	/**
	 * Override this function to customize the markup for the uninstall section on the plugin settings page
	 */
	public function render_uninstall() {

		?>
		<form action="" method="post">
			<?php wp_nonce_field( 'uninstall', 'gf_addon_uninstall' ) ?>
			<?php if ( $this->current_user_can_any( $this->_capabilities_uninstall ) ) { ?>

				<div class="hr-divider"></div>

				<h3><span><i
							class="fa fa-times"></i> <?php printf( esc_html_x( 'Uninstall %s Extension', 'Title for the uninstall section on the settings page for a Gravity Flow extension.', 'gravityflow' ), $this->get_short_title() ) ?></span>
				</h3>
				<div class="delete-alert alert_red">
					<h3><i class="fa fa-exclamation-triangle gf_invalid"></i> Warning</h3>

					<div class="gf_delete_notice">
						<?php echo $this->uninstall_warning_message() ?>
					</div>
					<input type="submit" name="uninstall"
					       value="<?php echo esc_attr_x( 'Uninstall Extension', 'Button text on the settings page for an extension.', 'gravityflow' ) ?>"
					       class="button"
					       onclick="return confirm('<?php echo esc_js( $this->uninstall_confirm_message() ); ?>');">
				</div>

			<?php } ?>
		</form>
		<?php
	}

	/**
	 * Get the settings for the app settings tab.
	 *
	 * @return array
	 */
	public function app_settings_fields() {
		return array(
			array(
				'title'  => $this->get_short_title(),
				'fields' => array(
					array(
						'name'                => 'license_key',
						'label'               => esc_html__( 'License Key', 'gravityflow' ),
						'type'                => 'text',
						'validation_callback' => array( $this, 'license_validation' ),
						'feedback_callback'   => array( $this, 'license_feedback' ),
						'error_message'       => __( 'Invalid license', 'gravityflow' ),
						'class'               => 'large',
						'default_value'       => '',
					),
				),
			),
		);
	}

	/**
	 * Return the saved settings.
	 *
	 * @return mixed
	 */
	public function get_app_settings() {
		return parent::get_app_settings();
	}

	/**
	 * Validate the license key setting.
	 *
	 * @param string $value The field value; the license key.
	 * @param array  $field The field properties.
	 *
	 * @return bool|null
	 */
	public function license_feedback( $value, $field ) {

		if ( empty( $value ) ) {
			return null;
		}

		$license_data = $this->check_license( $value );

		$valid = null;
		if ( empty( $license_data ) || $license_data->license == 'invalid' ) {
			$valid = false;
		} elseif ( $license_data->license == 'valid' ) {
			$valid = true;
		}

		return $valid;

	}

	/**
	 * Retrieve the license data.
	 *
	 * @param string $value The license key for this extension.
	 *
	 * @return array|mixed|object
	 */
	public function check_license( $value ) {
		$response = gravity_flow()->perform_edd_license_request( 'check_license', $value, $this->edd_item_name );

		return json_decode( wp_remote_retrieve_body( $response ) );

	}

	/**
	 * Deactivate the old license key and active the new license key.
	 *
	 * @param array  $field         The field properties.
	 * @param string $field_setting The field value; the license key.
	 */
	public function license_validation( $field, $field_setting ) {
		$old_license = $this->get_app_setting( 'license_key' );

		if ( $old_license && $field_setting != $old_license ) {
			// Deactivate the old site.
			$response = gravity_flow()->perform_edd_license_request( 'deactivate_license', $old_license, $this->edd_item_name );
		}


		if ( empty( $field_setting ) ) {
			return;
		}

		$this->activate_license( $field_setting );

	}

	/**
	 * Activate the license key.
	 *
	 * @param string $license_key The license key for this extension.
	 *
	 * @return array|mixed|object
	 */
	public function activate_license( $license_key ) {
		$response = gravity_flow()->perform_edd_license_request( 'activate_license', $license_key, $this->edd_item_name );

		// Force plugins page to refresh the update info.
		set_site_transient( 'update_plugins', null );
		$cache_key = md5( 'edd_plugin_' . sanitize_key( $this->_path ) . '_version_info' );
		delete_transient( $cache_key );

		return json_decode( wp_remote_retrieve_body( $response ) );
	}

	/**
	 * Override to add menu items to the Gravity Flow app menu.
	 *
	 * @param array $menu_items The app menu items.
	 *
	 * @return array
	 */
	public function menu_items( $menu_items ) {
		return $menu_items;
	}

	/**
	 * Override to add menu items to the Gravity Flow toolbar.
	 *
	 * @param array $menu_items The toolbar menu items.
	 *
	 * @return array
	 */
	public function toolbar_menu_items( $menu_items ) {
		return $menu_items;
	}

	/**
	 * Add the failed requirements error message.
	 *
	 * @since 1.7.1-dev
	 */
	public function failed_requirements_init() {
		$failed_requirements = $this->meets_minimum_requirements();

		// Prepare errors list.
		$errors = '';
		foreach ( $failed_requirements['errors'] as $error ) {
			$errors .= sprintf( '<li>%s</li>', esc_html( $error ) );
		}

		// Prepare error message.
		$error_message = sprintf(
			'%s<br />%s<ol>%s</ol>',
			sprintf( esc_html__( '%s is not able to run because your WordPress environment has not met the minimum requirements.', 'gravityflow' ), $this->_title ),
			sprintf( esc_html__( 'Please resolve the following issues to use %s:', 'gravityflow' ), $this->get_short_title() ),
			$errors
		);

		// Add error message.
		GFCommon::add_error_message( $error_message );
	}

	/**
	 * Determine if the add-ons minimum requirements have been met with Gravity Forms 2.2+.
	 *
	 * @since 1.8.1-dev
	 *
	 * @return array
	 */
	public function meets_minimum_requirements() {
		if ( $this->is_gravityforms_supported( '2.2' ) ) {
			return parent::meets_minimum_requirements();
		}

		return array( 'meets_requirements' => true, 'errors' => array() );
	}

	/**
	 * Add the settings link for the extension to the installed plugins page.
	 *
	 * @param array  $links An array of plugin action links.
	 * @param string $file  Path to the plugin file relative to the plugins directory.
	 *
	 * @since 1.7.1-dev
	 *
	 * @return array
	 */
	public function plugin_settings_link( $links, $file ) {
		if ( $file != $this->_path ) {
			return $links;
		}

		array_unshift( $links, '<a href="' . admin_url( 'admin.php' ) . '?page=gravityflow_settings&view=' . $this->_slug . '">' . esc_html__( 'Settings', 'gravityflow' ) . '</a>' );

		return $links;
	}
}