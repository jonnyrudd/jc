<?php
/**
Plugin Name: Tomo Addons
Plugin URI: https://www.linkedin.com/in/tom-mo-a5a56b46
Description: Customization by Tomo
Version: 1.0.0
Author: Tom Mo
Author URI: https://www.linkedin.com/in/tom-mo-a5a56b46
License: GPLv2+
Text Domain: tomo
*/
function startsWith($haystack, $needle){
     $length = strlen($needle);
     return (substr($haystack, 0, $length) === $needle);
}

function endsWith($haystack, $needle){
    $length = strlen($needle);

    return $length === 0 || 
    (substr($haystack, -$length) === $needle);
}

class Tomo_Addons {
	// Constructor
    function __construct() {
		add_action( 'admin_menu', array( $this, 'tomo_add_menu' ));
		register_activation_hook( __FILE__, array( $this, 'tomo_install' ) );
		register_deactivation_hook( __FILE__, array( $this, 'tomo_uninstall' ) );
    }

	/*
	* Actions perform at loading of admin menu
	*/
    function tomo_add_menu() {
        add_menu_page(
						__( 'Products', 'tomo' ),
						__( 'Products', 'tomo' ),
						'read',
						'product_info',
						array( $this, 'include_product_info' ),
						'dashicons-products',
						2
					);
					
		add_submenu_page( 
							'product_info', 
							__( 'Request', 'tomo' ),
							__( 'Request', 'tomo' ),
							'read', 
							'product_request',
							array( $this, 'include_product_request' ));
		
		add_menu_page(
						__( 'Vendors', 'tomo' ),
						__( 'Vendors', 'tomo' ),
						'read',
						'vendor_info',
						array( $this, 'include_vendor_info' ),
						'dashicons-id-alt',
						2
					);
					
		add_submenu_page( 
							'vendor_info', 
							__( 'Request', 'tomo' ),
							__( 'Request', 'tomo' ),
							'read', 
							'vendor_request',
							array( $this, 'include_vendor_request' ));
							
		add_menu_page(
						__( 'Clients', 'tomo' ),
						__( 'Clients', 'tomo' ),
						'read',
						'client_info',
						array( $this, 'include_client_info' ),
						'dashicons-admin-site',
						2
					);
					
		add_submenu_page( 
							'client_info', 
							__( 'Request', 'tomo' ),
							__( 'Request', 'tomo' ),
							'read', 
							'client_request',
							array( $this, 'include_client_request' ));
		
		add_menu_page(
						__( 'Warehouses', 'tomo' ),
						__( 'Warehouses', 'tomo' ),
						'read',
						'warehouse_info',
						array( $this, 'include_warehouse_info' ),
						'dashicons-hammer',
						2
					);
					
		add_submenu_page( 
							'warehouse_info', 
							__( 'Request', 'tomo' ),
							__( 'Request', 'tomo' ),
							'read', 
							'warehouse_request',
							array( $this, 'include_warehouse_request' ));
		
		add_menu_page(
						__( 'Orders', 'tomo' ),
						__( 'Orders', 'tomo' ),
						'read',
						'order_info',
						array( $this, 'include_order_info' ),
						'dashicons-list-view',
						3
					);
		
		add_menu_page(
						__( 'Workflow One', 'tomo' ),
						__( 'Workflow One', 'tomo' ),
						'read',
						'workflow_1',
						array( $this, 'include_workflow_1' ),
						'dashicons-share-alt',
						5
					);
		
		add_submenu_page( 
							null,//'workflow_1', 
							__( 'WF1 Request', 'tomo' ),
							__( 'WF1 Request', 'tomo' ),
							'read', 
							'wf1_request',
							array( $this, 'include_wf1_request' ));
							
		add_menu_page(
						__( 'Workflow Two', 'tomo' ),
						__( 'Workflow Two', 'tomo' ),
						'read',
						'workflow_2',
						array( $this, 'include_workflow_2' ),
						'dashicons-art',
						6
					);
		
		add_submenu_page( 
							null,
							__( 'WF2 Request', 'tomo' ),
							__( 'WF2 Request', 'tomo' ),
							'read', 
							'wf2_request',
							array( $this, 'include_wf2_request' ));
		
		add_menu_page(
						__( 'Workflow Three', 'tomo' ),
						__( 'Workflow Three', 'tomo' ),
						'read',
						'workflow_3',
						array( $this, 'include_workflow_3' ),
						'dashicons-pressthis',
						7
					);
		
		add_submenu_page( 
							null,//'workflow_1', 
							__( 'WF3 Request', 'tomo' ),
							__( 'WF3 Request', 'tomo' ),
							'read', 
							'wf3_request',
							array( $this, 'include_wf3_request' ));
		
		add_menu_page(
						__( 'Workflow Four', 'tomo' ),
						__( 'Workflow Four', 'tomo' ),
						'read',
						'workflow_4',
						array( $this, 'include_workflow_4' ),
						'dashicons-star-filled',
						8
					);
		
		add_submenu_page( 
							null,//'workflow_1', 
							__( 'WF4 Request', 'tomo' ),
							__( 'WF4 Request', 'tomo' ),
							'read', 
							'wf4_request',
							array( $this, 'include_wf4_request' ));
		
		add_menu_page(
						__( 'Workflow Five', 'tomo' ),
						__( 'Workflow Five', 'tomo' ),
						'read',
						'workflow_5',
						array( $this, 'include_workflow_5' ),
						'dashicons-flag',
						9
					);
		
		add_submenu_page( 
							null,
							__( 'WF5 Request', 'tomo' ),
							__( 'WF5 Request', 'tomo' ),
							'read', 
							'wf5_request',
							array( $this, 'include_wf5_request' ));
		
		add_menu_page(
						__( 'Finance Data', 'tomo' ),
						__( 'Finance Data', 'tomo' ),
						'read',
						'workflow_6',
						array( $this, 'include_workflow_6' ),
						'dashicons-chart-area',
						10
					);
		
		add_submenu_page( 
							'workflow_6',
							__( 'Finance Folder', 'tomo' ),
							__( 'Finance Folder', 'tomo' ),
							'read', 
							'wf6_request',
							array( $this, 'include_wf6_request' ));
		
		add_menu_page(
						__( 'Templates', 'tomo' ),
						__( 'Templates', 'tomo' ),
						'read',
						'template_folder',
						array( $this, 'include_template_folder' ),
						'dashicons-awards',
						11
					);
							
		// Folder Display Page
		add_submenu_page( 
							null,
							__( 'Folder', 'tomo' ),
							__( 'Folder', 'tomo' ),
							'read', 
							'folder_display',
							array( $this, 'include_folder_display' ));
    }
	
	function include_product_info(){
		
		include_once( plugin_dir_path( __FILE__ ) . 'includes/productInfo.php' );
	}
	
	function include_product_request(){
		
		include_once( plugin_dir_path( __FILE__ ) . 'includes/productRequest.php' );
	}
	
	function include_vendor_info(){
		
		include_once( plugin_dir_path( __FILE__ ) . 'includes/vendorInfo.php' );
	}
	
