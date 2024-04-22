<?php

namespace Drupal\openai_integration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\openai_integration\Service\OpenAIService;
use Symfony\Component\DependencyInjection\ContainerInterface;

class OpenAIForm extends FormBase {

    protected $openAIService;

    public function __construct(OpenAIService $openAIService) {
        $this->openAIService = $openAIService;
    }

    public static function create(ContainerInterface $container) {
        return new static(
            $container->get('openai_integration.openai_service')
        );
    }

    public function getFormId() {
        return 'openai_integration_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state) {
        $form['#attached']['library'][] = 'openai_integration/styles';

        $conversation = $this->openAIService->getConversationHistoryForForm();

        $form['conversation'] = [
            '#type' => 'markup',
            '#markup' => $this->buildConversationMarkup($conversation),
            '#allowed_tags' => ['div', 'br', 'strong'],
            '#prefix' => '<div id="conversation-wrapper">',
            '#suffix' => '</div>',
        ];

        $form['prompt'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Prompt'),
            '#description' => $this->t('Please type your prompt.'),
            '#required' => TRUE,
            '#attributes' => ['id' => 'edit-prompt'],
        ];

        $form['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Submit'),
            '#ajax' => [
                'callback' => '::promptSubmitAjax',
                'wrapper' => 'conversation-wrapper',
                'effect' => 'fade',
            ],
        ];
        
        // Adds a clear button for resetting the chat
        $form['clear'] = [
            '#type' => 'button',
            '#value' => $this->t('Clear Context'),
            '#ajax' => [
                'callback' => '::clearConversationAjax',
                'wrapper' => 'conversation-wrapper',
                'effect' => 'fade',
            ],
        ];

        return $form;
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {
        // The submit handler does not do anything for AJAX submissions.
    }

    public function promptSubmitAjax(array &$form, FormStateInterface $form_state) {
        error_log('Entered AJAX Callback');  // Check log to see if this executes
        $prompt = $form_state->getValue('prompt');
        error_log('Prompt: ' . print_r($prompt, TRUE));  // Log the prompt
        
        try {
            $responseText = $this->openAIService->generateResponse($prompt);
            $this->messenger()->addMessage($this->t('OpenAI response: @response', ['@response' => $responseText]));
        } catch (\Exception $e) {
            $this->messenger()->addError($this->t('An error occurred while processing your request.'));
        }

        // Refresh the conversation part of the form to include the new response.
        $conversation = $this->openAIService->getConversationHistoryForForm();
        $markup = $this->buildConversationMarkup($conversation);

        $response = new AjaxResponse();
        $response->addCommand(new ReplaceCommand('#conversation-wrapper', '<div id="conversation-wrapper">' . $markup . '</div>'));
        $response->addCommand(new InvokeCommand('#edit-prompt', 'val', [''])); // Clears the prompt box

        return $response;
    }

    public function clearConversationAjax(array &$form, FormStateInterface $form_state) {
        $this->openAIService->clearConversationHistory();

        $response = new AjaxResponse();
        $response->addCommand(new ReplaceCommand('#conversation-wrapper', '<div id="conversation-wrapper"></div>'));
        
        return $response;
    }

    // Helper function to build conversation markup
    protected function buildConversationMarkup(array $conversation) {
        $markup = '<div class="conversation">';
        foreach ($conversation as $message) {
            if ($message['role'] !== 'system') {  // Skip system messages
                $markup .= "<br><strong>" . $message['role'] . ":</strong> " . htmlspecialchars($message['content']);
            }
        }
        $markup .= '</div>';
        return $markup;
    }
}