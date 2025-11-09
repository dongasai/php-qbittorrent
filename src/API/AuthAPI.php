<?php
declare(strict_types=1);

namespace PhpQbittorrent\API;

use PhpQbittorrent\Transport\TransportInterface;
use PhpQbittorrent\Exception\{
    AuthenticationException,
    NetworkException,
    ClientException
};

/**
 * 认证API类
 *
 * 处理qBittorrent的登录、登出和认证相关操作
 */
final class AuthAPI
{
    private TransportInterface $transport;

    public function __construct(TransportInterface $transport)
    {
        $this->transport = $transport;
    }

    /**
     * 登录qBittorrent
     *
     * @param string $username 用户名
     * @param string $password 密码
     * @return bool 登录是否成功
     * @throws AuthenticationException 认证失败
     * @throws NetworkException 网络错误
     */
    public function login(string $username, string $password): bool
    {
        try {
            $response = $this->transport->request('POST', '/api/v2/auth/login', [
                'form_params' => [
                    'username' => $username,
                    'password' => $password
                ]
            ]);

            // qBittorrent登录成功时返回空数组
            if (empty($response)) {
                // 检查是否有Set-Cookie头（这里简化处理）
                $lastResponseCode = $this->transport->getLastResponseCode();

                if ($lastResponseCode === 200) {
                    // 在实际实现中，应该从响应头中提取SID cookie
                    // 这里暂时返回true，实际的cookie管理在传输层处理
                    return true;
                } else {
                    throw new AuthenticationException(
                        '登录失败：服务器返回错误状态码',
                        'LOGIN_FAILED',
                        ['status_code' => $lastResponseCode]
                    );
                }
            }

            throw new AuthenticationException(
                '登录失败：意外的服务器响应',
                'UNEXPECTED_RESPONSE'
            );

        } catch (AuthenticationException $e) {
            throw $e;
        } catch (NetworkException $e) {
            throw new NetworkException(
                '登录失败：网络连接错误',
                'NETWORK_ERROR',
                [],
                'POST',
                '/api/v2/auth/login'
            );
        } catch (ClientException $e) {
            if ($e->getHttpStatusCode() === 403) {
                throw new AuthenticationException(
                    '登录失败：IP地址被禁止访问',
                    'IP_BLOCKED',
                    [],
                    null,
                    '检查qBittorrent的安全设置或尝试更换IP地址'
                );
            }

            if ($e->getHttpStatusCode() === 401) {
                throw new AuthenticationException(
                    '登录失败：用户名或密码错误',
                    'INVALID_CREDENTIALS'
                );
            }

            throw new AuthenticationException(
                '登录失败：' . $e->getMessage(),
                'LOGIN_ERROR',
                $e->getErrorDetails()
            );
        }
    }

    /**
     * 登出qBittorrent
     *
     * @return bool 登出是否成功
     * @throws ClientException 登出失败
     */
    public function logout(): bool
    {
        try {
            $this->transport->request('POST', '/api/v2/auth/logout');
            return true;

        } catch (ClientException $e) {
            // 即使登出失败，也不应该抛出严重异常
            // 因为可能只是会话已经过期
            return false;
        }
    }

    /**
     * 检查认证状态
     *
     * @return bool 是否已认证
     */
    public function isLoggedIn(): bool
    {
        try {
            // 尝试访问一个需要认证的API来检查登录状态
            $this->transport->request('GET', '/api/v2/torrents/info');
            return true;
        } catch (ClientException $e) {
            if ($e->getHttpStatusCode() === 401 || $e->getHttpStatusCode() === 403) {
                return false;
            }
            // 其他错误可能是网络问题，不应认为是未认证
            return true;
        }
    }

    /**
     * 获取当前认证的Cookie值
     *
     * @return string|null Cookie值
     */
    public function getAuthCookie(): ?string
    {
        return $this->transport->getAuthentication();
    }

    /**
     * 设置认证Cookie
     *
     * @param string|null $cookie Cookie值
     */
    public function setAuthCookie(?string $cookie): void
    {
        $this->transport->setAuthentication($cookie);
    }

    /**
     * 验证认证Cookie是否有效
     *
     * @param string $cookie Cookie值
     * @return bool 是否有效
     */
    public function validateCookie(string $cookie): bool
    {
        try {
            $originalCookie = $this->getAuthCookie();
            $this->setAuthCookie($cookie);

            $isValid = $this->isLoggedIn();
            $this->setAuthCookie($originalCookie);

            return $isValid;
        } catch (ClientException $e) {
            return false;
        }
    }

    /**
     * 延长会话有效期
     *
     * 在某些qBittorrent配置中，会话可能需要定期刷新
     *
     * @return bool 是否成功延长会话
     */
    public function refreshSession(): bool
    {
        try {
            // 通过简单的API调用来刷新会话
            $this->transport->request('GET', '/api/v2/app/version');
            return true;
        } catch (ClientException $e) {
            return false;
        }
    }

    /**
     * 获取当前登录用户信息（如果qBittorrent支持）
     *
     * 注意：标准的qBittorrent API不提供用户信息
     * 这个方法适用于某些修改版本或未来版本
     *
     * @return array 用户信息
     */
    public function getCurrentUser(): array
    {
        try {
            return $this->transport->request('GET', '/api/v2/auth/user');
        } catch (ClientException $e) {
            // 如果API不存在，返回基本信息
            return [
                'authenticated' => $this->isLoggedIn(),
                'cookie_set' => $this->getAuthCookie() !== null
            ];
        }
    }

    /**
     * 测试连接到qBittorrent服务器
     *
     * @return bool 是否可以连接
     */
    public function testConnection(): bool
    {
        try {
            $this->transport->request('GET', '/api/v2/app/webapiVersion');
            return true;
        } catch (ClientException $e) {
            return false;
        }
    }

    /**
     * 获取服务器支持的方法列表
     *
     * @return array 支持的API端点
     */
    public function getSupportedMethods(): array
    {
        $standardEndpoints = [
            'login' => 'POST /api/v2/auth/login',
            'logout' => 'POST /api/v2/auth/logout',
            'application_version' => 'GET /api/v2/app/version',
            'webapi_version' => 'GET /api/v2/app/webapiVersion',
            'torrents_info' => 'GET /api/v2/torrents/info',
            'torrents_add' => 'POST /api/v2/torrents/add',
            'transfer_info' => 'GET /api/v2/transfer/info',
        ];

        // 检查哪些端点是实际可用的
        $supported = [];
        $originalAuth = $this->getAuthCookie();

        foreach ($standardEndpoints as $method => $endpoint) {
            try {
                // 对于需要认证的端点，跳过检查
                if (in_array($method, ['torrents_info', 'torrents_add', 'transfer_info'])) {
                    continue;
                }

                $this->transport->request($method, $endpoint);
                $supported[$method] = $endpoint;
            } catch (ClientException $e) {
                // 端点不可用
            }
        }

        $this->setAuthCookie($originalAuth);

        return $supported;
    }
}