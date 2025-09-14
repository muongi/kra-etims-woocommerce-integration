<?php
/**
 * Simple API Connection Test
 * 
 * Run this script to test the API connection
 * Access via: yourdomain.com/wp-content/plugins/kra-etims-woocommerce-main/test-connection.php
 */

// Load WordPress
require_once dirname(__FILE__) . '/../../../wp-config.php';

// Get settings
$settings = get_option('kra_etims_wc_settings', array());
$custom_api_url = isset($settings['custom_api_live_url']) ? $settings['custom_api_live_url'] : 'https://your-production-api.com/injongeReceipts';

echo "<h1>KRA eTims API Connection Test</h1>\n";
echo "<p><strong>Current API URL:</strong> $custom_api_url</p>\n";

// Test data
$test_data = array(
    'test' => true,
    'timestamp' => time(),
    'message' => 'Connection test from cPanel server'
);

echo "<h2>Testing Connection...</h2>\n";

try {
    // Test 1: Basic cURL test
    echo "<h3>Test 1: Direct cURL Test</h3>\n";
    
    if (!function_exists('curl_init')) {
        echo "<p style='color: red;'>❌ cURL is not available on this server</p>\n";
    } else {
        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => $custom_api_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($test_data),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Accept: application/json'
            )
        ));
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        $curl_errno = curl_errno($ch);
        
        curl_close($ch);
        
        if ($curl_errno) {
            echo "<p style='color: red;'>❌ cURL Error: $curl_error (Error #$curl_errno)</p>\n";
        } else {
            echo "<p style='color: green;'>✅ cURL request completed</p>\n";
            echo "<p><strong>HTTP Code:</strong> $http_code</p>\n";
            echo "<p><strong>Response:</strong> " . htmlspecialchars(substr($response, 0, 500)) . "...</p>\n";
        }
    }
    
    // Test 2: WordPress HTTP API test
    echo "<h3>Test 2: WordPress HTTP API Test</h3>\n";
    
    $args = array(
        'method' => 'POST',
        'timeout' => 30,
        'headers' => array(
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ),
        'body' => json_encode($test_data),
        'sslverify' => false
    );
    
    $response = wp_remote_post($custom_api_url, $args);
    
    if (is_wp_error($response)) {
        echo "<p style='color: red;'>❌ WordPress HTTP API Error: " . $response->get_error_message() . "</p>\n";
    } else {
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        echo "<p style='color: green;'>✅ WordPress HTTP API request completed</p>\n";
        echo "<p><strong>HTTP Code:</strong> $response_code</p>\n";
        echo "<p><strong>Response:</strong> " . htmlspecialchars(substr($response_body, 0, 500)) . "...</p>\n";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Exception: " . $e->getMessage() . "</p>\n";
}

echo "<h2>Troubleshooting Tips</h2>\n";
echo "<ul>\n";
echo "<li>If you see 'Connection refused', your hosting provider may be blocking outbound connections to port 9015</li>\n";
echo "<li>Try changing the API URL to use HTTPS instead of HTTP</li>\n";
echo "<li>Contact your hosting provider to whitelist the IP address 141.147.88.85</li>\n";
echo "<li>Check if your hosting provider allows custom API calls</li>\n";
echo "<li>Consider using a different port or domain for your API</li>\n";
echo "</ul>\n";

echo "<h2>Next Steps</h2>\n";
echo "<p>1. Run the debug script: <a href='debug-api-connection.php'>debug-api-connection.php</a></p>\n";
echo "<p>2. Check your WordPress error logs for more details</p>\n";
echo "<p>3. Contact your hosting provider with the error details</p>\n";
?> 