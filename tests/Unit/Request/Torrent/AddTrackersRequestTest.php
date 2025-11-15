<?php
declare(strict_types=1);

namespace PhpQbittorrent\Tests\Unit\Request\Torrent;

use PHPUnit\Framework\TestCase;
use PhpQbittorrent\Request\Torrent\AddTrackersRequest;
use PhpQbittorrent\Request\Torrent\AddTrackersRequestBuilder;
use PhpQbittorrent\Exception\ValidationException;

/**
 * AddTrackersRequest 单元测试
 */
class AddTrackersRequestTest extends TestCase
{
    private const VALID_HASH = '123456789012345678901234567891234567890';
    private const VALID_URL_1 = 'https://tracker1.example.com/announce';
    private const VALID_URL_2 = 'https://tracker2.example.com/announce';
    private const VALID_URL_3 = 'udp://tracker3.example.com:6969/announce';

    /**
     * 测试使用工厂方法创建有效请求
     */
    public function testCreateValidRequest(): void
    {
        $urls = [self::VALID_URL_1, self::VALID_URL_2];
        $request = AddTrackersRequest::create(self::VALID_HASH, $urls);

        $this->assertSame(self::VALID_HASH, $request->getHash());
        $this->assertSame($urls, $request->getUrls());
        $this->assertTrue($request->validate()->isValid());
    }

    /**
     * 测试使用Builder创建有效请求
     */
    public function testBuilderCreatesValidRequest(): void
    {
        $request = AddTrackersRequest::builder()
            ->withHash(self::VALID_HASH)
            ->addUrl(self::VALID_URL_1)
            ->addUrl(self::VALID_URL_2)
            ->addUrl(self::VALID_URL_3)
            ->build();

        $this->assertSame(self::VALID_HASH, $request->getHash());
        $this->assertCount(3, $request->getUrls());
        $this->assertContains(self::VALID_URL_1, $request->getUrls());
        $this->assertContains(self::VALID_URL_2, $request->getUrls());
        $this->assertContains(self::VALID_URL_3, $request->getUrls());
    }

    /**
     * 测试Builder的withUrls方法
     */
    public function testBuilderWithUrlsMethod(): void
    {
        $urls = [self::VALID_URL_1, self::VALID_URL_2];
        $request = AddTrackersRequest::builder()
            ->withHash(self::VALID_HASH)
            ->withUrls($urls)
            ->build();

        $this->assertSame($urls, $request->getUrls());
    }

    /**
     * 测试请求转换为数组格式
     */
    public function testToArray(): void
    {
        $urls = [self::VALID_URL_1, self::VALID_URL_2];
        $request = AddTrackersRequest::create(self::VALID_HASH, $urls);

        $expected = [
            'hash' => self::VALID_HASH,
            'urls' => implode("\n", $urls)
        ];

        $this->assertSame($expected, $request->toArray());
    }

    /**
     * 测试空哈希验证
     */
    public function testEmptyHashValidation(): void
    {
        $this->expectException(ValidationException::class);
        AddTrackersRequest::create('', [self::VALID_URL_1]);
    }

    /**
     * 测试无效哈希长度验证
     */
    public function testInvalidHashLengthValidation(): void
    {
        $this->expectException(ValidationException::class);
        AddTrackersRequest::create('invalid_hash', [self::VALID_URL_1]);
    }

    /**
     * 测试无效哈希字符验证
     */
    public function testInvalidHashCharactersValidation(): void
    {
        $this->expectException(ValidationException::class);
        AddTrackersRequest::create('g123456789012345678901234567890123456789012g', [self::VALID_URL_1]);
    }

    /**
     * 测试空URL列表验证
     */
    public function testEmptyUrlsValidation(): void
    {
        $this->expectException(ValidationException::class);
        AddTrackersRequest::create(self::VALID_HASH, []);
    }

    /**
     * 测试空URL验证
     */
    public function testEmptyUrlValidation(): void
    {
        $this->expectException(ValidationException::class);
        AddTrackersRequest::create(self::VALID_HASH, [self::VALID_URL_1, '']);
    }

    /**
     * 测试URL数量限制验证
     */
    public function testTooManyUrlsValidation(): void
    {
        $this->expectException(ValidationException::class);

        $urls = [];
        for ($i = 0; $i < 101; $i++) {
            $urls[] = "https://tracker{$i}.example.com/announce";
        }

        AddTrackersRequest::create(self::VALID_HASH, $urls);
    }

    /**
     * 测试URL长度限制验证
     */
    public function testTooLongUrlValidation(): void
    {
        $this->expectException(ValidationException::class);

        $longUrl = str_repeat('https://tracker.example.com/', 300); // 超过2048字符
        AddTrackersRequest::create(self::VALID_HASH, [$longUrl]);
    }

    /**
     * 测试无效URL协议验证
     */
    public function testInvalidUrlProtocolValidation(): void
    {
        $this->expectException(ValidationException::class);
        AddTrackersRequest::create(self::VALID_HASH, ['ftp://invalid.example.com/announce']);
    }

    /**
     * 测试有效的HTTP URL
     */
    public function testValidHttpUrl(): void
    {
        $request = AddTrackersRequest::create(self::VALID_HASH, [self::VALID_URL_1]);
        $this->assertTrue($request->validate()->isValid());
    }

    /**
     * 测试有效的HTTPS URL
     */
    public function testValidHttpsUrl(): void
    {
        $request = AddTrackersRequest::create(self::VALID_HASH, [self::VALID_URL_2]);
        $this->assertTrue($request->validate()->isValid());
    }

    /**
     * 测试有效的UDP URL
     */
    public function testValidUdpUrl(): void
    {
        $request = AddTrackersRequest::create(self::VALID_HASH, [self::VALID_URL_3]);
        $this->assertTrue($request->validate()->isValid());
    }

    /**
     * 测试获取摘要信息
     */
    public function testGetSummary(): void
    {
        $urls = [self::VALID_URL_1, self::VALID_URL_2];
        $request = AddTrackersRequest::create(self::VALID_HASH, $urls);

        $summary = $request->getSummary();

        $this->assertSame(self::VALID_HASH, $summary['hash']);
        $this->assertSame(2, $summary['url_count']);
        $this->assertSame($urls, $summary['urls']);
        $this->assertSame('/addTrackers', $summary['endpoint']);
        $this->assertSame('POST', $summary['method']);
        $this->assertTrue($summary['requires_auth']);
    }

    /**
     * 测试Builder重置功能
     */
    public function testBuilderReset(): void
    {
        $builder = AddTrackersRequest::builder()
            ->withHash(self::VALID_HASH)
            ->addUrl(self::VALID_URL_1);

        $summaryBeforeReset = $builder->getSummary();
        $this->assertSame(self::VALID_HASH, $summaryBeforeReset['hash']);
        $this->assertCount(1, $summaryBeforeReset['urls']);

        $builder->reset();
        $summaryAfterReset = $builder->getSummary();
        $this->assertNull($summaryAfterReset['hash']);
        $this->assertCount(0, $summaryAfterReset['urls']);
    }

    /**
     * 测试Builder缺少必要参数
     */
    public function testBuilderMissingRequiredParameters(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        AddTrackersRequest::builder()
            ->withHash(self::VALID_HASH)
            ->build(); // 缺少URL
    }

    /**
     * 测试Builder缺少哈希
     */
    public function testBuilderMissingHash(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        AddTrackersRequest::builder()
            ->addUrl(self::VALID_URL_1)
            ->build(); // 缺少哈希
    }
}