<?php
// filename: src/Form/OpenAIForm.php
namespace Drupal\openai_integration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
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
        $form['#attached']['library'][] = 'openai_integration/ajax';

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
            '#description' => $this->t('Please type your prompt to OpenAI.'),
            '#required' => TRUE,
        ];

        $form['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Submit'),
            '#ajax' => [
                'callback' => '::submitFormAjax',
                'wrapper' => 'conversation-wrapper',
                'effect' => 'fade',
            ],
        ];

        $form['clear_context'] = [
            '#type' => 'submit',
            '#value' => $this->t('Clear Context'),
            '#submit' => ['::clearContextSubmit'],
            '#ajax' => [
                'callback' => '::clearContextAjax',
                'wrapper' => 'conversation-wrapper',
                'effect' => 'fade',
            ],
        ];

        return $form;
    }

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
            $this->messenger()->addMessage($this->t('OpenAI response: @response', ['@response' => $response]));
        }
        catch (\Exception $e) {
            // Handle exceptions (e.g., log the error, display a user-friendly message)
            $this->messenger()->addError($this->t('An error occurred while processing your request.'));
        }

        // Reset the user input for the 'prompt' field
        $user_input = $form_state->getUserInput();
        $user_input['prompt'] = '';
        $form_state->setUserInput($user_input);

        $conversation = $this->openAIService->getConversationHistoryForForm();
        $form['conversation']['#markup'] = $this->buildConversationMarkup($conversation);

        return $form['conversation'];
    }

    public function clearContextAjax(array &$form, FormStateInterface $form_state) {
        $this->openAIService->clearConversationHistory();
        $this->messenger()->addMessage($this->t('Conversation context has been cleared.'));

        $form['conversation']['#markup'] = $this->buildConversationMarkup([]);

        return $form['conversation'];
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {
        // No need to implement this method as we're using AJAX submission
    }

    public function clearContextSubmit(array &$form, FormStateInterface $form_state) {
        // No need to implement this method as we're using AJAX submission
    }
}