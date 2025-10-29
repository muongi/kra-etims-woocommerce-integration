<?php
/**
 * Tax Handler Class
 * Integrates KRA eTims tax types with WooCommerce tax system
 *
 * @package KRA_eTims_WC
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Tax Handler Class
 */
class KRA_eTims_WC_Tax_Handler {
    /**
     * Instance
     *
     * @var KRA_eTims_WC_Tax_Handler
     */
    private static $instance = null;

    /**
     * KRA Tax Type to WooCommerce Tax Class mapping
     *
     * @var array
     */
    private $tax_class_map = array(
        'B' => 'vat-16-tax-b',      // VAT 16%
        'A' => 'exempt-tax-a',      // Exempt
        'C' => 'export-tax-c',      // Export
        'D' => 'non-vat-tax-d'      // Non-VAT
    );

    /**
     * Get instance
     *
     * @return KRA_eTims_WC_Tax_Handler
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
        // Setup WooCommerce taxes on plugin activation
        add_action('admin_init', array($this, 'maybe_setup_taxes'));
        
        // Ensure tax settings are correct on cart/checkout load
        add_action('woocommerce_cart_loaded_from_session', array($this, 'ensure_tax_settings'));
        add_action('wp_loaded', array($this, 'ensure_tax_settings'));
        
        // Removed problematic filters that were adding tax on top of tax-inclusive prices
        // WooCommerce handles tax-inclusive display natively when settings are correct
        
        // Add tax class field to product edit page
        add_action('woocommerce_product_options_tax', array($this, 'add_kra_tax_type_field'));
        
        // Save KRA tax type when product is saved
        add_action('woocommerce_process_product_meta', array($this, 'save_kra_tax_type_field'));
        
        // Sync tax class when product is saved
        add_action('woocommerce_process_product_meta', array($this, 'sync_tax_class_with_kra_type'), 20);
        
        // Add column to products list
        add_filter('manage_edit-product_columns', array($this, 'add_kra_tax_column'));
        add_action('manage_product_posts_custom_column', array($this, 'display_kra_tax_column'), 10, 2);
    }

    /**
     * Check if taxes need to be setup and setup if needed
     */
    public function maybe_setup_taxes() {
        // Check if taxes have been setup
        $taxes_setup = get_option('kra_etims_wc_taxes_setup', false);
        
        if (!$taxes_setup) {
            $this->setup_woocommerce_taxes();
            $this->clear_all_tax_rates(); // Clear any existing tax rates
            update_option('kra_etims_wc_taxes_setup', true);
        }
    }
    
    /**
     * Clear all WooCommerce tax rates
     * This prevents WooCommerce from calculating tax on tax-inclusive prices
     */
    private function clear_all_tax_rates() {
        global $wpdb;
        
        // Delete all tax rates
        $wpdb->query("DELETE FROM {$wpdb->prefix}woocommerce_tax_rates");
        $wpdb->query("DELETE FROM {$wpdb->prefix}woocommerce_tax_rate_locations");
        
        // Clear WooCommerce cache
        delete_transient('wc_tax_rates');
        wp_cache_delete('tax-rates', 'woocommerce');
        
        error_log('KRA eTims: All WooCommerce tax rates cleared to prevent double taxation');
    }

    /**
     * Setup WooCommerce taxes for KRA compliance
     */
    public function setup_woocommerce_taxes() {
        // Enable tax calculations (for API reporting only, not for display)
        update_option('woocommerce_calc_taxes', 'yes');
        
        // Set prices to include tax (tax-inclusive pricing)
        update_option('woocommerce_prices_include_tax', 'yes');
        
        // Display prices including tax in shop
        update_option('woocommerce_tax_display_shop', 'incl');
        
        // Display prices including tax in cart
        update_option('woocommerce_tax_display_cart', 'incl');
        
        // Set tax based on customer billing address
        update_option('woocommerce_tax_based_on', 'billing');
        
        // DO NOT setup tax classes or rates
        // Tax calculation will be done only for API reporting, not by WooCommerce
        // This prevents double taxation on tax-inclusive prices
        
        error_log('KRA eTims: WooCommerce taxes configured for KRA compliance (tax-inclusive only, no rates)');
    }

    /**
     * Ensure tax settings are correct (called on cart load and page load)
     */
    public function ensure_tax_settings() {
        // Force settings to be correct every time
        if (get_option('woocommerce_prices_include_tax') !== 'yes') {
            update_option('woocommerce_prices_include_tax', 'yes');
        }
        if (get_option('woocommerce_tax_display_cart') !== 'incl') {
            update_option('woocommerce_tax_display_cart', 'incl');
        }
        if (get_option('woocommerce_tax_display_shop') !== 'incl') {
            update_option('woocommerce_tax_display_shop', 'incl');
        }
        if (get_option('woocommerce_calc_taxes') !== 'yes') {
            update_option('woocommerce_calc_taxes', 'yes');
        }
    }

