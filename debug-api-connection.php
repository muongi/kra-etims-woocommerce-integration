<?php
/**
 * Debug API Connection Script
 * 
 * This script helps diagnose API connection issues on cPanel servers
 * Run this script to test connectivity and identify the problem
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/../../../');
}

// Load WordPress
require_once ABSPATH . 'wp-config.php';

// Get current settings
$settings = get_option('kra_etims_wc_settings', array());
$environment = isset($settings['environment']) ? $settings['environment'] : 'development';
$custom_api_url = isset($settings['custom_api_live_url']) ? $settings['custom_api_live_url'] : 'https://your-production-api.com/injongeReceipts';

echo "<h2>KRA eTims API Connection Debug</h2>\n";
echo "<pre>\n";

echo "=== Current Settings ===\n";
echo "Environment: " . $environment . "\n";
echo "Custom API URL: " . $custom_api_url . "\n\n";

// Test 1: Basic connectivity test
echo "=== Test 1: Basic Connectivity ===\n";
$parsed_url = parse_url($custom_api_url);
$host = $parsed_url['host'];
$port = isset($parsed_url['port']) ? $parsed_url['port'] : (strpos($custom_api_url, 'https://') === 0 ? 443 : 80);

echo "Host: " . $host . "\n";
echo "Port: " . $port . "\n";

// Test if we can resolve the host
$ip = gethostbyname($host);
echo "Resolved IP: " . $ip . "\n";

if ($ip === $host) {
    echo "❌ ERROR: Could not resolve hostname\n";
} else {
    echo "✅ Hostname resolved successfully\n";
}

// Test 2: Socket connection test
echo "\n=== Test 2: Socket Connection Test ===\n";
$socket = @fsockopen($host, $port, $errno, $errstr, 10);
if ($socket) {
    echo "✅ Socket connection successful\n";
    fclose($socket);
} else {
    echo "❌ Socket connection failed: $errstr ($errno)\n";
}

// Test 3: cURL test
echo "\n=== Test 3: cURL Test ===\n";
if (function_exists('curl_init')) {
    echo "✅ cURL is available\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $custom_api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    $curl_errno = curl_errno($ch);
    
    curl_close($ch);
    
    if ($curl_errno) {
        echo "❌ cURL Error: $curl_error (Error #$curl_errno)\n";
    } else {
        echo "✅ cURL request successful\n";
        echo "HTTP Code: $http_code\n";
    }
} else {
    echo "❌ cURL is not available\n";
}

// Test 4: WordPress HTTP API test
echo "\n=== Test 4: WordPress HTTP API Test ===\n";
$args = array(
    'timeout' => 10,
    'sslverify' => false,
    'method' => 'HEAD'
);

$response = wp_remote_request($custom_api_url, $args);

if (is_wp_error($response)) {
    echo "❌ WordPress HTTP API Error: " . $response->get_error_message() . "\n";
} else {
    $response_code = wp_remote_retrieve_response_code($response);
    echo "✅ WordPress HTTP API request successful\n";
    echo "HTTP Code: $response_code\n";
}

// Test 5: Server environment info
echo "\n=== Test 5: Server Environment ===\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "\n";
echo "Allow URL Fopen: " . (ini_get('allow_url_fopen') ? 'Yes' : 'No') . "\n";
echo "cURL Enabled: " . (function_exists('curl_init') ? 'Yes' : 'No') . "\n";

// Test 6: Firewall/Proxy detection
echo "\n=== Test 6: Network Configuration ===\n";
$proxy_vars = array('HTTP_PROXY', 'HTTPS_PROXY', 'http_proxy', 'https_proxy');
$proxy_found = false;

foreach ($proxy_vars as $var) {
    if (isset($_SERVER[$var])) {
        echo "Proxy detected: $var = " . $_SERVER[$var] . "\n";
        $proxy_found = true;
    }
}

if (!$proxy_found) {
    echo "No proxy configuration detected\n";
}

// Test 7: DNS resolution test
echo "\n=== Test 7: DNS Resolution Test ===\n";
$dns_records = dns_get_record($host, DNS_ANY);
if ($dns_records) {
    echo "✅ DNS records found:\n";
    foreach ($dns_records as $record) {
        echo "  - Type: " . $record['type'] . ", Target: " . (isset($record['target']) ? $record['target'] : $record['ip']) . "\n";
    }
} else {
    echo "❌ No DNS records found\n";
}

echo "\n=== Recommendations ===\n";
echo "1. Check if your cPanel server allows outbound connections to port 9015\n";
echo "2. Verify if there's a firewall blocking the connection\n";
echo "3. Check if your hosting provider allows custom API calls\n";
echo "4. Consider using a different port or HTTPS instead of HTTP\n";
echo "5. Contact your hosting provider if the issue persists\n";

echo "</pre>\n";
?> 