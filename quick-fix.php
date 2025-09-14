<?php
/**
 * Quick Fix Script for API Connection Issues
 * 
 * This script helps you quickly test and fix API connection problems
 */

// Load WordPress
require_once dirname(__FILE__) . '/../../../wp-config.php';

echo "<h1>Quick Fix for API Connection Issues</h1>\n";

// Get current settings
$settings = get_option('kra_etims_wc_settings', array());
$current_url = isset($settings['custom_api_live_url']) ? $settings['custom_api_live_url'] : 'https://your-production-api.com/injongeReceipts';

echo "<p><strong>Current API URL:</strong> $current_url</p>\n";

// Test different configurations
$test_configs = array(
    'Original' => 'https://api.injonge.co.ke:8443/injonge/ObrReceipts',
    'HTTPS Standard Port' => 'https://api.injonge.co.ke/injonge/ObrReceipts',
    'HTTP Standard Port' => 'http://api.injonge.co.ke/injonge/ObrReceipts',
    'Port 443' => 'https://api.injonge.co.ke:443/injonge/ObrReceipts',
    'Port 8080' => 'https://api.injonge.co.ke:8080/injonge/ObrReceipts',
    'Port 9000' => 'https://api.injonge.co.ke:9000/injonge/ObrReceipts'
);

$test_data = array(
    'test' => true,
    'timestamp' => time(),
    'message' => 'Quick fix test'
);

echo "<h2>Testing Different Configurations</h2>\n";

$working_configs = array();

foreach ($test_configs as $name => $url) {
    echo "<h3>Testing: $name</h3>\n";
    echo "<p><strong>URL:</strong> $url</p>\n";
    
    // Test with cURL
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
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
            echo "<p style='color: red;'>❌ Failed: $curl_error (Error #$curl_errno)</p>\n";
        } else {
            echo "<p style='color: green;'>✅ Success! HTTP Code: $http_code</p>\n";
            $working_configs[] = array('name' => $name, 'url' => $url, 'code' => $http_code);
        }
    }
    
    echo "<hr>\n";
}

// Show working configurations
if (!empty($working_configs)) {
    echo "<h2>✅ Working Configurations Found</h2>\n";
    echo "<ul>\n";
    foreach ($working_configs as $config) {
        echo "<li><strong>{$config['name']}</strong>: {$config['url']} (HTTP {$config['code']})</li>\n";
    }
    echo "</ul>\n";
    
    echo "<h3>Quick Fix Options:</h3>\n";
    echo "<p>1. <strong>Update your plugin settings</strong> with one of the working URLs above</p>\n";
    echo "<p>2. <strong>Enable 'Force HTTPS'</strong> in the plugin settings</p>\n";
    echo "<p>3. <strong>Set a custom port</strong> if needed</p>\n";
    
    // Auto-update option
    if (isset($_GET['auto_update']) && !empty($working_configs)) {
        $best_config = $working_configs[0]; // Use the first working config
        $settings['custom_api_live_url'] = $best_config['url'];
        update_option('kra_etims_wc_settings', $settings);
        echo "<p style='color: green;'><strong>✅ Auto-updated settings to: {$best_config['url']}</strong></p>\n";
    } else {
        echo "<p><a href='?auto_update=1' style='background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 3px;'>Auto-Update to Best Working Configuration</a></p>\n";
    }
} else {
    echo "<h2>❌ No Working Configurations Found</h2>\n";
    echo "<p>All tested configurations failed. This suggests:</p>\n";
    echo "<ul>\n";
    echo "<li>Your hosting provider is blocking outbound connections</li>\n";
    echo "<li>The API server is not accessible from your server</li>\n";
    echo "<li>There's a network configuration issue</li>\n";
    echo "</ul>\n";
    
    echo "<h3>Next Steps:</h3>\n";
    echo "<p>1. <strong>Contact your hosting provider</strong> to allow outbound HTTPS connections</p>\n";
    echo "<p>2. <strong>Contact the API provider</strong> to verify the correct endpoint</p>\n";
    echo "<p>3. <strong>Try using a proxy service</strong> like Cloudflare Workers</p>\n";
    echo "<p>4. <strong>Check if the API server is running</strong> and accessible</p>\n";
}

echo "<h2>Manual Configuration</h2>\n";
echo "<p>If you want to manually set a specific URL, update your plugin settings:</p>\n";
echo "<ol>\n";
echo "<li>Go to <strong>WooCommerce > KRA eTims Settings</strong></li>\n";
echo "<li>Find the <strong>Production API URL</strong> field</li>\n";
echo "<li>Enter one of the working URLs above</li>\n";
echo "<li>Save the settings</li>\n";
echo "</ol>\n";

echo "<h2>Additional Troubleshooting</h2>\n";
echo "<p><a href='test-specific-connection.php' style='background: #d63638; color: white; padding: 10px 20px; text-decoration: none; border-radius: 3px;'>Run Detailed Connection Test</a></p>\n";
echo "<p><a href='debug-api-connection.php' style='background: #00a32a; color: white; padding: 10px 20px; text-decoration: none; border-radius: 3px;'>Run Full Debug Test</a></p>\n";
?> 