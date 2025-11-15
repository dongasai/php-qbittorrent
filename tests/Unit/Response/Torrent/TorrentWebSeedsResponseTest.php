<?php
declare(strict_types=1);

namespace Tests\Unit\Response\Torrent;

use PHPUnit\Framework\TestCase;
use PhpQbittorrent\Response\Torrent\TorrentWebSeedsResponse;
use PhpQbittorrent\Model\TorrentWebSeed;
use PhpQbittorrent\Exception\ResponseParseException;
use PhpQbittorrent\Exception\ValidationException;

/**
 * TorrentWebSeedsResponse 单元测试
 */
class TorrentWebSeedsResponseTest extends TestCase
{
    private string $testHash = '8c212779b4abde7c6bc608063a0d008b7e40ce32';

    public function testSuccessResponseCreation(): void
    {
        $webSeeds = [
            new TorrentWebSeed('https://example1.com/file.iso'),
            new TorrentWebSeed('https://example2.com/file.iso'),
        ];
        $rawResponse = ['status' => 200];
        $responseData = ['data' => [['url' => 'https://example1.com/file.iso'], ['url' => 'https://example2.com/file.iso']]];

        $response = TorrentWebSeedsResponse::success($webSeeds, $this->testHash, $rawResponse, $responseData);

        $this->assertEquals($webSeeds, $response->getWebSeeds());
        $this->assertEquals($this->testHash, $response->getTorrentHash());
        $this->assertEquals(2, $response->getCount());
        $this->assertTrue($response->hasWebSeeds());
        $this->assertTrue($response->validate()->isValid());
    }

    public function testErrorResponseCreation(): void
    {
        $rawResponse = ['status' => 404];
        $responseData = ['error' => 'Torrent not found'];

        $response = TorrentWebSeedsResponse::error($rawResponse, $responseData, 'Custom error message');

        $this->assertEquals([], $response->getWebSeeds());
        $this->assertEquals('', $response->getTorrentHash());
        $this->assertEquals(0, $response->getCount());
        $this->assertFalse($response->hasWebSeeds());
    }

    public function testFromApiResponseValid(): void
    {
        $apiResponse = [
            'data' => [
                ['url' => 'https://example1.com/file.iso'],
                ['url' => 'https://example2.com/file.iso'],
            ],
            'headers' => ['Content-Type' => 'application/json'],
            'status_code' => 200,
            'body' => '[{"url":"https://example1.com/file.iso"},{"url":"https://example2.com/file.iso"}]'
        ];

        $response = TorrentWebSeedsResponse::fromApiResponse($apiResponse, $this->testHash);

        $this->assertCount(2, $response->getWebSeeds());
        $this->assertEquals($this->testHash, $response->getTorrentHash());
        $this->assertTrue($response->hasWebSeeds());
        $this->assertEquals(2, $response->getCount());
    }

    public function testFromApiResponseEmpty(): void
    {
        $apiResponse = [
            'data' => [],
            'headers' => ['Content-Type' => 'application/json'],
            'status_code' => 200,
            'body' => '[]'
        ];

        $response = TorrentWebSeedsResponse::fromApiResponse($apiResponse, $this->testHash);

        $this->assertEquals([], $response->getWebSeeds());
        $this->assertEquals($this->testHash, $response->getTorrentHash());
        $this->assertEquals(0, $response->getCount());
        $this->assertFalse($response->hasWebSeeds());
    }

    public function testFromApiResponseMissingData(): void
    {
        $this->expectException(ResponseParseException::class);
        $this->expectExceptionMessage('Missing data field in API response');

        $apiResponse = [
            'headers' => ['Content-Type' => 'application/json'],
            'status_code' => 200
        ];

        TorrentWebSeedsResponse::fromApiResponse($apiResponse, $this->testHash);
    }

    public function testFromApiResponseInvalidData(): void
    {
        $this->expectException(ResponseParseException::class);
        $this->expectExceptionMessage('Response data must be an array');

        $apiResponse = [
            'data' => 'not an array',
            'headers' => ['Content-Type' => 'application/json'],
            'status_code' => 200
        ];

        TorrentWebSeedsResponse::fromApiResponse($apiResponse, $this->testHash);
    }

    public function testFromApiResponseInvalidWebSeedData(): void
    {
        $this->expectException(ResponseParseException::class);
        $this->expectExceptionMessageMatches('/Failed to parse web seed at index \d+/');

        $apiResponse = [
            'data' => [
                ['invalid' => 'data'],
            ],
            'headers' => ['Content-Type' => 'application/json'],
            'status_code' => 200
        ];

        TorrentWebSeedsResponse::fromApiResponse($apiResponse, $this->testHash);
    }

    public function testFindByUrl(): void
    {
        $webSeeds = [
            new TorrentWebSeed('https://example1.com/file.iso'),
            new TorrentWebSeed('https://example2.com/file.iso'),
            new TorrentWebSeed('https://example3.com/file.iso'),
        ];
        $response = TorrentWebSeedsResponse::success($webSeeds, $this->testHash, [], []);

        $found = $response->findByUrl('https://example2.com/file.iso');
        $notFound = $response->findByUrl('https://notfound.com/file.iso');

        $this->assertNotNull($found);
        $this->assertEquals('https://example2.com/file.iso', $found->getUrl());
        $this->assertNull($notFound);
    }

