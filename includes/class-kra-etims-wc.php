<?php
/**
 * Main plugin class
 *
 * @package KRA_eTims_WC
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main plugin class
 */
class KRA_eTims_WC {
    /**
     * Plugin instance
     *
     * @var KRA_eTims_WC
     */
    private static $instance = null;

    /**
     * Plugin settings
     *
     * @var array
     */
    private $settings;

    /**
     * Constructor
     */
    public function __construct() {
        $this->settings = get_option('kra_etims_wc_settings', array());
    }

    /**
     * Get plugin instance
     *
     * @return KRA_eTims_WC
     */
    public static function get_instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize plugin
     */
    public function init() {
        // Include required files
        $this->includes();

        // Initialize admin
        if (is_admin()) {
            $this->init_admin();
        }

        // Add WooCommerce hooks
        $this->add_woocommerce_hooks();
        
        // Register shortcodes
        KRA_eTims_WC_QR_Code::register_shortcodes();
        
        // Initialize receipt display
        new KRA_eTims_WC_Receipt_Display();
        
        // Initialize category handler
        KRA_eTims_WC_Category_Handler::get_instance();
        
        // Initialize tax handler
        KRA_eTims_WC_Tax_Handler::get_instance();
        
        // Initialize bulk tax updater
        KRA_eTims_WC_Bulk_Tax_Updater::get_instance();
        
        // Initialize sync class
        new KRA_eTims_WC_Sync();
    }

    /**
     * Include required files
     */
    private function includes() {
        // Include API class
        require_once KRA_ETIMS_WC_PLUGIN_DIR . 'includes/class-kra-etims-wc-api.php';
        
        // Include admin class
        require_once KRA_ETIMS_WC_PLUGIN_DIR . 'includes/admin/class-kra-etims-wc-admin.php';
        
        // Include order handler class
        require_once KRA_ETIMS_WC_PLUGIN_DIR . 'includes/class-kra-etims-wc-order-handler.php';
        
        // Include receipt display class
        require_once KRA_ETIMS_WC_PLUGIN_DIR . 'includes/class-kra-etims-wc-receipt-display.php';
        
        // Include QR code class
        require_once KRA_ETIMS_WC_PLUGIN_DIR . 'includes/class-kra-etims-wc-qr-code.php';
        
        // Include sync class
        require_once KRA_ETIMS_WC_PLUGIN_DIR . 'includes/class-kra-etims-wc-sync.php';
        
        // Include tax handler class
        require_once KRA_ETIMS_WC_PLUGIN_DIR . 'includes/class-kra-etims-wc-tax-handler.php';
        
        // Include bulk tax updater class
        require_once KRA_ETIMS_WC_PLUGIN_DIR . 'includes/class-kra-etims-wc-bulk-tax-updater.php';
        
        // Include category handler class
        require_once KRA_ETIMS_WC_PLUGIN_DIR . 'includes/class-kra-etims-wc-category-handler.php';
    }

    /**
     * Initialize admin
     */
    private function init_admin() {
        $admin = new KRA_eTims_WC_Admin();
        $admin->init();
    }

