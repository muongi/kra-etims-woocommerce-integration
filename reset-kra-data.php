<?php
/**
 * Reset KRA eTims Data
 * 
 * This script clears all KRA eTims data (SIDs and injonge codes) from the database
 * Use this when you want to re-sync categories and products with your API
 * 
 * IMPORTANT: Delete this file after use for security
 */

// Load WordPress
require_once('../../../wp-load.php');

// Check if user is logged in and has admin capabilities
if (!is_user_logged_in() || !current_user_can('manage_options')) {
    die('Unauthorized access');
}

echo "<h1>Reset KRA eTims Data</h1>";
echo "<p>This will clear all SID and injonge code data from your database.</p>";

// Add confirmation check
if (!isset($_GET['confirm']) || $_GET['confirm'] !== 'yes') {
    ?>
    <div style="background: #fff3cd; border: 1px solid #ffc107; padding: 20px; margin: 20px 0; border-radius: 5px;">
        <h2 style="color: #856404;">⚠️ Warning</h2>
        <p><strong>This action will delete:</strong></p>
        <ul>
            <li>All category Server IDs (SID)</li>
            <li>All product injonge codes</li>
            <li>All product SIDs</li>
            <li>All product category SIDs</li>
            <li>All product sync status data</li>
        </ul>
        <p><strong>You will need to re-sync all categories and products after this.</strong></p>
        <p>
            <a href="?confirm=yes" class="button button-primary" style="background: #dc3545; border-color: #dc3545; padding: 10px 20px; text-decoration: none; color: white; display: inline-block; margin-top: 10px;">
                Yes, Reset All KRA eTims Data
            </a>
            <a href="<?php echo admin_url('admin.php?page=kra-etims-sync'); ?>" class="button" style="padding: 10px 20px; margin-left: 10px;">
                Cancel
            </a>
        </p>
    </div>
    <?php
    echo "<p><strong>Note:</strong> This action cannot be undone. Make sure you have a backup.</p>";
    exit;
}

global $wpdb;

echo "<h2>Clearing KRA eTims Data...</h2>";

// 1. Clear Category SIDs
echo "<h3>1. Clearing Category Server IDs (SID)</h3>";
$category_meta_deleted = $wpdb->query(
    "DELETE FROM {$wpdb->termmeta} WHERE meta_key = '_kra_etims_server_id'"
);
echo "<p style='color: green;'>✅ Deleted {$category_meta_deleted} category SID records</p>";

// 2. Clear Product Injonge Codes
echo "<h3>2. Clearing Product Injonge Codes</h3>";
$injonge_code_deleted = $wpdb->query(
    "DELETE FROM {$wpdb->postmeta} WHERE meta_key = '_injonge_code'"
);
echo "<p style='color: green;'>✅ Deleted {$injonge_code_deleted} product injonge code records</p>";

// 3. Clear Product SIDs
echo "<h3>3. Clearing Product Server IDs (SID)</h3>";
$product_sid_deleted = $wpdb->query(
    "DELETE FROM {$wpdb->postmeta} WHERE meta_key = '_injonge_sid'"
);
echo "<p style='color: green;'>✅ Deleted {$product_sid_deleted} product SID records</p>";

// 4. Clear Product Category SIDs
echo "<h3>4. Clearing Product Category SIDs</h3>";
$category_sid_deleted = $wpdb->query(
    "DELETE FROM {$wpdb->postmeta} WHERE meta_key = '_injonge_category_sid'"
);
echo "<p style='color: green;'>✅ Deleted {$category_sid_deleted} product category SID records</p>";

// 5. Clear Product Sync Status
echo "<h3>5. Clearing Product Sync Status</h3>";
$sync_status_deleted = $wpdb->query(
    "DELETE FROM {$wpdb->postmeta} WHERE meta_key IN ('_injonge_status', '_injonge_last_sync', '_injonge_response', '_injonge_error')"
);
echo "<p style='color: green;'>✅ Deleted {$sync_status_deleted} product sync status records</p>";

// 6. Clear API Notes
echo "<h3>6. Clearing API Notes</h3>";
$api_notes_deleted = $wpdb->query(
    "DELETE FROM {$wpdb->postmeta} WHERE meta_key IN ('_api_note', '_api_error_note')"
);
echo "<p style='color: green;'>✅ Deleted {$api_notes_deleted} API note records</p>";

// Summary
echo "<hr>";
echo "<h2 style='color: green;'>✅ Reset Complete!</h2>";
echo "<div style='background: #d1ecf1; border: 1px solid #0c5460; padding: 20px; margin: 20px 0; border-radius: 5px;'>";
echo "<h3>Summary:</h3>";
echo "<ul>";
echo "<li><strong>Category SIDs removed:</strong> {$category_meta_deleted}</li>";
echo "<li><strong>Product injonge codes removed:</strong> {$injonge_code_deleted}</li>";
echo "<li><strong>Product SIDs removed:</strong> {$product_sid_deleted}</li>";
echo "<li><strong>Product category SIDs removed:</strong> {$category_sid_deleted}</li>";
echo "<li><strong>Sync status records removed:</strong> {$sync_status_deleted}</li>";
echo "<li><strong>API notes removed:</strong> {$api_notes_deleted}</li>";
echo "</ul>";
echo "</div>";

echo "<h2>Next Steps:</h2>";
echo "<ol>";
echo "<li><strong>Go to KRA eTims → Sync Items</strong></li>";
echo "<li><strong>Sync Categories</strong> to get new SIDs from your API</li>";
echo "<li><strong>Sync Products</strong> after categories are synced</li>";
echo "<li><strong>Delete this file</strong> (reset-kra-data.php) for security</li>";
echo "</ol>";

echo "<p style='margin-top: 30px;'>";
echo "<a href='" . admin_url('admin.php?page=kra-etims-sync') . "' class='button button-primary' style='padding: 10px 20px;'>Go to Sync Page</a>";
echo "</p>";

echo "<hr>";
echo "<p style='color: red;'><strong>⚠️ SECURITY WARNING:</strong> Delete this file (reset-kra-data.php) immediately after use!</p>";
?>

