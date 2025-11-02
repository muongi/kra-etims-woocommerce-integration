=== KRA eTims Connector ===
Contributors: shadrackmatata
Tags: woocommerce, kra, etims, tax, kenya, electronic tax invoice management system, etims
Requires at least: 5.6
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: ISC
License URI: https://opensource.org/licenses/ISC

Connects WooCommerce with Kenya Revenue Authority (KRA) Electronic Tax Invoice Management System (eTims) to generate eTims compliant receipts.

== Description ==

This plugin allows WooCommerce store owners to automatically submit sales transactions to the KRA eTims system when orders are completed and generate eTims-compliant receipts. It provides a seamless integration between your WordPress WooCommerce store and the KRA eTims API, ensuring compliance with KRA's electronic tax invoice requirements for online sales.

= Features =

* Automatic submission of sales transactions to KRA eTims when orders are completed
* Generation of eTims-compliant receipts for online sales on WordPress WooCommerce websites
* Manual order submission capability
* Environment switching (Development/Production)
* Customer TIN/PIN management
* Tax calculation and reporting
* QR code generation for receipts
* Comprehensive error handling and logging

= Installation =

1. Upload the plugin files to the `/wp-content/plugins/kra-etims-woocommerce` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to the 'KRA eTims' menu in your WordPress admin to configure the plugin settings

= Configuration =

1. Navigate to the 'KRA eTims' menu in your WordPress admin
2. Enter your KRA eTims API credentials:
   - TIN (Taxpayer Identification Number)
   - Branch ID
   - Device Serial
   - Environment (Development/Production)
   - Custom API URL
3. Save your settings

= eTims-Compliant Receipts =

The plugin generates receipts that are fully compliant with KRA eTims requirements. All receipts generated are compliant with KRA eTims requirements for online sales.

== Frequently Asked Questions ==

= Can I manually submit orders to KRA eTims? =

Yes, you can manually submit an order to KRA eTims from the WooCommerce order edit screen:

1. Go to WooCommerce → Orders
2. Open the order you want to submit
3. In the "KRA eTims Integration" meta box, click the "Submit to KRA eTims" button

= Can I submit multiple orders at once? =

Yes, you can submit multiple orders using bulk actions:

1. Go to WooCommerce → Orders
2. Select the orders you want to submit
3. Select "Submit to KRA eTims" from the dropdown
4. Click "Apply"

== Screenshots ==

1. KRA eTims visual configurator for WordPress WooCommerce

== Changelog ==

= 1.0.0 =
* Initial release
* KRA eTims visual configurator for WordPress WooCommerce
