<?php
declare(strict_types=1);

namespace Tests\Unit\Model;

use PHPUnit\Framework\TestCase;
use PhpQbittorrent\Model\TorrentWebSeed;
use PhpQbittorrent\Exception\ValidationException;

/**
 * TorrentWebSeed 单元测试
 */
class TorrentWebSeedTest extends TestCase
{
    public function testValidWebSeedCreation(): void
    {
        $url = 'https://example.com/webseed/file.iso';
        $webSeed = new TorrentWebSeed($url);

        $this->assertEquals($url, $webSeed->getUrl());
        $this->assertTrue($webSeed->validate()->isValid());
    }

    public function testWebSeedFromValidArray(): void
    {
        $data = ['url' => 'https://example.com/webseed/file.iso'];
        $webSeed = TorrentWebSeed::fromArray($data);

        $this->assertEquals('https://example.com/webseed/file.iso', $webSeed->getUrl());
        $this->assertTrue($webSeed->validate()->isValid());
    }

    public function testWebSeedToArray(): void
    {
        $url = 'https://example.com/webseed/file.iso';
        $webSeed = new TorrentWebSeed($url);

        $expected = ['url' => $url];
        $this->assertEquals($expected, $webSeed->toArray());
    }

    public function testWebSeedToJson(): void
    {
        $url = 'https://example.com/webseed/file.iso';
        $webSeed = new TorrentWebSeed($url);

        $json = $webSeed->toJson();
        $this->assertJson($json);

        $decoded = json_decode($json, true);
        $this->assertEquals(['url' => $url], $decoded);
    }

    public function testWebSeedToString(): void
    {
        $url = 'https://example.com/webseed/file.iso';
        $webSeed = new TorrentWebSeed($url);

        $this->assertEquals($url, (string)$webSeed);
    }

    public function testWebSeedEquals(): void
    {
        $url = 'https://example.com/webseed/file.iso';
        $webSeed1 = new TorrentWebSeed($url);
        $webSeed2 = new TorrentWebSeed($url);
        $webSeed3 = new TorrentWebSeed('https://different.com/file.iso');

        $this->assertTrue($webSeed1->equals($webSeed2));
        $this->assertFalse($webSeed1->equals($webSeed3));
    }

    public function testEmptyUrlValidation(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid TorrentWebSeed');

        new TorrentWebSeed('');
    }

    public function testInvalidUrlValidation(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid TorrentWebSeed');

        new TorrentWebSeed('not-a-valid-url');
    }

    public function testInvalidProtocolValidation(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid TorrentWebSeed');

        new TorrentWebSeed('ftp://example.com/file.iso');
    }

    public function testUrlTooLongValidation(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid TorrentWebSeed');

        $longUrl = 'https://example.com/' . str_repeat('a', 2050);
        new TorrentWebSeed($longUrl);
    }

    public function testSetValidUrl(): void
    {
        $webSeed = new TorrentWebSeed('https://example.com/old.iso');
        $newUrl = 'https://example.com/new.iso';

        $result = $webSeed->setUrl($newUrl);

        $this->assertSame($webSeed, $result);
        $this->assertEquals($newUrl, $webSeed->getUrl());
        $this->assertTrue($webSeed->validate()->isValid());
    }

    public function testSetInvalidUrl(): void
    {
        $webSeed = new TorrentWebSeed('https://example.com/old.iso');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid URL for TorrentWebSeed');

        $webSeed->setUrl('not-a-valid-url');
    }

    public function testGetSummary(): void
    {
        $url = 'https://user:pass@example.com:8080/path/file.iso?query=1#fragment';
        $webSeed = new TorrentWebSeed($url);

        $summary = $webSeed->getSummary();

        $this->assertEquals($url, $summary['url']);
        $this->assertEquals('https', $summary['scheme']);
        $this->assertEquals('example.com', $summary['host']);
        $this->assertEquals(8080, $summary['port']);
        $this->assertEquals('/path/file.iso', $summary['path']);
        $this->assertEquals(strlen($url), $summary['length']);
        $this->assertTrue($summary['is_valid']);
    }

    public function testFromArrayMissingUrl(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Missing required field: url');

        TorrentWebSeed::fromArray(['not_url' => 'value']);
    }

    public function testFromArrayNonStringUrl(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Field "url" must be a string');

        TorrentWebSeed::fromArray(['url' => 123]);
    }

    /**
     * @dataProvider validUrlProvider
     */
    public function testValidUrls(string $url): void
    {
        $webSeed = new TorrentWebSeed($url);

        $this->assertEquals($url, $webSeed->getUrl());
        $this->assertTrue($webSeed->validate()->isValid());
    }

    /**
     * @dataProvider invalidUrlProvider
     */
    public function testInvalidUrls(string $url): void
    {
        $this->expectException(ValidationException::class);
        new TorrentWebSeed($url);
    }

    public static function validUrlProvider(): array
    {
        return [
            'HTTPS URL' => ['https://example.com/file.iso'],
            'HTTP URL' => ['http://example.com/file.iso'],
            'URL with port' => ['https://example.com:8080/file.iso'],
            'URL with path' => ['https://example.com/path/to/file.iso'],
            'URL with query' => ['https://example.com/file.iso?version=1'],
        ];
    }

    public static function invalidUrlProvider(): array
    {
        return [
            'Empty string' => [''],
            'Spaces only' => ['   '],
            'Missing protocol' => ['example.com/file.iso'],
            'FTP protocol' => ['ftp://example.com/file.iso'],
            'File protocol' => ['file:///path/to/file.iso'],
            'Invalid format' => ['not-a-url'],
        ];
    }
}