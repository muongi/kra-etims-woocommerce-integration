<?php
/**
 * Quick Fix for log() method calls
 * 
 * This script removes any remaining log() method calls that might be causing the fatal error.
 * Run this script on your server to fix the issue.
 */

echo "<h1>Quick Fix for log() Method Calls</h1>";

// Files to fix
$files_to_fix = array(
    'includes/class-kra-etims-wc-api.php',
    'includes/class-kra-etims-wc-order-handler.php',
    'includes/class-kra-etims-wc.php',
    'includes/admin/class-kra-etims-wc-admin.php'
);

$total_fixed = 0;

foreach ($files_to_fix as $file) {
    $file_path = __DIR__ . '/' . $file;
    
    if (file_exists($file_path)) {
        $content = file_get_contents($file_path);
        $original_content = $content;
        
        // Remove log() method calls
        $content = preg_replace('/->log\s*\([^)]*\);/', '//->log() call removed;', $content);
        $content = preg_replace('/->log\s*\([^)]*\)/', '//->log() call removed', $content);
        
        // Count how many were fixed
        $fixed_count = substr_count($original_content, '->log(') - substr_count($content, '->log(');
        
        if ($fixed_count > 0) {
            // Write the fixed content back
            if (file_put_contents($file_path, $content)) {
                echo "<p style='color: green;'>✅ Fixed $fixed_count log() calls in $file</p>";
                $total_fixed += $fixed_count;
            } else {
                echo "<p style='color: red;'>❌ Failed to write fixes to $file</p>";
            }
        } else {
            echo "<p style='color: blue;'>ℹ️ No log() calls found in $file</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠️ File not found: $file</p>";
    }
}

if ($total_fixed > 0) {
    echo "<h2>✅ Fix Complete!</h2>";
    echo "<p>Removed $total_fixed log() method calls from the plugin files.</p>";
    echo "<p><strong>Next steps:</strong></p>";
    echo "<ol>";
    echo "<li>Clear any caching plugins or server caches</li>";
    echo "<li>Deactivate and reactivate the plugin</li>";
    echo "<li>Test the plugin functionality</li>";
    echo "</ol>";
} else {
    echo "<h2>ℹ️ No Fixes Needed</h2>";
    echo "<p>No log() method calls were found in the plugin files.</p>";
    echo "<p>The error might be due to:</p>";
    echo "<ul>";
    echo "<li>Server cache needs to be cleared</li>";
    echo "<li>Plugin needs to be deactivated and reactivated</li>";
    echo "<li>Files weren't properly uploaded to the server</li>";
    echo "</ul>";
}

echo "<h2>Manual Steps to Complete the Fix</h2>";
echo "<ol>";
echo "<li><strong>Clear Caches:</strong> Clear any WordPress caching plugins, server caches, or CDN caches</li>";
echo "<li><strong>Deactivate Plugin:</strong> Go to WordPress admin → Plugins → KRA eTims Integration → Deactivate</li>";
echo "<li><strong>Reactivate Plugin:</strong> Go to WordPress admin → Plugins → KRA eTims Integration → Activate</li>";
echo "<li><strong>Test:</strong> Try processing an order to see if the error is resolved</li>";
echo "</ol>";

echo "<h2>If the Error Persists</h2>";
echo "<p>If you're still getting the error after these steps:</p>";
echo "<ol>";
echo "<li>Upload the complete latest version of the plugin from your local machine</li>";
echo "<li>Make sure all files are properly uploaded (especially the API class files)</li>";
echo "<li>Check if your server has any file upload restrictions</li>";
echo "<li>Contact your hosting provider if the issue continues</li>";
echo "</ol>";

echo "<p><strong>Note:</strong> After running this fix, you can delete this file for security.</p>";
?> 