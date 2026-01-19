<?php
/**
 * Spaceship.com API Client for WHMCS
 *
 * A comprehensive API client for communicating with Spaceship.com's domain API.
 * Handles authentication, request/response processing, and async operations.
 *
 * @copyright 2026
 * @license MIT
 */

namespace WHMCS\Module\Registrar\Spaceship;

class ApiClient
{
    /**
     * Production API URL
     */
    const PRODUCTION_API_URL = 'https://spaceship.dev/api';

    /**
     * Test/Sandbox API URL
     */
    const SANDBOX_API_URL = 'https://spaceship.dev/api';

    /**
     * @var string API Key
     */
    protected $apiKey;

    /**
     * @var string API Secret
     */
    protected $apiSecret;

    /**
     * @var bool Test Mode enabled
     */
    protected $testMode;

    /**
     * @var array Last API response
     */
    protected $lastResponse = [];

    /**
     * @var array Last response headers
     */
    protected $lastHeaders = [];

    /**
     * @var string Last async operation ID
     */
    protected $lastAsyncOperationId = '';

    /**
     * @var int Request timeout in seconds
     */
    protected $timeout = 120;

    /**
     * Constructor.
     *
     * @param string $apiKey
     * @param string $apiSecret
     * @param bool $testMode
     */
    public function __construct($apiKey, $apiSecret, $testMode = false)
    {
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
        $this->testMode = (bool) $testMode;
    }

    /**
     * Get the API base URL.
     *
     * @return string
     */
    protected function getBaseUrl()
    {
        return $this->testMode ? self::SANDBOX_API_URL : self::PRODUCTION_API_URL;
    }

    /**
     * Make a GET request to the API.
     *
     * @param string $endpoint
     * @param array $queryParams
     * @return array
     * @throws \Exception
     */
    public function get($endpoint, $queryParams = [])
    {
        return $this->request('GET', $endpoint, [], $queryParams);
    }

    /**
     * Make a POST request to the API.
     *
     * @param string $endpoint
     * @param array $data
     * @return array
     * @throws \Exception
     */
    public function post($endpoint, $data = [])
    {
        return $this->request('POST', $endpoint, $data);
    }

    /**
     * Make a PUT request to the API.
     *
     * @param string $endpoint
     * @param array $data
     * @return array
     * @throws \Exception
     */
    public function put($endpoint, $data = [])
    {
        return $this->request('PUT', $endpoint, $data);
    }

    /**
     * Make a DELETE request to the API.
     *
     * @param string $endpoint
     * @param array $data
     * @return array
     * @throws \Exception
     */
    public function delete($endpoint, $data = [])
    {
        return $this->request('DELETE', $endpoint, $data);
    }

    /**
     * Make a PATCH request to the API.
     *
     * @param string $endpoint
     * @param array $data
     * @return array
     * @throws \Exception
     */
    public function patch($endpoint, $data = [])
    {
        return $this->request('PATCH', $endpoint, $data);
    }

    /**
     * Make an API request.
     *
     * @param string $method HTTP method
     * @param string $endpoint API endpoint
     * @param array $data Request body data
     * @param array $queryParams Query string parameters
     * @return array
     * @throws \Exception
     */
    protected function request($method, $endpoint, $data = [], $queryParams = [])
    {
        $url = $this->getBaseUrl() . $endpoint;

        // Add query parameters if any
        if (!empty($queryParams)) {
            $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($queryParams);
        }

        $ch = curl_init();

        $headers = [
            'X-Api-Key: ' . $this->apiKey,
            'X-Api-Secret: ' . $this->apiSecret,
            'Content-Type: application/json',
            'Accept: application/json',
        ];

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_HEADER, true);

