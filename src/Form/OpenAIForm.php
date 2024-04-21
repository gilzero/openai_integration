<?php
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
    $conversation = $this->openAIService->getConversationHistoryForForm();
    $form['conversation'] = [
      '#type' => 'markup',
      '#markup' => $this->buildConversationMarkup($conversation),
      '#allowed_tags' => ['div', 'br', 'strong'],
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
    ];
    return $form;
  }
  // Helper function to build conversation markup
  protected function buildConversationMarkup(array $conversation) {
    return '<div class="conversation">' . implode("<br>", array_map(
        function($m) {
          return '<strong>' . $m['role'] . ':</strong> ' . $m['content'];
        }, $conversation)
      ) . '</div>';
  }
  public function submitForm(array &$form, FormStateInterface $form_state) {
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
    $form_state->setRebuild(TRUE);
  }
}
