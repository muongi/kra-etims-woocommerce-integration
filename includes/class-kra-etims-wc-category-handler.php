<?php
/**
 * Category handler class
 *
 * @package KRA_eTims_WC
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Category handler class
 */
class KRA_eTims_WC_Category_Handler {
    /**
     * Instance
     *
     * @var KRA_eTims_WC_Category_Handler
     */
    private static $instance = null;
    
    /**
     * Settings
     *
     * @var array
     */
    private $settings;
    
    /**
     * Hooks added flag
     *
     * @var bool
     */
    private $hooks_added = false;

    /**
     * KRA eTims Item Codes for different categories
     *
     * @var array
     */
    private $item_codes = array(
        'electronics' => array(
            '4111460100' => 'Electronics - General',
            '4111460200' => 'Electronics - Computers & Accessories',
            '4111460300' => 'Electronics - Mobile Phones & Tablets',
            '4111460400' => 'Electronics - Audio & Video Equipment',
            '4111460500' => 'Electronics - Home Appliances'
        ),
        'spare_parts' => array(
            '4111470100' => 'Spare Parts - Automotive',
            '4111470200' => 'Spare Parts - Machinery',
            '4111470300' => 'Spare Parts - Electronics',
            '4111470400' => 'Spare Parts - Industrial'
        ),
        'wheels' => array(
            '4111480100' => 'Wheels - Automotive',
            '4111480200' => 'Wheels - Motorcycle',
            '4111480300' => 'Wheels - Bicycle',
            '4111480400' => 'Wheels - Industrial'
        ),
        'accessories' => array(
            '4111490100' => 'Accessories - Fashion',
            '4111490200' => 'Accessories - Electronics',
            '4111490300' => 'Accessories - Automotive',
            '4111490400' => 'Accessories - Home & Garden'
        ),
        'clothes' => array(
            '4111500100' => 'Clothes - Men\'s Wear',
            '4111500200' => 'Clothes - Women\'s Wear',
            '4111500300' => 'Clothes - Children\'s Wear',
            '4111500400' => 'Clothes - Sports & Active Wear',
            '4111500500' => 'Clothes - Formal Wear'
        ),
        'shoes' => array(
            '4111510100' => 'Shoes - Men\'s Footwear',
            '4111510200' => 'Shoes - Women\'s Footwear',
            '4111510300' => 'Shoes - Children\'s Footwear',
            '4111510400' => 'Shoes - Sports & Athletic',
            '4111510500' => 'Shoes - Formal & Business'
        ),
        'food' => array(
            '4111520100' => 'Food - Fresh Produce',
            '4111520200' => 'Food - Dairy Products',
            '4111520300' => 'Food - Meat & Poultry',
            '4111520400' => 'Food - Seafood',
            '4111520500' => 'Food - Grains & Cereals',
            '4111520600' => 'Food - Canned & Preserved',
            '4111520700' => 'Food - Beverages',
            '4111520800' => 'Food - Snacks & Confectionery',
            '4111520900' => 'Food - Spices & Condiments',
            '4111521000' => 'Food - Organic & Health Foods'
        ),
        'fast_foods' => array(
            '4111530100' => 'Fast Foods - Burgers & Sandwiches',
            '4111530200' => 'Fast Foods - Pizza',
            '4111530300' => 'Fast Foods - Fried Chicken',
            '4111530400' => 'Fast Foods - Hot Dogs',
            '4111530500' => 'Fast Foods - Tacos & Mexican',
            '4111530600' => 'Fast Foods - Asian Cuisine',
            '4111530700' => 'Fast Foods - Salads & Wraps',
            '4111530800' => 'Fast Foods - Desserts & Ice Cream',
            '4111530900' => 'Fast Foods - Beverages & Drinks',
            '4111531000' => 'Fast Foods - Sides & Appetizers'
        ),
        'special' => array(
            '990500XX' => 'Special Category',
            '9900000005' => 'Special Category'
        ),
        'exempt' => array(
            '9901114600' => 'Capital goods exemption for manufacturing sector (2B+ investment, pre-27Dec2024, until 27Dec2025)',
            '9901111400' => 'Taxable goods to government contractors (pre-25Apr2020, unexpired period)',
            '99011114' => 'Taxable goods to government contractors (pre-25Apr2020, unexpired period)',
            '99031000' => 'Exempt Goods or Services',
            '99021027' => 'Taxable services for specialized hospitals construction (CS Health recommendation)',
            '99011100' => 'Exempt Goods (Paragraph 100 - 146)',
            '99011063' => 'Taxable goods for specialized hospitals (50+ beds, CS Health recommendation)',
            '99021000' => 'Exempt Service',
            '99011146' => 'Capital goods exemption for manufacturing sector (2B+ investment)',
            '99011000' => 'Exempt Goods (Paragraph 1 - 99)'
        )
    );

