# KRA eTims WooCommerce Integration

A comprehensive WordPress plugin that integrates WooCommerce stores with the Kenya Revenue Authority (KRA) eTims system for automated tax reporting and compliance.

## 🎯 Overview

This plugin provides seamless integration between WooCommerce and KRA eTims, enabling:
- **Automated Category Management** with predefined KRA eTims item codes
- **Product Synchronization** with automatic inheritance of tax codes
- **Real-time Order Processing** with KRA eTims receipt generation
- **Customer TIN/PIN Validation** for compliance
- **Flexible API Integration** supporting both HTTP and HTTPS protocols

## 📋 Features

### 🏷️ Category Management
- **Predefined Item Codes**: 60+ KRA eTims item codes across 10 categories
- **Dropdown Selection**: Easy-to-use dropdown menus for item code selection
- **API Synchronization**: Automatic upload to KRA eTims API with SID retrieval
- **Special Categories**: Support for Special and Exempt categories

### 📦 Product Integration
- **Category Inheritance**: Products automatically inherit SID and unspec codes from categories
- **Mandatory Categories**: Ensures all products have proper KRA eTims classification
- **Tax Type Support**: A (Exempt 0%), B (VAT 16%), C (Export 0%), D (Non VAT 0%)
- **Validation**: Comprehensive validation before product saving

### 🛒 Order Processing
- **Real-time Receipts**: Automatic KRA eTims receipt generation
- **Tax Calculations**: Accurate tax calculation based on product tax types
- **Customer TIN Validation**: 11-character alphanumeric TIN/PIN validation
- **Price Handling**: Automatic price formatting and validation

### 👤 Customer Management
- **TIN/PIN Fields**: Customer TIN collection during checkout
- **Admin Integration**: TIN fields in order management
- **Account Integration**: TIN fields in customer accounts
- **Validation**: Format validation and error handling

### 🔧 Technical Features
- **API Flexibility**: Support for both HTTP and HTTPS protocols
- **SSL Compatibility**: Multiple TLS version support
- **Error Handling**: Comprehensive error management and logging
- **Performance**: Optimized database queries and caching

## 🚀 Installation

### Prerequisites
- WordPress 5.0 or higher
- WooCommerce 5.0 or higher
- PHP 7.4 or higher
- SSL certificate (recommended for production)
- **Injonge Etims POS system** for complete integration

### 🏪 **Injonge Etims POS Integration**
This plugin is specifically designed to work with **Injonge Etims POS** system for complete KRA eTims compliance. 

**For Production Setup:**
- Contact **Rhenium Solutions** for complete Injonge Etims POS setup
- Phone: +254721638836
- Email: info@rheniumgroup.co.ke
- Get production-ready API endpoints and configuration

### Installation Steps

1. **Download the Plugin**
   ```bash
   # Clone the repository
   git clone https://github.com/your-username/kra-etims-woocommerce.git
   
   # Or download the ZIP file and extract to wp-content/plugins/
   ```

2. **Activate the Plugin**
   - Go to WordPress Admin → Plugins
   - Find "KRA eTims WooCommerce Integration"
   - Click "Activate"

3. **Configure Settings**
   - Go to WooCommerce → KRA eTims
   - Enter your API credentials and settings (contact Rhenium Solutions for production details)
   - Configure company information

## ⚙️ Configuration

### API Settings

Navigate to **WooCommerce → KRA eTims** to configure:

#### Basic Settings
- **API Base URL**: Your KRA eTims API endpoint
- **API Key**: Your authentication key
- **Company Name**: Your business name (max 20 characters)
- **Protocol**: HTTP or HTTPS

#### Advanced Settings
- **SSL Verification**: Enable/disable SSL verification
- **Timeout**: API request timeout (default: 30 seconds)
- **Retry Attempts**: Number of retry attempts for failed requests

### Category Configuration

1. **Set Up Categories**
   - Go to **Products → Categories**
   - Edit each category
   - Select appropriate KRA eTims Item Code from dropdown
   - Save changes

2. **Available Categories**
   - **Electronics**: 5 item codes for electronic devices
   - **Spare Parts**: 4 item codes for machinery parts
   - **Wheels**: 4 item codes for automotive wheels
   - **Accessories**: 4 item codes for various accessories
   - **Clothes**: 5 item codes for clothing items
   - **Shoes**: 5 item codes for footwear
   - **Food**: 10 item codes for food products
   - **Fast Foods**: 10 item codes for prepared foods
   - **Special**: 2 generic special category codes
   - **Exempt**: 10 specific exemption codes

### Product Configuration

1. **Assign Categories**
   - Edit each product
   - Assign to appropriate category (mandatory)
   - Product will automatically inherit KRA eTims data

2. **Tax Type Selection**
   - Select tax type: A, B, C, or D
   - Tax calculations will be applied automatically

