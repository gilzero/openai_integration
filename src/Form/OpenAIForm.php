<?php

// File: src/Form/OpenAIForm.php
namespace Drupal\openai_integration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\openai_integration\Service\OpenAIService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Psr\Log\LoggerInterface;

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

        $form['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Send'),
            '#ajax' => [
                'callback' => '::promptSubmitAjax',
                'wrapper' => 'conversation-wrapper',
                'effect' => 'fade',
                'speed' => 'slow',
            ],
        ];

        return $form;
    }

    public function promptSubmitAjax(array &$form, FormStateInterface $form_state) {
        $response = new AjaxResponse();
        $errors = $form_state->getErrors();

        if (!empty($errors)) {
            $error_messages = ['#theme' => 'status_messages'];
            foreach ($errors as $error) {
                $response->addCommand(new HtmlCommand('.form-error-messages', (string)$error));
            }
            return $response;
        }

        $prompt = htmlspecialchars($form_state->getValue('prompt'), ENT_QUOTES, 'UTF-8');
        try {
            $responseText = $this->openAIService->generateResponse($prompt);
        } catch (\Exception $e) {
            $this->logger->error(
                "Failed to generate response for prompt: @prompt. Error: @error", [
                    '@prompt' => $prompt,
                    '@error' => $e->getMessage(),
                    'link' => $e->getTraceAsString() // provides full stack trace
                ]
            );
            $response->addCommand(new HtmlCommand('.error-message', 'An error occurred while processing your request.'));
            return $response;
        }

        $userMarkup = '<div class="user-message">' . $prompt . '</div>';
        $assistantMarkup = '<div class="assistant-message">' . htmlspecialchars($responseText, ENT_QUOTES, 'UTF-8') . '</div>';

        $response->addCommand(new AppendCommand('#conversation-wrapper', $userMarkup));
        $response->addCommand(new AppendCommand('#conversation-wrapper', $assistantMarkup));

        return $response;
    }

    public function validateForm(array &$form, FormStateInterface $form_state) {
        $prompt = trim($form_state->getValue('prompt'));
        if (empty($prompt)) {
            $form_state->setErrorByName('prompt', $this->t('Your prompt cannot be empty.'));
        }
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {
        // Form submission is handled via AJAX, so this remains empty.
    }
}