    /**
     * Get instance
     *
     * @return KRA_eTims_WC_Category_Handler
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
        $this->settings = get_option('kra_etims_wc_settings', array());
        $this->add_hooks();
    }
    
    /**
     * Add hooks
     */
    private function add_hooks() {
        // Prevent duplicate hooks
        if ($this->hooks_added) {
            return;
        }
        
        // Hook into category creation and updates
        add_action('created_product_cat', array($this, 'handle_category_created'), 10, 2);
        add_action('edited_product_cat', array($this, 'handle_category_updated'), 10, 2);
        
        // Add custom fields to category forms
        add_action('product_cat_add_form_fields', array($this, 'add_category_unspec_field'));
        add_action('product_cat_edit_form_fields', array($this, 'edit_category_unspec_field'), 10, 2);
        
        // Save category fields
        add_action('created_product_cat', array($this, 'save_category_unspec_field'), 10, 2);
        add_action('edited_product_cat', array($this, 'save_category_unspec_field'), 10, 2);
        
        // Add custom column to category list
        add_filter('manage_edit-product_cat_columns', array($this, 'add_category_columns'));
        add_filter('manage_product_cat_custom_column', array($this, 'add_category_column_content'), 10, 3);
        
        $this->hooks_added = true;
    }

    /**
     * Handle category created
     *
     * @param int $term_id Term ID
     * @param int $tt_id Term taxonomy ID
     */
    public function handle_category_created($term_id, $tt_id) {
        try {
            $this->send_category_to_api($term_id, 'create');
        } catch (Exception $e) {
            error_log('KRA eTims Category Handler Error (Create): ' . $e->getMessage());
        }
    }

    /**
     * Handle category updated
     *
     * @param int $term_id Term ID
     * @param int $tt_id Term taxonomy ID
     */
    public function handle_category_updated($term_id, $tt_id) {
        try {
            $this->send_category_to_api($term_id, 'update');
        } catch (Exception $e) {
            error_log('KRA eTims Category Handler Error (Update): ' . $e->getMessage());
        }
    }

    /**
     * Send category to API
     *
     * @param int $term_id Term ID
     * @param string $action Action (create/update)
     * @return bool Success status
     */
    private function send_category_to_api($term_id, $action) {
        // Get category data
        $category = get_term($term_id, 'product_cat');
        if (!$category || is_wp_error($category)) {
            throw new Exception('Category not found');
        }

        // Get unspec code (itemcode)
        $itemcode = get_term_meta($term_id, '_kra_etims_unspec_code', true);
        if (empty($itemcode)) {
            // Use default item code if not set
            $itemcode = '4111460100';
        }

        // Get settings for tin and bhfId
        $settings = get_option('kra_etims_wc_settings', array());
        $tin = isset($settings['tin']) ? $settings['tin'] : '990888000';
        $bhfId = isset($settings['bhfId']) ? $settings['bhfId'] : '00';

        // Prepare category data in your exact JSON format
        $category_data = array(
            'catergory_name' => $category->name, // Note: matches your spelling
            'description' => $category->description ?: '000',
            'price' => '0', // Default price for categories
            'tin' => $tin,
            'bhfId' => $bhfId,
            'itemcode' => $itemcode
        );

        // Get API base URL from settings
        $api_base_url = isset($this->settings['api_base_url']) ? $this->settings['api_base_url'] : '';
        
        if (empty($api_base_url)) {
            throw new Exception('API Base URL not configured in settings');
        }
        
        // Build the full URL with your specific endpoint
        $api_url = rtrim($api_base_url, '/') . '/add_categories';
        
        // Debug logging
        error_log('KRA eTims Category Handler: Sending category to API - ' . $category->name);
        error_log('KRA eTims Category Handler: API URL - ' . $api_url);
        error_log('KRA eTims Category Handler: Category data - ' . json_encode($category_data));
        
        // Prepare request args
        $args = array(
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
            'cookies' => array(),
        );
        
        // Make request
        $response = wp_remote_request($api_url, $args);
        
        // Check for errors
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            throw new Exception('API request failed: ' . $error_message);
        }
        
