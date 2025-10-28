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
        add_action('wp_ajax_kra_etims_resync_categories', array($this, 'ajax_resync_categories'));
        add_action('wp_ajax_kra_etims_sync_products', array($this, 'ajax_sync_products'));
        add_action('wp_ajax_kra_etims_get_sync_status', array($this, 'ajax_get_sync_status'));
        add_action('wp_ajax_kra_etims_clear_category_data', array($this, 'ajax_clear_category_data'));
        add_action('wp_ajax_kra_etims_clear_product_data', array($this, 'ajax_clear_product_data'));
        add_action('wp_ajax_kra_etims_force_clear_all', array($this, 'ajax_force_clear_all'));
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
        global $wpdb;
        $db_prefix = $wpdb->prefix;
        ?>
        <div class="wrap">
            <h1><?php _e('KRA eTims - Sync Categories & Products', 'kra-etims-integration'); ?></h1>
            
            <!-- Database Prefix Info -->
            <div class="kra-etims-db-info" style="background: #f0f6fc; border-left: 4px solid #0073aa; padding: 10px 15px; margin: 20px 0; border-radius: 4px;">
                <p style="margin: 0;">
                    <strong><?php _e('Database Prefix:', 'kra-etims-integration'); ?></strong> 
                    <code style="background: #fff; padding: 2px 6px; border-radius: 3px; font-weight: bold;"><?php echo esc_html($db_prefix); ?></code>
                    <span style="color: #666; font-size: 0.9em;"><?php _e('(All operations are compatible with any database prefix)', 'kra-etims-integration'); ?></span>
                </p>
            </div>
            
            <!-- Force Clear All Section -->
            <div class="kra-etims-force-clear-section" style="background: #fff; border-left: 4px solid #dc3545; padding: 15px; margin: 20px 0; border-radius: 4px;">
                <h3 style="margin-top: 0; color: #dc3545;">⚠️ Force Clear All KRA Data</h3>
                <p>Use this to forcefully clear ALL KRA eTims data (categories SIDs, product codes, sync status). This is useful when regular clear buttons are not working.</p>
                <button id="force-clear-all-btn" class="button button-danger" style="background: #dc3545; color: white; border-color: #dc3545;">
                    <?php _e('🗑️ Force Clear All KRA Data', 'kra-etims-integration'); ?>
                </button>
                <div id="force-clear-progress" style="display: none; margin-top: 10px;">
                    <div class="progress-bar">
                        <div class="progress-fill"></div>
                    </div>
                    <p id="force-clear-status"></p>
                </div>
            </div>
            
            <div class="kra-etims-sync-container">
                <!-- Categories Section -->
                <div class="kra-etims-sync-section">
                    <h2><?php _e('Categories Synchronization', 'kra-etims-integration'); ?></h2>
                    <p><?php _e('Upload categories to your API and get SID (Server ID) for each category.', 'kra-etims-integration'); ?></p>
                    
                    <div class="kra-etims-category-status">
                        <?php $this->display_category_status(); ?>
                    </div>
                    
                    <button id="sync-categories-btn" class="button button-primary">
                        <?php _e('Sync New Categories', 'kra-etims-integration'); ?>
                    </button>
                    <button id="resync-categories-btn" class="button button-primary" style="margin-left: 10px;">
                        <?php _e('Force Resync All', 'kra-etims-integration'); ?>
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
            // Force Clear All KRA Data
            $('#force-clear-all-btn').on('click', function() {
                if (!confirm('⚠️ WARNING: This will forcefully delete ALL KRA eTims data:\n\n• All category Server IDs (SID)\n• All product injonge codes\n• All product SIDs\n• All sync status records\n• All API notes\n\nThis action CANNOT be undone!\n\nAre you absolutely sure?')) {
                    return;
                }
                
                var $btn = $(this);
                var $progress = $('#force-clear-progress');
                var $status = $('#force-clear-status');
                
                $btn.prop('disabled', true);
                $progress.show();
                $status.text('Forcefully clearing all KRA data...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'kra_etims_force_clear_all',
                        nonce: '<?php echo wp_create_nonce('kra_etims_sync'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $status.html('<strong style="color: green;">✅ ' + response.data + '</strong>');
                            $('.progress-fill').css('width', '100%');
                            setTimeout(function() {
                                location.reload();
                            }, 3000);
                        } else {
                            $status.html('<strong style="color: red;">❌ Error: ' + response.data + '</strong>');
                        }
                    },
                    error: function() {
                        $status.html('<strong style="color: red;">❌ Network error occurred</strong>');
                    },
                    complete: function() {
                        $btn.prop('disabled', false);
                    }
                });
            });
            
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
            
            // Force Resync All Categories
            $('#resync-categories-btn').on('click', function() {
                if (!confirm('This will resync ALL categories (including those already synced). Continue?')) {
                    return;
                }
                
                var $btn = $(this);
                var $progress = $('#category-sync-progress');
                var $status = $('#category-sync-status');
                
                $btn.prop('disabled', true);
                $progress.show();
                $status.text('Force resyncing all categories...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'kra_etims_resync_categories',
                        nonce: '<?php echo wp_create_nonce('kra_etims_sync'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $status.text('Categories resynced successfully!');
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
     * AJAX resync categories (force sync all, including those with SID)
     */
    public function ajax_resync_categories() {
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
            
            // Get existing SID for logging
            $old_sid = get_term_meta($category->term_id, '_kra_etims_server_id', true);
            
            // Clear the existing SID to force a fresh sync
            if (!empty($old_sid)) {
                delete_term_meta($category->term_id, '_kra_etims_server_id');
                error_log("KRA eTims Resync: Cleared old SID ({$old_sid}) for category '{$category->name}'");
            }
            
            error_log("KRA eTims Resync: Force syncing category '{$category->name}'");
            
            // Send category to API
            try {
                $result = $this->send_category_to_api($category, $unspec_code);
                
                if ($result['success']) {
                    $synced_count++;
                    // Get the new SID for verification
                    $new_sid = get_term_meta($category->term_id, '_kra_etims_server_id', true);
                    if ($new_sid) {
                        error_log("KRA eTims Resync: Category '{$category->name}' updated with new SID: {$new_sid} (old: {$old_sid})");
                    } else {
                        error_log("KRA eTims Resync: WARNING - Category '{$category->name}' synced but no SID returned by API");
                    }
                } else {
                    $failed_count++;
                    // Restore old SID if sync failed
                    if (!empty($old_sid)) {
                        update_term_meta($category->term_id, '_kra_etims_server_id', $old_sid);
                        error_log("KRA eTims Resync: Restored old SID for '{$category->name}' after failed sync");
                    }
                    $error_msg = !empty($result['message']) ? $result['message'] : 'Unknown API error';
                    $errors[] = "'{$category->name}': {$error_msg}";
                }
            } catch (Exception $e) {
                $failed_count++;
                // Restore old SID if exception occurred
                if (!empty($old_sid)) {
                    update_term_meta($category->term_id, '_kra_etims_server_id', $old_sid);
                }
                $errors[] = "'{$category->name}': " . $e->getMessage();
            }
            
            // Continue to next category regardless of success or failure
        }
        
        $total_categories = count($categories);
        
        // Build success message
        $message = "Force resync completed: {$synced_count} successful";
        
        if ($skipped_count > 0) {
            $message .= ", {$skipped_count} skipped (no unspec code)";
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
            
            // Send product to API using product handler (which uses /update_items endpoint)
            try {
                // Ensure product has required meta before syncing
                update_post_meta($product_id, '_injonge_unspec', $unspec_code);
                update_post_meta($product_id, '_injonge_category_sid', $server_id);
                
                // Use product handler to sync (uses /update_items endpoint and saves injonge code)
                $product_handler = new KRA_eTims_WC_Product_Handler();
                $result = $product_handler->send_product_to_api($product, 'create');
                
                if ($result['success']) {
                    $synced_count++;
                    // Log success with injonge code if available
                    if (isset($result['injonge_code'])) {
                        error_log("KRA eTims Sync: Product '{$product->get_name()}' synced successfully with Injonge Code: {$result['injonge_code']}");
                    }
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
     * Send product to API (DEPRECATED - Now uses Product Handler)
     * Kept for backward compatibility but should use Product Handler instead
     * 
     * @deprecated Use KRA_eTims_WC_Product_Handler::send_product_to_api() instead
     */
    private function send_product_to_api($product, $category) {
        // This method is deprecated - use product handler instead
        // Keeping for backward compatibility but redirecting to product handler
        $product_handler = new KRA_eTims_WC_Product_Handler();
        return $product_handler->send_product_to_api($product, 'create');
    }
    
    /**
     * AJAX clear category data
     * Now properly handles custom database prefixes (e.g., wptl_, wplt_)
     */
    public function ajax_clear_category_data() {
        check_ajax_referer('kra_etims_sync', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        global $wpdb;
        
        // Get the actual table name with proper prefix
        $table_name = $wpdb->prefix . 'termmeta';
        error_log("KRA eTims: Clearing category SIDs from table: {$table_name}");
        
        // First check if there are any records to delete
        $count_before = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM `{$table_name}` WHERE meta_key = %s",
            '_kra_etims_server_id'
        ));
        
        error_log("KRA eTims: Found {$count_before} category SID records before deletion");
        
        // Delete all category SIDs using prepared statement for security with backticks for table name
        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM `{$table_name}` WHERE meta_key = %s",
            '_kra_etims_server_id'
        ));
        
        // Check for database errors
        if ($wpdb->last_error) {
            error_log("KRA eTims: Database error during category clear: " . $wpdb->last_error);
            wp_send_json_error("Database error: " . $wpdb->last_error . " (Prefix: {$wpdb->prefix})");
            return;
        }
        
        // Verify the deletion
        $count_after = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM `{$table_name}` WHERE meta_key = %s",
            '_kra_etims_server_id'
        ));
        error_log("KRA eTims: Found {$count_after} category SID records after deletion");
        
        // Clear WordPress object cache
        wp_cache_flush();
        
        if ($deleted !== false) {
            $message = "Successfully cleared {$deleted} category SID record(s)";
            if ($count_before > 0 && $deleted === 0) {
                $message .= " (Note: Records found but none deleted - check database permissions)";
            }
            $message .= " [Prefix: {$wpdb->prefix}]";
            wp_send_json_success($message);
        } else {
            wp_send_json_error('Failed to clear category data - database query returned false');
        }
    }
    
    /**
     * AJAX clear product data
     * Now properly handles custom database prefixes (e.g., wptl_, wplt_)
     */
    public function ajax_clear_product_data() {
        check_ajax_referer('kra_etims_sync', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        global $wpdb;
        
        // Get the actual table name with proper prefix
        $table_name = $wpdb->prefix . 'postmeta';
        error_log("KRA eTims: Clearing product KRA data from table: {$table_name}");
        
        $total_deleted = 0;
        $details = array();
        
        // Clear Product Injonge Codes
        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM `{$table_name}` WHERE meta_key = %s",
            '_injonge_code'
        ));
        if ($deleted !== false) {
            $total_deleted += $deleted;
            $details[] = "injonge_codes: {$deleted}";
            error_log("KRA eTims: Cleared {$deleted} injonge codes");
        }
        
        // Clear Product SIDs
        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM `{$table_name}` WHERE meta_key = %s",
            '_injonge_sid'
        ));
        if ($deleted !== false) {
            $total_deleted += $deleted;
            $details[] = "product_sids: {$deleted}";
            error_log("KRA eTims: Cleared {$deleted} product SIDs");
        }
        
        // Clear Product Category SIDs
        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM `{$table_name}` WHERE meta_key = %s",
            '_injonge_category_sid'
        ));
        if ($deleted !== false) {
            $total_deleted += $deleted;
            $details[] = "category_sids: {$deleted}";
            error_log("KRA eTims: Cleared {$deleted} product category SIDs");
        }
        
        // Clear Product Sync Status - using backticks for table name
        $deleted = $wpdb->query(
            "DELETE FROM `{$table_name}` WHERE meta_key IN ('_injonge_status', '_injonge_last_sync', '_injonge_response', '_injonge_error')"
        );
        if ($deleted !== false) {
            $total_deleted += $deleted;
            $details[] = "sync_status: {$deleted}";
            error_log("KRA eTims: Cleared {$deleted} sync status records");
        }
        
        // Clear API Notes - using backticks for table name
        $deleted = $wpdb->query(
            "DELETE FROM `{$table_name}` WHERE meta_key IN ('_api_note', '_api_error_note')"
        );
        if ($deleted !== false) {
            $total_deleted += $deleted;
            $details[] = "api_notes: {$deleted}";
            error_log("KRA eTims: Cleared {$deleted} API note records");
        }
        
        // Check for database errors
        if ($wpdb->last_error) {
            error_log("KRA eTims: Database error during product clear: " . $wpdb->last_error);
            wp_send_json_error("Database error: " . $wpdb->last_error . " (Prefix: {$wpdb->prefix})");
            return;
        }
        
        // Clear WordPress object cache
        wp_cache_flush();
        
        if ($total_deleted !== false && $total_deleted >= 0) {
            $message = "Successfully cleared {$total_deleted} product KRA data record(s)";
            if (!empty($details)) {
                $message .= " (" . implode(', ', $details) . ")";
            }
            $message .= " [Prefix: {$wpdb->prefix}]";
            error_log("KRA eTims: Product clear completed - Total deleted: {$total_deleted}");
            wp_send_json_success($message);
        } else {
            wp_send_json_error('Failed to clear product data - database query returned false');
        }
    }
    
    /**
     * AJAX force clear all KRA data (both categories and products)
     * Uses aggressive clearing methods when regular clear fails
     * Now properly handles custom database prefixes (e.g., wptl_, wplt_)
     */
    public function ajax_force_clear_all() {
        check_ajax_referer('kra_etims_sync', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        global $wpdb;
        
        // Get the actual table names with proper prefix
        $prefix = $wpdb->prefix;
        $termmeta_table = $wpdb->prefix . 'termmeta';
        $postmeta_table = $wpdb->prefix . 'postmeta';
        
        error_log("KRA eTims: Force Clear All - Starting with custom prefix support");
        error_log("KRA eTims: Database prefix: {$prefix}");
        error_log("KRA eTims: Termmeta table: {$termmeta_table}");
        error_log("KRA eTims: Postmeta table: {$postmeta_table}");
        
        $total_deleted = 0;
        $operations = array();
        $errors = array();
        
        // Add database prefix to response for user verification
        $operations[] = "Database prefix: {$prefix}";
        
        // Verify tables exist before proceeding - use prepared statement for safety
        $termmeta_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $termmeta_table));
        $postmeta_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $postmeta_table));
        
        if (!$termmeta_exists) {
            error_log("KRA eTims: ERROR - Table {$termmeta_table} does not exist!");
            wp_send_json_error("Database error: Table {$termmeta_table} not found. Prefix: {$prefix}");
            return;
        }
        
        if (!$postmeta_exists) {
            error_log("KRA eTims: ERROR - Table {$postmeta_table} does not exist!");
            wp_send_json_error("Database error: Table {$postmeta_table} not found. Prefix: {$prefix}");
            return;
        }
        
        error_log("KRA eTims: Table verification passed - both tables exist");
        
        // Disable foreign key checks temporarily (if supported)
        $wpdb->query("SET FOREIGN_KEY_CHECKS = 0");
        
        try {
            // === CATEGORY DATA CLEARING ===
            error_log("KRA eTims: Force clearing category SIDs from {$termmeta_table}");
            
            // Count before deletion for verification
            $count_before = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM `{$termmeta_table}` WHERE meta_key = %s",
                '_kra_etims_server_id'
            ));
            error_log("KRA eTims: Found {$count_before} category SID records to clear");
            
            // Method 1: Delete primary meta key with backticks for table name
            $deleted = $wpdb->query($wpdb->prepare(
                "DELETE FROM `{$termmeta_table}` WHERE meta_key = %s",
                '_kra_etims_server_id'
            ));
            
            if ($deleted !== false) {
                $total_deleted += $deleted;
                $operations[] = "Category SIDs: {$deleted}";
                error_log("KRA eTims: Cleared {$deleted} category SIDs");
            } else {
                error_log("KRA eTims: ERROR clearing category SIDs: " . $wpdb->last_error);
                $errors[] = "Category SID deletion failed: " . $wpdb->last_error;
            }
            
            // Method 2: Clear unspec codes
            $deleted = $wpdb->query($wpdb->prepare(
                "DELETE FROM `{$termmeta_table}` WHERE meta_key = %s",
                '_kra_etims_unspec_code'
            ));
            
            if ($deleted !== false && $deleted > 0) {
                $total_deleted += $deleted;
                $operations[] = "Category unspec codes: {$deleted}";
                error_log("KRA eTims: Cleared {$deleted} unspec codes");
            }
            
            // Method 3: Clear any other KRA-related term meta using LIKE with prepared statement
            $deleted = $wpdb->query(
                "DELETE FROM `{$termmeta_table}` WHERE meta_key LIKE '_kra_etims_%'"
            );
            
            if ($deleted !== false && $deleted > 0) {
                $total_deleted += $deleted;
                $operations[] = "Other category meta: {$deleted}";
                error_log("KRA eTims: Cleared {$deleted} additional category meta");
            }
            
            // === PRODUCT DATA CLEARING ===
            error_log("KRA eTims: Force clearing product KRA data from {$postmeta_table}");
            
            // Count product records before deletion
            $count_before = $wpdb->get_var(
                "SELECT COUNT(*) FROM `{$postmeta_table}` WHERE meta_key LIKE '_injonge_%' OR meta_key LIKE '_api_%note' OR meta_key LIKE '_kra_etims_%'"
            );
            error_log("KRA eTims: Found {$count_before} product KRA records to clear");
            
            // Clear all KRA-related product meta keys using explicit list
            $meta_keys = array(
                '_injonge_code',
                '_injonge_sid',
                '_injonge_category_sid',
                '_injonge_status',
                '_injonge_last_sync',
                '_injonge_response',
                '_injonge_error',
                '_api_note',
                '_api_error_note',
                '_kra_etims_server_id',
                '_kra_etims_synced'
            );
            
            foreach ($meta_keys as $meta_key) {
                $deleted = $wpdb->query($wpdb->prepare(
                    "DELETE FROM `{$postmeta_table}` WHERE meta_key = %s",
                    $meta_key
                ));
                
                if ($deleted !== false && $deleted > 0) {
                    $total_deleted += $deleted;
                    $operations[] = "{$meta_key}: {$deleted}";
                    error_log("KRA eTims: Cleared {$deleted} records for {$meta_key}");
                }
            }
            
            // Aggressive: Clear any remaining meta with LIKE patterns
            $deleted = $wpdb->query(
                "DELETE FROM `{$postmeta_table}` WHERE 
                meta_key LIKE '_injonge_%' OR 
                meta_key LIKE '_kra_etims_%' OR 
                meta_key LIKE '_api_%note'"
            );
            
            if ($deleted !== false && $deleted > 0) {
                $total_deleted += $deleted;
                $operations[] = "Additional product meta: {$deleted}";
                error_log("KRA eTims: Cleared {$deleted} additional product meta");
            }
            
            // Check for database errors
            if ($wpdb->last_error) {
                $errors[] = $wpdb->last_error;
                error_log("KRA eTims: Database error during force clear: " . $wpdb->last_error);
            }
            
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
            error_log("KRA eTims: Exception during force clear: " . $e->getMessage());
        }
        
        // Re-enable foreign key checks
        $wpdb->query("SET FOREIGN_KEY_CHECKS = 1");
        
        // Verify clearing worked by counting remaining records
        $category_count = $wpdb->get_var(
            "SELECT COUNT(*) FROM `{$termmeta_table}` WHERE meta_key LIKE '_kra_etims_%'"
        );
        
        $product_count = $wpdb->get_var(
            "SELECT COUNT(*) FROM `{$postmeta_table}` WHERE 
            meta_key LIKE '_injonge_%' OR 
            meta_key LIKE '_kra_etims_%' OR 
            meta_key LIKE '_api_%note'"
        );
        
        error_log("KRA eTims: Force clear completed - Total deleted: {$total_deleted}");
        error_log("KRA eTims: Remaining category meta: {$category_count}, product meta: {$product_count}");
        
        // Clear WordPress object cache to ensure changes are reflected
        wp_cache_flush();
        
        // Build response message
        if ($total_deleted > 0 || (!empty($operations) && count($operations) > 1)) {
            $message = "Force clear completed! Deleted {$total_deleted} total records<br>";
            $message .= "<small>Using prefix: {$prefix} | Tables: {$termmeta_table}, {$postmeta_table}</small>";
            
            if (!empty($operations)) {
                $message .= "<br><br><strong>Details:</strong><br>" . implode('<br>', $operations);
            }
            
            if ($category_count > 0 || $product_count > 0) {
                $message .= "<br><br><strong>⚠️ Note:</strong> Found {$category_count} category and {$product_count} product meta records still remaining.";
                if ($category_count > 0 || $product_count > 0) {
                    $message .= " Some records may be persistent. Try refreshing the page.";
                }
            } else {
                $message .= "<br><br><strong>✅ Success:</strong> All KRA data has been cleared successfully!";
            }
            
            if (!empty($errors)) {
                $message .= "<br><br><strong>⚠️ Errors:</strong><br>" . implode('<br>', $errors);
            }
            
            wp_send_json_success($message);
        } else {
            $message = "No KRA data found to clear (or already cleared)";
            $message .= "<br><small>Prefix: {$prefix}</small>";
            
            if (!empty($errors)) {
                $message .= "<br><br><strong>Errors:</strong><br>" . implode('<br>', $errors);
                wp_send_json_error($message);
            } else {
                // If no errors and no data, consider it success (already clean)
                wp_send_json_success($message);
            }
        }
    }
} 