    public function testGetUrls(): void
    {
        $urls = [
            'https://example1.com/file.iso',
            'https://example2.com/file.iso',
            'https://example3.com/file.iso',
        ];
        $webSeeds = array_map(fn($url) => new TorrentWebSeed($url), $urls);
        $response = TorrentWebSeedsResponse::success($webSeeds, $this->testHash, [], []);

        $resultUrls = $response->getUrls();

        $this->assertEquals($urls, $resultUrls);
        $this->assertCount(3, $resultUrls);
    }

    public function testGetHttpWebSeeds(): void
    {
        $webSeeds = [
            new TorrentWebSeed('http://example1.com/file.iso'),
            new TorrentWebSeed('https://example2.com/file.iso'),
            new TorrentWebSeed('http://example3.com/file.iso'),
        ];
        $response = TorrentWebSeedsResponse::success($webSeeds, $this->testHash, [], []);

        $httpSeeds = $response->getHttpWebSeeds();

        $this->assertCount(2, $httpSeeds);
        $this->assertEquals('http://example1.com/file.iso', $httpSeeds[0]->getUrl());
        $this->assertEquals('http://example3.com/file.iso', $httpSeeds[1]->getUrl());
    }

    public function testGetHttpsWebSeeds(): void
    {
        $webSeeds = [
            new TorrentWebSeed('http://example1.com/file.iso'),
            new TorrentWebSeed('https://example2.com/file.iso'),
            new TorrentWebSeed('https://example3.com/file.iso'),
        ];
        $response = TorrentWebSeedsResponse::success($webSeeds, $this->testHash, [], []);

        $httpsSeeds = $response->getHttpsWebSeeds();

        $this->assertCount(2, $httpsSeeds);
        $this->assertEquals('https://example2.com/file.iso', $httpsSeeds[0]->getUrl());
        $this->assertEquals('https://example3.com/file.iso', $httpsSeeds[1]->getUrl());
    }

    public function testToArray(): void
    {
        $webSeeds = [
            new TorrentWebSeed('https://example1.com/file.iso'),
            new TorrentWebSeed('http://example2.com/file.iso'),
        ];
        $response = TorrentWebSeedsResponse::success($webSeeds, $this->testHash, [], []);

        $array = $response->toArray();

        $this->assertArrayHasKey('webseeds', $array);
        $this->assertArrayHasKey('torrent_hash', $array);
        $this->assertArrayHasKey('count', $array);
        $this->assertArrayHasKey('has_webseeds', $array);
        $this->assertArrayHasKey('urls', $array);
        $this->assertArrayHasKey('http_count', $array);
        $this->assertArrayHasKey('https_count', $array);

        $this->assertEquals(2, $array['count']);
        $this->assertTrue($array['has_webseeds']);
        $this->assertEquals(1, $array['http_count']);
        $this->assertEquals(1, $array['https_count']);
    }

    public function testGetSummary(): void
    {
        $webSeeds = [
            new TorrentWebSeed('https://example1.com/file.iso'),
            new TorrentWebSeed('http://example2.com/file.iso'),
        ];
        $response = TorrentWebSeedsResponse::success($webSeeds, $this->testHash, [], []);

        $summary = $response->getSummary();

        $this->assertArrayHasKey('torrent_hash', $summary);
        $this->assertArrayHasKey('webseeds_count', $summary);
        $this->assertArrayHasKey('has_webseeds', $summary);
        $this->assertArrayHasKey('http_count', $summary);
        $this->assertArrayHasKey('https_count', $summary);
        $this->assertArrayHasKey('response_valid', $summary);
        $this->assertArrayHasKey('response_data_size', $summary);

        $this->assertEquals($this->testHash, $summary['torrent_hash']);
        $this->assertEquals(2, $summary['webseeds_count']);
        $this->assertTrue($summary['has_webseeds']);
        $this->assertEquals(1, $summary['http_count']);
        $this->assertEquals(1, $summary['https_count']);
        $this->assertTrue($summary['response_valid']);
    }

    public function testValidationWithInvalidWebSeeds(): void
    {
        $webSeeds = [
            'invalid web seed', // not TorrentWebSeed instance
        ];
        $response = TorrentWebSeedsResponse::success($webSeeds, $this->testHash, [], []);

        $validation = $response->validate();
        $this->assertFalse($validation->isValid());
        $this->assertArrayHasKey('Web seed at index 0 is not a TorrentWebSeed instance', $validation->getErrors());
    }

    public function testValidationWithInvalidTorrentHash(): void
    {
        $webSeeds = [new TorrentWebSeed('https://example.com/file.iso')];
        $invalidHash = 'invalid_hash';

        $response = TorrentWebSeedsResponse::success($webSeeds, $invalidHash, [], []);

        $validation = $response->validate();
        $this->assertFalse($validation->isValid());
        $this->assertArrayHasKey('Torrent hash is invalid', $validation->getErrors());
    }

    /**
     * @dataProvider urlProvider
     */
    public function testProtocolFiltering(string $url, int $expectedHttp, int $expectedHttps): void
    {
        $webSeeds = [new TorrentWebSeed($url)];
        $response = TorrentWebSeedsResponse::success($webSeeds, $this->testHash, [], []);

        $httpCount = $response->getHttpWebSeeds();
        $httpsCount = $response->getHttpsWebSeeds();

        $this->assertEquals($expectedHttp, count($httpCount));
        $this->assertEquals($expectedHttps, count($httpsCount));
    }

    public static function urlProvider(): array
    {
        return [
            'HTTPS URL' => ['https://example.com/file.iso', 0, 1],
            'HTTP URL' => ['http://example.com/file.iso', 1, 0],
        ];
    }
}