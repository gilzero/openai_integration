<?php
// src/Service/OpenAIService.php

namespace Drupal\openai_integration\Service;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\openai_integration\Service\OpenAIAPIClient;

class OpenAIService {
    protected $apiClient;
    protected $session;
    protected $logger;
    protected $configFactory;
    protected $messenger;

    const MAX_PROMPT_LENGTH = 4096; // Example limit, specify based on actual API constraints

    public function __construct(OpenAIAPIClient $apiClient, SessionInterface $session, LoggerChannelFactoryInterface $loggerFactory, ConfigFactoryInterface $configFactory, MessengerInterface $messenger) {
        $this->apiClient = $apiClient;
        $this->session = $session;
        $this->logger = $loggerFactory->get('openai_integration');
        $this->configFactory = $configFactory;
        $this->messenger = $messenger;
    }

    public function generateResponse($prompt) {
        $promptCleaned = strip_tags($prompt);
        $promptCleaned = htmlspecialchars($promptCleaned, ENT_QUOTES, 'UTF-8');
        
        if (strlen($promptCleaned) > self::MAX_PROMPT_LENGTH) {
            $this->messenger->addError('The prompt is too long. Please reduce the length and try again.');
            return null;
        }
    
        $conversationHistory = $this->getConversationHistory();
        $this->addSystemPrompt($conversationHistory);
        $conversationHistory[] = ['role' => 'user', 'content' => $promptCleaned];
    
        // Truncate conversation history if it exceeds a certain length
        $maxConversationLength = 10; // Adjust this value based on your requirements
        $conversationHistory = array_slice($conversationHistory, -$maxConversationLength);
    
        $this->saveConversationHistory($conversationHistory);
    
        $model = $this->configFactory->get('openai_integration.settings')->get('model_name');
        
        try {
            $responseData = $this->apiClient->sendRequest('/chat/completions', [
                'model' => $model,
                'messages' => $conversationHistory,
            ]);
    
            $responseText = $responseData['choices'][0]['message']['content'] ?? 'No response content available.';
            $conversationHistory[] = ['role' => 'assistant', 'content' => $responseText];
            $this->saveConversationHistory($conversationHistory);
            return $responseText;
        } catch (\Exception $e) {
            $this->handleResponseError($e);
        }
    
        return null;
    }

    public function getConversationHistory() {
        return $this->session->get('conversation_history', []);
    }

    public function saveConversationHistory(array $history) {
        $this->session->set('conversation_history', $history);
    }

    private function addSystemPrompt(&$conversationHistory) {
        $systemPrompt = $this->configFactory->get('openai_integration.settings')->get('system_prompt');
        if (!empty($systemPrompt)) {
            $conversationHistory[] = ['role' => 'system', 'content' => $systemPrompt];
        }
    }

    private function processResponse($model, $conversationHistory) {
        $responseData = $this->apiClient->sendRequest('/chat/completions', [
            'model' => $model,
            'messages' => $conversationHistory,
        ]);

        $responseText = $responseData['choices'][0]['message']['content'] ?? 'No response content available.';
        $conversationHistory[] = ['role' => 'assistant', 'content' => $responseText];
        $this->saveConversationHistory($conversationHistory);
        return $responseText;
    }

    private function handleResponseError(\Exception $e) {
        $this->logger->error('Failed to generate response from model: @error', ['@error' => $e->getMessage()]);
        $this->messenger->addError('Sorry, an error occurred while processing your request. Please try again.');
    }

    /**
     * Provides a list of available models.
     * This should query OpenAI to get the actual models but is hardcoded as an example.
     */
    public function getAvailableModels() {
        return [
            'gpt-3.5-turbo' => 'GPT 3.5',
            'gpt-4' => 'GPT 4',
            'gpt-4-turbo' => 'GPT 4 Turbo'
        ];
    }
}