	function include_vendor_request(){
		
		include_once( plugin_dir_path( __FILE__ ) . 'includes/vendorRequest.php' );
	}
	
	function include_client_info(){
		
		include_once( plugin_dir_path( __FILE__ ) . 'includes/clientInfo.php' );
	}
	
	function include_client_request(){
		
		include_once( plugin_dir_path( __FILE__ ) . 'includes/clientRequest.php' );
	}
	
	function include_warehouse_info(){
		
		include_once( plugin_dir_path( __FILE__ ) . 'includes/warehouseInfo.php' );
	}
	
	function include_warehouse_request(){
		
		include_once( plugin_dir_path( __FILE__ ) . 'includes/warehouseRequest.php' );
	}
	
	function include_order_info(){
		
		include_once( plugin_dir_path( __FILE__ ) . 'includes/orderInfo.php' );
	}
	
	function include_workflow_1(){
		
		include_once( plugin_dir_path( __FILE__ ) . 'includes/workflow1.php' );
	}
	
	function include_wf1_request(){
		
		include_once( plugin_dir_path( __FILE__ ) . 'includes/workflow1Request.php' );
	}
	
	function include_workflow_2(){
		
		include_once( plugin_dir_path( __FILE__ ) . 'includes/workflow2.php' );
	}
	
	function include_wf2_request(){
		
		include_once( plugin_dir_path( __FILE__ ) . 'includes/workflow2Request.php' );
	}
	
	function include_workflow_3(){
		
		include_once( plugin_dir_path( __FILE__ ) . 'includes/workflow3.php' );
	}
	
	function include_wf3_request(){
		
		include_once( plugin_dir_path( __FILE__ ) . 'includes/workflow3Request.php' );
	}
	
	function include_workflow_4(){
		
		include_once( plugin_dir_path( __FILE__ ) . 'includes/workflow4.php' );
	}
	
	function include_wf4_request(){
		
		include_once( plugin_dir_path( __FILE__ ) . 'includes/workflow4Request.php' );
	}
	
	function include_workflow_5(){
		
		include_once( plugin_dir_path( __FILE__ ) . 'includes/workflow5.php' );
	}
	
	function include_wf5_request(){
		
		include_once( plugin_dir_path( __FILE__ ) . 'includes/workflow5Request.php' );
	}
	
	function include_workflow_6(){
		
		include_once( plugin_dir_path( __FILE__ ) . 'includes/workflow6.php' );
	}
	
	function include_wf6_request(){
		
		include_once( plugin_dir_path( __FILE__ ) . 'includes/workflow6Request.php' );
	}
	
	function include_template_folder(){
		
		include_once( plugin_dir_path( __FILE__ ) . 'includes/templateFolder.php' );
	}

	function include_folder_display(){
		
		include_once( plugin_dir_path( __FILE__ ) . 'includes/folderDisplay.php' );
	}

    /*
     * Actions perform on activation of plugin
     */
    function tomo_install() {
    }

    /*
     * Actions perform on de-activation of plugin
     */
    function tomo_uninstall() {
    }
}

new Tomo_Addons();

