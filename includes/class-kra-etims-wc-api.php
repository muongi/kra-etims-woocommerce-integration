<?php
/**
 * API class
 *
 * @package KRA_eTims_WC
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * API class
 */
class KRA_eTims_WC_API {
    /**
     * API base URL
     *
     * @var string
     */
    private $api_base_url;

    /**
     * Custom API URL
     *
     * @var string
     */
    private $custom_api_url;

    /**
     * API credentials
     *
     * @var array
     */
    private $credentials;

    /**
     * API settings
     *
     * @var array
     */
    private $settings;



    /**
     * Constructor
     */
    public function __construct() {
        $this->settings = get_option('kra_etims_wc_settings', array());
        
        // Set API base URL based on environment
        $this->set_api_environment();
        
        // Set custom API URL
        $this->set_custom_api_url();
    }
    
    /**
     * Set API environment
     * 
     * Sets the API base URL based on the selected environment (development or production)
     */
    private function set_api_environment() {
        $environment = isset($this->settings['environment']) ? $this->settings['environment'] : 'development';
        
        if ($environment === 'production') {
            $this->api_base_url = 'https://etims-api.kra.go.ke/etims-api';
        } else {
            $this->api_base_url = 'https://etims-api-sbx.kra.go.ke';
        }
    }

    /**
     * Set custom API URL
     * 
     * Sets the custom API URL based on the selected environment
     */
    private function set_custom_api_url() {
        $environment = isset($this->settings['environment']) ? $this->settings['environment'] : 'development';
        
        if ($environment === 'production') {
            $this->custom_api_url = isset($this->settings['custom_api_live_url']) ? $this->settings['custom_api_live_url'] : 'http://your-production-api.com/injongeReceipts';
        } else {
            $this->custom_api_url = isset($this->settings['custom_api_live_url']) ? $this->settings['custom_api_live_url'] : 'http://your-production-api.com/injongeReceipts';
        }
        
        // Don't enforce HTTPS - let user choose HTTP or HTTPS
        // Remove any protocol enforcement logic
        
        // Handle custom port configuration
        if (isset($this->settings['custom_api_port']) && !empty($this->settings['custom_api_port'])) {
            $parsed_url = parse_url($this->custom_api_url);
            if ($parsed_url) {
                $scheme = isset($parsed_url['scheme']) ? $parsed_url['scheme'] : 'http';
                $host = $parsed_url['host'];
                $path = isset($parsed_url['path']) ? $parsed_url['path'] : '';
                $port = $this->settings['custom_api_port'];
                
                $this->custom_api_url = $scheme . '://' . $host . ':' . $port . $path;
            }
        }
    }



    /**
     * Make API request
     *
     * @param string $endpoint API endpoint
     * @param array $data Request data
     * @param string $method HTTP method
     * @return array Response data
     */
    private function make_request($endpoint, $data = array(), $method = 'POST') {
        $url = $this->api_base_url . $endpoint;
        
        // Prepare request args
        $args = array(
            'method' => $method,
            'timeout' => 30,
            'redirection' => 5,
            'httpversion' => '1.1',
            'blocking' => true,
            'headers' => array(
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ),
            'body' => json_encode($data),
            'cookies' => array(),
        );
        
        // Make request
        $response = wp_remote_request($url, $args);
        
        // Check for errors
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            throw new Exception($error_message);
        }
        
        // Get response code
        $response_code = wp_remote_retrieve_response_code($response);
        
        // Get response body
        $response_body = wp_remote_retrieve_body($response);
        $response_data = json_decode($response_body, true);
        
        // Check for error response
        if ($response_code < 200 || $response_code >= 300) {
            $error_message = isset($response_data['message']) ? $response_data['message'] : 'Unknown error';
            throw new Exception($error_message, $response_code);
        }
        
