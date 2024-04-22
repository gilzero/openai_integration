<?php
// file: src/Service/OpenAIAPIClient.php
namespace Drupal\openai_integration\Service;

use GuzzleHttp\ClientInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

class OpenAIAPIClient implements OpenAIAPIClientInterface {
  protected $httpClient;
  protected $apiKey;
  protected $baseUrl = 'https://api.openai.com/v1';
  protected $logger;

  public function __construct(ClientInterface $httpClient, ConfigFactoryInterface $configFactory, LoggerChannelFactoryInterface $loggerFactory) {
    $this->httpClient = $httpClient;
    $this->apiKey = $configFactory->get('openai_integration.settings')->get('openai_api_key');
    $this->logger = $loggerFactory->get('openai_integration');
  }

  public function sendRequest($methodName, $payload) {
    try {
      $response = $this->httpClient->request('POST', $this->baseUrl . $methodName, [
        'headers' => [
          'Authorization' => 'Bearer ' . $this->apiKey,
          'Content-Type' => 'application/json',
        ],
        'json' => $payload,
      ]);
      return json_decode($response->getBody()->getContents(), true);
    } catch (\Exception $e) {
      $this->logger->error('API Request failed: @error', ['@error' => $e->getMessage()]);
      throw $e;
    }
  }
}