    /**
     * Force tax-inclusive price display in cart
     * 
     * @param string $price Price HTML
     * @param array $cart_item Cart item data
     * @param string $cart_item_key Cart item key
     * @return string Modified price HTML
     */
    public function force_tax_inclusive_price_display($price, $cart_item, $cart_item_key) {
        if (!isset($cart_item['data']) || !is_a($cart_item['data'], 'WC_Product')) {
            return $price;
        }
        
        $product = $cart_item['data'];
        
        // Get price including tax
        $price_incl_tax = wc_get_price_including_tax($product);
        
        // Return formatted price with tax included
        return wc_price($price_incl_tax);
    }

    /**
     * Force tax-inclusive subtotal display in cart
     * 
     * @param string $subtotal Subtotal HTML
     * @param array $cart_item Cart item data
     * @param string $cart_item_key Cart item key
     * @return string Modified subtotal HTML
     */
    public function force_tax_inclusive_subtotal_display($subtotal, $cart_item, $cart_item_key) {
        if (!isset($cart_item['data']) || !is_a($cart_item['data'], 'WC_Product')) {
            return $subtotal;
        }
        
        $product = $cart_item['data'];
        $quantity = isset($cart_item['quantity']) ? $cart_item['quantity'] : 1;
        
        // Get subtotal including tax (price * quantity)
        $subtotal_incl_tax = wc_get_price_including_tax($product, array('qty' => $quantity));
        
        // Return formatted subtotal with tax included
        return wc_price($subtotal_incl_tax);
    }

    /**
     * Force cart subtotal to show tax-inclusive
     * 
     * @param string $subtotal Cart subtotal HTML
     * @return string Modified subtotal HTML
     */
    public function force_cart_subtotal_incl_tax($subtotal) {
        if (!WC()->cart) {
            return $subtotal;
        }
        
        // Get cart subtotal including tax
        $cart_subtotal = WC()->cart->get_subtotal();
        $cart_subtotal_tax = WC()->cart->get_subtotal_tax();
        $subtotal_incl_tax = $cart_subtotal + $cart_subtotal_tax;
        
        // Return formatted subtotal with tax included
        return wc_price($subtotal_incl_tax);
    }

    /**
     * Setup tax classes
     */
    private function setup_tax_classes() {
        // Get existing tax classes
        $existing_classes = WC_Tax::get_tax_classes();
        
        // Define KRA tax classes
        $kra_classes = array(
            'VAT 16% (Tax B)',
            'Exempt (Tax A)',
            'Export (Tax C)',
            'Non-VAT (Tax D)'
        );
        
        // Add new classes
        $all_classes = array_merge($existing_classes, $kra_classes);
        $all_classes = array_unique($all_classes);
        
        // Update tax classes
        update_option('woocommerce_tax_classes', implode("\n", $all_classes));
        
        error_log('KRA eTims: Tax classes setup: ' . implode(', ', $kra_classes));
    }

