<?php
/**
 * Server Files Update Helper
 * 
 * This script helps identify and fix any remaining log() method calls
 * that might be causing the fatal error on the server.
 */

echo "<h1>Server Files Update Helper</h1>";

// Check if we're in WordPress context
if (!function_exists('wp_remote_get')) {
    echo "<p style='color: red;'>❌ WordPress functions not available. Please run this from within WordPress.</p>";
    exit;
}

// Files to check for log() calls
$files_to_check = array(
    'includes/class-kra-etims-wc-api.php',
    'includes/class-kra-etims-wc-order-handler.php',
    'includes/class-kra-etims-wc.php',
    'includes/admin/class-kra-etims-wc-admin.php'
);

echo "<h2>Checking for log() method calls</h2>";

$found_log_calls = false;

foreach ($files_to_check as $file) {
    $file_path = __DIR__ . '/' . $file;
    
    if (file_exists($file_path)) {
        $content = file_get_contents($file_path);
        
        // Check for log() method calls
        if (preg_match_all('/->log\s*\(/i', $content, $matches, PREG_OFFSET_CAPTURE)) {
            echo "<p style='color: red;'>❌ Found " . count($matches[0]) . " log() calls in $file</p>";
            $found_log_calls = true;
            
            // Show the lines with log() calls
            $lines = explode("\n", $content);
            foreach ($matches[0] as $match) {
                $line_number = substr_count(substr($content, 0, $match[1]), "\n") + 1;
                $line_content = isset($lines[$line_number - 1]) ? trim($lines[$line_number - 1]) : 'Unknown line';
                echo "<p style='margin-left: 20px; font-family: monospace; background: #f0f0f0; padding: 5px;'>Line $line_number: " . htmlspecialchars($line_content) . "</p>";
            }
        } else {
            echo "<p style='color: green;'>✅ No log() calls found in $file</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠️ File not found: $file</p>";
    }
}

if (!$found_log_calls) {
    echo "<h2>✅ All files are clean!</h2>";
    echo "<p>No log() method calls found in the plugin files.</p>";
    echo "<p>The error might be due to:</p>";
    echo "<ul>";
    echo "<li>Server has an older version of the plugin</li>";
    echo "<li>Files weren't properly uploaded to the server</li>";
    echo "<li>Server cache needs to be cleared</li>";
    echo "</ul>";
} else {
    echo "<h2>🔧 Fix Required</h2>";
    echo "<p>Found log() method calls that need to be removed. Please:</p>";
    echo "<ol>";
    echo "<li>Upload the latest version of the plugin files to your server</li>";
    echo "<li>Clear any server caches</li>";
    echo "<li>Deactivate and reactivate the plugin</li>";
    echo "</ol>";
}

echo "<h2>Manual Fix Instructions</h2>";
echo "<p>If you need to manually fix the files on your server:</p>";
echo "<ol>";
echo "<li>Download the latest plugin files from your local machine</li>";
echo "<li>Upload them to your server, replacing the old files</li>";
echo "<li>Make sure to upload these specific files:";
echo "<ul>";
echo "<li>includes/class-kra-etims-wc-api.php</li>";
echo "<li>includes/class-kra-etims-wc-order-handler.php</li>";
echo "<li>includes/class-kra-etims-wc.php</li>";
echo "<li>includes/admin/class-kra-etims-wc-admin.php</li>";
echo "</ul>";
echo "</li>";
echo "<li>Clear any caching plugins or server caches</li>";
echo "<li>Deactivate and reactivate the plugin</li>";
echo "</ol>";

echo "<h2>Quick Fix Script</h2>";
echo "<p>You can also run this command on your server to remove any remaining log() calls:</p>";
echo "<pre style='background: #f0f0f0; padding: 10px; border-radius: 5px;'>";
echo "find /path/to/your/plugin -name '*.php' -exec sed -i 's/->log(/\/\/->log(/g' {} \\;";
echo "</pre>";
echo "<p><strong>Note:</strong> Replace '/path/to/your/plugin' with the actual path to your plugin directory.</p>";
?> 