<?php
declare(strict_types=1);

namespace Dongasai\qBittorrent\API\v2;

use Dongasai\qBittorrent\Contract\ApiInterface;
use Dongasai\qBittorrent\Contract\TransportInterface;
use Dongasai\qBittorrent\Contract\TransportResponse;
use Dongasai\qBittorrent\Request\Auth\LoginRequest;
use Dongasai\qBittorrent\Request\Auth\LogoutRequest;
use Dongasai\qBittorrent\Response\Auth\LoginResponse;
use Dongasai\qBittorrent\Response\Auth\LogoutResponse;
use Dongasai\qBittorrent\Exception\NetworkException;
use Dongasai\qBittorrent\Exception\ApiRuntimeException;
use Dongasai\qBittorrent\Exception\ValidationException;

/**
 * 认证API v2
 *
 * 提供用户登录、登出和认证状态管理功能
 */
class AuthAPI implements ApiInterface
{
    /** @var TransportInterface 传输层实例 */
    private TransportInterface $transport;

    /** @var string|null 当前会话ID */
    private ?string $currentSessionId = null;

    /** @var string|null 当前用户名 */
    private ?string $currentUsername = null;

    /** @var int|null 会话过期时间 */
    private ?int $sessionExpiresAt = null;

    /**
     * 构造函数
     *
     * @param TransportInterface $transport 传输层实例
     */
    public function __construct(TransportInterface $transport)
    {
        $this->transport = $transport;
    }

    /**
     * 获取API的基础路径
     *
     * @return string API基础路径
     */
    public function getBasePath(): string
    {
        return '/api/v2/auth';
    }

    /**
     * 获取传输层实例
     *
     * @return TransportInterface 传输层实例
     */
    public function getTransport(): TransportInterface
    {
        return $this->transport;
    }

    /**
     * 设置传输层实例
     *
     * @param TransportInterface $transport 传输层实例
     * @return static 返回自身以支持链式调用
     */
    public function setTransport(TransportInterface $transport): static
    {
        $this->transport = $transport;
        return $this;
    }

    /**
     * 执行GET请求
     *
     * @param string $endpoint API端点
     * @param array<string, mixed> $parameters 请求参数
     * @param array<string, string> $headers 请求头
     * @return \Dongasai\qBittorrent\Contract\ResponseInterface 响应对象
     */
    public function get(string $endpoint, array $parameters = [], array $headers = []): \Dongasai\qBittorrent\Contract\ResponseInterface
    {
        $url = $this->getBasePath() . $endpoint;
        $transportResponse = $this->transport->get($url, $parameters, $headers);
        return $this->createGenericResponse($transportResponse);
    }

    /**
     * 执行POST请求
     *
     * @param string $endpoint API端点
     * @param array<string, mixed> $data 请求数据
     * @param array<string, string> $headers 请求头
     * @return \Dongasai\qBittorrent\Contract\ResponseInterface 响应对象
     */
    public function post(string $endpoint, array $data = [], array $headers = []): \Dongasai\qBittorrent\Contract\ResponseInterface
    {
        $url = $this->getBasePath() . $endpoint;
        $transportResponse = $this->transport->post($url, $data, $headers);
        return $this->createGenericResponse($transportResponse);
    }

    /**
     * 用户登录
     *
     * @param LoginRequest $request 登录请求对象
     * @return LoginResponse 登录响应对象
     * @throws NetworkException 网络异常
     * @throws ValidationException 验证异常
     * @throws ApiRuntimeException API运行时异常
     */
    public function login(LoginRequest $request): LoginResponse
    {
        // 验证请求
        $validation = $request->validate();
        if (!$validation->isValid()) {
            throw ValidationException::fromValidationResult(
                $validation,
                'Login request validation failed'
            );
        }

        try {
            // 发送登录请求
            $url = $this->getBasePath() . $request->getEndpoint();
            $transportResponse = $this->transport->post(
                $url,
                $request->toArray(),
                $request->getHeaders()
            );

            // 处理响应
            return $this->handleLoginResponse($transportResponse, $request);

        } catch (NetworkException $e) {
            throw new ApiRuntimeException(
                'Login failed due to network error: ' . $e->getMessage(),
                'LOGIN_NETWORK_ERROR',
                ['original_error' => $e->getMessage()],
                $url,
                'POST',
                null,
                ['request_summary' => $request->getSummary()],
                $e
            );
        }
    }

