<?php
declare(strict_types=1);

namespace Tests\Unit\Request\Torrent;

use PHPUnit\Framework\TestCase;
use PhpQbittorrent\Request\Torrent\GetTorrentPieceStatesRequest;
use PhpQbittorrent\Exception\ValidationException;

/**
 * GetTorrentPieceStatesRequest 单元测试
 */
class GetTorrentPieceStatesRequestTest extends TestCase
{
    public function testValidRequestCreation(): void
    {
        $hash = '8c212779b4abde7c6bc608063a0d008b7e40ce32';
        $request = GetTorrentPieceStatesRequest::create($hash);

        $this->assertEquals($hash, $request->getHash());
        $this->assertEquals('/torrents/pieceStates', $request->getEndpoint());
        $this->assertEquals('GET', $request->getMethod());
        $this->assertTrue($request->validate()->isValid());
    }

    public function testRequestToArray(): void
    {
        $hash = '8c212779b4abde7c6bc608063a0d008b7e40ce32';
        $request = GetTorrentPieceStatesRequest::create($hash);

        $expected = ['hash' => $hash];
        $this->assertEquals($expected, $request->toArray());
    }

    public function testGetQueryString(): void
    {
        $hash = '8c212779b4abde7c6bc608063a0d008b7e40ce32';
        $request = GetTorrentPieceStatesRequest::create($hash);

        $queryString = $request->getQueryString();
        $this->assertEquals('hash=' . urlencode($hash), $queryString);
    }

    public function testGetFullUrl(): void
    {
        $hash = '8c212779b4abde7c6bc608063a0d008b7e40ce32';
        $request = GetTorrentPieceStatesRequest::create($hash);

        $fullUrl = $request->getFullUrl();
        $this->assertEquals('/torrents/pieceStates?hash=' . urlencode($hash), $fullUrl);
    }

    public function testGetSummary(): void
    {
        $hash = '8c212779b4abde7c6bc608063a0d008b7e40ce32';
        $request = GetTorrentPieceStatesRequest::create($hash);

        $summary = $request->getSummary();

        $this->assertEquals($hash, $summary['hash']);
        $this->assertEquals(40, $summary['hash_length']);
        $this->assertTrue($summary['is_valid_hash']);
        $this->assertEquals('/torrents/pieceStates', $summary['endpoint']);
        $this->assertEquals('GET', $summary['method']);
        $this->assertTrue($summary['requires_auth']);
        $this->assertEquals('hash=' . urlencode($hash), $summary['query_string']);
        $this->assertEquals('/torrents/pieceStates?hash=' . urlencode($hash), $summary['full_url']);
    }

    public function testEmptyHashValidation(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('GetTorrentPieceStates request validation failed');

        GetTorrentPieceStatesRequest::create('');
    }

    public function testShortHashValidation(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('GetTorrentPieceStates request validation failed');

        GetTorrentPieceStatesRequest::create('short');
    }

    public function testLongHashValidation(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('GetTorrentPieceStates request validation failed');

        $longHash = '8c212779b4abde7c6bc608063a0d008b7e40ce32extra';
        GetTorrentPieceStatesRequest::create($longHash);
    }

    public function testInvalidCharactersHashValidation(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('GetTorrentPieceStates request validation failed');

        $invalidHash = '8c212779b4abde7c6bc608063a0d008b7e40ce3g'; // contains 'g'
        GetTorrentPieceStatesRequest::create($invalidHash);
    }

    public function testWhitespaceHashValidation(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('GetTorrentPieceStates request validation failed');

        GetTorrentPieceStatesRequest::create('   ');
    }

    public function testBuilderValidCreation(): void
    {
        $hash = '8c212779b4abde7c6bc608063a0d008b7e40ce32';
        $request = GetTorrentPieceStatesRequest::builder()
            ->hash($hash)
            ->build();

        $this->assertEquals($hash, $request->getHash());
        $this->assertTrue($request->validate()->isValid());
    }

    public function testBuilderMissingHash(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('缺少必需参数: hash');

        GetTorrentPieceStatesRequest::builder()->build();
    }

    public function testBuilderValidate(): void
    {
        $builder = GetTorrentPieceStatesRequest::builder();
        $validation = $builder->validate();

        $this->assertFalse($validation->isValid());
        $this->assertArrayHasKey('Torrent哈希值是必需的', $validation->getErrors());
    }

    public function testRequiresAuthentication(): void
    {
        $request = GetTorrentPieceStatesRequest::create('8c212779b4abde7c6bc608063a0d008b7e40ce32');
        $this->assertTrue($request->requiresAuthentication());
    }

    /**
     * @dataProvider validHashProvider
     */
    public function testValidHashes(string $hash): void
    {
        $request = GetTorrentPieceStatesRequest::create($hash);

        $this->assertEquals($hash, $request->getHash());
        $this->assertTrue($request->validate()->isValid());
    }

    /**
     * @dataProvider invalidHashProvider
     */
    public function testInvalidHashes(string $hash): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('GetTorrentPieceStates request validation failed');

        GetTorrentPieceStatesRequest::create($hash);
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