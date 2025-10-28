<?php
/**
 * Order handler class
 *
 * @package KRA_eTims_WC
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Order handler class
 */
class KRA_eTims_WC_Order_Handler {
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
     * Tax Handler instance
     *
     * @var KRA_eTims_WC_Tax_Handler
     */
    private $tax_handler;

    /**
     * Constructor
     */
    public function __construct() {
        $this->api = new KRA_eTims_WC_API();
        $this->settings = get_option('kra_etims_wc_settings', array());
        $this->tax_handler = KRA_eTims_WC_Tax_Handler::get_instance();
    }

    /**
     * Process order
     *
     * @param int $order_id Order ID
     * @return array Result
     */
    public function process_order($order_id) {
        // Get order
        $order = wc_get_order($order_id);
        if (!$order) {
            return array(
                'success' => false,
                'message' => __('Order not found.', 'kra-etims-integration')
            );
        }
        
        // Check if order status allows submission (not draft or pending)
        if (in_array($order->get_status(), array('draft', 'pending'))) {
            return array(
                'success' => false,
                'message' => __('Order must be processed before submitting to API.', 'kra-etims-integration')
            );
        }
        
        // Check if order has already been submitted successfully
        $custom_api_status = get_post_meta($order_id, '_custom_api_status', true);
        $receipt_signature = get_post_meta($order_id, '_receipt_signature', true);
        
        // If already submitted successfully, don't allow resubmission
        if ($custom_api_status === 'success') {
            return array(
                'success' => false,
                'message' => __('Order already submitted to custom API. Cannot resubmit.', 'kra-etims-integration')
            );
        }
        
        try {
            // Prepare receipt data for custom API
            $receipt_data = $this->prepare_receipt_data($order);
            
            // Send to custom API
            $custom_response = $this->api->send_to_custom_api($receipt_data);
            
            // Process API response
            $receipt_details = $this->process_api_response($custom_response);
            
            // Update order meta
            update_post_meta($order_id, '_custom_api_status', 'success');
            update_post_meta($order_id, '_custom_api_invoice_no', $receipt_data['invcNo']);
            update_post_meta($order_id, '_custom_api_submitted_at', current_time('mysql'));
            update_post_meta($order_id, '_custom_api_response', $custom_response);
            
            // Save receipt details if successful
            if ($receipt_details) {
                update_post_meta($order_id, '_receipt_number', $receipt_details['rcptNo']);
                update_post_meta($order_id, '_receipt_signature', $receipt_details['rcptSign']);
                update_post_meta($order_id, '_receipt_date', $receipt_details['vsdcRcptPbctDate']);
                update_post_meta($order_id, '_mrc_number', $receipt_details['mrcNo']);
                update_post_meta($order_id, '_sdc_id', $receipt_details['sdcId']);
                update_post_meta($order_id, '_internal_data', $receipt_details['intrlData']);
                update_post_meta($order_id, '_total_receipt_no', $receipt_details['totRcptNo']);
            }
            
            // Add order note with receipt details
            $note = __('Order successfully submitted to custom API with invoice number: ', 'eTIMS') . 
                   $receipt_data['invcNo'];
            
            if ($receipt_details) {
                $note .= "\n" . __('Receipt Details:', 'eTIMS');
                $note .= "\n- " . __('Invoice No:', 'eTIMS') . ' ' . $receipt_details['rcptNo'];
                $formatted_rcpt_sign = trim(chunk_split($receipt_details['rcptSign'], 4, '-'), '-');
                $note .= "\n- " . __('Receipt Signature:', 'eTIMS') . ' ' . $formatted_rcpt_sign;
                $note .= "\n- " . __('Receipt Date:', 'eTIMS') . ' ' . $receipt_details['vsdcRcptPbctDate'];
                $note .= "\n- " . __('CU Number:', 'eTIMS') . ' ' . $receipt_details['mrcNo'];
                $note .= "\n- " . __('SCU ID:', 'eTIMS') . ' ' . $receipt_details['sdcId'];
                
                // Add QR code URL
                $qr_url = KRA_eTims_WC_QR_Code::get_order_qr_url($order_id);
                if ($qr_url) {
                    $note .= "\n- " . __('QR Code URL:', 'eTIMS') . ' ' . $qr_url;
                }
            }
            
            $order->add_order_note($note);
            
            // Prepare detailed success message with transaction details
            $success_message = __('Order successfully submitted to custom API.', 'kra-etims-integration');
            
            if ($receipt_details) {
                $success_message .= "\n\n" . __('Transaction Details:', 'kra-etims-integration');
                $success_message .= "\n- " . __('Receipt Number:', 'kra-etims-integration') . ' ' . $receipt_details['rcptNo'];
                $success_message .= "\n- " . __('Receipt Signature:', 'kra-etims-integration') . ' ' . $this->format_receipt_signature($receipt_details['rcptSign']);
                $success_message .= "\n- " . __('Receipt Date:', 'kra-etims-integration') . ' ' . $receipt_details['vsdcRcptPbctDate'];
                $success_message .= "\n- " . __('MRC Number:', 'kra-etims-integration') . ' ' . $receipt_details['mrcNo'];
                $success_message .= "\n- " . __('SCU ID:', 'kra-etims-integration') . ' ' . $receipt_details['sdcId'];
            }
            
            return array(
                'success' => true,
                'message' => $success_message
            );
        } catch (Exception $e) {
            // Update order meta
            update_post_meta($order_id, '_custom_api_status', 'failed');
            update_post_meta($order_id, '_custom_api_error', $e->getMessage());
            
            // Add order note
            $order->add_order_note(
                __('Failed to submit order to custom API: ', 'kra-etims-integration') . 
                $e->getMessage()
            );
            
            return array(
                'success' => false,
                'message' => $e->getMessage()
            );
        }
    }

