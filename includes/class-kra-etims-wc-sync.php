<?php
/**
 * Synchronization Class
 *
 * @package KRA_eTims_WC
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Synchronization Class
 */
class KRA_eTims_WC_Sync {
    
    /**
     * API instance
     *
     * @var KRA_eTims_WC_API
     */
    private $api;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->api = new KRA_eTims_WC_API();
        
        // Add admin hooks
        add_action('admin_menu', array($this, 'add_sync_menu'));
        add_action('wp_ajax_kra_etims_sync_categories', array($this, 'ajax_sync_categories'));
        add_action('wp_ajax_kra_etims_sync_products', array($this, 'ajax_sync_products'));
        add_action('wp_ajax_kra_etims_get_sync_status', array($this, 'ajax_get_sync_status'));
        add_action('wp_ajax_kra_etims_clear_category_data', array($this, 'ajax_clear_category_data'));
        add_action('wp_ajax_kra_etims_clear_product_data', array($this, 'ajax_clear_product_data'));
    }
    
    /**
     * Add sync menu
     */
    public function add_sync_menu() {
        add_submenu_page(
            'kra-etims-wc',
            __('Sync Categories & Products', 'kra-etims-integration'),
            __('Sync Items', 'kra-etims-integration'),
            'manage_woocommerce',
            'kra-etims-sync',
            array($this, 'render_sync_page')
        );
    }
    
    /**
     * Render sync page
     */
    public function render_sync_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('KRA eTims - Sync Categories & Products', 'kra-etims-integration'); ?></h1>
            
            <div class="kra-etims-sync-container">
                <!-- Categories Section -->
                <div class="kra-etims-sync-section">
                    <h2><?php _e('Categories Synchronization', 'kra-etims-integration'); ?></h2>
                    <p><?php _e('Upload categories to your API and get SID (Server ID) for each category.', 'kra-etims-integration'); ?></p>
                    
                    <div class="kra-etims-category-status">
                        <?php $this->display_category_status(); ?>
                    </div>
                    
                    <button id="sync-categories-btn" class="button button-primary">
                        <?php _e('Sync Categories to API', 'kra-etims-integration'); ?>
                    </button>
                    <button id="clear-categories-btn" class="button button-secondary" style="margin-left: 10px;">
                        <?php _e('Clear Category SIDs', 'kra-etims-integration'); ?>
                    </button>
                    
                    <div id="category-sync-progress" style="display: none;">
                        <div class="progress-bar">
                            <div class="progress-fill"></div>
                        </div>
                        <p id="category-sync-status"><?php _e('Syncing categories...', 'kra-etims-integration'); ?></p>
                    </div>
                </div>
                
                <!-- Products Section -->
                <div class="kra-etims-sync-section">
                    <h2><?php _e('Products Synchronization', 'kra-etims-integration'); ?></h2>
                    <p><?php _e('Upload products to your API. Only products with categories that have SID and unspec code will be uploaded.', 'kra-etims-integration'); ?></p>
                    
                    <div class="kra-etims-product-status">
                        <?php $this->display_product_status(); ?>
                    </div>
                    
                    <button id="sync-products-btn" class="button button-primary">
                        <?php _e('Sync Products to API', 'kra-etims-integration'); ?>
                    </button>
                    <button id="clear-products-btn" class="button button-secondary" style="margin-left: 10px;">
                        <?php _e('Clear Product Data', 'kra-etims-integration'); ?>
                    </button>
                    
                    <div id="product-sync-progress" style="display: none;">
                        <div class="progress-bar">
                            <div class="progress-fill"></div>
                        </div>
                        <p id="product-sync-status"><?php _e('Syncing products...', 'kra-etims-integration'); ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
            .kra-etims-sync-container {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 30px;
                margin-top: 20px;
            }
            
            .kra-etims-sync-section {
                background: #fff;
                border: 1px solid #ccd0d4;
                border-radius: 4px;
                padding: 20px;
            }
            
            .kra-etims-sync-section h2 {
                margin-top: 0;
                color: #0073aa;
            }
            
            .progress-bar {
                width: 100%;
                height: 20px;
                background: #f0f0f0;
                border-radius: 10px;
                overflow: hidden;
                margin: 10px 0;
            }
            
            .progress-fill {
                height: 100%;
                background: #0073aa;
                width: 0%;
                transition: width 0.3s ease;
            }
            
            .status-table {
                width: 100%;
                border-collapse: collapse;
                margin: 15px 0;
            }
            
            .status-table th,
            .status-table td {
                padding: 8px;
                text-align: left;
                border-bottom: 1px solid #ddd;
            }
            
            .status-table th {
                background: #f9f9f9;
                font-weight: bold;
            }
            
            .status-ok {
                color: #00a32a;
                font-weight: bold;
            }
            
            .status-warning {
                color: #dba617;
                font-weight: bold;
            }
            
            .status-error {
                color: #d63638;
                font-weight: bold;
            }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Sync Categories
            $('#sync-categories-btn').on('click', function() {
                var $btn = $(this);
                var $progress = $('#category-sync-progress');
                var $status = $('#category-sync-status');
                
                $btn.prop('disabled', true);
                $progress.show();
                $status.text('Starting category sync...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'kra_etims_sync_categories',
                        nonce: '<?php echo wp_create_nonce('kra_etims_sync'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $status.text('Categories synced successfully!');
                            $('.progress-fill').css('width', '100%');
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        } else {
                            $status.text('Error: ' + response.data);
                        }
                    },
                    error: function() {
                        $status.text('Network error occurred');
                    },
                    complete: function() {
                        $btn.prop('disabled', false);
                    }
                });
            });
            
            // Sync Products
            $('#sync-products-btn').on('click', function() {
                var $btn = $(this);
                var $progress = $('#product-sync-progress');
                var $status = $('#product-sync-status');
                
                $btn.prop('disabled', true);
                $progress.show();
                $status.text('Starting product sync...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'kra_etims_sync_products',
                        nonce: '<?php echo wp_create_nonce('kra_etims_sync'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $status.text('Products synced successfully!');
                            $('.progress-fill').css('width', '100%');
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        } else {
                            $status.text('Error: ' + response.data);
                        }
                    },
                    error: function() {
                        $status.text('Network error occurred');
                    },
                    complete: function() {
                        $btn.prop('disabled', false);
                    }
                });
            });
            
            // Clear Category Data
            $('#clear-categories-btn').on('click', function() {
                if (!confirm('Are you sure you want to clear all Category Server IDs (SID)? This action cannot be undone.')) {
                    return;
                }
                
                var $btn = $(this);
                var $progress = $('#category-sync-progress');
                var $status = $('#category-sync-status');
                
                $btn.prop('disabled', true);
                $progress.show();
                $status.text('Clearing category data...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'kra_etims_clear_category_data',
                        nonce: '<?php echo wp_create_nonce('kra_etims_sync'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $status.text('Category data cleared successfully!');
                            $('.progress-fill').css('width', '100%');
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        } else {
                            $status.text('Error: ' + response.data);
                        }
                    },
                    error: function() {
                        $status.text('Network error occurred');
                    },
                    complete: function() {
                        $btn.prop('disabled', false);
                    }
                });
            });
            
            // Clear Product Data
            $('#clear-products-btn').on('click', function() {
                if (!confirm('Are you sure you want to clear all Product KRA data (injonge codes, SIDs, sync status)? This action cannot be undone.')) {
                    return;
                }
                
                var $btn = $(this);
                var $progress = $('#product-sync-progress');
                var $status = $('#product-sync-status');
                
                $btn.prop('disabled', true);
                $progress.show();
                $status.text('Clearing product data...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'kra_etims_clear_product_data',
                        nonce: '<?php echo wp_create_nonce('kra_etims_sync'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $status.text('Product data cleared successfully!');
                            $('.progress-fill').css('width', '100%');
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        } else {
                            $status.text('Error: ' + response.data);
                        }
                    },
                    error: function() {
                        $status.text('Network error occurred');
                    },
                    complete: function() {
                        $btn.prop('disabled', false);
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Display category status
     */
    private function display_category_status() {
        $categories = get_terms(array(
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
        ));
        
        $total_categories = count($categories);
        $synced_categories = 0;
        $categories_with_sid = 0;
        $categories_with_unspec = 0;
        
        echo '<table class="status-table">';
        echo '<tr><th>Category</th><th>Unspec Code</th><th>SID</th><th>Status</th></tr>';
        
        foreach ($categories as $category) {
            $unspec_code = get_term_meta($category->term_id, '_kra_etims_unspec_code', true);
            $server_id = get_term_meta($category->term_id, '_kra_etims_server_id', true);
            
            if ($server_id) {
                $categories_with_sid++;
            }
            if ($unspec_code) {
                $categories_with_unspec++;
            }
            if ($server_id && $unspec_code) {
                $synced_categories++;
            }
            
            $status_class = 'status-error';
            $status_text = 'Not Ready';
            
            if ($server_id && $unspec_code) {
                $status_class = 'status-ok';
                $status_text = 'Ready';
            } elseif ($unspec_code) {
                $status_class = 'status-warning';
                $status_text = 'Needs SID';
            } elseif ($server_id) {
                $status_class = 'status-warning';
                $status_text = 'Needs Unspec Code';
            }
            
            echo '<tr>';
            echo '<td>' . esc_html($category->name) . '</td>';
            echo '<td>' . esc_html($unspec_code ?: 'Not Set') . '</td>';
            echo '<td>' . esc_html($server_id ?: 'Not Set') . '</td>';
            echo '<td class="' . $status_class . '">' . esc_html($status_text) . '</td>';
            echo '</tr>';
        }
        
        echo '</table>';
        
        echo '<div class="sync-summary">';
        echo '<p><strong>Summary:</strong></p>';
        echo '<ul>';
        echo '<li>Total Categories: ' . $total_categories . '</li>';
        echo '<li>With Unspec Code: ' . $categories_with_unspec . '</li>';
        echo '<li>With SID: ' . $categories_with_sid . '</li>';
        echo '<li>Fully Synced: ' . $synced_categories . '</li>';
        echo '</ul>';
        echo '</div>';
    }
    
    /**
     * Display product status
     */
    private function display_product_status() {
        $products = wc_get_products(array(
            'limit' => -1,
            'status' => 'publish',
        ));
        
        $total_products = count($products);
        $ready_products = 0;
        $needs_category = 0;
        $needs_unspec = 0;
        $needs_sid = 0;
        
        echo '<table class="status-table">';
        echo '<tr><th>Product</th><th>Category</th><th>Unspec Code</th><th>SID</th><th>Status</th></tr>';
        
        foreach ($products as $product) {
            $product_id = $product->get_id();
            $categories = get_the_terms($product_id, 'product_cat');
            
            $category_name = 'No Category';
            $unspec_code = 'Not Set';
            $server_id = 'Not Set';
            $status_class = 'status-error';
            $status_text = 'No Category';
            
            if ($categories && !is_wp_error($categories)) {
                $primary_category = $categories[0];
                $category_name = $primary_category->name;
                
                $unspec_code = get_term_meta($primary_category->term_id, '_kra_etims_unspec_code', true);
                $server_id = get_term_meta($primary_category->term_id, '_kra_etims_server_id', true);
                
                if ($server_id && $unspec_code) {
                    $status_class = 'status-ok';
                    $status_text = 'Ready';
                    $ready_products++;
                } elseif ($unspec_code) {
                    $status_class = 'status-warning';
                    $status_text = 'Needs SID';
                    $needs_sid++;
                } else {
                    $status_class = 'status-warning';
                    $status_text = 'Needs Unspec Code';
                    $needs_unspec++;
                }
            } else {
                $needs_category++;
            }
            
            echo '<tr>';
            echo '<td>' . esc_html($product->get_name()) . '</td>';
            echo '<td>' . esc_html($category_name) . '</td>';
            echo '<td>' . esc_html($unspec_code) . '</td>';
            echo '<td>' . esc_html($server_id) . '</td>';
            echo '<td class="' . $status_class . '">' . esc_html($status_text) . '</td>';
            echo '</tr>';
        }
        
        echo '</table>';
        
        echo '<div class="sync-summary">';
        echo '<p><strong>Summary:</strong></p>';
        echo '<ul>';
        echo '<li>Total Products: ' . $total_products . '</li>';
        echo '<li>Ready for Sync: ' . $ready_products . '</li>';
        echo '<li>Needs Category: ' . $needs_category . '</li>';
        echo '<li>Needs Unspec Code: ' . $needs_unspec . '</li>';
        echo '<li>Needs SID: ' . $needs_sid . '</li>';
        echo '</ul>';
        echo '</div>';
    }
    
    /**
     * AJAX sync categories
     */
    public function ajax_sync_categories() {
        check_ajax_referer('kra_etims_sync', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $categories = get_terms(array(
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
        ));
        
        $synced_count = 0;
        $skipped_count = 0;
        $failed_count = 0;
        $errors = array();
        
        foreach ($categories as $category) {
            $unspec_code = get_term_meta($category->term_id, '_kra_etims_unspec_code', true);
            
            // Skip categories without unspec code
            if (empty($unspec_code)) {
                $skipped_count++;
                continue;
            }
            
            // Check if category already has a Server ID (SID)
            $existing_sid = get_term_meta($category->term_id, '_kra_etims_server_id', true);
            
            if (!empty($existing_sid)) {
                // Category already synced, skip it
                $skipped_count++;
                error_log("KRA eTims Sync: Skipping category '{$category->name}' - already has SID: {$existing_sid}");
                continue;
            }
            
            // Send category to API
            try {
                $result = $this->send_category_to_api($category, $unspec_code);
                
                if ($result['success']) {
                    $synced_count++;
                } else {
                    $failed_count++;
                    $error_msg = !empty($result['message']) ? $result['message'] : 'Unknown API error';
                    $errors[] = "'{$category->name}': {$error_msg}";
                }
            } catch (Exception $e) {
                $failed_count++;
                $errors[] = "'{$category->name}': " . $e->getMessage();
            }
            
            // Continue to next category regardless of success or failure
        }
        
        $total_categories = count($categories);
        
        // Build success message
        $message = "Sync completed: {$synced_count} successful";
        
        if ($skipped_count > 0) {
            $message .= ", {$skipped_count} skipped (no unspec code or already synced)";
        }
        
        if ($failed_count > 0) {
            $message .= ", {$failed_count} failed";
            if (!empty($errors)) {
                $message .= " - Failed categories: " . implode(' | ', array_slice($errors, 0, 5));
                if (count($errors) > 5) {
                    $message .= " (and " . (count($errors) - 5) . " more)";
                }
            }
        }
        
        // Always return success if at least one category synced, or if all were skipped
        if ($synced_count > 0 || $failed_count === 0) {
            wp_send_json_success($message);
        } else {
            // Only return error if ALL categories failed
            wp_send_json_error($message);
        }
    }
    
    /**
     * AJAX sync products
     */
    public function ajax_sync_products() {
        check_ajax_referer('kra_etims_sync', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $products = wc_get_products(array(
            'limit' => -1,
            'status' => 'publish',
        ));
        
        $synced_count = 0;
        $skipped_count = 0;
        $failed_count = 0;
        $errors = array();
        
        foreach ($products as $product) {
            $product_id = $product->get_id();
            $categories = get_the_terms($product_id, 'product_cat');
            
            if (!$categories || is_wp_error($categories)) {
                $skipped_count++;
                continue; // Skip and continue to next product
            }
            
            $primary_category = $categories[0];
            $unspec_code = get_term_meta($primary_category->term_id, '_kra_etims_unspec_code', true);
            $server_id = get_term_meta($primary_category->term_id, '_kra_etims_server_id', true);
            
            if (empty($unspec_code) || empty($server_id)) {
                $skipped_count++;
                continue; // Skip and continue to next product
            }
            
            // Send product to API
            try {
                $result = $this->send_product_to_api($product, $primary_category);
                
                if ($result['success']) {
                    $synced_count++;
                } else {
                    $failed_count++;
                    $error_msg = !empty($result['message']) ? $result['message'] : 'Unknown API error';
                    $errors[] = "'{$product->get_name()}': {$error_msg}";
                }
            } catch (Exception $e) {
                $failed_count++;
                $errors[] = "'{$product->get_name()}': " . $e->getMessage();
            }
            
            // Continue to next product regardless of success or failure
        }
        
        // Build success message
        $message = "Sync completed: {$synced_count} successful";
        
        if ($skipped_count > 0) {
            $message .= ", {$skipped_count} skipped (no category/SID)";
        }
        
        if ($failed_count > 0) {
            $message .= ", {$failed_count} failed";
            if (!empty($errors)) {
                $message .= " - Failed products: " . implode(' | ', array_slice($errors, 0, 5));
                if (count($errors) > 5) {
                    $message .= " (and " . (count($errors) - 5) . " more)";
                }
            }
        }
        
        // Always return success if at least one product synced, or if all were skipped
        if ($synced_count > 0 || $failed_count === 0) {
            wp_send_json_success($message);
        } else {
            // Only return error if ALL products failed
            wp_send_json_error($message);
        }
    }
    
    /**
     * Send category to API
     */
    private function send_category_to_api($category, $unspec_code) {
        $settings = get_option('kra_etims_wc_settings');
        
        // Get API base URL from settings (same as category handler)
        $api_base_url = isset($settings['api_base_url']) ? $settings['api_base_url'] : '';
        
        if (empty($api_base_url)) {
            return array('success' => false, 'message' => 'API Base URL not configured in settings');
        }
        
        // Get tin and bhfId from settings
        $tin = isset($settings['tin']) ? $settings['tin'] : '990888000';
        $bhfId = isset($settings['bhfId']) ? $settings['bhfId'] : '00';
        
        // Build API URL
        $api_url = rtrim($api_base_url, '/') . '/add_categories';
        
        // Prepare category data in the EXACT format as category handler (which works)
        $category_data = array(
            'catergory_name' => $category->name,  // Note: matches API spelling
            'description' => !empty($category->description) ? $category->description : '000',
            'price' => '0',  // Default price for categories
            'tin' => $tin,
            'bhfId' => $bhfId,
            'itemcode' => $unspec_code
        );
        
        // Log the request for debugging
        error_log('KRA eTims Sync: Sending category to API - ' . $category->name);
        error_log('KRA eTims Sync: API URL - ' . $api_url);
        error_log('KRA eTims Sync: Category data - ' . json_encode($category_data));
        
        // Make the API request
        $response = wp_remote_post($api_url, array(
            'method' => 'POST',
            'timeout' => 30,
            'redirection' => 5,
            'httpversion' => '1.1',
            'blocking' => true,
            'headers' => array(
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ),
            'body' => json_encode($category_data),
        ));
        
        // Check for request errors
        if (is_wp_error($response)) {
            $error_msg = $response->get_error_message();
            error_log('KRA eTims Sync: API request failed - ' . $error_msg);
            return array('success' => false, 'message' => 'API request failed: ' . $error_msg);
        }
        
        // Get response code and body
        $response_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        // Log the response for debugging
        error_log('KRA eTims Sync: API Response Code - ' . $response_code);
        error_log('KRA eTims Sync: API Response Body - ' . $body);
        
        // Check response code
        if ($response_code < 200 || $response_code >= 300) {
            $error_message = isset($data['message']) ? $data['message'] : 'HTTP ' . $response_code;
            error_log('KRA eTims Sync: API error - ' . $error_message);
            return array('success' => false, 'message' => $error_message);
        }
        
        // Check for success in response data
        if (isset($data['success']) && $data['success']) {
            // Save SID if provided
            if (isset($data['sid'])) {
                update_term_meta($category->term_id, '_kra_etims_server_id', $data['sid']);
                error_log('KRA eTims Sync: Category synced successfully with SID - ' . $data['sid']);
            } elseif (isset($data['id'])) {
                // Alternative field name
                update_term_meta($category->term_id, '_kra_etims_server_id', $data['id']);
                error_log('KRA eTims Sync: Category synced successfully with ID - ' . $data['id']);
            } else {
                error_log('KRA eTims Sync: Category synced successfully (no SID returned)');
            }
            return array('success' => true, 'message' => 'Category synced successfully');
        } else {
            // Extract error message from response
            $error_message = 'Unknown API error';
            if (isset($data['message'])) {
                $error_message = $data['message'];
            } elseif (isset($data['error'])) {
                $error_message = $data['error'];
            } elseif (!empty($body)) {
                $error_message = substr($body, 0, 200); // First 200 chars of response
            }
            
            error_log('KRA eTims Sync: API returned error - ' . $error_message);
            return array('success' => false, 'message' => $error_message);
        }
    }
    
    /**
     * Send product to API
     */
    private function send_product_to_api($product, $category) {
        $settings = get_option('kra_etims_wc_settings');
        $api_url = isset($settings['custom_api_live_url']) ? $settings['custom_api_live_url'] : '';
        
        if (empty($api_url)) {
            return array('success' => false, 'message' => 'API URL not configured');
        }
        
                    $unspec_code = get_term_meta($category->term_id, '_kra_etims_unspec_code', true);
        $server_id = get_term_meta($category->term_id, '_kra_etims_server_id', true);
        
        $product_data = array(
            'name' => $product->get_name(),
            'sku' => $product->get_sku(),
            'price' => $product->get_price(),
            'category_id' => $server_id,
            'unspec_code' => $unspec_code,
            'description' => $product->get_description(),
            'short_description' => $product->get_short_description(),
            'status' => $product->get_status()
        );
        
        $response = wp_remote_post($api_url . '/add_products', array(
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode($product_data),
            'timeout' => 30,
        ));
        
        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['success']) && $data['success']) {
            return array('success' => true, 'message' => 'Product synced successfully');
        } else {
            return array('success' => false, 'message' => isset($data['message']) ? $data['message'] : 'Unknown error');
        }
    }
    
    /**
     * AJAX clear category data
     */
    public function ajax_clear_category_data() {
        check_ajax_referer('kra_etims_sync', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        global $wpdb;
        
        // Log the table name being used for debugging
        $table_name = $wpdb->termmeta;
        error_log("KRA eTims: Clearing category SIDs from table: {$table_name}");
        
        // First check if there are any records to delete
        $count_query = $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->termmeta} WHERE meta_key = %s",
            '_kra_etims_server_id'
        );
        $count_before = $wpdb->get_var($count_query);
        
        error_log("KRA eTims: Found {$count_before} category SID records before deletion");
        
        // Delete all category SIDs using prepared statement for security
        $deleted = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->termmeta} WHERE meta_key = %s",
                '_kra_etims_server_id'
            )
        );
        
        // Check for database errors
        if ($wpdb->last_error) {
            error_log("KRA eTims: Database error during category clear: " . $wpdb->last_error);
            wp_send_json_error("Database error: " . $wpdb->last_error);
            return;
        }
        
        // Verify the deletion
        $count_after = $wpdb->get_var($count_query);
        error_log("KRA eTims: Found {$count_after} category SID records after deletion");
        
        if ($deleted !== false) {
            $message = "Successfully cleared {$deleted} category SID record(s)";
            if ($count_before > 0 && $deleted === 0) {
                $message .= " (Note: Records found but none deleted - check permissions)";
            }
            wp_send_json_success($message);
        } else {
            wp_send_json_error('Failed to clear category data - database query returned false');
        }
    }
    
    /**
     * AJAX clear product data
     */
    public function ajax_clear_product_data() {
        check_ajax_referer('kra_etims_sync', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        global $wpdb;
        
        // Log the table name being used for debugging
        $table_name = $wpdb->postmeta;
        error_log("KRA eTims: Clearing product KRA data from table: {$table_name}");
        
        $total_deleted = 0;
        $details = array();
        
        // Clear Product Injonge Codes
        $deleted = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->postmeta} WHERE meta_key = %s",
                '_injonge_code'
            )
        );
        if ($deleted !== false) {
            $total_deleted += $deleted;
            $details[] = "injonge_codes: {$deleted}";
            error_log("KRA eTims: Cleared {$deleted} injonge codes");
        }
        
        // Clear Product SIDs
        $deleted = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->postmeta} WHERE meta_key = %s",
                '_injonge_sid'
            )
        );
        if ($deleted !== false) {
            $total_deleted += $deleted;
            $details[] = "product_sids: {$deleted}";
            error_log("KRA eTims: Cleared {$deleted} product SIDs");
        }
        
        // Clear Product Category SIDs
        $deleted = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->postmeta} WHERE meta_key = %s",
                '_injonge_category_sid'
            )
        );
        if ($deleted !== false) {
            $total_deleted += $deleted;
            $details[] = "category_sids: {$deleted}";
            error_log("KRA eTims: Cleared {$deleted} product category SIDs");
        }
        
        // Clear Product Sync Status
        $deleted = $wpdb->query(
            "DELETE FROM {$wpdb->postmeta} WHERE meta_key IN ('_injonge_status', '_injonge_last_sync', '_injonge_response', '_injonge_error')"
        );
        if ($deleted !== false) {
            $total_deleted += $deleted;
            $details[] = "sync_status: {$deleted}";
            error_log("KRA eTims: Cleared {$deleted} sync status records");
        }
        
        // Clear API Notes
        $deleted = $wpdb->query(
            "DELETE FROM {$wpdb->postmeta} WHERE meta_key IN ('_api_note', '_api_error_note')"
        );
        if ($deleted !== false) {
            $total_deleted += $deleted;
            $details[] = "api_notes: {$deleted}";
            error_log("KRA eTims: Cleared {$deleted} API note records");
        }
        
        // Check for database errors
        if ($wpdb->last_error) {
            error_log("KRA eTims: Database error during product clear: " . $wpdb->last_error);
            wp_send_json_error("Database error: " . $wpdb->last_error);
            return;
        }
        
        if ($total_deleted !== false && $total_deleted >= 0) {
            $message = "Successfully cleared {$total_deleted} product KRA data record(s)";
            if (!empty($details)) {
                $message .= " (" . implode(', ', $details) . ")";
            }
            error_log("KRA eTims: Product clear completed - Total deleted: {$total_deleted}");
            wp_send_json_success($message);
        } else {
            wp_send_json_error('Failed to clear product data - database query returned false');
        }
    }
} 