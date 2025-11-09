<?php
declare(strict_types=1);

namespace PhpQbittorrent\Transport;

use PhpQbittorrent\Exception\ClientException;
use PhpQbittorrent\Exception\NetworkException;
use PhpQbittorrent\Exception\AuthenticationException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Nyholm\Psr7\Factory\Psr17Factory;

/**
 * cURL HTTP传输实现
 *
 * 基于cURL的PSR-18 HTTP客户端实现
 */
final class CurlTransport implements TransportInterface
{
    private string $baseUrl = '';
    private ?string $cookie = null;
    private float $timeout = 30.0;
    private float $connectTimeout = 10.0;
    private bool $verifySSL = true;
    private ?string $sslCertPath = null;
    private ?string $proxy = null;
    private ?string $proxyAuth = null;
    private string $userAgent = 'php-qbittorrent/1.0.0';
    private ?int $lastResponseCode = null;
    private ?string $lastError = null;

    private RequestFactoryInterface $requestFactory;
    private StreamFactoryInterface $streamFactory;

    public function __construct(
        ?RequestFactoryInterface $requestFactory = null,
        ?StreamFactoryInterface $streamFactory = null
    ) {
        $this->requestFactory = $requestFactory ?? new Psr17Factory();
        $this->streamFactory = $streamFactory ?? new Psr17Factory();
    }

    public function request(string $method, string $uri, array $options = []): array
    {
        $url = $this->buildUrl($uri);
        $request = $this->requestFactory->createRequest($method, $url);

        // 设置请求头
        if ($this->cookie) {
            $request = $request->withHeader('Cookie', $this->cookie);
        }

        $request = $request->withHeader('User-Agent', $this->userAgent);
        $request = $request->withHeader('Accept', 'application/json');

        // 处理请求体
        if (isset($options['json']) && is_array($options['json'])) {
            $request = $request
                ->withHeader('Content-Type', 'application/json')
                ->withBody($this->streamFactory->createStream(
                    json_encode($options['json'], JSON_UNESCAPED_UNICODE)
                ));
        } elseif (isset($options['form_params']) && is_array($options['form_params'])) {
            $request = $request
                ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
                ->withBody($this->streamFactory->createStream(
                    http_build_query($options['form_params'], '', '&')
                ));
        } elseif (isset($options['multipart']) && is_array($options['multipart'])) {
            // 处理multipart/form-data
            $boundary = uniqid('boundary_', true);
            $body = $this->buildMultipartBody($options['multipart'], $boundary);

            $request = $request
                ->withHeader('Content-Type', "multipart/form-data; boundary={$boundary}")
                ->withBody($this->streamFactory->createStream($body));
        } elseif (isset($options['body'])) {
            $request = $request->withBody($this->streamFactory->createStream($options['body']));
        }

        // 处理查询参数
        if (isset($options['query']) && is_array($options['query'])) {
            $queryString = http_build_query($options['query'], '', '&', PHP_QUERY_RFC3986);
            $uri = $request->getUri()->withQuery($queryString);
            $request = $request->withUri($uri);
        }

        $response = $this->sendRequest($request);
        return $this->parseResponse($response, (string) $request->getUri());
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $curl = curl_init();

        // 基础cURL选项
        $options = [
            CURLOPT_URL => (string) $request->getUri(),
            CURLOPT_CUSTOMREQUEST => $request->getMethod(),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => $this->connectTimeout,
            CURLOPT_USERAGENT => $this->userAgent,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_SSL_VERIFYPEER => $this->verifySSL,
            CURLOPT_SSL_VERIFYHOST => $this->verifySSL ? 2 : 0,
        ];

        // 设置请求头
        $headers = [];
        foreach ($request->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                $headers[] = "{$name}: {$value}";
            }
        }

        // 添加认证cookie
        if ($this->cookie) {
            $headers[] = "Cookie: {$this->cookie}";
        }

        $options[CURLOPT_HTTPHEADER] = $headers;

        // 设置请求体
        $body = $request->getBody();
        if ($body->getSize() > 0) {
            $options[CURLOPT_POSTFIELDS] = (string) $body;
        }

