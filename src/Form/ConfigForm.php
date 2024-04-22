<?php
// src/Form/ConfigForm.php
namespace Drupal\openai_integration\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\openai_integration\Service\OpenAIService;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ConfigForm extends ConfigFormBase {

    /**
     * OpenAI service.
     *
     * @var \Drupal\openai_integration\Service\OpenAIService
     */
    protected $openAIService;

    /**
     * Constructor for dependency injection.
     *
     * @param \Drupal\openai_integration\Service\OpenAIService $openAIService
     *   The OpenAI service.
     */
    public function __construct(OpenAIService $openAIService) {
        $this->openAIService = $openAIService;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container) {
        return new static(
            $container->get('openai_integration.openai_service')
        );
    }

    /**
     * Gets the configuration names that will be editable.
     */
    protected function getEditableConfigNames() {
        return ['openai_integration.settings'];
    }

    /**
     * Gets the unique string identifying the form.
     */
    public function getFormId() {
        return 'openai_integration_admin_settings';
    }

    /**
     * Form constructor.
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        $config = $this->config('openai_integration.settings');

        $form['openai_api_key'] = [
            '#type' => 'textfield',
            '#title' => $this->t('OpenAI API Key'),
            '#default_value' => $config->get('openai_api_key'),
            '#required' => TRUE,
            '#description' => $this->t('Enter your OpenAI API key.'),
            '#ajax' => [
                'callback' => '::validateApiKeyAjax',
                'wrapper' => 'api-key-validation-message',
                'event' => 'change',
                'progress' => [
                    'type' => 'throbber',
                    'message' => $this->t('Validating...'),
                ],
            ],
            '#suffix' => '<div id="api-key-validation-message"></div>'
        ];

        $form['model_name'] = [
            '#type' => 'select',
            '#title' => $this->t('Model Name'),
            '#default_value' => $config->get('model_name'),
            '#options' => $this->openAIService->getAvailableModels(),
            '#required' => TRUE,
            '#description' => $this->t('Select the AI model for your integration.'),
        ];

        $form['system_prompt'] = [
            '#type' => 'textarea',
            '#title' => $this->t('System Prompt'),
            '#default_value' => $config->get('system_prompt'),
            '#description' => $this->t('Enter a system prompt or default question for the AI.'),
            '#rows' => 4,
            '#cols' => 60,
            '#resizable' => 'vertical',
            '#required' => FALSE,
        ];

        $form['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Save configuration'),
        ];

        return parent::buildForm($form, $form_state);
    }

    /**
     * AJAX callback for validating the API key.
     */
    public function validateApiKeyAjax(array &$form, FormStateInterface $form_state) {
        $response = new AjaxResponse();
        $apiKey = $form_state->getValue('openai_api_key');
    
        try {
            $isValid = $this->openAIService->checkApiKey($apiKey);  // Assuming this method exists.
            $cssClass = $isValid ? 'response-valid' : 'response-invalid';
            $message = $isValid ? $this->t('API Key is valid.') : $this->t('API Key is invalid.');
        } catch (\Exception $e) {
            $message = $this->t('Failed to verify API Key due to an error.');
            $cssClass = 'response-error';
        }

        $response->addCommand(new HtmlCommand('#api-key-validation-message', "<div class=\"{$cssClass}\">{$message}</div>"));
        return $response;
    }

    /**
     * Form submission handler.
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        parent::submitForm($form, $form_state);
        $this->config('openai_integration.settings')
            ->set('openai_api_key', $form_state->getValue('openai_api_key'))
            ->set('model_name', $form_state->getValue('model_name'))
            ->set('system_prompt', $form_state->getValue('system_prompt'))
            ->save();
    }
}