        switch ($method) {
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                if (!empty($data)) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                }
                break;
            case 'PUT':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                if (!empty($data)) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                }
                break;
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                if (!empty($data)) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                }
                break;
            case 'PATCH':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
                if (!empty($data)) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                }
                break;
            case 'GET':
            default:
                curl_setopt($ch, CURLOPT_HTTPGET, true);
                break;
        }

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \Exception('Connection Error: ' . $error);
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

        curl_close($ch);

        // Separate headers and body
        $headerString = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);

        // Parse headers
        $this->lastHeaders = $this->parseHeaders($headerString);

        // Store async operation ID if present
        if (isset($this->lastHeaders['spaceship-async-operationid'])) {
            $this->lastAsyncOperationId = $this->lastHeaders['spaceship-async-operationid'];
        }

        // Parse response body
        $this->lastResponse = json_decode($body, true);

        // Log the module call
        $this->logModuleCall($method . ' ' . $endpoint, $data, $body, $this->lastResponse);

        // Handle HTTP errors
        if ($httpCode >= 400) {
            $errorMessage = $this->getErrorMessage($this->lastResponse, $httpCode);
            throw new \Exception($errorMessage);
        }

        return $this->lastResponse ?? [];
    }

    /**
     * Parse HTTP headers from response.
     *
     * @param string $headerString
     * @return array
     */
    protected function parseHeaders($headerString)
    {
        $headers = [];
        $lines = explode("\r\n", $headerString);

        foreach ($lines as $line) {
            if (strpos($line, ':') !== false) {
                list($key, $value) = explode(':', $line, 2);
                $headers[strtolower(trim($key))] = trim($value);
            }
        }

        return $headers;
    }

    /**
     * Get error message from response.
     *
     * @param array|null $response
     * @param int $httpCode
     * @return string
     */
    protected function getErrorMessage($response, $httpCode)
    {
        if (isset($response['detail'])) {
            return $response['detail'];
        }

        if (isset($response['title'])) {
            return $response['title'];
        }

        if (isset($response['message'])) {
            return $response['message'];
        }

        if (isset($response['error'])) {
            return $response['error'];
        }

        // Handle validation errors
        if (isset($response['errors']) && is_array($response['errors'])) {
            $errorMessages = [];
            foreach ($response['errors'] as $field => $errors) {
                if (is_array($errors)) {
                    $errorMessages[] = $field . ': ' . implode(', ', $errors);
                } else {
                    $errorMessages[] = $field . ': ' . $errors;
                }
            }
            if (!empty($errorMessages)) {
                return implode('; ', $errorMessages);
            }
        }

        // Default error messages based on HTTP code
        $defaultErrors = [
            400 => 'Bad Request',
            401 => 'Unauthorized - Please check your API credentials',
            403 => 'Forbidden - Insufficient permissions',
            404 => 'Resource not found',
            422 => 'Validation error',
            429 => 'Rate limit exceeded - Please try again later',
            500 => 'Internal server error',
        ];

        return $defaultErrors[$httpCode] ?? 'Unknown error (HTTP ' . $httpCode . ')';
    }

    /**
     * Get the last async operation ID.
     *
     * @return string
     */
    public function getAsyncOperationId()
    {
        return $this->lastAsyncOperationId;
    }

    /**
     * Wait for an async operation to complete.
     *
     * @param string $operationId
     * @param int $maxWaitSeconds Maximum time to wait
     * @param int $pollIntervalSeconds Time between polls
     * @return array Operation result
     * @throws \Exception
     */
    public function waitForAsyncOperation($operationId, $maxWaitSeconds = 120, $pollIntervalSeconds = 5)
    {
        $startTime = time();

        while ((time() - $startTime) < $maxWaitSeconds) {
            $result = $this->get("/v1/async-operations/{$operationId}");

            $status = $result['status'] ?? '';

            if ($status === 'success' || $status === 'failed') {
                return $result;
            }

            // Still pending, wait and retry
            sleep($pollIntervalSeconds);
        }

        throw new \Exception('Async operation timeout - operation did not complete within ' . $maxWaitSeconds . ' seconds');
    }

    /**
     * Check async operation status.
     *
     * @param string $operationId
     * @return array
     * @throws \Exception
     */
    public function getAsyncOperationStatus($operationId)
    {
        return $this->get("/v1/async-operations/{$operationId}");
    }

    /**
     * Get from last response.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getFromResponse($key, $default = '')
    {
        if (strpos($key, '.') !== false) {
            $keys = explode('.', $key);
            $value = $this->lastResponse;
            foreach ($keys as $k) {
                if (!isset($value[$k])) {
                    return $default;
                }
                $value = $value[$k];
            }
            return $value;
        }

        return isset($this->lastResponse[$key]) ? $this->lastResponse[$key] : $default;
    }

    /**
     * Get last response.
     *
     * @return array
     */
    public function getLastResponse()
    {
        return $this->lastResponse;
    }

    /**
     * Get last response headers.
     *
     * @return array
     */
    public function getLastHeaders()
    {
        return $this->lastHeaders;
    }

    /**
     * Log module call for WHMCS.
     *
     * @param string $action
     * @param mixed $request
     * @param mixed $response
     * @param mixed $processedResponse
     */
    protected function logModuleCall($action, $request, $response, $processedResponse)
    {
        // Mask sensitive data
        $sensitiveKeys = [
            $this->apiKey,
            $this->apiSecret,
        ];

        if (function_exists('logModuleCall')) {
            logModuleCall(
                'Spaceship',
                $action,
                $request,
                $response,
                $processedResponse,
                $sensitiveKeys
            );
        }
    }

    /**
     * Set request timeout.
     *
     * @param int $seconds
     * @return $this
     */
    public function setTimeout($seconds)
    {
        $this->timeout = (int) $seconds;
        return $this;
    }

    /**
     * Validate API credentials by making a test call.
     *
     * @return bool
     */
    public function validateCredentials()
    {
        try {
            $this->get('/v1/domains?take=1&skip=0');
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