        // Get response code
        $response_code = wp_remote_retrieve_response_code($response);
        
        // Get response body
        $response_body = wp_remote_retrieve_body($response);
        $response_data = json_decode($response_body, true);
        
        // Debug logging for response
        error_log('KRA eTims Category Handler: API Response Code - ' . $response_code);
        error_log('KRA eTims Category Handler: API Response Body - ' . $response_body);
        
        // Check for error response
        if ($response_code < 200 || $response_code >= 300) {
            $error_message = isset($response_data['message']) ? $response_data['message'] : 'Unknown API error';
            throw new Exception('API error: ' . $error_message . ' (Status: ' . $response_code . ')');
        }
        
        // Process response to get sid (if your API returns it)
        if (isset($response_data['sid'])) {
            // Save the server ID (sid) to category meta
            update_term_meta($term_id, '_kra_etims_server_id', $response_data['sid']);
            
            // Log success
            error_log('KRA eTims Category Handler: Category successfully sent to API - ' . $category->name . ' (SID: ' . $response_data['sid'] . ')');
        } elseif (isset($response_data['id'])) {
            // Alternative field name for server ID
            update_term_meta($term_id, '_kra_etims_server_id', $response_data['id']);
            error_log('KRA eTims Category Handler: Category successfully sent to API - ' . $category->name . ' (ID: ' . $response_data['id'] . ')');
        } else {
            // Log success even if no sid returned
            error_log('KRA eTims Category Handler: Category successfully sent to API - ' . $category->name);
        }
        