    /**
     * 用户登出
     *
     * @param LogoutRequest $request 登出请求对象
     * @return LogoutResponse 登出响应对象
     * @throws NetworkException 网络异常
     * @throws ValidationException 验证异常
     * @throws ApiRuntimeException API运行时异常
     */
    public function logout(LogoutRequest $request): LogoutResponse
    {
        // 验证请求
        $validation = $request->validate();
        if (!$validation->isValid()) {
            throw ValidationException::fromValidationResult(
                $validation,
                'Logout request validation failed'
            );
        }

        try {
            // 发送登出请求
            $url = $this->getBasePath() . $request->getEndpoint();
            $transportResponse = $this->transport->post(
                $url,
                $request->toArray(),
                $request->getHeaders()
            );

            // 处理响应
            return $this->handleLogoutResponse($transportResponse, $request);

        } catch (NetworkException $e) {
            throw new ApiRuntimeException(
                'Logout failed due to network error: ' . $e->getMessage(),
                'LOGOUT_NETWORK_ERROR',
                ['original_error' => $e->getMessage()],
                $url,
                'POST',
                null,
                ['request_summary' => $request->getSummary()],
                $e
            );
        }
    }

    /**
     * 检查登录状态
     *
     * @return bool 是否已登录
     */
    public function isLoggedIn(): bool
    {
        try {
            // 通过访问应用版本API来检查登录状态
            $url = $this->transport->getBaseUrl() . '/api/v2/app/version';
            $response = $this->transport->get($url);
            return $response->getStatusCode() === 200;
        } catch (NetworkException $e) {
            return false;
        }
    }

    /**
     * 获取当前会话信息
     *
     * @return array<string, mixed> 会话信息
     */
    public function getCurrentSessionInfo(): array
    {
        return [
            'session_id' => $this->currentSessionId,
            'username' => $this->currentUsername,
            'expires_at' => $this->sessionExpiresAt,
            'is_logged_in' => $this->isLoggedIn(),
            'remaining_time' => $this->getRemainingSessionTime(),
        ];
    }

    /**
     * 获取剩余会话时间
     *
     * @return int|null 剩余时间（秒）
     */
    public function getRemainingSessionTime(): ?int
    {
        if ($this->sessionExpiresAt === null) {
            return null;
        }

        $remaining = $this->sessionExpiresAt - time();
        return max(0, $remaining);
    }

    /**
     * 检查会话是否过期
     *
     * @return bool 是否过期
     */
    public function isSessionExpired(): bool
    {
        if ($this->sessionExpiresAt === null) {
            return false;
        }

        return time() > $this->sessionExpiresAt;
    }

    /**
     * 清除本地会话状态
     *
     * @return void
     */
    public function clearLocalSession(): void
    {
        $this->currentSessionId = null;
        $this->currentUsername = null;
        $this->sessionExpiresAt = null;
    }

    /**
     * 处理登录响应
     *
     * @param TransportResponse $transportResponse 传输响应
     * @param LoginRequest $request 登录请求
     * @return LoginResponse 登录响应
     */
    private function handleLoginResponse(TransportResponse $transportResponse, LoginRequest $request): LoginResponse
    {
        $statusCode = $transportResponse->getStatusCode();
        $headers = $transportResponse->getHeaders();
        $rawResponse = $transportResponse->getBody();

        if ($statusCode === 200) {
            // 登录成功
            $sessionId = $this->extractSessionId($headers);
            if ($sessionId === null) {
                return LoginResponse::failure(
                    ['无法从响应中提取会话ID'],
                    $headers,
                    $statusCode,
                    $rawResponse
                );
            }

            // 更新本地会话状态
            $this->currentSessionId = $sessionId;
            $this->currentUsername = $request->getUsername();
            // 设置默认的会话过期时间（24小时）
            $this->sessionExpiresAt = time() + 86400;

            // 创建用户信息
            $userInfo = [
                'username' => $request->getUsername(),
                'login_time' => time(),
                'login_method' => 'password',
            ];

            return LoginResponse::success(
                $sessionId,
                $headers,
                $statusCode,
                $rawResponse,
                $userInfo
            );

        } elseif ($statusCode === 403) {
            // IP被禁止
            return LoginResponse::failure(
                ['用户IP因登录失败次数过多而被禁止访问'],
                $headers,
                $statusCode,
                $rawResponse
            );

        } elseif ($statusCode === 401) {
            // 认证失败
            return LoginResponse::failure(
                ['用户名或密码错误'],
                $headers,
                $statusCode,
                $rawResponse
            );

        } else {
            // 其他错误
            return LoginResponse::failure(
                ["登录失败，状态码: {$statusCode}"],
                $headers,
                $statusCode,
                $rawResponse
            );
        }
    }

