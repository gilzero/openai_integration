<?php
<<<<<<< HEAD
// filename: src/Form/OpenAIForm.php
namespace Drupal\openai_integration\Form;
use Drupal\Core\Ajax\MessageCommand;
=======

namespace Drupal\openai_integration\Form;

>>>>>>> HEAD@{1}
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\openai_integration\Service\OpenAIService;
use Symfony\Component\DependencyInjection\ContainerInterface;
<<<<<<< HEAD
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\InvokeCommand;

class OpenAIForm extends FormBase {
=======

class OpenAIForm extends FormBase {

>>>>>>> HEAD@{1}
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
<<<<<<< HEAD
        $form['#attached']['library'][] = 'openai_integration/ajax';

        $conversation = $this->openAIService->getConversationHistoryForForm();
=======

        $conversation = $this->openAIService->getConversationHistoryForForm();

>>>>>>> HEAD@{1}
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
<<<<<<< HEAD
            '#description' => $this->t('Please type your prompt to OpenAI.'),
            '#required' => TRUE,
=======
            '#description' => $this->t('Please type your prompt.'),
            '#required' => TRUE,
            '#attributes' => ['id' => 'edit-prompt'],
>>>>>>> HEAD@{1}
        ];

        $form['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Submit'),
            '#ajax' => [
<<<<<<< HEAD
                'callback' => '::submitFormAjax',
=======
                'callback' => '::promptSubmitAjax',
>>>>>>> HEAD@{1}
                'wrapper' => 'conversation-wrapper',
                'effect' => 'fade',
            ],
        ];
<<<<<<< HEAD

        $form['clear_context'] = [
            '#type' => 'submit',
            '#value' => $this->t('Clear Context'),
            '#submit' => ['::clearContextSubmit'],
            '#ajax' => [
                'callback' => '::clearContextAjax',
=======
        
        // Adds a clear button for resetting the chat
        $form['clear'] = [
            '#type' => 'button',
            '#value' => $this->t('Clear Context'),
            '#ajax' => [
                'callback' => '::clearConversationAjax',
>>>>>>> HEAD@{1}
                'wrapper' => 'conversation-wrapper',
                'effect' => 'fade',
            ],
        ];

        return $form;
    }

<<<<<<< HEAD
    // Helper function to build conversation markup
    protected function buildConversationMarkup(array $conversation) {
        if (empty($conversation)) {
            return '<div class="conversation">No conversation history available.</div>';
        }

        return '<div class="conversation">' . implode("<br>", array_map(
                    function($m) {
                        return '<strong>' . $m['role'] . ':</strong> ' . $m['content'];
                    }, $conversation)
            ) . '</div>';
    }

    public function submitFormAjax(array &$form, FormStateInterface $form_state) {
        $prompt = $form_state->getValue('prompt');

        try {
            $response = $this->openAIService->generateResponse($prompt);
            $message = $this->t('OpenAI response: @response', ['@response' => $response]);
        }
        catch (\Exception $e) {
            // Handle exceptions (e.g., log the error, display a user-friendly message)
            $message = $this->t('An error occurred while processing your request.');
        }

        // Reset the user input for the 'prompt' field
        $user_input = $form_state->getUserInput();
        $user_input['prompt'] = '';
        $form_state->setUserInput($user_input);

        $conversation = $this->openAIService->getConversationHistoryForForm();
        $form['conversation']['#markup'] = $this->buildConversationMarkup($conversation);

        $response = new AjaxResponse();
        $response->addCommand(new ReplaceCommand('#conversation-wrapper', $form['conversation']['#markup']));
        $response->addCommand(new InvokeCommand('#openai-integration-form input[name="prompt"]', 'val', ['']));
        $response->addCommand(new MessageCommand($message));

        return $response;
    }

    public function clearContextAjax(array &$form, FormStateInterface $form_state) {
        $this->openAIService->clearConversationHistory();
        $message = $this->t('Conversation context has been cleared.');

        $form['conversation']['#markup'] = $this->buildConversationMarkup([]);
        $form['prompt']['#value'] = '';

        $response = new AjaxResponse();
        $response->addCommand(new ReplaceCommand('#conversation-wrapper', $form['conversation']['#markup']));
        $response->addCommand(new InvokeCommand('#openai-integration-form input[name="prompt"]', 'val', ['']));
        $response->addCommand(new MessageCommand($message));

        return $response;
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {
        // No need to implement this method as we're using AJAX submission
    }

    public function clearContextSubmit(array &$form, FormStateInterface $form_state) {
        // No need to implement this method as we're using AJAX submission
=======
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
>>>>>>> HEAD@{1}
    }
}