        return true;
    }

    /**
     * Add unspec code field to category add form
     */
    public function add_category_unspec_field() {
        ?>
        <div class="form-field term-unspec-code-wrap">
            <label for="kra_etims_unspec_code"><?php _e('KRA eTims Item Code', 'kra-etims-integration'); ?></label>
            <select name="kra_etims_unspec_code" id="kra_etims_unspec_code">
                <option value=""><?php _e('Select Item Code', 'kra-etims-integration'); ?></option>
                <?php foreach ($this->item_codes as $category_type => $codes): ?>
                    <optgroup label="<?php echo esc_attr(ucfirst(str_replace('_', ' ', $category_type))); ?>">
                        <?php foreach ($codes as $code => $description): ?>
                            <option value="<?php echo esc_attr($code); ?>"><?php echo esc_html($code . ' - ' . $description); ?></option>
                        <?php endforeach; ?>
                    </optgroup>
                <?php endforeach; ?>
            </select>
            <p class="description"><?php _e('Select the appropriate item code for this category. Products in this category will inherit this item code.', 'kra-etims-integration'); ?></p>
        </div>
        <?php
    }

    /**
     * Add unspec code field to category edit form
     *
     * @param object $term Term object
     * @param string $taxonomy Taxonomy name
     */
    public function edit_category_unspec_field($term, $taxonomy) {
        $itemcode = get_term_meta($term->term_id, '_kra_etims_unspec_code', true);
        $server_id = get_term_meta($term->term_id, '_kra_etims_server_id', true);
        ?>
        <tr class="form-field term-unspec-code-wrap">
            <th scope="row">
                <label for="kra_etims_unspec_code"><?php _e('KRA eTims Item Code', 'kra-etims-integration'); ?></label>
            </th>
            <td>
                <select name="kra_etims_unspec_code" id="kra_etims_unspec_code">
                    <option value=""><?php _e('Select Item Code', 'kra-etims-integration'); ?></option>
                    <?php foreach ($this->item_codes as $category_type => $codes): ?>
                        <optgroup label="<?php echo esc_attr(ucfirst(str_replace('_', ' ', $category_type))); ?>">
                            <?php foreach ($codes as $code => $description): ?>
                                <option value="<?php echo esc_attr($code); ?>" <?php selected($itemcode, $code); ?>>
                                    <?php echo esc_html($code . ' - ' . $description); ?>
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endforeach; ?>
                </select>
                <p class="description"><?php _e('Select the appropriate item code for this category. Products in this category will inherit this item code.', 'kra-etims-integration'); ?></p>
            </td>
        </tr>
        <?php if (!empty($server_id)): ?>
        <tr class="form-field term-server-id-wrap">
            <th scope="row">
                <label><?php _e('Server ID (SID)', 'kra-etims-integration'); ?></label>
            </th>
            <td>
                <input type="text" value="<?php echo esc_attr($server_id); ?>" readonly style="background-color: #f0f0f0;" />
                <p class="description"><?php _e('This is the server ID returned from the API. It cannot be edited.', 'kra-etims-integration'); ?></p>
            </td>
        </tr>
        <?php endif; ?>
        <?php
    }

    /**
     * Save category unspec code field
     *
     * @param int $term_id Term ID
     * @param int $tt_id Term taxonomy ID
     */
    public function save_category_unspec_field($term_id, $tt_id) {
        if (isset($_POST['kra_etims_unspec_code'])) {
            $itemcode = sanitize_text_field($_POST['kra_etims_unspec_code']);
            update_term_meta($term_id, '_kra_etims_unspec_code', $itemcode);
        }
    }

    /**
     * Add custom columns to category list
     *
     * @param array $columns Existing columns
     * @return array Modified columns
     */
    public function add_category_columns($columns) {
        $new_columns = array();
        
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            if ($key === 'description') {
                $new_columns['itemcode'] = __('Item Code', 'kra-etims-integration');
                $new_columns['server_id'] = __('Server ID', 'kra-etims-integration');
            }
        }
        
        return $new_columns;
    }

    /**
     * Add content to custom columns
     *
     * @param string $content Column content
     * @param string $column_name Column name
     * @param int $term_id Term ID
     * @return string Modified content
     */
    public function add_category_column_content($content, $column_name, $term_id) {
        switch ($column_name) {
            case 'itemcode':
                $itemcode = get_term_meta($term_id, '_kra_etims_unspec_code', true);
                if ($itemcode) {
                    // Find the description for this item code
                    $description = '';
                    foreach ($this->item_codes as $category_type => $codes) {
                        if (isset($codes[$itemcode])) {
                            $description = $codes[$itemcode];
                            break;
                        }
                    }
                    return esc_html($itemcode) . ($description ? ' - ' . esc_html($description) : '');
                }
                return '<span style="color: #999;">—</span>';
                
            case 'server_id':
                $server_id = get_term_meta($term_id, '_kra_etims_server_id', true);
                return $server_id ? esc_html($server_id) : '<span style="color: #999;">—</span>';
                
            default:
                return $content;
        }
    }

    /**
     * Get category server ID
     *
     * @param int $category_id Category ID
     * @return string|false Server ID or false if not found
     */
    public function get_category_server_id($category_id) {
        return get_term_meta($category_id, '_kra_etims_server_id', true);
    }

    /**
     * Get category item code
     *
     * @param int $category_id Category ID
     * @return string|false Item code or false if not found
     */
    public function get_category_unspec_code($category_id) {
        return get_term_meta($category_id, '_kra_etims_unspec_code', true);
    }

    /**
     * Get item code description
     *
     * @param string $itemcode Item code
     * @return string Description or empty string if not found
     */
    public function get_item_code_description($itemcode) {
        foreach ($this->item_codes as $category_type => $codes) {
            if (isset($codes[$itemcode])) {
                return $codes[$itemcode];
            }
        }
        return '';
    }
}