    /**
     * Prepare sales data
     *
     * @param WC_Order $order WooCommerce order
     * @return array Sales data
     */
    private function prepare_sales_data($order) {
        // Generate invoice number
        $invoice_number = $this->generate_invoice_number($order);
        
        // Prepare items
        $items = array();
        
        // Get order items
        $order_items = $order->get_items();
        
        foreach ($order_items as $item_id => $item) {
            // Get item data using safe methods
            $item_name = $item->get_name();
            $quantity = $item->get_quantity();
            $price = $order->get_item_total($item, false, false); // Get item price without tax
            $total = $price * $quantity;
            
            // Calculate tax amount as 16% of the price, as required by KRA eTims
            $tax_amount = round($price * 0.16, 2);
            $total_tax = $tax_amount * $quantity;
            
            // Set tax type code to V for VAT (16%)
            $tax_type_code = 'V'; // V for VAT
            
            // Add item to items array
            $items[] = array(
                'itemCd' => 'WC-' . $item_id, // Use item ID as item code
                'itemNm' => $item_name,
                'qty' => $quantity,
                'prc' => $price,
                'splyAmt' => $total,
                'dcRt' => 0,
                'dcAmt' => 0,
                'taxTyCd' => $tax_type_code,
                'taxAmt' => $total_tax
            );
        }
        
        // Add shipping as an item if applicable
        $shipping_total = $order->get_shipping_total();
        if ($shipping_total > 0) {
            // Calculate shipping tax as 16% of shipping price
            $shipping_tax = round($shipping_total * 0.16, 2);
            $tax_type_code = 'V'; // V for VAT
            
            $items[] = array(
                'itemCd' => 'SHIPPING',
                'itemNm' => __('Shipping', 'kra-etims-integration'),
                'qty' => 1,
                'prc' => $shipping_total,
                'splyAmt' => $shipping_total,
                'dcRt' => 0,
                'dcAmt' => 0,
                'taxTyCd' => $tax_type_code,
                'taxAmt' => $shipping_tax
            );
        }
        
        // Prepare sales data
        $sales_data = array(
            'invoice_number' => $invoice_number,
            'items' => $items
        );
        
        return $sales_data;
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

    /**
     * Generate invoice number
     *
     * @param WC_Order $order WooCommerce order
     * @return string Invoice number
     */
    private function generate_invoice_number($order) {
        // Check if invoice number already exists
        $invoice_number = get_post_meta($order->get_id(), '_kra_etims_invoice_no', true);
        if (!empty($invoice_number)) {
            return $invoice_number;
        }
        
        // Generate new invoice number
        $prefix = 'WC';
        $order_number = $order->get_order_number();
        $date = date('Ymd');
        
        $invoice_number = $prefix . '-' . $order_number . '-' . $date;
        
        return $invoice_number;
    }

    /**
     * Prepare receipt data for custom API
     *
     * @param WC_Order $order WooCommerce order
     * @return array Receipt data
     */
    private function prepare_receipt_data($order) {
        // Get settings
        $settings = get_option('kra_etims_wc_settings');
        $company_name = isset($settings['company_name']) && !empty($settings['company_name']) ? $settings['company_name'] : 'Company Name';
        $company_name_truncated = substr($company_name, 0, 20); // Ensure trdeNm is max 20 characters
        
        // Generate invoice number
        $invoice_number = $this->generate_invoice_number($order);
        
        // Get current date and time in required format
        $current_date = current_time('Ymd');
        $current_time = current_time('His');
        $current_datetime = $current_date . $current_time;
        
        // Get customer details
        $customer_name = trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name());
        $customer_company = $order->get_billing_company();
        $customer_phone = $order->get_billing_phone() ?: '777777777';
        
        // Use company name if available, otherwise use customer name
        $customer_display_name = !empty($customer_company) ? $customer_company : ($customer_name ?: 'Guest');
        
        // Get customer PIN from order meta
        $customer_tin = get_post_meta($order->get_id(), '_customer_tin', true);
        
        // Validate customer TIN/PIN length
        if (empty($customer_tin)) {
            $customer_tin = '00000000000'; // 11 zeros if no customer PIN
        } else {
            // Remove any spaces or special characters, keep alphanumeric
            $customer_tin = preg_replace('/[^A-Za-z0-9]/', '', $customer_tin);
            
            // Convert to uppercase for consistency
            $customer_tin = strtoupper($customer_tin);
            
            // Ensure exactly 11 characters
            if (strlen($customer_tin) !== 11) {
                if (strlen($customer_tin) > 11) {
                    // Truncate to 11 characters
                    $customer_tin = substr($customer_tin, 0, 11);
                } else {
                    // Pad with zeros to make it 11 characters
                    $customer_tin = str_pad($customer_tin, 11, '0', STR_PAD_LEFT);
                }
            }
        }
        
        // Prepare items with tax details
        $item_list = array();
        $order_items = $order->get_items();
        $item_seq = 1;
        $total_taxable_amount_a = 0; // Tax A (exempted)
        $total_taxable_amount_b = 0; // Tax B (16% VAT)
        $total_taxable_amount_c = 0; // Tax C (export)
        $total_taxable_amount_d = 0; // Tax D (non VAT)
        $total_tax_amount_a = 0;     // Tax A amount (should be 0)
        $total_tax_amount_b = 0;     // Tax B amount (16%)
        $total_tax_amount_c = 0;     // Tax C amount (should be 0)
        $total_tax_amount_d = 0;     // Tax D amount (should be 0)
        $total_amount = 0;
        
        foreach ($order_items as $item_id => $item) {
            // Get product data - try different methods to get product ID
            $product_id = 0;
            if (method_exists($item, 'get_product_id')) {
                $product_id = $item->get_product_id();
            } elseif (method_exists($item, 'get_variation_id')) {
                $product_id = $item->get_variation_id();
            } elseif (isset($item['product_id'])) {
                $product_id = $item['product_id'];
            }
            $product = $product_id ? wc_get_product($product_id) : null;
            
            // Get item data using safe methods
            $item_name = $item->get_name();
            $quantity = $item->get_quantity();
            
            // Get tax information from WooCommerce (using our tax handler)
            $tax_info = $this->tax_handler->get_item_tax_info($item, $order);
            
            $unit_price = $tax_info['unit_price_with_tax']; // Price including tax (tax-inclusive)
            $total_price = $tax_info['total_with_tax']; // Total including tax
            $taxable_amount = $tax_info['taxable_amount']; // Amount without tax
            $tax_amount = $tax_info['tax_amount']; // Tax amount
            $tax_type = $tax_info['kra_tax_type']; // KRA tax type (A, B, C, D)
            
            // Set price to 0 if not available
            if (empty($unit_price) || $unit_price <= 0) {
                $unit_price = 0;
            }
            if (empty($total_price) || $total_price <= 0) {
                $total_price = 0;
            }
            
            // Get product meta for KRA eTims data
            $injonge_code = get_post_meta($product_id, '_injonge_code', true);
            $unspec_code = get_post_meta($product_id, '_injonge_unspec', true);
            
            // Default values if not set
            if (empty($injonge_code)) {
                $injonge_code = 'WC-' . $item_id; // Fallback to item ID
            }
            if (empty($unspec_code)) {
                $unspec_code = '2711290500'; // Default classification code
            }
            
            // Save injonge code to order item meta (for display in order details)
            if (method_exists($item, 'add_meta_data')) {
                $item->add_meta_data('_injonge_code', $injonge_code, true);
                $item->save_meta_data();
            } else {
                // Fallback for older WooCommerce versions
                wc_add_order_item_meta($item_id, '_injonge_code', $injonge_code, true);
            }
            
            // Add to totals based on tax type
            switch ($tax_type) {
                case 'A': // Exempt (0%)
                    $total_taxable_amount_a += $taxable_amount;
                    $total_tax_amount_a += $tax_amount;
                    break;
                    
                case 'B': // VAT (16%)
                    $total_taxable_amount_b += $taxable_amount;
                    $total_tax_amount_b += $tax_amount;
                    break;
                    
                case 'C': // Export (0%)
                    $total_taxable_amount_c += $taxable_amount;
                    $total_tax_amount_c += $tax_amount;
                    break;
                    
                case 'D': // Non VAT (0%)
                    $total_taxable_amount_d += $taxable_amount;
                    $total_tax_amount_d += $tax_amount;
                    break;
                    
                default: // Default to VAT (16%)
                    $total_taxable_amount_b += $taxable_amount;
                    $total_tax_amount_b += $tax_amount;
                    break;
            }
            
            $total_amount += $total_price;
            
            // Add item to item list
            $item_list[] = array(
                'itemSeq' => $item_seq,
                'itemClsCd' => $unspec_code, // Use unspec code as itemClsCd
                'itemCd' => $injonge_code,   // Use injonge_code as itemCd
                'itemNm' => $item_name,
                'bcd' => 'null',
                'pkgUnitCd' => 'NT',
                'pkg' => 1,
                'prc' => round($unit_price, 0),
                'qty' => $quantity,
                'splyAmt' => round($total_price, 0),
                'dcRt' => '0.00',
                'dcAmt' => '0.00',
                'isrccCd' => null,
                'isrccNm' => null,
                'isrcRt' => null,
                'isrcAmt' => null,
                'taxTyCd' => $tax_type,
                'taxAmt' => round($tax_amount, 2),
                'taxblAmt' => round($taxable_amount, 2),
                'totAmt' => round($total_price, 0),
                'qtyUnitCd' => 'U'
            );
            
            $item_seq++;
        }
        
        // Add shipping as an item if applicable
        $shipping_total = $order->get_shipping_total();
        $shipping_tax = $order->get_shipping_tax();
        
        if ($shipping_total > 0 || $shipping_tax > 0) {
            // Set shipping price to 0 if not available
            if (empty($shipping_total) || $shipping_total <= 0) {
                $shipping_total = 0;
            }
            
            // Calculate shipping amounts (tax-inclusive)
            $shipping_with_tax = $shipping_total + $shipping_tax;
            $shipping_taxable = $shipping_total; // Amount without tax
            
            // Shipping is typically Tax B (16% VAT)
            $total_taxable_amount_b += $shipping_taxable;
            $total_tax_amount_b += $shipping_tax;
            $total_amount += $shipping_with_tax;
            
            $item_list[] = array(
                'itemSeq' => $item_seq,
                'itemClsCd' => '3120150700', // Shipping classification code
                'itemCd' => 'SHIPPING',
                'itemNm' => 'Shipping',
                'bcd' => 'null',
                'pkgUnitCd' => 'NT',
                'pkg' => 1,
                'prc' => round($shipping_with_tax, 0),
                'qty' => 1,
                'splyAmt' => round($shipping_with_tax, 0),
                'dcRt' => '0.00',
                'dcAmt' => '0.00',
                'isrccCd' => null,
                'isrccNm' => null,
                'isrcRt' => null,
                'isrcAmt' => null,
                'taxTyCd' => 'B', // Shipping is always Tax B
                'taxAmt' => round($shipping_tax, 2),
                'taxblAmt' => round($shipping_taxable, 2),
                'totAmt' => round($shipping_with_tax, 0),
                'qtyUnitCd' => 'U'
            );
        }
        
        // Calculate total taxable amounts
        $total_taxable_amount = $total_taxable_amount_a + $total_taxable_amount_b + $total_taxable_amount_c + $total_taxable_amount_d;
        $total_tax_amount = $total_tax_amount_a + $total_tax_amount_b + $total_tax_amount_c + $total_tax_amount_d;
        
        // Prepare receipt data in the exact format required
        $receipt_data = array(
            'tin' => isset($settings['tin']) ? $settings['tin'] : '',
            'custTin' => $customer_tin,
            'custNm' => $customer_display_name,
            'rcptTyCd' => 'S',
            'salesTyCd' => 'N',
            'bhfId' => isset($settings['bhfId']) ? $settings['bhfId'] : '00',
            'invcNo' => $invoice_number,
            'refno' => $invoice_number,
            'orgInvcNo' => 0,
            'pmtTyCd' => '01',
            'salesSttsCd' => '02',
            'cfmDt' => $current_datetime,
            'stockRlsDt' => $current_datetime,
            'salesDt' => $current_date,
            'cnclReqDt' => null,
            'cnclDt' => null,
            'rfdDt' => null,
            'rfdRsnCd' => null,
            'totItemCnt' => count($item_list),
            'taxblAmtA' => round($total_taxable_amount_a, 0), // Tax A taxable amount
            'taxblAmtB' => round($total_taxable_amount_b, 0), // Tax B taxable amount
            'taxblAmtC' => round($total_taxable_amount_c, 0), // Tax C taxable amount
            'taxblAmtD' => round($total_taxable_amount_d, 0), // Tax D taxable amount
            'taxblAmtE' => 0,
            'taxRtA' => 0,  // Tax A rate (0% for exempt)
            'taxRtB' => 16, // Tax B rate (16% VAT)
            'taxRtC' => 0,  // Tax C rate (0% for export)
            'taxRtD' => 0,  // Tax D rate (0% for non VAT)
            'taxRtE' => 0,
            'taxAmtA' => round($total_tax_amount_a, 2), // Tax A amount (should be 0)
            'taxAmtB' => round($total_tax_amount_b, 2), // Tax B amount (16%)
            'taxAmtC' => round($total_tax_amount_c, 2), // Tax C amount (should be 0)
            'taxAmtD' => round($total_tax_amount_d, 2), // Tax D amount (should be 0)
            'taxAmtE' => 0,
            'totTaxblAmt' => round($total_taxable_amount, 0),
            'totTaxAmt' => round($total_tax_amount, 2),
            'totAmt' => round($total_amount, 0),
            'prchrAcptcYn' => 'Y',
            'remark' => 'Injonge pos',
            'modrId' => '1234',
            'modrNm' => $company_name,
            'regrNm' => $company_name,
            'regrId' => '1234',
            'deviceSerial' => isset($settings['device_serial']) ? $settings['device_serial'] : '6756756565',
            'receipt' => array(
                'curRcptNo' => 0,
                'totRcptNo' => 0,
                'custTin' => $customer_tin,
                'custMblNo' => $customer_phone,
                'rptNo' => 0,
                'rcptPbctDt' => $current_datetime,
                'trdeNm' => $company_name_truncated,
                'adrs' => $company_name,
                'topMsg' => $company_name,
                'btmMsg' => 'Thank you',
                'prchrAcptcYn' => 'Y'
            ),
            'itemList' => $item_list
        );
        
        return $receipt_data;
    }