add_action( 'admin_enqueue_scripts', 'load_custom_wp_admin_style' );
function load_custom_wp_admin_style($hook) {
	if('toplevel_page_product_info' == $hook) {
		wp_enqueue_style( 'bootstrap_css', plugins_url('css/bootstrap.min.css', __FILE__) );
		wp_enqueue_style( 'query_builder_css', plugins_url('css/query-builder.default.min.css', __FILE__) );
		wp_enqueue_style( 'fontawesome_css', plugins_url('css/fontawesome-all.min.css', __FILE__) );
		
		//wp_enqueue_script( 'popper_js', plugins_url('js/popper.min.js', __FILE__) );
		wp_enqueue_script( 'bootstrap_js', plugins_url('js/bootstrap.min.js', __FILE__) );

		wp_enqueue_script( 'tomo_utility_js', plugins_url('js/tomoutil.js', __FILE__) );
		
		wp_enqueue_script( 'sql_parser_js', plugins_url('js/sql-parser.min.js', __FILE__) );
		wp_enqueue_script( 'moment_js', plugins_url('js/moment.js', __FILE__) );
		wp_enqueue_script( 'query_builder_js', plugins_url('js/query-builder.standalone.min.js', __FILE__) );
		wp_enqueue_script( 'query_builder_i18n_en_js', plugins_url('js/query-builder.en.js', __FILE__) );
		wp_enqueue_script( 'query_builder_i18n_cn_js', plugins_url('js/query-builder.zh-CN.js', __FILE__) );
	}

	if(endsWith($hook, '_page_product_request')) {
		wp_enqueue_style( 'bootstrap_css', plugins_url('css/bootstrap.min.css', __FILE__) );
		wp_enqueue_style( 'fontawesome_css', plugins_url('css/fontawesome-all.min.css', __FILE__) );
		
		//wp_enqueue_script( 'popper_js', plugins_url('js/popper.min.js', __FILE__) );
		wp_enqueue_script( 'bootstrap_js', plugins_url('js/bootstrap.min.js', __FILE__) );
	}
	
	if('toplevel_page_vendor_info' == $hook) {
		wp_enqueue_style( 'bootstrap_css', plugins_url('css/bootstrap.min.css', __FILE__) );
		wp_enqueue_style( 'query_builder_css', plugins_url('css/query-builder.default.min.css', __FILE__) );
		wp_enqueue_style( 'fontawesome_css', plugins_url('css/fontawesome-all.min.css', __FILE__) );
		
		//wp_enqueue_script( 'popper_js', plugins_url('js/popper.min.js', __FILE__) );
		wp_enqueue_script( 'bootstrap_js', plugins_url('js/bootstrap.min.js', __FILE__) );

		wp_enqueue_script( 'tomo_utility_js', plugins_url('js/tomoutil.js', __FILE__) );
		
		wp_enqueue_script( 'sql_parser_js', plugins_url('js/sql-parser.min.js', __FILE__) );
		wp_enqueue_script( 'moment_js', plugins_url('js/moment.js', __FILE__) );
		wp_enqueue_script( 'query_builder_js', plugins_url('js/query-builder.standalone.min.js', __FILE__) );
		wp_enqueue_script( 'query_builder_i18n_en_js', plugins_url('js/query-builder.en.js', __FILE__) );
		wp_enqueue_script( 'query_builder_i18n_cn_js', plugins_url('js/query-builder.zh-CN.js', __FILE__) );
	}
	
	if(endsWith($hook, '_page_vendor_request')) {
		wp_enqueue_style( 'bootstrap_css', plugins_url('css/bootstrap.min.css', __FILE__) );
		wp_enqueue_style( 'fontawesome_css', plugins_url('css/fontawesome-all.min.css', __FILE__) );
		
		//wp_enqueue_script( 'popper_js', plugins_url('js/popper.min.js', __FILE__) );
		wp_enqueue_script( 'bootstrap_js', plugins_url('js/bootstrap.min.js', __FILE__) );
	}
	
	if('toplevel_page_client_info' == $hook) {
		wp_enqueue_style( 'bootstrap_css', plugins_url('css/bootstrap.min.css', __FILE__) );
		wp_enqueue_style( 'query_builder_css', plugins_url('css/query-builder.default.min.css', __FILE__) );
		wp_enqueue_style( 'fontawesome_css', plugins_url('css/fontawesome-all.min.css', __FILE__) );
		
		//wp_enqueue_script( 'popper_js', plugins_url('js/popper.min.js', __FILE__) );
		wp_enqueue_script( 'bootstrap_js', plugins_url('js/bootstrap.min.js', __FILE__) );

		wp_enqueue_script( 'tomo_utility_js', plugins_url('js/tomoutil.js', __FILE__) );
		
		wp_enqueue_script( 'sql_parser_js', plugins_url('js/sql-parser.min.js', __FILE__) );
		wp_enqueue_script( 'moment_js', plugins_url('js/moment.js', __FILE__) );
		wp_enqueue_script( 'query_builder_js', plugins_url('js/query-builder.standalone.min.js', __FILE__) );
		wp_enqueue_script( 'query_builder_i18n_en_js', plugins_url('js/query-builder.en.js', __FILE__) );
		wp_enqueue_script( 'query_builder_i18n_cn_js', plugins_url('js/query-builder.zh-CN.js', __FILE__) );
	}
	
	if(endsWith($hook, '_page_client_request')) {
		wp_enqueue_style( 'bootstrap_css', plugins_url('css/bootstrap.min.css', __FILE__) );
		wp_enqueue_style( 'fontawesome_css', plugins_url('css/fontawesome-all.min.css', __FILE__) );
		
		//wp_enqueue_script( 'popper_js', plugins_url('js/popper.min.js', __FILE__) );
		wp_enqueue_script( 'bootstrap_js', plugins_url('js/bootstrap.min.js', __FILE__) );
	}
	
	if('toplevel_page_warehouse_info' == $hook) {
		wp_enqueue_style( 'bootstrap_css', plugins_url('css/bootstrap.min.css', __FILE__) );
		wp_enqueue_style( 'query_builder_css', plugins_url('css/query-builder.default.min.css', __FILE__) );
		wp_enqueue_style( 'fontawesome_css', plugins_url('css/fontawesome-all.min.css', __FILE__) );
		
		//wp_enqueue_script( 'popper_js', plugins_url('js/popper.min.js', __FILE__) );
		wp_enqueue_script( 'bootstrap_js', plugins_url('js/bootstrap.min.js', __FILE__) );

		wp_enqueue_script( 'tomo_utility_js', plugins_url('js/tomoutil.js', __FILE__) );
		
		wp_enqueue_script( 'sql_parser_js', plugins_url('js/sql-parser.min.js', __FILE__) );
		wp_enqueue_script( 'moment_js', plugins_url('js/moment.js', __FILE__) );
		wp_enqueue_script( 'query_builder_js', plugins_url('js/query-builder.standalone.min.js', __FILE__) );
		wp_enqueue_script( 'query_builder_i18n_en_js', plugins_url('js/query-builder.en.js', __FILE__) );
		wp_enqueue_script( 'query_builder_i18n_cn_js', plugins_url('js/query-builder.zh-CN.js', __FILE__) );
	}
	
	if(endsWith($hook, '_page_warehouse_request')) {
		wp_enqueue_style( 'bootstrap_css', plugins_url('css/bootstrap.min.css', __FILE__) );
		wp_enqueue_style( 'fontawesome_css', plugins_url('css/fontawesome-all.min.css', __FILE__) );
		
		//wp_enqueue_script( 'popper_js', plugins_url('js/popper.min.js', __FILE__) );
		wp_enqueue_script( 'bootstrap_js', plugins_url('js/bootstrap.min.js', __FILE__) );
	}
	
	if('toplevel_page_order_info' == $hook) {
		wp_enqueue_style( 'bootstrap_css', plugins_url('css/bootstrap.min.css', __FILE__) );
		wp_enqueue_style( 'query_builder_css', plugins_url('css/query-builder.default.min.css', __FILE__) );
		wp_enqueue_style( 'fontawesome_css', plugins_url('css/fontawesome-all.min.css', __FILE__) );
		
		//wp_enqueue_script( 'popper_js', plugins_url('js/popper.min.js', __FILE__) );
		wp_enqueue_script( 'bootstrap_js', plugins_url('js/bootstrap.min.js', __FILE__) );

		wp_enqueue_script( 'tomo_utility_js', plugins_url('js/tomoutil.js', __FILE__) );
		
		wp_enqueue_script( 'sql_parser_js', plugins_url('js/sql-parser.min.js', __FILE__) );
		wp_enqueue_script( 'moment_js', plugins_url('js/moment.js', __FILE__) );
		wp_enqueue_script( 'query_builder_js', plugins_url('js/query-builder.standalone.min.js', __FILE__) );
		wp_enqueue_script( 'query_builder_i18n_en_js', plugins_url('js/query-builder.en.js', __FILE__) );
		wp_enqueue_script( 'query_builder_i18n_cn_js', plugins_url('js/query-builder.zh-CN.js', __FILE__) );
	}
	
	if('toplevel_page_workflow_1' == $hook) {
		wp_enqueue_style( 'bootstrap_css', plugins_url('css/bootstrap.min.css', __FILE__) );
		wp_enqueue_style( 'query_builder_css', plugins_url('css/query-builder.default.min.css', __FILE__) );
		wp_enqueue_style( 'fontawesome_css', plugins_url('css/fontawesome-all.min.css', __FILE__) );
		
		//wp_enqueue_script( 'popper_js', plugins_url('js/popper.min.js', __FILE__) );
		wp_enqueue_script( 'bootstrap_js', plugins_url('js/bootstrap.min.js', __FILE__) );

		wp_enqueue_script( 'tomo_utility_js', plugins_url('js/tomoutil.js', __FILE__) );
		
		wp_enqueue_script( 'sql_parser_js', plugins_url('js/sql-parser.min.js', __FILE__) );
		wp_enqueue_script( 'moment_js', plugins_url('js/moment.js', __FILE__) );
		wp_enqueue_script( 'query_builder_js', plugins_url('js/query-builder.standalone.min.js', __FILE__) );
		wp_enqueue_script( 'query_builder_i18n_en_js', plugins_url('js/query-builder.en.js', __FILE__) );
		wp_enqueue_script( 'query_builder_i18n_cn_js', plugins_url('js/query-builder.zh-CN.js', __FILE__) );
	}
	
	if(endsWith($hook, '_page_wf1_request')) {
		wp_enqueue_style( 'bootstrap_css', plugins_url('css/bootstrap.min.css', __FILE__) );
		wp_enqueue_style( 'fontawesome_css', plugins_url('css/fontawesome-all.min.css', __FILE__) );
		
		//wp_enqueue_script( 'popper_js', plugins_url('js/popper.min.js', __FILE__) );
		wp_enqueue_script( 'bootstrap_js', plugins_url('js/bootstrap.min.js', __FILE__) );

		wp_enqueue_script( 'tomo_utility_js', plugins_url('js/tomoutil.js', __FILE__) );
	}
	
	if('toplevel_page_workflow_2' == $hook) {
		wp_enqueue_style( 'bootstrap_css', plugins_url('css/bootstrap.min.css', __FILE__) );
		wp_enqueue_style( 'query_builder_css', plugins_url('css/query-builder.default.min.css', __FILE__) );
		wp_enqueue_style( 'fontawesome_css', plugins_url('css/fontawesome-all.min.css', __FILE__) );
		
		//wp_enqueue_script( 'popper_js', plugins_url('js/popper.min.js', __FILE__) );
		wp_enqueue_script( 'bootstrap_js', plugins_url('js/bootstrap.min.js', __FILE__) );

		wp_enqueue_script( 'tomo_utility_js', plugins_url('js/tomoutil.js', __FILE__) );
		
		wp_enqueue_script( 'sql_parser_js', plugins_url('js/sql-parser.min.js', __FILE__) );
		wp_enqueue_script( 'moment_js', plugins_url('js/moment.js', __FILE__) );
		wp_enqueue_script( 'query_builder_js', plugins_url('js/query-builder.standalone.min.js', __FILE__) );
		wp_enqueue_script( 'query_builder_i18n_en_js', plugins_url('js/query-builder.en.js', __FILE__) );
		wp_enqueue_script( 'query_builder_i18n_cn_js', plugins_url('js/query-builder.zh-CN.js', __FILE__) );
	}
	
	if(endsWith($hook, '_page_wf2_request')) {
		wp_enqueue_style( 'bootstrap_css', plugins_url('css/bootstrap.min.css', __FILE__) );
		wp_enqueue_style( 'fontawesome_css', plugins_url('css/fontawesome-all.min.css', __FILE__) );
		
		//wp_enqueue_script( 'popper_js', plugins_url('js/popper.min.js', __FILE__) );
		wp_enqueue_script( 'bootstrap_js', plugins_url('js/bootstrap.min.js', __FILE__) );

		wp_enqueue_script( 'tomo_utility_js', plugins_url('js/tomoutil.js', __FILE__) );
	}
	
	if('toplevel_page_workflow_3' == $hook) {
		wp_enqueue_style( 'bootstrap_css', plugins_url('css/bootstrap.min.css', __FILE__) );
		wp_enqueue_style( 'query_builder_css', plugins_url('css/query-builder.default.min.css', __FILE__) );
		wp_enqueue_style( 'fontawesome_css', plugins_url('css/fontawesome-all.min.css', __FILE__) );
		
		//wp_enqueue_script( 'popper_js', plugins_url('js/popper.min.js', __FILE__) );
		wp_enqueue_script( 'bootstrap_js', plugins_url('js/bootstrap.min.js', __FILE__) );

		wp_enqueue_script( 'tomo_utility_js', plugins_url('js/tomoutil.js', __FILE__) );
		
		wp_enqueue_script( 'sql_parser_js', plugins_url('js/sql-parser.min.js', __FILE__) );
		wp_enqueue_script( 'moment_js', plugins_url('js/moment.js', __FILE__) );
		wp_enqueue_script( 'query_builder_js', plugins_url('js/query-builder.standalone.min.js', __FILE__) );
		wp_enqueue_script( 'query_builder_i18n_en_js', plugins_url('js/query-builder.en.js', __FILE__) );
		wp_enqueue_script( 'query_builder_i18n_cn_js', plugins_url('js/query-builder.zh-CN.js', __FILE__) );
	}
	
	if(endsWith($hook, '_page_wf3_request')) {
		wp_enqueue_style( 'bootstrap_css', plugins_url('css/bootstrap.min.css', __FILE__) );
		wp_enqueue_style( 'fontawesome_css', plugins_url('css/fontawesome-all.min.css', __FILE__) );
		
		//wp_enqueue_script( 'popper_js', plugins_url('js/popper.min.js', __FILE__) );
		wp_enqueue_script( 'bootstrap_js', plugins_url('js/bootstrap.min.js', __FILE__) );

		wp_enqueue_script( 'tomo_utility_js', plugins_url('js/tomoutil.js', __FILE__) );
	}
	
	if('toplevel_page_workflow_4' == $hook) {
		wp_enqueue_style( 'bootstrap_css', plugins_url('css/bootstrap.min.css', __FILE__) );
		wp_enqueue_style( 'query_builder_css', plugins_url('css/query-builder.default.min.css', __FILE__) );
		wp_enqueue_style( 'fontawesome_css', plugins_url('css/fontawesome-all.min.css', __FILE__) );
		
		//wp_enqueue_script( 'popper_js', plugins_url('js/popper.min.js', __FILE__) );
		wp_enqueue_script( 'bootstrap_js', plugins_url('js/bootstrap.min.js', __FILE__) );

		wp_enqueue_script( 'tomo_utility_js', plugins_url('js/tomoutil.js', __FILE__) );
		
		wp_enqueue_script( 'sql_parser_js', plugins_url('js/sql-parser.min.js', __FILE__) );
		wp_enqueue_script( 'moment_js', plugins_url('js/moment.js', __FILE__) );
		wp_enqueue_script( 'query_builder_js', plugins_url('js/query-builder.standalone.min.js', __FILE__) );
		wp_enqueue_script( 'query_builder_i18n_en_js', plugins_url('js/query-builder.en.js', __FILE__) );
		wp_enqueue_script( 'query_builder_i18n_cn_js', plugins_url('js/query-builder.zh-CN.js', __FILE__) );
	}
	
	if(endsWith($hook, '_page_wf4_request')) {
		wp_enqueue_style( 'bootstrap_css', plugins_url('css/bootstrap.min.css', __FILE__) );
		wp_enqueue_style( 'fontawesome_css', plugins_url('css/fontawesome-all.min.css', __FILE__) );
		
		//wp_enqueue_script( 'popper_js', plugins_url('js/popper.min.js', __FILE__) );
		wp_enqueue_script( 'bootstrap_js', plugins_url('js/bootstrap.min.js', __FILE__) );

		wp_enqueue_script( 'tomo_utility_js', plugins_url('js/tomoutil.js', __FILE__) );
	}
	
	if('toplevel_page_workflow_5' == $hook) {
		wp_enqueue_style( 'bootstrap_css', plugins_url('css/bootstrap.min.css', __FILE__) );
		wp_enqueue_style( 'query_builder_css', plugins_url('css/query-builder.default.min.css', __FILE__) );
		wp_enqueue_style( 'fontawesome_css', plugins_url('css/fontawesome-all.min.css', __FILE__) );
		
		//wp_enqueue_script( 'popper_js', plugins_url('js/popper.min.js', __FILE__) );
		wp_enqueue_script( 'bootstrap_js', plugins_url('js/bootstrap.min.js', __FILE__) );

		wp_enqueue_script( 'tomo_utility_js', plugins_url('js/tomoutil.js', __FILE__) );
		
		wp_enqueue_script( 'sql_parser_js', plugins_url('js/sql-parser.min.js', __FILE__) );
		wp_enqueue_script( 'moment_js', plugins_url('js/moment.js', __FILE__) );
		wp_enqueue_script( 'query_builder_js', plugins_url('js/query-builder.standalone.min.js', __FILE__) );
		wp_enqueue_script( 'query_builder_i18n_en_js', plugins_url('js/query-builder.en.js', __FILE__) );
		wp_enqueue_script( 'query_builder_i18n_cn_js', plugins_url('js/query-builder.zh-CN.js', __FILE__) );
	}
	
	if(endsWith($hook, '_page_wf5_request')) {
		wp_enqueue_style( 'bootstrap_css', plugins_url('css/bootstrap.min.css', __FILE__) );
		wp_enqueue_style( 'fontawesome_css', plugins_url('css/fontawesome-all.min.css', __FILE__) );
		
		//wp_enqueue_script( 'popper_js', plugins_url('js/popper.min.js', __FILE__) );
		wp_enqueue_script( 'bootstrap_js', plugins_url('js/bootstrap.min.js', __FILE__) );

		wp_enqueue_script( 'tomo_utility_js', plugins_url('js/tomoutil.js', __FILE__) );
	}
	
	if('toplevel_page_workflow_6' == $hook) {
		wp_enqueue_style( 'bootstrap_css', plugins_url('css/bootstrap.min.css', __FILE__) );
		wp_enqueue_style( 'query_builder_css', plugins_url('css/query-builder.default.min.css', __FILE__) );
		wp_enqueue_style( 'fontawesome_css', plugins_url('css/fontawesome-all.min.css', __FILE__) );
		
		//wp_enqueue_script( 'popper_js', plugins_url('js/popper.min.js', __FILE__) );
		wp_enqueue_script( 'bootstrap_js', plugins_url('js/bootstrap.min.js', __FILE__) );

		wp_enqueue_script( 'tomo_utility_js', plugins_url('js/tomoutil.js', __FILE__) );
		
		wp_enqueue_script( 'sql_parser_js', plugins_url('js/sql-parser.min.js', __FILE__) );
		wp_enqueue_script( 'moment_js', plugins_url('js/moment.js', __FILE__) );
		wp_enqueue_script( 'query_builder_js', plugins_url('js/query-builder.standalone.min.js', __FILE__) );
		wp_enqueue_script( 'query_builder_i18n_en_js', plugins_url('js/query-builder.en.js', __FILE__) );
		wp_enqueue_script( 'query_builder_i18n_cn_js', plugins_url('js/query-builder.zh-CN.js', __FILE__) );
		//wp_enqueue_script( 'query_builder_tooltip_errors_js', plugins_url('js/bt-tooltip-errors.js', __FILE__) );
	}
	
	if(endsWith($hook, '_page_wf6_request')) {
		wp_enqueue_style( 'bootstrap_css', plugins_url('css/bootstrap.min.css', __FILE__) );
		wp_enqueue_style( 'fontawesome_css', plugins_url('css/fontawesome-all.min.css', __FILE__) );
		
		//wp_enqueue_script( 'popper_js', plugins_url('js/popper.min.js', __FILE__) );
		wp_enqueue_script( 'bootstrap_js', plugins_url('js/bootstrap.min.js', __FILE__) );

		wp_enqueue_script( 'tomo_utility_js', plugins_url('js/tomoutil.js', __FILE__) );
	}
	
	if('toplevel_page_template_folder' == $hook) {
		wp_enqueue_style( 'bootstrap_css', plugins_url('css/bootstrap.min.css', __FILE__) );
		wp_enqueue_style( 'fontawesome_css', plugins_url('css/fontawesome-all.min.css', __FILE__) );
		
		//wp_enqueue_script( 'popper_js', plugins_url('js/popper.min.js', __FILE__) );
		wp_enqueue_script( 'bootstrap_js', plugins_url('js/bootstrap.min.js', __FILE__) );

		wp_enqueue_script( 'tomo_utility_js', plugins_url('js/tomoutil.js', __FILE__) );
	}
	
	if('toplevel_page_gf_edit_forms' == $hook) {
		// Global CSS and Script
		wp_enqueue_script( 'tomo_global_js', plugins_url('js/tomoglobal.js', __FILE__) );
	}
}

