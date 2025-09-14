<?php
/**
 * Product Handler Class
 *
 * @package KRA_eTims_WC
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Product Handler class
 */
class KRA_eTims_WC_Product_Handler {
    
    /**
     * API instance
     *
     * @var KRA_eTims_WC_API
     */
    private $api;
    
    /**
     * Settings
     *
     * @var array
     */
    private $settings;
    
    /**
     * Category handler instance
     *
     * @var KRA_eTims_WC_Category_Handler
     */
    private $category_handler;
    
    /**
     * Tax types mapping
     *
     * @var array
     */
    private $tax_types = array(
        'A' => array(
            'name' => 'A - Exempt',
            'rate' => 0,
            'description' => 'Exempt (0%)'
        ),
        'B' => array(
            'name' => 'B - VAT',
            'rate' => 16,
            'description' => 'VAT (16%)'
        ),
        'C' => array(
            'name' => 'C - Export',
            'rate' => 0,
            'description' => 'Export (0%)'
        ),
        'D' => array(
            'name' => 'D - Non VAT',
            'rate' => 0,
            'description' => 'Non VAT (0%)'
        )
    );
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->settings = get_option('kra_etims_wc_settings', array());
        $this->api = new KRA_eTims_WC_API();
        $this->category_handler = KRA_eTims_WC_Category_Handler::get_instance();
        
        // Hook into product save
        add_action('woocommerce_process_product_meta', array($this, 'process_product_save'), 10, 1);
        add_action('woocommerce_process_product_meta', array($this, 'process_product_update'), 20, 1);
        
        // Hook into product creation
        add_action('woocommerce_new_product', array($this, 'process_new_product'), 10, 1);
        
        // Add product meta fields
        add_action('woocommerce_product_options_general_product_data', array($this, 'add_product_meta_fields'));
        add_action('woocommerce_process_product_meta', array($this, 'save_product_meta_fields'));
        
        // Add product columns
        add_filter('manage_product_posts_columns', array($this, 'add_product_columns'));
        add_action('manage_product_posts_custom_column', array($this, 'display_product_columns'), 10, 2);
        
        // Auto-update product when category changes
        add_action('woocommerce_product_set_category_ids', array($this, 'update_product_from_category'), 10, 2);
        
        // Validate product before saving
        add_action('woocommerce_before_product_object_save', array($this, 'validate_product_before_save'), 10, 2);
        
