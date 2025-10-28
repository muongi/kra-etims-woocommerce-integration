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
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Add top-level menu item
        add_menu_page(
            __('KRA eTims', 'kra-etims-integration'),
            __('KRA eTims', 'kra-etims-integration'),
            'manage_woocommerce',
            'kra-etims-wc',
            array($this, 'render_settings_page'),
            'dashicons-money-alt',
            58 // Position after WooCommerce
        );
        
        // Add submenu items
        add_submenu_page(
            'woocommerce',
            __('KRA eTims', 'kra-etims-integration'),
            __('KRA eTims', 'kra-etims-integration'),
            'manage_woocommerce',
            'kra-etims-wc',
            array($this, 'render_settings_page')
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
            __('KRA eTims Settings', 'kra-etims-integration'),
            array($this, 'render_general_section'),
            'kra_etims_wc_settings'
        );
        
        add_settings_section(
            'kra_etims_wc_api',
            __('Custom API Settings', 'kra-etims-integration'),
            array($this, 'render_api_section'),
            'kra_etims_wc_settings'
        );
        

        
        // Add settings fields
        add_settings_field(
            'kra_etims_wc_tin',
            __('Taxpayer Identification Number (TIN)', 'kra-etims-integration'),
            array($this, 'render_tin_field'),
            'kra_etims_wc_settings',
            'kra_etims_wc_api'
        );
        
        add_settings_field(
            'kra_etims_wc_company_name',
            __('Company Name', 'kra-etims-integration'),
            array($this, 'render_company_name_field'),
            'kra_etims_wc_settings',
            'kra_etims_wc_api'
        );
        
        add_settings_field(
            'kra_etims_wc_bhfId',
            __('Branch ID', 'kra-etims-integration'),
            array($this, 'render_bhfId_field'),
            'kra_etims_wc_settings',
            'kra_etims_wc_api'
        );
        
        add_settings_field(
            'kra_etims_wc_device_serial',
            __('Device Serial', 'kra-etims-integration'),
            array($this, 'render_device_serial_field'),
            'kra_etims_wc_settings',
            'kra_etims_wc_api'
        );
        
        add_settings_field(
            'kra_etims_wc_environment',
            __('Environment', 'kra-etims-integration'),
            array($this, 'render_environment_field'),
            'kra_etims_wc_settings',
            'kra_etims_wc_api'
        );
        
        add_settings_field(
            'kra_etims_wc_auto_submit',
            __('Auto Submit', 'kra-etims-integration'),
            array($this, 'render_auto_submit_field'),
            'kra_etims_wc_settings',
            'kra_etims_wc_general'
        );
        

        
        // Custom API settings fields

        
        add_settings_field(
            'kra_etims_wc_custom_api_live_url',
            __('Production API URL', 'kra-etims-integration'),
            array($this, 'render_custom_api_live_url_field'),
            'kra_etims_wc_settings',
            'kra_etims_wc_api'
        );
        
        add_settings_field(
            'kra_etims_wc_api_base_url',
            __('API Base URL', 'kra-etims-integration'),
            array($this, 'render_api_base_url_field'),
            'kra_etims_wc_settings',
            'kra_etims_wc_api'
        );
        
        add_settings_field(
            'kra_etims_wc_current_endpoint',
            __('Current API Endpoint', 'kra-etims-integration'),
            array($this, 'render_current_endpoint_field'),
            'kra_etims_wc_settings',
            'kra_etims_wc_api'
        );
        
        add_settings_field(
            'kra_etims_wc_force_https',
            __('Force HTTPS', 'kra-etims-integration'),
            array($this, 'render_force_https_field'),
            'kra_etims_wc_settings',
            'kra_etims_wc_api'
        );
        
        add_settings_field(
            'kra_etims_wc_custom_api_port',
            __('Custom API Port', 'kra-etims-integration'),
            array($this, 'render_custom_api_port_field'),
            'kra_etims_wc_settings',
            'kra_etims_wc_api'
        );
        
        // Product API settings
        add_settings_field(
            'kra_etims_wc_auto_submit_products',
            __('Auto Submit Products', 'kra-etims-integration'),
            array($this, 'render_auto_submit_products_field'),
            'kra_etims_wc_settings',
            'kra_etims_wc_api'
        );
        
        add_settings_field(
            'kra_etims_wc_auto_update_products',
            __('Auto Update Products', 'kra-etims-integration'),
            array($this, 'render_auto_update_products_field'),
            'kra_etims_wc_settings',
            'kra_etims_wc_api'
        );
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        // Get current environment
        $settings = get_option('kra_etims_wc_settings');
        $current_env = isset($settings['environment']) ? $settings['environment'] : 'development';
        $env_class = $current_env === 'production' ? 'production-env' : 'development-env';
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="kra-etims-environment-notice <?php echo esc_attr($env_class); ?>">
                <h2>
                    <?php if ($current_env === 'production'): ?>
                        <span class="dashicons dashicons-warning"></span> 
                        <?php _e('PRODUCTION ENVIRONMENT', 'kra-etims-integration'); ?>
                    <?php else: ?>
                        <span class="dashicons dashicons-admin-tools"></span> 
                        <?php _e('DEVELOPMENT/SANDBOX ENVIRONMENT', 'kra-etims-integration'); ?>
                    <?php endif; ?>
                </h2>
                <p>
                    <?php if ($current_env === 'production'): ?>
                        <?php _e('You are currently using the PRODUCTION environment. All transactions will be sent to the official KRA eTims system.', 'kra-etims-integration'); ?>
                    <?php else: ?>
                        <?php _e('You are currently using the DEVELOPMENT/SANDBOX environment. Use this for testing before moving to production.', 'kra-etims-integration'); ?>
                    <?php endif; ?>
                </p>
            </div>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('kra_etims_wc_settings');
                do_settings_sections('kra_etims_wc_settings');
                submit_button();
                ?>
            </form>
            

            
            <style>
                .kra-etims-environment-notice {
                    background: #fff;
                    border: 1px solid #ccd0d4;
                    border-radius: 4px;
                    padding: 15px;
                    margin: 20px 0;
                }
                
                .kra-etims-environment-notice h2 {
                    margin: 0 0 10px 0;
                    font-size: 16px;
                }
                
                .kra-etims-environment-notice h2 .dashicons {
                    margin-right: 8px;
                }
                
                .production-env {
                    border-left: 4px solid #d63638;
                }
                
                .production-env h2 {
                    color: #d63638;
                }
                
                .development-env {
                    border-left: 4px solid #00a32a;
                }
                
                .development-env h2 {
                    color: #00a32a;
                }
                
                .kra-etims-environment-selector {
                    display: flex;
                    gap: 15px;
                    margin-bottom: 10px;
                }
                .environment-option {
                    flex: 1;
                    border: 2px solid #ccc;
                    border-radius: 5px;
                    padding: 15px;
                    cursor: pointer;
                    display: flex;
                    flex-direction: column;
                    transition: all 0.3s ease;
                }
                .environment-option.selected {
                    border-color: #2271b1;
                    background-color: #f0f6fc;
                }
                .environment-option:hover {
                    border-color: #2271b1;
                }
                .environment-label {
                    display: flex;
                    align-items: center;
                    font-weight: bold;
                    margin-bottom: 5px;
                }
                .environment-description {
                    font-size: 12px;
                    color: #666;
                }
            </style>
        </div>
        <?php
    }

    /**
     * Render general section
     */
    public function render_general_section() {
        echo '<p>' . __('Configure general settings for the KRA eTims WooCommerce integration.', 'kra-etims-integration') . '</p>';
    }

    /**
     * Render API section
     */
    public function render_api_section() {
        ?>
        <p><?php _e('Configure your custom API endpoint for sending receipt data.', 'kra-etims-integration'); ?></p>
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
        <p class="description"><?php _e('Your Taxpayer Identification Number (TIN) to be sent with receipt data.', 'kra-etims-integration'); ?></p>
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
        <p class="description"><?php _e('Your company name to be sent with receipt data. Maximum 20 characters.', 'kra-etims-integration'); ?></p>
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
        <p class="description"><?php _e('Your branch ID for the KRA eTims system.', 'kra-etims-integration'); ?></p>
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
        <p class="description"><?php _e('Device serial number for the KRA eTims system. Used for Tax B (16% VAT) transactions.', 'kra-etims-integration'); ?></p>
        <?php
    }

    /**
     * Render environment field
     */
    public function render_environment_field() {
        $settings = get_option('kra_etims_wc_settings');
        $value = isset($settings['environment']) ? $settings['environment'] : 'development';
        ?>
        <div class="kra-etims-environment-selector">
            <label class="environment-option <?php echo $value === 'development' ? 'selected' : ''; ?>">
                <input type="radio" name="kra_etims_wc_settings[environment]" value="development" <?php checked($value, 'development'); ?> />
                <span class="environment-label">
                    <span class="dashicons dashicons-admin-tools"></span>
                    <?php _e('Development / Sandbox', 'kra-etims-integration'); ?>
                </span>
                <span class="environment-description">
                    <?php _e('Use for development. Connects to your Development API endpoint.', 'kra-etims-integration'); ?>
                </span>
            </label>
            
            <label class="environment-option <?php echo $value === 'production' ? 'selected' : ''; ?>">
                <input type="radio" name="kra_etims_wc_settings[environment]" value="production" <?php checked($value, 'production'); ?> />
                <span class="environment-label">
                    <span class="dashicons dashicons-warning"></span>
                    <?php _e('Production', 'kra-etims-integration'); ?>
                </span>
                <span class="environment-description">
                    <?php _e('Use for live transactions. Connects to your Production API endpoint.', 'kra-etims-integration'); ?>
                </span>
            </label>
        </div>
        <p class="description"><?php _e('Select the environment to use. Always configure in Development first before switching to Production.', 'kra-etims-integration'); ?></p>
        <style>
            .kra-etims-environment-selector {
                display: flex;
                gap: 15px;
                margin-bottom: 10px;
            }
            .environment-option {
                flex: 1;
                border: 2px solid #ccc;
                border-radius: 5px;
                padding: 15px;
                cursor: pointer;
                display: flex;
                flex-direction: column;
                transition: all 0.3s ease;
            }
            .environment-option.selected {
                border-color: #2271b1;
                background-color: #f0f6fc;
            }
            .environment-option:hover {
                border-color: #2271b1;
            }
            .environment-option input[type="radio"] {
                margin-right: 10px;
            }
            .environment-label {
                font-weight: bold;
                display: flex;
                align-items: center;
                margin-bottom: 5px;
            }
            .environment-label .dashicons {
                margin-right: 8px;
            }
            .environment-description {
                color: #646970;
                font-size: 13px;
            }
        </style>
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
            <option value="yes" <?php selected($value, 'yes'); ?>><?php _e('Yes', 'kra-etims-integration'); ?></option>
            <option value="no" <?php selected($value, 'no'); ?>><?php _e('No', 'kra-etims-integration'); ?></option>
        </select>
        <p class="description"><?php _e('Automatically submit receipt data to your custom API when an order is completed.', 'kra-etims-integration'); ?></p>
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
            <option value="yes" <?php selected($value, 'yes'); ?>><?php _e('Yes', 'kra-etims-integration'); ?></option>
            <option value="no" <?php selected($value, 'no'); ?>><?php _e('No', 'kra-etims-integration'); ?></option>
        </select>
        <p class="description"><?php _e('Enable sending receipt data to your custom API endpoint.', 'kra-etims-integration'); ?></p>
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
        <p class="description"><?php _e('API endpoint URL for production environment. This will be used when "Production" environment is selected.', 'kra-etims-integration'); ?></p>
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
        <p class="description"><?php _e('Base URL for your API (e.g., https://your-api-domain.com). The plugin will append /add_categories for categories and /update_items for products.', 'kra-etims-integration'); ?></p>
        <?php
    }

    /**
     * Render current endpoint field
     */
    public function render_current_endpoint_field() {
        $settings = get_option('kra_etims_wc_settings');
        $environment = isset($settings['environment']) ? $settings['environment'] : 'development';
        
        if ($environment === 'production') {
            $current_url = isset($settings['custom_api_live_url']) ? $settings['custom_api_live_url'] : 'https://your-production-api.com/injongeReceipts';
            $status_class = 'production';
            $status_text = __('Production', 'kra-etims-integration');
        } else {
            $current_url = isset($settings['custom_api_live_url']) ? $settings['custom_api_live_url'] : 'https://your-production-api.com/injongeReceipts';
            $status_class = 'development';
            $status_text = __('Development', 'kra-etims-integration');
        }
        ?>
        <div class="current-endpoint-display">
            <span class="endpoint-status <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
            <code class="endpoint-url"><?php echo esc_html($current_url); ?></code>
        </div>
        <p class="description"><?php _e('This is the API endpoint that will be used based on your current environment setting.', 'kra-etims-integration'); ?></p>
        <style>
            .current-endpoint-display {
                background: #f9f9f9;
                border: 1px solid #ddd;
                padding: 10px;
                border-radius: 4px;
                margin: 5px 0;
            }
            .endpoint-status {
                display: inline-block;
                padding: 2px 8px;
                border-radius: 3px;
                font-size: 11px;
                font-weight: bold;
                text-transform: uppercase;
                margin-right: 10px;
            }
            .endpoint-status.development {
                background: #0073aa;
                color: white;
            }
            .endpoint-status.production {
                background: #d63638;
                color: white;
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
            <?php _e('Force HTTPS for API calls (useful for cPanel servers with connection issues)', 'kra-etims-integration'); ?>
        </label>
        <p class="description"><?php _e('Check this if you\'re experiencing connection issues on cPanel servers. This will convert HTTP URLs to HTTPS.', 'kra-etims-integration'); ?></p>
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
        <p class="description"><?php _e('Custom port for API calls (leave empty to use default). Useful if your hosting provider blocks certain ports.', 'kra-etims-integration'); ?></p>
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
            <option value="yes" <?php selected($value, 'yes'); ?>><?php _e('Yes', 'kra-etims-integration'); ?></option>
            <option value="no" <?php selected($value, 'no'); ?>><?php _e('No', 'kra-etims-integration'); ?></option>
        </select>
        <p class="description"><?php _e('Automatically send new products to your API when they are created.', 'kra-etims-integration'); ?></p>
        <?php
    }

    /**
     * Render auto update products field
     */
    public function render_auto_update_products_field() {
        $settings = get_option('kra_etims_wc_settings');
        $value = isset($settings['auto_update_products']) ? $settings['auto_update_products'] : 'no';
        ?>
        <select name="kra_etims_wc_settings[auto_update_products]">
            <option value="yes" <?php selected($value, 'yes'); ?>><?php _e('Yes', 'kra-etims-integration'); ?></option>
            <option value="no" <?php selected($value, 'no'); ?>><?php _e('No', 'kra-etims-integration'); ?></option>
        </select>
        <p class="description"><?php _e('Automatically update products in your API when they are modified.', 'kra-etims-integration'); ?></p>
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
        
        // Sanitize select fields
        if (isset($input['environment'])) {
            $sanitized['environment'] = sanitize_text_field($input['environment']);
        }
        
        // Sanitize checkbox fields
        $sanitized['auto_submit'] = isset($input['auto_submit']) ? 'yes' : 'no';
        
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
        $sanitized['auto_update_products'] = isset($input['auto_update_products']) ? 'yes' : 'no';
        
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
        if ($hook === 'post.php' && isset($_GET['post_type']) && $_GET['post_type'] === 'shop_order') {
            wp_enqueue_script(
                'kra-etims-wc-order-admin',
                KRA_ETIMS_WC_PLUGIN_URL . 'assets/js/order-admin.js',
                array('jquery'),
                KRA_ETIMS_WC_VERSION,
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
                    'customerTinFoundText' => __('Customer TIN found and populated.', 'kra-etims-integration'),
                    'customerTinNotFoundText' => __('No TIN found for this customer.', 'kra-etims-integration')
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
            wp_send_json_error(__('Security check failed.', 'kra-etims-integration'));
        }
        
        // Check order ID
        if (!isset($_GET['order_id'])) {
            wp_send_json_error(__('Order ID is required.', 'kra-etims-integration'));
        }
        
        try {
            $order_id = intval($_GET['order_id']);
            
            // Check if order exists
            $order = wc_get_order($order_id);
            if (!$order) {
                wp_send_json_error(__('Order not found.', 'kra-etims-integration'));
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
            wp_send_json_error(__('Security check failed.', 'kra-etims-integration'));
        }
        
        // Check if user is admin
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions. Only administrators can process refunds.', 'kra-etims-integration'));
        }
        
        // Check order ID
        if (!isset($_GET['order_id'])) {
            wp_send_json_error(__('Order ID is required.', 'kra-etims-integration'));
        }
        
        try {
            $order_id = intval($_GET['order_id']);
            
            // Check if order exists
            $order = wc_get_order($order_id);
            if (!$order) {
                wp_send_json_error(__('Order not found.', 'kra-etims-integration'));
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
            wp_send_json_error(__('Security check failed.', 'kra-etims-integration'));
        }
        
        // Check if customer ID is provided
        if (!isset($_POST['customer_id']) || empty($_POST['customer_id'])) {
            wp_send_json_error(__('Customer ID is required.', 'kra-etims-integration'));
        }
        
        $customer_id = intval($_POST['customer_id']);
        
        // Get customer TIN from user meta
        $customer_tin = get_user_meta($customer_id, '_customer_tin', true);
        
        if (!empty($customer_tin)) {
            wp_send_json_success(array(
                'tin' => $customer_tin,
                'message' => __('Customer TIN found.', 'kra-etims-integration')
            ));
        } else {
            wp_send_json_error(__('No TIN found for this customer.', 'kra-etims-integration'));
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
                    <p><?php _e('Order successfully submitted to Custom API.', 'kra-etims-integration'); ?></p>
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
                <p><?php _e('The refund has been successfully submitted to the custom API.', 'kra-etims-integration'); ?></p>
                <?php if ($refund_invoice_no): ?>
                <p><strong><?php _e('Refund Invoice No:', 'kra-etims-integration'); ?></strong> <code><?php echo esc_html($refund_invoice_no); ?></code></p>
                <?php endif; ?>
                <?php if ($refund_submitted_at): ?>
                <p><strong><?php _e('Processed at:', 'kra-etims-integration'); ?></strong> <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($refund_submitted_at))); ?></p>
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
