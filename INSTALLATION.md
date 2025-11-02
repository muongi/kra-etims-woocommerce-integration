# Installation Guide - KRA eTims WooCommerce Plugin

This guide provides detailed step-by-step instructions for installing and configuring the KRA eTims WooCommerce Plugin.

## 📋 Prerequisites

Before installing the plugin, ensure your system meets these requirements:

### System Requirements
- **WordPress**: 5.0 or higher
- **WooCommerce**: 5.0 or higher
- **PHP**: 7.4 or higher
- **MySQL**: 5.6 or higher
- **SSL Certificate**: Recommended for production use
- **Injonge Etims POS system**: Required for complete integration

### Required Extensions
- **cURL**: For API communication
- **JSON**: For data processing
- **OpenSSL**: For SSL/TLS connections

### 🏪 **Injonge Etims POS Integration**
This plugin is specifically designed to work with **Injonge Etims POS** system for complete KRA eTims compliance and seamless integration.

**For Production Setup:**
- Contact **Rhenium Group** for complete Injonge Etims POS setup
- Phone: +254721638836
- Email: info@rheniumgroup.co.ke
- Get production-ready API endpoints and configuration
- Professional installation and configuration support

## 🚀 Installation Methods

### Method 1: Manual Installation (Recommended)

1. **Download the Plugin**
   - Download the ZIP file from your plugin source
   - Or obtain the plugin files from your provider

2. **Upload to WordPress**
   - Extract the plugin files
   - Upload the `kra-etims-woocommerce-main` folder to `/wp-content/plugins/`
   - Ensure the folder structure is: `/wp-content/plugins/kra-etims-woocommerce-main/`

3. **Activate the Plugin**
   - Go to WordPress Admin → Plugins
   - Find "KRA eTims WooCommerce Plugin"
   - Click "Activate"

### Method 2: WordPress Admin Upload

1. **Download ZIP**
   - Download the plugin ZIP file from GitHub releases

2. **Upload via Admin**
   - Go to WordPress Admin → Plugins → Add New
   - Click "Upload Plugin"
   - Choose the ZIP file
   - Click "Install Now"
   - Click "Activate Plugin"

## ⚙️ Initial Configuration

### Step 1: Access Plugin Settings

1. Navigate to **WooCommerce → KRA eTims** in your WordPress admin
2. You'll see the main configuration page

### Step 2: Configure API Settings

#### Basic Configuration
```
API Base URL: https://your-api-domain.com/api
API Key: your-api-key-here
Company Name: Your Business Name (max 20 characters)
Environment: Production/Development
```

#### Advanced Settings
```
SSL Verification: Enabled (recommended)
Timeout: 30 seconds
Retry Attempts: 3
Protocol: HTTPS (recommended)
```

### Step 3: Test API Connection

1. Click "Test Connection" button
2. Verify successful connection
3. Check for any error messages

## 🏷️ Category Setup

### Step 1: Configure Product Categories

1. Go to **Products → Categories**
2. Edit each existing category or create new ones
3. For each category:
   - Select appropriate KRA eTims Item Code from dropdown
   - Save changes

### Step 2: Available Item Code Categories

| Category | Item Codes | Description |
|----------|------------|-------------|
| Electronics | 5 codes | Mobile phones, computers, etc. |
| Spare Parts | 4 codes | Machinery and equipment parts |
| Wheels | 4 codes | Automotive wheels and tires |
| Accessories | 4 codes | Various accessories |
| Clothes | 5 codes | Clothing and apparel |
| Shoes | 5 codes | Footwear and shoes |
| Food | 10 codes | Food products and groceries |
| Fast Foods | 10 codes | Prepared and fast foods |
| Special | 2 codes | Special category codes |
| Exempt | 10 codes | Tax exemption codes |

### Step 3: Sync Categories to API

1. Go to **KRA eTims → Sync Items**
2. Click "Sync Categories to API"
3. Monitor the sync progress
4. Verify SIDs are retrieved for each category

## 📦 Product Configuration

### Step 1: Assign Categories to Products

1. Go to **Products → All Products**
2. Edit each product
3. Assign to appropriate category (mandatory)
4. Product will automatically inherit:
   - Unspec code from category
   - SID from category

### Step 2: Configure Tax Types

For each product, select the appropriate tax type:

