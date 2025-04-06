/**
 * My PDF Plugin - Frontend JavaScript
 * Handles the AJAX request for PDF generation
 */

(function($) {
    'use strict';

    // Main PDF generation functionality
    var MyPDFPlugin = {
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            $(document).on('click', '.generate-pdf-button', this.handleGeneratePDF);
        },

        handleGeneratePDF: function(e) {
            e.preventDefault();

            var $button = $(this);
            var $container = $button.closest('.inside'); // Adjusted for metabox.
            var $loadingIndicator = $container.find('.pdf-loading-indicator');
            var $messageContainer = $container.find('.pdf-message');
            
            // Get data from button data attributes
            var postId = $button.data('post-id');
            var nonce = $button.data('nonce');
            
            // Prevent multiple clicks
            if ($button.hasClass('disabled')) {
                return false;
            }
            
            // Show loading indicator and disable button
            $loadingIndicator.addClass('active');
            $button.addClass('disabled');
            $messageContainer.removeClass('success error').hide().empty();
            
            // Make AJAX request
            $.ajax({
                url: my_pdf_plugin.ajax_url,
                type: 'POST',
                data: {
                    action: 'generate_pdf',
                    post_id: postId,
                    nonce: nonce
                },
                success: function(response) {
                    // Hide loading indicator
                    $loadingIndicator.removeClass('active');
                    $button.removeClass('disabled');
                    
                    if (response.success) {
                        // Handle successful response
                        $messageContainer.addClass('success')
                            .html('PDF generated successfully!')
                            .show();
                        
                        if (response.data && response.data.pdf_url) {
                            // Create download link
                            var $downloadLink = $('<a>', {
                                'href': response.data.pdf_url,
                                'class': 'pdf-download-link',
                                'target': '_blank',
                                'text': 'Download PDF'
                            });
                            
                            $messageContainer.append('<br>').append($downloadLink);
                            
                            // Optionally open the PDF in a new tab
                            if (my_pdf_plugin.auto_open_pdf) {
                                window.open(response.data.pdf_url, '_blank');
                            }
                        }
                    } else {
                        // Handle error response
                        var errorMessage = response.data && response.data.message 
                            ? response.data.message 
                            : 'An unknown error occurred while generating the PDF.';
                        
                        $messageContainer.addClass('error')
                            .text(errorMessage)
                            .show();
                    }
                },
                error: function(xhr, status, error) {
                    // Hide loading indicator
                    $loadingIndicator.removeClass('active');
                    $button.removeClass('disabled');
                    
                    // Display error message
                    var errorMessage = 'A server error occurred. Please try again later.';
                    
                    if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                        errorMessage = xhr.responseJSON.data.message;
                    }
                    
                    $messageContainer.addClass('error')
                        .text(errorMessage)
                        .show();
                    
                    // Log detailed error for debugging
                    console.error('PDF Generation Error:', {
                        status: status,
                        error: error,
                        response: xhr.responseText
                    });
                }
            });
            
            return false;
        },
    };

    // Initialize when document is ready
    $(document).ready(function() {
        MyPDFPlugin.init();
    });

})(jQuery);