    /**
     * Process API response and extract receipt details
     *
     * @param array $response API response
     * @return array|false Receipt details or false if unsuccessful
     */
    private function process_api_response($response) {
        // Check if response has the expected structure
        if (!isset($response['resultCd'])) {
            return false;
        }
        
        // Check if the response indicates success (resultCd = "000")
        if ($response['resultCd'] !== '000') {
            return false;
        }
        
        // Check if data is present
        if (!isset($response['data'])) {
            return false;
        }
        
        $data = $response['data'];
        
        // Extract receipt details
        $receipt_details = array(
            'rcptNo' => isset($data['rcptNo']) ? $data['rcptNo'] : '',
            'rcptSign' => isset($data['rcptSign']) ? $data['rcptSign'] : '',
            'vsdcRcptPbctDate' => isset($data['vsdcRcptPbctDate']) ? $data['vsdcRcptPbctDate'] : '',
            'mrcNo' => isset($data['mrcNo']) ? $data['mrcNo'] : '',
            'sdcId' => isset($data['sdcId']) ? $data['sdcId'] : '',
            'intrlData' => isset($data['intrlData']) ? $data['intrlData'] : '',
            'totRcptNo' => isset($data['totRcptNo']) ? $data['totRcptNo'] : '',
            'invcNo' => isset($data['invcNo']) ? $data['invcNo'] : ''
        );
        
        return $receipt_details;
    }