add_action( 'wp_ajax_load_prod_detail', 'load_prod_detail' );
function load_prod_detail() {
	if(!isset( $_GET['pid'] )){
		echo 'error';
		die();
	}
	
	$pid = absint( $_GET['pid'] );
	
	$tb = array();
	
	$thl = array();
	$thk = array();
	
	global $wpdb;
	$prodFields = $wpdb->get_results('SHOW FULL COLUMNS FROM ms_product', ARRAY_N);
	for($i = 0; $i < count($prodFields); $i++){
		array_push($thl, $prodFields[$i][8]);
		array_push($thk, $prodFields[$i][0]);
	}
	
	array_push($tb, $thl);
	array_push($tb, $thk);
	
$sql = <<<SQL
	SELECT p.*
	FROM ms_product p
	LEFT JOIN
		(SELECT CD, clientName
		FROM ms_product
		WHERE id = %d) AS t
	ON p.CD = t.CD AND p.clientName = t.clientName
	WHERE t.CD IS NOT NULL
	ORDER BY p.createdTime DESC
SQL;
	$sql = $wpdb->prepare($sql, array($pid));

	$prodInfos = $wpdb->get_results($sql, ARRAY_A);
	
	foreach ( $prodInfos as $prod ) {
		$rc = array();
		for($i = 0; $i < count($thk); $i++){
			array_push($rc, $prod[$thk[$i]]);
		}
		
		array_push($tb, $rc);
	}
	
	echo json_encode($tb);
	
	die();
}

