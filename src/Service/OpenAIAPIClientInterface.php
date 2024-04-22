<?php
// File: src/Service/OpenAIAPIClientInterface.php

namespace Drupal\openai_integration\Service;

/**
 * Interface for OpenAI API Client.
 */
interface OpenAIAPIClientInterface {
    /**
     * Sends a request to the specified OpenAI API method.
     *
     * @param string $methodName The API method to be called.
     * @param array $payload The data to be sent as the request body.
     * @param string $method The HTTP method to use for the request, defaults to 'POST'.
     * 
     * @return array The API response as an associative array.
     * 
     * @throws \Exception If there is an error during the request.
     */
    public function sendRequest($methodName, array $payload, $method = 'POST');
}