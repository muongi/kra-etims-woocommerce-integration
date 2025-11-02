<?php
/**
 * Admin class
 *
 * @package KRA_eTims_WC
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin class
 */
class KRA_eTims_WC_Admin {
    /**
     * Constructor
     */
    public function __construct() {
        // Nothing to do here
    }

    /**
     * Initialize admin
     */
    public function init() {
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Register settings
        add_action('admin_init', array($this, 'register_settings'));
        
        // Add admin scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Add AJAX handlers
        add_action('wp_ajax_kra_etims_submit_order', array($this, 'ajax_submit_order'));
        add_action('wp_ajax_kra_etims_refund_order', array($this, 'ajax_refund_order'));
        add_action('wp_ajax_kra_etims_get_customer_tin', array($this, 'ajax_get_customer_tin'));
        
        // Add admin notices
        add_action('admin_notices', array($this, 'admin_notices'));
        
        // Add AJAX handler to clear tax rates
        add_action('wp_ajax_kra_etims_clear_tax_rates', array($this, 'ajax_clear_tax_rates'));
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Add top-level menu item
        add_menu_page(
            __('KRA eTims', 'kra-etims-connector'),
            __('KRA eTims', 'kra-etims-connector'),
            'manage_woocommerce',
            'kra-etims-wc',
            array($this, 'render_settings_page'),
            'dashicons-money-alt',
            58 // Position after WooCommerce
        );
        
        // Add submenu items
        add_submenu_page(
            'woocommerce',
            __('KRA eTims', 'kra-etims-connector'),
            __('KRA eTims', 'kra-etims-connector'),
            'manage_woocommerce',
            'kra-etims-wc',
            array($this, 'render_settings_page')
        );
        
        // Add Reports submenu under KRA eTims main menu
        add_submenu_page(
            'kra-etims-wc',
            __('eTIMS Reports', 'kra-etims-connector'),
            __('eTIMS Reports', 'kra-etims-connector'),
            'manage_woocommerce',
            'kra-etims-wc-reports',
            array($this, 'render_reports_page')
        );

    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('kra_etims_wc_settings', 'kra_etims_wc_settings', array(
            'sanitize_callback' => array($this, 'sanitize_settings')
        ));
        
        // Add settings sections
        add_settings_section(
            'kra_etims_wc_general',
            __('KRA eTims Settings', 'kra-etims-connector'),
            array($this, 'render_general_section'),
            'kra_etims_wc_settings'
        );
        
        add_settings_section(
            'kra_etims_wc_api',
            __('Custom API Settings', 'kra-etims-connector'),
            array($this, 'render_api_section'),
            'kra_etims_wc_settings'
        );
        

        
        // Add settings fields
        add_settings_field(
            'kra_etims_wc_tin',
            __('Taxpayer Identification Number (TIN)', 'kra-etims-connector'),
            array($this, 'render_tin_field'),
            'kra_etims_wc_settings',
            'kra_etims_wc_api'
        );
        
        add_settings_field(
            'kra_etims_wc_company_name',
            __('Company Name', 'kra-etims-connector'),
            array($this, 'render_company_name_field'),
            'kra_etims_wc_settings',
            'kra_etims_wc_api'
        );
        
        add_settings_field(
            'kra_etims_wc_bhfId',
            __('Branch ID', 'kra-etims-connector'),
            array($this, 'render_bhfId_field'),
            'kra_etims_wc_settings',
            'kra_etims_wc_api'
        );
        
        add_settings_field(
            'kra_etims_wc_device_serial',
            __('Device Serial', 'kra-etims-connector'),
            array($this, 'render_device_serial_field'),
            'kra_etims_wc_settings',
            'kra_etims_wc_api'
        );
        
        add_settings_field(
            'kra_etims_wc_auto_submit',
            __('Auto Submit', 'kra-etims-connector'),
            array($this, 'render_auto_submit_field'),
            'kra_etims_wc_settings',
            'kra_etims_wc_general'
        );
        
        add_settings_field(
            'kra_etims_wc_include_shipping',
            __('Include Shipping in KRA API', 'kra-etims-connector'),
            array($this, 'render_include_shipping_field'),
            'kra_etims_wc_settings',
            'kra_etims_wc_general'
        );

        
        // Custom API settings fields

        
        add_settings_field(
            'kra_etims_wc_custom_api_live_url',
            __('Production API URL', 'kra-etims-connector'),
            array($this, 'render_custom_api_live_url_field'),
            'kra_etims_wc_settings',
            'kra_etims_wc_api'
        );
        
        add_settings_field(
            'kra_etims_wc_api_base_url',
            __('API Base URL', 'kra-etims-connector'),
            array($this, 'render_api_base_url_field'),
            'kra_etims_wc_settings',
            'kra_etims_wc_api'
        );
        
        add_settings_field(
            'kra_etims_wc_current_endpoint',
            __('Current API Endpoint', 'kra-etims-connector'),
            array($this, 'render_current_endpoint_field'),
            'kra_etims_wc_settings',
            'kra_etims_wc_api'
        );
        
        add_settings_field(
            'kra_etims_wc_force_https',
            __('Force HTTPS', 'kra-etims-connector'),
            array($this, 'render_force_https_field'),
            'kra_etims_wc_settings',
            'kra_etims_wc_api'
        );
        
        add_settings_field(
            'kra_etims_wc_custom_api_port',
            __('Custom API Port', 'kra-etims-connector'),
            array($this, 'render_custom_api_port_field'),
            'kra_etims_wc_settings',
            'kra_etims_wc_api'
        );
        
        // Product API settings
        add_settings_field(
            'kra_etims_wc_auto_submit_products',
            __('Auto Submit Products', 'kra-etims-connector'),
            array($this, 'render_auto_submit_products_field'),
            'kra_etims_wc_settings',
            'kra_etims_wc_api'
        );
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('kra_etims_wc_settings');
                do_settings_sections('kra_etims_wc_settings');
                submit_button();
                ?>
            </form>
            
            <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ccd0d4; border-left: 4px solid #d63638; border-radius: 4px;">
                <h2 style="margin-top: 0;"><?php _e('Tax Rate Fix (Tax-Inclusive Pricing)', 'kra-etims-connector'); ?></h2>
                <p><?php _e('If your products are showing incorrect prices (e.g., 533 showing as 618), click the button below to clear all WooCommerce tax rates. This prevents double taxation on tax-inclusive prices.', 'kra-etims-connector'); ?></p>
                <p><strong><?php _e('Note:', 'kra-etims-connector'); ?></strong> <?php _e('After clicking this button, you may need to re-save your products for the changes to take effect. Tax will still be calculated correctly for API reporting.', 'kra-etims-connector'); ?></p>
                <button type="button" id="kra-etims-clear-tax-rates" class="button button-secondary" style="background-color: #d63638; border-color: #d63638; color: #fff;">
                    <?php _e('Clear All Tax Rates (Fix Double Taxation)', 'kra-etims-connector'); ?>
                </button>
                <span id="kra-etims-tax-clear-message" style="margin-left: 10px;"></span>
            </div>
            
            <script>
            jQuery(document).ready(function($) {
                $('#kra-etims-clear-tax-rates').on('click', function() {
                    if (!confirm('<?php _e('Are you sure you want to clear all WooCommerce tax rates? This will fix double taxation issues but you may need to re-save your products.', 'kra-etims-connector'); ?>')) {
                        return;
                    }
                    
                    var $button = $(this);
                    var $message = $('#kra-etims-tax-clear-message');
                    
                    $button.prop('disabled', true).text('<?php _e('Clearing...', 'kra-etims-connector'); ?>');
                    $message.html('');
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'kra_etims_clear_tax_rates',
                            nonce: '<?php echo wp_create_nonce('kra_etims_clear_tax_rates'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                $message.html('<span style="color: #00a32a;">✓ ' + response.data.message + '</span>');
                                alert('<?php _e('Tax rates cleared successfully! Please re-save your products to apply changes.', 'kra-etims-connector'); ?>');
                            } else {
                                $message.html('<span style="color: #d63638;">✗ ' + response.data + '</span>');
                            }
                            $button.prop('disabled', false).text('<?php _e('Clear All Tax Rates (Fix Double Taxation)', 'kra-etims-connector'); ?>');
                        },
                        error: function() {
                            $message.html('<span style="color: #d63638;">✗ <?php _e('An error occurred.', 'kra-etims-connector'); ?></span>');
                            $button.prop('disabled', false).text('<?php _e('Clear All Tax Rates (Fix Double Taxation)', 'kra-etims-connector'); ?>');
                        }
                    });
                });
            });
            </script>
        </div>
        <?php
    }

    /**
     * Render reports page
     */
    public function render_reports_page() {
        // Get filter parameters
        $date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
        $date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';
        $status_filter = isset($_GET['status_filter']) ? sanitize_text_field($_GET['status_filter']) : 'all';
        
        // Query orders that have been submitted to custom API
        $args = array(
            'limit' => -1,
            'type' => 'shop_order',
            'orderby' => 'date',
            'order' => 'DESC',
        );
        
        // Add date filter if provided
        if ($date_from && $date_to) {
            $args['date_created'] = $date_from . '...' . $date_to;
        }
        
        $orders = wc_get_orders($args);
        
        // Filter orders that have been submitted to API
        $submitted_orders = array();
        foreach ($orders as $order) {
            $custom_api_status = get_post_meta($order->get_id(), '_custom_api_status', true);
            
            // Apply status filter
            if ($status_filter === 'all' || 
                ($status_filter === 'success' && $custom_api_status === 'success') ||
                ($status_filter === 'not_submitted' && empty($custom_api_status))) {
                
                if ($custom_api_status === 'success' || $status_filter === 'not_submitted') {
                    $submitted_orders[] = $order;
                }
            }
        }
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ccd0d4; border-radius: 4px;">
                <h2><?php _e('Filter Orders', 'kra-etims-connector'); ?></h2>
                <form method="get" action="">
                    <input type="hidden" name="page" value="kra-etims-wc-reports" />
                    
                    <table class="form-table">
                        <tr>
                            <th><label for="date_from"><?php _e('Date From:', 'kra-etims-connector'); ?></label></th>
                            <td>
                                <input type="date" id="date_from" name="date_from" value="<?php echo esc_attr($date_from); ?>" />
                            </td>
                            <th><label for="date_to"><?php _e('Date To:', 'kra-etims-connector'); ?></label></th>
                            <td>
                                <input type="date" id="date_to" name="date_to" value="<?php echo esc_attr($date_to); ?>" />
                            </td>
                        </tr>
                        <tr>
                            <th><label for="status_filter"><?php _e('Status:', 'kra-etims-connector'); ?></label></th>
                            <td colspan="3">
                                <select id="status_filter" name="status_filter">
                                    <option value="all" <?php selected($status_filter, 'all'); ?>><?php _e('All Orders', 'kra-etims-connector'); ?></option>
                                    <option value="success" <?php selected($status_filter, 'success'); ?>><?php _e('Submitted to API', 'kra-etims-connector'); ?></option>
                                    <option value="not_submitted" <?php selected($status_filter, 'not_submitted'); ?>><?php _e('Not Submitted', 'kra-etims-connector'); ?></option>
                                </select>
                            </td>
                        </tr>
                    </table>
                    
                    <p>
                        <button type="submit" class="button button-primary"><?php _e('Apply Filter', 'kra-etims-connector'); ?></button>
                        <a href="?page=kra-etims-wc-reports" class="button"><?php _e('Clear Filter', 'kra-etims-connector'); ?></a>
                    </p>
                </form>
            </div>
            
            <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ccd0d4; border-radius: 4px;">
                <h2><?php _e('Submitted Orders', 'kra-etims-connector'); ?> (<?php echo count($submitted_orders); ?>)</h2>
                
                <?php if (empty($submitted_orders)) : ?>
                    <p><?php _e('No orders found matching your criteria.', 'kra-etims-connector'); ?></p>
                <?php else : ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th style="width: 100px;"><?php _e('Order #', 'kra-etims-connector'); ?></th>
                                <th><?php _e('Date', 'kra-etims-connector'); ?></th>
                                <th><?php _e('Customer', 'kra-etims-connector'); ?></th>
                                <th><?php _e('Total', 'kra-etims-connector'); ?></th>
                                <th><?php _e('Etims Invoice No', 'kra-etims-connector'); ?></th>
                                <th><?php _e('Receipt Signature', 'kra-etims-connector'); ?></th>
                                <th><?php _e('QR Code', 'kra-etims-connector'); ?></th>
                                <th><?php _e('Actions', 'kra-etims-connector'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($submitted_orders as $order) : 
                                $order_id = $order->get_id();
                                $receipt_number = get_post_meta($order_id, '_receipt_number', true);
                                $receipt_signature = get_post_meta($order_id, '_receipt_signature', true);
                                $qr_url = KRA_eTims_WC_QR_Code::get_order_qr_url($order_id);
                                $custom_api_status = get_post_meta($order_id, '_custom_api_status', true);
                                
                                // Format signature
                                $formatted_signature = $this->format_receipt_signature($receipt_signature);
                            ?>
                            <tr>
                                <td>
                                    <strong><a href="<?php echo admin_url('post.php?post=' . $order_id . '&action=edit'); ?>">#<?php echo $order_id; ?></a></strong>
                                </td>
                                <td><?php echo esc_html($order->get_date_created()->date_i18n(get_option('date_format'))); ?></td>
                                <td>
                                    <?php 
                                    $customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
                                    echo esc_html($customer_name ? $customer_name : 'Guest');
                                    ?>
                                </td>
                                <td><?php echo $order->get_formatted_order_total(); ?></td>
                                <td>
                                    <?php if ($receipt_number) : ?>
                                        <code><?php echo esc_html($receipt_number); ?></code>
                                    <?php else : ?>
                                        <span style="color: #999;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($receipt_signature) : ?>
                                        <code style="font-size: 11px;"><?php echo esc_html($formatted_signature); ?></code>
                                    <?php else : ?>
                                        <span style="color: #999;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($qr_url) : ?>
                                        <a href="<?php echo esc_url($qr_url); ?>" target="_blank" class="button button-small">
                                            <?php _e('View QR', 'kra-etims-connector'); ?>
                                        </a>
                                    <?php else : ?>
                                        <span style="color: #999;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?php echo admin_url('post.php?post=' . $order_id . '&action=edit'); ?>" class="button button-small">
                                        <?php _e('View Order', 'kra-etims-connector'); ?>
                                    </a>
                                    <?php if ($custom_api_status === 'success') : ?>
                                        <span style="color: #00a32a; margin-left: 5px;">✓</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
            <style>
                .wp-list-table th {
                    font-weight: bold;
                    background: #f0f0f1;
                }
                .wp-list-table td {
                    vertical-align: middle;
                }
                .wp-list-table code {
                    background: #f0f0f1;
                    padding: 3px 6px;
                    border-radius: 3px;
                    font-size: 12px;
                }
            </style>
        </div>
        <?php
    }

    /**
     * Render general section
     */
    public function render_general_section() {
        echo '<p>' . __('Configure general settings for the KRA eTims WooCommerce integration.', 'kra-etims-connector') . '</p>';
    }

    /**
     * Render API section
     */
    public function render_api_section() {
        ?>
        <p><?php _e('Configure your custom API endpoint for sending receipt data.', 'kra-etims-connector'); ?></p>
        <?php
    }

    /**
     * Render TIN field
     */
    public function render_tin_field() {
        $settings = get_option('kra_etims_wc_settings');
        $value = isset($settings['tin']) ? $settings['tin'] : '';
        ?>
        <input type="text" name="kra_etims_wc_settings[tin]" value="<?php echo esc_attr($value); ?>" class="regular-text" />
        <p class="description"><?php _e('Your Taxpayer Identification Number (TIN) to be sent with receipt data.', 'kra-etims-connector'); ?></p>
        <?php
    }

    /**
     * Render company name field
     */
    public function render_company_name_field() {
        $settings = get_option('kra_etims_wc_settings');
        $value = isset($settings['company_name']) ? $settings['company_name'] : '';
        ?>
        <input type="text" name="kra_etims_wc_settings[company_name]" value="<?php echo esc_attr($value); ?>" class="regular-text" maxlength="20" />
        <p class="description"><?php _e('Your company name to be sent with receipt data. Maximum 20 characters.', 'kra-etims-connector'); ?></p>
        <?php
    }

    /**
     * Render branch ID field
     */
    public function render_bhfId_field() {
        $settings = get_option('kra_etims_wc_settings');
        $value = isset($settings['bhfId']) ? $settings['bhfId'] : '00';
        ?>
        <input type="text" name="kra_etims_wc_settings[bhfId]" value="<?php echo esc_attr($value); ?>" class="regular-text" placeholder="00" />
        <p class="description"><?php _e('Your branch ID for the KRA eTims system.', 'kra-etims-connector'); ?></p>
        <?php
    }

    /**
     * Render device serial field
     */
    public function render_device_serial_field() {
        $settings = get_option('kra_etims_wc_settings');
        $value = isset($settings['device_serial']) ? $settings['device_serial'] : '30349fe8442deb86';
        ?>
        <input type="text" name="kra_etims_wc_settings[device_serial]" value="<?php echo esc_attr($value); ?>" class="regular-text" placeholder="30349fe8442deb86" />
        <p class="description"><?php _e('Device serial number for the KRA eTims system. Used for Tax B (16% VAT) transactions.', 'kra-etims-connector'); ?></p>
        <?php
    }

    /**
     * Render auto submit field
     */
    public function render_auto_submit_field() {
        $settings = get_option('kra_etims_wc_settings');
        $value = isset($settings['auto_submit']) ? $settings['auto_submit'] : 'yes';
        ?>
        <select name="kra_etims_wc_settings[auto_submit]">
            <option value="yes" <?php selected($value, 'yes'); ?>><?php _e('Yes', 'kra-etims-connector'); ?></option>
            <option value="no" <?php selected($value, 'no'); ?>><?php _e('No', 'kra-etims-connector'); ?></option>
        </select>
        <p class="description"><?php _e('Automatically submit receipt data to your custom API when an order is completed.', 'kra-etims-connector'); ?></p>
        <?php
    }

    /**
     * Render include shipping field
     */
    public function render_include_shipping_field() {
        $settings = get_option('kra_etims_wc_settings');
        $value = isset($settings['include_shipping']) ? $settings['include_shipping'] : 'yes';
        ?>
        <select name="kra_etims_wc_settings[include_shipping]">
            <option value="yes" <?php selected($value, 'yes'); ?>><?php _e('Yes', 'kra-etims-connector'); ?></option>
            <option value="no" <?php selected($value, 'no'); ?>><?php _e('No', 'kra-etims-connector'); ?></option>
        </select>
        <p class="description"><?php _e('Include shipping costs in the data sent to KRA eTIMS API. Set to "No" if you don\'t want shipping to be sent to KRA.', 'kra-etims-connector'); ?></p>
        <?php
    }



    /**
     * Render custom API enabled field
     */
    public function render_custom_api_enabled_field() {
        $settings = get_option('kra_etims_wc_settings');
        $value = isset($settings['custom_api_enabled']) ? $settings['custom_api_enabled'] : 'no';
        ?>
        <select name="kra_etims_wc_settings[custom_api_enabled]">
            <option value="yes" <?php selected($value, 'yes'); ?>><?php _e('Yes', 'kra-etims-connector'); ?></option>
            <option value="no" <?php selected($value, 'no'); ?>><?php _e('No', 'kra-etims-connector'); ?></option>
        </select>
        <p class="description"><?php _e('Enable sending receipt data to your custom API endpoint.', 'kra-etims-connector'); ?></p>
        <?php
    }



    /**
     * Render custom API live URL field
     */
    public function render_custom_api_live_url_field() {
        $settings = get_option('kra_etims_wc_settings');
        $value = isset($settings['custom_api_live_url']) ? $settings['custom_api_live_url'] : 'https://your-production-api.com/injongeReceipts';
        ?>
        <input type="url" name="kra_etims_wc_settings[custom_api_live_url]" value="<?php echo esc_attr($value); ?>" class="regular-text" placeholder="https://your-production-api.com/injongeReceipts" />
        <p class="description"><?php _e('API endpoint URL for production environment. This will be used when "Production" environment is selected.', 'kra-etims-connector'); ?></p>
        <?php
    }

    /**
     * Render API base URL field
     */
    public function render_api_base_url_field() {
        $settings = get_option('kra_etims_wc_settings');
        $value = isset($settings['api_base_url']) ? $settings['api_base_url'] : '';
        ?>
        <input type="url" name="kra_etims_wc_settings[api_base_url]" value="<?php echo esc_attr($value); ?>" class="regular-text" placeholder="https://your-api-domain.com" />
        <p class="description"><?php _e('Base URL for your API (e.g., https://your-api-domain.com). The plugin will append /add_categories for categories and /update_items for products.', 'kra-etims-connector'); ?></p>
        <?php
    }

    /**
     * Render current endpoint field
     */
    public function render_current_endpoint_field() {
        $settings = get_option('kra_etims_wc_settings');
        $current_url = isset($settings['custom_api_live_url']) ? $settings['custom_api_live_url'] : 'https://your-production-api.com/injongeReceipts';
        ?>
        <div class="current-endpoint-display">
            <code class="endpoint-url"><?php echo esc_html($current_url); ?></code>
        </div>
        <p class="description"><?php _e('This is the API endpoint that will be used for sending receipt data.', 'kra-etims-connector'); ?></p>
        <style>
            .current-endpoint-display {
                background: #f9f9f9;
                border: 1px solid #ddd;
                padding: 10px;
                border-radius: 4px;
                margin: 5px 0;
            }
            .endpoint-url {
                font-family: monospace;
                background: white;
                padding: 2px 6px;
                border-radius: 3px;
                border: 1px solid #ccc;
            }
        </style>
        <?php
    }

    /**
     * Render force HTTPS field
     */
    public function render_force_https_field() {
        $settings = get_option('kra_etims_wc_settings');
        $value = isset($settings['force_https']) ? $settings['force_https'] : 'no';
        ?>
        <label>
            <input type="checkbox" name="kra_etims_wc_settings[force_https]" value="yes" <?php checked($value, 'yes'); ?> />
            <?php _e('Force HTTPS for API calls (useful for cPanel servers with connection issues)', 'kra-etims-connector'); ?>
        </label>
        <p class="description"><?php _e('Check this if you\'re experiencing connection issues on cPanel servers. This will convert HTTP URLs to HTTPS.', 'kra-etims-connector'); ?></p>
        <?php
    }

    /**
     * Render custom API port field
     */
    public function render_custom_api_port_field() {
        $settings = get_option('kra_etims_wc_settings');
        $value = isset($settings['custom_api_port']) ? $settings['custom_api_port'] : '';
        ?>
        <input type="number" name="kra_etims_wc_settings[custom_api_port]" value="<?php echo esc_attr($value); ?>" class="small-text" placeholder="443" min="1" max="65535" />
        <p class="description"><?php _e('Custom port for API calls (leave empty to use default). Useful if your hosting provider blocks certain ports.', 'kra-etims-connector'); ?></p>
        <?php
    }

    /**
     * Render auto submit products field
     */
    public function render_auto_submit_products_field() {
        $settings = get_option('kra_etims_wc_settings');
        $value = isset($settings['auto_submit_products']) ? $settings['auto_submit_products'] : 'no';
        ?>
        <select name="kra_etims_wc_settings[auto_submit_products]">
            <option value="yes" <?php selected($value, 'yes'); ?>><?php _e('Yes', 'kra-etims-connector'); ?></option>
            <option value="no" <?php selected($value, 'no'); ?>><?php _e('No', 'kra-etims-connector'); ?></option>
        </select>
        <p class="description"><?php _e('Automatically send new products to your API when they are created.', 'kra-etims-connector'); ?></p>
        <?php
    }

    /**
     * Sanitize settings
     *
     * @param array $input The settings input.
     * @return array Sanitized settings.
     */
    public function sanitize_settings($input) {
        $sanitized = array();
        
        // Sanitize text fields
        if (isset($input['tin'])) {
            $sanitized['tin'] = sanitize_text_field($input['tin']);
        }
        
        if (isset($input['company_name'])) {
            $company_name = sanitize_text_field($input['company_name']);
            // Ensure company name is not longer than 20 characters for trdeNm field
            $sanitized['company_name'] = substr($company_name, 0, 20);
        }
        
        if (isset($input['bhfId'])) {
            $sanitized['bhfId'] = sanitize_text_field($input['bhfId']);
        }
        
        if (isset($input['device_serial'])) {
            $sanitized['device_serial'] = sanitize_text_field($input['device_serial']);
        }
        
        // Sanitize checkbox fields
        $sanitized['auto_submit'] = isset($input['auto_submit']) ? 'yes' : 'no';
        $sanitized['include_shipping'] = isset($input['include_shipping']) ? $input['include_shipping'] : 'yes';
        
        // Sanitize custom API fields
        if (isset($input['custom_api_live_url'])) {
            $sanitized['custom_api_live_url'] = esc_url_raw($input['custom_api_live_url']);
        }
        
        if (isset($input['api_base_url'])) {
            $sanitized['api_base_url'] = esc_url_raw($input['api_base_url']);
        }
        
        // Sanitize new fields
        $sanitized['force_https'] = isset($input['force_https']) ? 'yes' : 'no';
        
        if (isset($input['custom_api_port'])) {
            $port = intval($input['custom_api_port']);
            if ($port >= 1 && $port <= 65535) {
                $sanitized['custom_api_port'] = $port;
            }
        }
        
        // Sanitize product API settings
        $sanitized['auto_submit_products'] = isset($input['auto_submit_products']) ? 'yes' : 'no';
        
        return $sanitized;
    }
    


    /**
     * Enqueue scripts
     *
     * @param string $hook Hook suffix
     */
    public function enqueue_scripts($hook) {
        // Load on KRA eTims settings page
        if ($hook === 'woocommerce_page_kra-etims-wc') {
            wp_enqueue_script(
                'kra-etims-wc-admin',
                KRA_ETIMS_WC_PLUGIN_URL . 'assets/js/admin.js',
                array('jquery'),
                KRA_ETIMS_WC_VERSION,
                true
            );
            
            wp_localize_script(
                'kra-etims-wc-admin',
                'kraEtimsWcAdmin',
                array(
                    'ajaxUrl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('kra_etims_wc_admin')
                )
            );
        }
        
        // Load on order edit pages for customer TIN functionality
        $is_order_page = (
            ($hook === 'post.php' && isset($_GET['post_type']) && $_GET['post_type'] === 'shop_order') ||
            ($hook === 'woocommerce_page_wc-orders') ||
            (strpos($hook, 'wc-orders') !== false)
        );
        
        if ($is_order_page) {
            wp_enqueue_script(
                'kra-etims-wc-order-admin',
                KRA_ETIMS_WC_PLUGIN_URL . 'assets/js/order-admin.js',
                array('jquery'),
                KRA_ETIMS_WC_VERSION . '.' . time(), // Add timestamp to force reload
                true
            );
            
            wp_enqueue_style(
                'kra-etims-wc-admin',
                KRA_ETIMS_WC_PLUGIN_URL . 'assets/css/admin.css',
                array(),
                KRA_ETIMS_WC_VERSION
            );
            
            wp_localize_script(
                'kra-etims-wc-order-admin',
                'kraEtimsWcOrderAdmin',
                array(
                    'ajaxUrl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('kra_etims_wc_admin'),
                    'getCustomerTinAction' => 'kra_etims_get_customer_tin',
                    'customerTinFoundText' => __('Customer TIN found and populated.', 'kra-etims-connector'),
                    'customerTinNotFoundText' => __('No TIN found for this customer.', 'kra-etims-connector')
                )
            );
        }
    }

    /**
     * AJAX submit order
     */
    public function ajax_submit_order() {
        // Check nonce
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'kra_etims_submit_order')) {
            wp_send_json_error(__('Security check failed.', 'kra-etims-connector'));
        }
        
        // Check order ID
        if (!isset($_GET['order_id'])) {
            wp_send_json_error(__('Order ID is required.', 'kra-etims-connector'));
        }
        
        try {
            $order_id = intval($_GET['order_id']);
            
            // Check if order exists
            $order = wc_get_order($order_id);
            if (!$order) {
                wp_send_json_error(__('Order not found.', 'kra-etims-connector'));
            }
            
            $order_handler = new KRA_eTims_WC_Order_Handler();
            $result = $order_handler->process_order($order_id);
            
            if ($result['success']) {
                // Get transaction details for the response
                $receipt_number = get_post_meta($order_id, '_receipt_number', true);
                $receipt_signature = get_post_meta($order_id, '_receipt_signature', true);
                $receipt_date = get_post_meta($order_id, '_receipt_date', true);
                $mrc_number = get_post_meta($order_id, '_mrc_number', true);
                $sdc_id = get_post_meta($order_id, '_sdc_id', true);
                $invoice_number = get_post_meta($order_id, '_custom_api_invoice_no', true);
                
                wp_send_json_success(array(
                    'message' => $result['message'],
                    'redirect_url' => add_query_arg(array(
                        'post' => $order_id,
                        'action' => 'edit',
                        'kra_etims_success' => 1
                    ), admin_url('post.php')),
                    'transaction_details' => array(
                        'receipt_number' => $receipt_number,
                        'receipt_signature' => $receipt_signature,
                        'receipt_date' => $receipt_date,
                        'mrc_number' => $mrc_number,
                        'sdc_id' => $sdc_id,
                        'invoice_number' => $invoice_number
                    )
                ));
            } else {
                wp_send_json_error($result['message']);
            }
            
        } catch (Exception $e) {
            wp_send_json_error('Error processing order: ' . $e->getMessage());
        }
    }



    /**
     * AJAX refund order
     */
    public function ajax_refund_order() {
        // Check nonce
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'kra_etims_refund_order')) {
            wp_send_json_error(__('Security check failed.', 'kra-etims-connector'));
        }
        
        // Check if user is admin
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions. Only administrators can process refunds.', 'kra-etims-connector'));
        }
        
        // Check order ID
        if (!isset($_GET['order_id'])) {
            wp_send_json_error(__('Order ID is required.', 'kra-etims-connector'));
        }
        
        try {
            $order_id = intval($_GET['order_id']);
            
            // Check if order exists
            $order = wc_get_order($order_id);
            if (!$order) {
                wp_send_json_error(__('Order not found.', 'kra-etims-connector'));
            }
            
            $order_handler = new KRA_eTims_WC_Order_Handler();
            $result = $order_handler->process_refund($order_id);
            
            if ($result['success']) {
                // Redirect back to order page with success message
                $redirect_url = add_query_arg(array(
                    'post' => $order_id,
                    'action' => 'edit',
                    'kra_etims_refund_success' => 1
                ), admin_url('post.php'));
                
                wp_redirect($redirect_url);
                exit;
            } else {
                // Redirect back to order page with error message
                $redirect_url = add_query_arg(array(
                    'post' => $order_id,
                    'action' => 'edit',
                    'kra_etims_refund_error' => urlencode($result['message'])
                ), admin_url('post.php'));
                
                wp_redirect($redirect_url);
                exit;
            }
            
        } catch (Exception $e) {
            // Redirect back to order page with error message
            $redirect_url = add_query_arg(array(
                'post' => $order_id,
                'action' => 'edit',
                'kra_etims_refund_error' => urlencode('Error processing refund: ' . $e->getMessage())
            ), admin_url('post.php'));
            
            wp_redirect($redirect_url);
            exit;
        }
    }

    /**
     * AJAX get customer TIN data
     */
    public function ajax_get_customer_tin() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'kra_etims_wc_admin')) {
            wp_send_json_error(__('Security check failed.', 'kra-etims-connector'));
        }
        
        // Check if customer ID is provided
        if (!isset($_POST['customer_id']) || empty($_POST['customer_id'])) {
            wp_send_json_error(__('Customer ID is required.', 'kra-etims-connector'));
        }
        
        $customer_id = intval($_POST['customer_id']);
        
        // Get customer TIN from user meta
        $customer_tin = get_user_meta($customer_id, '_customer_tin', true);
        
        if (!empty($customer_tin)) {
            wp_send_json_success(array(
                'tin' => $customer_tin,
                'message' => __('Customer TIN found.', 'kra-etims-connector')
            ));
        } else {
            wp_send_json_error(__('No TIN found for this customer.', 'kra-etims-connector'));
        }
    }



    /**
     * Admin notices
     */
    public function admin_notices() {
        global $post;
        
        if (!$post || $post->post_type !== 'shop_order') {
            return;
        }
        
        if (isset($_GET['kra_etims_success'])) {
            // Get transaction details from order meta
            $receipt_number = get_post_meta($post->ID, '_receipt_number', true);
            $receipt_signature = get_post_meta($post->ID, '_receipt_signature', true);
            $receipt_date = get_post_meta($post->ID, '_receipt_date', true);
            $mrc_number = get_post_meta($post->ID, '_mrc_number', true);
            $sdc_id = get_post_meta($post->ID, '_sdc_id', true);
            $invoice_number = get_post_meta($post->ID, '_custom_api_invoice_no', true);
            
            ?>
            <div class="notice notice-success is-dismissible">
                <h3 style="margin-top: 0;">✅ KRA eTims Transaction Successful</h3>
                <?php if ($receipt_number || $receipt_signature || $mrc_number): ?>
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 4px; margin: 10px 0;">
                        <h4 style="margin-top: 0; color: #0073aa;">Transaction Details:</h4>
                        <table style="width: 100%; border-collapse: collapse;">
                            <?php if ($receipt_number): ?>
                            <tr>
                                <td style="padding: 5px 10px; font-weight: bold; width: 150px;">Receipt Number:</td>
                                <td style="padding: 5px 10px;"><code><?php echo esc_html($receipt_number); ?></code></td>
                            </tr>
                            <?php endif; ?>
                            
                            <?php if ($receipt_signature): ?>
                            <tr>
                                <td style="padding: 5px 10px; font-weight: bold;">Receipt Signature:</td>
                                <td style="padding: 5px 10px;"><code><?php echo esc_html($this->format_receipt_signature($receipt_signature)); ?></code></td>
                            </tr>
                            <?php endif; ?>
                            
                            <?php if ($receipt_date): ?>
                            <tr>
                                <td style="padding: 5px 10px; font-weight: bold;">Receipt Date:</td>
                                <td style="padding: 5px 10px;"><?php echo esc_html($receipt_date); ?></td>
                            </tr>
                            <?php endif; ?>
                            
                            <?php if ($mrc_number): ?>
                            <tr>
                                <td style="padding: 5px 10px; font-weight: bold;">CU Number:</td>
                                <td style="padding: 5px 10px;"><code><?php echo esc_html($mrc_number); ?></code></td>
                            </tr>
                            <?php endif; ?>
                            
                            <?php if ($sdc_id): ?>
                            <tr>
                                <td style="padding: 5px 10px; font-weight: bold;">SCU ID:</td>
                                <td style="padding: 5px 10px;"><code><?php echo esc_html($sdc_id); ?></code></td>
                            </tr>
                            <?php endif; ?>
                            
                            <?php if ($invoice_number): ?>
                            <tr>
                                <td style="padding: 5px 10px; font-weight: bold;">Invoice Number:</td>
                                <td style="padding: 5px 10px;"><code><?php echo esc_html($invoice_number); ?></code></td>
                            </tr>
                            <?php endif; ?>
                        </table>
                    </div>
                <?php else: ?>
                    <p><?php _e('Order successfully submitted to Custom API.', 'kra-etims-connector'); ?></p>
                <?php endif; ?>
                
                <p style="margin-bottom: 0;">
                    <a href="<?php echo esc_url(add_query_arg('kra_etims_view_receipt', '1', get_edit_post_link($post->ID))); ?>" 
                       class="button button-primary" style="margin-right: 10px;">
                        📄 View Receipt
                    </a>
                    <a href="<?php echo esc_url(add_query_arg('kra_etims_download_receipt', '1', get_edit_post_link($post->ID))); ?>" 
                       class="button button-secondary">
                        📥 Download Receipt
                    </a>
                </p>
            </div>
            <?php
        }
        
        if (isset($_GET['kra_etims_error'])) {
            ?>
            <div class="notice notice-error is-dismissible">
                <p><?php echo esc_html(urldecode($_GET['kra_etims_error'])); ?></p>
            </div>
            <?php
        }
        
        // Refund success notice
        if (isset($_GET['kra_etims_refund_success'])) {
            $refund_invoice_no = get_post_meta($post->ID, '_custom_api_refund_invoice_no', true);
            $refund_submitted_at = get_post_meta($post->ID, '_custom_api_refund_submitted_at', true);
            ?>
            <div class="notice notice-success is-dismissible">
                <h3 style="margin-top: 0;">✅ Refund Successfully Processed</h3>
                <p><?php _e('The refund has been successfully submitted to the custom API.', 'kra-etims-connector'); ?></p>
                <?php if ($refund_invoice_no): ?>
                <p><strong><?php _e('Refund Invoice No:', 'kra-etims-connector'); ?></strong> <code><?php echo esc_html($refund_invoice_no); ?></code></p>
                <?php endif; ?>
                <?php if ($refund_submitted_at): ?>
                <p><strong><?php _e('Processed at:', 'kra-etims-connector'); ?></strong> <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($refund_submitted_at))); ?></p>
                <?php endif; ?>
            </div>
            <?php
        }
        
        // Refund error notice
        if (isset($_GET['kra_etims_refund_error'])) {
            ?>
            <div class="notice notice-error is-dismissible">
                <h3 style="margin-top: 0;">❌ Refund Processing Failed</h3>
                <p><?php echo esc_html(urldecode($_GET['kra_etims_refund_error'])); ?></p>
            </div>
            <?php
        }
    }
    
    /**
     * AJAX handler to clear WooCommerce tax rates
     */
    public function ajax_clear_tax_rates() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'kra_etims_clear_tax_rates')) {
            wp_send_json_error(__('Security check failed.', 'kra-etims-connector'));
        }
        
        // Check permissions
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(__('Insufficient permissions.', 'kra-etims-connector'));
        }
        
        global $wpdb;
        
        // Delete all tax rates
        $rates_deleted = $wpdb->query("DELETE FROM {$wpdb->prefix}woocommerce_tax_rates");
        $locations_deleted = $wpdb->query("DELETE FROM {$wpdb->prefix}woocommerce_tax_rate_locations");
        
        // Clear WooCommerce cache
        delete_transient('wc_tax_rates');
        wp_cache_delete('tax-rates', 'woocommerce');
        
        wp_send_json_success(array(
            'message' => sprintf(__('Successfully cleared %d tax rates and %d locations.', 'kra-etims-connector'), $rates_deleted, $locations_deleted)
        ));
    }
    
    /**
     * Format receipt signature with dashes
     *
     * @param string $receipt_signature
     * @return string
     */
    private function format_receipt_signature($receipt_signature) {
        if (empty($receipt_signature)) {
            return '';
        }
        
        // Remove any existing dashes and format with hyphens every 4 characters
        $clean_signature = preg_replace('/[^A-Za-z0-9]/', '', $receipt_signature);
        
        if (strlen($clean_signature) > 0) {
            // Format with hyphens every 4 characters
            $formatted = '';
            for ($i = 0; $i < strlen($clean_signature); $i++) {
                if ($i > 0 && $i % 4 === 0) {
                    $formatted .= '-';
                }
                $formatted .= $clean_signature[$i];
            }
            return $formatted;
        }
        
        return $receipt_signature;
    }
}
