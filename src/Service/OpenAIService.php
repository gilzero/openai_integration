<?php
// filename: src/Service/OpenAIService.php
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
        $this->session->remove('openai_conversation');
    }
}