        return $response_data;
    }

    /**
     * Send receipt data to custom API
     *
     * @param array $receipt_data Receipt data including TIN, branch ID, customer details, and tax details
     * @return array Response data
     */
    public function send_to_custom_api($receipt_data) {
        try {
            // Make request to custom API
            $response = $this->make_custom_request($receipt_data);
            
            return $response;
        } catch (Exception $e) {
            throw new Exception(__('Failed to send receipt data to custom API: ', 'kra-etims-integration') . $e->getMessage(), $e->getCode());
        }
    }

    /**
     * Make custom API request
     *
     * @param array $data Request data
     * @param string $method HTTP method
     * @param string $custom_url Custom URL (optional)
     * @return array Response data
     */
    private function make_custom_request($data = array(), $method = 'POST', $custom_url = null) {
        $url = $custom_url ?: $this->custom_api_url;
        
        // Enhanced debugging
        $this->log_debug_info('Custom API Request', array(
            'url' => $url,
            'method' => $method,
            'data_size' => strlen(json_encode($data)),
            'timestamp' => current_time('mysql')
        ));
        
        // Prepare request args with enhanced error handling
        $args = array(
            'method' => $method,
            'timeout' => 60, // Increased timeout for cPanel servers
            'redirection' => 5,
            'httpversion' => '1.1',
            'blocking' => true,
            'headers' => array(
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'User-Agent' => 'KRA-eTims-WooCommerce/1.0.1',
            ),
            'body' => json_encode($data),
            'cookies' => array(),
            'sslverify' => false, // Disable SSL verification for flexibility
            'http_errors' => false,
        );
        
        // Try WordPress HTTP API first
        $response = wp_remote_request($url, $args);
        
        // Check for WordPress HTTP API errors
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            $error_code = $response->get_error_code();
            
            $this->log_debug_info('WordPress HTTP API Error', array(
                'error_code' => $error_code,
                'error_message' => $error_message,
                'url' => $url
            ));
            
            // Try cURL as fallback with enhanced SSL handling
            $curl_response = $this->make_curl_request($url, $data, $method);
            if ($curl_response !== false) {
                return $curl_response;
            }
            
            throw new Exception($error_message);
        }
        
        // Get response code
        $response_code = wp_remote_retrieve_response_code($response);
        
        // Get response body
        $response_body = wp_remote_retrieve_body($response);
        $response_data = json_decode($response_body, true);
        
        $this->log_debug_info('API Response', array(
            'response_code' => $response_code,
            'response_size' => strlen($response_body),
            'url' => $url
        ));
        
        // Check for error response
        if ($response_code < 200 || $response_code >= 300) {
            $error_message = isset($response_data['message']) ? $response_data['message'] : 'HTTP Error: ' . $response_code;
            throw new Exception($error_message, $response_code);
        }
        
        return $response_data;
    }
    
    /**
     * Make cURL request as fallback
     *
     * @param string $url Request URL
     * @param array $data Request data
     * @param string $method HTTP method
     * @return array|false Response data or false on failure
     */
    private function make_curl_request($url, $data = array(), $method = 'POST') {
        if (!function_exists('curl_init')) {
            return false;
        }
        
        $ch = curl_init();
        
        // Parse URL to determine protocol
        $parsed_url = parse_url($url);
        $is_https = ($parsed_url['scheme'] === 'https');
        
        // Basic cURL options
        $curl_options = array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Accept: application/json',
                'User-Agent: KRA-eTims-WooCommerce/1.0.1'
            ),
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => json_encode($data),
        );
        
        // SSL configuration - be flexible and don't enforce specific settings
        if ($is_https) {
            // For HTTPS, use minimal SSL settings to avoid conflicts
            $curl_options[CURLOPT_SSL_VERIFYPEER] = false;
            $curl_options[CURLOPT_SSL_VERIFYHOST] = false;
            
            // Try with default SSL settings first
            curl_setopt_array($ch, $curl_options);
            $response = curl_exec($ch);
            $curl_error = curl_error($ch);
            $curl_errno = curl_errno($ch);
            
            // If default fails, try different SSL versions
            if ($curl_errno) {
                $ssl_versions = array(
                    CURL_SSLVERSION_DEFAULT,
                    CURL_SSLVERSION_TLSv1_2,
                    CURL_SSLVERSION_TLSv1_1,
                    CURL_SSLVERSION_TLSv1_0
                );
                
                foreach ($ssl_versions as $ssl_version) {
                    $curl_options[CURLOPT_SSLVERSION] = $ssl_version;
                    curl_setopt_array($ch, $curl_options);
                    
                    $response = curl_exec($ch);
                    $curl_error = curl_error($ch);
                    $curl_errno = curl_errno($ch);
                    
                    if (!$curl_errno) {
                        break; // Success, exit loop
                    }
                    
                    $this->log_debug_info('cURL SSL Version Attempt', array(
                        'ssl_version' => $ssl_version,
                        'curl_errno' => $curl_errno,
                        'curl_error' => $curl_error,
                        'url' => $url
                    ));
                }
            }
        } else {
            // HTTP request - no SSL options needed
            curl_setopt_array($ch, $curl_options);
            $response = curl_exec($ch);
            $curl_error = curl_error($ch);
            $curl_errno = curl_errno($ch);
        }
        
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($curl_errno) {
            $this->log_debug_info('cURL Error', array(
                'curl_errno' => $curl_errno,
                'curl_error' => $curl_error,
                'url' => $url,
                'is_https' => $is_https
            ));
            return false;
        }
        
        $response_data = json_decode($response, true);
        
        if ($http_code < 200 || $http_code >= 300) {
            $error_message = isset($response_data['message']) ? $response_data['message'] : 'HTTP Error: ' . $http_code;
            throw new Exception($error_message, $http_code);
        }
        
        return $response_data;
    }
    
    /**
     * Log debug information
     *
     * @param string $type Debug type
     * @param array $data Debug data
     */
    private function log_debug_info($type, $data) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('KRA eTims Debug [' . $type . ']: ' . json_encode($data));
        }
    }

    /**
     * Test connection to custom API
     *
     * @return array Test result with status and details
     */
    public function test_custom_connection() {
        try {
            // Make a test request
            $test_data = array(
                'test' => true,
                'timestamp' => current_time('timestamp'),
                'environment' => isset($this->settings['environment']) ? $this->settings['environment'] : 'development'
            );
            
            $response = $this->make_custom_request($test_data);
            
            return array(
                'success' => true,
                'message' => 'Connection successful',
                'response' => $response
            );
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => $e->getCode()
            );
        }
    }
    
    /**
     * Test API URL and protocol
     *
     * @param string $url URL to test
     * @return array Test result
     */
    public function test_api_url($url = null) {
        $test_url = $url ?: $this->custom_api_url;
        
        try {
            // Parse URL
            $parsed_url = parse_url($test_url);
            if (!$parsed_url) {
                return array(
                    'success' => false,
                    'message' => 'Invalid URL format'
                );
            }
            
            $scheme = $parsed_url['scheme'];
            $host = $parsed_url['host'];
            $port = isset($parsed_url['port']) ? $parsed_url['port'] : ($scheme === 'https' ? 443 : 80);
            
            // Test basic connectivity
            $connection = @fsockopen($host, $port, $errno, $errstr, 10);
            if (!$connection) {
                return array(
                    'success' => false,
                    'message' => "Cannot connect to $host:$port - $errstr ($errno)"
                );
            }
            fclose($connection);
            
            // Test with cURL
            if (function_exists('curl_init')) {
                $ch = curl_init();
                curl_setopt_array($ch, array(
                    CURLOPT_URL => $test_url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 10,
                    CURLOPT_CONNECTTIMEOUT => 5,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
                    CURLOPT_NOBODY => true,
                    CURLOPT_FOLLOWLOCATION => true
                ));
                
                $response = curl_exec($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curl_error = curl_error($ch);
                $curl_errno = curl_errno($ch);
                curl_close($ch);
                
                if ($curl_errno) {
                    return array(
                        'success' => false,
                        'message' => "cURL Error: $curl_error ($curl_errno)",
                        'details' => array(
                            'scheme' => $scheme,
                            'host' => $host,
                            'port' => $port,
                            'url' => $test_url
                        )
                    );
                }
                
                return array(
                    'success' => true,
                    'message' => "Connection successful (HTTP $http_code)",
                    'details' => array(
                        'scheme' => $scheme,
                        'host' => $host,
                        'port' => $port,
                        'http_code' => $http_code,
                        'url' => $test_url
                    )
                );
            }
            
            return array(
                'success' => true,
                'message' => "Basic connectivity test passed",
                'details' => array(
                    'scheme' => $scheme,
                    'host' => $host,
                    'port' => $port,
                    'url' => $test_url
                )
            );
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => $e->getMessage()
            );
        }
    }
    
    /**
     * Send product to API
     *
     * @param array $product_data Product data
     * @param string $action Action type (create or update)
     * @return array Response data
     */
    public function send_product_to_api($product_data, $action = 'create') {
        try {
            // Get API base URL
            $api_base_url = isset($this->settings['api_base_url']) ? $this->settings['api_base_url'] : '';
            
            if (empty($api_base_url)) {
                throw new Exception('API base URL is not configured. Please set it in the plugin settings.');
            }
            
            // Build endpoint URL
            $endpoint = '/update_items';
            $url = rtrim($api_base_url, '/') . $endpoint;
            
            // Log the request
            $this->log_debug_info('Product API Request', array(
                'url' => $url,
                'action' => $action,
                'product_data' => $product_data
            ));
            
            // Make request
            $response = $this->make_custom_request($product_data, 'POST', $url);
            
            // Log the response
            $this->log_debug_info('Product API Response', array(
                'response' => $response
            ));
            
            return $response;
            
        } catch (Exception $e) {
            throw new Exception(__('Failed to send product to API: ', 'kra-etims-integration') . $e->getMessage(), $e->getCode());
        }
    }
    
    /**
     * Send category to API
     *
     * @param array $category_data Category data
     * @return array Response data
     */
    public function send_category_to_api($category_data) {
        try {
            // Get API base URL
            $api_base_url = isset($this->settings['api_base_url']) ? $this->settings['api_base_url'] : '';
            
            if (empty($api_base_url)) {
                throw new Exception('API base URL is not configured. Please set it in the plugin settings.');
            }
            
            // Build endpoint URL
            $endpoint = '/add_categories';
            $url = rtrim($api_base_url, '/') . $endpoint;
            
            // Log the request
            $this->log_debug_info('Category API Request', array(
                'url' => $url,
                'category_data' => $category_data
            ));
            
            // Make request
            $response = $this->make_custom_request($category_data, 'POST', $url);
            
            // Log the response
            $this->log_debug_info('Category API Response', array(
                'response' => $response
            ));
            
            return $response;
            
        } catch (Exception $e) {
            throw new Exception(__('Failed to send category to API: ', 'kra-etims-integration') . $e->getMessage(), $e->getCode());
        }
    }
}
