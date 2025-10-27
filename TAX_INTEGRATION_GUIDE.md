# WooCommerce Tax Integration with KRA eTims

## 🎉 What's New

Your KRA eTims plugin now **fully integrates with WooCommerce's native tax system**! This means:

- ✅ Taxes are automatically calculated by WooCommerce at checkout
- ✅ Prices are **tax-inclusive** (1000 KES includes VAT)
- ✅ KRA tax types sync with WooCommerce tax classes
- ✅ Customers see correct tax breakdown
- ✅ Reports show proper tax amounts
- ✅ Full KRA compliance

---

## 📋 KRA Tax Types & WooCommerce Tax Classes

The plugin automatically creates these tax rates:

| KRA Type | Tax Class | Rate | Description | Example Use |
|----------|-----------|------|-------------|-------------|
| **B** | VAT 16% (Tax B) | 16% | Standard VAT | Most products |
| **A** | Exempt (Tax A) | 0% | Tax exempt goods | Basic foods, medical supplies |
| **C** | Export (Tax C) | 0% | Export goods | International sales |
| **D** | Non-VAT (Tax D) | 0% | Non-VAT items | Certain services |

---

## 🚀 Automatic Setup (First Time)

When you activate or update the plugin, it **automatically**:

1. ✅ Enables WooCommerce tax calculations
2. ✅ Sets prices to **tax-inclusive** mode
3. ✅ Creates 4 KRA tax classes
4. ✅ Creates 5 tax rates (including standard)
5. ✅ Configures tax display settings

**You don't need to do anything!** The taxes are ready to use.

---

## 🛍️ How to Assign Tax Types to Products

### Method 1: Individual Product

1. Go to **Products → Edit Product**
2. Scroll to **Product Data → General** tab
3. Find two fields:
   - **Tax Class**: Select WooCommerce tax class
   - **KRA Tax Type**: Select A, B, C, or D

4. Click **Update**

**Note:** When you select KRA Tax Type, the WooCommerce Tax Class automatically syncs!

### Method 2: Bulk Edit

1. Go to **Products → All Products**
2. Select multiple products (checkboxes)
3. Choose **Bulk Actions → Edit**
4. Set **Tax class** dropdown
5. Click **Update**

---

## 💡 How Tax Works Now

### Before (Old System):
```
Product Price: 1000 KES
Plugin calculates: 862.07 taxable + 137.93 tax
Customer pays: 1000 KES (no tax shown at checkout)
```

### After (New System):
```
Product Price: 1000 KES (tax-inclusive)
WooCommerce shows: 862.07 subtotal + 137.93 VAT (16%)
Customer pays: 1000 KES
Receipt shows proper tax breakdown ✅
```

---

## 🔍 View Tax Information

### In Product List

A new **KRA Tax** column shows each product's tax type:
- 🔵 **B - VAT 16%** (blue)
- 🟢 **A - Exempt** (green)
- 🟣 **C - Export** (purple)
- 🔴 **D - Non-VAT** (red)

### In Orders

Orders now show:
- Subtotal (without tax)
- Tax amount
- Total (with tax)

### In KRA API Submission

The plugin now sends **actual WooCommerce taxes** to your custom API:
- More accurate calculations
- Matches what customer paid
- Proper tax breakdown by type

---

## 📊 Example Scenarios

### Scenario 1: Electronics Store (VAT Products)

```php
Product: Laptop - 116,000 KES
KRA Tax Type: B (VAT 16%)

At Checkout:
- Subtotal: 100,000 KES
- VAT (16%): 16,000 KES
- Total: 116,000 KES

Sent to KRA API:
{
  "taxTyCd": "B",
  "taxblAmt": 100000,
  "taxAmt": 16000,
  "totAmt": 116000
}
```

### Scenario 2: Grocery Store (Mixed Tax Types)

```php
Product 1: Rice - 200 KES (Tax A - Exempt)
Product 2: Soda - 116 KES (Tax B - VAT 16%)

At Checkout:
- Rice: 200 KES (0% tax)
- Soda: 100 KES + 16 KES VAT
- Total: 316 KES

Tax Breakdown:
- taxblAmtA: 200 (exempt)
- taxblAmtB: 100 (VAT)
- taxAmtA: 0
- taxAmtB: 16
```

---

## 🛠️ Configuration Options

### Tax Settings (Already Configured)

Go to **WooCommerce → Settings → Tax** to view:

- ✅ **Enable tax rates**: YES
- ✅ **Prices include tax**: YES
- ✅ **Calculate tax based on**: Customer billing address
- ✅ **Display prices in shop**: Including tax
- ✅ **Display prices in cart**: Including tax

### Customization

If you need to adjust tax rates:

1. Go to **WooCommerce → Settings → Tax**
2. Select the tax class tab (e.g., "VAT 16% (Tax B)")
3. Click on the rate to edit
4. Change rate or add country-specific rules
5. Save changes

---

## 🔄 Migration from Old System

### For Existing Products

The plugin will:
1. Read existing `_injonge_taxid` meta (if any)
2. Default to Tax B (VAT 16%) if not set
3. Automatically sync tax class

### For New Products

Simply select **KRA Tax Type** when creating products.

---

## 🧪 Testing Your Setup

### Test 1: Create a Test Order

1. Add a product to cart
2. Proceed to checkout
3. Check if tax is displayed
4. Complete order
5. Verify tax in order details

### Test 2: Submit to KRA API

