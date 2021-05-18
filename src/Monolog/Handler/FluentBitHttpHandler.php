<?php

namespace Bagaswh\MonologFluentBit\Monolog\Handler;

use Exception;
use InvalidArgumentException;
use Monolog\Logger;
use Monolog\Handler\AbstractProcessingHandler;

/**
 * FluentBitHttpHandler
 * 
 * This handles sending log to Fluent Bit through HTTP input.
 */
final class FluentBitHttpHandler extends AbstractProcessingHandler
{
  private $curlHandle;
  private $targetHttpConfig = [];

  public function __construct(array $targetHttpConfig = [], $level = Logger::DEBUG, bool $bubble = true)
  {
    parent::__construct($level, $bubble);

    $this->targetHttpConfig = $targetHttpConfig;
  }

  public function setTargetHttpConfig(array $config = [])
  {
    $this->targetHttpConfig = $config;
  }

  private function getCurlHandle()
  {
    if (!$this->curlHandle) {
      $this->curlHandle = curl_init();
      $this->setupCurl($this->curlHandle);
    }
    return $this->curlHandle;
  }

  private function setupCurl($ch)
  {
    $url = $this->targetHttpConfig['url'];
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

    if (isset($this->targetHttpConfig['auth'])) {

      $auth = $this->targetHttpConfig['auth'];
      $type = $auth['type'];

      switch ($type) {
        case 'basic':
          $username = $auth['username'];
          $password = $auth['password'];
          curl_setopt($ch, CURLOPT_USERPWD, $username . ':' . $password);
          break;
        default:
          throw new InvalidArgumentException('Unsupported HTTP authentication type: ' . $type);
      }
    }
  }

  private function makeRequest(array $data)
  {
    $ch = $this->getCurlHandle();

    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    $result = curl_exec($ch);
    curl_close($ch);
    $this->curlHandle = null;

    return $result;
  }

  private function makeRequestAsync(array $data)
  {
    throw new Exception('Not implemented');
  }

  protected function write(array $record): void
  {
    $isAsync = false;
    if ($isAsync) {
      $this->makeRequestAsync($record);
    } else {
      $this->makeRequest($record);
    }
  }
}
