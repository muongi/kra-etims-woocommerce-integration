# API Documentation - KRA eTims WooCommerce Plugin

This document provides comprehensive information about the API integration between WooCommerce and the KRA eTims system through **Injonge Etims POS**.

## 🏪 **Injonge Etims POS Integration**

This plugin is specifically designed to work with **Injonge Etims POS** system for complete KRA eTims compliance.

**For Production API Details:**
- Contact **Rhenium Group** for Injonge Etims POS API endpoints
- Phone: +254721638836
- Email: info@rheniumgroup.co.ke
- Get production-ready API configuration and credentials

## 🔗 API Overview

The KRA eTims WooCommerce Plugin communicates with Injonge Etims POS API endpoints to:
- Upload category data and retrieve Server IDs (SIDs)
- Upload product data with category references
- Generate KRA eTims compliant receipts
- Handle customer TIN validation
- Synchronize with Kenya Revenue Authority systems

## 📡 API Endpoints

### Base URL Configuration

The API base URL is configured in the plugin settings:
```
WooCommerce → KRA eTims → API Settings → API Base URL
```

### Available Endpoints

| Endpoint | Method | Purpose | Authentication |
|----------|--------|---------|----------------|
| `/add_categories` | POST | Upload categories and get SIDs | API Key |
| `/add_products` | POST | Upload products with category data | API Key |
| Custom Receipt Endpoint | POST | Generate KRA eTims receipts | API Key |

## 🔐 Authentication

### API Key Authentication

All API requests require an API key in the headers:

```http
Authorization: Bearer YOUR_API_KEY
Content-Type: application/json
```

### Configuration

Set your API key in the plugin settings:
```
WooCommerce → KRA eTims → API Settings → API Key
```

## 📊 Data Formats

### 1. Category Upload (`/add_categories`)

#### Request Payload
```json
{
  "category_name": "Electronics",
  "unspec_code": "4111410100",
  "description": "Electronics - Mobile Phones",
  "api_key": "your-api-key"
}
```

#### Response Format
```json
{
  "success": true,
  "data": {
    "sid": "12345",
    "id": "12345",
    "message": "Category uploaded successfully"
  }
}
```

#### Error Response
```json
{
  "success": false,
  "error": "Invalid unspec code",
  "code": "INVALID_UNSPEC"
}
```

### 2. Product Upload (`/add_products`)

#### Request Payload
```json
{
  "product_name": "iPhone 13",
  "unspec_code": "4111410100",
  "category_id": "12345",
  "taxid": "B",
  "price": "150000",
  "image": "https://example.com/image.jpg",
  "api_key": "your-api-key"
}
```

#### Response Format
```json
{
  "success": true,
  "data": {
    "product_id": "67890",
    "message": "Product uploaded successfully"
  }
}
```

#### Error Response
```json
{
  "success": false,
  "error": "Category not found",
  "code": "CATEGORY_NOT_FOUND"
}
```

### 3. Receipt Generation (Custom Endpoint)

#### Request Payload
```json
{
  "custTin": "P051769063X",
  "custNm": "ABC Company Ltd",
  "modrNm": "Your Company",
  "regrNm": "Your Company",
  "trdeNm": "Your Company",
  "adrs": "123 Main Street, Nairobi",
  "topMsg": "Thank you for your purchase",
  "itemList": [
    {
      "itemCd": "4111410100",
      "itemClsCd": "4111410100",
      "itemNm": "iPhone 13",
      "unitPrice": "150000",
      "qty": "1",
      "totalPrice": "150000"
    }
  ],
  "taxblAmtA": "0",
  "taxblAmtB": "150000",
  "taxblAmtC": "0",
  "taxblAmtD": "0",
  "taxAmtA": "0",
  "taxAmtB": "24000",
  "taxAmtC": "0",
  "taxAmtD": "0",
  "totalAmount": "174000",
  "api_key": "your-api-key"
}
```