    /**
     * Setup tax rates for each class
     */
    private function setup_tax_rates() {
        global $wpdb;
        
        // Define tax rates
        $tax_rates = array(
            // VAT 16% (Tax B) - Default and most common
            array(
                'tax_rate_country'  => 'KE',
                'tax_rate_state'    => '',
                'tax_rate'          => '16.0000',
                'tax_rate_name'     => 'VAT',
                'tax_rate_priority' => 1,
                'tax_rate_compound' => 0,
                'tax_rate_shipping' => 1,
                'tax_rate_order'    => 1,
                'tax_rate_class'    => 'vat-16-tax-b'
            ),
            // Exempt (Tax A)
            array(
                'tax_rate_country'  => 'KE',
                'tax_rate_state'    => '',
                'tax_rate'          => '0.0000',
                'tax_rate_name'     => 'Exempt',
                'tax_rate_priority' => 1,
                'tax_rate_compound' => 0,
                'tax_rate_shipping' => 0,
                'tax_rate_order'    => 2,
                'tax_rate_class'    => 'exempt-tax-a'
            ),
            // Export (Tax C)
            array(
                'tax_rate_country'  => '',
                'tax_rate_state'    => '',
                'tax_rate'          => '0.0000',
                'tax_rate_name'     => 'Export',
                'tax_rate_priority' => 1,
                'tax_rate_compound' => 0,
                'tax_rate_shipping' => 0,
                'tax_rate_order'    => 3,
                'tax_rate_class'    => 'export-tax-c'
            ),
            // Non-VAT (Tax D)
            array(
                'tax_rate_country'  => 'KE',
                'tax_rate_state'    => '',
                'tax_rate'          => '0.0000',
                'tax_rate_name'     => 'Non-VAT',
                'tax_rate_priority' => 1,
                'tax_rate_compound' => 0,
                'tax_rate_shipping' => 0,
                'tax_rate_order'    => 4,
                'tax_rate_class'    => 'non-vat-tax-d'
            ),
            // Standard rate (for products without specific tax class)
            array(
                'tax_rate_country'  => 'KE',
                'tax_rate_state'    => '',
                'tax_rate'          => '16.0000',
                'tax_rate_name'     => 'VAT',
                'tax_rate_priority' => 1,
                'tax_rate_compound' => 0,
                'tax_rate_shipping' => 1,
                'tax_rate_order'    => 0,
                'tax_rate_class'    => '' // Standard rate
            )
        );
        
        // Insert each tax rate
        foreach ($tax_rates as $rate) {
            // Check if rate already exists
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT tax_rate_id FROM {$wpdb->prefix}woocommerce_tax_rates 
                WHERE tax_rate_country = %s 
                AND tax_rate_class = %s 
                AND tax_rate = %s",
                $rate['tax_rate_country'],
                $rate['tax_rate_class'],
                $rate['tax_rate']
            ));
            
