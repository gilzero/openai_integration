(function ($, Drupal) {
    Drupal.behaviors.openaiIntegrationAjax = {
        attach: function (context, settings) {
            this.initializeForm(context);
            this.setupEventListeners(context);
        },
        initializeForm: function(context) {
            var $form = $('#openai_integration_form', context);
            var $promptInput = $('#edit-prompt', context);
            this.toggleSubmitButtonState($promptInput.val().trim(), $form.find('.form-submit'));
        },
        setupEventListeners: function(context) {
            var $form = $('#openai_integration_form', context);
            var $promptInput = $('#edit-prompt', context);
            var $submitButton = $form.find('.form-submit');

            // Debounce input handler to limit how often we toggle the submit button state
            var debounceToggleSubmit = Drupal.debounce(() => {
                this.toggleSubmitButtonState($promptInput.val().trim(), $submitButton);
            }, 300);  // Debounce for 300 milliseconds

            $promptInput.on('input', debounceToggleSubmit);

            $form.on('submit', (event) => this.handleFormSubmit(event, $form, $promptInput, $submitButton));
        },
        toggleSubmitButtonState: function(promptValue, $submitButton) {
            $submitButton.prop('disabled', promptValue === '');
        },
        handleFormSubmit: function(event, $form, $promptInput, $submitButton) {
            event.preventDefault();

            var promptValue = $promptInput.val().trim();
            if (!promptValue) {
                return; // Exit the function if validation fails
            }

            $submitButton.prop('disabled', true);
            $('#feedback-field').html('<div>Processing your request...</div>');
            
            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                data: $form.serialize(),
                dataType: 'json',
                success: (response) => this.handleSuccess(response, $promptInput, $submitButton),
                error: (response) => this.handleError(response, $submitButton)
            });
        },
        handleSuccess: function(response, $promptInput, $submitButton) {
            $('#feedback-field').empty();
            $('#conversation-wrapper').append('<div class="message user-message">' + $promptInput.val() + '</div>');
            $('#conversation-wrapper').append('<div class="message assistant-message">' + response.data + '</div>');
            $('#conversation-wrapper').animate({ scrollTop: $('#conversation-wrapper').prop("scrollHeight")}, 1000);
            $submitButton.prop('disabled', false);
            $promptInput.val(''); // Clear input field after successful submission
        },
        handleError: function(response, $submitButton) {
            let errorMessage = 'Error processing request. Please try again.';
            if (response.responseJSON && response.responseJSON.message) {
                errorMessage = response.responseJSON.message; // Display a specific error message if available
            } else if (response.statusText) {
                errorMessage += ' Details: ' + response.statusText;
            }
        
            $('#feedback-field').html('<div class="alert alert-danger">' + errorMessage + '</div>');
            $submitButton.prop('disabled', false);
        }
    };
})(jQuery, Drupal);