#### Response Format
```json
{
  "success": true,
  "data": {
    "receipt_number": "RCP001234",
    "qr_code": "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAA...",
    "signature": "ABCD-1234-EFGH-5678",
    "timestamp": "2024-08-17T10:30:00Z"
  }
}
```

#### Error Response
```json
{
  "success": false,
  "error": "Invalid customer TIN",
  "code": "INVALID_TIN"
}
```

## 🔧 Implementation Examples

### PHP cURL Example

#### Category Upload
```php
function upload_category_to_api($category_data) {
    $api_url = 'https://your-api-domain.com/api/add_categories';
    $api_key = 'your-api-key';
    
    $payload = array(
        'category_name' => $category_data['name'],
        'unspec_code' => $category_data['unspec_code'],
        'description' => $category_data['description'],
        'api_key' => $api_key
    );
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 200) {
        return json_decode($response, true);
    }
    
    return false;
}
```

#### Product Upload
```php
function upload_product_to_api($product_data) {
    $api_url = 'https://your-api-domain.com/api/add_products';
    $api_key = 'your-api-key';
    
    $payload = array(
        'product_name' => $product_data['name'],
        'unspec_code' => $product_data['unspec_code'],
        'category_id' => $product_data['category_id'],
        'taxid' => $product_data['taxid'],
        'price' => $product_data['price'],
        'image' => $product_data['image'],
        'api_key' => $api_key
    );
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 200) {
        return json_decode($response, true);
    }
    
    return false;
}
```

#### Receipt Generation
```php
function generate_receipt($order_data) {
    $api_url = 'https://your-api-domain.com/api/generate_receipt';
    $api_key = 'your-api-key';
    
    $payload = array(
        'custTin' => $order_data['customer_tin'],
        'custNm' => $order_data['customer_name'],
        'modrNm' => $order_data['company_name'],
        'regrNm' => $order_data['company_name'],
        'trdeNm' => $order_data['company_name'],
        'adrs' => $order_data['company_address'],
        'topMsg' => $order_data['top_message'],
        'itemList' => $order_data['items'],
        'taxblAmtA' => $order_data['taxable_amount_a'],
        'taxblAmtB' => $order_data['taxable_amount_b'],
        'taxblAmtC' => $order_data['taxable_amount_c'],
        'taxblAmtD' => $order_data['taxable_amount_d'],
        'taxAmtA' => $order_data['tax_amount_a'],
        'taxAmtB' => $order_data['tax_amount_b'],
        'taxAmtC' => $order_data['tax_amount_c'],
        'taxAmtD' => $order_data['tax_amount_d'],
        'totalAmount' => $order_data['total_amount'],
        'api_key' => $api_key
    );
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 200) {
        return json_decode($response, true);
    }
    
    return false;
}
```

### JavaScript/AJAX Example