add_action( 'wp_ajax_save_product', 'save_product' );
add_action( 'wp_ajax_nopriv_save_product', 'save_product' );
function save_product() {
	$clientName 				= $_POST['clientName'];
	$productCategory 			= $_POST['productCategory'];
	$CD 							= $_POST['CD'];
	$englishName 				= $_POST['englishName'];
	$customerEngAbbr		= $_POST['customerEngAbbr'];
	$customerChsAbbr			= $_POST['customerChsAbbr'];
	$customerMaterial			= $_POST['customerMaterial'];
	$chineseName 				= $_POST['chineseName'];
	$declareUnitPriceUSD		= $_POST['declareUnitPriceUSD'];
	$numberInOuterBox		= $_POST['numberInOuterBox'];
	$outerBoxW					= $_POST['outerBoxW'];
	$outerBoxD 					= $_POST['outerBoxD'];
	$outerBoxH		 			= $_POST['outerBoxH'];
	$outerBoxVolume 			= $_POST['outerBoxVolume'];
	$outerBoxNetWeight		= $_POST['outerBoxNetWeight'];
	$outerBoxGrossWeight	= $_POST['outerBoxGrossWeight'];
	$productInspection 		= $_POST['productInspection'];
	$customerCode	 			= $_POST['customerCode'];
	$supplier		 				= $_POST['supplier'];
	$shipping		 			= $_POST['shipping'];
	$salesFOB		 			= $_POST['salesFOB'];
	$factoryPriceRMB 			= $_POST['factoryPriceRMB'];
	$factoryPriceUSD			= $_POST['factoryPriceUSD'];
	$haveInnerBox 				= $_POST['haveInnerBox'];
	$numberInInnerBox 		= $_POST['numberInInnerBox'];
	$remark			 			= $_POST['remark'];
	$createdBy			 		= get_user_by('id', $_POST['createdBy'])->user_login;
	global $wpdb;
$sql = <<<SQL
	INSERT INTO ms_product
	(clientName, productCategory, CD, englishName, customerEngAbbr, customerChsAbbr, customerMaterial, chineseName, declareUnitPriceUSD, numberInOuterBox, outerBoxW, outerBoxD, outerBoxH, outerBoxVolume, outerBoxNetWeight, outerBoxGrossWeight, productInspection, customerCode, supplier, shipping, salesFOB, factoryPriceRMB, factoryPriceUSD, haveInnerBox, numberInInnerBox, remark, createdBy, createdTime)
	VALUES ('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', %f, %d, %f, %f, %f, %f, %f, %f, '%s', '%s', '%s', '%s', %f, %f, %f, '%s', %d, '%s', '%s', NOW())
SQL;
	$wpdb->query( $wpdb->prepare($sql, array(
										$clientName,
										$productCategory,
										$CD,
										$englishName,
										$customerEngAbbr,
										$customerChsAbbr,
										$customerMaterial,
										$chineseName,
										$declareUnitPriceUSD,
										$numberInOuterBox,
										$outerBoxW,
										$outerBoxD,
										$outerBoxH,
										$outerBoxVolume,
										$outerBoxNetWeight,
										$outerBoxGrossWeight,
										$productInspection,
										$customerCode,
										$supplier,
										$shipping,
										$salesFOB,
										$factoryPriceRMB,
										$factoryPriceUSD,
										$haveInnerBox,
										$numberInInnerBox,
										$remark,
										$createdBy
									)
							)
				);
	die();
}

