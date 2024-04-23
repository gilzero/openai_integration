<?php
// file: src/Service/OpenAIService.php
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
    protected $model;  // Declare the model property explicitly.

    const MAX_PROMPT_LENGTH = 4096;
    const MAX_CONVERSATION_LENGTH = 10;

    public function __construct(OpenAIAPIClient $apiClient, SessionInterface $session, LoggerChannelFactoryInterface $loggerFactory, ConfigFactoryInterface $configFactory, MessengerInterface $messenger) {
        $this->apiClient = $apiClient;
        $this->session = $session;
        $this->logger = $loggerFactory->get('openai_integration');
        $this->configFactory = $configFactory;
        $this->messenger = $messenger;
        $this->model = $this->configFactory->get('openai_integration.settings')->get('model_name');
    }

    public function generateResponse($prompt) {
        if ($this->validatePrompt($prompt)) {
            $this->addToConversation('user', $prompt);
            $this->addSystemPrompt();
            return $this->fetchResponse();
        }
        return null;
    }

    private function validatePrompt(&$prompt) {
        $prompt = htmlspecialchars(strip_tags($prompt), ENT_QUOTES, 'UTF-8');
        if (strlen($prompt) > self::MAX_PROMPT_LENGTH) {
            $this->messenger->addError('The prompt is too long. Please reduce the length and try again.');
            return false;
        }
        return true;
    }

    private function addToConversation($role, $content) {
        $conversation = $this->getConversationHistory();
        $conversation[] = ['role' => $role, 'content' => $content];
        $conversation = array_slice($conversation, -self::MAX_CONVERSATION_LENGTH);
        $this->saveConversationHistory($conversation);
    }

    private function fetchResponse() {
        try {
            $conversation = $this->getConversationHistory();
            $response = $this->apiClient->sendRequest('/chat/completions', [
                'model' => $this->model,
                'messages' => $conversation,
            ]);
            $responseContent = $response['choices'][0]['message']['content'] ?? 'No response content available.';
            $this->addToConversation('assistant', $responseContent);
            return $responseContent;
        } catch (\Exception $e) {
            $this->handleResponseError($e);
        }
        return null;
    }

    private function addSystemPrompt() {
        $systemPrompt = $this->configFactory->get('openai_integration.settings')->get('system_prompt');
        if (!empty($systemPrompt)) {
            $this->addToConversation('system', $systemPrompt);
        }
    }

    private function handleResponseError(\Exception $e) {
        $this->logger->error('Failed to generate response from model: @error', ['@error' => $e->getMessage()]);
        $this->messenger->addError('Sorry, an error occurred while processing your request. Please try again.');
    }

    public function getConversationHistory() {
        return $this->session->get('conversation_history', []);
    }

    private function saveConversationHistory(array $history) {
        $this->session->set('conversation_history', $history);
    }

    public function getAvailableModels() {
        // This method would ideally query OpenAI or cache the information.
        return [
            'gpt-3.5-turbo' => 'GPT 3.5',
            'gpt-4' => 'GPT 4',
            'gpt-4-turbo' => 'GPT 4 Turbo',
        ];
    }
}