        // 设置SSL证书路径
        if ($this->sslCertPath) {
            $options[CURLOPT_CAINFO] = $this->sslCertPath;
        }

        // 设置代理
        if ($this->proxy) {
            $options[CURLOPT_PROXY] = $this->proxy;
            if ($this->proxyAuth) {
                $options[CURLOPT_PROXYUSERPWD] = $this->proxyAuth;
            }
        }

        curl_setopt_array($curl, $options);

        // 执行请求
        $responseBody = curl_exec($curl);
        $error = curl_error($curl);
        $errno = curl_errno($curl);
        $info = curl_getinfo($curl);

        $this->lastResponseCode = $info['http_code'] ?? null;

        curl_close($curl);

        // 处理cURL错误
        if ($errno !== CURLE_OK) {
            $this->lastError = $error;
            throw $this->createNetworkException($errno, $error, (string) $request->getUri(), $request->getMethod());
        }

        // 分离header和body
        if ($responseBody === false) {
            $responseBody = '';
        }

        $headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $responseHeaders = substr($responseBody, 0, $headerSize);
        $responseBody = substr($responseBody, $headerSize);

        // 处理cookie
        $this->handleCookies($responseHeaders);

        // 创建响应
        $factory = new Psr17Factory();
        $response = $factory->createResponse($this->lastResponseCode);

        // 解析响应头
        $this->parseHeaders($response, $responseHeaders);

        // 设置响应体
        $response = $response->withBody($factory->createStream($responseBody));

