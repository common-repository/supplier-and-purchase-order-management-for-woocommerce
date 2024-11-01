<?php

/**
 * Plugin Name:       Supplier and Purchase Order Management for WooCommerce
 * Description:       Manage suppliers and create purchase orders from your WooCommerce products
 * Version:           1.1.4
 * Author:            IT-iCO SRL
 * Author URI:        https://it-ico.com
 * Text Domain:       supplier-and-purchase-order-management-for-woocommerce
 * Domain Path:       /languages
 * Requires Plugins:  woocommerce
 */

 // Act on plugin activation
register_activation_hook( __FILE__, "init_db_spom" );
register_uninstall_hook( __FILE__, 'spom_uninstall' );


if ( !defined( 'ABSPATH' ) ) exit;

define("SUPPLIER_PLUGIN_DIR_PATH",plugin_dir_path(__FILE__));


add_action( 'init', 'spom_wpdocs_load_textdomain' );
 
function spom_wpdocs_load_textdomain() {
    load_plugin_textdomain( 'supplier-and-purchase-order-management-for-woocommerce', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' ); 
}


// Activate Plugin
// Initialize DB Tables
function init_db_spom() {

    if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
        include_once( ABSPATH . '/wp-admin/includes/plugin.php' );
      }
      if ( current_user_can( 'activate_plugins' ) && ! class_exists( 'WooCommerce' ) ) {
        // Deactivate the plugin.
        deactivate_plugins( plugin_basename( __FILE__ ) );
        // Throw an error in the WordPress admin console.
        $error_message = '<p style="font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,Oxygen-Sans,Ubuntu,Cantarell,\'Helvetica Neue\',sans-serif;font-size: 13px;line-height: 1.5;color:#444;">' . esc_html__( 'This plugin requires ', 'supplier-and-purchase-order-management-for-woocommerce' ) . '<a target="_blank" href="' . esc_url( 'https://wordpress.org/plugins/woocommerce/' ) . '">WooCommerce</a>' . esc_html__( ' plugin to be active. Please install and activate WooCommerce.', 'supplier-and-purchase-order-management-for-woocommerce' ) . '</p>';
        die( $error_message ); // WPCS: XSS ok.
      }


	// WP Globals
	global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
	// Customer Table
	$supplierTable = $wpdb->prefix . 'suppliers';

	// Create supplier Table if not exist

        $sql = "CREATE TABLE $supplierTable (
            id int(11) NOT NULL AUTO_INCREMENT,
            suppliername varchar(500) NOT NULL,
		    email varchar(500) NOT NULL,
		    nombrecontacto varchar(500) NOT NULL,
		    nota varchar(2000),
		    telefono varchar(500) NOT NULL,
		    provincia varchar(150),
		    direccion varchar(500),
            productids varchar(20000),
            lastupdate_at datetime,
            created_at datetime NOT NULL,
            PRIMARY KEY supplier_id (id)
        ) $charset_collate;";

        add_option( "suppliers_db_version", "1.0" );

		// Include Upgrade Script
		require_once( ABSPATH . '/wp-admin/includes/upgrade.php' );
	
		// Create Table
		dbDelta( $sql );




    $posupplierTable = $wpdb->prefix . 'posuppliers';

	// Create PO Table if not exist

        $sqlpo = "CREATE TABLE $posupplierTable (
            id int(11) NOT NULL AUTO_INCREMENT,
            supplieridfk int(11) NOT NULL,
            postatus varchar(500) NOT NULL,
		    nota varchar(2000),
            confirmaciononreceivedsent tinyint(1) default 0,
            lastupdate_at datetime,
            created_at datetime NOT NULL,
            FOREIGN KEY supplier_id (supplieridfk) REFERENCES $supplierTable(id),
            PRIMARY KEY po_id (id)
            
        ) $charset_collate;";

        add_option( "suppliers_db_version", "1.0" );

		// Include Upgrade Script
		require_once( ABSPATH . '/wp-admin/includes/upgrade.php' );
	
		// Create Table
		dbDelta( $sqlpo );





    $poProductsupplierTable = $wpdb->prefix . 'poproductsuppliers';

    // Create PO Table if not exist

    $sqlprod = "CREATE TABLE $poProductsupplierTable (
        id int(11) NOT NULL AUTO_INCREMENT,
        poidfk int(11) NOT NULL,
        productid int(11) NOT NULL,
        productname varchar(500) NOT NULL,
        quantityordered int(11) NOT NULL,
        quantityreceived int(11),
        price decimal(10,2),
        tax decimal(10,2),
        productimg BLOB,
        created_at datetime NOT NULL,
        FOREIGN KEY po_id (poidfk) REFERENCES $posupplierTable(id),
        PRIMARY KEY product_id (id)
    ) $charset_collate;";

    add_option( "suppliers_db_version", "1.0" );

    // Include Upgrade Script
    require_once( ABSPATH . '/wp-admin/includes/upgrade.php' );

    // Create Table
    dbDelta( $sqlprod );






}
function spom_uninstall() {
    // Uninstallation stuff here
    // WP Globals
	global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
	// Customer Table
	$supplierTable = $wpdb->prefix . 'suppliers';
    $posupplierTable = $wpdb->prefix . 'posuppliers';
    $poProductsupplierTable = $wpdb->prefix . 'poproductsuppliers';

    $wpdb->query( "DROP TABLE IF EXISTS $poProductsupplierTable" );
    delete_option("suppliers_db_version");

    $wpdb->query( "DROP TABLE IF EXISTS $posupplierTable" );
    delete_option("suppliers_db_version");

    $wpdb->query( "DROP TABLE IF EXISTS $supplierTable" );
    delete_option("suppliers_db_version");

}

