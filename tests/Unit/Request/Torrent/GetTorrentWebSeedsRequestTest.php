<?php
declare(strict_types=1);

namespace Tests\Unit\Request\Torrent;

use PHPUnit\Framework\TestCase;
use PhpQbittorrent\Request\Torrent\GetTorrentWebSeedsRequest;
use PhpQbittorrent\Exception\ValidationException;

/**
 * GetTorrentWebSeedsRequest 单元测试
 */
class GetTorrentWebSeedsRequestTest extends TestCase
{
    public function testValidRequestCreation(): void
    {
        $hash = '8c212779b4abde7c6bc608063a0d008b7e40ce32';
        $request = GetTorrentWebSeedsRequest::create($hash);

        $this->assertEquals($hash, $request->getHash());
        $this->assertEquals('/torrents/webseeds', $request->getEndpoint());
        $this->assertEquals('GET', $request->getMethod());
        $this->assertTrue($request->validate()->isValid());
    }

    public function testRequestToArray(): void
    {
        $hash = '8c212779b4abde7c6bc608063a0d008b7e40ce32';
        $request = GetTorrentWebSeedsRequest::create($hash);

        $expected = ['hash' => $hash];
        $this->assertEquals($expected, $request->toArray());
    }

    public function testGetQueryString(): void
    {
        $hash = '8c212779b4abde7c6bc608063a0d008b7e40ce32';
        $request = GetTorrentWebSeedsRequest::create($hash);

        $queryString = $request->getQueryString();
        $this->assertEquals('hash=' . urlencode($hash), $queryString);
    }

    public function testGetFullUrl(): void
    {
        $hash = '8c212779b4abde7c6bc608063a0d008b7e40ce32';
        $request = GetTorrentWebSeedsRequest::create($hash);

        $fullUrl = $request->getFullUrl();
        $this->assertEquals('/torrents/webseeds?hash=' . urlencode($hash), $fullUrl);
    }

    public function testGetSummary(): void
    {
        $hash = '8c212779b4abde7c6bc608063a0d008b7e40ce32';
        $request = GetTorrentWebSeedsRequest::create($hash);

        $summary = $request->getSummary();

        $this->assertEquals($hash, $summary['hash']);
        $this->assertEquals(40, $summary['hash_length']);
        $this->assertTrue($summary['is_valid_hash']);
        $this->assertEquals('/torrents/webseeds', $summary['endpoint']);
        $this->assertEquals('GET', $summary['method']);
        $this->assertTrue($summary['requires_auth']);
        $this->assertEquals('hash=' . urlencode($hash), $summary['query_string']);
        $this->assertEquals('/torrents/webseeds?hash=' . urlencode($hash), $summary['full_url']);
    }

    public function testEmptyHashValidation(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('GetTorrentWebSeeds request validation failed');

        GetTorrentWebSeedsRequest::create('');
    }

    public function testShortHashValidation(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('GetTorrentWebSeeds request validation failed');

        GetTorrentWebSeedsRequest::create('short');
    }

    public function testLongHashValidation(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('GetTorrentWebSeeds request validation failed');

        $longHash = '8c212779b4abde7c6bc608063a0d008b7e40ce32extra';
        GetTorrentWebSeedsRequest::create($longHash);
    }

    public function testInvalidCharactersHashValidation(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('GetTorrentWebSeeds request validation failed');

        $invalidHash = '8c212779b4abde7c6bc608063a0d008b7e40ce3g'; // contains 'g'
        GetTorrentWebSeedsRequest::create($invalidHash);
    }

    public function testBuilderValidCreation(): void
    {
        $hash = '8c212779b4abde7c6bc608063a0d008b7e40ce32';
        $request = GetTorrentWebSeedsRequest::builder()
            ->hash($hash)
            ->build();

        $this->assertEquals($hash, $request->getHash());
        $this->assertTrue($request->validate()->isValid());
    }

    public function testBuilderMissingHash(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Missing required parameter: hash');

        GetTorrentWebSeedsRequest::builder()->build();
    }

    public function testBuilderValidate(): void
    {
        $builder = GetTorrentWebSeedsRequest::builder();
        $validation = $builder->validate();

        $this->assertFalse($validation->isValid());
        $this->assertArrayHasKey('hash', $validation->getErrors());
    }

    public function testRequiresAuthentication(): void
    {
        $request = GetTorrentWebSeedsRequest::create('8c212779b4abde7c6bc608063a0d008b7e40ce32');
        $this->assertTrue($request->requiresAuthentication());
    }

    /**
     * @dataProvider validHashProvider
     */
    public function testValidHashes(string $hash): void
    {
        $request = GetTorrentWebSeedsRequest::create($hash);

        $this->assertEquals($hash, $request->getHash());
        $this->assertTrue($request->validate()->isValid());
    }

    /**
     * @dataProvider invalidHashProvider
     */
    public function testInvalidHashes(string $hash): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('GetTorrentWebSeeds request validation failed');

        GetTorrentWebSeedsRequest::create($hash);
    }

    public static function validHashProvider(): array
    {
        return [
            'Lowercase hash' => ['8c212779b4abde7c6bc608063a0d008b7e40ce32'],
            'Uppercase hash' => ['8C212779B4ABDE7C6BC608063A0D008B7E40CE32'],
            'Mixed case hash' => ['8c212779b4abDe7c6BC608063a0d008b7e40CE32'],
            'Numeric hash' => ['0123456789abcdef0123456789abcdef01234567'],
        ];
    }

    public static function invalidHashProvider(): array
    {
        return [
            'Empty string' => [''],
            'Spaces only' => ['   '],
            'Too short' => ['8c212779b4abde7c6bc608063a0d008b7e40ce3'],
            'Too long' => ['8c212779b4abde7c6bc608063a0d008b7e40ce321'],
            'Invalid characters' => ['8c212779b4abde7c6bc608063a0d008b7e40ce3g'],
            'Special characters' => ['8c212779b4abde7c6bc608063a0d008b7e40ce3!'],
        ];
    }
}