1. Go to order edit page
2. Look for **KRA eTims** meta box
3. Click **Submit to API**
4. Check the response
5. Verify tax breakdown matches

### Test 3: View Different Tax Types

1. Create products with different tax types
2. Add all to cart
3. Check if taxes calculate correctly
4. Tax A should be 0%
5. Tax B should be 16%

---

## 📱 Customer Experience

### Cart Page
```
Product: Laptop
Price: KES 116,000 (incl. VAT)

Subtotal: KES 100,000
VAT (16%): KES 16,000
Total: KES 116,000
```

### Checkout Page
```
Order Summary
Subtotal: KES 100,000
Shipping: KES 500 (incl. VAT 80)
VAT: KES 16,080
Total: KES 116,580
```

### Order Receipt (Email)
```
Item                Qty    Price        Tax      Total
Laptop              1      100,000      16,000   116,000
Shipping            1      420          80       500

Subtotal:                  100,420
VAT (16%):                 16,080
Total:                     116,500 KES
```

---

## 🎯 Benefits

### For Store Owner

- ✅ **Accurate tax collection** - WooCommerce handles calculations
- ✅ **KRA compliance** - Proper tax breakdown sent to API
- ✅ **Easy management** - No manual tax calculations
- ✅ **Better reports** - See tax by type (A, B, C, D)
- ✅ **Professional receipts** - Clear tax display

### For Customers

- ✅ **Transparent pricing** - See tax breakdown
- ✅ **Trust** - Proper tax display builds confidence
- ✅ **Clarity** - Know exactly what they're paying

### For Accountant

- ✅ **Easy reconciliation** - Tax reports match KRA submissions
- ✅ **Audit trail** - All tax calculations documented
- ✅ **Compliance** - Meets KRA requirements

---

## ❓ FAQ

### Q: Will this affect my existing prices?

**A:** No! If your prices were already tax-inclusive, they stay the same. WooCommerce just properly breaks down the tax now.

### Q: Do I need to change all my product prices?

**A:** No. The system assumes your current prices include tax.

### Q: What if I want tax-exclusive pricing?

**A:** Go to **WooCommerce → Settings → Tax → Tax Options** and change "Prices entered with tax included" to "No". However, tax-inclusive is recommended for Kenya.

### Q: Can I have different tax rates for different products?

**A:** Yes! That's the whole point. Assign different KRA tax types (A, B, C, D) to different products.

### Q: Will old orders still work?

**A:** Yes! Old orders keep their original tax calculations. Only new orders use WooCommerce taxes.

### Q: Can I customize the tax rates?

**A:** Yes, but be careful! Changing Tax B from 16% would affect KRA compliance. Only do this if KRA changes VAT rate.

---

## 🆘 Troubleshooting

### Problem: Tax not showing at checkout

**Solution:**
1. Go to **WooCommerce → Settings → General**
2. Make sure "Enable tax rates and calculations" is checked
3. Save changes

### Problem: Wrong tax amount calculated

**Solution:**
1. Check product's KRA Tax Type
2. Verify WooCommerce tax class matches
3. Check if customer address is in Kenya (KE)

### Problem: Products show "Not Set" in KRA Tax column

**Solution:**
1. Edit the product
2. Select KRA Tax Type (default to B if unsure)
3. Save product

### Problem: Tax shows 0% for everything

**Solution:**
1. Go to **WooCommerce → Settings → Tax**
2. Check if tax rates exist for "VAT 16% (Tax B)"
3. If not, deactivate and reactivate the plugin

---

## 📞 Support

For issues or questions:

1. Check the [README.md](README.md) file
2. Review the [API_DOCUMENTATION.md](API_DOCUMENTATION.md)
3. Check WooCommerce tax settings
4. Contact plugin author: Rhenium Group Limited

---

## 🔄 Version History

### v1.0.2 (Current)
- ✅ Added WooCommerce tax integration
- ✅ Automatic tax setup
- ✅ Tax-inclusive pricing
- ✅ KRA tax type sync
- ✅ Improved tax accuracy

### v1.0.1
- Basic KRA eTims integration
- Manual tax calculations

---

## 📝 Technical Details

For developers who want to understand the implementation:

### Tax Handler Class

Located at: `includes/class-kra-etims-wc-tax-handler.php`

**Key Methods:**
- `setup_woocommerce_taxes()` - Configures WooCommerce
- `setup_tax_rates()` - Creates tax rates in database
- `get_item_tax_info()` - Extracts tax from order items
- `sync_tax_class_with_kra_type()` - Syncs tax classes

### Order Handler Updates

Located at: `includes/class-kra-etims-wc-order-handler.php`

**Changes:**
- Uses `$this->tax_handler->get_item_tax_info()` instead of calculating
- Reads actual WooCommerce tax amounts
- Properly handles tax-inclusive pricing

### Database Tables

Tax rates stored in:
- `wp_woocommerce_tax_rates`
- `wp_woocommerce_tax_rate_locations`

Product tax meta:
- `_injonge_taxid` - KRA tax type (A, B, C, D)
- `_tax_class` - WooCommerce tax class

---

## ✨ What's Next?

Future enhancements could include:
- Bulk tax type assignment tool
- Tax reports by KRA type
- Automatic tax rate updates from KRA
- Multi-currency tax support
- Tax exemption certificates

---

**Congratulations!** Your store is now fully tax-compliant with KRA eTims! 🎉