        return $response;
    }

    public function setBaseUrl(string $baseUrl): void
    {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function setAuthentication(?string $cookie): void
    {
        $this->cookie = $cookie;
    }

    public function getAuthentication(): ?string
    {
        return $this->cookie;
    }

    public function getLastResponseCode(): int
    {
        return $this->lastResponseCode ?? 0;
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    public function setTimeout(float $timeout): void
    {
        $this->timeout = $timeout;
    }

    public function setConnectTimeout(float $timeout): void
    {
        $this->connectTimeout = $timeout;
    }

    public function setUserAgent(string $userAgent): void
    {
        $this->userAgent = $userAgent;
    }

    public function setVerifySSL(bool $verify): void
    {
        $this->verifySSL = $verify;
    }

    public function setSSLCertPath(?string $path): void
    {
        $this->sslCertPath = $path;
    }

    public function setProxy(?string $proxy, ?string $auth = null): void
    {
        $this->proxy = $proxy;
        $this->proxyAuth = $auth;
    }

    public function close(): void
    {
        // cURL已经自动关闭，这里可以执行其他清理工作
        $this->lastError = null;
        $this->lastResponseCode = null;
    }

    /**
     * 构建完整URL
     */
    private function buildUrl(string $uri): string
    {
        if (str_starts_with($uri, 'http://') || str_starts_with($uri, 'https://')) {
            return $uri;
        }

        $baseUrl = $this->baseUrl;
        if (empty($baseUrl)) {
            throw new NetworkException('基础URL未设置', 'NO_BASE_URL');
        }

        $uri = ltrim($uri, '/');
        return "{$baseUrl}/{$uri}";
    }

    /**
     * 解析响应数据
     */
    private function parseResponse(ResponseInterface $response, string $uri = ''): array
    {
        $statusCode = $response->getStatusCode();
        $body = (string) $response->getBody();

        // 处理认证失败
        if ($statusCode === 401 || $statusCode === 403) {
            $uri = $this->getBaseUrl() ? $this->getBaseUrl() : 'unknown';
            throw AuthenticationException::accessDenied($uri);
        }

        // 处理客户端错误
        if ($statusCode >= 400) {
            throw new ClientException(
                "HTTP {$statusCode}: {$body}",
                'HTTP_ERROR',
                ['status_code' => $statusCode, 'body' => $body],
                $statusCode
            );
        }

        // 处理空响应
        if (empty($body)) {
            return [];
        }

        // 解析JSON响应
        $data = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            // 对于特定的端点，允许非JSON响应
            if (str_contains($uri, '/auth/login')) {
                // 登录端点可能返回非JSON内容，检查状态码
                if ($statusCode === 200) {
                    return [];
                }
            }

            // 对于版本端点和add torrent端点，允许纯文本响应
            if (str_contains($uri, '/app/version') ||
                str_contains($uri, '/app/webapiVersion') ||
                str_contains($uri, '/torrents/add')) {
                // 这些端点可能返回纯文本或空响应
                $trimmedBody = trim($body);
                return empty($trimmedBody) ? [] : [$trimmedBody];
            }

            throw new ClientException(
                "JSON解析失败: " . json_last_error_msg(),
                'JSON_PARSE_ERROR',
                [
                    'json_error' => json_last_error(),
                    'json_error_msg' => json_last_error_msg(),
                    'raw_response' => $body,
                    'uri' => $uri
                ]
            );
        }

        return $data;
    }

    /**
     * 处理响应中的Cookie
     */
    private function handleCookies(string $responseHeaders): void
    {
        $headers = explode("\r\n", $responseHeaders);
        foreach ($headers as $header) {
            if (str_starts_with(strtolower($header), 'set-cookie:')) {
                $cookieLine = substr($header, 11); // 移除 'Set-Cookie:'
                $parts = explode(';', $cookieLine);
                $cookiePart = trim($parts[0]);

                if (str_contains($cookiePart, '=')) {
                    list($name, $value) = explode('=', $cookiePart, 2);

                    // 如果是SID cookie，保存到认证信息中
                    if (trim($name) === 'SID') {
                        $this->cookie = trim($cookiePart);
                        break;
                    }
                }
            }
        }
    }

    /**
     * 解析响应头
     */
    private function parseHeaders(ResponseInterface $response, string $responseHeaders): void
    {
        $headers = explode("\r\n", $responseHeaders);
        $factory = new Psr17Factory();

        foreach ($headers as $headerLine) {
            if (empty($headerLine) || str_starts_with($headerLine, 'HTTP/')) {
                continue;
            }

            $parts = explode(':', $headerLine, 2);
            if (count($parts) === 2) {
                $name = trim($parts[0]);
                $value = trim($parts[1]);
                $response = $response->withHeader($name, $value);
            }
        }
    }

    /**
     * 创建网络异常
     */
    private function createNetworkException(int $errno, string $error, string $uri, string $method): NetworkException
    {
        // 超时错误
        if ($errno === CURLE_OPERATION_TIMEDOUT || $errno === CURLE_COULDNT_CONNECT) {
            return NetworkException::timeout($this->timeout, $uri, $method);
        }

        // SSL错误
        if ($errno === CURLE_SSL_CONNECT_ERROR ||
            $errno === CURLE_SSL_CERTPROBLEM ||
            $errno === CURLE_SSL_CIPHER) {
            return NetworkException::sslError($error, $uri);
        }

        // DNS解析失败
        if ($errno === CURLE_COULDNT_RESOLVE_HOST) {
            return NetworkException::dnsFailed(parse_url($uri, PHP_URL_HOST) ?: $uri, $uri);
        }

        // 连接失败
        return NetworkException::connectionFailed($uri, $method);
    }

    /**
     * 构建multipart/form-data请求体
     */
    private function buildMultipartBody(array $multipart, string $boundary): string
    {
        $body = '';

        foreach ($multipart as $part) {
            $body .= "--{$boundary}\r\n";

            if (isset($part['filename'])) {
                // 文件上传
                $body .= "Content-Disposition: form-data; name=\"{$part['name']}\"; filename=\"{$part['filename']}\"\r\n";
                $body .= "Content-Type: {$part['content_type']}\r\n\r\n";
                $body .= $part['contents'] . "\r\n";
            } else {
                // 普通字段
                $body .= "Content-Disposition: form-data; name=\"{$part['name']}\"\r\n\r\n";
                $body .= $part['contents'] . "\r\n";
            }
        }

        $body .= "--{$boundary}--\r\n";
        return $body;
    }
}