///////////////////////////////////////////////////////////////////////////////////
add_action( 'wp_ajax_load_vendor_detail', 'load_vendor_detail' );
function load_vendor_detail() {
	if(!isset( $_GET['pid'] )){
		echo 'error';
		die();
	}
	
	$pid = absint( $_GET['pid'] );
	
	$tb = array();
	
	$thl = array();
	$thk = array();
	
	global $wpdb;
	$prodFields = $wpdb->get_results('SHOW FULL COLUMNS FROM ms_vendor', ARRAY_N);
	for($i = 0; $i < count($prodFields); $i++){
		array_push($thl, $prodFields[$i][8]);
		array_push($thk, $prodFields[$i][0]);
	}
	
	array_push($tb, $thl);
	array_push($tb, $thk);
	
$sql = <<<SQL
	SELECT p.*
	FROM ms_vendor p
	LEFT JOIN
		(SELECT shortName
		FROM ms_vendor
		WHERE id = %d) AS t
	ON p.shortName = t.shortName
	WHERE t.shortName IS NOT NULL
	ORDER BY p.createdTime DESC
SQL;
	$sql = $wpdb->prepare($sql, array($pid));

	$prodInfos = $wpdb->get_results($sql, ARRAY_A);
	
	foreach ( $prodInfos as $prod ) {
		$rc = array();
		for($i = 0; $i < count($thk); $i++){
			array_push($rc, $prod[$thk[$i]]);
		}
		
		array_push($tb, $rc);
	}
	
	echo json_encode($tb);
	
	die();
}

add_action( 'wp_ajax_save_vendor', 'save_vendor' );
add_action( 'wp_ajax_nopriv_save_vendor', 'save_vendor' );
function save_vendor() {
	$name 				= $_POST['name'];
	$shortName			= $_POST['shortName'];
	$factoryAddress		= $_POST['factoryAddress'];
	$factoryContract	= $_POST['factoryContract'];
	$factoryPhone		= $_POST['factoryPhone'];
	$factoryFax			= $_POST['factoryFax'];
	$createdBy			= get_user_by('id', $_POST['createdBy'])->user_login;
	
	global $wpdb;
$sql = <<<SQL
	INSERT INTO ms_vendor
	(name, shortName, factoryAddress, factoryContract, factoryPhone, factoryFax, createdBy, createdTime)
	VALUES ('%s', '%s', '%s', '%s', '%s', '%s', '%s', NOW())
SQL;
	$wpdb->query( $wpdb->prepare($sql, array(
										$name,
										$shortName,
										$factoryAddress,
										$factoryContract,
										$factoryPhone,
										$factoryFax,
										$createdBy
									)
							)
				);
	die();
}

///////////////////////////////////////////////////////////////////////////////////
add_action( 'wp_ajax_load_client_detail', 'load_client_detail' );
function load_client_detail() {
	if(!isset( $_GET['pid'] )){
		echo 'error';
		die();
	}
	
	$pid = absint( $_GET['pid'] );
	
	$tb = array();
	
	$thl = array();
	$thk = array();
	
	global $wpdb;
	$prodFields = $wpdb->get_results('SHOW FULL COLUMNS FROM ms_client', ARRAY_N);
	for($i = 0; $i < count($prodFields); $i++){
		array_push($thl, $prodFields[$i][8]);
		array_push($thk, $prodFields[$i][0]);
	}
	
	array_push($tb, $thl);
	array_push($tb, $thk);
	
$sql = <<<SQL
	SELECT p.*
	FROM ms_client p
	LEFT JOIN
		(SELECT shortName
		FROM ms_client
		WHERE id = %d) AS t
	ON p.shortName = t.shortName
	WHERE t.shortName IS NOT NULL
	ORDER BY p.createdTime DESC
SQL;
	$sql = $wpdb->prepare($sql, array($pid));

	$prodInfos = $wpdb->get_results($sql, ARRAY_A);
	
	foreach ( $prodInfos as $prod ) {
		$rc = array();
		for($i = 0; $i < count($thk); $i++){
			array_push($rc, $prod[$thk[$i]]);
		}
		
		array_push($tb, $rc);
	}
	
	echo json_encode($tb);
	
	die();
}

add_action( 'wp_ajax_save_client', 'save_client' );
add_action( 'wp_ajax_nopriv_save_client', 'save_client' );
function save_client() {
	$name 				= $_POST['name'];
	$shortName			= $_POST['shortName'];
	$address			= $_POST['address'];
	$contact			= $_POST['contact'];
	$phone				= $_POST['phone'];
	$email				= $_POST['email'];
	$billingMethod		= $_POST['billingMethod'];
	$createdBy			= get_user_by('id', $_POST['createdBy'])->user_login;
	
	global $wpdb;
$sql = <<<SQL
	INSERT INTO ms_client
	(name, shortName, address, contact, phone, email, billingMethod, createdBy, createdTime)
	VALUES ('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', NOW())
SQL;
	$wpdb->query( $wpdb->prepare($sql, array(
											$name,
											$shortName,
											$address,
											$contact,
											$phone,
											$email,
											$billingMethod,
											$createdBy
									)
							)
				);
	die();
}