| Tax Type | Rate | Description |
|----------|------|-------------|
| A | 0% | Exempt |
| B | 16% | VAT |
| C | 0% | Export |
| D | 0% | Non VAT |

### Step 3: Validate Products

1. Ensure all products have:
   - Category assigned
   - Tax type selected
   - Valid pricing

2. Products without proper setup will show validation errors

## 👤 Customer TIN Configuration

### Step 1: Enable TIN Fields

The plugin automatically adds TIN fields to:
- Checkout process
- Order management
- Customer accounts

### Step 2: TIN Validation

Customer TINs must be:
- Exactly 11 characters
- Alphanumeric format
- Example: `P051769063X`

## 🛒 Order Processing Setup

### Step 1: Configure Order Status

1. Go to **WooCommerce → Settings → Orders**
2. Ensure order completion triggers are set correctly
3. Orders marked as "Completed" will automatically generate KRA eTims receipts

### Step 2: Test Order Processing

1. Create a test order
2. Complete the order
3. Verify KRA eTims receipt generation
4. Check receipt formatting and data

## 📊 Sync Interface Setup

### Step 1: Access Sync Page

1. Go to **KRA eTims → Sync Items**
2. Review category and product status
3. Identify items needing attention

### Step 2: Manual Synchronization

1. **Sync Categories**
   - Click "Sync Categories to API"
   - Monitor progress
   - Verify SID retrieval

2. **Sync Products**
   - Click "Sync Products to API"
   - Only products with complete category setup will sync
   - Review sync results

## 🔧 Advanced Configuration

### SSL/TLS Settings

If experiencing SSL connection issues:

1. **Check Protocol**
   - Try HTTP instead of HTTPS
   - Verify SSL certificate validity

2. **TLS Version**
   - Plugin automatically tries multiple TLS versions
   - Check server compatibility

### Error Handling

1. **Enable Debug Mode**
   ```php
   // Add to wp-config.php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   ```

2. **Check Error Logs**
   - Review WordPress debug log
   - Check server error logs

## ✅ Verification Checklist

After installation, verify these items:

### ✅ Basic Setup
- [ ] Plugin activated successfully
- [ ] API settings configured
- [ ] API connection test passed
- [ ] Company name set (max 20 characters)

### ✅ Category Setup
- [ ] All categories have item codes selected
- [ ] Categories synced to API
- [ ] SIDs retrieved for all categories
- [ ] Sync status shows "Ready" for categories

### ✅ Product Setup
- [ ] All products assigned to categories
- [ ] Tax types selected for all products
- [ ] Products inherit category data correctly
- [ ] No validation errors on products

### ✅ Order Processing
- [ ] Test order created successfully
- [ ] KRA eTims receipt generated
- [ ] Receipt displays correctly
- [ ] Customer TIN validation working

### ✅ Customer Experience
- [ ] TIN field appears during checkout
- [ ] TIN validation works correctly
- [ ] Receipt displays on order confirmation
- [ ] Receipt sent via email

## 🚨 Common Installation Issues

### Issue 1: Plugin Not Appearing
**Solution**: Check file permissions and ensure proper folder structure

### Issue 2: API Connection Failed
**Solution**: Verify API credentials and network connectivity

### Issue 3: Categories Not Syncing
**Solution**: Ensure item codes are selected and API endpoint is accessible

### Issue 4: Products Not Saving
**Solution**: Assign categories and select tax types for all products

### Issue 5: SSL Connection Errors
**Solution**: Try HTTP protocol or check SSL certificate configuration

## 📞 Support

If you encounter issues during installation:

1. Check the troubleshooting section in the main README
2. Review WordPress error logs
3. Create an issue on GitHub with detailed error information
4. Include system information and error messages

## 👨‍💻 Developer Support

For KRA eTims API integration and technical support:
- **Rhenium Group Limited**
- **Phone**: +254721638836
- **Email**: info@rheniumgroup.co.ke
- **Website**: [Rhenium Group Limited](https://rheniumgroup.co.ke)

### API Integration Services
Rhenium Group Limited provides:
- Complete KRA eTims API integration setup
- Custom API endpoint configuration
- Connection testing and troubleshooting
- Ongoing technical support

---

**Note**: This installation guide assumes basic familiarity with WordPress and WooCommerce administration. For additional help, refer to the main documentation or contact Rhenium Group Limited for API integration services. 