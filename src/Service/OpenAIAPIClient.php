<?php
// file: src/Service/OpenAIAPIClient.php

namespace Drupal\openai_integration\Service;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
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

    public function sendRequest($methodName, $payload, $method = 'POST') {
        try {
            $options = [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ];
            
            $response = $this->httpClient->request($method, $this->baseUrl . $methodName, $options);
            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            $this->handleException($e);
        }
    }

    protected function handleException(GuzzleException $e) {
        // Log specific errors based on the exception type or HTTP status codes
        $this->logger->error('API request to OpenAI failed: @error, Code: @code', [
            '@error' => $e->getMessage(),
            '@code' => $e->getCode()
        ]);

        // Here you can add logic to handle specific scenarios based on status codes or exception types
        // For example, retry logic, fallback mechanisms, or enhanced user messaging
        
        throw new \RuntimeException('Failed to communicate with OpenAI API.', 0, $e);
    }
}