<?php
// src/Form/OpenAIForm.php
namespace Drupal\openai_integration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface; 
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\AppendCommand;
use Psr\Log\LoggerInterface;
use Drupal\openai_integration\Service\OpenAIService;

class OpenAIForm extends FormBase {

    protected $openAIService;
    protected $logger;

    public function __construct(OpenAIService $openAIService, LoggerInterface $logger) {
        $this->openAIService = $openAIService;
        $this->logger = $logger;
    }

    public static function create(ContainerInterface $container) {
        return new static(
            $container->get('openai_integration.openai_service'),
            $container->get('logger.factory')->get('openai_integration')
        );
    }

    public function getFormId() {
        return 'openai_integration_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state) {
        $form['#attached']['library'][] = 'openai_integration/ajax';
        
        $form['conversation_wrapper'] = [
            '#type' => 'container',
            '#attributes' => ['id' => 'conversation-wrapper'],
        ];

        $form['prompt'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Ask something...'),
            '#required' => TRUE,
            '#attributes' => [
                'placeholder' => $this->t('Type your prompt here...'),
            ],
        ];

        $form['actions'] = [
            '#type' => 'actions',
        ];

        $form['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Send'),
            '#ajax' => [
                'callback' => '::promptSubmitAjax',
                'wrapper' => 'conversation-wrapper',
                'effect' => 'fade',
                'speed' => 'slow',
                'progress' => [
                    'type' => 'throbber',
                    'message' => $this->t('Processing...'),
                ],
            ],
        ];

        $form['actions']['clear'] = [
            '#type' => 'submit',
            '#value' => $this->t('Clear Conversation'),
            '#submit' => ['::clearConversation'],
            '#ajax' => [
                'callback' => '::clearConversationAjax',
                'wrapper' => 'conversation-wrapper',
            ],
        ];

        return $form;
    }

    public function validateForm(array &$form, FormStateInterface $form_state) {
        $prompt = trim($form_state->getValue('prompt'));
        if (empty($prompt)) {
            $form_state->setErrorByName('prompt', $this->t('Your prompt cannot be empty.'));
        }
    }

    public function promptSubmitAjax(array &$form, FormStateInterface $form_state) {
        $response = new AjaxResponse();
        if ($errors = $form_state->getErrors()) {
            $renderer = \Drupal::service('renderer');
            $error_messages = ['#type' => 'status_messages'];
            $rendered_messages = $renderer->renderRoot($error_messages);
            $response->addCommand(new HtmlCommand('.form-error-messages', $rendered_messages));
            return $response;
        }
    
        $prompt = $form_state->getValue('prompt');
        $responseText = $this->openAIService->generateResponse($prompt);
        
        if ($responseText === null) {
            // Error handling in case `generateResponse` returns null (e.g., prompt too long)
            $error_message = $this->t('Could not process your request. Please try again.');
            $response->addCommand(new HtmlCommand('.form-error-messages', $error_message));
            return $response;
        }
    
        $userMarkup = '<div class="user-message">' . htmlspecialchars($prompt, ENT_QUOTES, 'UTF-8') . '</div>';
        $assistantMarkup = '<div class="assistant-message">' . htmlspecialchars($responseText, ENT_QUOTES, 'UTF-8') . '</div>';
    
        // Clear the input area after submitting
        $response->addCommand(new \Drupal\Core\Ajax\InvokeCommand('#edit-prompt', 'val', ['']));
        
        // Add user and assistant messages to the conversation wrapper
        $response->addCommand(new AppendCommand('#conversation-wrapper', $userMarkup));
        $response->addCommand(new AppendCommand('#conversation-wrapper', $assistantMarkup));
    
        return $response;
    }

    public function clearConversation(array &$form, FormStateInterface $form_state) {
        $this->openAIService->saveConversationHistory([]); // Clearing the conversation history
        $this->messenger()->addMessage($this->t('Conversation history cleared.'));
    }

    public function clearConversationAjax(array &$form, FormStateInterface $form_state) {
        $response = new AjaxResponse();
        $response->addCommand(new HtmlCommand('#conversation-wrapper', ''));
        return $response;
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {
        // No actions needed here since AJAX handles the form submission.
    }
}