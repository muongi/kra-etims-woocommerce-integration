/**
 * KRA eTims WooCommerce Integration Order Admin JavaScript
 */
(function($) {
    'use strict';

    // Function to populate customer TIN field
    function populateCustomerTin(customerId) {
        if (!customerId || customerId === '0') {
            return;
        }

        // Make AJAX request to get customer TIN
        $.ajax({
            url: kraEtimsWcOrderAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: kraEtimsWcOrderAdmin.getCustomerTinAction,
                customer_id: customerId,
                nonce: kraEtimsWcOrderAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Populate the customer TIN field (try multiple selectors)
                    var $tinField = $('#customer_tin, input[name="customer_tin"]');
                    $tinField.val(response.data.tin);
                    
                    // Show success message
                    console.log('Customer TIN populated:', response.data.tin);
                    showMessage(kraEtimsWcOrderAdmin.customerTinFoundText, 'success');
                } else {
                    // Show not found message
                    console.log('No TIN found for customer');
                    showMessage(kraEtimsWcOrderAdmin.customerTinNotFoundText, 'info');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error fetching customer TIN:', error);
                showMessage('Error fetching customer TIN data.', 'error');
            }
        });
    }

    // Function to show messages
    function showMessage(message, type) {
        // Remove any existing messages
        $('.kra-etims-customer-tin-message').remove();
        
        // Create message element
        var messageClass = 'kra-etims-customer-tin-message notice notice-' + type;
        var messageHtml = '<div class="' + messageClass + '" style="margin: 10px 0; padding: 10px;"><p style="margin: 0;">' + message + '</p></div>';
        
        // Find the customer TIN field and insert message after it
        var $tinField = $('#customer_tin, input[name="customer_tin"]');
        if ($tinField.length > 0) {
            var $container = $tinField.closest('.form-field, .form-field-wide, div');
            if ($container.length > 0) {
                $container.after(messageHtml);
            } else {
                $tinField.after(messageHtml);
            }
        }
        
        // Auto-remove message after 3 seconds
        setTimeout(function() {
            $('.kra-etims-customer-tin-message').fadeOut(function() {
                $(this).remove();
            });
        }, 3000);
    }

    // Handle customer selection change (multiple selectors for compatibility)
    $(document).on('change', '#customer_user, select[name="customer_user"]', function() {
        var customerId = $(this).val();
        populateCustomerTin(customerId);
    });

    // Handle customer selection change for enhanced select (WooCommerce 3.0+)
    $(document).on('select2:select', '#customer_user, select[name="customer_user"]', function(e) {
        var customerId = e.params.data.id;
        populateCustomerTin(customerId);
    });

    // Handle customer selection change for select2 clear
    $(document).on('select2:clear', '#customer_user, select[name="customer_user"]', function() {
        // Clear the customer TIN field when customer is cleared
        $('#customer_tin, input[name="customer_tin"]').val('');
        $('.kra-etims-customer-tin-message').remove();
    });

    // Initialize on page load (in case customer is already selected)
    $(document).ready(function() {
        var customerId = $('#customer_user, select[name="customer_user"]').val();
        if (customerId && customerId !== '0') {
            // Small delay to ensure WooCommerce has initialized
            setTimeout(function() {
                populateCustomerTin(customerId);
            }, 1000);
        }
        
        // Monitor for changes to the customer field (for dynamic content loading)
        var checkInterval = setInterval(function() {
            var $customerField = $('#customer_user, select[name="customer_user"]');
            if ($customerField.length > 0) {
                var customerId = $customerField.val();
                if (customerId && customerId !== '0' && !$('#customer_tin, input[name="customer_tin"]').val()) {
                    populateCustomerTin(customerId);
                    clearInterval(checkInterval);
                }
            }
        }, 1000);
        
        // Stop checking after 10 seconds
        setTimeout(function() {
            clearInterval(checkInterval);
        }, 10000);
    });

})(jQuery); 