<?php
// file: src/Service/OpenAIAPIClient.php

namespace Drupal\openai_integration\Service;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Implements an API client for communicating with the OpenAI API.
 */
class OpenAIAPIClient implements OpenAIAPIClientInterface {
    protected $httpClient;
    protected $apiKey;
    protected $baseUrl = 'https://api.openai.com/v1';
    protected $logger;

    /**
     * Constructor for the OpenAIAPIClient.
     * 
     * @param ClientInterface $httpClient
     *   The HTTP client to send requests.
     * @param ConfigFactoryInterface $configFactory
     *   The configuration factory to access module settings.
     * @param LoggerChannelFactoryInterface $loggerFactory
     *   The logger factory for logging messages.
     */
    public function __construct(ClientInterface $httpClient, ConfigFactoryInterface $configFactory, LoggerChannelFactoryInterface $loggerFactory) {
        $this->httpClient = $httpClient;
        $this->apiKey = $configFactory->get('openai_integration.settings')->get('openai_api_key');
        $this->logger = $loggerFactory->get('openai_integration');
    }

    /**
     * Sends a request to the specified OpenAI API method.
     *
     * @param string $methodName The API method to be called.
     * @param array $payload The data to be sent as the request body.
     * @param string $method The HTTP method to use for the request, defaults to 'POST'.
     * 
     * @return array The API response as an associative array.
     * 
     * @throws \RuntimeException If there is an error during the request.
     */
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
            $responseBody = json_decode($response->getBody()->getContents(), true);

            // Log successful request details
            $this->logger->info('API request successful', [
                'method' => $method,
                'url' => $this->baseUrl . $methodName,
                'response' => $responseBody  // Consider sanitizing if sensitive data is involved
            ]);
    
            return $responseBody;
        } catch (GuzzleException $e) {
            return $this->handleException($e, $method, $this->baseUrl . $methodName);
        }
    }

    /**
     * Handles exceptions during API requests.
     *
     * @param GuzzleException $e The caught exception.
     * @param string $method The HTTP method used.
     * @param string $url The URL of the API request.
     *
     * @throws \RuntimeException When rethrowing the exception with additional context.
     */
    protected function handleException(GuzzleException $e, $method, $url) {
        // Log specific errors with detailed context
        $this->logger->error('API request failed', [
            'method' => $method,
            'url' => $url,
            'error' => $e->getMessage(),
            'code' => $e->getCode()
        ]);

        // Here you can add logic to handle specific scenarios based on status codes or exception types
        // For example, retry logic, fallback mechanisms, or enhanced user messaging
        
        throw new \RuntimeException('Failed to communicate with OpenAI API.', 0, $e);
    }
}