## 📊 Synchronization

### Manual Sync

Use the **KRA eTims → Sync Items** page to:

1. **Sync Categories**
   - Upload categories to KRA eTims API
   - Retrieve Server IDs (SID)
   - View sync status and errors

2. **Sync Products**
   - Upload products with proper category data
   - Only products with complete category setup will be synced
   - View detailed sync reports

### Sync Status

The sync page displays:
- **Category Status**: Item codes, SIDs, and sync readiness
- **Product Status**: Category inheritance and sync eligibility
- **Summary Statistics**: Total counts and sync progress

## 🛒 Usage

### For Customers

1. **Checkout Process**
   - Customers enter TIN/PIN during checkout
   - TIN is validated for 11-character alphanumeric format
   - Receipt is automatically generated with KRA eTims data

2. **Order Confirmation**
   - KRA eTims receipt is displayed on order confirmation
   - Receipt includes QR code and formatted signature
   - Receipt is also sent via email

### For Administrators

1. **Order Management**
   - TIN fields available in order edit pages
   - KRA eTims receipt data visible in order details
   - Manual receipt regeneration if needed

2. **Product Management**
   - Category assignment is mandatory
   - Tax type selection for each product
   - Validation prevents saving incomplete products

3. **Category Management**
   - Item code selection from predefined dropdowns
   - API synchronization status tracking
   - Bulk operations for multiple categories

## 🔧 API Integration

### Endpoints Used

- **`/add_categories`**: Upload category data and retrieve SIDs
- **`/add_products`**: Upload product data with category references
- **Custom Receipt Endpoint**: Generate KRA eTims receipts

### Data Format

#### Category Payload
```json
{
  "category_name": "Electronics",
  "unspec_code": "4111410100",
  "description": "Electronics - Mobile Phones"
}
```

#### Product Payload
```json
{
  "product_name": "Smartphone",
  "unspec_code": "4111410100",
  "category_id": "123",
  "taxid": "B",
  "price": "50000"
}
```

#### Receipt Payload
```json
{
  "custTin": "P051769063X",
  "custNm": "Company Name",
  "itemList": [
    {
      "itemCd": "4111410100",
      "itemClsCd": "4111410100",
      "unitPrice": "50000",
      "qty": "1"
    }
  ],
  "taxblAmtB": "50000",
  "taxAmtB": "8000"
}
```

## 🐛 Troubleshooting

### Common Issues

#### 1. SSL Connection Errors
**Problem**: `cURL error 35: wrong version number`
**Solution**: 
- Check API URL protocol (HTTP vs HTTPS)
- Verify SSL certificate validity
- Try different TLS versions in settings

#### 2. Category Sync Issues
**Problem**: Categories show "Not Set" for item codes
**Solution**:
- Ensure categories have item codes selected
- Check API endpoint connectivity
- Verify API credentials

#### 3. Product Validation Errors
**Problem**: Products can't be saved
**Solution**:
- Assign products to categories
- Ensure categories have item codes and SIDs
- Check tax type selection

#### 4. Receipt Generation Failures
**Problem**: Receipts not generating
**Solution**:
- Verify customer TIN format (11 characters)
- Check product category setup
- Ensure API endpoint is accessible

### Debug Mode

Enable debug mode in WordPress to see detailed error messages:

```php
// Add to wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## 📝 Changelog

### Version 1.0.0
- Initial release
- Category management with predefined item codes
- Product integration with category inheritance
- Order processing with KRA eTims receipt generation
- Customer TIN validation
- API integration with SSL support
- Sync interface for manual synchronization

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📄 License

This project is licensed under the GPL v2 or later - see the [LICENSE](LICENSE) file for details.

## 🆘 Support

For support and questions:
- Create an issue on GitHub
- Check the troubleshooting section
- Review the WordPress error logs

## 🔗 Links

- [KRA eTims Documentation](https://www.kra.go.ke/etims)
- [WooCommerce Documentation](https://docs.woocommerce.com/)
- [WordPress Plugin Development](https://developer.wordpress.org/plugins/)

## 👨‍💻 Developer Information

**Rhenium Group Limited**
- **Phone**: +254721638836
- **Email**: info@rheniumgroup.co.ke
- **Website**: [Rhenium Group Limited](https://rheniumgroup.co.ke)

### KRA eTims API Integration
For KRA eTims API connection details and integration support, please contact:
- **Rhenium Group Limited** at +254721638836 or info@rheniumgroup.co.ke
- They provide complete KRA eTims API integration services
- Custom API endpoints and connection setup available

---

**Note**: This plugin is designed for use with the Kenya Revenue Authority eTims system. Ensure compliance with local tax regulations and KRA requirements. For API integration services, contact Rhenium Group Limited.