function spom_supplier_menus_development(){
    add_menu_page("Suppliers",__('Suppliers', 'supplier-and-purchase-order-management-for-woocommerce'),"manage_woocommerce","supplier-plugin","spom_supplier_list_call",'data:image/svg+xml;base64,'.base64_encode('<svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
    width="256px" height="152px" viewBox="0 0 256 152" enable-background="new 0 0 256 152" xml:space="preserve">
<path fill="white" d="M103.24,78.417H26.824V2h29.461v24.401h17.495V2h29.461V78.417z M87.651,126.576c0,12.775-10.338,23.113-23.113,23.113
   c-12.775,0-23.113-10.338-23.113-23.113s10.338-23.113,23.113-23.113C77.314,103.463,87.651,113.801,87.651,126.576z
    M73.391,126.576c0-4.853-4-8.853-8.853-8.853c-4.853,0-8.853,4-8.853,8.853c0,4.853,4,8.853,8.853,8.853
   C69.391,135.429,73.391,131.429,73.391,126.576z M233.156,126.592c0,12.775-10.338,23.113-23.113,23.113
   c-12.775,0-23.113-10.338-23.113-23.113s10.338-23.113,23.113-23.113C222.818,103.479,233.156,113.817,233.156,126.592z
    M218.896,126.592c0-4.853-4-8.853-8.853-8.853c-4.853,0-8.853,4-8.853,8.853c0,4.853,4,8.853,8.853,8.853
   C214.896,135.445,218.896,131.445,218.896,126.592z M2,85.799h129v36H99.924h-3.195c-0.924-0.242-1.681-0.83-1.849-1.755
   c-2.521-14.456-15.129-24.482-30.341-24.482s-27.82,10.026-30.341,24.482c-0.168,0.84-0.924,1.512-1.848,1.755H10
   c-4.481-0.242-8-3.874-8-8.356V85.799z M254,106.765v6.219c0,4.707-4.209,8.815-9,8.815h-3c-0.924,0-1.448-0.83-1.616-1.755
   c-2.521-14.456-15.129-25.466-30.341-25.466s-27.82,11.01-30.341,25.466c-0.168,0.84-0.777,1.755-1.702,1.755h-40v-92h27
   c2.437,0.084,4.482,1.514,6.163,3.195L208.565,69.4c0.336,0.336,0.931,0.4,1.435,0.4h24c7.48,0,13,6.604,13,14v21h5
   C253.009,104.799,254,105.841,254,106.765z M192.007,66.794l-27.063-25.9c-0.42-0.336-1.439-1.095-1.944-1.095h-13
   c-1.009,0-2,0.991-2,2v26c0,1.009,0.991,2,2,2h41C192.597,69.799,193.016,68.055,192.007,66.794z"/></svg>'), '10');
    //add_submenu_page("supplier-plugin","List Suppliers","Lista de Proveedores","manage_options","supplier-plugin-listsuppliers","spom_supplier_list_call");
    add_submenu_page("supplier-plugin","Add Supplier",__('Add Suppliers', 'supplier-and-purchase-order-management-for-woocommerce'),"manage_woocommerce","supplier-plugin-add","spom_supplier_add_call");
    add_submenu_page("supplier-plugin","PO List",__('PO List', 'supplier-and-purchase-order-management-for-woocommerce'),"manage_woocommerce","supplier-plugin-po-list","spom_supplier_po_list_call");
    add_submenu_page("supplier-plugin","Inventory",__('Inventory', 'supplier-and-purchase-order-management-for-woocommerce'),"manage_woocommerce","supplier-plugin-inventory","spom_supplier_inventory_call");


    add_submenu_page(NULL,"Supplier Profile","Perfil Proveedor","manage_woocommerce","supplier-plugin-perfil","spom_supplier_profile_call");
    add_submenu_page(NULL,"Create PO","Crear OC","manage_woocommerce","supplier-plugin-createpo","spom_create_po_call");
    add_submenu_page(NULL,"PO View","View OC","manage_woocommerce","supplier-plugin-poview","spom_view_po_call");
    add_submenu_page(NULL,"Edit PO View","Edit OC","manage_woocommerce","supplier-plugin-editpo","spom_edit_po_call");
    add_submenu_page(NULL,"Receive PO","Recibir OC","manage_woocommerce","supplier-plugin-receivepo","spom_receive_po_call");
    add_submenu_page(NULL,"Send Confirmation","Enviar Confirmacion OC","manage_woocommerce","supplier-plugin-sendconf","spom_send_conf_call");



}

add_action("admin_menu","spom_supplier_menus_development", 25);

function spom_supplier_list_call(){

    include_once SUPPLIER_PLUGIN_DIR_PATH.'/views/listsuppliers.php';
}

function spom_supplier_add_call(){

    include_once SUPPLIER_PLUGIN_DIR_PATH.'/views/addsupplier.php';
}

function spom_supplier_inventory_call(){

    include_once SUPPLIER_PLUGIN_DIR_PATH.'/views/stockquantities.php';
}

function spom_supplier_profile_call(){

    include_once SUPPLIER_PLUGIN_DIR_PATH.'/views/supplierprofile.php';
}

function spom_create_po_call(){

    include_once SUPPLIER_PLUGIN_DIR_PATH.'/views/createpurchaseorder.php';
}

function spom_view_po_call(){

    include_once SUPPLIER_PLUGIN_DIR_PATH.'/views/poview.php';
}

function spom_supplier_po_list_call(){
    include_once SUPPLIER_PLUGIN_DIR_PATH.'/views/listpos.php';
}

function spom_edit_po_call(){
    include_once SUPPLIER_PLUGIN_DIR_PATH.'/views/editpurchaseorder.php';
}

function spom_receive_po_call(){
    include_once SUPPLIER_PLUGIN_DIR_PATH.'/views/recibirentregapo.php';
}

function spom_send_conf_call(){
    include_once SUPPLIER_PLUGIN_DIR_PATH.'/views/sendconfirmation.php';
}




