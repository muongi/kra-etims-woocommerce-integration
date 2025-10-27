<?php
/**
 * Database Prefix Test & Diagnostic Tool
 * 
 * This script checks your WordPress database prefix and KRA eTims data
 * Run this to verify everything is working correctly on different servers
 * 
 * Usage: Access via browser at: your-site.com/wp-content/plugins/kra-etims-woocommerce-main/test-database-prefix.php
 * 
 * IMPORTANT: Delete this file after use for security
 */

// Load WordPress
require_once('../../../wp-load.php');

// Check if user is logged in and has admin capabilities
if (!is_user_logged_in() || !current_user_can('manage_options')) {
    die('Unauthorized access. Please log in as an administrator.');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>KRA eTims - Database Prefix Diagnostic</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, sans-serif;
            max-width: 1200px;
            margin: 50px auto;
            padding: 20px;
            background: #f0f0f1;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        h1 {
            color: #1d2327;
            border-bottom: 2px solid #2271b1;
            padding-bottom: 10px;
        }
        h2 {
            color: #2271b1;
            margin-top: 30px;
        }
        .info-box {
            background: #f0f6fc;
            border-left: 4px solid #2271b1;
            padding: 15px;
            margin: 15px 0;
        }
        .success {
            background: #d1f0d1;
            border-left: 4px solid #00a32a;
        }
        .warning {
            background: #fff3cd;
            border-left: 4px solid #dba617;
        }
        .error {
            background: #ffd4d4;
            border-left: 4px solid #d63638;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        table th,
        table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        table th {
            background: #f9f9f9;
            font-weight: bold;
        }
        code {
            background: #f0f0f0;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
        .delete-warning {
            background: #d63638;
            color: white;
            padding: 15px;
            border-radius: 5px;
            margin-top: 30px;
            text-align: center;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 KRA eTims - Database Prefix Diagnostic</h1>
        
        <?php
        global $wpdb;
        
        // Get database prefix
        $prefix = $wpdb->prefix;
        $db_name = DB_NAME;
        $db_user = DB_USER;
        ?>
        
        <h2>📊 Database Information</h2>
        <div class="info-box">
            <table>
                <tr>
                    <th>Property</th>
                    <th>Value</th>
                </tr>
                <tr>
                    <td><strong>Database Prefix:</strong></td>
                    <td><code><?php echo esc_html($prefix); ?></code></td>
                </tr>
                <tr>
                    <td><strong>Database Name:</strong></td>
                    <td><code><?php echo esc_html($db_name); ?></code></td>
                </tr>
                <tr>
                    <td><strong>Database User:</strong></td>
                    <td><code><?php echo esc_html($db_user); ?></code></td>
                </tr>
                <tr>
                    <td><strong>termmeta Table:</strong></td>
                    <td><code><?php echo esc_html($wpdb->termmeta); ?></code></td>
                </tr>
                <tr>
                    <td><strong>postmeta Table:</strong></td>
                    <td><code><?php echo esc_html($wpdb->postmeta); ?></code></td>
                </tr>
            </table>
        </div>
        
        <h2>🗂️ KRA eTims Data Status</h2>
        
        <?php
        // Check Category SIDs
        $category_sid_count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->termmeta} WHERE meta_key = %s",
                '_kra_etims_server_id'
            )
        );
        
        // Check Product Injonge Codes
        $injonge_code_count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = %s",
                '_injonge_code'
            )
        );
        
        // Check Product SIDs
        $product_sid_count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = %s",
                '_injonge_sid'
            )
        );
        
        // Check Sync Status
        $sync_status_count = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key IN ('_injonge_status', '_injonge_last_sync', '_injonge_response', '_injonge_error')"
        );
        
        // Check API Notes
        $api_notes_count = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key IN ('_api_note', '_api_error_note')"
        );
        
        $total_records = $category_sid_count + $injonge_code_count + $product_sid_count + $sync_status_count + $api_notes_count;
        ?>
        
        <div class="info-box <?php echo $total_records > 0 ? 'success' : 'warning'; ?>">
            <h3>Total KRA eTims Records: <?php echo $total_records; ?></h3>
        </div>
        
        <table>
            <tr>
                <th>Data Type</th>
                <th>Table</th>
                <th>Meta Key</th>
                <th>Count</th>
            </tr>
            <tr>
                <td>Category Server IDs (SID)</td>
                <td><code><?php echo esc_html($wpdb->termmeta); ?></code></td>
                <td><code>_kra_etims_server_id</code></td>
                <td><strong><?php echo $category_sid_count; ?></strong></td>
            </tr>
            <tr>
                <td>Product Injonge Codes</td>
                <td><code><?php echo esc_html($wpdb->postmeta); ?></code></td>
                <td><code>_injonge_code</code></td>
                <td><strong><?php echo $injonge_code_count; ?></strong></td>
            </tr>
            <tr>
                <td>Product SIDs</td>
                <td><code><?php echo esc_html($wpdb->postmeta); ?></code></td>
                <td><code>_injonge_sid</code></td>
                <td><strong><?php echo $product_sid_count; ?></strong></td>
            </tr>
            <tr>
                <td>Sync Status Records</td>
                <td><code><?php echo esc_html($wpdb->postmeta); ?></code></td>
                <td><code>_injonge_status, etc.</code></td>
                <td><strong><?php echo $sync_status_count; ?></strong></td>
            </tr>
            <tr>
                <td>API Notes</td>
                <td><code><?php echo esc_html($wpdb->postmeta); ?></code></td>
                <td><code>_api_note, _api_error_note</code></td>
                <td><strong><?php echo $api_notes_count; ?></strong></td>
            </tr>
        </table>
        
        <h2>✅ Database Connection Test</h2>
        <?php
        // Test database connection and permissions
        $can_select = false;
        $can_delete = false;
        $errors = array();
        
        // Test SELECT permission
        try {
            $test_select = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->termmeta} LIMIT 1");
            $can_select = true;
        } catch (Exception $e) {
            $errors[] = "SELECT test failed: " . $e->getMessage();
        }
        
        // Test DELETE permission (on a non-existent key)
        try {
            $test_delete = $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM {$wpdb->termmeta} WHERE meta_key = %s LIMIT 0",
                    '_test_nonexistent_key_12345'
                )
            );
            $can_delete = true;
        } catch (Exception $e) {
            $errors[] = "DELETE test failed: " . $e->getMessage();
        }
        
        if ($wpdb->last_error) {
            $errors[] = "Database error: " . $wpdb->last_error;
        }
        ?>
        
        <div class="info-box <?php echo empty($errors) ? 'success' : 'error'; ?>">
            <table>
                <tr>
                    <th>Permission</th>
                    <th>Status</th>
                </tr>
                <tr>
                    <td>SELECT (Read)</td>
                    <td><?php echo $can_select ? '✅ Allowed' : '❌ Denied'; ?></td>
                </tr>
                <tr>
                    <td>DELETE (Remove)</td>
                    <td><?php echo $can_delete ? '✅ Allowed' : '❌ Denied'; ?></td>
                </tr>
            </table>
            
            <?php if (!empty($errors)): ?>
                <h4 style="color: #d63638;">Errors Detected:</h4>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo esc_html($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        
        <h2>🔧 Troubleshooting Steps</h2>
        <div class="info-box">
            <h4>If Clear buttons are not working on your server:</h4>
            <ol>
                <li><strong>Check WordPress Error Log:</strong> Look for log entries starting with "KRA eTims:" in your <code>wp-content/debug.log</code> file</li>
                <li><strong>Enable WordPress Debug:</strong> Add these lines to <code>wp-config.php</code>:
                    <pre style="background: #f0f0f0; padding: 10px; border-radius: 4px;">define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);</pre>
                </li>
                <li><strong>Check Browser Console:</strong> Open browser developer tools (F12) and check for JavaScript errors</li>
                <li><strong>Verify User Permissions:</strong> Make sure you're logged in as an administrator with <code>manage_woocommerce</code> capability</li>
                <li><strong>Database Prefix Match:</strong> Ensure the prefix shown above (<code><?php echo esc_html($prefix); ?></code>) matches your actual database tables</li>
            </ol>
        </div>
        
        <h2>📝 Notes</h2>
        <div class="info-box">
            <ul>
                <li>The plugin now automatically detects and uses your database prefix</li>
                <li>All clear operations are logged to the WordPress error log</li>
                <li>The clear functions use prepared statements for security</li>
                <li>Before/after counts are logged for verification</li>
            </ul>
        </div>
        
        <div class="delete-warning">
            ⚠️ SECURITY WARNING: Delete this file (test-database-prefix.php) after use!
        </div>
        
        <p style="text-align: center; margin-top: 20px;">
            <a href="<?php echo admin_url('admin.php?page=kra-etims-sync'); ?>" class="button button-primary">
                Go to Sync Page
            </a>
        </p>
    </div>
</body>
</html>