#### Category Upload
```javascript
function uploadCategory(categoryData) {
    const apiUrl = 'https://your-api-domain.com/api/add_categories';
    const apiKey = 'your-api-key';
    
    const payload = {
        category_name: categoryData.name,
        unspec_code: categoryData.unspecCode,
        description: categoryData.description,
        api_key: apiKey
    };
    
    fetch(apiUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${apiKey}`
        },
        body: JSON.stringify(payload)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('Category uploaded successfully:', data.data);
        } else {
            console.error('Upload failed:', data.error);
        }
    })
    .catch(error => {
        console.error('Network error:', error);
    });
}
```

## 📋 Data Validation

### Category Data Validation

| Field | Type | Required | Validation |
|-------|------|----------|------------|
| category_name | string | Yes | Max 100 characters |
| unspec_code | string | Yes | Must be valid KRA eTims code |
| description | string | No | Max 500 characters |

### Product Data Validation

| Field | Type | Required | Validation |
|-------|------|----------|------------|
| product_name | string | Yes | Max 200 characters |
| unspec_code | string | Yes | Must be valid KRA eTims code |
| category_id | string | Yes | Must exist in system |
| taxid | string | Yes | A, B, C, or D |
| price | number | Yes | Must be positive |
| image | string | No | Valid URL format |

### Receipt Data Validation

| Field | Type | Required | Validation |
|-------|------|----------|------------|
| custTin | string | Yes | 11 alphanumeric characters |
| custNm | string | Yes | Max 100 characters |
| modrNm | string | Yes | Max 20 characters |
| itemList | array | Yes | Non-empty array |
| totalAmount | number | Yes | Must be positive |

## 🔄 Error Handling

### HTTP Status Codes

| Code | Description | Action |
|------|-------------|--------|
| 200 | Success | Process response data |
| 400 | Bad Request | Check request payload |
| 401 | Unauthorized | Verify API key |
| 403 | Forbidden | Check permissions |
| 404 | Not Found | Verify endpoint URL |
| 500 | Server Error | Contact API provider |

### Error Response Format

```json
{
  "success": false,
  "error": "Error description",
  "code": "ERROR_CODE",
  "details": {
    "field": "Additional error details"
  }
}
```

### Common Error Codes

| Code | Description | Solution |
|------|-------------|----------|
| INVALID_API_KEY | API key is invalid | Check API key configuration |
| INVALID_UNSPEC | Unspec code not found | Verify KRA eTims code |
| CATEGORY_NOT_FOUND | Category doesn't exist | Sync categories first |
| INVALID_TIN | Customer TIN format invalid | Check TIN format (11 chars) |
| MISSING_REQUIRED_FIELD | Required field missing | Check request payload |
| SERVER_ERROR | Internal server error | Contact API provider |

## 🔒 Security Considerations

### API Key Security
- Store API keys securely
- Use HTTPS for all API communications
- Rotate API keys regularly
- Never expose API keys in client-side code

### Data Validation
- Validate all input data
- Sanitize data before sending to API
- Implement rate limiting
- Log API requests for monitoring

### SSL/TLS
- Use TLS 1.2 or higher
- Verify SSL certificates
- Implement certificate pinning if needed
- Handle SSL errors gracefully

## 📊 Monitoring and Logging

### Request Logging
```php
function log_api_request($endpoint, $payload, $response, $status_code) {
    $log_entry = array(
        'timestamp' => date('Y-m-d H:i:s'),
        'endpoint' => $endpoint,
        'payload' => $payload,
        'response' => $response,
        'status_code' => $status_code
    );
    
    error_log(json_encode($log_entry));
}
```

### Response Monitoring
```php
function monitor_api_response($response) {
    if (!$response['success']) {
        // Log error
        error_log('API Error: ' . $response['error']);
        
        // Send notification
        send_error_notification($response['error']);
    }
}
```

## 🧪 Testing

### Test Endpoints
```bash
# Test category upload
curl -X POST https://your-api-domain.com/api/add_categories \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -d '{
    "category_name": "Test Category",
    "unspec_code": "4111410100",
    "description": "Test description"
  }'

# Test product upload
curl -X POST https://your-api-domain.com/api/add_products \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -d '{
    "product_name": "Test Product",
    "unspec_code": "4111410100",
    "category_id": "12345",
    "taxid": "B",
    "price": "1000"
  }'
```

### Test Data
Use these test values for development:
- **Test TIN**: `P000000004G`
- **Test Unspec Code**: `4111410100`
- **Test Category ID**: `12345`

## 📞 Support

For API-related issues:
1. Check error logs for detailed information
2. Verify API credentials and endpoints
3. Test with provided examples
4. Contact API provider for server-side issues

## 👨‍💻 API Integration Support

For KRA eTims API integration services and support:
- **Rhenium Group Limited**
- **Phone**: +254721638836
- **Email**: info@rheniumgroup.co.ke
- **Website**: [Rhenium Group Limited](https://rheniumgroup.co.ke)

### Services Provided
- Complete KRA eTims API setup and configuration
- Custom API endpoint development
- Connection testing and troubleshooting
- API integration consulting
- Ongoing technical support

---

**Note**: This documentation assumes you have a custom API that handles the KRA eTims integration. For API integration services and connection details, contact Rhenium Group Limited. Adjust endpoints and data formats according to your specific API implementation. 