///////////////////////////////////////////////////////////////////////////////////
add_action( 'wp_ajax_load_warehouse_detail', 'load_warehouse_detail' );
function load_warehouse_detail() {
	if(!isset( $_GET['pid'] )){
		echo 'error';
		die();
	}
	
	$pid = absint( $_GET['pid'] );
	
	$tb = array();
	
	$thl = array();
	$thk = array();
	
	global $wpdb;
	$prodFields = $wpdb->get_results('SHOW FULL COLUMNS FROM ms_warehouse', ARRAY_N);
	for($i = 0; $i < count($prodFields); $i++){
		array_push($thl, $prodFields[$i][8]);
		array_push($thk, $prodFields[$i][0]);
	}
	
	array_push($tb, $thl);
	array_push($tb, $thk);
	
$sql = <<<SQL
	SELECT p.*
	FROM ms_warehouse p
	LEFT JOIN
		(SELECT name
		FROM ms_warehouse
		WHERE id = %d) AS t
	ON p.name = t.name
	WHERE t.name IS NOT NULL
	ORDER BY p.createdTime DESC
SQL;
	$sql = $wpdb->prepare($sql, array($pid));

	$prodInfos = $wpdb->get_results($sql, ARRAY_A);
	
	foreach ( $prodInfos as $prod ) {
		$rc = array();
		for($i = 0; $i < count($thk); $i++){
			array_push($rc, $prod[$thk[$i]]);
		}
		
		array_push($tb, $rc);
	}
	
	echo json_encode($tb);
	
	die();
}

add_action( 'wp_ajax_save_warehouse', 'save_warehouse' );
add_action( 'wp_ajax_nopriv_save_warehouse', 'save_warehouse' );
function save_warehouse() {
	$name 				= $_POST['name'];
	$address			= $_POST['address'];
	$contact			= $_POST['contact'];
	$phone				= $_POST['phone'];
	$createdBy			= get_user_by('id', $_POST['createdBy'])->user_login;
	
	global $wpdb;
$sql = <<<SQL
	INSERT INTO ms_warehouse
	(name, address, contact, phone, createdBy, createdTime)
	VALUES ('%s', '%s', '%s', '%s', '%s', NOW())
SQL;
	$wpdb->query( $wpdb->prepare($sql, array(
											$name,
											$address,
											$contact,
											$phone,
											$createdBy
									)
							)
				);
	die();
}

///////////////////////////////////////////////////////////////////////////////////////

add_action( 'wp_ajax_del_order', 'del_order' );
//add_action( 'wp_ajax_nopriv_del_order', 'del_order' );
function del_order() {
	$ids = $_POST['ids'];
	
	global $wpdb;
	$wpdb->query("DELETE FROM ms_order WHERE statusCode = 0 AND id IN ($ids)");
	$wpdb->query("DELETE rl_order_container FROM rl_order_container LEFT JOIN ms_order ON rl_order_container.orderId = ms_order.id WHERE ms_order.statusCode = 0 AND ms_order.id IN ($ids)");

	die();
}

add_action( 'wp_ajax_upd_order_invoice', 'upd_order_invoice' );
//add_action( 'wp_ajax_nopriv_del_order', 'del_order' );
function upd_order_invoice() {
	$pid = $_POST['pid'];
	$ivn = $_POST['ivn'];
	
	global $wpdb;
	$wpdb->update( 
		'ms_order', 
		array( 
			'invoiceNumber' => $ivn
		), 
		array( 'id' => $pid ), 
		array( 
			'%s'
		), 
		array( '%d' ) 
	);

	die();
}

add_action( 'wp_ajax_upd_order_verificationNumber', 'upd_order_verificationNumber' );
//add_action( 'wp_ajax_nopriv_del_order', 'del_order' );
function upd_order_verificationNumber() {
	$pid = $_POST['pid'];
	$vfn = $_POST['vfn'];
	
	global $wpdb;
	$wpdb->update( 
		'ms_order', 
		array( 
			'verificationNumber' => $vfn
		), 
		array( 'id' => $pid ), 
		array( 
			'%s'
		), 
		array( '%d' ) 
	);

	die();
}

add_action( 'wp_ajax_upd_order_containers', 'upd_order_containers' );
//add_action( 'wp_ajax_nopriv_del_order', 'del_order' );
function upd_order_containers() {
	$pid = $_POST['pid'];
	$ctn = $_POST['ctn'];
	$ttl = $_POST['ttl'];

	$arr = array();
	if(strpos($ctn, ':') === false){
		$arr[] = array(
						'cid' => $ctn,
						'qty' => $ttl
					  );
	} else {
		foreach(explode("\n", $ctn) AS $ln){
			$arr[] = array(
							'cid' => explode(':', $ln)[0],
							'qty' => explode(':', $ln)[1]
						  );
		}
	}
	
	global $wpdb;
	$wpdb->delete( 'rl_order_container', array( 'orderId' => $pid ) );
	if(trim($ctn) != ''){
		if(strpos($ctn, ':') === false){
			$cid = explode(':', explode('\n', $ctn)[0])[0];
			$qty = $ttl;
			
			$wpdb->insert( 
					'rl_order_container', 
					array( 
						'orderId'	=> $pid, 
						'cid' 		=> $cid, 
						'qty' 		=> $qty
					), 
					array( 
						'%d', 
						'%s',
						'%d'
					)
				);
		} else {
			foreach(explode("\n", $ctn) AS $ln){
				$wpdb->insert( 
					'rl_order_container', 
					array( 
						'orderId'	=> $pid, 
						'cid' 		=> explode(':', $ln)[0],
						'qty' 		=> explode(':', $ln)[1]
					), 
					array( 
						'%d', 
						'%s',
						'%d'
					)
				);
			}
		}
	}

	die();
}

add_action( 'wp_ajax_add_to_session_pool', 'add_to_session_pool' );
function add_to_session_pool() {
	$ids 	= $_POST['ids'];
	$pool 	= $_POST['pool'];

	$idArr 	= explode(',', $ids);
	
	session_start();
	$_SESSION[$pool] = isset($_SESSION[$pool]) ? $_SESSION[$pool] : array();
	$_SESSION[$pool] = array_unique(array_merge($_SESSION[$pool], $idArr));

	echo count($_SESSION[$pool]);
	die();
}

add_action( 'wp_ajax_remove_from_session_pool', 'remove_from_session_pool' );
function remove_from_session_pool() {
	$ids 	= $_POST['ids'];
	$pool 	= $_POST['pool'];

	$idArr 	= explode(',', $ids);
	
	session_start();
	$_SESSION[$pool] = isset($_SESSION[$pool]) ? $_SESSION[$pool] : array();
	$_SESSION[$pool] = array_diff($_SESSION[$pool], $idArr);

	echo count($_SESSION[$pool]);
	die();
}

add_action( 'wp_ajax_get_pool_orders', 'get_pool_orders' );
function get_pool_orders() {
	$pool 	= $_POST['pool'];
	$status = $_POST['status'];
	
	$result = array();
	
	global $wpdb;
	
	$prodFields = $wpdb->get_results('SHOW FULL COLUMNS FROM vw_all_orders', ARRAY_N);
	$prodCols = array();
	$prodColNames = array();
	for($i = 0; $i < count($prodFields); $i++){
		array_push($prodCols,$prodFields[$i][0]);
		array_push($prodColNames,$prodFields[$i][8]);
	}
	
	array_push($result,$prodCols);
	array_push($result,$prodColNames);
	
	session_start();
	$filters = isset($_SESSION[$pool]) && count($_SESSION[$pool]) > 0 ? " statusCode = $status AND id IN (" . implode(',', $_SESSION[$pool]) . ') ' : " statusCode = $status AND 1 = 0 ";
	
$sql = <<<SQL
	SELECT *
	FROM vw_all_orders
	WHERE $filters
SQL;

	$prodInfos = $wpdb->get_results($sql, ARRAY_N);
	
	echo json_encode(array_merge($result, $prodInfos));
	die();
}

