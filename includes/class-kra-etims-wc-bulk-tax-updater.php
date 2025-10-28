<?php
/**
 * Bulk Tax Updater Class
 * Provides GUI for bulk updating KRA tax types on products
 *
 * @package KRA_eTims_WC
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Bulk Tax Updater Class
 */
class KRA_eTims_WC_Bulk_Tax_Updater {
    /**
     * Instance
     *
     * @var KRA_eTims_WC_Bulk_Tax_Updater
     */
    private static $instance = null;

    /**
     * Get instance
     *
     * @return KRA_eTims_WC_Bulk_Tax_Updater
     */
    public static function get_instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    public function __construct() {
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Handle AJAX requests
        add_action('wp_ajax_kra_etims_preview_bulk_update', array($this, 'ajax_preview_bulk_update'));
        add_action('wp_ajax_kra_etims_execute_bulk_update', array($this, 'ajax_execute_bulk_update'));
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'kra-etims-wc',
            __('Bulk Tax Update', 'kra-etims-integration'),
            __('Bulk Tax Update', 'kra-etims-integration'),
            'manage_woocommerce',
            'kra-etims-bulk-tax-update',
            array($this, 'render_bulk_update_page')
        );
    }

    /**
     * Render bulk update page
     */
    public function render_bulk_update_page() {
        // Get all product categories
        $categories = get_terms(array(
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
        ));

        // Get statistics
        $stats = $this->get_tax_statistics();
        ?>
        <div class="wrap">
            <h1><?php _e('KRA Tax Bulk Update Tool', 'kra-etims-integration'); ?></h1>
            
            <?php if (isset($_GET['updated']) && $_GET['updated'] > 0): ?>
                <div class="notice notice-success is-dismissible">
                    <p><strong><?php printf(__('Successfully updated %d products!', 'kra-etims-integration'), intval($_GET['updated'])); ?></strong></p>
                </div>
            <?php endif; ?>

            <!-- Statistics Section -->
            <div class="kra-etims-stats-section" style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ccd0d4; border-radius: 4px;">
                <h2 style="margin-top: 0;"><?php _e('Current Tax Status', 'kra-etims-integration'); ?></h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                    <div style="background: #f0f6fc; padding: 15px; border-radius: 4px;">
                        <strong style="color: #0073aa; font-size: 24px;"><?php echo $stats['total_products']; ?></strong>
                        <p style="margin: 5px 0 0 0;"><?php _e('Total Products', 'kra-etims-integration'); ?></p>
                    </div>
                    <div style="background: #e8f5e9; padding: 15px; border-radius: 4px;">
                        <strong style="color: #46b450; font-size: 24px;"><?php echo $stats['tax_b']; ?></strong>
                        <p style="margin: 5px 0 0 0;"><?php _e('Tax B (VAT 16%)', 'kra-etims-integration'); ?></p>
                    </div>
                    <div style="background: #fff3e0; padding: 15px; border-radius: 4px;">
                        <strong style="color: #dba617; font-size: 24px;"><?php echo $stats['tax_a']; ?></strong>
                        <p style="margin: 5px 0 0 0;"><?php _e('Tax A (Exempt)', 'kra-etims-integration'); ?></p>
                    </div>
                    <div style="background: #fce4ec; padding: 15px; border-radius: 4px;">
                        <strong style="color: #826eb4; font-size: 24px;"><?php echo $stats['tax_c']; ?></strong>
                        <p style="margin: 5px 0 0 0;"><?php _e('Tax C (Export)', 'kra-etims-integration'); ?></p>
                    </div>
                    <div style="background: #ffebee; padding: 15px; border-radius: 4px;">
                        <strong style="color: #d63638; font-size: 24px;"><?php echo $stats['tax_d']; ?></strong>
                        <p style="margin: 5px 0 0 0;"><?php _e('Tax D (Non-VAT)', 'kra-etims-integration'); ?></p>
                    </div>
                    <div style="background: #f5f5f5; padding: 15px; border-radius: 4px;">
                        <strong style="color: #999; font-size: 24px;"><?php echo $stats['not_set']; ?></strong>
                        <p style="margin: 5px 0 0 0;"><?php _e('Not Set', 'kra-etims-integration'); ?></p>
                    </div>
                </div>
            </div>

            <!-- Bulk Update Form -->
            <div class="kra-etims-bulk-update-section" style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ccd0d4; border-radius: 4px;">
                <h2 style="margin-top: 0;"><?php _e('Bulk Update Options', 'kra-etims-integration'); ?></h2>
                
                <form id="kra-etims-bulk-update-form">
                    <?php wp_nonce_field('kra_etims_bulk_update', 'kra_etims_bulk_update_nonce'); ?>
                    
                    <!-- Update Method -->
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="update_method"><?php _e('Update Method', 'kra-etims-integration'); ?></label>
                            </th>
                            <td>
                                <select id="update_method" name="update_method" style="width: 100%; max-width: 400px;">
                                    <option value="all"><?php _e('All Products', 'kra-etims-integration'); ?></option>
                                    <option value="category"><?php _e('By Category', 'kra-etims-integration'); ?></option>
                                    <option value="no_tax_type"><?php _e('Products Without Tax Type', 'kra-etims-integration'); ?></option>
                                    <option value="specific_tax_type"><?php _e('Products with Specific Tax Type', 'kra-etims-integration'); ?></option>
                                    <option value="specific_ids"><?php _e('Specific Product IDs', 'kra-etims-integration'); ?></option>
                                </select>
                                <p class="description"><?php _e('Choose which products to update', 'kra-etims-integration'); ?></p>
                            </td>
                        </tr>

                        <!-- Category Selection (shown when category method selected) -->
                        <tr id="category_selection" style="display: none;">
                            <th scope="row">
                                <label for="category_ids"><?php _e('Select Categories', 'kra-etims-integration'); ?></label>
                            </th>
                            <td>
                                <select id="category_ids" name="category_ids[]" multiple style="width: 100%; max-width: 400px; height: 150px;">
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo esc_attr($category->term_id); ?>">
                                            <?php echo esc_html($category->name); ?> (<?php echo $category->count; ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description"><?php _e('Hold Ctrl/Cmd to select multiple categories', 'kra-etims-integration'); ?></p>
                            </td>
                        </tr>

                        <!-- Current Tax Type Selection -->
                        <tr id="current_tax_type_selection" style="display: none;">
                            <th scope="row">
                                <label for="current_tax_type"><?php _e('Current Tax Type', 'kra-etims-integration'); ?></label>
                            </th>
                            <td>
                                <select id="current_tax_type" name="current_tax_type" style="width: 100%; max-width: 400px;">
                                    <option value="A"><?php _e('A - Exempt (0%)', 'kra-etims-integration'); ?></option>
                                    <option value="B" selected><?php _e('B - VAT 16%', 'kra-etims-integration'); ?></option>
                                    <option value="C"><?php _e('C - Export (0%)', 'kra-etims-integration'); ?></option>
                                    <option value="D"><?php _e('D - Non-VAT (0%)', 'kra-etims-integration'); ?></option>
                                    <option value="none"><?php _e('Not Set', 'kra-etims-integration'); ?></option>
                                </select>
                                <p class="description"><?php _e('Select products that currently have this tax type', 'kra-etims-integration'); ?></p>
                            </td>
                        </tr>

                        <!-- Product IDs Selection -->
                        <tr id="product_ids_selection" style="display: none;">
                            <th scope="row">
                                <label for="product_ids"><?php _e('Product IDs', 'kra-etims-integration'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="product_ids" name="product_ids" style="width: 100%; max-width: 400px;" 
                                       placeholder="<?php _e('e.g., 123, 456, 789', 'kra-etims-integration'); ?>">
                                <p class="description"><?php _e('Enter product IDs separated by commas', 'kra-etims-integration'); ?></p>
                            </td>
                        </tr>

                        <!-- New Tax Type -->
                        <tr>
                            <th scope="row">
                                <label for="new_tax_type"><?php _e('New Tax Type', 'kra-etims-integration'); ?></label>
                            </th>
                            <td>
                                <select id="new_tax_type" name="new_tax_type" required style="width: 100%; max-width: 400px;">
                                    <option value=""><?php _e('-- Select Tax Type --', 'kra-etims-integration'); ?></option>
                                    <option value="B"><?php _e('B - VAT 16%', 'kra-etims-integration'); ?></option>
                                    <option value="A"><?php _e('A - Exempt (0%)', 'kra-etims-integration'); ?></option>
                                    <option value="C"><?php _e('C - Export (0%)', 'kra-etims-integration'); ?></option>
                                    <option value="D"><?php _e('D - Non-VAT (0%)', 'kra-etims-integration'); ?></option>
                                </select>
                                <p class="description"><?php _e('Select the tax type to apply to selected products', 'kra-etims-integration'); ?></p>
                            </td>
                        </tr>

                        <!-- Also Update Tax Class -->
                        <tr>
                            <th scope="row">
                                <label for="update_tax_class"><?php _e('Also Update Tax Class', 'kra-etims-integration'); ?></label>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" id="update_tax_class" name="update_tax_class" value="1" checked>
                                    <?php _e('Automatically sync WooCommerce tax class with KRA tax type', 'kra-etims-integration'); ?>
                                </label>
                                <p class="description"><?php _e('If checked, the WooCommerce tax class will be updated to match the KRA tax type', 'kra-etims-integration'); ?></p>
                            </td>
                        </tr>
                    </table>

                    <!-- Preview Section -->
                    <div id="preview-section" style="display: none; margin: 20px 0; padding: 15px; background: #f9f9f9; border-radius: 4px;">
                        <h3><?php _e('Preview', 'kra-etims-integration'); ?></h3>
                        <div id="preview-content"></div>
                        <p id="preview-loading" style="display: none;">
                            <span class="spinner is-active" style="float: none; margin: 0 10px 0 0;"></span>
                            <?php _e('Loading preview...', 'kra-etims-integration'); ?>
                        </p>
                    </div>

                    <!-- Action Buttons -->
                    <p class="submit">
                        <button type="button" id="preview-btn" class="button button-secondary">
                            <?php _e('Preview Affected Products', 'kra-etims-integration'); ?>
                        </button>
                        <button type="button" id="execute-btn" class="button button-primary" style="display: none;">
                            <?php _e('Execute Bulk Update', 'kra-etims-integration'); ?>
                        </button>
                    </p>

                    <!-- Progress Bar -->
                    <div id="progress-section" style="display: none; margin: 20px 0;">
                        <div style="background: #f0f0f0; border-radius: 10px; height: 30px; overflow: hidden;">
                            <div id="progress-bar" style="background: #0073aa; height: 100%; width: 0%; transition: width 0.3s; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
                                0%
                            </div>
                        </div>
                        <p id="progress-text" style="margin: 10px 0; text-align: center;"></p>
                    </div>

                    <!-- Results Section -->
                    <div id="results-section" style="display: none; margin: 20px 0; padding: 15px; background: #e8f5e9; border: 1px solid #46b450; border-radius: 4px;">
                        <h3 style="color: #46b450; margin-top: 0;"><?php _e('Update Completed!', 'kra-etims-integration'); ?></h3>
                        <div id="results-content"></div>
                    </div>
                </form>
            </div>

            <!-- Help Section -->
            <div class="kra-etims-help-section" style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ccd0d4; border-radius: 4px;">
                <h2><?php _e('Help & Information', 'kra-etims-integration'); ?></h2>
                <ul>
                    <li><strong><?php _e('All Products:', 'kra-etims-integration'); ?></strong> <?php _e('Updates every published product in your store', 'kra-etims-integration'); ?></li>
                    <li><strong><?php _e('By Category:', 'kra-etims-integration'); ?></strong> <?php _e('Updates only products in selected categories', 'kra-etims-integration'); ?></li>
                    <li><strong><?php _e('Products Without Tax Type:', 'kra-etims-integration'); ?></strong> <?php _e('Updates only products that don\'t have a KRA tax type set', 'kra-etims-integration'); ?></li>
                    <li><strong><?php _e('Products with Specific Tax Type:', 'kra-etims-integration'); ?></strong> <?php _e('Updates only products that currently have the selected tax type', 'kra-etims-integration'); ?></li>
                    <li><strong><?php _e('Specific Product IDs:', 'kra-etims-integration'); ?></strong> <?php _e('Updates only the products with the specified IDs', 'kra-etims-integration'); ?></li>
                </ul>
                <p><strong><?php _e('⚠️ Important:', 'kra-etims-integration'); ?></strong> <?php _e('Always preview before executing. Consider backing up your database for large updates.', 'kra-etims-integration'); ?></p>
            </div>
        </div>

        <style>
            .kra-etims-product-preview {
                max-height: 400px;
                overflow-y: auto;
                border: 1px solid #ddd;
                padding: 10px;
                background: #fff;
            }
            .kra-etims-product-item {
                padding: 8px;
                border-bottom: 1px solid #eee;
            }
            .kra-etims-product-item:last-child {
                border-bottom: none;
            }
        </style>

        <script>
        jQuery(document).ready(function($) {
            // Show/hide fields based on update method
            $('#update_method').on('change', function() {
                var method = $(this).val();
                $('#category_selection, #current_tax_type_selection, #product_ids_selection').hide();
                $('#preview-section, #execute-btn').hide();
                
                if (method === 'category') {
                    $('#category_selection').show();
                } else if (method === 'specific_tax_type') {
                    $('#current_tax_type_selection').show();
                } else if (method === 'specific_ids') {
                    $('#product_ids_selection').show();
                }
            });

            // Preview button
            $('#preview-btn').on('click', function() {
                var formData = {
                    action: 'kra_etims_preview_bulk_update',
                    nonce: '<?php echo wp_create_nonce('kra_etims_bulk_update'); ?>',
                    update_method: $('#update_method').val(),
                    category_ids: $('#category_ids').val() || [],
                    current_tax_type: $('#current_tax_type').val(),
                    product_ids: $('#product_ids').val(),
                    new_tax_type: $('#new_tax_type').val()
                };

                $('#preview-section').show();
                $('#preview-content').html('');
                $('#preview-loading').show();

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        $('#preview-loading').hide();
                        if (response.success) {
                            $('#preview-content').html(response.data.html);
                            if (response.data.count > 0) {
                                $('#execute-btn').show();
                            } else {
                                $('#execute-btn').hide();
                                $('#preview-content').append('<p style="color: #d63638;"><strong>' + 
                                    '<?php _e('No products found matching your criteria.', 'kra-etims-integration'); ?>' + 
                                    '</strong></p>');
                            }
                        } else {
                            $('#preview-content').html('<p style="color: #d63638;">Error: ' + response.data + '</p>');
                        }
                    },
                    error: function() {
                        $('#preview-loading').hide();
                        $('#preview-content').html('<p style="color: #d63638;">Network error occurred</p>');
                    }
                });
            });

            // Execute button
            $('#execute-btn').on('click', function() {
                if (!confirm('<?php _e('Are you sure you want to update these products? This action cannot be undone.', 'kra-etims-integration'); ?>')) {
                    return;
                }

                var formData = {
                    action: 'kra_etims_execute_bulk_update',
                    nonce: '<?php echo wp_create_nonce('kra_etims_bulk_update'); ?>',
                    update_method: $('#update_method').val(),
                    category_ids: $('#category_ids').val() || [],
                    current_tax_type: $('#current_tax_type').val(),
                    product_ids: $('#product_ids').val(),
                    new_tax_type: $('#new_tax_type').val(),
                    update_tax_class: $('#update_tax_class').is(':checked') ? 1 : 0
                };

                $('#progress-section').show();
                $('#execute-btn').prop('disabled', true);
                $('#preview-btn').prop('disabled', true);
                $('#results-section').hide();

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        $('#execute-btn').prop('disabled', false);
                        $('#preview-btn').prop('disabled', false);
                        
                        if (response.success) {
                            $('#progress-bar').css('width', '100%').text('100%');
                            $('#progress-text').text('<?php _e('Update completed!', 'kra-etims-integration'); ?>');
                            $('#results-section').show();
                            $('#results-content').html(response.data.html);
                            
                            // Reload page after 3 seconds to show updated stats
                            setTimeout(function() {
                                window.location.reload();
                            }, 3000);
                        } else {
                            $('#progress-text').html('<span style="color: #d63638;">Error: ' + response.data + '</span>');
                        }
                    },
                    error: function() {
                        $('#execute-btn').prop('disabled', false);
                        $('#preview-btn').prop('disabled', false);
                        $('#progress-text').html('<span style="color: #d63638;">Network error occurred</span>');
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * Get tax statistics
     *
     * @return array Statistics
     */
    private function get_tax_statistics() {
        global $wpdb;

        // Get table names with proper prefix
        $posts_table = $wpdb->prefix . 'posts';
        $postmeta_table = $wpdb->prefix . 'postmeta';

        $stats = array(
            'total_products' => 0,
            'tax_a' => 0,
            'tax_b' => 0,
            'tax_c' => 0,
            'tax_d' => 0,
            'not_set' => 0
        );

        // Get total products
        $stats['total_products'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM `{$posts_table}` 
            WHERE post_type = %s AND post_status = 'publish'",
            'product'
        ));

        // Get count by tax type (compatible with any prefix)
        $tax_types = array('A', 'B', 'C', 'D');
        foreach ($tax_types as $type) {
            $count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT pm.post_id) 
                FROM `{$postmeta_table}` pm
                INNER JOIN `{$posts_table}` p ON pm.post_id = p.ID
                WHERE pm.meta_key = %s 
                AND pm.meta_value = %s
                AND p.post_type = %s
                AND p.post_status = 'publish'",
                '_injonge_taxid',
                $type,
                'product'
            ));
            $stats['tax_' . strtolower($type)] = $count;
        }

        // Get products without tax type
        $stats['not_set'] = $wpdb->get_var(
            "SELECT COUNT(DISTINCT p.ID) 
            FROM `{$posts_table}` p
            LEFT JOIN `{$postmeta_table}` pm ON p.ID = pm.post_id AND pm.meta_key = '_injonge_taxid'
            WHERE p.post_type = 'product'
            AND p.post_status = 'publish'
            AND pm.meta_value IS NULL"
        );

        return $stats;
    }

    /**
     * AJAX handler for preview
     */
    public function ajax_preview_bulk_update() {
        check_ajax_referer('kra_etims_bulk_update', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Insufficient permissions');
        }

        $update_method = isset($_POST['update_method']) ? sanitize_text_field($_POST['update_method']) : 'all';
        $category_ids = isset($_POST['category_ids']) ? array_map('intval', $_POST['category_ids']) : array();
        $current_tax_type = isset($_POST['current_tax_type']) ? sanitize_text_field($_POST['current_tax_type']) : '';
        $product_ids = isset($_POST['product_ids']) ? sanitize_text_field($_POST['product_ids']) : '';
        $new_tax_type = isset($_POST['new_tax_type']) ? sanitize_text_field($_POST['new_tax_type']) : '';

        if (empty($new_tax_type)) {
            wp_send_json_error('Please select a new tax type');
        }

        // Get affected products
        $products = $this->get_affected_products($update_method, $category_ids, $current_tax_type, $product_ids);

        // Build preview HTML
        $html = '<p><strong>' . sprintf(__('Found %d products that will be updated:', 'kra-etims-integration'), count($products)) . '</strong></p>';
        
        if (count($products) > 0) {
            $html .= '<div class="kra-etims-product-preview">';
            foreach (array_slice($products, 0, 50) as $product) {
                $current = get_post_meta($product->ID, '_injonge_taxid', true);
                $html .= '<div class="kra-etims-product-item">';
                $html .= '<strong>ID ' . $product->ID . ':</strong> ' . esc_html($product->post_title);
                $html .= ' <span style="color: #999;">(' . ($current ?: __('Not Set', 'kra-etims-integration')) . ' → ' . $new_tax_type . ')</span>';
                $html .= '</div>';
            }
            if (count($products) > 50) {
                $html .= '<div class="kra-etims-product-item" style="color: #999;">';
                $html .= sprintf(__('... and %d more products', 'kra-etims-integration'), count($products) - 50);
                $html .= '</div>';
            }
            $html .= '</div>';
        }

        wp_send_json_success(array(
            'html' => $html,
            'count' => count($products)
        ));
    }

    /**
     * AJAX handler for execution
     */
    public function ajax_execute_bulk_update() {
        check_ajax_referer('kra_etims_bulk_update', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Insufficient permissions');
        }

        $update_method = isset($_POST['update_method']) ? sanitize_text_field($_POST['update_method']) : 'all';
        $category_ids = isset($_POST['category_ids']) ? array_map('intval', $_POST['category_ids']) : array();
        $current_tax_type = isset($_POST['current_tax_type']) ? sanitize_text_field($_POST['current_tax_type']) : '';
        $product_ids = isset($_POST['product_ids']) ? sanitize_text_field($_POST['product_ids']) : '';
        $new_tax_type = isset($_POST['new_tax_type']) ? sanitize_text_field($_POST['new_tax_type']) : '';
        $update_tax_class = isset($_POST['update_tax_class']) && $_POST['update_tax_class'] == '1';

        if (empty($new_tax_type)) {
            wp_send_json_error('Please select a new tax type');
        }

        // Get affected products
        $products = $this->get_affected_products($update_method, $category_ids, $current_tax_type, $product_ids);

        if (empty($products)) {
            wp_send_json_error('No products found matching your criteria');
        }

        // Execute update
        $updated = $this->execute_bulk_update($products, $new_tax_type, $update_tax_class);

        // Build results HTML
        $html = '<p><strong>' . sprintf(__('Successfully updated %d products!', 'kra-etims-integration'), $updated['success']) . '</strong></p>';
        
        if ($updated['updated_tax_class'] > 0) {
            $html .= '<p>' . sprintf(__('Also updated WooCommerce tax class for %d products.', 'kra-etims-integration'), $updated['updated_tax_class']) . '</p>';
        }

        if ($updated['failed'] > 0) {
            $html .= '<p style="color: #d63638;">' . sprintf(__('Failed to update %d products.', 'kra-etims-integration'), $updated['failed']) . '</p>';
        }

        wp_send_json_success(array(
            'html' => $html,
            'count' => $updated['success']
        ));
    }

    /**
     * Get affected products based on criteria
     *
     * @param string $update_method Update method
     * @param array $category_ids Category IDs
     * @param string $current_tax_type Current tax type
     * @param string $product_ids Product IDs string
     * @return array Products
     */
    private function get_affected_products($update_method, $category_ids, $current_tax_type, $product_ids) {
        global $wpdb;

        // Get table names with proper prefix
        $posts_table = $wpdb->prefix . 'posts';
        $postmeta_table = $wpdb->prefix . 'postmeta';
        $term_relationships_table = $wpdb->prefix . 'term_relationships';
        $term_taxonomy_table = $wpdb->prefix . 'term_taxonomy';

        $query = "SELECT DISTINCT p.ID, p.post_title 
                  FROM `{$posts_table}` p
                  WHERE p.post_type = 'product' 
                  AND p.post_status = 'publish'";

        switch ($update_method) {
            case 'category':
                if (!empty($category_ids)) {
                    $placeholders = implode(',', array_fill(0, count($category_ids), '%d'));
                    $query .= " AND p.ID IN (
                        SELECT object_id FROM `{$term_relationships_table}` tr
                        INNER JOIN `{$term_taxonomy_table}` tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                        WHERE tt.term_id IN ($placeholders)
                        AND tt.taxonomy = 'product_cat'
                    )";
                    $prepared = $wpdb->prepare($query, $category_ids);
                    $query = $prepared;
                }
                break;

            case 'no_tax_type':
                $query .= " AND p.ID NOT IN (
                    SELECT post_id FROM `{$postmeta_table}`
                    WHERE meta_key = '_injonge_taxid'
                )";
                break;

            case 'specific_tax_type':
                if ($current_tax_type === 'none') {
                    $query .= " AND p.ID NOT IN (
                        SELECT post_id FROM `{$postmeta_table}`
                        WHERE meta_key = '_injonge_taxid'
                    )";
                } else {
                    $query .= $wpdb->prepare(" AND p.ID IN (
                        SELECT post_id FROM `{$postmeta_table}`
                        WHERE meta_key = %s AND meta_value = %s
                    )", '_injonge_taxid', $current_tax_type);
                }
                break;

            case 'specific_ids':
                if (!empty($product_ids)) {
                    $ids = array_filter(array_map('intval', explode(',', $product_ids)));
                    if (!empty($ids)) {
                        $placeholders = implode(',', array_fill(0, count($ids), '%d'));
                        $prepared = $wpdb->prepare(" AND p.ID IN ($placeholders)", $ids);
                        $query .= $prepared;
                    }
                }
                break;

            case 'all':
            default:
                // No additional conditions
                break;
        }

        $products = $wpdb->get_results($query);

        return $products ?: array();
    }

    /**
     * Execute bulk update
     *
     * @param array $products Products to update
     * @param string $new_tax_type New tax type
     * @param bool $update_tax_class Whether to update tax class
     * @return array Results
     */
    private function execute_bulk_update($products, $new_tax_type, $update_tax_class) {
        global $wpdb;

        // Get tax handler for tax class mapping
        $tax_handler = KRA_eTims_WC_Tax_Handler::get_instance();
        $tax_class_map = array(
            'A' => 'exempt-tax-a',
            'B' => 'vat-16-tax-b',
            'C' => 'export-tax-c',
            'D' => 'non-vat-tax-d'
        );
        $tax_class = isset($tax_class_map[$new_tax_type]) ? $tax_class_map[$new_tax_type] : '';

        $success = 0;
        $failed = 0;
        $updated_tax_class = 0;

        // Get table name with proper prefix
        $postmeta_table = $wpdb->prefix . 'postmeta';

        foreach ($products as $product) {
            // Update KRA tax type
            $result = update_post_meta($product->ID, '_injonge_taxid', $new_tax_type);

            if ($result !== false) {
                $success++;

                // Update WooCommerce tax class if requested
                if ($update_tax_class && !empty($tax_class)) {
                    $product_obj = wc_get_product($product->ID);
                    if ($product_obj) {
                        $product_obj->set_tax_class($tax_class);
                        $product_obj->save();
                        $updated_tax_class++;
                    }
                }
            } else {
                $failed++;
            }

            // Clear object cache for this product
            wp_cache_delete($product->ID, 'posts');
            wp_cache_delete($product->ID, 'post_meta');
        }

        // Clear WooCommerce cache
        if (function_exists('wc_delete_product_transients')) {
            foreach ($products as $product) {
                wc_delete_product_transients($product->ID);
            }
        }

        return array(
            'success' => $success,
            'failed' => $failed,
            'updated_tax_class' => $updated_tax_class
        );
    }
}

