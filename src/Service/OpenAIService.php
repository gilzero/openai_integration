<?php
<<<<<<< HEAD
// filename: src/Service/OpenAIService.php
=======

>>>>>>> HEAD@{1}
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
<<<<<<< HEAD
        $conversationHistory = $this->getConversationHistory();
        $conversationHistory[] = ['role' => 'user', 'content' => $prompt];

        $response = $this->httpClient->request('POST', 'https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . getenv('OPENAI_API_KEY'),
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => 'gpt-3.5-turbo',
                'messages' => $conversationHistory,
            ],
        ]);

        $responseData = json_decode($response->getBody()->getContents(), true);
        $assistantResponse = $responseData['choices'][0]['message']['content'];
        $conversationHistory[] = ['role' => 'assistant', 'content' => $assistantResponse];
        $this->saveConversationHistory($conversationHistory);

=======
        $config = \Drupal::config('openai_integration.settings');
        $apiKey = $config->get('openai_api_key');
        $modelName = $config->get('model_name');
        $systemPrompt = $config->get('system_prompt');
    
        $conversationHistory = $this->getConversationHistory();
    
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
        $assistantResponse = $responseData['choices'][0]['message']['content'] ?? "Response error or not found.";
        
        // Append the latest assistant answer to history, if applicable
        if (isset($responseData['choices'][0]['message'])) {
            $conversationHistory[] = ['role' => 'assistant', 'content' => $assistantResponse];
        }
    
        $this->saveConversationHistory($conversationHistory);
>>>>>>> HEAD@{1}
        return $assistantResponse;
    }

    protected function getConversationHistory() {
        return $this->session->get('openai_conversation', []);
    }

    public function getConversationHistoryForForm() {
        return $this->getConversationHistory();
    }

    protected function saveConversationHistory(array $conversation) {
        $this->session->set('openai_conversation', $conversation);
    }

    public function clearConversationHistory() {
<<<<<<< HEAD
        $this->session->remove('openai_conversation');
=======
        $this->session->set('openai_conversation', []);
>>>>>>> HEAD@{1}
    }
}