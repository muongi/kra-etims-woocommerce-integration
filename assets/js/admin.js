/**
 * KRA eTims WooCommerce Plugin Admin JavaScript
 */
(function($) {
    'use strict';

    // Handle KRA eTims submit button clicks
    $(document).on('click', 'a[href*="action=kra_etims_submit_order"]', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var originalText = $button.text();
        var url = $button.attr('href');
        
        // Disable button and show loading
        $button.prop('disabled', true).text('Submitting...');
        
        // Make AJAX request
        $.ajax({
            url: url,
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Show success message
                    showMessage(response.data.message, 'success');
                    
                    // Redirect after a short delay
                    setTimeout(function() {
                        window.location.href = response.data.redirect_url;
                    }, 1500);
                } else {
                    // Show error message
                    showMessage(response.data || 'An error occurred while submitting the order.', 'error');
                    $button.prop('disabled', false).text(originalText);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', xhr.responseText);
                
                // Try to parse error response
                var errorMessage = 'An error occurred while submitting the order.';
                try {
                    var response = JSON.parse(xhr.responseText);
                    if (response.data) {
                        errorMessage = response.data;
                    }
                } catch (e) {
                    // If we can't parse JSON, use the raw response
                    if (xhr.responseText) {
                        errorMessage = xhr.responseText;
                    }
                }
                
                showMessage(errorMessage, 'error');
                $button.prop('disabled', false).text(originalText);
            }
        });
    });

    // Function to show messages
    function showMessage(message, type) {
        // Remove any existing messages
        $('.kra-etims-message').remove();
        
        // Create message element
        var messageClass = 'kra-etims-message notice notice-' + type + ' is-dismissible';
        var messageHtml = '<div class="' + messageClass + '"><p>' + message + '</p></div>';
        
        // Insert message at the top of the page
        $('.wp-header-end').after(messageHtml);
        
        // Auto-remove message after 5 seconds
        setTimeout(function() {
            $('.kra-etims-message').fadeOut();
        }, 5000);
    }

    // QR Code download functionality
    window.downloadQRCode = function(button) {
        var qrUrl = button.getAttribute('data-url');
        var qrApiUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' + encodeURIComponent(qrUrl) + '&format=png&margin=2&ecc=M';
        
        // Create a temporary link to download the QR code
        var link = document.createElement('a');
        link.href = qrApiUrl;
        link.download = 'qr-code-' + Date.now() + '.png';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    };

})(jQuery);