    /**
     * Add WooCommerce hooks
     */
    private function add_woocommerce_hooks() {
        // Process order when status changes to completed
        add_action('woocommerce_order_status_completed', array($this, 'process_completed_order'), 10, 1);
        
        // Add custom order actions
        add_action('woocommerce_order_actions', array($this, 'add_order_actions'));
        add_action('woocommerce_order_action_kra_etims_submit', array($this, 'process_order_action_submit'));
        
        // Add order meta box - use the correct hook for WooCommerce orders
        add_action('add_meta_boxes', array($this, 'add_order_meta_box'), 10, 2);
        add_action('woocommerce_admin_order_data_after_billing_address', array($this, 'add_order_meta_box_woocommerce'), 10, 1);
        
        // Add customer TIN field to checkout
        add_action('woocommerce_after_checkout_billing_form', array($this, 'add_customer_tin_field'));
        add_action('woocommerce_checkout_update_order_meta', array($this, 'save_customer_tin_field'));
        
        // Add customer TIN field to admin order edit
        add_action('woocommerce_admin_order_data_after_billing_address', array($this, 'add_admin_customer_tin_field'), 10, 1);
        add_action('woocommerce_process_shop_order_meta', array($this, 'save_admin_customer_tin_field'), 10, 2);
        
        // Handle customer changes on orders
        add_action('woocommerce_order_customer_changed', array($this, 'handle_customer_change'), 10, 2);
        
        // Add customer TIN field to account pages
        add_action('woocommerce_edit_account_form', array($this, 'add_account_tin_field'));
        add_action('woocommerce_save_account_details', array($this, 'save_account_tin_field'));
    }

    /**
     * Process completed order
     *
     * @param int $order_id Order ID
     */
    public function process_completed_order($order_id) {
        // Check if auto-submit is enabled
        if (isset($this->settings['auto_submit']) && $this->settings['auto_submit'] === 'yes') {
            $order_handler = new KRA_eTims_WC_Order_Handler();
            $order_handler->process_order($order_id);
        }
    }

    /**
     * Add custom order actions
     *
     * @param array $actions Order actions
     * @return array
     */
    public function add_order_actions($actions) {
        $actions['kra_etims_submit'] = __('Submit to KRA eTims', 'kra-etims-integration');
        return $actions;
    }

    /**
     * Process order action submit
     *
     * @param WC_Order $order Order object
     */
    public function process_order_action_submit($order) {
        $order_handler = new KRA_eTims_WC_Order_Handler();
        $order_handler->process_order($order->get_id());
    }

    /**
     * Add order meta box
     *
     * @param string $post_type Post type
     * @param WP_Post $post Post object
     */
    public function add_order_meta_box($post_type, $post) {
        if ($post_type === 'shop_order') {
            add_meta_box(
                'kra_etims_wc_order_meta_box',
                __('KRA eTims Integration', 'kra-etims-integration'),
                array($this, 'render_order_meta_box'),
                'shop_order',
                'side',
                'default'
            );
        }
    }

