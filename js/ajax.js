// File: js/ajax.js
(function ($, Drupal) {
    Drupal.behaviors.openaiIntegrationAjax = {
        attach: function (context, settings) {
            var $form = $('#openai_integration_form', context);
            var $submitButton = $form.find('.btn-primary');
            var $feedbackField = $('#feedback-field', context);  // Feedback area for responses

            $form.on('submit', function (event) {
                event.preventDefault();
                $submitButton.prop('disabled', true);
                $feedbackField.html('<div>Processing your request...</div>');

                var formData = $form.serialize();
                $.ajax({
                    url: $form.attr('action'),
                    type: 'POST',
                    data: formData,
                    dataType: 'json',

                    success: function(response) {
                        $submitButton.prop('disabled', false);
                        // Automatically scroll to bottom of conversation wrapper
                        var $conversationWrapper = $('#conversation-wrapper');
                        $conversationWrapper.animate({ scrollTop: $conversationWrapper.prop("scrollHeight")}, 1000);
                    },

                    error: function(response) {
                        let errorMessage = 'Error processing request.';
                        if (response.responseJSON && response.responseJSON.errors) {
                            errorMessage = response.responseJSON.errors.join("<br />");
                        }
                        $feedbackField.html('<div class="alert alert-danger">' + errorMessage + '</div>');
                        $submitButton.prop('disabled', false);
                    }
                });
            });
        }
    };
})(jQuery, Drupal);