        // Add validation messages
        add_action('admin_notices', array($this, 'display_validation_notices'));
    }
    
    /**
     * Update product data when category changes
     *
     * @param int $product_id Product ID
     * @param array $category_ids Category IDs
     */
    public function update_product_from_category($product_id, $category_ids) {
        if (!empty($category_ids)) {
            $primary_category_id = $category_ids[0];
            
            // Get category SID and unspec code
            $category_sid = $this->category_handler->get_category_server_id($primary_category_id);
            $category_unspec = $this->category_handler->get_category_unspec_code($primary_category_id);
            
            // Update product meta
            if ($category_sid) {
                update_post_meta($product_id, '_injonge_category_sid', $category_sid);
            }
            if ($category_unspec) {
                update_post_meta($product_id, '_injonge_unspec', $category_unspec);
            }
            
            // Log the update
            error_log('KRA eTims Product Handler: Updated product ' . $product_id . ' with category SID: ' . $category_sid . ', unspec: ' . $category_unspec);
        }
    }
    
    /**
     * Process new product creation
     *
     * @param int $product_id Product ID
     */
    public function process_new_product($product_id) {
        $product = wc_get_product($product_id);
        if (!$product) {
            return;
        }
        
        // Check if product has categories assigned
        $category_ids = $product->get_category_ids();
        if (empty($category_ids)) {
            // Add error notice
            $this->add_admin_notice(
                sprintf(
                    __('Product "%s" must be assigned to a category to inherit KRA eTims data. Please assign a category and save again.', 'kra-etims-integration'),
                    $product->get_name()
                ),
                'error'
            );
            return;
        }
        
        // Auto-populate from category
        $this->populate_product_from_category($product);
        
        // Validate that required data is populated
        $this->validate_product_kra_data($product);
        
        // Check if auto-submit is enabled
        if (!isset($this->settings['auto_submit_products']) || $this->settings['auto_submit_products'] !== 'yes') {
            return;
        }
        
        // Send product to API
        $this->send_product_to_api($product);
    }
    
    /**
     * Process product save
     *
     * @param int $product_id Product ID
     */
    public function process_product_save($product_id) {
        $product = wc_get_product($product_id);
        if (!$product) {
            return;
        }
        
        // Check if product has categories assigned
        $category_ids = $product->get_category_ids();
        if (empty($category_ids)) {
            // Add error notice
            $this->add_admin_notice(
                sprintf(
                    __('Product "%s" must be assigned to a category to inherit KRA eTims data. Please assign a category and save again.', 'kra-etims-integration'),
                    $product->get_name()
                ),
                'error'
            );
            return;
        }
        
        // Auto-populate from category
        $this->populate_product_from_category($product);
        
        // Validate that required data is populated
        $this->validate_product_kra_data($product);
        
        // Save product meta fields
        $this->save_product_meta_fields($product_id);
    }
    
    /**
     * Process product update
     *
     * @param int $product_id Product ID
     */
    public function process_product_update($product_id) {
        $product = wc_get_product($product_id);
        if (!$product) {
            return;
        }
        
        // Check if auto-update is enabled
        if (!isset($this->settings['auto_update_products']) || $this->settings['auto_update_products'] !== 'yes') {
            return;
        }
        
        // Send product to API for update
        $this->send_product_to_api($product, 'update');
    }
    
    /**
     * Populate product data from category
     *
     * @param WC_Product $product Product object
     */
    private function populate_product_from_category($product) {
        $category_ids = $product->get_category_ids();
        
        if (!empty($category_ids)) {
            $primary_category_id = $category_ids[0];
            
            // Get category SID and unspec code
            $category_sid = $this->category_handler->get_category_server_id($primary_category_id);
            $category_unspec = $this->category_handler->get_category_unspec_code($primary_category_id);
            
            // Update product meta if not already set
            if ($category_sid && !get_post_meta($product->get_id(), '_injonge_category_sid', true)) {
                update_post_meta($product->get_id(), '_injonge_category_sid', $category_sid);
            }
            
            if ($category_unspec && !get_post_meta($product->get_id(), '_injonge_unspec', true)) {
                update_post_meta($product->get_id(), '_injonge_unspec', $category_unspec);
            }
        }
    }
    
    /**
     * Send product to API
     *
     * @param WC_Product $product Product object
     * @param string $action Action type (create or update)
     * @return array Response data
     */
    public function send_product_to_api($product, $action = 'create') {
        try {
            // Prepare product data
            $product_data = $this->prepare_product_data($product);
            
            // Send to API
            $response = $this->api->send_product_to_api($product_data, $action);
            
            // Process response
            if ($response && isset($response['injongecode'])) {
                // Save injonge code
                update_post_meta($product->get_id(), '_injonge_code', $response['injongecode']);
                update_post_meta($product->get_id(), '_injonge_sid', $response['sid']);
                update_post_meta($product->get_id(), '_injonge_status', 'success');
                update_post_meta($product->get_id(), '_injonge_response', $response);
                update_post_meta($product->get_id(), '_injonge_last_sync', current_time('mysql'));
                
                // Add product note
                $note = sprintf(
                    __('Product successfully %s to API. Injonge Code: %s, SID: %s', 'kra-etims-integration'),
                    $action === 'create' ? 'sent' : 'updated',
                    $response['injongecode'],
                    $response['sid']
                );
                
                $product->add_meta_data('_api_note', $note);
                $product->save_meta_data();
                
                return array(
                    'success' => true,
                    'message' => $note,
                    'injonge_code' => $response['injongecode'],
                    'sid' => $response['sid']
                );
            } else {
                throw new Exception('Invalid API response: Missing injonge_code');
            }
            
        } catch (Exception $e) {
            // Save error status
            update_post_meta($product->get_id(), '_injonge_status', 'failed');
            update_post_meta($product->get_id(), '_injonge_error', $e->getMessage());
            update_post_meta($product->get_id(), '_injonge_last_sync', current_time('mysql'));
            
            // Add error note
            $error_note = sprintf(
                __('Failed to %s product to API: %s', 'kra-etims-integration'),
                $action === 'create' ? 'send' : 'update',
                $e->getMessage()
            );
            
            $product->add_meta_data('_api_error_note', $error_note);
            $product->save_meta_data();
            
            return array(
                'success' => false,
                'message' => $error_note
            );
        }
    }
    
    /**
     * Prepare product data for API
     *
     * @param WC_Product $product Product object
     * @return array Product data
     */
    private function prepare_product_data($product) {
        // Get product meta
        $unspec = get_post_meta($product->get_id(), '_injonge_unspec', true);
        $taxid = get_post_meta($product->get_id(), '_injonge_taxid', true);
        $category_sid = get_post_meta($product->get_id(), '_injonge_category_sid', true);
        $stockable = get_post_meta($product->get_id(), '_injonge_stockable', true);
        $opening_stock = get_post_meta($product->get_id(), '_injonge_opening_stock', true);
        
        // If unspec is not set, try to get from category
        if (empty($unspec)) {
            $category_ids = $product->get_category_ids();
            if (!empty($category_ids)) {
                $primary_category_id = $category_ids[0];
                $unspec = $this->category_handler->get_category_unspec_code($primary_category_id);
            }
        }
        
        // If category SID is not set, try to get from category
        if (empty($category_sid)) {
            $category_ids = $product->get_category_ids();
            if (!empty($category_ids)) {
                $primary_category_id = $category_ids[0];
                $category_sid = $this->category_handler->get_category_server_id($primary_category_id);
            }
        }
        
        // Get product image
        $image_url = '';
        if ($product->get_image_id()) {
            $image_url = wp_get_attachment_url($product->get_image_id());
        }
        
        // Get TIN from settings
        $tin = isset($this->settings['tin']) ? $this->settings['tin'] : '';
        $bhfId = isset($this->settings['bhfId']) ? $this->settings['bhfId'] : '00';
        
        // Prepare product data
        $product_data = array(
            'product_name' => $product->get_name(),
            'unspec' => $unspec ?: '4111460100', // Default if not set
            'price' => $product->get_regular_price(),
            'taxid' => $taxid ?: 'B', // Default to VAT (16%)
            'tin' => $tin,
            'category_id' => $category_sid ?: '324', // Use category SID instead of hardcoded ID
            'image' => $image_url,
            'bhfId' => $bhfId,
            'stockable' => $stockable ?: 0,
            'opening_stock' => $opening_stock ?: 0
        );
        
        return $product_data;
    }
    
    /**
     * Add product meta fields
     */
    public function add_product_meta_fields() {
        global $post;
        
        echo '<div class="options_group">';
        echo '<h4 style="margin: 0 0 10px 0; color: #0073aa;">KRA eTims Product Settings</h4>';
        
        // Check if product has categories
        $category_ids = get_the_terms($post->ID, 'product_cat');
        $has_categories = $category_ids && !is_wp_error($category_ids) && !empty($category_ids);
        
        if (!$has_categories) {
            echo '<div class="notice notice-warning" style="margin: 10px 0;">';
            echo '<p><strong>' . __('⚠️ Category Required', 'kra-etims-integration') . '</strong></p>';
            echo '<p>' . __('This product must be assigned to a category to inherit KRA eTims data (UNSPSC Code and Category SID). Please assign a category first.', 'kra-etims-integration') . '</p>';
            echo '</div>';
        }
        
        // UNSPSC Code (auto-populated from category)
        $unspec = get_post_meta($post->ID, '_injonge_unspec', true);
        $category_unspec = '';
        
        if ($has_categories) {
            $primary_category = $category_ids[0];
            $category_unspec = $this->category_handler->get_category_unspec_code($primary_category->term_id);
        }
        
        woocommerce_wp_text_input(array(
            'id' => '_injonge_unspec',
            'label' => __('UNSPSC Code', 'kra-etims-integration'),
            'placeholder' => $category_unspec ?: '4111460100',
            'value' => $unspec,
            'desc_tip' => true,
            'description' => $has_categories 
                ? __('UNSPSC code for the product. Auto-populated from category if available.', 'kra-etims-integration')
                : __('UNSPSC code for the product. Will be auto-populated when a category is assigned.', 'kra-etims-integration'),
            'custom_attributes' => $has_categories ? array() : array('readonly' => 'readonly')
        ));
        
        // Tax ID (updated to A,B,C,D)
        woocommerce_wp_select(array(
            'id' => '_injonge_taxid',
            'label' => __('Tax ID', 'kra-etims-integration'),
            'options' => array(
                '' => __('Select Tax Type', 'kra-etims-integration'),
                'A' => 'A - Exempt (0%)',
                'B' => 'B - VAT (16%)',
                'C' => 'C - Export (0%)',
                'D' => 'D - Non VAT (0%)'
            ),
            'desc_tip' => true,
            'description' => __('Tax classification for the product. This is mandatory for KRA eTims compliance.', 'kra-etims-integration')
        ));
        
        // Category SID (auto-populated from category)
        $category_sid = get_post_meta($post->ID, '_injonge_category_sid', true);
        $category_sid_from_category = '';
        
        if ($has_categories) {
            $primary_category = $category_ids[0];
            $category_sid_from_category = $this->category_handler->get_category_server_id($primary_category->term_id);
        }
        
        woocommerce_wp_text_input(array(
            'id' => '_injonge_category_sid',
            'label' => __('Category SID', 'kra-etims-integration'),
            'placeholder' => $category_sid_from_category ?: 'Auto from category',
            'value' => $category_sid,
            'desc_tip' => true,
            'description' => $has_categories 
                ? __('Category Server ID. Auto-populated from category if available.', 'kra-etims-integration')
                : __('Category Server ID. Will be auto-populated when a category is assigned.', 'kra-etims-integration'),
            'custom_attributes' => $has_categories ? array() : array('readonly' => 'readonly')
        ));
        
        // Stockable
        woocommerce_wp_checkbox(array(
            'id' => '_injonge_stockable',
            'label' => __('Stockable', 'kra-etims-integration'),
            'desc_tip' => true,
            'description' => __('Whether this product is stockable', 'kra-etims-integration')
        ));
        
        // Opening Stock
        woocommerce_wp_text_input(array(
            'id' => '_injonge_opening_stock',
            'label' => __('Opening Stock', 'kra-etims-integration'),
            'type' => 'number',
            'placeholder' => '0',
            'desc_tip' => true,
            'description' => __('Initial stock quantity', 'kra-etims-integration')
        ));
        
        // Display current injonge code if exists
        $injonge_code = get_post_meta($post->ID, '_injonge_code', true);
        if ($injonge_code) {
            echo '<p class="form-field">';
            echo '<label>' . __('Current Injonge Code', 'kra-etims-integration') . '</label>';
            echo '<input type="text" value="' . esc_attr($injonge_code) . '" readonly style="background-color: #f0f0f0;" />';
            echo '</p>';
        }
        
        // Display last sync status
        $last_sync = get_post_meta($post->ID, '_injonge_last_sync', true);
        $sync_status = get_post_meta($post->ID, '_injonge_status', true);
        
        if ($last_sync) {
            echo '<p class="form-field">';
            echo '<label>' . __('Last API Sync', 'kra-etims-integration') . '</label>';
            echo '<input type="text" value="' . esc_attr($last_sync) . ' (' . esc_attr($sync_status) . ')" readonly style="background-color: #f0f0f0;" />';
            echo '</p>';
        }
        
        // Display category information if available
        if ($has_categories) {
            $primary_category = $category_ids[0];
            $category_unspec = $this->category_handler->get_category_unspec_code($primary_category->term_id);
            $category_sid = $this->category_handler->get_category_server_id($primary_category->term_id);
            
            echo '<div class="form-field" style="background-color: #f9f9f9; padding: 10px; border-left: 4px solid #0073aa;">';
            echo '<label style="font-weight: bold;">' . __('Category Information', 'kra-etims-integration') . '</label>';
            echo '<p style="margin: 5px 0;"><strong>' . __('Category:', 'kra-etims-integration') . '</strong> ' . esc_html($primary_category->name) . '</p>';
            echo '<p style="margin: 5px 0;"><strong>' . __('Item Code:', 'kra-etims-integration') . '</strong> ' . (empty($category_unspec) ? '<span style="color: red;">Not set</span>' : esc_html($category_unspec)) . '</p>';
            echo '<p style="margin: 5px 0;"><strong>' . __('Server ID:', 'kra-etims-integration') . '</strong> ' . (empty($category_sid) ? '<span style="color: red;">Not set</span>' : esc_html($category_sid)) . '</p>';
            echo '</div>';
        }
        
        echo '</div>';
    }
    
    /**
     * Save product meta fields
     *
     * @param int $product_id Product ID
     */
    public function save_product_meta_fields($product_id) {
        // Save UNSPSC code
        if (isset($_POST['_injonge_unspec'])) {
            update_post_meta($product_id, '_injonge_unspec', sanitize_text_field($_POST['_injonge_unspec']));
        }
        
        // Save Tax ID
        if (isset($_POST['_injonge_taxid'])) {
            update_post_meta($product_id, '_injonge_taxid', sanitize_text_field($_POST['_injonge_taxid']));
        }
        
        // Save Category SID
        if (isset($_POST['_injonge_category_sid'])) {
            update_post_meta($product_id, '_injonge_category_sid', sanitize_text_field($_POST['_injonge_category_sid']));
        }
        
        // Save Stockable
        $stockable = isset($_POST['_injonge_stockable']) ? 'yes' : 'no';
        update_post_meta($product_id, '_injonge_stockable', $stockable);
        
        // Save Opening Stock
        if (isset($_POST['_injonge_opening_stock'])) {
            update_post_meta($product_id, '_injonge_opening_stock', intval($_POST['_injonge_opening_stock']));
        }
    }
    
    /**
     * Add product columns
     *
     * @param array $columns Existing columns
     * @return array Modified columns
     */
    public function add_product_columns($columns) {
        $new_columns = array();
        
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            if ($key === 'price') {
                $new_columns['category_status'] = __('Category Status', 'kra-etims-integration');
                $new_columns['injonge_code'] = __('Injonge Code', 'kra-etims-integration');
                $new_columns['tax_type'] = __('Tax Type', 'kra-etims-integration');
                $new_columns['api_status'] = __('API Status', 'kra-etims-integration');
            }
        }
        
        return $new_columns;
    }
    
    /**
     * Display product columns
     *
     * @param string $column Column name
     * @param int $post_id Post ID
     */
    public function display_product_columns($column, $post_id) {
        switch ($column) {
            case 'category_status':
                $product = wc_get_product($post_id);
                if ($product) {
                    $category_ids = $product->get_category_ids();
                    if (empty($category_ids)) {
                        echo '<span style="color: red;">❌ ' . __('No Category', 'kra-etims-integration') . '</span>';
                    } else {
                        $primary_category_id = $category_ids[0];
                        $category = get_term($primary_category_id, 'product_cat');
                        $category_unspec = $this->category_handler->get_category_unspec_code($primary_category_id);
                        $category_sid = $this->category_handler->get_category_server_id($primary_category_id);
                        
                        if (empty($category_unspec)) {
                            echo '<span style="color: orange;">⚠️ ' . __('No Item Code', 'kra-etims-integration') . '</span>';
                        } elseif (empty($category_sid)) {
                            echo '<span style="color: orange;">⚠️ ' . __('No SID', 'kra-etims-integration') . '</span>';
                        } else {
                            echo '<span style="color: green;">✅ ' . esc_html($category ? $category->name : 'Unknown') . '</span>';
                        }
                    }
                } else {
                    echo '<span style="color: #999;">—</span>';
                }
                break;
                
            case 'injonge_code':
                $injonge_code = get_post_meta($post_id, '_injonge_code', true);
                if ($injonge_code) {
                    echo '<code>' . esc_html($injonge_code) . '</code>';
                } else {
                    echo '<span style="color: #999;">—</span>';
                }
                break;
                
            case 'tax_type':
                $taxid = get_post_meta($post_id, '_injonge_taxid', true);
                if ($taxid && isset($this->tax_types[$taxid])) {
                    $tax_info = $this->tax_types[$taxid];
                    echo '<span title="' . esc_attr($tax_info['description']) . '">' . esc_html($tax_info['name']) . '</span>';
                } else {
                    echo '<span style="color: red;">⚠️ ' . __('Not Set', 'kra-etims-integration') . '</span>';
                }
                break;
                
            case 'api_status':
                $status = get_post_meta($post_id, '_injonge_status', true);
                $last_sync = get_post_meta($post_id, '_injonge_last_sync', true);
                
                if ($status === 'success') {
                    echo '<span style="color: green;">✅ ' . __('Synced', 'kra-etims-integration') . '</span>';
                } elseif ($status === 'failed') {
                    echo '<span style="color: red;">❌ ' . __('Failed', 'kra-etims-integration') . '</span>';
                } else {
                    echo '<span style="color: #999;">—</span>';
                }
                
                if ($last_sync) {
                    echo '<br><small>' . esc_html($last_sync) . '</small>';
                }
                break;
        }
    }
    
    /**
     * Get tax type information
     *
     * @param string $taxid Tax ID
     * @return array|false Tax type info or false if not found
     */
    public function get_tax_type_info($taxid) {
        return isset($this->tax_types[$taxid]) ? $this->tax_types[$taxid] : false;
    }
    
    /**
     * Validate product KRA data
     *
     * @param WC_Product $product Product object
     */
    private function validate_product_kra_data($product) {
        $product_id = $product->get_id();
        $unspec = get_post_meta($product_id, '_injonge_unspec', true);
        $category_sid = get_post_meta($product_id, '_injonge_category_sid', true);
        $taxid = get_post_meta($product_id, '_injonge_taxid', true);
        
        $errors = array();
        
        // Check if unspec code is set
        if (empty($unspec)) {
            $errors[] = __('UNSPSC Code is required. Please assign the product to a category with an item code.', 'kra-etims-integration');
        }
        
        // Check if category SID is set
        if (empty($category_sid)) {
            $errors[] = __('Category SID is required. Please assign the product to a category that has been sent to the API.', 'kra-etims-integration');
        }
        
        // Check if tax type is set
        if (empty($taxid)) {
            $errors[] = __('Tax Type is required. Please select a tax type (A, B, C, or D).', 'kra-etims-integration');
        }
        
        // If there are errors, add admin notice
        if (!empty($errors)) {
            $error_message = sprintf(
                __('Product "%s" has missing KRA eTims data: %s', 'kra-etims-integration'),
                $product->get_name(),
                implode(', ', $errors)
            );
            $this->add_admin_notice($error_message, 'warning');
        }
    }
    
    /**
     * Validate product before saving
     *
     * @param WC_Product $product Product object
     * @param bool $updating Whether this is an update
     */
    public function validate_product_before_save($product, $updating) {
        // Skip validation for new products (they will be validated after save)
        if (!$updating) {
            return;
        }
        
        $product_id = $product->get_id();
        $category_ids = $product->get_category_ids();
        
        // Check if product has categories
        if (empty($category_ids)) {
            $this->add_validation_error(
                sprintf(
                    __('Product "%s" must be assigned to a category to inherit KRA eTims data. Please assign a category and save again.', 'kra-etims-integration'),
                    $product->get_name()
                )
            );
            return;
        }
        
        // Check if category has required data
        $primary_category_id = $category_ids[0];
        $category_unspec = $this->category_handler->get_category_unspec_code($primary_category_id);
        $category_sid = $this->category_handler->get_category_server_id($primary_category_id);
        
        if (empty($category_unspec)) {
            $category = get_term($primary_category_id, 'product_cat');
            $this->add_validation_error(
                sprintf(
                    __('Category "%s" does not have an item code set. Please set an item code for the category first.', 'kra-etims-integration'),
                    $category ? $category->name : 'Unknown'
                )
            );
        }
        
        if (empty($category_sid)) {
            $category = get_term($primary_category_id, 'product_cat');
            $this->add_validation_error(
                sprintf(
                    __('Category "%s" has not been sent to the API yet. Please ensure the category is properly configured and sent to the API.', 'kra-etims-integration'),
                    $category ? $category->name : 'Unknown'
                )
            );
        }
    }
    
    /**
     * Add validation error
     *
     * @param string $message Error message
     */
    private function add_validation_error($message) {
        $errors = get_transient('kra_etims_validation_errors');
        if (!$errors) {
            $errors = array();
        }
        $errors[] = $message;
        set_transient('kra_etims_validation_errors', $errors, 60);
    }
    
    /**
     * Display validation notices
     */
    public function display_validation_notices() {
        $errors = get_transient('kra_etims_validation_errors');
        if ($errors) {
            foreach ($errors as $error) {
                echo '<div class="notice notice-error"><p>' . esc_html($error) . '</p></div>';
            }
            delete_transient('kra_etims_validation_errors');
        }
    }
    
    /**
     * Add admin notice
     *
     * @param string $message Message to display
     * @param string $type Notice type (error, warning, success, info)
     */
    private function add_admin_notice($message, $type = 'info') {
        $notice_key = 'kra_etims_product_' . time();
        set_transient($notice_key, array(
            'message' => $message,
            'type' => $type
        ), 60);
        
        add_action('admin_notices', function() use ($notice_key) {
            $notice = get_transient($notice_key);
            if ($notice) {
                $class = 'notice notice-' . $notice['type'];
                printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($notice['message']));
                delete_transient($notice_key);
            }
        });
    }
} 