    /**
     * Render order meta box
     *
     * @param WP_Post $post Post object
     */
    public function render_order_meta_box($post) {
        $order_id = $post->ID;
        $order = wc_get_order($order_id);
        $custom_api_status = get_post_meta($order_id, '_custom_api_status', true);
        $custom_api_invoice_no = get_post_meta($order_id, '_custom_api_invoice_no', true);
        $custom_api_submitted_at = get_post_meta($order_id, '_custom_api_submitted_at', true);
        
        // Get receipt details
        $receipt_number = get_post_meta($order_id, '_receipt_number', true);
        $receipt_signature = get_post_meta($order_id, '_receipt_signature', true);
        $receipt_date = get_post_meta($order_id, '_receipt_date', true);
        $mrc_number = get_post_meta($order_id, '_mrc_number', true);
        $sdc_id = get_post_meta($order_id, '_sdc_id', true);
        
        ?>
        <div class="kra-etims-wc-order-meta-box">
            <p>
                <strong><?php _e('Custom API Status:', 'kra-etims-integration'); ?></strong>
                <?php echo $custom_api_status ? esc_html($custom_api_status) : __('Not submitted', 'kra-etims-integration'); ?>
            </p>
            
            <?php 
            // Show customer TIN if available
            $customer_tin = get_post_meta($order_id, '_customer_tin', true);
            if ($customer_tin) : 
            ?>
            <p>
                <strong><?php _e('Customer TIN:', 'kra-etims-integration'); ?></strong>
                <?php echo esc_html($customer_tin); ?>
            </p>
            <?php endif; ?>
            
            <?php if ($custom_api_invoice_no) : ?>
            <p>
                <strong><?php _e('Invoice No:', 'kra-etims-integration'); ?></strong>
                <?php echo esc_html($custom_api_invoice_no); ?>
            </p>
            <?php endif; ?>
            
            <?php if ($receipt_number) : ?>
            <p>
                <strong><?php _e('Receipt No:', 'kra-etims-integration'); ?></strong>
                <?php echo esc_html($receipt_number); ?>
            </p>
            <?php endif; ?>
            
            <?php if ($receipt_signature) : ?>
            <p>
                <strong><?php _e('Receipt Signature:', 'kra-etims-integration'); ?></strong>
                <code><?php echo esc_html($this->format_receipt_signature($receipt_signature)); ?></code>
            </p>
            <?php endif; ?>
            
            <?php if ($receipt_date) : ?>
            <p>
                <strong><?php _e('Receipt Date:', 'kra-etims-integration'); ?></strong>
                <?php echo esc_html($receipt_date); ?>
            </p>
            <?php endif; ?>
            
            <?php if ($mrc_number) : ?>
            <p>
                <strong><?php _e('CU Number:', 'kra-etims-integration'); ?></strong>
                <?php echo esc_html($mrc_number); ?><br>
                <strong><?php _e('SCU ID:', 'kra-etims-integration'); ?></strong>
                <?php echo esc_html($sdc_id); ?>
            </p>
            <?php endif; ?>
            
            <?php 
            // Display QR code URL
            $qr_url = KRA_eTims_WC_QR_Code::get_order_qr_url($order_id);
            if ($qr_url) : 
            ?>
            <p>
                <strong><?php _e('QR Code URL:', 'kra-etims-integration'); ?></strong>
                <br>
                <div class="qr-code-url"><?php echo esc_html($qr_url); ?></div>
            </p>
            <div style="margin-top: 10px;">
                <strong><?php _e('QR Code:', 'kra-etims-integration'); ?></strong>
                <br>
                <?php echo KRA_eTims_WC_QR_Code::generate_qr_image($qr_url, 120); ?>
            </div>
            <?php endif; ?>
            
            <?php if ($custom_api_submitted_at) : ?>
            <p>
                <strong><?php _e('Submitted at:', 'kra-etims-integration'); ?></strong>
                <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($custom_api_submitted_at))); ?>
            </p>
            <?php endif; ?>
            
            <p>
                <a href="<?php echo wp_nonce_url(admin_url('admin-ajax.php?action=kra_etims_submit_order&order_id=' . $order_id), 'kra_etims_submit_order'); ?>" class="button">
                    <?php _e('Submit to Custom API', 'kra-etims-integration'); ?>
                </a>
            </p>
        </div>
        <?php
    }

    /**
     * Add order meta box for WooCommerce orders (alternative method)
     *
     * @param WC_Order $order Order object
     */
    public function add_order_meta_box_woocommerce($order) {
        $order_id = $order->get_id();
        $custom_api_status = get_post_meta($order_id, '_custom_api_status', true);
        $custom_api_invoice_no = get_post_meta($order_id, '_custom_api_invoice_no', true);
        $custom_api_submitted_at = get_post_meta($order_id, '_custom_api_submitted_at', true);
        
        // Get receipt details
        $receipt_number = get_post_meta($order_id, '_receipt_number', true);
        $receipt_signature = get_post_meta($order_id, '_receipt_signature', true);
        $receipt_date = get_post_meta($order_id, '_receipt_date', true);
        $mrc_number = get_post_meta($order_id, '_mrc_number', true);
        $sdc_id = get_post_meta($order_id, '_sdc_id', true);
        
        ?>
        <div class="kra-etims-wc-order-meta-box" style="background: #f9f9f9; padding: 15px; border: 1px solid #ddd; border-radius: 4px; margin: 10px 0;">
            <h3 style="margin-top: 0; color: #0073aa;">🔗 KRA eTims Integration</h3>
            
            <p>
                <strong><?php _e('Custom API Status:', 'kra-etims-integration'); ?></strong>
                <?php echo $custom_api_status ? esc_html($custom_api_status) : __('Not submitted', 'kra-etims-integration'); ?>
            </p>
            
            <?php 
            // Show customer TIN if available
            $customer_tin = get_post_meta($order_id, '_customer_tin', true);
            if ($customer_tin) : 
            ?>
            <p>
                <strong><?php _e('Customer TIN:', 'kra-etims-integration'); ?></strong>
                <?php echo esc_html($customer_tin); ?>
            </p>
            <?php endif; ?>
            
            <?php if ($custom_api_invoice_no) : ?>
            <p>
                <strong><?php _e('Invoice No:', 'kra-etims-integration'); ?></strong>
                <?php echo esc_html($custom_api_invoice_no); ?>
            </p>
            <?php endif; ?>
            
            <?php if ($receipt_number) : ?>
            <p>
                <strong><?php _e('Receipt No:', 'kra-etims-integration'); ?></strong>
                <?php echo esc_html($receipt_number); ?>
            </p>
            <?php endif; ?>
            
            <?php if ($receipt_signature) : ?>
            <p>
                <strong><?php _e('Receipt Signature:', 'kra-etims-integration'); ?></strong>
                <code><?php echo esc_html($this->format_receipt_signature($receipt_signature)); ?></code>
            </p>
            <?php endif; ?>
            
            <?php if ($receipt_date) : ?>
            <p>
                <strong><?php _e('Receipt Date:', 'kra-etims-integration'); ?></strong>
                <?php echo esc_html($receipt_date); ?>
            </p>
            <?php endif; ?>
            
            <?php if ($mrc_number) : ?>
            <p>
                <strong><?php _e('CU Number:', 'kra-etims-integration'); ?></strong>
                <?php echo esc_html($mrc_number); ?><br>
                <strong><?php _e('SCU ID:', 'kra-etims-integration'); ?></strong>
                <?php echo esc_html($sdc_id); ?>
            </p>
            <?php endif; ?>
            
            <?php 
            // Display QR code URL
            $qr_url = KRA_eTims_WC_QR_Code::get_order_qr_url($order_id);
            if ($qr_url) : 
            ?>
            <p>
                <strong><?php _e('QR Code URL:', 'kra-etims-integration'); ?></strong>
                <br>
                <div class="qr-code-url"><?php echo esc_html($qr_url); ?></div>
            </p>
            <div style="margin-top: 10px;">
                <strong><?php _e('QR Code:', 'kra-etims-integration'); ?></strong>
                <br>
                <?php echo KRA_eTims_WC_QR_Code::generate_qr_image($qr_url, 120); ?>
            </div>
            <?php endif; ?>
            
            <?php if ($custom_api_submitted_at) : ?>
            <p>
                <strong><?php _e('Submitted at:', 'kra-etims-integration'); ?></strong>
                <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($custom_api_submitted_at))); ?>
            </p>
            <?php endif; ?>
            
            <?php 
            // Determine if order can be submitted
            $order_status = $order->get_status();
            $already_submitted = ($custom_api_status === 'success');
            
            // Allow submission for orders that are not draft/pending and haven't been submitted
            $can_submit = !in_array($order_status, array('draft', 'pending')) && !$already_submitted;
            
            // Show appropriate button based on status
            if ($already_submitted) {
                // Already submitted - disabled button
                $button_text = __('Submit to Custom API', 'kra-etims-integration');
                $button_class = 'button button-primary';
                $disabled = true;
                $message = __('Order already submitted to custom API. Cannot resubmit.', 'kra-etims-integration');
            } elseif (in_array($order_status, array('draft', 'pending'))) {
                // Order not ready - disabled button
                $button_text = __('Submit to Custom API', 'kra-etims-integration');
                $button_class = 'button button-primary';
                $disabled = true;
                $message = __('Order must be processed before submitting.', 'kra-etims-integration');
            } else {
                // Ready to submit - enabled submit button
                $button_text = __('Submit to Custom API', 'kra-etims-integration');
                $button_class = 'button button-primary';
                $disabled = false;
                $message = __('Ready to submit to custom API.', 'kra-etims-integration');
            }
            ?>
            <p>
                <?php if ($disabled) : ?>
                <button class="<?php echo $button_class; ?>" disabled style="opacity: 0.6; cursor: not-allowed;">
                    <?php echo $button_text; ?>
                </button>
                <?php else : ?>
                <a href="<?php echo wp_nonce_url(admin_url('admin-ajax.php?action=kra_etims_submit_order&order_id=' . $order_id), 'kra_etims_submit_order'); ?>" class="<?php echo $button_class; ?>">
                    <?php echo $button_text; ?>
                </a>
                <?php endif; ?>
                <br><small style="color: <?php echo $disabled ? '#999' : '#666'; ?>;"><?php echo $message; ?></small>
            </p>
        </div>
        <?php
    }

    /**
     * Format internal data with dashes
     *
     * @param string $internal_data Internal data string
     * @return string Formatted internal data
     */
    private function format_internal_data($internal_data) {
        // Remove any existing dashes and format as PMOO-NY5U-H4GU-ELTR-D4GV-RUA6-XE
        $clean_data = str_replace('-', '', $internal_data);
        if (strlen($clean_data) >= 28) {
            return substr($clean_data, 0, 4) . '-' . 
                   substr($clean_data, 4, 4) . '-' . 
                   substr($clean_data, 8, 4) . '-' . 
                   substr($clean_data, 12, 4) . '-' . 
                   substr($clean_data, 16, 4) . '-' . 
                   substr($clean_data, 20, 4) . '-' . 
                   substr($clean_data, 24, 2);
        }
        return $internal_data;
    }

    /**
     * Format receipt signature with dashes
     *
     * @param string $receipt_signature Receipt signature string
     * @return string Formatted receipt signature
     */
    private function format_receipt_signature($receipt_signature) {
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

    /**
     * Add customer TIN field to checkout
     */
    public function add_customer_tin_field($checkout) {
        // Get customer TIN from user meta if logged in
        $customer_tin = '';
        if (is_user_logged_in()) {
            $customer_tin = get_user_meta(get_current_user_id(), '_customer_tin', true);
        }
        
        echo '<div id="customer_tin_field">';
        woocommerce_form_field('customer_tin', array(
            'type' => 'text',
            'class' => array('form-row-wide'),
            'label' => __('Customer TIN/PIN', 'kra-etims-integration'),
            'placeholder' => __('Enter 11-character tax identification number', 'kra-etims-integration'),
            'required' => false,
            'maxlength' => 11,
            'pattern' => '[A-Za-z0-9]{0,11}',
            'description' => __('Optional: Customer tax identification number (TIN/PIN) - must be exactly 11 characters (e.g., P051769063X)', 'kra-etims-integration')
        ), $customer_tin);
        echo '</div>';
    }

    /**
     * Save customer TIN field from checkout
     */
    public function save_customer_tin_field($order_id) {
        if (!empty($_POST['customer_tin'])) {
            $customer_tin = sanitize_text_field($_POST['customer_tin']);
            
            // Validate and format TIN
            $customer_tin = $this->validate_and_format_tin($customer_tin);
            
            update_post_meta($order_id, '_customer_tin', $customer_tin);
            
            // Also save to customer user meta if customer is logged in
            if (is_user_logged_in()) {
                $customer_id = get_current_user_id();
                update_user_meta($customer_id, '_customer_tin', $customer_tin);
            }
        }
    }

    /**
     * Add customer TIN field to admin order edit
     */
    public function add_admin_customer_tin_field($order) {
        $customer_tin = get_post_meta($order->get_id(), '_customer_tin', true);
        ?>
        <div class="form-field form-field-wide">
            <label for="customer_tin"><?php _e('Customer TIN/PIN:', 'kra-etims-integration'); ?></label>
            <input type="text" id="customer_tin" name="customer_tin" value="<?php echo esc_attr($customer_tin); ?>" 
                   placeholder="Enter 11-character tax identification number" maxlength="11" pattern="[A-Za-z0-9]{0,11}" style="width: 100%;" />
            <p class="description"><?php _e('Customer tax identification number (TIN/PIN) - must be exactly 11 characters (e.g., P051769063X) (optional)', 'kra-etims-integration'); ?></p>
        </div>
        <?php
    }

    /**
     * Save customer TIN field from admin
     */
    public function save_admin_customer_tin_field($order_id, $post) {
        if (isset($_POST['customer_tin'])) {
            $customer_tin = sanitize_text_field($_POST['customer_tin']);
            
            // Validate and format TIN
            $customer_tin = $this->validate_and_format_tin($customer_tin);
            
            update_post_meta($order_id, '_customer_tin', $customer_tin);
            
            // Also save to customer user meta if customer is associated with order
            $order = wc_get_order($order_id);
            if ($order && $order->get_customer_id()) {
                $customer_id = $order->get_customer_id();
                update_user_meta($customer_id, '_customer_tin', $customer_tin);
            }
        }
    }
    
    /**
     * Validate and format TIN/PIN
     *
     * @param string $tin TIN/PIN to validate
     * @return string Formatted TIN/PIN
     */
    private function validate_and_format_tin($tin) {
        // Remove any spaces or special characters, keep alphanumeric
        $tin = preg_replace('/[^A-Za-z0-9]/', '', $tin);
        
        // Convert to uppercase for consistency
        $tin = strtoupper($tin);
        
        // Ensure exactly 11 characters
        if (strlen($tin) !== 11) {
            if (strlen($tin) > 11) {
                // Truncate to 11 characters
                $tin = substr($tin, 0, 11);
            } else {
                // Pad with zeros to make it 11 characters
                $tin = str_pad($tin, 11, '0', STR_PAD_LEFT);
            }
        }
        
        return $tin;
    }

    /**
     * Handle customer change on order
     *
     * @param int $order_id Order ID
     * @param int $customer_id New customer ID
     */
    public function handle_customer_change($order_id, $customer_id) {
        if ($customer_id > 0) {
            // Get customer TIN from user meta
            $customer_tin = get_user_meta($customer_id, '_customer_tin', true);
            
            if (!empty($customer_tin)) {
                // Update order meta with customer TIN
                update_post_meta($order_id, '_customer_tin', $customer_tin);
            }
        }
    }

    /**
     * Add customer TIN field to account edit form
     */
    public function add_account_tin_field() {
        $customer_tin = get_user_meta(get_current_user_id(), '_customer_tin', true);
        ?>
        <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
            <label for="account_tin"><?php _e('Tax Identification Number (TIN)', 'kra-etims-integration'); ?></label>
            <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" 
                   name="account_tin" id="account_tin" 
                   value="<?php echo esc_attr($customer_tin); ?>" 
                   placeholder="<?php _e('Enter 11-character tax identification number', 'kra-etims-integration'); ?>" 
                   maxlength="11" pattern="[A-Za-z0-9]{0,11}" />
            <span class="description"><?php _e('Your tax identification number (TIN/PIN) - must be exactly 11 characters (e.g., P051769063X)', 'kra-etims-integration'); ?></span>
        </p>
        <?php
    }

    /**
     * Save customer TIN field from account form
     */
    public function save_account_tin_field($user_id) {
        if (isset($_POST['account_tin'])) {
            $customer_tin = sanitize_text_field($_POST['account_tin']);
            
            // Validate and format TIN
            $customer_tin = $this->validate_and_format_tin($customer_tin);
            
            update_user_meta($user_id, '_customer_tin', $customer_tin);
        }
    }
}
