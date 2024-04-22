<?php

namespace Drupal\openai_integration\Service;

use GuzzleHttp\ClientInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class OpenAIService {
    protected $httpClient;
    protected $session;

    public function __construct(ClientInterface $httpClient, SessionInterface $session) {
        $this->httpClient = $httpClient;
        $this->session = $session;
    }

    public function generateResponse($prompt) {
        $config = \Drupal::config('openai_integration.settings');
        $apiKey = $config->get('openai_api_key');
        $modelName = $config->get('model_name');
        $systemPrompt = $config->get('system_prompt');

        $conversationHistory = $this->getConversationHistory();

        // Debug: Log the conversation history for anonymous users
        if (\Drupal::currentUser()->isAnonymous()) {
            \Drupal::logger('openai_integration')->notice('Conversation history for anonymous user: ' . print_r($conversationHistory, TRUE));
        }

        // Add system prompt as first message if it exists
        if (!empty($systemPrompt)) {
            array_unshift($conversationHistory, ['role' => 'system', 'content' => $systemPrompt]);
        }

        $conversationHistory[] = ['role' => 'user', 'content' => $prompt];

        $response = $this->httpClient->request('POST', 'https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => $modelName,
                'messages' => $conversationHistory,
            ],
        ]);

        $responseData = json_decode($response->getBody()->getContents(), true);

        // Debug: Log the API response for anonymous users
        if (\Drupal::currentUser()->isAnonymous()) {
            \Drupal::logger('openai_integration')->notice('API response for anonymous user: ' . print_r($responseData, TRUE));
        }

        $assistantResponse = $responseData['choices'][0]['message']['content'] ?? "Response error or not found.";

        // Append the latest assistant answer to history, if applicable
        if (isset($responseData['choices'][0]['message'])) {
            $conversationHistory[] = ['role' => 'assistant', 'content' => $assistantResponse];
        }

        $this->saveConversationHistory($conversationHistory);

        return $assistantResponse;
    }

    protected function getConversationHistory() {
        // Debug: Log the conversation history retrieval for anonymous users
        if (\Drupal::currentUser()->isAnonymous()) {
            \Drupal::logger('openai_integration')->notice('Retrieving conversation history for anonymous user');
        }

        return $this->session->get('openai_conversation', []);
    }

    public function getConversationHistoryForForm() {
        return $this->getConversationHistory();
    }

    protected function saveConversationHistory(array $conversation) {
        // Debug: Log the conversation history saving for anonymous users
        if (\Drupal::currentUser()->isAnonymous()) {
            \Drupal::logger('openai_integration')->notice('Saving conversation history for anonymous user: ' . print_r($conversation, TRUE));
        }

        $this->session->set('openai_conversation', $conversation);
    }

    public function clearConversationHistory() {
        // Debug: Log the conversation history clearing for anonymous users
        if (\Drupal::currentUser()->isAnonymous()) {
            \Drupal::logger('openai_integration')->notice('Clearing conversation history for anonymous user');
        }

        $this->session->remove('openai_conversation');
    }
}