// filename: js/ajax.js
(function ($, Drupal) {
    Drupal.behaviors.openaiIntegrationAjax = {
        attach: function (context, settings) {
            // Scroll to the bottom of the conversation wrapper when updated via AJAX
            $(document).ajaxComplete(function (event, xhr, settings) {
                if (settings.extraData && settings.extraData.indexOf('openai_integration_form') !== -1) {
                    var $conversationWrapper = $('#conversation-wrapper');
                    $conversationWrapper.scrollTop($conversationWrapper[0].scrollHeight);
                }
            });
        }
    };
})(jQuery, Drupal);