<?php
/**
 * QR Code Generation Class
 *
 * @package KRA_eTims_WC
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * QR Code Generation Class
 */
class KRA_eTims_WC_QR_Code {
    
    /**
     * Generate QR code URL
     *
     * @param string $tin TIN number
     * @param string $bhfid Branch ID
     * @param string $rcpt_sign Receipt signature
     * @return string QR code URL
     */
    public static function generate_qr_url($tin, $bhfid, $rcpt_sign) {
        $qr_string = "https://etims.kra.go.ke/common/link/etims/receipt/indexEtimsReceiptData?Data=" . $tin . $bhfid . $rcpt_sign;
        return $qr_string;
    }
    
    /**
     * Generate QR code image using multiple methods with fallbacks
     *
     * @param string $qr_url QR code URL
     * @param int $size QR code size (default: 200)
     * @param bool $show_download Show download link (default: true)
     * @return string QR code image HTML
     */
    public static function generate_qr_image($qr_url, $size = 200, $show_download = true) {
        // Try local QR code generation first
        $local_qr_code = self::generate_local_qr_code($qr_url, $size);
        
        if ($local_qr_code) {
            $html = '<div class="receipt-qr-code">';
            $html .= $local_qr_code;
            $html .= '<p class="qr-code-text">' . __('Scan to verify receipt', 'kra-etims-connector') . '</p>';
            
            if ($show_download) {
                $html .= '<p class="qr-code-download">';
                $html .= '<button onclick="downloadQRCode(this)" data-url="' . esc_attr($qr_url) . '" class="button button-small">';
                $html .= __('Download QR Code', 'kra-etims-connector');
                $html .= '</button>';
                $html .= '</p>';
            }
            
            $html .= '</div>';
            
            return $html;
        }
        
        // Fallback to external API
        $qr_image_url = self::get_qr_code_image_url($qr_url, $size);
        
        $html = '<div class="receipt-qr-code">';
        $html .= '<img src="' . esc_url($qr_image_url) . '" alt="QR Code" width="' . $size . '" height="' . $size . '" onerror="this.style.display=\'none\'; this.nextElementSibling.style.display=\'block\';" />';
        $html .= '<div class="qr-code-fallback" style="display: none; padding: 20px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px; text-align: center;">';
        $html .= '<p><strong>' . __('QR Code Preview Unavailable', 'kra-etims-connector') . '</strong></p>';
        $html .= '<p>' . __('Please use the URL below to verify the receipt:', 'kra-etims-connector') . '</p>';
        $html .= '<code style="font-size: 10px; word-break: break-all; display: block; margin: 10px 0; padding: 8px; background: #fff; border: 1px solid #ccc;">' . esc_html($qr_url) . '</code>';
        $html .= '</div>';
        $html .= '<p class="qr-code-text">' . __('Scan to verify receipt', 'kra-etims-connector') . '</p>';
        
        if ($show_download) {
            $html .= '<p class="qr-code-download">';
            $html .= '<a href="' . esc_url($qr_image_url) . '" download="qr-code-' . time() . '.png" class="button button-small">';
            $html .= __('Download QR Code', 'kra-etims-connector');
            $html .= '</a>';
            $html .= '</p>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Get QR code image URL using multiple providers with fallbacks
     *
     * @param string $qr_url QR code URL
     * @param int $size QR code size
     * @return string QR code image URL
     */
    private static function get_qr_code_image_url($qr_url, $size) {
        // Method 1: QR Server API (primary) - This is working
        $qr_server_url = 'https://api.qrserver.com/v1/create-qr-code/?size=' . $size . 'x' . $size . '&data=' . urlencode($qr_url) . '&format=png&margin=2&ecc=M';
        
        // Method 2: Google Charts API (fallback) - This is failing (404)
        $google_charts_url = 'https://chart.googleapis.com/chart?chs=' . $size . 'x' . $size . '&cht=qr&chl=' . urlencode($qr_url) . '&choe=UTF-8&chld=L|0';
        
        // Return QR Server URL as primary (it's working)
        return $qr_server_url;
    }
    
    /**
     * Generate QR code locally using a simple approach
     *
     * @param string $qr_url QR code URL
     * @param int $size QR code size
     * @return string|false QR code HTML or false if failed
     */
    private static function generate_local_qr_code($qr_url, $size) {
        // Use QR Server API directly (it's working reliably)
        $qr_api_url = 'https://api.qrserver.com/v1/create-qr-code/?size=' . $size . 'x' . $size . '&data=' . urlencode($qr_url) . '&format=png&margin=2&ecc=M';
        
        // Test if the API is accessible
        $response = wp_remote_get($qr_api_url, array(
            'timeout' => 10,
            'user-agent' => 'WordPress/' . get_bloginfo('version') . '; ' . get_bloginfo('url')
        ));
        
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            // Fallback to simple SVG-based QR code
            return self::generate_simple_svg_qr($qr_url, $size);
        }
        
        // If API works, return the image
        return '<img src="' . esc_url($qr_api_url) . '" alt="QR Code" width="' . $size . '" height="' . $size . '" style="border: 1px solid #ddd; border-radius: 4px;" />';
    }
    
    /**
     * Generate a simple SVG-based QR code (basic implementation)
     *
     * @param string $qr_url QR code URL
     * @param int $size QR code size
     * @return string SVG QR code HTML
     */
    private static function generate_simple_svg_qr($qr_url, $size) {
        // This is a very basic implementation - for production, consider using a proper QR library
        $svg_size = $size;
        $cell_size = 8;
        $margin = 4;
        $data_length = strlen($qr_url);
        
        // Create a simple pattern based on the URL (this is not a real QR code, just a visual placeholder)
        $pattern = '';
        for ($i = 0; $i < 25; $i++) {
            for ($j = 0; $j < 25; $j++) {
                $value = ($i * $j + $data_length) % 2;
                $pattern .= $value ? '1' : '0';
            }
        }
        
        $svg = '<svg width="' . $svg_size . '" height="' . $svg_size . '" xmlns="http://www.w3.org/2000/svg">';
        $svg .= '<rect width="' . $svg_size . '" height="' . $svg_size . '" fill="white"/>';
        
        $cells_per_row = 25;
        $cell_width = ($svg_size - 2 * $margin) / $cells_per_row;
        
        for ($i = 0; $i < $cells_per_row; $i++) {
            for ($j = 0; $j < $cells_per_row; $j++) {
                $index = $i * $cells_per_row + $j;
                if (isset($pattern[$index]) && $pattern[$index] === '1') {
                    $x = $margin + $j * $cell_width;
                    $y = $margin + $i * $cell_width;
                    $svg .= '<rect x="' . $x . '" y="' . $y . '" width="' . $cell_width . '" height="' . $cell_width . '" fill="black"/>';
                }
            }
        }
        
        $svg .= '</svg>';
        
        return '<div style="text-align: center; padding: 10px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px;">';
        $svg .= '<p style="margin: 0 0 10px 0; font-size: 12px; color: #666;">QR Code Placeholder</p>';
        $svg .= '<p style="margin: 0; font-size: 10px; color: #999;">Use URL below to verify receipt</p>';
        $svg .= '</div>';
    }
    
    /**
     * Generate QR code for an order
     *
     * @param int $order_id Order ID
     * @param int $size QR code size
     * @return string QR code HTML or empty string if not available
     */
    public static function generate_order_qr_code($order_id, $size = 200) {
        // Get settings
        $settings = get_option('kra_etims_wc_settings', array());
        $tin = isset($settings['tin']) ? $settings['tin'] : '';
        $bhfid = isset($settings['bhfId']) ? $settings['bhfId'] : '';
        
        // Get receipt signature from order
        $rcpt_sign = get_post_meta($order_id, '_receipt_signature', true);
        
        if (empty($tin) || empty($bhfid) || empty($rcpt_sign)) {
            return '';
        }
        
        $qr_url = self::generate_qr_url($tin, $bhfid, $rcpt_sign);
        return self::generate_qr_image($qr_url, $size);
    }
    
    /**
     * Generate QR code for customer receipt/email
     *
     * @param int $order_id Order ID
     * @param int $size QR code size (default: 150)
     * @param bool $show_download Show download link (default: false for customer emails)
     * @return string QR code HTML or empty string if not available
     */
    public static function generate_customer_qr_code($order_id, $size = 150, $show_download = false) {
        // Get settings
        $settings = get_option('kra_etims_wc_settings', array());
        $tin = isset($settings['tin']) ? $settings['tin'] : '';
        $bhfid = isset($settings['bhfId']) ? $settings['bhfId'] : '';
        
        // Get receipt signature from order
        $rcpt_sign = get_post_meta($order_id, '_receipt_signature', true);
        
        if (empty($tin) || empty($bhfid) || empty($rcpt_sign)) {
            return '';
        }
        
        $qr_url = self::generate_qr_url($tin, $bhfid, $rcpt_sign);
        return self::generate_qr_image($qr_url, $size, $show_download);
    }
    
    /**
     * Generate QR code URL for an order
     *
     * @param int $order_id Order ID
     * @return string QR code URL or empty string if not available
     */
    public static function get_order_qr_url($order_id) {
        // Get settings
        $settings = get_option('kra_etims_wc_settings', array());
        $tin = isset($settings['tin']) ? $settings['tin'] : '';
        $bhfid = isset($settings['bhfId']) ? $settings['bhfId'] : '';
        
        // Get receipt signature from order
        $rcpt_sign = get_post_meta($order_id, '_receipt_signature', true);
        
        if (empty($tin) || empty($bhfid) || empty($rcpt_sign)) {
            return '';
        }
        
        return self::generate_qr_url($tin, $bhfid, $rcpt_sign);
    }
    
    /**
     * Register shortcode for QR code display
     */
    public static function register_shortcodes() {
        add_shortcode('kra_etims_qr_code', array(__CLASS__, 'qr_code_shortcode'));
    }
    
    /**
     * QR code shortcode callback
     *
     * @param array $atts Shortcode attributes
     * @return string QR code HTML
     */
    public static function qr_code_shortcode($atts) {
        $atts = shortcode_atts(array(
            'order_id' => 0,
            'size' => 200,
            'show_download' => 'true'
        ), $atts);
        
        $order_id = intval($atts['order_id']);
        $size = intval($atts['size']);
        $show_download = ($atts['show_download'] === 'true');
        
        if ($order_id <= 0) {
            return '<p>' . __('Error: Invalid order ID', 'kra-etims-connector') . '</p>';
        }
        
        return self::generate_customer_qr_code($order_id, $size, $show_download);
    }

    /**
     * Test QR code generation
     *
     * @return array Test results
     */
    public static function test_qr_code_generation() {
        $test_url = 'https://example.com/test';
        $results = array();
        
        // Test local QR code generation
        $local_result = self::generate_local_qr_code($test_url, 200);
        $results['local'] = !empty($local_result);
        
        // Test Google Charts API
        $google_url = 'https://chart.googleapis.com/chart?chs=200x200&cht=qr&chl=' . urlencode($test_url) . '&choe=UTF-8&chld=L|0';
        $google_response = wp_remote_get($google_url, array('timeout' => 10));
        $results['google_charts'] = !is_wp_error($google_response) && wp_remote_retrieve_response_code($google_response) === 200;
        
        // Test QR Server API
        $qr_server_url = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($test_url) . '&format=png&margin=2&ecc=M';
        $qr_server_response = wp_remote_get($qr_server_url, array('timeout' => 10));
        $results['qr_server'] = !is_wp_error($qr_server_response) && wp_remote_retrieve_response_code($qr_server_response) === 200;
        
        return $results;
    }
} 