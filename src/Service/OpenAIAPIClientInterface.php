<?php
// File: src/Service/OpenAIAPIClientInterface.php

namespace Drupal\openai_integration\Service;

interface OpenAIAPIClientInterface {
  public function sendRequest($methodName, $payload);
}