            if (!$existing) {
                WC_Tax::_insert_tax_rate($rate);
                error_log('KRA eTims: Added tax rate: ' . $rate['tax_rate_name'] . ' (' . $rate['tax_rate'] . '%)');
            }
        }
        
        // Clear tax rate cache
        WC_Cache_Helper::invalidate_cache_group('taxes');
    }

    /**
     * Add KRA tax type field to product edit page
     */
    public function add_kra_tax_type_field() {
        global $post;
        
        $current_tax_type = get_post_meta($post->ID, '_injonge_taxid', true);
        if (empty($current_tax_type)) {
            $current_tax_type = 'B'; // Default to VAT
        }
        
        ?>
        <p class="form-field">
            <label for="kra_tax_type"><?php _e('KRA Tax Type', 'kra-etims-integration'); ?></label>
            <select id="kra_tax_type" name="kra_tax_type" class="select short">
                <option value="B" <?php selected($current_tax_type, 'B'); ?>>B - VAT 16%</option>
                <option value="A" <?php selected($current_tax_type, 'A'); ?>>A - Exempt (0%)</option>
                <option value="C" <?php selected($current_tax_type, 'C'); ?>>C - Export (0%)</option>
                <option value="D" <?php selected($current_tax_type, 'D'); ?>>D - Non-VAT (0%)</option>
            </select>
            <span class="description"><?php _e('Select the KRA tax type for this product. The WooCommerce tax class will be automatically synced.', 'kra-etims-integration'); ?></span>
        </p>
        <?php
    }

    /**
     * Save KRA tax type field
     *
     * @param int $post_id Product ID
     */
    public function save_kra_tax_type_field($post_id) {
        if (isset($_POST['kra_tax_type'])) {
            $tax_type = sanitize_text_field($_POST['kra_tax_type']);
            update_post_meta($post_id, '_injonge_taxid', $tax_type);
        }
    }

    /**
     * Sync WooCommerce tax class with KRA tax type
     * DISABLED: Do not sync tax classes to prevent WooCommerce from calculating tax
     * Tax calculation is handled for API reporting only, not for display
     *
     * @param int $post_id Product ID
     */
    public function sync_tax_class_with_kra_type($post_id) {
        // DISABLED: Do not assign WooCommerce tax classes
        // We keep KRA tax type in product meta for API reporting only
        // This prevents WooCommerce from calculating tax on top of tax-inclusive prices
        
        $kra_tax_type = get_post_meta($post_id, '_injonge_taxid', true);
        
        if (!empty($kra_tax_type)) {
            // Just set the product to use standard tax class (with no rates configured)
            $product = wc_get_product($post_id);
            if ($product) {
                $product->set_tax_class(''); // Empty = Standard tax class
                $product->save();
                
                error_log("KRA eTims: Product {$post_id} - KRA Type: {$kra_tax_type} saved (WC tax class set to Standard)");
            }
        }
    }

    /**
     * Get KRA tax type from WooCommerce tax class
     *
     * @param string $tax_class WooCommerce tax class
     * @return string KRA tax type
     */
    public function get_kra_tax_type_from_class($tax_class) {
        $flipped = array_flip($this->tax_class_map);
        return isset($flipped[$tax_class]) ? $flipped[$tax_class] : 'B'; // Default to VAT
    }

    /**
     * Add KRA tax type column to products list
     *
     * @param array $columns Existing columns
     * @return array Modified columns
     */
    public function add_kra_tax_column($columns) {
        $new_columns = array();
        
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            if ($key === 'price') {
                $new_columns['kra_tax_type'] = __('KRA Tax', 'kra-etims-integration');
            }
        }
        
        return $new_columns;
    }

    /**
     * Display KRA tax type in products list column
     *
     * @param string $column Column name
     * @param int $post_id Product ID
     */
    public function display_kra_tax_column($column, $post_id) {
        if ($column === 'kra_tax_type') {
            $tax_type = get_post_meta($post_id, '_injonge_taxid', true);
            
            $tax_labels = array(
                'A' => '<span style="color: #46b450;">A - Exempt</span>',
                'B' => '<span style="color: #0073aa;">B - VAT 16%</span>',
                'C' => '<span style="color: #826eb4;">C - Export</span>',
                'D' => '<span style="color: #d63638;">D - Non-VAT</span>'
            );
            
            echo isset($tax_labels[$tax_type]) ? $tax_labels[$tax_type] : '<span style="color: #999;">Not Set</span>';
        }
    }

    /**
     * Get tax information from order item
     *
     * @param WC_Order_Item_Product $item Order item
     * @param WC_Order $order Order object
     * @return array Tax information
     */
    public function get_item_tax_info($item, $order) {
        $product_id = $item->get_product_id();
        $quantity = $item->get_quantity();
        
        // Get KRA tax type
        $kra_tax_type = get_post_meta($product_id, '_injonge_taxid', true);
        if (empty($kra_tax_type)) {
            $kra_tax_type = 'B'; // Default to VAT
        }
        
        // Get tax rate for this tax type
        $tax_rate = $this->get_tax_rate_for_type($kra_tax_type);
        
        // Get totals from WooCommerce - prices are tax-inclusive
        $line_total = $item->get_total(); // Total including tax (tax-inclusive price)
        $line_tax_woo = $item->get_total_tax(); // WooCommerce calculated tax
        
        // Check if prices are tax-inclusive
        $prices_include_tax = get_option('woocommerce_prices_include_tax') === 'yes';
        
        // For tax-inclusive pricing, manually calculate tax for proper API reporting
        if ($prices_include_tax && $line_total > 0) {
            // Calculate tax amount from tax-inclusive price
            // Formula: Tax = Total * (TaxRate / (100 + TaxRate))
            // For 16% VAT: Tax = 569 * (16 / 116) = 78.48
            $line_tax = ($line_total * $tax_rate) / (100 + $tax_rate);
            $line_subtotal = $line_total - $line_tax; // Taxable amount (without tax)
        } else {
            // Prices exclude tax (standard WooCommerce calculation)
            $line_subtotal = $item->get_subtotal(); // Subtotal without tax
            $line_tax = $line_tax_woo > 0 ? $line_tax_woo : ($line_subtotal * $tax_rate / 100);
            $line_total = $line_subtotal + $line_tax; // Total with tax
        }
        
        // Calculate per-unit values
        $unit_price_with_tax = $line_total / $quantity;
        $unit_tax = $line_tax / $quantity;
        $unit_price_without_tax = $line_subtotal / $quantity;
        
        return array(
            'kra_tax_type' => $kra_tax_type,
            'total_with_tax' => round($line_total, 2),
            'total_tax' => round($line_tax, 2),
            'total_without_tax' => round($line_subtotal, 2),
            'unit_price_with_tax' => round($unit_price_with_tax, 2),
            'unit_tax' => round($unit_tax, 2),
            'unit_price_without_tax' => round($unit_price_without_tax, 2),
            'taxable_amount' => round($line_subtotal, 2), // Amount without tax (for API)
            'tax_amount' => round($line_tax, 2) // Tax amount (for API)
        );
    }
    
    /**
     * Get tax rate for KRA tax type
     *
     * @param string $tax_type KRA tax type (A, B, C, D)
     * @return float Tax rate percentage
     */
    private function get_tax_rate_for_type($tax_type) {
        $tax_rates = array(
            'A' => 0,   // Exempt
            'B' => 16,  // VAT
            'C' => 0,   // Export
            'D' => 0    // Non-VAT
        );
        
        return isset($tax_rates[$tax_type]) ? $tax_rates[$tax_type] : 16; // Default to 16%
    }
}