    /**
     * Process refund
     *
     * @param int $order_id Order ID
     * @return array Result
     */
    public function process_refund($order_id) {
        // Check if user is admin
        if (!current_user_can('manage_options')) {
            return array(
                'success' => false,
                'message' => __('Insufficient permissions. Only administrators can process refunds.', 'kra-etims-integration')
            );
        }
        
        // Get order
        $order = wc_get_order($order_id);
        if (!$order) {
            return array(
                'success' => false,
                'message' => __('Order not found.', 'kra-etims-integration')
            );
        }
        
        // Check if original order was submitted successfully
        $custom_api_status = get_post_meta($order_id, '_custom_api_status', true);
        if ($custom_api_status !== 'success') {
            return array(
                'success' => false,
                'message' => __('Original order must be submitted successfully before processing refund.', 'kra-etims-integration')
            );
        }
        
        // Check if refund has already been processed
        $refund_status = get_post_meta($order_id, '_custom_api_refund_status', true);
        if ($refund_status === 'success') {
            return array(
                'success' => false,
                'message' => __('Refund already processed for this order.', 'kra-etims-integration')
            );
        }
        
        try {
            // Prepare refund data (similar to receipt data but with refund-specific changes)
            $refund_data = $this->prepare_refund_data($order);
            
            // Send to custom API
            $custom_response = $this->api->send_to_custom_api($refund_data);
            
            // Process API response
            $receipt_details = $this->process_api_response($custom_response);
            
            // Update order meta
            update_post_meta($order_id, '_custom_api_refund_status', 'success');
            update_post_meta($order_id, '_custom_api_refund_invoice_no', $refund_data['invcNo']);
            update_post_meta($order_id, '_custom_api_refund_submitted_at', current_time('mysql'));
            update_post_meta($order_id, '_custom_api_refund_response', $custom_response);
            
            // Add order note
            $note = __('Refund processed and submitted to custom API.', 'kra-etims-integration');
            if ($receipt_details) {
                $note .= "\n\n" . __('Refund Transaction Details:', 'kra-etims-integration');
                $note .= "\n- " . __('Receipt Number:', 'kra-etims-integration') . ' ' . $receipt_details['rcptNo'];
                $note .= "\n- " . __('Receipt Signature:', 'kra-etims-integration') . ' ' . $receipt_details['rcptSign'];
            }
            $order->add_order_note($note);
            
            // Prepare success message
            $success_message = __('Refund successfully submitted to custom API.', 'kra-etims-integration');
            
            return array(
                'success' => true,
                'message' => $success_message
            );
        } catch (Exception $e) {
            // Update order meta
            update_post_meta($order_id, '_custom_api_refund_status', 'failed');
            update_post_meta($order_id, '_custom_api_refund_error', $e->getMessage());
            
            // Add order note
            $order->add_order_note(
                __('Failed to process refund: ', 'kra-etims-integration') . 
                $e->getMessage()
            );
            
            return array(
                'success' => false,
                'message' => $e->getMessage()
            );
        }
    }

    /**
     * Prepare refund data (modified receipt data for refund)
     *
     * @param WC_Order $order WooCommerce order
     * @return array Refund data
     */
    private function prepare_refund_data($order) {
        // Get the original receipt data
        $receipt_data = $this->prepare_receipt_data($order);
        
        // Get current date and time in required format
        $current_date = current_time('Ymd');
        $current_time = current_time('His');
        $current_datetime = $current_date . $current_time;
        
        // Modify specific fields for refund
        $receipt_data['rcptTyCd'] = 'R'; // R for Refund (was 'S' for Sale)
        $receipt_data['rfdRsnCd'] = '06'; // Refund reason code
        $receipt_data['rfdDt'] = $current_datetime; // Refund date (format: YmdHis)
        
        // Keep all other fields the same (items, amounts, taxes, etc.)
        
        return $receipt_data;
    }
}
