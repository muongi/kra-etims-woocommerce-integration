<?php
/**
 * Plugin Name: KRA eTims Connector
 * Description: Integrates WooCommerce with Kenya Revenue Authority (KRA) Electronic Tax Invoice Management System (eTims) to Generate eTims compliant receipts
 * Version: 1.0.1
 * Author: Rhenium Group Limited    
 * Text Domain: kra-etims-integration
 * Domain Path: /languages
 * Requires at least: 5.6
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 * License: ISC
 * 
 * Plugin Icon: assets/images/icon.jpeg
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('KRA_ETIMS_WC_VERSION', '1.0.0');
define('KRA_ETIMS_WC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('KRA_ETIMS_WC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('KRA_ETIMS_WC_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Check if WooCommerce is active
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    add_action('admin_notices', function() {
        ?>
        <div class="notice notice-error">
            <p><?php _e('KRA eTims WooCommerce Plugin requires WooCommerce to be installed and activated.', 'kra-etims-integration'); ?></p>
        </div>
        <?php
    });
    return;
}

// Include required files
require_once KRA_ETIMS_WC_PLUGIN_DIR . 'includes/class-kra-etims-wc.php';
require_once KRA_ETIMS_WC_PLUGIN_DIR . 'includes/class-kra-etims-wc-product-handler.php';

// Initialize the plugin
function kra_etims_wc_init() {
    $plugin = new KRA_eTims_WC();
    $plugin->init();
}
add_action('plugins_loaded', 'kra_etims_wc_init');

// Register activation hook
register_activation_hook(__FILE__, 'kra_etims_wc_activate');
function kra_etims_wc_activate() {
    // Create necessary database tables and options
    if (!get_option('kra_etims_wc_settings')) {
        add_option('kra_etims_wc_settings', array(
            'tin' => '',
            'company_name' => 'Company Name',
            'bhfId' => '00',
            'device_serial' => '30349fe8442deb86',
            'environment' => 'development',
            'auto_submit' => 'yes',
            'custom_api_live_url' => 'https://your-production-api.com/injongeReceipts',
            'api_base_url' => ''
        ));
    }
}

// Register deactivation hook
register_deactivation_hook(__FILE__, 'kra_etims_wc_deactivate');
function kra_etims_wc_deactivate() {
    // Clean up if needed
}