    /**
     * 处理登出响应
     *
     * @param TransportResponse $transportResponse 传输响应
     * @param LogoutRequest $request 登出请求
     * @return LogoutResponse 登出响应
     */
    private function handleLogoutResponse(TransportResponse $transportResponse, LogoutRequest $request): LogoutResponse
    {
        $statusCode = $transportResponse->getStatusCode();
        $headers = $transportResponse->getHeaders();
        $rawResponse = $transportResponse->getBody();

        if ($statusCode === 200) {
            // 登出成功，清除本地会话状态
            $this->clearLocalSession();

            return LogoutResponse::success(
                $headers,
                $statusCode,
                $rawResponse,
                true, // sessionCleared
                $request->shouldClearAllSessions()
            );

        } else {
            // 登出失败
            return LogoutResponse::failure(
                ["登出失败，状态码: {$statusCode}"],
                $headers,
                $statusCode,
                $rawResponse
            );
        }
    }

    /**
     * 从响应头中提取会话ID
     *
     * @param array<string, string> $headers 响应头
     * @return string|null 会话ID
     */
    private function extractSessionId(array $headers): ?string
    {
        if (isset($headers['Set-Cookie'])) {
            // 匹配SID=后面的内容，直到分号或字符串结束
            if (preg_match('/SID=([^;]+)/', $headers['Set-Cookie'], $matches)) {
                return trim($matches[1]);
            }
        }

        return null;
    }

    /**
     * 创建通用响应对象
     *
     * @param TransportResponse $transportResponse 传输响应
     * @return \Dongasai\qBittorrent\Contract\ResponseInterface 响应对象
     */
    private function createGenericResponse(TransportResponse $transportResponse): \Dongasai\qBittorrent\Contract\ResponseInterface
    {
        // 这里可以创建一个通用的响应对象
        // 为了简化，我们创建一个简单的响应数组
        return new class($transportResponse) implements \Dongasai\qBittorrent\Contract\ResponseInterface {
            private TransportResponse $response;
            private array $data;

            public function __construct(TransportResponse $response)
            {
                $this->response = $response;
                $this->data = $response->getJson() ?? [];
            }

            public static function fromArray(array $data): static
            {
                // 实现逻辑
                return new self(new class($data) implements TransportResponse {
                    private array $data;
                    public function __construct(array $data) { $this->data = $data; }
                    public function getStatusCode(): int { return 200; }
                    public function getHeaders(): array { return []; }
                    public function getBody(): string { return ''; }
                    public function getJson(): ?array { return $this->data; }
                    public function isSuccess(int ...$acceptableCodes): bool { return true; }
                    public function isJson(): bool { return true; }
                    public function getHeader(string $name): ?string { return null; }
                });
            }

            public function isSuccess(): bool { return $this->response->isSuccess(); }
            public function getErrors(): array { return []; }
            public function getData(): mixed { return $this->data; }
            public function getStatusCode(): int { return $this->response->getStatusCode(); }
            public function getHeaders(): array { return $this->response->getHeaders(); }
            public function getRawResponse(): string { return $this->response->getBody(); }
            public function toArray(): array { return $this->data; }
            public function jsonSerialize(): array { return $this->data; }
        };
    }
}