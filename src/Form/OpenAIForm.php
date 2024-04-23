<?php
// src/Form/OpenAIForm.php
namespace Drupal\openai_integration\Form;

use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface; 
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\AppendCommand;
use Psr\Log\LoggerInterface;
use Drupal\openai_integration\Service\OpenAIService;
use Parsedown;

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
        $form['#attached']['library'][] = 'openai_integration/styles';
        
        $form['conversation_wrapper'] = [
            '#type' => 'container',
            '#attributes' => [
                'id' => 'conversation-wrapper',
                'aria-live' => 'polite',
                'aria-atomic' => 'true',
            ],
        ];

        $form['prompt'] = [
            '#type' => 'textarea',
            '#title' => $this->t('我是陈未名克隆体, 希望我可以帮到你.😊'),
            '#description' => $this->t('Enter your message or question for the AI assistant.'),
            '#required' => TRUE,
            '#attributes' => [
                'placeholder' => $this->t('请输入您的指令'),
                'required' => 'required',
                'aria-label' => $this->t('User prompt'),
            ],
        ];

        $form['actions'] = [
            '#type' => 'actions',
        ];

        $form['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('发送'),
            '#ajax' => [
                'callback' => '::promptSubmitAjax',
                'wrapper' => 'conversation-wrapper',
                'effect' => 'fade',
                'speed' => 'slow',
                'progress' => [
                    'type' => 'throbber',
                    'message' => $this->t('思考中🤔'),
                ],
            ],
        ];

        $form['actions']['clear'] = [
            '#type' => 'submit',
            '#value' => $this->t('清除'),
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
            $form_state->setErrorByName('prompt', $this->t('您的指令不能为空.'));
        } elseif (strlen($prompt) > 4096) {
            $form_state->setErrorByName('prompt', $this->t('您的指令过长, 请限制在4096个字符以内.'));
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
    
        // Use Parsedown to convert Markdown response to HTML
        $parser = new Parsedown();
        $htmlResponse = $parser->text($responseText);

        // Sanitize the prompt to prevent XSS
        // Since Parsedown's text() method returns safe HTML, you don't need to sanitize $htmlResponse
        $safePrompt = \Drupal\Component\Utility\Html::escape($prompt);
        $userMarkup = '<div class="message user-message">' . $safePrompt . '</div>';
        $assistantMarkup = '<div class="message assistant-message">' . $htmlResponse . '</div>';
    
        // Clear the input area after submitting
        $response->addCommand(new InvokeCommand('#edit-prompt', 'val', ['']));
        
        // Add user and assistant messages to the conversation wrapper
        $response->addCommand(new AppendCommand('#conversation-wrapper', $userMarkup));
        $response->addCommand(new AppendCommand('#conversation-wrapper', $assistantMarkup));
    
        return $response;
    }

    public function clearConversation(array &$form, FormStateInterface $form_state) {
        $this->openAIService->saveConversationHistory([]); // Clearing the conversation history
        $this->messenger()->addMessage($this->t('对话已清除.'));
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