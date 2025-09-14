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
                    // Populate the customer TIN field
                    $('#customer_tin').val(response.data.tin);
                    
                    // Show success message
                    showMessage(kraEtimsWcOrderAdmin.customerTinFoundText, 'success');
                } else {
                    // Show not found message
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
        var messageHtml = '<div class="' + messageClass + '"><p>' + message + '</p></div>';
        
        // Insert message after the customer TIN field
        $('#customer_tin').closest('.form-field').after(messageHtml);
        
        // Auto-remove message after 3 seconds
        setTimeout(function() {
            $('.kra-etims-customer-tin-message').fadeOut();
        }, 3000);
    }

    // Handle customer selection change
    $(document).on('change', '#customer_user', function() {
        var customerId = $(this).val();
        populateCustomerTin(customerId);
    });

    // Handle customer selection change for enhanced select (WooCommerce 3.0+)
    $(document).on('select2:select', '#customer_user', function(e) {
        var customerId = e.params.data.id;
        populateCustomerTin(customerId);
    });

    // Handle customer selection change for select2 clear
    $(document).on('select2:clear', '#customer_user', function() {
        // Clear the customer TIN field when customer is cleared
        $('#customer_tin').val('');
        $('.kra-etims-customer-tin-message').remove();
    });

    // Initialize on page load (in case customer is already selected)
    $(document).ready(function() {
        var customerId = $('#customer_user').val();
        if (customerId && customerId !== '0') {
            // Small delay to ensure WooCommerce has initialized
            setTimeout(function() {
                populateCustomerTin(customerId);
            }, 500);
        }
    });

})(jQuery); 