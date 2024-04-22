<?php
/*
 * File: ConfigForm.php
 */

namespace Drupal\openai_integration\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class ConfigForm extends ConfigFormBase {

  protected function getEditableConfigNames() {
    return [
      'openai_integration.settings',
    ];
  }

  public function getFormId() {
    return 'openai_integration_admin_settings';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('openai_integration.settings');

    $form['openai_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('OpenAI API Key'),
      '#default_value' => $config->get('openai_api_key'),
      '#required' => TRUE,
    ];

    $form['model_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Model Name'),
      '#default_value' => $config->get('model_name'),
      '#required' => TRUE,
      '#description' => $this->t('Enter the model name, e.g., gpt-3.5-turbo, gpt-4-turbo'),
    ];

    $form['system_prompt'] = [
      '#type' => 'textarea',
      '#title' => $this->t('System Prompt'),
      '#default_value' => $config->get('system_prompt'),
      '#description' => $this->t('Enter a system prompt to provide context or instructions for the model.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('openai_integration.settings')
      ->set('openai_api_key', $form_state->getValue('openai_api_key'))
      ->set('model_name', $form_state->getValue('model_name'))
      ->set('system_prompt', $form_state->getValue('system_prompt'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}