add_action( 'wp_ajax_get_pool_finance_orders', 'get_pool_finance_orders' );
function get_pool_finance_orders() {
	$pool 	= $_POST['pool'];
	
	$result = array();
	
	$headTitle 	= 	[
						'编号',
						'年',
						'月',
						'日',
						'公司名称',
						'内部编号',
						'发票号码',
						'客户名',
						'报关单号',
						'商品编码',
						'报关名称',
						'海关编码',
						'RMB采购单价',
						'USD采购单价',
						'出货数量',
						'RMB采购金额',
						'USD采购金额',
						'退税率',
						'退税额',
						'供应商',
						'净重',
						'报关单位',
						'PI号码',
						'报关单价',
						'报关金额',
						'汇率',
						'利润额',
						'利润率',
						'状态码',
						'关联文件',
						'ETD'
					];
					
	global $wpdb;
	
	$prodFields = $wpdb->get_results('SHOW FULL COLUMNS FROM vw_finance_report', ARRAY_N);
	$prodCols = array();
	$prodColNames = array();
	for($i = 0; $i < count($prodFields); $i++){
		array_push($prodCols,$prodFields[$i][0]);
		array_push($prodColNames,$headTitle[$i]);
	}
	
	array_push($result,$prodCols);
	array_push($result,$prodColNames);
	
	session_start();
	$filters = isset($_SESSION[$pool]) && count($_SESSION[$pool]) > 0 ? " id IN (" . implode(',', $_SESSION[$pool]) . ') ' : " 1 = 0 ";
	
$sql = <<<SQL
	SELECT *
	FROM vw_finance_report
	WHERE $filters
SQL;

	$prodInfos = $wpdb->get_results($sql, ARRAY_N);
	
	echo json_encode(array_merge($result, $prodInfos));
	die();
}

add_action('admin_footer', 'custom_admin_js');
function custom_admin_js() {
    echo "<script type='text/javascript' >document.body.className+=' folded';</script>";
}


add_action( 'wp_ajax_update_order_status_code', 'update_order_status_code' );
add_action( 'wp_ajax_nopriv_update_order_status_code', 'update_order_status_code' );
function update_order_status_code() {
	if(!isset($_GET['code']) || !isset($_POST['orderIds'])){
		die();
	}
		
	$code 				= $_GET['code'];
	$orderIds 			= $_POST['orderIds'];
	
	global $wpdb;
$sql = <<<SQL
	UPDATE ms_order
	SET
		statusCode = %d
	WHERE
		id IN ($orderIds)
SQL;
	$wpdb->query($wpdb->prepare($sql, array($code)));
	die();
}

add_action( 'wp_ajax_archive_document', 'archive_document' );
add_action( 'wp_ajax_nopriv_archive_document', 'archive_document' );
function archive_document() {
	$tmpFolder 			= $_POST['tmpFolder'];
	
	//$folderName	= end(explode('/', $tmpFolder));
	parse_str(parse_url($tmpFolder, PHP_URL_QUERY), $output);
	$folderName = $output['sub'];
	
	$srcFolder	= wp_upload_dir()['basedir'] . "/processing/" . $folderName;
	$destFolder = wp_upload_dir()['basedir'] . "/archive/" . $folderName;
	
	@mkdir($destFolder, 0777, TRUE);
	foreach(scandir($srcFolder) as $file){
		if($file == '..' || $file == '.' || $file == '.tmb' || $file == '.quarantine'){
			continue;
		}
		
		$oldfile = "$srcFolder/" . $file;
		$newfile = "$destFolder/" . $file;
		
		if(!copy($oldfile, $newfile)){
			error_log("$oldfile TO $newfile File archive failed");
		}
	}
	
	die();
}

add_action( 'wp_ajax_get_exchange_rate', 'get_exchange_rate' );
add_action( 'wp_ajax_nopriv_get_exchange_rate', 'get_exchange_rate' );
function get_exchange_rate() {
	$dt = date("Ym");
	if(isset($_GET['dt'])){
		$dt = $_GET['dt'];
	}
	
	$yr = substr($dt, 0, 4);
	$mn = substr($dt, 4, 2);
	
	
	$json = file_get_contents("http://data.fixer.io/api/$yr-$mn-01?access_key=1a968d5fc7f83c0e9feb3928499b00f0&symbols=USD,CNY");
	$obj = json_decode($json);
	
	global $wpdb;
	$wpdb->insert( 
		'ms_exchange_rate', 
		array( 
			'dateString' 	=> $dt,
			'exchangeRate' 	=> $obj->rates->CNY / $obj->rates->USD
		), 
		array( 
			'%s', 
			'%f' 
		) 
	);
	
	die();
}

add_action( 'wp_ajax_get_sub_order_details', 'get_sub_order_details' );
function get_sub_order_details() {
	$orderId = $_POST['orderId'];
	
	$tb = array();
	$thl = array();
	$thk = array();
	
	global $wpdb;
	$prodFields = $wpdb->get_results('SHOW FULL COLUMNS FROM ms_order', ARRAY_N);
	for($i = 0; $i < count($prodFields); $i++){
		array_push($thl, $prodFields[$i][8]);
		array_push($thk, $prodFields[$i][0]);
	}
	
	array_push($tb, $thk);
	array_push($tb, $thl);
	
$sql = <<<SQL
	SELECT 
		minor.*
	FROM ms_order AS major
	LEFT JOIN ms_order AS minor
	ON 
			major.PI = minor.PI 
	AND 	major.Number = minor.Number
	AND	major.clientName = minor.clientName
	AND	major.CD = minor.CD
	WHERE major.id = %d AND minor.id <> %d
SQL;
	$sql = $wpdb->prepare($sql, array($orderId,$orderId));

	$prodInfos = $wpdb->get_results($sql, ARRAY_A);
	
	foreach ( $prodInfos as $prod ) {
		$rc = array();
		for($i = 0; $i < count($thk); $i++){
			array_push($rc, $prod[$thk[$i]]);
		}
		
		array_push($tb, $rc);
	}
	
	echo json_encode($tb);
	
	die();
}

add_action( 'wp_ajax_get_sum_order_details', 'get_sum_order_details' );
function get_sum_order_details() {
	$orderId = $_POST['orderId'];
	
	global $wpdb;
$sql = <<<SQL
	SELECT id, clientName, CD, salesQuantity, '' AS remaining, PI, Number, companyName, statusCode
	FROM ms_order
	WHERE id = %d
SQL;
	$sql = $wpdb->prepare($sql, array($orderId));
	$sumInfos = $wpdb->get_results($sql, ARRAY_N);
	echo json_encode($sumInfos);
	die();
}
?>