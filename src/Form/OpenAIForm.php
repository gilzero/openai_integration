<?php
// filename: src/Form/OpenAIForm.php
namespace Drupal\openai_integration\Form;
use Drupal\Core\Ajax\MessageCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\openai_integration\Service\OpenAIService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\InvokeCommand;

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
    }
}