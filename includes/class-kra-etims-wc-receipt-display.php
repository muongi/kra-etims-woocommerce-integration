<?php
/**
 * Receipt Display Class
 *
 * @package KRA_eTims_WC
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Receipt Display Class
 */
class KRA_eTims_WC_Receipt_Display {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Add hooks for displaying receipt details
        add_action('woocommerce_order_details_after_order_table', array($this, 'display_receipt_details'), 10, 1);
        add_action('woocommerce_email_order_details', array($this, 'display_receipt_details_email'), 10, 4);
        add_filter('woocommerce_order_formatted_line_items', array($this, 'add_receipt_info_to_items'), 10, 2);
    }
    
    /**
     * Display receipt details on order details page
     *
     * @param WC_Order $order Order object
     */
    public function display_receipt_details($order) {
        $receipt_number = get_post_meta($order->get_id(), '_receipt_number', true);
        $receipt_signature = get_post_meta($order->get_id(), '_receipt_signature', true);
        $receipt_date = get_post_meta($order->get_id(), '_receipt_date', true);
        $mrc_number = get_post_meta($order->get_id(), '_mrc_number', true);
        $sdc_id = get_post_meta($order->get_id(), '_sdc_id', true);
        
        if ($receipt_number) {
            ?>
            <section class="woocommerce-order-receipt-details">
                <h2><?php _e('SCU INFORMATION', 'kra-etims-integration'); ?></h2>
                <div class="receipt-details-container">
                    <div class="receipt-info">
                        <table class="woocommerce-table shop_table receipt_details">
                            <tbody>
                                <tr>
                                    <th><?php _e('Receipt Number:', 'kra-etims-integration'); ?></th>
                                    <td><?php echo esc_html($receipt_number); ?></td>
                                </tr>
                                <?php 
                                // Show customer TIN if available
                                $customer_tin = get_post_meta($order->get_id(), '_customer_tin', true);
                                if ($customer_tin) : 
                                ?>
                                <tr>
                                    <th><?php _e('Customer TIN:', 'kra-etims-integration'); ?></th>
                                    <td><?php echo esc_html($customer_tin); ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php 
                                // Get internal data and format it
                                $internal_data = get_post_meta($order->get_id(), '_internal_data', true);
                                if ($internal_data) : 
                                ?>
                                <tr>
                                    <th><?php _e('Internal Data:', 'kra-etims-integration'); ?></th>
                                    <td><code><?php echo esc_html($this->format_internal_data($internal_data)); ?></code></td>
                                </tr>
                                <?php endif; ?>
                                <?php if ($receipt_signature) : ?>
                                <tr>
                                    <th><?php _e('Receipt Signature:', 'kra-etims-integration'); ?></th>
                                    <td><code><?php echo esc_html($this->format_receipt_signature($receipt_signature)); ?></code></td>
                                </tr>
                                <?php endif; ?>
                                <?php if ($receipt_date) : ?>
                                <tr>
                                    <th><?php _e('Receipt Date:', 'kra-etims-integration'); ?></th>
                                    <td><?php echo esc_html($this->format_receipt_date($receipt_date)); ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php if ($mrc_number) : ?>
                                <tr>
                                    <th><?php _e('CU Number:', 'kra-etims-integration'); ?></th>
                                    <td><?php echo esc_html($mrc_number); ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php if ($sdc_id) : ?>
                                <tr>
                                    <th><?php _e('SCU ID:', 'kra-etims-integration'); ?></th>
                                    <td><?php echo esc_html($sdc_id); ?></td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php 
                    // Display QR code
                    $qr_code = KRA_eTims_WC_QR_Code::generate_order_qr_code($order->get_id(), 120);
                    if ($qr_code) : 
                    ?>
                    <div class="receipt-qr-section">
                        <?php echo $qr_code; ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <style>
                    .receipt-details-container {
                        display: flex;
                        gap: 20px;
                        align-items: flex-start;
                    }
                    .receipt-info {
                        flex: 1;
                    }
                    .receipt-qr-section {
                        flex-shrink: 0;
                    }
                    .receipt-qr-code {
                        text-align: center;
                    }
                    .receipt-qr-code img {
                        border: 1px solid #ddd;
                        border-radius: 4px;
                    }
                    .qr-code-text {
                        margin: 5px 0 0 0;
                        font-size: 12px;
                        color: #666;
                    }
                    @media (max-width: 768px) {
                        .receipt-details-container {
                            flex-direction: column;
                        }
                        .receipt-qr-section {
                            align-self: center;
                        }
                    }
                </style>
            </section>
            <?php
        }
    }
    
    /**
     * Display receipt details in email
     *
     * @param WC_Order $order Order object
     * @param bool $sent_to_admin Whether email is sent to admin
     * @param bool $plain_text Whether email is plain text
     * @param WC_Email $email Email object
     */
    public function display_receipt_details_email($order, $sent_to_admin, $plain_text, $email) {
        $receipt_number = get_post_meta($order->get_id(), '_receipt_number', true);
        $receipt_signature = get_post_meta($order->get_id(), '_receipt_signature', true);
        $receipt_date = get_post_meta($order->get_id(), '_receipt_date', true);
        $mrc_number = get_post_meta($order->get_id(), '_mrc_number', true);
        $sdc_id = get_post_meta($order->get_id(), '_sdc_id', true);
        
        if ($receipt_number) {
            if ($plain_text) {
                echo "\n" . __('SCU INFORMATION', 'kra-etims-integration') . "\n";
                echo __('Receipt Number:', 'kra-etims-integration') . ' ' . $receipt_number . "\n";
                
                // Show customer TIN if available
                $customer_tin = get_post_meta($order->get_id(), '_customer_tin', true);
                if ($customer_tin) {
                    echo __('Customer TIN:', 'kra-etims-integration') . ' ' . $customer_tin . "\n";
                }
                
                // Get internal data and format it
                $internal_data = get_post_meta($order->get_id(), '_internal_data', true);
                if ($internal_data) {
                    echo __('Internal Data:', 'kra-etims-integration') . ' ' . $this->format_internal_data($internal_data) . "\n";
                }
                
                if ($receipt_signature) {
                    echo __('Receipt Signature:', 'kra-etims-integration') . ' ' . $this->format_receipt_signature($receipt_signature) . "\n";
                }
                if ($receipt_date) {
                    echo __('Receipt Date:', 'kra-etims-integration') . ' ' . $this->format_receipt_date($receipt_date) . "\n";
                }
                if ($mrc_number) {
                    echo __('CU Number:', 'kra-etims-integration') . ' ' . $mrc_number . "\n";
                }
                if ($sdc_id) {
                    echo __('SCU ID:', 'kra-etims-integration') . ' ' . $sdc_id . "\n";
                }
                echo "\n";
            } else {
                ?>
                <h2><?php _e('SCU INFORMATION', 'kra-etims-integration'); ?></h2>
                <table cellspacing="0" cellpadding="6" style="width: 100%; border: 1px solid #eee;">
                    <tbody>
                        <tr>
                            <th style="text-align:left; border-bottom: 1px solid #eee;"><?php _e('Receipt Number:', 'kra-etims-integration'); ?></th>
                            <td style="text-align:left; border-bottom: 1px solid #eee;"><?php echo esc_html($receipt_number); ?></td>
                        </tr>
                        <?php 
                        // Show customer TIN if available
                        $customer_tin = get_post_meta($order->get_id(), '_customer_tin', true);
                        if ($customer_tin) : 
                        ?>
                        <tr>
                            <th style="text-align:left; border-bottom: 1px solid #eee;"><?php _e('Customer TIN:', 'kra-etims-integration'); ?></th>
                            <td style="text-align:left; border-bottom: 1px solid #eee;"><?php echo esc_html($customer_tin); ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php 
                        // Get internal data and format it
                        $internal_data = get_post_meta($order->get_id(), '_internal_data', true);
                        if ($internal_data) : 
                        ?>
                        <tr>
                            <th style="text-align:left; border-bottom: 1px solid #eee;"><?php _e('Internal Data:', 'kra-etims-integration'); ?></th>
                            <td style="text-align:left; border-bottom: 1px solid #eee;"><code><?php echo esc_html($this->format_internal_data($internal_data)); ?></code></td>
                        </tr>
                        <?php endif; ?>
                        <?php if ($receipt_signature) : ?>
                        <tr>
                            <th style="text-align:left; border-bottom: 1px solid #eee;"><?php _e('Receipt Signature:', 'kra-etims-integration'); ?></th>
                            <td style="text-align:left; border-bottom: 1px solid #eee;"><code><?php echo esc_html($this->format_receipt_signature($receipt_signature)); ?></code></td>
                        </tr>
                        <?php endif; ?>
                        <?php if ($receipt_date) : ?>
                        <tr>
                            <th style="text-align:left; border-bottom: 1px solid #eee;"><?php _e('Receipt Date:', 'kra-etims-integration'); ?></th>
                            <td style="text-align:left; border-bottom: 1px solid #eee;"><?php echo esc_html($this->format_receipt_date($receipt_date)); ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if ($mrc_number) : ?>
                        <tr>
                            <th style="text-align:left; border-bottom: 1px solid #eee;"><?php _e('CU Number:', 'kra-etims-integration'); ?></th>
                            <td style="text-align:left; border-bottom: 1px solid #eee;"><?php echo esc_html($mrc_number); ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if ($sdc_id) : ?>
                        <tr>
                            <th style="text-align:left; border-bottom: 1px solid #eee;"><?php _e('SCU ID:', 'kra-etims-integration'); ?></th>
                            <td style="text-align:left; border-bottom: 1px solid #eee;"><?php echo esc_html($sdc_id); ?></td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <?php 
                // Display QR code in email
                $qr_code = KRA_eTims_WC_QR_Code::generate_order_qr_code($order->get_id(), 120);
                if ($qr_code) : 
                ?>
                <div style="text-align: center; margin-top: 20px;">
                    <?php echo $qr_code; ?>
                </div>
                <?php endif; ?>
                <?php
            }
        }
    }
    
    /**
     * Add receipt info to order items
     *
     * @param array $items Order items
     * @param WC_Order $order Order object
     * @return array
     */
    public function add_receipt_info_to_items($items, $order) {
        $receipt_number = get_post_meta($order->get_id(), '_receipt_number', true);
        
        if ($receipt_number) {
            $items['receipt_info'] = array(
                'name' => __('Receipt Number', 'kra-etims-integration'),
                'value' => $receipt_number,
                'type' => 'receipt'
            );
        }
        
        return $items;
    }
    
    /**
     * Format receipt date from DDMMYYHHMMSS to readable format
     *
     * @param string $receipt_date Receipt date in DDMMYYHHMMSS format
     * @return string Formatted date
     */
    private function format_receipt_date($receipt_date) {
        if (strlen($receipt_date) === 12) {
            $day = substr($receipt_date, 0, 2);
            $month = substr($receipt_date, 2, 2);
            $year = '20' . substr($receipt_date, 4, 2);
            $hour = substr($receipt_date, 6, 2);
            $minute = substr($receipt_date, 8, 2);
            $second = substr($receipt_date, 10, 2);
            
            return sprintf('%s-%s-%s %s:%s:%s', $year, $month, $day, $hour, $minute, $second);
        }
        
        return $receipt_date;
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
} 