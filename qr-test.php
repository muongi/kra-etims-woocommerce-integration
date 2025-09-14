<?php
/**
 * Simple QR Code Test Script
 * 
 * This script tests QR code generation without requiring WordPress
 */

echo "<h1>QR Code Generation Test</h1>";

// Test URLs
$test_urls = array(
    'https://example.com/test',
    'https://etims.kra.go.ke/common/link/etims/receipt/indexEtimsReceiptData?Data=900008987600ABPC7KI36NZKW2TR'
);

// Test different QR code services
$services = array(
    'QR Server API' => 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={DATA}&format=png&margin=2&ecc=M',
    'Google Charts API' => 'https://chart.googleapis.com/chart?chs=200x200&cht=qr&chl={DATA}&choe=UTF-8&chld=L|0',
    'GoQR.me API' => 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={DATA}&format=png&margin=2&ecc=H'
);

echo "<h2>Testing QR Code Services</h2>";

foreach ($services as $service_name => $service_url) {
    echo "<h3>$service_name</h3>";
    
    foreach ($test_urls as $test_url) {
        $url = str_replace('{DATA}', urlencode($test_url), $service_url);
        
        echo "<p><strong>Test URL:</strong> " . htmlspecialchars($test_url) . "</p>";
        echo "<p><strong>Service URL:</strong> " . htmlspecialchars($url) . "</p>";
        
        // Test the URL
        $context = stream_context_create(array(
            'http' => array(
                'timeout' => 10,
                'user_agent' => 'QR-Test/1.0'
            )
        ));
        
        $headers = get_headers($url, 1, $context);
        
        if ($headers && strpos($headers[0], '200') !== false) {
            echo "<p style='color: green;'>✅ Working</p>";
            echo "<img src='" . htmlspecialchars($url) . "' alt='QR Code' style='border: 1px solid #ccc; margin: 10px;' />";
        } else {
            echo "<p style='color: red;'>❌ Failed</p>";
            if ($headers) {
                echo "<p>Response: " . htmlspecialchars($headers[0]) . "</p>";
            }
        }
        
        echo "<hr>";
    }
}

echo "<h2>Manual Test Links</h2>";
echo "<p>You can also test these URLs manually in your browser:</p>";

foreach ($services as $service_name => $service_url) {
    foreach ($test_urls as $test_url) {
        $url = str_replace('{DATA}', urlencode($test_url), $service_url);
        echo "<p><strong>$service_name:</strong> <a href='" . htmlspecialchars($url) . "' target='_blank'>" . htmlspecialchars($url) . "</a></p>";
    }
}

echo "<h2>Recommendations</h2>";
echo "<ul>";
echo "<li>If QR Server API works, the plugin should display QR codes correctly</li>";
echo "<li>If Google Charts API works, it will be used as a fallback</li>";
echo "<li>If both fail, check your server's network connectivity</li>";
echo "<li>Consider using a local QR code library for better reliability</li>";
echo "</ul>";
?> 