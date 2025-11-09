<?php
declare(strict_types=1);

namespace PhpQbittorrent\Tests\Unit\Config;

use PhpQbittorrent\Tests\TestCase;
use PhpQbittorrent\Config\ClientConfig;

/**
 * ClientConfig单元测试
 */
class ClientConfigTest extends TestCase
{
    public function testConstructorWithBasicParameters(): void
    {
        $url = 'http://localhost:8080';
        $username = 'admin';
        $password = 'admin123';

        $config = new ClientConfig($url, $username, $password);

        $this->assertEquals($url, $config->getUrl());
        $this->assertEquals($username, $config->getUsername());
        $this->assertEquals($password, $config->getPassword());
        $this->assertEquals(30.0, $config->getTimeout());
        $this->assertEquals(10.0, $config->getConnectTimeout());
        $this->assertTrue($config->isVerifySSL());
        $this->assertNull($config->getProxy());
        $this->assertNull($config->getProxyAuth());
    }

    public function testConstructorTrimsTrailingSlash(): void
    {
        $url = 'http://localhost:8080/';
        $config = new ClientConfig($url);

        $this->assertEquals('http://localhost:8080', $config->getUrl());
    }

    public function testFromArray(): void
    {
        $array = [
            'url' => 'https://example.com:9090',
            'username' => 'testuser',
            'password' => 'testpass',
            'timeout' => 45.5,
            'connect_timeout' => 15.0,
            'verify_ssl' => false,
            'ssl_cert_path' => '/path/to/cert.pem',
            'proxy' => 'http://proxy.example.com:8080',
            'proxy_auth' => 'user:pass',
            'user_agent' => 'custom-agent/1.0'
        ];

        $config = ClientConfig::fromArray($array);

        $this->assertEquals($array['url'], $config->getUrl());
        $this->assertEquals($array['username'], $config->getUsername());
        $this->assertEquals($array['password'], $config->getPassword());
        $this->assertEquals($array['timeout'], $config->getTimeout());
        $this->assertEquals($array['connect_timeout'], $config->getConnectTimeout());
        $this->assertEquals($array['verify_ssl'], $config->isVerifySSL());
        $this->assertEquals($array['ssl_cert_path'], $config->getSSLCertPath());
        $this->assertEquals($array['proxy'], $config->getProxy());
        $this->assertEquals($array['proxy_auth'], $config->getProxyAuth());
        $this->assertEquals($array['user_agent'], $config->getUserAgent());
    }

    public function testFromArrayWithMinimalData(): void
    {
        $array = ['url' => 'http://localhost:8080'];
        $config = ClientConfig::fromArray($array);

        $this->assertEquals($array['url'], $config->getUrl());
        $this->assertNull($config->getUsername());
        $this->assertNull($config->getPassword());
        $this->assertEquals(30.0, $config->getTimeout()); // 默认值
        $this->assertTrue($config->isVerifySSL()); // 默认值
    }

    public function testValidateValidConfig(): void
    {
        $config = new ClientConfig('http://localhost:8080');
        $this->assertTrue($config->validate());
        $this->assertEmpty($config->getErrors());
    }

    public function testValidateEmptyUrl(): void
    {
        $config = new ClientConfig('');
        $this->assertFalse($config->validate());
        $errors = $config->getErrors();
        $this->assertArrayHasKey('url', $errors);
        $this->assertEquals('URL不能为空', $errors['url']);
    }

    public function testValidateInvalidUrl(): void
    {
        $config = new ClientConfig('invalid-url');
        $this->assertFalse($config->validate());
        $errors = $config->getErrors();
        $this->assertArrayHasKey('url', $errors);
        $this->assertEquals('URL格式无效', $errors['url']);
    }

    public function testValidateNegativeTimeout(): void
    {
        $config = new ClientConfig('http://localhost:8080');
        $config->setTimeout(-1.0);
        $this->assertFalse($config->validate());
        $errors = $config->getErrors();
        $this->assertArrayHasKey('timeout', $errors);
        $this->assertEquals('超时时间必须大于0', $errors['timeout']);
    }

    public function testValidateZeroTimeout(): void
    {
        $config = new ClientConfig('http://localhost:8080');
        $config->setTimeout(0.0);
        $this->assertFalse($config->validate());
        $errors = $config->getErrors();
        $this->assertArrayHasKey('timeout', $errors);
    }

    public function testValidateNegativeConnectTimeout(): void
    {
        $config = new ClientConfig('http://localhost:8080');
        $config->setConnectTimeout(-1.0);
        $this->assertFalse($config->validate());
        $errors = $config->getErrors();
        $this->assertArrayHasKey('connect_timeout', $errors);
    }

    public function testValidateInvalidProxyUrl(): void
    {
        $config = new ClientConfig('http://localhost:8080');
        $config->setProxy('invalid-proxy-url');
        $this->assertFalse($config->validate());
        $errors = $config->getErrors();
        $this->assertArrayHasKey('proxy', $errors);
        $this->assertEquals('代理URL格式无效', $errors['proxy']);
    }

