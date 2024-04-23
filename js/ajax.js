(function ($, Drupal) {
    Drupal.behaviors.openaiIntegrationAjax = {
        attach: function (context, settings) {
            const $form = $('#openai_integration_form', context);
            const $promptInput = $('#edit-prompt', context);
            const $submitButton = $form.find('.form-submit');

            // Check if elements exist before initialization to handle permission-based UI rendering
            if ($promptInput.length && $submitButton.length) {
                this.initializeForm($promptInput, $submitButton);
                this.setupEventListeners($form, $promptInput, $submitButton);
            }
        },
        initializeForm: function($promptInput, $submitButton) {
            this.toggleSubmitButtonState($promptInput.val().trim(), $submitButton);
        },
        setupEventListeners: function($form, $promptInput, $submitButton) {
            const debounceToggleSubmit = Drupal.debounce(() => {
                this.toggleSubmitButtonState($promptInput.val().trim(), $submitButton);
            }, 300);

            $promptInput.on('input', debounceToggleSubmit);
            $form.on('submit', (event) => this.handleFormSubmit(event, $form, $promptInput, $submitButton));
        },
        toggleSubmitButtonState: function(promptValue, $submitButton) {
            $submitButton.prop('disabled', promptValue === '');
        },
        handleFormSubmit: function(event, $form, $promptInput, $submitButton) {
            event.preventDefault();
            if ($promptInput.val().trim()) {
                this.submitForm($form, $promptInput, $submitButton);
            }
        },
        submitForm: function($form, $promptInput, $submitButton) {
            if (!$promptInput.val().trim()) {
                return; // Exit if the prompt is empty
            }
            this.showFeedback('Processing your request...');

            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                data: $form.serialize(),
                dataType: 'json',
                success: (response) => this.handleSuccess(response, $promptInput, $submitButton),
                error: (response) => this.handleError(response, $submitButton),
            });
        },
        handleSuccess: function(response, $promptInput, $submitButton) {
            this.clearFeedback();
            this.appendMessages($promptInput.val(), response.data);
            $promptInput.val(''); // Clear input field after successful submission
            $submitButton.prop('disabled', false);
        },
        handleError: function(response, $submitButton) {
            let errorMessage = 'Error processing request. Please try again.';
            errorMessage += this.formatErrorDetails(response);
            this.showFeedback(errorMessage, true);
            $submitButton.prop('disabled', false);
        },
        showFeedback: function(message, isError = false) {
            const messageType = isError ? 'alert-danger' : '';
            $('#feedback-field').html(`<div class="alert ${messageType}">${message}</div>`);
        },
        clearFeedback: function() {
            $('#feedback-field').empty();
        },
        appendMessages: function(userMessage, assistantMessage) {
            const messageBlock = `<div class="message user-message">${userMessage}</div>
                                  <div class="message assistant-message">${assistantMessage}</div>`;
            const $conversationWrapper = $('#conversation-wrapper');
            $conversationWrapper.append(messageBlock);
            $conversationWrapper.animate({ scrollTop: $conversationWrapper.prop("scrollHeight")}, 1000);
        },
        formatErrorDetails: function(response) {
            return response.responseJSON && response.responseJSON.message 
                   ? ` Details: ${response.responseJSON.message}` 
                   : ` Details: ${response.statusText}`;
        }
    };
})(jQuery, Drupal);