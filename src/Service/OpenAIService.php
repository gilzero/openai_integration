<?php
// src/Service/OpenAIService.php
namespace Drupal\openai_integration\Service;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

class OpenAIService {
    protected $apiClient;
    protected $session;
    protected $logger;
    protected $configFactory;

    /**
     * Constructs an OpenAIService object.
     *
     * @param OpenAIAPIClient $apiClient The API client to interact with OpenAI.
     * @param SessionInterface $session The session to store conversation histories.
     * @param LoggerChannelFactoryInterface $loggerFactory The logger factory.
     * @param ConfigFactoryInterface $configFactory The configuration factory.
     */
    public function __construct(OpenAIAPIClient $apiClient, SessionInterface $session, LoggerChannelFactoryInterface $loggerFactory, ConfigFactoryInterface $configFactory) {
        $this->apiClient = $apiClient;
        $this->session = $session;
        $this->logger = $loggerFactory->get('openai_integration');
        $this->configFactory = $configFactory;
    }

    /**
     * Generate a response from OpenAI based on the user's input prompt.
     *
     * @param string $prompt The user's input prompt.
     * @return string The response text from OpenAI.
     */
    public function generateResponse($prompt) {
        $promptCleaned = htmlspecialchars($prompt, ENT_QUOTES, 'UTF-8');
        $conversationHistory = $this->getConversationHistory();
        $conversationHistory[] = ['role' => 'user', 'content' => $promptCleaned];

        $model = $this->configFactory->get('openai_integration.settings')->get('model_name');
        $payload = [
            'model' => $model,
            'messages' => $conversationHistory,
        ];

        try {
            $responseData = $this->apiClient->sendRequest('/chat/completions', $payload);
            $responseText = $responseData['choices'][0]['message']['content'] ?? 'No response content available.';

            $conversationHistory[] = ['role' => 'assistant', 'content' => $responseText];
            $this->saveConversationHistory($conversationHistory);

            return $responseText;
        } catch (\Exception $e) {
            $this->logger->error('Failed to generate response from OpenAI: @error', ['@error' => $e->getMessage()]);
            throw $e; // Rethrow the exception for handling upstream.
        }
    }

    /**
     * Retrieves the stored conversation history from the session.
     */
    public function getConversationHistory() {
        return $this->session->get('openai_conversation', []);
    }

    /**
     * Saves the given conversation history into the session.
     */
    protected function saveConversationHistory(array $conversation) {
        $this->session->set('openai_conversation', $conversation);
    }

    /**
     * Clears the stored conversation history from the session.
     */
    public function clearConversationHistory() {
        $this->session->remove('openai_conversation');
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