    public function testValidateWithNullProxy(): void
    {
        $config = new ClientConfig('http://localhost:8080');
        $config->setProxy(null);
        $this->assertTrue($config->validate());
    }

    public function testSetters(): void
    {
        $config = new ClientConfig('http://localhost:8080');

        // Test setUrl
        $newUrl = 'https://newhost:9090/';
        $config->setUrl($newUrl);
        $this->assertEquals('https://newhost:9090', $config->getUrl());

        // Test setUsername
        $config->setUsername('newuser');
        $this->assertEquals('newuser', $config->getUsername());

        // Test setPassword
        $config->setPassword('newpass');
        $this->assertEquals('newpass', $config->getPassword());

        // Test setTimeout
        $config->setTimeout(60.5);
        $this->assertEquals(60.5, $config->getTimeout());

        // Test setConnectTimeout
        $config->setConnectTimeout(20.5);
        $this->assertEquals(20.5, $config->getConnectTimeout());

        // Test setVerifySSL
        $config->setVerifySSL(false);
        $this->assertFalse($config->isVerifySSL());

        // Test setSSLCertPath
        $certPath = '/new/path/to/cert.pem';
        $config->setSSLCertPath($certPath);
        $this->assertEquals($certPath, $config->getSSLCertPath());

        // Test setProxy
        $config->setProxy('http://newproxy:3128', 'newuser:newpass');
        $this->assertEquals('http://newproxy:3128', $config->getProxy());
        $this->assertEquals('newuser:newpass', $config->getProxyAuth());

        // Test setUserAgent
        $config->setUserAgent('new-agent/2.0');
        $this->assertEquals('new-agent/2.0', $config->getUserAgent());
    }

    public function testHasCredentials(): void
    {
        $config1 = new ClientConfig('http://localhost:8080');
        $this->assertFalse($config1->hasCredentials());

        $config2 = new ClientConfig('http://localhost:8080', 'user');
        $this->assertFalse($config2->hasCredentials());

        $config3 = new ClientConfig('http://localhost:8080', null, 'pass');
        $this->assertFalse($config3->hasCredentials());

        $config4 = new ClientConfig('http://localhost:8080', 'user', 'pass');
        $this->assertTrue($config4->hasCredentials());
    }

    public function testToArray(): void
    {
        $config = new ClientConfig('https://example.com:9090', 'user', 'pass');
        $config->setTimeout(45.0);
        $config->setConnectTimeout(15.0);
        $config->setVerifySSL(false);
        $config->setSSLCertPath('/path/to/cert.pem');
        $config->setProxy('http://proxy:8080', 'proxyuser:proxypass');
        $config->setUserAgent('test-agent/1.0');

        $array = $config->toArray();

        $this->assertEquals([
            'url' => 'https://example.com:9090',
            'username' => 'user',
            'password' => 'pass',
            'timeout' => 45.0,
            'connect_timeout' => 15.0,
            'verify_ssl' => false,
            'ssl_cert_path' => '/path/to/cert.pem',
            'proxy' => 'http://proxy:8080',
            'proxy_auth' => 'proxyuser:proxypass',
            'user_agent' => 'test-agent/1.0',
        ], $array);
    }

    public function testDefaultUserAgent(): void
    {
        $config = new ClientConfig('http://localhost:8080');
        $this->assertEquals('php-qbittorrent/1.0.0', $config->getUserAgent());
    }

    public function testEmptyCredentials(): void
    {
        $config = new ClientConfig('http://localhost:8080', '', '');
        $this->assertNull($config->getUsername());
        $this->assertNull($config->getPassword());
        $this->assertFalse($config->hasCredentials());
    }

    /**
     * 测试边界值
     */
    public function testBoundaryValues(): void
    {
        $config = new ClientConfig('http://localhost:8080');

        // 测试极小的超时值
        $config->setTimeout(0.1);
        $this->assertTrue($config->validate());

        $config->setConnectTimeout(0.1);
        $this->assertTrue($config->validate());

        // 测试极大的超时值
        $config->setTimeout(9999.9);
        $this->assertTrue($config->validate());

        $config->setConnectTimeout(9999.9);
        $this->assertTrue($config->validate());
    }

    /**
     * 测试各种URL格式
     */
    public function testVariousUrlFormats(): void
    {
        $validUrls = [
            'http://localhost:8080',
            'https://example.com',
            'https://example.com:9090',
            'http://192.168.1.1:8080',
            'https://subdomain.example.com/path'
        ];

        foreach ($validUrls as $url) {
            $config = new ClientConfig($url);
            $this->assertTrue($config->validate(), "URL should be valid: {$url}");
        }

        $invalidUrls = [
            'ftp://example.com',
            'not-a-url',
            '',
            'http://',
            '://missing-protocol.com'
        ];

        foreach ($invalidUrls as $url) {
            $config = new ClientConfig($url);
            $this->assertFalse($config->validate(), "URL should be invalid: {$url}");
        }
    }
}